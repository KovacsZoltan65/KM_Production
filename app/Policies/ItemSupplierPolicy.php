<?php

namespace App\Policies;

use App\Models\ItemSupplier;
use App\Models\User;

class ItemSupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('item-suppliers.view');
    }

    public function create(User $user): bool
    {
        return $user->can('item-suppliers.create');
    }

    public function update(User $user, ItemSupplier $itemSupplier): bool
    {
        return $user->can('item-suppliers.update');
    }

    public function delete(User $user, ItemSupplier $itemSupplier): bool
    {
        return $user->can('item-suppliers.delete');
    }
}
