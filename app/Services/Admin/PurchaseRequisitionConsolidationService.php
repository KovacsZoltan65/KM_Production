<?php

namespace App\Services\Admin;

use App\Enums\PurchaseRequisitionItemStatus;
use App\Enums\PurchaseRequisitionStatus;
use App\Enums\SupplyProposalStatus;
use App\Enums\SupplyStrategy;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use App\Models\SupplyProposal;
use App\Models\User;
use App\Repositories\Contracts\PurchaseRequisitionRepositoryInterface;
use App\Repositories\Contracts\SupplyProposalRepositoryInterface;
use App\Services\AuditLogService;
use App\Services\BusinessCacheInvalidator;
use App\Services\CodeGeneratorService;
use App\Support\Procurement\PurchaseRequisitionConsolidationResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Converts explicitly selected approved purchase proposals into new Draft PR documents. */
final class PurchaseRequisitionConsolidationService
{
    public function __construct(
        private readonly SupplyProposalRepositoryInterface $proposals,
        private readonly PurchaseRequisitionRepositoryInterface $requisitions,
        private readonly CodeGeneratorService $codeGenerator,
        private readonly AuditLogService $audit,
        private readonly BusinessCacheInvalidator $cacheInvalidator,
    ) {}

    /** @param list<int> $proposalIds */
    public function consolidate(array $proposalIds, ?User $causer = null): PurchaseRequisitionConsolidationResult
    {
        $ids = collect($proposalIds)->map(fn (mixed $id): int => (int) $id)->unique()->sort()->values()->all();

        if ($ids === []) {
            throw ValidationException::withMessages([
                'proposal_ids' => __('planning.supply_proposals.consolidation.validation.selection_required'),
            ]);
        }

        $result = DB::transaction(function () use ($ids, $causer): PurchaseRequisitionConsolidationResult {
            $proposals = $this->proposals->lockForConsolidation($ids);

            if ($proposals->count() !== count($ids)) {
                throw ValidationException::withMessages([
                    'proposal_ids' => __('planning.supply_proposals.consolidation.validation.not_found'),
                ]);
            }

            foreach ($proposals as $proposal) {
                $this->revalidate($proposal);
            }

            $createdIds = [];
            $itemCount = 0;
            $sourceCount = 0;
            $firstRequisition = null;

            foreach ($this->grouped($proposals) as $group) {
                /** @var SupplyProposal $first */
                $first = $group->first();
                $requisition = $this->requisitions->createDraft([
                    'requisition_number' => $this->codeGenerator->generate('purchase_requisition'),
                    'status' => PurchaseRequisitionStatus::Draft->value,
                    'supplier_id' => $first->supplier_id,
                    'requested_by' => $causer?->id,
                    'requested_at' => now(),
                    'required_at' => $first->required_at?->toDateString(),
                    'proposed_supply_at' => $first->proposed_supply_at?->toDateString(),
                    'notes' => __('procurement.purchase_requisitions.notes.consolidated_from_supply_proposals'),
                ]);
                $firstRequisition ??= $requisition;
                $createdIds[] = $requisition->id;

                foreach ($this->groupItems($group) as $itemGroup) {
                    $itemCount++;
                    $quantity = $this->sumQuantities($itemGroup);
                    /** @var SupplyProposal $itemFirst */
                    $itemFirst = $itemGroup->first();
                    $item = $this->requisitions->createItem($requisition, [
                        'item_id' => $itemFirst->item_id,
                        'quantity' => $quantity,
                        'planned_quantity' => $quantity,
                        'replenishment_excess_quantity' => '0.000',
                        'unit' => $itemFirst->unit,
                        'status' => PurchaseRequisitionItemStatus::Draft->value,
                    ]);

                    foreach ($itemGroup as $proposal) {
                        $this->requisitions->createProposalSource($item, [
                            'supply_proposal_id' => $proposal->id,
                            'quantity' => $proposal->proposed_quantity,
                        ]);
                        $sourceCount++;
                    }

                    $this->assertSourceInvariant($item, $quantity);
                }
            }

            if (! $firstRequisition instanceof PurchaseRequisition) {
                throw ValidationException::withMessages([
                    'proposal_ids' => __('planning.supply_proposals.consolidation.validation.no_eligible'),
                ]);
            }

            $this->audit->log('purchase_requisition_consolidation_completed', $firstRequisition, [
                'proposal_count' => $proposals->count(),
                'requisition_count' => count($createdIds),
                'item_count' => $itemCount,
                'source_count' => $sourceCount,
            ], $causer);

            return new PurchaseRequisitionConsolidationResult(
                $createdIds,
                $proposals->count(),
                $itemCount,
                $sourceCount,
            );
        });

        $this->cacheInvalidator->procurementChanged();

        return $result;
    }

    private function revalidate(SupplyProposal $proposal): void
    {
        if ($proposal->getRawOriginal('status') !== SupplyProposalStatus::Approved->value
            || $proposal->getRawOriginal('strategy') !== SupplyStrategy::Purchase->value) {
            throw ValidationException::withMessages([
                'proposal_ids' => __('planning.supply_proposals.consolidation.validation.not_eligible', ['id' => $proposal->id]),
            ]);
        }

        if ($this->toThousandths((string) $proposal->proposed_quantity) <= 0) {
            throw ValidationException::withMessages([
                'proposal_ids' => __('planning.supply_proposals.consolidation.validation.invalid_quantity', ['id' => $proposal->id]),
            ]);
        }

        if (! $proposal->item->is_active || $proposal->unit !== $proposal->item->unit) {
            throw ValidationException::withMessages([
                'proposal_ids' => __('planning.supply_proposals.consolidation.validation.invalid_item_unit', ['id' => $proposal->id]),
            ]);
        }

        if ($proposal->purchaseRequisitionSource()->exists()) {
            throw ValidationException::withMessages([
                'proposal_ids' => __('planning.supply_proposals.consolidation.validation.already_consolidated', ['id' => $proposal->id]),
            ]);
        }

        if ($proposal->supplier_id !== null && (! $proposal->supplier?->is_active
            || ! $this->proposals->hasUsableProcurementSource($proposal->item_id, $proposal->supplier_id))) {
            throw ValidationException::withMessages([
                'proposal_ids' => __('planning.supply_proposals.consolidation.validation.invalid_supplier', ['id' => $proposal->id]),
            ]);
        }
    }

    /** @param Collection<int, SupplyProposal> $proposals @return Collection<string, Collection<int, SupplyProposal>> */
    private function grouped(Collection $proposals): Collection
    {
        return $proposals
            ->groupBy(fn (SupplyProposal $proposal): string => implode('|', [
                $proposal->strategy->value,
                $proposal->supplier_id === null ? 'null' : (string) $proposal->supplier_id,
                $proposal->required_at?->toDateString() ?? 'null',
                $proposal->proposed_supply_at?->toDateString() ?? 'null',
            ]))
            ->sortKeys();
    }

    /** @param Collection<int, SupplyProposal> $proposals @return Collection<string, Collection<int, SupplyProposal>> */
    private function groupItems(Collection $proposals): Collection
    {
        return $proposals
            ->groupBy(fn (SupplyProposal $proposal): string => "{$proposal->item_id}|{$proposal->unit}")
            ->sortKeys();
    }

    /** @param Collection<int, SupplyProposal> $proposals */
    private function sumQuantities(Collection $proposals): string
    {
        $total = $proposals->sum(fn (SupplyProposal $proposal): int => $this->toThousandths((string) $proposal->proposed_quantity));

        return $this->fromThousandths($total);
    }

    private function assertSourceInvariant(PurchaseRequisitionItem $item, string $quantity): void
    {
        $sourceTotal = $item->proposalSources()->get(['quantity'])
            ->sum(fn ($source): int => $this->toThousandths((string) $source->quantity));

        if ($sourceTotal !== $this->toThousandths($quantity)) {
            throw ValidationException::withMessages([
                'proposal_ids' => __('planning.supply_proposals.consolidation.validation.source_mismatch'),
            ]);
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
