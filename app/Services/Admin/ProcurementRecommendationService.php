<?php

namespace App\Services\Admin;

use App\Repositories\Contracts\ManufacturingIntelligenceRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class ProcurementRecommendationService
{
    public function __construct(private readonly ManufacturingIntelligenceRepositoryInterface $repository) {}

    public function recommendations(): array
    {
        return Cache::remember('intelligence.recommendations', now()->addMinutes(5), fn (): array => $this->repository->procurementRecommendations());
    }
}
