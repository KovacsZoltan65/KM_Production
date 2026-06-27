<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexRequest;
use App\Http\Requests\Admin\StoreEmployeeRequest;
use App\Http\Requests\Admin\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Models\ProfessionalRole;
use App\Models\User;
use App\Services\Admin\EmployeeAdminService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function __construct(private readonly EmployeeAdminService $service) {}

    /**
     * @param IndexRequest $request
     * @return Response
     */
    public function index(IndexRequest $request): Response
    {
        $this->authorize('viewAny', Employee::class);

        return Inertia::render('Admin/Employees/Index', [
            'records' => $this->service->paginateForAdminIndex($request->filters(), $request->perPage()),
            'filters' => $request->filters(),
            'options' => [
                'professionalRoles' => ProfessionalRole::query()->orderBy('name')->get(['id', 'name', 'code']),
                'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
            ],
        ]);
    }

    /**
     * @param StoreEmployeeRequest $request
     * @return RedirectResponse
     */
    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return back()->with('success', 'Employee created.');
    }

    /**
     * @param UpdateEmployeeRequest $request
     * @param Employee $employee
     * @return RedirectResponse
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $this->service->update($employee, $request->validated(), $request->user());

        return back()->with('success', 'Employee updated.');
    }

    /**
     * @param Employee $employee
     * @return RedirectResponse
     */
    public function destroy(Employee $employee): RedirectResponse
    {
        $this->authorize('delete', $employee);
        $this->service->delete($employee, request()->user());

        return back()->with('success', 'Employee deleted.');
    }
}
