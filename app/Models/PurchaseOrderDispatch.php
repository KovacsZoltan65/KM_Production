<?php

namespace App\Models;

use App\Enums\PurchaseOrderDispatchChannel;
use App\Enums\PurchaseOrderDispatchStatus;
use App\Models\Concerns\AppendOnly;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $purchase_order_id
 * @property int $dispatch_sequence
 * @property string $idempotency_key
 * @property string $request_fingerprint
 * @property int|null $previous_dispatch_id
 * @property PurchaseOrderDispatchChannel $channel
 * @property string|null $supplier_code_snapshot
 * @property string|null $supplier_name_snapshot
 * @property string|null $recipient_name
 * @property string|null $recipient_email
 * @property string|null $recipient_reference
 * @property CarbonImmutable $attempted_at
 * @property CarbonImmutable|null $dispatched_at
 * @property PurchaseOrderDispatchStatus $status
 * @property string|null $failure_reason
 * @property string|null $redispatch_reason
 * @property CarbonImmutable|null $buyer_requested_delivery_date_snapshot
 * @property int|null $initiated_by
 * @property string|null $notes
 * @property-read Collection<int, SupplierAcknowledgement> $acknowledgements
 */
#[Fillable([
    'purchase_order_id', 'dispatch_sequence', 'idempotency_key', 'request_fingerprint',
    'previous_dispatch_id', 'channel', 'supplier_code_snapshot', 'supplier_name_snapshot',
    'recipient_name', 'recipient_email', 'recipient_reference', 'attempted_at',
    'dispatched_at', 'status', 'failure_reason', 'redispatch_reason',
    'buyer_requested_delivery_date_snapshot', 'initiated_by', 'notes',
])]
class PurchaseOrderDispatch extends Model
{
    use AppendOnly;

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class)->withTrashed();
    }

    public function previousDispatch(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_dispatch_id');
    }

    public function nextDispatch(): HasOne
    {
        return $this->hasOne(self::class, 'previous_dispatch_id');
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    /** @return HasMany<SupplierAcknowledgement, $this> */
    public function acknowledgements(): HasMany
    {
        return $this->hasMany(SupplierAcknowledgement::class);
    }

    protected function casts(): array
    {
        return [
            'channel' => PurchaseOrderDispatchChannel::class,
            'attempted_at' => 'immutable_datetime',
            'dispatched_at' => 'immutable_datetime',
            'status' => PurchaseOrderDispatchStatus::class,
            'buyer_requested_delivery_date_snapshot' => 'immutable_date',
        ];
    }
}
