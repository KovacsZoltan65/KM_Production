<?php

namespace App\Services\Admin;

use App\Repositories\Contracts\ReportingRepositoryInterface;
use App\Support\Cache\BusinessCacheDomain;
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
            BusinessCacheKey::make(BusinessCacheDomain::ReportsCustomerOrders, 'summary', $filters),
            now()->addSeconds(60),
            fn (): array => $this->reports->customerOrdersSummary($filters),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function productionSummary(): array
    {
        return Cache::remember(BusinessCacheKey::make(BusinessCacheDomain::ReportsProduction, 'summary'), now()->addSeconds(60), fn (): array => $this->reports->productionSummary());
    }

    /**
     * @return array<string, mixed>
     */
    public function inventorySummary(): array
    {
        return Cache::remember(BusinessCacheKey::make(BusinessCacheDomain::ReportsInventory, 'summary'), now()->addSeconds(60), fn (): array => $this->reports->inventorySummary());
    }

    /**
     * @return array<string, mixed>
     */
    public function procurementSummary(): array
    {
        return Cache::remember(BusinessCacheKey::make(BusinessCacheDomain::ReportsProcurement, 'summary'), now()->addSeconds(60), fn (): array => $this->reports->procurementSummary());
    }

    /**
     * @return array<string, mixed>
     */
    public function qualitySummary(): array
    {
        return Cache::remember(BusinessCacheKey::make(BusinessCacheDomain::ReportsQuality, 'summary'), now()->addSeconds(60), fn (): array => $this->reports->qualitySummary());
    }

    /**
     * @return array<string, mixed>
     */
    public function shopFloorSummary(): array
    {
        return Cache::remember(BusinessCacheKey::make(BusinessCacheDomain::ReportsShopFloor, 'summary'), now()->addSeconds(60), fn (): array => $this->reports->shopFloorSummary());
    }
}
