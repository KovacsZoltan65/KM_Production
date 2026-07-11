<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\StockBalance;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', StockBalance::class);

        return Inertia::render('Admin/Inventory/Index');
    }
}
