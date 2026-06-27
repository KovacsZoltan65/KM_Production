<?php

namespace App\Services\Admin;

use App\Repositories\Contracts\ReportingRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public function __construct(private readonly ReportingRepositoryInterface $reports) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        return Cache::remember('dashboard.summary', now()->addSeconds(60), fn (): array => $this->reports->dashboardSummary());
    }
}
