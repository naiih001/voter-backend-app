// ============================================================
//  Voter dashboard
// ============================================================

import { api } from '../core/api.js';
import { store } from '../core/store.js';
import { ui } from '../core/ui.js';
import { navigate } from '../core/router.js';

function timeRemaining(endTime) {
  const diff = new Date(endTime).getTime() - Date.now();
  if (diff <= 0) return 'Closed';
  const h = Math.floor(diff / (1000 * 60 * 60));
  const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
  return `${h}<span style="font-size:.875rem;font-weight:400;">h</span> ${m}<span style="font-size:.875rem;font-weight:400;">m</span>`;
}

function ballotIcon() {
  return `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 010 14.14M15.54 8.46a5 5 0 010 7.07"/></svg>`;
}

export async function view(params, root) {
  root.className = 'container';
  await store.refreshUser();
  const user = store.getUser();

  root.innerHTML = `
    <div class="flex-between mt-32" style="align-items:flex-start;">
      <div>
        <h1 class="page-title">Hello, <span id="user-name">${ui.escapeHtml(user?.name || '')}</span></h1>
        <p class="page-subtitle">Welcome back to the University Electronic Voting System.</p>
      </div>
      <div style="display:flex;align-items:center;gap:8px;border:1px solid var(--green-live);border-radius:999px;padding:6px 14px;font-size:.875rem;color:var(--text-secondary);white-space:nowrap;">
        <span style="width:8px;height:8px;border-radius:50%;background:var(--green-live);"></span>
        <span id="election-status">Loading…</span>
      </div>
    </div>

    <div class="alert" id="dashboard-alert" style="display:none;margin-top:16px;"></div>

    <div class="stat-cards">
      <div class="stat-card">
        <div class="icon" style="background:var(--blue-light);color:var(--blue-primary);">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
        </div>
        <div class="label">Verification</div>
        <div class="value" style="font-size:1.125rem;">Eligibility</div>
        <div class="sub-value" style="font-size:1.25rem;font-weight:700;" id="eligibility-status">…</div>
      </div>
      <div class="stat-card">
        <div class="icon" style="background:#FEE2E2;color:var(--red-badge);">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><polyline points="16 2 12 7 8 2"/></svg>
        </div>
        <div class="label">Status</div>
        <div class="value" style="font-size:1.125rem;">Vote Status</div>
        <div class="sub-value" style="font-size:1.25rem;font-weight:700;" id="vote-status">Loading</div>
      </div>
      <div class="stat-card">
        <div class="icon" style="background:var(--bg-page);color:var(--text-muted);">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="label">Deadline</div>
        <div class="value" style="font-size:1.125rem;">Time Remaining</div>
        <div class="sub-value" style="font-size:1.25rem;font-weight:700;" id="time-remaining">--</div>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;margin-top:24px;">
      <div class="card">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
          <div style="width:32px;height:32px;border-radius:8px;background:var(--blue-light);display:flex;align-items:center;justify-content:center;color:var(--blue-primary);">${ballotIcon()}</div>
          <h3 class="section-title">Active Elections</h3>
        </div>
        <div id="elections-list"><p class="text-muted" style="padding:20px 0;text-align:center;">Loading elections…</p></div>
        <a href="/candidates" data-link class="link-blue" style="display:inline-block;margin-top:12px;font-size:.875rem;">View All Candidates →</a>
      </div>
      <div class="cta-card">
        <div class="cta-icon">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--blue-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><polyline points="16 2 12 7 8 2"/></svg>
        </div>
        <h3>Ready to shape the future?</h3>
        <p>Cast your vote for the next student leaders.</p>
        <a href="/vote" data-link class="btn-primary" style="width:100%;margin-top:8px;">Proceed to Vote Now →</a>
        <div style="display:flex;align-items:center;gap:6px;margin-top:8px;font-size:.75rem;color:var(--text-muted);">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
          256-bit Encrypted Session
        </div>
      </div>
    </div>`;

  const alertEl = root.querySelector('#dashboard-alert');

  root.querySelector('#eligibility-status').textContent = user?.is_eligible ? 'Eligible' : 'Not Eligible';
  root.querySelector('#eligibility-status').style.color = user?.is_eligible ? 'var(--green-live)' : 'var(--red-badge)';

  const electionsRes = await api.get('/elections');
  if (!electionsRes.ok) {
    ui.showAlert(alertEl, 'Failed to load elections.');
    return;
  }
  const elections = electionsRes.data;
  renderElections(root, elections);

  if (elections.length > 0) {
    const election = elections[0];
    root.querySelector('#election-status').textContent = 'Live: ' + election.title;
    root.querySelector('#time-remaining').innerHTML = timeRemaining(election.end_time);
    checkVoteStatus(root, election);
  } else {
    root.querySelector('#election-status').textContent = 'No Active Elections';
    root.querySelector('#elections-list').innerHTML = '<p class="text-muted" style="padding:20px 0;text-align:center;">No elections are currently open for voting.</p>';
  }
}

function renderElections(root, elections) {
  const container = root.querySelector('#elections-list');
  if (elections.length === 0) {
    container.innerHTML = '<p class="text-muted">No active elections at this time.</p>';
    return;
  }
  container.innerHTML = elections.map((e) => `
    <div class="announcement-item">
      <div class="announcement-header">
        <h4>${ui.escapeHtml(e.title)}</h4>
        <span class="announcement-time">${ui.escapeHtml(ui.formatDate(e.start_time))}</span>
      </div>
      <p class="announcement-desc">${ui.escapeHtml(e.description || 'No description provided.')}</p>
      <div style="margin-top:8px;">
        <span class="btn-pill" style="cursor:default;">${e.positions_count || 0} positions</span>
      </div>
    </div>`).join('');
}

async function checkVoteStatus(root, election) {
  const positionsRes = await api.get(`/elections/${election.id}/positions`);
  const statusEl = root.querySelector('#vote-status');
  if (positionsRes.ok && positionsRes.data.length > 0) {
    const position = positionsRes.data[0];
    const res = await api.get(`/eligibility?position_id=${position.id}`);
    if (res.ok) {
      const cast = !res.data.eligible;
      statusEl.textContent = cast ? 'Cast' : 'Not Cast';
      statusEl.style.color = cast ? 'var(--green-live)' : 'var(--red-badge)';
    } else {
      // 403 => not eligible / election inactive / already voted
      statusEl.textContent = 'Cast';
      statusEl.style.color = 'var(--green-live)';
    }
  } else {
    statusEl.textContent = '—';
  }
}
