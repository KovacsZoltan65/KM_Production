<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductionTaskStatus;
use App\Enums\QualityCheckResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FinishProductionTaskRequest;
use App\Http\Requests\Admin\GenerateProductionTasksRequest;
use App\Http\Requests\Admin\IndexRequest;
use App\Http\Requests\Admin\StartProductionTaskRequest;
use App\Http\Requests\Admin\StoreProductionTaskRequest;
use App\Http\Requests\Admin\UpdateProductionTaskRequest;
use App\Models\Employee;
use App\Models\Item;
use App\Models\ItemInstance;
use App\Models\Location;
use App\Models\OperationSequenceStep;
use App\Models\ProductionOrder;
use App\Models\ProductionTask;
use App\Services\Admin\ProductionTaskService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProductionTaskController extends Controller
{
    public function __construct(private readonly ProductionTaskService $service) {}

    /**
     * Megjeleníti a gyártási feladatok adminisztrációs listaoldalát.
     *
     * A ProductionTaskPolicy `viewAny` metódusa engedélyez. Az oldal a
     * lapozott rekordok mellett a szűréshez és szerkesztéshez szükséges opciókat kapja.
     *
     * @param  IndexRequest  $request  A validált listaoldali kérés.
     * @return Response Inertia válasz a gyártási feladatok index oldalához.
     */
    public function index(IndexRequest $request): Response
    {
        $this->authorize('viewAny', ProductionTask::class);

        return Inertia::render('Admin/ProductionTasks/Index', [
            'records' => $this->service->paginateForAdminIndex($request->filters(), $request->perPage()),
            'filters' => $request->filters(),
            'statusOptions' => $this->statusOptions(),
            'employeeOptions' => $this->employeeOptions(),
            'productionOrderOptions' => $this->productionOrderOptions(),
            'itemInstanceOptions' => $this->itemInstanceOptions(),
            'operationStepOptions' => $this->operationStepOptions(),
        ]);
    }

    /**
     * Megjeleníti a kiválasztott gyártási feladat adatlapját.
     *
     * A ProductionTaskPolicy `view` metódusa engedélyez, a részletes rekordot
     * a ProductionTaskService tölti be a kapcsolódó választási listák mellé.
     *
     * @param  ProductionTask  $productionTask  A megjelenítendő feladat.
     * @return Response Inertia válasz a gyártási feladat adatlapjához.
     */
    public function show(ProductionTask $productionTask): Response
    {
        $this->authorize('view', $productionTask);

        return Inertia::render('Admin/ProductionTasks/Show', [
            'productionTask' => $this->service->findForShow($productionTask),
            'employeeOptions' => $this->employeeOptions(),
            'itemOptions' => $this->itemOptions(),
            'locationOptions' => $this->locationOptions(),
            'qualityResultOptions' => $this->qualityResultOptions(),
        ]);
    }

    /**
     * Létrehoz egy új gyártási feladatot.
     *
     * A StoreProductionTaskRequest validál és engedélyez. A
     * ProductionTaskService menti és auditnaplózza a feladatot.
     *
     * @param  StoreProductionTaskRequest  $request  A validált és engedélyezett kérés.
     * @return RedirectResponse Átirányítás a gyártási feladatok listájára.
     */
    public function store(StoreProductionTaskRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return redirect()->route('admin.production-tasks.index')->with('success', __('production.tasks.messages.created'));
    }

    /**
     * Frissíti a megadott gyártási feladatot.
     *
     * Az UpdateProductionTaskRequest validál és engedélyez, a Service pedig
     * menti és auditnaplózza a módosításokat.
     *
     * @param  UpdateProductionTaskRequest  $request  A validált és engedélyezett kérés.
     * @param  ProductionTask  $productionTask  A módosítandó feladat.
     * @return RedirectResponse Visszairányítás sikeres módosítási üzenettel.
     */
    public function update(UpdateProductionTaskRequest $request, ProductionTask $productionTask): RedirectResponse
    {
        $this->service->update($productionTask, $request->validated(), $request->user());

        return back()->with('success', __('production.tasks.messages.updated'));
    }

    /**
     * Törli a megadott gyártási feladatot.
     *
     * A ProductionTaskPolicy `delete` metódusa engedélyez. A Service törli és
     * a végrehajtó felhasználóhoz kapcsolva auditnaplózza a rekordot.
     *
     * @param  ProductionTask  $productionTask  A törlendő feladat.
     * @return RedirectResponse Átirányítás a gyártási feladatok listájára.
     */
    public function destroy(ProductionTask $productionTask): RedirectResponse
    {
        $this->authorize('delete', $productionTask);
        $this->service->delete($productionTask, request()->user());

        return redirect()->route('admin.production-tasks.index')->with('success', __('production.tasks.messages.deleted'));
    }

    /**
     * Legenerálja egy gyártási rendelés végrehajtási feladatait.
     *
     * A GenerateProductionTasksRequest validál és engedélyez. A Service a
     * megadott rendeléshez és dolgozóhoz létrehozza, majd auditnaplózza a feladatokat.
     *
     * @param  GenerateProductionTasksRequest  $request  A validált és engedélyezett kérés.
     * @return RedirectResponse Visszairányítás a létrehozott feladatok számával.
     */
    public function generateFromOrder(GenerateProductionTasksRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $count = $this->service->generateFromOrder(
            ProductionOrder::query()->findOrFail($payload['production_order_id']),
            Employee::query()->findOrFail($payload['employee_id']),
            $request->user(),
        );

        return back()->with('success', __('production.tasks.messages.generated', ['count' => $count]));
    }

    /**
     * Elindítja a megadott gyártási feladat végrehajtását.
     *
     * A StartProductionTaskRequest végzi az engedélyezést. A Service ellenőrzi
     * az állapotot, rögzíti az indítást és auditnaplózza a műveletet.
     *
     * @param  StartProductionTaskRequest  $request  Az engedélyezett kérés.
     * @param  ProductionTask  $productionTask  Az elindítandó feladat.
     * @return RedirectResponse Visszairányítás sikeres indítási üzenettel.
     */
    public function start(StartProductionTaskRequest $request, ProductionTask $productionTask): RedirectResponse
    {
        $this->service->start($productionTask, $request->user());

        return back()->with('success', __('production.tasks.messages.started'));
    }

    /**
     * Befejezi a megadott gyártási feladat végrehajtását.
     *
     * A FinishProductionTaskRequest végzi az engedélyezést. A Service kezeli
     * az állapotátmenetet, a következő lépést és a művelet auditnaplózását.
     *
     * @param  FinishProductionTaskRequest  $request  Az engedélyezett kérés.
     * @param  ProductionTask  $productionTask  A befejezendő feladat.
     * @return RedirectResponse Visszairányítás sikeres befejezési üzenettel.
     */
    public function finish(FinishProductionTaskRequest $request, ProductionTask $productionTask): RedirectResponse
    {
        $this->service->finish($productionTask, $request->user());

        return back()->with('success', __('production.tasks.messages.finished'));
    }

    /**
     * Összeállítja a gyártási feladatállapotok választási listáját.
     *
     * @return list<array{label: string, value: string}> A lokalizált állapotopciók.
     */
    private function statusOptions(): array
    {
        return collect(ProductionTaskStatus::cases())
            ->map(fn (ProductionTaskStatus $status): array => [
                'label' => __("status.{$status->value}"),
                'value' => $status->value,
            ])
            ->values()
            ->all();
    }

    /**
     * Összeállítja az aktív dolgozók választási listáját.
     *
     * @return array<int, array{id: int, label: string}> A dolgozóopciók.
     */
    private function employeeOptions(): array
    {
        return Employee::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'employee_number', 'name'])
            ->map(fn (Employee $employee): array => [
                'id' => $employee->id,
                'label' => "{$employee->employee_number} - {$employee->name}",
            ])
            ->all();
    }

    /**
     * Összeállítja a gyártási rendelések választási listáját.
     *
     * @return array<int, array{id: int, label: string}> A rendelésopciók.
     */
    private function productionOrderOptions(): array
    {
        return ProductionOrder::query()
            ->orderByDesc('created_at')
            ->get(['id', 'order_number'])
            ->map(fn (ProductionOrder $order): array => ['id' => $order->id, 'label' => $order->order_number])
            ->all();
    }

    /**
     * Összeállítja a legutóbbi egyedi cikkpéldányok választási listáját.
     *
     * @return array<int, array{id: int, label: string}> A cikkpéldány-opciók.
     */
    private function itemInstanceOptions(): array
    {
        return ItemInstance::query()
            ->orderByDesc('created_at')
            ->limit(100)
            ->get(['id', 'serial_number'])
            ->map(fn (ItemInstance $instance): array => ['id' => $instance->id, 'label' => $instance->serial_number])
            ->all();
    }

    /**
     * Összeállítja a műveletsor-lépések választási listáját.
     *
     * A lépések címkéjébe a sorrendet, művelettípust és gyáregységet foglalja.
     *
     * @return array<int, array{id: int, label: string}> A műveletlépés-opciók.
     */
    private function operationStepOptions(): array
    {
        return OperationSequenceStep::query()
            ->with(['operationType', 'factoryUnit'])
            ->orderBy('operation_sequence_id')
            ->orderBy('step_order')
            ->get()
            ->map(fn (OperationSequenceStep $step): array => [
                'id' => $step->id,
                'label' => "#{$step->step_order} {$step->operationType?->name} ({$step->factoryUnit?->code})",
            ])
            ->all();
    }

    /**
     * Összeállítja a feladathoz választható cikkek listáját.
     *
     * @return array<int, array{id: int, unit: string, label: string}> A cikkopciók.
     */
    private function itemOptions(): array
    {
        return Item::query()
            ->orderBy('item_number')
            ->get(['id', 'item_number', 'name', 'unit'])
            ->map(fn (Item $item): array => [
                'id' => $item->id,
                'unit' => $item->unit,
                'label' => "{$item->item_number} - {$item->name}",
            ])
            ->all();
    }

    /**
     * Összeállítja a helyek választási listáját.
     *
     * @return array<int, array{id: int, label: string}> A helyopciók.
     */
    private function locationOptions(): array
    {
        return Location::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn (Location $location): array => [
                'id' => $location->id,
                'label' => "{$location->code} - {$location->name}",
            ])
            ->all();
    }

    /**
     * Összeállítja a minőségellenőrzési eredmények választási listáját.
     *
     * @return list<array{label: string, value: string}> A lokalizált eredményopciók.
     */
    private function qualityResultOptions(): array
    {
        return collect(QualityCheckResult::cases())
            ->map(fn (QualityCheckResult $result): array => [
                'label' => __("enum.quality_check_result.{$result->value}"),
                'value' => $result->value,
            ])
            ->all();
    }
}
