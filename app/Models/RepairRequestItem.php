<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Persistence mapping only; services own authorization, transactions and audit.
 *
 * @property int $id
 * @property int $repair_request_id
 * @property int $owner_customer_id
 * @property int $item_instance_id
 * @property string $reported_fault
 * @property int $origin_production_order_id
 * @property int $origin_verified_by
 * @property Carbon $origin_verified_at
 * @property array<array-key, mixed> $origin_evidence
 * @property int $membership_command_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read RepairRequest|null $repairRequest
 * @property-read Customer|null $ownerCustomer
 * @property-read ItemInstance|null $itemInstance
 * @property-read ProductionOrder|null $originProductionOrder
 * @property-read User|null $originVerifier
 * @property-read RepairCommand|null $membershipCommand
 * @property-read RepairOrder|null $repairOrder
 * @property-read ActiveRepairClaim|null $activeRepairClaim
 * @property-read Collection<int, RepairStatusHistory> $statusHistories
 */
class RepairRequestItem extends Model
{
    protected $table = 'repair_request_items';

    // Explicit assignment only: actor, command and lifecycle fields are never payload-fillable.
    protected $guarded = ['*'];

    protected $dateFormat = 'Y-m-d H:i:s.u';

    /**
     * @return BelongsTo<RepairRequest, $this>
     */
    public function repairRequest(): BelongsTo
    {
        return $this->belongsTo(RepairRequest::class, 'repair_request_id', 'id');
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function ownerCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'owner_customer_id', 'id')->withTrashed();
    }

    /**
     * @return BelongsTo<ItemInstance, $this>
     */
    public function itemInstance(): BelongsTo
    {
        return $this->belongsTo(ItemInstance::class, 'item_instance_id', 'id');
    }

    /**
     * @return BelongsTo<ProductionOrder, $this>
     */
    public function originProductionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class, 'origin_production_order_id', 'id')->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function originVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'origin_verified_by', 'id');
    }

    /**
     * @return BelongsTo<RepairCommand, $this>
     */
    public function membershipCommand(): BelongsTo
    {
        return $this->belongsTo(RepairCommand::class, 'membership_command_id', 'id');
    }

    /**
     * @return HasOne<RepairOrder, $this>
     */
    public function repairOrder(): HasOne
    {
        return $this->hasOne(RepairOrder::class, 'repair_request_item_id', 'id');
    }

    /**
     * @return HasOne<ActiveRepairClaim, $this>
     */
    public function activeRepairClaim(): HasOne
    {
        return $this->hasOne(ActiveRepairClaim::class, 'repair_request_item_id', 'id');
    }

    /**
     * @return HasMany<RepairStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(RepairStatusHistory::class, 'repair_request_item_id', 'id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'repair_request_id' => 'integer',
            'owner_customer_id' => 'integer',
            'item_instance_id' => 'integer',
            'origin_production_order_id' => 'integer',
            'origin_verified_by' => 'integer',
            'origin_verified_at' => 'datetime',
            'origin_evidence' => 'array',
            'membership_command_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
