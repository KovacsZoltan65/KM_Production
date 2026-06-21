<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Location;
use App\Models\StockBalance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockBalance>
 */
class StockBalanceFactory extends Factory
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
            'location_id' => Location::factory(),
            'item_batch_id' => null,
            'quantity' => fake()->randomFloat(3, 1, 100),
        ];
    }
}
