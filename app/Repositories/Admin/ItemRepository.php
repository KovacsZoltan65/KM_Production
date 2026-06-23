<?php

namespace App\Repositories\Admin;

use App\Models\Item;
use App\Repositories\Contracts\ItemRepositoryInterface;

class ItemRepository extends AbstractAdminRepository implements ItemRepositoryInterface
{
    protected string $modelClass = Item::class;

    protected array $searchable = ['item_number', 'name', 'unit'];

    protected array $sortable = [
        'id',
        'item_number',
        'name',
        'item_type',
        'unit',
        'requires_serial_number',
        'is_active',
        'created_at',
    ];
}
