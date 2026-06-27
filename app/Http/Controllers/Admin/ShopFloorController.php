<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\ShopFloorService;
use Inertia\Inertia;
use Inertia\Response;

class ShopFloorController extends Controller
{
    public function __construct(private readonly ShopFloorService $service) {}

    /**
     * @return Response
     */
    public function index(): Response
    {
        abort_unless(request()->user()->can('shop-floor.view'), 403);

        return Inertia::render('Admin/ShopFloor/Index', [
            'tasks' => $this->service->tasks(),
        ]);
    }

    /**
     * @return Response
     */
    public function myTasks(): Response
    {
        abort_unless(request()->user()->can('shop-floor.view'), 403);

        return Inertia::render('Admin/ShopFloor/MyTasks', [
            'tasks' => $this->service->myTasks(request()->user()),
        ]);
    }
}
