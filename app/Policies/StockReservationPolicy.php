<?php

namespace App\Policies;

use App\Models\StockReservation;
use App\Models\User;

/**
 * A `StockReservation` modell műveleteinek hozzáférési szabályait határozza meg.
 */
class StockReservationPolicy
{
    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: készletfoglalás listájának megtekintése.
     *
     * A művelethez a `inventory.view` permission szükséges.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: készletfoglalás adatlapjának megtekintése.
     *
     * A művelethez a `inventory.view` permission szükséges.
     */
    public function view(User $user, StockReservation $stockReservation): bool
    {
        return $user->can('inventory.view');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: készletfoglalás feloldása.
     *
     * A művelethez a `inventory.release` permission szükséges.
     */
    public function release(User $user, StockReservation $stockReservation): bool
    {
        return $user->can('inventory.release');
    }
}
