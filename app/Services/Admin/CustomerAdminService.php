<?php

namespace App\Services\Admin;

use App\Repositories\Contracts\CustomerAdminRepositoryInterface;
use App\Services\AuditLogService;

class CustomerAdminService extends AbstractAdminService
{
    public function __construct(CustomerAdminRepositoryInterface $repository, AuditLogService $auditLogService)
    {
        parent::__construct($repository, $auditLogService);
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
}
