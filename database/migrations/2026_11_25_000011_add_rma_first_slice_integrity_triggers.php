<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = [
        'repair_commands', 'repair_requests', 'repair_request_items', 'repair_orders',
        'active_repair_claims', 'product_warranty_rules', 'product_handover_evidences',
        'warranty_assessments', 'repair_status_histories',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            $this->guard($table, 'bd', 'BEFORE DELETE', 'immutable RMA record');
            if ($table !== 'repair_commands') {
                $this->guard($table, 'bu', 'BEFORE UPDATE', 'immutable RMA record');
            }
        }

        $same = [];
        foreach (Schema::getColumnListing('repair_commands') as $column) {
            if (! in_array($column, ['status', 'result_type', 'result_id', 'result_snapshot', 'completed_at', 'updated_at'], true)) {
                $same[] = $this->equal("OLD.{$column}", "NEW.{$column}");
            }
        }
        $condition = "OLD.status = 'processing' AND NEW.status = 'completed' AND NEW.completed_at IS NOT NULL AND NEW.result_snapshot IS NOT NULL AND ".implode(' AND ', $same);
        $this->guard('repair_commands', 'bu', 'BEFORE UPDATE', 'invalid command transition', "NOT ({$condition})");

        $this->claimAcquisition();
        $this->evidenceInsert('product_handover_evidences', 'evidence_document_id');
        $this->evidenceInsert('repair_status_histories', 'authorization_document_id');

        // Locking reads are required here: a stale RR snapshot cannot authorize destructive edits.
        $reference = <<<'SQL'
DECLARE handover_id BIGINT UNSIGNED DEFAULT NULL;
DECLARE history_id BIGINT UNSIGNED DEFAULT NULL;
DECLARE CONTINUE HANDLER FOR NOT FOUND BEGIN END;
SELECT id INTO handover_id FROM product_handover_evidences WHERE evidence_document_id=OLD.id LIMIT 1 FOR UPDATE;
SELECT id INTO history_id FROM repair_status_histories WHERE authorization_document_id=OLD.id LIMIT 1 FOR UPDATE;
SQL;
        $sqliteReference = 'EXISTS (SELECT 1 FROM product_handover_evidences WHERE evidence_document_id=OLD.id) OR EXISTS (SELECT 1 FROM repair_status_histories WHERE authorization_document_id=OLD.id)';
        $protected = ['id', 'documentable_type', 'documentable_id', 'version', 'checksum', 'disk', 'path', 'file_path', 'original_filename', 'mime_type', 'file_size', 'deleted_at'];
        $changed = implode(' OR ', array_map(fn (string $column): string => 'NOT ('.$this->equal("OLD.{$column}", "NEW.{$column}").')', $protected));
        foreach (['bu' => 'BEFORE UPDATE', 'bd' => 'BEFORE DELETE'] as $suffix => $event) {
            $change = $suffix === 'bu' ? " AND ({$changed})" : '';
            $this->create('documents', $suffix, $event,
                $reference."\nIF (handover_id IS NOT NULL OR history_id IS NOT NULL){$change} THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='referenced RMA evidence'; END IF;",
                "SELECT RAISE(ABORT, 'referenced RMA evidence') WHERE ({$sqliteReference}){$change};");
        }

        $serialChanged = implode(' OR ', array_map(fn (string $column): string => 'NOT ('.$this->equal("OLD.{$column}", "NEW.{$column}").')', ['id', 'serial_number', 'item_id', 'production_order_id']));
        $this->create('item_instances', 'bu', 'BEFORE UPDATE', <<<SQL
DECLARE member_id BIGINT UNSIGNED DEFAULT NULL;
DECLARE handover_id BIGINT UNSIGNED DEFAULT NULL;
DECLARE CONTINUE HANDLER FOR NOT FOUND BEGIN END;
IF {$serialChanged} THEN
    SELECT id INTO member_id FROM repair_request_items WHERE item_instance_id=OLD.id LIMIT 1 FOR UPDATE;
    SELECT id INTO handover_id FROM product_handover_evidences WHERE item_instance_id=OLD.id LIMIT 1 FOR UPDATE;
    IF member_id IS NOT NULL OR handover_id IS NOT NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='referenced RMA serial identity';
    END IF;
END IF;
SQL,
            "SELECT RAISE(ABORT, 'referenced RMA serial identity') WHERE ({$serialChanged}) AND (EXISTS (SELECT 1 FROM repair_request_items WHERE item_instance_id=OLD.id) OR EXISTS (SELECT 1 FROM product_handover_evidences WHERE item_instance_id=OLD.id));");

        foreach (['bu' => 'BEFORE UPDATE', 'bd' => 'BEFORE DELETE'] as $suffix => $event) {
            $this->guard('activity_log', $suffix, $event, 'immutable RMA audit', "OLD.log_name='rma'");
        }
    }

    public function down(): void
    {
        foreach ([...self::TABLES, 'documents', 'item_instances', 'activity_log'] as $table) {
            foreach (['bi', 'ai', 'bu', 'bd'] as $suffix) {
                DB::unprepared("DROP TRIGGER IF EXISTS rma_{$table}_{$suffix}");
            }
        }
    }

    private function claimAcquisition(): void
    {
        $this->create('repair_request_items', 'ai', 'AFTER INSERT', <<<'SQL'
DECLARE case_status VARCHAR(16) DEFAULT NULL;
DECLARE opening_id BIGINT UNSIGNED DEFAULT NULL;
DECLARE actor BIGINT UNSIGNED DEFAULT NULL;
SELECT status, opening_command_id INTO case_status, opening_id FROM repair_requests WHERE id=NEW.repair_request_id FOR UPDATE;
SELECT actor_id INTO actor FROM repair_commands WHERE id=NEW.membership_command_id FOR UPDATE;
IF case_status IS NULL OR case_status<>'open' OR opening_id<>NEW.membership_command_id OR actor IS NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='invalid RMA membership command';
END IF;
INSERT INTO active_repair_claims (item_instance_id,repair_request_item_id,claimed_by,command_id,created_at,updated_at)
VALUES (NEW.item_instance_id,NEW.id,actor,NEW.membership_command_id,NEW.created_at,NEW.updated_at);
SQL,
            <<<'SQL'
SELECT RAISE(ABORT, 'invalid RMA membership command') WHERE NOT EXISTS (
    SELECT 1 FROM repair_requests r JOIN repair_commands c ON c.id=NEW.membership_command_id
    WHERE r.id=NEW.repair_request_id AND r.status='open' AND r.opening_command_id=c.id
);
INSERT INTO active_repair_claims (item_instance_id,repair_request_item_id,claimed_by,command_id,created_at,updated_at)
SELECT NEW.item_instance_id,NEW.id,actor_id,NEW.membership_command_id,NEW.created_at,NEW.updated_at FROM repair_commands WHERE id=NEW.membership_command_id;
SQL);
    }

    private function evidenceInsert(string $table, string $column): void
    {
        $predecessor = $table === 'product_handover_evidences' ? <<<'SQL'
IF NEW.supersedes_evidence_id IS NOT NULL AND NOT EXISTS (
    SELECT 1 FROM product_handover_evidences WHERE id=NEW.supersedes_evidence_id AND item_instance_id=NEW.item_instance_id
) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='invalid handover predecessor'; END IF;
SQL : '';
        $sqlitePredecessor = $table === 'product_handover_evidences' ? <<<'SQL'
SELECT RAISE(ABORT, 'invalid handover predecessor') WHERE NEW.supersedes_evidence_id IS NOT NULL AND NOT EXISTS (
    SELECT 1 FROM product_handover_evidences WHERE id=NEW.supersedes_evidence_id AND item_instance_id=NEW.item_instance_id
);
SQL : '';
        $this->create($table, 'bi', 'BEFORE INSERT', <<<SQL
DECLARE proof_id BIGINT UNSIGNED DEFAULT NULL;
DECLARE CONTINUE HANDLER FOR NOT FOUND BEGIN END;
IF NEW.{$column} IS NOT NULL THEN
    SELECT id INTO proof_id FROM documents WHERE id=NEW.{$column} AND deleted_at IS NULL
        AND version>=1 AND checksum REGEXP '^[0-9A-Fa-f]{64}$' AND disk IS NOT NULL AND disk<>''
        AND COALESCE(NULLIF(path,''), NULLIF(file_path,'')) IS NOT NULL FOR UPDATE;
    IF proof_id IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='invalid RMA document evidence'; END IF;
END IF;
{$predecessor}
SQL,
            <<<SQL
SELECT RAISE(ABORT, 'invalid RMA document evidence') WHERE NEW.{$column} IS NOT NULL AND NOT EXISTS (
    SELECT 1 FROM documents WHERE id=NEW.{$column} AND deleted_at IS NULL AND version>=1
    AND length(checksum)=64 AND checksum NOT GLOB '*[^0-9A-Fa-f]*' AND disk IS NOT NULL AND disk<>''
    AND COALESCE(NULLIF(path,''), NULLIF(file_path,'')) IS NOT NULL
);
{$sqlitePredecessor}
SQL);
    }

    private function equal(string $left, string $right): string
    {
        return DB::getDriverName() === 'mysql' ? "({$left} <=> {$right})" : "({$left} IS {$right})";
    }

    private function guard(string $table, string $suffix, string $event, string $message, string $condition = '1=1'): void
    {
        $this->create($table, $suffix, $event,
            "IF {$condition} THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='{$message}'; END IF;",
            "SELECT RAISE(ABORT, '{$message}') WHERE {$condition};");
    }

    private function create(string $table, string $suffix, string $event, string $mysql, string $sqlite): void
    {
        $body = DB::getDriverName() === 'mysql' ? $mysql : $sqlite;
        DB::unprepared("CREATE TRIGGER rma_{$table}_{$suffix} {$event} ON {$table} FOR EACH ROW BEGIN\n{$body}\nEND");
    }
};
