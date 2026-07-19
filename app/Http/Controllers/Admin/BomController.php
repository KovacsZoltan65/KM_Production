<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexRequest;
use App\Http\Requests\Admin\StoreBomRequest;
use App\Http\Requests\Admin\UpdateBomRequest;
use App\Models\Bom;
use App\Models\Item;
use App\Services\Admin\BomAdminService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BomController extends Controller
{
    public function __construct(private readonly BomAdminService $service) {}

    /**
     * Megjeleníti a darabjegyzékek adminisztrációs listaoldalát.
     *
     * A hozzáférést a BomPolicy `viewAny` metódusa ellenőrzi.
     * Az oldal számára átadja:
     * - a darabjegyzékek szűrt és lapozott listáját;
     * - az aktuálisan alkalmazott szűrőket;
     * - az aktív cikkek listáját a szűrők és űrlapmezők feltöltéséhez.
     *
     * @param  IndexRequest  $request  A validált listaoldali kérés.
     * @return Response Inertia válasz a darabjegyzékek index oldalához.
     */
    public function index(IndexRequest $request): Response
    {
        // Ellenőrzi, hogy a bejelentkezett felhasználó jogosult-e
        // a darabjegyzékek listájának megtekintésére.
        //
        // A BomPolicy::viewAny() metódust hívja meg.
        $this->authorize('viewAny', Bom::class);

        // Szűrőket tartalmazó tömb a darabjegyzékek listázásához.
        $filters = $request->filters();

        // Megjeleníti a darabjegyzékek adminisztrációs listaoldalát.
        return Inertia::render('Admin/Boms/Index', [
            // A darabjegyzékek szerveroldalon szűrt és lapozott listája.
            'records' => $this->service->paginateForAdminIndex(
                $filters,
                $request->perPage()
            ),

            // Visszaadja az aktuális szűrőket, hogy a frontend
            // megőrizhesse a szűrőmezők állapotát.
            'filters' => $filters,

            // Az aktív cikkek listája a darabjegyzékhez kapcsolódó
            // szűrők és kiválasztómezők feltöltéséhez.
            'itemOptions' => Item::query()
                ->where('is_active', true)
                ->orderBy('item_number')
                ->get(['id', 'item_number', 'name', 'unit']),
        ]);
    }

    /**
     * Létrehoz egy új darabjegyzéket (BOM).
     *
     * A bemenetet a StoreBomRequest validálja és jogosultság szempontjából
     * is ellenőrzi. A létrehozás üzleti logikáját a BomService végzi,
     * amely a naplózáshoz megkapja a műveletet végrehajtó felhasználót is.
     *
     * Sikeres mentés után visszatér az előző oldalra, és egy sikerüzenetet
     * jelenít meg.
     *
     * @param  StoreBomRequest  $request  A validált HTTP kérés.
     * @return RedirectResponse Visszairányítás az előző oldalra.
     */
    public function store(StoreBomRequest $request): RedirectResponse
    {
        // Létrehozza az új darabjegyzéket a validált adatok alapján.
        // A bejelentkezett felhasználó átadásra kerül audit naplózás céljából.
        $this->service->create(
            $request->validated(),
            $request->user()
        );

        // Visszatér az előző oldalra sikeres mentési üzenettel.
        return back()->with('success', __('bom.messages.created'));
    }

    /**
     * Frissíti egy meglévő darabjegyzék (BOM) adatait.
     *
     * A bemenetet az UpdateBomRequest validálja és jogosultság szempontjából
     * is ellenőrzi. A módosítás üzleti logikáját a BomService végzi,
     * amely a naplózáshoz megkapja a műveletet végrehajtó felhasználót is.
     *
     * Sikeres mentés után visszatér az előző oldalra, és egy sikerüzenetet
     * jelenít meg.
     *
     * @param  UpdateBomRequest  $request  A validált HTTP kérés.
     * @param  Bom  $bom  A módosítandó darabjegyzék.
     * @return RedirectResponse Visszairányítás az előző oldalra.
     */
    public function update(UpdateBomRequest $request, Bom $bom): RedirectResponse
    {
        // Frissíti a darabjegyzék adatait a validált bemenet alapján.
        // A bejelentkezett felhasználó átadásra kerül audit naplózás céljából.
        $this->service->update($bom, $request->validated(), $request->user());

        // Visszatér az előző oldalra sikeres módosítási üzenettel.
        return back()->with('success', __('bom.messages.updated'));
    }

    /**
     * Törli a megadott darabjegyzéket (BOM).
     *
     * A művelet előtt a BomPolicy `delete` metódusa ellenőrzi,
     * hogy a bejelentkezett felhasználó jogosult-e a darabjegyzék
     * törlésére. A törlés üzleti logikáját a BomService végzi,
     * amely a naplózáshoz megkapja a műveletet végrehajtó felhasználót is.
     *
     * Sikeres törlés után visszatér az előző oldalra, és egy sikerüzenetet
     * jelenít meg.
     *
     * @param  Bom  $bom  A törlendő darabjegyzék.
     * @return RedirectResponse Visszairányítás az előző oldalra.
     */
    public function destroy(Bom $bom): RedirectResponse
    {
        // Ellenőrzi, hogy a felhasználó jogosult-e a darabjegyzék törlésére.
        // A BomPolicy::delete() metódust hívja meg.
        $this->authorize('delete', $bom);

        // Törli a darabjegyzéket, és átadja a műveletet végrehajtó
        // felhasználót audit naplózás céljából.
        $this->service->delete($bom, request()->user());

        // Visszatér az előző oldalra sikeres törlési üzenettel.
        return back()->with('success', __('bom.messages.deleted'));
    }
}
