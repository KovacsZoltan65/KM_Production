<?php

namespace App\Services;

use App\Support\Cache\BusinessCacheDomain;
use App\Support\Cache\BusinessCacheKey;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class BusinessCacheInvalidator
{
    public function customerOrdersChanged(): void
    {
        $this->invalidate([
            BusinessCacheDomain::Dashboard,
            BusinessCacheDomain::ReportsCustomerOrders,
            BusinessCacheDomain::IntelligenceDashboard,
            BusinessCacheDomain::IntelligenceRisks,
            BusinessCacheDomain::IntelligenceRecommendations,
            BusinessCacheDomain::Capacity,
        ]);
    }

    public function productionChanged(): void
    {
        $this->invalidate([
            BusinessCacheDomain::Dashboard,
            BusinessCacheDomain::ReportsProduction,
            BusinessCacheDomain::ReportsShopFloor,
            BusinessCacheDomain::IntelligenceDashboard,
            BusinessCacheDomain::IntelligenceBottlenecks,
            BusinessCacheDomain::IntelligenceQualityTrends,
            BusinessCacheDomain::IntelligenceRisks,
            BusinessCacheDomain::Capacity,
        ]);
    }

    public function inventoryChanged(): void
    {
        $this->invalidate([
            BusinessCacheDomain::Dashboard,
            BusinessCacheDomain::ReportsInventory,
            BusinessCacheDomain::IntelligenceDashboard,
            BusinessCacheDomain::IntelligenceMaterialForecast,
            BusinessCacheDomain::IntelligenceRisks,
            BusinessCacheDomain::IntelligenceRecommendations,
        ]);
    }

    public function procurementChanged(): void
    {
        $this->invalidate([
            BusinessCacheDomain::Dashboard,
            BusinessCacheDomain::ReportsProcurement,
            BusinessCacheDomain::IntelligenceDashboard,
            BusinessCacheDomain::IntelligenceSupplierPerformance,
            BusinessCacheDomain::IntelligenceRisks,
            BusinessCacheDomain::IntelligenceRecommendations,
        ]);
    }

    public function qualityChanged(): void
    {
        $this->invalidate([
            BusinessCacheDomain::Dashboard,
            BusinessCacheDomain::ReportsQuality,
            BusinessCacheDomain::IntelligenceDashboard,
            BusinessCacheDomain::IntelligenceQualityTrends,
            BusinessCacheDomain::IntelligenceRisks,
        ]);
    }

    public function capacityChanged(): void
    {
        $this->invalidate([
            BusinessCacheDomain::Capacity,
            BusinessCacheDomain::IntelligenceDashboard,
            BusinessCacheDomain::IntelligenceBottlenecks,
            BusinessCacheDomain::IntelligenceRisks,
        ]);
    }

    public function workforceChanged(): void
    {
        $this->invalidate([
            BusinessCacheDomain::ReportsShopFloor,
            BusinessCacheDomain::Capacity,
            BusinessCacheDomain::IntelligenceDashboard,
            BusinessCacheDomain::IntelligenceBottlenecks,
        ]);
    }

    public function documentsChanged(): void
    {
        $this->invalidate([BusinessCacheDomain::Dashboard]);
    }

    /**
     * @param  list<BusinessCacheDomain>  $domains
     */
    public function invalidate(array $domains): void
    {
        if (DB::transactionLevel() > 0) {
            DB::afterCommit(fn () => $this->invalidateNow($domains));

            return;
        }

        $this->invalidateNow($domains);
    }

    /**
     * @param  list<BusinessCacheDomain>  $domains
     */
    private function invalidateNow(array $domains): void
    {
        foreach (array_values(array_unique($domains, SORT_REGULAR)) as $domain) {
            $key = BusinessCacheKey::generationKey($domain);
            Cache::add($key, 1, now()->addYears(10));
            $generation = Cache::increment($key);

            if (! is_int($generation)) {
                throw new RuntimeException("A(z) {$domain->value} cache-domain invalidációja sikertelen.");
            }
        }
    }
}
