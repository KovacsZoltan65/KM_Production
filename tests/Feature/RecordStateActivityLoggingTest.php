<?php

namespace Tests\Feature;

use App\Enums\DocumentType;
use App\Models\FactoryUnit;
use App\Models\User;
use App\Services\Admin\FactoryUnitAdminService;
use App\Services\Admin\UserAdminService;
use App\Services\AuditLogService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Spatie\Activitylog\Actions\LogActivityAction;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditValueFixture extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'document_type' => DocumentType::class,
            'happened_at' => 'datetime',
            'enabled' => 'boolean',
            'payload' => 'array',
        ];
    }
}

class RecordStateActivityLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        LogActivityAction::clearBeforeLoggingCallbacks();

        parent::tearDown();
    }

    public function test_created_activity_contains_allowlisted_attributes_and_business_properties(): void
    {
        $role = Role::query()->create(['name' => 'viewer', 'guard_name' => 'web']);

        $user = app(UserAdminService::class)->create([
            'name' => 'Audit User',
            'email' => 'audit@example.com',
            'password' => 'plain-secret',
            'roles' => [$role->name],
        ]);

        $activity = Activity::query()->where('event', 'admin_user_created')->firstOrFail();
        $changes = $activity->attribute_changes->toArray();

        $this->assertSame($user->getKey(), $changes['attributes']['id']);
        $this->assertSame('Audit User', $changes['attributes']['name']);
        $this->assertSame(['viewer'], $activity->properties->get('roles'));
        $this->assertSensitiveValuesAreAbsent($activity);
    }

    public function test_updated_activity_contains_dirty_only_old_and_new_values(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'stable@example.com',
        ]);
        Activity::query()->delete();

        app(UserAdminService::class)->update($user, [
            'name' => 'New Name',
            'email' => 'stable@example.com',
            'password' => null,
            'roles' => [],
        ]);

        $activity = Activity::query()->where('event', 'admin_user_updated')->firstOrFail();
        $changes = $activity->attribute_changes->toArray();

        $this->assertSame(['name' => 'Old Name'], $changes['old']);
        $this->assertSame(['name' => 'New Name'], $changes['attributes']);
        $this->assertSame(array_keys($changes['old']), array_keys($changes['attributes']));
        $this->assertArrayNotHasKey('updated_at', $changes['attributes']);
    }

    public function test_no_op_admin_update_does_not_create_activity(): void
    {
        $user = User::factory()->create();
        Activity::query()->delete();

        app(UserAdminService::class)->update($user, [
            'name' => $user->name,
            'email' => $user->email,
            'password' => null,
            'roles' => [],
        ]);

        $this->assertSame(0, Activity::query()->where('event', 'admin_user_updated')->count());
    }

    public function test_password_update_is_never_recorded_as_plain_text_or_hash(): void
    {
        $user = User::factory()->create(['name' => 'Before']);
        Activity::query()->delete();

        app(UserAdminService::class)->update($user, [
            'name' => 'After',
            'email' => $user->email,
            'password' => 'replacement-secret',
            'roles' => [],
        ]);

        $activity = Activity::query()->where('event', 'admin_user_updated')->firstOrFail();

        $this->assertSame(['name' => 'Before'], $activity->attribute_changes->get('old'));
        $this->assertSame(['name' => 'After'], $activity->attribute_changes->get('attributes'));
        $this->assertSensitiveValuesAreAbsent($activity);
    }

    public function test_values_are_normalized_deterministically(): void
    {
        $fixture = new AuditValueFixture;
        $fixture->forceFill([
            'document_type' => DocumentType::Drawing,
            'happened_at' => '2026-07-29 10:15:00',
            'enabled' => 1,
            'nullable_value' => null,
            'payload' => ['quantity' => 3, 'valid' => true, 'comment' => null],
        ]);

        $attributes = app(AuditLogService::class)->auditableAttributes($fixture);

        $this->assertSame(DocumentType::Drawing->value, $attributes['document_type']);
        $this->assertSame('2026-07-29T10:15:00+00:00', $attributes['happened_at']);
        $this->assertTrue($attributes['enabled']);
        $this->assertNull($attributes['nullable_value']);
        $this->assertSame(['quantity' => 3, 'valid' => true, 'comment' => null], $attributes['payload']);
    }

    public function test_business_properties_remain_separate_from_attribute_changes(): void
    {
        $factoryUnit = FactoryUnit::factory()->create(['name' => 'Old']);
        $original = $factoryUnit->getRawOriginal();
        $factoryUnit->update(['name' => 'New']);

        app(AuditLogService::class)->logUpdated(
            'factory_unit.test.updated',
            $factoryUnit,
            $original,
            properties: ['source' => 'manual'],
        );

        $activity = Activity::query()->where('event', 'factory_unit.test.updated')->firstOrFail();

        $this->assertSame('manual', $activity->properties->get('source'));
        $this->assertSame(['name' => 'Old'], $activity->attribute_changes->get('old'));
        $this->assertSame(['name' => 'New'], $activity->attribute_changes->get('attributes'));
    }

    public function test_user_role_sync_is_logged_as_relation_change_without_record_dump(): void
    {
        $viewer = Role::query()->create(['name' => 'viewer', 'guard_name' => 'web']);
        $operator = Role::query()->create(['name' => 'operator', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($viewer);
        Activity::query()->delete();

        app(UserAdminService::class)->update($user, [
            'name' => $user->name,
            'email' => $user->email,
            'password' => null,
            'roles' => [$operator->name],
        ]);

        $activity = Activity::query()->where('event', 'admin_user_updated')->firstOrFail();

        $this->assertSame([], $activity->attribute_changes->get('old'));
        $this->assertSame([], $activity->attribute_changes->get('attributes'));
        $this->assertSame([
            'roles' => [
                'old' => ['viewer'],
                'attributes' => ['operator'],
            ],
        ], $activity->properties->get('relations'));
    }

    public function test_audit_failure_rolls_back_common_admin_create(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        LogActivityAction::beforeLogging(static function (): void {
            throw new RuntimeException('Simulated audit failure.');
        });

        try {
            app(FactoryUnitAdminService::class)->create([
                'code' => 'ROLLBACK',
                'name' => 'Must Roll Back',
                'description' => null,
                'daily_capacity_minutes' => 480,
                'shift_count' => 1,
                'is_active' => true,
            ]);

            $this->fail('Az audit hibájának meg kellett szakítania a tranzakciót.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated audit failure.', $exception->getMessage());
        }

        $this->assertDatabaseMissing('factory_units', ['code' => 'ROLLBACK']);
    }

    private function assertSensitiveValuesAreAbsent(Activity $activity): void
    {
        $serialized = json_encode([
            'attribute_changes' => $activity->attribute_changes,
            'properties' => $activity->properties,
        ], JSON_THROW_ON_ERROR);

        $this->assertStringNotContainsString('password', strtolower($serialized));
        $this->assertStringNotContainsString('plain-secret', $serialized);
        $this->assertStringNotContainsString('replacement-secret', $serialized);
        $this->assertStringNotContainsString('$2y$', $serialized);
    }
}
