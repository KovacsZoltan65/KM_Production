<?php

namespace App\Services\Admin;

use App\Repositories\Contracts\ManufacturingIntelligenceRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class SupplierPerformanceService
{
    public function __construct(private readonly ManufacturingIntelligenceRepositoryInterface $repository) {}

    public function analyze(): array
    {
        return Cache::remember('intelligence.supplier_performance', now()->addMinutes(5), fn (): array => $this->repository->supplierPerformance());
    }
}
