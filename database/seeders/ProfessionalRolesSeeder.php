<?php

namespace Database\Seeders;

use App\Models\ProfessionalRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class ProfessionalRolesSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $professionalRoles = [
            [
                'code' => 'OPERATOR',
                'name' => 'Gépkezelő',
                'description' => 'Megmunkáló műveleteket végző dolgozó.',
                'is_active' => true,
            ],
            [
                'code' => 'PACKER',
                'name' => 'Csomagoló',
                'description' => 'Készárut csomagoló dolgozó.',
                'is_active' => true,
            ],
            [
                'code' => 'MAINTENANCE_TECHNICIAN',
                'name' => 'Karbantartó technikus',
                'description' => 'Gépek karbantartását végző dolgozó.',
                'is_active' => true,
            ],
            [
                'code' => 'SUPERVISOR',
                'name' => 'Műszakvezető',
                'description' => 'Műszakot irányító dolgozó.',
                'is_active' => true,
            ],
            [
                'code' => 'QUALITY_MANAGER',
                'name' => 'Minőségügyi vezető',
                'description' => 'Minőségügyi feladatokat irányító dolgozó.',
                'is_active' => true,
            ],
            [
                'code' => 'QUALITY_INSPECTOR',
                'name' => 'Minőségellenőr',
                'description' => 'Minőségellenőrzési feladatokat végző dolgozó.',
                'is_active' => true,
            ],
        ];

        foreach ($professionalRoles as $role) {
            // Create professional role logic here
            ProfessionalRole::create($role);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
