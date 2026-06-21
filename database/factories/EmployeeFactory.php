<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\ProfessionalRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'professional_role_id' => ProfessionalRole::factory(),
            'employee_number' => strtoupper(fake()->unique()->bothify('EMP-####')),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'is_active' => true,
            'hired_at' => fake()->optional()->date(),
            'left_at' => null,
        ];
    }
}
