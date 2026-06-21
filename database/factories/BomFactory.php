<?php

namespace Database\Factories;

use App\Models\Bom;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bom>
 */
class BomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_id' => Item::factory()->finishedProduct(),
            'version' => fake()->numberBetween(1, 10),
            'name' => fake()->words(3, true).' V1',
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
