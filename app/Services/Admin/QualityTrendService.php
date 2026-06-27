<?php

namespace App\Services\Admin;

use App\Repositories\Contracts\ManufacturingIntelligenceRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class QualityTrendService
{
    public function __construct(private readonly ManufacturingIntelligenceRepositoryInterface $repository) {}

    public function analyze(): array
    {
        return Cache::remember('intelligence.quality_trends', now()->addMinutes(5), fn (): array => $this->repository->qualityTrends());
    }
}
