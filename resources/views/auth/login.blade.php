@extends('layouts.app')

@section('title', 'UniVote EVS — Login')

@section('navbar')
  <nav class="navbar">
    <div class="nav-logo">UniVote EVS</div>
    <button class="hamburger" aria-label="Toggle navigation">
      <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
  </nav>
@endsection

@section('content')
  <main class="center-container" style="margin-top: 80px;">
    <div class="auth-icon">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"/></svg>
    </div>
    <h1 class="page-title text-center">Sign in to your account</h1>
    <p class="page-subtitle text-center">University Electronic Voting System</p>

    <div id="auth-alert" class="alert" style="display: none;"></div>

    <div class="card mt-24">
      <div class="tabs">
        <button class="tab active" data-tab="student">Student</button>
        <button class="tab" data-tab="admin">Administrator</button>
      </div>

      <div class="tab-panel active" data-tab="student" style="margin-top: 20px;">
        <div class="info-banner">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--blue-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
          <span>Secure 256-bit Encrypted Session</span>
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
            <label class="checkbox-label">
              <input type="checkbox" id="student-remember"> Remember me
            </label>
            <a href="#" style="font-size: 0.875rem;">Forgot password?</a>
          </div>

          <button type="submit" id="student-login-btn" class="btn-primary" style="width: 100%; margin-top: 16px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
            Secure Login
          </button>
        </form>
      </div>

      <div class="tab-panel" data-tab="admin" style="margin-top: 20px;">
        <div class="info-banner">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--blue-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
          <span>Secure 256-bit Encrypted Session</span>
        </div>

        <form id="admin-login-form" onsubmit="return false;">
          <div class="form-group">
            <label class="form-label">Email Address</label>
            <div class="input-wrapper">
              <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
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
            <label class="checkbox-label">
              <input type="checkbox" id="admin-remember"> Remember me
            </label>
            <a href="#" style="font-size: 0.875rem;">Forgot password?</a>
          </div>

          <button type="submit" id="admin-login-btn" class="btn-primary" style="width: 100%; margin-top: 16px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
            Secure Login
          </button>
        </form>
      </div>
    </div>

    <p class="auth-card-footer mt-16">Not registered for this election? <a href="/register">Create an account</a></p>
  </main>
@endsection

@section('footer')
  <footer class="footer">
    <span>&copy; 2024 University Electronic Voting System. All rights reserved. Secure &amp; Encrypted.</span>
    <div class="footer-links">
      <a href="#">Security Policy</a>
      <span>&middot;</span>
      <a href="#">Terms of Participation</a>
      <span>&middot;</span>
      <a href="#">Contact Support</a>
    </div>
  </footer>
@endsection

@section('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      redirectIfAuthed();

      // Tab switching
      document.querySelectorAll('.tabs').forEach(tabsContainer => {
        const tabs = tabsContainer.querySelectorAll('.tab');
        const panels = tabsContainer.parentElement.querySelectorAll('.tab-panel');

        tabs.forEach(tab => {
          tab.addEventListener('click', () => {
            const target = tab.dataset.tab;
            tabs.forEach(t => t.classList.remove('active'));
            panels.forEach(p => p.classList.remove('active'));
            tab.classList.add('active');
            const panel = tabsContainer.parentElement.querySelector(`.tab-panel[data-tab="${target}"]`);
            if (panel) panel.classList.add('active');
          });
        });
      });

      const alertEl = document.getElementById('auth-alert');

      // Student login
      document.getElementById('student-login-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('student-login-btn');
        const matric = document.getElementById('student-matric').value.trim();
        const password = document.getElementById('student-password').value;

        if (!matric || !password) {
          showError(alertEl, 'Please enter your matric number and password.');
          return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Signing in...';

        const res = await api.post('/login', { matric_number: matric, password });

        if (res?.ok) {
          api.setToken(res.data.token);
          localStorage.setItem('uv_user', JSON.stringify(res.data.user));
          showSuccess(alertEl, 'Login successful! Redirecting...');
          setTimeout(() => window.location.href = '/dashboard', 600);
        } else {
          showError(alertEl, res?.data?.message || 'Invalid credentials. Please try again.');
          btn.disabled = false;
          btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg> Secure Login';
        }
      });

      // Admin login
      document.getElementById('admin-login-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('admin-login-btn');
        const email = document.getElementById('admin-email').value.trim();
        const password = document.getElementById('admin-password').value;

        if (!email || !password) {
          showError(alertEl, 'Please enter your email and password.');
          return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Signing in...';

        const res = await api.post('/login', { email, password, role: 'admin' });

        if (res?.ok) {
          api.setToken(res.data.token);
          localStorage.setItem('uv_user', JSON.stringify(res.data.user));
          showSuccess(alertEl, 'Login successful! Redirecting...');
          setTimeout(() => window.location.href = '/dashboard', 600);
        } else {
          showError(alertEl, res?.data?.message || 'Invalid credentials. Please try again.');
          btn.disabled = false;
          btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg> Secure Login';
        }
      });
    });
  </script>
@endsection
