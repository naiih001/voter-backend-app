<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_check(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk()
            ->assertJson(['status' => 'OK']);
    }

    public function test_register_voter(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Voter One',
            'email' => 'voter1@test.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => 'voter',
            'matric_number' => 'STU001',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['message', 'user', 'token'])
            ->assertJson(['user' => ['role' => 'voter']]);
    }

    public function test_register_admin(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => 'admin',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['message', 'user', 'token']);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@test.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'Duplicate',
            'email' => 'taken@test.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => 'voter',
        ]);

        $response->assertUnprocessable();
    }

    public function test_register_validates_required_fields(): void
    {
        $response = $this->postJson('/api/register', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password', 'role']);
    }

    public function test_login(): void
    {
        User::factory()->create([
            'email' => 'login@test.com',
            'password' => bcrypt('Password1!'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'login@test.com',
            'password' => 'Password1!',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['message', 'user', 'token']);
    }

    public function test_login_rejects_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'login@test.com',
            'password' => bcrypt('Password1!'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'login@test.com',
            'password' => 'WrongPassword!',
        ]);

        $response->assertUnprocessable();
    }

    public function test_get_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/user');

        $response->assertOk()
            ->assertJson(['id' => $user->id]);
    }

    public function test_unauthenticated_access_returns_401(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertUnauthorized();
    }

    public function test_logout(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Verify authenticated
        $this->getJson('/api/user')->assertOk();

        // Logout
        $this->postJson('/api/logout')->assertOk();

        // Token revoked
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
