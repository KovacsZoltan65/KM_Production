<?php

namespace App\Services\Admin;

use App\Repositories\Contracts\ManufacturingIntelligenceRepositoryInterface;
use Illuminate\Support\Facades\Cache;

/** Az anyaghiányokból képzett beszerzési javaslatokat szolgáltatja. */
class ProcurementRecommendationService
{
    public function __construct(private readonly ManufacturingIntelligenceRepositoryInterface $repository) {}

    /** @return array{rows: list<array<string, mixed>>} A gyorsítótárazott beszerzési javaslatok. */
    public function recommendations(): array
    {
        return Cache::remember('intelligence.recommendations', now()->addMinutes(5), fn (): array => $this->repository->procurementRecommendations());
    }
}
