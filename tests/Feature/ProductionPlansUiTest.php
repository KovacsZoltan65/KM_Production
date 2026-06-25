<?php

namespace Tests\Feature;

use App\Enums\ProductionPlanStatus;
use App\Models\Bom;
use App\Models\CustomerOrder;
use App\Models\CustomerOrderItem;
use App\Models\Item;
use App\Models\OperationSequence;
use App\Models\ProductionPlan;
use App\Models\ProductionPlanItem;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ProductionPlansUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_production_plans_page(): void
    {
        $this->get('/admin/production-plans')->assertRedirect('/login');
    }

    public function test_user_without_permission_cannot_access_production_plans_page(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->get('/admin/production-plans')
            ->assertForbidden();
    }

    public function test_super_admin_can_access_production_plans_page(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/admin/production-plans')
            ->assertOk();
    }

    public function test_production_plan_can_be_created_from_customer_order(): void
    {
        $admin = $this->superAdmin();
        [$customerOrder] = $this->customerOrderWithItem();

        $this->actingAs($admin)
            ->post('/admin/production-plans', $this->payload($customerOrder))
            ->assertRedirect();

        $this->assertDatabaseHas('production_plans', [
            'customer_order_id' => $customerOrder->id,
            'status' => ProductionPlanStatus::Draft->value,
        ]);
    }

    public function test_plan_items_are_created_automatically(): void
    {
        $admin = $this->superAdmin();
        [$customerOrder, $customerOrderItem] = $this->customerOrderWithItem();

        $this->actingAs($admin)
            ->post('/admin/production-plans', $this->payload($customerOrder))
            ->assertRedirect();

        $this->assertDatabaseHas('production_plan_items', [
            'customer_order_item_id' => $customerOrderItem->id,
            'item_id' => $customerOrderItem->item_id,
            'quantity' => 3,
        ]);
    }

    public function test_bom_can_be_selected(): void
    {
        $admin = $this->superAdmin();
        [$productionPlan, $planItem, $item] = $this->productionPlanWithItem();
        $bom = Bom::factory()->create(['item_id' => $item->id, 'version' => 1, 'is_active' => true]);

        $this->actingAs($admin)
            ->put("/admin/production-plans/{$productionPlan->id}", $this->updatePayload($productionPlan, [
                ['id' => $planItem->id, 'bom_id' => $bom->id],
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('production_plan_items', [
            'id' => $planItem->id,
            'bom_id' => $bom->id,
        ]);
    }

    public function test_operation_sequence_can_be_selected(): void
    {
        $admin = $this->superAdmin();
        [$productionPlan, $planItem, $item] = $this->productionPlanWithItem();
        $sequence = OperationSequence::factory()->create(['item_id' => $item->id, 'version' => 1, 'is_active' => true]);

        $this->actingAs($admin)
            ->put("/admin/production-plans/{$productionPlan->id}", $this->updatePayload($productionPlan, [
                ['id' => $planItem->id, 'operation_sequence_id' => $sequence->id],
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('production_plan_items', [
            'id' => $planItem->id,
            'operation_sequence_id' => $sequence->id,
        ]);
    }

    public function test_plan_approve_works(): void
    {
        $admin = $this->superAdmin();
        [$productionPlan] = $this->productionPlanWithItem();

        $this->actingAs($admin)
            ->patch("/admin/production-plans/{$productionPlan->id}/approve")
            ->assertRedirect();

        $this->assertDatabaseHas('production_plans', [
            'id' => $productionPlan->id,
            'status' => ProductionPlanStatus::Approved->value,
            'approved_by' => $admin->id,
        ]);
    }

    public function test_approved_plan_cannot_be_approved_again(): void
    {
        $admin = $this->superAdmin();
        [$productionPlan] = $this->productionPlanWithItem(['status' => ProductionPlanStatus::Approved]);

        $this->actingAs($admin)
            ->from('/admin/production-plans')
            ->patch("/admin/production-plans/{$productionPlan->id}/approve")
            ->assertRedirect('/admin/production-plans')
            ->assertSessionHasErrors('status');
    }

    public function test_production_orders_can_be_generated(): void
    {
        $admin = $this->superAdmin();
        [$productionPlan, $planItem, $item, $customerOrderItem] = $this->productionPlanWithItem(['status' => ProductionPlanStatus::Approved]);
        $bom = Bom::factory()->create(['item_id' => $item->id, 'version' => 1]);
        $sequence = OperationSequence::factory()->create(['item_id' => $item->id, 'version' => 1]);
        $planItem->update(['bom_id' => $bom->id, 'operation_sequence_id' => $sequence->id]);

        $this->actingAs($admin)
            ->post("/admin/production-plans/{$productionPlan->id}/generate-production-orders")
            ->assertRedirect();

        $this->assertDatabaseHas('production_orders', [
            'production_plan_item_id' => $planItem->id,
            'customer_order_item_id' => $customerOrderItem->id,
            'item_id' => $item->id,
            'bom_id' => $bom->id,
            'operation_sequence_id' => $sequence->id,
            'quantity' => 3,
        ]);
    }

    public function test_production_order_cannot_be_generated_without_bom(): void
    {
        $admin = $this->superAdmin();
        [$productionPlan, $planItem, $item] = $this->productionPlanWithItem(['status' => ProductionPlanStatus::Approved]);
        $sequence = OperationSequence::factory()->create(['item_id' => $item->id, 'version' => 1]);
        $planItem->update(['bom_id' => null, 'operation_sequence_id' => $sequence->id]);

        $this->actingAs($admin)
            ->from("/admin/production-plans/{$productionPlan->id}")
            ->post("/admin/production-plans/{$productionPlan->id}/generate-production-orders")
            ->assertRedirect("/admin/production-plans/{$productionPlan->id}")
            ->assertSessionHasErrors('items');
    }

    public function test_production_order_cannot_be_generated_without_operation_sequence(): void
    {
        $admin = $this->superAdmin();
        [$productionPlan, $planItem, $item] = $this->productionPlanWithItem(['status' => ProductionPlanStatus::Approved]);
        $bom = Bom::factory()->create(['item_id' => $item->id, 'version' => 1]);
        $planItem->update(['bom_id' => $bom->id, 'operation_sequence_id' => null]);

        $this->actingAs($admin)
            ->from("/admin/production-plans/{$productionPlan->id}")
            ->post("/admin/production-plans/{$productionPlan->id}/generate-production-orders")
            ->assertRedirect("/admin/production-plans/{$productionPlan->id}")
            ->assertSessionHasErrors('items');
    }

    public function test_status_filter_works(): void
    {
        $admin = $this->superAdmin();
        ProductionPlan::factory()->create(['plan_number' => 'PP-2026-DRAFT', 'status' => ProductionPlanStatus::Draft]);
        ProductionPlan::factory()->create(['plan_number' => 'PP-2026-APPROVED', 'status' => ProductionPlanStatus::Approved]);

        $response = $this->actingAs($admin)->get('/admin/production-plans?status=approved');

        $response->assertOk();
        $response->assertSee('PP-2026-APPROVED');
        $response->assertDontSee('PP-2026-DRAFT');
    }

    public function test_search_works(): void
    {
        $admin = $this->superAdmin();
        $needleOrder = CustomerOrder::factory()->create(['order_number' => 'SO-2026-NEEDLE']);
        $needleOrder->customer->update(['name' => 'Needle Customer']);
        $otherOrder = CustomerOrder::factory()->create(['order_number' => 'SO-2026-OTHER']);
        $otherOrder->customer->update(['name' => 'Other Customer']);
        ProductionPlan::factory()->create(['plan_number' => 'PP-2026-NEEDLE', 'customer_order_id' => $needleOrder->id]);
        ProductionPlan::factory()->create(['plan_number' => 'PP-2026-OTHER', 'customer_order_id' => $otherOrder->id]);

        $response = $this->actingAs($admin)->get('/admin/production-plans?search=Needle');

        $response->assertOk();
        $response->assertSee('PP-2026-NEEDLE');
        $response->assertDontSee('PP-2026-OTHER');
    }

    public function test_show_page_can_be_loaded(): void
    {
        $admin = $this->superAdmin();
        [$productionPlan] = $this->productionPlanWithItem(['plan_number' => 'PP-2026-SHOW']);

        $this->actingAs($admin)
            ->get("/admin/production-plans/{$productionPlan->id}")
            ->assertOk()
            ->assertSee('PP-2026-SHOW');
    }

    public function test_activity_log_is_created_for_approve_event(): void
    {
        $admin = $this->superAdmin();
        [$productionPlan] = $this->productionPlanWithItem();

        $this->actingAs($admin)
            ->patch("/admin/production-plans/{$productionPlan->id}/approve")
            ->assertRedirect();

        $this->assertTrue(Activity::query()->where('event', 'production_plan_approved')->exists());
    }

    public function test_activity_log_is_created_for_production_order_generation(): void
    {
        $admin = $this->superAdmin();
        [$productionPlan, $planItem, $item] = $this->productionPlanWithItem(['status' => ProductionPlanStatus::Approved]);
        $bom = Bom::factory()->create(['item_id' => $item->id, 'version' => 1]);
        $sequence = OperationSequence::factory()->create(['item_id' => $item->id, 'version' => 1]);
        $planItem->update(['bom_id' => $bom->id, 'operation_sequence_id' => $sequence->id]);

        $this->actingAs($admin)
            ->post("/admin/production-plans/{$productionPlan->id}/generate-production-orders")
            ->assertRedirect();

        $this->assertTrue(Activity::query()->where('event', 'production_orders_generated')->exists());
    }

    /**
     * @return array{0: CustomerOrder, 1: CustomerOrderItem, 2: Item}
     */
    private function customerOrderWithItem(): array
    {
        $item = Item::factory()->finishedProduct()->create(['unit' => 'db']);
        $customerOrder = CustomerOrder::factory()->create();
        $customerOrderItem = CustomerOrderItem::factory()->create([
            'customer_order_id' => $customerOrder->id,
            'item_id' => $item->id,
            'quantity' => 3,
            'unit' => 'db',
        ]);

        return [$customerOrder, $customerOrderItem, $item];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{0: ProductionPlan, 1: ProductionPlanItem, 2: Item, 3: CustomerOrderItem}
     */
    private function productionPlanWithItem(array $overrides = []): array
    {
        [$customerOrder, $customerOrderItem, $item] = $this->customerOrderWithItem();
        $productionPlan = ProductionPlan::factory()->create(array_merge([
            'customer_order_id' => $customerOrder->id,
        ], $overrides));
        $planItem = ProductionPlanItem::factory()->create([
            'production_plan_id' => $productionPlan->id,
            'customer_order_item_id' => $customerOrderItem->id,
            'item_id' => $item->id,
            'bom_id' => null,
            'operation_sequence_id' => null,
            'quantity' => 3,
        ]);

        return [$productionPlan, $planItem, $item, $customerOrderItem];
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(CustomerOrder $customerOrder): array
    {
        return [
            'customer_order_id' => $customerOrder->id,
            'planned_start_date' => now()->addWeek()->format('Y-m-d'),
            'planned_finish_date' => now()->addWeeks(3)->format('Y-m-d'),
            'notes' => 'Planning notes',
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $itemOverrides
     * @return array<string, mixed>
     */
    private function updatePayload(ProductionPlan $productionPlan, array $itemOverrides): array
    {
        $items = $productionPlan->items()->get()->map(function (ProductionPlanItem $item) use ($itemOverrides): array {
            $override = collect($itemOverrides)->firstWhere('id', $item->id) ?? [];

            return \array_merge([
                'id' => $item->id,
                'bom_id' => $item->bom_id,
                'operation_sequence_id' => $item->operation_sequence_id,
                'planned_start_date' => now()->addWeek()->format('Y-m-d'),
                'planned_finish_date' => now()->addWeeks(3)->format('Y-m-d'),
                'notes' => $item->notes,
            ], $override);
        })->values()->all();

        return [
            'planned_start_date' => now()->addWeek()->format('Y-m-d'),
            'planned_finish_date' => now()->addWeeks(3)->format('Y-m-d'),
            'notes' => 'Updated planning notes',
            'items' => $items,
        ];
    }

    private function superAdmin(string $email = 'production-plans-admin@example.com'): User
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
