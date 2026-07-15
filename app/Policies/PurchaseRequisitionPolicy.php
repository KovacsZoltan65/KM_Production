<?php

namespace App\Policies;

use App\Models\PurchaseRequisition;
use App\Models\User;

/**
 * A `PurchaseRequisition` modell műveleteinek hozzáférési szabályait határozza meg.
 */
class PurchaseRequisitionPolicy
{
    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: beszerzési igény listájának megtekintése.
     *
     * A művelethez a `procurement.view` permission szükséges.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('procurement.view');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: beszerzési igény adatlapjának megtekintése.
     *
     * A művelethez a `procurement.view` permission szükséges.
     */
    public function view(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $user->can('procurement.view');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: beszerzési igény létrehozása.
     *
     * A művelethez a `procurement.create` permission szükséges.
     */
    public function create(User $user): bool
    {
        return $user->can('procurement.create');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: beszerzési igény módosítása.
     *
     * A művelethez a `procurement.update` permission szükséges.
     */
    public function update(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $user->can('procurement.update');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: beszerzési igény törlése.
     *
     * A művelethez a `procurement.delete` permission szükséges.
     */
    public function delete(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $user->can('procurement.delete');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: beszerzési igény jóváhagyása.
     *
     * A művelethez a `procurement.approve` permission szükséges.
     */
    public function approve(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $user->can('procurement.approve');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: beszerzési igény beszerzési rendelésének generálása.
     *
     * A művelethez a `purchase-orders.generate` permission szükséges.
     */
    public function generatePurchaseOrder(User $user, PurchaseRequisition $purchaseRequisition): bool
    {
        return $user->can('purchase-orders.generate');
    }
}
