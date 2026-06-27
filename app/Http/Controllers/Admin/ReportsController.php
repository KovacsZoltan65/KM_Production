<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CustomerOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexRequest;
use App\Models\Customer;
use App\Services\Admin\ReportingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportsController extends Controller
{
    public function __construct(private readonly ReportingService $service) {}

    /**
     * @param IndexRequest $request
     * @return Response
     */
    public function customerOrders(IndexRequest $request): Response
    {
        $this->authorizeReports($request);

        return Inertia::render('Admin/Reports/CustomerOrders', [
            'report' => $this->service->customerOrdersSummary($request->filters()),
            'filters' => $request->filters(),
            'statusOptions' => $this->customerOrderStatusOptions(),
            'customerOptions' => $this->customerOptions(),
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function production(Request $request): Response
    {
        $this->authorizeReports($request);

        return Inertia::render('Admin/Reports/Production', [
            'report' => $this->service->productionSummary(),
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function inventory(Request $request): Response
    {
        $this->authorizeReports($request);

        return Inertia::render('Admin/Reports/Inventory', [
            'report' => $this->service->inventorySummary(),
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function procurement(Request $request): Response
    {
        $this->authorizeReports($request);

        return Inertia::render('Admin/Reports/Procurement', [
            'report' => $this->service->procurementSummary(),
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function quality(Request $request): Response
    {
        $this->authorizeReports($request);

        return Inertia::render('Admin/Reports/Quality', [
            'report' => $this->service->qualitySummary(),
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function shopFloor(Request $request): Response
    {
        $this->authorizeReports($request);

        return Inertia::render('Admin/Reports/ShopFloor', [
            'report' => $this->service->shopFloorSummary(),
        ]);
    }

    /**
     * Summary of authorizeReports
     * @param Request $request
     * @return void
     */
    private function authorizeReports(Request $request): void
    {
        abort_unless($request->user()?->can('reports.view'), 403);
    }

    /**
     * @return array[]
     */
    private function customerOrderStatusOptions(): array
    {
        return collect(CustomerOrderStatus::cases())
            ->map(fn (CustomerOrderStatus $status): array => [
                'label' => str($status->value)->replace('_', ' ')->title()->toString(),
                'value' => $status->value,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array[]
     */
    private function customerOptions(): array
    {
        return Customer::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Customer $customer): array => [
                'id' => $customer->id,
                'label' => $customer->name,
            ])
            ->all();
    }
}
