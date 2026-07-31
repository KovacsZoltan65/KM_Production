<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $professional_roles = [
            'OPERATOR' => 1, // Replace with actual professional role IDs
            'QUALITY_INSPECTOR' => 2, // Replace with actual professional role IDs
        ];

        $employees = [
            [
                'employee_number' => 'EMP-0001',
                'name' => 'Kiss János',
                'email' => 'kiss.janos@kmgepgyarto.test',
                'phone' => '+36 30 111 2222',
                'professional_role_id' => $professional_roles['OPERATOR'],
                'is_active' => true,
                'hired_at' => '2026-07-06',
            ],
            [
                'employee_number' => 'EMP-0002',
                'name' => 'Nagy Anna',
                'email' => 'nagy.anna@kmgepgyarto.test',
                'phone' => '+36 30 333 4444',
                'professional_role_id' => $professional_roles['QUALITY_INSPECTOR'],
                'is_active' => true,
                'hired_at' => '2026-07-06',
            ]
        ];

        foreach($employees as $employee) {
            // Create employee logic here
            Employee::create($employee);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}