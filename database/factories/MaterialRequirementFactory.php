<?php

namespace Database\Factories;

use App\Enums\MaterialRequirementStatus;
use App\Models\CustomerOrderItem;
use App\Models\Item;
use App\Models\MaterialRequirement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaterialRequirement>
 */
class MaterialRequirementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $requiredQuantity = fake()->randomFloat(3, 1, 50);
        $availableQuantity = fake()->randomFloat(3, 0, $requiredQuantity);
        $reservedQuantity = fake()->randomFloat(3, 0, $availableQuantity);

        return [
            'customer_order_item_id' => CustomerOrderItem::factory(),
            'production_order_id' => null,
            'bom_item_id' => null,
            'required_item_id' => Item::factory()->purchasedMaterial(),
            'required_at' => fake()->boolean(75)
                ? fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d')
                : null,
            'required_quantity' => $requiredQuantity,
            'available_quantity' => $availableQuantity,
            'reserved_quantity' => $reservedQuantity,
            'missing_quantity' => max(0, $requiredQuantity - $availableQuantity),
            'unit' => 'db',
            'status' => MaterialRequirementStatus::Calculated,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
