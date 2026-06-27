<?php

namespace Database\Factories;

use App\Models\FactoryUnit;
use App\Models\FactoryUnitCalendar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FactoryUnitCalendar>
 */
class FactoryUnitCalendarFactory extends Factory
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
            'weekday' => $this->faker->numberBetween(1, 5),
            'work_start' => '08:00:00',
            'work_end' => '16:00:00',
            'break_minutes' => 30,
            'is_working_day' => true,
        ];
    }
}
