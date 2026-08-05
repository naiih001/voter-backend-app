# Implementation Plan — How to Handle the Schema

Stack context: fresh Laravel 13 skeleton, PostgreSQL (`pgsql`, database `voter_db`), no auth package installed yet, only the stock migrations and a `/api/health` route.

## Overall read

The schema is already the boring, correct shape for a voting app. The line that does the real work is `UNIQUE (position_id, voter_id)` on `votes` — the anti-double-vote guarantee, enforced by the database, not by app logic. Everything else is structure around it. The implementation job is mostly discipline: transactional vote insert, DB-level uniqueness, restrict-on-delete for vote FKs, and status gating at the service layer.

## Migrations — one per table, with the right delete behavior

**users**: extend the stock Laravel migration. Add `matric_number` (unique), `role` (enum, default `voter`), `is_eligible` (bool, default true). Driver is Postgres, and Laravel's `$table->enum(...)` maps to varchar + a check constraint there — no MySQL/ENUM migration issues. Add a PHP enum (`App\Enums\Role`) and cast it in the model.

**elections**: `created_by` FK via `foreignId()->constrained('users')`. Add `SoftDeletes` — an election with votes can't be hard-deleted without destroying the voting record, and a report/demo needs history preserved.

**positions / candidates**: FK to parent with `cascadeOnDelete()`. Deleting an election removes its positions; deleting a position removes its candidates.

**votes**: all FKs (`election_id`, `position_id`, `candidate_id`, `voter_id`) use `restrictOnDelete()`. Votes are immutable history — never cascade-delete them. Keep `position_id` and `election_id` denormalized on the row as the schema does: result queries become a single indexed group-by instead of joins.

**audit_logs**: `user_id` nullable (system actions), indexes on `user_id` and `created_at`.

**Indexing note**: PostgreSQL does not auto-index FK columns (MySQL InnoDB does). Add explicit indexes on `votes(election_id, position_id)`, `candidates(position_id)`, `positions(election_id)` — the join/group-by paths.

## Models

Plain Eloquent relationships:

- `Election hasMany Position`, `Election hasMany Vote`, `Election belongsTo User` (creator)
- `Position hasMany Candidate`, `Position hasMany Vote`
- `Candidate belongsTo Position`, `Candidate belongsTo User` (nullable), `Candidate hasMany Vote`
- `Vote belongsTo Election / Position / Candidate / Voter(User)`
- `User hasMany Vote`, `User hasMany Candidate`, `User hasMany Election` (created)

Interesting bits: casts (`role` enum, `is_eligible` bool), and `Election` query scopes (`open()`, `active()` for the time-window check).

## Vote-casting service — where integrity lives

One method, wrapped in `DB::transaction`:

1. **Checks inside the transaction**: election `status === 'open'`, `now()` within `start_time`/`end_time`, voter `is_eligible`, `candidate.position_id === position.id`, and `position.election_id === election.id` (otherwise a voter could stuff votes using a candidate from another position).
2. **Insert the vote.**
3. **Insert the audit row** (`cast vote`) — same transaction, so a vote can never exist without its audit trail.
4. **Catch `UniqueConstraintViolationException`** → return 409 "already voted".

App-side checks are UX. The unique constraint is the law. Two concurrent double-submits: one insert wins, the other hits the constraint and becomes a clean 409. Never "fix" this with an app-level pre-check alone.

Status gating is a service concern, not just controller middleware: "open" = status flag **and** time window, evaluated at insert time. Optionally a scheduled command auto-closes expired elections for admin display.

## API surface

**Auth**: add Sanctum (skeleton has none). `POST /api/login` returns a token; `auth:sanctum` on everything but login.

**Voter routes**:
- `GET /api/elections` — open elections only
- `GET /api/elections/{id}` — positions + candidates
- `POST /api/elections/{id}/votes` — cast vote

**Admin routes** (role-gated middleware):
- CRUD elections / positions / candidates
- `GET /api/admin/elections/{id}/results`
- `GET /api/audit-logs`

**Results**: `Vote::where('election_id', $id)->groupBy('position_id', 'candidate_id')->count()`. Left-join from positions so empty positions show zeroes. The report shows counts; no winner is persisted.

## Demo-ability

Factories + a seeder: 1 admin, ~30 voters, one open election, 3 positions, 2–4 candidates each, a few hundred votes so results look real. `php artisan db:seed` → fully demoable. The audit log earns its keep in the report.

## Deliberate schema decisions (small changes to discuss)

- `votes.candidate_id`: restrict delete — deleting a candidate with votes must fail.
- `candidates.user_id` nullable + `name`: fine as-is (allows unregistered candidates). If `user_id` is set, enforce one-position-per-user in the service; the nullable column makes a DB unique constraint awkward.
- Keep `status` default `draft`, flip to `open` only via admin. Guardrail: the demo can't accidentally accept votes.
