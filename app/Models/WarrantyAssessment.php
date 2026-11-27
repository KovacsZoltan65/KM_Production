<?php

namespace App\Models;

use App\Enums\WarrantyClassification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Persistence mapping only; services own authorization, transactions and audit.
 *
 * @property int $id
 * @property int $repair_order_id
 * @property int $item_instance_id
 * @property int $subject_item_id
 * @property int $version
 * @property WarrantyClassification $classification
 * @property int|null $handover_evidence_id
 * @property int|null $product_warranty_rule_id
 * @property array<array-key, mixed> $rule_snapshot
 * @property Carbon|null $warranty_start
 * @property Carbon|null $warranty_end
 * @property int $assessed_by
 * @property Carbon $assessed_at
 * @property string $justification
 * @property bool $is_override
 * @property string|null $override_reason
 * @property int $command_id
 * @property Carbon $created_at
 * @property-read RepairOrder|null $repairOrder
 * @property-read ItemInstance|null $itemInstance
 * @property-read Item|null $subjectItem
 * @property-read ProductHandoverEvidence|null $handoverEvidence
 * @property-read ProductWarrantyRule|null $productWarrantyRule
 * @property-read User|null $assessor
 * @property-read RepairCommand|null $command
 */
class WarrantyAssessment extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'warranty_assessments';

    // Explicit assignment only: actor, command and lifecycle fields are never payload-fillable.
    protected $guarded = ['*'];

    protected $dateFormat = 'Y-m-d H:i:s.u';

    /**
     * @return BelongsTo<RepairOrder, $this>
     */
    public function repairOrder(): BelongsTo
    {
        return $this->belongsTo(RepairOrder::class, 'repair_order_id', 'id');
    }

    /**
     * @return BelongsTo<ItemInstance, $this>
     */
    public function itemInstance(): BelongsTo
    {
        return $this->belongsTo(ItemInstance::class, 'item_instance_id', 'id');
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function subjectItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'subject_item_id', 'id')->withTrashed();
    }

    /**
     * @return BelongsTo<ProductHandoverEvidence, $this>
     */
    public function handoverEvidence(): BelongsTo
    {
        return $this->belongsTo(ProductHandoverEvidence::class, 'handover_evidence_id', 'id');
    }

    /**
     * @return BelongsTo<ProductWarrantyRule, $this>
     */
    public function productWarrantyRule(): BelongsTo
    {
        return $this->belongsTo(ProductWarrantyRule::class, 'product_warranty_rule_id', 'id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by', 'id');
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
            'repair_order_id' => 'integer',
            'item_instance_id' => 'integer',
            'subject_item_id' => 'integer',
            'version' => 'integer',
            'classification' => WarrantyClassification::class,
            'handover_evidence_id' => 'integer',
            'product_warranty_rule_id' => 'integer',
            'rule_snapshot' => 'array',
            'warranty_start' => 'datetime',
            'warranty_end' => 'datetime',
            'assessed_by' => 'integer',
            'assessed_at' => 'datetime',
            'is_override' => 'boolean',
            'command_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
