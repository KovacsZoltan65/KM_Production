<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;
use App\Models\ProfessionalRole;

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
                'code' => 'QUALITY_INSPECTOR',
                'name' => 'Minőségellenőr',
                'description' => 'Minőségellenőrzési feladatokat végző dolgozó.',
                'is_active' => true,
            ]
        ];

        foreach($professionalRoles as $role) {
            // Create professional role logic here
            ProfessionalRole::create($role);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}