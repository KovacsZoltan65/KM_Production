<?php

namespace Database\Factories;

use App\Enums\ItemInstanceStatus;
use App\Models\FactoryUnit;
use App\Models\Item;
use App\Models\ItemInstance;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemInstance>
 */
class ItemInstanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_id' => Item::factory()->manufacturedPart(),
            'serial_number' => strtoupper(fake()->unique()->bothify('HEG/2026/####')),
            'factory_unit_id' => FactoryUnit::factory(),
            'current_location_id' => Location::factory(),
            'current_status' => ItemInstanceStatus::Planned,
            'production_order_id' => null,
        ];
    }
}
