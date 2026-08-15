// ============================================================
//  UniVote EVS — History-API router with auth/admin guards
// ============================================================

import { api } from './api.js';
import { store } from './store.js';
import { ui } from './ui.js';

import * as auth from '../views/auth.js';
import * as dashboard from '../views/dashboard.js';
import * as elections from '../views/elections.js';
import * as candidates from '../views/candidates.js';
import * as vote from '../views/vote.js';
import * as profile from '../views/profile.js';
import * as adminDashboard from '../views/admin/dashboard.js';
import * as adminElections from '../views/admin/elections.js';
import * as adminResults from '../views/admin/results.js';
import * as adminPositions from '../views/admin/positions.js';
import * as adminCandidates from '../views/admin/candidates.js';
import * as adminUsers from '../views/admin/users.js';
import * as adminAudit from '../views/admin/audit.js';
import * as notfound from '../views/notfound.js';

let currentPath = location.pathname;

export function getCurrentPath() {
  return currentPath;
}

const routes = [
  { pattern: '/login', view: auth.login, auth: 'guest' },
  { pattern: '/register', view: auth.register, auth: 'guest' },
  { pattern: '/forgot', view: auth.forgot, auth: 'guest' },
  { pattern: '/reset', view: auth.reset, auth: 'guest' },
  { pattern: '/dashboard', view: dashboard.view, auth: 'user' },
  { pattern: '/candidates', view: candidates.view, auth: 'user' },
  { pattern: '/vote', view: vote.view, auth: 'user' },
  { pattern: '/profile', view: profile.view, auth: 'user' },
  { pattern: '/elections', view: elections.view, auth: 'user' },
  { pattern: '/elections/:id', view: elections.detail },
  { pattern: '/admin', view: adminDashboard.view, auth: 'admin' },
  { pattern: '/admin/elections', view: adminElections.view, auth: 'admin' },
  { pattern: '/admin/elections/:id', view: adminElections.detail, auth: 'admin' },
  { pattern: '/admin/elections/:id/results', view: adminResults.view, auth: 'admin' },
  { pattern: '/admin/positions', view: adminPositions.view, auth: 'admin' },
  { pattern: '/admin/candidates', view: adminCandidates.view, auth: 'admin' },
  { pattern: '/admin/users', view: adminUsers.view, auth: 'admin' },
  { pattern: '/admin/audit', view: adminAudit.view, auth: 'admin' },
];

function matchPattern(pattern, path) {
  const pp = pattern.split('/').filter(Boolean);
  const sp = path.split('/').filter(Boolean);
  if (pp.length !== sp.length) return null;
  const params = {};
  for (let i = 0; i < pp.length; i++) {
    if (pp[i].startsWith(':')) {
      params[pp[i].slice(1)] = decodeURIComponent(sp[i]);
    } else if (pp[i] !== sp[i]) {
      return null;
    }
  }
  return params;
}

function matchRoute(path) {
  for (const r of routes) {
    const params = matchPattern(r.pattern, path);
    if (params) return { route: r, params };
  }
  return null;
}

function homeFor(user) {
  return user && user.role === 'admin' ? '/admin' : '/dashboard';
}

export function navigate(path, opts = {}) {
  if (opts.replace) {
    history.replaceState({}, '', path);
  } else {
    history.pushState({}, '', path);
  }
  currentPath = path;
  render();
}

async function render() {
  const path = currentPath;

  if (path === '/') {
    navigate(homeFor(store.getUser()), { replace: true });
    return;
  }

  const matched = matchRoute(path);
  const root = document.getElementById('app-root');
  if (!root) return;

  ui.renderNav();
  ui.renderFooter();

  if (!matched) {
    root.innerHTML = '';
    notfound.view(root);
    return;
  }

  const { route, params } = matched;
  const token = api.token;

  // Ensure we have user info for guard decisions.
  if ((route.auth === 'user' || route.auth === 'admin') && token && !store.getUser()) {
    await store.refreshUser();
  }

  // Guards
  if (route.auth === 'guest' && token && store.getUser()) {
    navigate(homeFor(store.getUser()), { replace: true });
    return;
  }
  if ((route.auth === 'user' || route.auth === 'admin') && !token) {
    navigate('/login', { replace: true });
    return;
  }
  if (route.auth === 'admin' && !store.isAdmin()) {
    navigate('/dashboard', { replace: true });
    return;
  }

  root.innerHTML = '';
  try {
    await route.view(params, root);
  } catch (err) {
    console.error('[render]', err);
    root.innerHTML = '';
    root.appendChild(ui.el('div', { class: 'container', style: 'padding-top:48px;' },
      ui.el('div', { class: 'alert alert-error' }, 'Something went wrong loading this page.')));
  }
}

export function initRouter() {
  document.addEventListener('click', (e) => {
    const a = e.target.closest('a');
    if (!a) return;
    const href = a.getAttribute('href');
    if (!href) return;
    if (a.target && a.target !== '_self') return;
    if (a.hasAttribute('data-no-router')) return;
    if (href.startsWith('http') || href.startsWith('//')) return;
    if (href === '#' || href.startsWith('#')) {
      e.preventDefault();
      return;
    }
    e.preventDefault();
    navigate(href);
  });

  window.addEventListener('popstate', () => {
    currentPath = location.pathname;
    render();
  });

  currentPath = location.pathname;
  render();
}
