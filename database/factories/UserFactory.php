<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'matric_number' => 'STU' . fake()->unique()->numerify('###'),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => Role::VOTER,
            'is_eligible' => true,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => Role::ADMIN]);
    }

    public function voter(): static
    {
        return $this->state(fn () => ['role' => Role::VOTER]);
    }

    public function ineligible(): static
    {
        return $this->state(fn () => ['is_eligible' => false]);
    }
}
