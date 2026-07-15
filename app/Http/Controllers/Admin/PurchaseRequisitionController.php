<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PurchaseRequisitionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApprovePurchaseRequisitionRequest;
use App\Http\Requests\Admin\GeneratePurchaseOrderRequest;
use App\Http\Requests\Admin\IndexRequest;
use App\Http\Requests\Admin\StorePurchaseRequisitionRequest;
use App\Http\Requests\Admin\UpdatePurchaseRequisitionRequest;
use App\Models\Item;
use App\Models\PurchaseRequisition;
use App\Models\Supplier;
use App\Services\Admin\PurchaseRequisitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseRequisitionController extends Controller
{
    public function __construct(private readonly PurchaseRequisitionService $service) {}

    /**
     * Megjeleníti a beszerzési igények adminisztrációs listaoldalát.
     *
     * A PurchaseRequisitionPolicy `viewAny` metódusa engedélyez. Az oldal
     * lapozott rekordokat, szűrőket, állapotokat és cikkopciókat kap.
     *
     * @param  IndexRequest  $request  A validált listaoldali kérés.
     * @return Response Inertia válasz a beszerzési igények index oldalához.
     */
    public function index(IndexRequest $request): Response
    {
        $this->authorize('viewAny', PurchaseRequisition::class);

        return Inertia::render('Admin/PurchaseRequisitions/Index', [
            'records' => $this->service->paginateForAdminIndex($request->filters(), $request->perPage()),
            'filters' => $request->filters(),
            'statusOptions' => $this->statusOptions(),
            'itemOptions' => $this->itemOptions(),
        ]);
    }

    /**
     * Megjeleníti a kiválasztott beszerzési igény adatlapját.
     *
     * A PurchaseRequisitionPolicy `view` metódusa ellenőrzi a hozzáférést. A
     * Service részletes rekordot, a controller beszállítóopciókat ad az oldalnak.
     *
     * @param  PurchaseRequisition  $purchaseRequisition  A megjelenítendő igény.
     * @return Response Inertia válasz a beszerzési igény adatlapjához.
     */
    public function show(PurchaseRequisition $purchaseRequisition): Response
    {
        $this->authorize('view', $purchaseRequisition);

        return Inertia::render('Admin/PurchaseRequisitions/Show', [
            'purchaseRequisition' => $this->service->findForShow($purchaseRequisition),
            'supplierOptions' => $this->supplierOptions(),
        ]);
    }

    /**
     * Létrehoz egy új beszerzési igényt.
     *
     * A StorePurchaseRequisitionRequest validál és engedélyez. A Service
     * tranzakcióban menti és auditnaplózza az igényt és annak tételeit.
     *
     * @param  StorePurchaseRequisitionRequest  $request  A validált és engedélyezett kérés.
     * @return RedirectResponse Visszairányítás sikeres létrehozási üzenettel.
     */
    public function store(StorePurchaseRequisitionRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return back()->with('success', __('procurement.purchase_requisitions.messages.created'));
    }

    /**
     * Frissíti a megadott beszerzési igényt.
     *
     * Az UpdatePurchaseRequisitionRequest validál és engedélyez, a Service
     * pedig menti és auditnaplózza a változtatásokat.
     *
     * @param  UpdatePurchaseRequisitionRequest  $request  A validált és engedélyezett kérés.
     * @param  PurchaseRequisition  $purchaseRequisition  A módosítandó igény.
     * @return RedirectResponse Visszairányítás sikeres módosítási üzenettel.
     */
    public function update(UpdatePurchaseRequisitionRequest $request, PurchaseRequisition $purchaseRequisition): RedirectResponse
    {
        $this->service->update($purchaseRequisition, $request->validated(), $request->user());

        return back()->with('success', __('procurement.purchase_requisitions.messages.updated'));
    }

    /**
     * Törli a megadott beszerzési igényt.
     *
     * A PurchaseRequisitionPolicy `delete` metódusa engedélyez, a Service
     * pedig törli és auditnaplózza a rekordot.
     *
     * @param  PurchaseRequisition  $purchaseRequisition  A törlendő igény.
     * @return RedirectResponse Visszairányítás sikeres törlési üzenettel.
     */
    public function destroy(PurchaseRequisition $purchaseRequisition): RedirectResponse
    {
        $this->authorize('delete', $purchaseRequisition);
        $this->service->delete($purchaseRequisition, request()->user());

        return back()->with('success', __('procurement.purchase_requisitions.messages.deleted'));
    }

    /**
     * Jóváhagyja a megadott beszerzési igényt.
     *
     * Az ApprovePurchaseRequisitionRequest ellenőrzi a jogosultságot. A
     * Service végrehajtja és auditnaplózza az állapotváltást.
     *
     * @param  ApprovePurchaseRequisitionRequest  $request  Az engedélyezett kérés.
     * @param  PurchaseRequisition  $purchaseRequisition  A jóváhagyandó igény.
     * @return RedirectResponse Visszairányítás sikeres jóváhagyási üzenettel.
     */
    public function approve(ApprovePurchaseRequisitionRequest $request, PurchaseRequisition $purchaseRequisition): RedirectResponse
    {
        $this->service->approve($purchaseRequisition, $request->user());

        return back()->with('success', __('procurement.purchase_requisitions.messages.approved'));
    }

    /**
     * Beszerzési rendelést készít a jóváhagyott igényből.
     *
     * A GeneratePurchaseOrderRequest validál és engedélyez. A Service
     * tranzakcióban létrehozza és auditnaplózza a rendelést.
     *
     * @param  GeneratePurchaseOrderRequest  $request  A validált és engedélyezett kérés.
     * @param  PurchaseRequisition  $purchaseRequisition  A feldolgozandó igény.
     * @return RedirectResponse Átirányítás a létrehozott rendelés adatlapjára.
     */
    public function generatePurchaseOrder(GeneratePurchaseOrderRequest $request, PurchaseRequisition $purchaseRequisition): RedirectResponse
    {
        $purchaseOrder = $this->service->generatePurchaseOrder(
            $purchaseRequisition,
            (int) $request->validated('supplier_id'),
            $request->validated('expected_delivery_date'),
            $request->user()
        );

        return redirect()->route('admin.purchase-orders.show', $purchaseOrder)->with('success', __('procurement.purchase_requisitions.messages.purchase_order_generated'));
    }

    /**
     * Beszerzési igényt generál a nyitott anyagszükségletekből.
     *
     * A PurchaseRequisitionPolicy `create` művelete engedélyez. A Service
     * összegyűjti, létrehozza és auditnaplózza az igényt, majd annak lapjára irányít.
     *
     * @param  IndexRequest  $request  A hitelesített felhasználót tartalmazó kérés.
     * @return RedirectResponse Átirányítás a létrehozott igény adatlapjára.
     */
    public function generateFromMaterialRequirements(IndexRequest $request): RedirectResponse
    {
        $this->authorize('create', PurchaseRequisition::class);
        $requisition = $this->service->generateFromMaterialRequirements($request->user());

        return redirect()->route('admin.purchase-requisitions.show', $requisition)->with('success', __('procurement.purchase_requisitions.messages.generated'));
    }

    /**
     * Összeállítja a beszerzési igényállapotok választási listáját.
     *
     * @return list<array{label: string, value: string}> A lokalizált állapotopciók.
     */
    private function statusOptions(): array
    {
        return collect(PurchaseRequisitionStatus::cases())
            ->map(fn (PurchaseRequisitionStatus $status): array => [
                'label' => __("status.{$status->value}"),
                'value' => $status->value,
            ])
            ->values()
            ->all();
    }

    /**
     * Összeállítja az igényhez választható cikkek listáját.
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
     * Összeállítja az aktív beszállítók választási listáját.
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
}
