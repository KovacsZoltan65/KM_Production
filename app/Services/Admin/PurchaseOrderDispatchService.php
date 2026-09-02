<?php

namespace App\Services\Admin;

use App\Enums\PurchaseOrderDispatchChannel;
use App\Enums\PurchaseOrderDispatchStatus;
use App\Enums\PurchaseOrderItemStatus;
use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDispatch;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use App\Repositories\Contracts\PurchaseOrderDispatchRepositoryInterface;
use App\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use App\Services\AuditLogService;
use App\Services\BusinessCacheInvalidator;
use App\Support\Procurement\ProcurementQuantity;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/** Records user-attested external Purchase Order dispatch attempts. */
class PurchaseOrderDispatchService
{
    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $purchaseOrders,
        private readonly PurchaseOrderDispatchRepositoryInterface $dispatches,
        private readonly AuditLogService $auditLogService,
        private readonly BusinessCacheInvalidator $cacheInvalidator,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function recordSuccessful(PurchaseOrder $purchaseOrder, array $attributes, User $actor): PurchaseOrderDispatch
    {
        return $this->record($purchaseOrder, $attributes, $actor, PurchaseOrderDispatchStatus::Succeeded);
    }

    /** @param array<string, mixed> $attributes */
    public function recordFailed(PurchaseOrder $purchaseOrder, array $attributes, User $actor): PurchaseOrderDispatch
    {
        return $this->record($purchaseOrder, $attributes, $actor, PurchaseOrderDispatchStatus::Failed);
    }

    /** @param array<string, mixed> $attributes */
    private function record(
        PurchaseOrder $purchaseOrder,
        array $attributes,
        User $actor,
        PurchaseOrderDispatchStatus $status,
    ): PurchaseOrderDispatch {
        $payload = $this->normalizePayload($attributes, $status);
        $fingerprint = $this->fingerprint($payload);

        /** @var array{dispatch: PurchaseOrderDispatch, created: bool} $result */
        $result = DB::transaction(function () use ($purchaseOrder, $actor, $payload, $fingerprint, $status): array {
            $lockedOrder = $this->purchaseOrders->lockForExecution($purchaseOrder->id);
            $existing = $this->dispatches->findByIdempotencyKey($lockedOrder->id, $payload['idempotency_key']);

            if ($existing !== null) {
                if (! hash_equals($existing->request_fingerprint, $fingerprint)) {
                    $this->fail('idempotency_key', 'procurement.dispatch.validation.idempotency_conflict');
                }

                return ['dispatch' => $existing, 'created' => false];
            }

            $this->assertEligible($lockedOrder);
            $latest = $this->dispatches->latestForPurchaseOrder($lockedOrder->id);
            $nextSequence = $this->dispatches->nextSequenceForPurchaseOrder($lockedOrder->id);
            $this->assertPredecessor($latest, $payload['previous_dispatch_id'], $payload['redispatch_reason']);

            $dispatch = $this->dispatches->create([
                'purchase_order_id' => $lockedOrder->id,
                'dispatch_sequence' => $nextSequence,
                'idempotency_key' => $payload['idempotency_key'],
                'request_fingerprint' => $fingerprint,
                'previous_dispatch_id' => $payload['previous_dispatch_id'],
                'channel' => $payload['channel'],
                'supplier_code_snapshot' => $lockedOrder->supplier?->code,
                'supplier_name_snapshot' => $lockedOrder->supplier?->name,
                'recipient_name' => $payload['recipient_name'],
                'recipient_email' => $payload['recipient_email'],
                'recipient_reference' => $payload['recipient_reference'],
                'attempted_at' => $payload['attempted_at'],
                'dispatched_at' => $payload['dispatched_at'],
                'status' => $status,
                'failure_reason' => $payload['failure_reason'],
                'redispatch_reason' => $payload['redispatch_reason'],
                'buyer_requested_delivery_date_snapshot' => $lockedOrder->expected_delivery_date,
                'initiated_by' => $actor->id,
                'notes' => $payload['notes'],
            ]);

            $event = $status === PurchaseOrderDispatchStatus::Succeeded
                ? 'purchase_order_dispatched'
                : 'purchase_order_dispatch_failed';

            $this->auditLogService->log($event, $dispatch, [
                'purchase_order_id' => $lockedOrder->id,
                'dispatch_sequence' => $dispatch->dispatch_sequence,
                'status' => $status->value,
                'previous_dispatch_id' => $dispatch->previous_dispatch_id,
            ], $actor);

            return ['dispatch' => $dispatch, 'created' => true];
        });

        if ($result['created']) {
            $this->cacheInvalidator->procurementChanged();
        }

        return $result['dispatch'];
    }

    /** @param array<string, mixed> $attributes @return array<string, mixed> */
    private function normalizePayload(array $attributes, PurchaseOrderDispatchStatus $status): array
    {
        $key = trim((string) ($attributes['idempotency_key'] ?? ''));
        if (preg_match('/\A[\x20-\x7E]{1,100}\z/D', $key) !== 1) {
            $this->fail('idempotency_key', 'procurement.dispatch.validation.invalid_idempotency_key');
        }

        $channel = PurchaseOrderDispatchChannel::tryFrom((string) ($attributes['channel'] ?? ''));
        if ($channel === null) {
            $this->fail('channel', 'procurement.dispatch.validation.invalid_channel');
        }

        $attemptedAt = $this->dateTime($attributes['attempted_at'] ?? null, 'attempted_at');
        $dispatchedAt = $status === PurchaseOrderDispatchStatus::Succeeded
            ? $this->dateTime($attributes['dispatched_at'] ?? null, 'dispatched_at')
            : null;

        if ($attemptedAt->isFuture() || $dispatchedAt?->isFuture()) {
            $this->fail('attempted_at', 'procurement.dispatch.validation.future_timestamp');
        }

        if ($dispatchedAt !== null && $dispatchedAt->lt($attemptedAt)) {
            $this->fail('dispatched_at', 'procurement.dispatch.validation.invalid_timestamp_order');
        }

        if ($status === PurchaseOrderDispatchStatus::Failed && array_key_exists('dispatched_at', $attributes) && $attributes['dispatched_at'] !== null) {
            $this->fail('dispatched_at', 'procurement.dispatch.validation.failed_dispatched_at');
        }

        $failureReason = $status === PurchaseOrderDispatchStatus::Failed
            ? $this->text($attributes['failure_reason'] ?? null)
            : null;
        if ($status === PurchaseOrderDispatchStatus::Failed && $failureReason === null) {
            $this->fail('failure_reason', 'procurement.dispatch.validation.failure_reason_required');
        }

        $payload = [
            'idempotency_key' => $key,
            'status' => $status->value,
            'channel' => $channel->value,
            'recipient_name' => $this->text($attributes['recipient_name'] ?? null),
            'recipient_email' => $this->email($attributes['recipient_email'] ?? null),
            'recipient_reference' => $this->text($attributes['recipient_reference'] ?? null),
            'attempted_at' => $attemptedAt,
            'dispatched_at' => $dispatchedAt,
            'failure_reason' => $failureReason,
            'redispatch_reason' => $this->text($attributes['redispatch_reason'] ?? null),
            'notes' => $this->text($attributes['notes'] ?? null),
            'previous_dispatch_id' => isset($attributes['previous_dispatch_id']) ? (int) $attributes['previous_dispatch_id'] : null,
        ];

        $this->assertRecipient($channel, $payload);

        return $payload;
    }

    private function assertEligible(PurchaseOrder $purchaseOrder): void
    {
        if (! \in_array($purchaseOrder->status, [PurchaseOrderStatus::Ordered, PurchaseOrderStatus::PartiallyReceived], true)) {
            $this->fail('status', 'procurement.dispatch.validation.ineligible_status');
        }

        if ($purchaseOrder->supplier === null || $purchaseOrder->supplier->trashed() || ! $purchaseOrder->supplier->is_active) {
            $this->fail('supplier_id', 'procurement.dispatch.validation.supplier_unavailable');
        }

        $scope = $purchaseOrder->items
            ->reject(fn (PurchaseOrderItem $item): bool => $item->status === PurchaseOrderItemStatus::Cancelled);

        if ($scope->isEmpty() || $scope->contains(function (PurchaseOrderItem $item): bool {
            try {
                return ! ProcurementQuantity::from($item->ordered_quantity)->isPositive();
            } catch (Throwable) {
                return true;
            }
        })) {
            $this->fail('items', 'procurement.dispatch.validation.invalid_items');
        }
    }

    private function assertPredecessor(
        ?PurchaseOrderDispatch $latest,
        ?int $previousDispatchId,
        ?string $redispatchReason,
    ): void {
        if ($latest === null && ($previousDispatchId !== null || $redispatchReason !== null)) {
            $this->fail('previous_dispatch_id', 'procurement.dispatch.validation.invalid_predecessor');
        }

        if ($latest !== null && ($previousDispatchId !== $latest->id || $redispatchReason === null)) {
            $this->fail('previous_dispatch_id', 'procurement.dispatch.validation.invalid_predecessor');
        }
    }

    /** @param array<string, mixed> $payload */
    private function assertRecipient(PurchaseOrderDispatchChannel $channel, array $payload): void
    {
        if ($channel === PurchaseOrderDispatchChannel::Email && $payload['recipient_email'] === null) {
            $this->fail('recipient_email', 'procurement.dispatch.validation.recipient_required');
        }

        if ($channel === PurchaseOrderDispatchChannel::Manual
            && $payload['recipient_name'] === null
            && $payload['recipient_email'] === null
            && $payload['recipient_reference'] === null) {
            $this->fail('recipient_reference', 'procurement.dispatch.validation.recipient_required');
        }

        if ($channel === PurchaseOrderDispatchChannel::Other
            && ($payload['recipient_reference'] === null || $payload['notes'] === null)) {
            $this->fail('recipient_reference', 'procurement.dispatch.validation.other_details_required');
        }
    }

    /** @param array<string, mixed> $payload */
    private function fingerprint(array $payload): string
    {
        $canonical = [
            'status' => $payload['status'],
            'channel' => $payload['channel'],
            'recipient_name' => $payload['recipient_name'],
            'recipient_email' => $payload['recipient_email'],
            'recipient_reference' => $payload['recipient_reference'],
            'attempted_at' => $payload['attempted_at']->utc()->format('Y-m-d\TH:i:s\Z'),
            'dispatched_at' => $payload['dispatched_at']?->utc()->format('Y-m-d\TH:i:s\Z'),
            'failure_reason' => $payload['failure_reason'],
            'redispatch_reason' => $payload['redispatch_reason'],
            'notes' => $payload['notes'],
            'previous_dispatch_id' => $payload['previous_dispatch_id'],
        ];

        return hash('sha256', json_encode($canonical, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
    }

    private function dateTime(mixed $value, string $field): Carbon
    {
        try {
            return Carbon::parse($value)->utc()->setMicrosecond(0);
        } catch (Throwable) {
            $this->fail($field, 'procurement.dispatch.validation.invalid_timestamp');
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
            $this->fail('recipient_email', 'procurement.dispatch.validation.invalid_email');
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
