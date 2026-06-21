<?php

namespace Database\Factories;

use App\Enums\LocationType;
use App\Models\FactoryUnit;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'factory_unit_id' => FactoryUnit::factory(),
            'code' => strtoupper(fake()->unique()->bothify('LOC-###')),
            'name' => fake()->words(2, true),
            'location_type' => fake()->randomElement(LocationType::cases())->value,
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
