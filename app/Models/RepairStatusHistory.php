<?php

namespace App\Models;

use App\Enums\RepairStateDimension;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Persistence mapping only; services own authorization, transactions and audit.
 *
 * @property int $id
 * @property int|null $repair_request_id
 * @property int|null $repair_order_id
 * @property int|null $repair_request_item_id
 * @property int|null $item_instance_id
 * @property RepairStateDimension $state_dimension
 * @property string|null $from_state
 * @property string $to_state
 * @property int $actor_id
 * @property Carbon $occurred_at
 * @property string $reason
 * @property array<array-key, mixed> $metadata
 * @property int|null $authorization_document_id
 * @property int $command_id
 * @property int $event_sequence
 * @property Carbon $created_at
 * @property-read RepairRequest|null $repairRequest
 * @property-read RepairOrder|null $repairOrder
 * @property-read RepairRequestItem|null $repairRequestItem
 * @property-read ItemInstance|null $itemInstance
 * @property-read User|null $actor
 * @property-read Document|null $authorizationDocument
 * @property-read RepairCommand|null $command
 */
class RepairStatusHistory extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'repair_status_histories';

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
     * @return BelongsTo<RepairOrder, $this>
     */
    public function repairOrder(): BelongsTo
    {
        return $this->belongsTo(RepairOrder::class, 'repair_order_id', 'id');
    }

    /**
     * @return BelongsTo<RepairRequestItem, $this>
     */
    public function repairRequestItem(): BelongsTo
    {
        return $this->belongsTo(RepairRequestItem::class, 'repair_request_item_id', 'id');
    }

    /**
     * @return BelongsTo<ItemInstance, $this>
     */
    public function itemInstance(): BelongsTo
    {
        return $this->belongsTo(ItemInstance::class, 'item_instance_id', 'id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id', 'id');
    }

    /**
     * @return BelongsTo<Document, $this>
     */
    public function authorizationDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'authorization_document_id', 'id')->withTrashed();
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
            'id' => 'integer',
            'repair_request_id' => 'integer',
            'repair_order_id' => 'integer',
            'repair_request_item_id' => 'integer',
            'item_instance_id' => 'integer',
            'state_dimension' => RepairStateDimension::class,
            'actor_id' => 'integer',
            'occurred_at' => 'datetime',
            'metadata' => 'array',
            'authorization_document_id' => 'integer',
            'command_id' => 'integer',
            'event_sequence' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
