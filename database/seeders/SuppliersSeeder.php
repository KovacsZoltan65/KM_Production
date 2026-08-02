<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class SuppliersSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $suppliers = [
            [
                'code' => 'SUP-0001',
                'name' => 'Alapanyag Gyártó Kft.',
                'tax_number' => '87654321-2-13',
                'email' => 'ertekesites@alapanyag-gyarto.test',
                'phone' => '+36 20 333 4444',
                'address' => '8000 Székesfehérvár, Acél út 5.',
                'notes' => 'Alapanyagokat gyártó és beszállító.',
                'is_active' => true,
            ],
        ];

        foreach ($suppliers as $supplier) {
            // Create supplier logic here
            Supplier::create($supplier);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
