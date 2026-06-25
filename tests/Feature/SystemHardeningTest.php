<?php

namespace Tests\Feature;

use App\Enums\ItemInstanceStatus;
use App\Enums\OperationTypeCode;
use App\Enums\ProductionPlanStatus;
use App\Enums\ProductionTaskStatus;
use App\Enums\PurchaseRequisitionStatus;
use App\Enums\QualityCheckResult;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\CustomerOrderItem;
use App\Models\Employee;
use App\Models\FactoryUnit;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Item;
use App\Models\Location;
use App\Models\MaterialRequirement;
use App\Models\OperationSequence;
use App\Models\OperationSequenceStep;
use App\Models\OperationType;
use App\Models\ProductionOrder;
use App\Models\ProductionPlan;
use App\Models\ProductionPlanItem;
use App\Models\ProductionTask;
use App\Models\ProfessionalRole;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use App\Models\SerialSequence;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\StockReservation;
use App\Models\User;
use App\Services\Admin\GoodsReceiptService;
use App\Services\Admin\MaterialRequirementService;
use App\Services\Admin\ProductionPlanService;
use App\Services\Admin\ProductionTaskMaterialService;
use App\Services\Admin\ProductionTaskService;
use App\Services\Admin\PurchaseRequisitionService;
use App\Services\Admin\StockReservationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class SystemHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_order_generation_is_idempotent(): void
    {
        [$plan, $planItem] = $this->approvedProductionPlanContext();

        app(ProductionPlanService::class)->generateProductionOrders($plan);
        app(ProductionPlanService::class)->generateProductionOrders($plan->refresh());

        $this->assertDatabaseCount('production_orders', 1);
        $this->assertDatabaseHas('production_orders', [
            'production_plan_item_id' => $planItem->id,
        ]);
    }

    public function test_production_task_generation_is_idempotent(): void
    {
        [$order, $employee] = $this->productionOrderContext(quantity: 1);

        app(ProductionTaskService::class)->generateFromOrder($order, $employee);

        $this->expectException(ValidationException::class);
        app(ProductionTaskService::class)->generateFromOrder($order->refresh(), $employee);
    }

    public function test_goods_receipt_post_is_idempotent_and_double_post_is_blocked(): void
    {
        $receipt = $this->goodsReceiptContext(quantity: 5);

        app(GoodsReceiptService::class)->post($receipt);

        $this->assertDatabaseCount('stock_movements', 1);

        try {
            app(GoodsReceiptService::class)->post($receipt);
            $this->fail('Second goods receipt post was not blocked.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('stock_movements', 1);
        }
    }

    public function test_serial_generation_is_unique_and_sequence_is_locked_for_update(): void
    {
        [$order, $employee] = $this->productionOrderContext(quantity: 2);

        app(ProductionTaskService::class)->generateFromOrder($order, $employee);

        $serials = \App\Models\ItemInstance::query()
            ->where('production_order_id', $order->id)
            ->pluck('serial_number');

        $this->assertCount(2, $serials);
        $this->assertSame($serials->count(), $serials->unique()->count());
        $this->assertDatabaseHas('serial_sequences', [
            'prefix' => 'AAA',
            'last_number' => 2,
        ]);
        $this->assertStringContainsString('lockForUpdate', file_get_contents(app_path('Services/Admin/ProductionTaskService.php')));
    }

    public function test_negative_stock_consume_is_blocked(): void
    {
        [$order, $employee] = $this->productionOrderContext();
        app(ProductionTaskService::class)->generateFromOrder($order, $employee);
        $task = ProductionTask::query()->where('production_order_id', $order->id)->firstOrFail();
        $task->update(['status' => ProductionTaskStatus::InProgress->value]);
        $material = Item::factory()->purchasedMaterial()->create(['unit' => 'pcs']);
        $location = Location::factory()->create();
        StockBalance::factory()->create([
            'item_id' => $material->id,
            'location_id' => $location->id,
            'quantity' => 1,
        ]);

        $this->expectException(ValidationException::class);

        app(ProductionTaskMaterialService::class)->store($task, [
            'item_id' => $material->id,
            'location_id' => $location->id,
            'used_quantity' => 2,
            'unit' => 'pcs',
        ]);
    }

    public function test_viewer_cannot_perform_write_action(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('viewer');

        $this->actingAs($user)
            ->post(route('admin.production-tasks.generate-from-order'), [])
            ->assertForbidden();
    }

    public function test_worker_cannot_perform_admin_crud(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('worker');

        $this->actingAs($user)
            ->post(route('admin.users.store'), [
                'name' => 'Blocked User',
                'email' => 'blocked@example.com',
                'password' => 'password',
                'roles' => [],
            ])
            ->assertForbidden();
    }

    public function test_procurement_manager_cannot_start_production_task(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('procurement-manager');
        [$order, $employee] = $this->productionOrderContext();
        app(ProductionTaskService::class)->generateFromOrder($order, $employee);
        $task = ProductionTask::query()->where('production_order_id', $order->id)->firstOrFail();

        $this->actingAs($user)
            ->patch(route('admin.production-tasks.start', $task))
            ->assertForbidden();
    }

    public function test_quality_manager_can_record_quality_check(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('quality-manager');
        [$order, $employee] = $this->productionOrderContext(secondStepRequiresCheck: true);
        app(ProductionTaskService::class)->generateFromOrder($order, $employee);
        $task = ProductionTask::query()
            ->where('production_order_id', $order->id)
            ->whereHas('operationSequenceStep', fn ($query) => $query->where('step_order', 2))
            ->firstOrFail();
        $task->update(['status' => ProductionTaskStatus::WaitingForCheck->value]);

        $this->actingAs($user)
            ->post(route('admin.production-tasks.quality-checks.store', $task), [
                'checked_by' => $employee->id,
                'result' => QualityCheckResult::Accepted->value,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('quality_checks', [
            'production_task_id' => $task->id,
            'result' => QualityCheckResult::Accepted->value,
        ]);
    }

    public function test_route_helper_contains_production_execution_routes(): void
    {
        $routes = file_get_contents(resource_path('js/Utils/routes.js'));

        foreach ([
            'admin.production-tasks.index',
            'admin.production-tasks.start',
            'admin.production-tasks.finish',
            'admin.production-tasks.materials.store',
            'admin.production-tasks.quality-checks.store',
            'admin.shop-floor.index',
            'admin.shop-floor.my-tasks',
        ] as $routeName) {
            $this->assertStringContainsString($routeName, $routes);
        }
    }

    public function test_audit_log_is_created_when_production_task_is_finished(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('production-manager');
        [$order, $employee] = $this->productionOrderContext();
        app(ProductionTaskService::class)->generateFromOrder($order, $employee);
        $task = ProductionTask::query()->where('production_order_id', $order->id)->firstOrFail();
        $task->update(['status' => ProductionTaskStatus::InProgress->value]);

        $this->actingAs($user)
            ->patch(route('admin.production-tasks.finish', $task))
            ->assertRedirect();

        $this->assertTrue(Activity::query()->where('event', 'production_task_finished')->exists());
    }

    public function test_material_requirement_calculation_does_not_duplicate_records(): void
    {
        [$order] = $this->materialRequirementContext();

        app(MaterialRequirementService::class)->calculateForProductionOrder($order);
        app(MaterialRequirementService::class)->calculateForProductionOrder($order);

        $this->assertDatabaseCount('material_requirements', 1);
    }

    public function test_stock_reservation_generation_does_not_duplicate_reservations(): void
    {
        [$order, $material, $location] = $this->materialRequirementContext(requiredQuantity: 3);
        StockBalance::factory()->create([
            'item_id' => $material->id,
            'location_id' => $location->id,
            'quantity' => 10,
        ]);

        app(StockReservationService::class)->reserveForProductionOrder($order);
        app(StockReservationService::class)->reserveForProductionOrder($order);

        $this->assertDatabaseCount('stock_reservations', 1);
        $this->assertSame(3.0, (float) StockReservation::query()->sum('reserved_quantity'));
    }

    public function test_purchase_requisition_generation_does_not_duplicate_records(): void
    {
        MaterialRequirement::factory()->create([
            'missing_quantity' => 5,
            'unit' => 'pcs',
        ]);

        app(PurchaseRequisitionService::class)->generateFromMaterialRequirements();

        try {
            app(PurchaseRequisitionService::class)->generateFromMaterialRequirements();
            $this->fail('Second purchase requisition generation was not blocked.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('purchase_requisitions', 1);
            $this->assertDatabaseCount('purchase_requisition_item_sources', 1);
        }
    }

    public function test_search_reset_pagination_works_on_production_tasks_index(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('production-manager');
        [$order, $employee] = $this->productionOrderContext(quantity: 6);
        app(ProductionTaskService::class)->generateFromOrder($order, $employee);

        $this->actingAs($user)
            ->get(route('admin.production-tasks.index', ['search' => 'needle', 'page' => 1]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/ProductionTasks/Index')
                ->where('records.current_page', 1));
    }

    /**
     * @return array{0: ProductionPlan, 1: ProductionPlanItem}
     */
    private function approvedProductionPlanContext(): array
    {
        $product = Item::factory()->finishedProduct()->create();
        $customerOrderItem = CustomerOrderItem::factory()->create(['item_id' => $product->id]);
        $plan = ProductionPlan::factory()->create([
            'customer_order_id' => $customerOrderItem->customer_order_id,
            'status' => ProductionPlanStatus::Approved->value,
        ]);
        $bom = Bom::factory()->create(['item_id' => $product->id]);
        $sequence = OperationSequence::factory()->create(['item_id' => $product->id]);
        $planItem = ProductionPlanItem::factory()->create([
            'production_plan_id' => $plan->id,
            'customer_order_item_id' => $customerOrderItem->id,
            'item_id' => $product->id,
            'bom_id' => $bom->id,
            'operation_sequence_id' => $sequence->id,
            'quantity' => 1,
        ]);

        return [$plan, $planItem];
    }

    /**
     * @return array{0: ProductionOrder, 1: Employee}
     */
    private function productionOrderContext(int $quantity = 1, bool $secondStepRequiresCheck = false): array
    {
        $employee = Employee::factory()->create();
        $product = Item::factory()->finishedProduct()->create();
        $factoryUnit = FactoryUnit::factory()->create(['code' => 'AAA']);
        $role = ProfessionalRole::factory()->create();
        $sequence = OperationSequence::factory()->create(['item_id' => $product->id]);
        $cutting = OperationType::query()->create([
            'code' => OperationTypeCode::CUTTING->value,
            'name' => 'Cutting',
            'is_active' => true,
        ]);
        $welding = OperationType::query()->create([
            'code' => OperationTypeCode::WELDING->value,
            'name' => 'Welding',
            'is_active' => true,
        ]);

        OperationSequenceStep::factory()->create([
            'operation_sequence_id' => $sequence->id,
            'step_order' => 1,
            'operation_type_id' => $cutting->id,
            'factory_unit_id' => $factoryUnit->id,
            'professional_role_id' => $role->id,
            'requires_quality_check' => false,
        ]);
        OperationSequenceStep::factory()->create([
            'operation_sequence_id' => $sequence->id,
            'step_order' => 2,
            'operation_type_id' => $welding->id,
            'factory_unit_id' => $factoryUnit->id,
            'professional_role_id' => $role->id,
            'requires_quality_check' => $secondStepRequiresCheck,
        ]);

        $order = ProductionOrder::factory()->create([
            'item_id' => $product->id,
            'operation_sequence_id' => $sequence->id,
            'quantity' => $quantity,
        ]);

        return [$order, $employee];
    }

    private function goodsReceiptContext(float $quantity): GoodsReceipt
    {
        $item = Item::factory()->purchasedMaterial()->create();
        $location = Location::factory()->create();
        $receipt = GoodsReceipt::factory()->create();
        GoodsReceiptItem::factory()->create([
            'goods_receipt_id' => $receipt->id,
            'item_id' => $item->id,
            'location_id' => $location->id,
            'quantity' => $quantity,
        ]);

        return $receipt;
    }

    /**
     * @return array{0: ProductionOrder, 1: Item, 2: Location}
     */
    private function materialRequirementContext(float $requiredQuantity = 2): array
    {
        $product = Item::factory()->finishedProduct()->create();
        $material = Item::factory()->purchasedMaterial()->create(['unit' => 'pcs']);
        $location = Location::factory()->create();
        $bom = Bom::factory()->create(['item_id' => $product->id]);
        BomItem::factory()->create([
            'bom_id' => $bom->id,
            'item_id' => $material->id,
            'quantity' => $requiredQuantity,
            'unit' => 'pcs',
        ]);
        $customerOrderItem = CustomerOrderItem::factory()->create(['item_id' => $product->id]);
        $order = ProductionOrder::factory()->create([
            'customer_order_item_id' => $customerOrderItem->id,
            'item_id' => $product->id,
            'bom_id' => $bom->id,
            'quantity' => 1,
        ]);

        return [$order, $material, $location];
    }
}
