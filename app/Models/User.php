<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    // Constants for backward compat with controllers
    public const ROLE_VOTER = 'voter';
    public const ROLE_ADMIN = 'admin';

    protected $fillable = [
        'name',
        'matric_number',
        'email',
        'password',
        'role',
        'is_eligible',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'role' => Role::class,
            'is_eligible' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class, 'voter_id');
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }

    public function electionsCreated(): HasMany
    {
        return $this->hasMany(Election::class, 'created_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::ADMIN;
    }
}
