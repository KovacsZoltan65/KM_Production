<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;

/**
 * A `PurchaseOrder` modell műveleteinek hozzáférési szabályait határozza meg.
 */
class PurchaseOrderPolicy
{
    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: beszerzési rendelés listájának megtekintése.
     *
     * A művelethez a `procurement.view` permission szükséges.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('procurement.view');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: beszerzési rendelés adatlapjának megtekintése.
     *
     * A művelethez a `procurement.view` permission szükséges.
     */
    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('procurement.view');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: beszerzési rendelés létrehozása.
     *
     * A művelethez a `procurement.create` permission szükséges.
     */
    public function create(User $user): bool
    {
        return $user->can('procurement.create');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: beszerzési rendelés módosítása.
     *
     * A művelethez a `procurement.update` permission szükséges.
     */
    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('procurement.update');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: beszerzési rendelés törlése.
     *
     * A művelethez a `procurement.delete` permission szükséges.
     */
    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('procurement.delete');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: beszerzési rendelés jóváhagyása.
     *
     * A művelethez a `procurement.approve` permission szükséges.
     */
    public function approve(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('procurement.approve');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: beszerzési rendelés lezárása.
     *
     * A művelethez a `procurement.update` permission szükséges.
     */
    public function close(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->can('procurement.update');
    }
}
