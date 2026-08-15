// ============================================================
//  Voter elections — list + detail
// ============================================================

import { api } from '../core/api.js';
import { ui } from '../core/ui.js';
import { navigate } from '../core/router.js';
import { store } from '../core/store.js';

function avatarColor(i) { return `var(--candidate-${i % 6})`; }
function avatarBg(i) { return `var(--candidate-bg-${i % 6})`; }

export async function view(params, root) {
  root.className = 'container';
  root.innerHTML = `
    <h1 class="page-title mt-32">Elections</h1>
    <p class="page-subtitle">Browse open elections and the candidates contesting each position.</p>
    <div id="elections-list" class="mt-24"></div>`;
  const list = root.querySelector('#elections-list');
  list.appendChild(ui.loadingBlock('Loading elections…'));

  const res = await api.get('/elections');
  if (!res.ok) {
    list.innerHTML = '';
    list.appendChild(ui.el('div', { class: 'alert alert-error' }, 'Failed to load elections.'));
    return;
  }
  const elections = res.data;
  if (elections.length === 0) {
    list.innerHTML = '';
    list.appendChild(ui.emptyState('No open elections are available right now.'));
    return;
  }

  list.innerHTML = elections.map((e) => `
    <div class="card" style="margin-bottom:16px;">
      <div class="flex-between">
        <div>
          <h3 class="section-title">${ui.escapeHtml(e.title)}</h3>
          <p class="text-muted" style="font-size:.8125rem;margin-top:4px;">${ui.escapeHtml(ui.formatDate(e.start_time))} — ${ui.escapeHtml(ui.formatDate(e.end_time))}</p>
        </div>
        <a href="/elections/${e.id}" data-link class="btn-outline">View Positions</a>
      </div>
      <p class="mt-8">${ui.escapeHtml(e.description || 'No description provided.')}</p>
    </div>`).join('');
}

export async function detail(params, root) {
  root.className = 'container';
  const id = params.id;
  if (api.token && !store.getUser()) await store.refreshUser();
  const user = store.getUser();
  root.innerHTML = `
    ${user ? '<a href="/elections" data-link class="link-blue mt-32" style="display:inline-block;">← All Elections</a>' : ''}
    <div id="election-detail" class="mt-16"></div>`;
  const container = root.querySelector('#election-detail');
  container.appendChild(ui.loadingBlock('Loading election…'));

  const res = await api.get(`/elections/${id}`, false);
  if (!res.ok) {
    container.innerHTML = '';
    container.appendChild(ui.el('div', { class: 'alert alert-error' }, 'Failed to load election.'));
    return;
  }
  const election = res.data;
  const positions = election.positions || [];

  container.innerHTML = `
    <h1 class="page-title">${ui.escapeHtml(election.title)}</h1>
    <p class="page-subtitle">${ui.escapeHtml(election.description || 'No description provided.')}</p>
    <div class="flex-between mt-16">
      <span class="btn-pill" style="cursor:default;">${positions.length} positions</span>
      <div class="election-actions">
        <button class="btn-outline" id="share-election">Share election</button>
        <button class="btn-primary" id="vote-cta">${user ? 'Go to Vote →' : 'Sign in to vote'}</button>
      </div>
    </div>
    <div id="positions" class="mt-24"></div>`;

  container.querySelector('#share-election').addEventListener('click', () => {
    ui.shareLink({
      title: election.title,
      text: election.description || 'View this election on UniVote EVS.',
      url: `${location.origin}/elections/${election.id}`,
    });
  });

  container.querySelector('#vote-cta').addEventListener('click', () => {
    if (!user) {
      sessionStorage.setItem('uv_return_path', `/elections/${election.id}`);
      navigate('/login');
      return;
    }
    localStorage.setItem('uv_election_id', String(election.id));
    navigate('/vote');
  });

  const posWrap = container.querySelector('#positions');
  if (positions.length === 0) {
    posWrap.appendChild(ui.emptyState('This election has no positions yet.'));
    return;
  }

  posWrap.innerHTML = positions.map((p, pi) => `
    <div class="card" style="margin-bottom:16px;">
      <h3 class="section-title">${ui.escapeHtml(p.title)}</h3>
      <p class="text-muted" style="font-size:.8125rem;">${ui.escapeHtml(p.description || '')}</p>
      <div class="candidate-grid" style="margin-top:16px;grid-template-columns:repeat(4,1fr);">
        ${(p.candidates || []).map((c, ci) => `
          <div class="candidate-card">
            <div class="candidate-card-header" style="background:${avatarBg(pi + ci)};">
              <div class="candidate-avatar-lg" style="background:${avatarColor(pi + ci)};">${ui.escapeHtml(ui.getInitials(c.name))}</div>
            </div>
            <div class="candidate-card-body">
              <div class="position">${ui.escapeHtml(p.title)}</div>
              <h3>${ui.escapeHtml(c.name)}</h3>
              <p class="faculty">${ui.escapeHtml((c.manifesto || 'Candidate').split(' ').slice(0, 6).join(' ') + (c.manifesto && c.manifesto.split(' ').length > 6 ? '…' : ''))}</p>
            </div>
          </div>`).join('') || '<p class="text-muted" style="grid-column:1/-1;">No candidates for this position.</p>'}
      </div>
    </div>`).join('');
}
