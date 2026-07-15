<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;

/**
 * A `Location` modell műveleteinek hozzáférési szabályait határozza meg.
 */
class LocationPolicy
{
    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: hely listájának megtekintése.
     *
     * A művelethez a `locations.view` permission szükséges.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('locations.view');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: hely létrehozása.
     *
     * A művelethez a `locations.create` permission szükséges.
     */
    public function create(User $user): bool
    {
        return $user->can('locations.create');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: hely módosítása.
     *
     * A művelethez a `locations.update` permission szükséges.
     */
    public function update(User $user, Location $location): bool
    {
        return $user->can('locations.update');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: hely törlése.
     *
     * A művelethez a `locations.delete` permission szükséges.
     */
    public function delete(User $user, Location $location): bool
    {
        return $user->can('locations.delete');
    }
}
