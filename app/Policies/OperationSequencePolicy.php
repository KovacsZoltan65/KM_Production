<?php

namespace App\Policies;

use App\Models\OperationSequence;
use App\Models\User;

/**
 * A `OperationSequence` modell műveleteinek hozzáférési szabályait határozza meg.
 */
class OperationSequencePolicy
{
    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: műveletsor listájának megtekintése.
     *
     * A művelethez a `operation-sequences.view` permission szükséges.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('operation-sequences.view');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: műveletsor létrehozása.
     *
     * A művelethez a `operation-sequences.create` permission szükséges.
     */
    public function create(User $user): bool
    {
        return $user->can('operation-sequences.create');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: műveletsor módosítása.
     *
     * A művelethez a `operation-sequences.update` permission szükséges.
     */
    public function update(User $user, OperationSequence $operationSequence): bool
    {
        return $user->can('operation-sequences.update');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: műveletsor törlése.
     *
     * A művelethez a `operation-sequences.delete` permission szükséges.
     */
    public function delete(User $user, OperationSequence $operationSequence): bool
    {
        return $user->can('operation-sequences.delete');
    }
}
