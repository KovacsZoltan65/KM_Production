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
        ]);
    }
}
