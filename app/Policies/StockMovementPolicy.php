<?php

namespace App\Policies;

use App\Models\StockMovement;
use App\Models\User;

/**
 * A `StockMovement` modell műveleteinek hozzáférési szabályait határozza meg.
 */
class StockMovementPolicy
{
    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: készletmozgás listájának megtekintése.
     *
     * A művelethez a `inventory.view` permission szükséges.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: készletmozgás adatlapjának megtekintése.
     *
     * A művelethez a `inventory.view` permission szükséges.
     */
    public function view(User $user, StockMovement $stockMovement): bool
    {
        return $user->can('inventory.view');
    }
}
