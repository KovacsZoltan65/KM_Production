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

    public function show(CustomerOrder $customerOrder): Response
    {
        $this->authorize('view', $customerOrder);

        return Inertia::render('Admin/CustomerOrders/Show', [
            'customerOrder' => $this->service->findForShow($customerOrder),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function store(StoreCustomerOrderRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return back()->with('success', __('orders.messages.created'));
    }

    public function update(UpdateCustomerOrderRequest $request, CustomerOrder $customerOrder): RedirectResponse
    {
        $this->service->update($customerOrder, $request->validated(), $request->user());

        return back()->with('success', __('orders.messages.updated'));
    }

    public function destroy(CustomerOrder $customerOrder): RedirectResponse
    {
        $this->authorize('delete', $customerOrder);
        $this->service->delete($customerOrder, request()->user());

        return back()->with('success', __('orders.messages.deleted'));
    }

    public function confirm(ConfirmCustomerOrderRequest $request, CustomerOrder $customerOrder): RedirectResponse
    {
        $this->service->confirm($customerOrder, $request->user());

        return back()->with('success', __('orders.messages.confirmed'));
    }

    public function cancel(CancelCustomerOrderRequest $request, CustomerOrder $customerOrder): RedirectResponse
    {
        $this->service->cancel($customerOrder, $request->user());

        return back()->with('success', __('orders.messages.cancelled'));
    }

    /**
     * @return Collection<int, array{id: int, code: string, name: string, label: string}>
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
     * @return Collection<int, array{id: int, item_number: string, name: string, unit: string, label: string}>
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
     * @return array<int, array{label: string, value: string}>
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
