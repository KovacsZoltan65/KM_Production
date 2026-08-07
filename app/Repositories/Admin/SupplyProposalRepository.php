<?php

namespace App\Repositories\Admin;

use App\Models\Item;
use App\Models\ItemSupplier;
use App\Models\SupplyProposal;
use App\Repositories\Contracts\SupplyProposalRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SupplyProposalRepository extends AbstractAdminRepository implements SupplyProposalRepositoryInterface
{
    protected string $modelClass = SupplyProposal::class;

    protected array $sortable = [
        'id', 'item_id', 'supplier_id', 'strategy', 'proposed_quantity',
        'required_at', 'proposed_supply_at', 'status', 'created_at',
    ];

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, SupplyProposal>
     */
    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = SupplyProposal::query()->with(['item', 'supplier', 'creator']);
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('reason_code', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('item', fn (Builder $item): Builder => $item
                        ->where('item_number', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%"))
                    ->orWhereHas('supplier', fn (Builder $supplier): Builder => $supplier
                        ->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%"));
            });
        }

        foreach (['status', 'strategy', 'item_id', 'supplier_id'] as $filter) {
            if (($filters[$filter] ?? null) !== null && $filters[$filter] !== '') {
                $query->where($filter, $filters[$filter]);
            }
        }

        $sort = \in_array($filters['sort'] ?? null, $this->sortable, true)
            ? (string) $filters['sort']
            : 'id';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction)->paginate($perPage)->withQueryString();
    }

    public function findLocked(int $id): SupplyProposal
    {
        return SupplyProposal::query()->whereKey($id)->lockForUpdate()->firstOrFail();
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

    public function usableSupplierOptions(int $limit = 1000): Collection
    {
        return ItemSupplier::query()
            ->with('supplier:id,code,name')
            ->active()
            ->approved()
            ->validAt(now())
            ->whereHas('supplier', fn (Builder $supplier): Builder => $supplier->where('is_active', true))
            ->orderBy('item_id')
            ->orderBy('priority')
            ->limit($limit)
            ->get(['id', 'item_id', 'supplier_id'])
            ->map(fn (ItemSupplier $source): array => [
                'item_id' => $source->item_id,
                'supplier_id' => $source->supplier_id,
                'code' => $source->supplier->code,
                'name' => $source->supplier->name,
            ]);
    }

    public function hasUsableProcurementSource(int $itemId, int $supplierId): bool
    {
        return ItemSupplier::query()
            ->where('item_id', $itemId)
            ->where('supplier_id', $supplierId)
            ->active()
            ->approved()
            ->validAt(now())
            ->whereHas('supplier', fn (Builder $supplier): Builder => $supplier->where('is_active', true))
            ->exists();
    }
}
