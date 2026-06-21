<?php

namespace Database\Factories;

use App\Models\FactoryUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FactoryUnit>
 */
class FactoryUnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('FU-###')),
            'name' => fake()->company().' Unit',
            'description' => fake()->optional()->sentence(),
            'daily_capacity_minutes' => fake()->optional()->numberBetween(240, 960),
            'shift_count' => fake()->numberBetween(1, 3),
            'is_active' => true,
        ];
    }
}
