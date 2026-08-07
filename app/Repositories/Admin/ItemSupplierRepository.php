<?php

namespace App\Repositories\Admin;

use App\Models\Item;
use App\Models\ItemSupplier;
use App\Models\Supplier;
use App\Repositories\Contracts\ItemSupplierRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ItemSupplierRepository extends AbstractAdminRepository implements ItemSupplierRepositoryInterface
{
    protected string $modelClass = ItemSupplier::class;

    protected array $sortable = [
        'id',
        'item_id',
        'supplier_id',
        'supplier_item_code',
        'purchase_unit',
        'minimum_order_quantity',
        'order_multiple',
        'unit_price',
        'currency',
        'lead_time_days',
        'priority',
        'is_preferred',
        'is_approved',
        'is_active',
        'valid_from',
        'valid_until',
        'created_at',
    ];

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, ItemSupplier>
     */
    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = ItemSupplier::query()->with(['item', 'supplier']);
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->where('supplier_item_code', 'like', "%{$search}%")
                    ->orWhere('purchase_unit', 'like', "%{$search}%")
                    ->orWhere('currency', 'like', "%{$search}%")
                    ->orWhereHas('item', fn (Builder $itemQuery): Builder => $itemQuery
                        ->where('item_number', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%"))
                    ->orWhereHas('supplier', fn (Builder $supplierQuery): Builder => $supplierQuery
                        ->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%"));
            });
        }

        if ($filters['item_id'] ?? null) {
            $query->where('item_id', $filters['item_id']);
        }

        if ($filters['supplier_id'] ?? null) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (($filters['status'] ?? null) === 'active') {
            $query->active();
        } elseif (($filters['status'] ?? null) === 'inactive') {
            $query->where('is_active', false);
        }

        if (array_key_exists('approved', $filters) && $filters['approved'] !== null) {
            $query->where('is_approved', filter_var($filters['approved'], FILTER_VALIDATE_BOOL));
        }

        $sort = \in_array($filters['sort'] ?? null, $this->sortable, true)
            ? (string) $filters['sort']
            : 'priority';
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return $query
            ->orderBy($sort, $direction)
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function lockItem(int $itemId): void
    {
        Item::query()->whereKey($itemId)->lockForUpdate()->firstOrFail();
    }

    public function clearActivePreferredForItem(int $itemId, ?int $exceptId = null): void
    {
        ItemSupplier::query()
            ->where('item_id', $itemId)
            ->active()
            ->preferred()
            ->when($exceptId !== null, fn (Builder $query): Builder => $query->whereKeyNot($exceptId))
            ->update(['is_preferred' => false]);
    }

    /**
     * @return Collection<int, ItemSupplier>
     */
    public function activeApprovedForItem(int $itemId): Collection
    {
        return ItemSupplier::query()
            ->with('supplier')
            ->where('item_id', $itemId)
            ->active()
            ->approved()
            ->validAt(now())
            ->orderBy('priority')
            ->orderBy('id')
            ->get();
    }

    public function itemOptions(int $limit = 500): Collection
    {
        return Item::query()
            ->where('is_active', true)
            ->orderBy('item_number')
            ->limit($limit)
            ->get(['id', 'item_number', 'name', 'unit'])
            ->map(fn (Item $item): array => [
                'id' => $item->id,
                'item_number' => $item->item_number,
                'name' => $item->name,
                'unit' => $item->unit,
            ]);
    }

    public function supplierOptions(int $limit = 500): Collection
    {
        return Supplier::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->limit($limit)
            ->get(['id', 'code', 'name'])
            ->map(fn (Supplier $supplier): array => [
                'id' => $supplier->id,
                'code' => $supplier->code,
                'name' => $supplier->name,
            ]);
    }
}
