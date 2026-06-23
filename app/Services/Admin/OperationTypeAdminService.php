<?php

namespace App\Services\Admin;

use App\Repositories\Contracts\OperationTypeRepositoryInterface;
use App\Services\AuditLogService;

class OperationTypeAdminService extends AbstractAdminService
{
    public function __construct(OperationTypeRepositoryInterface $repository, AuditLogService $auditLogService)
    {
        parent::__construct($repository, $auditLogService);
    }

    protected function createdEvent(): string
    {
        return 'admin_operation_type_created';
    }

    protected function updatedEvent(): string
    {
        return 'admin_operation_type_updated';
    }

    protected function deletedEvent(): string
    {
        return 'admin_operation_type_deleted';
    }
}
