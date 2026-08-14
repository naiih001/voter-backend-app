// ============================================================
//  Admin — Elections (CRUD + lifecycle + results)
// ============================================================

import { api } from '../../core/api.js';
import { ui } from '../../core/ui.js';
import { navigate } from '../../core/router.js';

const STATUS_CLASS = { draft: 'orange', open: 'green', closed: 'red' };

export async function view(params, root) {
  root.className = 'container';
  root.innerHTML = `
    <div class="flex-between mt-32">
      <div>
        <h1 class="page-title">Elections</h1>
        <p class="page-subtitle">Create elections, manage their lifecycle, and view results.</p>
      </div>
      <button class="btn-primary" id="create-btn">+ New Election</button>
    </div>
    <div id="elections-table" class="card mt-24" style="padding:0;"></div>`;

  const tableEl = root.querySelector('#elections-table');
  tableEl.appendChild(ui.loadingBlock('Loading elections…'));

  root.querySelector('#create-btn').addEventListener('click', () => electionModal(null));

  async function load() {
    const res = await api.get('/elections');
    if (!res.ok) {
      tableEl.innerHTML = '<div class="alert alert-error" style="margin:16px;">Failed to load elections.</div>';
      return;
    }
    const elections = res.data;
    if (elections.length === 0) {
      tableEl.innerHTML = '';
      tableEl.appendChild(ui.emptyState('No elections yet. Create your first election.'));
      return;
    }
    tableEl.innerHTML = `
      <div class="table-wrap">
        <table class="table">
          <thead><tr>
            <th>Title</th><th>Status</th><th>Start</th><th>End</th><th>Positions</th><th style="text-align:right;">Actions</th>
          </tr></thead>
          <tbody>
            ${elections.map((e) => `
              <tr data-id="${e.id}">
                <td><strong>${ui.escapeHtml(e.title)}</strong>${e.description ? `<div class="text-muted" style="font-size:.8125rem;">${ui.escapeHtml(e.description.slice(0, 80))}</div>` : ''}</td>
                <td><span class="badge-pill ${STATUS_CLASS[e.status] || ''}">${ui.escapeHtml(e.status)}</span></td>
                <td class="text-muted">${ui.escapeHtml(ui.formatDate(e.start_time))}</td>
                <td class="text-muted">${ui.escapeHtml(ui.formatDate(e.end_time))}</td>
                <td>${e.positions_count || 0}</td>
                <td style="text-align:right;white-space:nowrap;">
                  <button class="btn-pill act-edit">Edit</button>
                  <button class="btn-pill act-toggle">${e.status === 'open' ? 'Close' : 'Open'}</button>
                  <button class="btn-pill act-results">Results</button>
                  <button class="btn-pill act-delete">Delete</button>
                </td>
              </tr>`).join('')}
          </tbody>
        </table>
      </div>`;

    tableEl.querySelectorAll('tr[data-id]').forEach((row) => {
      const id = row.dataset.id;
      const election = elections.find((e) => String(e.id) === String(id));
      row.querySelector('.act-edit').addEventListener('click', () => electionModal(election));
      row.querySelector('.act-toggle').addEventListener('click', () => toggleStatus(election));
      row.querySelector('.act-results').addEventListener('click', () => navigate(`/admin/elections/${election.id}/results`));
      row.querySelector('.act-delete').addEventListener('click', () => deleteElection(election));
    });
  }
  function electionModal(election) {
    const isEdit = !!election;
    const title = ui.field.text({ label: 'Title', value: election?.title || '', placeholder: 'e.g. Student Council 2026' });
    const description = ui.field.textarea({ label: 'Description', value: election?.description || '' });
    const start = ui.field.text({ label: 'Start Time', type: 'datetime-local', value: election ? ui.toLocalInput(election.start_time) : '' });
    const end = ui.field.text({ label: 'End Time', type: 'datetime-local', value: election ? ui.toLocalInput(election.end_time) : '' });
    const status = ui.field.select({
      label: 'Status',
      value: election?.status || 'draft',
      options: [
        { value: 'draft', label: 'Draft' },
        { value: 'open', label: 'Open' },
        { value: 'closed', label: 'Closed' },
      ],
    });

    const form = ui.el('div', {}, title.node, description.node, start.node, end.node);
    if (isEdit) form.appendChild(status.node);

    const alertEl = ui.el('div', { class: 'alert', style: 'display:none;margin-bottom:16px;' });
    form.insertBefore(alertEl, form.firstChild);

    const m = ui.openModal({
      title: isEdit ? 'Edit Election' : 'New Election',
      body: form,
      actions: [
        { label: 'Cancel', class: 'btn-outline', onClick: () => ui.closeModal() },
        { label: isEdit ? 'Save Changes' : 'Create Election', class: 'btn-primary', onClick: () => {} },
      ],
    });
    const submitBtn = m.actionsNode.querySelector('.btn-primary');

    submitBtn.addEventListener('click', async () => {
      const payload = {
        title: title.value(),
        description: description.value(),
        start_time: ui.toBackendDate(start.value()),
        end_time: ui.toBackendDate(end.value()),
      };
      if (isEdit) payload.status = status.value();
      if (!payload.title || !payload.start_time || !payload.end_time) {
        ui.showAlert(alertEl, 'Please fill in title, start time, and end time.');
        return;
      }
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner"></span> Saving…';
      ui.hideAlert(alertEl);
      const res = isEdit ? await api.put(`/elections/${election.id}`, payload) : await api.post('/elections', payload);
      if (res.ok) {
        ui.toast(`${payload.title} ${isEdit ? 'updated.' : 'created.'}`, 'success');
        ui.closeModal();
        load();
      } else {
        let msg = res.data?.message || 'Could not save election.';
        if (res.data?.errors) msg = Object.values(res.data.errors).flat().join(' ');
        ui.showAlert(alertEl, msg);
        ui.toast(msg, 'error');
        submitBtn.disabled = false;
        submitBtn.textContent = isEdit ? 'Save Changes' : 'Create Election';
      }
    });
  }

  async function toggleStatus(election) {
    const newStatus = election.status === 'open' ? 'closed' : 'open';
    const res = await api.put(`/elections/${election.id}`, { status: newStatus });
    if (res.ok) {
      ui.toast(`Election "${election.title}" ${newStatus}.`, 'success');
      load();
    } else {
      ui.toast(res.data?.message || 'Could not update status.', 'error');
    }
  }

  async function deleteElection(election) {
    const ok = await ui.confirmDialog(`Delete election "${election.title}"? This soft-deletes it and cannot be undone from the UI.`);
    if (!ok) return;
    const res = await api.del(`/elections/${election.id}`);
    if (res.ok) {
      ui.toast(`Election "${election.title}" deleted.`, 'success');
      load();
    } else {
      ui.toast(res.data?.message || 'Could not delete election.', 'error');
    }
  }

  load();
}
