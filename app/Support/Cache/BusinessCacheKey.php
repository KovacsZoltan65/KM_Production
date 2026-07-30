<?php

namespace App\Support\Cache;

use Illuminate\Support\Facades\Cache;

final class BusinessCacheKey
{
    private const PREFIX = 'km-production';

    public static function dashboardSummary(): string
    {
        return self::make(BusinessCacheDomain::Dashboard, 'summary');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public static function customerOrdersReport(array $filters = []): string
    {
        return self::make(BusinessCacheDomain::ReportsCustomerOrders, 'summary', $filters);
    }

    public static function productionReport(): string
    {
        return self::make(BusinessCacheDomain::ReportsProduction, 'summary');
    }

    public static function inventoryReport(): string
    {
        return self::make(BusinessCacheDomain::ReportsInventory, 'summary');
    }

    public static function procurementReport(): string
    {
        return self::make(BusinessCacheDomain::ReportsProcurement, 'summary');
    }

    public static function qualityReport(): string
    {
        return self::make(BusinessCacheDomain::ReportsQuality, 'summary');
    }

    public static function shopFloorReport(): string
    {
        return self::make(BusinessCacheDomain::ReportsShopFloor, 'summary');
    }

    public static function intelligenceDashboard(): string
    {
        return self::make(BusinessCacheDomain::IntelligenceDashboard, 'summary');
    }

    public static function bottleneckAnalysis(): string
    {
        return self::make(BusinessCacheDomain::IntelligenceBottlenecks, 'analysis');
    }

    public static function materialForecast(): string
    {
        return self::make(BusinessCacheDomain::IntelligenceMaterialForecast, 'forecast');
    }

    public static function supplierPerformance(): string
    {
        return self::make(BusinessCacheDomain::IntelligenceSupplierPerformance, 'analysis');
    }

    public static function qualityTrends(): string
    {
        return self::make(BusinessCacheDomain::IntelligenceQualityTrends, 'analysis');
    }

    public static function productionRisks(): string
    {
        return self::make(BusinessCacheDomain::IntelligenceRisks, 'analysis');
    }

    public static function procurementRecommendations(): string
    {
        return self::make(BusinessCacheDomain::IntelligenceRecommendations, 'analysis');
    }

    public static function capacityDashboard(): string
    {
        return self::make(BusinessCacheDomain::Capacity, 'dashboard');
    }

    public static function capacityFactoryUnits(): string
    {
        return self::make(BusinessCacheDomain::Capacity, 'factory-units');
    }

    public static function capacityEmployees(): string
    {
        return self::make(BusinessCacheDomain::Capacity, 'employees');
    }

    public static function capacitySchedule(): string
    {
        return self::make(BusinessCacheDomain::Capacity, 'schedule');
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public static function make(BusinessCacheDomain $domain, string $name, array $parameters = []): string
    {
        $normalized = self::normalize($parameters);
        $suffix = $normalized === []
            ? ''
            : ':'.hash('sha256', json_encode($normalized, JSON_THROW_ON_ERROR));

        return sprintf(
            '%s:%s:g%d:%s%s',
            self::PREFIX,
            $domain->value,
            self::generation($domain),
            $name,
            $suffix,
        );
    }

    public static function generationKey(BusinessCacheDomain $domain): string
    {
        return sprintf('%s:cache-generation:%s', self::PREFIX, $domain->value);
    }

    public static function generation(BusinessCacheDomain $domain): int
    {
        $generation = Cache::get(self::generationKey($domain), 1);

        return is_int($generation) && $generation > 0 ? $generation : 1;
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    private static function normalize(array $parameters): array
    {
        $normalized = [];

        foreach ($parameters as $key => $value) {
            $normalized[(string) $key] = self::normalizeValue($value);
        }

        ksort($normalized);

        return $normalized;
    }

    private static function normalizeValue(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            $normalized = array_map(self::normalizeValue(...), $value);
            usort(
                $normalized,
                static fn (mixed $left, mixed $right): int => strcmp(
                    json_encode($left, JSON_THROW_ON_ERROR),
                    json_encode($right, JSON_THROW_ON_ERROR),
                ),
            );

            return $normalized;
        }

        return self::normalize($value);
    }
}
