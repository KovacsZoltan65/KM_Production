<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

/**
 * A `Customer` modell műveleteinek hozzáférési szabályait határozza meg.
 */
class CustomerPolicy
{
    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: vevő listájának megtekintése.
     *
     * A művelethez a `customers.view` permission szükséges.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('customers.view');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: vevő létrehozása.
     *
     * A művelethez a `customers.create` permission szükséges.
     */
    public function create(User $user): bool
    {
        return $user->can('customers.create');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: vevő módosítása.
     *
     * A művelethez a `customers.update` permission szükséges.
     */
    public function update(User $user, Customer $customer): bool
    {
        return $user->can('customers.update');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: vevő törlése.
     *
     * A művelethez a `customers.delete` permission szükséges.
     */
    public function delete(User $user, Customer $customer): bool
    {
        return $user->can('customers.delete');
    }
}
