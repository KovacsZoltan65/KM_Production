<?php

namespace App\Services\Admin;

use App\Repositories\Contracts\SupplierAdminRepositoryInterface;
use App\Services\AuditLogService;

class SupplierAdminService extends AbstractAdminService
{
    public function __construct(SupplierAdminRepositoryInterface $repository, AuditLogService $auditLogService)
    {
        parent::__construct($repository, $auditLogService);
    }

    protected function createdEvent(): string
    {
        return 'admin_supplier_created';
    }

    protected function updatedEvent(): string
    {
        return 'admin_supplier_updated';
    }

    protected function deletedEvent(): string
    {
        return 'admin_supplier_deleted';
    }
}
