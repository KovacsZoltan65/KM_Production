<?php

namespace Database\Factories;

use App\Models\FactoryUnit;
use App\Models\OperationSequence;
use App\Models\OperationSequenceStep;
use App\Models\OperationType;
use App\Models\ProfessionalRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OperationSequenceStep>
 */
class OperationSequenceStepFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'operation_sequence_id' => OperationSequence::factory(),
            'step_order' => fake()->numberBetween(1, 20),
            'operation_type_id' => OperationType::factory(),
            'factory_unit_id' => FactoryUnit::factory(),
            'professional_role_id' => ProfessionalRole::factory(),
            'estimated_duration_minutes' => fake()->numberBetween(15, 240),
            'requires_quality_check' => false,
            'instructions' => fake()->optional()->sentence(),
        ];
    }
}
