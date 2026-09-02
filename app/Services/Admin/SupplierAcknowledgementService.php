<?php

namespace App\Services\Admin;

use App\Enums\PurchaseOrderDispatchStatus;
use App\Enums\PurchaseOrderItemStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\SupplierAcknowledgementDeliveryDateVariance;
use App\Enums\SupplierAcknowledgementLineStatus;
use App\Enums\SupplierAcknowledgementQuantityVariance;
use App\Enums\SupplierAcknowledgementSource;
use App\Enums\SupplierAcknowledgementStatus;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDispatch;
use App\Models\PurchaseOrderItem;
use App\Models\SupplierAcknowledgement;
use App\Models\User;
use App\Repositories\Contracts\PurchaseOrderDispatchRepositoryInterface;
use App\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use App\Repositories\Contracts\SupplierAcknowledgementRepositoryInterface;
use App\Services\AuditLogService;
use App\Services\BusinessCacheInvalidator;
use App\Support\Procurement\ProcurementQuantity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/** Records immutable supplier response snapshots and their correction chain. */
class SupplierAcknowledgementService
{
    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $purchaseOrders,
        private readonly PurchaseOrderDispatchRepositoryInterface $dispatches,
        private readonly SupplierAcknowledgementRepositoryInterface $acknowledgements,
        private readonly AuditLogService $auditLogService,
        private readonly BusinessCacheInvalidator $cacheInvalidator,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function record(PurchaseOrder $purchaseOrder, array $attributes, User $actor): SupplierAcknowledgement
    {
        $payload = $this->normalizePayload($attributes);

        /** @var array{acknowledgement: SupplierAcknowledgement, created: bool} $result */
        $result = DB::transaction(function () use ($purchaseOrder, $payload, $actor): array {
            $lockedOrder = $this->purchaseOrders->lockForExecution($purchaseOrder->id);
            $existingByKey = $this->acknowledgements->findByIdempotencyKey($lockedOrder->id, $payload['idempotency_key']);

            if ($existingByKey !== null) {
                $scopeIds = $existingByKey->scopeItems->pluck('purchase_order_item_id')->map(static fn (mixed $id): int => (int) $id)->all();
                $fingerprint = $this->fingerprint($payload, $scopeIds);

                if (! hash_equals($existingByKey->response_fingerprint, $fingerprint)) {
                    $this->fail('idempotency_key', 'procurement.acknowledgement.validation.idempotency_conflict');
                }

                return ['acknowledgement' => $existingByKey, 'created' => false];
            }

            $scope = $this->scope($lockedOrder);
            $scopeIds = $scope->modelKeys();
            sort($scopeIds);
            $fingerprint = $this->fingerprint($payload, $scopeIds);
            $duplicate = $this->acknowledgements->findByResponseFingerprint($lockedOrder->id, $fingerprint);

            if ($duplicate !== null) {
                return ['acknowledgement' => $duplicate, 'created' => false];
            }

            $effective = $this->acknowledgements->effectiveForPurchaseOrder($lockedOrder->id);
            $nextSequence = $this->acknowledgements->nextSequenceForPurchaseOrder($lockedOrder->id);
            $this->assertLifecycleAndPredecessor($lockedOrder, $effective, $payload);
            $dispatch = $this->linkedDispatch($lockedOrder, $payload);
            $this->assertAttributionAndTime($lockedOrder, $dispatch, $payload);

            $lineRows = $this->evaluateLines($scope, $payload['lines'], $this->buyerBaseline($dispatch, $lockedOrder));
            $evaluation = $this->evaluateHeader($scope->count(), $lineRows);

            $acknowledgement = $this->acknowledgements->create([
                'purchase_order_id' => $lockedOrder->id,
                'purchase_order_dispatch_id' => $dispatch?->id,
                'acknowledgement_sequence' => $nextSequence,
                'idempotency_key' => $payload['idempotency_key'],
                'response_fingerprint' => $fingerprint,
                'supersedes_acknowledgement_id' => $payload['supersedes_acknowledgement_id'],
                'correction_reason' => $payload['correction_reason'],
                'source' => $payload['source'],
                'supplier_reference' => $payload['supplier_reference'],
                'acknowledgement_received_at' => $payload['acknowledgement_received_at'],
                'acknowledged_by_name' => $payload['acknowledged_by_name'],
                'acknowledged_by_email' => $payload['acknowledged_by_email'],
                'status' => $evaluation['status'],
                'requires_follow_up' => $evaluation['requires_follow_up'],
                'requires_replanning' => $evaluation['requires_replanning'],
                'recorded_by' => $actor->id,
                'notes' => $payload['notes'],
            ]);

            $this->acknowledgements->createScopeItems($acknowledgement, array_map(
                static fn (int $itemId): array => ['purchase_order_item_id' => $itemId],
                $scopeIds,
            ));
            $this->acknowledgements->createItems($acknowledgement, $lineRows);

            $event = $effective === null
                ? 'supplier_acknowledgement_recorded'
                : 'supplier_acknowledgement_superseded';
            $this->auditLogService->log($event, $acknowledgement, [
                'purchase_order_id' => $lockedOrder->id,
                'acknowledgement_sequence' => $acknowledgement->acknowledgement_sequence,
                'status' => $evaluation['status']->value,
                'purchase_order_dispatch_id' => $dispatch?->id,
                'supersedes_acknowledgement_id' => $effective?->id,
                'item_count' => \count($lineRows),
                'missing_count' => $evaluation['missing_count'],
                'rejected_count' => $evaluation['rejected_count'],
                'variance_count' => $evaluation['variance_count'],
                'requires_follow_up' => $evaluation['requires_follow_up'],
                'requires_replanning' => $evaluation['requires_replanning'],
            ], $actor);

            return ['acknowledgement' => $acknowledgement->load(['items', 'scopeItems']), 'created' => true];
        });

        if ($result['created']) {
            $this->cacheInvalidator->procurementChanged();
        }

        return $result['acknowledgement'];
    }

    public function effectiveFor(PurchaseOrder $purchaseOrder): ?SupplierAcknowledgement
    {
        return $this->acknowledgements->effectiveForPurchaseOrder($purchaseOrder->id);
    }

    /** @param array<string, mixed> $attributes @return array<string, mixed> */
    private function normalizePayload(array $attributes): array
    {
        $key = trim((string) ($attributes['idempotency_key'] ?? ''));
        if (preg_match('/\A[\x20-\x7E]{1,100}\z/D', $key) !== 1) {
            $this->fail('idempotency_key', 'procurement.acknowledgement.validation.invalid_idempotency_key');
        }

        $source = SupplierAcknowledgementSource::tryFrom((string) ($attributes['source'] ?? ''));
        if ($source === null) {
            $this->fail('source', 'procurement.acknowledgement.validation.invalid_source');
        }

        $rawLines = $attributes['lines'] ?? [];
        if (! \is_array($rawLines) || $rawLines === []) {
            $this->fail('lines', 'procurement.acknowledgement.validation.lines_required');
        }

        $lines = [];
        foreach ($rawLines as $index => $rawLine) {
            if (! \is_array($rawLine)) {
                $this->fail("lines.{$index}", 'procurement.acknowledgement.validation.invalid_line');
            }

            $line = $this->normalizeLine($rawLine, $index);
            if (isset($lines[$line['purchase_order_item_id']])) {
                $this->fail("lines.{$index}.purchase_order_item_id", 'procurement.acknowledgement.validation.duplicate_line');
            }
            $lines[$line['purchase_order_item_id']] = $line;
        }
        ksort($lines);

        return [
            'idempotency_key' => $key,
            'purchase_order_dispatch_id' => isset($attributes['purchase_order_dispatch_id']) ? (int) $attributes['purchase_order_dispatch_id'] : null,
            'supersedes_acknowledgement_id' => isset($attributes['supersedes_acknowledgement_id']) ? (int) $attributes['supersedes_acknowledgement_id'] : null,
            'correction_reason' => $this->text($attributes['correction_reason'] ?? null),
            'source' => $source->value,
            'supplier_reference' => $this->text($attributes['supplier_reference'] ?? null),
            'acknowledgement_received_at' => $this->dateTime($attributes['acknowledgement_received_at'] ?? null),
            'acknowledged_by_name' => $this->text($attributes['acknowledged_by_name'] ?? null),
            'acknowledged_by_email' => $this->email($attributes['acknowledged_by_email'] ?? null),
            'notes' => $this->text($attributes['notes'] ?? null),
            'lines' => array_values($lines),
        ];
    }

    /** @param array<string, mixed> $line @return array<string, mixed> */
    private function normalizeLine(array $line, int $index): array
    {
        $itemId = (int) ($line['purchase_order_item_id'] ?? 0);
        if ($itemId < 1) {
            $this->fail("lines.{$index}.purchase_order_item_id", 'procurement.acknowledgement.validation.invalid_item');
        }

        $status = SupplierAcknowledgementLineStatus::tryFrom((string) ($line['line_status'] ?? ''));
        if ($status === null) {
            $this->fail("lines.{$index}.line_status", 'procurement.acknowledgement.validation.invalid_line_status');
        }

        $promisedDate = $this->date($line['promised_delivery_date'] ?? null, "lines.{$index}.promised_delivery_date");
        $promisedQuantity = null;

        if ($status === SupplierAcknowledgementLineStatus::Accepted) {
            try {
                $quantity = ProcurementQuantity::from($line['promised_quantity'] ?? '');
            } catch (Throwable) {
                $this->fail("lines.{$index}.promised_quantity", 'procurement.acknowledgement.validation.invalid_quantity');
            }

            if (! $quantity->isPositive()) {
                $this->fail("lines.{$index}.promised_quantity", 'procurement.acknowledgement.validation.invalid_quantity');
            }
            $promisedQuantity = $quantity->decimal();
        } elseif (($line['promised_quantity'] ?? null) !== null || $promisedDate !== null) {
            $this->fail("lines.{$index}", 'procurement.acknowledgement.validation.rejected_fields');
        }

        return [
            'purchase_order_item_id' => $itemId,
            'line_status' => $status->value,
            'promised_quantity' => $promisedQuantity,
            'promised_delivery_date' => $promisedDate,
            'notes' => $this->text($line['notes'] ?? null),
        ];
    }

    /** @return Collection<int, PurchaseOrderItem> */
    private function scope(PurchaseOrder $purchaseOrder): Collection
    {
        $scope = $purchaseOrder->items
            ->reject(fn (PurchaseOrderItem $item): bool => $item->status === PurchaseOrderItemStatus::Cancelled)
            ->values();

        if ($scope->isEmpty() || $scope->contains(function (PurchaseOrderItem $item): bool {
            try {
                return ! ProcurementQuantity::from($item->ordered_quantity)->isPositive();
            } catch (Throwable) {
                return true;
            }
        })) {
            $this->fail('items', 'procurement.acknowledgement.validation.invalid_scope');
        }

        return $scope;
    }

    /** @param array<string, mixed> $payload */
    private function assertLifecycleAndPredecessor(
        PurchaseOrder $purchaseOrder,
        ?SupplierAcknowledgement $effective,
        array $payload,
    ): void {
        if ($effective === null) {
            if (! \in_array($purchaseOrder->status, [PurchaseOrderStatus::Ordered, PurchaseOrderStatus::PartiallyReceived], true)) {
                $this->fail('status', 'procurement.acknowledgement.validation.ineligible_status');
            }

            if ($payload['supersedes_acknowledgement_id'] !== null || $payload['correction_reason'] !== null) {
                $this->fail('supersedes_acknowledgement_id', 'procurement.acknowledgement.validation.invalid_predecessor');
            }

            return;
        }

        if ($payload['supersedes_acknowledgement_id'] !== $effective->id || $payload['correction_reason'] === null) {
            $this->fail('supersedes_acknowledgement_id', 'procurement.acknowledgement.validation.invalid_predecessor');
        }
    }

    /** @param array<string, mixed> $payload */
    private function linkedDispatch(PurchaseOrder $purchaseOrder, array $payload): ?PurchaseOrderDispatch
    {
        if ($payload['purchase_order_dispatch_id'] === null) {
            return null;
        }

        $dispatch = $this->dispatches->findById($payload['purchase_order_dispatch_id']);
        if ($dispatch === null
            || $dispatch->purchase_order_id !== $purchaseOrder->id
            || $dispatch->getRawOriginal('status') !== PurchaseOrderDispatchStatus::Succeeded->value) {
            $this->fail('purchase_order_dispatch_id', 'procurement.acknowledgement.validation.invalid_dispatch');
        }

        return $dispatch;
    }

    private function buyerBaseline(?PurchaseOrderDispatch $dispatch, PurchaseOrder $purchaseOrder): mixed
    {
        if ($dispatch === null) {
            return $purchaseOrder->expected_delivery_date;
        }

        return $dispatch->buyer_requested_delivery_date_snapshot ?? $purchaseOrder->expected_delivery_date;
    }

    /** @param array<string, mixed> $payload */
    private function assertAttributionAndTime(PurchaseOrder $purchaseOrder, ?PurchaseOrderDispatch $dispatch, array $payload): void
    {
        if ($payload['supplier_reference'] === null
            && $payload['acknowledged_by_name'] === null
            && $payload['acknowledged_by_email'] === null) {
            $this->fail('supplier_reference', 'procurement.acknowledgement.validation.attribution_required');
        }

        if (($dispatch === null || $payload['source'] === SupplierAcknowledgementSource::Other->value)
            && $payload['notes'] === null) {
            $this->fail('notes', 'procurement.acknowledgement.validation.notes_required');
        }

        /** @var Carbon $receivedAt */
        $receivedAt = $payload['acknowledgement_received_at'];
        if ($receivedAt->isFuture()) {
            $this->fail('acknowledgement_received_at', 'procurement.acknowledgement.validation.future_timestamp');
        }

        if ($dispatch?->dispatched_at !== null && $receivedAt->lt($dispatch->dispatched_at)) {
            $this->fail('acknowledgement_received_at', 'procurement.acknowledgement.validation.before_dispatch');
        }

        if ($dispatch === null && $purchaseOrder->ordered_at !== null && $receivedAt->lt($purchaseOrder->ordered_at)) {
            $this->fail('acknowledgement_received_at', 'procurement.acknowledgement.validation.before_ordered');
        }
    }

    /**
     * @param  Collection<int, PurchaseOrderItem>  $scope
     * @param  list<array<string, mixed>>  $lines
     * @return list<array<string, mixed>>
     */
    private function evaluateLines(Collection $scope, array $lines, mixed $buyerBaseline): array
    {
        $items = $scope->keyBy('id');
        $baseline = $buyerBaseline === null ? null : Carbon::parse($buyerBaseline)->toDateString();
        $rows = [];

        foreach ($lines as $index => $line) {
            $item = $items->get($line['purchase_order_item_id']);
            if (! $item instanceof PurchaseOrderItem) {
                $this->fail("lines.{$index}.purchase_order_item_id", 'procurement.acknowledgement.validation.item_outside_scope');
            }

            $ordered = ProcurementQuantity::from($item->ordered_quantity);
            $lineStatus = SupplierAcknowledgementLineStatus::from($line['line_status']);

            if ($lineStatus === SupplierAcknowledgementLineStatus::Rejected) {
                $rows[] = [
                    ...$line,
                    'ordered_quantity_snapshot' => $ordered->decimal(),
                    'unit_snapshot' => $item->unit,
                    'buyer_requested_delivery_date_snapshot' => $baseline,
                    'quantity_variance' => SupplierAcknowledgementQuantityVariance::Rejected,
                    'quantity_variance_amount' => null,
                    'delivery_date_variance' => SupplierAcknowledgementDeliveryDateVariance::Rejected,
                ];

                continue;
            }

            $promised = ProcurementQuantity::from($line['promised_quantity']);
            $varianceAmount = $promised->minus($ordered);
            $quantityVariance = match ($varianceAmount->thousandths() <=> 0) {
                -1 => SupplierAcknowledgementQuantityVariance::Reduced,
                0 => SupplierAcknowledgementQuantityVariance::Matched,
                1 => SupplierAcknowledgementQuantityVariance::Increased,
            };

            $rows[] = [
                ...$line,
                'ordered_quantity_snapshot' => $ordered->decimal(),
                'unit_snapshot' => $item->unit,
                'buyer_requested_delivery_date_snapshot' => $baseline,
                'quantity_variance' => $quantityVariance,
                'quantity_variance_amount' => $varianceAmount->decimal(),
                'delivery_date_variance' => $this->deliveryVariance($line['promised_delivery_date'], $baseline),
            ];
        }

        return $rows;
    }

    private function deliveryVariance(?string $promisedDate, ?string $baseline): SupplierAcknowledgementDeliveryDateVariance
    {
        if ($promisedDate === null) {
            return SupplierAcknowledgementDeliveryDateVariance::NotConfirmed;
        }
        if ($baseline === null) {
            return SupplierAcknowledgementDeliveryDateVariance::NoBuyerBaseline;
        }

        return match ($promisedDate <=> $baseline) {
            -1 => SupplierAcknowledgementDeliveryDateVariance::Earlier,
            0 => SupplierAcknowledgementDeliveryDateVariance::Matched,
            1 => SupplierAcknowledgementDeliveryDateVariance::Later,
        };
    }

    /** @param list<array<string, mixed>> $rows @return array<string, mixed> */
    private function evaluateHeader(int $scopeCount, array $rows): array
    {
        $missingCount = $scopeCount - \count($rows);
        $rejectedCount = \count(array_filter($rows, static fn (array $row): bool => $row['line_status'] === SupplierAcknowledgementLineStatus::Rejected->value));
        $varianceCount = \count(array_filter($rows, static fn (array $row): bool => $row['quantity_variance'] !== SupplierAcknowledgementQuantityVariance::Matched
            || $row['delivery_date_variance'] !== SupplierAcknowledgementDeliveryDateVariance::Matched));

        $fullyRejected = $missingCount === 0 && $rejectedCount === $scopeCount;
        $fullyAccepted = $missingCount === 0
            && $rejectedCount === 0
            && $varianceCount === 0;
        $status = $fullyRejected
            ? SupplierAcknowledgementStatus::Rejected
            : ($fullyAccepted ? SupplierAcknowledgementStatus::Accepted : SupplierAcknowledgementStatus::AcceptedWithChanges);

        $requiresReplanning = $missingCount > 0 || \count(array_filter($rows, static fn (array $row): bool => $row['line_status'] === SupplierAcknowledgementLineStatus::Rejected->value
            || $row['quantity_variance'] === SupplierAcknowledgementQuantityVariance::Reduced
            || \in_array($row['delivery_date_variance'], [
                SupplierAcknowledgementDeliveryDateVariance::Later,
                SupplierAcknowledgementDeliveryDateVariance::NotConfirmed,
                SupplierAcknowledgementDeliveryDateVariance::NoBuyerBaseline,
            ], true))) > 0;

        return [
            'status' => $status,
            'requires_follow_up' => $status !== SupplierAcknowledgementStatus::Accepted,
            'requires_replanning' => $requiresReplanning,
            'missing_count' => $missingCount,
            'rejected_count' => $rejectedCount,
            'variance_count' => $varianceCount,
        ];
    }

    /** @param array<string, mixed> $payload @param list<int> $scopeIds */
    private function fingerprint(array $payload, array $scopeIds): string
    {
        sort($scopeIds);
        $canonicalLines = array_map(static fn (array $line): array => [
            'purchase_order_item_id' => $line['purchase_order_item_id'],
            'line_status' => $line['line_status'],
            'promised_quantity' => $line['promised_quantity'],
            'promised_delivery_date' => $line['promised_delivery_date'],
            'notes' => $line['notes'],
        ], $payload['lines']);

        $canonical = [
            'purchase_order_dispatch_id' => $payload['purchase_order_dispatch_id'],
            'source' => $payload['source'],
            'supplier_reference' => $payload['supplier_reference'],
            'acknowledgement_received_at' => $payload['acknowledgement_received_at']->utc()->format('Y-m-d\TH:i:s\Z'),
            'acknowledged_by_name' => $payload['acknowledged_by_name'],
            'acknowledged_by_email' => $payload['acknowledged_by_email'],
            'supersedes_acknowledgement_id' => $payload['supersedes_acknowledgement_id'],
            'scope_purchase_order_item_ids' => $scopeIds,
            'lines' => $canonicalLines,
        ];

        return hash('sha256', json_encode($canonical, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }

    private function dateTime(mixed $value): Carbon
    {
        try {
            return Carbon::parse($value)->utc()->setMicrosecond(0);
        } catch (Throwable) {
            $this->fail('acknowledgement_received_at', 'procurement.acknowledgement.validation.invalid_timestamp');
        }
    }

    private function date(mixed $value, string $field): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (Throwable) {
            $this->fail($field, 'procurement.acknowledgement.validation.invalid_date');
        }
    }

    private function email(mixed $value): ?string
    {
        $email = $this->text($value);
        if ($email === null) {
            return null;
        }

        $email = strtolower($email);
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->fail('acknowledged_by_email', 'procurement.acknowledgement.validation.invalid_email');
        }

        return $email;
    }

    private function text(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function fail(string $field, string $key): never
    {
        throw ValidationException::withMessages([$field => __($key)]);
    }
}
