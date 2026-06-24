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

    public function show(GoodsReceipt $goodsReceipt): Response
    {
        $this->authorize('view', $goodsReceipt);

        return Inertia::render('Admin/GoodsReceipts/Show', [
            'goodsReceipt' => $this->service->findForShow($goodsReceipt),
        ]);
    }

    public function store(StoreGoodsReceiptRequest $request): RedirectResponse
    {
        $goodsReceipt = $this->service->create($request->validated(), $request->user());

        return redirect()->route('admin.goods-receipts.show', $goodsReceipt)->with('success', 'Goods receipt created.');
    }

    public function post(PostGoodsReceiptRequest $request, GoodsReceipt $goodsReceipt): RedirectResponse
    {
        $this->service->post($goodsReceipt, $request->user());

        return back()->with('success', 'Goods receipt posted.');
    }

    private function statusOptions(): array
    {
        return collect(GoodsReceiptStatus::cases())
            ->map(fn (GoodsReceiptStatus $status): array => [
                'label' => str($status->value)->replace('_', ' ')->title()->toString(),
                'value' => $status->value,
            ])
            ->values()
            ->all();
    }

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
