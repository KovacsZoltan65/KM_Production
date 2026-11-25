<?php

use App\Models\Customer;
use App\Models\Item;
use App\Models\ItemInstance;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\RmaSchemaFixture;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->rma = new RmaSchemaFixture;
});

it('creates all approved tables and preserves inventory column definitions', function (): void {
    foreach (['repair_commands', 'repair_requests', 'repair_request_items', 'repair_orders', 'active_repair_claims', 'product_warranty_rules', 'product_handover_evidences', 'warranty_assessments', 'repair_status_histories'] as $table) {
        expect(Schema::hasTable($table))->toBeTrue();
        expect(Schema::hasColumn($table, 'deleted_at'))->toBeFalse();
    }
    expect(Schema::hasColumn('active_repair_claims', 'id'))->toBeFalse();
    expect(Schema::hasColumn('repair_request_items', 'received_at'))->toBeFalse();
    expect(Schema::hasColumn('repair_orders', 'diagnostic_fee_mode'))->toBeFalse();
    expect(Schema::hasColumn('product_handover_evidences', 'repair_release_id'))->toBeFalse();
    expect(Schema::hasIndex('item_instances', ['id', 'item_id'], 'unique'))->toBeTrue();
    expect(Schema::getColumnListing('item_instances'))->toBe(['id', 'item_id', 'serial_number', 'factory_unit_id', 'current_location_id', 'current_status', 'production_order_id', 'created_at', 'updated_at']);
});

it('acquires one typed claim atomically with membership', function (): void {
    $case = $this->rma->request();
    $member = $this->rma->member($case);
    $claim = DB::table('active_repair_claims')->sole();
    expect((int) $claim->repair_request_item_id)->toBe($member)
        ->and((int) $claim->item_instance_id)->toBe($this->rma->serial->id)
        ->and((int) $claim->claimed_by)->toBe($this->rma->actor->id);
    expect(DB::table('stock_movements')->count())->toBe(0);
});

it('rejects a duplicate active serial and rolls back the losing member statement', function (): void {
    $this->rma->member($this->rma->request());
    $second = $this->rma->request();
    expect(fn () => $this->rma->member($second))->toThrow(QueryException::class);
    expect(DB::table('repair_request_items')->where('repair_request_id', $second)->exists())->toBeFalse();
    expect(DB::table('active_repair_claims')->count())->toBe(1);
});

it('requires the correct owner and opening command for membership', function (string $field): void {
    $case = $this->rma->request();
    $value = $field === 'owner_customer_id' ? Customer::factory()->create()->id : $this->rma->command();
    expect(fn () => $this->rma->member($case, [$field => $value]))->toThrow(QueryException::class);
    expect(DB::table('active_repair_claims')->count())->toBe(0);
})->with(['owner_customer_id', 'membership_command_id']);

it('prohibits claim deletion and alteration and case closure', function (): void {
    $case = $this->rma->request();
    $this->rma->member($case);
    expect(fn () => DB::table('active_repair_claims')->delete())->toThrow(QueryException::class);
    expect(fn () => DB::table('active_repair_claims')->update(['claimed_by' => $this->rma->actor->id]))->toThrow(QueryException::class);
    expect(fn () => DB::table('repair_requests')->where('id', $case)->update(['status' => 'closed']))->toThrow(QueryException::class);
    expect(fn () => $this->rma->request(['status' => 'closed']))->toThrow(QueryException::class);
});

it('permits only command completion and then protects its identity and result', function (): void {
    $command = $this->rma->command();
    expect(fn () => DB::table('repair_commands')->where('id', $command)->update(['actor_id' => $this->rma->actor->id]))->toThrow(QueryException::class);
    DB::table('repair_commands')->where('id', $command)->update(['status' => 'completed', 'completed_at' => RmaSchemaFixture::TIME, 'result_snapshot' => '{}']);
    expect(fn () => DB::table('repair_commands')->where('id', $command)->update(['result_snapshot' => '{"changed":true}']))->toThrow(QueryException::class);
    expect(fn () => DB::table('repair_commands')->where('id', $command)->delete())->toThrow(QueryException::class);
});

it('enforces command key uniqueness and command shape', function (): void {
    $this->rma->command(['idempotency_key' => 'same']);
    expect(fn () => $this->rma->command(['idempotency_key' => 'same']))->toThrow(QueryException::class);
    expect(fn () => $this->rma->command(['status' => 'completed']))->toThrow(QueryException::class);
    expect(fn () => $this->rma->command(['actor_id' => 999999999]))->toThrow(QueryException::class);
});

it('rejects invalid rule values', function (array $changes): void {
    expect(fn () => $this->rma->rule($changes))->toThrow(QueryException::class);
})->with(array_map(fn (array $changes): array => [$changes], [['version' => 0], ['duration_value' => 0], ['duration_unit' => 'week']]));

it('allows a pending assessment and enforces typed item and proof tuples', function (): void {
    $order = $this->rma->order($this->rma->member($this->rma->request()));
    $this->rma->assessment($order);
    expect(fn () => $this->rma->assessment($order, ['version' => 2, 'subject_item_id' => Item::factory()->create()->id]))->toThrow(QueryException::class);
    expect(fn () => $this->rma->assessment($order, ['version' => 2, 'classification' => 'unknown']))->toThrow(QueryException::class);
    expect(fn () => $this->rma->assessment($order, ['version' => 2, 'is_override' => 1]))->toThrow(QueryException::class);
    expect(fn () => $this->rma->assessment($order, ['version' => 2, 'warranty_end' => RmaSchemaFixture::TIME]))->toThrow(QueryException::class);
    $otherRule = $this->rma->rule(['item_id' => Item::factory()->create()->id]);
    expect(fn () => $this->rma->assessment($order, ['version' => 2, 'product_warranty_rule_id' => $otherRule]))->toThrow(QueryException::class);
    $otherHandover = $this->rma->handover($this->rma->document()->id, ['item_instance_id' => ItemInstance::factory()->create()->id]);
    expect(fn () => $this->rma->assessment($order, ['version' => 2, 'handover_evidence_id' => $otherHandover]))->toThrow(QueryException::class);
});

it('protects every first-slice immutable record', function (string $table): void {
    $case = $this->rma->request();
    $member = $this->rma->member($case);
    $order = $this->rma->order($member);
    $this->rma->rule();
    $this->rma->handover($this->rma->document()->id);
    $this->rma->assessment($order);
    $this->rma->history($case);
    expect(fn () => DB::table($table)->update(['created_at' => RmaSchemaFixture::TIME]))->toThrow(QueryException::class);
    expect(fn () => DB::table($table)->delete())->toThrow(QueryException::class);
})->with(['repair_requests', 'repair_request_items', 'repair_orders', 'active_repair_claims', 'product_warranty_rules', 'product_handover_evidences', 'warranty_assessments', 'repair_status_histories']);

it('protects evidence reached through either handover or authorization history', function (string $source): void {
    $document = $this->rma->document();
    if ($source === 'handover') {
        $this->rma->handover($document->id);
    } else {
        $this->rma->history($this->rma->request(), ['authorization_document_id' => $document->id]);
    }
    foreach (['checksum' => str_repeat('a', 64), 'path' => 'changed.pdf', 'version' => 2, 'deleted_at' => RmaSchemaFixture::TIME, 'documentable_id' => 999999] as $column => $value) {
        expect(fn () => DB::table('documents')->where('id', $document->id)->update([$column => $value]))->toThrow(QueryException::class);
    }
    expect(fn () => DB::table('documents')->where('id', $document->id)->delete())->toThrow(QueryException::class);
    DB::table('documents')->where('id', $document->id)->update(['title' => 'Permitted title', 'is_current' => false]);
    expect(DB::table('documents')->where('id', $document->id)->value('title'))->toBe('Permitted title');
})->with(['handover', 'history']);

it('rejects incomplete or deleted document evidence', function (array $changes): void {
    $document = $this->rma->document();
    DB::table('documents')->where('id', $document->id)->update($changes);
    expect(fn () => $this->rma->handover($document->id))->toThrow(QueryException::class);
    expect(fn () => $this->rma->history($this->rma->request(), ['authorization_document_id' => $document->id]))->toThrow(QueryException::class);
})->with(array_map(fn (array $changes): array => [$changes], [['checksum' => null], ['checksum' => str_repeat('z', 64)], ['version' => 0], ['disk' => null], ['path' => null, 'file_path' => null], ['deleted_at' => RmaSchemaFixture::TIME]]));

it('appends same-serial handover corrections and rejects foreign predecessors', function (): void {
    $document = $this->rma->document();
    $first = $this->rma->handover($document->id);
    $this->rma->handover($document->id, ['supersedes_evidence_id' => $first]);
    expect(fn () => $this->rma->handover($document->id, ['supersedes_evidence_id' => $first]))->toThrow(QueryException::class);
    $foreign = $this->rma->handover($document->id, ['item_instance_id' => ItemInstance::factory()->create()->id]);
    expect(fn () => $this->rma->handover($document->id, ['supersedes_evidence_id' => $foreign]))->toThrow(QueryException::class);
});

it('protects referenced serial identity while allowing existing inventory projections', function (string $source): void {
    if ($source === 'member') {
        $this->rma->member($this->rma->request());
    } else {
        $this->rma->handover($this->rma->document()->id);
    }
    foreach (['serial_number' => 'changed', 'item_id' => Item::factory()->create()->id, 'production_order_id' => null] as $field => $value) {
        expect(fn () => DB::table('item_instances')->where('id', $this->rma->serial->id)->update([$field => $value]))->toThrow(QueryException::class);
    }
    DB::table('item_instances')->where('id', $this->rma->serial->id)->update(['current_status' => 'available']);
    expect(fn () => DB::table('item_instances')->where('id', $this->rma->serial->id)->delete())->toThrow(QueryException::class);
    $unrelated = ItemInstance::factory()->create();
    $unrelated->update(['serial_number' => 'unrelated-update']);
    expect($unrelated->refresh()->serial_number)->toBe('unrelated-update');
})->with(['member', 'handover']);

it('protects only RMA audit rows without claiming audit orchestration proof', function (): void {
    $id = DB::table('activity_log')->insertGetId(['log_name' => 'rma', 'description' => 'Fixture audit']);
    expect(fn () => DB::table('activity_log')->where('id', $id)->update(['log_name' => 'other']))->toThrow(QueryException::class);
    expect(fn () => DB::table('activity_log')->where('id', $id)->delete())->toThrow(QueryException::class);
    $other = DB::table('activity_log')->insertGetId(['log_name' => 'other', 'description' => 'Legacy audit']);
    expect(DB::table('activity_log')->where('id', $other)->delete())->toBe(1);
});

it('enforces history XOR pair completeness sequence and dimension', function (array $changes): void {
    expect(fn () => $this->rma->history($this->rma->request(), $changes))->toThrow(QueryException::class);
})->with(array_map(fn (array $changes): array => [$changes], [['repair_request_id' => null], ['item_instance_id' => 1], ['event_sequence' => 0], ['state_dimension' => 'finance']]));

it('rolls back all database writes when a later statement fails', function (): void {
    $initial = DB::table('repair_commands')->count();
    expect(function (): void {
        DB::transaction(function (): void {
            $case = $this->rma->request();
            $this->rma->order($this->rma->member($case));
            $this->rma->history($case);
            throw new RuntimeException('Injected database caller failure; no service exists yet');
        });
    })->toThrow(RuntimeException::class);
    expect(DB::table('repair_commands')->count())->toBe($initial);
    foreach (['repair_requests', 'repair_request_items', 'repair_orders', 'active_repair_claims', 'repair_status_histories'] as $table) {
        expect(DB::table($table)->count())->toBe(0);
    }
});

it('rolls back a multi-serial opening after a later claim conflicts', function (): void {
    $this->rma->member($this->rma->request());
    $unclaimed = ItemInstance::factory()->create(['item_id' => $this->rma->serial->item_id, 'production_order_id' => $this->rma->origin->id]);
    $commands = DB::table('repair_commands')->count();
    expect(function () use ($unclaimed): void {
        DB::transaction(function () use ($unclaimed): void {
            $case = $this->rma->request();
            $this->rma->order($this->rma->member($case, ['item_instance_id' => $unclaimed->id]));
            $this->rma->member($case);
        });
    })->toThrow(QueryException::class);
    expect(DB::table('repair_requests')->count())->toBe(1);
    expect(DB::table('repair_request_items')->count())->toBe(1);
    expect(DB::table('active_repair_claims')->count())->toBe(1);
    expect(DB::table('repair_orders')->count())->toBe(0);
    expect(DB::table('repair_commands')->count())->toBe($commands);
});

it('can roll back domain rows after an audit INSERT failure without claiming service proof', function (): void {
    expect(function (): void {
        DB::transaction(function (): void {
            $case = $this->rma->request();
            $this->rma->order($this->rma->member($case));
            $this->rma->history($case);
            // Deliberately violates the existing NOT NULL audit description.
            DB::table('activity_log')->insert(['log_name' => 'rma']);
        });
    })->toThrow(QueryException::class);
    foreach (['repair_commands', 'repair_requests', 'repair_request_items', 'repair_orders', 'active_repair_claims', 'repair_status_histories'] as $table) {
        expect(DB::table($table)->count())->toBe(0);
    }
});
