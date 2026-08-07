<?php

use App\Enums\SupplyProposalStatus;
use App\Enums\SupplyStrategy;
use App\Models\Item;
use App\Models\ItemSupplier;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\Supplier;
use App\Models\SupplyProposal;
use App\Models\User;
use App\Services\Admin\SupplyProposalService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

function supplyProposalUser(array $permissions): User
{
    seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->givePermissionTo($permissions);

    return $user;
}

function usableSupplySource(Item $item, ?Supplier $supplier = null, array $overrides = []): ItemSupplier
{
    return ItemSupplier::factory()->create([
        'item_id' => $item->id,
        'supplier_id' => ($supplier ?? Supplier::factory()->create())->id,
        'purchase_unit' => $item->unit,
        'is_active' => true,
        'is_approved' => true,
        'valid_from' => now()->subDay()->toDateString(),
        'valid_until' => now()->addDay()->toDateString(),
        ...$overrides,
    ]);
}

function proposalPayload(Item $item, ?Supplier $supplier = null): array
{
    return [
        'strategy' => SupplyStrategy::Purchase->value,
        'item_id' => $item->id,
        'supplier_id' => $supplier?->id,
        'proposed_quantity' => 100,
        'required_at' => '2026-08-09',
        'proposed_supply_at' => '2026-08-08',
        'reason_code' => 'material_shortage',
        'notes' => 'Manuális planning javaslat.',
    ];
}

it('creates the supply proposal schema with planning and attribution fields', function (): void {
    expect(Schema::hasColumns('supply_proposals', [
        'strategy', 'item_id', 'supplier_id', 'proposed_quantity', 'unit',
        'required_at', 'proposed_supply_at', 'status', 'reason_code', 'notes',
        'created_by', 'approved_by', 'approved_at', 'rejected_by', 'rejected_at',
        'cancelled_by', 'cancelled_at',
    ]))->toBeTrue();
});

it('casts enums and exposes item supplier and user relations', function (): void {
    $user = User::factory()->create();
    $item = Item::factory()->create();
    $supplier = Supplier::factory()->create();
    $proposal = SupplyProposal::factory()->create([
        'item_id' => $item->id,
        'supplier_id' => $supplier->id,
        'created_by' => $user->id,
    ]);

    expect($proposal->strategy)->toBe(SupplyStrategy::Purchase)
        ->and($proposal->status)->toBe(SupplyProposalStatus::Draft)
        ->and($proposal->item->is($item))->toBeTrue()
        ->and($proposal->supplier?->is($supplier))->toBeTrue()
        ->and($proposal->creator?->is($user))->toBeTrue()
        ->and($item->supplyProposals()->whereKey($proposal->id)->exists())->toBeTrue()
        ->and($supplier->supplyProposals()->whereKey($proposal->id)->exists())->toBeTrue();
});

it('creates the manual acceptance scenario as draft in item base unit', function (): void {
    $user = supplyProposalUser(['supply-proposals.create']);
    $item = Item::factory()->create(['name' => 'Kémcső alapanyag', 'unit' => 'kg']);
    $supplier = Supplier::factory()->create(['name' => 'LaborPlast Kft.']);
    usableSupplySource($item, $supplier);

    actingAs($user)->post('/admin/supply-proposals', proposalPayload($item, $supplier))->assertRedirect();

    assertDatabaseHas('supply_proposals', [
        'item_id' => $item->id,
        'supplier_id' => $supplier->id,
        'strategy' => 'purchase',
        'proposed_quantity' => 100,
        'unit' => 'kg',
        'status' => 'draft',
        'created_by' => $user->id,
        'reason_code' => 'material_shortage',
    ]);
    assertDatabaseHas('activity_log', [
        'event' => 'supply_proposal_created',
        'description' => 'supply_proposal_created',
    ]);
});

it('allows purchase proposal without selected supplier', function (): void {
    $user = supplyProposalUser(['supply-proposals.create']);
    $item = Item::factory()->create(['unit' => 'kg']);

    actingAs($user)->post('/admin/supply-proposals', proposalPayload($item))->assertRedirect();

    assertDatabaseHas('supply_proposals', ['item_id' => $item->id, 'supplier_id' => null]);
});

it('validates item quantity and supported strategy', function (array $changes, string $field): void {
    $user = supplyProposalUser(['supply-proposals.create']);
    $item = Item::factory()->create();

    actingAs($user)
        ->post('/admin/supply-proposals', [...proposalPayload($item), ...$changes])
        ->assertSessionHasErrors($field);
})->with([
    'missing item' => [['item_id' => null], 'item_id'],
    'nonexistent item' => [['item_id' => 999999], 'item_id'],
    'zero quantity' => [['proposed_quantity' => 0], 'proposed_quantity'],
    'negative quantity' => [['proposed_quantity' => -1], 'proposed_quantity'],
    'invalid strategy' => [['strategy' => 'transfer'], 'strategy'],
]);

it('rejects a nonexistent supplier', function (): void {
    $user = supplyProposalUser(['supply-proposals.create']);
    $item = Item::factory()->create();

    actingAs($user)
        ->post('/admin/supply-proposals', [...proposalPayload($item), 'supplier_id' => 999999])
        ->assertSessionHasErrors('supplier_id');
});

it('requires a usable procurement source for a selected supplier', function (array $sourceState): void {
    $user = supplyProposalUser(['supply-proposals.create']);
    $item = Item::factory()->create();
    $supplier = Supplier::factory()->create();
    usableSupplySource($item, $supplier, $sourceState);

    actingAs($user)
        ->post('/admin/supply-proposals', proposalPayload($item, $supplier))
        ->assertSessionHasErrors('supplier_id');
})->with([
    'inactive source' => [['is_active' => false]],
    'unapproved source' => [['is_approved' => false]],
    'expired source' => [['valid_until' => now()->subDay()->toDateString()]],
    'not yet valid source' => [['valid_from' => now()->addDay()->toDateString()]],
]);

it('allows a late proposal to represent planning risk', function (): void {
    $user = supplyProposalUser(['supply-proposals.create']);
    $item = Item::factory()->create();
    $payload = [...proposalPayload($item), 'proposed_supply_at' => '2026-08-10'];

    actingAs($user)->post('/admin/supply-proposals', $payload)->assertSessionHasNoErrors();
});

it('supports draft proposed approved lifecycle with attribution and no execution documents', function (): void {
    $user = supplyProposalUser([
        'supply-proposals.update', 'supply-proposals.approve',
    ]);
    $proposal = SupplyProposal::factory()->create();

    actingAs($user)->patch("/admin/supply-proposals/{$proposal->id}/propose")->assertRedirect();
    expect($proposal->refresh()->status)->toBe(SupplyProposalStatus::Proposed);

    actingAs($user)->patch("/admin/supply-proposals/{$proposal->id}/approve")->assertRedirect();
    $proposal->refresh();

    expect($proposal->status)->toBe(SupplyProposalStatus::Approved)
        ->and($proposal->approved_by)->toBe($user->id)
        ->and($proposal->approved_at)->not->toBeNull()
        ->and(PurchaseRequisition::query()->count())->toBe(0)
        ->and(PurchaseOrder::query()->count())->toBe(0);
    assertDatabaseHas('activity_log', ['description' => 'supply_proposal_proposed']);
    assertDatabaseHas('activity_log', ['description' => 'supply_proposal_approved']);
});

it('supports proposed rejected lifecycle with attribution', function (): void {
    $user = supplyProposalUser(['supply-proposals.approve']);
    $proposal = SupplyProposal::factory()->proposed()->create();

    actingAs($user)->patch("/admin/supply-proposals/{$proposal->id}/reject")->assertRedirect();

    expect($proposal->refresh()->status)->toBe(SupplyProposalStatus::Rejected)
        ->and($proposal->rejected_by)->toBe($user->id)
        ->and($proposal->rejected_at)->not->toBeNull();
});

it('supports cancellation from draft proposed and approved', function (SupplyProposalStatus $status): void {
    $user = supplyProposalUser(['supply-proposals.delete']);
    $proposal = SupplyProposal::factory()->create(['status' => $status]);

    actingAs($user)->patch("/admin/supply-proposals/{$proposal->id}/cancel")->assertRedirect();

    expect($proposal->refresh()->status)->toBe(SupplyProposalStatus::Cancelled)
        ->and($proposal->cancelled_by)->toBe($user->id)
        ->and($proposal->cancelled_at)->not->toBeNull();
})->with([
    SupplyProposalStatus::Draft,
    SupplyProposalStatus::Proposed,
    SupplyProposalStatus::Approved,
]);

it('rejects forbidden lifecycle transitions', function (SupplyProposalStatus $from, string $action): void {
    $user = supplyProposalUser([
        'supply-proposals.update', 'supply-proposals.approve', 'supply-proposals.delete',
    ]);
    $proposal = SupplyProposal::factory()->create(['status' => $from]);

    actingAs($user)
        ->patch("/admin/supply-proposals/{$proposal->id}/{$action}")
        ->assertSessionHasErrors('status');
})->with([
    'rejected cannot approve' => [SupplyProposalStatus::Rejected, 'approve'],
    'cancelled cannot propose' => [SupplyProposalStatus::Cancelled, 'propose'],
    'draft cannot approve' => [SupplyProposalStatus::Draft, 'approve'],
    'approved cannot reject' => [SupplyProposalStatus::Approved, 'reject'],
]);

it('only permits updates in draft status', function (SupplyProposalStatus $status): void {
    $user = supplyProposalUser(['supply-proposals.update']);
    $proposal = SupplyProposal::factory()->create(['status' => $status]);

    actingAs($user)
        ->put("/admin/supply-proposals/{$proposal->id}", proposalPayload($proposal->item))
        ->assertSessionHasErrors('status');
})->with([
    SupplyProposalStatus::Proposed,
    SupplyProposalStatus::Approved,
    SupplyProposalStatus::Rejected,
    SupplyProposalStatus::Cancelled,
]);

it('updates a draft and refreshes the item base unit snapshot', function (): void {
    $user = supplyProposalUser(['supply-proposals.update']);
    $proposal = SupplyProposal::factory()->create(['unit' => 'old']);
    $proposal->item->update(['unit' => 'kg']);

    actingAs($user)
        ->put("/admin/supply-proposals/{$proposal->id}", proposalPayload($proposal->item))
        ->assertRedirect();

    expect($proposal->refresh()->unit)->toBe('kg');
});

it('protects every lifecycle action with explicit permissions', function (): void {
    $user = supplyProposalUser([]);
    $proposal = SupplyProposal::factory()->create();

    actingAs($user)->get('/admin/supply-proposals')->assertForbidden();
    actingAs($user)->post('/admin/supply-proposals', proposalPayload($proposal->item))->assertForbidden();
    actingAs($user)->put("/admin/supply-proposals/{$proposal->id}", proposalPayload($proposal->item))->assertForbidden();
    actingAs($user)->patch("/admin/supply-proposals/{$proposal->id}/propose")->assertForbidden();
    actingAs($user)->patch("/admin/supply-proposals/{$proposal->id}/approve")->assertForbidden();
    actingAs($user)->patch("/admin/supply-proposals/{$proposal->id}/reject")->assertForbidden();
    actingAs($user)->patch("/admin/supply-proposals/{$proposal->id}/cancel")->assertForbidden();
});

it('assigns proposal preparation decision and read permissions to the intended roles', function (): void {
    seed(RolesAndPermissionsSeeder::class);

    $procurement = Role::findByName('procurement-manager');
    $production = Role::findByName('production-manager');
    $viewer = Role::findByName('viewer');

    expect($procurement->hasAllPermissions([
        'supply-proposals.view',
        'supply-proposals.create',
        'supply-proposals.update',
        'supply-proposals.approve',
        'supply-proposals.delete',
    ]))->toBeTrue()
        ->and($production->hasAllPermissions([
            'supply-proposals.view',
            'supply-proposals.create',
            'supply-proposals.update',
        ]))->toBeTrue()
        ->and($production->hasPermissionTo('supply-proposals.approve'))->toBeFalse()
        ->and($viewer->hasPermissionTo('supply-proposals.view'))->toBeTrue()
        ->and($viewer->hasPermissionTo('supply-proposals.create'))->toBeFalse();
});

it('provides only usable item-specific supplier selector options', function (): void {
    $item = Item::factory()->create();
    $otherItem = Item::factory()->create();
    $valid = usableSupplySource($item);
    $other = usableSupplySource($otherItem);
    $invalid = usableSupplySource($item, null, ['is_approved' => false]);

    $options = app(SupplyProposalService::class)->supplierOptionsByItem();

    $itemSupplierIds = collect($options[$item->id])->pluck('id')->all();

    expect($itemSupplierIds)->toBe([$valid->supplier_id]);
    expect($itemSupplierIds)->not->toContain($invalid->supplier_id);
    expect(collect($options[$otherItem->id])->pluck('id')->all())
        ->toBe([$other->supplier_id]);
});
