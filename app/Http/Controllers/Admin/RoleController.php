<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexRequest;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Services\Admin\RoleAdminService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(private readonly RoleAdminService $service) {}

    public function index(IndexRequest $request): Response
    {
        $this->authorize('viewAny', Role::class);

        return Inertia::render('Admin/Roles/Index', [
            'records' => $this->service
                ->paginateForAdminIndex($request->filters(), $request->perPage())
                ->through(fn (Role $role): array => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'permissions' => $role->permissions->pluck('name')->values()->all(),
                    'created_at' => $role->created_at,
                ]),
            'filters' => $request->filters(),
            'options' => [
                'permissions' => Permission::query()->orderBy('name')->pluck('name')->all(),
            ],
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return back()->with('success', 'Role created.');
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->service->update($role, $request->validated(), $request->user());

        return back()->with('success', 'Role updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);
        $this->service->delete($role, request()->user());

        return back()->with('success', 'Role deleted.');
    }
}
