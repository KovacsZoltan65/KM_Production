<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ItemType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexRequest;
use App\Http\Requests\Admin\StoreItemRequest;
use App\Http\Requests\Admin\UpdateItemRequest;
use App\Models\Item;
use App\Services\Admin\ItemAdminService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ItemController extends Controller
{
    public function __construct(private readonly ItemAdminService $service) {}

    /**
     * Megjeleníti a cikkek adminisztrációs listaoldalát.
     *
     * Az ItemPolicy `viewAny` metódusa ellenőrzi a hozzáférést. Az oldal a
     * szűrt, lapozott cikkeket, az aktuális szűrőket és a cikktípusokat kapja.
     *
     * @param  IndexRequest  $request  A validált listaoldali kérés.
     * @return Response Inertia válasz a cikkek index oldalához.
     */
    public function index(IndexRequest $request): Response
    {
        $this->authorize('viewAny', Item::class);

        return Inertia::render('Admin/Items/Index', [
            'records' => $this->service->paginateForAdminIndex($request->filters(), $request->perPage()),
            'filters' => $request->filters(),
            'itemTypes' => collect(ItemType::cases())
                ->map(fn (ItemType $type): array => [
                    'label' => str($type->value)->replace('_', ' ')->title()->toString(),
                    'value' => $type->value,
                ])
                ->values(),
        ]);
    }

    /**
     * Létrehoz egy új cikket.
     *
     * A StoreItemRequest validál és az ItemPolicy `create` műveletével
     * engedélyez. Az ItemAdminService menti és auditnaplózza a cikket.
     *
     * @param  StoreItemRequest  $request  A validált és engedélyezett kérés.
     * @return RedirectResponse Visszairányítás sikeres létrehozási üzenettel.
     */
    public function store(StoreItemRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return back()->with('success', __('messages.created'));
    }

    /**
     * Frissíti a megadott cikket.
     *
     * Az UpdateItemRequest validál és az ItemPolicy `update` műveletével
     * engedélyez. Az ItemAdminService menti és auditnaplózza a változásokat.
     *
     * @param  UpdateItemRequest  $request  A validált és engedélyezett kérés.
     * @param  Item  $item  A módosítandó cikk.
     * @return RedirectResponse Visszairányítás sikeres módosítási üzenettel.
     */
    public function update(UpdateItemRequest $request, Item $item): RedirectResponse
    {
        $this->service->update($item, $request->validated(), $request->user());

        return back()->with('success', __('messages.updated'));
    }

    /**
     * Törli a megadott cikket.
     *
     * Az ItemPolicy `delete` metódusa ellenőrzi a hozzáférést. Az
     * ItemAdminService törli és a végrehajtó felhasználóhoz köti az auditot.
     *
     * @param  Item  $item  A törlendő cikk.
     * @return RedirectResponse Visszairányítás sikeres törlési üzenettel.
     */
    public function destroy(Item $item): RedirectResponse
    {
        $this->authorize('delete', $item);
        $this->service->delete($item, request()->user());

        return back()->with('success', __('messages.deleted'));
    }
}
