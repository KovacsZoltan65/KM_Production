<?php

namespace Tests\Feature;

use App\Enums\ItemType;
use App\Enums\OperationTypeCode;
use App\Models\Bom;
use App\Models\FactoryUnit;
use App\Models\Item;
use App\Models\OperationSequence;
use App\Models\OperationType;
use App\Models\ProfessionalRole;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ProductionMasterUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_items_index(): void
    {
        $this->get('/admin/items')->assertRedirect('/login');
    }

    public function test_super_admin_can_access_items_index(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/admin/items')
            ->assertOk();
    }

    public function test_user_without_permission_cannot_access_items_index(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->get('/admin/items')
            ->assertForbidden();
    }

    public function test_item_can_be_created(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->post('/admin/items', $this->itemPayload([
                'item_number' => 'ITEM-UI-001',
                'item_type' => ItemType::ManufacturedPart->value,
                'requires_serial_number' => false,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('items', [
            'item_number' => 'ITEM-UI-001',
            'requires_serial_number' => true,
        ]);
    }

    public function test_item_can_be_updated(): void
    {
        $admin = $this->superAdmin();
        $item = Item::factory()->purchasedMaterial()->create(['item_number' => 'ITEM-UI-002']);

        $this->actingAs($admin)
            ->put("/admin/items/{$item->id}", $this->itemPayload([
                'item_number' => 'ITEM-UI-002',
                'name' => 'Updated Item',
                'item_type' => ItemType::FinishedProduct->value,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'name' => 'Updated Item',
            'requires_serial_number' => true,
        ]);
    }

    public function test_item_can_be_deleted(): void
    {
        $admin = $this->superAdmin();
        $item = Item::factory()->create();

        $this->actingAs($admin)
            ->delete("/admin/items/{$item->id}")
            ->assertRedirect();

        $this->assertSoftDeleted('items', ['id' => $item->id]);
    }

    public function test_purchased_material_forces_serial_flag_false(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->post('/admin/items', $this->itemPayload([
                'item_number' => 'ITEM-UI-003',
                'item_type' => ItemType::PurchasedMaterial->value,
                'requires_serial_number' => true,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('items', [
            'item_number' => 'ITEM-UI-003',
            'requires_serial_number' => false,
        ]);
    }

    public function test_manufactured_and_finished_items_force_serial_flag_true(): void
    {
        $admin = $this->superAdmin();

        foreach ([ItemType::ManufacturedPart, ItemType::SemiFinishedProduct, ItemType::FinishedProduct] as $index => $type) {
            $this->actingAs($admin)
                ->post('/admin/items', $this->itemPayload([
                    'item_number' => 'ITEM-UI-SERIAL-'.$index,
                    'item_type' => $type->value,
                    'requires_serial_number' => false,
                ]))
                ->assertRedirect();

            $this->assertDatabaseHas('items', [
                'item_number' => 'ITEM-UI-SERIAL-'.$index,
                'requires_serial_number' => true,
            ]);
        }
    }

    public function test_bom_can_be_created_with_items(): void
    {
        $admin = $this->superAdmin();
        $parent = Item::factory()->finishedProduct()->create();
        $component = Item::factory()->purchasedMaterial()->create();

        $this->actingAs($admin)
            ->post('/admin/boms', $this->bomPayload($parent, [
                ['item_id' => $component->id, 'quantity' => 2.5, 'unit' => 'db', 'notes' => 'Cut to size'],
            ]))
            ->assertRedirect();

        $bom = Bom::query()->where('item_id', $parent->id)->firstOrFail();

        $this->assertDatabaseHas('bom_items', [
            'bom_id' => $bom->id,
            'item_id' => $component->id,
            'unit' => 'db',
        ]);
    }

    public function test_bom_can_be_updated(): void
    {
        $admin = $this->superAdmin();
        $parent = Item::factory()->finishedProduct()->create();
        $component = Item::factory()->purchasedMaterial()->create();
        $replacement = Item::factory()->purchasedMaterial()->create();
        $bom = Bom::factory()->create(['item_id' => $parent->id, 'version' => 1]);
        $bom->bomItems()->create(['item_id' => $component->id, 'quantity' => 1, 'unit' => 'db']);

        $this->actingAs($admin)
            ->put("/admin/boms/{$bom->id}", $this->bomPayload($parent, [
                ['item_id' => $replacement->id, 'quantity' => 4, 'unit' => 'm', 'notes' => null],
            ], ['name' => 'Updated BOM']))
            ->assertRedirect();

        $this->assertDatabaseHas('boms', ['id' => $bom->id, 'name' => 'Updated BOM']);
        $this->assertDatabaseMissing('bom_items', ['bom_id' => $bom->id, 'item_id' => $component->id]);
        $this->assertDatabaseHas('bom_items', ['bom_id' => $bom->id, 'item_id' => $replacement->id]);
    }

    public function test_duplicate_bom_item_is_rejected(): void
    {
        $admin = $this->superAdmin();
        $parent = Item::factory()->finishedProduct()->create();
        $component = Item::factory()->purchasedMaterial()->create();

        $this->actingAs($admin)
            ->post('/admin/boms', $this->bomPayload($parent, [
                ['item_id' => $component->id, 'quantity' => 1, 'unit' => 'db', 'notes' => null],
                ['item_id' => $component->id, 'quantity' => 2, 'unit' => 'db', 'notes' => null],
            ]))
            ->assertSessionHasErrors('items.1.item_id');
    }

    public function test_operation_type_crud_works(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->post('/admin/operation-types', [
                'code' => OperationTypeCode::CUTTING->value,
                'name' => 'Cutting',
                'description' => null,
                'is_active' => true,
            ])
            ->assertRedirect();

        $operationType = OperationType::query()->where('code', OperationTypeCode::CUTTING->value)->firstOrFail();

        $this->actingAs($admin)
            ->put("/admin/operation-types/{$operationType->id}", [
                'code' => OperationTypeCode::CUTTING->value,
                'name' => 'Updated cutting',
                'description' => 'Updated.',
                'is_active' => false,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('operation_types', ['id' => $operationType->id, 'name' => 'Updated cutting']);

        $this->actingAs($admin)->delete("/admin/operation-types/{$operationType->id}")->assertRedirect();
        $this->assertSoftDeleted('operation_types', ['id' => $operationType->id]);
    }

    public function test_operation_sequence_can_be_created_with_steps(): void
    {
        $admin = $this->superAdmin();
        $item = Item::factory()->finishedProduct()->create();
        [$operationType, $factoryUnit, $professionalRole] = $this->stepDependencies();

        $this->actingAs($admin)
            ->post('/admin/operation-sequences', $this->sequencePayload($item, [
                $this->stepPayload($operationType, $factoryUnit, $professionalRole, 10),
            ]))
            ->assertRedirect();

        $sequence = OperationSequence::query()->where('item_id', $item->id)->firstOrFail();

        $this->assertDatabaseHas('operation_sequence_steps', [
            'operation_sequence_id' => $sequence->id,
            'step_order' => 10,
            'operation_type_id' => $operationType->id,
        ]);
    }

    public function test_operation_sequence_step_order_must_be_unique(): void
    {
        $admin = $this->superAdmin();
        $item = Item::factory()->finishedProduct()->create();
        [$operationType, $factoryUnit, $professionalRole] = $this->stepDependencies();

        $this->actingAs($admin)
            ->post('/admin/operation-sequences', $this->sequencePayload($item, [
                $this->stepPayload($operationType, $factoryUnit, $professionalRole, 10),
                $this->stepPayload($operationType, $factoryUnit, $professionalRole, 10),
            ]))
            ->assertSessionHasErrors('steps.1.step_order');
    }

    public function test_activity_log_is_created_for_item_or_bom_modification(): void
    {
        $admin = $this->superAdmin();
        $parent = Item::factory()->finishedProduct()->create();

        $this->actingAs($admin)
            ->post('/admin/items', $this->itemPayload(['item_number' => 'ITEM-UI-LOG']))
            ->assertRedirect();

        $this->actingAs($admin)
            ->post('/admin/boms', $this->bomPayload($parent, []))
            ->assertRedirect();

        $this->assertTrue(Activity::query()->where('event', 'admin_item_created')->exists());
        $this->assertTrue(Activity::query()->where('event', 'admin_bom_created')->exists());
    }

    public function test_server_side_search_works_on_items_index(): void
    {
        $admin = $this->superAdmin();
        Item::factory()->create(['item_number' => 'SEARCH-ITEM-001', 'name' => 'Needle target']);
        Item::factory()->create(['item_number' => 'OTHER-ITEM-001', 'name' => 'Other item']);

        $response = $this->actingAs($admin)->get('/admin/items?search=Needle');

        $response->assertOk();
        $response->assertSee('Needle target');
        $response->assertDontSee('Other item');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function itemPayload(array $overrides = []): array
    {
        return \array_merge([
            'item_number' => 'ITEM-UI-DEFAULT',
            'name' => 'UI Item',
            'item_type' => ItemType::PurchasedMaterial->value,
            'unit' => 'db',
            'width' => null,
            'length' => null,
            'thickness' => null,
            'diameter' => null,
            'requires_serial_number' => false,
            'is_active' => true,
        ], $overrides);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function bomPayload(Item $item, array $items, array $overrides = []): array
    {
        return \array_merge([
            'item_id' => $item->id,
            'version' => 1,
            'name' => 'UI BOM',
            'description' => null,
            'is_active' => true,
            'items' => $items,
        ], $overrides);
    }

    /**
     * @return array{0: OperationType, 1: FactoryUnit, 2: ProfessionalRole}
     */
    private function stepDependencies(): array
    {
        return [
            OperationType::factory()->create(['code' => OperationTypeCode::ASSEMBLY->value]),
            FactoryUnit::factory()->create(),
            ProfessionalRole::factory()->create(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function stepPayload(
        OperationType $operationType,
        FactoryUnit $factoryUnit,
        ProfessionalRole $professionalRole,
        int $stepOrder,
    ): array {
        return [
            'step_order' => $stepOrder,
            'operation_type_id' => $operationType->id,
            'factory_unit_id' => $factoryUnit->id,
            'professional_role_id' => $professionalRole->id,
            'estimated_duration_minutes' => 45,
            'requires_quality_check' => true,
            'instructions' => 'Follow the work note.',
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $steps
     * @return array<string, mixed>
     */
    private function sequencePayload(Item $item, array $steps): array
    {
        return [
            'item_id' => $item->id,
            'version' => 1,
            'name' => 'UI sequence',
            'description' => null,
            'is_active' => true,
            'steps' => $steps,
        ];
    }

    private function superAdmin(string $email = 'admin@example.com'): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create([
            'email' => $email,
            'email_verified_at' => now(),
        ]);
        $user->assignRole('super-admin');

        return $user;
    }
}
