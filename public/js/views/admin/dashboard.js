// ============================================================
//  Admin dashboard
// ============================================================

import { api } from '../../core/api.js';
import { ui } from '../../core/ui.js';

const STATUS_COLOR = { draft: 'orange', open: 'green', closed: 'red' };

export async function view(params, root) {
  root.className = 'container';
  root.innerHTML = `
    <h1 class="page-title mt-32">Admin Dashboard</h1>
    <p class="page-subtitle">Overview of voting activity and system counts.</p>

    <div class="section-header mt-24 section-header-end">
      <h3 class="section-title">Voting Statistics</h3>
      <div class="form-group responsive-select" style="margin-bottom:0;">
        <label class="form-label">Filter by election</label>
        <select class="form-input" id="stats-election"></select>
      </div>
    </div>

    <div class="stat-cards" id="stat-cards" style="margin-top:16px;"></div>
    <div class="card mt-24" id="by-position-card">
      <h3 class="section-title">Votes by Position</h3>
      <div id="by-position" class="mt-16"></div>
    </div>

    <div class="dashboard-grid mt-24">
      <div class="card">
        <h3 class="section-title">Recent Audit Log</h3>
        <div id="audit-list" class="mt-16"></div>
      </div>
      <div class="card">
        <h3 class="section-title">System Counts</h3>
        <div id="counts" class="mt-16"></div>
      </div>
    </div>`;

  const statCards = root.querySelector('#stat-cards');
  const byPosition = root.querySelector('#by-position');
  const auditList = root.querySelector('#audit-list');
  const countsEl = root.querySelector('#counts');
  const electionSelect = root.querySelector('#stats-election');

  // Populate election filter
  const electionsRes = await api.get('/elections');
  const allOption = ui.el('option', { value: '' }, 'All elections');
  electionSelect.appendChild(allOption);
  if (electionsRes.ok) {
    electionsRes.data.forEach((e) => {
      electionSelect.appendChild(ui.el('option', { value: String(e.id) }, e.title));
    });
  }
  electionSelect.addEventListener('change', () => loadStats(electionSelect.value));

  async function loadStats(electionId) {
    statCards.innerHTML = '';
    byPosition.innerHTML = '';
    statCards.appendChild(ui.loadingBlock('Loading statistics…'));
    const res = await api.get('/votes/stats' + (electionId ? `?election_id=${electionId}` : ''));
    statCards.innerHTML = '';
    if (!res.ok) {
      statCards.appendChild(ui.el('div', { class: 'alert alert-error' }, 'Failed to load statistics.'));
      return;
    }
    const s = res.data;
    statCards.appendChild(statCard('Total Votes', String(s.total_votes), 'var(--blue-light)', 'var(--blue-primary)'));
    statCards.appendChild(statCard('Unique Voters', String(s.unique_voters), 'var(--red-light)', 'var(--red-badge)'));

    const rows = s.by_position || [];
    if (rows.length === 0) {
      byPosition.appendChild(ui.emptyState('No votes recorded yet.'));
    } else {
      const max = Math.max(...rows.map((r) => r.vote_count), 1);
      byPosition.innerHTML = rows.map((r) => {
        const pct = Math.round((r.vote_count / max) * 100);
        const title = r.position?.title || 'Unknown position';
        return `
          <div style="margin-bottom:14px;">
            <div class="flex-between" style="margin-bottom:6px;">
              <span style="font-size:.875rem;font-weight:600;">${ui.escapeHtml(title)}</span>
              <span class="text-muted" style="font-size:.8125rem;">${r.vote_count} votes</span>
            </div>
            <div class="bar"><div class="bar-fill" style="width:${pct}%;"></div></div>
          </div>`;
      }).join('');
    }
  }

  function statCard(label, value, bg, color) {
    return ui.el('div', { class: 'stat-card' },
      ui.el('div', { class: 'icon', style: `background:${bg};color:${color};` },
        ui.el('svg', { width: '20', height: '20', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' },
          ui.el('path', { d: 'M3 3v18h18' }), ui.el('path', { d: 'M18 9l-5 5-3-3-3 3' })
        )
      ),
      ui.el('div', { class: 'label' }, label),
      ui.el('div', { class: 'value' }, value)
    );
  }

  // System counts
  countsEl.appendChild(ui.loadingBlock('Loading counts…'));
  const [usersRes, auditsRes] = await Promise.all([api.get('/users'), api.get('/audit-logs')]);
  countsEl.innerHTML = '';

  let elections = [];
  let users = [];
  let drafts = 0, opens = 0, closeds = 0;
  if (electionsRes.ok) {
    elections = electionsRes.data;
    elections.forEach((e) => { if (e.status === 'draft') drafts++; else if (e.status === 'open') opens++; else if (e.status === 'closed') closeds++; });
  }
  if (usersRes.ok) users = usersRes.data;

  countsEl.innerHTML = `
    <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border);">
      <span class="text-muted">Elections</span><strong>${elections.length}</strong></div>
    <div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border);">
      <span class="text-muted">Open / Draft / Closed</span><strong>${opens} / ${drafts} / ${closeds}</strong></div>
    <div style="display:flex;justify-content:space-between;padding:10px 0;">
      <span class="text-muted">Users</span><strong>${users.length}</strong></div>`;

  // Audit
  auditList.appendChild(ui.loadingBlock('Loading…'));
  if (!auditsRes.ok) {
    auditList.innerHTML = '<p class="text-muted">Failed to load audit log.</p>';
  } else {
    const logs = (auditsRes.data.data || auditsRes.data).slice(0, 5);
    if (logs.length === 0) {
      auditList.appendChild(ui.emptyState('No audit entries yet.'));
    } else {
      auditList.innerHTML = logs.map((l) => `
        <div class="announcement-item">
          <div class="announcement-header">
            <h4>${ui.escapeHtml(l.action)}</h4>
            <span class="announcement-time">${ui.escapeHtml(ui.formatDate(l.created_at))}</span>
          </div>
          <p class="announcement-desc">${ui.escapeHtml(l.user?.name || 'System')}</p>
        </div>`).join('');
    }
  }

  await loadStats('');
}
