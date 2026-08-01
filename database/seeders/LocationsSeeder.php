<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class LocationsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $factory_units = [
            'BP-GYARTAS' => 1,
        ];

        $locations = [
            [
                'factory_unit_id' => $factory_units['BP-GYARTAS'],
                'code' => 'ALAP-R1',
                'name' => 'Alapanyag raktár',
                'location_type' => 'warehouse',
                'description' => 'Beérkező acéllemezek és csavarok tárolása.',
                'is_active' => true,
            ], [
                'factory_unit_id' => $factory_units['BP-GYARTAS'],
                'code' => 'MUHELY-1',
                'name' => 'Megmunkáló műhely',
                'location_type' => 'quality_area',
                'description' => 'Gyártásközi és végellenőrzési terület.',
                'is_active' => true,
            ], [
                'factory_unit_id' => $factory_units['BP-GYARTAS'],
                'code' => 'ME-1',
                'name' => 'Minőségellenőrzési pont',
                'location_type' => 'quality_area',
                'description' => 'Gyártásközi és végellenőrzési terület.',
                'is_active' => true,
            ], [
                'factory_unit_id' => $factory_units['BP-GYARTAS'],
                'code' => 'KESZ-R1',
                'name' => 'Készáru raktár',
                'location_type' => 'finished_goods',
                'description' => 'Elkészült termékek átmeneti tárolása.',
                'is_active' => true,
            ],
        ];

        foreach ($locations as $location) {
            // Create location logic here
            Location::create($location);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
