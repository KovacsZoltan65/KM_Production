<?php

namespace App\Services\Admin;

use App\Enums\GoodsReceiptStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequisitionStatus;
use App\Models\GoodsReceipt;
use App\Models\MaterialRequirement;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use Illuminate\Support\Collection;

class ProcurementDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function metrics(): array
    {
        return [
            'open_requisitions' => PurchaseRequisition::query()
                ->whereIn('status', [PurchaseRequisitionStatus::Draft->value, PurchaseRequisitionStatus::Requested->value, PurchaseRequisitionStatus::Approved->value])
                ->count(),
            'open_purchase_orders' => PurchaseOrder::query()
                ->whereIn('status', [PurchaseOrderStatus::Draft->value, PurchaseOrderStatus::Ordered->value, PurchaseOrderStatus::PartiallyReceived->value])
                ->count(),
            'pending_goods_receipts' => GoodsReceipt::query()
                ->where('status', GoodsReceiptStatus::Draft->value)
                ->count(),
            'shortages_count' => MaterialRequirement::query()
                ->where('missing_quantity', '>', 0)
                ->count(),
            'top_missing_materials' => $this->topMissingMaterials(),
        ];
    }

    /**
     * @return Collection<int, array{item_id: int, item_number: string, name: string, missing_quantity: float, unit: string}>
     */
    private function topMissingMaterials(): Collection
    {
        return MaterialRequirement::query()
            ->selectRaw('required_item_id, unit, SUM(missing_quantity) as missing_quantity')
            ->with('requiredItem:id,item_number,name')
            ->where('missing_quantity', '>', 0)
            ->groupBy('required_item_id', 'unit')
            ->orderByDesc('missing_quantity')
            ->limit(5)
            ->get()
            ->map(fn (MaterialRequirement $requirement): array => [
                'item_id' => $requirement->required_item_id,
                'item_number' => $requirement->requiredItem->item_number,
                'name' => $requirement->requiredItem->name,
                'missing_quantity' => (float) $requirement->missing_quantity,
                'unit' => $requirement->unit,
            ]);
    }
}
