<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAdminService extends AbstractAdminService
{
    public function __construct(RoleRepositoryInterface $repository, AuditLogService $auditLogService)
    {
        parent::__construct($repository, $auditLogService);
    }

    public function update(Model $model, array $attributes, ?User $causer = null): Model
    {
        /** @var Role $model */
        if ($model->name === 'super-admin') {
            unset($attributes['name'], $attributes['permissions']);
            $model->syncPermissions(Permission::query()->pluck('name')->all());
        }

        return parent::update($model, $attributes, $causer);
    }

    public function delete(Model $model, ?User $causer = null): void
    {
        /** @var Role $model */
        if ($model->name === 'super-admin') {
            throw ValidationException::withMessages([
                'role' => __('master_data.roles.validation.super_admin_cannot_delete'),
            ]);
        }

        parent::delete($model, $causer);
    }

    protected function createdEvent(): string
    {
        return 'admin_role_created';
    }

    protected function updatedEvent(): string
    {
        return 'admin_role_updated';
    }

    protected function deletedEvent(): string
    {
        return 'admin_role_deleted';
    }
}
