<?php

namespace App\Services\Admin;

use App\Enums\SupplyProposalStatus;
use App\Enums\SupplyStrategy;
use App\Models\Item;
use App\Models\SupplyProposal;
use App\Models\User;
use App\Repositories\Contracts\SupplyProposalRepositoryInterface;
use App\Services\AuditLogService;
use App\Services\BusinessCacheInvalidator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** A Supply Proposal szerkeszthetőségét, invariánsait és döntési életciklusát kezeli. */
class SupplyProposalService
{
    public function __construct(
        private readonly SupplyProposalRepositoryInterface $proposals,
        private readonly AuditLogService $auditLogService,
        private readonly BusinessCacheInvalidator $cacheInvalidator,
    ) {}

    /** @return LengthAwarePaginator<int, SupplyProposal> */
    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->proposals->paginateForAdminIndex($filters, $perPage);
    }

    public function itemOptions(): Collection
    {
        return $this->proposals->itemOptions()->map(fn (array $item): array => [
            ...$item,
            'label' => "{$item['item_number']} - {$item['name']}",
        ]);
    }

    public function supplierOptionsByItem(): array
    {
        return $this->proposals->usableSupplierOptions()
            ->groupBy('item_id')
            ->map(fn (Collection $sources): array => $sources
                ->unique('supplier_id')
                ->map(fn (array $source): array => [
                    'id' => $source['supplier_id'],
                    'label' => "{$source['code']} - {$source['name']}",
                ])
                ->values()
                ->all())
            ->all();
    }

    public function create(array $attributes, ?User $causer = null): SupplyProposal
    {
        $attributes = $this->normalizedAttributes($attributes);

        $proposal = DB::transaction(function () use ($attributes, $causer): SupplyProposal {
            $this->validateSupplier($attributes);
            /** @var SupplyProposal $proposal */
            $proposal = $this->proposals->create([
                ...$attributes,
                'status' => SupplyProposalStatus::Draft->value,
                'created_by' => $causer?->id,
            ]);
            $this->auditLogService->logCreated('supply_proposal_created', $proposal, $causer, [
                'manual' => true,
            ]);

            return $proposal->refresh();
        });

        $this->cacheInvalidator->procurementChanged();

        return $proposal;
    }

    public function update(SupplyProposal $proposal, array $attributes, ?User $causer = null): SupplyProposal
    {
        $proposal = DB::transaction(function () use ($proposal, $attributes, $causer): SupplyProposal {
            $locked = $this->proposals->findLocked($proposal->id);
            $this->assertEditable($locked);
            $attributes = $this->normalizedAttributes($attributes);
            $this->validateSupplier($attributes);
            $original = $locked->getRawOriginal();
            /** @var SupplyProposal $updated */
            $updated = $this->proposals->update($locked, $attributes);
            $this->auditLogService->logUpdated('supply_proposal_updated', $updated, $original, $causer);

            return $updated->refresh();
        });

        $this->cacheInvalidator->procurementChanged();

        return $proposal;
    }

    public function propose(SupplyProposal $proposal, ?User $causer = null): SupplyProposal
    {
        return $this->transition($proposal, SupplyProposalStatus::Proposed, $causer);
    }

    public function approve(SupplyProposal $proposal, ?User $causer = null): SupplyProposal
    {
        return $this->transition($proposal, SupplyProposalStatus::Approved, $causer);
    }

    public function reject(SupplyProposal $proposal, ?User $causer = null): SupplyProposal
    {
        return $this->transition($proposal, SupplyProposalStatus::Rejected, $causer);
    }

    public function cancel(SupplyProposal $proposal, ?User $causer = null): SupplyProposal
    {
        return $this->transition($proposal, SupplyProposalStatus::Cancelled, $causer);
    }

    private function transition(SupplyProposal $proposal, SupplyProposalStatus $target, ?User $causer): SupplyProposal
    {
        $proposal = DB::transaction(function () use ($proposal, $target, $causer): SupplyProposal {
            $locked = $this->proposals->findLocked($proposal->id);

            if (! $locked->status->canTransitionTo($target)) {
                throw ValidationException::withMessages([
                    'status' => __('planning.supply_proposals.validation.invalid_transition'),
                ]);
            }

            $original = $locked->getRawOriginal();
            $attributes = ['status' => $target->value];
            $timestamp = now();

            if ($target === SupplyProposalStatus::Approved) {
                $attributes += ['approved_by' => $causer?->id, 'approved_at' => $timestamp];
            } elseif ($target === SupplyProposalStatus::Rejected) {
                $attributes += ['rejected_by' => $causer?->id, 'rejected_at' => $timestamp];
            } elseif ($target === SupplyProposalStatus::Cancelled) {
                $attributes += ['cancelled_by' => $causer?->id, 'cancelled_at' => $timestamp];
            }

            /** @var SupplyProposal $updated */
            $updated = $this->proposals->update($locked, $attributes);
            $this->auditLogService->logUpdated(
                "supply_proposal_{$target->value}",
                $updated,
                $original,
                $causer,
            );

            return $updated->refresh();
        });

        $this->cacheInvalidator->procurementChanged();

        return $proposal;
    }

    private function assertEditable(SupplyProposal $proposal): void
    {
        if (! $proposal->status->isEditable()) {
            throw ValidationException::withMessages([
                'status' => __('planning.supply_proposals.validation.only_draft_editable'),
            ]);
        }
    }

    private function validateSupplier(array $attributes): void
    {
        if (($attributes['supplier_id'] ?? null) === null) {
            return;
        }

        if (($attributes['strategy'] ?? null) !== SupplyStrategy::Purchase->value) {
            throw ValidationException::withMessages([
                'supplier_id' => __('planning.supply_proposals.validation.supplier_strategy'),
            ]);
        }

        if (! $this->proposals->hasUsableProcurementSource(
            (int) $attributes['item_id'],
            (int) $attributes['supplier_id'],
        )) {
            throw ValidationException::withMessages([
                'supplier_id' => __('planning.supply_proposals.validation.invalid_procurement_source'),
            ]);
        }
    }

    private function normalizedAttributes(array $attributes): array
    {
        $item = Item::query()->findOrFail((int) $attributes['item_id']);

        return [
            'strategy' => (string) $attributes['strategy'],
            'item_id' => $item->id,
            'supplier_id' => $attributes['supplier_id'] ?? null,
            'proposed_quantity' => $attributes['proposed_quantity'],
            'unit' => $item->unit,
            'required_at' => $attributes['required_at'] ?? null,
            'proposed_supply_at' => $attributes['proposed_supply_at'] ?? null,
            'reason_code' => $attributes['reason_code'] ?? null,
            'notes' => $attributes['notes'] ?? null,
        ];
    }
}
