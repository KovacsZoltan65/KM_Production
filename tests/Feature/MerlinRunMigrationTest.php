<?php

use App\Models\MaterialRequirement;
use App\Models\MerlinRun;
use App\Models\ProblemCase;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PHPUnit\Framework\Assert;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

uses(DatabaseMigrations::class);

it('round trips only the MerlinRun migration while preserving existing business history', function (): void {
    $case = ProblemCase::query()->create([
        'type' => 'material_shortage', 'material_requirement_id' => MaterialRequirement::factory()->create()->id,
        'lifecycle' => 'open', 'detection_snapshot' => [], 'current_evaluation' => 'active',
        'current_evaluated_at' => '2026-10-13 08:00:00', 'current_evaluation_evidence' => [],
    ]);
    $evaluation = $case->evaluations()->create([
        'result' => 'active', 'evaluated_at' => '2026-10-13 08:00:00',
        'recorded_for' => 'migration_test_fixture', 'evidence' => [],
    ]);
    $migration = require database_path('migrations/2026_10_13_000001_create_merlin_run_foundation.php');
    expect(Schema::hasTable('merlin_runs'))->toBeTrue()
        ->and(Schema::hasTable('merlin_run_admission_audits'))->toBeTrue();
    $migration->down();
    expect(Schema::hasTable('merlin_runs'))->toBeFalse()
        ->and(Schema::hasTable('merlin_run_admission_audits'))->toBeFalse()
        ->and($evaluation->fresh()->problem_case_id)->toBe($case->id);
    $migration->up();
    expect(Schema::hasTable('merlin_runs'))->toBeTrue()
        ->and(Schema::hasTable('merlin_run_admission_audits'))->toBeTrue()
        ->and($case->fresh()->evaluations()->sole()->id)->toBe($evaluation->id);
});

it('enforces active and idempotency uniqueness across contending MySQL processes', function (): void {
    if (DB::connection()->getDriverName() !== 'mysql') {
        Assert::markTestSkipped('Real MySQL contention evidence only; SQLite compatibility is tested separately.');
    }

    $engines = DB::select("SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('merlin_runs', 'merlin_run_admission_audits', 'problem_cases', 'problem_case_evaluations', 'users')");
    expect($engines)->toHaveCount(5);
    foreach ($engines as $table) {
        expect($table->ENGINE)->toBe('InnoDB');
    }

    $case = ProblemCase::query()->create([
        'type' => 'material_shortage', 'material_requirement_id' => MaterialRequirement::factory()->create()->id,
        'lifecycle' => 'open', 'detection_snapshot' => [], 'current_evaluation' => 'active',
        'current_evaluated_at' => '2026-10-13 08:00:00', 'current_evaluation_evidence' => [],
    ]);
    $evaluation = $case->evaluations()->create([
        'result' => 'active', 'evaluated_at' => '2026-10-13 08:00:00',
        'recorded_for' => 'concurrency_test_fixture', 'evidence' => [],
    ]);
    $actor = User::factory()->create();
    $configuration = DB::connection()->getConfig();
    $dsn = "mysql:host={$configuration['host']};port={$configuration['port']};dbname={$configuration['database']};charset=utf8mb4";
    $childCode = <<<'PHP'
        $pdo = new PDO($argv[1], $argv[2], getenv('MERLIN_TEST_DB_PASSWORD'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('SET SESSION innodb_lock_wait_timeout = 1');
        $row = json_decode(base64_decode($argv[3]), true, 512, JSON_THROW_ON_ERROR);
        $columns = implode(', ', array_map(fn ($name) => '`'.$name.'`', array_keys($row)));
        $insert = $pdo->prepare('INSERT INTO merlin_runs ('.$columns.') VALUES ('.implode(', ', array_fill(0, count($row), '?')).')');
        try {
            $insert->execute(array_values($row));
            exit(2);
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) !== 1205) { echo 'UNEXPECTED_FIRST_FAILURE'; exit(3); }
            echo "BLOCKED\n";
            flush();
        }
        if (trim(fgets(STDIN)) !== 'COMMITTED') { exit(4); }
        try {
            $insert->execute(array_values($row));
            exit(5);
        } catch (PDOException $e) {
            echo ($e->errorInfo[1] ?? null) === 1062 ? 'DUPLICATE_REJECTED' : 'UNEXPECTED_SECOND_FAILURE';
        }
        PHP;

    foreach (['active', 'idempotency'] as $constraint) {
        $row = [
            'problem_case_id' => $case->id, 'initiating_user_id' => $actor->id, 'historical_actor_id' => $actor->id,
            'starting_evaluation_id' => $evaluation->id, 'idempotency_key' => (string) Str::uuid(),
            'request_schema_version' => 1, 'canonical_request' => '{}', 'fingerprint_version' => 1,
            'request_fingerprint' => hash('sha256', '{}'), 'configuration_schema_version' => 1, 'configuration' => [],
            'status' => $constraint === 'active' ? 'pending' : 'failed',
            'stop_reason' => $constraint === 'active' ? null : 'PERSISTENCE_TEST',
            'completed_at' => $constraint === 'active' ? null : '2026-10-13 08:02:00',
        ];
        $input = new InputStream;
        DB::beginTransaction();
        $process = null;
        try {
            $first = MerlinRun::query()->create($row);
            $duplicate = $first->getAttributes();
            $duplicate['id'] = (string) Str::uuid();
            if ($constraint === 'active') {
                $duplicate['idempotency_key'] = (string) Str::uuid();
            }
            $process = new Process([
                PHP_BINARY, '-r', $childCode, $dsn, $configuration['username'],
                base64_encode(json_encode($duplicate, JSON_THROW_ON_ERROR)),
            ], base_path(), ['MERLIN_TEST_DB_PASSWORD' => $configuration['password']], $input, 15);
            $process->start();
            expect($process->waitUntil(fn (string $type, string $output): bool => str_contains($output, 'BLOCKED')))->toBeTrue();
            DB::commit();
            $input->write("COMMITTED\n");
            $input->close();
            expect($process->wait())->toBe(0)
                ->and($process->getOutput())->toContain('DUPLICATE_REJECTED')
                ->and(DB::table('merlin_runs')->where('id', $duplicate['id'])->exists())->toBeFalse();
            DB::table('merlin_runs')->where('id', $first->id)->delete();
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            $input->close();
            $process?->stop();
        }
    }
});
