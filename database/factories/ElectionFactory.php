<?php

namespace Database\Factories;

use App\Models\Election;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ElectionFactory extends Factory
{
    protected $model = Election::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'status' => 'draft',
            'start_time' => now()->subDay(),
            'end_time' => now()->addWeek(),
            'created_by' => User::factory(),
        ];
    }
}
