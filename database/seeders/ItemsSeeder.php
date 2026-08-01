<?php

namespace Database\Seeders;

use App\Models\Item;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class ItemsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $items = [
            [
                'item_number' => 'PRD-0001',
                'name' => 'Kémcső 11x70',
                'item_type' => 'manufactured_part',
                'unit' => 'db',
                'width' => null,
                'length' => '70.000',
                'thickness' => null,
                'diameter' => '11.000',
                'requires_serial_number' => false,
                'is_active' => true,
            ], [
                'item_number' => 'PRD-0002',
                'name' => 'Dugó 11',
                'item_type' => 'manufactured_part',
                'unit' => 'db',
                'width' => null,
                'length' => null,
                'thickness' => null,
                'diameter' => '11.000',
                'requires_serial_number' => false,
                'is_active' => true,
            ], [
                'item_number' => 'PRD-0003',
                'name' => 'Kémcső dugóval 11x70',
                'item_type' => 'finished_product',
                'unit' => 'db',
                'width' => null,
                'length' => '70.000',
                'thickness' => null,
                'diameter' => '11.000',
                'requires_serial_number' => false,
                'is_active' => true,
            ], [
                'item_number' => 'MAT-0001',
                'name' => 'Kémcső anyag',
                'item_type' => 'purchased_material',
                'unit' => 'kg',
                'width' => null,
                'length' => null,
                'thickness' => null,
                'diameter' => null,
                'requires_serial_number' => false,
                'is_active' => true,
            ], [
                'item_number' => 'MAT-0002',
                'name' => 'Dugó anyag',
                'item_type' => 'purchased_material',
                'unit' => 'kg',
                'width' => null,
                'length' => null,
                'thickness' => null,
                'diameter' => null,
                'requires_serial_number' => false,
                'is_active' => true,
            ],
        ];

        foreach ($items as $item) {
            Item::create($item);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
