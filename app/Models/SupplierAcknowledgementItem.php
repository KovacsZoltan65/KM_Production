<?php

namespace App\Models;

use App\Enums\SupplierAcknowledgementDeliveryDateVariance;
use App\Enums\SupplierAcknowledgementLineStatus;
use App\Enums\SupplierAcknowledgementQuantityVariance;
use App\Models\Concerns\AppendOnly;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $supplier_acknowledgement_id
 * @property int $purchase_order_item_id
 * @property SupplierAcknowledgementLineStatus $line_status
 * @property string|null $promised_quantity
 * @property CarbonImmutable|null $promised_delivery_date
 * @property string $ordered_quantity_snapshot
 * @property string $unit_snapshot
 * @property CarbonImmutable|null $buyer_requested_delivery_date_snapshot
 * @property SupplierAcknowledgementQuantityVariance $quantity_variance
 * @property string|null $quantity_variance_amount
 * @property SupplierAcknowledgementDeliveryDateVariance $delivery_date_variance
 * @property string|null $notes
 */
#[Fillable([
    'supplier_acknowledgement_id', 'purchase_order_item_id', 'line_status',
    'promised_quantity', 'promised_delivery_date', 'ordered_quantity_snapshot',
    'unit_snapshot', 'buyer_requested_delivery_date_snapshot', 'quantity_variance',
    'quantity_variance_amount', 'delivery_date_variance', 'notes',
])]
class SupplierAcknowledgementItem extends Model
{
    use AppendOnly;

    public function acknowledgement(): BelongsTo
    {
        return $this->belongsTo(SupplierAcknowledgement::class, 'supplier_acknowledgement_id');
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class)->withTrashed();
    }

    protected function casts(): array
    {
        return [
            'line_status' => SupplierAcknowledgementLineStatus::class,
            'promised_quantity' => 'decimal:3',
            'promised_delivery_date' => 'immutable_date',
            'ordered_quantity_snapshot' => 'decimal:3',
            'buyer_requested_delivery_date_snapshot' => 'immutable_date',
            'quantity_variance' => SupplierAcknowledgementQuantityVariance::class,
            'quantity_variance_amount' => 'decimal:3',
            'delivery_date_variance' => SupplierAcknowledgementDeliveryDateVariance::class,
        ];
    }
}
