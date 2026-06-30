<?php

namespace App\Models;

use App\Enums\PurchaseRequisitionStatus;
use Database\Factories\PurchaseRequisitionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $requisition_number
 * @property PurchaseRequisitionStatus $status
 * @property int|null $requested_by
 * @property Carbon|null $requested_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, PurchaseRequisitionItem> $items
 * @property-read int|null $items_count
 * @property-read User|null $requester
 *
 * @method static \Database\Factories\PurchaseRequisitionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequisition newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequisition newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequisition onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequisition query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequisition whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequisition whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequisition whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequisition whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequisition whereRequestedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequisition whereRequestedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequisition whereRequisitionNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequisition whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequisition whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequisition withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseRequisition withoutTrashed()
 *
 * @mixin \Eloquent
 */
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
