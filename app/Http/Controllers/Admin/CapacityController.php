<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ScheduleProductionOrderRequest;
use App\Http\Requests\Admin\SimulateCapacityRequest;
use App\Models\CustomerOrder;
use App\Models\ProductionOrder;
use App\Services\Admin\CapacityPlanningService;
use App\Services\Admin\LeadTimeEstimator;
use App\Services\Admin\SchedulingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CapacityController extends Controller
{
    public function __construct(
        private readonly CapacityPlanningService $capacity,
        private readonly SchedulingService $scheduler,
        private readonly LeadTimeEstimator $estimator,
    ) {}

    public function dashboard(Request $request): Response
    {
        $request->user()?->can('capacity.view') ?: abort(403);

        return Inertia::render('Admin/Capacity/Dashboard', $this->capacity->dashboard());
    }

    public function factoryUnits(Request $request): Response
    {
        $request->user()?->can('capacity.view') ?: abort(403);

        return Inertia::render('Admin/Capacity/FactoryUnits', [
            'loads' => $this->capacity->factoryUnitLoads(),
        ]);
    }

    /**
     * Megjeleníti a kapacitástervezés dolgozói terhelés (Employees) oldalát.
     *
     * A művelet csak olyan felhasználók számára érhető el, akik rendelkeznek
     * a `capacity.view` jogosultsággal. Jogosultság hiányában 403 (Forbidden)
     * válasz kerül visszaadásra.
     *
     * Az oldal számára átadja az alkalmazottak aktuális kapacitás-terhelési
     * adatait, amelyeket a CapacityService biztosít.
     *
     * @param  Request  $request  Az aktuális HTTP kérés.
     * @return Response Inertia válasz az Employees oldalhoz.
     */
    public function employees(Request $request): Response
    {
        // Ellenőrzi, hogy a bejelentkezett felhasználó rendelkezik-e
        // a kapacitástervezés megtekintéséhez szükséges jogosultsággal.
        // Jogosultság hiányában HTTP 403 (Forbidden) választ ad.
        // $request->user()?->can('capacity.view') ?: abort(403);
        // abort_unless($request->user()?->can('capacity.view'), 403);
        $this->authorizeView($request);

        // Betölti a dolgozók kapacitás-terhelési oldalát, és átadja
        // a szolgáltatás által előállított terhelési adatokat.
        return Inertia::render('Admin/Capacity/Employees', [
            'loads' => $this->capacity->employeeLoads(),
        ]);
    }

    /**
     * Megjeleníti a kapacitástervezés ütemezési (Schedule) oldalát.
     *
     * A művelet csak olyan felhasználók számára érhető el, akik rendelkeznek
     * a `capacity.view` jogosultsággal. Jogosultság hiányában HTTP 403
     * (Forbidden) válasz kerül visszaadásra.
     *
     * Az oldal számára átadja:
     * - a kapacitásütemezés sorait;
     * - a tervezhető gyártási rendelések listáját;
     * - azt az információt, hogy a felhasználó jogosult-e új
     *   kapacitástervek készítésére (`capacity.plan`).
     *
     * @param  Request  $request  Az aktuális HTTP kérés.
     * @return Response Inertia válasz a Schedule oldalhoz.
     */
    public function schedule(Request $request): Response
    {
        // Ellenőrzi, hogy a felhasználó jogosult-e a
        // kapacitástervezési modul megtekintésére.
        // Jogosultság hiányában HTTP 403 választ ad.
        // $request->user()?->can('capacity.view') ?: abort(403);
        $this->authorizeView($request);

        // Betölti a kapacitástervezési ütemező oldalt.
        return Inertia::render('Admin/Capacity/Schedule', [
            // Az ütemező megjelenítéséhez szükséges sorok.
            'rows' => $this->capacity->scheduleRows(),

            // A tervezéshez választható gyártási rendelések.
            'productionOrders' => $this->capacity->productionOrderOptions(),

            // Jelzi, hogy a felhasználó létrehozhat-e vagy módosíthat-e
            // kapacitásterveket.
            'canPlan' => $request->user()?->can('capacity.plan') ?? false,
        ]);
    }

    /**
     * Ütemezi a kiválasztott gyártási rendelést.
     *
     * A bemenetet a ScheduleProductionOrderRequest validálja és az alapvető
     * jogosultságokat ellenőrzi. Ha a felhasználó meglévő ütemezést szeretne
     * felülírni (`override`), akkor ehhez külön `capacity.override`
     * jogosultsággal is rendelkeznie kell.
     *
     * A sikeres ütemezést a CapacityScheduler szolgáltatás végzi.
     * A művelet befejezése után az előző oldalra irányít vissza egy
     * sikerüzenettel.
     *
     * @param  ScheduleProductionOrderRequest  $request  A validált HTTP kérés.
     * @return RedirectResponse Visszairányítás az előző oldalra.
     */
    public function storeSchedule(ScheduleProductionOrderRequest $request): RedirectResponse
    {

        // Meglévő ütemezés felülírása csak megfelelő jogosultsággal engedélyezett.
        if ($request->boolean('override') && ! ($request->user()?->can('capacity.override') ?? false)) {
            abort(403);
        }

        // Betölti az ütemezendő gyártási rendelést.
        $productionOrder = ProductionOrder::query()->findOrFail($request->integer('production_order_id'));

        // Elvégzi a gyártási rendelés ütemezését.
        // Az override paraméter határozza meg, hogy a meglévő
        // ütemezés felülírható-e.
        $this->scheduler->schedule($productionOrder, $request->boolean('override'));

        // Visszatér az előző oldalra sikeres ütemezési üzenettel.
        return back()->with('success', __('capacity.schedule.messages.generated'));
    }

    /**
     * Megjeleníti a kapacitástervezés szimulációs (Simulation) oldalát.
     *
     * A művelet csak olyan felhasználók számára érhető el, akik rendelkeznek
     * a `capacity.view` jogosultsággal. Jogosultság hiányában HTTP 403
     * (Forbidden) válasz kerül visszaadásra.
     *
     * Az oldal számára átadja a szimulációhoz választható vevői rendeléseket.
     * A szimuláció eredménye kezdetben `null`, amelyet a frontend a
     * felhasználói művelet után tölt fel.
     *
     * @param  Request  $request  Az aktuális HTTP kérés.
     * @return Response Inertia válasz a Simulation oldalhoz.
     */
    public function simulate(Request $request): Response
    {
        // Ellenőrzi, hogy a felhasználó jogosult-e a
        // kapacitástervezési modul megtekintésére.
        // Jogosultság hiányában HTTP 403 választ ad.
        // $request->user()?->can('capacity.view') ?: abort(403);
        $this->authorizeView($request);

        // Betölti a kapacitásszimuláció oldalát.
        return Inertia::render('Admin/Capacity/Simulation', [
            // A szimulációhoz választható vevői rendelések.
            'customerOrders' => $this->capacity->customerOrderOptions(),

            // A szimuláció eredménye. Az oldal első betöltésekor még nincs
            // számítási eredmény, ezért kezdetben null értéket kap.
            'result' => null,
        ]);
    }

    /**
     * Lefuttatja a kiválasztott vevői rendelés kapacitásszimulációját.
     *
     * A SimulateCapacityRequest végzi a kérés validálását és a szükséges
     * jogosultság ellenőrzését. A metódus betölti a kiválasztott vevői
     * rendelést, majd a LeadTimeEstimator segítségével elkészíti annak
     * kapacitás- és teljesítési becslését.
     *
     * A szimuláció auditnaplózással fut, ezért a számítás ténye és eredménye
     * később visszakövethető.
     *
     * Az oldal újrarenderelésekor visszaadja:
     * - a választható vevői rendelések listáját;
     * - a kiválasztott vevői rendelés azonosítóját;
     * - a szimuláció eredményét.
     *
     * @param  SimulateCapacityRequest  $request  A validált és engedélyezett HTTP kérés.
     * @return Response Inertia válasz a szimuláció eredményével.
     */
    public function runSimulation(SimulateCapacityRequest $request): Response
    {
        // Betölti a validált azonosítóhoz tartozó vevői rendelést.
        // Ha a rekord időközben nem létezik, HTTP 404 válasz keletkezik.
        $customerOrder = CustomerOrder::query()->findOrFail($request->integer('customer_order_id'));

        // Újrarendereli a kapacitásszimulációs oldalt a kiválasztott
        // rendeléssel és a kiszámított becslési eredménnyel.
        return Inertia::render('Admin/Capacity/Simulation', [
            // A szimulációhoz választható vevői rendelések.
            'customerOrders' => $this->capacity->customerOrderOptions(),

            // A kiválasztott rendelés azonosítója, hogy a frontend
            // megőrizhesse az aktuális kiválasztást.
            'selectedCustomerOrderId' => $customerOrder->id,

            // Elkészíti a rendelés várható teljesítési becslését.
            // Az audit: true bekapcsolja a számítás auditnaplózását.
            'result' => $this->estimator->estimate($customerOrder, audit: true),
        ]);
    }

    private function authorizeView(Request $request): void
    {
        abort_unless($request->user()?->can('capacity.view'), 403);
    }
}
