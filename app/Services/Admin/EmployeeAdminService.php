<?php

namespace App\Services\Admin;

use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\AuditLogService;
use App\Services\BusinessCacheInvalidator;

class EmployeeAdminService extends AbstractAdminService
{
    public function __construct(EmployeeRepositoryInterface $repository, AuditLogService $auditLogService, private readonly BusinessCacheInvalidator $cacheInvalidator)
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

    protected function afterWrite(): void
    {
        $this->cacheInvalidator->workforceChanged();
    }
}
