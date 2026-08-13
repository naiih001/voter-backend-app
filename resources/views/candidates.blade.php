@extends('layouts.app')

@section('title', 'UniVote EVS — Candidates')

@section('navbar')
  <nav class="navbar">
    <div class="nav-logo">UniVote EVS</div>
    <div class="nav-links">
      <a href="/dashboard">Home</a>
      <a href="/candidates" class="active">Candidates</a>
      <a href="/vote">Vote</a>
    </div>
    <button class="hamburger" aria-label="Toggle navigation">
      <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
    <div class="nav-actions">
      <button class="nav-bell" aria-label="Notifications">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
        <span class="badge" id="notif-badge">0</span>
      </button>
      <div class="avatar-circle" data-user-initials>SJ</div>
    </div>
  </nav>
@endsection

@section('content')
  <main class="container">
    <h1 class="page-title mt-32">Candidate Directory</h1>
    <p class="page-subtitle" style="max-width: 600px;">Review the profiles and manifestos of all candidates standing for election. Ensure you are informed before casting your ballot.</p>

    <div class="alert" id="candidates-alert" style="display: none;"></div>

    <div class="flex-between mt-24">
      <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <button class="btn-pill active filter-pill" data-position="all">All Positions</button>
        <div id="position-filters"></div>
      </div>
      <div class="search-input">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" class="candidate-search" placeholder="Search candidates...">
      </div>
    </div>

    <div class="candidate-grid" id="candidate-grid">
      <p class="text-muted" style="grid-column: 1/-1; padding: 40px 0; text-align: center;">Loading candidates...</p>
    </div>

    <div class="text-center mt-32">
      <button class="btn-outline" id="load-more" style="display: none;">Load More Candidates</button>
    </div>
  </main>
@endsection

@section('footer')
  <footer class="footer">
    <span>&copy; 2024 University Electronic Voting System. All rights reserved. Secure &amp; Encrypted.</span>
    <div class="footer-links">
      <a href="#">Security Policy</a>
      <span>&middot;</span>
      <a href="#">Terms of Participation</a>
      <span>&middot;</span>
      <a href="#">Contact Support</a>
    </div>
  </footer>
@endsection

@section('scripts')
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      requireAuth();

      // Hamburger
      const hamburger = document.querySelector('.hamburger');
      const navLinks = document.querySelector('.nav-links');
      if (hamburger && navLinks) {
        hamburger.addEventListener('click', () => navLinks.classList.toggle('open'));
      }

      const alertEl = document.getElementById('candidates-alert');
      let allCandidates = [];
      let positions = [];
      let currentFilter = 'all';

      // Color palette for avatars
      const avatarColors = ['#16A34A', '#2563EB', '#F97316', '#9333EA', '#DC2626', '#0891B2'];
      const headerColors = ['#DCFCE7', '#DBEAFE', '#FEF3C7', '#F3E8FF', '#FEE2E2', '#CFFAFE'];

      async function loadCandidates() {
        const res = await api.get('/candidates');
        if (!res?.ok) {
          showError(alertEl, 'Failed to load candidates.');
          return;
        }

        allCandidates = res.data;
        await loadPositions();
        renderFilters();
        renderCandidates(allCandidates);
      }

      async function loadPositions() {
        const res = await api.get('/positions');
        if (res?.ok) {
          positions = res.data;
        }
      }

      function renderFilters() {
        const container = document.getElementById('position-filters');
        container.innerHTML = positions.map(p =>
          `<button class="btn-pill filter-pill" data-position="${p.id}">${p.title}</button>`
        ).join('');

        // Re-bind filter events
        document.querySelectorAll('.filter-pill').forEach(pill => {
          pill.addEventListener('click', () => {
            document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            currentFilter = pill.dataset.position;
            applyFilters();
          });
        });
      }

      function renderCandidates(candidates) {
        const grid = document.getElementById('candidate-grid');
        if (candidates.length === 0) {
          grid.innerHTML = '<p class="text-muted" style="grid-column: 1/-1; padding: 40px 0; text-align: center;">No candidates found.</p>';
          return;
        }

        grid.innerHTML = candidates.map((c, i) => {
          const colorIdx = i % avatarColors.length;
          const initials = getInitials(c.name);
          const positionTitle = c.position?.title || 'Candidate';
          const faculty = c.manifesto ? c.manifesto.split(' ').slice(0, 3).join(' ') + '...' : 'No manifesto';

          return `
            <div class="candidate-card" data-position="${c.position_id}" data-name="${c.name.toLowerCase()}">
              <div class="candidate-card-header" style="background: ${headerColors[colorIdx]};">
                <div class="candidate-avatar-lg" style="background: ${avatarColors[colorIdx]};">${initials}</div>
                <div class="verified-badge">Verified</div>
              </div>
              <div class="candidate-card-body">
                <div class="position">${positionTitle}</div>
                <h3>${c.name}</h3>
                <p class="faculty">${faculty}</p>
                <a href="#" class="manifesto-link" data-candidate-id="${c.id}">View Manifesto &rarr;</a>
                <button class="btn-primary add-to-ballot" data-candidate-id="${c.id}" data-position-id="${c.position_id}">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><polyline points="16 2 12 7 8 2"/></svg>
                  Add to Ballot
                </button>
              </div>
            </div>
          `;
        }).join('');

        bindCandidateEvents();
      }

      function bindCandidateEvents() {
        document.querySelectorAll('.add-to-ballot').forEach(btn => {
          btn.addEventListener('click', (e) => {
            e.preventDefault();
            const candidateId = btn.dataset.candidateId;
            const positionId = btn.dataset.positionId;
            addToBallot(candidateId, positionId);
          });
        });

        document.querySelectorAll('.manifesto-link').forEach(link => {
          link.addEventListener('click', (e) => {
            e.preventDefault();
            const candidateId = link.dataset.candidateId;
            showManifesto(candidateId);
          });
        });
      }

      let ballot = JSON.parse(localStorage.getItem('uv_ballot') || '{}');

      function addToBallot(candidateId, positionId) {
        ballot[positionId] = candidateId;
        localStorage.setItem('uv_ballot', JSON.stringify(ballot));
        showSuccess(alertEl, 'Candidate added to your ballot! Proceed to vote when ready.');
        setTimeout(() => alertEl.style.display = 'none', 3000);
      }

      async function showManifesto(candidateId) {
        const res = await api.get(`/candidates/${candidateId}`);
        if (res?.ok) {
          const c = res.data;
          alert(`${c.name} — ${c.position?.title || ''}\n\n${c.manifesto || 'No manifesto available.'}`);
        }
      }

      function applyFilters() {
        const searchQuery = document.querySelector('.candidate-search')?.value.toLowerCase() || '';
        let filtered = allCandidates;

        if (currentFilter !== 'all') {
          filtered = filtered.filter(c => String(c.position_id) === String(currentFilter));
        }

        if (searchQuery) {
          filtered = filtered.filter(c =>
            c.name.toLowerCase().includes(searchQuery) ||
            (c.manifesto || '').toLowerCase().includes(searchQuery)
          );
        }

        renderCandidates(filtered);
      }

      // Search
      document.querySelector('.candidate-search')?.addEventListener('input', applyFilters);

      loadCandidates();
    });
  </script>
@endsection
