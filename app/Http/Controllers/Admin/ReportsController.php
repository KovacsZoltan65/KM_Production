<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CustomerOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexRequest;
use App\Models\Customer;
use App\Services\Admin\ReportingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportsController extends Controller
{
    public function __construct(private readonly ReportingService $service) {}

    /**
     * Megjeleníti a vevői rendelések összesítő riportját.
     *
     * A `reports.view` jogosultság hiányában 403 választ ad. A riportot a
     * ReportingService állítja össze a validált szűrők alapján.
     *
     * @param  IndexRequest  $request  A validált riportkérés.
     * @return Response Inertia válasz a riporttal és választási listákkal.
     */
    public function customerOrders(IndexRequest $request): Response
    {
        $this->authorizeReports($request);

        return Inertia::render('Admin/Reports/CustomerOrders', [
            'report' => $this->service->customerOrdersSummary($request->filters()),
            'filters' => $request->filters(),
            'statusOptions' => $this->customerOrderStatusOptions(),
            'customerOptions' => $this->customerOptions(),
        ]);
    }

    /**
     * Megjeleníti a gyártási összesítő riportot.
     *
     * A hozzáféréshez `reports.view` jogosultság szükséges; ennek hiánya
     * 403 választ eredményez. Az adatokat a ReportingService szolgáltatja.
     *
     * @param  Request  $request  Az aktuális HTTP kérés.
     * @return Response Inertia válasz a gyártási riporttal.
     */
    public function production(Request $request): Response
    {
        $this->authorizeReports($request);

        return Inertia::render('Admin/Reports/Production', [
            'report' => $this->service->productionSummary(),
        ]);
    }

    /**
     * Megjeleníti a készlet-összesítő riportot.
     *
     * A `reports.view` jogosultságot közvetlen ellenőrzés érvényesíti, az
     * összesített készletadatokat pedig a ReportingService biztosítja.
     *
     * @param  Request  $request  Az aktuális HTTP kérés.
     * @return Response Inertia válasz a készletriporttal.
     */
    public function inventory(Request $request): Response
    {
        $this->authorizeReports($request);

        return Inertia::render('Admin/Reports/Inventory', [
            'report' => $this->service->inventorySummary(),
        ]);
    }

    /**
     * Megjeleníti a beszerzési összesítő riportot.
     *
     * A `reports.view` jogosultság hiányában 403 választ ad. A megjelenített
     * beszerzési mutatókat a ReportingService állítja össze.
     *
     * @param  Request  $request  Az aktuális HTTP kérés.
     * @return Response Inertia válasz a beszerzési riporttal.
     */
    public function procurement(Request $request): Response
    {
        $this->authorizeReports($request);

        return Inertia::render('Admin/Reports/Procurement', [
            'report' => $this->service->procurementSummary(),
        ]);
    }

    /**
     * Megjeleníti a minőségügyi összesítő riportot.
     *
     * A `reports.view` jogosultság hiányában 403 választ ad, a riport
     * adatkészletét pedig a ReportingService szolgáltatja.
     *
     * @param  Request  $request  Az aktuális HTTP kérés.
     * @return Response Inertia válasz a minőségügyi riporttal.
     */
    public function quality(Request $request): Response
    {
        $this->authorizeReports($request);

        return Inertia::render('Admin/Reports/Quality', [
            'report' => $this->service->qualitySummary(),
        ]);
    }

    /**
     * Megjeleníti a műhelyszintű összesítő riportot.
     *
     * A hozzáféréshez `reports.view` jogosultság szükséges. A
     * ReportingService állítja elő a műhely állapotát összegző adatokat.
     *
     * @param  Request  $request  Az aktuális HTTP kérés.
     * @return Response Inertia válasz a műhelyriporttal.
     */
    public function shopFloor(Request $request): Response
    {
        $this->authorizeReports($request);

        return Inertia::render('Admin/Reports/ShopFloor', [
            'report' => $this->service->shopFloorSummary(),
        ]);
    }

    /**
     * Ellenőrzi a riportok megtekintési jogosultságát.
     *
     * @param  Request  $request  A hitelesített felhasználót tartalmazó kérés.
     */
    private function authorizeReports(Request $request): void
    {
        abort_unless($request->user()?->can('reports.view'), 403);
    }

    /**
     * Összeállítja a vevői rendelésállapotok választási listáját.
     *
     * A CustomerOrderStatus enum értékeiből a frontend szűrőjéhez készít
     * megjelenítési címkéket és technikai értékeket.
     *
     * @return list<array{label: string, value: string}> Az állapotopciók.
     */
    private function customerOrderStatusOptions(): array
    {
        return collect(CustomerOrderStatus::cases())
            ->map(fn (CustomerOrderStatus $status): array => [
                'label' => str($status->value)->replace('_', ' ')->title()->toString(),
                'value' => $status->value,
            ])
            ->values()
            ->all();
    }

    /**
     * Összeállítja a választható vevők listáját.
     *
     * A Customer rekordokat név szerint rendezi, majd a riport szűrője által
     * használt azonosító–címke párokká alakítja.
     *
     * @return array<int, array{id: int, label: string}> A vevőopciók.
     */
    private function customerOptions(): array
    {
        return Customer::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Customer $customer): array => [
                'id' => $customer->id,
                'label' => $customer->name,
            ])
            ->all();
    }
}
