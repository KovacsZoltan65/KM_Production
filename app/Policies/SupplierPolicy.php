<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

/**
 * A `Supplier` modell műveleteinek hozzáférési szabályait határozza meg.
 */
class SupplierPolicy
{
    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: beszállító listájának megtekintése.
     *
     * A művelethez a `suppliers.view` permission szükséges.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('suppliers.view');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: beszállító létrehozása.
     *
     * A művelethez a `suppliers.create` permission szükséges.
     */
    public function create(User $user): bool
    {
        return $user->can('suppliers.create');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: beszállító módosítása.
     *
     * A művelethez a `suppliers.update` permission szükséges.
     */
    public function update(User $user, Supplier $supplier): bool
    {
        return $user->can('suppliers.update');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: beszállító törlése.
     *
     * A művelethez a `suppliers.delete` permission szükséges.
     */
    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->can('suppliers.delete');
    }
}
