<?php

namespace Database\Seeders;

use App\Models\FactoryUnit;
use Illuminate\Database\Seeder;

class FactoryUnitsSeeder extends Seeder
{
    public function run(): void
    {
        $factory_units = [
            [
                'code' => 'BP-GYARTAS',
                'name' => 'Budapesti gyártóüzem',
                'description' => 'Műanyag termékek gyártására használt üzem.',
                'daily_capacity_minutes' => 480,
                'shift_count' => 1,
                'is_active' => true,
            ],
        ];

        foreach ($factory_units as $unit) {
            FactoryUnit::query()->updateOrCreate(
                ['code' => $unit['code']],
                $unit,
            );
        }
    }
}
