<?php

namespace App\Services\Admin;

use App\Repositories\Contracts\ManufacturingIntelligenceRepositoryInterface;
use App\Support\Cache\BusinessCacheDomain;
use App\Support\Cache\BusinessCacheKey;
use Illuminate\Support\Facades\Cache;

/** Az anyaghiányokból képzett beszerzési javaslatokat szolgáltatja. */
class ProcurementRecommendationService
{
    public function __construct(private readonly ManufacturingIntelligenceRepositoryInterface $repository) {}

    /** @return array{rows: list<array<string, mixed>>} A gyorsítótárazott beszerzési javaslatok. */
    public function recommendations(): array
    {
        return Cache::remember(BusinessCacheKey::make(BusinessCacheDomain::IntelligenceRecommendations, 'analysis'), now()->addMinutes(5), fn (): array => $this->repository->procurementRecommendations());
    }
}
