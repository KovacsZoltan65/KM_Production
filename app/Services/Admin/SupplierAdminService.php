<?php

namespace App\Services\Admin;

use App\Repositories\Contracts\SupplierAdminRepositoryInterface;
use App\Services\AuditLogService;
use App\Services\BusinessCacheInvalidator;
use App\Services\CodeCreationService;

class SupplierAdminService extends CodeAwareAdminService
{
    public function __construct(SupplierAdminRepositoryInterface $repository, AuditLogService $auditLogService, CodeCreationService $codeCreationService, private readonly BusinessCacheInvalidator $cacheInvalidator)
    {
        parent::__construct($repository, $auditLogService, $codeCreationService);
    }

    protected function codeType(array $attributes): string
    {
        return 'supplier';
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

    protected function afterWrite(): void
    {
        $this->cacheInvalidator->procurementChanged();
    }
}
