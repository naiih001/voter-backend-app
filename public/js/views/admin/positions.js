// ============================================================
//  Admin — Positions (CRUD)
// ============================================================

import { api } from '../../core/api.js';
import { ui } from '../../core/ui.js';

export async function view(params, root) {
  root.className = 'container';
  root.innerHTML = `
    <div class="flex-between mt-32">
      <div>
        <h1 class="page-title">Positions</h1>
        <p class="page-subtitle">Manage the positions contestants can run for.</p>
      </div>
      <button class="btn-primary" id="create-btn">+ New Position</button>
    </div>
    <div id="positions-table" class="card mt-24" style="padding:0;"></div>`;

  const tableEl = root.querySelector('#positions-table');
  tableEl.appendChild(ui.loadingBlock('Loading positions…'));

  // election options for the create form
  let elections = [];
  const electionsRes = await api.get('/elections');
  if (electionsRes.ok) elections = electionsRes.data;

  root.querySelector('#create-btn').addEventListener('click', () => positionModal(null));

  async function load() {
    const res = await api.get('/positions');
    if (!res.ok) {
      tableEl.innerHTML = '<div class="alert alert-error" style="margin:16px;">Failed to load positions.</div>';
      return;
    }
    const positions = res.data;
    if (positions.length === 0) {
      tableEl.innerHTML = '';
      tableEl.appendChild(ui.emptyState('No positions yet. Create a position under an election.'));
      return;
    }
    tableEl.innerHTML = `
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Title</th><th>Election</th><th>Candidates</th><th style="text-align:right;">Actions</th></tr></thead>
          <tbody>
            ${positions.map((p) => `
              <tr data-id="${p.id}">
                <td><strong>${ui.escapeHtml(p.title)}</strong>${p.description ? `<div class="text-muted" style="font-size:.8125rem;">${ui.escapeHtml(p.description.slice(0, 80))}</div>` : ''}</td>
                <td class="text-muted">${ui.escapeHtml(p.election?.title || '—')}</td>
                <td>${p.candidates_count || 0}</td>
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
      const pos = positions.find((p) => String(p.id) === String(id));
      row.querySelector('.act-edit').addEventListener('click', () => positionModal(pos));
      row.querySelector('.act-delete').addEventListener('click', () => deletePosition(pos));
    });
  }

  function positionModal(position) {
    const isEdit = !!position;
    const electionId = ui.field.select({
      label: 'Election',
      value: position?.election_id || elections[0]?.id || '',
      options: elections.map((e) => ({ value: String(e.id), label: e.title })),
    });
    const title = ui.field.text({ label: 'Title', value: position?.title || '', placeholder: 'e.g. President' });
    const description = ui.field.textarea({ label: 'Description', value: position?.description || '' });

    const form = ui.el('div', {}, electionId.node, title.node, description.node);
    const alertEl = ui.el('div', { class: 'alert', style: 'display:none;margin-bottom:16px;' });
    form.insertBefore(alertEl, form.firstChild);

    const m = ui.openModal({
      title: isEdit ? 'Edit Position' : 'New Position',
      body: form,
      actions: [
        { label: 'Cancel', class: 'btn-outline', onClick: () => ui.closeModal() },
        { label: isEdit ? 'Save Changes' : 'Create Position', class: 'btn-primary', onClick: () => {} },
      ],
    });
    const submitBtn = m.actionsNode.querySelector('.btn-primary');

    submitBtn.addEventListener('click', async () => {
      const payload = { title: title.value(), description: description.value() };
      if (!isEdit) payload.election_id = parseInt(electionId.value(), 10);
      if (!payload.title) { ui.showAlert(alertEl, 'Please enter a title.'); return; }
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner"></span> Saving…';
      ui.hideAlert(alertEl);
      const res = isEdit ? await api.put(`/positions/${position.id}`, payload) : await api.post('/positions', payload);
      if (res.ok) {
        ui.toast(`Position "${payload.title}" ${isEdit ? 'updated.' : 'created.'}`, 'success');
        ui.closeModal();
        load();
      } else {
        let msg = res.data?.message || 'Could not save position.';
        if (res.data?.errors) msg = Object.values(res.data.errors).flat().join(' ');
        ui.showAlert(alertEl, msg);
        ui.toast(msg, 'error');
        submitBtn.disabled = false;
        submitBtn.textContent = isEdit ? 'Save Changes' : 'Create Position';
      }
    });
  }

  async function deletePosition(position) {
    const ok = await ui.confirmDialog(`Delete position "${position.title}"? Its candidates and votes will be removed too.`);
    if (!ok) return;
    const res = await api.del(`/positions/${position.id}`);
    if (res.ok) {
      ui.toast(`Position "${position.title}" deleted.`, 'success');
      load();
    } else {
      ui.toast(res.data?.message || 'Could not delete position.', 'error');
    }
  }

  load();
}
