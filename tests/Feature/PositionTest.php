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

class PositionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $voter;
    private Election $election;
    private Position $position;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => Role::ADMIN]);
        $this->voter = User::factory()->create(['role' => Role::VOTER]);
        $this->election = Election::factory()->create(['created_by' => $this->admin->id]);
        $this->position = $this->election->positions()->create(['title' => 'President']);
    }

    // ── GET /api/elections/{election}/positions ──

    public function test_voter_can_get_positions_for_election(): void
    {
        $this->position->candidates()->create(['name' => 'Alice']);

        $response = $this->actingAs($this->voter)
            ->getJson("/api/elections/{$this->election->id}/positions");

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.candidates_count', 1);
    }

    public function test_empty_election_returns_no_positions(): void
    {
        $emptyElection = Election::factory()->create(['created_by' => $this->admin->id]);

        $response = $this->actingAs($this->voter)
            ->getJson("/api/elections/{$emptyElection->id}/positions");

        $response->assertOk()
            ->assertJsonCount(0);
    }

    // ── GET /api/positions/{position}/candidates ──

    public function test_voter_can_get_candidates_for_position(): void
    {
        $this->position->candidates()->create(['name' => 'Alice']);
        $this->position->candidates()->create(['name' => 'Bob']);

        $response = $this->actingAs($this->voter)
            ->getJson("/api/positions/{$this->position->id}/candidates");

        $response->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.name', 'Alice');
    }

    public function test_candidates_ordered_by_name(): void
    {
        $this->position->candidates()->create(['name' => 'Zara']);
        $this->position->candidates()->create(['name' => 'Alice']);

        $response = $this->actingAs($this->voter)
            ->getJson("/api/positions/{$this->position->id}/candidates");

        $response->assertOk()
            ->assertJsonPath('0.name', 'Alice')
            ->assertJsonPath('1.name', 'Zara');
    }

    // ── POST /api/positions (admin) ──

    public function test_admin_can_create_position(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/positions', [
            'election_id' => $this->election->id,
            'title' => 'Vice President',
        ]);

        $response->assertCreated()
            ->assertJsonPath('position.title', 'Vice President');

        $this->assertDatabaseHas('positions', [
            'election_id' => $this->election->id,
            'title' => 'Vice President',
        ]);
    }

    public function test_admin_create_position_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/positions', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['election_id', 'title']);
    }

    public function test_voter_cannot_create_position(): void
    {
        $this->actingAs($this->voter)->postJson('/api/positions', [
            'election_id' => $this->election->id,
            'title' => 'Hack',
        ])->assertForbidden();
    }

    // ── PUT /api/positions/{position} (admin) ──

    public function test_admin_can_update_position(): void
    {
        $response = $this->actingAs($this->admin)
            ->putJson("/api/positions/{$this->position->id}", [
                'title' => 'President Updated',
            ]);

        $response->assertOk()
            ->assertJsonPath('position.title', 'President Updated');
    }

    public function test_voter_cannot_update_position(): void
    {
        $this->actingAs($this->voter)
            ->putJson("/api/positions/{$this->position->id}", ['title' => 'Hacked'])
            ->assertForbidden();
    }

    // ── DELETE /api/positions/{position} (admin) ──

    public function test_admin_can_delete_position(): void
    {
        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/positions/{$this->position->id}");

        $response->assertOk()
            ->assertJson(['message' => 'Position removed.']);

        $this->assertDatabaseMissing('positions', ['id' => $this->position->id]);
    }

    public function test_voter_cannot_delete_position(): void
    {
        $this->actingAs($this->voter)
            ->deleteJson("/api/positions/{$this->position->id}")
            ->assertForbidden();
    }
}
