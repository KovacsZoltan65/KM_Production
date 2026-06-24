<?php

namespace Tests\Feature;

use App\Enums\GoodsReceiptStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequisitionStatus;
use App\Enums\StockMovementType;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Item;
use App\Models\Location;
use App\Models\MaterialRequirement;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequisition;
use App\Models\StockBalance;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Admin\GoodsReceiptService;
use App\Services\Admin\PurchaseRequisitionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ProcurementManagementUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_dashboard_loads(): void
    {
        $user = $this->verifiedUser('procurement-manager');

        $this->actingAs($user)
            ->get(route('admin.procurement.dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Procurement/Dashboard')
                ->has('metrics.open_requisitions')
                ->has('metrics.top_missing_materials'));
    }

    public function test_requisition_can_be_generated_from_shortages(): void
    {
        $user = $this->verifiedUser('procurement-manager');
        $material = Item::factory()->purchasedMaterial()->create(['unit' => 'db']);
        MaterialRequirement::factory()->create([
            'required_item_id' => $material->id,
            'missing_quantity' => 10,
            'unit' => 'db',
        ]);

        $requisition = app(PurchaseRequisitionService::class)->generateFromMaterialRequirements($user);

        $this->assertSame(PurchaseRequisitionStatus::Requested, $requisition->status);
        $this->assertDatabaseHas('purchase_requisition_items', [
            'purchase_requisition_id' => $requisition->id,
            'item_id' => $material->id,
            'quantity' => 10,
        ]);
    }

    public function test_identical_items_are_consolidated(): void
    {
        $material = Item::factory()->purchasedMaterial()->create(['unit' => 'db']);
        MaterialRequirement::factory()->count(2)->create([
            'required_item_id' => $material->id,
            'missing_quantity' => 5,
            'unit' => 'db',
        ]);

        $requisition = app(PurchaseRequisitionService::class)->generateFromMaterialRequirements();

        $this->assertSame(1, $requisition->items()->count());
        $this->assertDatabaseHas('purchase_requisition_items', [
            'purchase_requisition_id' => $requisition->id,
            'item_id' => $material->id,
            'quantity' => 10,
        ]);
    }

    public function test_requisition_preserves_source_links(): void
    {
        $material = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
        $requirementA = MaterialRequirement::factory()->create(['required_item_id' => $material->id, 'missing_quantity' => 2, 'unit' => 'kg']);
        $requirementB = MaterialRequirement::factory()->create(['required_item_id' => $material->id, 'missing_quantity' => 3, 'unit' => 'kg']);

        $requisition = app(PurchaseRequisitionService::class)->generateFromMaterialRequirements();
        $item = $requisition->items()->firstOrFail();

        $this->assertDatabaseHas('purchase_requisition_item_sources', [
            'purchase_requisition_item_id' => $item->id,
            'material_requirement_id' => $requirementA->id,
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('purchase_requisition_item_sources', [
            'purchase_requisition_item_id' => $item->id,
            'material_requirement_id' => $requirementB->id,
            'quantity' => 3,
        ]);
    }

    public function test_requisition_approve_works(): void
    {
        $user = $this->verifiedUser('procurement-manager');
        $requisition = PurchaseRequisition::factory()->create(['status' => PurchaseRequisitionStatus::Requested]);

        $this->actingAs($user)
            ->patch(route('admin.purchase-requisitions.approve', $requisition))
            ->assertRedirect();

        $this->assertDatabaseHas('purchase_requisitions', [
            'id' => $requisition->id,
            'status' => PurchaseRequisitionStatus::Approved->value,
        ]);
    }

    public function test_purchase_order_can_be_generated_from_approved_requisition(): void
    {
        $user = $this->verifiedUser('procurement-manager');
        $supplier = Supplier::factory()->create();
        $requisition = PurchaseRequisition::factory()->create(['status' => PurchaseRequisitionStatus::Approved]);
        $material = Item::factory()->purchasedMaterial()->create();
        $requisition->items()->create([
            'item_id' => $material->id,
            'quantity' => 4,
            'unit' => $material->unit,
        ]);

        $this->actingAs($user)
            ->post(route('admin.purchase-requisitions.generate-purchase-order', $requisition), [
                'supplier_id' => $supplier->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('purchase_orders', [
            'supplier_id' => $supplier->id,
            'purchase_requisition_id' => $requisition->id,
        ]);
        $this->assertDatabaseHas('purchase_order_items', [
            'item_id' => $material->id,
            'ordered_quantity' => 4,
        ]);
    }

    public function test_purchase_order_approve_works(): void
    {
        $user = $this->verifiedUser('procurement-manager');
        $purchaseOrder = PurchaseOrder::factory()->create(['status' => PurchaseOrderStatus::Draft]);

        $this->actingAs($user)
            ->patch(route('admin.purchase-orders.approve', $purchaseOrder))
            ->assertRedirect();

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
            'status' => PurchaseOrderStatus::Ordered->value,
        ]);
    }

    public function test_goods_receipt_can_be_created(): void
    {
        $user = $this->verifiedUser('procurement-manager');
        $purchaseOrder = PurchaseOrder::factory()->create();
        $item = Item::factory()->purchasedMaterial()->create();
        $location = Location::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.goods-receipts.store'), [
                'purchase_order_id' => $purchaseOrder->id,
                'items' => [[
                    'item_id' => $item->id,
                    'location_id' => $location->id,
                    'quantity' => 7,
                ]],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('goods_receipts', [
            'purchase_order_id' => $purchaseOrder->id,
            'status' => GoodsReceiptStatus::Draft->value,
        ]);
        $this->assertDatabaseHas('goods_receipt_items', [
            'item_id' => $item->id,
            'location_id' => $location->id,
            'quantity' => 7,
        ]);
    }

    public function test_goods_receipt_post_creates_stock_movement_and_increases_balance(): void
    {
        $user = $this->verifiedUser('procurement-manager');
        [$goodsReceipt, $item, $location] = $this->goodsReceiptFixture();

        $this->actingAs($user)
            ->post(route('admin.goods-receipts.post', $goodsReceipt))
            ->assertRedirect();

        $this->assertDatabaseHas('goods_receipts', [
            'id' => $goodsReceipt->id,
            'status' => GoodsReceiptStatus::Posted->value,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'item_id' => $item->id,
            'to_location_id' => $location->id,
            'quantity' => 6,
            'movement_type' => StockMovementType::PurchaseReceive->value,
            'source_type' => GoodsReceipt::class,
            'source_id' => $goodsReceipt->id,
        ]);
        $this->assertDatabaseHas('stock_balances', [
            'item_id' => $item->id,
            'location_id' => $location->id,
            'quantity' => 6,
        ]);
    }

    public function test_posted_goods_receipt_cannot_be_posted_again(): void
    {
        [$goodsReceipt] = $this->goodsReceiptFixture(status: GoodsReceiptStatus::Posted);

        $this->expectException(ValidationException::class);

        app(GoodsReceiptService::class)->post($goodsReceipt);
    }

    public function test_audit_log_is_created_on_requisition_generation(): void
    {
        $user = $this->verifiedUser('procurement-manager');
        $material = Item::factory()->purchasedMaterial()->create();
        MaterialRequirement::factory()->create(['required_item_id' => $material->id, 'missing_quantity' => 1]);

        $requisition = app(PurchaseRequisitionService::class)->generateFromMaterialRequirements($user);

        $activity = Activity::query()->where('event', 'purchase_requisition_generated')->firstOrFail();
        $this->assertTrue($activity->subject->is($requisition));
        $this->assertTrue($activity->causer->is($user));
    }

    public function test_audit_log_is_created_on_goods_receipt_post(): void
    {
        $user = $this->verifiedUser('procurement-manager');
        [$goodsReceipt] = $this->goodsReceiptFixture();

        app(GoodsReceiptService::class)->post($goodsReceipt, $user);

        $activity = Activity::query()->where('event', 'goods_receipt_posted')->firstOrFail();
        $this->assertTrue($activity->subject->is($goodsReceipt));
        $this->assertTrue($activity->causer->is($user));
    }

    public function test_procurement_pages_are_permission_protected(): void
    {
        $user = $this->verifiedUser();

        $this->actingAs($user)
            ->get(route('admin.procurement.dashboard'))
            ->assertForbidden();
    }

    private function verifiedUser(?string $role = null): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        if ($role !== null) {
            $user->assignRole($role);
        }

        return $user;
    }

    /**
     * @return array{0: GoodsReceipt, 1: Item, 2: Location}
     */
    private function goodsReceiptFixture(GoodsReceiptStatus $status = GoodsReceiptStatus::Draft): array
    {
        $item = Item::factory()->purchasedMaterial()->create();
        $location = Location::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->create(['status' => PurchaseOrderStatus::Ordered]);
        $purchaseOrderItem = PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'item_id' => $item->id,
            'ordered_quantity' => 6,
            'received_quantity' => 0,
            'unit' => $item->unit,
        ]);
        $goodsReceipt = GoodsReceipt::factory()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'status' => $status,
        ]);
        GoodsReceiptItem::factory()->create([
            'goods_receipt_id' => $goodsReceipt->id,
            'purchase_order_item_id' => $purchaseOrderItem->id,
            'item_id' => $item->id,
            'location_id' => $location->id,
            'quantity' => 6,
        ]);

        return [$goodsReceipt, $item, $location];
    }
}
