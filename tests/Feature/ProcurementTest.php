<?php

namespace Tests\Feature;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Item;
use App\Models\ItemBatch;
use App\Models\Location;
use App\Models\MaterialRequirement;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use App\Models\Supplier;
use Database\Seeders\InventorySeeder;
use Database\Seeders\ItemMasterDataSeeder;
use Database\Seeders\OrderProductionSeeder;
use Database\Seeders\ProcurementSeeder;
use Database\Seeders\ProductionMasterDataSeeder;
use Database\Seeders\ProductionStructureSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcurementTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_requisition_connects_to_items(): void
    {
        $purchaseRequisition = PurchaseRequisition::factory()->create();
        $item = Item::factory()->purchasedMaterial()->create();

        $requisitionItem = PurchaseRequisitionItem::factory()->create([
            'purchase_requisition_id' => $purchaseRequisition->id,
            'item_id' => $item->id,
            'unit' => $item->unit,
        ]);

        $this->assertTrue($purchaseRequisition->items->contains($requisitionItem));
        $this->assertTrue($requisitionItem->item->is($item));
    }

    public function test_purchase_requisition_item_can_connect_to_material_requirement(): void
    {
        $materialRequirement = MaterialRequirement::factory()->create();

        $requisitionItem = PurchaseRequisitionItem::factory()->create([
            'material_requirement_id' => $materialRequirement->id,
            'item_id' => $materialRequirement->required_item_id,
            'unit' => $materialRequirement->unit,
        ]);

        $this->assertTrue($requisitionItem->materialRequirement->is($materialRequirement));
    }

    public function test_purchase_order_belongs_to_supplier(): void
    {
        $supplier = Supplier::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->create(['supplier_id' => $supplier->id]);

        $this->assertTrue($purchaseOrder->supplier->is($supplier));
    }

    public function test_purchase_order_item_belongs_to_item(): void
    {
        $item = Item::factory()->purchasedMaterial()->create();
        $purchaseOrderItem = PurchaseOrderItem::factory()->create([
            'item_id' => $item->id,
            'unit' => $item->unit,
        ]);

        $this->assertTrue($purchaseOrderItem->item->is($item));
    }

    public function test_purchase_order_item_stores_received_quantity(): void
    {
        $purchaseOrderItem = PurchaseOrderItem::factory()->create([
            'ordered_quantity' => 100,
            'received_quantity' => 40,
        ]);

        $this->assertSame('40.000', $purchaseOrderItem->received_quantity);
    }

    public function test_goods_receipt_can_connect_to_purchase_order(): void
    {
        $purchaseOrder = PurchaseOrder::factory()->create();
        $goodsReceipt = GoodsReceipt::factory()->create(['purchase_order_id' => $purchaseOrder->id]);

        $this->assertTrue($goodsReceipt->purchaseOrder->is($purchaseOrder));
    }

    public function test_goods_receipt_item_belongs_to_location(): void
    {
        $location = Location::factory()->create();
        $receiptItem = GoodsReceiptItem::factory()->create(['location_id' => $location->id]);

        $this->assertTrue($receiptItem->location->is($location));
    }

    public function test_goods_receipt_item_belongs_to_batch(): void
    {
        $batch = ItemBatch::factory()->create();

        $receiptItem = GoodsReceiptItem::factory()->create([
            'item_id' => $batch->item_id,
            'item_batch_id' => $batch->id,
        ]);

        $this->assertTrue($receiptItem->itemBatch->is($batch));
    }

    public function test_goods_receipt_item_belongs_to_item(): void
    {
        $item = Item::factory()->purchasedMaterial()->create();
        $receiptItem = GoodsReceiptItem::factory()->create(['item_id' => $item->id]);

        $this->assertTrue($receiptItem->item->is($item));
    }

    public function test_procurement_seeder_is_idempotent(): void
    {
        $this->seed(ProductionMasterDataSeeder::class);
        $this->seed(ItemMasterDataSeeder::class);
        $this->seed(ProductionStructureSeeder::class);
        $this->seed(OrderProductionSeeder::class);
        $this->seed(InventorySeeder::class);
        $this->seed(ProcurementSeeder::class);
        $this->seed(ProcurementSeeder::class);

        $this->assertDatabaseCount('purchase_requisitions', 1);
        $this->assertDatabaseCount('purchase_requisition_items', 3);
        $this->assertDatabaseCount('purchase_orders', 1);
        $this->assertDatabaseCount('purchase_order_items', 3);
        $this->assertDatabaseCount('goods_receipts', 1);
        $this->assertDatabaseCount('goods_receipt_items', 3);
        $this->assertDatabaseCount('suppliers', 1);

        $this->assertDatabaseHas('purchase_orders', [
            'order_number' => 'PO-SUP-2026-000001',
        ]);
        $this->assertDatabaseHas('goods_receipts', [
            'receipt_number' => 'GR-2026-000001',
        ]);
    }

    public function test_purchase_order_number_must_be_unique(): void
    {
        PurchaseOrder::factory()->create(['order_number' => 'PO-SUP-2026-000001']);

        $this->expectException(QueryException::class);

        PurchaseOrder::factory()->create(['order_number' => 'PO-SUP-2026-000001']);
    }

    public function test_goods_receipt_number_must_be_unique(): void
    {
        GoodsReceipt::factory()->create(['receipt_number' => 'GR-2026-000001']);

        $this->expectException(QueryException::class);

        GoodsReceipt::factory()->create(['receipt_number' => 'GR-2026-000001']);
    }
}
