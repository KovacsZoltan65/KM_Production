<?php

use App\Enums\ItemType;
use App\Models\Item;
use App\Models\User;
use App\Services\Admin\ItemAdminService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

function itemSerialNumberAdmin(): User
{
    seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('super-admin');

    return $user;
}

it('preserves a disabled serial number requirement for a manufactured part during update', function (): void {
    $item = Item::factory()->manufacturedPart()->create([
        'requires_serial_number' => true,
        'is_active' => true,
    ]);

    /** @var Item $updatedItem */
    $updatedItem = app(ItemAdminService::class)->update($item, [
        'item_type' => ItemType::ManufacturedPart,
        'requires_serial_number' => false,
        'is_active' => false,
    ]);

    expect($updatedItem->requires_serial_number)->toBeFalse()
        ->and($updatedItem->is_active)->toBeFalse()
        ->and($item->refresh()->requires_serial_number)->toBeFalse()
        ->and($item->is_active)->toBeFalse();

    assertDatabaseHas('items', [
        'id' => $item->id,
        'requires_serial_number' => false,
        'is_active' => false,
    ]);

    $activity = Activity::query()
        ->where('event', 'admin_item_updated')
        ->where('subject_type', Item::class)
        ->where('subject_id', $item->id)
        ->firstOrFail();

    expect($activity->attribute_changes->get('old'))->toMatchArray([
        'requires_serial_number' => true,
    ])->and($activity->attribute_changes->get('attributes'))->toMatchArray([
        'requires_serial_number' => false,
    ]);
});

it('preserves an enabled serial number requirement for a purchased material during update', function (): void {
    $item = Item::factory()->purchasedMaterial()->create([
        'requires_serial_number' => false,
    ]);

    /** @var Item $updatedItem */
    $updatedItem = app(ItemAdminService::class)->update($item, [
        'item_type' => ItemType::PurchasedMaterial->value,
        'requires_serial_number' => true,
    ]);

    expect($updatedItem->requires_serial_number)->toBeTrue()
        ->and($item->refresh()->requires_serial_number)->toBeTrue();

    assertDatabaseHas('items', [
        'id' => $item->id,
        'requires_serial_number' => true,
    ]);
});

it('preserves a disabled serial number requirement when creating a manufactured part', function (): void {
    /** @var Item $item */
    $item = app(ItemAdminService::class)->create([
        'item_number' => 'PRD-SERIAL-REGRESSION',
        'name' => 'Serial number regression item',
        'item_type' => ItemType::ManufacturedPart->value,
        'unit' => 'pcs',
        'requires_serial_number' => false,
        'is_active' => true,
    ]);

    expect($item->refresh()->requires_serial_number)->toBeFalse();

    assertDatabaseHas('items', [
        'id' => $item->id,
        'item_type' => ItemType::ManufacturedPart->value,
        'requires_serial_number' => false,
    ]);
});

it('accepts boolean serial number requirements throughout the admin update request', function (
    ItemType $itemType,
    bool $originalValue,
    bool $newValue,
): void {
    $admin = itemSerialNumberAdmin();
    $item = Item::factory()->create([
        'item_type' => $itemType,
        'requires_serial_number' => $originalValue,
    ]);

    actingAs($admin)
        ->put(route('admin.items.update', $item), [
            'name' => $item->name,
            'item_type' => $itemType->value,
            'unit' => $item->unit,
            'requires_serial_number' => $newValue,
            'is_active' => true,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', __('messages.updated'));

    expect($item->refresh()->requires_serial_number)->toBe($newValue);

    assertDatabaseHas('items', [
        'id' => $item->id,
        'requires_serial_number' => $newValue,
    ]);
})->with([
    'manufactured part true to false' => [ItemType::ManufacturedPart, true, false],
    'purchased material false to true' => [ItemType::PurchasedMaterial, false, true],
]);
