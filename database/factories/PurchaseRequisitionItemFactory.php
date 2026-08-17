<?php

namespace Database\Factories;

use App\Enums\PurchaseRequisitionItemStatus;
use App\Models\Item;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseRequisitionItem>
 */
class PurchaseRequisitionItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(3, 1, 100);

        return [
            'purchase_requisition_id' => PurchaseRequisition::factory(),
            'material_requirement_id' => null,
            'item_id' => Item::factory()->purchasedMaterial(),
            'quantity' => $quantity,
            'planned_quantity' => $quantity,
            'replenishment_excess_quantity' => 0,
            'unit' => 'db',
            'status' => PurchaseRequisitionItemStatus::Draft,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
