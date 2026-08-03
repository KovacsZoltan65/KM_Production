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
            'OPERATOR' => 1,
            'QUALITY_INSPECTOR' => 2,
            'PACKER' => 3,
            'QUALITY_MANAGER' => 4,
            'SUPERVISOR' => 5,
            'MAINTENANCE_TECHNICIAN' => 6,
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
            ],
            [
                'employee_number' => 'EMP-0003',
                'name' => 'Szabó Péter',
                'email' => 'szabo.peter@kmgepgyarto.test',
                'phone' => '+36 30 555 6666',
                'professional_role_id' => $professional_roles['PACKER'],
                'is_active' => true,
                'hired_at' => '2026-07-06',
            ],
            [
                'employee_number' => 'EMP-0004',
                'name' => 'Tóth Eszter',
                'email' => 'toth.eszter@kmgepgyarto.test',
                'phone' => '+36 30 777 8888',
                'professional_role_id' => $professional_roles['QUALITY_MANAGER'],
                'is_active' => true,
                'hired_at' => '2026-07-06',
            ],
            [
                'employee_number' => 'EMP-0005',
                'name' => 'Farkas László',
                'email' => 'farkas.laszlo@kmgepgyarto.test',
                'phone' => '+36 30 999 0000',
                'professional_role_id' => $professional_roles['SUPERVISOR'],
                'is_active' => true,
                'hired_at' => '2026-07-06',
            ],[
                'employee_number' => 'EMP-0006',
                'name' => 'Kovács Mária',
                'email' => 'kovacs.maria@kmgepgyarto.test',
                'phone' => '+36 30 111 2222',
                'professional_role_id' => $professional_roles['MAINTENANCE_TECHNICIAN'],
                'is_active' => true,
                'hired_at' => '2026-07-06',
            ],
        ];

        foreach ($employees as $employee) {
            // Create employee logic here
            Employee::create($employee);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
