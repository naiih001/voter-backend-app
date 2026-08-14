// ============================================================
//  Voter ballot — stepper flow + My Votes
// ============================================================

import { api } from '../core/api.js';
import { ui } from '../core/ui.js';
import { navigate } from '../core/router.js';

const PALETTE = ['#16A34A', '#2563EB', '#F97316', '#9333EA', '#DC2626', '#0891B2'];
const HEADER = ['#DCFCE7', '#DBEAFE', '#FEF3C7', '#F3E8FF', '#FEE2E2', '#CFFAFE'];

function lockIcon() {
  return `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>`;
}

function checkIcon() {
  return `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`;
}

function abstainIcon() {
  return `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>`;
}

export async function view(params, root) {
  root.className = 'container';
  root.style.maxWidth = '700px';
  root.style.margin = '0 auto';

  root.innerHTML = `
    <div class="flex-between mt-32">
      <div>
        <h1 class="page-title">Cast Your Vote</h1>
        <p class="page-subtitle">Select one candidate per position, then review.</p>
      </div>
      <button class="btn-outline" id="my-votes-btn">My Votes</button>
    </div>
    <div class="alert" id="vote-alert" style="display:none;"></div>
    <div class="stepper mt-32" id="stepper">
      <div class="text-muted" style="padding:20px 0;text-align:center;">Loading voting positions…</div>
    </div>
    <div id="vote-content"></div>`;

  const alertEl = root.querySelector('#vote-alert');
  const stepperEl = root.querySelector('#stepper');
  const contentEl = root.querySelector('#vote-content');

  root.querySelector('#my-votes-btn').addEventListener('click', () => showMyVotes());

  // Load election
  const electionsRes = await api.get('/elections');
  if (!electionsRes.ok || electionsRes.data.length === 0) {
    stepperEl.innerHTML = '';
    contentEl.innerHTML = '';
    contentEl.appendChild(ui.emptyState('No active elections are available for voting.'));
    return;
  }

  let election;
  const preselect = localStorage.getItem('uv_election_id');
  election = electionsRes.data.find((e) => String(e.id) === String(preselect)) || electionsRes.data[0];

  const positionsRes = await api.get(`/elections/${election.id}/positions`);
  if (!positionsRes.ok || positionsRes.data.length === 0) {
    stepperEl.innerHTML = '';
    contentEl.innerHTML = '';
    contentEl.appendChild(ui.emptyState('This election has no positions to vote on.'));
    return;
  }

  const positions = positionsRes.data;
  let currentStep = 0;
  let selections = JSON.parse(localStorage.getItem('uv_ballot') || '{}');
  let submitted = false;

  function renderStepper() {
    const steps = positions.map((p, i) => `
      <div class="step ${i < currentStep ? 'completed' : ''} ${i === currentStep ? 'active' : ''}" data-step="${i}">
        <div class="step-circle">${i < currentStep ? checkIcon() : i + 1}</div>
        <div class="step-label">${ui.escapeHtml(p.title)}</div>
      </div>
      ${i < positions.length - 1 ? `<div class="step-connector ${i < currentStep ? 'completed' : ''}"></div>` : ''}`).join('');

    const reviewStep = `
      <div class="step ${currentStep >= positions.length ? 'active' : ''}" data-step="${positions.length}">
        <div class="step-circle">${positions.length + 1}</div>
        <div class="step-label">Review</div>
      </div>`;

    stepperEl.innerHTML = steps + reviewStep;
    stepperEl.querySelectorAll('.step').forEach((step) => {
      step.addEventListener('click', () => {
        const idx = parseInt(step.dataset.step, 10);
        if (idx <= currentStep) {
          currentStep = idx;
          renderStepper();
          renderCurrentStep();
        }
      });
    });
  }

  async function renderCurrentStep() {
    if (currentStep >= positions.length) {
      renderReview();
      return;
    }
    const position = positions[currentStep];
    contentEl.innerHTML = `
      <h2 class="page-title text-center mt-24">Select ${ui.escapeHtml(position.title)}</h2>
      <p class="page-subtitle text-center">Select exactly one candidate for this position.</p>
      <div class="vote-options mt-32" id="vote-options">
        <p class="text-muted" style="text-align:center;padding:20px;">Loading candidates…</p>
      </div>
      <hr class="divider">
      <div class="flex-between mt-24">
        <a href="/dashboard" data-link class="btn-outline">← Previous</a>
        <button class="btn-primary" id="review-btn" ${selections[position.id] ? '' : 'disabled'}>
          ${currentStep < positions.length - 1 ? 'Next Position →' : 'Review Selection →'}
        </button>
      </div>`;

    const candidatesRes = await api.get(`/positions/${position.id}/candidates`);
    if (!candidatesRes.ok) {
      contentEl.querySelector('#vote-options').innerHTML = '<p class="text-muted" style="text-align:center;padding:20px;">Failed to load candidates.</p>';
      return;
    }
    renderVoteOptions(position, candidatesRes.data);

    contentEl.querySelector('#review-btn').addEventListener('click', () => {
      currentStep = Math.min(currentStep + 1, positions.length);
      renderStepper();
      renderCurrentStep();
    });
  }

  function renderVoteOptions(position, candidates) {
    const optionsContainer = contentEl.querySelector('#vote-options');
    const reviewBtn = contentEl.querySelector('#review-btn');

    const options = candidates.map((c, i) => {
      const ci = i % PALETTE.length;
      const selected = selections[position.id] == c.id;
      return `
        <div class="vote-option ${selected ? 'selected' : ''}" data-candidate-id="${c.id}">
          <div class="vote-option-info">
            <div class="avatar-placeholder" style="background:${HEADER[ci]};color:${PALETTE[ci]};">${ui.escapeHtml(ui.getInitials(c.name))}</div>
            <div>
              <h4>${ui.escapeHtml(c.name)}</h4>
              <p>${ui.escapeHtml(c.manifesto ? c.manifesto.split(' ').slice(0, 4).join(' ') + '…' : 'Candidate')}</p>
            </div>
          </div>
          <div class="radio-circle"></div>
        </div>`;
    }).join('');

    const abstain = `
      <div class="vote-option" data-candidate-id="abstain">
        <div class="vote-option-info">
          <div class="avatar-placeholder" style="background:var(--bg-page);color:var(--text-muted);">${abstainIcon()}</div>
          <div>
            <h4 style="font-style:italic;">Abstain</h4>
            <p>Do not cast a vote for this position</p>
          </div>
        </div>
        <div class="radio-circle"></div>
      </div>`;

    optionsContainer.innerHTML = options + abstain;
    optionsContainer.querySelectorAll('.vote-option').forEach((opt) => {
      opt.addEventListener('click', () => {
        optionsContainer.querySelectorAll('.vote-option').forEach((o) => o.classList.remove('selected'));
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

  function renderReview() {
    const reviewItems = positions.map((p) => {
      const candidateId = selections[p.id];
      const choiceText = candidateId ? 'Selected' : 'Abstained';
      return `
        <div class="vote-option" style="cursor:default;">
          <div class="vote-option-info">
            <div><h4>${ui.escapeHtml(p.title)}</h4><p>${choiceText}</p></div>
          </div>
        </div>`;
    }).join('');

    contentEl.innerHTML = `
      <h2 class="page-title text-center mt-24">Review Your Ballot</h2>
      <p class="page-subtitle text-center">Please confirm your selections before submitting.</p>
      <div class="vote-options mt-32">${reviewItems}</div>
      <hr class="divider">
      <div class="flex-between mt-24">
        <button class="btn-outline" id="back-btn">← Back to Edit</button>
        <button class="btn-primary" id="submit-btn">Submit Ballot →</button>
      </div>`;

    contentEl.querySelector('#back-btn').addEventListener('click', () => {
      currentStep = positions.length - 1;
      renderStepper();
      renderCurrentStep();
    });
    contentEl.querySelector('#submit-btn').addEventListener('click', () => submitBallot());
  }

  async function submitBallot() {
    if (submitted) return;
    const submitBtn = contentEl.querySelector('#submit-btn');
    if (Object.keys(selections).length === 0) {
      ui.toast('Select at least one candidate before submitting.', 'error');
      return;
    }
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner"></span> Submitting…';

    let successCount = 0;
    let firstError = null;

    for (const [positionId, candidateId] of Object.entries(selections)) {
      const res = await api.post('/votes', {
        position_id: parseInt(positionId, 10),
        candidate_id: parseInt(candidateId, 10),
      });
      if (res?.ok) {
        successCount++;
      } else {
        firstError = res?.data?.message || (res?.data?.errors ? Object.values(res.data.errors).flat().join(' ') : 'Failed to cast vote.');
      }
    }

    if (successCount > 0) {
      localStorage.removeItem('uv_ballot');
      selections = {};
      ui.toast('Vote cast successfully.', 'success');
      setTimeout(() => navigate('/dashboard'), 900);
    } else if (firstError) {
      ui.toast(firstError, 'error');
      submitted = true;
      submitBtn.disabled = true;
      submitBtn.textContent = 'Already Voted';
    }
  }

  async function showMyVotes() {
    const res = await api.get('/votes/mine');
    if (!res.ok) { ui.toast('Could not load your votes.', 'error'); return; }
    const votes = res.data;
    const body = votes.length === 0
      ? ui.el('p', { class: 'text-muted' }, 'You have not cast any votes yet.')
      : ui.el('div', { class: 'vote-options' },
          ...votes.map((v) => ui.el('div', { class: 'vote-option', style: 'cursor:default;' },
            ui.el('div', { class: 'vote-option-info' },
              ui.el('div', {},
                ui.el('h4', {}, v.position?.title || 'Position'),
                ui.el('p', {}, 'Voted: ' + (v.candidate?.name || 'Unknown'))
              )
            )
          ))
        );
    ui.openModal({ title: 'My Votes', body, actions: [{ label: 'Close', class: 'btn-primary', onClick: () => ui.closeModal() }] });
  }

  renderStepper();
  renderCurrentStep();
}
