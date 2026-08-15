<?php

use App\Enums\PurchaseRequisitionStatus;
use App\Enums\SupplyProposalStatus;
use App\Models\Item;
use App\Models\ItemSupplier;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItemProposalSource;
use App\Models\Supplier;
use App\Models\SupplyProposal;
use App\Models\User;
use App\Services\Admin\PurchaseRequisitionConsolidationService;
use App\Services\Admin\PurchaseRequisitionService;
use App\Services\AuditLogService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mockery\CompositeExpectation;
use Mockery\MockInterface;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

function approvedPurchaseProposal(array $attributes = []): SupplyProposal
{
    $item = $attributes['item'] ?? Item::factory()->create(['unit' => 'kg']);
    $supplier = $attributes['supplier'] ?? null;

    if ($supplier instanceof Supplier && ! ItemSupplier::query()
        ->where('item_id', $item->id)
        ->where('supplier_id', $supplier->id)
        ->exists()) {
        ItemSupplier::factory()->approved()->create([
            'item_id' => $item->id,
            'supplier_id' => $supplier->id,
            'purchase_unit' => $item->unit,
            'is_active' => true,
            'valid_from' => now()->subDay()->toDateString(),
            'valid_until' => now()->addDay()->toDateString(),
        ]);
    }

    unset($attributes['item'], $attributes['supplier']);

    return SupplyProposal::factory()->approved()->create([
        'item_id' => $item->id,
        'supplier_id' => $supplier?->id,
        'unit' => $item->unit,
        'proposed_quantity' => '1.000',
        'required_at' => '2026-08-20',
        'proposed_supply_at' => '2026-08-18',
        ...$attributes,
    ]);
}

it('creates the explicit proposal consolidation schema', function (): void {
    expect(Schema::hasColumns('purchase_requisitions', [
        'supplier_id', 'required_at', 'proposed_supply_at',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('purchase_requisition_item_proposal_sources', [
            'purchase_requisition_item_id', 'supply_proposal_id', 'quantity',
        ]))->toBeTrue();
});

it('creates one draft requisition item and source from one approved purchase proposal', function (): void {
    $proposal = approvedPurchaseProposal(['proposed_quantity' => '6.000']);

    $result = app(PurchaseRequisitionConsolidationService::class)->consolidate([$proposal->id]);
    $requisition = PurchaseRequisition::query()->with('items.proposalSources')->sole();

    expect($result->createdRequisitionIds)->toBe([$requisition->id])
        ->and($result->proposalCount)->toBe(1)
        ->and($result->itemCount)->toBe(1)
        ->and($result->sourceCount)->toBe(1)
        ->and($requisition->status)->toBe(PurchaseRequisitionStatus::Draft)
        ->and($requisition->requisition_number)->toBe('PR-0001')
        ->and($requisition->items->sole()->quantity)->toBe('6.000')
        ->and($requisition->items->sole()->proposalSources->sole()->quantity)->toBe('6.000');
});

it('excludes every non-approved proposal status', function (SupplyProposalStatus $status): void {
    $proposal = approvedPurchaseProposal(['status' => $status]);

    expect(fn () => app(PurchaseRequisitionConsolidationService::class)->consolidate([$proposal->id]))
        ->toThrow(ValidationException::class);

    assertDatabaseCount('purchase_requisitions', 0);
})->with([
    SupplyProposalStatus::Draft,
    SupplyProposalStatus::Proposed,
    SupplyProposalStatus::Rejected,
    SupplyProposalStatus::Cancelled,
]);

it('consolidates the kémcső quantities exactly and preserves both proposal sources', function (): void {
    $item = Item::factory()->create(['name' => 'Kémcső alapanyag', 'unit' => 'kg']);
    $first = approvedPurchaseProposal(['item' => $item, 'proposed_quantity' => '3.333']);
    $second = approvedPurchaseProposal(['item' => $item, 'proposed_quantity' => '6.667']);

    app(PurchaseRequisitionConsolidationService::class)->consolidate([$second->id, $first->id]);
    $itemRow = PurchaseRequisition::query()->sole()->items()->with('proposalSources')->sole();

    expect($itemRow->quantity)->toBe('10.000')
        ->and($itemRow->proposalSources)->toHaveCount(2)
        ->and($itemRow->proposalSources->sum(fn ($source): float => (float) $source->quantity))->toBe(10.0);
});

it('puts different items into separate lines of the same requisition', function (): void {
    $supplier = Supplier::factory()->create();
    $first = approvedPurchaseProposal([
        'item' => Item::factory()->create(['unit' => 'kg']),
        'supplier' => $supplier,
        'proposed_quantity' => '5.000',
    ]);
    $second = approvedPurchaseProposal([
        'item' => Item::factory()->create(['unit' => 'db']),
        'supplier' => $supplier,
        'proposed_quantity' => '100.000',
    ]);

    app(PurchaseRequisitionConsolidationService::class)->consolidate([$first->id, $second->id]);

    expect(PurchaseRequisition::query()->count())->toBe(1)
        ->and(PurchaseRequisition::query()->sole()->items()->count())->toBe(2);
});

it('separates supplier and date groups while allowing a supplierless group', function (): void {
    $item = Item::factory()->create(['unit' => 'kg']);
    $supplierA = Supplier::factory()->create();
    $supplierB = Supplier::factory()->create();
    $proposals = collect([
        approvedPurchaseProposal(['item' => $item, 'supplier' => $supplierA]),
        approvedPurchaseProposal(['item' => $item, 'supplier' => $supplierB]),
        approvedPurchaseProposal(['item' => $item]),
        approvedPurchaseProposal(['item' => $item, 'required_at' => '2026-08-21']),
    ]);

    app(PurchaseRequisitionConsolidationService::class)->consolidate($proposals->pluck('id')->all());

    expect(PurchaseRequisition::query()->count())->toBe(4)
        ->and(PurchaseRequisition::query()->whereNull('supplier_id')->count())->toBe(2)
        ->and(PurchaseRequisition::query()->where('supplier_id', $supplierA->id)->count())->toBe(1)
        ->and(PurchaseRequisition::query()->where('supplier_id', $supplierB->id)->count())->toBe(1);
});

it('is idempotent by rejecting a second full consumption without duplicates', function (): void {
    $proposal = approvedPurchaseProposal();
    $service = app(PurchaseRequisitionConsolidationService::class);
    $service->consolidate([$proposal->id]);

    expect(fn () => $service->consolidate([$proposal->id]))
        ->toThrow(ValidationException::class);

    assertDatabaseCount('purchase_requisitions', 1);
    assertDatabaseCount('purchase_requisition_item_proposal_sources', 1);
});

it('uses a unique proposal source as the final duplicate consumption guard', function (): void {
    $proposal = approvedPurchaseProposal();
    app(PurchaseRequisitionConsolidationService::class)->consolidate([$proposal->id]);
    $source = PurchaseRequisitionItemProposalSource::query()->sole();

    expect(fn () => PurchaseRequisitionItemProposalSource::query()->create([
        'purchase_requisition_item_id' => $source->purchase_requisition_item_id,
        'supply_proposal_id' => $proposal->id,
        'quantity' => $proposal->proposed_quantity,
    ]))->toThrow(QueryException::class);
});

it('revalidates item unit and supplier source inside consolidation', function (): void {
    $supplier = Supplier::factory()->create();
    $proposal = approvedPurchaseProposal(['supplier' => $supplier]);
    $proposal->item->update(['unit' => 'db']);

    expect(fn () => app(PurchaseRequisitionConsolidationService::class)->consolidate([$proposal->id]))
        ->toThrow(ValidationException::class);

    assertDatabaseCount('purchase_requisitions', 0);
});

it('rolls back every created document and source when the batch audit fails', function (): void {
    $proposal = approvedPurchaseProposal();
    $audit = Mockery::mock(AuditLogService::class, function (MockInterface $mock): void {
        $expectation = $mock->shouldReceive('log');

        if (! $expectation instanceof CompositeExpectation) {
            throw new LogicException('Mockery did not create a concrete method expectation.');
        }

        $expectation->__call('once', []);
        $expectation->__call('andThrow', [new RuntimeException('audit failed')]);
    });
    app()->instance(AuditLogService::class, $audit);

    expect(fn () => app(PurchaseRequisitionConsolidationService::class)->consolidate([$proposal->id]))
        ->toThrow(RuntimeException::class, 'audit failed');

    assertDatabaseCount('purchase_requisitions', 0);
    assertDatabaseCount('purchase_requisition_items', 0);
    assertDatabaseCount('purchase_requisition_item_proposal_sources', 0);
});

it('protects the consolidation endpoint and reports the created requisition', function (): void {
    seed(RolesAndPermissionsSeeder::class);
    $proposal = approvedPurchaseProposal();
    $unauthorized = User::factory()->create();
    $authorized = User::factory()->create();
    $authorized->givePermissionTo('procurement.create');

    actingAs($unauthorized)
        ->post(route('admin.purchase-requisitions.consolidate'), ['proposal_ids' => [$proposal->id]])
        ->assertForbidden();

    actingAs($authorized)
        ->post(route('admin.purchase-requisitions.consolidate'), ['proposal_ids' => [$proposal->id]])
        ->assertRedirect(route('admin.purchase-requisitions.show', PurchaseRequisition::query()->sole()));

    assertDatabaseHas('activity_log', [
        'event' => 'purchase_requisition_consolidation_completed',
        'description' => 'purchase_requisition_consolidation_completed',
    ]);
});

it('prevents cancelling an approved proposal after downstream consolidation', function (): void {
    seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->create();
    $user->givePermissionTo('supply-proposals.delete');
    $proposal = approvedPurchaseProposal();
    app(PurchaseRequisitionConsolidationService::class)->consolidate([$proposal->id]);

    actingAs($user)
        ->patch(route('admin.supply-proposals.cancel', $proposal))
        ->assertSessionHasErrors('status');

    expect($proposal->refresh()->status)->toBe(SupplyProposalStatus::Approved);
});

it('preserves the consolidated supplier boundary during purchase order generation', function (): void {
    $consolidatedSupplier = Supplier::factory()->create();
    $differentSupplier = Supplier::factory()->create();
    $proposal = approvedPurchaseProposal(['supplier' => $consolidatedSupplier]);

    app(PurchaseRequisitionConsolidationService::class)->consolidate([$proposal->id]);
    $requisition = PurchaseRequisition::query()->sole();
    $requisition->update(['status' => PurchaseRequisitionStatus::Approved]);

    expect(fn () => app(PurchaseRequisitionService::class)->generatePurchaseOrder(
        $requisition,
        $differentSupplier->id,
    ))->toThrow(ValidationException::class);

    assertDatabaseCount('purchase_orders', 0);
});
