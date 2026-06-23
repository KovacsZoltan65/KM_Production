<?php

namespace App\Models;

use Database\Factories\ProductionTaskMaterialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $production_task_id
 * @property int $item_id
 * @property int|null $item_batch_id
 * @property numeric $planned_quantity
 * @property numeric $used_quantity
 * @property string $unit
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Item|null $item
 * @property-read \App\Models\ItemBatch|null $itemBatch
 * @property-read \App\Models\ProductionTask $productionTask
 * @method static \Database\Factories\ProductionTaskMaterialFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionTaskMaterial newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionTaskMaterial newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionTaskMaterial query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionTaskMaterial whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionTaskMaterial whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionTaskMaterial whereItemBatchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionTaskMaterial whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionTaskMaterial whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionTaskMaterial wherePlannedQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionTaskMaterial whereProductionTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionTaskMaterial whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionTaskMaterial whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionTaskMaterial whereUsedQuantity($value)
 * @mixin \Eloquent
 */
#[Fillable([
    'production_task_id',
    'item_id',
    'item_batch_id',
    'planned_quantity',
    'used_quantity',
    'unit',
    'notes',
])]
class ProductionTaskMaterial extends Model
{
    /** @use HasFactory<ProductionTaskMaterialFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<ProductionTask, $this>
     */
    public function productionTask(): BelongsTo
    {
        return $this->belongsTo(ProductionTask::class);
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * @return BelongsTo<ItemBatch, $this>
     */
    public function itemBatch(): BelongsTo
    {
        return $this->belongsTo(ItemBatch::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'planned_quantity' => 'decimal:3',
            'used_quantity' => 'decimal:3',
        ];
    }
}
