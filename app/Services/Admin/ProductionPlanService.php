<?php

namespace App\Services\Admin;

use App\Enums\ProductionPlanItemStatus;
use App\Enums\ProductionPlanStatus;
use App\Models\CustomerOrder;
use App\Models\ProductionPlan;
use App\Models\User;
use App\Repositories\Contracts\ProductionPlanRepositoryInterface;
use App\Services\AuditLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A termelési tervek és a belőlük képzett gyártási rendelések folyamatát kezeli.
 *
 * A tervtételek mentését repository-ra delegálja, az állapotváltásokat és
 * generálást tranzakcióban hajtja végre és auditnaplózza.
 */
class ProductionPlanService
{
    public function __construct(
        private readonly ProductionPlanRepositoryInterface $repository,
        private readonly AuditLogService $auditLogService,
        private readonly MaterialRequirementService $materialRequirementService,
        private readonly StockReservationService $stockReservationService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->paginateForAdminIndex($filters, $perPage);
    }

    public function findForShow(ProductionPlan $productionPlan): ProductionPlan
    {
        return $this->repository->findForShow($productionPlan);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(array $payload, ?User $causer = null): ProductionPlan
    {
        $customerOrder = CustomerOrder::query()
            ->with('items')
            ->findOrFail((int) $payload['customer_order_id']);

        $items = $customerOrder->items
            ->map(fn ($item): array => [
                'customer_order_item_id' => $item->id,
                'item_id' => $item->item_id,
                'quantity' => $item->quantity,
                'planned_start_date' => $payload['planned_start_date'] ?? null,
                'planned_finish_date' => $payload['planned_finish_date'] ?? null,
                'status' => ProductionPlanItemStatus::Draft->value,
            ])
            ->values()
            ->all();

        $attributes = [
            'customer_order_id' => $customerOrder->id,
            'planned_start_date' => $payload['planned_start_date'] ?? null,
            'planned_finish_date' => $payload['planned_finish_date'] ?? null,
            'created_by' => $causer?->id,
            'notes' => $payload['notes'] ?? null,
        ];

        $productionPlan = $this->repository->createWithItems($attributes, $items);
        $this->auditLogService->log('production_plan_created', $productionPlan, [], $causer);

        return $productionPlan;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(ProductionPlan $productionPlan, array $payload, ?User $causer = null): ProductionPlan
    {
        $attributes = [
            'planned_start_date' => $payload['planned_start_date'] ?? null,
            'planned_finish_date' => $payload['planned_finish_date'] ?? null,
            'notes' => $payload['notes'] ?? null,
        ];

        $items = collect($payload['items'] ?? [])
            ->map(fn (array $item): array => [
                'id' => $item['id'],
                'bom_id' => $item['bom_id'] ?? null,
                'operation_sequence_id' => $item['operation_sequence_id'] ?? null,
                'planned_start_date' => $item['planned_start_date'] ?? null,
                'planned_finish_date' => $item['planned_finish_date'] ?? null,
                'notes' => $item['notes'] ?? null,
            ])
            ->values()
            ->all();

        $productionPlan = $this->repository->updateWithItems($productionPlan, $attributes, $items);
        $this->auditLogService->log('production_plan_updated', $productionPlan, [], $causer);

        return $productionPlan;
    }

    public function approve(ProductionPlan $productionPlan, ?User $causer = null): ProductionPlan
    {
        if (! \in_array($productionPlan->status, [ProductionPlanStatus::Draft, ProductionPlanStatus::Calculated], true)) {
            throw ValidationException::withMessages([
                'status' => __('production.plans.validation.approve_status'),
            ]);
        }

        $productionPlan = $this->repository->approve($productionPlan, $causer?->id);
        $this->auditLogService->log('production_plan_approved', $productionPlan, [], $causer);

        return $productionPlan;
    }

    public function generateProductionOrders(ProductionPlan $productionPlan, ?User $causer = null): void
    {
        $productionPlan = $this->repository->findForShow($productionPlan);

        if ($productionPlan->status !== ProductionPlanStatus::Approved) {
            throw ValidationException::withMessages(['status' => __('production.plans.validation.generate_status')]);
        }

        foreach ($productionPlan->items as $item) {
            if ($item->bom_id === null) {
                throw ValidationException::withMessages(['items' => __('production.plans.validation.missing_bom')]);
            }

            if ($item->operation_sequence_id === null) {
                throw ValidationException::withMessages(['items' => __('production.plans.validation.missing_operation_sequence')]);
            }
        }

        DB::transaction(function () use ($productionPlan, $causer): void {
            $orders = $this->repository->generateProductionOrders($productionPlan, $causer?->id);

            foreach ($orders as $order) {
                $this->materialRequirementService->calculateForProductionOrder($order, $causer);
                $this->stockReservationService->reserveForProductionOrder($order, $causer);
            }

            $this->auditLogService->log('production_orders_generated', $productionPlan, [
                'generated_count' => $orders->count(),
            ], $causer);
        });
    }

    public function delete(ProductionPlan $productionPlan, ?User $causer = null): void
    {
        $this->auditLogService->log('production_plan_deleted', $productionPlan, [], $causer);
        $this->repository->delete($productionPlan);
    }
}
