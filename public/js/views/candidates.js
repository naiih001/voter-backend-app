// ============================================================
//  Voter candidate directory
// ============================================================

import { api } from '../core/api.js';
import { ui } from '../core/ui.js';

function colorIdx(i) { return i % 6; }
function paletteVar(i) { return `var(--candidate-${i})`; }
function headerVar(i) { return `var(--candidate-bg-${i})`; }

export async function view(params, root) {
  root.className = 'container';
  root.innerHTML = `
    <h1 class="page-title mt-32">Candidate Directory</h1>
    <p class="page-subtitle" style="max-width:600px;">Review the profiles and manifestos of all candidates standing for election. Ensure you are informed before casting your ballot.</p>
    <div class="alert" id="candidates-alert" style="display:none;"></div>
    <div class="flex-between mt-24">
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <button class="btn-pill active filter-pill" data-position="all">All Positions</button>
        <div id="position-filters"></div>
      </div>
      <div class="search-input">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" class="candidate-search" placeholder="Search candidates…">
      </div>
    </div>
    <div class="candidate-grid" id="candidate-grid" style="margin-top:24px;">
      <p class="text-muted" style="grid-column:1/-1;padding:40px 0;text-align:center;">Loading candidates…</p>
    </div>`;

  const alertEl = root.querySelector('#candidates-alert');
  const grid = root.querySelector('#candidate-grid');
  const filtersEl = root.querySelector('#position-filters');
  let allCandidates = [];
  let positions = [];
  let currentFilter = 'all';

  const res = await api.get('/candidates');
  if (!res.ok) {
    ui.showAlert(alertEl, 'Failed to load candidates.');
    return;
  }
  allCandidates = res.data;

  const posRes = await api.get('/positions');
  if (posRes.ok) positions = posRes.data;

  filtersEl.innerHTML = positions.map((p) =>
    `<button class="btn-pill filter-pill" data-position="${p.id}">${ui.escapeHtml(p.title)}</button>`).join('');

  root.querySelectorAll('.filter-pill').forEach((pill) => {
    pill.addEventListener('click', () => {
      root.querySelectorAll('.filter-pill').forEach((p) => p.classList.remove('active'));
      pill.classList.add('active');
      currentFilter = pill.dataset.position;
      applyFilters();
    });
  });

  root.querySelector('.candidate-search').addEventListener('input', applyFilters);

  function applyFilters() {
    const q = root.querySelector('.candidate-search').value.toLowerCase();
    let filtered = allCandidates;
    if (currentFilter !== 'all') {
      filtered = filtered.filter((c) => String(c.position_id) === String(currentFilter));
    }
    if (q) {
      filtered = filtered.filter((c) =>
        c.name.toLowerCase().includes(q) ||
        (c.manifesto || '').toLowerCase().includes(q));
    }
    renderCandidates(filtered);
  }

  function renderCandidates(candidates) {
    if (candidates.length === 0) {
      grid.innerHTML = '<p class="text-muted" style="grid-column:1/-1;padding:40px 0;text-align:center;">No candidates found.</p>';
      return;
    }
    grid.innerHTML = candidates.map((c, i) => {
      const ci = colorIdx(i);
      const posTitle = c.position?.title || 'Candidate';
      const faculty = c.manifesto ? c.manifesto.split(' ').slice(0, 3).join(' ') + '…' : 'No manifesto';
      return `
        <div class="candidate-card" data-position="${c.position_id}" data-name="${ui.escapeHtml(c.name.toLowerCase())}">
          <div class="candidate-card-header" style="background:${headerVar(ci)};">
            <div class="candidate-avatar-lg" style="background:${paletteVar(ci)};">${ui.escapeHtml(ui.getInitials(c.name))}</div>
            <div class="verified-badge">Verified</div>
          </div>
          <div class="candidate-card-body">
            <div class="position">${ui.escapeHtml(posTitle)}</div>
            <h3>${ui.escapeHtml(c.name)}</h3>
            <p class="faculty">${ui.escapeHtml(faculty)}</p>
            <a href="#" class="manifesto-link" data-candidate-id="${c.id}">View Manifesto →</a>
            <button class="btn-primary add-to-ballot" data-candidate-id="${c.id}" data-position-id="${c.position_id}">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><polyline points="16 2 12 7 8 2"/></svg>
              Add to Ballot
            </button>
          </div>
        </div>`;
    }).join('');

    grid.querySelectorAll('.add-to-ballot').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const ballot = JSON.parse(localStorage.getItem('uv_ballot') || '{}');
        ballot[btn.dataset.positionId] = btn.dataset.candidateId;
        localStorage.setItem('uv_ballot', JSON.stringify(ballot));
        ui.toast(`${btn.closest('.candidate-card').querySelector('h3').textContent} added to your ballot.`, 'success');
      });
    });
    grid.querySelectorAll('.manifesto-link').forEach((link) => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        showManifesto(link.dataset.candidateId);
      });
    });
  }

  async function showManifesto(candidateId) {
    const cRes = await api.get(`/candidates/${candidateId}`);
    if (!cRes.ok) { ui.toast('Could not load manifesto.', 'error'); return; }
    const c = cRes.data;
    ui.openModal({
      title: c.name,
      subtitle: c.position?.title || '',
      body: ui.el('p', { style: 'white-space:pre-wrap;line-height:1.6;' }, c.manifesto || 'No manifesto available.'),
      actions: [{ label: 'Close', class: 'btn-primary', onClick: () => ui.closeModal() }],
    });
  }

  applyFilters();
}
