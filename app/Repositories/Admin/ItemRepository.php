<?php

namespace App\Repositories\Admin;

use App\Models\Item;
use App\Repositories\Contracts\ItemRepositoryInterface;
use Illuminate\Support\Collection;

class ItemRepository extends AbstractAdminRepository implements ItemRepositoryInterface
{
    protected string $modelClass = Item::class;

    protected array $with = ['activeItemSuppliers'];

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

    /**
     * Visszaadja a vevői rendeléshez választható aktív késztermékeket.
     *
     * @return Collection<int, Item>
     */
    public function orderableOptions(): Collection
    {
        return Item::query()
            ->orderable()
            ->orderBy('item_number')
            ->get(['id', 'item_number', 'name', 'unit']);
    }

    /**
     * @param  list<int>  $itemIds
     * @return Collection<int, int>
     */
    public function orderableItemIds(array $itemIds): Collection
    {
        if ($itemIds === []) {
            return collect();
        }

        /** @var Collection<int, int> $ids */
        $ids = Item::query()
            ->orderable()
            ->whereKey($itemIds)
            ->pluck('id');

        return $ids;
    }
}
