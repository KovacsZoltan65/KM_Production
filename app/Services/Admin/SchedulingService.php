<?php

namespace App\Services\Admin;

use App\Models\CapacityReservation;
use App\Models\ProductionOrder;
use App\Repositories\Contracts\CapacityRepositoryInterface;
use App\Services\AuditLogService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SchedulingService
{
    public function __construct(
        private readonly CapacityRepositoryInterface $capacity,
        private readonly AuditLogService $audit,
        private readonly CapacityPlanningService $planning,
        private readonly CapacitySlotFinder $slotFinder,
    ) {}

    /**
     * @return Collection<int, CapacityReservation>
     */
    public function schedule(ProductionOrder $productionOrder, bool $override = false): Collection
    {
        return DB::transaction(function () use ($productionOrder, $override): Collection {
            $tasks = $this->capacity->tasksForProductionOrder($productionOrder);
            $cursor = CarbonImmutable::parse($productionOrder->planned_start_date ?? now())->startOfDay()->addHours(8);
            $reservations = collect();

            foreach ($tasks as $task) {
                $step = $task->operationSequenceStep;
                $duration = max(1, (int) $step->estimated_duration_minutes);
                [$reservedFrom, $reservedUntil] = $this->slotFinder->findSlot($step->factory_unit_id, $cursor, $duration, $task->id);

                $reservations->push($this->capacity->createOrUpdateReservation(
                    ['production_task_id' => $task->id],
                    [
                        'factory_unit_id' => $step->factory_unit_id,
                        'employee_id' => $task->employee_id,
                        'reserved_from' => $reservedFrom,
                        'reserved_until' => $reservedUntil,
                        'planned_minutes' => $duration,
                        'status' => $override ? 'overridden' : 'planned',
                    ],
                ));

                $cursor = $reservedUntil;
            }

            $this->planning->forgetCapacityCache();

            $this->audit->log('capacity_schedule_generated', $productionOrder, [
                'production_order_id' => $productionOrder->id,
                'reservation_count' => $reservations->count(),
            ]);

            if ($override) {
                $this->audit->log('capacity_override', $productionOrder, [
                    'production_order_id' => $productionOrder->id,
                    'reservation_count' => $reservations->count(),
                ]);
            }

            return $reservations;
        });
    }
}
