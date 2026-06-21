<?php

namespace Database\Factories;

use App\Models\ItemInstance;
use App\Models\ItemInstanceRelation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemInstanceRelation>
 */
class ItemInstanceRelationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parent_item_instance_id' => ItemInstance::factory(),
            'child_item_instance_id' => ItemInstance::factory(),
            'quantity' => 1,
        ];
    }
}
