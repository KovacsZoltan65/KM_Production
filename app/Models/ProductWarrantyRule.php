<?php

namespace App\Models;

use App\Enums\WarrantyDurationUnit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Persistence mapping only; services own authorization, transactions and audit.
 *
 * @property int $id
 * @property int $item_id
 * @property int $version
 * @property Carbon $effective_from
 * @property int $duration_value
 * @property WarrantyDurationUnit $duration_unit
 * @property string $rule_reference
 * @property int $approved_by
 * @property Carbon $approved_at
 * @property int $command_id
 * @property Carbon $created_at
 * @property-read Item|null $item
 * @property-read User|null $approver
 * @property-read RepairCommand|null $command
 * @property-read Collection<int, WarrantyAssessment> $warrantyAssessments
 */
class ProductWarrantyRule extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'product_warranty_rules';

    // Explicit assignment only: actor, command and lifecycle fields are never payload-fillable.
    protected $guarded = ['*'];

    protected $dateFormat = 'Y-m-d H:i:s.u';

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'id')->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }

    /**
     * @return BelongsTo<RepairCommand, $this>
     */
    public function command(): BelongsTo
    {
        return $this->belongsTo(RepairCommand::class, 'command_id', 'id');
    }

    /**
     * @return HasMany<WarrantyAssessment, $this>
     */
    public function warrantyAssessments(): HasMany
    {
        return $this->hasMany(WarrantyAssessment::class, 'product_warranty_rule_id', 'id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'item_id' => 'integer',
            'version' => 'integer',
            'effective_from' => 'datetime',
            'duration_value' => 'integer',
            'duration_unit' => WarrantyDurationUnit::class,
            'approved_by' => 'integer',
            'approved_at' => 'datetime',
            'command_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
