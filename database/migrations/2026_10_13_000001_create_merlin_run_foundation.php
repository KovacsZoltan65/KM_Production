<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        if (! in_array($driver, ['mysql', 'sqlite'], true)) {
            throw new RuntimeException('MerlinRun foundation supports MySQL and SQLite only.');
        }

        // The full parent key is required by both engines for same-case history integrity.
        Schema::table('problem_case_evaluations', function (Blueprint $table): void {
            $table->unique(['problem_case_id', 'id'], 'problem_case_evaluations_case_id_unique');
        });

        $foreignId = $driver === 'mysql' ? 'BIGINT UNSIGNED' : 'INTEGER';
        $statusCollation = $driver === 'mysql' ? 'COLLATE utf8mb4_bin' : 'COLLATE BINARY';
        $engine = $driver === 'mysql' ? 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';
        $bytes = fn (string $column): string => $driver === 'mysql'
            ? "OCTET_LENGTH({$column})" : "LENGTH(CAST({$column} AS BLOB))";
        $requestBytes = $bytes('canonical_request');
        $configBytes = $bytes('configuration');
        $resultBytes = $bytes('result');

        // Explicit CREATE keeps native CHECK constraints in SQLite's initial table DDL.
        // Blueprint has no portable CHECK API; ALTER ADD CHECK is not supported by SQLite.
        DB::statement(<<<SQL
            CREATE TABLE merlin_runs (
                id CHAR(36) NOT NULL PRIMARY KEY,
                problem_case_id CHAR(36) NOT NULL,
                initiating_user_id {$foreignId} NULL,
                historical_actor_id {$foreignId} NOT NULL,
                starting_evaluation_id {$foreignId} NOT NULL,
                status VARCHAR(30) {$statusCollation} NOT NULL,
                stop_reason VARCHAR(100) NULL,
                idempotency_key CHAR(36) NOT NULL,
                request_schema_version INTEGER NOT NULL,
                canonical_request TEXT NOT NULL,
                fingerprint_version INTEGER NOT NULL,
                request_fingerprint CHAR(64) NOT NULL,
                configuration_schema_version INTEGER NOT NULL,
                configuration TEXT NOT NULL,
                result_schema_version INTEGER NULL,
                result TEXT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NULL,
                started_at DATETIME NULL,
                completed_at DATETIME NULL,
                cancellation_requested_at DATETIME NULL,
                cancelled_at DATETIME NULL,
                active_marker INTEGER GENERATED ALWAYS AS (
                    CASE WHEN status IN ('pending', 'running') THEN 1 ELSE NULL END
                ) STORED,
                CONSTRAINT merlin_runs_status_check
                    CHECK (status IN ('pending', 'running', 'completed', 'failed', 'cancelled')),
                CONSTRAINT merlin_runs_terminal_check CHECK (
                    (status IN ('pending', 'running') AND completed_at IS NULL AND stop_reason IS NULL)
                    OR (status IN ('completed', 'failed', 'cancelled') AND completed_at IS NOT NULL
                        AND stop_reason IS NOT NULL AND LENGTH(stop_reason) BETWEEN 1 AND 100)
                ),
                CONSTRAINT merlin_runs_start_check CHECK (
                    (status <> 'pending' OR started_at IS NULL)
                    AND (status NOT IN ('running', 'completed') OR started_at IS NOT NULL)
                ),
                CONSTRAINT merlin_runs_cancellation_check CHECK (
                    (status = 'cancelled' AND cancelled_at IS NOT NULL AND cancelled_at = completed_at)
                    OR (status <> 'cancelled' AND cancelled_at IS NULL)
                ),
                CONSTRAINT merlin_runs_versions_check CHECK (
                    request_schema_version > 0 AND fingerprint_version > 0 AND configuration_schema_version > 0
                ),
                CONSTRAINT merlin_runs_request_check CHECK (
                    JSON_VALID(canonical_request) AND {$requestBytes} BETWEEN 1 AND 65535
                    AND LENGTH(request_fingerprint) = 64
                ),
                CONSTRAINT merlin_runs_configuration_check CHECK (
                    JSON_VALID(configuration) AND {$configBytes} BETWEEN 1 AND 65535
                ),
                CONSTRAINT merlin_runs_result_check CHECK (
                    (result IS NULL AND result_schema_version IS NULL)
                    OR (result IS NOT NULL AND result_schema_version IS NOT NULL AND result_schema_version > 0
                        AND JSON_VALID(result) AND {$resultBytes} BETWEEN 1 AND 65535
                        AND status IN ('completed', 'failed', 'cancelled'))
                ),
                CONSTRAINT merlin_runs_case_fk FOREIGN KEY (problem_case_id)
                    REFERENCES problem_cases (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
                CONSTRAINT merlin_runs_user_fk FOREIGN KEY (initiating_user_id)
                    REFERENCES users (id) ON DELETE SET NULL ON UPDATE RESTRICT,
                CONSTRAINT merlin_runs_start_evaluation_fk FOREIGN KEY (problem_case_id, starting_evaluation_id)
                    REFERENCES problem_case_evaluations (problem_case_id, id) ON DELETE RESTRICT ON UPDATE RESTRICT,
                CONSTRAINT merlin_runs_active_unique UNIQUE (problem_case_id, active_marker),
                CONSTRAINT merlin_runs_idempotency_unique UNIQUE (historical_actor_id, problem_case_id, idempotency_key),
                CONSTRAINT merlin_runs_audit_identity_unique UNIQUE (id, problem_case_id, historical_actor_id, starting_evaluation_id)
            ) {$engine}
            SQL);

        Schema::create('merlin_run_admission_audits', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->uuid('run_id')->primary();
            $table->uuid('problem_case_id');
            $table->unsignedBigInteger('historical_actor_id');
            $table->unsignedBigInteger('starting_evaluation_id');
            $table->string('event_type', 100);
            $table->unsignedSmallInteger('schema_version');
            $table->timestamp('event_at');
            $table->string('admission_outcome', 40);
            $table->uuid('correlation_id');
            $table->foreign(
                ['run_id', 'problem_case_id', 'historical_actor_id', 'starting_evaluation_id'],
                'merlin_run_admission_audits_run_fk',
            )->references(['id', 'problem_case_id', 'historical_actor_id', 'starting_evaluation_id'])
                ->on('merlin_runs')->cascadeOnDelete()->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merlin_run_admission_audits');
        Schema::dropIfExists('merlin_runs');
        Schema::table('problem_case_evaluations', function (Blueprint $table): void {
            $table->dropUnique('problem_case_evaluations_case_id_unique');
        });
    }
};
