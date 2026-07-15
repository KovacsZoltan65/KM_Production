<?php

namespace App\Policies;

use App\Models\ProfessionalRole;
use App\Models\User;

/**
 * A `ProfessionalRole` modell műveleteinek hozzáférési szabályait határozza meg.
 */
class ProfessionalRolePolicy
{
    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: szakmai szerepkör listájának megtekintése.
     *
     * A művelethez a `professional-roles.view` permission szükséges.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('professional-roles.view');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: szakmai szerepkör létrehozása.
     *
     * A művelethez a `professional-roles.create` permission szükséges.
     */
    public function create(User $user): bool
    {
        return $user->can('professional-roles.create');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: szakmai szerepkör módosítása.
     *
     * A művelethez a `professional-roles.update` permission szükséges.
     */
    public function update(User $user, ProfessionalRole $professionalRole): bool
    {
        return $user->can('professional-roles.update');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: szakmai szerepkör törlése.
     *
     * A művelethez a `professional-roles.delete` permission szükséges.
     */
    public function delete(User $user, ProfessionalRole $professionalRole): bool
    {
        return $user->can('professional-roles.delete');
    }
}
