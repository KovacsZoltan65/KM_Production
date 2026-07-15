<?php

namespace App\Policies;

use App\Models\OperationType;
use App\Models\User;

/**
 * A `OperationType` modell műveleteinek hozzáférési szabályait határozza meg.
 */
class OperationTypePolicy
{
    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: művelettípus listájának megtekintése.
     *
     * A művelethez a `operation-types.view` permission szükséges.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('operation-types.view');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: művelettípus létrehozása.
     *
     * A művelethez a `operation-types.create` permission szükséges.
     */
    public function create(User $user): bool
    {
        return $user->can('operation-types.create');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: művelettípus módosítása.
     *
     * A művelethez a `operation-types.update` permission szükséges.
     */
    public function update(User $user, OperationType $operationType): bool
    {
        return $user->can('operation-types.update');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: művelettípus törlése.
     *
     * A művelethez a `operation-types.delete` permission szükséges.
     */
    public function delete(User $user, OperationType $operationType): bool
    {
        return $user->can('operation-types.delete');
    }
}
