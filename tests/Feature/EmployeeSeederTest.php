<?php

use App\Models\Employee;
use App\Models\ProfessionalRole;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\ProfessionalRolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

it('seeds the assembler employee with its professional role without duplicates on repeat runs', function (): void {
    seed(ProfessionalRolesSeeder::class);
    seed(EmployeeSeeder::class);

    $employee = Employee::query()->where('employee_number', 'EMP-0008')->sole();
    $role = ProfessionalRole::query()->where('code', 'ASSEMBLER')->sole();

    expect($employee->professional_role_id)->toBe($role->id);

    seed(ProfessionalRolesSeeder::class);
    seed(EmployeeSeeder::class);

    expect(Employee::query()->where('employee_number', 'EMP-0008')->sole()->id)->toBe($employee->id)
        ->and(Employee::query()->where('employee_number', 'EMP-0008')->sole()->professional_role_id)->toBe($role->id)
        ->and(ProfessionalRole::query()->where('code', 'ASSEMBLER')->sole()->id)->toBe($role->id);
});
