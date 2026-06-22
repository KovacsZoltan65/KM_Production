<?php

namespace App\Services\Admin;

use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\AuditLogService;

class EmployeeAdminService extends AbstractAdminService
{
    public function __construct(EmployeeRepositoryInterface $repository, AuditLogService $auditLogService)
    {
        parent::__construct($repository, $auditLogService);
    }

    protected function createdEvent(): string
    {
        return 'admin_employee_created';
    }

    protected function updatedEvent(): string
    {
        return 'admin_employee_updated';
    }

    protected function deletedEvent(): string
    {
        return 'admin_employee_deleted';
    }
}
