<?php

namespace App\Policies;

use App\Models\GoodsReceipt;
use App\Models\User;

/**
 * A `GoodsReceipt` modell műveleteinek hozzáférési szabályait határozza meg.
 */
class GoodsReceiptPolicy
{
    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: áruátvétel listájának megtekintése.
     *
     * A művelethez a `procurement.view` permission szükséges.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('procurement.view');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: áruátvétel adatlapjának megtekintése.
     *
     * A művelethez a `procurement.view` permission szükséges.
     */
    public function view(User $user, GoodsReceipt $goodsReceipt): bool
    {
        return $user->can('procurement.view');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: áruátvétel létrehozása.
     *
     * A művelethez a `goods-receipts.create` permission szükséges.
     */
    public function create(User $user): bool
    {
        return $user->can('goods-receipts.create');
    }

    /**
     * Meghatározza, hogy engedélyezett-e a következő művelet: áruátvétel könyvelése.
     *
     * A művelethez a `goods-receipts.post` permission szükséges.
     */
    public function post(User $user, GoodsReceipt $goodsReceipt): bool
    {
        return $user->can('goods-receipts.post');
    }
}
