// ============================================================
//  Admin — Election results
// ============================================================

import { api } from '../../core/api.js';
import { ui } from '../../core/ui.js';

export async function view(params, root) {
  root.className = 'container';
  root.innerHTML = `
    <a href="/admin/elections" data-link class="link-blue mt-32" style="display:inline-block;">← Elections</a>
    <div class="page-header mt-16 page-header-end">
      <h1 class="page-title">Election Results</h1>
      <div class="form-group responsive-select" style="margin-bottom:0;">
        <label class="form-label">Select election</label>
        <select class="form-input" id="results-election"></select>
      </div>
    </div>
    <div id="results-body" class="mt-24"></div>`;

  const select = root.querySelector('#results-election');
  const body = root.querySelector('#results-body');

  const res = await api.get('/elections');
  if (!res.ok) {
    body.appendChild(ui.el('div', { class: 'alert alert-error' }, 'Failed to load elections.'));
    return;
  }
  const elections = res.data;
  if (elections.length === 0) {
    body.appendChild(ui.emptyState('No elections available.'));
    return;
  }
  // default: preselected via route param or latest
  const defaultId = params.id || elections[elections.length - 1].id;
  elections.forEach((e) => {
    select.appendChild(ui.el('option', { value: String(e.id), selected: String(e.id) === String(defaultId) }, e.title));
  });
  select.addEventListener('change', () => load(select.value));

  async function load(id) {
    body.innerHTML = '';
    body.appendChild(ui.loadingBlock('Loading results…'));
    const r = await api.get(`/elections/${id}/results`);
    body.innerHTML = '';
    if (!r.ok) {
      body.appendChild(ui.el('div', { class: 'alert alert-error' }, 'Failed to load results.'));
      return;
    }
    const data = r.data;
    const results = data.results || [];
    if (results.length === 0) {
      body.appendChild(ui.emptyState('This election has no positions or votes yet.'));
      return;
    }
    body.innerHTML = `<h3 class="section-title mb-16">${ui.escapeHtml(data.election || 'Election')}</h3>`;
    results.forEach((pos) => {
      const total = pos.total_votes || 0;
      const card = ui.el('div', { class: 'card', style: 'margin-bottom:16px;' },
        ui.el('div', { class: 'flex-between' },
          ui.el('h4', { class: 'section-title' }, ui.escapeHtml(pos.position)),
          ui.el('span', { class: 'text-muted', style: 'font-size:.8125rem;' }, `${total} total votes`)
        )
      );
      if (!pos.candidates || pos.candidates.length === 0) {
        card.appendChild(ui.el('p', { class: 'text-muted mt-8' }, 'No candidates.'));
      } else {
        const max = Math.max(...pos.candidates.map((c) => c.votes), 1);
        pos.candidates.forEach((c) => {
          const pct = total > 0 ? Math.round((c.votes / total) * 100) : 0;
          const barPct = Math.round((c.votes / max) * 100);
          card.appendChild(ui.el('div', { class: 'mt-16' },
            ui.el('div', { class: 'flex-between', style: 'margin-bottom:6px;' },
              ui.el('span', { style: 'font-size:.875rem;font-weight:600;' }, ui.escapeHtml(c.name)),
              ui.el('span', { class: 'text-muted', style: 'font-size:.8125rem;' }, `${c.votes} votes · ${pct}%`)
            ),
            ui.el('div', { class: 'bar' }, ui.el('div', { class: 'bar-fill', style: `width:${barPct}%;` }))
          ));
        });
      }
      body.appendChild(card);
    });
  }

  load(defaultId);
}
