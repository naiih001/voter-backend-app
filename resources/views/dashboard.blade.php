@extends('layouts.app')

@section('title', 'UniVote EVS — Dashboard')

@section('navbar')
  <nav class="navbar">
    <div class="nav-logo">UniVote EVS</div>
    <div class="nav-links">
      <a href="/dashboard" class="active">Home</a>
      <a href="/candidates">Candidates</a>
      <a href="/vote">Vote</a>
    </div>
    <button class="hamburger" aria-label="Toggle navigation">
      <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
    <div class="nav-actions">
      <button class="nav-bell" aria-label="Notifications">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
        <span class="badge" id="notif-badge">0</span>
      </button>
      <div class="avatar-circle" data-user-initials>SJ</div>
    </div>
  </nav>
@endsection

@section('content')
  <main class="container">
    <div class="flex-between mt-32" style="align-items: flex-start;">
      <div>
        <h1 class="page-title">Hello, <span data-user-name>Sarah Jenkins</span></h1>
        <p class="page-subtitle">Welcome back to the University Electronic Voting System.</p>
      </div>
      <div id="election-status-badge" style="display: flex; align-items: center; gap: 8px; border: 1px solid var(--green-live); border-radius: 999px; padding: 6px 14px; font-size: 0.875rem; color: var(--text-secondary); white-space: nowrap;">
        <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--green-live);"></span>
        <span id="election-status">Loading...</span>
      </div>
    </div>

    <div class="alert" id="dashboard-alert" style="display: none; margin-top: 16px;"></div>

    <div class="stat-cards">
      <!-- Verification -->
      <div class="stat-card">
        <div class="icon" style="background: var(--blue-light); color: var(--blue-primary);">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
        </div>
        <div class="label">Verification</div>
        <div class="value" style="font-size: 1.125rem;">Eligibility</div>
        <div class="sub-value" style="font-size: 1.25rem; font-weight: var(--fw-bold); color: var(--green-live);" id="eligibility-status">...</div>
      </div>
      <!-- Status -->
      <div class="stat-card">
        <div class="icon" style="background: #FEE2E2; color: var(--red-badge);">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><polyline points="16 2 12 7 8 2"/></svg>
        </div>
        <div class="label">Status</div>
        <div class="value" style="font-size: 1.125rem;">Vote Status</div>
        <div class="sub-value" style="font-size: 1.25rem; font-weight: var(--fw-bold);" id="vote-status">Loading</div>
      </div>
      <!-- Deadline -->
      <div class="stat-card">
        <div class="icon" style="background: var(--bg-page); color: var(--text-muted);">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="label">Deadline</div>
        <div class="value" style="font-size: 1.125rem;">Time Remaining</div>
        <div class="sub-value" style="font-size: 1.25rem; font-weight: var(--fw-bold);" id="time-remaining">--</div>
      </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-top: 24px;">
      <!-- Elections / Announcements -->
      <div class="card">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 16px;">
          <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--blue-light); display: flex; align-items: center; justify-content: center; color: var(--blue-primary);">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 010 14.14M15.54 8.46a5 5 0 010 7.07"/></svg>
          </div>
          <h3 class="section-title">Active Elections</h3>
        </div>

        <div id="elections-list">
          <p class="text-muted" style="padding: 20px 0; text-align: center;">Loading elections...</p>
        </div>

        <a href="/candidates" class="link-blue" style="display: inline-block; margin-top: 12px; font-size: 0.875rem;">View All Candidates &rarr;</a>
      </div>

      <!-- CTA -->
      <div class="cta-card">
        <div class="cta-icon">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--blue-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><polyline points="16 2 12 7 8 2"/></svg>
        </div>
        <h3>Ready to shape the future?</h3>
        <p>Cast your vote for the next student leaders.</p>
        <a href="/vote" class="btn-primary" style="width: 100%; margin-top: 8px;">Proceed to Vote Now &rarr;</a>
        <div style="display: flex; align-items: center; gap: 6px; margin-top: 8px; font-size: 0.75rem; color: var(--text-muted);">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
          256-bit Encrypted Session
        </div>
      </div>
    </div>
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
      requireAuth();

      // Hamburger
      const hamburger = document.querySelector('.hamburger');
      const navLinks = document.querySelector('.nav-links');
      if (hamburger && navLinks) {
        hamburger.addEventListener('click', () => navLinks.classList.toggle('open'));
      }

      // Note: Backend has no announcements endpoint, so we show elections only
      const alertEl = document.getElementById('dashboard-alert');

      async function loadDashboard() {
        const userRes = await api.get('/user');
        if (!userRes?.ok) return;

        const user = userRes.data;
        document.querySelectorAll('[data-user-name]').forEach(el => el.textContent = user.name);
        document.querySelectorAll('[data-user-initials]').forEach(el => el.textContent = getInitials(user.name));

        document.getElementById('eligibility-status').textContent = user.is_eligible ? 'Eligible' : 'Not Eligible';
        document.getElementById('eligibility-status').style.color = user.is_eligible ? 'var(--green-live)' : 'var(--red-badge)';

        // Load elections
        const electionsRes = await api.get('/elections');
        if (electionsRes?.ok) {
          const elections = electionsRes.data;
          renderElections(elections);
          if (elections.length > 0) {
            const election = elections[0];
            document.getElementById('election-status').textContent = 'Live: ' + election.title;
            updateTimeRemaining(election);

            // Check if already voted for this election
            checkVoteStatus(election);
          } else {
            document.getElementById('election-status').textContent = 'No Active Elections';
            document.getElementById('elections-list').innerHTML = '<p class="text-muted" style="padding: 20px 0; text-align: center;">No elections are currently open for voting.</p>';
          }
        }
      }

      function renderElections(elections) {
        const container = document.getElementById('elections-list');
        if (elections.length === 0) {
          container.innerHTML = '<p class="text-muted">No active elections at this time.</p>';
          return;
        }

        container.innerHTML = elections.map(e => `
          <div class="announcement-item">
            <div class="announcement-header">
              <h4>${e.title}</h4>
              <span class="announcement-time">${formatDate(e.start_time)}</span>
            </div>
            <p class="announcement-desc">${e.description || 'No description provided.'}</p>
            <div style="margin-top: 8px;">
              <span class="btn-pill" style="cursor: default;">${e.positions_count || 0} positions</span>
            </div>
          </div>
        `).join('');
      }

      async function checkVoteStatus(election) {
        // We need an election_id and a position. For demo, check the first position.
        const positionsRes = await api.get(`/elections/${election.id}/positions`);
        if (positionsRes?.ok && positionsRes.data.length > 0) {
          const position = positionsRes.data[0];
          const res = await api.get(`/eligibility?position_id=${position.id}`);
          if (res?.ok) {
            document.getElementById('vote-status').textContent = res.data.eligible ? 'Not Cast' : 'Cast';
            document.getElementById('vote-status').style.color = res.data.eligible ? 'var(--red-badge)' : 'var(--green-live)';
          }
        }
      }

      function updateTimeRemaining(election) {
        const endTime = new Date(election.end_time).getTime();
        const now = Date.now();
        const diff = endTime - now;

        if (diff <= 0) {
          document.getElementById('time-remaining').textContent = 'Closed';
          return;
        }

        const hours = Math.floor(diff / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        document.getElementById('time-remaining').innerHTML = `${hours}<span style="font-size: 0.875rem; font-weight: var(--fw-regular);">h</span> ${minutes}<span style="font-size: 0.875rem; font-weight: var(--fw-regular);">m</span>`;
      }

      loadDashboard();
    });
  </script>
@endsection
