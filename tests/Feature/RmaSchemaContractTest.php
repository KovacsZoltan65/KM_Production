<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('matches every approved column and nullability against the normative WP2 table', function (): void {
    $contract = file_get_contents(base_path('.kiro/designs/rma-0003c2-wp2-mysql-schema-integrity-review.md'));
    $contract = explode('#### 2.1.3.', explode('#### 2.1.2.', $contract)[1])[0];
    preg_match_all('/^\| (\w+) — [^|]+\| ([^|]+)\|/mu', $contract, $rows, PREG_SET_ORDER);
    expect($rows)->toHaveCount(9);
    foreach ($rows as $row) {
        $table = $row[1];
        $columns = collect(Schema::getColumns($table))->keyBy('name');
        $expected = ['created_at'];
        if ($table !== 'active_repair_claims') {
            $expected[] = 'id';
        }
        if (in_array($table, ['repair_commands', 'repair_requests', 'repair_request_items', 'repair_orders', 'active_repair_claims'], true)) {
            $expected[] = 'updated_at';
        }
        foreach (explode(';', trim($row[2])) as $definition) {
            preg_match('/^\s*(\w+)\s+(.+)$/u', $definition, $field);
            expect($field)->not->toBeEmpty();
            $expected[] = $field[1];
            expect($columns->has($field[1]))->toBeTrue();
            $this->assertSame(str_contains($field[2], '?'), $columns[$field[1]]['nullable'], $table.'.'.$field[1]);
        }
        expect($columns->keys()->sort()->values()->all())->toBe(collect($expected)->sort()->values()->all());
        foreach (Schema::getForeignKeys($table) as $foreign) {
            expect(strtolower($foreign['on_update']))->toBe('restrict');
            expect(strtolower($foreign['on_delete']))->toBe('restrict');
        }
    }
});

it('has the unique subject tuples required by composite foreign keys', function (): void {
    foreach ([
        'item_instances' => [['id', 'item_id']],
        'repair_requests' => [['id', 'owner_customer_id'], ['case_number'], ['opening_command_id'], ['closing_command_id']],
        'repair_request_items' => [['id', 'item_instance_id'], ['id', 'repair_request_id'], ['repair_request_id', 'item_instance_id']],
        'repair_commands' => [['command_type', 'idempotency_key']],
        'active_repair_claims' => [['repair_request_item_id']],
        'repair_orders' => [['repair_request_item_id'], ['work_order_number']],
        'product_warranty_rules' => [['id', 'item_id'], ['item_id', 'version'], ['item_id', 'effective_from']],
        'product_handover_evidences' => [['id', 'item_instance_id'], ['command_id'], ['supersedes_evidence_id']],
        'warranty_assessments' => [['repair_order_id', 'version'], ['command_id']],
        'repair_status_histories' => [['command_id', 'event_sequence']],
    ] as $table => $keys) {
        foreach ($keys as $key) {
            expect(Schema::hasIndex($table, $key, 'unique'))->toBeTrue();
        }
    }
});

it('uses native MySQL binary identities precision engine and enforced CHECK constraints', function (): void {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('MySQL catalog proof, not SQLite affinity compatibility.');
    }
    $columns = collect(DB::select("SELECT COLUMN_NAME, COLUMN_TYPE, DATETIME_PRECISION FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='repair_commands'"))->keyBy('COLUMN_NAME');
    expect($columns['command_type']->COLUMN_TYPE)->toBe('varbinary(64)')
        ->and($columns['idempotency_key']->COLUMN_TYPE)->toBe('varbinary(128)')
        ->and($columns['payload_hash']->COLUMN_TYPE)->toBe('binary(32)')
        ->and((int) $columns['created_at']->DATETIME_PRECISION)->toBe(6);
    $checks = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND CONSTRAINT_TYPE='CHECK' AND CONSTRAINT_NAME LIKE 'rma\\_%'");
    expect($checks)->toHaveCount(15);
    $tables = DB::select("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('repair_commands','repair_requests','repair_request_items','repair_orders','active_repair_claims','product_warranty_rules','product_handover_evidences','warranty_assessments','repair_status_histories')");
    expect($tables)->toHaveCount(9);
    foreach ($tables as $table) {
        expect($table->ENGINE)->toBe('InnoDB');
    }
});
