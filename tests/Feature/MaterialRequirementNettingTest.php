<?php

use App\Enums\PurchaseOrderItemStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseRequisitionStatus;
use App\Enums\StockReservationStatus;
use App\Models\BomItem;
use App\Models\CustomerOrderItem;
use App\Models\Item;
use App\Models\MaterialRequirement;
use App\Models\ProductionOrder;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequisition;
use App\Models\StockBalance;
use App\Models\StockReservation;
use App\Models\SupplyProposal;
use App\Services\Admin\MaterialRequirementNettingService;
use App\Services\Admin\MaterialRequirementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function nettingRequirement(
    Item $item,
    string $quantity,
    ?string $requiredAt,
    ?CustomerOrderItem $customerOrderItem = null,
): MaterialRequirement {
    $productionOrder = ProductionOrder::factory()->create([
        ...($customerOrderItem === null ? [] : ['customer_order_item_id' => $customerOrderItem->id]),
        'planned_start_date' => $requiredAt,
    ]);
    $bomItem = BomItem::factory()->create([
        'bom_id' => $productionOrder->bom_id,
        'item_id' => $item->id,
        'quantity' => 1,
        'unit' => $item->unit,
    ]);

    return MaterialRequirement::factory()->create([
        'production_order_id' => $productionOrder->id,
        'bom_item_id' => $bomItem->id,
        'customer_order_item_id' => $productionOrder->customer_order_item_id,
        'required_item_id' => $item->id,
        'required_at' => $requiredAt,
        'required_quantity' => $quantity,
        'unit' => $item->unit,
    ]);
}

function nettingStock(Item $item, string $quantity): void
{
    StockBalance::factory()->create([
        'item_id' => $item->id,
        'quantity' => $quantity,
    ]);
}

function nettingIncoming(
    Item $item,
    string $ordered,
    string $received,
    ?string $expectedAt,
    PurchaseOrderStatus $orderStatus = PurchaseOrderStatus::Ordered,
    PurchaseOrderItemStatus $itemStatus = PurchaseOrderItemStatus::Ordered,
): PurchaseOrderItem {
    $order = PurchaseOrder::factory()->create([
        'status' => $orderStatus,
        'expected_delivery_date' => $expectedAt,
    ]);

    return PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $order->id,
        'item_id' => $item->id,
        'ordered_quantity' => $ordered,
        'received_quantity' => $received,
        'unit' => $item->unit,
        'status' => $itemStatus,
    ]);
}

/** @param Collection<int, MaterialRequirement>|null $requirements */
function nettingResults(?Collection $requirements = null): Collection
{
    return app(MaterialRequirementNettingService::class)->calculate($requirements);
}

it('preserves split production order and BOM lineage for the same customer demand and item', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    $customerOrderItem = CustomerOrderItem::factory()->create();
    $first = nettingRequirement($item, '6.000', '2026-08-10', $customerOrderItem);
    $second = nettingRequirement($item, '4.000', '2026-08-20', $customerOrderItem);

    $results = nettingResults();

    expect($results)->toHaveCount(2)
        ->and($results->pluck('requirementId')->all())->toBe([$first->id, $second->id])
        ->and($results[0]->productionOrderId)->toBe($first->production_order_id)
        ->and($results[0]->bomItemId)->toBe($first->bom_item_id)
        ->and($results[1]->productionOrderId)->toBe($second->production_order_id)
        ->and($results[1]->bomItemId)->toBe($second->bom_item_id);
});

it('is idempotent and creates no planning or procurement artifacts', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    nettingRequirement($item, '10.000', '2026-08-10');

    $first = nettingResults()->map->toArray()->all();
    $second = nettingResults()->map->toArray()->all();

    expect($second)->toBe($first)
        ->and(SupplyProposal::query()->count())->toBe(0)
        ->and(PurchaseRequisition::query()->count())->toBe(0)
        ->and(PurchaseOrder::query()->count())->toBe(0);
});

it('ignores stale Material Requirement snapshot quantities', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    $requirement = nettingRequirement($item, '10.000', '2026-08-10');
    $requirement->update([
        'available_quantity' => 777,
        'reserved_quantity' => 888,
        'missing_quantity' => 999,
    ]);
    nettingStock($item, '4.000');

    $result = nettingResults()->sole();

    expect($result->grossRequirement)->toBe('10.000')
        ->and($result->onHandCoverage)->toBe('4.000')
        ->and($result->netRequirement)->toBe('6.000');
});

it('consumes shared stock once in required date order', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    $later = nettingRequirement($item, '7.000', '2026-08-20');
    $earlier = nettingRequirement($item, '5.000', '2026-08-10');
    nettingStock($item, '4.000');

    $results = nettingResults();

    expect($results->pluck('requirementId')->all())->toBe([$earlier->id, $later->id])
        ->and($results[0]->onHandCoverage)->toBe('4.000')
        ->and($results[0]->netRequirement)->toBe('1.000')
        ->and($results[1]->onHandCoverage)->toBe('0.000')
        ->and($results[1]->netRequirement)->toBe('7.000');
});

it('fully covers sequential requirements from one stock pool', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    nettingRequirement($item, '5.000', '2026-08-10');
    nettingRequirement($item, '7.000', '2026-08-20');
    nettingStock($item, '12.000');

    expect(nettingResults()->pluck('netRequirement')->all())->toBe(['0.000', '0.000']);
});

it('subtracts only active reservations from free on-hand stock', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    nettingRequirement($item, '10.000', '2026-08-10');
    nettingStock($item, '10.000');
    StockReservation::factory()->create([
        'item_id' => $item->id,
        'reserved_quantity' => '3.000',
        'status' => StockReservationStatus::Active,
    ]);
    StockReservation::factory()->create([
        'item_id' => $item->id,
        'reserved_quantity' => '4.000',
        'status' => StockReservationStatus::Released,
    ]);

    $result = nettingResults()->sole();

    expect($result->onHandCoverage)->toBe('7.000')
        ->and($result->netRequirement)->toBe('3.000');
});

it('does not let later incoming cover an earlier requirement', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    nettingRequirement($item, '6.000', '2026-08-10');
    nettingIncoming($item, '10.000', '0.000', '2026-08-15');

    $result = nettingResults()->sole();

    expect($result->incomingCoverage)->toBe('0.000')
        ->and($result->netRequirement)->toBe('6.000');
});

it('uses same-day firm incoming supply', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    nettingRequirement($item, '6.000', '2026-08-10');
    nettingIncoming($item, '6.000', '0.000', '2026-08-10');

    $result = nettingResults()->sole();

    expect($result->incomingCoverage)->toBe('6.000')
        ->and($result->netRequirement)->toBe('0.000');
});

it('excludes undated and unit-inconsistent purchase order supply', function (
    ?string $expectedAt,
    string $unit,
): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    nettingRequirement($item, '5.000', '2026-08-20');
    $incoming = nettingIncoming($item, '5.000', '0.000', $expectedAt);
    $incoming->update(['unit' => $unit]);

    expect(nettingResults()->sole()->netRequirement)->toBe('5.000');
})->with([
    'unknown expected date' => [null, 'kg'],
    'different unit' => ['2026-08-15', 'db'],
]);

it('consumes later incoming once for the first requirement whose cutoff permits it', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    nettingRequirement($item, '5.000', '2026-08-10');
    nettingRequirement($item, '7.000', '2026-08-20');
    nettingStock($item, '4.000');
    nettingIncoming($item, '5.000', '0.000', '2026-08-15');

    $results = nettingResults();

    expect($results[0]->onHandCoverage)->toBe('4.000')
        ->and($results[0]->incomingCoverage)->toBe('0.000')
        ->and($results[0]->netRequirement)->toBe('1.000')
        ->and($results[1]->onHandCoverage)->toBe('0.000')
        ->and($results[1]->incomingCoverage)->toBe('5.000')
        ->and($results[1]->netRequirement)->toBe('2.000');
});

it('uses only the remaining quantity of a partially received firm PO', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    nettingRequirement($item, '10.000', '2026-08-20');
    nettingStock($item, '4.000');
    nettingIncoming(
        $item,
        '10.000',
        '4.000',
        '2026-08-15',
        PurchaseOrderStatus::PartiallyReceived,
        PurchaseOrderItemStatus::PartiallyReceived,
    );

    $result = nettingResults()->sole();

    expect($result->onHandCoverage)->toBe('4.000')
        ->and($result->incomingCoverage)->toBe('6.000')
        ->and($result->netRequirement)->toBe('0.000');
});

it('excludes ineligible PO lifecycle states', function (
    PurchaseOrderStatus $orderStatus,
    PurchaseOrderItemStatus $itemStatus,
): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    nettingRequirement($item, '5.000', '2026-08-20');
    nettingIncoming($item, '5.000', '0.000', '2026-08-15', $orderStatus, $itemStatus);

    expect(nettingResults()->sole()->netRequirement)->toBe('5.000');
})->with([
    'draft PO' => [PurchaseOrderStatus::Draft, PurchaseOrderItemStatus::Ordered],
    'cancelled PO' => [PurchaseOrderStatus::Cancelled, PurchaseOrderItemStatus::Ordered],
    'fully received PO' => [PurchaseOrderStatus::Received, PurchaseOrderItemStatus::Received],
    'cancelled PO item' => [PurchaseOrderStatus::Ordered, PurchaseOrderItemStatus::Cancelled],
]);

it('excludes a fully received quantity already represented in stock', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    nettingRequirement($item, '12.000', '2026-08-20');
    nettingStock($item, '5.000');
    nettingIncoming(
        $item,
        '5.000',
        '5.000',
        '2026-08-15',
        PurchaseOrderStatus::Received,
        PurchaseOrderItemStatus::Received,
    );

    $result = nettingResults()->sole();

    expect($result->onHandCoverage)->toBe('5.000')
        ->and($result->incomingCoverage)->toBe('0.000')
        ->and($result->netRequirement)->toBe('7.000');
});

it('treats null required dates as untimed and does not qualify future incoming', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    $untimed = nettingRequirement($item, '6.000', null);
    $timed = nettingRequirement($item, '2.000', '2026-08-10');
    nettingStock($item, '3.000');
    nettingIncoming($item, '10.000', '0.000', '2026-08-15');

    $results = nettingResults();

    expect($results->pluck('requirementId')->all())->toBe([$timed->id, $untimed->id])
        ->and($results[0]->onHandCoverage)->toBe('2.000')
        ->and($results[0]->netRequirement)->toBe('0.000')
        ->and($results[1]->requiredAt)->toBeNull()
        ->and($results[1]->onHandCoverage)->toBe('1.000')
        ->and($results[1]->incomingCoverage)->toBe('0.000')
        ->and($results[1]->netRequirement)->toBe('5.000');
});

it('excludes approved Supply Proposals and Purchase Requisitions from firm supply', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    nettingRequirement($item, '5.000', '2026-08-20');
    SupplyProposal::factory()->approved()->create([
        'item_id' => $item->id,
        'supplier_id' => null,
        'proposed_quantity' => '100.000',
        'unit' => $item->unit,
    ]);
    PurchaseRequisition::factory()->create(['status' => PurchaseRequisitionStatus::Approved]);

    expect(nettingResults()->sole()->netRequirement)->toBe('5.000');
});

it('calculates the test tube reference quantity exactly to three decimals', function (
    string $stock,
    ?string $incoming,
    ?string $incomingAt,
    string $expectedNet,
): void {
    $material = Item::factory()->purchasedMaterial()->create([
        'name' => 'Kémcső alapanyag',
        'unit' => 'kg',
    ]);
    $requirement = nettingRequirement($material, '10.000', '2026-08-10');
    $requirement->productionOrder->update(['quantity' => '10000.000']);
    $requirement->bomItem->update(['quantity' => '0.001']);
    app(MaterialRequirementService::class)->calculateForProductionOrder(
        $requirement->productionOrder->fresh(),
    );

    if ($stock !== '0.000') {
        nettingStock($material, $stock);
    }

    if ($incoming !== null) {
        nettingIncoming($material, $incoming, '0.000', $incomingAt);
    }

    $result = nettingResults()->sole();

    expect($result->grossRequirement)->toBe('10.000')
        ->and($result->netRequirement)->toBe($expectedNet);
})->with([
    'A: no supply' => ['0.000', null, null, '10.000'],
    'B: partial stock' => ['3.000', null, null, '7.000'],
    'C: stock and timely incoming' => ['3.000', '4.000', '2026-08-09', '3.000'],
    'D: stock and late incoming' => ['3.000', '4.000', '2026-08-11', '7.000'],
]);

it('keeps independent Item base-unit pools in one batch calculation', function (): void {
    $firstItem = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    $secondItem = Item::factory()->purchasedMaterial()->create(['unit' => 'db']);
    nettingRequirement($firstItem, '5.000', '2026-08-10');
    nettingRequirement($firstItem, '7.000', '2026-08-20');
    nettingRequirement($secondItem, '8.000', '2026-08-15');
    nettingStock($firstItem, '4.000');
    nettingStock($secondItem, '8.000');

    expect(nettingResults()->pluck('netRequirement')->all())
        ->toBe(['1.000', '7.000', '0.000']);
});

it('uses a bounded four-query batch read as requirement volume grows', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);

    foreach (range(1, 20) as $day) {
        nettingRequirement($item, '1.000', sprintf('2026-08-%02d', $day));
    }

    nettingStock($item, '5.000');
    nettingIncoming($item, '10.000', '0.000', '2026-08-10');

    DB::flushQueryLog();
    DB::enableQueryLog();
    $results = nettingResults();
    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($results)->toHaveCount(20)
        ->and($queryCount)->toBe(4);
});
