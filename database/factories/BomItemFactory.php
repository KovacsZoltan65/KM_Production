<?php

namespace Database\Factories;

use App\Models\Bom;
use App\Models\BomItem;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BomItem>
 */
class BomItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bom_id' => Bom::factory(),
            'item_id' => Item::factory()->purchasedMaterial(),
            'quantity' => fake()->randomFloat(3, 1, 10),
            'unit' => 'db',
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
