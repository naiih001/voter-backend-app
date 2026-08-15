// ============================================================
//  UniVote EVS — Shared UI primitives & navigation
// ============================================================

import { api } from './api.js';
import { store } from './store.js';
import { navigate, getCurrentPath } from './router.js';

/* ---------- DOM helpers ---------- */

export function escapeHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

const SVG_NS = 'http://www.w3.org/2000/svg';
const SVG_TAGS = new Set([
  'svg', 'path', 'circle', 'line', 'polyline', 'polygon', 'rect', 'g',
  'ellipse', 'text', 'defs', 'use', 'symbol', 'tspan', 'clipPath', 'image',
]);

export function el(tag, attrs = {}, ...children) {
  const useNS = SVG_TAGS.has(tag);
  const node = useNS ? document.createElementNS(SVG_NS, tag) : document.createElement(tag);
  for (const [k, v] of Object.entries(attrs || {})) {
    if (v === null || v === undefined || v === false) continue;
    if (k === 'class') node.setAttribute('class', v);
    else if (k === 'html') node.innerHTML = v;
    else if (k === 'text') node.textContent = v;
    else if (k === 'dataset') Object.assign(node.dataset, v);
    else if (k.startsWith('on') && typeof v === 'function') {
      node.addEventListener(k.slice(2).toLowerCase(), v);
    } else if (v === true) {
      node.setAttribute(k, '');
    } else {
      node.setAttribute(k, v);
    }
  }
  for (const child of children.flat()) {
    if (child === null || child === undefined || child === false) continue;
    node.appendChild(
      typeof child === 'string' || typeof child === 'number'
        ? document.createTextNode(String(child))
        : child
    );
  }
  return node;
}

/* ---------- Alerts (modal field-validation only) ---------- */

export function showAlert(container, msg, type = 'error') {
  if (!container) return;
  container.textContent = msg;
  container.className = `alert alert-${type}`;
  container.style.display = 'block';
}

export function hideAlert(container) {
  if (!container) return;
  container.style.display = 'none';
  container.textContent = '';
  container.className = 'alert';
}

/* ---------- Toasts (global, non-blocking state-change signal) ---------- */

export function toast(msg, type = 'success') {
  const container = document.getElementById('toast-container');
  if (!container) return;
  const node = el('div', { class: `toast toast-${type}`, role: 'status', 'aria-live': 'polite' }, msg);
  container.appendChild(node);
  setTimeout(() => {
    node.style.opacity = '0';
    node.style.transform = 'translateY(-8px)';
    node.style.transition = 'opacity 0.2s, transform 0.2s';
    setTimeout(() => node.remove(), 200);
  }, 3000);
}

export async function shareLink({ title, text = '', url }) {
  if (navigator.share) {
    try {
      await navigator.share({ title, text, url });
      return;
    } catch (error) {
      if (error.name === 'AbortError') return;
    }
  }

  try {
    await navigator.clipboard.writeText(url);
    toast('Election link copied.', 'success');
  } catch {
    toast('Could not copy the election link.', 'error');
  }
}

/* ---------- Form field builders ---------- */

export const field = {
  text(opts = {}) {
    const input = el('input', {
      type: opts.type || 'text',
      class: 'form-input',
      placeholder: opts.placeholder || '',
      value: opts.value ?? '',
    });
    const wrap = el('div', { class: 'form-group' },
      opts.label ? el('label', { class: 'form-label' }, opts.label) : null,
      input
    );
    return { node: wrap, input, value: () => input.value.trim() };
  },

  textarea(opts = {}) {
    const input = el('textarea', { class: 'form-input', rows: opts.rows || 3, placeholder: opts.placeholder || '' });
    input.value = opts.value ?? '';
    const wrap = el('div', { class: 'form-group' },
      opts.label ? el('label', { class: 'form-label' }, opts.label) : null,
      input
    );
    return { node: wrap, input, value: () => input.value.trim() };
  },

  select(opts = {}) {
    const select = el('select', { class: 'form-input' });
    (opts.options || []).forEach((o) => {
      const opt = el('option', { value: o.value }, o.label);
      if (String(o.value) === String(opts.value ?? '')) opt.selected = true;
      select.appendChild(opt);
    });
    const wrap = el('div', { class: 'form-group' },
      opts.label ? el('label', { class: 'form-label' }, opts.label) : null,
      select
    );
    return { node: wrap, input: select, value: () => select.value };
  },

  checkbox(opts = {}) {
    const input = el('input', { type: 'checkbox' });
    input.checked = !!opts.checked;
    const label = el('label', { class: 'checkbox-label' }, input, opts.label || '');
    const wrap = el('div', { class: 'form-group' }, label);
    return { node: wrap, input, value: () => input.checked };
  },
};

/* ---------- Modal ---------- */

let activeModalCleanup = null;

export function openModal({ title, subtitle = '', body, actions = [] }) {
  activeModalCleanup?.();
  activeModalCleanup = null;
  const modalRoot = document.getElementById('app-modal');
  const closeBtn = el('button', { class: 'modal-close', 'aria-label': 'Close', type: 'button' },
    el('svg', { width: '20', height: '20', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', 'stroke-linecap': 'round' },
      el('line', { x1: '6', y1: '6', x2: '18', y2: '18' }),
      el('line', { x1: '18', y1: '6', x2: '6', y2: '18' })
    )
  );

  const header = el('div', { class: 'flex-between' },
    el('div', {},
      el('h3', {}, title),
      subtitle ? el('p', { class: 'modal-subtitle' }, subtitle) : null
    ),
    closeBtn
  );

  const bodyNode = el('div', { class: 'modal-body' });
  if (typeof body === 'string') bodyNode.innerHTML = body;
  else if (body) bodyNode.appendChild(body);

  const actionsNode = el('div', { class: 'modal-actions' },
    ...actions.map((a) => el('button', {
      class: a.class || 'btn-outline',
      onClick: () => a.onClick && a.onClick(),
    }, a.label))
  );

  const modal = el('div', { class: 'modal', role: 'dialog', 'aria-modal': 'true', 'aria-labelledby': 'modal-title' }, header, bodyNode, actionsNode);
  modal.querySelector('h3')?.setAttribute('id', 'modal-title');
  const overlay = el('div', { class: 'modal-overlay' }, modal);

  closeBtn.addEventListener('click', closeModal);
  overlay.addEventListener('click', (e) => { if (e.target === overlay) closeModal(); });

  modalRoot.innerHTML = '';
  modalRoot.appendChild(overlay);
  const previousFocus = document.activeElement;
  const onKeydown = (event) => {
    if (event.key === 'Escape') {
      closeModal();
      return;
    }
    if (event.key !== 'Tab') return;
    const focusable = [...modal.querySelectorAll('button, a[href], input, select, textarea, [tabindex]:not([tabindex="-1"])')]
      .filter((node) => !node.disabled && node.offsetParent !== null);
    if (!focusable.length) return;
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  };
  document.addEventListener('keydown', onKeydown);
  activeModalCleanup = () => {
    document.removeEventListener('keydown', onKeydown);
    if (previousFocus instanceof HTMLElement && previousFocus.isConnected) previousFocus.focus();
  };
  closeBtn.focus();
  return { overlay, modal, bodyNode, actionsNode, close: closeModal };
}

export function closeModal() {
  const modalRoot = document.getElementById('app-modal');
  if (modalRoot) modalRoot.innerHTML = '';
  activeModalCleanup?.();
  activeModalCleanup = null;
}

export function confirmDialog(msg) {
  return new Promise((resolve) => {
    openModal({
      title: 'Please confirm',
      body: el('p', {}, msg),
      actions: [
        { label: 'Cancel', class: 'btn-outline', onClick: () => { closeModal(); resolve(false); } },
        { label: 'Confirm', class: 'btn-primary', onClick: () => { closeModal(); resolve(true); } },
      ],
    });
  });
}

/* ---------- Loading / empty ---------- */

export function spinner() {
  return el('span', { class: 'spinner' });
}

export function loadingBlock(message = 'Loading…') {
  return el('div', { class: 'text-center', style: 'padding: 48px 0;' },
    el('span', { class: 'spinner' }),
    el('p', { class: 'text-muted mt-8' }, message)
  );
}

export function emptyState(message = 'Nothing here yet.') {
  return el('div', { class: 'empty-state' },
    el('svg', { width: '40', height: '40', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '1.5', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' },
      el('circle', { cx: '12', cy: '12', r: '10' }),
      el('path', { d: 'M8 15h8M9 9h.01M15 9h.01' })
    ),
    el('p', {}, message)
  );
}

/* ---------- Date / formatting (ported) ---------- */

export function formatDate(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  if (isNaN(date.getTime())) return '';
  return date.toLocaleDateString('en-US', {
    year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit',
  });
}

export function getInitials(name) {
  if (!name) return '?';
  return name.split(' ').map((n) => n[0]).join('').slice(0, 2).toUpperCase();
}

export function toBackendDate(value) {
  // datetime-local "2026-08-13T14:30" -> "2026-08-13 14:30:00"
  if (!value) return '';
  const v = value.replace('T', ' ');
  return v.length === 16 ? v + ':00' : v;
}

export function toLocalInput(value) {
  // "2026-08-13 14:30:00" -> "2026-08-13T14:30"
  if (!value) return '';
  return String(value).replace(' ', 'T').slice(0, 16);
}

/* ---------- Navigation bar & footer ---------- */

export async function logout() {
  await api.post('/logout', {}).catch(() => {});
  store.clear();
  api.setToken(null);
  navigate('/login');
  toast('Signed out.', 'info');
}

function activeClass(path) {
  const current = getCurrentPath();
  return current === path || (path !== '/admin' && current.startsWith(`${path}/`)) ? 'active' : '';
}

export function renderNav() {
  const nav = document.getElementById('app-nav');
  if (!nav) return;
  const user = store.getUser();
  nav.className = 'navbar';
  nav.innerHTML = '';

  nav.appendChild(el('button', { class: 'hamburger', type: 'button', 'aria-label': 'Open navigation', 'aria-expanded': 'false', 'aria-controls': 'primary-navigation' },
    el('svg', { width: '22', height: '22', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', 'stroke-linecap': 'round' },
      el('line', { x1: '4', y1: '6', x2: '20', y2: '6' }),
      el('line', { x1: '4', y1: '12', x2: '20', y2: '12' }),
      el('line', { x1: '4', y1: '18', x2: '20', y2: '18' })
    )
  ));
  nav.appendChild(el('a', { href: '/', class: 'nav-logo', 'data-link': '' }, 'UniVote EVS'));

  if (!user) {
    const links = el('div', { class: 'nav-links', id: 'primary-navigation' },
      el('a', { href: '/login', 'data-link': '', class: activeClass('/login') }, 'Login'),
      el('a', { href: '/register', 'data-link': '', class: activeClass('/register') }, 'Register'),
    );
    nav.appendChild(links);
    bindMobileNav(nav, links);
    return;
  }

  let links;
  if (user.role === 'admin') {
    links = el('div', { class: 'nav-links', id: 'primary-navigation' },
      el('a', { href: '/admin', 'data-link': '', class: activeClass('/admin') }, 'Dashboard'),
      el('a', { href: '/admin/elections', 'data-link': '', class: activeClass('/admin/elections') }, 'Elections'),
      el('a', { href: '/admin/users', 'data-link': '', class: activeClass('/admin/users') }, 'Users'),
      el('a', { href: '/admin/audit', 'data-link': '', class: activeClass('/admin/audit') }, 'Audit Log'),
      el('a', { href: '/dashboard', 'data-link': '', class: activeClass('/dashboard') }, 'Voter View'),
    );
  } else {
    links = el('div', { class: 'nav-links', id: 'primary-navigation' },
      el('a', { href: '/dashboard', 'data-link': '', class: activeClass('/dashboard') }, 'Home'),
      el('a', { href: '/candidates', 'data-link': '', class: activeClass('/candidates') }, 'Candidates'),
      el('a', { href: '/vote', 'data-link': '', class: activeClass('/vote') }, 'Vote'),
      el('a', { href: '/profile', 'data-link': '', class: activeClass('/profile') }, 'Profile'),
    );
  }
  nav.appendChild(links);
  bindMobileNav(nav, links);

  const menu = el('div', { id: 'nav-user-menu', class: 'nav-user-menu', style: 'display:none;' },
    el('a', { href: '/profile', 'data-link': '', class: 'nav-menu-item' }, 'Profile'),
    el('button', { class: 'nav-menu-item', onClick: () => logout() }, 'Sign out'),
  );
  const avatar = el('button', { class: 'avatar-circle', title: user.name, 'aria-label': `Open account menu for ${user.name}`, 'aria-expanded': 'false', type: 'button' }, getInitials(user.name));
  avatar.addEventListener('click', (e) => {
    e.stopPropagation();
    const open = menu.style.display === 'none';
    menu.style.display = open ? 'block' : 'none';
    avatar.setAttribute('aria-expanded', String(open));
  });

  nav.appendChild(el('div', { class: 'nav-actions' }, avatar, menu));
}

function bindMobileNav(nav, links) {
  const trigger = nav.querySelector('.hamburger');
  if (!trigger) return;
  const close = () => {
    links.classList.remove('open');
    trigger.setAttribute('aria-expanded', 'false');
    trigger.setAttribute('aria-label', 'Open navigation');
  };
  trigger.addEventListener('click', (event) => {
    event.stopPropagation();
    const open = !links.classList.contains('open');
    links.classList.toggle('open', open);
    trigger.setAttribute('aria-expanded', String(open));
    trigger.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
  });
  links.querySelectorAll('a').forEach((link) => link.addEventListener('click', close));
  nav.addEventListener('keydown', (event) => { if (event.key === 'Escape') close(); });
}

// Close the user menu on any outside click (bound once).
if (typeof document !== 'undefined') {
  document.addEventListener('click', () => {
    const m = document.getElementById('nav-user-menu');
    if (m) m.style.display = 'none';
    const avatar = document.querySelector('.avatar-circle[aria-expanded]');
    if (avatar) avatar.setAttribute('aria-expanded', 'false');
    const links = document.getElementById('primary-navigation');
    const trigger = document.querySelector('.hamburger');
    if (links && trigger) {
      links.classList.remove('open');
      trigger.setAttribute('aria-expanded', 'false');
      trigger.setAttribute('aria-label', 'Open navigation');
    }
  });
}

export function renderFooter() {
  const footer = document.getElementById('app-footer');
  if (!footer) return;
  footer.className = 'footer';
  footer.innerHTML = `
    <span>&copy; ${new Date().getFullYear()} University Electronic Voting System. All rights reserved. Secure &amp; Encrypted.</span>
    <div class="footer-links">
      <a href="#">Security Policy</a><span>&middot;</span>
      <a href="#">Terms of Participation</a><span>&middot;</span>
      <a href="#">Contact Support</a>
    </div>`;
}

export const ui = {
  escapeHtml, el, showAlert, hideAlert, toast, shareLink, openModal, closeModal,
  confirmDialog, field, spinner, loadingBlock, emptyState, formatDate,
  getInitials, toBackendDate, toLocalInput, renderNav, renderFooter,
};
