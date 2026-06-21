<?php

namespace Database\Factories;

use App\Models\SerialSequence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SerialSequence>
 */
class SerialSequenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prefix' => strtoupper(fake()->unique()->lexify('???')),
            'year' => (int) date('Y'),
            'last_number' => fake()->numberBetween(0, 999),
        ];
    }
}
