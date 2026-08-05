<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $voter;
    private Election $election;
    private Position $position;
    private Candidate $candidate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => Role::ADMIN]);
        $this->voter = User::factory()->create(['role' => Role::VOTER]);
        $this->election = Election::factory()->create(['created_by' => $this->admin->id]);
        $this->position = $this->election->positions()->create(['title' => 'President']);
        $this->candidate = $this->position->candidates()->create(['name' => 'Alice']);
    }

    // ── GET /api/candidates/{candidate} ──

    public function test_voter_can_show_candidate(): void
    {
        $response = $this->actingAs($this->voter)
            ->getJson("/api/candidates/{$this->candidate->id}");

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Alice'])
            ->assertJsonPath('position.id', $this->position->id);
    }

    // ── POST /api/candidates (admin) ──

    public function test_admin_can_create_candidate(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/candidates', [
            'position_id' => $this->position->id,
            'name' => 'Bob',
        ]);

        $response->assertCreated()
            ->assertJsonPath('candidate.name', 'Bob');

        $this->assertDatabaseHas('candidates', [
            'position_id' => $this->position->id,
            'name' => 'Bob',
        ]);
    }

    public function test_admin_create_candidate_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/candidates', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['position_id', 'name']);
    }

    public function test_admin_create_candidate_validates_position_exists(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/candidates', [
            'position_id' => 9999,
            'name' => 'Ghost',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['position_id']);
    }

    public function test_voter_cannot_create_candidate(): void
    {
        $this->actingAs($this->voter)->postJson('/api/candidates', [
            'position_id' => $this->position->id,
            'name' => 'Hacker',
        ])->assertForbidden();
    }

    // ── PUT /api/candidates/{candidate} (admin) ──

    public function test_admin_can_update_candidate(): void
    {
        $response = $this->actingAs($this->admin)
            ->putJson("/api/candidates/{$this->candidate->id}", [
                'name' => 'Alice Updated',
            ]);

        $response->assertOk()
            ->assertJsonPath('candidate.name', 'Alice Updated');
    }

    public function test_voter_cannot_update_candidate(): void
    {
        $this->actingAs($this->voter)
            ->putJson("/api/candidates/{$this->candidate->id}", ['name' => 'Hacked'])
            ->assertForbidden();
    }

    // ── DELETE /api/candidates/{candidate} (admin) ──

    public function test_admin_can_delete_candidate(): void
    {
        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/candidates/{$this->candidate->id}");

        $response->assertOk()
            ->assertJson(['message' => 'Candidate removed.']);

        $this->assertDatabaseMissing('candidates', ['id' => $this->candidate->id]);
    }

    public function test_voter_cannot_delete_candidate(): void
    {
        $this->actingAs($this->voter)
            ->deleteJson("/api/candidates/{$this->candidate->id}")
            ->assertForbidden();
    }
}
