<?php

namespace App\Models;

use Database\Factories\ItemSupplierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Egy Item és Supplier közötti, üzleti feltételekkel rendelkező beszerzési forrás.
 *
 * A conversion factor jelentése: 1 purchase_unit = conversion_factor × Item base unit.
 * A lead_time_days naptári napokat jelent.
 *
 * @property int $id
 * @property int $item_id
 * @property int $supplier_id
 * @property string|null $supplier_item_code
 * @property string $purchase_unit
 * @property numeric $conversion_factor
 * @property numeric|null $minimum_order_quantity
 * @property numeric|null $order_multiple
 * @property numeric|null $unit_price
 * @property string|null $currency
 * @property int|null $lead_time_days
 * @property int $priority
 * @property bool $is_preferred
 * @property bool $is_approved
 * @property bool $is_active
 * @property Carbon|null $valid_from
 * @property Carbon|null $valid_until
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Item $item
 * @property-read Supplier $supplier
 */
#[Fillable([
    'item_id',
    'supplier_id',
    'supplier_item_code',
    'purchase_unit',
    'conversion_factor',
    'minimum_order_quantity',
    'order_multiple',
    'unit_price',
    'currency',
    'lead_time_days',
    'priority',
    'is_preferred',
    'is_approved',
    'is_active',
    'valid_from',
    'valid_until',
])]
class ItemSupplier extends Model
{
    /** @use HasFactory<ItemSupplierFactory> */
    use HasFactory;

    /**
     * @param  Builder<ItemSupplier>  $query
     * @return Builder<ItemSupplier>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<ItemSupplier>  $query
     * @return Builder<ItemSupplier>
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true);
    }

    /**
     * @param  Builder<ItemSupplier>  $query
     * @return Builder<ItemSupplier>
     */
    public function scopePreferred(Builder $query): Builder
    {
        return $query->where('is_preferred', true);
    }

    /**
     * @param  Builder<ItemSupplier>  $query
     * @return Builder<ItemSupplier>
     */
    public function scopeValidAt(Builder $query, Carbon $date): Builder
    {
        return $query
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('valid_from')
                ->orWhereDate('valid_from', '<=', $date))
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('valid_until')
                ->orWhereDate('valid_until', '>=', $date));
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'conversion_factor' => 'decimal:6',
            'minimum_order_quantity' => 'decimal:3',
            'order_multiple' => 'decimal:3',
            'unit_price' => 'decimal:4',
            'lead_time_days' => 'integer',
            'priority' => 'integer',
            'is_preferred' => 'boolean',
            'is_approved' => 'boolean',
            'is_active' => 'boolean',
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }
}
