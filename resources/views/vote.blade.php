@extends('layouts.app')

@section('title', 'UniVote EVS — Vote')

@section('navbar')
  <nav class="navbar" style="border-bottom: 1px solid var(--border);">
    <div class="nav-logo">UniVote EVS</div>
    <div class="nav-actions">
      <div style="display: flex; align-items: center; gap: 6px; border: 1px solid var(--border); border-radius: 999px; padding: 6px 14px; font-size: 0.8125rem; color: var(--text-secondary);">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
        Secure Session
      </div>
      <div class="avatar-circle" data-user-initials>SJ</div>
    </div>
  </nav>
@endsection

@section('content')
  <main class="container" style="max-width: 700px; margin: 0 auto;">
    <div id="vote-alert" class="alert" style="display: none;"></div>

    <!-- Stepper -->
    <div class="stepper mt-32" id="stepper">
      <div class="text-muted" style="padding: 20px 0; text-align: center;">Loading voting positions...</div>
    </div>

    <div id="vote-content">
      <!-- Dynamic content injected here -->
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

      const alertEl = document.getElementById('vote-alert');
      let election = null;
      let positions = [];
      let currentStep = 0;
      let selections = JSON.parse(localStorage.getItem('uv_ballot') || '{}');
      const avatarColors = ['#16A34A', '#2563EB', '#F97316', '#9333EA', '#DC2626', '#0891B2'];
      const headerColors = ['#DCFCE7', '#DBEAFE', '#FEF3C7', '#F3E8FF', '#FEE2E2', '#CFFAFE'];

      async function initVote() {
        // Get first active election
        const electionsRes = await api.get('/elections');
        if (!electionsRes?.ok || electionsRes.data.length === 0) {
          showError(alertEl, 'No active elections available for voting.');
          return;
        }

        election = electionsRes.data[0];

        // Get positions for this election
        const positionsRes = await api.get(`/elections/${election.id}/positions`);
        if (!positionsRes?.ok) {
          showError(alertEl, 'Failed to load voting positions.');
          return;
        }

        positions = positionsRes.data;
        renderStepper();
        renderCurrentStep();
      }

      function renderStepper() {
        const stepper = document.getElementById('stepper');
        const steps = positions.map((p, i) => `
          <div class="step ${i < currentStep ? 'completed' : ''} ${i === currentStep ? 'active' : ''}" data-step="${i}">
            <div class="step-circle">${i < currentStep ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>' : i + 1}</div>
            <div class="step-label">${p.title}</div>
          </div>
          ${i < positions.length - 1 ? `<div class="step-connector ${i < currentStep ? 'completed' : ''}"></div>` : ''}
        `).join('');

        // Add Review step
        const reviewStep = `
          <div class="step ${currentStep >= positions.length ? 'active' : ''}" data-step="${positions.length}">
            <div class="step-circle">${positions.length + 1}</div>
            <div class="step-label">Review</div>
          </div>
        `;

        stepper.innerHTML = steps + reviewStep;

        stepper.querySelectorAll('.step').forEach(step => {
          step.addEventListener('click', () => {
            const stepIdx = parseInt(step.dataset.step);
            if (stepIdx <= currentStep) {
              currentStep = stepIdx;
              renderStepper();
              renderCurrentStep();
            }
          });
        });
      }

      async function renderCurrentStep() {
        const content = document.getElementById('vote-content');

        if (currentStep >= positions.length) {
          renderReview(content);
          return;
        }

        const position = positions[currentStep];
        content.innerHTML = `
          <h2 class="page-title text-center mt-24">Select ${position.title}</h2>
          <p class="page-subtitle text-center">Select exactly one candidate for this position.</p>
          <div class="vote-options mt-32" id="vote-options">
            <p class="text-muted" style="text-align: center; padding: 20px;">Loading candidates...</p>
          </div>
          <hr class="divider">
          <div class="flex-between mt-24">
            <a href="/dashboard" class="btn-outline">&larr; Previous</a>
            <button class="btn-primary btn-review" id="review-btn" ${selections[position.id] ? '' : 'disabled'}>
              ${currentStep < positions.length - 1 ? 'Next Position &rarr;' : 'Review Selection &rarr;'}
            </button>
          </div>
        `;

        // Load candidates for this position
        const candidatesRes = await api.get(`/positions/${position.id}/candidates`);
        if (candidatesRes?.ok) {
          renderVoteOptions(content, position, candidatesRes.data);
        }
      }

      function renderVoteOptions(content, position, candidates) {
        const optionsContainer = content.querySelector('#vote-options');
        const reviewBtn = content.querySelector('#review-btn');

        const options = candidates.map((c, i) => {
          const colorIdx = i % avatarColors.length;
          const selected = selections[position.id] == c.id;
          return `
            <div class="vote-option ${selected ? 'selected' : ''}" data-candidate-id="${c.id}">
              <div class="vote-option-info">
                <div class="avatar-placeholder" style="background: ${headerColors[colorIdx]}; color: ${avatarColors[colorIdx]};">${getInitials(c.name)}</div>
                <div>
                  <h4>${c.name}</h4>
                  <p>${c.manifesto ? c.manifesto.split(' ').slice(0, 4).join(' ') + '...' : 'Candidate'}</p>
                </div>
              </div>
              <div class="radio-circle"></div>
            </div>
          `;
        }).join('');

        // Add abstain option
        const abstain = `
          <div class="vote-option ${!selections[position.id] ? '' : ''}" data-candidate-id="abstain">
            <div class="vote-option-info">
              <div class="avatar-placeholder" style="background: var(--bg-page); color: var(--text-muted);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
              </div>
              <div>
                <h4 style="font-style: italic;">Abstain</h4>
                <p>Do not cast a vote for this position</p>
              </div>
            </div>
            <div class="radio-circle"></div>
          </div>
        `;

        optionsContainer.innerHTML = options + abstain;

        optionsContainer.querySelectorAll('.vote-option').forEach(opt => {
          opt.addEventListener('click', () => {
            optionsContainer.querySelectorAll('.vote-option').forEach(o => o.classList.remove('selected'));
            opt.classList.add('selected');

            const candidateId = opt.dataset.candidateId;
            if (candidateId === 'abstain') {
              delete selections[position.id];
            } else {
              selections[position.id] = candidateId;
            }
            localStorage.setItem('uv_ballot', JSON.stringify(selections));
            reviewBtn.disabled = false;
          });
        });
      }

      function renderReview(content) {
        const reviewItems = positions.map(p => {
          const candidateId = selections[p.id];
          let choiceText = 'Abstained';
          if (candidateId) {
            choiceText = 'Selected';
          }
          return `
            <div class="vote-option" style="cursor: default;">
              <div class="vote-option-info">
                <div>
                  <h4>${p.title}</h4>
                  <p>${choiceText}</p>
                </div>
              </div>
            </div>
          `;
        }).join('');

        content.innerHTML = `
          <h2 class="page-title text-center mt-24">Review Your Ballot</h2>
          <p class="page-subtitle text-center">Please confirm your selections before submitting.</p>
          <div class="vote-options mt-32">
            ${reviewItems}
          </div>
          <hr class="divider">
          <div class="flex-between mt-24">
            <button class="btn-outline" id="back-btn">&larr; Back to Edit</button>
            <button class="btn-primary" id="submit-btn">Submit Ballot &rarr;</button>
          </div>
        `;

        content.querySelector('#back-btn').addEventListener('click', () => {
          currentStep = positions.length - 1;
          renderStepper();
          renderCurrentStep();
        });

        content.querySelector('#submit-btn').addEventListener('click', submitBallot);
      }

      async function submitBallot() {
        const submitBtn = document.getElementById('submit-btn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner"></span> Submitting...';

        let successCount = 0;
        let errorCount = 0;

        for (const [positionId, candidateId] of Object.entries(selections)) {
          const res = await api.post('/votes', {
            position_id: parseInt(positionId),
            candidate_id: parseInt(candidateId),
          });

          if (res?.ok) {
            successCount++;
          } else {
            errorCount++;
            showError(alertEl, res?.data?.message || `Failed to cast vote for position ${positionId}.`);
          }
        }

        if (successCount > 0) {
          showSuccess(alertEl, `${successCount} vote(s) cast successfully!`);
          localStorage.removeItem('uv_ballot');
          selections = {};
          setTimeout(() => window.location.href = '/dashboard', 1500);
        } else if (errorCount > 0) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = 'Submit Ballot &rarr;';
        }
      }

      initVote();
    });
  </script>
@endsection
