<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\ItemBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemBatch>
 */
class ItemBatchFactory extends Factory
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
            'batch_number' => strtoupper(fake()->unique()->bothify('BATCH-####')),
            'supplier_id' => null,
            'received_at' => fake()->optional()->date(),
            'expires_at' => fake()->optional()->passthrough(
                fake()->dateTimeBetween('+1 month', '+2 years')->format('Y-m-d')
            ),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
