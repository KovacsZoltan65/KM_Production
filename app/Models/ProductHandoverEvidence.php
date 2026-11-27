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
 * @property int $item_instance_id
 * @property int $recipient_customer_id
 * @property Carbon $handed_over_at
 * @property string $handover_reference
 * @property int $evidence_document_id
 * @property int $verified_by
 * @property Carbon $verified_at
 * @property int|null $supersedes_evidence_id
 * @property int $command_id
 * @property Carbon $created_at
 * @property-read ItemInstance|null $itemInstance
 * @property-read Customer|null $recipientCustomer
 * @property-read Document|null $evidenceDocument
 * @property-read User|null $verifier
 * @property-read ProductHandoverEvidence|null $supersedesEvidence
 * @property-read ProductHandoverEvidence|null $supersedingEvidence
 * @property-read RepairCommand|null $command
 * @property-read Collection<int, WarrantyAssessment> $warrantyAssessments
 */
class ProductHandoverEvidence extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'product_handover_evidences';

    // Explicit assignment only: actor, command and lifecycle fields are never payload-fillable.
    protected $guarded = ['*'];

    protected $dateFormat = 'Y-m-d H:i:s.u';

    /**
     * @return BelongsTo<ItemInstance, $this>
     */
    public function itemInstance(): BelongsTo
    {
        return $this->belongsTo(ItemInstance::class, 'item_instance_id', 'id');
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function recipientCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'recipient_customer_id', 'id')->withTrashed();
    }

    /**
     * @return BelongsTo<Document, $this>
     */
    public function evidenceDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'evidence_document_id', 'id')->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by', 'id');
    }

    /**
     * @return BelongsTo<ProductHandoverEvidence, $this>
     */
    public function supersedesEvidence(): BelongsTo
    {
        return $this->belongsTo(ProductHandoverEvidence::class, 'supersedes_evidence_id', 'id');
    }

    /**
     * @return HasOne<ProductHandoverEvidence, $this>
     */
    public function supersedingEvidence(): HasOne
    {
        return $this->hasOne(ProductHandoverEvidence::class, 'supersedes_evidence_id', 'id');
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
        return $this->hasMany(WarrantyAssessment::class, 'handover_evidence_id', 'id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'item_instance_id' => 'integer',
            'recipient_customer_id' => 'integer',
            'handed_over_at' => 'datetime',
            'evidence_document_id' => 'integer',
            'verified_by' => 'integer',
            'verified_at' => 'datetime',
            'supersedes_evidence_id' => 'integer',
            'command_id' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
