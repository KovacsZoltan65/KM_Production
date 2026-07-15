<?php

namespace App\Policies;

use App\Enums\CustomerOrderStatus;
use App\Models\CustomerOrder;
use App\Models\User;

/**
 * A `CustomerOrder` modell műveleteinek hozzáférési szabályait határozza meg.
 */
class CustomerOrderPolicy
{
    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: vevői rendelés listájának megtekintése.
     *
     * A művelethez a `customer-orders.view` permission szükséges.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('customer-orders.view');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: vevői rendelés adatlapjának megtekintése.
     *
     * A művelethez a `customer-orders.view` permission szükséges.
     */
    public function view(User $user, CustomerOrder $customerOrder): bool
    {
        return $user->can('customer-orders.view');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: vevői rendelés létrehozása.
     *
     * A művelethez a `customer-orders.create` permission szükséges.
     */
    public function create(User $user): bool
    {
        return $user->can('customer-orders.create');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: vevői rendelés módosítása.
     *
     * A művelethez a `customer-orders.update` permission szükséges.
     */
    public function update(User $user, CustomerOrder $customerOrder): bool
    {
        return $user->can('customer-orders.update');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: vevői rendelés törlése.
     *
     * A művelethez a `customer-orders.delete` permission szükséges. Emellett csak Draft vagy Cancelled állapotú rendelés törölhető.
     */
    public function delete(User $user, CustomerOrder $customerOrder): bool
    {
        return $user->can('customer-orders.delete')
            && \in_array($customerOrder->status, [CustomerOrderStatus::Draft, CustomerOrderStatus::Cancelled], true);
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: vevői rendelés visszaigazolása.
     *
     * A művelethez a `customer-orders.confirm` permission szükséges.
     */
    public function confirm(User $user, CustomerOrder $customerOrder): bool
    {
        return $user->can('customer-orders.confirm');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: vevői rendelés visszavonása.
     *
     * A művelethez a `customer-orders.cancel` permission szükséges.
     */
    public function cancel(User $user, CustomerOrder $customerOrder): bool
    {
        return $user->can('customer-orders.cancel');
    }
}
