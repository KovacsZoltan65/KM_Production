<?php

use App\Enums\PurchaseRequisitionExecutionReadinessReason as ReasonCode;
use App\Enums\PurchaseRequisitionStatus;
use App\Models\Item;
use App\Models\ItemSupplier;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use App\Models\Supplier;
use App\Models\SupplyProposal;
use App\Models\User;
use App\Repositories\Contracts\ItemSupplierRepositoryInterface;
use App\Services\Admin\PurchaseRequisitionExecutionReadinessService;
use App\Support\Procurement\PurchaseRequisitionExecutionReadinessResult;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\CompositeExpectation;
use Mockery\MockInterface;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

/** @return array{requisition: PurchaseRequisition, item: PurchaseRequisitionItem, source: ItemSupplier, supplier: Supplier} */
function executionReadyRequisition(array $requisitionOverrides = [], array $sourceOverrides = [], array $itemOverrides = []): array
{
    $supplier = Supplier::factory()->create(['is_active' => true]);
    $item = Item::factory()->purchasedMaterial()->create(['is_active' => true, 'unit' => 'db']);
    $source = ItemSupplier::factory()->approved()->create([
        'supplier_id' => $supplier->id,
        'item_id' => $item->id,
        'is_active' => true,
        'valid_from' => null,
        'valid_until' => null,
        'purchase_unit' => 'doboz',
        'conversion_factor' => '1.000000',
        'minimum_order_quantity' => '10.000',
        'order_multiple' => null,
        'unit_price' => '1250.0000',
        'currency' => 'HUF',
        'lead_time_days' => 5,
        ...$sourceOverrides,
    ]);
    $requisition = PurchaseRequisition::factory()->create([
        'status' => PurchaseRequisitionStatus::Approved,
        'supplier_id' => $supplier->id,
        'required_at' => '2026-09-30',
        'proposed_supply_at' => '2026-09-20',
        ...$requisitionOverrides,
    ]);
    $requisitionItem = $requisition->items()->create([
        'item_id' => $item->id,
        'planned_quantity' => '7.000',
        'quantity' => '10.000',
        'replenishment_excess_quantity' => '3.000',
        'replenishment_item_supplier_id' => $source->id,
        'replenishment_minimum_order_quantity' => '10.000',
        'replenishment_order_multiple' => null,
        'replenishment_strategy' => 'moq',
        'replenishment_calculated_at' => '2026-08-24 08:00:00',
        'unit' => 'db',
        ...$itemOverrides,
    ]);

    foreach (['4.000', '3.000'] as $quantity) {
        $proposal = SupplyProposal::factory()->approved()->create([
            'item_id' => $item->id,
            'supplier_id' => $supplier->id,
            'proposed_quantity' => $quantity,
            'unit' => 'db',
        ]);
        $requisitionItem->proposalSources()->create([
            'supply_proposal_id' => $proposal->id,
            'quantity' => $quantity,
        ]);
    }

    return compact('requisition', 'source', 'supplier') + ['item' => $requisitionItem];
}

/** @return list<string> */
function executionReasonCodes(array $reasons): array
{
    return array_map(fn ($reason): string => $reason->code->value, $reasons);
}

function evaluateExecutionReadiness(PurchaseRequisition $requisition): PurchaseRequisitionExecutionReadinessResult
{
    return app(PurchaseRequisitionExecutionReadinessService::class)->evaluate(
        $requisition,
        Carbon::parse('2026-08-24', 'UTC'),
        Carbon::parse('2026-08-24 12:00:00', 'UTC'),
    );
}

it('returns a structured ready result for an approved current requisition', function (): void {
    ['requisition' => $requisition, 'source' => $source, 'supplier' => $supplier] = executionReadyRequisition();

    $result = evaluateExecutionReadiness($requisition);

    expect($result->isReady())->toBeTrue()
        ->and($result->blockingReasons)->toBe([])
        ->and($result->warnings)->toBe([])
        ->and($result->itemResults)->toHaveCount(1)
        ->and($result->itemResults[0]->isReady())->toBeTrue()
        ->and($result->itemResults[0]->supplierId)->toBe($supplier->id)
        ->and($result->itemResults[0]->itemSupplierId)->toBe($source->id)
        ->and($result->toArray())->toMatchArray([
            'purchase_requisition_id' => $requisition->id,
            'is_ready' => true,
            'blocking_reasons' => [],
            'warnings' => [],
            'checked_at' => '2026-08-24T12:00:00.000000Z',
        ]);
});

it('only treats approved requisitions as readiness candidates', function (PurchaseRequisitionStatus $status, bool $ready): void {
    ['requisition' => $requisition] = executionReadyRequisition(['status' => $status]);

    $result = evaluateExecutionReadiness($requisition);

    expect($result->isReady())->toBe($ready);
    if (! $ready) {
        expect(executionReasonCodes($result->blockingReasons))->toContain(ReasonCode::PrNotApproved->value);
    }
})->with([
    'draft' => [PurchaseRequisitionStatus::Draft, false],
    'requested' => [PurchaseRequisitionStatus::Requested, false],
    'approved' => [PurchaseRequisitionStatus::Approved, true],
    'ordered' => [PurchaseRequisitionStatus::Ordered, false],
    'cancelled' => [PurchaseRequisitionStatus::Cancelled, false],
]);

it('blocks an approved requisition without a selected supplier', function (): void {
    ['requisition' => $requisition] = executionReadyRequisition(['supplier_id' => null]);

    $result = evaluateExecutionReadiness($requisition);

    expect($result->isReady())->toBeFalse()
        ->and(executionReasonCodes($result->blockingReasons))->toContain(ReasonCode::SupplierMissing->value);
});

it('blocks an inactive supplier', function (): void {
    ['requisition' => $requisition, 'supplier' => $supplier] = executionReadyRequisition();
    $supplier->update(['is_active' => false]);

    $result = evaluateExecutionReadiness($requisition);

    expect($result->isReady())->toBeFalse()
        ->and(executionReasonCodes($result->blockingReasons))->toContain(ReasonCode::SupplierInactive->value);
});

it('blocks an inactive item', function (): void {
    ['requisition' => $requisition, 'item' => $requisitionItem] = executionReadyRequisition();
    $requisitionItem->item->update(['is_active' => false]);

    $result = evaluateExecutionReadiness($requisition);

    expect($result->isReady())->toBeFalse()
        ->and(executionReasonCodes($result->blockingReasons))->toContain(ReasonCode::ItemInactive->value);
});

it('blocks a soft-deleted item without failing evaluation', function (): void {
    ['requisition' => $requisition, 'item' => $requisitionItem] = executionReadyRequisition();
    $requisitionItem->item->delete();

    $result = evaluateExecutionReadiness($requisition);

    expect($result->isReady())->toBeFalse()
        ->and(executionReasonCodes($result->blockingReasons))->toContain(ReasonCode::ItemInactive->value);
});

it('blocks a requisition item whose unit no longer matches its item', function (): void {
    ['requisition' => $requisition, 'item' => $requisitionItem] = executionReadyRequisition();
    $requisitionItem->update(['unit' => 'kg']);

    $result = evaluateExecutionReadiness($requisition);

    expect($result->isReady())->toBeFalse()
        ->and(executionReasonCodes($result->blockingReasons))->toContain(ReasonCode::ItemUnitInvalid->value);
});

it('blocks an approved requisition without items', function (): void {
    ['requisition' => $requisition] = executionReadyRequisition();
    $requisition->items()->delete();

    $result = evaluateExecutionReadiness($requisition);

    expect($result->isReady())->toBeFalse()
        ->and(executionReasonCodes($result->blockingReasons))->toContain(ReasonCode::ItemsMissing->value);
});

it('blocks every currently ineligible procurement source', function (array $changes): void {
    ['requisition' => $requisition, 'source' => $source] = executionReadyRequisition();
    $source->update($changes);

    $result = evaluateExecutionReadiness($requisition);

    expect($result->isReady())->toBeFalse()
        ->and(executionReasonCodes($result->blockingReasons))->toContain(ReasonCode::ItemSupplierInvalid->value);
})->with([
    'inactive' => [['is_active' => false]],
    'unapproved' => [['is_approved' => false]],
    'expired' => [['valid_until' => '2026-08-23']],
    'future valid' => [['valid_from' => '2026-08-25']],
]);

it('blocks ambiguous execution sources instead of selecting the first one', function (): void {
    ['requisition' => $requisition, 'source' => $source] = executionReadyRequisition();
    $duplicate = $source->replicate();
    $duplicate->id = $source->id + 1000;
    $duplicate->setRelation('item', $source->item);
    $duplicate->setRelation('supplier', $source->supplier);

    $sources = Mockery::mock(ItemSupplierRepositoryInterface::class, function (MockInterface $mock) use ($source, $duplicate): void {
        $expectation = $mock->shouldReceive('eligibleForSupplierAndItemsAt');

        if (! $expectation instanceof CompositeExpectation) {
            throw new LogicException('Mockery did not create a concrete method expectation.');
        }

        $expectation->__call('once', []);
        $expectation->andReturn(collect([$source, $duplicate]));
    });
    app()->instance(ItemSupplierRepositoryInterface::class, $sources);

    $result = evaluateExecutionReadiness($requisition);

    expect($result->isReady())->toBeFalse()
        ->and(executionReasonCodes($result->blockingReasons))->toContain(ReasonCode::ItemSupplierAmbiguous->value);
});

it('distinguishes an exact calculation from replenishment never being calculated', function (): void {
    ['requisition' => $requisition, 'source' => $source, 'item' => $item] = executionReadyRequisition(
        sourceOverrides: ['minimum_order_quantity' => null, 'order_multiple' => null],
        itemOverrides: [
            'planned_quantity' => '7.000',
            'quantity' => '7.000',
            'replenishment_excess_quantity' => '0.000',
            'replenishment_minimum_order_quantity' => null,
            'replenishment_order_multiple' => null,
            'replenishment_strategy' => 'exact',
        ],
    );

    expect(evaluateExecutionReadiness($requisition)->isReady())->toBeTrue();

    $item->update([
        'replenishment_item_supplier_id' => null,
        'replenishment_strategy' => null,
        'replenishment_calculated_at' => null,
    ]);
    $missing = evaluateExecutionReadiness($requisition);

    expect($source->minimum_order_quantity)->toBeNull()
        ->and($missing->isReady())->toBeFalse()
        ->and(executionReasonCodes($missing->blockingReasons))->toContain(ReasonCode::ReplenishmentNotCalculated->value);
});

it('detects stale replenishment without recalculating quantity', function (array $sourceChanges, array $itemChanges = []): void {
    ['requisition' => $requisition, 'source' => $source, 'item' => $item] = executionReadyRequisition();
    $source->update($sourceChanges);
    if ($itemChanges !== []) {
        $item->update($itemChanges);
    }
    $quantityBefore = $item->fresh()->quantity;

    $result = evaluateExecutionReadiness($requisition);

    expect($result->isReady())->toBeFalse()
        ->and(executionReasonCodes($result->blockingReasons))->toContain(ReasonCode::ReplenishmentStale->value)
        ->and($item->fresh()->quantity)->toBe($quantityBefore);
})->with([
    'MOQ changed from 10 to 12' => [['minimum_order_quantity' => '12.000']],
    'multiple introduced' => [['order_multiple' => '4.000']],
]);

it('detects a changed selected procurement source as stale', function (): void {
    ['requisition' => $requisition, 'source' => $source] = executionReadyRequisition();
    $replacementSupplier = Supplier::factory()->create(['is_active' => true]);
    $replacement = ItemSupplier::factory()->approved()->create([
        'supplier_id' => $replacementSupplier->id,
        'item_id' => $source->item_id,
        'is_active' => true,
        'valid_from' => null,
        'valid_until' => null,
        'purchase_unit' => $source->purchase_unit,
        'conversion_factor' => $source->conversion_factor,
        'minimum_order_quantity' => $source->minimum_order_quantity,
        'order_multiple' => $source->order_multiple,
    ]);
    $requisition->update(['supplier_id' => $replacementSupplier->id]);

    $result = evaluateExecutionReadiness($requisition);

    expect($replacement->id)->not->toBe($source->id)
        ->and($result->isReady())->toBeFalse()
        ->and(executionReasonCodes($result->blockingReasons))->toContain(ReasonCode::ReplenishmentStale->value);
});

it('blocks broken exact quantity invariants', function (array $changes): void {
    ['requisition' => $requisition, 'item' => $item] = executionReadyRequisition();
    $item->update($changes);

    $result = evaluateExecutionReadiness($requisition);

    expect($result->isReady())->toBeFalse()
        ->and(executionReasonCodes($result->blockingReasons))->toContain(ReasonCode::QuantityInvariantFailed->value);
})->with([
    'planned plus excess mismatch' => [['replenishment_excess_quantity' => '2.999']],
    'requested below planned' => [['quantity' => '6.999', 'replenishment_excess_quantity' => '0.000']],
    'zero requested' => [['planned_quantity' => '0.000', 'quantity' => '0.000', 'replenishment_excess_quantity' => '0.000']],
]);

it('validates proposal source total against planned quantity not requested quantity', function (): void {
    ['requisition' => $requisition, 'item' => $item] = executionReadyRequisition();

    expect(evaluateExecutionReadiness($requisition)->isReady())->toBeTrue();

    $item->proposalSources()->firstOrFail()->update(['quantity' => '3.999']);
    $mismatch = evaluateExecutionReadiness($requisition);

    expect($item->quantity)->toBe('10.000')
        ->and($mismatch->isReady())->toBeFalse()
        ->and(executionReasonCodes($mismatch->blockingReasons))->toContain(ReasonCode::ProposalLineageMismatch->value);
});

it('keeps lead time price and date risks as non-blocking warnings', function (): void {
    ['requisition' => $requisition, 'source' => $source] = executionReadyRequisition([
        'required_at' => '2026-08-25',
        'proposed_supply_at' => '2026-08-26',
    ]);
    $source->update(['lead_time_days' => 10, 'unit_price' => null, 'currency' => null]);

    $result = evaluateExecutionReadiness($requisition);

    expect($result->isReady())->toBeTrue()
        ->and(executionReasonCodes($result->warnings))->toBe([
            ReasonCode::ExpectedLateSupply->value,
            ReasonCode::PriceMissing->value,
        ]);
});

it('reports missing and past required dates as deterministic non-blocking warnings', function (mixed $requiredAt, ReasonCode $reason): void {
    ['requisition' => $requisition] = executionReadyRequisition(['required_at' => $requiredAt]);

    $result = evaluateExecutionReadiness($requisition);

    expect($result->isReady())->toBeTrue()
        ->and(executionReasonCodes($result->warnings))->toContain($reason->value);
})->with([
    'missing' => [null, ReasonCode::RequiredDateMissing],
    'past' => ['2026-08-23', ReasonCode::RequiredDatePassed],
]);

it('evaluates readiness without any persisted side effect', function (): void {
    ['requisition' => $requisition] = executionReadyRequisition();
    $tables = [
        'purchase_requisitions',
        'purchase_requisition_items',
        'purchase_requisition_item_proposal_sources',
        'supply_proposals',
        'item_suppliers',
        'purchase_orders',
        'stock_balances',
        'stock_movements',
        'stock_reservations',
        'activity_log',
    ];
    $snapshot = fn (string $table): array => DB::table($table)
        ->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all();
    $before = collect($tables)->mapWithKeys(fn (string $table): array => [$table => $snapshot($table)])->all();

    $result = evaluateExecutionReadiness($requisition);

    $after = collect($tables)->mapWithKeys(fn (string $table): array => [$table => $snapshot($table)])->all();
    expect($result->isReady())->toBeTrue()
        ->and($after)->toBe($before);
});

it('exposes the current readiness result on the authorized requisition detail page', function (): void {
    seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->create();
    $user->givePermissionTo('procurement.view');
    ['requisition' => $requisition] = executionReadyRequisition();

    actingAs($user);
    get(route('admin.purchase-requisitions.show', $requisition))
        ->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Admin/PurchaseRequisitions/Show')
            ->where('executionReadiness.purchase_requisition_id', $requisition->id)
            ->where('executionReadiness.is_ready', true)
            ->has('executionReadiness.blocking_reasons', 0)
            ->has('executionReadiness.item_results', 1));
});
