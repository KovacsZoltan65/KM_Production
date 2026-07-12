<?php

namespace App\Services\Admin;

use App\Repositories\Contracts\ManufacturingIntelligenceRepositoryInterface;
use Illuminate\Support\Facades\Cache;

/** A beszállítói teljesítménymutatók gyorsítótárazott lekérdezését biztosítja. */
class SupplierPerformanceService
{
    public function __construct(private readonly ManufacturingIntelligenceRepositoryInterface $repository) {}

    /** @return array{rows: list<array<string, mixed>>} A beszállítói teljesítményadatok. */
    public function analyze(): array
    {
        return Cache::remember('intelligence.supplier_performance', now()->addMinutes(5), fn (): array => $this->repository->supplierPerformance());
    }
}
