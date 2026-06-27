<?php

namespace App\Services\Admin;

use App\Repositories\Contracts\ManufacturingIntelligenceRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class ManufacturingIntelligenceService
{
    public function __construct(
        private readonly BottleneckAnalysisService $bottlenecks,
        private readonly MaterialForecastService $materialForecast,
        private readonly SupplierPerformanceService $supplierPerformance,
        private readonly QualityTrendService $qualityTrends,
        private readonly ProductionRiskService $productionRisks,
        private readonly ProcurementRecommendationService $recommendations,
        private readonly ManufacturingIntelligenceRepositoryInterface $repository,
    ) {}

    public function dashboard(): array
    {
        return Cache::remember('intelligence.dashboard', now()->addMinutes(5), fn (): array => [
            'high_risk_orders' => collect($this->productionRisks->score()['rows'])->where('risk_level', 'high')->values()->take(10)->all(),
            'bottleneck_factory_units' => collect($this->bottlenecks->analyze()['rows'])->whereIn('status', ['bottleneck', 'watch'])->values()->take(10)->all(),
            'materials_at_risk' => collect($this->materialForecast->forecast()['rows'])->whereIn('risk_level', ['critical', 'warning'])->values()->take(10)->all(),
            'slow_suppliers' => collect($this->supplierPerformance->analyze()['rows'])->filter(fn (array $row): bool => ($row['on_time_rate'] ?? 100) < 80)->values()->take(10)->all(),
            'quality_risk_products' => collect($this->qualityTrends->analyze()['rows'])->where('defect_rate', '>', 0)->sortByDesc('defect_rate')->values()->take(10)->all(),
            'recommended_purchases' => collect($this->recommendations->recommendations()['rows'])->take(10)->all(),
            'lead_time_accuracy' => $this->repository->leadTimeAccuracy(),
        ]);
    }
}
