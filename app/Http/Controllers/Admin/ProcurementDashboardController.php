<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseRequisition;
use App\Services\Admin\ProcurementDashboardService;
use Inertia\Inertia;
use Inertia\Response;

class ProcurementDashboardController extends Controller
{
    public function __construct(private readonly ProcurementDashboardService $service) {}

    public function __invoke(): Response
    {
        $this->authorize('viewAny', PurchaseRequisition::class);

        return Inertia::render('Admin/Procurement/Dashboard', [
            'metrics' => $this->service->metrics(),
        ]);
    }
}
