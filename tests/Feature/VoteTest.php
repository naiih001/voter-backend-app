<?php

namespace Tests\Feature;

use App\Enums\ElectionStatus;
use App\Enums\Role;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoteTest extends TestCase
{
    use RefreshDatabase;

    private Election $election;
    private Position $position;
    private Candidate $alice;
    private Candidate $bob;
    private User $voter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->election = Election::factory()->create([
            'status' => ElectionStatus::OPEN,
            'start_time' => now()->subDay(),
            'end_time' => now()->addWeek(),
        ]);

        $this->position = $this->election->positions()->create(['title' => 'President']);
        $this->alice = $this->position->candidates()->create(['name' => 'Alice']);
        $this->bob = $this->position->candidates()->create(['name' => 'Bob']);
        $this->voter = User::factory()->create(['role' => Role::VOTER, 'is_eligible' => true]);
    }

    public function test_check_eligibility(): void
    {
        $response = $this->actingAs($this->voter)
            ->getJson("/api/eligibility?position_id={$this->position->id}");

        $response->assertOk()
            ->assertJson(['eligible' => true]);
    }

    public function test_ineligible_user_cannot_vote(): void
    {
        $this->voter->update(['is_eligible' => false]);

        $response = $this->actingAs($this->voter)
            ->getJson("/api/eligibility?position_id={$this->position->id}");

        $response->assertForbidden()
            ->assertJson(['eligible' => false]);
    }

    public function test_cast_vote(): void
    {
        $response = $this->actingAs($this->voter)->postJson('/api/votes', [
            'position_id' => $this->position->id,
            'candidate_id' => $this->alice->id,
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['message', 'vote']);

        $this->assertDatabaseHas('votes', [
            'position_id' => $this->position->id,
            'candidate_id' => $this->alice->id,
            'voter_id' => $this->voter->id,
        ]);
    }

    public function test_duplicate_vote_rejected(): void
    {
        $this->actingAs($this->voter)->postJson('/api/votes', [
            'position_id' => $this->position->id,
            'candidate_id' => $this->alice->id,
        ])->assertCreated();

        // Try again
        $response = $this->actingAs($this->voter)->postJson('/api/votes', [
            'position_id' => $this->position->id,
            'candidate_id' => $this->bob->id,
        ]);

        $response->assertUnprocessable();
    }

    public function test_cannot_vote_for_wrong_position(): void
    {
        $otherPosition = $this->election->positions()->create(['title' => 'VP']);
        $otherCandidate = $otherPosition->candidates()->create(['name' => 'Charlie']);

        $response = $this->actingAs($this->voter)->postJson('/api/votes', [
            'position_id' => $this->position->id,
            'candidate_id' => $otherCandidate->id,
        ]);

        $response->assertUnprocessable();
    }

    public function test_ineligible_user_cannot_cast_vote(): void
    {
        $this->voter->update(['is_eligible' => false]);

        $response = $this->actingAs($this->voter)->postJson('/api/votes', [
            'position_id' => $this->position->id,
            'candidate_id' => $this->alice->id,
        ]);

        $response->assertUnprocessable();
    }

    public function test_my_votes(): void
    {
        $this->actingAs($this->voter)->postJson('/api/votes', [
            'position_id' => $this->position->id,
            'candidate_id' => $this->alice->id,
        ])->assertCreated();

        $response = $this->actingAs($this->voter)->getJson('/api/votes/mine');

        $response->assertOk()
            ->assertJsonCount(1);
    }

    public function test_voters_can_see_positions_and_candidates(): void
    {
        $this->actingAs($this->voter)->getJson('/api/positions')
            ->assertOk();

        $this->actingAs($this->voter)->getJson('/api/candidates')
            ->assertOk();

        $this->actingAs($this->voter)
            ->getJson("/api/positions/{$this->position->id}")
            ->assertOk();
    }

    public function test_voter_cannot_manage_positions(): void
    {
        $this->actingAs($this->voter)->postJson('/api/positions', [
            'election_id' => $this->election->id,
            'title' => 'Hack',
        ])->assertForbidden();

        $this->actingAs($this->voter)
            ->putJson("/api/positions/{$this->position->id}", ['title' => 'Hacked'])
            ->assertForbidden();

        $this->actingAs($this->voter)
            ->deleteJson("/api/positions/{$this->position->id}")
            ->assertForbidden();
    }

    public function test_voter_cannot_manage_candidates(): void
    {
        $this->actingAs($this->voter)->postJson('/api/candidates', [
            'position_id' => $this->position->id,
            'name' => 'Hacker',
        ])->assertForbidden();
    }

    public function test_unauthenticated_cannot_vote(): void
    {
        $this->postJson('/api/votes', [
            'position_id' => $this->position->id,
            'candidate_id' => $this->alice->id,
        ])->assertUnauthorized();
    }
}
