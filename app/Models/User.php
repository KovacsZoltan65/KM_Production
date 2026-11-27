<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property-read Collection<int, RepairCommand> $repairCommands
 * @property-read Collection<int, RepairRequest> $createdRepairRequests
 * @property-read Collection<int, RepairRequest> $closedRepairRequests
 * @property-read Collection<int, RepairRequestItem> $originVerifiedRepairRequestItems
 * @property-read Collection<int, ActiveRepairClaim> $activeRepairClaims
 * @property-read Collection<int, ProductWarrantyRule> $approvedProductWarrantyRules
 * @property-read Collection<int, ProductHandoverEvidence> $verifiedProductHandoverEvidences
 * @property-read Collection<int, WarrantyAssessment> $warrantyAssessments
 * @property-read Collection<int, RepairStatusHistory> $repairStatusHistories
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Employee|null $employee
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read Collection<int, Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read Collection<int, Role> $roles
 * @property-read int|null $roles_count
 * @property-read Collection<int, Permission> $teams
 * @property-read int|null $teams_count
 *
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User permission($permissions, bool $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User role($roles, ?string $guard = null, bool $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User team($teams, bool $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutRole($roles, ?string $guard = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTeam($teams)
 *
 * @mixin \Eloquent
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * @return HasOne<Employee, $this>
     */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * @return HasMany<RepairCommand, $this>
     */
    public function repairCommands(): HasMany
    {
        return $this->hasMany(RepairCommand::class, 'actor_id', 'id');
    }

    /**
     * @return HasMany<RepairRequest, $this>
     */
    public function createdRepairRequests(): HasMany
    {
        return $this->hasMany(RepairRequest::class, 'created_by', 'id');
    }

    /**
     * @return HasMany<RepairRequest, $this>
     */
    public function closedRepairRequests(): HasMany
    {
        return $this->hasMany(RepairRequest::class, 'closed_by', 'id');
    }

    /**
     * @return HasMany<RepairRequestItem, $this>
     */
    public function originVerifiedRepairRequestItems(): HasMany
    {
        return $this->hasMany(RepairRequestItem::class, 'origin_verified_by', 'id');
    }

    /**
     * @return HasMany<ActiveRepairClaim, $this>
     */
    public function activeRepairClaims(): HasMany
    {
        return $this->hasMany(ActiveRepairClaim::class, 'claimed_by', 'id');
    }

    /**
     * @return HasMany<ProductWarrantyRule, $this>
     */
    public function approvedProductWarrantyRules(): HasMany
    {
        return $this->hasMany(ProductWarrantyRule::class, 'approved_by', 'id');
    }

    /**
     * @return HasMany<ProductHandoverEvidence, $this>
     */
    public function verifiedProductHandoverEvidences(): HasMany
    {
        return $this->hasMany(ProductHandoverEvidence::class, 'verified_by', 'id');
    }

    /**
     * @return HasMany<WarrantyAssessment, $this>
     */
    public function warrantyAssessments(): HasMany
    {
        return $this->hasMany(WarrantyAssessment::class, 'assessed_by', 'id');
    }

    /**
     * @return HasMany<RepairStatusHistory, $this>
     */
    public function repairStatusHistories(): HasMany
    {
        return $this->hasMany(RepairStatusHistory::class, 'actor_id', 'id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
