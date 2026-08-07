<?php

use App\Models\Item;
use App\Models\ItemSupplier;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Admin\ItemSupplierService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    seed(RolesAndPermissionsSeeder::class);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function itemSupplierPayload(Item $item, Supplier $supplier, array $overrides = []): array
{
    return array_merge([
        'item_id' => $item->id,
        'supplier_id' => $supplier->id,
        'supplier_item_code' => 'LP-RAW-1170',
        'purchase_unit' => 'bag',
        'conversion_factor' => 25,
        'minimum_order_quantity' => 100,
        'order_multiple' => 25,
        'unit_price' => 1250,
        'currency' => 'huf',
        'lead_time_days' => 5,
        'priority' => 1,
        'is_preferred' => true,
        'is_approved' => true,
        'is_active' => true,
        'valid_from' => '2026-08-01',
        'valid_until' => '2027-08-01',
    ], $overrides);
}

function procurementUser(array $permissions = []): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);

    if ($permissions !== []) {
        $user->givePermissionTo($permissions);
    }

    return $user;
}

it('creates the item_suppliers table with procurement source constraints', function (): void {
    expect(Schema::hasColumns('item_suppliers', [
        'item_id',
        'supplier_id',
        'supplier_item_code',
        'purchase_unit',
        'conversion_factor',
        'minimum_order_quantity',
        'order_multiple',
        'unit_price',
        'currency',
        'lead_time_days',
        'priority',
        'is_preferred',
        'is_approved',
        'is_active',
        'valid_from',
        'valid_until',
    ]))->toBeTrue();
});

it('exposes item and supplier relationships in both directions', function (): void {
    $source = ItemSupplier::factory()->create();

    expect($source->item)->toBeInstanceOf(Item::class)
        ->and($source->supplier)->toBeInstanceOf(Supplier::class)
        ->and($source->item->itemSuppliers->contains($source))->toBeTrue()
        ->and($source->supplier->itemSuppliers->contains($source))->toBeTrue();
});

it('creates an auditable procurement source with normalized currency', function (): void {
    $user = procurementUser(['item-suppliers.create']);
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    $supplier = Supplier::factory()->create(['name' => 'LaborPlast Kft.']);

    actingAs($user)
        ->post(route('admin.item-suppliers.store'), itemSupplierPayload($item, $supplier))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $source = ItemSupplier::query()->sole();

    expect($source->currency)->toBe('HUF')
        ->and($source->conversion_factor)->toBe('25.000000')
        ->and($source->minimum_order_quantity)->toBe('100.000')
        ->and($source->is_preferred)->toBeTrue()
        ->and($source->is_approved)->toBeTrue()
        ->and(Activity::query()->where('event', 'item_supplier_created')->exists())->toBeTrue();
});

it('rejects a nonexistent item', function (): void {
    $user = procurementUser(['item-suppliers.create']);
    $supplier = Supplier::factory()->create();

    actingAs($user)
        ->post(route('admin.item-suppliers.store'), itemSupplierPayload(
            Item::factory()->make(['id' => 999999]),
            $supplier,
        ))
        ->assertSessionHasErrors('item_id');
});

it('rejects a nonexistent supplier', function (): void {
    $user = procurementUser(['item-suppliers.create']);
    $item = Item::factory()->create();

    actingAs($user)
        ->post(route('admin.item-suppliers.store'), itemSupplierPayload(
            $item,
            Supplier::factory()->make(['id' => 999999]),
        ))
        ->assertSessionHasErrors('supplier_id');
});

it('rejects duplicate item and supplier pairs', function (): void {
    $user = procurementUser(['item-suppliers.create']);
    $source = ItemSupplier::factory()->create();

    actingAs($user)
        ->post(route('admin.item-suppliers.store'), itemSupplierPayload($source->item, $source->supplier))
        ->assertSessionHasErrors('supplier_id');
});

it('rejects invalid procurement condition values', function (array $overrides, string $field): void {
    $user = procurementUser(['item-suppliers.create']);
    $item = Item::factory()->create();
    $supplier = Supplier::factory()->create();

    actingAs($user)
        ->post(route('admin.item-suppliers.store'), itemSupplierPayload($item, $supplier, $overrides))
        ->assertSessionHasErrors($field);
})->with([
    'zero conversion factor' => [['conversion_factor' => 0], 'conversion_factor'],
    'negative MOQ' => [['minimum_order_quantity' => -1], 'minimum_order_quantity'],
    'zero order multiple' => [['order_multiple' => 0], 'order_multiple'],
    'negative lead time' => [['lead_time_days' => -1], 'lead_time_days'],
    'negative unit price' => [['unit_price' => -1], 'unit_price'],
    'invalid validity range' => [['valid_from' => '2026-09-01', 'valid_until' => '2026-08-01'], 'valid_until'],
]);

it('switches the active preferred source transactionally for one item only', function (): void {
    $user = procurementUser(['item-suppliers.create']);
    $item = Item::factory()->create();
    $otherItem = Item::factory()->create();
    $first = ItemSupplier::factory()->preferred()->create(['item_id' => $item->id]);
    $otherItemPreferred = ItemSupplier::factory()->preferred()->create(['item_id' => $otherItem->id]);
    $secondSupplier = Supplier::factory()->create();

    actingAs($user)
        ->post(route('admin.item-suppliers.store'), itemSupplierPayload($item, $secondSupplier))
        ->assertSessionHasNoErrors();

    expect($first->refresh()->is_preferred)->toBeFalse()
        ->and($otherItemPreferred->refresh()->is_preferred)->toBeTrue()
        ->and(ItemSupplier::query()
            ->where('item_id', $item->id)
            ->active()
            ->preferred()
            ->count())->toBe(1);
});

it('updates a source without failing uniqueness against itself', function (): void {
    $user = procurementUser(['item-suppliers.update']);
    $source = ItemSupplier::factory()->create(['supplier_item_code' => 'OLD']);

    actingAs($user)
        ->put(route('admin.item-suppliers.update', $source), itemSupplierPayload(
            $source->item,
            $source->supplier,
            ['supplier_item_code' => 'NEW', 'priority' => 2],
        ))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($source->refresh()->supplier_item_code)->toBe('NEW')
        ->and($source->priority)->toBe(2)
        ->and(Activity::query()->where('event', 'item_supplier_updated')->exists())->toBeTrue();
});

it('switches preferred source during update', function (): void {
    $user = procurementUser(['item-suppliers.update']);
    $item = Item::factory()->create();
    $preferred = ItemSupplier::factory()->preferred()->create(['item_id' => $item->id]);
    $replacement = ItemSupplier::factory()->create(['item_id' => $item->id]);

    actingAs($user)
        ->put(route('admin.item-suppliers.update', $replacement), itemSupplierPayload(
            $item,
            $replacement->supplier,
            ['is_preferred' => true],
        ))
        ->assertSessionHasNoErrors();

    expect($preferred->refresh()->is_preferred)->toBeFalse()
        ->and($replacement->refresh()->is_preferred)->toBeTrue();
});

it('inactivates instead of hard deleting and removes preferred state', function (): void {
    $user = procurementUser(['item-suppliers.delete']);
    $source = ItemSupplier::factory()->preferred()->approved()->create();

    actingAs($user)
        ->delete(route('admin.item-suppliers.destroy', $source))
        ->assertRedirect();

    expect($source->refresh()->is_active)->toBeFalse()
        ->and($source->is_preferred)->toBeFalse()
        ->and(ItemSupplier::query()->whereKey($source->id)->exists())->toBeTrue()
        ->and(Activity::query()->where('event', 'item_supplier_inactivated')->exists())->toBeTrue();
});

it('returns only active approved and currently valid sources for planning selectors', function (): void {
    $item = Item::factory()->create();
    $eligible = ItemSupplier::factory()->approved()->create(['item_id' => $item->id, 'priority' => 2]);
    ItemSupplier::factory()->approved()->inactive()->create(['item_id' => $item->id]);
    ItemSupplier::factory()->create(['item_id' => $item->id, 'is_approved' => false]);
    ItemSupplier::factory()->approved()->create([
        'item_id' => $item->id,
        'valid_from' => now()->addDay(),
    ]);

    $options = app(ItemSupplierService::class)->activeApprovedForItem($item->id);

    expect($options)->toHaveCount(1)
        ->and($options->first()->is($eligible))->toBeTrue();
});

it('allows a user with view permission to open the procurement source page', function (): void {
    $user = procurementUser(['item-suppliers.view']);
    ItemSupplier::factory()->create();

    actingAs($user)
        ->get(route('admin.item-suppliers.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('Admin/ItemSuppliers/Index')
            ->has('records.data', 1)
            ->has('itemOptions')
            ->has('supplierOptions'));
});

it('denies procurement source routes without their explicit permissions', function (): void {
    $user = procurementUser();
    $source = ItemSupplier::factory()->create();

    actingAs($user)->get(route('admin.item-suppliers.index'))->assertForbidden();
    actingAs($user)
        ->post(route('admin.item-suppliers.store'), itemSupplierPayload($source->item, Supplier::factory()->create()))
        ->assertForbidden();
    actingAs($user)
        ->put(route('admin.item-suppliers.update', $source), itemSupplierPayload($source->item, $source->supplier))
        ->assertForbidden();
    actingAs($user)->delete(route('admin.item-suppliers.destroy', $source))->assertForbidden();
});

it('assigns source management only to procurement managers and view access selectively', function (): void {
    $procurementManager = User::factory()->create();
    $procurementManager->assignRole('procurement-manager');
    $warehouseManager = User::factory()->create();
    $warehouseManager->assignRole('warehouse-manager');

    expect($procurementManager->can('item-suppliers.view'))->toBeTrue()
        ->and($procurementManager->can('item-suppliers.create'))->toBeTrue()
        ->and($procurementManager->can('item-suppliers.update'))->toBeTrue()
        ->and($procurementManager->can('item-suppliers.delete'))->toBeTrue()
        ->and($warehouseManager->can('item-suppliers.view'))->toBeTrue()
        ->and($warehouseManager->can('item-suppliers.create'))->toBeFalse();
});

it('shows active procurement relationships on item and supplier pages without commercial terms', function (): void {
    $user = procurementUser(['items.view', 'suppliers.view']);
    $source = ItemSupplier::factory()->create(['unit_price' => 1250, 'currency' => 'HUF']);

    actingAs($user)
        ->get(route('admin.items.index'))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->where('records.data.0.active_item_suppliers.0.supplier.name', $source->supplier->name)
            ->missing('records.data.0.active_item_suppliers.0.unit_price'));

    actingAs($user)
        ->get(route('admin.suppliers.index'))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->where('records.data.0.active_item_suppliers.0.item.name', $source->item->name)
            ->missing('records.data.0.active_item_suppliers.0.unit_price'));
});
