<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
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

    public function test_admin_can_list_audit_logs(): void
    {
        AuditLog::factory()->count(3)->create(['user_id' => $this->admin->id]);

        $response = $this->actingAs($this->admin)->getJson('/api/audit-logs');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_voter_cannot_list_audit_logs(): void
    {
        AuditLog::factory()->create();

        $this->actingAs($this->voter)
            ->getJson('/api/audit-logs')
            ->assertForbidden();
    }

    public function test_admin_can_filter_audit_logs_by_user(): void
    {
        AuditLog::factory()->count(2)->create(['user_id' => $this->admin->id]);
        AuditLog::factory()->count(3)->create(['user_id' => $this->voter->id]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/audit-logs?user_id={$this->voter->id}");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_filter_audit_logs_by_action(): void
    {
        AuditLog::factory()->create(['user_id' => $this->admin->id, 'action' => 'vote.cast']);
        AuditLog::factory()->create(['user_id' => $this->admin->id, 'action' => 'user.registered']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/audit-logs?action=vote.cast');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_audit_logs_paginated(): void
    {
        AuditLog::factory()->count(55)->create(['user_id' => $this->admin->id]);

        $response = $this->actingAs($this->admin)->getJson('/api/audit-logs');

        $response->assertOk()
            ->assertJsonCount(50, 'data');
    }

    public function test_audit_logs_ordered_newest_first(): void
    {
        $old = AuditLog::factory()->create([
            'user_id' => $this->admin->id,
            'created_at' => now()->subDay(),
        ]);
        $new = AuditLog::factory()->create([
            'user_id' => $this->admin->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/audit-logs');

        $response->assertOk()
            ->assertJsonPath('data.0.id', $new->id)
            ->assertJsonPath('data.1.id', $old->id);
    }

    public function test_unauthenticated_cannot_list_audit_logs(): void
    {
        $this->getJson('/api/audit-logs')->assertUnauthorized();
    }
}
