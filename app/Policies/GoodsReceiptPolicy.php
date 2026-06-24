<?php

namespace App\Policies;

use App\Models\GoodsReceipt;
use App\Models\User;

class GoodsReceiptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('procurement.view');
    }

    public function view(User $user, GoodsReceipt $goodsReceipt): bool
    {
        return $user->can('procurement.view');
    }

    public function create(User $user): bool
    {
        return $user->can('goods-receipts.create');
    }

    public function post(User $user, GoodsReceipt $goodsReceipt): bool
    {
        return $user->can('goods-receipts.post');
    }
}
