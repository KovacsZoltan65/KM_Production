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
 * @property string $unit
 * @property PurchaseRequisitionItemStatus $status
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Item|null $item
 * @property-read MaterialRequirement|null $materialRequirement
 * @property-read PurchaseRequisition|null $purchaseRequisition
 * @property-read Collection<int, PurchaseRequisitionItemSource> $sources
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
    'unit',
    'status',
    'notes',
])]
class PurchaseRequisitionItem extends Model
{
    /** @use HasFactory<PurchaseRequisitionItemFactory> */
    use HasFactory, SoftDeletes;

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

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'status' => PurchaseRequisitionItemStatus::class,
        ];
    }
}
