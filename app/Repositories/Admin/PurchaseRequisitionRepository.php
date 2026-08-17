<?php

namespace App\Repositories\Admin;

use App\Models\MaterialRequirement;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use App\Models\PurchaseRequisitionItemProposalSource;
use App\Repositories\Contracts\PurchaseRequisitionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PurchaseRequisitionRepository extends AbstractAdminRepository implements PurchaseRequisitionRepositoryInterface
{
    protected string $modelClass = PurchaseRequisition::class;

    protected array $searchable = ['requisition_number', 'notes'];

    protected array $sortable = ['id', 'requisition_number', 'status', 'requested_at', 'created_at'];

    protected array $with = ['requester'];

    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->query()->withCount('items');

        if (($filters['status'] ?? null) !== null) {
            $query->where('status', $filters['status']);
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('requisition_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $sort = \in_array($filters['sort'] ?? null, $this->sortable, true) ? (string) $filters['sort'] : 'id';
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($sort, $direction)->paginate($perPage)->withQueryString();
    }

    public function findForShow(PurchaseRequisition $purchaseRequisition): PurchaseRequisition
    {
        return $purchaseRequisition->load([
            'requester',
            'items.item',
            'items.materialRequirement.customerOrderItem.customerOrder',
            'items.sources.materialRequirement.customerOrderItem.customerOrder',
            'items.proposalSources.supplyProposal',
            'supplier',
        ])->loadCount('items');
    }

    public function findForSupplierSelection(PurchaseRequisition $purchaseRequisition): PurchaseRequisition
    {
        return $purchaseRequisition->loadMissing('items.item');
    }

    public function lockForSupplierSelection(int $requisitionId): PurchaseRequisition
    {
        return PurchaseRequisition::query()
            ->whereKey($requisitionId)
            ->lockForUpdate()
            ->with('items.item')
            ->firstOrFail();
    }

    public function assignSupplier(PurchaseRequisition $requisition, int $supplierId): PurchaseRequisition
    {
        $requisition->update(['supplier_id' => $supplierId]);

        return $requisition->refresh();
    }

    public function lockForReplenishment(int $requisitionId): PurchaseRequisition
    {
        return PurchaseRequisition::query()
            ->whereKey($requisitionId)
            ->lockForUpdate()
            ->with(['supplier', 'items.item', 'items.proposalSources'])
            ->firstOrFail();
    }

    public function updateItemReplenishment(PurchaseRequisitionItem $item, array $attributes): PurchaseRequisitionItem
    {
        $item->update($attributes);

        return $item->refresh();
    }

    public function createDraft(array $attributes): PurchaseRequisition
    {
        return PurchaseRequisition::query()->create($attributes);
    }

    public function createItem(PurchaseRequisition $requisition, array $attributes): PurchaseRequisitionItem
    {
        return $requisition->items()->create($attributes);
    }

    public function createProposalSource(PurchaseRequisitionItem $item, array $attributes): PurchaseRequisitionItemProposalSource
    {
        return $item->proposalSources()->create($attributes);
    }

    public function missingMaterialRequirements(): Collection
    {
        return MaterialRequirement::query()
            ->with(['requiredItem', 'customerOrderItem.customerOrder'])
            ->where('missing_quantity', '>', 0)
            ->whereDoesntHave('purchaseRequisitionSources')
            ->orderBy('required_item_id')
            ->get();
    }
}
