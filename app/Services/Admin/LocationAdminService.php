<?php

namespace App\Services\Admin;

use App\Repositories\Contracts\LocationRepositoryInterface;
use App\Services\AuditLogService;
use App\Services\BusinessCacheInvalidator;
use App\Services\CodeCreationService;

class LocationAdminService extends CodeAwareAdminService
{
    public function __construct(LocationRepositoryInterface $repository, AuditLogService $auditLogService, CodeCreationService $codeCreationService, private readonly BusinessCacheInvalidator $cacheInvalidator)
    {
        parent::__construct($repository, $auditLogService, $codeCreationService);
    }

    protected function codeType(array $attributes): string
    {
        return 'location';
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
