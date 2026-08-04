<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

class CandidateFactory extends Factory
{
    protected $model = Candidate::class;

    public function definition(): array
    {
        return [
            'position_id' => Position::factory(),
            'name' => fake()->name(),
            'manifesto' => fake()->paragraph(),
        ];
    }
}
