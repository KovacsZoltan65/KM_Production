<?php

namespace App\Policies;

use App\Models\StockBalance;
use App\Models\User;

/**
 * A `StockBalance` modell műveleteinek hozzáférési szabályait határozza meg.
 */
class StockBalancePolicy
{
    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: készletegyenleg listájának megtekintése.
     *
     * A művelethez a `inventory.view` permission szükséges.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: készletegyenleg adatlapjának megtekintése.
     *
     * A művelethez a `inventory.view` permission szükséges.
     */
    public function view(User $user, StockBalance $stockBalance): bool
    {
        return $user->can('inventory.view');
    }
}
