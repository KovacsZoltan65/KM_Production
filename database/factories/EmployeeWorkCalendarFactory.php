<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeWorkCalendar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeWorkCalendar>
 */
class EmployeeWorkCalendarFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'weekday' => $this->faker->numberBetween(1, 5),
            'work_start' => '08:00:00',
            'work_end' => '16:00:00',
            'break_minutes' => 30,
        ];
    }
}
