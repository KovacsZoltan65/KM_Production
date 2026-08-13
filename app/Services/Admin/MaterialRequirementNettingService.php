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
        $onHandPools = collect($this->repository->usableOnHandByItem($itemIds))
            ->map(fn (string $quantity): int => $this->toThousandths($quantity))
            ->all();
        $incomingPools = collect($this->repository->firmIncomingByItem($itemIds))
            ->map(fn (array $rows): array => array_map(fn (array $row): array => [
                'id' => $row['id'],
                'available_at' => $row['available_at'],
                'remaining' => max(0, $this->toThousandths($row['ordered_quantity'])
                    - $this->toThousandths($row['received_quantity'])),
            ], $rows))
            ->all();

        return $requirements->map(function (MaterialRequirement $requirement) use (&$onHandPools, &$incomingPools): MaterialRequirementNettingResult {
            $itemId = $requirement->required_item_id;
            $gross = max(0, $this->toThousandths((string) $requirement->required_quantity));
            $onHand = min($gross, $onHandPools[$itemId] ?? 0);
            $onHandPools[$itemId] = ($onHandPools[$itemId] ?? 0) - $onHand;
            $remaining = $gross - $onHand;
            $incoming = 0;
            $requiredAt = $requirement->required_at?->toDateString();

            if ($requiredAt !== null && isset($incomingPools[$itemId])) {
                foreach ($incomingPools[$itemId] as &$pool) {
                    if ($pool['available_at'] > $requiredAt || $remaining === 0) {
                        continue;
                    }

                    $coverage = min($remaining, $pool['remaining']);
                    $pool['remaining'] -= $coverage;
                    $incoming += $coverage;
                    $remaining -= $coverage;
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
