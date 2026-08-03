<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class InitialInstallationSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            AdminUserSeeder::class,

            FactoryUnitsSeeder::class,
            LocationsSeeder::class,
            ProfessionalRolesSeeder::class,
            EmployeeSeeder::class,
            SuppliersSeeder::class,
            CustomersSeeder::class,

            ItemsSeeder::class,
            OperationTypesSeeder::class,
        ]);
    }
}
