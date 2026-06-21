<?php

namespace Database\Factories;

use App\Enums\StockReservationStatus;
use App\Models\CustomerOrderItem;
use App\Models\Item;
use App\Models\Location;
use App\Models\StockReservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockReservation>
 */
class StockReservationFactory extends Factory
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
            'customer_order_item_id' => CustomerOrderItem::factory(),
            'production_order_id' => null,
            'reserved_quantity' => fake()->randomFloat(3, 1, 25),
            'status' => StockReservationStatus::Active,
            'reserved_by' => null,
            'reserved_at' => now(),
            'released_at' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
