<?php

use App\Enums\PurchaseOrderItemStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\StockReservationStatus;
use App\Models\BomItem;
use App\Models\Item;
use App\Models\MaterialRequirement;
use App\Models\MaterialRequirementPeg;
use App\Models\ProductionOrder;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequisition;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\StockReservation;
use App\Models\SupplyProposal;
use App\Repositories\Contracts\MaterialRequirementPegRepositoryInterface;
use App\Services\Admin\MaterialRequirementNettingService;
use App\Services\Admin\MaterialRequirementPeggingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

it('creates the explicit persisted peg schema', function (): void {
    expect(Schema::hasColumns('material_requirement_pegs', [
        'material_requirement_id',
        'stock_balance_id',
        'purchase_order_item_id',
        'quantity',
        'unit',
        'supply_at',
        'calculated_at',
    ]))->toBeTrue();
});

function pegRequirement(Item $item, string $quantity, ?string $requiredAt): MaterialRequirement
{
    $order = ProductionOrder::factory()->create(['planned_start_date' => $requiredAt]);
    $bomItem = BomItem::factory()->create(['bom_id' => $order->bom_id, 'item_id' => $item->id, 'unit' => $item->unit]);

    return MaterialRequirement::factory()->create([
        'production_order_id' => $order->id,
        'bom_item_id' => $bomItem->id,
        'customer_order_item_id' => $order->customer_order_item_id,
        'required_item_id' => $item->id,
        'required_quantity' => $quantity,
        'required_at' => $requiredAt,
        'unit' => $item->unit,
    ]);
}

function pegIncoming(Item $item, string $ordered, string $received, ?string $date, PurchaseOrderStatus $status = PurchaseOrderStatus::Ordered): PurchaseOrderItem
{
    $order = PurchaseOrder::factory()->create(['status' => $status, 'expected_delivery_date' => $date]);

    return PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $order->id,
        'item_id' => $item->id,
        'ordered_quantity' => $ordered,
        'received_quantity' => $received,
        'unit' => $item->unit,
        'status' => $status === PurchaseOrderStatus::PartiallyReceived
            ? PurchaseOrderItemStatus::PartiallyReceived
            : PurchaseOrderItemStatus::Ordered,
    ]);
}

it('persists exact stock and PO trace matching the 0009 kémcső coverage', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    $requirement = pegRequirement($item, '10.000', '2026-08-10');
    $stock = StockBalance::factory()->create(['item_id' => $item->id, 'quantity' => '3.000']);
    $poItem = pegIncoming($item, '4.000', '0.000', '2026-08-09');

    $result = app(MaterialRequirementNettingService::class)->calculate()->sole();
    $pegs = app(MaterialRequirementPeggingService::class)->recalculateForRequirement($requirement);

    expect($result->grossRequirement)->toBe('10.000')
        ->and($result->onHandCoverage)->toBe('3.000')
        ->and($result->incomingCoverage)->toBe('4.000')
        ->and($result->netRequirement)->toBe('3.000')
        ->and($pegs)->toHaveCount(2)
        ->and($pegs[0]->stock_balance_id)->toBe($stock->id)
        ->and($pegs[0]->quantity)->toBe('3.000')
        ->and($pegs[1]->purchase_order_item_id)->toBe($poItem->id)
        ->and($pegs[1]->quantity)->toBe('4.000')
        ->and($pegs->every(fn (MaterialRequirementPeg $peg): bool => ($peg->stock_balance_id === null) xor ($peg->purchase_order_item_id === null)))->toBeTrue();

    assertDatabaseHas('activity_log', [
        'event' => 'material_requirement_pegging_recalculated',
        'subject_type' => MaterialRequirement::class,
        'subject_id' => $requirement->id,
    ]);
});

it('uses deterministic concrete stock sources and never overallocates shared supply', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    $first = pegRequirement($item, '5.000', '2026-08-10');
    $second = pegRequirement($item, '7.000', '2026-08-20');
    $stockA = StockBalance::factory()->create(['item_id' => $item->id, 'quantity' => '2.000']);
    $stockB = StockBalance::factory()->create(['item_id' => $item->id, 'quantity' => '3.000']);
    $poItem = pegIncoming($item, '5.000', '0.000', '2026-08-15');

    $pegs = app(MaterialRequirementPeggingService::class)->recalculateForRequirements(collect([$second, $first]));

    expect($pegs->where('material_requirement_id', $first->id)->pluck('stock_balance_id')->all())->toBe([$stockA->id, $stockB->id])
        ->and($pegs->where('material_requirement_id', $first->id)->sum('quantity'))->toEqual(5.0)
        ->and($pegs->where('material_requirement_id', $second->id)->sole()->purchase_order_item_id)->toBe($poItem->id)
        ->and(MaterialRequirementPeg::query()->where('stock_balance_id', $stockA->id)->sum('quantity'))->toEqual(2.0)
        ->and(MaterialRequirementPeg::query()->where('stock_balance_id', $stockB->id)->sum('quantity'))->toEqual(3.0)
        ->and(MaterialRequirementPeg::query()->where('purchase_order_item_id', $poItem->id)->sum('quantity'))->toEqual(5.0);
});

it('expands a single requirement recalculation to all competing requirements for the item', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    $first = pegRequirement($item, '4.000', '2026-08-10');
    $second = pegRequirement($item, '4.000', '2026-08-20');
    $stock = StockBalance::factory()->create(['item_id' => $item->id, 'quantity' => '5.000']);
    $service = app(MaterialRequirementPeggingService::class);

    $service->recalculateForRequirements(collect([$first, $second]));
    $returned = $service->recalculateForRequirement($second);

    expect($returned)->toHaveCount(1)
        ->and($returned->sole()->material_requirement_id)->toBe($second->id)
        ->and($returned->sole()->quantity)->toBe('1.000')
        ->and(MaterialRequirementPeg::query()->where('stock_balance_id', $stock->id)->sum('quantity'))->toEqual(5.0);
});

it('subtracts active reservations before creating concrete stock pegs', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    $requirement = pegRequirement($item, '10.000', '2026-08-10');
    $stock = StockBalance::factory()->create(['item_id' => $item->id, 'quantity' => '10.000']);
    StockReservation::factory()->create(['item_id' => $item->id, 'reserved_quantity' => '3.000', 'status' => StockReservationStatus::Active]);
    StockReservation::factory()->create(['item_id' => $item->id, 'reserved_quantity' => '4.000', 'status' => StockReservationStatus::Released]);

    $peg = app(MaterialRequirementPeggingService::class)->recalculateForRequirement($requirement)->sole();

    expect($peg->stock_balance_id)->toBe($stock->id)->and($peg->quantity)->toBe('7.000');
});

it('follows the 0009 timing and PO lifecycle policy', function (?string $requiredAt, ?string $supplyAt, PurchaseOrderStatus $status, bool $expected): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    $requirement = pegRequirement($item, '5.000', $requiredAt);
    pegIncoming($item, '5.000', '0.000', $supplyAt, $status);

    expect(app(MaterialRequirementPeggingService::class)->recalculateForRequirement($requirement)->isNotEmpty())->toBe($expected);
})->with([
    'timely firm PO' => ['2026-08-10', '2026-08-09', PurchaseOrderStatus::Ordered, true],
    'same-day firm PO' => ['2026-08-10', '2026-08-10', PurchaseOrderStatus::Ordered, true],
    'late PO' => ['2026-08-10', '2026-08-11', PurchaseOrderStatus::Ordered, false],
    'draft PO' => ['2026-08-10', '2026-08-09', PurchaseOrderStatus::Draft, false],
    'cancelled PO' => ['2026-08-10', '2026-08-09', PurchaseOrderStatus::Cancelled, false],
    'untimed requirement' => [null, '2026-08-09', PurchaseOrderStatus::Ordered, false],
]);

it('uses free stock but not future incoming for an untimed requirement', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    $requirement = pegRequirement($item, '5.000', null);
    $stock = StockBalance::factory()->create(['item_id' => $item->id, 'quantity' => '2.000']);
    pegIncoming($item, '3.000', '0.000', '2026-08-09');

    $peg = app(MaterialRequirementPeggingService::class)->recalculateForRequirement($requirement)->sole();

    expect($peg->stock_balance_id)->toBe($stock->id)
        ->and($peg->purchase_order_item_id)->toBeNull()
        ->and($peg->quantity)->toBe('2.000');
});

it('pegs only the remaining quantity of a partially received PO', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    $requirement = pegRequirement($item, '10.000', '2026-08-10');
    $poItem = pegIncoming($item, '10.000', '4.000', '2026-08-09', PurchaseOrderStatus::PartiallyReceived);

    $peg = app(MaterialRequirementPeggingService::class)->recalculateForRequirement($requirement)->sole();

    expect($peg->purchase_order_item_id)->toBe($poItem->id)->and($peg->quantity)->toBe('6.000');
});

it('rebuilds calculated pegs idempotently after supply changes without creating execution artifacts', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    $requirement = pegRequirement($item, '5.000', '2026-08-10');
    $stock = StockBalance::factory()->create(['item_id' => $item->id, 'quantity' => '3.000']);
    $service = app(MaterialRequirementPeggingService::class);

    $first = $service->recalculateForRequirement($requirement)->map->only(['stock_balance_id', 'purchase_order_item_id', 'quantity'])->all();
    $second = $service->recalculateForRequirement($requirement)->map->only(['stock_balance_id', 'purchase_order_item_id', 'quantity'])->all();
    $stock->update(['quantity' => '1.000']);
    $rebuilt = $service->recalculateForRequirement($requirement);

    expect($second)->toBe($first)
        ->and(MaterialRequirementPeg::query()->count())->toBe(1)
        ->and($rebuilt->sole()->quantity)->toBe('1.000')
        ->and(StockMovement::query()->count())->toBe(0)
        ->and(StockReservation::query()->count())->toBe(0);
});

it('ignores stale requirement snapshots and non-firm planning and requisition artifacts', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    $requirement = pegRequirement($item, '5.000', '2026-08-10');
    $requirement->update(['missing_quantity' => '999.000', 'available_quantity' => '999.000', 'reserved_quantity' => '999.000']);
    SupplyProposal::factory()->approved()->create(['item_id' => $item->id, 'supplier_id' => null, 'proposed_quantity' => '100.000', 'unit' => $item->unit]);
    PurchaseRequisition::factory()->create();

    expect(app(MaterialRequirementPeggingService::class)->recalculateForRequirement($requirement))->toBeEmpty();
});

it('rolls back a failed pegset replacement and keeps the previous trace', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    $requirement = pegRequirement($item, '5.000', '2026-08-10');
    $stock = StockBalance::factory()->create(['item_id' => $item->id, 'quantity' => '3.000']);
    app(MaterialRequirementPeggingService::class)->recalculateForRequirement($requirement);

    app()->instance(MaterialRequirementPegRepositoryInterface::class, new class implements MaterialRequirementPegRepositoryInterface
    {
        public function requirementIdsForItems(array $itemIds): array
        {
            return MaterialRequirement::query()->whereIn('required_item_id', $itemIds)->pluck('id')->all();
        }

        public function lockRequirements(array $requirementIds): Collection
        {
            return MaterialRequirement::query()->whereIn('id', $requirementIds)->get();
        }

        public function replace(array $requirementIds, array $rows): void
        {
            MaterialRequirementPeg::query()->whereIn('material_requirement_id', $requirementIds)->delete();
            throw new RuntimeException('Simulated persistence failure.');
        }
    });

    expect(fn () => app(MaterialRequirementPeggingService::class)->recalculateForRequirement($requirement))
        ->toThrow(RuntimeException::class)
        ->and(MaterialRequirementPeg::query()->sole()->stock_balance_id)->toBe($stock->id)
        ->and(MaterialRequirementPeg::query()->sole()->quantity)->toBe('3.000');
});
