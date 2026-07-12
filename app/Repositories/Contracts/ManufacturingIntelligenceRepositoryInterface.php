<?php

namespace App\Repositories\Contracts;

/**
 * A gyártási intelligencia aggregált adatkészleteinek lekérdezési szerződése.
 */
interface ManufacturingIntelligenceRepositoryInterface
{
    /** @return array{rows: list<array<string, mixed>>} A kapacitási szűk keresztmetszetek. */
    public function bottlenecks(): array;

    /** @return array{rows: list<array<string, mixed>>} Az anyagkészlet-előrejelzés sorai. */
    public function materialForecast(): array;

    /** @return array{rows: list<array<string, mixed>>} A beszállítói teljesítmény sorai. */
    public function supplierPerformance(): array;

    /** @return array{rows: list<array<string, mixed>>} A minőségügyi trendek sorai. */
    public function qualityTrends(): array;

    /** @return array{rows: list<array<string, mixed>>} A gyártási kockázatok sorai. */
    public function productionRisks(): array;

    /** @return array{rows: list<array<string, mixed>>} A beszerzési javaslatok sorai. */
    public function procurementRecommendations(): array;

    /**
     * Összesíti a tervezett és tényleges gyártási befejezések pontosságát.
     *
     * @return array<string, bool|float|int|string> Az átfutásipontosság mutatói.
     */
    public function leadTimeAccuracy(): array;
}
