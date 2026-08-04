<?php

namespace Database\Seeders;

use App\Models\FactoryUnit;
use Illuminate\Database\Seeder;

class FactoryUnitsSeeder extends Seeder
{
    public function run(): void
    {
        $factoryUnitId = FactoryUnit::query()
            ->where('code', 'BP-GYARTAS')
            ->valueOrFail('id');

        foreach ($factoryUnitId as $unit) {
            // Create factory unit logic here
            FactoryUnit::create($unit);
        }
    }
}
