<?php

namespace App\Models;

use App\Enums\SupplierAcknowledgementSource;
use App\Enums\SupplierAcknowledgementStatus;
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
 * @property int|null $purchase_order_dispatch_id
 * @property int $acknowledgement_sequence
 * @property string $idempotency_key
 * @property string $response_fingerprint
 * @property int|null $supersedes_acknowledgement_id
 * @property string|null $correction_reason
 * @property SupplierAcknowledgementSource $source
 * @property string|null $supplier_reference
 * @property CarbonImmutable $acknowledgement_received_at
 * @property string|null $acknowledged_by_name
 * @property string|null $acknowledged_by_email
 * @property SupplierAcknowledgementStatus $status
 * @property bool $requires_follow_up
 * @property bool $requires_replanning
 * @property int|null $recorded_by
 * @property string|null $notes
 * @property-read Collection<int, SupplierAcknowledgementItem> $items
 * @property-read Collection<int, SupplierAcknowledgementScopeItem> $scopeItems
 */
#[Fillable([
    'purchase_order_id', 'purchase_order_dispatch_id', 'acknowledgement_sequence',
    'idempotency_key', 'response_fingerprint', 'supersedes_acknowledgement_id',
    'correction_reason', 'source', 'supplier_reference', 'acknowledgement_received_at',
    'acknowledged_by_name', 'acknowledged_by_email', 'status', 'requires_follow_up',
    'requires_replanning', 'recorded_by', 'notes',
])]
class SupplierAcknowledgement extends Model
{
    use AppendOnly;

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class)->withTrashed();
    }

    public function dispatch(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderDispatch::class, 'purchase_order_dispatch_id');
    }

    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_acknowledgement_id');
    }

    public function successor(): HasOne
    {
        return $this->hasOne(self::class, 'supersedes_acknowledgement_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /** @return HasMany<SupplierAcknowledgementItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(SupplierAcknowledgementItem::class);
    }

    /** @return HasMany<SupplierAcknowledgementScopeItem, $this> */
    public function scopeItems(): HasMany
    {
        return $this->hasMany(SupplierAcknowledgementScopeItem::class);
    }

    protected function casts(): array
    {
        return [
            'source' => SupplierAcknowledgementSource::class,
            'acknowledgement_received_at' => 'immutable_datetime',
            'status' => SupplierAcknowledgementStatus::class,
            'requires_follow_up' => 'boolean',
            'requires_replanning' => 'boolean',
        ];
    }
}
