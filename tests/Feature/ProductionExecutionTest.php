<?php

namespace Tests\Feature;

use App\Enums\ProductionTaskStatus;
use App\Enums\QualityCheckResult;
use App\Models\Employee;
use App\Models\Item;
use App\Models\ItemBatch;
use App\Models\ItemInstance;
use App\Models\OperationSequenceStep;
use App\Models\ProductionOrder;
use App\Models\ProductionTask;
use App\Models\ProductionTaskMaterial;
use App\Models\QualityCheck;
use Database\Seeders\InventorySeeder;
use Database\Seeders\ItemMasterDataSeeder;
use Database\Seeders\OrderProductionSeeder;
use Database\Seeders\ProcurementSeeder;
use Database\Seeders\ProductionExecutionSeeder;
use Database\Seeders\ProductionMasterDataSeeder;
use Database\Seeders\ProductionStructureSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionExecutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_task_belongs_to_production_order(): void
    {
        $productionOrder = ProductionOrder::factory()->create();
        $productionTask = ProductionTask::factory()->create([
            'production_order_id' => $productionOrder->id,
        ]);

        $this->assertTrue($productionTask->productionOrder->is($productionOrder));
    }

    public function test_production_task_belongs_to_item_instance(): void
    {
        $itemInstance = ItemInstance::factory()->create();
        $productionTask = ProductionTask::factory()->create([
            'item_instance_id' => $itemInstance->id,
        ]);

        $this->assertTrue($productionTask->itemInstance->is($itemInstance));
    }

    public function test_production_task_belongs_to_operation_sequence_step(): void
    {
        $operationSequenceStep = OperationSequenceStep::factory()->create();
        $productionTask = ProductionTask::factory()->create([
            'operation_sequence_step_id' => $operationSequenceStep->id,
        ]);

        $this->assertTrue($productionTask->operationSequenceStep->is($operationSequenceStep));
    }

    public function test_production_task_belongs_to_employee(): void
    {
        $employee = Employee::factory()->create();
        $productionTask = ProductionTask::factory()->create([
            'employee_id' => $employee->id,
        ]);

        $this->assertTrue($productionTask->employee->is($employee));
    }

    public function test_same_item_instance_and_operation_step_can_appear_only_once(): void
    {
        $itemInstance = ItemInstance::factory()->create();
        $operationSequenceStep = OperationSequenceStep::factory()->create();

        ProductionTask::factory()->create([
            'item_instance_id' => $itemInstance->id,
            'operation_sequence_step_id' => $operationSequenceStep->id,
        ]);

        $this->expectException(QueryException::class);

        ProductionTask::factory()->create([
            'item_instance_id' => $itemInstance->id,
            'operation_sequence_step_id' => $operationSequenceStep->id,
        ]);
    }

    public function test_production_task_material_belongs_to_production_task(): void
    {
        $productionTask = ProductionTask::factory()->create();
        $material = ProductionTaskMaterial::factory()->create([
            'production_task_id' => $productionTask->id,
        ]);

        $this->assertTrue($material->productionTask->is($productionTask));
    }

    public function test_production_task_material_belongs_to_item(): void
    {
        $item = Item::factory()->purchasedMaterial()->create();
        $material = ProductionTaskMaterial::factory()->create([
            'item_id' => $item->id,
            'unit' => $item->unit,
        ]);

        $this->assertTrue($material->item->is($item));
    }

    public function test_production_task_material_can_connect_to_batch(): void
    {
        $batch = ItemBatch::factory()->create();

        $material = ProductionTaskMaterial::factory()->create([
            'item_id' => $batch->item_id,
            'item_batch_id' => $batch->id,
        ]);

        $this->assertTrue($material->itemBatch->is($batch));
    }

    public function test_quality_check_belongs_to_production_task(): void
    {
        $productionTask = ProductionTask::factory()->create();
        $qualityCheck = QualityCheck::factory()->create([
            'production_task_id' => $productionTask->id,
        ]);

        $this->assertTrue($qualityCheck->productionTask->is($productionTask));
    }

    public function test_quality_check_belongs_to_inspector_employee(): void
    {
        $employee = Employee::factory()->create();
        $qualityCheck = QualityCheck::factory()->create([
            'checked_by' => $employee->id,
        ]);

        $this->assertTrue($qualityCheck->inspector->is($employee));
    }

    public function test_quality_check_result_is_cast_to_enum(): void
    {
        $qualityCheck = QualityCheck::factory()->create([
            'result' => QualityCheckResult::ReworkRequired,
        ]);

        $this->assertSame(QualityCheckResult::ReworkRequired, $qualityCheck->result);
    }

    public function test_production_execution_seeder_is_idempotent(): void
    {
        $this->seed(ProductionMasterDataSeeder::class);
        $this->seed(ItemMasterDataSeeder::class);
        $this->seed(ProductionStructureSeeder::class);
        $this->seed(OrderProductionSeeder::class);
        $this->seed(InventorySeeder::class);
        $this->seed(ProcurementSeeder::class);
        $this->seed(ProductionExecutionSeeder::class);
        $this->seed(ProductionExecutionSeeder::class);

        $this->assertDatabaseCount('employees', 1);
        $this->assertDatabaseCount('item_instances', 1);
        $this->assertDatabaseCount('production_tasks', 1);
        $this->assertDatabaseCount('production_task_materials', 1);
        $this->assertDatabaseCount('quality_checks', 1);

        $this->assertDatabaseHas('item_instances', [
            'serial_number' => 'AAA/2026/0001',
        ]);
        $this->assertDatabaseHas('production_tasks', [
            'status' => ProductionTaskStatus::Completed->value,
        ]);
        $this->assertDatabaseHas('quality_checks', [
            'result' => QualityCheckResult::Accepted->value,
        ]);
    }
}
