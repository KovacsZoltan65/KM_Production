<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Services\Admin\UserAdminService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(private readonly UserAdminService $service) {}

    public function index(IndexRequest $request): Response
    {
        $this->authorize('viewAny', User::class);

        return Inertia::render('Admin/Users/Index', [
            'records' => $this->service
                ->paginateForAdminIndex($request->filters(), $request->perPage())
                ->through(fn (User $user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'roles' => $user->roles->pluck('name')->values()->all(),
                    'created_at' => $user->created_at,
                ]),
            'filters' => $request->filters(),
            'options' => [
                'roles' => Role::query()->orderBy('name')->pluck('name')->all(),
            ],
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return back()->with('success', __('messages.created'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->service->update($user, $request->validated(), $request->user());

        return back()->with('success', __('messages.updated'));
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);
        $this->service->delete($user, request()->user());

        return back()->with('success', __('messages.deleted'));
    }
}
