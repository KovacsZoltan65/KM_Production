<?php

namespace App\Services\Admin;

use App\Models\MaterialRequirement;
use App\Repositories\Contracts\MaterialRequirementNettingRepositoryInterface;
use App\Support\MaterialPlanning\MaterialRequirementNettingResult;
use Illuminate\Support\Collection;

class MaterialRequirementNettingService
{
    public function __construct(private readonly MaterialRequirementNettingRepositoryInterface $repository) {}

    /**
     * Calculates requirement-level net quantities without persisting supply allocation.
     *
     * @param  Collection<int, MaterialRequirement>|null  $requirements
     * @return Collection<int, MaterialRequirementNettingResult>
     */
    public function calculate(?Collection $requirements = null): Collection
    {
        $requirements ??= $this->repository->requirements();
        $requirements = $requirements
            ->sort(function (MaterialRequirement $left, MaterialRequirement $right): int {
                if ($left->required_item_id !== $right->required_item_id) {
                    return $left->required_item_id <=> $right->required_item_id;
                }

                if ($left->required_at === null) {
                    return $right->required_at === null
                        ? $left->id <=> $right->id
                        : 1;
                }

                if ($right->required_at === null) {
                    return -1;
                }

                return ($left->required_at->toDateString() <=> $right->required_at->toDateString())
                    ?: ($left->id <=> $right->id);
            })
            ->values();

        $itemIds = $requirements->pluck('required_item_id')->unique()->values()->all();
        $stockPools = collect($this->repository->usableStockByItem($itemIds))
            ->map(fn (array $rows): array => array_map(fn (array $row): array => [
                'id' => $row['id'],
                'remaining' => $this->toThousandths($row['quantity']),
            ], $rows))
            ->all();
        $incomingPools = collect($this->repository->firmIncomingByItem($itemIds))
            ->map(fn (array $rows): array => array_map(fn (array $row): array => [
                'id' => $row['id'],
                'available_at' => $row['available_at'],
                'remaining' => max(0, $this->toThousandths($row['ordered_quantity'])
                    - $this->toThousandths($row['received_quantity'])),
            ], $rows))
            ->all();

        return $requirements->map(function (MaterialRequirement $requirement) use (&$stockPools, &$incomingPools): MaterialRequirementNettingResult {
            $itemId = $requirement->required_item_id;
            $gross = max(0, $this->toThousandths((string) $requirement->required_quantity));
            $onHand = 0;
            $remaining = $gross;
            $incoming = 0;
            $allocations = [];
            $requiredAt = $requirement->required_at?->toDateString();

            if (isset($stockPools[$itemId])) {
                foreach ($stockPools[$itemId] as &$pool) {
                    $coverage = min($remaining, $pool['remaining']);
                    $pool['remaining'] -= $coverage;
                    $onHand += $coverage;
                    $remaining -= $coverage;

                    if ($coverage > 0) {
                        $allocations[] = ['source_type' => 'stock_balance', 'source_id' => $pool['id'], 'quantity' => $this->fromThousandths($coverage), 'supply_at' => null];
                    }

                    if ($remaining === 0) {
                        break;
                    }
                }
                unset($pool);
            }

            if ($requiredAt !== null && isset($incomingPools[$itemId])) {
                foreach ($incomingPools[$itemId] as &$pool) {
                    if ($pool['available_at'] > $requiredAt || $remaining === 0) {
                        continue;
                    }

                    $coverage = min($remaining, $pool['remaining']);
                    $pool['remaining'] -= $coverage;
                    $incoming += $coverage;
                    $remaining -= $coverage;
                    if ($coverage > 0) {
                        $allocations[] = ['source_type' => 'purchase_order_item', 'source_id' => $pool['id'], 'quantity' => $this->fromThousandths($coverage), 'supply_at' => $pool['available_at']];
                    }
                }
                unset($pool);
            }

            return new MaterialRequirementNettingResult(
                requirementId: $requirement->id,
                productionOrderId: $requirement->production_order_id,
                bomItemId: $requirement->bom_item_id,
                requiredItemId: $itemId,
                requiredAt: $requiredAt,
                unit: $requirement->unit,
                grossRequirement: $this->fromThousandths($gross),
                onHandCoverage: $this->fromThousandths($onHand),
                incomingCoverage: $this->fromThousandths($incoming),
                netRequirement: $this->fromThousandths($remaining),
                allocations: $allocations,
            );
        });
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
