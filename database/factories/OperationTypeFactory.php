<?php

namespace Database\Factories;

use App\Enums\OperationTypeCode;
use App\Models\OperationType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OperationType>
 */
class OperationTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = fake()->randomElement(OperationTypeCode::cases());

        return [
            'code' => $code,
            'name' => str($code->value)->replace('_', ' ')->title()->toString(),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
