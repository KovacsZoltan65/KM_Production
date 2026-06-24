<?php

namespace App\Repositories\Contracts;

use App\Models\GoodsReceipt;

interface GoodsReceiptRepositoryInterface extends AdminRepositoryInterface
{
    public function findForShow(GoodsReceipt $goodsReceipt): GoodsReceipt;
}
