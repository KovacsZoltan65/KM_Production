<?php

namespace App\Repositories\Contracts;

use App\Models\CapacityReservation;
use App\Models\CustomerOrder;
use App\Models\FactoryUnitCalendar;
use App\Models\ProductionOrder;
use App\Models\ProductionTask;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

interface CapacityRepositoryInterface
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function factoryUnitLoads(CarbonInterface $from, CarbonInterface $until): Collection;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function employeeLoads(CarbonInterface $from, CarbonInterface $until): Collection;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function scheduleRows(CarbonInterface $from, CarbonInterface $until): Collection;

    /**
     * @return Collection<int, array{id: int, label: string}>
     */
    public function productionOrderOptions(): Collection;

    /**
     * @return Collection<int, array{id: int, label: string}>
     */
    public function customerOrderOptions(): Collection;

    /**
     * @return EloquentCollection<int, ProductionTask>
     */
    public function tasksForProductionOrder(ProductionOrder $productionOrder): EloquentCollection;

    public function factoryCalendarFor(int $factoryUnitId, int $weekday): ?FactoryUnitCalendar;

    public function nextFactoryReservationOverlap(int $factoryUnitId, CarbonInterface $from, CarbonInterface $until, ?int $ignoreProductionTaskId = null): ?CapacityReservation;

    /**
     * @return EloquentCollection<int, CapacityReservation>
     */
    public function factoryReservationsForHorizon(int $factoryUnitId, CarbonInterface $from, CarbonInterface $until): EloquentCollection;

    public function createOrUpdateReservation(array $attributes, array $values): CapacityReservation;

    /**
     * @return EloquentCollection<int, ProductionOrder>
     */
    public function productionOrdersForCustomerOrder(CustomerOrder $customerOrder): EloquentCollection;

    public function reservationCount(): int;
}
