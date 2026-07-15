<?php

namespace App\Policies;

use App\Models\Item;
use App\Models\User;

/**
 * A `Item` modell műveleteinek hozzáférési szabályait határozza meg.
 */
class ItemPolicy
{
    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: cikk listájának megtekintése.
     *
     * A művelethez a `items.view` permission szükséges.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('items.view');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: cikk létrehozása.
     *
     * A művelethez a `items.create` permission szükséges.
     */
    public function create(User $user): bool
    {
        return $user->can('items.create');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: cikk módosítása.
     *
     * A művelethez a `items.update` permission szükséges.
     */
    public function update(User $user, Item $item): bool
    {
        return $user->can('items.update');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: cikk törlése.
     *
     * A művelethez a `items.delete` permission szükséges.
     */
    public function delete(User $user, Item $item): bool
    {
        return $user->can('items.delete');
    }
}
