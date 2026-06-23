<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexRequest;
use App\Http\Requests\Admin\StoreBomRequest;
use App\Http\Requests\Admin\UpdateBomRequest;
use App\Models\Bom;
use App\Models\Item;
use App\Services\Admin\BomAdminService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BomController extends Controller
{
    public function __construct(private readonly BomAdminService $service) {}

    public function index(IndexRequest $request): Response
    {
        $this->authorize('viewAny', Bom::class);

        return Inertia::render('Admin/Boms/Index', [
            'records' => $this->service->paginateForAdminIndex($request->filters(), $request->perPage()),
            'filters' => $request->filters(),
            'itemOptions' => Item::query()
                ->where('is_active', true)
                ->orderBy('item_number')
                ->get(['id', 'item_number', 'name', 'unit']),
        ]);
    }

    public function store(StoreBomRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return back()->with('success', 'BOM created.');
    }

    public function update(UpdateBomRequest $request, Bom $bom): RedirectResponse
    {
        $this->service->update($bom, $request->validated(), $request->user());

        return back()->with('success', 'BOM updated.');
    }

    public function destroy(Bom $bom): RedirectResponse
    {
        $this->authorize('delete', $bom);
        $this->service->delete($bom, request()->user());

        return back()->with('success', 'BOM deleted.');
    }
}
