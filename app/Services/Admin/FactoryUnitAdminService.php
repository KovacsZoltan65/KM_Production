<?php

namespace App\Services\Admin;

use App\Repositories\Contracts\FactoryUnitRepositoryInterface;
use App\Services\AuditLogService;
use App\Services\BusinessCacheInvalidator;
use App\Services\CodeCreationService;

class FactoryUnitAdminService extends CodeAwareAdminService
{
    public function __construct(FactoryUnitRepositoryInterface $repository, AuditLogService $auditLogService, CodeCreationService $codeCreationService, private readonly BusinessCacheInvalidator $cacheInvalidator)
    {
        parent::__construct($repository, $auditLogService, $codeCreationService);
    }

    protected function codeType(array $attributes): string
    {
        return 'factory_unit';
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

    protected function afterWrite(): void
    {
        $this->cacheInvalidator->productionChanged();
        $this->cacheInvalidator->capacityChanged();
    }
}
