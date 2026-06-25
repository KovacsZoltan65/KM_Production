<?php

namespace Tests\Feature;

use App\Enums\ItemInstanceStatus;
use App\Enums\OperationTypeCode;
use App\Enums\ProductionOrderStatus;
use App\Enums\ProductionTaskStatus;
use App\Enums\QualityCheckResult;
use App\Enums\StockMovementType;
use App\Enums\StockReservationStatus;
use App\Models\Employee;
use App\Models\FactoryUnit;
use App\Models\Item;
use App\Models\ItemInstance;
use App\Models\Location;
use App\Models\OperationSequence;
use App\Models\OperationSequenceStep;
use App\Models\OperationType;
use App\Models\ProductionOrder;
use App\Models\ProductionTask;
use App\Models\ProfessionalRole;
use App\Models\StockBalance;
use App\Models\StockReservation;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ProductionExecutionUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_can_list_production_tasks_but_cannot_generate_them(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('viewer');

        $this->actingAs($user)
            ->get(route('admin.production-tasks.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Admin/ProductionTasks/Index'));

        $this->actingAs($user)
            ->post(route('admin.production-tasks.generate-from-order'), [])
            ->assertForbidden();
    }

    public function test_production_tasks_can_be_generated_from_production_order(): void
    {
        [$user, $employee, $order] = $this->executionContext(quantity: 2);

        $this->actingAs($user)
            ->post(route('admin.production-tasks.generate-from-order'), [
                'production_order_id' => $order->id,
                'employee_id' => $employee->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('item_instances', 2);
        $this->assertDatabaseCount('production_tasks', 4);
        $this->assertDatabaseHas('production_tasks', ['status' => ProductionTaskStatus::Ready->value]);
        $this->assertDatabaseHas('production_tasks', ['status' => ProductionTaskStatus::Planned->value]);
        $this->assertDatabaseHas('item_instances', ['serial_number' => 'AAA/2026/0001']);
        $this->assertDatabaseHas('production_orders', ['id' => $order->id, 'status' => ProductionOrderStatus::Released->value]);
        $this->assertTrue(Activity::query()->where('event', 'production_tasks_generated')->exists());
    }

    public function test_ready_task_can_be_started(): void
    {
        [$user, $employee, $order] = $this->executionContext();
        $task = $this->generatedTask($order, $employee, ProductionTaskStatus::Ready);

        $this->actingAs($user)
            ->patch(route('admin.production-tasks.start', $task))
            ->assertRedirect();

        $this->assertDatabaseHas('production_tasks', ['id' => $task->id, 'status' => ProductionTaskStatus::InProgress->value]);
        $this->assertDatabaseHas('item_instances', ['id' => $task->item_instance_id, 'current_status' => ItemInstanceStatus::InProduction->value]);
        $this->assertTrue(Activity::query()->where('event', 'production_task_started')->exists());
    }

    public function test_only_ready_task_can_be_started(): void
    {
        [$user, $employee, $order] = $this->executionContext();
        $task = $this->generatedTask($order, $employee, ProductionTaskStatus::Planned);

        $this->actingAs($user)
            ->patch(route('admin.production-tasks.start', $task))
            ->assertSessionHasErrors('status');
    }

    public function test_finishing_non_quality_task_completes_it_and_readies_next_task(): void
    {
        [$user, $employee, $order] = $this->executionContext();
        $first = $this->generatedTask($order, $employee, ProductionTaskStatus::InProgress);
        $next = ProductionTask::query()
            ->where('item_instance_id', $first->item_instance_id)
            ->where('id', '!=', $first->id)
            ->firstOrFail();

        $this->actingAs($user)
            ->patch(route('admin.production-tasks.finish', $first))
            ->assertRedirect();

        $this->assertDatabaseHas('production_tasks', ['id' => $first->id, 'status' => ProductionTaskStatus::Completed->value]);
        $this->assertDatabaseHas('production_tasks', ['id' => $next->id, 'status' => ProductionTaskStatus::Ready->value]);
        $this->assertTrue(Activity::query()->where('event', 'production_task_finished')->exists());
    }

    public function test_finishing_quality_required_task_waits_for_check(): void
    {
        [$user, $employee, $order] = $this->executionContext(secondStepRequiresCheck: true);
        $task = $this->generatedTask($order, $employee, ProductionTaskStatus::InProgress, stepOrder: 2);

        $this->actingAs($user)
            ->patch(route('admin.production-tasks.finish', $task))
            ->assertRedirect();

        $this->assertDatabaseHas('production_tasks', ['id' => $task->id, 'status' => ProductionTaskStatus::WaitingForCheck->value]);
        $this->assertDatabaseHas('item_instances', ['id' => $task->item_instance_id, 'current_status' => ItemInstanceStatus::WaitingForCheck->value]);
    }

    public function test_accepted_quality_check_completes_task_and_order_when_all_tasks_are_done(): void
    {
        [$user, $employee, $order] = $this->executionContext(secondStepRequiresCheck: true);
        $task = $this->generatedTask($order, $employee, ProductionTaskStatus::WaitingForCheck, stepOrder: 2);
        ProductionTask::query()
            ->where('production_order_id', $order->id)
            ->where('id', '!=', $task->id)
            ->update(['status' => ProductionTaskStatus::Completed->value]);

        $this->actingAs($user)
            ->post(route('admin.production-tasks.quality-checks.store', $task), [
                'checked_by' => $employee->id,
                'result' => QualityCheckResult::Accepted->value,
                'notes' => 'Looks good.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('quality_checks', ['production_task_id' => $task->id, 'result' => QualityCheckResult::Accepted->value]);
        $this->assertDatabaseHas('production_tasks', ['id' => $task->id, 'status' => ProductionTaskStatus::Completed->value]);
        $this->assertDatabaseHas('production_orders', ['id' => $order->id, 'status' => ProductionOrderStatus::Completed->value]);
        $this->assertTrue(Activity::query()->where('event', 'production_task_quality_checked')->exists());
        $this->assertTrue(Activity::query()->where('event', 'production_order_completed')->exists());
    }

    public function test_rejected_quality_check_rejects_task_and_item_instance(): void
    {
        [$user, $employee, $order] = $this->executionContext(secondStepRequiresCheck: true);
        $task = $this->generatedTask($order, $employee, ProductionTaskStatus::WaitingForCheck, stepOrder: 2);

        $this->actingAs($user)
            ->post(route('admin.production-tasks.quality-checks.store', $task), [
                'checked_by' => $employee->id,
                'result' => QualityCheckResult::Rejected->value,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('production_tasks', ['id' => $task->id, 'status' => ProductionTaskStatus::Rejected->value]);
        $this->assertDatabaseHas('item_instances', ['id' => $task->item_instance_id, 'current_status' => ItemInstanceStatus::Rejected->value]);
        $this->assertTrue(Activity::query()->where('event', 'production_task_rejected')->exists());
    }

    public function test_material_usage_creates_stock_movement_and_decreases_balance(): void
    {
        [$user, $employee, $order] = $this->executionContext();
        $task = $this->generatedTask($order, $employee, ProductionTaskStatus::InProgress);
        $material = Item::factory()->purchasedMaterial()->create(['unit' => 'pcs']);
        $location = Location::factory()->create();
        StockBalance::factory()->create(['item_id' => $material->id, 'location_id' => $location->id, 'quantity' => 10]);
        $reservation = StockReservation::factory()->create([
            'item_id' => $material->id,
            'location_id' => $location->id,
            'production_order_id' => $order->id,
            'reserved_quantity' => 4,
        ]);

        $this->actingAs($user)
            ->post(route('admin.production-tasks.materials.store', $task), [
                'item_id' => $material->id,
                'location_id' => $location->id,
                'stock_reservation_id' => $reservation->id,
                'planned_quantity' => 4,
                'used_quantity' => 4,
                'unit' => 'pcs',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('production_task_materials', ['production_task_id' => $task->id, 'item_id' => $material->id, 'used_quantity' => 4]);
        $this->assertDatabaseHas('stock_balances', ['item_id' => $material->id, 'location_id' => $location->id, 'quantity' => 6]);
        $this->assertDatabaseHas('stock_movements', ['item_id' => $material->id, 'movement_type' => StockMovementType::ProductionConsume->value]);
        $this->assertDatabaseHas('stock_reservations', ['id' => $reservation->id, 'status' => StockReservationStatus::Consumed->value]);
        $this->assertTrue(Activity::query()->where('event', 'production_task_material_used')->exists());
    }

    public function test_material_usage_cannot_create_negative_stock(): void
    {
        [$user, $employee, $order] = $this->executionContext();
        $task = $this->generatedTask($order, $employee, ProductionTaskStatus::InProgress);
        $material = Item::factory()->purchasedMaterial()->create(['unit' => 'pcs']);
        $location = Location::factory()->create();
        StockBalance::factory()->create(['item_id' => $material->id, 'location_id' => $location->id, 'quantity' => 1]);

        $this->actingAs($user)
            ->post(route('admin.production-tasks.materials.store', $task), [
                'item_id' => $material->id,
                'location_id' => $location->id,
                'used_quantity' => 2,
                'unit' => 'pcs',
            ])
            ->assertSessionHasErrors('used_quantity');

        $this->assertDatabaseHas('stock_balances', ['item_id' => $material->id, 'location_id' => $location->id, 'quantity' => 1]);
    }

    public function test_my_tasks_only_returns_tasks_for_authenticated_employee(): void
    {
        [$user, $employee, $order] = $this->executionContext();
        $employee->update(['user_id' => $user->id]);
        $ownTask = $this->generatedTask($order, $employee, ProductionTaskStatus::Ready);
        $otherEmployee = Employee::factory()->create();
        ProductionTask::query()
            ->where('production_order_id', $order->id)
            ->whereKeyNot($ownTask->id)
            ->firstOrFail()
            ->update([
                'employee_id' => $otherEmployee->id,
                'status' => ProductionTaskStatus::Ready->value,
            ]);

        $this->actingAs($user)
            ->get(route('admin.shop-floor.my-tasks'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/ShopFloor/MyTasks')
                ->has('tasks', 1)
                ->where('tasks.0.id', $ownTask->id));
    }

    /**
     * @return array{0: User, 1: Employee, 2: ProductionOrder}
     */
    private function executionContext(int $quantity = 1, bool $secondStepRequiresCheck = false): array
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('production-manager');
        $employee = Employee::factory()->create();
        $product = Item::factory()->finishedProduct()->create();
        $factoryUnit = FactoryUnit::factory()->create(['code' => 'AAA']);
        $role = ProfessionalRole::factory()->create();
        $sequence = OperationSequence::factory()->create(['item_id' => $product->id]);
        $firstOperation = OperationType::query()->create([
            'code' => OperationTypeCode::CUTTING->value,
            'name' => 'Task Step 1',
            'is_active' => true,
        ]);
        $secondOperation = OperationType::query()->create([
            'code' => OperationTypeCode::WELDING->value,
            'name' => 'Task Step 2',
            'is_active' => true,
        ]);
        OperationSequenceStep::factory()->create([
            'operation_sequence_id' => $sequence->id,
            'step_order' => 1,
            'operation_type_id' => $firstOperation->id,
            'factory_unit_id' => $factoryUnit->id,
            'professional_role_id' => $role->id,
            'requires_quality_check' => false,
        ]);
        OperationSequenceStep::factory()->create([
            'operation_sequence_id' => $sequence->id,
            'step_order' => 2,
            'operation_type_id' => $secondOperation->id,
            'factory_unit_id' => $factoryUnit->id,
            'professional_role_id' => $role->id,
            'requires_quality_check' => $secondStepRequiresCheck,
        ]);
        $order = ProductionOrder::factory()->create([
            'item_id' => $product->id,
            'operation_sequence_id' => $sequence->id,
            'quantity' => $quantity,
        ]);

        return [$user, $employee, $order];
    }

    private function generatedTask(ProductionOrder $order, Employee $employee, ProductionTaskStatus $status, int $stepOrder = 1): ProductionTask
    {
        app(\App\Services\Admin\ProductionTaskService::class)->generateFromOrder($order, $employee);
        $task = ProductionTask::query()
            ->where('production_order_id', $order->id)
            ->whereHas('operationSequenceStep', fn ($query) => $query->where('step_order', $stepOrder))
            ->firstOrFail();
        $task->update(['status' => $status->value]);

        return $task->refresh();
    }
}
