<?php

namespace App\Repositories\Contracts;

interface ReportingRepositoryInterface
{
    /**
     * @return array<string, mixed>
     */
    public function dashboardSummary(): array;

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function customerOrdersSummary(array $filters = []): array;

    /**
     * @return array<string, mixed>
     */
    public function productionSummary(): array;

    /**
     * @return array<string, mixed>
     */
    public function inventorySummary(): array;

    /**
     * @return array<string, mixed>
     */
    public function procurementSummary(): array;

    /**
     * @return array<string, mixed>
     */
    public function qualitySummary(): array;

    /**
     * @return array<string, mixed>
     */
    public function shopFloorSummary(): array;
}
