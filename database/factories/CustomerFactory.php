<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('CUS-###')),
            'name' => fake()->company(),
            'tax_number' => fake()->optional()->numerify('########-#-##'),
            'email' => fake()->optional()->companyEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'billing_address' => fake()->optional()->address(),
            'shipping_address' => fake()->optional()->address(),
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
