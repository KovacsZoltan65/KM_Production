<?php

namespace Database\Factories;

use App\Models\ProfessionalRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProfessionalRole>
 */
class ProfessionalRoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('ROLE-###')),
            'name' => fake()->jobTitle(),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
