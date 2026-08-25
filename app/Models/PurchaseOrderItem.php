<?php

namespace App\Models;

use App\Enums\PurchaseOrderItemStatus;
use Database\Factories\PurchaseOrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $purchase_order_id
 * @property int|null $purchase_requisition_item_id
 * @property int|null $item_supplier_id
 * @property int $item_id
 * @property string|null $item_number_snapshot
 * @property string|null $item_name_snapshot
 * @property numeric $ordered_quantity
 * @property numeric|null $planned_quantity_snapshot
 * @property numeric|null $replenishment_excess_quantity_snapshot
 * @property numeric $received_quantity
 * @property string $unit
 * @property string|null $purchase_unit_snapshot
 * @property numeric|null $conversion_factor_snapshot
 * @property numeric|null $unit_price_snapshot
 * @property string|null $currency_snapshot
 * @property int|null $lead_time_days_snapshot
 * @property numeric|null $minimum_order_quantity_snapshot
 * @property numeric|null $order_multiple_snapshot
 * @property PurchaseOrderItemStatus $status
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Item|null $item
 * @property-read ItemSupplier|null $itemSupplier
 * @property-read PurchaseOrder|null $purchaseOrder
 * @property-read PurchaseRequisitionItem|null $purchaseRequisitionItem
 *
 * @method static \Database\Factories\PurchaseOrderItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem whereOrderedQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem wherePurchaseOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem wherePurchaseRequisitionItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem whereReceivedQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseOrderItem withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'purchase_order_id',
    'purchase_requisition_item_id',
    'item_supplier_id',
    'item_id',
    'item_number_snapshot',
    'item_name_snapshot',
    'ordered_quantity',
    'planned_quantity_snapshot',
    'replenishment_excess_quantity_snapshot',
    'received_quantity',
    'unit',
    'purchase_unit_snapshot',
    'conversion_factor_snapshot',
    'unit_price_snapshot',
    'currency_snapshot',
    'lead_time_days_snapshot',
    'minimum_order_quantity_snapshot',
    'order_multiple_snapshot',
    'status',
    'notes',
])]
class PurchaseOrderItem extends Model
{
    /** @use HasFactory<PurchaseOrderItemFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<PurchaseOrder, $this>
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * @return BelongsTo<PurchaseRequisitionItem, $this>
     */
    public function purchaseRequisitionItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisitionItem::class);
    }

    /** @return BelongsTo<ItemSupplier, $this> */
    public function itemSupplier(): BelongsTo
    {
        return $this->belongsTo(ItemSupplier::class);
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ordered_quantity' => 'decimal:3',
            'planned_quantity_snapshot' => 'decimal:3',
            'replenishment_excess_quantity_snapshot' => 'decimal:3',
            'received_quantity' => 'decimal:3',
            'conversion_factor_snapshot' => 'decimal:6',
            'unit_price_snapshot' => 'decimal:4',
            'lead_time_days_snapshot' => 'integer',
            'minimum_order_quantity_snapshot' => 'decimal:3',
            'order_multiple_snapshot' => 'decimal:3',
            'status' => PurchaseOrderItemStatus::class,
        ];
    }
}
