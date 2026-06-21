<?php

namespace App\Models;

use Database\Factories\ProductionTaskMaterialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
