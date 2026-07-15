<?php

namespace App\Policies;

use App\Models\ProductionPlan;
use App\Models\User;

/**
 * A `ProductionPlan` modell műveleteinek hozzáférési szabályait határozza meg.
 */
class ProductionPlanPolicy
{
    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: termelési terv listájának megtekintése.
     *
     * A művelethez a `production-plans.view` permission szükséges.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('production-plans.view');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: termelési terv adatlapjának megtekintése.
     *
     * A művelethez a `production-plans.view` permission szükséges.
     */
    public function view(User $user, ProductionPlan $productionPlan): bool
    {
        return $user->can('production-plans.view');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: termelési terv létrehozása.
     *
     * A művelethez a `production-plans.create` permission szükséges.
     */
    public function create(User $user): bool
    {
        return $user->can('production-plans.create');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: termelési terv módosítása.
     *
     * A művelethez a `production-plans.update` permission szükséges.
     */
    public function update(User $user, ProductionPlan $productionPlan): bool
    {
        return $user->can('production-plans.update');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: termelési terv törlése.
     *
     * A művelethez a `production-plans.delete` permission szükséges.
     */
    public function delete(User $user, ProductionPlan $productionPlan): bool
    {
        return $user->can('production-plans.delete');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: termelési terv jóváhagyása.
     *
     * A művelethez a `production-plans.approve` permission szükséges.
     */
    public function approve(User $user, ProductionPlan $productionPlan): bool
    {
        return $user->can('production-plans.approve');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: termelési terv termelési rendeléseinek generálása.
     *
     * A művelethez a `production-orders.generate` permission szükséges.
     */
    public function generateProductionOrders(User $user, ProductionPlan $productionPlan): bool
    {
        return $user->can('production-orders.generate');
    }
}
