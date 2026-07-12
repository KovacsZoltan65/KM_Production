<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexRequest;
use App\Http\Requests\Admin\StoreCustomerRequest;
use App\Http\Requests\Admin\UpdateCustomerRequest;
use App\Models\Customer;
use App\Services\Admin\CustomerAdminService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function __construct(private readonly CustomerAdminService $service) {}

    /**
     * Megjeleníti a vevők adminisztrációs listaoldalát.
     *
     * A hozzáférést a CustomerPolicy `viewAny` metódusa ellenőrzi.
     * Az oldal számára átadja:
     * - a vevők szűrt és lapozott listáját;
     * - az aktuálisan alkalmazott szűrőket.
     *
     * @param  IndexRequest  $request  A validált listaoldali kérés.
     * @return Response Inertia válasz a vevők index oldalához.
     */
    public function index(IndexRequest $request): Response
    {
        // Ellenőrzi, hogy a bejelentkezett felhasználó jogosult-e
        // a vevők listájának megtekintésére.
        //
        // A CustomerPolicy::viewAny() metódust hívja meg.
        $this->authorize('viewAny', Customer::class);

        // Szűrőket tartalmazó tömb a vevők listázásához.
        $filters = $request->filters();

        // Megjeleníti a vevők adminisztrációs listaoldalát.
        return Inertia::render('Admin/Customers/Index', [
            // A vevők szerveroldalon szűrt és lapozott listája.
            'records' => $this->service->paginateForAdminIndex(
                $filters, 
                $request->perPage()
            ),
            'filters' => $filters,
        ]);
    }

    /**
     * Létrehoz egy új vevőt.
     *
     * A bemenetet a StoreCustomerRequest validálja és jogosultság
     * szempontjából is ellenőrzi. A létrehozás üzleti logikáját a
     * CustomerService végzi, amely a naplózáshoz megkapja a műveletet
     * végrehajtó felhasználót is.
     *
     * Sikeres mentés után visszatér az előző oldalra, és egy sikerüzenetet
     * jelenít meg.
     *
     * @param  StoreCustomerRequest  $request  A validált HTTP kérés.
     * @return RedirectResponse Visszairányítás az előző oldalra.
     */
    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        // Létrehozza az új vevőt a validált adatok alapján.
        // A bejelentkezett felhasználó átadásra kerül audit naplózás céljából.
        $this->service->create($request->validated(), $request->user());

        // Visszatér az előző oldalra sikeres mentési üzenettel.
        return back()->with('success', __('messages.created'));
    }

    /**
     * Frissíti egy meglévő vevő adatait.
     *
     * A bemenetet az UpdateCustomerRequest validálja és jogosultság
     * szempontjából is ellenőrzi. A módosítás üzleti logikáját a
     * CustomerService végzi, amely a naplózáshoz megkapja a műveletet
     * végrehajtó felhasználót is.
     *
     * Sikeres mentés után visszatér az előző oldalra, és egy sikerüzenetet
     * jelenít meg.
     *
     * @param  UpdateCustomerRequest  $request   A validált HTTP kérés.
     * @param  Customer               $customer  A módosítandó vevő.
     * @return RedirectResponse Visszairányítás az előző oldalra.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        // Frissíti a vevő adatait a validált bemenet alapján.
        // A bejelentkezett felhasználó átadásra kerül audit naplózás céljából.
        $this->service->update($customer, $request->validated(), $request->user());

        // Visszatér az előző oldalra sikeres módosítási üzenettel.
        return back()->with('success', __('messages.updated'));
    }

    /**
     * Törli a megadott vevőt.
     *
     * A művelet előtt a CustomerPolicy `delete` metódusa ellenőrzi,
     * hogy a bejelentkezett felhasználó jogosult-e a vevő törlésére.
     * A törlés üzleti logikáját a CustomerService végzi, amely a
     * naplózáshoz megkapja a műveletet végrehajtó felhasználót is.
     *
     * Sikeres törlés után visszatér az előző oldalra, és egy sikerüzenetet
     * jelenít meg.
     *
     * @param  Customer  $customer  A törlendő vevő.
     * @return RedirectResponse Visszairányítás az előző oldalra.
     */
    public function destroy(Customer $customer): RedirectResponse
    {
        // Ellenőrzi, hogy a felhasználó jogosult-e a vevő törlésére.
        // A CustomerPolicy::delete() metódust hívja meg.
        $this->authorize('delete', $customer);

        // Törli a vevőt, és átadja a műveletet végrehajtó
        // felhasználót audit naplózás céljából.
        $this->service->delete($customer, request()->user());

        // Visszatér az előző oldalra sikeres törlési üzenettel.
        return back()->with('success', __('messages.deleted'));
    }
}
