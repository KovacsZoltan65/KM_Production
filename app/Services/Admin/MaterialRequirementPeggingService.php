<?php

namespace App\Services\Admin;

use App\Models\MaterialRequirement;
use App\Models\MaterialRequirementPeg;
use App\Repositories\Contracts\MaterialRequirementPegRepositoryInterface;
use App\Services\AuditLogService;
use App\Support\MaterialPlanning\MaterialRequirementNettingResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MaterialRequirementPeggingService
{
    public function __construct(
        private readonly MaterialRequirementNettingService $netting,
        private readonly MaterialRequirementPegRepositoryInterface $pegs,
        private readonly AuditLogService $audit,
    ) {}

    /** @return Collection<int, MaterialRequirementPeg> */
    public function recalculateForRequirement(MaterialRequirement $requirement): Collection
    {
        return $this->recalculateForRequirements(collect([$requirement]))
            ->where('material_requirement_id', $requirement->id)
            ->values();
    }

    /** @param Collection<int, MaterialRequirement> $requirements @return Collection<int, MaterialRequirementPeg> */
    public function recalculateForRequirements(Collection $requirements): Collection
    {
        $itemIds = $requirements->pluck('required_item_id')->map(fn (mixed $id): int => (int) $id)->unique()->values()->all();

        if ($itemIds === []) {
            return collect();
        }

        $ids = $this->pegs->requirementIdsForItems($itemIds);

        return DB::transaction(function () use ($ids): Collection {
            $locked = $this->pegs->lockRequirements($ids);
            $results = $this->netting->calculate($locked);
            $calculatedAt = now();
            $rows = [];
            $covered = 0;

            foreach ($results as $result) {
                $this->validateResult($result);

                foreach ($result->allocations as $allocation) {
                    $quantity = $this->toThousandths($allocation['quantity']);
                    $covered += $quantity;
                    $rows[] = [
                        'material_requirement_id' => $result->requirementId,
                        'stock_balance_id' => $allocation['source_type'] === 'stock_balance' ? $allocation['source_id'] : null,
                        'purchase_order_item_id' => $allocation['source_type'] === 'purchase_order_item' ? $allocation['source_id'] : null,
                        'quantity' => $allocation['quantity'],
                        'unit' => $result->unit,
                        'supply_at' => $allocation['supply_at'],
                        'calculated_at' => $calculatedAt,
                        'created_at' => $calculatedAt,
                        'updated_at' => $calculatedAt,
                    ];
                }
            }

            $this->pegs->replace($ids, $rows);
            $subject = $locked->first();
            $this->audit->log('material_requirement_pegging_recalculated', $subject, [
                'requirements_count' => $locked->count(),
                'pegs_count' => count($rows),
                'covered_quantity' => $this->fromThousandths($covered),
                'calculated_at' => $calculatedAt->toISOString(),
            ]);

            return MaterialRequirementPeg::query()
                ->whereIn('material_requirement_id', $ids)
                ->with(['stockBalance.location', 'purchaseOrderItem.purchaseOrder'])
                ->orderBy('material_requirement_id')
                ->orderByRaw('CASE WHEN stock_balance_id IS NULL THEN 1 ELSE 0 END')
                ->orderBy('supply_at')
                ->orderBy('id')
                ->get();
        });
    }

    private function validateResult(MaterialRequirementNettingResult $result): void
    {
        $stock = 0;
        $incoming = 0;

        foreach ($result->allocations as $allocation) {
            $quantity = $this->toThousandths($allocation['quantity']);
            if ($quantity <= 0) {
                throw ValidationException::withMessages(['pegging' => __('planning.pegging.validation.invalid_allocation')]);
            }

            $allocation['source_type'] === 'stock_balance' ? $stock += $quantity : $incoming += $quantity;
        }

        $gross = $this->toThousandths($result->grossRequirement);
        $net = $this->toThousandths($result->netRequirement);
        if ($stock !== $this->toThousandths($result->onHandCoverage)
            || $incoming !== $this->toThousandths($result->incomingCoverage)
            || $stock + $incoming + $net !== $gross) {
            throw ValidationException::withMessages(['pegging' => __('planning.pegging.validation.coverage_mismatch')]);
        }
    }

    private function toThousandths(string $quantity): int
    {
        [$whole, $fraction] = array_pad(explode('.', $quantity, 2), 2, '');

        return ((int) $whole * 1000) + (int) str_pad(substr($fraction, 0, 3), 3, '0');
    }

    private function fromThousandths(int $quantity): string
    {
        return sprintf('%d.%03d', intdiv($quantity, 1000), $quantity % 1000);
    }
}
