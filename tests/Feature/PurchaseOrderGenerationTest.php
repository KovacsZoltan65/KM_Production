<?php

use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequisitionItemStatus;
use App\Enums\PurchaseRequisitionStatus;
use App\Models\GoodsReceipt;
use App\Models\Item;
use App\Models\ItemSupplier;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\StockReservation;
use App\Models\Supplier;
use App\Models\SupplyProposal;
use App\Models\User;
use App\Services\Admin\PurchaseRequisitionService;
use App\Services\AuditLogService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mockery\CompositeExpectation;
use Mockery\MockInterface;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Carbon::setTestNow('2026-08-24 12:00:00');
});

afterEach(function (): void {
    Carbon::setTestNow();
});

/** @return array{requisition: PurchaseRequisition, item: PurchaseRequisitionItem, source: ItemSupplier, supplier: Supplier, material: Item} */
function purchaseOrderReadyFixture(array $requisition = [], array $source = [], array $item = []): array
{
    $supplier = Supplier::factory()->create([
        'code' => 'SUP-EXEC',
        'name' => 'Execution Supplier',
        'is_active' => true,
    ]);
    $material = Item::factory()->purchasedMaterial()->create([
        'item_number' => 'KEM-001',
        'name' => 'Kémcső alapanyag',
        'unit' => 'kg',
        'is_active' => true,
    ]);
    $itemSupplier = ItemSupplier::factory()->approved()->create([
        'supplier_id' => $supplier->id,
        'item_id' => $material->id,
        'purchase_unit' => 'bag',
        'conversion_factor' => '25.000000',
        'minimum_order_quantity' => '12.000',
        'order_multiple' => '5.000',
        'unit_price' => '1250.0000',
        'currency' => 'EUR',
        'lead_time_days' => 5,
        'is_active' => true,
        'valid_from' => null,
        'valid_until' => null,
        ...$source,
    ]);
    $purchaseRequisition = PurchaseRequisition::factory()->create([
        'status' => PurchaseRequisitionStatus::Approved,
        'supplier_id' => $supplier->id,
        'required_at' => '2026-09-30',
        'proposed_supply_at' => '2026-09-20',
        ...$requisition,
    ]);
    $requisitionItem = $purchaseRequisition->items()->create([
        'item_id' => $material->id,
        'planned_quantity' => '10.000',
        'quantity' => '15.000',
        'replenishment_excess_quantity' => '5.000',
        'replenishment_item_supplier_id' => $itemSupplier->id,
        'replenishment_minimum_order_quantity' => '12.000',
        'replenishment_order_multiple' => '5.000',
        'replenishment_strategy' => 'moq_and_order_multiple',
        'replenishment_calculated_at' => now(),
        'unit' => 'kg',
        'status' => PurchaseRequisitionItemStatus::Requested,
        ...$item,
    ]);

    foreach (['6.000', '4.000'] as $quantity) {
        $proposal = SupplyProposal::factory()->approved()->create([
            'item_id' => $material->id,
            'supplier_id' => $supplier->id,
            'proposed_quantity' => $quantity,
            'unit' => 'kg',
        ]);
        $requisitionItem->proposalSources()->create([
            'supply_proposal_id' => $proposal->id,
            'quantity' => $quantity,
        ]);
    }

    return [
        'requisition' => $purchaseRequisition,
        'item' => $requisitionItem,
        'source' => $itemSupplier,
        'supplier' => $supplier,
        'material' => $material,
    ];
}

it('adds the execution snapshot and one-PR-to-one-PO schema', function (): void {
    expect(Schema::hasColumns('purchase_orders', [
        'purchase_requisition_id', 'supplier_code_snapshot', 'supplier_name_snapshot',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('purchase_order_items', [
            'purchase_requisition_item_id', 'item_supplier_id', 'item_number_snapshot',
            'item_name_snapshot', 'planned_quantity_snapshot',
            'replenishment_excess_quantity_snapshot', 'purchase_unit_snapshot',
            'conversion_factor_snapshot', 'unit_price_snapshot', 'currency_snapshot',
            'lead_time_days_snapshot', 'minimum_order_quantity_snapshot',
            'order_multiple_snapshot',
        ]))->toBeTrue();
});

it('creates a Draft PO from the kémcső READY lineage and freezes execution snapshots', function (): void {
    ['requisition' => $requisition, 'item' => $requisitionItem, 'source' => $source, 'supplier' => $supplier] = purchaseOrderReadyFixture();

    $purchaseOrder = app(PurchaseRequisitionService::class)
        ->generatePurchaseOrder($requisition, null, '2026-09-25');
    $orderItem = $purchaseOrder->items()->sole();

    expect($purchaseOrder->status)->toBe(PurchaseOrderStatus::Draft)
        ->and($purchaseOrder->supplier_id)->toBe($supplier->id)
        ->and($purchaseOrder->supplier_code_snapshot)->toBe('SUP-EXEC')
        ->and($purchaseOrder->supplier_name_snapshot)->toBe('Execution Supplier')
        ->and($orderItem->purchase_requisition_item_id)->toBe($requisitionItem->id)
        ->and($orderItem->item_supplier_id)->toBe($source->id)
        ->and($orderItem->ordered_quantity)->toBe('15.000')
        ->and($orderItem->planned_quantity_snapshot)->toBe('10.000')
        ->and($orderItem->replenishment_excess_quantity_snapshot)->toBe('5.000')
        ->and($orderItem->unit)->toBe('kg')
        ->and($orderItem->purchase_unit_snapshot)->toBe('bag')
        ->and($orderItem->conversion_factor_snapshot)->toBe('25.000000')
        ->and($orderItem->unit_price_snapshot)->toBe('1250.0000')
        ->and($orderItem->currency_snapshot)->toBe('EUR')
        ->and($orderItem->minimum_order_quantity_snapshot)->toBe('12.000')
        ->and($orderItem->order_multiple_snapshot)->toBe('5.000')
        ->and($requisition->fresh()->status)->toBe(PurchaseRequisitionStatus::Ordered)
        ->and($requisitionItem->fresh()->status)->toBe(PurchaseRequisitionItemStatus::Ordered);

    $source->update([
        'unit_price' => '9999.0000',
        'minimum_order_quantity' => '100.000',
        'conversion_factor' => '10.000000',
    ]);
    $supplier->update(['code' => 'CHANGED', 'name' => 'Changed Supplier']);
    $orderItem->refresh();
    $purchaseOrder->refresh();

    expect($purchaseOrder->supplier_code_snapshot)->toBe('SUP-EXEC')
        ->and($purchaseOrder->supplier_name_snapshot)->toBe('Execution Supplier')
        ->and($orderItem->unit_price_snapshot)->toBe('1250.0000')
        ->and($orderItem->minimum_order_quantity_snapshot)->toBe('12.000')
        ->and($orderItem->conversion_factor_snapshot)->toBe('25.000000');

    $activity = Activity::query()->where('event', 'purchase_order_generated')->sole();
    expect($activity->properties->get('purchase_order_id'))->toBe($purchaseOrder->id)
        ->and($activity->properties->get('purchase_requisition_id'))->toBe($requisition->id)
        ->and($activity->properties->get('supplier_id'))->toBe($supplier->id)
        ->and($activity->properties->get('items_count'))->toBe(1);
});

it('accepts non-blocking missing-price and late-supply warnings', function (): void {
    ['requisition' => $requisition] = purchaseOrderReadyFixture(
        ['required_at' => '2026-08-25'],
        ['unit_price' => null, 'currency' => null, 'lead_time_days' => 10],
    );

    $order = app(PurchaseRequisitionService::class)->generatePurchaseOrder($requisition);

    expect($order->items()->sole()->unit_price_snapshot)->toBeNull()
        ->and($order->items()->sole()->currency_snapshot)->toBeNull();
});

it('blocks every critical execution-readiness regression without side effects', function (Closure $invalidate): void {
    $fixture = purchaseOrderReadyFixture();
    $invalidate($fixture);

    expect(fn () => app(PurchaseRequisitionService::class)->generatePurchaseOrder($fixture['requisition']))
        ->toThrow(ValidationException::class);

    assertDatabaseCount('purchase_orders', 0);
    expect($fixture['requisition']->fresh()->status)->toBe(PurchaseRequisitionStatus::Approved)
        ->and($fixture['item']->fresh()->status)->toBe(PurchaseRequisitionItemStatus::Requested);
})->with([
    'supplier missing' => fn (array $fixture) => $fixture['requisition']->update(['supplier_id' => null]),
    'supplier inactive' => fn (array $fixture) => $fixture['supplier']->update(['is_active' => false]),
    'ItemSupplier inactive after UI READY' => fn (array $fixture) => $fixture['source']->update(['is_active' => false]),
    'replenishment stale after MOQ change' => fn (array $fixture) => $fixture['source']->update(['minimum_order_quantity' => '20.000']),
    'replenishment missing' => fn (array $fixture) => $fixture['item']->update(['replenishment_calculated_at' => null]),
    'quantity invariant mismatch' => fn (array $fixture) => $fixture['item']->update(['quantity' => '14.000']),
    'proposal lineage mismatch' => fn (array $fixture) => $fixture['item']->proposalSources()->firstOrFail()->update(['quantity' => '5.000']),
]);

it('rejects supplierless and conflicting legacy request supplier inputs', function (): void {
    seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->create();
    $user->givePermissionTo('purchase-orders.generate');
    $fixture = purchaseOrderReadyFixture();
    $otherSupplier = Supplier::factory()->create();

    $fixture['requisition']->update(['supplier_id' => null]);
    actingAs($user)
        ->post(route('admin.purchase-requisitions.generate-purchase-order', $fixture['requisition']), [
            'supplier_id' => $fixture['supplier']->id,
        ])
        ->assertSessionHasErrors('supplier_id');
    assertDatabaseCount('purchase_orders', 0);

    $fixture['requisition']->update(['supplier_id' => $fixture['supplier']->id]);
    actingAs($user)
        ->post(route('admin.purchase-requisitions.generate-purchase-order', $fixture['requisition']), [
            'supplier_id' => $otherSupplier->id,
        ])
        ->assertSessionHasErrors('supplier_id');
    assertDatabaseCount('purchase_orders', 0);
});

it('blocks repeat generation and has a database unique concurrency backstop', function (): void {
    ['requisition' => $requisition, 'supplier' => $supplier] = purchaseOrderReadyFixture();
    $service = app(PurchaseRequisitionService::class);
    $order = $service->generatePurchaseOrder($requisition);

    expect(fn () => $service->generatePurchaseOrder($requisition))
        ->toThrow(ValidationException::class, __('procurement.purchase_requisitions.validation.purchase_order_already_generated'));

    expect(fn () => PurchaseOrder::factory()->create([
        'purchase_requisition_id' => $requisition->id,
        'supplier_id' => $supplier->id,
    ]))->toThrow(QueryException::class);

    expect(PurchaseOrder::query()->where('purchase_requisition_id', $requisition->id)->sole()->is($order))->toBeTrue();
});

it('rolls back PO snapshots, audit and lifecycle when audit logging fails', function (): void {
    ['requisition' => $requisition, 'item' => $item] = purchaseOrderReadyFixture();
    $audit = Mockery::mock(AuditLogService::class, function (MockInterface $mock): void {
        $expectation = $mock->shouldReceive('log');
        if (! $expectation instanceof CompositeExpectation) {
            throw new LogicException('Mockery did not create an audit expectation.');
        }
        $expectation->__call('once', []);
        $expectation->__call('andThrow', [new RuntimeException('forced generation audit failure')]);
    });
    app()->instance(AuditLogService::class, $audit);

    expect(fn () => app(PurchaseRequisitionService::class)->generatePurchaseOrder($requisition))
        ->toThrow(RuntimeException::class, 'forced generation audit failure');

    assertDatabaseCount('purchase_orders', 0);
    assertDatabaseCount('purchase_order_items', 0);
    expect($requisition->fresh()->status)->toBe(PurchaseRequisitionStatus::Approved)
        ->and($item->fresh()->status)->toBe(PurchaseRequisitionItemStatus::Requested)
        ->and(Activity::query()->where('event', 'purchase_order_generated')->exists())->toBeFalse();
});

it('creates only the execution document and does not mutate inventory or receiving', function (): void {
    ['requisition' => $requisition] = purchaseOrderReadyFixture();
    $proposalQuantities = SupplyProposal::query()->orderBy('id')->pluck('proposed_quantity')->all();
    $requirements = DB::table('material_requirements')->orderBy('id')->get()->toArray();
    $pegs = DB::table('material_requirement_pegs')->orderBy('id')->get()->toArray();

    app(PurchaseRequisitionService::class)->generatePurchaseOrder($requisition);

    expect(GoodsReceipt::query()->count())->toBe(0)
        ->and(StockBalance::query()->count())->toBe(0)
        ->and(StockMovement::query()->count())->toBe(0)
        ->and(StockReservation::query()->count())->toBe(0)
        ->and(SupplyProposal::query()->orderBy('id')->pluck('proposed_quantity')->all())->toBe($proposalQuantities)
        ->and(DB::table('material_requirements')->orderBy('id')->get()->toArray())->toBe($requirements)
        ->and(DB::table('material_requirement_pegs')->orderBy('id')->get()->toArray())->toBe($pegs)
        ->and(PurchaseOrderItem::query()->count())->toBe(1);
});
