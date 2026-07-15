<?php

namespace App\Policies;

use App\Models\Bom;
use App\Models\User;

/**
 * A `Bom` modell műveleteinek hozzáférési szabályait határozza meg.
 */
class BomPolicy
{
    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: anyagjegyzék listájának megtekintése.
     *
     * A művelethez a `boms.view` permission szükséges.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('boms.view');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: anyagjegyzék létrehozása.
     *
     * A művelethez a `boms.create` permission szükséges.
     */
    public function create(User $user): bool
    {
        return $user->can('boms.create');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: anyagjegyzék módosítása.
     *
     * A művelethez a `boms.update` permission szükséges.
     */
    public function update(User $user, Bom $bom): bool
    {
        return $user->can('boms.update');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: anyagjegyzék törlése.
     *
     * A művelethez a `boms.delete` permission szükséges.
     */
    public function delete(User $user, Bom $bom): bool
    {
        return $user->can('boms.delete');
    }
}
