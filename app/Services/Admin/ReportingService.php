<?php

namespace App\Services\Admin;

use App\Repositories\Contracts\ReportingRepositoryInterface;
use App\Support\Cache\BusinessCacheKey;
use Illuminate\Support\Facades\Cache;

class ReportingService
{
    public function __construct(private readonly ReportingRepositoryInterface $reports) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function customerOrdersSummary(array $filters = []): array
    {
        return Cache::remember(
            BusinessCacheKey::customerOrdersReport($filters),
            now()->addSeconds(60),
            fn (): array => $this->reports->customerOrdersSummary($filters),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function productionSummary(): array
    {
        return Cache::remember(BusinessCacheKey::productionReport(), now()->addSeconds(60), fn (): array => $this->reports->productionSummary());
    }

    /**
     * @return array<string, mixed>
     */
    public function inventorySummary(): array
    {
        return Cache::remember(BusinessCacheKey::inventoryReport(), now()->addSeconds(60), fn (): array => $this->reports->inventorySummary());
    }

    /**
     * @return array<string, mixed>
     */
    public function procurementSummary(): array
    {
        return Cache::remember(BusinessCacheKey::procurementReport(), now()->addSeconds(60), fn (): array => $this->reports->procurementSummary());
    }

    /**
     * @return array<string, mixed>
     */
    public function qualitySummary(): array
    {
        return Cache::remember(BusinessCacheKey::qualityReport(), now()->addSeconds(60), fn (): array => $this->reports->qualitySummary());
    }

    /**
     * @return array<string, mixed>
     */
    public function shopFloorSummary(): array
    {
        return Cache::remember(BusinessCacheKey::shopFloorReport(), now()->addSeconds(60), fn (): array => $this->reports->shopFloorSummary());
    }
}
