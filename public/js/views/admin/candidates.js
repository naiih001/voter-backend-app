// ============================================================
//  Admin — Candidates (CRUD)
// ============================================================

import { api } from '../../core/api.js';
import { ui } from '../../core/ui.js';

export async function view(params, root) {
  root.className = 'container';
  root.innerHTML = `
    <div class="flex-between mt-32">
      <div>
        <h1 class="page-title">Candidates</h1>
        <p class="page-subtitle">Manage candidates contesting each position.</p>
      </div>
      <button class="btn-primary" id="create-btn">+ New Candidate</button>
    </div>
    <div class="flex-between mt-16">
      <div class="search-input">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" id="search" class="candidate-search" placeholder="Search by name…">
      </div>
    </div>
    <div id="candidates-table" class="card mt-16" style="padding:0;"></div>`;

  const tableEl = root.querySelector('#candidates-table');

  let positions = [];
  const positionsRes = await api.get('/positions');
  if (positionsRes.ok) positions = positionsRes.data;

  root.querySelector('#create-btn').addEventListener('click', () => candidateModal(null));
  root.querySelector('#search').addEventListener('input', () => load());

  async function load() {
    tableEl.innerHTML = '';
    tableEl.appendChild(ui.loadingBlock('Loading candidates…'));
    const q = root.querySelector('#search').value.trim();
    const res = await api.get('/candidates' + (q ? `?position_id=&q=${encodeURIComponent(q)}` : ''));
    if (!res.ok) {
      tableEl.innerHTML = '<div class="alert alert-error" style="margin:16px;">Failed to load candidates.</div>';
      return;
    }
    let candidates = res.data;
    if (q) {
      candidates = candidates.filter((c) => c.name.toLowerCase().includes(q.toLowerCase()));
    }
    if (candidates.length === 0) {
      tableEl.innerHTML = '';
      tableEl.appendChild(ui.emptyState('No candidates found.'));
      return;
    }
    tableEl.innerHTML = `
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Name</th><th>Position</th><th>Matric</th><th>Manifesto</th><th style="text-align:right;">Actions</th></tr></thead>
          <tbody>
            ${candidates.map((c) => `
              <tr data-id="${c.id}">
                <td><strong>${ui.escapeHtml(c.name)}</strong></td>
                <td class="text-muted">${ui.escapeHtml(c.position?.title || '—')}</td>
                <td class="text-muted">${ui.escapeHtml(c.matric_number || '—')}</td>
                <td class="text-muted" style="max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${ui.escapeHtml(c.manifesto || '—')}</td>
                <td style="text-align:right;white-space:nowrap;">
                  <button class="btn-pill act-edit">Edit</button>
                  <button class="btn-pill act-delete">Delete</button>
                </td>
              </tr>`).join('')}
          </tbody>
        </table>
      </div>`;

    tableEl.querySelectorAll('tr[data-id]').forEach((row) => {
      const id = row.dataset.id;
      const cand = candidates.find((c) => String(c.id) === String(id));
      row.querySelector('.act-edit').addEventListener('click', () => candidateModal(cand));
      row.querySelector('.act-delete').addEventListener('click', () => deleteCandidate(cand));
    });
  }

  function candidateModal(candidate) {
    const isEdit = !!candidate;
    const positionId = ui.field.select({
      label: 'Position',
      value: candidate?.position_id || positions[0]?.id || '',
      options: positions.map((p) => ({ value: String(p.id), label: p.title })),
    });
    const name = ui.field.text({ label: 'Full Name', value: candidate?.name || '' });
    const matric = ui.field.text({ label: 'Matric Number (optional)', value: candidate?.matric_number || '' });
    const manifesto = ui.field.textarea({ label: 'Manifesto', value: candidate?.manifesto || '' });

    const form = ui.el('div', {}, positionId.node, name.node, matric.node, manifesto.node);
    const alertEl = ui.el('div', { class: 'alert', style: 'display:none;margin-bottom:16px;' });
    form.insertBefore(alertEl, form.firstChild);

    const m = ui.openModal({
      title: isEdit ? 'Edit Candidate' : 'New Candidate',
      body: form,
      actions: [
        { label: 'Cancel', class: 'btn-outline', onClick: () => ui.closeModal() },
        { label: isEdit ? 'Save Changes' : 'Create Candidate', class: 'btn-primary', onClick: () => {} },
      ],
    });
    const submitBtn = m.actionsNode.querySelector('.btn-primary');

    submitBtn.addEventListener('click', async () => {
      const payload = {
        position_id: parseInt(positionId.value(), 10),
        name: name.value(),
        matric_number: matric.value(),
        manifesto: manifesto.value(),
      };
      if (!payload.name) { ui.showAlert(alertEl, 'Please enter a name.'); return; }
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner"></span> Saving…';
      ui.hideAlert(alertEl);
      const res = isEdit ? await api.put(`/candidates/${candidate.id}`, payload) : await api.post('/candidates', payload);
      if (res.ok) {
        ui.toast(`Candidate "${payload.name}" ${isEdit ? 'updated.' : 'created.'}`, 'success');
        ui.closeModal();
        load();
      } else {
        let msg = res.data?.message || 'Could not save candidate.';
        if (res.data?.errors) msg = Object.values(res.data.errors).flat().join(' ');
        ui.showAlert(alertEl, msg);
        ui.toast(msg, 'error');
        submitBtn.disabled = false;
        submitBtn.textContent = isEdit ? 'Save Changes' : 'Create Candidate';
      }
    });
  }

  async function deleteCandidate(candidate) {
    const ok = await ui.confirmDialog(`Delete candidate "${candidate.name}"?`);
    if (!ok) return;
    const res = await api.del(`/candidates/${candidate.id}`);
    if (res.ok) {
      ui.toast(`Candidate "${candidate.name}" deleted.`, 'success');
      load();
    } else {
      ui.toast(res.data?.message || 'Could not delete candidate.', 'error');
    }
  }

  load();
}
