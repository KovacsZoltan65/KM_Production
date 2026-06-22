<?php

namespace App\Services\Admin;

use App\Repositories\Contracts\FactoryUnitRepositoryInterface;
use App\Services\AuditLogService;

class FactoryUnitAdminService extends AbstractAdminService
{
    public function __construct(FactoryUnitRepositoryInterface $repository, AuditLogService $auditLogService)
    {
        parent::__construct($repository, $auditLogService);
    }

    protected function createdEvent(): string
    {
        return 'admin_factory_unit_created';
    }

    protected function updatedEvent(): string
    {
        return 'admin_factory_unit_updated';
    }

    protected function deletedEvent(): string
    {
        return 'admin_factory_unit_deleted';
    }
}
