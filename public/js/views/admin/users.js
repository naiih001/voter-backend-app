// ============================================================
//  Admin — Users (edit role/eligibility, delete)
// ============================================================

import { api } from '../../core/api.js';
import { store } from '../../core/store.js';
import { ui } from '../../core/ui.js';

export async function view(params, root) {
  root.className = 'container';
  root.innerHTML = `
    <div class="flex-between mt-32"><div><h1 class="page-title">Users</h1><p class="page-subtitle">Manage voter and administrator accounts.</p></div><button class="btn-primary" id="create-admin">Add administrator</button></div>
    <div id="users-table" class="card mt-24" style="padding:0;"></div>`;

  const tableEl = root.querySelector('#users-table');
  tableEl.appendChild(ui.loadingBlock('Loading users…'));
  root.querySelector('#create-admin').addEventListener('click', createAdminModal);

  function createAdminModal() {
    const name = ui.field.text({ label: 'Full name' });
    const email = ui.field.text({ label: 'Email', type: 'email' });
    const password = ui.field.text({ label: 'Temporary password', type: 'password' });
    const confirm = ui.field.text({ label: 'Confirm password', type: 'password' });
    const modal = ui.openModal({ title: 'Add administrator', body: ui.el('div', {}, name.node, email.node, password.node, confirm.node), actions: [{ label: 'Cancel', class: 'btn-outline', onClick: ui.closeModal }, { label: 'Create administrator', class: 'btn-primary', onClick: () => {} }] });
    modal.actionsNode.querySelector('.btn-primary').addEventListener('click', async () => {
      const response = await api.post('/users', { name: name.value(), email: email.value(), password: password.value(), password_confirmation: confirm.value() });
      if (!response.ok) { ui.toast(response.data?.message || 'Administrator could not be created.', 'error'); return; }
      ui.closeModal(); ui.toast('Administrator created.', 'success'); load();
    });
  }

  async function load() {
    const res = await api.get('/users');
    if (!res.ok) {
      tableEl.innerHTML = '<div class="alert alert-error" style="margin:16px;">Failed to load users.</div>';
      return;
    }
    const users = res.data;
    const currentId = store.getUser()?.id;
    if (users.length === 0) {
      tableEl.innerHTML = '';
      tableEl.appendChild(ui.emptyState('No users found.'));
      return;
    }
    tableEl.innerHTML = `
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Name</th><th>Email</th><th>Matric</th><th>Role</th><th>Eligible</th><th style="text-align:right;">Actions</th></tr></thead>
          <tbody>
            ${users.map((u) => `
              <tr data-id="${u.id}">
                <td><strong>${ui.escapeHtml(u.name)}</strong></td>
                <td class="text-muted">${ui.escapeHtml(u.email)}</td>
                <td class="text-muted">${ui.escapeHtml(u.matric_number || '—')}</td>
                <td><span class="badge-pill ${u.role === 'admin' ? 'blue' : 'green'}">${ui.escapeHtml(u.role)}</span></td>
                <td><span class="badge-pill ${u.is_eligible ? 'green' : 'red'}">${u.is_eligible ? 'Eligible' : 'Not Eligible'}</span></td>
                <td style="text-align:right;white-space:nowrap;">
                  <button class="btn-pill act-edit">Edit</button>
                  <button class="btn-pill act-delete" ${String(u.id) === String(currentId) ? 'disabled style="opacity:.5;cursor:not-allowed;"' : ''}>Delete</button>
                </td>
              </tr>`).join('')}
          </tbody>
        </table>
      </div>`;

    tableEl.querySelectorAll('tr[data-id]').forEach((row) => {
      const id = row.dataset.id;
      const user = users.find((u) => String(u.id) === String(id));
      row.querySelector('.act-edit').addEventListener('click', () => userModal(user));
      const delBtn = row.querySelector('.act-delete');
      if (String(user.id) !== String(currentId)) {
        delBtn.addEventListener('click', () => deleteUser(user));
      }
    });
  }

  function userModal(user) {
    const name = ui.field.text({ label: 'Full Name', value: user?.name || '' });
    const email = ui.field.text({ label: 'Email', type: 'email', value: user?.email || '' });
    const role = ui.field.select({
      label: 'Role',
      value: user?.role || 'voter',
      options: [{ value: 'voter', label: 'Voter' }, { value: 'admin', label: 'Administrator' }],
    });
    const eligible = ui.field.checkbox({ label: 'Eligible to vote', checked: !!user?.is_eligible });

    const form = ui.el('div', {}, name.node, email.node, role.node, eligible.node);
    const alertEl = ui.el('div', { class: 'alert', style: 'display:none;margin-bottom:16px;' });
    form.insertBefore(alertEl, form.firstChild);

    const m = ui.openModal({
      title: 'Edit User',
      subtitle: user.email,
      body: form,
      actions: [
        { label: 'Cancel', class: 'btn-outline', onClick: () => ui.closeModal() },
        { label: 'Save Changes', class: 'btn-primary', onClick: () => {} },
      ],
    });
    const submitBtn = m.actionsNode.querySelector('.btn-primary');

    submitBtn.addEventListener('click', async () => {
      const payload = {
        name: name.value(),
        email: email.value(),
        role: role.value(),
        is_eligible: eligible.value(),
      };
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner"></span> Saving…';
      ui.hideAlert(alertEl);
      const res = await api.put(`/users/${user.id}`, payload);
      if (res.ok) {
        ui.toast(`User "${payload.name}" updated.`, 'success');
        ui.closeModal();
        load();
      } else {
        let msg = res.data?.message || 'Could not update user.';
        if (res.data?.errors) msg = Object.values(res.data.errors).flat().join(' ');
        ui.showAlert(alertEl, msg);
        ui.toast(msg, 'error');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Save Changes';
      }
    });
  }

  async function deleteUser(user) {
    const ok = await ui.confirmDialog(`Delete user "${user.name}"? This cannot be undone.`);
    if (!ok) return;
    const res = await api.del(`/users/${user.id}`);
    if (res.ok) {
      ui.toast(`User "${user.name}" deleted.`, 'success');
      load();
    } else {
      ui.toast(res.data?.message || 'Could not delete user.', 'error');
    }
  }

  load();
}
