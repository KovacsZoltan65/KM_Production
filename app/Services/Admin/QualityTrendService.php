<?php

namespace App\Services\Admin;

use App\Repositories\Contracts\ManufacturingIntelligenceRepositoryInterface;
use Illuminate\Support\Facades\Cache;

/** A gyártási minőségtrendek gyorsítótárazott lekérdezését biztosítja. */
class QualityTrendService
{
    public function __construct(private readonly ManufacturingIntelligenceRepositoryInterface $repository) {}

    /** @return array{rows: list<array<string, mixed>>} A minőségügyi trendadatok. */
    public function analyze(): array
    {
        return Cache::remember('intelligence.quality_trends', now()->addMinutes(5), fn (): array => $this->repository->qualityTrends());
    }
}
