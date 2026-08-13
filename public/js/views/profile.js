// ============================================================
//  Voter profile — view + edit
// ============================================================

import { api } from '../core/api.js';
import { store } from '../core/store.js';
import { ui } from '../core/ui.js';

export async function view(params, root) {
  root.className = 'container';
  root.style.maxWidth = '640px';
  root.innerHTML = `
    <h1 class="page-title mt-32">My Profile</h1>
    <p class="page-subtitle">Update your personal details and password.</p>
    <div class="alert" id="profile-alert" style="display:none;"></div>
    <div id="profile-body" class="mt-24"></div>`;

  const alertEl = root.querySelector('#profile-alert');
  const body = root.querySelector('#profile-body');

  const res = await api.get('/user');
  if (!res.ok) {
    body.appendChild(ui.el('div', { class: 'alert alert-error' }, 'Failed to load profile.'));
    return;
  }
  const user = res.data;
  render(user);

  function render(u) {
    body.innerHTML = '';
    const card = ui.el('div', { class: 'card' },
      ui.el('div', { class: 'flex-between', style: 'align-items:flex-start;' },
        ui.el('div', {},
          ui.el('div', { class: 'avatar-circle', style: 'width:56px;height:56px;font-size:1.25rem;margin-bottom:12px;' }, ui.getInitials(u.name)),
          ui.el('h3', { class: 'section-title' }, u.name),
          ui.el('p', { class: 'text-muted' }, `${u.email}${u.matric_number ? ' · ' + u.matric_number : ''}`),
          ui.el('div', { class: 'mt-8' },
            ui.el('span', { class: `badge-pill ${u.role === 'admin' ? 'blue' : 'green'}` }, u.role === 'admin' ? 'Administrator' : 'Voter'),
            ui.el('span', { class: `badge-pill ${u.is_eligible ? 'green' : 'red'}`, style: 'margin-left:8px;' }, u.is_eligible ? 'Eligible' : 'Not Eligible')
          )
        )
      ),
      ui.el('hr', { class: 'divider' }),
      (() => {
        const nameF = ui.field.text({ label: 'Full Name', value: u.name });
        const emailF = ui.field.text({ label: 'Email Address', type: 'email', value: u.email });
        const pwF = ui.field.text({ label: 'New Password (leave blank to keep)', type: 'password' });
        const pwConfF = ui.field.text({ label: 'Confirm New Password', type: 'password' });

        const saveBtn = ui.el('button', { class: 'btn-primary', style: 'margin-top:8px;' }, 'Save Changes');
        saveBtn.addEventListener('click', async () => {
          const payload = { name: nameF.value(), email: emailF.value() };
          if (pwF.value()) {
            payload.password = pwF.value();
            payload.password_confirmation = pwConfF.value();
          }
          saveBtn.disabled = true;
          saveBtn.innerHTML = '<span class="spinner"></span> Saving…';
          ui.hideAlert(alertEl);
          const r = await api.put('/user', payload);
          if (r.ok) {
            store.setUser({ ...store.getUser(), name: r.data.user.name, email: r.data.user.email });
            ui.toast('Profile updated.', 'success');
            render({ ...u, name: r.data.user.name, email: r.data.user.email });
          } else {
            let msg = r.data?.message || 'Could not update profile.';
            if (r.data?.errors) msg = Object.values(r.data.errors).flat().join(' ');
            ui.showAlert(alertEl, msg);
            ui.toast(msg, 'error');
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save Changes';
          }
        });

        return ui.el('div', {},
          nameF.node, emailF.node, pwF.node, pwConfF.node, saveBtn);
      })()
    );
    body.appendChild(card);
  }
}
