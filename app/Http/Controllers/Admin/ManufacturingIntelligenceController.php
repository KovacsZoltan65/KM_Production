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

    /**
     * @param Request $request
     * @return Response
     */
    public function dashboard(Request $request): Response
    {
        $this->authorizeView($request);

        return Inertia::render('Admin/Intelligence/Dashboard', [
            'dashboard' => $this->intelligence->dashboard(),
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function bottlenecks(Request $request): Response
    {
        $this->authorizeView($request);

        return Inertia::render('Admin/Intelligence/Bottlenecks', [
            'analysis' => $this->bottlenecks->analyze(),
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function materialForecast(Request $request): Response
    {
        $this->authorizeView($request);

        return Inertia::render('Admin/Intelligence/MaterialForecast', [
            'forecast' => $this->materialForecast->forecast(),
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function supplierPerformance(Request $request): Response
    {
        $this->authorizeView($request);

        return Inertia::render('Admin/Intelligence/SupplierPerformance', [
            'performance' => $this->supplierPerformance->analyze(),
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function qualityTrends(Request $request): Response
    {
        $this->authorizeView($request);

        return Inertia::render('Admin/Intelligence/QualityTrends', [
            'trends' => $this->qualityTrends->analyze(),
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function risks(Request $request): Response
    {
        $this->authorizeView($request);

        return Inertia::render('Admin/Intelligence/Risks', [
            'risks' => $this->risks->score(),
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function recommendations(Request $request): Response
    {
        abort_unless($request->user()?->can('intelligence.recommendations'), 403);

        return Inertia::render('Admin/Intelligence/Recommendations', [
            'recommendations' => $this->recommendations->recommendations(),
        ]);
    }

    /**
     * @param Request $request
     * @return void
     */
    private function authorizeView(Request $request): void
    {
        abort_unless($request->user()?->can('intelligence.view'), 403);
    }
}
