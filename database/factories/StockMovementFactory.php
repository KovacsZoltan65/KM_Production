<?php

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\Item;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_id' => Item::factory()->purchasedMaterial(),
            'item_batch_id' => null,
            'item_instance_id' => null,
            'from_location_id' => null,
            'to_location_id' => null,
            'quantity' => fake()->randomFloat(3, 1, 50),
            'movement_type' => StockMovementType::Correction,
            'source_type' => null,
            'source_id' => null,
            'performed_by' => null,
            'performed_at' => now(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
