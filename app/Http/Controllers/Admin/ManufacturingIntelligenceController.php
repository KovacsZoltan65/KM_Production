<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\BottleneckAnalysisService;
use App\Services\Admin\ManufacturingIntelligenceService;
use App\Services\Admin\MaterialForecastService;
use App\Services\Admin\ProcurementRecommendationService;
use App\Services\Admin\ProductionRiskService;
use App\Services\Admin\QualityTrendService;
use App\Services\Admin\SupplierPerformanceService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ManufacturingIntelligenceController extends Controller
{
    public function __construct(
        private readonly ManufacturingIntelligenceService $intelligence,
        private readonly BottleneckAnalysisService $bottlenecks,
        private readonly MaterialForecastService $materialForecast,
        private readonly SupplierPerformanceService $supplierPerformance,
        private readonly QualityTrendService $qualityTrends,
        private readonly ProductionRiskService $risks,
        private readonly ProcurementRecommendationService $recommendations,
    ) {}

    public function dashboard(Request $request): Response
    {
        $this->authorizeView($request);

        return Inertia::render('Admin/Intelligence/Dashboard', [
            'dashboard' => $this->intelligence->dashboard(),
        ]);
    }

    public function bottlenecks(Request $request): Response
    {
        $this->authorizeView($request);

        return Inertia::render('Admin/Intelligence/Bottlenecks', [
            'analysis' => $this->bottlenecks->analyze(),
        ]);
    }

    public function materialForecast(Request $request): Response
    {
        $this->authorizeView($request);

        return Inertia::render('Admin/Intelligence/MaterialForecast', [
            'forecast' => $this->materialForecast->forecast(),
        ]);
    }

    public function supplierPerformance(Request $request): Response
    {
        $this->authorizeView($request);

        return Inertia::render('Admin/Intelligence/SupplierPerformance', [
            'performance' => $this->supplierPerformance->analyze(),
        ]);
    }

    public function qualityTrends(Request $request): Response
    {
        $this->authorizeView($request);

        return Inertia::render('Admin/Intelligence/QualityTrends', [
            'trends' => $this->qualityTrends->analyze(),
        ]);
    }

    public function risks(Request $request): Response
    {
        $this->authorizeView($request);

        return Inertia::render('Admin/Intelligence/Risks', [
            'risks' => $this->risks->score(),
        ]);
    }

    public function recommendations(Request $request): Response
    {
        abort_unless($request->user()?->can('intelligence.recommendations'), 403);

        return Inertia::render('Admin/Intelligence/Recommendations', [
            'recommendations' => $this->recommendations->recommendations(),
        ]);
    }

    private function authorizeView(Request $request): void
    {
        abort_unless($request->user()?->can('intelligence.view'), 403);
    }
}
