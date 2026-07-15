<?php

namespace App\Policies;

use App\Models\QualityCheck;
use App\Models\User;

/**
 * A `QualityCheck` modell műveleteinek hozzáférési szabályait határozza meg.
 */
class QualityCheckPolicy
{
    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: minőségellenőrzés listájának megtekintése.
     *
     * A művelethez a `production-tasks.check` permission szükséges.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('production-tasks.check');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: minőségellenőrzés adatlapjának megtekintése.
     *
     * A művelethez a `production-tasks.check` permission szükséges.
     */
    public function view(User $user, QualityCheck $qualityCheck): bool
    {
        return $user->can('production-tasks.check');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: minőségellenőrzés létrehozása.
     *
     * A művelethez a `production-tasks.check` permission szükséges.
     */
    public function create(User $user): bool
    {
        return $user->can('production-tasks.check');
    }
}
