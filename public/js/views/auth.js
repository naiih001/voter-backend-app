// ============================================================
//  Auth views — login, register, forgot, reset
// ============================================================

import { api } from '../core/api.js';
import { store } from '../core/store.js';
import { ui } from '../core/ui.js';
import { navigate } from '../core/router.js';

const LOCK_SVG = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>`;
const USER_SVG = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>`;
const CARD_SVG = `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/></svg>`;
const SHIELD_SVG = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--blue-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>`;

function authShell(innerHtml) {
  return `
    <div class="auth-icon">${CARD_SVG}</div>
    <h1 class="page-title text-center">Sign in to your account</h1>
    <p class="page-subtitle text-center">University Electronic Voting System</p>
    <div id="auth-alert" class="alert" style="display:none;"></div>
    <div class="card mt-24">
      ${innerHtml}
    </div>`;
}

function bindTabs(root) {
  root.querySelectorAll('.tabs').forEach((tabsContainer) => {
    const tabs = tabsContainer.querySelectorAll('.tab');
    tabs.forEach((tab) => {
      tab.addEventListener('click', () => {
        const target = tab.dataset.tab;
        tabs.forEach((t) => t.classList.remove('active'));
        tab.classList.add('active');
        const panel = tabsContainer.parentElement.querySelector(`.tab-panel[data-tab="${target}"]`);
        tabsContainer.parentElement.querySelectorAll('.tab-panel').forEach((p) => p.classList.remove('active'));
        if (panel) panel.classList.add('active');
      });
    });
  });
}

async function finishLogin(res, btn, alertEl) {
  if (res?.ok) {
    api.setToken(res.data.token);
    store.setUser(res.data.user);
    const role = res.data.user?.role;
    navigate(role === 'admin' ? '/admin' : '/dashboard');
  } else {
    ui.showAlert(alertEl, res?.data?.message || 'Invalid credentials. Please try again.');
    btn.disabled = false;
    btn.innerHTML = `${LOCK_SVG} Secure Login`;
  }
}

export async function login(params, root) {
  root.className = 'center-container';
  root.style.marginTop = '80px';

  root.innerHTML = authShell(`
    <div class="tab-panel active" data-tab="student" style="margin-top: 20px;">
      <div class="info-banner">
        ${SHIELD_SVG}<span>Secure 256-bit Encrypted Session</span>
      </div>
      <form id="student-login-form" onsubmit="return false;">
        <div class="form-group">
          <label class="form-label">Student ID / Matric Number</label>
          <div class="input-wrapper">
            <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
            <input type="text" id="student-matric" class="form-input" placeholder="Enter your student ID" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <div class="input-wrapper">
            <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 11-7.78 7.78 5.5 5.5 0 017.78-7.78zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
            <input type="password" id="student-password" class="form-input" placeholder="Enter your password" required>
          </div>
        </div>
        <div class="form-footer">
          <label class="checkbox-label"><input type="checkbox" id="student-remember"> Remember me</label>
          <a href="/forgot" data-link style="font-size: 0.875rem;">Forgot password?</a>
        </div>
        <button type="submit" id="student-login-btn" class="btn-primary" style="width: 100%; margin-top: 16px;">${LOCK_SVG} Secure Login</button>
      </form>
    </div>

    <div class="tab-panel" data-tab="admin" style="margin-top: 20px;">
      <div class="info-banner">
        ${SHIELD_SVG}<span>Secure 256-bit Encrypted Session</span>
      </div>
      <form id="admin-login-form" onsubmit="return false;">
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <div class="input-wrapper">
            ${USER_SVG}
            <input type="email" id="admin-email" class="form-input" placeholder="admin@university.edu" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <div class="input-wrapper">
            <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 11-7.78 7.78 5.5 5.5 0 017.78-7.78zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
            <input type="password" id="admin-password" class="form-input" placeholder="Enter your password" required>
          </div>
        </div>
        <div class="form-footer">
          <label class="checkbox-label"><input type="checkbox" id="admin-remember"> Remember me</label>
          <a href="/forgot" data-link style="font-size: 0.875rem;">Forgot password?</a>
        </div>
        <button type="submit" id="admin-login-btn" class="btn-primary" style="width: 100%; margin-top: 16px;">${LOCK_SVG} Secure Login</button>
      </form>
    </div>
  `);

  root.insertAdjacentHTML('beforeend', '<p class="auth-card-footer mt-16">Not registered for this election? <a href="/register" data-link>Create an account</a></p>');

  bindTabs(root);

  const alertEl = root.querySelector('#auth-alert');

  root.querySelector('#student-login-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = root.querySelector('#student-login-btn');
    const matric = root.querySelector('#student-matric').value.trim();
    const password = root.querySelector('#student-password').value;
    if (!matric || !password) {
      ui.showAlert(alertEl, 'Please enter your matric number and password.');
      return;
    }
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Signing in…';
    ui.hideAlert(alertEl);
    const res = await api.post('/login', { matric_number: matric, password }, false);
    await finishLogin(res, btn, alertEl);
  });

  root.querySelector('#admin-login-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = root.querySelector('#admin-login-btn');
    const email = root.querySelector('#admin-email').value.trim();
    const password = root.querySelector('#admin-password').value;
    if (!email || !password) {
      ui.showAlert(alertEl, 'Please enter your email and password.');
      return;
    }
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Signing in…';
    ui.hideAlert(alertEl);
    const res = await api.post('/login', { email, password }, false);
    await finishLogin(res, btn, alertEl);
  });
}

function registerShell(innerHtml) {
  return `
    <div class="auth-icon">${CARD_SVG}</div>
    <h1 class="page-title text-center">Create your account</h1>
    <p class="page-subtitle text-center">Join the University Electronic Voting System</p>
    <div id="auth-alert" class="alert" style="display:none;"></div>
    <div class="card mt-24">
      ${innerHtml}
    </div>`;
}

async function finishRegister(res, btn, alertEl) {
  if (res?.ok) {
    api.setToken(res.data.token);
    store.setUser(res.data.user);
    const role = res.data.user?.role;
    navigate(role === 'admin' ? '/admin' : '/dashboard');
  } else {
    let msg = res?.data?.message || 'Registration failed. Please try again.';
    if (res?.data?.errors) {
      msg = Object.values(res.data.errors).flat().join(' ');
    }
    ui.showAlert(alertEl, msg);
    btn.disabled = false;
    btn.innerHTML = 'Create Account';
  }
}

export async function register(params, root) {
  root.className = 'center-container';
  root.style.marginTop = '80px';

  root.innerHTML = registerShell(`
    <div class="tabs">
      <button class="tab active" data-tab="student">Student</button>
      <button class="tab" data-tab="admin">Administrator</button>
    </div>

    <div class="tab-panel active" data-tab="student" style="margin-top: 20px;">
      <form id="student-register-form" onsubmit="return false;">
        <div class="form-group">
          <label class="form-label">Full Name</label>
          <input type="text" id="student-name" class="form-input" placeholder="Jane Doe" required>
        </div>
        <div class="form-group">
          <label class="form-label">Matric Number</label>
          <input type="text" id="student-matric" class="form-input" placeholder="e.g. UG/2021/001" required>
        </div>
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <div class="input-wrapper">
            ${USER_SVG}
            <input type="email" id="student-email" class="form-input" placeholder="you@university.edu" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" id="student-password" class="form-input" placeholder="Min. 8 characters" required>
          </div>
          <div class="form-group">
            <label class="form-label">Confirm Password</label>
            <input type="password" id="student-password-confirm" class="form-input" placeholder="Re-enter password" required>
          </div>
        </div>
        <button type="submit" id="student-register-btn" class="btn-primary" style="width: 100%; margin-top: 16px;">Create Account</button>
      </form>
    </div>

    <p class="text-muted mt-16">Administrator accounts are issued by the university election office.</p>
  `);

  root.insertAdjacentHTML('beforeend', '<p class="auth-card-footer mt-16">Already have an account? <a href="/login" data-link>Sign in</a></p>');

  const alertEl = root.querySelector('#auth-alert');

  root.querySelector('#student-register-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = root.querySelector('#student-register-btn');
    const payload = {
      name: root.querySelector('#student-name').value.trim(),
      matric_number: root.querySelector('#student-matric').value.trim(),
      email: root.querySelector('#student-email').value.trim(),
      password: root.querySelector('#student-password').value,
      password_confirmation: root.querySelector('#student-password-confirm').value,
      role: 'voter',
    };
    if (!payload.name || !payload.matric_number || !payload.email || !payload.password) {
      ui.showAlert(alertEl, 'Please fill in all fields.');
      return;
    }
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Creating…';
    ui.hideAlert(alertEl);
    const res = await api.post('/register', payload, false);
    await finishRegister(res, btn, alertEl);
  });

}

export async function forgot(params, root) {
  root.className = 'center-container';
  root.style.marginTop = '80px';
  root.innerHTML = `
    <div class="auth-icon">${CARD_SVG}</div>
    <h1 class="page-title text-center">Reset your password</h1>
    <p class="page-subtitle text-center">Enter your email and we'll send a reset link.</p>
    <div id="auth-alert" class="alert" style="display:none;"></div>
    <div class="card mt-24">
      <form id="forgot-form" onsubmit="return false;">
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <div class="input-wrapper">
            ${USER_SVG}
            <input type="email" id="forgot-email" class="form-input" placeholder="you@university.edu" required>
          </div>
        </div>
        <button type="submit" id="forgot-btn" class="btn-primary" style="width: 100%; margin-top: 16px;">Send Reset Link</button>
      </form>
    </div>
    <p class="auth-card-footer mt-16"><a href="/login" data-link>Back to sign in</a></p>`;

  const alertEl = root.querySelector('#auth-alert');
  root.querySelector('#forgot-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = root.querySelector('#forgot-btn');
    const email = root.querySelector('#forgot-email').value.trim();
    if (!email) { ui.showAlert(alertEl, 'Please enter your email.'); return; }
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Sending…';
    const res = await api.post('/forgot-password', { email }, false);
    if (res.ok) {
      ui.showAlert(alertEl, res.data?.message || 'If that email exists, a reset link has been sent.', 'success');
    } else {
      ui.showAlert(alertEl, res.data?.message || 'Could not send reset link.');
    }
    btn.disabled = false;
    btn.innerHTML = 'Send Reset Link';
  });
}

export async function reset(params, root) {
  const q = new URLSearchParams(location.search);
  const email = q.get('email') || '';
  const token = q.get('token') || '';
  root.className = 'center-container';
  root.style.marginTop = '80px';
  root.innerHTML = `
    <div class="auth-icon">${CARD_SVG}</div>
    <h1 class="page-title text-center">Set a new password</h1>
    <p class="page-subtitle text-center">Choose a new password for your account.</p>
    <div id="auth-alert" class="alert" style="display:none;"></div>
    <div class="card mt-24">
      <form id="reset-form" onsubmit="return false;">
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <div class="input-wrapper">
            ${USER_SVG}
            <input type="email" id="reset-email" class="form-input" value="${ui.escapeHtml(email)}" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">New Password</label>
            <input type="password" id="reset-password" class="form-input" placeholder="Min. 8 characters" required>
          </div>
          <div class="form-group">
            <label class="form-label">Confirm Password</label>
            <input type="password" id="reset-password-confirm" class="form-input" placeholder="Re-enter password" required>
          </div>
        </div>
        <button type="submit" id="reset-btn" class="btn-primary" style="width: 100%; margin-top: 16px;">Reset Password</button>
      </form>
    </div>
    <p class="auth-card-footer mt-16"><a href="/login" data-link>Back to sign in</a></p>`;

  const alertEl = root.querySelector('#auth-alert');
  root.querySelector('#reset-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = root.querySelector('#reset-btn');
    const payload = {
      email: root.querySelector('#reset-email').value.trim(),
      password: root.querySelector('#reset-password').value,
      password_confirmation: root.querySelector('#reset-password-confirm').value,
      token,
    };
    if (!payload.email || !payload.password || !payload.token) {
      ui.showAlert(alertEl, 'Missing email, password, or reset token.');
      return;
    }
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> Resetting…';
    const res = await api.post('/reset-password', payload, false);
    if (res.ok) {
      ui.showAlert(alertEl, res.data?.message || 'Password reset successful. You can now sign in.', 'success');
      setTimeout(() => navigate('/login'), 1200);
    } else {
      let msg = res.data?.message || 'Could not reset password.';
      if (res.data?.errors) msg = Object.values(res.data.errors).flat().join(' ');
      ui.showAlert(alertEl, msg);
    }
    btn.disabled = false;
    btn.innerHTML = 'Reset Password';
  });
}
