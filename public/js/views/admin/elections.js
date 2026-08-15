import { api } from '../../core/api.js';
import { ui } from '../../core/ui.js';
import { navigate } from '../../core/router.js';

const STATUS_CLASS = { draft: 'orange', scheduled: 'blue', open: 'green', closed: 'red' };

function localDateTimeValue(date) {
  const pad = (value) => String(value).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function defaultVotingWindow() {
  const start = new Date();
  start.setSeconds(0, 0);
  start.setMinutes(Math.ceil(start.getMinutes() / 15) * 15);
  const end = new Date(start.getTime() + 24 * 60 * 60 * 1000);
  return { start: localDateTimeValue(start), end: localDateTimeValue(end) };
}

export async function view(params, root) {
  root.className = 'container';
  root.innerHTML = `
    <div class="page-header mt-32">
      <div><h1 class="page-title">Election office</h1><p class="page-subtitle">Prepare, publish, and review every university election from one place.</p></div>
      <button class="btn-primary" id="create-election">New election</button>
    </div>
    <div class="docket-rail mt-24">
      ${['Draft', 'Scheduled', 'Voting open', 'Results ready'].map((label) => `<div class="docket-step"><strong>Lifecycle</strong><span>${label}</span></div>`).join('')}
    </div>
    <div id="election-list" class="card responsive-table-card mt-24" style="padding:0"></div>`;

  root.querySelector('#create-election').addEventListener('click', () => electionEditor());
  const list = root.querySelector('#election-list');
  list.appendChild(ui.loadingBlock('Loading elections...'));
  const response = await api.get('/elections');
  if (!response.ok) {
    list.innerHTML = '<div class="alert alert-error" style="margin:16px">Elections could not be loaded. Try again.</div>';
    return;
  }
  if (!response.data.length) {
    list.innerHTML = '';
    list.appendChild(ui.emptyState('No elections yet. Create a draft to begin.'));
    return;
  }
  list.innerHTML = `<div class="table-wrap"><table class="table responsive-table"><thead><tr><th>Election</th><th>Window</th><th>Ballot</th><th>Status</th><th></th></tr></thead><tbody>${response.data.map((e) => `
    <tr><td data-label="Election"><strong>${ui.escapeHtml(e.title)}</strong><div class="text-muted">${ui.escapeHtml(e.description || 'No description')}</div></td>
    <td data-label="Window" class="text-muted">${ui.escapeHtml(ui.formatDate(e.start_time))}<br>${ui.escapeHtml(ui.formatDate(e.end_time))}</td>
    <td data-label="Ballot">${e.positions_count || 0} positions</td><td data-label="Status"><span class="badge-pill ${STATUS_CLASS[e.status] || ''}">${ui.escapeHtml(e.status)}</span></td>
    <td data-label="Actions" class="table-actions"><button class="btn-outline manage-election" data-id="${e.id}">Manage</button></td></tr>`).join('')}</tbody></table></div>`;
  list.querySelectorAll('.manage-election').forEach((button) => button.addEventListener('click', () => navigate(`/admin/elections/${button.dataset.id}`)));
}

export async function detail(params, root) {
  root.className = 'container';
  root.innerHTML = '<div class="mt-32" id="workspace"></div>';
  const workspace = root.querySelector('#workspace');
  workspace.appendChild(ui.loadingBlock('Opening election workspace...'));
  const response = await api.get(`/elections/${params.id}`);
  if (!response.ok) {
    workspace.innerHTML = '<div class="alert alert-error">Election could not be opened.</div>';
    return;
  }
  const election = response.data;
  const positions = election.positions || [];
  const readiness = election.readiness || { ready: false, checks: [] };
  workspace.innerHTML = `
    <a href="/admin/elections" class="link-blue">Back to elections</a>
    <div class="page-header mt-16"><div><h1 class="page-title">${ui.escapeHtml(election.title)}</h1><p class="page-subtitle">${ui.escapeHtml(election.description || 'No description provided.')}</p></div><div class="election-actions">${election.status !== 'draft' ? '<button class="btn-outline" id="share-election">Share election</button>' : ''}<span class="badge-pill ${STATUS_CLASS[election.status] || ''}">${ui.escapeHtml(election.status)}</span></div></div>
    ${docket(election)}
    <nav class="workspace-tabs" aria-label="Election workspace"><a href="#overview" class="active">Overview</a><a href="#ballot">Ballot</a><a href="#schedule">Schedule</a><a href="/admin/elections/${election.id}/results">Results</a></nav>
    <section id="overview" class="mt-24"><div class="card"><div class="section-header"><h2 class="section-title">Publication readiness</h2>${lifecycleButton(election)}</div>
      <div class="mt-16">${readiness.checks.map((check) => `<div class="announcement-item"><strong>${check.passed ? 'Ready' : 'Needs attention'}</strong><p class="text-muted">${ui.escapeHtml(check.label)}</p></div>`).join('')}</div></div></section>
    <section id="ballot" class="mt-32"><div class="section-header"><div><h2 class="section-title">Ballot structure</h2><p class="page-subtitle">Positions and candidates are locked after publication.</p></div>${election.status === 'draft' ? '<button class="btn-primary" id="add-position">Add position</button>' : ''}</div>
      <div class="mt-16" id="position-list">${positions.length ? positions.map((position) => positionBlock(position, election)).join('') : '<div class="card"><p class="text-muted">No positions yet.</p></div>'}</div></section>
    <section id="schedule" class="mt-32 card"><div class="section-header"><div><h2 class="section-title">Voting window</h2><p class="page-subtitle">Opening and closing happen automatically.</p></div>${election.status === 'draft' ? '<button class="btn-outline" id="edit-election">Edit details</button>' : ''}</div>
      <p class="mt-16"><strong>Starts</strong><br><span class="text-muted">${ui.escapeHtml(ui.formatDate(election.start_time))}</span></p><p class="mt-16"><strong>Ends</strong><br><span class="text-muted">${ui.escapeHtml(ui.formatDate(election.end_time))}</span></p></section>`;

  workspace.querySelector('#publish-election')?.addEventListener('click', () => lifecycle(election, 'publish'));
  workspace.querySelector('#share-election')?.addEventListener('click', () => {
    ui.shareLink({
      title: election.title,
      text: election.description || 'View this election on UniVote EVS.',
      url: `${location.origin}/elections/${election.id}`,
    });
  });
  workspace.querySelector('#unpublish-election')?.addEventListener('click', () => lifecycle(election, 'unpublish'));
  workspace.querySelector('#edit-election')?.addEventListener('click', () => electionEditor(election));
  workspace.querySelector('#add-position')?.addEventListener('click', () => positionEditor(election));
  workspace.querySelectorAll('.add-candidate').forEach((button) => button.addEventListener('click', () => candidateEditor(button.dataset.position)));
}

function docket(election) {
  const states = ['draft', 'scheduled', 'open', 'closed'];
  const labels = ['Preparation', 'Scheduled', 'Voting open', 'Results ready'];
  return `<div class="docket-rail mt-24">${states.map((state, i) => `<div class="docket-step ${election.status === state ? 'current' : ''}"><strong>${i + 1} of 4</strong><span>${labels[i]}</span></div>`).join('')}</div>`;
}

function lifecycleButton(election) {
  if (election.status === 'draft') return '<button class="btn-primary" id="publish-election">Publish election</button>';
  if (election.status === 'scheduled') return '<button class="btn-outline" id="unpublish-election">Return to draft</button>';
  return '<span class="text-muted">Lifecycle is automatic</span>';
}

function positionBlock(position, election) {
  return `<div class="card" style="margin-bottom:12px"><div class="section-header"><div><h3 class="section-title">${ui.escapeHtml(position.title)}</h3><p class="text-muted">${ui.escapeHtml(position.description || 'No description')}</p></div>${election.status === 'draft' ? `<button class="btn-outline add-candidate" data-position="${position.id}">Add candidate</button>` : ''}</div>
    <div class="mt-16">${position.candidates?.length ? position.candidates.map((candidate) => `<div class="announcement-item"><strong>${ui.escapeHtml(candidate.name)}</strong><p class="text-muted">${ui.escapeHtml(candidate.matric_number || 'No matric number')} · ${ui.escapeHtml(candidate.manifesto || 'No manifesto')}</p></div>`).join('') : '<p class="text-muted">No candidates yet.</p>'}</div></div>`;
}

async function lifecycle(election, action) {
  const response = await api.post(`/elections/${election.id}/${action}`, {});
  if (response.ok) { ui.toast(response.data.message, 'success'); detail({ id: election.id }, document.getElementById('app-root')); }
  else ui.toast(response.data?.message || 'Election could not be updated.', 'error');
}

function electionEditor(election = null) {
  const title = ui.field.text({ label: 'Election title', value: election?.title || '', placeholder: 'Student Union Election 2026' });
  const description = ui.field.textarea({ label: 'Description', value: election?.description || '' });
  const defaults = defaultVotingWindow();
  const startValue = election ? ui.toLocalInput(election.start_time) : defaults.start;
  const endValue = election ? ui.toLocalInput(election.end_time) : defaults.end;
  const startDate = ui.field.text({ label: 'Start date', type: 'date', value: startValue.slice(0, 10) });
  const startTime = ui.field.text({ label: 'Start time', type: 'time', value: startValue.slice(11, 16) });
  const endDate = ui.field.text({ label: 'End date', type: 'date', value: endValue.slice(0, 10) });
  const endTime = ui.field.text({ label: 'End time', type: 'time', value: endValue.slice(11, 16) });
  const body = ui.el('div', {},
    title.node,
    description.node,
    ui.el('p', { class: 'form-hint election-window-hint' }, 'Choose when voting opens and closes.'),
    ui.el('div', { class: 'form-row' }, startDate.node, startTime.node),
    ui.el('div', { class: 'form-row' }, endDate.node, endTime.node)
  );
  const modal = ui.openModal({ title: election ? 'Edit election' : 'Create election', body, actions: [{ label: 'Cancel', class: 'btn-outline', onClick: ui.closeModal }, { label: election ? 'Save changes' : 'Create draft', class: 'btn-primary', onClick: () => {} }] });
  modal.actionsNode.querySelector('.btn-primary').addEventListener('click', async () => {
    if (![startDate, startTime, endDate, endTime].every((field) => field.value())) {
      ui.toast('Choose both a date and time for the voting window.', 'error');
      return;
    }
    const payload = {
      title: title.value(),
      description: description.value(),
      start_time: ui.toBackendDate(`${startDate.value()}T${startTime.value()}`),
      end_time: ui.toBackendDate(`${endDate.value()}T${endTime.value()}`),
    };
    const response = election ? await api.put(`/elections/${election.id}`, payload) : await api.post('/elections', payload);
    if (!response.ok) { ui.toast(response.data?.message || 'Election could not be saved.', 'error'); return; }
    ui.closeModal(); ui.toast(election ? 'Election updated.' : 'Draft created.', 'success');
    navigate(`/admin/elections/${election?.id || response.data.election.id}`);
  });
}

function positionEditor(election) {
  const title = ui.field.text({ label: 'Position title', placeholder: 'President' });
  const description = ui.field.textarea({ label: 'Description' });
  const body = ui.el('div', {}, title.node, description.node);
  const modal = ui.openModal({ title: 'Add position', body, actions: [{ label: 'Cancel', class: 'btn-outline', onClick: ui.closeModal }, { label: 'Add position', class: 'btn-primary', onClick: () => {} }] });
  modal.actionsNode.querySelector('.btn-primary').addEventListener('click', async () => {
    const response = await api.post('/positions', { election_id: election.id, title: title.value(), description: description.value() });
    if (!response.ok) { ui.toast(response.data?.message || 'Position could not be added.', 'error'); return; }
    ui.closeModal(); detail({ id: election.id }, document.getElementById('app-root'));
  });
}

function candidateEditor(positionId) {
  const name = ui.field.text({ label: 'Candidate name' });
  const matric = ui.field.text({ label: 'Matric number' });
  const manifesto = ui.field.textarea({ label: 'Manifesto' });
  const body = ui.el('div', {}, name.node, matric.node, manifesto.node);
  const modal = ui.openModal({ title: 'Add candidate', body, actions: [{ label: 'Cancel', class: 'btn-outline', onClick: ui.closeModal }, { label: 'Add candidate', class: 'btn-primary', onClick: () => {} }] });
  modal.actionsNode.querySelector('.btn-primary').addEventListener('click', async () => {
    const response = await api.post('/candidates', { position_id: Number(positionId), name: name.value(), matric_number: matric.value(), manifesto: manifesto.value() });
    if (!response.ok) { ui.toast(response.data?.message || 'Candidate could not be added.', 'error'); return; }
    ui.closeModal(); location.reload();
  });
}
