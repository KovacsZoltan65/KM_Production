<?php

namespace App\Services\Admin;

use App\Repositories\Contracts\ManufacturingIntelligenceRepositoryInterface;
use App\Support\Cache\BusinessCacheDomain;
use App\Support\Cache\BusinessCacheKey;
use Illuminate\Support\Facades\Cache;

/** Az anyagkészlet-kockázatok gyorsítótárazott előrejelzését biztosítja. */
class MaterialForecastService
{
    public function __construct(private readonly ManufacturingIntelligenceRepositoryInterface $repository) {}

    /** @return array{rows: list<array<string, mixed>>} Az anyagkészlet-előrejelzés. */
    public function forecast(): array
    {
        return Cache::remember(BusinessCacheKey::make(BusinessCacheDomain::IntelligenceMaterialForecast, 'forecast'), now()->addMinutes(5), fn (): array => $this->repository->materialForecast());
    }
}
