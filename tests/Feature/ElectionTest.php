<?php

namespace Tests\Feature;

use App\Enums\ElectionStatus;
use App\Enums\Role;
use App\Models\Election;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ElectionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $voter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => Role::ADMIN]);
        $this->voter = User::factory()->create(['role' => Role::VOTER]);
    }

    public function test_admin_can_create_election(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/elections', [
            'title' => 'Student Council 2025',
            'description' => 'Annual election',
            'start_time' => now()->addDay(),
            'end_time' => now()->addWeek(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('election.title', 'Student Council 2025')
            ->assertJsonFragment(['status' => 'draft']);
    }

    public function test_voter_cannot_create_election(): void
    {
        $response = $this->actingAs($this->voter)->postJson('/api/elections', [
            'title' => 'Hack',
            'start_time' => now(),
            'end_time' => now()->addDay(),
        ]);

        $response->assertForbidden();
    }

    public function test_admin_can_update_election(): void
    {
        $election = Election::factory()->create(['created_by' => $this->admin->id]);

        $response = $this->actingAs($this->admin)
            ->putJson("/api/elections/{$election->id}", ['status' => 'open']);

        $response->assertOk()
            ->assertJson(['election' => ['status' => 'open']]);
    }

    public function test_voter_sees_only_active_elections(): void
    {
        // Active election (within time window)
        Election::factory()->create([
            'status' => ElectionStatus::OPEN,
            'start_time' => now()->subDay(),
            'end_time' => now()->addDay(),
        ]);

        // Future election (not active yet)
        Election::factory()->create([
            'status' => ElectionStatus::OPEN,
            'start_time' => now()->addWeek(),
            'end_time' => now()->addMonth(),
        ]);

        // Draft election
        Election::factory()->create(['status' => ElectionStatus::DRAFT]);

        $response = $this->actingAs($this->voter)->getJson('/api/elections');

        $response->assertOk()
            ->assertJsonCount(1); // Only the active one
    }

    public function test_admin_sees_all_elections(): void
    {
        Election::factory()->count(3)->create(['created_by' => $this->admin->id]);

        $response = $this->actingAs($this->admin)->getJson('/api/elections');

        $response->assertOk()
            ->assertJsonCount(3);
    }

    public function test_show_election_with_positions(): void
    {
        $election = Election::factory()->create(['published_at' => now()]);
        $position = $election->positions()->create(['title' => 'President']);
        $position->candidates()->create(['name' => 'Alice']);

        $response = $this->actingAs($this->voter)
            ->getJson("/api/elections/{$election->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'positions');
    }

    public function test_guest_can_view_published_election(): void
    {
        $election = Election::factory()->create(['published_at' => now()]);

        $this->getJson("/api/elections/{$election->id}")
            ->assertOk()
            ->assertJsonPath('id', $election->id)
            ->assertJsonMissingPath('readiness');
    }

    public function test_guest_cannot_view_unpublished_election(): void
    {
        $election = Election::factory()->create(['published_at' => null]);

        $this->getJson("/api/elections/{$election->id}")
            ->assertNotFound();
    }

    public function test_admin_can_view_unpublished_election(): void
    {
        $election = Election::factory()->create(['published_at' => null]);

        $this->actingAs($this->admin)
            ->getJson("/api/elections/{$election->id}")
            ->assertOk()
            ->assertJsonStructure(['readiness']);
    }

    public function test_admin_can_delete_election(): void
    {
        $election = Election::factory()->create(['created_by' => $this->admin->id]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/elections/{$election->id}");

        $response->assertOk();

        // Soft deleted — deleted_at should be set
        $this->assertDatabaseHas('elections', ['id' => $election->id]);
        $this->assertNull(Election::find($election->id)); // Not in regular queries
    }

    public function test_election_results(): void
    {
        $election = Election::factory()->create();
        $position = $election->positions()->create(['title' => 'President']);
        $alice = $position->candidates()->create(['name' => 'Alice']);
        $bob = $position->candidates()->create(['name' => 'Bob']);

        // Alice gets 2 votes, Bob gets 1
        $voter1 = User::factory()->create();
        $voter2 = User::factory()->create();
        $voter3 = User::factory()->create();

        $alice->votes()->create(['election_id' => $election->id, 'position_id' => $position->id, 'voter_id' => $voter1->id]);
        $alice->votes()->create(['election_id' => $election->id, 'position_id' => $position->id, 'voter_id' => $voter2->id]);
        $bob->votes()->create(['election_id' => $election->id, 'position_id' => $position->id, 'voter_id' => $voter3->id]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/elections/{$election->id}/results");

        $response->assertOk()
            ->assertJsonPath('results.0.candidates.0.votes', 2)
            ->assertJsonPath('results.0.candidates.1.votes', 1);
    }

    public function test_voter_cannot_access_results(): void
    {
        $election = Election::factory()->create();

        $response = $this->actingAs($this->voter)
            ->getJson("/api/elections/{$election->id}/results");

        $response->assertForbidden();
    }
}
