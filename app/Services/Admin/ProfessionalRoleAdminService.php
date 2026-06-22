<?php

namespace App\Services\Admin;

use App\Repositories\Contracts\ProfessionalRoleRepositoryInterface;
use App\Services\AuditLogService;

class ProfessionalRoleAdminService extends AbstractAdminService
{
    public function __construct(ProfessionalRoleRepositoryInterface $repository, AuditLogService $auditLogService)
    {
        parent::__construct($repository, $auditLogService);
    }

    protected function createdEvent(): string
    {
        return 'admin_professional_role_created';
    }

    protected function updatedEvent(): string
    {
        return 'admin_professional_role_updated';
    }

    protected function deletedEvent(): string
    {
        return 'admin_professional_role_deleted';
    }
}
