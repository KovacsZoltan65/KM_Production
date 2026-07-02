<?php

namespace App\Services\Admin;

use App\Enums\StockMovementType;
use App\Enums\StockReservationStatus;
use App\Models\ProductionTask;
use App\Models\ProductionTaskMaterial;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\StockReservation;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductionTaskMaterialService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function store(ProductionTask $productionTask, array $attributes, ?User $causer = null): ProductionTaskMaterial
    {
        return DB::transaction(function () use ($productionTask, $attributes, $causer): ProductionTaskMaterial {
            $reservation = $this->reservation($attributes['stock_reservation_id'] ?? null);
            $locationId = $attributes['location_id'] ?? $reservation?->location_id;
            $quantity = (float) $attributes['used_quantity'];
            $balance = $this->balanceForConsumption(
                (int) $attributes['item_id'],
                $attributes['item_batch_id'] ?? $reservation?->item_batch_id,
                $locationId,
                $quantity
            );

            $material = ProductionTaskMaterial::query()->create([
                'production_task_id' => $productionTask->id,
                'item_id' => $attributes['item_id'],
                'item_batch_id' => $attributes['item_batch_id'] ?? $balance->item_batch_id,
                'planned_quantity' => $attributes['planned_quantity'] ?? $quantity,
                'used_quantity' => $quantity,
                'unit' => $attributes['unit'],
                'notes' => $attributes['notes'] ?? null,
            ]);

            $balance->decrement('quantity', $quantity);

            StockMovement::query()->create([
                'item_id' => $material->item_id,
                'item_batch_id' => $material->item_batch_id,
                'from_location_id' => $balance->location_id,
                'quantity' => $quantity,
                'movement_type' => StockMovementType::ProductionConsume->value,
                'source_type' => ProductionTaskMaterial::class,
                'source_id' => $material->id,
                'performed_by' => $causer?->id,
                'performed_at' => now(),
                'notes' => 'Production task material used.',
            ]);

            if ($reservation !== null) {
                $reservation->update([
                    'status' => StockReservationStatus::Consumed->value,
                    'released_at' => now(),
                ]);
            } else {
                StockReservation::query()
                    ->where('production_order_id', $productionTask->production_order_id)
                    ->where('item_id', $material->item_id)
                    ->where('location_id', $balance->location_id)
                    ->where('item_batch_id', $material->item_batch_id)
                    ->where('status', StockReservationStatus::Active->value)
                    ->limit(1)
                    ->update([
                        'status' => StockReservationStatus::Consumed->value,
                        'released_at' => now(),
                    ]);
            }

            $this->auditLogService->log('production_task_material_used', $material, [
                'production_task_id' => $productionTask->id,
                'used_quantity' => $quantity,
            ], $causer);

            return $material->load(['item', 'itemBatch']);
        });
    }

    private function reservation(mixed $reservationId): ?StockReservation
    {
        if ($reservationId === null) {
            return null;
        }

        return StockReservation::query()->findOrFail($reservationId);
    }

    private function balanceForConsumption(int $itemId, mixed $itemBatchId, mixed $locationId, float $quantity): StockBalance
    {
        $query = StockBalance::query()
            ->where('item_id', $itemId)
            ->when($itemBatchId !== null, fn ($query) => $query->where('item_batch_id', $itemBatchId))
            ->when($itemBatchId === null, fn ($query) => $query->whereNull('item_batch_id'))
            ->when($locationId !== null, fn ($query) => $query->where('location_id', $locationId))
            ->where('quantity', '>=', $quantity)
            ->orderBy('id')
            ->lockForUpdate();

        $balance = $query->first();

        if ($balance === null) {
            throw ValidationException::withMessages(['used_quantity' => __('production.materials.validation.insufficient_stock')]);
        }

        return $balance;
    }
}
