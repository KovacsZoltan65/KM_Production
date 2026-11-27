<?php

namespace App\Models;

use App\Enums\RepairCommandStatus;
use Illuminate\Database\Eloquent\Attributes\Hidden;
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
 * @property string $command_type
 * @property string $idempotency_key
 * @property string $payload_hash
 * @property int $actor_id
 * @property RepairCommandStatus $status
 * @property string|null $result_type
 * @property int|null $result_id
 * @property array<array-key, mixed>|null $result_snapshot
 * @property Carbon|null $completed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User|null $actor
 * @property-read RepairRequest|null $openedRepairRequest
 * @property-read RepairRequest|null $closedRepairRequest
 * @property-read Collection<int, RepairRequestItem> $requestItems
 * @property-read Collection<int, ActiveRepairClaim> $activeClaims
 * @property-read Collection<int, ProductWarrantyRule> $warrantyRules
 * @property-read ProductHandoverEvidence|null $handoverEvidence
 * @property-read WarrantyAssessment|null $warrantyAssessment
 * @property-read Collection<int, RepairStatusHistory> $statusHistories
 */
// Keep binary identities/hash as raw bytes; omit non-UTF8 values from JSON.
#[Hidden(['command_type', 'idempotency_key', 'payload_hash', 'result_type'])]
class RepairCommand extends Model
{
    protected $table = 'repair_commands';

    // Explicit assignment only: actor, command and lifecycle fields are never payload-fillable.
    protected $guarded = ['*'];

    protected $dateFormat = 'Y-m-d H:i:s.u';

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id', 'id');
    }

    /**
     * @return HasOne<RepairRequest, $this>
     */
    public function openedRepairRequest(): HasOne
    {
        return $this->hasOne(RepairRequest::class, 'opening_command_id', 'id');
    }

    /**
     * @return HasOne<RepairRequest, $this>
     */
    public function closedRepairRequest(): HasOne
    {
        return $this->hasOne(RepairRequest::class, 'closing_command_id', 'id');
    }

    /**
     * @return HasMany<RepairRequestItem, $this>
     */
    public function requestItems(): HasMany
    {
        return $this->hasMany(RepairRequestItem::class, 'membership_command_id', 'id');
    }

    /**
     * @return HasMany<ActiveRepairClaim, $this>
     */
    public function activeClaims(): HasMany
    {
        return $this->hasMany(ActiveRepairClaim::class, 'command_id', 'id');
    }

    /**
     * @return HasMany<ProductWarrantyRule, $this>
     */
    public function warrantyRules(): HasMany
    {
        return $this->hasMany(ProductWarrantyRule::class, 'command_id', 'id');
    }

    /**
     * @return HasOne<ProductHandoverEvidence, $this>
     */
    public function handoverEvidence(): HasOne
    {
        return $this->hasOne(ProductHandoverEvidence::class, 'command_id', 'id');
    }

    /**
     * @return HasOne<WarrantyAssessment, $this>
     */
    public function warrantyAssessment(): HasOne
    {
        return $this->hasOne(WarrantyAssessment::class, 'command_id', 'id');
    }

    /**
     * @return HasMany<RepairStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(RepairStatusHistory::class, 'command_id', 'id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'actor_id' => 'integer',
            'status' => RepairCommandStatus::class,
            'result_id' => 'integer',
            'result_snapshot' => 'array',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
