<?php

namespace App\Services\Admin;

use App\Enums\StockReservationStatus;
use App\Models\ProductionOrder;
use App\Models\StockBalance;
use App\Models\StockReservation;
use App\Models\User;
use App\Repositories\Contracts\StockBalanceRepositoryInterface;
use App\Repositories\Contracts\StockReservationRepositoryInterface;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockReservationService
{
    public function __construct(
        private readonly StockBalanceRepositoryInterface $stockBalances,
        private readonly StockReservationRepositoryInterface $stockReservations,
        private readonly MaterialRequirementService $materialRequirementService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function reserveForProductionOrder(ProductionOrder $productionOrder, ?User $causer = null): void
    {
        DB::transaction(function () use ($productionOrder, $causer): void {
            $requirements = $this->materialRequirementService->calculateForProductionOrder($productionOrder, $causer);
            $createdCount = 0;

            foreach ($requirements as $requirement) {
                $alreadyReserved = $this->stockReservations->activeReservedQuantity(
                    $requirement->required_item_id,
                    null,
                    null,
                    $productionOrder->customer_order_item_id,
                    $productionOrder->id
                );
                $remainingQuantity = max(0, (float) $requirement->required_quantity - $alreadyReserved);

                if ($remainingQuantity <= 0) {
                    continue;
                }

                foreach ($this->stockBalances->balancesForReservation($requirement->required_item_id) as $balance) {
                    $availableOnBalance = $this->availableOnBalance($balance);

                    if ($availableOnBalance <= 0) {
                        continue;
                    }

                    $reservedQuantity = min($remainingQuantity, $availableOnBalance);
                    $this->stockReservations->createReservation([
                        'item_id' => $balance->item_id,
                        'location_id' => $balance->location_id,
                        'item_batch_id' => $balance->item_batch_id,
                        'customer_order_item_id' => $productionOrder->customer_order_item_id,
                        'production_order_id' => $productionOrder->id,
                        'reserved_quantity' => $reservedQuantity,
                        'status' => StockReservationStatus::Active->value,
                        'reserved_by' => $causer?->id,
                        'reserved_at' => now(),
                    ]);

                    $createdCount++;
                    $remainingQuantity -= $reservedQuantity;

                    if ($remainingQuantity <= 0) {
                        break;
                    }
                }
            }

            $this->materialRequirementService->calculateForProductionOrder($productionOrder, $causer);
            $this->auditLogService->log('stock_reserved', $productionOrder, [
                'reservations_count' => $createdCount,
            ], $causer);
        });
    }

    public function release(StockReservation $reservation, ?User $causer = null): StockReservation
    {
        if ($reservation->status !== StockReservationStatus::Active) {
            throw ValidationException::withMessages([
                'reservation' => 'Only active reservations can be released.',
            ]);
        }

        return DB::transaction(function () use ($reservation, $causer): StockReservation {
            $reservation = $this->stockReservations->release($reservation);

            if ($reservation->productionOrder !== null) {
                $this->materialRequirementService->calculateForProductionOrder($reservation->productionOrder, $causer);
            }

            $this->auditLogService->log('stock_reservation_released', $reservation, [
                'reserved_quantity' => $reservation->reserved_quantity,
            ], $causer);

            return $reservation;
        });
    }

    private function availableOnBalance(StockBalance $balance): float
    {
        $reserved = $this->stockReservations->activeReservedQuantityForBalance(
            $balance->item_id,
            $balance->location_id,
            $balance->item_batch_id
        );

        return max(0, (float) $balance->quantity - $reserved);
    }
}
