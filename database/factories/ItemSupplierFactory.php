<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\ItemSupplier;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemSupplier>
 */
class ItemSupplierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'item_id' => Item::factory()->purchasedMaterial(),
            'supplier_id' => Supplier::factory(),
            'supplier_item_code' => strtoupper(fake()->optional()->bothify('SUP-ITEM-####')),
            'purchase_unit' => 'kg',
            'conversion_factor' => 1,
            'minimum_order_quantity' => fake()->optional()->randomFloat(3, 0, 100),
            'order_multiple' => fake()->optional()->randomFloat(3, 1, 25),
            'unit_price' => fake()->optional()->randomFloat(4, 1, 5000),
            'currency' => 'HUF',
            'lead_time_days' => fake()->numberBetween(0, 30),
            'priority' => 100,
            'is_preferred' => false,
            'is_approved' => false,
            'is_active' => true,
            'valid_from' => null,
            'valid_until' => null,
        ];
    }

    public function preferred(): static
    {
        return $this->state(fn (): array => [
            'is_preferred' => true,
            'is_active' => true,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (): array => ['is_approved' => true]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
            'is_preferred' => false,
        ]);
    }
}
