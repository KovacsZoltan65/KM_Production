<?php

use App\Enums\PurchaseRequisitionStatus;
use App\Models\Item;
use App\Models\ItemSupplier;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use App\Models\Supplier;
use App\Models\SupplyProposal;
use App\Models\User;
use App\Services\Admin\PurchaseRequisitionReplenishmentService;
use App\Services\AuditLogService;
use Database\Seeders\RolesAndPermissionsSeeder;
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
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

function replenishmentSource(Item $item, Supplier $supplier, array $attributes = []): ItemSupplier
{
    return ItemSupplier::factory()->approved()->create([
        'item_id' => $item->id,
        'supplier_id' => $supplier->id,
        'purchase_unit' => $item->unit,
        'conversion_factor' => '1.000000',
        'minimum_order_quantity' => null,
        'order_multiple' => null,
        'is_active' => true,
        'valid_from' => null,
        'valid_until' => null,
        ...$attributes,
    ]);
}

/** @param list<array{item: Item, planned: string}> $lines */
function replenishmentRequisition(Supplier $supplier, array $lines, array $attributes = []): PurchaseRequisition
{
    $requisition = PurchaseRequisition::factory()->create([
        'supplier_id' => $supplier->id,
        'status' => PurchaseRequisitionStatus::Draft,
        ...$attributes,
    ]);

    foreach ($lines as $line) {
        PurchaseRequisitionItem::factory()->create([
            'purchase_requisition_id' => $requisition->id,
            'item_id' => $line['item']->id,
            'unit' => $line['item']->unit,
            'planned_quantity' => $line['planned'],
            'quantity' => $line['planned'],
            'replenishment_excess_quantity' => '0.000',
        ]);
    }

    return $requisition;
}

function replenishmentDate(): Carbon
{
    return Carbon::parse('2026-08-16');
}

it('creates explicit planned and replenishment snapshot fields', function (): void {
    expect(Schema::hasColumns('purchase_requisition_items', [
        'planned_quantity',
        'replenishment_excess_quantity',
        'replenishment_item_supplier_id',
        'replenishment_minimum_order_quantity',
        'replenishment_order_multiple',
        'replenishment_strategy',
        'replenishment_calculated_at',
    ]))->toBeTrue();
});

it('calculates every supported strategy with exact thousandths', function (
    string $base,
    ?string $moq,
    ?string $multiple,
    string $adjusted,
    string $excess,
    string $strategy,
): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    $supplier = Supplier::factory()->create();
    $source = replenishmentSource($item, $supplier, [
        'minimum_order_quantity' => $moq,
        'order_multiple' => $multiple,
    ]);

    $result = app(PurchaseRequisitionReplenishmentService::class)
        ->calculateQuantity($base, 'kg', $source);

    expect($result->adjustedQuantity)->toBe($adjusted)
        ->and($result->excessQuantity)->toBe($excess)
        ->and($result->strategy)->toBe($strategy);
})->with([
    'exact' => ['7.000', null, null, '7.000', '0.000', 'exact'],
    'MOQ below' => ['7.000', '10.000', null, '10.000', '3.000', 'moq'],
    'MOQ equal' => ['10.000', '10.000', null, '10.000', '0.000', 'moq'],
    'MOQ above' => ['12.000', '10.000', null, '12.000', '0.000', 'moq'],
    'multiple rounds up' => ['7.000', null, '5.000', '10.000', '3.000', 'order_multiple'],
    'multiple exact' => ['10.000', null, '5.000', '10.000', '0.000', 'order_multiple'],
    'multiple decimal boundary' => ['10.001', null, '5.000', '15.000', '4.999', 'order_multiple'],
    'MOQ then multiple' => ['7.000', '10.000', '6.000', '12.000', '5.000', 'moq_and_order_multiple'],
    'MOQ multiple already valid' => ['12.000', '10.000', '6.000', '12.000', '0.000', 'moq_and_order_multiple'],
    'above MOQ rounds' => ['13.000', '10.000', '6.000', '18.000', '5.000', 'moq_and_order_multiple'],
    'small decimal' => ['0.001', null, '0.005', '0.005', '0.004', 'order_multiple'],
    'decimal MOQ' => ['3.333', '6.667', null, '6.667', '3.334', 'moq'],
]);

it('does not activate MOQ for zero demand and rejects negative demand', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    $supplier = Supplier::factory()->create();
    $source = replenishmentSource($item, $supplier, ['minimum_order_quantity' => '10.000']);
    $service = app(PurchaseRequisitionReplenishmentService::class);

    expect($service->calculateQuantity('0.000', 'kg', $source)->adjustedQuantity)->toBe('0.000')
        ->and(fn () => $service->calculateQuantity('-0.001', 'kg', $source))
        ->toThrow(ValidationException::class);
});

it('preserves multiple proposal lineage while applying item-specific policies atomically', function (): void {
    $supplier = Supplier::factory()->create();
    $itemA = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    $itemB = Item::factory()->purchasedMaterial()->create(['unit' => 'db']);
    replenishmentSource($itemA, $supplier, [
        'minimum_order_quantity' => '12.000',
        'order_multiple' => '5.000',
    ]);
    replenishmentSource($itemB, $supplier, ['order_multiple' => '6.000']);
    $requisition = replenishmentRequisition($supplier, [
        ['item' => $itemA, 'planned' => '10.000'],
        ['item' => $itemB, 'planned' => '13.000'],
    ]);
    $itemRowA = $requisition->items()->where('item_id', $itemA->id)->sole();
    $proposalA = SupplyProposal::factory()->approved()->create([
        'item_id' => $itemA->id, 'supplier_id' => $supplier->id, 'unit' => 'kg', 'proposed_quantity' => '6.000',
    ]);
    $proposalB = SupplyProposal::factory()->approved()->create([
        'item_id' => $itemA->id, 'supplier_id' => $supplier->id, 'unit' => 'kg', 'proposed_quantity' => '4.000',
    ]);
    $itemRowA->proposalSources()->createMany([
        ['supply_proposal_id' => $proposalA->id, 'quantity' => '6.000'],
        ['supply_proposal_id' => $proposalB->id, 'quantity' => '4.000'],
    ]);

    app(PurchaseRequisitionReplenishmentService::class)
        ->calculateForPurchaseRequisition($requisition, null, replenishmentDate());

    $items = $requisition->items()->with('proposalSources')->orderBy('item_id')->get()->keyBy('item_id');
    expect($items[$itemA->id]->planned_quantity)->toBe('10.000')
        ->and($items[$itemA->id]->quantity)->toBe('15.000')
        ->and($items[$itemA->id]->replenishment_excess_quantity)->toBe('5.000')
        ->and($items[$itemA->id]->replenishment_minimum_order_quantity)->toBe('12.000')
        ->and($items[$itemA->id]->replenishment_order_multiple)->toBe('5.000')
        ->and($items[$itemA->id]->proposalSources->pluck('quantity')->all())->toBe(['6.000', '4.000'])
        ->and($items[$itemB->id]->planned_quantity)->toBe('13.000')
        ->and($items[$itemB->id]->quantity)->toBe('18.000')
        ->and($proposalA->refresh()->proposed_quantity)->toBe('6.000')
        ->and($proposalB->refresh()->proposed_quantity)->toBe('4.000');

    assertDatabaseHas('activity_log', [
        'event' => 'purchase_requisition_replenishment_calculated',
        'subject_type' => PurchaseRequisition::class,
        'subject_id' => $requisition->id,
    ]);
    assertDatabaseCount('purchase_orders', 0);
    assertDatabaseCount('goods_receipts', 0);
    assertDatabaseCount('stock_balances', 0);
    assertDatabaseCount('stock_movements', 0);
    assertDatabaseCount('stock_reservations', 0);
});

it('recalculates from planned quantity without drift and is idempotent', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    $supplier = Supplier::factory()->create();
    $source = replenishmentSource($item, $supplier, ['minimum_order_quantity' => '10.000']);
    $requisition = replenishmentRequisition($supplier, [['item' => $item, 'planned' => '7.000']]);
    $service = app(PurchaseRequisitionReplenishmentService::class);

    $service->calculateForPurchaseRequisition($requisition, null, replenishmentDate());
    $service->calculateForPurchaseRequisition($requisition, null, replenishmentDate());
    expect($requisition->items()->sole()->quantity)->toBe('10.000');

    $source->update(['minimum_order_quantity' => '12.000']);
    $service->calculateForPurchaseRequisition($requisition, null, replenishmentDate());

    expect($requisition->items()->sole()->planned_quantity)->toBe('7.000')
        ->and($requisition->items()->sole()->quantity)->toBe('12.000')
        ->and($requisition->items()->sole()->replenishment_excess_quantity)->toBe('5.000');
});

it('requires a selected supplier and a currently eligible source', function (string $invalid): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    $supplier = Supplier::factory()->create();
    $source = replenishmentSource($item, $supplier);
    $requisition = replenishmentRequisition($supplier, [['item' => $item, 'planned' => '7.000']]);

    match ($invalid) {
        'missing' => $source->delete(),
        'inactive' => $source->update(['is_active' => false]),
        'unapproved' => $source->update(['is_approved' => false]),
        'expired' => $source->update(['valid_until' => '2026-08-15']),
        'inactive supplier' => $supplier->update(['is_active' => false]),
        'inactive item' => $item->update(['is_active' => false]),
        default => throw new InvalidArgumentException("Unsupported invalid source case: {$invalid}"),
    };

    expect(fn () => app(PurchaseRequisitionReplenishmentService::class)
        ->calculateForPurchaseRequisition($requisition, null, replenishmentDate()))
        ->toThrow(ValidationException::class)
        ->and($requisition->items()->sole()->quantity)->toBe('7.000');
})->with(['missing', 'inactive', 'unapproved', 'expired', 'inactive supplier', 'inactive item']);

it('blocks supplierless and every non-draft lifecycle state', function (?PurchaseRequisitionStatus $status): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    $supplier = Supplier::factory()->create();
    replenishmentSource($item, $supplier);
    $requisition = replenishmentRequisition($supplier, [['item' => $item, 'planned' => '7.000']], [
        'status' => $status ?? PurchaseRequisitionStatus::Draft,
        'supplier_id' => $status === null ? null : $supplier->id,
    ]);

    expect(fn () => app(PurchaseRequisitionReplenishmentService::class)
        ->calculateForPurchaseRequisition($requisition, null, replenishmentDate()))
        ->toThrow(ValidationException::class);
})->with([
    'supplierless Draft' => [null],
    PurchaseRequisitionStatus::Requested,
    PurchaseRequisitionStatus::Approved,
    PurchaseRequisitionStatus::Ordered,
    PurchaseRequisitionStatus::Cancelled,
]);

it('rolls back every item when one persisted policy is invalid', function (): void {
    $supplier = Supplier::factory()->create();
    $itemA = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    $itemB = Item::factory()->purchasedMaterial()->create(['unit' => 'db']);
    replenishmentSource($itemA, $supplier, ['minimum_order_quantity' => '10.000']);
    $invalid = replenishmentSource($itemB, $supplier, ['order_multiple' => '6.000']);
    DB::table('item_suppliers')->where('id', $invalid->id)->update(['conversion_factor' => -1]);
    $requisition = replenishmentRequisition($supplier, [
        ['item' => $itemA, 'planned' => '7.000'],
        ['item' => $itemB, 'planned' => '13.000'],
    ]);

    expect(fn () => app(PurchaseRequisitionReplenishmentService::class)
        ->calculateForPurchaseRequisition($requisition, null, replenishmentDate()))
        ->toThrow(ValidationException::class)
        ->and($requisition->items()->orderBy('id')->pluck('quantity')->all())->toBe(['7.000', '13.000']);
});

it('rolls back quantity persistence when the business audit fails', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    $supplier = Supplier::factory()->create();
    replenishmentSource($item, $supplier, ['minimum_order_quantity' => '10.000']);
    $requisition = replenishmentRequisition($supplier, [['item' => $item, 'planned' => '7.000']]);
    $audit = Mockery::mock(AuditLogService::class, function (MockInterface $mock): void {
        $expectation = $mock->shouldReceive('log');
        if (! $expectation instanceof CompositeExpectation) {
            throw new LogicException('Mockery did not create a concrete method expectation.');
        }
        $expectation->__call('once', []);
        $expectation->__call('andThrow', [new RuntimeException('audit failed')]);
    });
    app()->instance(AuditLogService::class, $audit);

    expect(fn () => app(PurchaseRequisitionReplenishmentService::class)
        ->calculateForPurchaseRequisition($requisition, null, replenishmentDate()))
        ->toThrow(RuntimeException::class, 'audit failed')
        ->and($requisition->items()->sole()->quantity)->toBe('7.000')
        ->and($requisition->items()->sole()->replenishment_calculated_at)->toBeNull();
});

it('protects the explicit calculation endpoint with procurement update permission', function (): void {
    seed(RolesAndPermissionsSeeder::class);
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    $supplier = Supplier::factory()->create();
    replenishmentSource($item, $supplier, ['minimum_order_quantity' => '10.000']);
    $requisition = replenishmentRequisition($supplier, [['item' => $item, 'planned' => '7.000']]);
    $unauthorized = User::factory()->create();
    $authorized = User::factory()->create();
    $authorized->givePermissionTo('procurement.update');

    actingAs($unauthorized)
        ->patch(route('admin.purchase-requisitions.calculate-replenishment', $requisition))
        ->assertForbidden();

    actingAs($authorized)
        ->patch(route('admin.purchase-requisitions.calculate-replenishment', $requisition))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($requisition->items()->sole()->quantity)->toBe('10.000');
    $activity = Activity::query()->where('event', 'purchase_requisition_replenishment_calculated')->sole();
    expect($activity->properties->get('items_count'))->toBe(1)
        ->and($activity->properties->get('changed_items_count'))->toBe(1);
});
