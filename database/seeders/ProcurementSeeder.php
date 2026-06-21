<?php

namespace Database\Seeders;

use App\Enums\PurchaseOrderItemStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequisitionItemStatus;
use App\Enums\PurchaseRequisitionStatus;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Item;
use App\Models\ItemBatch;
use App\Models\Location;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class ProcurementSeeder extends Seeder
{
    /**
     * Seed sample procurement documents.
     */
    public function run(): void
    {
        $supplier = Supplier::query()->updateOrCreate(
            ['code' => 'SUP-BASE'],
            [
                'name' => 'Alap beszállító',
                'email' => 'beszerzes@example.com',
                'is_active' => true,
            ],
        );

        $purchaseRequisition = PurchaseRequisition::query()->updateOrCreate(
            ['requisition_number' => 'PR-2026-000001'],
            [
                'status' => PurchaseRequisitionStatus::Requested->value,
                'requested_at' => '2026-06-21 08:00:00',
                'notes' => 'Minta beszerzési igény nyitó adatokhoz.',
            ],
        );

        $purchaseOrder = PurchaseOrder::query()->updateOrCreate(
            ['order_number' => 'PO-SUP-2026-000001'],
            [
                'supplier_id' => $supplier->id,
                'purchase_requisition_id' => $purchaseRequisition->id,
                'status' => PurchaseOrderStatus::Received->value,
                'ordered_at' => '2026-06-21 09:00:00',
                'expected_delivery_date' => '2026-06-28',
                'notes' => 'Minta beszállítói rendelés.',
            ],
        );

        $goodsReceipt = GoodsReceipt::query()->updateOrCreate(
            ['receipt_number' => 'GR-2026-000001'],
            [
                'purchase_order_id' => $purchaseOrder->id,
                'received_at' => '2026-06-21 12:00:00',
                'notes' => 'Minta áruátvétel.',
            ],
        );

        $mainWarehouse = Location::query()->where('code', 'MAIN-WH')->firstOrFail();

        foreach ($this->items() as $seedItem) {
            $item = Item::query()->where('item_number', $seedItem['item_number'])->firstOrFail();

            $requisitionItem = PurchaseRequisitionItem::query()->updateOrCreate(
                [
                    'purchase_requisition_id' => $purchaseRequisition->id,
                    'item_id' => $item->id,
                ],
                [
                    'quantity' => $seedItem['quantity'],
                    'unit' => $item->unit,
                    'status' => PurchaseRequisitionItemStatus::Ordered->value,
                ],
            );

            $orderItem = PurchaseOrderItem::query()->updateOrCreate(
                [
                    'purchase_order_id' => $purchaseOrder->id,
                    'item_id' => $item->id,
                ],
                [
                    'purchase_requisition_item_id' => $requisitionItem->id,
                    'ordered_quantity' => $seedItem['quantity'],
                    'received_quantity' => $seedItem['quantity'],
                    'unit' => $item->unit,
                    'status' => PurchaseOrderItemStatus::Received->value,
                ],
            );

            $batch = ItemBatch::query()->updateOrCreate(
                [
                    'item_id' => $item->id,
                    'batch_number' => $seedItem['batch_number'],
                ],
                [
                    'supplier_id' => $supplier->id,
                    'received_at' => '2026-06-21',
                    'notes' => 'Minta beszerzési batch.',
                ],
            );

            GoodsReceiptItem::query()->updateOrCreate(
                [
                    'goods_receipt_id' => $goodsReceipt->id,
                    'item_id' => $item->id,
                ],
                [
                    'purchase_order_item_id' => $orderItem->id,
                    'item_batch_id' => $batch->id,
                    'location_id' => $mainWarehouse->id,
                    'quantity' => $seedItem['quantity'],
                ],
            );
        }
    }

    /**
     * @return array<int, array{item_number: string, batch_number: string, quantity: int}>
     */
    private function items(): array
    {
        return [
            ['item_number' => 'SCR-M4X20', 'batch_number' => 'BATCH-2026-0002', 'quantity' => 250],
            ['item_number' => 'NUT-M4', 'batch_number' => 'BATCH-2026-0002', 'quantity' => 250],
            ['item_number' => 'PAINT-BLACK', 'batch_number' => 'BATCH-2026-0002', 'quantity' => 20],
        ];
    }
}
