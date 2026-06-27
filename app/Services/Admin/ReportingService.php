<?php

namespace App\Services\Admin;

use App\Repositories\Contracts\ReportingRepositoryInterface;
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
        return Cache::remember($this->cacheKey('reports.customer-orders', $filters), now()->addSeconds(60), fn (): array => $this->reports->customerOrdersSummary($filters));
    }

    /**
     * @return array<string, mixed>
     */
    public function productionSummary(): array
    {
        return Cache::remember('reports.production', now()->addSeconds(60), fn (): array => $this->reports->productionSummary());
    }

    /**
     * @return array<string, mixed>
     */
    public function inventorySummary(): array
    {
        return Cache::remember('reports.inventory', now()->addSeconds(60), fn (): array => $this->reports->inventorySummary());
    }

    /**
     * @return array<string, mixed>
     */
    public function procurementSummary(): array
    {
        return Cache::remember('reports.procurement', now()->addSeconds(60), fn (): array => $this->reports->procurementSummary());
    }

    /**
     * @return array<string, mixed>
     */
    public function qualitySummary(): array
    {
        return Cache::remember('reports.quality', now()->addSeconds(60), fn (): array => $this->reports->qualitySummary());
    }

    /**
     * @return array<string, mixed>
     */
    public function shopFloorSummary(): array
    {
        return Cache::remember('reports.shop-floor', now()->addSeconds(60), fn (): array => $this->reports->shopFloorSummary());
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function cacheKey(string $base, array $filters = []): string
    {
        $normalized = array_filter($filters, fn (mixed $value): bool => $value !== null && $value !== '');
        ksort($normalized);

        return $normalized === [] ? $base : $base.'.'.md5(json_encode($normalized, JSON_THROW_ON_ERROR));
    }
}
