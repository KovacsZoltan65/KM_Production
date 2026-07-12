<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PurchaseOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApprovePurchaseOrderRequest;
use App\Http\Requests\Admin\ClosePurchaseOrderRequest;
use App\Http\Requests\Admin\IndexRequest;
use App\Http\Requests\Admin\StorePurchaseOrderRequest;
use App\Http\Requests\Admin\UpdatePurchaseOrderRequest;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\Admin\PurchaseOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderController extends Controller
{
    public function __construct(private readonly PurchaseOrderService $service) {}

    /**
     * Megjeleníti a beszerzési rendelések adminisztrációs listaoldalát.
     *
     * A PurchaseOrderPolicy `viewAny` metódusa ellenőrzi a hozzáférést. Az
     * oldal lapozott rekordokat, szűrőket és választási listákat kap.
     *
     * @param  IndexRequest  $request  A validált listaoldali kérés.
     * @return Response Inertia válasz a beszerzési rendelések index oldalához.
     */
    public function index(IndexRequest $request): Response
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        return Inertia::render('Admin/PurchaseOrders/Index', [
            'records' => $this->service->paginateForAdminIndex($request->filters(), $request->perPage()),
            'filters' => $request->filters(),
            'statusOptions' => $this->statusOptions(),
            'supplierOptions' => $this->supplierOptions(),
            'itemOptions' => $this->itemOptions(),
        ]);
    }

    /**
     * Megjeleníti a kiválasztott beszerzési rendelés adatlapját.
     *
     * A PurchaseOrderPolicy `view` metódusa engedélyez, a részletesen
     * betöltött rendelést pedig a PurchaseOrderService biztosítja.
     *
     * @param  PurchaseOrder  $purchaseOrder  A megjelenítendő rendelés.
     * @return Response Inertia válasz a beszerzési rendelés adatlapjához.
     */
    public function show(PurchaseOrder $purchaseOrder): Response
    {
        $this->authorize('view', $purchaseOrder);

        return Inertia::render('Admin/PurchaseOrders/Show', [
            'purchaseOrder' => $this->service->findForShow($purchaseOrder),
        ]);
    }

    /**
     * Létrehoz egy új beszerzési rendelést.
     *
     * A StorePurchaseOrderRequest validál és engedélyez. A
     * PurchaseOrderService tranzakcióban menti és auditnaplózza a rendelést.
     *
     * @param  StorePurchaseOrderRequest  $request  A validált és engedélyezett kérés.
     * @return RedirectResponse Visszairányítás sikeres létrehozási üzenettel.
     */
    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return back()->with('success', __('procurement.purchase_orders.messages.created'));
    }

    /**
     * Frissíti a megadott beszerzési rendelést.
     *
     * Az UpdatePurchaseOrderRequest végzi a validálást és engedélyezést. A
     * PurchaseOrderService menti és auditnaplózza a módosításokat.
     *
     * @param  UpdatePurchaseOrderRequest  $request  A validált és engedélyezett kérés.
     * @param  PurchaseOrder  $purchaseOrder  A módosítandó rendelés.
     * @return RedirectResponse Visszairányítás sikeres módosítási üzenettel.
     */
    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->service->update($purchaseOrder, $request->validated(), $request->user());

        return back()->with('success', __('procurement.purchase_orders.messages.updated'));
    }

    /**
     * Törli a megadott beszerzési rendelést.
     *
     * A PurchaseOrderPolicy `delete` metódusa ellenőrzi a hozzáférést. A
     * PurchaseOrderService törli és auditnaplózza a rekordot.
     *
     * @param  PurchaseOrder  $purchaseOrder  A törlendő rendelés.
     * @return RedirectResponse Visszairányítás sikeres törlési üzenettel.
     */
    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('delete', $purchaseOrder);
        $this->service->delete($purchaseOrder, request()->user());

        return back()->with('success', __('procurement.purchase_orders.messages.deleted'));
    }

    /**
     * Jóváhagyja a megadott beszerzési rendelést.
     *
     * Az ApprovePurchaseOrderRequest ellenőrzi a művelet jogosultságát, a
     * PurchaseOrderService pedig végrehajtja és auditnaplózza az állapotváltást.
     *
     * @param  ApprovePurchaseOrderRequest  $request  Az engedélyezett kérés.
     * @param  PurchaseOrder  $purchaseOrder  A jóváhagyandó rendelés.
     * @return RedirectResponse Visszairányítás sikeres jóváhagyási üzenettel.
     */
    public function approve(ApprovePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->service->approve($purchaseOrder, $request->user());

        return back()->with('success', __('procurement.purchase_orders.messages.approved'));
    }

    /**
     * Lezárja a megadott beszerzési rendelést.
     *
     * A ClosePurchaseOrderRequest végzi az engedélyezést. A
     * PurchaseOrderService ellenőrzi, végrehajtja és auditnaplózza a lezárást.
     *
     * @param  ClosePurchaseOrderRequest  $request  Az engedélyezett kérés.
     * @param  PurchaseOrder  $purchaseOrder  A lezárandó rendelés.
     * @return RedirectResponse Visszairányítás sikeres lezárási üzenettel.
     */
    public function close(ClosePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->service->close($purchaseOrder, $request->user());

        return back()->with('success', __('procurement.purchase_orders.messages.closed'));
    }

    /**
     * Összeállítja a beszerzési rendelésállapotok választási listáját.
     *
     * @return array<int, array{label: string, value: string}> A lokalizált állapotopciók.
     */
    private function statusOptions(): array
    {
        return collect(PurchaseOrderStatus::cases())
            ->map(fn (PurchaseOrderStatus $status): array => [
                'label' => __("status.{$status->value}"),
                'value' => $status->value,
            ])
            ->values()
            ->all();
    }

    /**
     * Összeállítja az aktív beszállítók választási listáját.
     *
     * A Supplier rekordokból összetett kód–név címkéket készít a frontendnek.
     *
     * @return Collection<int, array{id: int, label: non-falsy-string}> A beszállítóopciók.
     */
    private function supplierOptions(): Collection
    {
        return Supplier::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name'])
            ->map(fn (Supplier $supplier): array => [
                'id' => $supplier->id,
                'label' => "{$supplier->code} - {$supplier->name}",
            ]);
    }

    /**
     * Összeállítja a rendeléshez választható cikkek listáját.
     *
     * @return Collection<int, array{id: int, unit: string, label: non-falsy-string}> A cikkopciók.
     */
    private function itemOptions(): Collection
    {
        return Item::query()
            ->orderBy('item_number')
            ->get(['id', 'item_number', 'name', 'unit'])
            ->map(fn (Item $item): array => [
                'id' => $item->id,
                'unit' => $item->unit,
                'label' => "{$item->item_number} - {$item->name}",
            ]);
    }
}
