<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

/**
 * A `Employee` modell műveleteinek hozzáférési szabályait határozza meg.
 */
class EmployeePolicy
{
    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: alkalmazott listájának megtekintése.
     *
     * A művelethez a `employees.view` permission szükséges.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('employees.view');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: alkalmazott adatlapjának megtekintése.
     *
     * A művelethez a `employees.view` permission szükséges.
     */
    public function view(User $user, Employee $employee): bool
    {
        return $user->can('employees.view');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: alkalmazott létrehozása.
     *
     * A művelethez a `employees.create` permission szükséges.
     */
    public function create(User $user): bool
    {
        return $user->can('employees.create');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: alkalmazott módosítása.
     *
     * A művelethez a `employees.update` permission szükséges.
     */
    public function update(User $user, Employee $employee): bool
    {
        return $user->can('employees.update');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: alkalmazott törlése.
     *
     * A művelethez a `employees.delete` permission szükséges.
     */
    public function delete(User $user, Employee $employee): bool
    {
        return $user->can('employees.delete');
    }
}
