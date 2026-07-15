<?php

namespace App\Policies;

use App\Models\MaterialRequirement;
use App\Models\User;

/**
 * A `MaterialRequirement` modell műveleteinek hozzáférési szabályait határozza meg.
 */
class MaterialRequirementPolicy
{
    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: anyagigény listájának megtekintése.
     *
     * A művelethez a `inventory.view` permission szükséges.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: anyagigény adatlapjának megtekintése.
     *
     * A művelethez a `inventory.view` permission szükséges.
     */
    public function view(User $user, MaterialRequirement $materialRequirement): bool
    {
        return $user->can('inventory.view');
    }
}
