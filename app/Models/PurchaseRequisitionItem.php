<?php

namespace App\Models;

use App\Enums\PurchaseRequisitionItemStatus;
use Database\Factories\PurchaseRequisitionItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $purchase_requisition_id
 * @property int|null $material_requirement_id
 * @property int $item_id
 * @property numeric $quantity
 * @property numeric $planned_quantity
 * @property numeric $replenishment_excess_quantity
 * @property int|null $replenishment_item_supplier_id
 * @property numeric|null $replenishment_minimum_order_quantity
 * @property numeric|null $replenishment_order_multiple
 * @property string|null $replenishment_strategy
 * @property Carbon|null $replenishment_calculated_at
 * @property string $unit
 * @property PurchaseRequisitionItemStatus $status
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Item|null $item
 * @property-read MaterialRequirement|null $materialRequirement
 * @property-read PurchaseRequisition|null $purchaseRequisition
 * @property-read ItemSupplier|null $replenishmentItemSupplier
 * @property-read Collection<int, PurchaseRequisitionItemSource> $sources
 * @property-read Collection<int, PurchaseRequisitionItemProposalSource> $proposalSources
 * @property-read int|null $sources_count
 * @property-read Collection<int, MaterialRequirement> $sourceMaterialRequirements
 * @property-read int|null $source_material_requirements_count
 *
 * @method static \Database\Factories\PurchaseRequisitionItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequisitionItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequisitionItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequisitionItem onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequisitionItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequisitionItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequisitionItem whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequisitionItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequisitionItem whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequisitionItem whereMaterialRequirementId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequisitionItem whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequisitionItem wherePurchaseRequisitionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequisitionItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequisitionItem whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequisitionItem whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequisitionItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequisitionItem withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequisitionItem withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'purchase_requisition_id',
    'material_requirement_id',
    'item_id',
    'quantity',
    'planned_quantity',
    'replenishment_excess_quantity',
    'replenishment_item_supplier_id',
    'replenishment_minimum_order_quantity',
    'replenishment_order_multiple',
    'replenishment_strategy',
    'replenishment_calculated_at',
    'unit',
    'status',
    'notes',
])]
class PurchaseRequisitionItem extends Model
{
    /** @use HasFactory<PurchaseRequisitionItemFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (PurchaseRequisitionItem $item): void {
            $item->planned_quantity ??= $item->quantity;
            $item->replenishment_excess_quantity ??= '0.000';
        });
    }

    /**
     * @return BelongsTo<PurchaseRequisition, $this>
     */
    public function purchaseRequisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class);
    }

    /**
     * @return BelongsTo<MaterialRequirement, $this>
     */
    public function materialRequirement(): BelongsTo
    {
        return $this->belongsTo(MaterialRequirement::class);
    }

    /**
     * @return HasMany<PurchaseRequisitionItemSource, $this>
     */
    public function sources(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionItemSource::class);
    }

    /** @return HasMany<PurchaseRequisitionItemProposalSource, $this> */
    public function proposalSources(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionItemProposalSource::class);
    }

    /**
     * @return BelongsToMany<MaterialRequirement, $this>
     */
    public function sourceMaterialRequirements(): BelongsToMany
    {
        return $this->belongsToMany(
            MaterialRequirement::class,
            'purchase_requisition_item_sources'
        )->withPivot('quantity')->withTimestamps();
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /** @return BelongsTo<ItemSupplier, $this> */
    public function replenishmentItemSupplier(): BelongsTo
    {
        return $this->belongsTo(ItemSupplier::class, 'replenishment_item_supplier_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'planned_quantity' => 'decimal:3',
            'replenishment_excess_quantity' => 'decimal:3',
            'replenishment_minimum_order_quantity' => 'decimal:3',
            'replenishment_order_multiple' => 'decimal:3',
            'replenishment_calculated_at' => 'datetime',
            'status' => PurchaseRequisitionItemStatus::class,
        ];
    }
}
