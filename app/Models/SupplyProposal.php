<?php

namespace App\Models;

use App\Enums\SupplyProposalStatus;
use App\Enums\SupplyStrategy;
use Database\Factories\SupplyProposalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Planning artifact: egy Item fedezésére tett, emberi döntésre váró javaslat.
 * Nem execution dokumentum és nem tényleges ellátási tény.
 *
 * @property int $id
 * @property SupplyStrategy $strategy
 * @property int $item_id
 * @property int|null $supplier_id
 * @property numeric $proposed_quantity
 * @property string $unit
 * @property Carbon|null $required_at
 * @property Carbon|null $proposed_supply_at
 * @property SupplyProposalStatus $status
 * @property string|null $reason_code
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $approved_by
 * @property Carbon|null $approved_at
 * @property int|null $rejected_by
 * @property Carbon|null $rejected_at
 * @property int|null $cancelled_by
 * @property Carbon|null $cancelled_at
 * @property-read Item $item
 * @property-read Supplier|null $supplier
 * @property-read User|null $creator
 * @property-read User|null $approver
 * @property-read User|null $rejector
 * @property-read User|null $canceller
 */
#[Fillable([
    'strategy',
    'item_id',
    'supplier_id',
    'proposed_quantity',
    'unit',
    'required_at',
    'proposed_supply_at',
    'status',
    'reason_code',
    'notes',
    'created_by',
    'approved_by',
    'approved_at',
    'rejected_by',
    'rejected_at',
    'cancelled_by',
    'cancelled_at',
])]
class SupplyProposal extends Model
{
    /** @use HasFactory<SupplyProposalFactory> */
    use HasFactory;

    /** @param Builder<SupplyProposal> $query */
    public function scopePurchase(Builder $query): Builder
    {
        return $query->where('strategy', SupplyStrategy::Purchase->value);
    }

    /** @param Builder<SupplyProposal> $query */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', SupplyProposalStatus::Draft->value);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    protected function casts(): array
    {
        return [
            'strategy' => SupplyStrategy::class,
            'proposed_quantity' => 'decimal:3',
            'required_at' => 'date',
            'proposed_supply_at' => 'date',
            'status' => SupplyProposalStatus::class,
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }
}
