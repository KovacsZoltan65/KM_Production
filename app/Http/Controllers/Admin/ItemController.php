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
     * Summary of store
     */
    public function store(StoreItemRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return back()->with('success', __('messages.created'));
    }

    /**
     * Summary of update
     */
    public function update(UpdateItemRequest $request, Item $item): RedirectResponse
    {
        $this->service->update($item, $request->validated(), $request->user());

        return back()->with('success', __('messages.updated'));
    }

    /**
     * Summary of destroy
     */
    public function destroy(Item $item): RedirectResponse
    {
        $this->authorize('delete', $item);
        $this->service->delete($item, request()->user());

        return back()->with('success', __('messages.deleted'));
    }
}
