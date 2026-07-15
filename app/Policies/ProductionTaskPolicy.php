<?php

namespace App\Policies;

use App\Models\ProductionTask;
use App\Models\User;

/**
 * A `ProductionTask` modell műveleteinek hozzáférési szabályait határozza meg.
 */
class ProductionTaskPolicy
{
    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: termelési feladat listájának megtekintése.
     *
     * A művelethez a `production-tasks.view` permission szükséges.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('production-tasks.view');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: termelési feladat adatlapjának megtekintése.
     *
     * A művelethez a `production-tasks.view` permission szükséges.
     */
    public function view(User $user, ProductionTask $productionTask): bool
    {
        return $user->can('production-tasks.view');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: termelési feladat létrehozása.
     *
     * A művelethez a `production-tasks.create` permission szükséges.
     */
    public function create(User $user): bool
    {
        return $user->can('production-tasks.create');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: termelési feladat módosítása.
     *
     * A művelethez a `production-tasks.update` permission szükséges.
     */
    public function update(User $user, ProductionTask $productionTask): bool
    {
        return $user->can('production-tasks.update');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: termelési feladat törlése.
     *
     * A művelethez a `production-tasks.delete` permission szükséges.
     */
    public function delete(User $user, ProductionTask $productionTask): bool
    {
        return $user->can('production-tasks.delete');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: termelési feladat elindítása.
     *
     * A művelethez a `production-tasks.start` permission szükséges.
     */
    public function start(User $user, ProductionTask $productionTask): bool
    {
        return $user->can('production-tasks.start');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: termelési feladat befejezése.
     *
     * A művelethez a `production-tasks.finish` permission szükséges.
     */
    public function finish(User $user, ProductionTask $productionTask): bool
    {
        return $user->can('production-tasks.finish');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: termelési feladat anyagfelhasználása.
     *
     * A művelethez a `production-tasks.materials` permission szükséges.
     */
    public function useMaterials(User $user, ProductionTask $productionTask): bool
    {
        return $user->can('production-tasks.materials');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: termelési feladat minőségellenőrzése.
     *
     * A művelethez a `production-tasks.check` permission szükséges.
     */
    public function check(User $user, ProductionTask $productionTask): bool
    {
        return $user->can('production-tasks.check');
    }
}
