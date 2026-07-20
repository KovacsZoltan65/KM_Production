<?php

namespace App\Services\Admin;

use App\Repositories\Contracts\LocationRepositoryInterface;
use App\Services\AuditLogService;
use App\Services\BusinessCacheInvalidator;

class LocationAdminService extends AbstractAdminService
{
    public function __construct(LocationRepositoryInterface $repository, AuditLogService $auditLogService, private readonly BusinessCacheInvalidator $cacheInvalidator)
    {
        parent::__construct($repository, $auditLogService);
    }

    protected function createdEvent(): string
    {
        return 'admin_location_created';
    }

    protected function updatedEvent(): string
    {
        return 'admin_location_updated';
    }

    protected function deletedEvent(): string
    {
        return 'admin_location_deleted';
    }

    protected function afterWrite(): void
    {
        $this->cacheInvalidator->inventoryChanged();
    }
}
