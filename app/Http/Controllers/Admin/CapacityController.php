<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ScheduleProductionOrderRequest;
use App\Http\Requests\Admin\SimulateCapacityRequest;
use App\Models\CustomerOrder;
use App\Models\ProductionOrder;
use App\Services\Admin\CapacityPlanningService;
use App\Services\Admin\LeadTimeEstimator;
use App\Services\Admin\SchedulingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CapacityController extends Controller
{
    public function __construct(
        private readonly CapacityPlanningService $capacity,
        private readonly SchedulingService $scheduler,
        private readonly LeadTimeEstimator $estimator,
    ) {}

    public function dashboard(Request $request): Response
    {
        $request->user()?->can('capacity.view') ?: abort(403);

        return Inertia::render('Admin/Capacity/Dashboard', $this->capacity->dashboard());
    }

    public function factoryUnits(Request $request): Response
    {
        $request->user()?->can('capacity.view') ?: abort(403);

        return Inertia::render('Admin/Capacity/FactoryUnits', [
            'loads' => $this->capacity->factoryUnitLoads(),
        ]);
    }

    public function employees(Request $request): Response
    {
        $request->user()?->can('capacity.view') ?: abort(403);

        return Inertia::render('Admin/Capacity/Employees', [
            'loads' => $this->capacity->employeeLoads(),
        ]);
    }

    public function schedule(Request $request): Response
    {
        $request->user()?->can('capacity.view') ?: abort(403);

        return Inertia::render('Admin/Capacity/Schedule', [
            'rows' => $this->capacity->scheduleRows(),
            'productionOrders' => $this->capacity->productionOrderOptions(),
            'canPlan' => $request->user()?->can('capacity.plan') ?? false,
        ]);
    }

    public function storeSchedule(ScheduleProductionOrderRequest $request): RedirectResponse
    {
        if ($request->boolean('override') && ! ($request->user()?->can('capacity.override') ?? false)) {
            abort(403);
        }

        $productionOrder = ProductionOrder::query()->findOrFail($request->integer('production_order_id'));

        $this->scheduler->schedule($productionOrder, $request->boolean('override'));

        return back()->with('success', 'Capacity schedule generated.');
    }

    public function simulate(Request $request): Response
    {
        $request->user()?->can('capacity.view') ?: abort(403);

        return Inertia::render('Admin/Capacity/Simulation', [
            'customerOrders' => $this->capacity->customerOrderOptions(),
            'result' => null,
        ]);
    }

    public function runSimulation(SimulateCapacityRequest $request): Response
    {
        $customerOrder = CustomerOrder::query()->findOrFail($request->integer('customer_order_id'));

        return Inertia::render('Admin/Capacity/Simulation', [
            'customerOrders' => $this->capacity->customerOrderOptions(),
            'selectedCustomerOrderId' => $customerOrder->id,
            'result' => $this->estimator->estimate($customerOrder, audit: true),
        ]);
    }
}
