<?php

namespace Database\Factories;

use App\Enums\ItemType;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_number' => strtoupper(fake()->unique()->bothify('ITEM-####')),
            'name' => fake()->words(3, true),
            'item_type' => ItemType::PurchasedMaterial,
            'unit' => fake()->randomElement(['db', 'kg', 'liter', 'm']),
            'width' => fake()->optional()->randomFloat(3, 1, 1000),
            'length' => fake()->optional()->randomFloat(3, 1, 1000),
            'thickness' => fake()->optional()->randomFloat(3, 1, 100),
            'diameter' => fake()->optional()->randomFloat(3, 1, 100),
            'requires_serial_number' => false,
            'is_active' => true,
        ];
    }

    public function purchasedMaterial(): static
    {
        return $this->state(fn (array $attributes) => [
            'item_type' => ItemType::PurchasedMaterial,
            'requires_serial_number' => false,
        ]);
    }

    public function manufacturedPart(): static
    {
        return $this->state(fn (array $attributes) => [
            'item_type' => ItemType::ManufacturedPart,
            'requires_serial_number' => true,
        ]);
    }

    public function semiFinishedProduct(): static
    {
        return $this->state(fn (array $attributes) => [
            'item_type' => ItemType::SemiFinishedProduct,
            'requires_serial_number' => true,
        ]);
    }

    public function finishedProduct(): static
    {
        return $this->state(fn (array $attributes) => [
            'item_type' => ItemType::FinishedProduct,
            'requires_serial_number' => true,
        ]);
    }
}
