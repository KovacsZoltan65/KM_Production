<?php

namespace App\Models;

use App\Enums\PurchaseRequisitionStatus;
use Database\Factories\PurchaseRequisitionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'requisition_number',
    'status',
    'requested_by',
    'requested_at',
    'notes',
])]
class PurchaseRequisition extends Model
{
    /** @use HasFactory<PurchaseRequisitionFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * @return HasMany<PurchaseRequisitionItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionItem::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PurchaseRequisitionStatus::class,
            'requested_at' => 'datetime',
        ];
    }
}
