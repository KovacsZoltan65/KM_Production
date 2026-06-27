<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $service) {}

    public function __invoke(Request $request): Response
    {
        abort_unless($request->user()?->can('dashboard.view'), 403);

        return Inertia::render('Admin/Dashboard', [
            'summary' => $this->service->summary(),
        ]);
    }
}
