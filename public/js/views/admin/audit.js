// ============================================================
//  Admin — Audit Log (paginated)
// ============================================================

import { api } from '../../core/api.js';
import { ui } from '../../core/ui.js';

export async function view(params, root) {
  root.className = 'container';
  root.innerHTML = `
    <h1 class="page-title mt-32">Audit Log</h1>
    <p class="page-subtitle">A record of administrative actions across the system.</p>
    <div class="toolbar mt-16">
      <div class="search-input">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" id="action-filter" class="candidate-search" placeholder="Filter by action…">
      </div>
      <div class="search-input">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" id="user-filter" class="candidate-search" placeholder="Filter by user id…">
      </div>
    </div>
    <div id="audit-table" class="card responsive-table-card mt-16" style="padding:0;"></div>
    <div class="text-center mt-16">
      <button class="btn-outline" id="load-more" style="display:none;">Load More</button>
    </div>`;

  const tableEl = root.querySelector('#audit-table');
  let page = 1;
  let lastPage = 1;
  let currentAction = '';
  let currentUser = '';

  const actionInput = root.querySelector('#action-filter');
  const userInput = root.querySelector('#user-filter');
  const loadMoreBtn = root.querySelector('#load-more');

  let debounce;
  const onFilter = () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
      currentAction = actionInput.value.trim();
      currentUser = userInput.value.trim();
      page = 1;
      load(true);
    }, 300);
  };
  actionInput.addEventListener('input', onFilter);
  userInput.addEventListener('input', onFilter);
  loadMoreBtn.addEventListener('click', () => { page++; load(false); });

  async function load(reset) {
    const params = new URLSearchParams();
    params.set('page', String(page));
    if (currentAction) params.set('action', currentAction);
    if (currentUser) params.set('user_id', currentUser);

    if (reset) {
      tableEl.innerHTML = '';
      tableEl.appendChild(ui.loadingBlock('Loading audit log…'));
    }
    const res = await api.get('/audit-logs?' + params.toString());
    if (!res.ok) {
      tableEl.innerHTML = '<div class="alert alert-error" style="margin:16px;">Failed to load audit log.</div>';
      return;
    }
    const envelope = res.data;
    const logs = envelope.data || [];
    lastPage = envelope.last_page || 1;
    page = envelope.current_page || page;

    if (reset) tableEl.innerHTML = '';

    if (logs.length === 0 && reset) {
      tableEl.appendChild(ui.emptyState('No audit entries found.'));
      loadMoreBtn.style.display = 'none';
      return;
    }

    let tbody = tableEl.querySelector('tbody');
    if (!tbody) {
      tableEl.innerHTML = `
        <div class="table-wrap">
          <table class="table responsive-table">
            <thead><tr><th>Action</th><th>User</th><th>When</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>`;
      tbody = tableEl.querySelector('tbody');
    }

    logs.forEach((l) => {
      const tr = ui.el('tr', {},
        ui.el('td', { dataset: { label: 'Action' } }, ui.el('strong', {}, ui.escapeHtml(l.action))),
        ui.el('td', { class: 'text-muted', dataset: { label: 'User' } }, ui.escapeHtml(l.user?.name || `User #${l.user_id}`)),
        ui.el('td', { class: 'text-muted', dataset: { label: 'When' } }, ui.escapeHtml(ui.formatDate(l.created_at)))
      );
      tbody.appendChild(tr);
    });

    loadMoreBtn.style.display = page < lastPage ? 'inline-block' : 'none';
  }

  load(true);
}
