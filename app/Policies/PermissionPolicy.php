<?php

namespace App\Policies;

use App\Models\User;

/**
 * A `Permission` modell műveleteinek hozzáférési szabályait határozza meg.
 */
class PermissionPolicy
{
    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: jogosultság listájának megtekintése.
     *
     * A művelethez a `permissions.view` permission szükséges.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('permissions.view');
    }
}
