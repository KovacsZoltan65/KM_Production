<?php

namespace App\Policies;

use App\Models\User;

/**
 * A `User` modell műveleteinek hozzáférési szabályait határozza meg.
 */
class UserPolicy
{
    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: felhasználó listájának megtekintése.
     *
     * A művelethez a `users.view` permission szükséges.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: felhasználó adatlapjának megtekintése.
     *
     * A művelethez a `users.view` permission szükséges.
     */
    public function view(User $user, User $model): bool
    {
        return $user->can('users.view');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: felhasználó létrehozása.
     *
     * A művelethez a `users.create` permission szükséges.
     */
    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: felhasználó módosítása.
     *
     * A művelethez a `users.update` permission szükséges.
     */
    public function update(User $user, User $model): bool
    {
        return $user->can('users.update');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: felhasználó törlése.
     *
     * A művelethez a `users.delete` permission szükséges.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->can('users.delete');
    }
}
