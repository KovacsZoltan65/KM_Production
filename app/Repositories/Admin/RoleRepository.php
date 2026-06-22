<?php

namespace App\Repositories\Admin;

use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class RoleRepository extends AbstractAdminRepository implements RoleRepositoryInterface
{
    protected string $modelClass = Role::class;

    protected array $searchable = ['name'];

    protected array $sortable = ['id', 'name', 'created_at'];

    protected array $with = ['permissions'];

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Model
    {
        $permissions = $attributes['permissions'] ?? [];
        unset($attributes['permissions']);
        $attributes['guard_name'] = 'web';

        /** @var Role $role */
        $role = parent::create($attributes);
        $role->syncPermissions($permissions);

        return $role->load('permissions');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Model $model, array $attributes): Model
    {
        $permissions = $attributes['permissions'] ?? null;
        unset($attributes['permissions']);

        /** @var Role $role */
        $role = parent::update($model, $attributes);

        if (is_array($permissions) && $role->name !== 'super-admin') {
            $role->syncPermissions($permissions);
        }

        return $role->load('permissions');
    }
}
