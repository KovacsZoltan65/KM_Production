<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LocationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexRequest;
use App\Http\Requests\Admin\StoreLocationRequest;
use App\Http\Requests\Admin\UpdateLocationRequest;
use App\Models\FactoryUnit;
use App\Models\Location;
use App\Services\Admin\LocationAdminService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LocationController extends Controller
{
    public function __construct(private readonly LocationAdminService $service) {}

    public function index(IndexRequest $request): Response
    {
        $this->authorize('viewAny', Location::class);

        return Inertia::render('Admin/Locations/Index', [
            'records' => $this->service->paginateForAdminIndex($request->filters(), $request->perPage()),
            'filters' => $request->filters(),
            'options' => [
                'factoryUnits' => FactoryUnit::query()->orderBy('name')->get(['id', 'name', 'code']),
                'locationTypes' => collect(LocationType::cases())->map(fn (LocationType $type): array => [
                    'label' => str($type->value)->replace('_', ' ')->title()->toString(),
                    'value' => $type->value,
                ])->values(),
            ],
        ]);
    }

    public function store(StoreLocationRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return back()->with('success', 'Location created.');
    }

    public function update(UpdateLocationRequest $request, Location $location): RedirectResponse
    {
        $this->service->update($location, $request->validated(), $request->user());

        return back()->with('success', 'Location updated.');
    }

    public function destroy(Location $location): RedirectResponse
    {
        $this->authorize('delete', $location);
        $this->service->delete($location, request()->user());

        return back()->with('success', 'Location deleted.');
    }
}
