<?php

namespace App\Services\Admin;

use App\Repositories\Contracts\ManufacturingIntelligenceRepositoryInterface;
use App\Support\Cache\BusinessCacheDomain;
use App\Support\Cache\BusinessCacheKey;
use Illuminate\Support\Facades\Cache;

/** A vevői rendelések gyártási kockázatainak gyorsítótárazott értékelését adja. */
class ProductionRiskService
{
    public function __construct(private readonly ManufacturingIntelligenceRepositoryInterface $repository) {}

    /** @return array{rows: list<array<string, mixed>>} A pontozott gyártási kockázatok. */
    public function score(): array
    {
        return Cache::remember(BusinessCacheKey::make(BusinessCacheDomain::IntelligenceRisks, 'analysis'), now()->addMinutes(5), fn (): array => $this->repository->productionRisks());
    }
}
