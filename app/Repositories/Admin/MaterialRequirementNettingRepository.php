<?php

namespace App\Repositories\Admin;

use App\Enums\PurchaseOrderItemStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\StockReservationStatus;
use App\Models\MaterialRequirement;
use App\Models\PurchaseOrderItem;
use App\Models\StockBalance;
use App\Models\StockReservation;
use App\Repositories\Contracts\MaterialRequirementNettingRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class MaterialRequirementNettingRepository implements MaterialRequirementNettingRepositoryInterface
{
    public function requirements(): Collection
    {
        return MaterialRequirement::query()
            ->orderBy('required_item_id')
            ->orderByRaw('CASE WHEN required_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('required_at')
            ->orderBy('id')
            ->get();
    }

    public function usableOnHandByItem(array $itemIds): array
    {
        return collect($this->usableStockByItem($itemIds))->map(
            fn (array $rows): string => $this->fromThousandths(array_sum(array_map(
                fn (array $row): int => $this->toThousandths($row['quantity']),
                $rows,
            ))),
        )->all();
    }

    public function usableStockByItem(array $itemIds): array
    {
        if ($itemIds === []) {
            return [];
        }

        $balances = StockBalance::query()
            ->whereIn('item_id', $itemIds)
            ->where('quantity', '>', 0)
            ->orderBy('item_id')
            ->orderBy('id')
            ->get(['id', 'item_id', 'quantity']);

        $reserved = StockReservation::query()
            ->whereIn('item_id', $itemIds)
            ->where('status', StockReservationStatus::Active->value)
            ->where('reserved_quantity', '>', 0)
            ->selectRaw('item_id, SUM(reserved_quantity) as quantity')
            ->groupBy('item_id')
            ->pluck('quantity', 'item_id');

        $result = array_fill_keys($itemIds, []);
        $remainingReservations = collect($itemIds)->mapWithKeys(fn (int $itemId): array => [
            $itemId => $this->toThousandths((string) ($reserved[$itemId] ?? '0')),
        ])->all();

        foreach ($balances as $balance) {
            $itemId = $balance->item_id;
            $physical = $this->toThousandths((string) $balance->quantity);
            $deduction = min($physical, $remainingReservations[$itemId] ?? 0);
            $remainingReservations[$itemId] = ($remainingReservations[$itemId] ?? 0) - $deduction;
            $usable = $physical - $deduction;

            if ($usable > 0) {
                $result[$itemId][] = ['id' => $balance->id, 'quantity' => $this->fromThousandths($usable)];
            }
        }

        return $result;
    }

    public function firmIncomingByItem(array $itemIds): array
    {
        if ($itemIds === []) {
            return [];
        }

        return PurchaseOrderItem::query()
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
            ->join('items', 'items.id', '=', 'purchase_order_items.item_id')
            ->whereIn('purchase_order_items.item_id', $itemIds)
            ->whereIn('purchase_orders.status', [
                PurchaseOrderStatus::Ordered->value,
                PurchaseOrderStatus::PartiallyReceived->value,
            ])
            ->whereIn('purchase_order_items.status', [
                PurchaseOrderItemStatus::Ordered->value,
                PurchaseOrderItemStatus::PartiallyReceived->value,
            ])
            ->whereNotNull('purchase_orders.expected_delivery_date')
            ->whereColumn('purchase_order_items.unit', 'items.unit')
            ->whereColumn('purchase_order_items.ordered_quantity', '>', 'purchase_order_items.received_quantity')
            ->whereNull('purchase_orders.deleted_at')
            ->whereNull('purchase_order_items.deleted_at')
            ->orderBy('purchase_order_items.item_id')
            ->orderBy('purchase_orders.expected_delivery_date')
            ->orderBy('purchase_order_items.id')
            ->get([
                'purchase_order_items.id',
                'purchase_order_items.purchase_order_id',
                'purchase_order_items.item_id',
                'purchase_order_items.ordered_quantity',
                'purchase_order_items.received_quantity',
                'purchase_orders.expected_delivery_date as available_at',
            ])
            ->groupBy('item_id')
            ->map(fn (Collection $rows): array => $rows->map(fn (PurchaseOrderItem $row): array => [
                'id' => $row->id,
                'purchase_order_id' => $row->purchase_order_id,
                'available_at' => CarbonImmutable::parse(
                    (string) $row->getRawOriginal('available_at'),
                )->toDateString(),
                'ordered_quantity' => (string) $row->ordered_quantity,
                'received_quantity' => (string) $row->received_quantity,
            ])->values()->all())
            ->all();
    }

    private function toThousandths(string $quantity): int
    {
        [$whole, $fraction] = array_pad(explode('.', $quantity, 2), 2, '');

        return ((int) $whole * 1000) + (int) str_pad(substr($fraction, 0, 3), 3, '0');
    }

    private function fromThousandths(int $quantity): string
    {
        return sprintf('%d.%03d', intdiv($quantity, 1000), $quantity % 1000);
    }
}
