<?php

namespace App\Policies;

use App\Models\StockReservation;
use App\Models\User;

class StockReservationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view');
    }

    public function view(User $user, StockReservation $stockReservation): bool
    {
        return $user->can('inventory.view');
    }

    public function release(User $user, StockReservation $stockReservation): bool
    {
        return $user->can('inventory.release');
    }
}
