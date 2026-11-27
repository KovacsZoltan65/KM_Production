<?php

namespace App\Models;

use App\Enums\RepairExecutionStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Persistence mapping only; services own authorization, transactions and audit.
 *
 * @property int $id
 * @property int $repair_request_item_id
 * @property string $work_order_number
 * @property RepairExecutionStatus $execution_status
 * @property int $current_attempt_number
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read RepairRequestItem|null $repairRequestItem
 * @property-read Collection<int, WarrantyAssessment> $warrantyAssessments
 * @property-read Collection<int, RepairStatusHistory> $statusHistories
 */
class RepairOrder extends Model
{
    protected $table = 'repair_orders';

    // Explicit assignment only: actor, command and lifecycle fields are never payload-fillable.
    protected $guarded = ['*'];

    protected $dateFormat = 'Y-m-d H:i:s.u';

    /**
     * @return BelongsTo<RepairRequestItem, $this>
     */
    public function repairRequestItem(): BelongsTo
    {
        return $this->belongsTo(RepairRequestItem::class, 'repair_request_item_id', 'id');
    }

    /**
     * @return HasMany<WarrantyAssessment, $this>
     */
    public function warrantyAssessments(): HasMany
    {
        return $this->hasMany(WarrantyAssessment::class, 'repair_order_id', 'id');
    }

    /**
     * @return HasMany<RepairStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(RepairStatusHistory::class, 'repair_order_id', 'id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'repair_request_item_id' => 'integer',
            'execution_status' => RepairExecutionStatus::class,
            'current_attempt_number' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
