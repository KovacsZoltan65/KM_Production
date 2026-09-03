<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $binaryCollation = DB::connection()->getDriverName() === 'mysql' ? 'ascii_bin' : 'BINARY';

        Schema::create('purchase_order_dispatches', function (Blueprint $table) use ($binaryCollation): void {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('dispatch_sequence');
            $table->string('idempotency_key', 100)->collation($binaryCollation);
            $table->char('request_fingerprint', 64);
            $table->foreignId('previous_dispatch_id')->nullable();
            $table->string('channel');
            $table->string('supplier_code_snapshot')->nullable();
            $table->string('supplier_name_snapshot');
            $table->string('recipient_name')->nullable();
            $table->string('recipient_email')->nullable();
            $table->string('recipient_reference')->nullable();
            $table->timestamp('attempted_at');
            $table->timestamp('dispatched_at')->nullable();
            $table->string('status');
            $table->text('failure_reason')->nullable();
            $table->text('redispatch_reason')->nullable();
            $table->date('buyer_requested_delivery_date_snapshot')->nullable();
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('previous_dispatch_id')->references('id')->on('purchase_order_dispatches')->restrictOnDelete();
            $table->unique(['purchase_order_id', 'dispatch_sequence'], 'po_dispatch_sequence_unique');
            $table->unique(['purchase_order_id', 'idempotency_key'], 'po_dispatch_idempotency_unique');
            $table->unique('previous_dispatch_id', 'po_dispatch_previous_unique');
            $table->index(['purchase_order_id', 'status', 'dispatched_at'], 'po_dispatch_status_time_index');
        });

        Schema::create('supplier_acknowledgements', function (Blueprint $table) use ($binaryCollation): void {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->restrictOnDelete();
            $table->foreignId('purchase_order_dispatch_id')->nullable()->constrained('purchase_order_dispatches')->restrictOnDelete();
            $table->unsignedInteger('acknowledgement_sequence');
            $table->string('idempotency_key', 100)->collation($binaryCollation);
            $table->char('response_fingerprint', 64);
            $table->foreignId('supersedes_acknowledgement_id')->nullable();
            $table->text('correction_reason')->nullable();
            $table->string('source');
            $table->string('supplier_reference')->nullable();
            $table->timestamp('acknowledgement_received_at');
            $table->string('acknowledged_by_name')->nullable();
            $table->string('acknowledged_by_email')->nullable();
            $table->string('status');
            $table->boolean('requires_follow_up');
            $table->boolean('requires_replanning');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('supersedes_acknowledgement_id')->references('id')->on('supplier_acknowledgements')->restrictOnDelete();
            $table->unique(['purchase_order_id', 'acknowledgement_sequence'], 'supplier_ack_sequence_unique');
            $table->unique(['purchase_order_id', 'idempotency_key'], 'supplier_ack_idempotency_unique');
            $table->unique(['purchase_order_id', 'response_fingerprint'], 'supplier_ack_response_unique');
            $table->unique('supersedes_acknowledgement_id', 'supplier_ack_predecessor_unique');
            $table->index(['purchase_order_id', 'acknowledgement_received_at'], 'supplier_ack_received_index');
        });

        Schema::create('supplier_acknowledgement_scope_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('supplier_acknowledgement_id')
                ->constrained(indexName: 'supplier_ack_scope_ack_fk')
                ->restrictOnDelete();
            $table->foreignId('purchase_order_item_id')
                ->constrained(indexName: 'supplier_ack_scope_po_item_fk')
                ->restrictOnDelete();
            $table->timestamps();

            $table->unique(['supplier_acknowledgement_id', 'purchase_order_item_id'], 'supplier_ack_scope_item_unique');
        });

        Schema::create('supplier_acknowledgement_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('supplier_acknowledgement_id')
                ->constrained(indexName: 'supplier_ack_item_ack_fk')
                ->restrictOnDelete();
            $table->foreignId('purchase_order_item_id')->constrained()->restrictOnDelete();
            $table->string('line_status');
            $table->decimal('promised_quantity', 18, 3)->nullable();
            $table->date('promised_delivery_date')->nullable();
            $table->decimal('ordered_quantity_snapshot', 18, 3);
            $table->string('unit_snapshot');
            $table->date('buyer_requested_delivery_date_snapshot')->nullable();
            $table->string('quantity_variance');
            $table->decimal('quantity_variance_amount', 18, 3)->nullable();
            $table->string('delivery_date_variance');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['supplier_acknowledgement_id', 'purchase_order_item_id'], 'supplier_ack_line_item_unique');
        });

        $this->addChecks('purchase_order_dispatches', [
            '{c}dispatch_sequence >= 1',
            "{c}status IN ('succeeded', 'failed')",
            "{c}channel IN ('manual', 'email', 'other')",
            "((({c}status = 'succeeded') AND {c}dispatched_at IS NOT NULL AND {c}failure_reason IS NULL) OR (({c}status = 'failed') AND {c}dispatched_at IS NULL AND {len}(TRIM(COALESCE({c}failure_reason, ''))) > 0))",
            "((({c}dispatch_sequence = 1) AND {c}previous_dispatch_id IS NULL AND {c}redispatch_reason IS NULL) OR (({c}dispatch_sequence > 1) AND {c}previous_dispatch_id IS NOT NULL AND {len}(TRIM(COALESCE({c}redispatch_reason, ''))) > 0))",
            "(({c}status = 'failed') OR ({c}dispatched_at >= {c}attempted_at))",
            "(({c}channel = 'email' AND {len}(TRIM(COALESCE({c}recipient_email, ''))) > 0) OR ({c}channel = 'manual' AND ({len}(TRIM(COALESCE({c}recipient_name, ''))) > 0 OR {len}(TRIM(COALESCE({c}recipient_email, ''))) > 0 OR {len}(TRIM(COALESCE({c}recipient_reference, ''))) > 0)) OR ({c}channel = 'other' AND {len}(TRIM(COALESCE({c}recipient_reference, ''))) > 0 AND {len}(TRIM(COALESCE({c}notes, ''))) > 0))",
            '{len}(TRIM({c}idempotency_key)) BETWEEN 1 AND 100',
            '{len}({c}request_fingerprint) = 64',
        ]);

        $this->addChecks('supplier_acknowledgements', [
            '{c}acknowledgement_sequence >= 1',
            "{c}source IN ('email', 'phone', 'manual', 'other')",
            "{c}status IN ('accepted', 'accepted_with_changes', 'rejected')",
            "((({c}acknowledgement_sequence = 1) AND {c}supersedes_acknowledgement_id IS NULL AND {c}correction_reason IS NULL) OR (({c}acknowledgement_sequence > 1) AND {c}supersedes_acknowledgement_id IS NOT NULL AND {len}(TRIM(COALESCE({c}correction_reason, ''))) > 0))",
            "({len}(TRIM(COALESCE({c}supplier_reference, ''))) > 0 OR {len}(TRIM(COALESCE({c}acknowledged_by_name, ''))) > 0 OR {len}(TRIM(COALESCE({c}acknowledged_by_email, ''))) > 0)",
            "({c}purchase_order_dispatch_id IS NOT NULL OR {len}(TRIM(COALESCE({c}notes, ''))) > 0)",
            '{len}(TRIM({c}idempotency_key)) BETWEEN 1 AND 100',
            '{len}({c}response_fingerprint) = 64',
        ]);

        $this->addChecks('supplier_acknowledgement_items', [
            "{c}line_status IN ('accepted', 'rejected')",
            "{c}quantity_variance IN ('matched', 'reduced', 'increased', 'rejected')",
            "{c}delivery_date_variance IN ('matched', 'earlier', 'later', 'not_confirmed', 'no_buyer_baseline', 'rejected')",
            '{c}ordered_quantity_snapshot > 0',
            "((({c}line_status = 'accepted') AND {c}promised_quantity > 0 AND {c}quantity_variance_amount IS NOT NULL) OR (({c}line_status = 'rejected') AND {c}promised_quantity IS NULL AND {c}promised_delivery_date IS NULL AND {c}quantity_variance_amount IS NULL AND {c}quantity_variance = 'rejected' AND {c}delivery_date_variance = 'rejected'))",
            "(({c}line_status = 'rejected') OR ({c}quantity_variance_amount = {c}promised_quantity - {c}ordered_quantity_snapshot))",
            "(({c}line_status = 'rejected') OR ({c}quantity_variance = 'matched' AND {c}quantity_variance_amount = 0) OR ({c}quantity_variance = 'reduced' AND {c}quantity_variance_amount < 0) OR ({c}quantity_variance = 'increased' AND {c}quantity_variance_amount > 0))",
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_acknowledgement_items');
        Schema::dropIfExists('supplier_acknowledgement_scope_items');
        Schema::dropIfExists('supplier_acknowledgements');
        Schema::dropIfExists('purchase_order_dispatches');
    }

    /** @param list<string> $expressions */
    private function addChecks(string $table, array $expressions): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            foreach ($expressions as $index => $expression) {
                $sql = str_replace(['{c}', '{len}'], ['', 'CHAR_LENGTH'], $expression);
                DB::statement(sprintf('ALTER TABLE `%s` ADD CONSTRAINT `%s_chk_%d` CHECK (%s)', $table, $table, $index + 1, $sql));
            }

            return;
        }

        if ($driver === 'sqlite') {
            $condition = implode(' AND ', array_map(
                static fn (string $expression): string => '('.str_replace(['{c}', '{len}'], ['NEW.', 'LENGTH'], $expression).')',
                $expressions,
            ));

            foreach (['INSERT', 'UPDATE'] as $operation) {
                $trigger = sprintf('%s_%s_checks', $table, strtolower($operation));
                DB::unprepared(sprintf(
                    "CREATE TRIGGER %s BEFORE %s ON %s FOR EACH ROW WHEN NOT (%s) BEGIN SELECT RAISE(ABORT, 'ADR 0016 database invariant violation'); END",
                    $trigger,
                    $operation,
                    $table,
                    $condition,
                ));
            }
        }
    }
};
