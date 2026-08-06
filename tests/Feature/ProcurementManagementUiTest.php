<?php

namespace Tests\Feature;

use App\Enums\GoodsReceiptStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequisitionItemStatus;
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
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Admin\GoodsReceiptService;
use App\Services\Admin\PurchaseOrderService;
use App\Services\Admin\PurchaseRequisitionService;
use App\Services\AuditLogService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use LogicException;
use Mockery;
use Mockery\CompositeExpectation;
use RuntimeException;
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
        $item = $requisition->items()->create([
            'item_id' => Item::factory()->purchasedMaterial()->create()->id,
            'quantity' => 4,
            'unit' => 'db',
            'status' => PurchaseRequisitionItemStatus::Draft,
        ]);

        $this->actingAs($user)
            ->patch(route('admin.purchase-requisitions.approve', $requisition))
            ->assertRedirect()
            ->assertSessionHas('success', __('procurement.purchase_requisitions.messages.approved'));

        $this->assertDatabaseHas('purchase_requisitions', [
            'id' => $requisition->id,
            'status' => PurchaseRequisitionStatus::Approved->value,
        ]);
        $this->assertSame(PurchaseRequisitionItemStatus::Requested, $item->fresh()->status);
        $activity = Activity::query()->where('event', 'purchase_requisition_approved')->firstOrFail();
        $this->assertTrue($activity->subject->is($requisition));
        $this->assertTrue($activity->causer->is($user));
    }

    public function test_requisition_cannot_be_approved_twice(): void
    {
        $user = $this->verifiedUser('procurement-manager');
        $requisition = PurchaseRequisition::factory()->create(['status' => PurchaseRequisitionStatus::Requested]);

        $this->actingAs($user)->patch(route('admin.purchase-requisitions.approve', $requisition));
        $this->actingAs($user)
            ->patch(route('admin.purchase-requisitions.approve', $requisition))
            ->assertSessionHasErrors('status');

        $this->assertSame(PurchaseRequisitionStatus::Approved, $requisition->fresh()->status);
        $this->assertSame(1, Activity::query()->where('event', 'purchase_requisition_approved')->count());
    }

    public function test_user_without_permission_cannot_approve_requisition(): void
    {
        $requisition = PurchaseRequisition::factory()->create(['status' => PurchaseRequisitionStatus::Requested]);

        $this->actingAs($this->verifiedUser())
            ->patch(route('admin.purchase-requisitions.approve', $requisition))
            ->assertForbidden();

        $this->assertSame(PurchaseRequisitionStatus::Requested, $requisition->fresh()->status);
        $this->assertFalse(Activity::query()->where('event', 'purchase_requisition_approved')->exists());
    }

    public function test_requisition_approve_rolls_back_when_audit_fails(): void
    {
        $requisition = PurchaseRequisition::factory()->create(['status' => PurchaseRequisitionStatus::Requested]);
        $item = $requisition->items()->create([
            'item_id' => Item::factory()->purchasedMaterial()->create()->id,
            'quantity' => 4,
            'unit' => 'db',
            'status' => PurchaseRequisitionItemStatus::Draft,
        ]);
        $auditLog = Mockery::mock(AuditLogService::class);
        $expectation = $auditLog->shouldReceive('logUpdated');
        if (! $expectation instanceof CompositeExpectation) {
            throw new LogicException('Mockery did not create a concrete method expectation.');
        }
        $expectation->__call('once', []);
        $expectation->__call('andThrow', [new RuntimeException('Forced approve audit failure')]);
        $this->app->instance(AuditLogService::class, $auditLog);

        try {
            app(PurchaseRequisitionService::class)->approve($requisition);
            $this->fail('The approve transaction did not fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced approve audit failure', $exception->getMessage());
            $this->assertSame(PurchaseRequisitionStatus::Requested, $requisition->fresh()->status);
            $this->assertSame(PurchaseRequisitionItemStatus::Draft, $item->fresh()->status);
            $this->assertFalse(Activity::query()->where('event', 'purchase_requisition_approved')->exists());
        }
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

        $response = $this->actingAs($user)
            ->post(route('admin.purchase-requisitions.generate-purchase-order', $requisition), [
                'supplier_id' => $supplier->id,
                'expected_delivery_date' => '2027-04-15',
            ]);

        $purchaseOrder = PurchaseOrder::query()->where('purchase_requisition_id', $requisition->id)->firstOrFail();
        $response
            ->assertRedirect(route('admin.purchase-orders.show', $purchaseOrder))
            ->assertSessionHas('success', __('procurement.purchase_requisitions.messages.purchase_order_generated'));
        $this->assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
            'supplier_id' => $supplier->id,
            'purchase_requisition_id' => $requisition->id,
            'status' => PurchaseOrderStatus::Draft->value,
            'expected_delivery_date' => '2027-04-15 00:00:00',
        ]);
        $this->assertDatabaseHas('purchase_order_items', [
            'purchase_order_id' => $purchaseOrder->id,
            'purchase_requisition_item_id' => $requisition->items()->firstOrFail()->id,
            'item_id' => $material->id,
            'ordered_quantity' => 4,
            'received_quantity' => 0,
            'unit' => $material->unit,
        ]);
        $this->assertSame(PurchaseRequisitionStatus::Ordered, $requisition->fresh()->status);
        $this->assertSame(PurchaseRequisitionItemStatus::Ordered, $requisition->items()->firstOrFail()->status);
        $activity = Activity::query()->where('event', 'purchase_order_generated')->firstOrFail();
        $this->assertTrue($activity->subject->is($purchaseOrder));
        $this->assertTrue($activity->causer->is($user));
    }

    public function test_purchase_order_generation_cannot_be_repeated(): void
    {
        $user = $this->verifiedUser('procurement-manager');
        $supplier = Supplier::factory()->create();
        $requisition = PurchaseRequisition::factory()->create(['status' => PurchaseRequisitionStatus::Approved]);
        $requisition->items()->create([
            'item_id' => Item::factory()->purchasedMaterial()->create()->id,
            'quantity' => 4,
            'unit' => 'db',
        ]);

        $payload = ['supplier_id' => $supplier->id];
        $this->actingAs($user)->post(route('admin.purchase-requisitions.generate-purchase-order', $requisition), $payload);
        $this->actingAs($user)
            ->post(route('admin.purchase-requisitions.generate-purchase-order', $requisition), $payload)
            ->assertSessionHasErrors('status');

        $this->assertSame(1, PurchaseOrder::query()->where('purchase_requisition_id', $requisition->id)->count());
        $this->assertSame(1, Activity::query()->where('event', 'purchase_order_generated')->count());
    }

    public function test_user_without_permission_cannot_generate_purchase_order(): void
    {
        $supplier = Supplier::factory()->create();
        $requisition = PurchaseRequisition::factory()->create(['status' => PurchaseRequisitionStatus::Approved]);

        $this->actingAs($this->verifiedUser())
            ->post(route('admin.purchase-requisitions.generate-purchase-order', $requisition), [
                'supplier_id' => $supplier->id,
            ])
            ->assertForbidden();

        $this->assertSame(PurchaseRequisitionStatus::Approved, $requisition->fresh()->status);
        $this->assertFalse(PurchaseOrder::query()->where('purchase_requisition_id', $requisition->id)->exists());
    }

    public function test_purchase_order_generation_rolls_back_when_audit_fails(): void
    {
        $supplier = Supplier::factory()->create();
        $requisition = PurchaseRequisition::factory()->create(['status' => PurchaseRequisitionStatus::Approved]);
        $item = $requisition->items()->create([
            'item_id' => Item::factory()->purchasedMaterial()->create()->id,
            'quantity' => 4,
            'unit' => 'db',
            'status' => PurchaseRequisitionItemStatus::Requested,
        ]);
        $auditLog = Mockery::mock(AuditLogService::class);
        $expectation = $auditLog->shouldReceive('log');
        if (! $expectation instanceof CompositeExpectation) {
            throw new LogicException('Mockery did not create a concrete method expectation.');
        }
        $expectation->__call('once', []);
        $expectation->__call('andThrow', [new RuntimeException('Forced generation audit failure')]);
        $this->app->instance(AuditLogService::class, $auditLog);

        try {
            app(PurchaseRequisitionService::class)->generatePurchaseOrder($requisition, $supplier->id);
            $this->fail('The generation transaction did not fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced generation audit failure', $exception->getMessage());
            $this->assertSame(PurchaseRequisitionStatus::Approved, $requisition->fresh()->status);
            $this->assertSame(PurchaseRequisitionItemStatus::Requested, $item->fresh()->status);
            $this->assertFalse(PurchaseOrder::query()->where('purchase_requisition_id', $requisition->id)->exists());
            $this->assertFalse(Activity::query()->where('event', 'purchase_order_generated')->exists());
        }
    }

    public function test_purchase_order_approve_works(): void
    {
        $user = $this->verifiedUser('procurement-manager');
        $purchaseOrder = PurchaseOrder::factory()->create(['status' => PurchaseOrderStatus::Draft]);

        $this->actingAs($user)
            ->patch(route('admin.purchase-orders.approve', $purchaseOrder))
            ->assertRedirect()
            ->assertSessionHas('success', __('procurement.purchase_orders.messages.approved'));

        $this->assertDatabaseHas('purchase_orders', [
            'id' => $purchaseOrder->id,
            'status' => PurchaseOrderStatus::Ordered->value,
        ]);
        $this->assertNotNull($purchaseOrder->fresh()->ordered_at);
        $activity = Activity::query()->where('event', 'purchase_order_approved')->firstOrFail();
        $this->assertTrue($activity->subject->is($purchaseOrder));
        $this->assertTrue($activity->causer->is($user));
    }

    public function test_purchase_order_cannot_be_approved_twice(): void
    {
        $user = $this->verifiedUser('procurement-manager');
        $purchaseOrder = PurchaseOrder::factory()->create(['status' => PurchaseOrderStatus::Draft]);

        $this->actingAs($user)->patch(route('admin.purchase-orders.approve', $purchaseOrder));
        $this->actingAs($user)
            ->patch(route('admin.purchase-orders.approve', $purchaseOrder))
            ->assertSessionHasErrors('status');

        $this->assertSame(PurchaseOrderStatus::Ordered, $purchaseOrder->fresh()->status);
        $this->assertSame(1, Activity::query()->where('event', 'purchase_order_approved')->count());
    }

    public function test_user_without_permission_cannot_approve_purchase_order(): void
    {
        $purchaseOrder = PurchaseOrder::factory()->create(['status' => PurchaseOrderStatus::Draft]);

        $this->actingAs($this->verifiedUser())
            ->patch(route('admin.purchase-orders.approve', $purchaseOrder))
            ->assertForbidden();

        $this->assertSame(PurchaseOrderStatus::Draft, $purchaseOrder->fresh()->status);
        $this->assertFalse(Activity::query()->where('event', 'purchase_order_approved')->exists());
    }

    public function test_purchase_order_approve_rolls_back_when_audit_fails(): void
    {
        $purchaseOrder = PurchaseOrder::factory()->create([
            'status' => PurchaseOrderStatus::Draft,
            'ordered_at' => null,
        ]);
        $this->mockFailingPurchaseOrderAudit('Forced purchase order approve audit failure');

        try {
            app(PurchaseOrderService::class)->approve($purchaseOrder);
            $this->fail('The purchase order approve transaction did not fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced purchase order approve audit failure', $exception->getMessage());
            $this->assertSame(PurchaseOrderStatus::Draft, $purchaseOrder->fresh()->status);
            $this->assertNull($purchaseOrder->fresh()->ordered_at);
            $this->assertFalse(Activity::query()->where('event', 'purchase_order_approved')->exists());
        }
    }

    public function test_purchase_order_close_works_for_open_statuses(): void
    {
        $user = $this->verifiedUser('procurement-manager');

        foreach ([PurchaseOrderStatus::Ordered, PurchaseOrderStatus::PartiallyReceived] as $status) {
            $purchaseOrder = PurchaseOrder::factory()->create(['status' => $status]);

            $this->actingAs($user)
                ->patch(route('admin.purchase-orders.close', $purchaseOrder))
                ->assertRedirect()
                ->assertSessionHas('success', __('procurement.purchase_orders.messages.closed'));

            $this->assertSame(PurchaseOrderStatus::Received, $purchaseOrder->fresh()->status);
            $activity = Activity::query()
                ->where('event', 'purchase_order_closed')
                ->where('subject_id', $purchaseOrder->id)
                ->firstOrFail();
            $this->assertTrue($activity->subject->is($purchaseOrder));
            $this->assertTrue($activity->causer->is($user));
        }
    }

    public function test_purchase_order_close_rejects_invalid_and_repeated_transitions(): void
    {
        $user = $this->verifiedUser('procurement-manager');
        $draft = PurchaseOrder::factory()->create(['status' => PurchaseOrderStatus::Draft]);
        $ordered = PurchaseOrder::factory()->create(['status' => PurchaseOrderStatus::Ordered]);

        $this->actingAs($user)
            ->patch(route('admin.purchase-orders.close', $draft))
            ->assertSessionHasErrors('status');
        $this->actingAs($user)->patch(route('admin.purchase-orders.close', $ordered));
        $this->actingAs($user)
            ->patch(route('admin.purchase-orders.close', $ordered))
            ->assertSessionHasErrors('status');

        $this->assertSame(PurchaseOrderStatus::Draft, $draft->fresh()->status);
        $this->assertSame(PurchaseOrderStatus::Received, $ordered->fresh()->status);
        $this->assertSame(1, Activity::query()->where('event', 'purchase_order_closed')->count());
    }

    public function test_user_without_permission_cannot_close_purchase_order(): void
    {
        $purchaseOrder = PurchaseOrder::factory()->create(['status' => PurchaseOrderStatus::Ordered]);

        $this->actingAs($this->verifiedUser())
            ->patch(route('admin.purchase-orders.close', $purchaseOrder))
            ->assertForbidden();

        $this->assertSame(PurchaseOrderStatus::Ordered, $purchaseOrder->fresh()->status);
        $this->assertFalse(Activity::query()->where('event', 'purchase_order_closed')->exists());
    }

    public function test_purchase_order_close_rolls_back_when_audit_fails(): void
    {
        $purchaseOrder = PurchaseOrder::factory()->create(['status' => PurchaseOrderStatus::Ordered]);
        $this->mockFailingPurchaseOrderAudit('Forced purchase order close audit failure');

        try {
            app(PurchaseOrderService::class)->close($purchaseOrder);
            $this->fail('The purchase order close transaction did not fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced purchase order close audit failure', $exception->getMessage());
            $this->assertSame(PurchaseOrderStatus::Ordered, $purchaseOrder->fresh()->status);
            $this->assertFalse(Activity::query()->where('event', 'purchase_order_closed')->exists());
        }
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
        [$goodsReceipt, $item, $location, $purchaseOrder, $purchaseOrderItem] = $this->goodsReceiptFixture();

        $this->actingAs($user)
            ->post(route('admin.goods-receipts.post', $goodsReceipt))
            ->assertRedirect()
            ->assertSessionHas('success', __('procurement.goods_receipts.messages.posted'));

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
            'performed_by' => $user->id,
        ]);
        $this->assertDatabaseHas('stock_balances', [
            'item_id' => $item->id,
            'location_id' => $location->id,
            'quantity' => 6,
        ]);
        $this->assertSame('6.000', $purchaseOrderItem->fresh()->received_quantity);
        $this->assertSame(PurchaseOrderStatus::Received, $purchaseOrder->fresh()->status);
        $this->assertSame(1, StockMovement::query()->where('source_type', GoodsReceipt::class)->where('source_id', $goodsReceipt->id)->count());
        $activity = Activity::query()->where('event', 'goods_receipt_posted')->firstOrFail();
        $this->assertTrue($activity->subject->is($goodsReceipt));
        $this->assertTrue($activity->causer->is($user));
    }

    public function test_partial_goods_receipt_updates_purchase_order_partially(): void
    {
        [$goodsReceipt, $item, $location, $purchaseOrder, $purchaseOrderItem] = $this->goodsReceiptFixture(quantity: 4, orderedQuantity: 10);

        app(GoodsReceiptService::class)->post($goodsReceipt);

        $this->assertSame('4.000', $purchaseOrderItem->fresh()->received_quantity);
        $this->assertSame(PurchaseOrderStatus::PartiallyReceived, $purchaseOrder->fresh()->status);
        $this->assertDatabaseHas('stock_balances', [
            'item_id' => $item->id,
            'location_id' => $location->id,
            'quantity' => 4,
        ]);
    }

    public function test_multiple_goods_receipts_accumulate_purchase_order_quantity(): void
    {
        [$firstReceipt, $item, $location, $purchaseOrder, $purchaseOrderItem] = $this->goodsReceiptFixture(quantity: 4, orderedQuantity: 10);
        $secondReceipt = GoodsReceipt::factory()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'status' => GoodsReceiptStatus::Draft,
        ]);
        GoodsReceiptItem::factory()->create([
            'goods_receipt_id' => $secondReceipt->id,
            'purchase_order_item_id' => $purchaseOrderItem->id,
            'item_id' => $item->id,
            'location_id' => $location->id,
            'quantity' => 6,
        ]);

        app(GoodsReceiptService::class)->post($firstReceipt);
        app(GoodsReceiptService::class)->post($secondReceipt);

        $this->assertSame('10.000', $purchaseOrderItem->fresh()->received_quantity);
        $this->assertSame(PurchaseOrderStatus::Received, $purchaseOrder->fresh()->status);
        $this->assertSame('10.000', StockBalance::query()->where('item_id', $item->id)->where('location_id', $location->id)->firstOrFail()->quantity);
        $this->assertSame(2, StockMovement::query()->where('source_type', GoodsReceipt::class)->whereIn('source_id', [$firstReceipt->id, $secondReceipt->id])->count());
    }

    public function test_posted_goods_receipt_cannot_be_posted_again_without_side_effects(): void
    {
        $user = $this->verifiedUser('procurement-manager');
        [$goodsReceipt, $item, $location, , $purchaseOrderItem] = $this->goodsReceiptFixture();

        $this->actingAs($user)->post(route('admin.goods-receipts.post', $goodsReceipt));
        $this->actingAs($user)
            ->post(route('admin.goods-receipts.post', $goodsReceipt))
            ->assertSessionHasErrors('status');

        $this->assertSame('6.000', $purchaseOrderItem->fresh()->received_quantity);
        $this->assertSame('6.000', StockBalance::query()->where('item_id', $item->id)->where('location_id', $location->id)->firstOrFail()->quantity);
        $this->assertSame(1, StockMovement::query()->where('source_type', GoodsReceipt::class)->where('source_id', $goodsReceipt->id)->count());
        $this->assertSame(1, Activity::query()->where('event', 'goods_receipt_posted')->count());
    }

    public function test_goods_receipt_creation_rejects_zero_and_negative_quantities(): void
    {
        $user = $this->verifiedUser('procurement-manager');
        $item = Item::factory()->purchasedMaterial()->create();
        $location = Location::factory()->create();

        foreach ([0, -1] as $quantity) {
            $this->actingAs($user)
                ->post(route('admin.goods-receipts.store'), [
                    'items' => [[
                        'item_id' => $item->id,
                        'location_id' => $location->id,
                        'quantity' => $quantity,
                    ]],
                ])
                ->assertSessionHasErrors('items.0.quantity');
        }

        $this->assertFalse(GoodsReceipt::query()->exists());
    }

    public function test_user_without_permission_cannot_post_goods_receipt(): void
    {
        [$goodsReceipt, $item, $location, $purchaseOrder, $purchaseOrderItem] = $this->goodsReceiptFixture();

        $this->actingAs($this->verifiedUser())
            ->post(route('admin.goods-receipts.post', $goodsReceipt))
            ->assertForbidden();

        $this->assertSame(GoodsReceiptStatus::Draft, $goodsReceipt->fresh()->status);
        $this->assertSame('0.000', $purchaseOrderItem->fresh()->received_quantity);
        $this->assertSame(PurchaseOrderStatus::Ordered, $purchaseOrder->fresh()->status);
        $this->assertFalse(StockBalance::query()->where('item_id', $item->id)->where('location_id', $location->id)->exists());
        $this->assertFalse(StockMovement::query()->where('source_type', GoodsReceipt::class)->where('source_id', $goodsReceipt->id)->exists());
    }

    public function test_goods_receipt_post_rolls_back_when_final_audit_fails(): void
    {
        [$goodsReceipt, $item, $location, $purchaseOrder, $purchaseOrderItem] = $this->goodsReceiptFixture();
        $auditLog = Mockery::mock(AuditLogService::class);
        $createdExpectation = $auditLog->shouldReceive('logCreated');
        $updatedExpectation = $auditLog->shouldReceive('logUpdated');
        if (! $createdExpectation instanceof CompositeExpectation || ! $updatedExpectation instanceof CompositeExpectation) {
            throw new LogicException('Mockery did not create concrete goods receipt audit expectations.');
        }
        $createdExpectation->__call('once', []);
        $updatedExpectation->__call('once', []);
        $updatedExpectation->__call('andThrow', [new RuntimeException('Forced receipt audit failure')]);
        $this->app->instance(AuditLogService::class, $auditLog);

        try {
            app(GoodsReceiptService::class)->post($goodsReceipt);
            $this->fail('The goods receipt post transaction did not fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced receipt audit failure', $exception->getMessage());
        }

        $this->assertGoodsReceiptPostWasRolledBack($goodsReceipt, $item, $location, $purchaseOrder, $purchaseOrderItem);
    }

    public function test_goods_receipt_post_rolls_back_when_stock_movement_creation_fails(): void
    {
        [$goodsReceipt, $item, $location, $purchaseOrder, $purchaseOrderItem] = $this->goodsReceiptFixture();
        StockMovement::creating(function (): never {
            throw new RuntimeException('Forced stock movement failure');
        });

        try {
            app(GoodsReceiptService::class)->post($goodsReceipt);
            $this->fail('The stock movement failure did not abort posting.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced stock movement failure', $exception->getMessage());
        }

        $this->assertGoodsReceiptPostWasRolledBack($goodsReceipt, $item, $location, $purchaseOrder, $purchaseOrderItem);
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

    private function mockFailingPurchaseOrderAudit(string $message): void
    {
        $auditLog = Mockery::mock(AuditLogService::class);
        $expectation = $auditLog->shouldReceive('logUpdated');
        if (! $expectation instanceof CompositeExpectation) {
            throw new LogicException('Mockery did not create a concrete method expectation.');
        }
        $expectation->__call('once', []);
        $expectation->__call('andThrow', [new RuntimeException($message)]);
        $this->app->instance(AuditLogService::class, $auditLog);
    }

    /**
     * @return array{0: GoodsReceipt, 1: Item, 2: Location, 3: PurchaseOrder, 4: PurchaseOrderItem}
     */
    private function goodsReceiptFixture(
        GoodsReceiptStatus $status = GoodsReceiptStatus::Draft,
        float $quantity = 6,
        float $orderedQuantity = 6,
        float $receivedQuantity = 0,
    ): array {
        $item = Item::factory()->purchasedMaterial()->create();
        $location = Location::factory()->create();
        $purchaseOrder = PurchaseOrder::factory()->create(['status' => PurchaseOrderStatus::Ordered]);
        $purchaseOrderItem = PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $purchaseOrder->id,
            'item_id' => $item->id,
            'ordered_quantity' => $orderedQuantity,
            'received_quantity' => $receivedQuantity,
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
            'quantity' => $quantity,
        ]);

        return [$goodsReceipt, $item, $location, $purchaseOrder, $purchaseOrderItem];
    }

    private function assertGoodsReceiptPostWasRolledBack(
        GoodsReceipt $goodsReceipt,
        Item $item,
        Location $location,
        PurchaseOrder $purchaseOrder,
        PurchaseOrderItem $purchaseOrderItem,
    ): void {
        $this->assertSame(GoodsReceiptStatus::Draft, $goodsReceipt->fresh()->status);
        $this->assertSame('0.000', $purchaseOrderItem->fresh()->received_quantity);
        $this->assertSame(PurchaseOrderStatus::Ordered, $purchaseOrder->fresh()->status);
        $this->assertFalse(StockBalance::query()->where('item_id', $item->id)->where('location_id', $location->id)->exists());
        $this->assertFalse(StockMovement::query()->where('source_type', GoodsReceipt::class)->where('source_id', $goodsReceipt->id)->exists());
        $this->assertFalse(Activity::query()->where('event', 'goods_receipt_posted')->exists());
    }
}
