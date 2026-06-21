<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\OperationSequence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OperationSequence>
 */
class OperationSequenceFactory extends Factory
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
            'name' => fake()->words(3, true).' sequence',
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
