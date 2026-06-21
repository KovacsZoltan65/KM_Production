<?php

namespace Database\Seeders;

use App\Enums\ItemInstanceStatus;
use App\Enums\OperationTypeCode;
use App\Enums\ProductionTaskStatus;
use App\Enums\QualityCheckResult;
use App\Models\Employee;
use App\Models\FactoryUnit;
use App\Models\Item;
use App\Models\ItemBatch;
use App\Models\ItemInstance;
use App\Models\Location;
use App\Models\OperationSequenceStep;
use App\Models\ProductionOrder;
use App\Models\ProductionTask;
use App\Models\ProductionTaskMaterial;
use App\Models\ProfessionalRole;
use App\Models\QualityCheck;
use Illuminate\Database\Seeder;

class ProductionExecutionSeeder extends Seeder
{
    /**
     * Seed sample production execution data.
     */
    public function run(): void
    {
        $productionOrder = ProductionOrder::query()
            ->where('order_number', 'PO-2026-000001')
            ->firstOrFail();

        $product = Item::query()->where('item_number', 'PRODUCT-AAA')->firstOrFail();
        $factoryUnit = FactoryUnit::query()->where('code', 'HEG')->firstOrFail();
        $location = Location::query()->where('code', 'HEG-WIP')->firstOrFail();
        $welderRole = ProfessionalRole::query()->where('code', 'WELDER')->firstOrFail();

        $employee = Employee::query()->updateOrCreate(
            ['employee_number' => 'EMP-WELDER-001'],
            [
                'professional_role_id' => $welderRole->id,
                'name' => 'Minta Hegesztő',
                'email' => 'welder@example.com',
                'is_active' => true,
                'hired_at' => '2026-01-01',
            ],
        );

        $itemInstance = ItemInstance::query()->updateOrCreate(
            ['serial_number' => 'AAA/2026/0001'],
            [
                'item_id' => $product->id,
                'factory_unit_id' => $factoryUnit->id,
                'current_location_id' => $location->id,
                'current_status' => ItemInstanceStatus::InProduction->value,
                'production_order_id' => $productionOrder->id,
            ],
        );

        $operationSequenceStep = OperationSequenceStep::query()
            ->where('operation_sequence_id', $productionOrder->operation_sequence_id)
            ->whereHas('operationType', function ($query): void {
                $query->where('code', OperationTypeCode::WELDING->value);
            })
            ->firstOrFail();

        $productionTask = ProductionTask::query()->updateOrCreate(
            [
                'item_instance_id' => $itemInstance->id,
                'operation_sequence_step_id' => $operationSequenceStep->id,
            ],
            [
                'production_order_id' => $productionOrder->id,
                'employee_id' => $employee->id,
                'status' => ProductionTaskStatus::Completed->value,
                'started_at' => '2026-07-01 08:00:00',
                'finished_at' => '2026-07-01 09:30:00',
                'notes' => 'Minta hegesztési művelet.',
            ],
        );

        $material = Item::query()->where('item_number', 'SCR-M4X20')->firstOrFail();
        $batch = ItemBatch::query()
            ->where('item_id', $material->id)
            ->where('batch_number', 'BATCH-2026-0001')
            ->firstOrFail();

        ProductionTaskMaterial::query()->updateOrCreate(
            [
                'production_task_id' => $productionTask->id,
                'item_id' => $material->id,
                'item_batch_id' => $batch->id,
            ],
            [
                'planned_quantity' => 8,
                'used_quantity' => 9,
                'unit' => $material->unit,
                'notes' => 'Minta anyagfelhasználás készletmozgás nélkül.',
            ],
        );

        QualityCheck::query()->updateOrCreate(
            [
                'production_task_id' => $productionTask->id,
                'checked_by' => $employee->id,
            ],
            [
                'result' => QualityCheckResult::Accepted->value,
                'checked_at' => '2026-07-01 09:45:00',
                'notes' => 'Minta ellenőrzés elfogadott eredménnyel.',
            ],
        );
    }
}
