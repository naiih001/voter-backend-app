@extends('layouts.app')

@section('title', 'UniVote EVS — Registration')

@section('navbar')
  <nav class="navbar">
    <div class="nav-logo">UniVote EVS</div>
    <button class="hamburger" aria-label="Toggle navigation">
      <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
  </nav>
@endsection

@section('content')
  <main class="center-container" style="max-width: 640px; margin-top: 48px;">
    <div class="auth-icon">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/><polyline points="17 11 19 13 23 9"/></svg>
    </div>
    <h1 class="page-title text-center">UniVote EVS Registration</h1>
    <p class="page-subtitle text-center">Create your secure account to participate in or manage upcoming university elections.</p>

    <div id="auth-alert" class="alert" style="display: none;"></div>

    <div class="card mt-24">
      <div class="tabs">
        <button class="tab active" data-tab="student">Student</button>
        <button class="tab" data-tab="admin">Administrator</button>
      </div>

      <div class="tab-panel active" data-tab="student" style="margin-top: 20px;">
        <form id="student-register-form" onsubmit="return false;">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Full Name</label>
              <div class="input-wrapper">
                <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <input type="text" id="student-name" class="form-input" placeholder="Legal Name" required>
              </div>
              <p class="form-hint">As it appears on your student ID.</p>
            </div>
            <div class="form-group">
              <label class="form-label">Matric Number</label>
              <div class="input-wrapper">
                <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                <input type="text" id="student-matric-reg" class="form-input" placeholder="E.G. U1234567" required>
              </div>
              <p class="form-hint">Your unique student identifier.</p>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">University Email</label>
            <div class="input-wrapper">
              <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
              <input type="email" id="student-email-reg" class="form-input" placeholder="student@university.edu" required>
            </div>
            <p class="form-hint">Must be an official .edu address for verification.</p>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Department</label>
              <div class="input-wrapper">
                <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"/><line x1="9" y1="22" x2="9" y2="2"/><line x1="15" y1="22" x2="15" y2="2"/></svg>
                <select id="student-department" class="form-input" style="appearance: auto;" required>
                  <option value="" disabled selected>Select Department</option>
                  <option>Faculty of Science</option>
                  <option>School of Business</option>
                  <option>Faculty of Arts</option>
                  <option>Engineering Dept</option>
                  <option>Faculty of Medicine</option>
                  <option>Faculty of Law</option>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Academic Level</label>
              <div class="input-wrapper">
                <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>
                <select id="student-level" class="form-input" style="appearance: auto;" required>
                  <option value="" disabled selected>Select Level</option>
                  <option>100 Level</option>
                  <option>200 Level</option>
                  <option>300 Level</option>
                  <option>400 Level</option>
                  <option>500 Level</option>
                  <option>Postgraduate</option>
                </select>
              </div>
            </div>
          </div>

          <div class="info-banner" style="background: #F0FDF4; border: 1px solid #BBF7D0;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--green-live)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
            <div>
              <strong style="color: var(--text-primary);">Identity Verification</strong><br>
              <span style="font-size: 0.8125rem;">Your identity will be verified against the university student database upon submission.</span>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Password</label>
              <div class="input-wrapper">
                <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                <input type="password" id="student-password-reg" class="form-input" placeholder="Min. 8 characters" required>
              </div>
              <p class="form-hint">Min. 8 characters, letters &amp; numbers.</p>
            </div>
            <div class="form-group">
              <label class="form-label">Confirm Password</label>
              <div class="input-wrapper">
                <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                <input type="password" id="student-password-confirm" class="form-input" placeholder="Confirm password" required>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label class="checkbox-label">
              <input type="checkbox" id="student-terms" required>
              I agree to the <a href="#">Terms of Participation</a> and <a href="#">Security Policy</a>.
            </label>
          </div>

          <button type="submit" id="student-register-btn" class="btn-primary" style="width: 100%;">
            Create Student Account &rarr;
          </button>

          <div class="auth-card-inner-footer">
            Already registered? <a href="/login">Log in securely</a>
          </div>
        </form>
      </div>

      <div class="tab-panel" data-tab="admin" style="margin-top: 20px;">
        <form id="admin-register-form" onsubmit="return false;">
          <div class="form-group">
            <label class="form-label">Full Name</label>
            <div class="input-wrapper">
              <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              <input type="text" id="admin-name-reg" class="form-input" placeholder="Admin Name" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Email Address</label>
            <div class="input-wrapper">
              <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
              <input type="email" id="admin-email-reg" class="form-input" placeholder="admin@university.edu" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Password</label>
            <div class="input-wrapper">
              <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
              <input type="password" id="admin-password-reg" class="form-input" placeholder="Min. 8 characters" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Confirm Password</label>
            <div class="input-wrapper">
              <svg class="input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
              <input type="password" id="admin-password-confirm" class="form-input" placeholder="Confirm password" required>
            </div>
          </div>
          <button type="submit" id="admin-register-btn" class="btn-primary" style="width: 100%;">Register as Administrator</button>
          <div class="auth-card-inner-footer">
            Already registered? <a href="/login">Log in securely</a>
          </div>
        </form>
      </div>
    </div>

    <p class="text-center mt-16" style="font-size: 0.75rem; color: var(--text-muted);">&#128274; End-to-End Encrypted Registration</p>
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

      // Student registration
      document.getElementById('student-register-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('student-register-btn');

        const name = document.getElementById('student-name').value.trim();
        const matric_number = document.getElementById('student-matric-reg').value.trim();
        const email = document.getElementById('student-email-reg').value.trim();
        const department = document.getElementById('student-department').value;
        const level = document.getElementById('student-level').value;
        const password = document.getElementById('student-password-reg').value;
        const password_confirmation = document.getElementById('student-password-confirm').value;
        const terms = document.getElementById('student-terms').checked;

        if (!name || !matric_number || !email || !department || !level || !password) {
          showError(alertEl, 'Please fill in all required fields.');
          return;
        }

        if (password !== password_confirmation) {
          showError(alertEl, 'Passwords do not match.');
          return;
        }

        if (!terms) {
          showError(alertEl, 'Please accept the Terms of Participation.');
          return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Creating account...';

        const res = await api.post('/register', {
          name,
          matric_number,
          email,
          password,
          password_confirmation,
          role: 'voter',
          department,
          level,
        });

        if (res?.ok) {
          api.setToken(res.data.token);
          localStorage.setItem('uv_user', JSON.stringify(res.data.user));
          showSuccess(alertEl, 'Registration successful! Redirecting...');
          setTimeout(() => window.location.href = '/dashboard', 600);
        } else {
          showError(alertEl, res?.data?.message || 'Registration failed. Please try again.');
          btn.disabled = false;
          btn.innerHTML = 'Create Student Account &rarr;';
        }
      });

      // Admin registration
      document.getElementById('admin-register-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('admin-register-btn');

        const name = document.getElementById('admin-name-reg').value.trim();
        const email = document.getElementById('admin-email-reg').value.trim();
        const password = document.getElementById('admin-password-reg').value;
        const password_confirmation = document.getElementById('admin-password-confirm').value;

        if (!name || !email || !password) {
          showError(alertEl, 'Please fill in all required fields.');
          return;
        }

        if (password !== password_confirmation) {
          showError(alertEl, 'Passwords do not match.');
          return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Creating account...';

        const res = await api.post('/register', {
          name,
          email,
          password,
          password_confirmation,
          role: 'admin',
        });

        if (res?.ok) {
          api.setToken(res.data.token);
          localStorage.setItem('uv_user', JSON.stringify(res.data.user));
          showSuccess(alertEl, 'Registration successful! Redirecting...');
          setTimeout(() => window.location.href = '/dashboard', 600);
        } else {
          showError(alertEl, res?.data?.message || 'Registration failed. Please try again.');
          btn.disabled = false;
          btn.innerHTML = 'Register as Administrator';
        }
      });
    });
  </script>
@endsection
