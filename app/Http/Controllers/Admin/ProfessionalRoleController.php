<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexRequest;
use App\Http\Requests\Admin\StoreProfessionalRoleRequest;
use App\Http\Requests\Admin\UpdateProfessionalRoleRequest;
use App\Models\ProfessionalRole;
use App\Services\Admin\ProfessionalRoleAdminService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProfessionalRoleController extends Controller
{
    public function __construct(private readonly ProfessionalRoleAdminService $service) {}

    public function index(IndexRequest $request): Response
    {
        $this->authorize('viewAny', ProfessionalRole::class);

        return Inertia::render('Admin/ProfessionalRoles/Index', [
            'records' => $this->service->paginateForAdminIndex($request->filters(), $request->perPage()),
            'filters' => $request->filters(),
        ]);
    }

    public function store(StoreProfessionalRoleRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return back()->with('success', __('messages.created'));
    }

    public function update(UpdateProfessionalRoleRequest $request, ProfessionalRole $professionalRole): RedirectResponse
    {
        $this->service->update($professionalRole, $request->validated(), $request->user());

        return back()->with('success', __('messages.updated'));
    }

    public function destroy(ProfessionalRole $professionalRole): RedirectResponse
    {
        $this->authorize('delete', $professionalRole);
        $this->service->delete($professionalRole, request()->user());

        return back()->with('success', __('messages.deleted'));
    }
}
