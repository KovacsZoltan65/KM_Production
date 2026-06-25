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

    /**
     * @return string
     */
    protected function createdEvent(): string
    {
        return 'admin_customer_created';
    }

    /**
     * @return string
     */
    protected function updatedEvent(): string
    {
        return 'admin_customer_updated';
    }

    /**
     * @return string
     */
    protected function deletedEvent(): string
    {
        return 'admin_customer_deleted';
    }
}
