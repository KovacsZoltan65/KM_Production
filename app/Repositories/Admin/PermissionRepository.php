<?php

namespace App\Repositories\Admin;

use App\Repositories\Contracts\PermissionRepositoryInterface;
use Spatie\Permission\Models\Permission;

class PermissionRepository extends AbstractAdminRepository implements PermissionRepositoryInterface
{
    protected string $modelClass = Permission::class;

    protected array $searchable = ['name'];

    protected array $sortable = ['id', 'name', 'created_at'];
}
