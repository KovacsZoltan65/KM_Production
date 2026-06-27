<?php

namespace App\Services\Admin;

use App\Repositories\Contracts\ManufacturingIntelligenceRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class MaterialForecastService
{
    public function __construct(private readonly ManufacturingIntelligenceRepositoryInterface $repository) {}

    public function forecast(): array
    {
        return Cache::remember('intelligence.material_forecast', now()->addMinutes(5), fn (): array => $this->repository->materialForecast());
    }
}
