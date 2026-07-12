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
     * Összesíti a gyáregységek terhelését a megadott időszakban.
     *
     * @return Collection<int, array{id: int, factory_unit: string, code: string,
     *     available_minutes: int, reserved_minutes: int, utilization: float,
     *     current_queue: int, status: string}> A gyáregységek kapacitásterhelése.
     */
    public function factoryUnitLoads(CarbonInterface $from, CarbonInterface $until): Collection;

    /**
     * Összesíti az aktív dolgozók terhelését a megadott időszakban.
     *
     * @return Collection<int, array{id: int, employee: string, professional_role: string,
     *     working_minutes: int, reserved_minutes: int, utilization: float,
     *     assigned_tasks: int, status: string}> A dolgozók kapacitásterhelése.
     */
    public function employeeLoads(CarbonInterface $from, CarbonInterface $until): Collection;

    /**
     * Visszaadja az időszak kapacitásfoglalásait ütemezési sorokként.
     *
     * @return Collection<int, covariant array{id: int, factory_unit: string,
     *     production_task: non-falsy-string, start: mixed, finish: mixed,
     *     duration: int<0, max>, employee: string, status: string}> Az ütemezési sorok.
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

    /**
     * Létrehozza vagy a keresési attribútumok alapján frissíti a foglalást.
     *
     * @param  array<string, mixed>  $attributes  A foglalást azonosító attribútumok.
     * @param  array<string, mixed>  $values  A mentendő foglalási értékek.
     */
    public function createOrUpdateReservation(array $attributes, array $values): CapacityReservation;

    /**
     * @return EloquentCollection<int, ProductionOrder>
     */
    public function productionOrdersForCustomerOrder(CustomerOrder $customerOrder): EloquentCollection;

    public function reservationCount(): int;
}
