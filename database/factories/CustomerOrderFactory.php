<?php

namespace Database\Factories;

use App\Enums\CustomerOrderStatus;
use App\Models\Customer;
use App\Models\CustomerOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerOrder>
 */
class CustomerOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_number' => strtoupper(fake()->unique()->bothify('SO-2026-######')),
            'customer_id' => Customer::factory(),
            'status' => CustomerOrderStatus::Draft,
            'requested_delivery_date' => fake()->optional()->dateTimeBetween('+1 week', '+3 months')?->format('Y-m-d'),
            'confirmed_at' => null,
            'notes' => fake()->optional()->sentence(),
            'created_by' => null,
        ];
    }
}
