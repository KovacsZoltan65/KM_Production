<?php

namespace App\Policies;

use App\Models\FactoryUnit;
use App\Models\User;

/**
 * A `FactoryUnit` modell műveleteinek hozzáférési szabályait határozza meg.
 */
class FactoryUnitPolicy
{
    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: gyáregység listájának megtekintése.
     *
     * A művelethez a `factory-units.view` permission szükséges.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('factory-units.view');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: gyáregység létrehozása.
     *
     * A művelethez a `factory-units.create` permission szükséges.
     */
    public function create(User $user): bool
    {
        return $user->can('factory-units.create');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: gyáregység módosítása.
     *
     * A művelethez a `factory-units.update` permission szükséges.
     */
    public function update(User $user, FactoryUnit $factoryUnit): bool
    {
        return $user->can('factory-units.update');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: gyáregység törlése.
     *
     * A művelethez a `factory-units.delete` permission szükséges.
     */
    public function delete(User $user, FactoryUnit $factoryUnit): bool
    {
        return $user->can('factory-units.delete');
    }
}
