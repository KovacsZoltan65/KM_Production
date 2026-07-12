<?php

namespace App\Services\Admin;

use App\Repositories\Contracts\ManufacturingIntelligenceRepositoryInterface;
use Illuminate\Support\Facades\Cache;

/** A kapacitási szűk keresztmetszetek gyorsítótárazott lekérdezését biztosítja. */
class BottleneckAnalysisService
{
    public function __construct(private readonly ManufacturingIntelligenceRepositoryInterface $repository) {}

    /** @return array{rows: list<array<string, mixed>>} A szűk keresztmetszeti mutatók. */
    public function analyze(): array
    {
        return Cache::remember('intelligence.bottlenecks', now()->addMinutes(5), fn (): array => $this->repository->bottlenecks());
    }
}
