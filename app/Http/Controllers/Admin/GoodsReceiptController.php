<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GoodsReceiptStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexRequest;
use App\Http\Requests\Admin\PostGoodsReceiptRequest;
use App\Http\Requests\Admin\StoreGoodsReceiptRequest;
use App\Models\GoodsReceipt;
use App\Models\Item;
use App\Models\Location;
use App\Models\PurchaseOrder;
use App\Services\Admin\GoodsReceiptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class GoodsReceiptController extends Controller
{
    public function __construct(private readonly GoodsReceiptService $service) {}

    /**
     * Megjeleníti az áruátvételek adminisztrációs listaoldalát.
     *
     * A GoodsReceiptPolicy `viewAny` metódusa ellenőrzi a hozzáférést. Az
     * oldal megkapja a lapozott rekordokat, szűrőket és választási listákat.
     *
     * @param  IndexRequest  $request  A validált listaoldali kérés.
     * @return Response Inertia válasz az áruátvételek index oldalához.
     */
    public function index(IndexRequest $request): Response
    {
        $this->authorize('viewAny', GoodsReceipt::class);

        return Inertia::render('Admin/GoodsReceipts/Index', [
            'records' => $this->service->paginateForAdminIndex($request->filters(), $request->perPage()),
            'filters' => $request->filters(),
            'statusOptions' => $this->statusOptions(),
            'purchaseOrderOptions' => $this->purchaseOrderOptions(),
            'itemOptions' => $this->itemOptions(),
            'locationOptions' => $this->locationOptions(),
        ]);
    }

    /**
     * Megjeleníti a kiválasztott áruátvétel részletes adatlapját.
     *
     * A GoodsReceiptPolicy `view` metódusa ellenőrzi a hozzáférést, a
     * kapcsolatokkal betöltött rekordot a GoodsReceiptService biztosítja.
     *
     * @param  GoodsReceipt  $goodsReceipt  A megjelenítendő áruátvétel.
     * @return Response Inertia válasz az áruátvétel adatlapjához.
     */
    public function show(GoodsReceipt $goodsReceipt): Response
    {
        $this->authorize('view', $goodsReceipt);

        return Inertia::render('Admin/GoodsReceipts/Show', [
            'goodsReceipt' => $this->service->findForShow($goodsReceipt),
        ]);
    }

    /**
     * Létrehoz egy új áruátvételt.
     *
     * A StoreGoodsReceiptRequest validálja és engedélyezi a kérést. A
     * GoodsReceiptService tranzakcióban menti és auditnaplózza az átvételt.
     *
     * @param  StoreGoodsReceiptRequest  $request  A validált és engedélyezett kérés.
     * @return RedirectResponse Átirányítás a létrehozott áruátvétel adatlapjára.
     */
    public function store(StoreGoodsReceiptRequest $request): RedirectResponse
    {
        $goodsReceipt = $this->service->create($request->validated(), $request->user());

        return redirect()->route('admin.goods-receipts.show', $goodsReceipt)->with('success', __('procurement.goods_receipts.messages.created'));
    }

    /**
     * Könyveli a megadott áruátvételt és annak készletmozgásait.
     *
     * A PostGoodsReceiptRequest végzi a jogosultság-ellenőrzést. A
     * GoodsReceiptService hajtja végre és auditnaplózza a könyvelési folyamatot.
     *
     * @param  PostGoodsReceiptRequest  $request  Az engedélyezett kérés.
     * @param  GoodsReceipt  $goodsReceipt  A könyvelendő áruátvétel.
     * @return RedirectResponse Visszairányítás sikeres könyvelési üzenettel.
     */
    public function post(PostGoodsReceiptRequest $request, GoodsReceipt $goodsReceipt): RedirectResponse
    {
        $this->service->post($goodsReceipt, $request->user());

        return back()->with('success', __('procurement.goods_receipts.messages.posted'));
    }

    /**
     * Összeállítja az áruátvételi állapotok választási listáját.
     *
     * A GoodsReceiptStatus enum értékeiből lokalizált címke–érték párokat
     * készít a frontend szűrői és űrlapmezői számára.
     *
     * @return array<int, array{label: string, value: string}> Az állapotopciók.
     */
    private function statusOptions(): array
    {
        return collect(GoodsReceiptStatus::cases())
            ->map(fn (GoodsReceiptStatus $status): array => [
                'label' => __("status.{$status->value}"),
                'value' => $status->value,
            ])
            ->values()
            ->all();
    }

    /**
     * Összeállítja a választható beszerzési rendelések listáját.
     *
     * A rendeléseket a beszállítóval együtt tölti be, és a frontend számára
     * azonosítót, rendelésszámot és beszállítói nevet tartalmazó címkét készít.
     *
     * @return Collection<int, array{id: int, label: non-falsy-string}> A rendelésopciók.
     */
    private function purchaseOrderOptions(): Collection
    {
        return PurchaseOrder::query()
            ->with('supplier')
            ->orderByDesc('created_at')
            ->get(['id', 'order_number', 'supplier_id'])
            ->map(fn (PurchaseOrder $purchaseOrder): array => [
                'id' => $purchaseOrder->id,
                'label' => "{$purchaseOrder->order_number} - ".($purchaseOrder->supplier?->name ?? 'Unknown supplier'),
            ]);
    }

    /**
     * Összeállítja az átvételhez választható cikkek listáját.
     *
     * A cikkeket cikkszám szerint rendezi, majd azonosítóval, mértékegységgel
     * és összetett megjelenítési címkével adja át a frontendnek.
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

    /**
     * Összeállítja az átvételi helyek választási listáját.
     *
     * A Location rekordokat kód szerint rendezi, majd azonosító–címke
     * párokká alakítja a frontend helyválasztó mezőihez.
     *
     * @return Collection<int, array{id: int, label: non-falsy-string}> A helyopciók.
     */
    private function locationOptions(): Collection
    {
        return Location::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn (Location $location): array => [
                'id' => $location->id,
                'label' => "{$location->code} - {$location->name}",
            ]);
    }
}
