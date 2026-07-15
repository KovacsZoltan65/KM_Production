<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * A `Role` modell műveleteinek hozzáférési szabályait határozza meg.
 */
class RolePolicy
{
    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: rendszerszerepkör listájának megtekintése.
     *
     * A művelethez a `roles.view` permission szükséges.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('roles.view');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: rendszerszerepkör létrehozása.
     *
     * A művelethez a `roles.create` permission szükséges.
     */
    public function create(User $user): bool
    {
        return $user->can('roles.create');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: rendszerszerepkör módosítása.
     *
     * A művelethez a `roles.update` permission szükséges.
     */
    public function update(User $user, Role $role): bool
    {
        return $user->can('roles.update');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: rendszerszerepkör törlése.
     *
     * A művelethez a `roles.delete` permission szükséges.
     */
    public function delete(User $user, Role $role): bool
    {
        return $user->can('roles.delete');
    }
}
