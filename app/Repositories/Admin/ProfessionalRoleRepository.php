<?php

namespace App\Repositories\Admin;

use App\Models\ProfessionalRole;
use App\Repositories\Contracts\ProfessionalRoleRepositoryInterface;

class ProfessionalRoleRepository extends AbstractAdminRepository implements ProfessionalRoleRepositoryInterface
{
    protected string $modelClass = ProfessionalRole::class;

    protected array $searchable = ['code', 'name', 'description'];

    protected array $sortable = ['id', 'code', 'name', 'is_active', 'created_at'];
}
