<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CustomerOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CancelCustomerOrderRequest;
use App\Http\Requests\Admin\ConfirmCustomerOrderRequest;
use App\Http\Requests\Admin\IndexRequest;
use App\Http\Requests\Admin\StoreCustomerOrderRequest;
use App\Http\Requests\Admin\UpdateCustomerOrderRequest;
use App\Models\Customer;
use App\Models\CustomerOrder;
use App\Models\Item;
use App\Services\Admin\CustomerOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class CustomerOrderController extends Controller
{
    public function __construct(private readonly CustomerOrderService $service) {}

    /**
     * Megjeleníti a vevői rendelések adminisztrációs listaoldalát.
     *
     * A hozzáférést a CustomerOrderPolicy `viewAny` metódusa ellenőrzi.
     * Az oldal számára átadja:
     * - a vevői rendelések szűrt és lapozott listáját;
     * - az aktuálisan alkalmazott szűrőket;
     * - a vevők, cikkek és állapotok választható listáját.
     *
     * @param  IndexRequest  $request  A validált listaoldali kérés.
     * @return Response Inertia válasz a vevői rendelések index oldalához.
     */
    public function index(IndexRequest $request): Response
    {
        $this->authorize('viewAny', CustomerOrder::class);

        return Inertia::render('Admin/CustomerOrders/Index', [
            'records' => $this->service->paginateForAdminIndex($request->filters(), $request->perPage()),
            'filters' => $request->filters(),
            'customerOptions' => $this->customerOptions(),
            'itemOptions' => $this->itemOptions(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    /**
     * Megjeleníti egy vevői rendelés részletes adatait.
     *
     * A hozzáférést a CustomerOrderPolicy `view` metódusa ellenőrzi.
     * Az oldal számára átadja a rendelés részletes adatait, valamint
     * az elérhető állapotváltozásokhoz szükséges állapotlistát.
     *
     * @param  CustomerOrder  $customerOrder  A megjelenítendő vevői rendelés.
     * @return Response Inertia válasz a rendelés adatlapjához.
     */
    public function show(CustomerOrder $customerOrder): Response
    {
        $this->authorize('view', $customerOrder);

        return Inertia::render('Admin/CustomerOrders/Show', [
            'customerOrder' => $this->service->findForShow($customerOrder),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    /**
     * Létrehoz egy új vevői rendelést.
     *
     * A bemenetet a StoreCustomerOrderRequest validálja és jogosultság
     * szempontjából is ellenőrzi. A létrehozás üzleti logikáját a
     * CustomerOrderService végzi, amely a naplózáshoz megkapja a
     * műveletet végrehajtó felhasználót is.
     *
     * Sikeres mentés után visszatér az előző oldalra, és egy
     * sikerüzenetet jelenít meg.
     *
     * @param  StoreCustomerOrderRequest  $request  A validált HTTP kérés.
     * @return RedirectResponse Visszairányítás az előző oldalra.
     */
    public function store(StoreCustomerOrderRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return back()->with('success', __('orders.messages.created'));
    }

    /**
     * Frissíti egy meglévő vevői rendelés adatait.
     *
     * A bemenetet az UpdateCustomerOrderRequest validálja és
     * jogosultság szempontjából is ellenőrzi. A módosítás üzleti
     * logikáját a CustomerOrderService végzi, amely a naplózáshoz
     * megkapja a műveletet végrehajtó felhasználót is.
     *
     * Sikeres mentés után visszatér az előző oldalra, és egy
     * sikerüzenetet jelenít meg.
     */
    public function update(UpdateCustomerOrderRequest $request, CustomerOrder $customerOrder): RedirectResponse
    {
        $this->service->update($customerOrder, $request->validated(), $request->user());

        return back()->with('success', __('orders.messages.updated'));
    }

    /**
     * Törli a megadott vevői rendelést.
     *
     * A művelet előtt a CustomerOrderPolicy `delete` metódusa
     * ellenőrzi, hogy a bejelentkezett felhasználó jogosult-e
     * a rendelés törlésére. A törlés üzleti logikáját a
     * CustomerOrderService végzi, amely a naplózáshoz megkapja
     * a műveletet végrehajtó felhasználót is.
     *
     * Sikeres törlés után visszatér az előző oldalra, és egy
     * sikerüzenetet jelenít meg.
     *
     * @param  CustomerOrder  $customerOrder  A törlendő rendelés.
     */
    public function destroy(CustomerOrder $customerOrder): RedirectResponse
    {
        $this->authorize('delete', $customerOrder);
        $this->service->delete($customerOrder, request()->user());

        return back()->with('success', __('orders.messages.deleted'));
    }

    /**
     * Jóváhagyja a megadott vevői rendelést.
     *
     * A bemenetet a ConfirmCustomerOrderRequest validálja és
     * jogosultság szempontjából is ellenőrzi. A jóváhagyás üzleti
     * logikáját a CustomerOrderService végzi.
     *
     * Sikeres jóváhagyás után visszatér az előző oldalra, és egy
     * sikerüzenetet jelenít meg.
     */
    public function confirm(ConfirmCustomerOrderRequest $request, CustomerOrder $customerOrder): RedirectResponse
    {
        $this->service->confirm($customerOrder, $request->user());

        return back()->with('success', __('orders.messages.confirmed'));
    }

    /**
     * Visszavonja a megadott vevői rendelést.
     *
     * A bemenetet a CancelCustomerOrderRequest validálja és
     * jogosultság szempontjából is ellenőrzi. A visszavonás üzleti
     * logikáját a CustomerOrderService végzi.
     *
     * Sikeres művelet után visszatér az előző oldalra, és egy
     * sikerüzenetet jelenít meg.
     */
    public function cancel(CancelCustomerOrderRequest $request, CustomerOrder $customerOrder): RedirectResponse
    {
        $this->service->cancel($customerOrder, $request->user());

        return back()->with('success', __('orders.messages.cancelled'));
    }

    /**
     * Visszaadja az aktív vevők listáját a kiválasztómezőkhöz.
     *
     * @return Collection<int, array{id: int, code: string, name: string, label: non-falsy-string}>
     */
    private function customerOptions(): Collection
    {
        return Customer::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn (Customer $customer): array => [
                'id' => $customer->id,
                'code' => $customer->code,
                'name' => $customer->name,
                'label' => "{$customer->code} - {$customer->name}",
            ]);
    }

    /**
     * @return Collection<int, array{id: int, item_number: string, name: string, unit: string, label: non-falsy-string}>
     */
    private function itemOptions(): Collection
    {
        return Item::query()
            ->where('is_active', true)
            ->orderBy('item_number')
            ->get(['id', 'item_number', 'name', 'unit'])
            ->map(fn (Item $item): array => [
                'id' => $item->id,
                'item_number' => $item->item_number,
                'name' => $item->name,
                'unit' => $item->unit,
                'label' => "{$item->item_number} - {$item->name}",
            ]);
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    private function statusOptions(): array
    {
        return collect(CustomerOrderStatus::cases())
            ->map(fn (CustomerOrderStatus $status): array => [
                'label' => __("status.{$status->value}"),
                'value' => $status->value,
            ])
            ->values()
            ->all();
    }
}
