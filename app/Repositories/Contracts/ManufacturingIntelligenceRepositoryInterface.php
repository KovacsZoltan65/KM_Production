<?php

namespace App\Repositories\Contracts;

interface ManufacturingIntelligenceRepositoryInterface
{
    public function bottlenecks(): array;

    public function materialForecast(): array;

    public function supplierPerformance(): array;

    public function qualityTrends(): array;

    public function productionRisks(): array;

    public function procurementRecommendations(): array;

    public function leadTimeAccuracy(): array;
}
