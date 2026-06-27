<?php

namespace App\Services\Admin;

use App\Repositories\Contracts\ManufacturingIntelligenceRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class ProductionRiskService
{
    public function __construct(private readonly ManufacturingIntelligenceRepositoryInterface $repository) {}

    public function score(): array
    {
        return Cache::remember('intelligence.risks', now()->addMinutes(5), fn (): array => $this->repository->productionRisks());
    }
}
