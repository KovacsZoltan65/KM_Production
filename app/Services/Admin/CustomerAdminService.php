<?php

namespace App\Services\Admin;

use App\Repositories\Contracts\CustomerAdminRepositoryInterface;
use App\Services\AuditLogService;
use App\Services\BusinessCacheInvalidator;
use App\Services\CodeCreationService;

class CustomerAdminService extends CodeAwareAdminService
{
    public function __construct(CustomerAdminRepositoryInterface $repository, AuditLogService $auditLogService, CodeCreationService $codeCreationService, private readonly BusinessCacheInvalidator $cacheInvalidator)
    {
        parent::__construct($repository, $auditLogService, $codeCreationService);
    }

    protected function codeType(array $attributes): string
    {
        return 'customer';
    }

    protected function createdEvent(): string
    {
        return 'admin_customer_created';
    }

    protected function updatedEvent(): string
    {
        return 'admin_customer_updated';
    }

    protected function deletedEvent(): string
    {
        return 'admin_customer_deleted';
    }

    protected function afterWrite(): void
    {
        $this->cacheInvalidator->customersChanged();
    }
}
