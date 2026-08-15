<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Election;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
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

    // ── PUT /api/user (profile update) ──

    public function test_user_can_update_own_profile(): void
    {
        $response = $this->actingAs($this->voter)->putJson('/api/user', [
            'name' => 'Updated Name',
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'Profile updated.']);

        $this->assertDatabaseHas('users', [
            'id' => $this->voter->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_user_can_update_own_email(): void
    {
        $response = $this->actingAs($this->voter)->putJson('/api/user', [
            'email' => 'newemail@test.com',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('users', ['email' => 'newemail@test.com']);
    }

    public function test_user_cannot_change_role_via_profile_update(): void
    {
        $this->actingAs($this->voter)->putJson('/api/user', [
            'role' => 'admin',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->voter->id,
            'role' => 'voter',
        ]);
    }

    public function test_profile_update_validates_unique_email(): void
    {
        User::factory()->create(['email' => 'taken@test.com']);

        $response = $this->actingAs($this->voter)->putJson('/api/user', [
            'email' => 'taken@test.com',
        ]);

        $response->assertUnprocessable();
    }

    public function test_unauthenticated_cannot_update_profile(): void
    {
        $this->putJson('/api/user', ['name' => 'Hacker'])->assertUnauthorized();
    }

    // ── GET /api/users (admin) ──

    public function test_admin_can_list_users(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/users');

        $response->assertOk()
            ->assertJsonCount(2); // admin + voter
    }

    public function test_voter_cannot_list_users(): void
    {
        $this->actingAs($this->voter)->getJson('/api/users')->assertForbidden();
    }

    public function test_admin_can_filter_users_by_role(): void
    {
        User::factory()->admin()->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/users?role=admin');

        $response->assertOk()
            ->assertJsonCount(2); // setUp admin + new admin
    }

    // ── GET /api/users/{user} (admin) ──

    public function test_admin_can_show_user(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson("/api/users/{$this->voter->id}");

        $response->assertOk()
            ->assertJsonFragment(['id' => $this->voter->id]);
    }

    public function test_voter_cannot_show_user(): void
    {
        $this->actingAs($this->voter)
            ->getJson("/api/users/{$this->voter->id}")
            ->assertForbidden();
    }

    // ── PUT /api/users/{user} (admin update) ──

    public function test_admin_can_update_user_role(): void
    {
        $response = $this->actingAs($this->admin)
            ->putJson("/api/users/{$this->voter->id}", [
                'role' => 'admin',
            ]);

        $response->assertOk()
            ->assertJson(['message' => 'User updated.']);

        $this->assertDatabaseHas('users', [
            'id' => $this->voter->id,
            'role' => 'admin',
        ]);
    }

    public function test_admin_can_toggle_user_eligibility(): void
    {
        $response = $this->actingAs($this->admin)
            ->putJson("/api/users/{$this->voter->id}", [
                'is_eligible' => false,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('users', [
            'id' => $this->voter->id,
            'is_eligible' => false,
        ]);
    }

    public function test_admin_update_validates_role_values(): void
    {
        $response = $this->actingAs($this->admin)
            ->putJson("/api/users/{$this->voter->id}", [
                'role' => 'superadmin',
            ]);

        $response->assertUnprocessable();
    }

    public function test_voter_cannot_admin_update_user(): void
    {
        $this->actingAs($this->voter)
            ->putJson("/api/users/{$this->voter->id}", ['role' => 'admin'])
            ->assertForbidden();
    }

    // ── DELETE /api/users/{user} (admin) ──

    public function test_admin_can_delete_user(): void
    {
        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/users/{$this->voter->id}");

        $response->assertOk()
            ->assertJson(['message' => 'User deleted.']);

        $this->assertDatabaseMissing('users', ['id' => $this->voter->id]);
    }

    public function test_admin_cannot_delete_user_who_created_an_election(): void
    {
        $owner = User::factory()->admin()->create();
        Election::factory()->create(['created_by' => $owner->id]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/users/{$owner->id}");

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'This administrator cannot be deleted because they created one or more elections and must remain for record keeping.',
            ]);
    }

    public function test_voter_cannot_delete_user(): void
    {
        $this->actingAs($this->voter)
            ->deleteJson("/api/users/{$this->admin->id}")
            ->assertForbidden();
    }
}
