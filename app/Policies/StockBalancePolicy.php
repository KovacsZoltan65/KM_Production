<?php

namespace App\Policies;

use App\Models\StockBalance;
use App\Models\User;

class StockBalancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view');
    }

    public function view(User $user, StockBalance $stockBalance): bool
    {
        return $user->can('inventory.view');
    }
}
