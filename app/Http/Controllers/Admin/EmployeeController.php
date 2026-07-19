<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexRequest;
use App\Http\Requests\Admin\StoreEmployeeRequest;
use App\Http\Requests\Admin\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Models\ProfessionalRole;
use App\Models\User;
use App\Services\Admin\EmployeeAdminService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function __construct(private readonly EmployeeAdminService $service) {}

    /**
     * Megjeleníti az alkalmazottak adminisztrációs listaoldalát.
     *
     * A hozzáféréshez a bejelentkezett felhasználónak rendelkeznie kell
     * az `employees.view` jogosultsággal, amelyet az EmployeePolicy
     * `viewAny` metódusa ellenőriz.
     *
     * Az oldal számára átadja:
     * - az alkalmazottak lapozott és szűrt listáját;
     * - az aktuálisan alkalmazott szűrőket;
     * - a szakmai szerepkörök és felhasználók választható listáját.
     *
     * @param  IndexRequest  $request  A validált listaoldali kérés.
     * @return Response Az Employees index oldal Inertia válasza.
     */
    public function index(IndexRequest $request): Response
    {
        // Ellenőrzi, hogy a felhasználó jogosult-e az alkalmazottak
        // listájának megtekintésére.
        //
        // Az EmployeePolicy::viewAny() metódust hívja meg, amely az
        // `employees.view` jogosultságot ellenőrzi.
        $this->authorize('viewAny', Employee::class);

        // Megjeleníti az alkalmazottak adminisztrációs listaoldalát.
        return Inertia::render('Admin/Employees/Index', [
            // Az alkalmazottak szerveroldalon szűrt és lapozott listája.
            'records' => $this->service->paginateForAdminIndex($request->filters(), $request->perPage()),
            // Az aktuális szűrők visszaadása a frontend állapotának
            // és a szűrőmezők értékeinek megőrzéséhez.
            'filters' => $request->filters(),
            // A létrehozási, szerkesztési és szűrési mezők
            // választható értékei.
            'options' => [
                // Az alkalmazotthoz rendelhető szakmai szerepkörök.
                'professionalRoles' => ProfessionalRole::query()->orderBy('name')->get(['id', 'name', 'code']),
                // Az alkalmazotthoz kapcsolható rendszerfelhasználók.
                'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
            ],
        ]);
    }

    /**
     * Létrehoz egy új alkalmazottat.
     *
     * A bemenetet a StoreEmployeeRequest validálja és jogosultság szempontjából
     * is ellenőrzi. A létrehozás üzleti logikáját az EmployeeService végzi,
     * amely a naplózáshoz megkapja a műveletet végrehajtó felhasználót is.
     *
     * Sikeres mentés után visszatér az előző oldalra, és egy sikerüzenetet
     * jelenít meg.
     *
     * @param  StoreEmployeeRequest  $request  A validált HTTP kérés.
     * @return RedirectResponse Visszairányítás az előző oldalra.
     */
    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        // Létrehozza az új alkalmazottat a validált adatok alapján.
        // A bejelentkezett felhasználó átadásra kerül audit naplózás céljából.
        $this->service->create($request->validated(), $request->user());

        // Visszatér az előző oldalra sikeres mentési üzenettel.
        return back()->with('success', __('messages.created'));
    }

    /**
     * Frissíti egy meglévő alkalmazott adatait.
     *
     * A bemenetet az UpdateEmployeeRequest validálja és jogosultság szempontjából
     * is ellenőrzi. A módosítás üzleti logikáját az EmployeeService végzi,
     * amely a naplózáshoz megkapja a műveletet végrehajtó felhasználót is.
     *
     * Sikeres mentés után visszatér az előző oldalra, és egy sikerüzenetet
     * jelenít meg.
     *
     * @param  UpdateEmployeeRequest  $request  A validált HTTP kérés.
     * @param  Employee  $employee  A módosítandó alkalmazott.
     * @return RedirectResponse Visszairányítás az előző oldalra.
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        // Frissíti az alkalmazott adatait a validált bemenet alapján.
        // A bejelentkezett felhasználó átadásra kerül audit naplózás céljából.
        $this->service->update($employee, $request->validated(), $request->user());

        // Visszatér az előző oldalra sikeres módosítási üzenettel.
        return back()->with('success', __('messages.updated'));
    }

    /**
     * Törli a megadott alkalmazottat.
     *
     * A művelet előtt az EmployeePolicy `delete` metódusa ellenőrzi,
     * hogy a bejelentkezett felhasználó jogosult-e az alkalmazott
     * törlésére. A törlés üzleti logikáját az EmployeeService végzi,
     * amely a naplózáshoz megkapja a műveletet végrehajtó felhasználót is.
     *
     * Sikeres törlés után visszatér az előző oldalra, és egy sikerüzenetet
     * jelenít meg.
     *
     * @param  Employee  $employee  A törlendő alkalmazott.
     * @return RedirectResponse Visszairányítás az előző oldalra.
     */
    public function destroy(Employee $employee): RedirectResponse
    {
        // Ellenőrzi, hogy a felhasználó jogosult-e az alkalmazott törlésére.
        // Az EmployeePolicy::delete() metódust hívja meg.
        $this->authorize('delete', $employee);

        // Törli az alkalmazottat, és átadja a műveletet végrehajtó
        // felhasználót audit naplózás céljából.
        $this->service->delete($employee, request()->user());

        // Visszatér az előző oldalra sikeres törlési üzenettel.
        return back()->with('success', __('messages.deleted'));
    }
}
