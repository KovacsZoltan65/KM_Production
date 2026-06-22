<?php

namespace App\Repositories\Admin;

use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;

class EmployeeRepository extends AbstractAdminRepository implements EmployeeRepositoryInterface
{
    protected string $modelClass = Employee::class;

    protected array $searchable = ['employee_number', 'name', 'email', 'phone'];

    protected array $sortable = ['id', 'employee_number', 'name', 'email', 'is_active', 'created_at'];

    protected array $with = ['professionalRole', 'user'];
}
