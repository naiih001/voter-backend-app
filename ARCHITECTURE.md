# UniVote EVS — Architecture Overview

> Electronic Voting System ("UniVote EVS"). A Laravel 13 JSON API paired with a
> dependency-free, build-free single-page application (SPA) front end. This
> document describes how the system is structured, how a request flows through
> it, and where the important design decisions live.

---

## 1. At a glance

| Concern            | Choice                                                        |
|--------------------|---------------------------------------------------------------|
| Language           | PHP 8.4                                                       |
| Framework          | Laravel 13 (skeleton app)                                     |
| Database           | PostgreSQL (`voter_backend`)                                  |
| Auth mechanism     | Laravel Sanctum personal-access tokens (Bearer)               |
| Backend surface    | Stateless JSON REST API under `/api/*`                        |
| Front end          | Vanilla ES-module SPA, **no build step**                      |
| Frontend transport | Native `fetch` + History API router                           |
| State store        | `localStorage` (token + cached user)                          |
| Styling           | Hand-written CSS (`public/css/styles.css`)                    |
| Tests              | PHPUnit 12 (Feature + Unit)                                   |
| Queue / cache      | `sync` queue, `file` cache/session (local dev defaults)       |

The project is split cleanly into **two deployable surfaces that share one
origin**:

1. The **Blade shell** (`resources/views/app.blade.php`) served by a catch-all
   web route — a single HTML page that loads the SPA bundle from `public/`.
2. The **JSON API** mounted at `/api` (and `/api/auth`) that the SPA talks to.

---

## 2. High-level architecture

```
                         ┌───────────────────────────────┐
    Browser  ──────────▶ │  Blade shell (app.blade.php)  │
                         │  #app-root, #app-nav, #app... │
                         └───────────────┬───────────────┘
                                         │ fetches
                                         ▼
                         ┌───────────────────────────────┐
                         │  Vanilla JS SPA (public/js)   │
                         │  router → views → core/*       │
                         └───────────────┬───────────────┘
                  Bearer token + JSON    │
                         ┌───────────────┴───────────────┐
                         ▼                               ▼
                 /{path?} catch-all              /api/*  (routes/api.php)
                 (web.php → view('app'))         (routes/auth.php included)
                                                         │
                                        ┌────────────────┴───────────────┐
                                        ▼                                ▼
                         Sanctum auth:sanctum            admin/verified middleware
                                        │
                         ┌──────────────┴───────────────────────────────┐
                         ▼                                              ▼
                 Controllers (HTTP layer)                    Eloquent Models
                 - thin request/response                      - relationships, casts,
                 - validation                                 - scopes (open/active)
                 - transactions (VoteController)              - enums (Role, Status)
                                        │
                                        ▼
                         PostgreSQL (migrations, FKs,
                         UNIQUE(position_id, voter_id),
                         restrict/cascade delete rules)
```

The API and the SPA are **decoupled by contract (JSON + Bearer token)**, not by
framework. The Blade shell exists only to deliver the first HTML payload and the
`csrf-token` meta tag; everything interactive is client-side.

---

## 3. Request lifecycle

### 3.1 SPA boot
1. `public/index.php` → Laravel kernel → `web.php` catch-all returns
   `view('app')`.
2. `app.blade.php` emits the shell: nav, `<main id="app-root">`, modal/toast
   containers, and `<script type="module" src="js/app.js">`.
3. `public/js/app.js` calls `initRouter()`, which renders the route matching
   `location.pathname`.

### 3.2 API call
1. SPA view calls `api.get/post/put/del(...)` (`core/api.js`).
2. `api` attaches `Authorization: Bearer <token>` (from `localStorage`) and the
   CSRF meta token, then `fetch('/api/...')`.
3. Laravel matches the route in `routes/api.php` (auth routes come from the
   `require __DIR__.'/auth.php'` at the top).
4. Global `auth:sanctum` middleware (plus `admin`/`verified` aliases where
   declared) authenticates and authorizes.
5. Controller validates, performs work (possibly in a `DB::transaction`), and
   returns JSON.
6. `bootstrap/app.php` is configured to render JSON exceptions for any
   `api/*` request, so errors come back as JSON, not HTML.

### 3.3 Front-end guards
`core/router.js` enforces three guard modes per route — `guest`, `user`,
`admin`. Before rendering a view it checks the token and the cached `role`
(from `store.js`); mismatches redirect (`/login`, `/dashboard`, or the
role-appropriate home). The back end re-enforces these same rules via the
`admin` and `auth:sanctum` middleware, so the front-end guards are UX, not
security.

---

## 4. Backend structure

```
app/
  Enums/
    Role.php            # voter | admin   (backed enum, model-cast target)
    ElectionStatus.php  # draft | open | closed
  Http/
    Controllers/
      Controller.php            # base (empty)
      ElectionController.php
      PositionController.php
      CandidateController.php
      VoteController.php        # contains the core integrity logic
      UserController.php
      AuditLogController.php
      Auth/  (Breeze-style auth controllers)
    Middleware/
      EnsureUserIsAdmin.php     # alias: 'admin'
      EnsureEmailIsVerified.php # alias: 'verified'
    Requests/Auth/LoginRequest.php
  Models/
    User, Election, Position, Candidate, Vote, AuditLog
  Providers/AppServiceProvider.php
```

Routing files: `routes/api.php` (main surface + `require auth.php`),
`routes/web.php` (SPA catch-all), `routes/console.php`.

Middleware is registered in `bootstrap/app.php`:
- `Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful` is
  **prepended** to the API middleware stack (stateful Sanctum for first-party
  SPA usage).
- Aliases: `verified → EnsureEmailIsVerified`, `admin → EnsureUserIsAdmin`.
- Exceptions: `shouldRenderJsonWhen($request->is('api/*'))`.

### Layering notes
- **Controllers are the business layer** for this app. They own validation,
  authorization-by-role, and — in the case of `VoteController` — transactions.
  There is no separate Service/Action/Repository layer yet; logic lives in the
  controllers.
- **Models** are thin Eloquent classes: relationships, attribute `casts`
  (enums + `is_eligible` bool + hashed password), and two query scopes on
  `Election` (`open()`, `active()`).

---

## 5. Data model

```
User (voter | admin)
  ├─ matric_number  UNIQUE (voters only; nullable for admins)
  ├─ role           Enum   DEFAULT voter
  ├─ is_eligible    bool   DEFAULT true
  ├─ hasMany Vote (as voter)
  ├─ hasMany Candidate
  ├─ hasMany Election (created_by)
  └─ hasMany AuditLog

Election  (SoftDeletes)
  ├─ status         Enum   DEFAULT draft
  ├─ start_time / end_time
  ├─ created_by     FK → users
  ├─ hasMany Position
  ├─ hasMany Vote
  └─ belongsTo User (creator)

Position
  ├─ election_id    FK → elections  (CASCADE on delete)
  ├─ hasMany Candidate
  └─ hasMany Vote

Candidate
  ├─ position_id    FK → positions  (CASCADE on delete)
  ├─ user_id        FK → users, NULLABLE (RESTRICT on delete)
  ├─ name, photo_path, manifesto
  └─ hasMany Vote

Vote  (immutable; no Eloquent timestamps)
  ├─ election_id    FK → elections   (RESTRICT)
  ├─ position_id    FK → positions   (RESTRICT)
  ├─ candidate_id   FK → candidates  (RESTRICT)
  ├─ voter_id       FK → users       (RESTRICT)
  └─ UNIQUE (position_id, voter_id)   ← the core integrity guarantee

AuditLog
  ├─ user_id  FK → users, NULLABLE
  └─ action   string
```

Key relationships and delete semantics (from the migrations):

| FK | On delete | Rationale |
|----|-----------|-----------|
| `positions.election_id` | `cascadeOnDelete()` | Removing an election prunes its positions. |
| `candidates.position_id` | `cascadeOnDelete()` | Removing a position prunes its candidates. |
| `candidates.user_id` | `restrictOnDelete()` | Don't delete a user that is a candidate. |
| `votes.*` (all four) | `restrictOnDelete()` | Votes are immutable history; never cascade-deleted. |

Vote also adds `index(['election_id','position_id'])` because PostgreSQL does
not auto-index foreign keys (unlike MySQL/InnoDB) and result queries group by
these columns.

---

## 6. Voting integrity — the important part

The whole system's trustworthiness rests on one rule: **one vote per voter per
position**, enforced by the database — not by app logic alone.

`votes` carries `UNIQUE(position_id, voter_id)`. In `VoteController::store()`
casting a vote is wrapped in `DB::transaction` and does, in order:

1. Validate `position_id` / `candidate_id` exist.
2. Reject ineligible voters (`is_eligible === false`).
3. Confirm the candidate belongs to the submitted `position_id` (prevents
   stuffing a vote for a candidate from a different position).
4. Confirm the election is **active** — `status === open` **and**
   `start_time <= now() <= end_time`.
5. Inside the transaction, `lockForUpdate()` the voter's existing vote row for
   that position; if it exists → reject ("already voted").
6. Insert the `Vote` row.

Because the unique index is the law, two near-simultaneous double-submits
resolve safely: one insert wins, the other hits the constraint (or the row
lock) and is cleanly rejected. The application-level pre-check is UX; the
constraint is the guarantee. `Election::scopeActive()` is the reusable
expression of "open + inside the time window".

`Vote` has `$timestamps = false` and a single nullable `created_at`, reflecting
that a ballot is an immutable event with no update time.

---

## 7. Authentication & authorization

**Authentication** — Laravel Sanctum personal-access tokens.
- `POST /api/register` (supports both roles; voters require a unique
  `matric_number`), `POST /api/login`, `POST /api/logout`.
- Email verification endpoints (`/verify-email/{id}/{hash}`,
  `/email/verification-notification`) follow the Breeze pattern.
- Password reset (`/forgot-password`, `/reset-password`) included.
- On success, the client stores the plain-text token in `localStorage`
  (`uv_token`) and the user object (`uv_user`); `api.headers` attaches the
  Bearer token and the CSRF meta token to every request.

**Authorization** — two layers:
- `admin` middleware (`EnsureUserIsAdmin`) gatekeeps every mutation route and
  the admin read endpoints in `api.php`. It checks `$user->isAdmin()`
  (`role === Role::ADMIN`).
- `verified` middleware (`EnsureEmailIsVerified`) is defined and available but
  is **not currently applied** to any route in `api.php` (see §10).
- The SPA independently hides admin nav links and redirects non-admins away
  from `/admin/*` via router guards — convenience only.

---

## 8. Election lifecycle

`ElectionStatus` is `draft → open → closed`. Elections are created in `draft`
(by an admin) and flipped to `open` via `PUT /api/elections/{id}`. The
`ElectionController`:
- `index()` — voters see only `open()->active()` elections (with positions
  count); admins see all.
- `show()` — loads positions with their candidates.
- `results()` — aggregates `votes_count` per candidate per position (via
  Eloquent `withCount`), returning per-position tallies. No winner is
  persisted; results are computed on demand.
- `destroy()` — **soft delete** (preserves voting history).

---

## 9. Audit logging

`AuditLog` records administrative mutations. `ElectionController` and
`UserController` (admin actions) write a row on create/update/delete, e.g.
`"Created election: …"`, `"Updated user: …"`. `user_id` is nullable to allow
system actions. `AuditLogController::index()` exposes them to admins with
filtering by `user_id`/`action` and 50-per-page pagination.

> **Observation:** `VoteController::store()` does **not** write an audit row,
> even though the design notes (`study/`) call for a `"cast vote"` entry. Vote
> casting is currently unaudited at the application level (the vote row itself
> is the audit trail). See §10.

---

## 10. Observations, gaps & improvement opportunities

These are factual notes about the current state, not required changes:

1. **Vote audit gap.** The `study/implementation-plan.md` specifies a `"cast
   vote"` audit entry written inside the same transaction as the vote. The
   shipped `VoteController::store()` omits it. Consider adding it for a complete
   trail.
2. **`verified` middleware unused.** `EnsureEmailIsVerified` is registered but
   no `api.php` route applies it, so unverified users can vote and administer.
   Apply `verified` where email verification is meant to be enforced.
3. **No dedicated service layer.** Vote-casting integrity logic lives directly
   in `VoteController`. As complexity grows, extracting a `VoteService` would
   improve testability and reuse (e.g., reusing it from console/scheduler
   commands).
4. **Admin role creation is open.** `POST /api/register` accepts `role: admin`
   from anyone. If admins should be provisioned out-of-band, restrict this.
5. **`DatabaseSeeder` is minimal.** It only creates a "Test User" (a voter).
   The `study/` plan calls for a demo seeder (1 admin, ~30 voters, an open
   election, positions, candidates, hundreds of votes). That richer seeder is
   not yet present, so the app is not auto-demoable out of the box.
6. **Synchronous queue.** `QUEUE_CONNECTION=sync` and the `composer dev`
   script runs `queue:listen`, but no jobs are dispatched. Email verification
   (`MAIL_MAILER=log`) and any future jobs run inline. Fine for local dev.
7. **Frontend has no build pipeline.** `package.json` `dev`/`build` are stubs;
   ES modules are served straight from `public/js`. This keeps things simple
   but means no bundling/minification; fine for an internal tool, revisit if
   the bundle grows.

---

## 11. Configuration & environment

- `.env` drives everything: `DB_CONNECTION=pgsql` (host/port/db/user/pass),
  `SANCTUM_STATEFUL_DOMAINS=localhost`, `SESSION_DOMAIN=localhost`,
  `CACHE_STORE=file`, `SESSION_DRIVER=file`, `QUEUE_CONNECTION=sync`,
  `MAIL_MAILER=log`.
- `config/database.php` was adjusted for PostgreSQL (SQLite references removed
  per the latest commit).
- `composer setup` script: install → copy `.env` → `key:generate` →
  `migrate --force`.

---

## 12. Testing

PHPUnit 12 with the standard `tests/Feature` + `tests/Unit` split and a base
`tests/TestCase`. Feature tests exist per domain: `AuthTest`, `ElectionTest`,
`PositionTest`, `CandidateTest`, `VoteTest`, `UserTest`, `AuditLogTest`. Models
have corresponding factories (`database/factories/*`). Run with
`composer test` (clears config, then `artisan test`). There is also a
standalone `scripts/test-routes.sh` shell script for route smoke-testing.

---

## 13. Frontend SPA structure (reference)

```
public/js/
  app.js                 # entry → initRouter()
  core/
    api.js               # fetch client, token + CSRF headers, 401 redirect
    store.js             # user/token state in localStorage, isAdmin()
    router.js            # History-API router, route table, guest/user/admin guards
    ui.js                # DOM helpers (el/escapeHtml), alerts, toasts, modals,
                         #   form fields, nav/footer, date formatting
  views/
    auth.js  dashboard.js  elections.js  candidates.js  vote.js  profile.js  notfound.js
    admin/  dashboard, elections, results, positions, candidates, users, audit
```

Design characteristics:
- **No framework.** Views are functions `(params, root) => Promise` that build
  DOM via the `ui.el()` helper (with built-in HTML escaping and SVG support).
- **Single source of truth for data access** is `core/api.js`; for auth state,
  `core/store.js`.
- **Navigation** is intercepted globally: same-origin `<a>` clicks call
  `navigate()` (History API) instead of full reloads; `popstate` re-renders.
- **Styling** is one hand-written `styles.css` (nav, cards, forms, modals,
  toasts, responsive layout).
```
