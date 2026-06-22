<?php

namespace App\Repositories\Admin;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class UserRepository extends AbstractAdminRepository implements UserRepositoryInterface
{
    protected string $modelClass = User::class;

    protected array $searchable = ['name', 'email'];

    protected array $sortable = ['id', 'name', 'email', 'created_at'];

    protected array $with = ['roles'];

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Model
    {
        $roles = $attributes['roles'] ?? [];
        unset($attributes['roles']);

        $attributes['password'] = Hash::make((string) $attributes['password']);

        /** @var User $user */
        $user = parent::create($attributes);
        $user->syncRoles($roles);

        return $user->load('roles');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Model $model, array $attributes): Model
    {
        $roles = $attributes['roles'] ?? null;
        unset($attributes['roles']);

        if (! empty($attributes['password'])) {
            $attributes['password'] = Hash::make((string) $attributes['password']);
        } else {
            unset($attributes['password']);
        }

        /** @var User $user */
        $user = parent::update($model, $attributes);

        if (is_array($roles)) {
            $user->syncRoles($roles);
        }

        return $user->load('roles');
    }
}
