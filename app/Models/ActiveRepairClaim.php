<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Trigger-created projection; retrieval only. No application acquisition/release workflow.
 *
 * @property int $item_instance_id
 * @property int $repair_request_item_id
 * @property int $claimed_by
 * @property int $command_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read ItemInstance|null $itemInstance
 * @property-read RepairRequestItem|null $repairRequestItem
 * @property-read User|null $claimer
 * @property-read RepairCommand|null $command
 */
class ActiveRepairClaim extends Model
{
    protected $table = 'active_repair_claims';

    // Explicit assignment only: actor, command and lifecycle fields are never payload-fillable.
    protected $guarded = ['*'];

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $primaryKey = 'item_instance_id';

    public $incrementing = false;

    /**
     * @return BelongsTo<ItemInstance, $this>
     */
    public function itemInstance(): BelongsTo
    {
        return $this->belongsTo(ItemInstance::class, 'item_instance_id', 'id');
    }

    /**
     * @return BelongsTo<RepairRequestItem, $this>
     */
    public function repairRequestItem(): BelongsTo
    {
        return $this->belongsTo(RepairRequestItem::class, 'repair_request_item_id', 'id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function claimer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by', 'id');
    }

    /**
     * @return BelongsTo<RepairCommand, $this>
     */
    public function command(): BelongsTo
    {
        return $this->belongsTo(RepairCommand::class, 'command_id', 'id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'item_instance_id' => 'integer',
            'repair_request_item_id' => 'integer',
            'claimed_by' => 'integer',
            'command_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
