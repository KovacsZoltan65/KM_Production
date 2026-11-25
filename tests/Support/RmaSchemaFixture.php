<?php

namespace Tests\Support;

use App\Models\Customer;
use App\Models\Document;
use App\Models\Employee;
use App\Models\ItemInstance;
use App\Models\ProductionOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Database rows only: this fixture does not implement an RMA application service. */
final class RmaSchemaFixture
{
    public const TIME = '2026-11-25 10:00:00.123456';

    public User $actor;

    public Employee $employee;

    public Customer $owner;

    public ItemInstance $serial;

    public ProductionOrder $origin;

    public function __construct()
    {
        $this->actor = User::factory()->create();
        $this->employee = Employee::factory()->create(['user_id' => $this->actor->id]);
        $this->owner = Customer::factory()->create();
        $this->origin = ProductionOrder::factory()->create();
        $this->serial = ItemInstance::factory()->create(['item_id' => $this->origin->item_id, 'production_order_id' => $this->origin->id]);
    }

    public function command(array $changes = []): int
    {
        return DB::table('repair_commands')->insertGetId(array_replace([
            'command_type' => 'case_open', 'idempotency_key' => (string) Str::uuid(),
            'payload_hash' => hash('sha256', 'fixture', true), 'actor_id' => $this->actor->id,
            'created_at' => self::TIME, 'updated_at' => self::TIME,
        ], $changes));
    }

    public function request(array $changes = []): int
    {
        return DB::table('repair_requests')->insertGetId(array_replace([
            'case_number' => (string) Str::uuid(), 'owner_customer_id' => $this->owner->id,
            'created_by' => $this->actor->id, 'creator_employee_id' => $this->employee->id,
            'reporter_name' => 'Fixture reporter', 'reporter_contact_snapshot' => '{}', 'owner_snapshot' => '{}',
            'opening_command_id' => $this->command(), 'created_at' => self::TIME, 'updated_at' => self::TIME,
        ], $changes));
    }

    public function memberData(int $request, array $changes = []): array
    {
        return array_replace([
            'repair_request_id' => $request, 'owner_customer_id' => $this->owner->id,
            'item_instance_id' => $this->serial->id, 'reported_fault' => 'Fixture fault',
            'origin_production_order_id' => $this->origin->id, 'origin_verified_by' => $this->actor->id,
            'origin_verified_at' => self::TIME, 'origin_evidence' => '{}',
            'membership_command_id' => DB::table('repair_requests')->where('id', $request)->value('opening_command_id'),
            'created_at' => self::TIME, 'updated_at' => self::TIME,
        ], $changes);
    }

    public function member(int $request, array $changes = []): int
    {
        return DB::table('repair_request_items')->insertGetId($this->memberData($request, $changes));
    }

    public function order(int $member): int
    {
        return DB::table('repair_orders')->insertGetId([
            'repair_request_item_id' => $member, 'work_order_number' => (string) Str::uuid(),
            'created_at' => self::TIME, 'updated_at' => self::TIME,
        ]);
    }

    public function document(): Document
    {
        return Document::factory()->create(['documentable_type' => $this->serial::class, 'documentable_id' => $this->serial->id]);
    }

    public function handover(int $document, array $changes = []): int
    {
        return DB::table('product_handover_evidences')->insertGetId(array_replace([
            'item_instance_id' => $this->serial->id, 'recipient_customer_id' => $this->owner->id,
            'handed_over_at' => self::TIME, 'handover_reference' => 'sale-fixture', 'evidence_document_id' => $document,
            'verified_by' => $this->actor->id, 'verified_at' => self::TIME, 'command_id' => $this->command(), 'created_at' => self::TIME,
        ], $changes));
    }

    public function rule(array $changes = []): int
    {
        return DB::table('product_warranty_rules')->insertGetId(array_replace([
            'item_id' => $this->serial->item_id, 'version' => 1, 'effective_from' => self::TIME,
            'duration_value' => 12, 'duration_unit' => 'month', 'rule_reference' => 'fixture only',
            'approved_by' => $this->actor->id, 'approved_at' => self::TIME, 'command_id' => $this->command(), 'created_at' => self::TIME,
        ], $changes));
    }

    public function assessment(int $order, array $changes = []): int
    {
        return DB::table('warranty_assessments')->insertGetId(array_replace([
            'repair_order_id' => $order, 'item_instance_id' => $this->serial->id, 'subject_item_id' => $this->serial->item_id,
            'version' => 1, 'classification' => 'pending_assessment', 'rule_snapshot' => '{}',
            'assessed_by' => $this->actor->id, 'assessed_at' => self::TIME, 'justification' => 'Missing proof',
            'command_id' => $this->command(), 'created_at' => self::TIME,
        ], $changes));
    }

    public function history(int $request, array $changes = []): int
    {
        return DB::table('repair_status_histories')->insertGetId(array_replace([
            'repair_request_id' => $request, 'state_dimension' => 'case', 'to_state' => 'open',
            'actor_id' => $this->actor->id, 'occurred_at' => self::TIME, 'reason' => 'Opened', 'metadata' => '{}',
            'command_id' => DB::table('repair_requests')->where('id', $request)->value('opening_command_id'),
            'event_sequence' => 1, 'created_at' => self::TIME,
        ], $changes));
    }
}
