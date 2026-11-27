<?php

namespace App\Models;

use App\Enums\RepairRequestStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Persistence mapping only; services own authorization, transactions and audit.
 *
 * @property int $id
 * @property string $case_number
 * @property int $owner_customer_id
 * @property int $created_by
 * @property int $creator_employee_id
 * @property int|null $reporter_employee_id
 * @property string $reporter_name
 * @property array<array-key, mixed> $reporter_contact_snapshot
 * @property array<array-key, mixed> $owner_snapshot
 * @property RepairRequestStatus $status
 * @property Carbon|null $closed_at
 * @property int|null $closed_by
 * @property int $opening_command_id
 * @property int|null $closing_command_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Customer|null $ownerCustomer
 * @property-read User|null $creator
 * @property-read Employee|null $creatorEmployee
 * @property-read Employee|null $reporterEmployee
 * @property-read User|null $closer
 * @property-read RepairCommand|null $openingCommand
 * @property-read RepairCommand|null $closingCommand
 * @property-read Collection<int, RepairRequestItem> $items
 * @property-read Collection<int, RepairStatusHistory> $statusHistories
 */
class RepairRequest extends Model
{
    protected $table = 'repair_requests';

    // Explicit assignment only: actor, command and lifecycle fields are never payload-fillable.
    protected $guarded = ['*'];

    protected $dateFormat = 'Y-m-d H:i:s.u';

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function ownerCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'owner_customer_id', 'id')->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function creatorEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'creator_employee_id', 'id')->withTrashed();
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function reporterEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reporter_employee_id', 'id')->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by', 'id');
    }

    /**
     * @return BelongsTo<RepairCommand, $this>
     */
    public function openingCommand(): BelongsTo
    {
        return $this->belongsTo(RepairCommand::class, 'opening_command_id', 'id');
    }

    /**
     * @return BelongsTo<RepairCommand, $this>
     */
    public function closingCommand(): BelongsTo
    {
        return $this->belongsTo(RepairCommand::class, 'closing_command_id', 'id');
    }

    /**
     * @return HasMany<RepairRequestItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(RepairRequestItem::class, 'repair_request_id', 'id');
    }

    /**
     * @return HasMany<RepairStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(RepairStatusHistory::class, 'repair_request_id', 'id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'owner_customer_id' => 'integer',
            'created_by' => 'integer',
            'creator_employee_id' => 'integer',
            'reporter_employee_id' => 'integer',
            'reporter_contact_snapshot' => 'array',
            'owner_snapshot' => 'array',
            'status' => RepairRequestStatus::class,
            'closed_at' => 'datetime',
            'closed_by' => 'integer',
            'opening_command_id' => 'integer',
            'closing_command_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
