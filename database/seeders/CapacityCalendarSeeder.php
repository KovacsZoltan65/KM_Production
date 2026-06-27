<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeWorkCalendar;
use App\Models\FactoryUnit;
use App\Models\FactoryUnitCalendar;
use Illuminate\Database\Seeder;

class CapacityCalendarSeeder extends Seeder
{
    /**
     * Seed default weekday calendars for capacity planning.
     */
    public function run(): void
    {
        FactoryUnit::query()->each(function (FactoryUnit $factoryUnit): void {
            foreach (range(1, 5) as $weekday) {
                FactoryUnitCalendar::query()->updateOrCreate(
                    [
                        'factory_unit_id' => $factoryUnit->id,
                        'weekday' => $weekday,
                    ],
                    [
                        'work_start' => '08:00:00',
                        'work_end' => '16:00:00',
                        'break_minutes' => 30,
                        'is_working_day' => true,
                    ],
                );
            }
        });

        Employee::query()->each(function (Employee $employee): void {
            foreach (range(1, 5) as $weekday) {
                EmployeeWorkCalendar::query()->updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'weekday' => $weekday,
                    ],
                    [
                        'work_start' => '08:00:00',
                        'work_end' => '16:00:00',
                        'break_minutes' => 30,
                    ],
                );
            }
        });
    }
}
