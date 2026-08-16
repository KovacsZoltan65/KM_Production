<?php

use App\Enums\PurchaseRequisitionStatus;
use App\Models\Item;
use App\Models\ItemSupplier;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Admin\SupplierSelectionService;
use App\Services\AuditLogService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\CompositeExpectation;
use Mockery\MockInterface;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

/** @param list<Item> $items */
function supplierSelectionRequisition(array $items, array $attributes = []): PurchaseRequisition
{
    $requisition = PurchaseRequisition::factory()->create([
        'status' => PurchaseRequisitionStatus::Draft,
        'supplier_id' => null,
        ...$attributes,
    ]);

    foreach ($items as $item) {
        PurchaseRequisitionItem::factory()->create([
            'purchase_requisition_id' => $requisition->id,
            'item_id' => $item->id,
            'quantity' => '7.000',
            'unit' => $item->unit,
        ]);
    }

    return $requisition;
}

function supplierSelectionSource(Item $item, Supplier $supplier, array $attributes = []): ItemSupplier
{
    return ItemSupplier::factory()->approved()->create([
        'item_id' => $item->id,
        'supplier_id' => $supplier->id,
        'purchase_unit' => $item->unit,
        'is_active' => true,
        'valid_from' => null,
        'valid_until' => null,
        ...$attributes,
    ]);
}

function supplierSelectionDate(): Carbon
{
    return Carbon::parse('2026-08-15');
}

it('returns only active approved currently valid sources with active master data', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['is_active' => true]);
    $requisition = supplierSelectionRequisition([$item]);
    $eligibleSupplier = Supplier::factory()->create(['name' => 'Eligible', 'is_active' => true]);
    supplierSelectionSource($item, $eligibleSupplier);

    supplierSelectionSource($item, Supplier::factory()->create(), ['is_active' => false]);
    supplierSelectionSource($item, Supplier::factory()->create(), ['is_approved' => false]);
    supplierSelectionSource($item, Supplier::factory()->create(), ['valid_until' => '2026-08-14']);
    supplierSelectionSource($item, Supplier::factory()->create(), ['valid_from' => '2026-08-16']);
    supplierSelectionSource($item, Supplier::factory()->create(['is_active' => false]));

    $candidates = app(SupplierSelectionService::class)
        ->candidatesForPurchaseRequisition($requisition, supplierSelectionDate());

    expect($candidates)->toHaveCount(1)
        ->and($candidates->sole()->supplierId)->toBe($eligibleSupplier->id);
});

it('uses supplier name ordering only and candidate lookup never mutates the requisition', function (): void {
    $item = Item::factory()->purchasedMaterial()->create();
    $requisition = supplierSelectionRequisition([$item]);
    $zeta = Supplier::factory()->create(['name' => 'Zeta Supplier']);
    $alpha = Supplier::factory()->create(['name' => 'Alpha Supplier']);
    supplierSelectionSource($item, $zeta, ['is_preferred' => true, 'priority' => 1]);
    supplierSelectionSource($item, $alpha, ['is_preferred' => false, 'priority' => 99]);

    $candidates = app(SupplierSelectionService::class)
        ->candidatesForPurchaseRequisition($requisition, supplierSelectionDate());

    expect($candidates->pluck('supplierId')->all())->toBe([$alpha->id, $zeta->id])
        ->and($candidates->last()->sources[0]['preferred'])->toBeTrue()
        ->and($candidates->last()->sources[0]['priority'])->toBe(1)
        ->and($requisition->refresh()->supplier_id)->toBeNull();

    assertDatabaseCount('activity_log', 0);
});

it('returns only the common supplier intersection for a multi-item requisition', function (): void {
    $itemA = Item::factory()->purchasedMaterial()->create();
    $itemB = Item::factory()->purchasedMaterial()->create();
    $requisition = supplierSelectionRequisition([$itemA, $itemB]);
    $supplierX = Supplier::factory()->create(['name' => 'Supplier X']);
    $supplierY = Supplier::factory()->create(['name' => 'Supplier Y']);
    $supplierZ = Supplier::factory()->create(['name' => 'Supplier Z']);

    supplierSelectionSource($itemA, $supplierX);
    supplierSelectionSource($itemA, $supplierY);
    supplierSelectionSource($itemB, $supplierX);
    supplierSelectionSource($itemB, $supplierZ);

    $candidates = app(SupplierSelectionService::class)
        ->candidatesForPurchaseRequisition($requisition, supplierSelectionDate());

    expect($candidates)->toHaveCount(1)
        ->and($candidates->sole()->supplierId)->toBe($supplierX->id)
        ->and($candidates->sole()->sources)->toHaveCount(2);
});

it('blocks a multi-item requisition without a common supplier', function (): void {
    $itemA = Item::factory()->purchasedMaterial()->create();
    $itemB = Item::factory()->purchasedMaterial()->create();
    $requisition = supplierSelectionRequisition([$itemA, $itemB]);
    $supplierX = Supplier::factory()->create();
    $supplierY = Supplier::factory()->create();
    supplierSelectionSource($itemA, $supplierX);
    supplierSelectionSource($itemB, $supplierY);

    $service = app(SupplierSelectionService::class);

    expect($service->candidatesForPurchaseRequisition($requisition, supplierSelectionDate()))->toBeEmpty()
        ->and(fn () => $service->selectSupplier($requisition, $supplierX->id, null, supplierSelectionDate()))
        ->toThrow(ValidationException::class)
        ->and($requisition->refresh()->supplier_id)->toBeNull();
});

it('selects an eligible supplier through the protected endpoint without changing quantity or execution state', function (): void {
    seed(RolesAndPermissionsSeeder::class);
    $item = Item::factory()->purchasedMaterial()->create();
    $requisition = supplierSelectionRequisition([$item]);
    $supplier = Supplier::factory()->create();
    supplierSelectionSource($item, $supplier, [
        'minimum_order_quantity' => '10.000',
        'order_multiple' => '5.000',
        'conversion_factor' => '25.000000',
        'purchase_unit' => 'bag',
    ]);
    $unauthorized = User::factory()->create();
    $authorized = User::factory()->create();
    $authorized->givePermissionTo('procurement.update');

    actingAs($unauthorized)
        ->patch(route('admin.purchase-requisitions.select-supplier', $requisition), ['supplier_id' => $supplier->id])
        ->assertForbidden();

    actingAs($authorized)
        ->patch(route('admin.purchase-requisitions.select-supplier', $requisition), ['supplier_id' => $supplier->id])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($requisition->refresh()->supplier_id)->toBe($supplier->id)
        ->and($requisition->status)->toBe(PurchaseRequisitionStatus::Draft)
        ->and($requisition->items()->sole()->quantity)->toBe('7.000');

    assertDatabaseHas('activity_log', [
        'event' => 'supplier_selected',
        'subject_type' => PurchaseRequisition::class,
        'subject_id' => $requisition->id,
        'causer_id' => $authorized->id,
    ]);
    $activity = Activity::query()->where('event', 'supplier_selected')->sole();
    expect($activity->properties->get('purchase_requisition_id'))->toBe($requisition->id)
        ->and($activity->properties->get('supplier_id'))->toBe($supplier->id)
        ->and($activity->properties->get('selection_mode'))->toBe('manual')
        ->and($activity->properties->get('candidate_count'))->toBe(1);
    assertDatabaseCount('purchase_orders', 0);
    assertDatabaseCount('goods_receipts', 0);
    assertDatabaseCount('stock_balances', 0);
    assertDatabaseCount('stock_movements', 0);
    assertDatabaseCount('stock_reservations', 0);
});

it('serializes common candidates on the requisition page without granting update implicitly', function (): void {
    seed(RolesAndPermissionsSeeder::class);
    $item = Item::factory()->purchasedMaterial()->create();
    $requisition = supplierSelectionRequisition([$item]);
    $supplier = Supplier::factory()->create(['code' => 'SUP-X', 'name' => 'Supplier X']);
    supplierSelectionSource($item, $supplier, ['is_preferred' => true, 'priority' => 1]);
    $viewer = User::factory()->create();
    $viewer->givePermissionTo('procurement.view');

    actingAs($viewer)
        ->get(route('admin.purchase-requisitions.show', $requisition))
        ->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Admin/PurchaseRequisitions/Show')
            ->where('canSelectSupplier', false)
            ->where('supplierCandidates.0.supplier_id', $supplier->id)
            ->where('supplierCandidates.0.sources.0.preferred', true)
            ->where('supplierCandidates.0.sources.0.priority', 1));

    expect($requisition->refresh()->supplier_id)->toBeNull();
});

it('revalidates the source at commit and rejects an invalidated candidate', function (): void {
    $item = Item::factory()->purchasedMaterial()->create();
    $requisition = supplierSelectionRequisition([$item]);
    $supplier = Supplier::factory()->create();
    $source = supplierSelectionSource($item, $supplier);
    $service = app(SupplierSelectionService::class);

    expect($service->candidatesForPurchaseRequisition($requisition, supplierSelectionDate()))->toHaveCount(1);
    $source->update(['is_active' => false]);

    expect(fn () => $service->selectSupplier($requisition, $supplier->id, null, supplierSelectionDate()))
        ->toThrow(ValidationException::class)
        ->and($requisition->refresh()->supplier_id)->toBeNull();
});

it('blocks selection when a requisition item is inactive', function (): void {
    $item = Item::factory()->purchasedMaterial()->create();
    $requisition = supplierSelectionRequisition([$item]);
    $supplier = Supplier::factory()->create();
    supplierSelectionSource($item, $supplier);
    $item->update(['is_active' => false]);

    expect(fn () => app(SupplierSelectionService::class)
        ->selectSupplier($requisition, $supplier->id, null, supplierSelectionDate()))
        ->toThrow(ValidationException::class)
        ->and($requisition->refresh()->supplier_id)->toBeNull();
});

it('allows selection only while the requisition is draft', function (PurchaseRequisitionStatus $status): void {
    $item = Item::factory()->purchasedMaterial()->create();
    $requisition = supplierSelectionRequisition([$item], ['status' => $status]);
    $supplier = Supplier::factory()->create();
    supplierSelectionSource($item, $supplier);

    expect(fn () => app(SupplierSelectionService::class)
        ->selectSupplier($requisition, $supplier->id, null, supplierSelectionDate()))
        ->toThrow(ValidationException::class)
        ->and($requisition->refresh()->supplier_id)->toBeNull();
})->with([
    PurchaseRequisitionStatus::Requested,
    PurchaseRequisitionStatus::Approved,
    PurchaseRequisitionStatus::Ordered,
    PurchaseRequisitionStatus::Cancelled,
]);

it('is idempotent for the same supplier and blocks a conflicting replacement', function (): void {
    $item = Item::factory()->purchasedMaterial()->create();
    $requisition = supplierSelectionRequisition([$item]);
    $supplierX = Supplier::factory()->create();
    $supplierY = Supplier::factory()->create();
    supplierSelectionSource($item, $supplierX);
    supplierSelectionSource($item, $supplierY);
    $service = app(SupplierSelectionService::class);

    $service->selectSupplier($requisition, $supplierX->id, null, supplierSelectionDate());
    $service->selectSupplier($requisition, $supplierX->id, null, supplierSelectionDate());

    expect(Activity::query()->where('event', 'supplier_selected')->count())->toBe(1)
        ->and(fn () => $service->selectSupplier($requisition, $supplierY->id, null, supplierSelectionDate()))
        ->toThrow(ValidationException::class)
        ->and($requisition->refresh()->supplier_id)->toBe($supplierX->id);
});

it('rolls back supplier persistence when the business audit fails', function (): void {
    $item = Item::factory()->purchasedMaterial()->create();
    $requisition = supplierSelectionRequisition([$item]);
    $supplier = Supplier::factory()->create();
    supplierSelectionSource($item, $supplier);
    $audit = Mockery::mock(AuditLogService::class, function (MockInterface $mock): void {
        $expectation = $mock->shouldReceive('log');

        if (! $expectation instanceof CompositeExpectation) {
            throw new LogicException('Mockery did not create a concrete method expectation.');
        }

        $expectation->__call('once', []);
        $expectation->__call('andThrow', [new RuntimeException('audit failed')]);
    });
    app()->instance(AuditLogService::class, $audit);

    expect(fn () => app(SupplierSelectionService::class)
        ->selectSupplier($requisition, $supplier->id, null, supplierSelectionDate()))
        ->toThrow(RuntimeException::class, 'audit failed')
        ->and($requisition->refresh()->supplier_id)->toBeNull();
});
