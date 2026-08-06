<?php

namespace Database\Seeders;

use App\Models\FactoryUnit;
use App\Models\Location;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class LocationsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $factoryUnitId = FactoryUnit::query()
            ->where('code', 'BP-GYARTAS')
            ->valueOrFail('id');

        $locations = [
            [
                'factory_unit_id' => $factoryUnitId,
                'code' => 'ALAP-R1',
                'name' => 'Alapanyag raktár',
                'location_type' => 'warehouse',
                'description' => 'Beérkező acéllemezek és csavarok tárolása.',
                'is_active' => true,
            ], [
                'factory_unit_id' => $factoryUnitId,
                'code' => 'MUHELY-1',
                'name' => 'Megmunkáló műhely',
                'location_type' => 'workshop',
                'description' => 'Gyártásközi és végellenőrzési terület.',
                'is_active' => true,
            ], [
                'factory_unit_id' => $factoryUnitId,
                'code' => 'ME-1',
                'name' => 'Minőségellenőrzési pont',
                'location_type' => 'quality_area',
                'description' => 'Gyártásközi és végellenőrzési terület.',
                'is_active' => true,
            ], [
                'factory_unit_id' => $factoryUnitId,
                'code' => 'KESZ-R1',
                'name' => 'Készáru raktár',
                'location_type' => 'finished_goods',
                'description' => 'Elkészült termékek átmeneti tárolása.',
                'is_active' => true,
            ],
        ];

        foreach ($locations as $location) {
            Location::query()->updateOrCreate(
                ['code' => $location['code']],
                $location,
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
