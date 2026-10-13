<?php

use App\Enums\MerlinRunStatus;
use App\Models\MaterialRequirement;
use App\Models\MerlinRun;
use App\Models\ProblemCase;
use App\Models\ProblemCaseEvaluation;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/** Database fixtures only; no admission, resolver or provider execution. */
function merlinPersistenceCase(): ProblemCase
{
    $case = ProblemCase::query()->create([
        'type' => 'material_shortage',
        'material_requirement_id' => MaterialRequirement::factory()->create()->id,
        'lifecycle' => 'open', 'detection_snapshot' => [],
        'current_evaluation' => 'active', 'current_evaluated_at' => '2026-10-13 08:00:00',
        'current_evaluation_evidence' => [],
    ]);
    $case->evaluations()->create([
        'result' => 'active', 'evaluated_at' => '2026-10-13 08:00:00',
        'recorded_for' => 'persistence_test_fixture', 'evidence' => [],
    ]);

    return $case;
}

/** @return array<string, mixed> */
function merlinPersistenceRow(ProblemCase $case, string $status = 'pending'): array
{
    $actor = User::factory()->create();
    $request = json_encode(['problem_case_id' => $case->id], JSON_THROW_ON_ERROR);
    $terminal = in_array($status, ['completed', 'failed', 'cancelled'], true);

    return [
        'id' => (string) Str::uuid(), 'problem_case_id' => $case->id,
        'initiating_user_id' => $actor->id, 'historical_actor_id' => $actor->id,
        'starting_evaluation_id' => $case->evaluations()->sole()->id,
        'status' => $status, 'stop_reason' => $terminal ? 'PERSISTENCE_TEST' : null,
        'idempotency_key' => (string) Str::uuid(), 'request_schema_version' => 1,
        'canonical_request' => $request, 'fingerprint_version' => 1,
        'request_fingerprint' => hash('sha256', $request),
        'configuration_schema_version' => 1, 'configuration' => '{"limits":{"model_turns":8}}',
        'result_schema_version' => null, 'result' => null,
        'created_at' => '2026-10-13 08:00:00', 'updated_at' => '2026-10-13 08:00:00',
        'started_at' => in_array($status, ['running', 'completed'], true) ? '2026-10-13 08:01:00' : null,
        'completed_at' => $terminal ? '2026-10-13 08:02:00' : null,
        'cancellation_requested_at' => null,
        'cancelled_at' => $status === 'cancelled' ? '2026-10-13 08:02:00' : null,
    ];
}

it('stores every allowed status and exposes typed relationships and casts', function (MerlinRunStatus $status): void {
    $case = merlinPersistenceCase();
    $row = merlinPersistenceRow($case, $status->value);
    DB::table('merlin_runs')->insert($row);
    $run = MerlinRun::query()->findOrFail($row['id']);

    expect($run->status)->toBe($status)
        ->and($run->status->isTerminal())->toBe(! in_array($status, [MerlinRunStatus::Pending, MerlinRunStatus::Running], true))
        ->and($run->configuration)->toBe(['limits' => ['model_turns' => 8]])
        ->and($run->problemCase->id)->toBe($case->id)
        ->and($run->startingEvaluation->id)->toBe($row['starting_evaluation_id'])
        ->and($run->initiatingUser->id)->toBe($row['initiating_user_id']);
})->with(MerlinRunStatus::cases());

it('rejects invalid statuses through direct SQL independently of model casts', function (string $status): void {
    $row = merlinPersistenceRow(merlinPersistenceCase());
    $row['status'] = $status;
    expect(fn () => DB::table('merlin_runs')->insert($row))->toThrow(QueryException::class);
})->with(['unknown', 'PENDING', '', 'waiting_for_human']);

it('rejects a second active row for the case with either active status', function (string $first, string $second): void {
    $case = merlinPersistenceCase();
    DB::table('merlin_runs')->insert(merlinPersistenceRow($case, $first));
    expect(fn () => DB::table('merlin_runs')->insert(merlinPersistenceRow($case, $second)))
        ->toThrow(QueryException::class);
})->with([['pending', 'pending'], ['pending', 'running'], ['running', 'pending'], ['running', 'running']]);

it('allows multiple terminal runs and an independent active slot per case', function (): void {
    $case = merlinPersistenceCase();
    foreach (['completed', 'failed', 'cancelled', 'completed'] as $status) {
        DB::table('merlin_runs')->insert(merlinPersistenceRow($case, $status));
    }
    DB::table('merlin_runs')->insert(merlinPersistenceRow($case));
    DB::table('merlin_runs')->insert(merlinPersistenceRow(merlinPersistenceCase(), 'running'));
    expect(DB::table('merlin_runs')->where('problem_case_id', $case->id)->count())->toBe(5);
});

it('keeps the slot on pending to running and releases it only on terminal status', function (): void {
    $case = merlinPersistenceCase();
    $row = merlinPersistenceRow($case);
    DB::table('merlin_runs')->insert($row);
    DB::table('merlin_runs')->where('id', $row['id'])->update(['status' => 'running', 'started_at' => '2026-10-13 08:01:00']);
    expect(DB::table('merlin_runs')->where('id', $row['id'])->value('active_marker'))->toBe(1);
    expect(fn () => DB::table('merlin_runs')->insert(merlinPersistenceRow($case)))->toThrow(QueryException::class);
    DB::table('merlin_runs')->where('id', $row['id'])->update([
        'status' => 'failed', 'stop_reason' => 'PERSISTENCE_TEST', 'completed_at' => '2026-10-13 08:02:00',
    ]);
    expect(DB::table('merlin_runs')->where('id', $row['id'])->value('active_marker'))->toBeNull();
    DB::table('merlin_runs')->insert(merlinPersistenceRow($case));
    expect(DB::table('merlin_runs')->count())->toBe(2);
});

it('keeps idempotency unique after live actor deletion without reserving a permanent tombstone', function (): void {
    $case = merlinPersistenceCase();
    $row = merlinPersistenceRow($case, 'failed');
    DB::table('merlin_runs')->insert($row);
    DB::table('users')->where('id', $row['initiating_user_id'])->delete();
    $run = MerlinRun::query()->findOrFail($row['id']);
    expect($run->initiating_user_id)->toBeNull()
        ->and($run->initiatingUser)->toBeNull()
        ->and($run->historical_actor_id)->toBe($row['historical_actor_id']);
    $duplicate = [...$row, 'id' => (string) Str::uuid(), 'initiating_user_id' => null];
    expect(fn () => DB::table('merlin_runs')->insert($duplicate))->toThrow(QueryException::class);
    DB::table('merlin_runs')->where('id', $row['id'])->delete();
    DB::table('merlin_runs')->insert($duplicate);
    expect(DB::table('merlin_runs')->count())->toBe(1);
});

it('scopes idempotency to historical actor and case', function (): void {
    $case = merlinPersistenceCase();
    $row = merlinPersistenceRow($case, 'completed');
    DB::table('merlin_runs')->insert($row);
    $anotherActor = merlinPersistenceRow($case, 'failed');
    $anotherActor['idempotency_key'] = $row['idempotency_key'];
    DB::table('merlin_runs')->insert($anotherActor);
    $otherCase = merlinPersistenceRow(merlinPersistenceCase(), 'failed');
    $otherCase['historical_actor_id'] = $row['historical_actor_id'];
    $otherCase['initiating_user_id'] = $row['initiating_user_id'];
    $otherCase['idempotency_key'] = $row['idempotency_key'];
    DB::table('merlin_runs')->insert($otherCase);
    expect(DB::table('merlin_runs')->count())->toBe(3);
});

it('requires a real case and an existing evaluation of that same case', function (string $invalid): void {
    $row = merlinPersistenceRow(merlinPersistenceCase());
    match ($invalid) {
        'missing case' => $row['problem_case_id'] = (string) Str::uuid(),
        'null case' => $row['problem_case_id'] = null,
        'missing evaluation' => $row['starting_evaluation_id'] = 999999999,
        'null evaluation' => $row['starting_evaluation_id'] = null,
        'other case evaluation' => $row['starting_evaluation_id'] = merlinPersistenceCase()->evaluations()->sole()->id,
        default => throw new InvalidArgumentException('Unknown invalid foreign-key fixture.'),
    };
    expect(fn () => DB::table('merlin_runs')->insert($row))->toThrow(QueryException::class);
})->with(['missing case', 'null case', 'missing evaluation', 'null evaluation', 'other case evaluation']);

it('restricts business parent deletion and cascades only run owned admission audit on cleanup', function (): void {
    $case = merlinPersistenceCase();
    $row = merlinPersistenceRow($case, 'failed');
    DB::table('merlin_runs')->insert($row);
    $audit = [
        'run_id' => $row['id'], 'problem_case_id' => $case->id,
        'historical_actor_id' => $row['historical_actor_id'], 'starting_evaluation_id' => $row['starting_evaluation_id'],
        'event_type' => 'investigation_admitted', 'schema_version' => 1,
        'event_at' => '2026-10-13 08:00:00', 'admission_outcome' => 'created', 'correlation_id' => (string) Str::uuid(),
    ];
    // Storage fixture, not a claim of successful trusted admission or audit writing.
    DB::table('merlin_run_admission_audits')->insert($audit);
    expect(fn () => DB::table('problem_cases')->where('id', $case->id)->delete())->toThrow(QueryException::class);
    expect(fn () => DB::table('problem_case_evaluations')->where('id', $row['starting_evaluation_id'])->delete())->toThrow(QueryException::class);
    DB::table('merlin_runs')->where('id', $row['id'])->delete();
    expect(DB::table('merlin_run_admission_audits')->count())->toBe(0)
        ->and(ProblemCase::query()->find($case->id))->not->toBeNull()
        ->and(ProblemCaseEvaluation::query()->find($row['starting_evaluation_id']))->not->toBeNull();
});

it('rejects admission audit provenance that disagrees with its run', function (): void {
    $row = merlinPersistenceRow(merlinPersistenceCase());
    DB::table('merlin_runs')->insert($row);
    expect(fn () => DB::table('merlin_run_admission_audits')->insert([
        'run_id' => $row['id'], 'problem_case_id' => $row['problem_case_id'],
        'historical_actor_id' => $row['historical_actor_id'] + 1, 'starting_evaluation_id' => $row['starting_evaluation_id'],
        'event_type' => 'investigation_admitted', 'schema_version' => 1, 'event_at' => '2026-10-13 08:00:00',
        'admission_outcome' => 'created', 'correlation_id' => (string) Str::uuid(),
    ]))->toThrow(QueryException::class);
});

it('rejects inconsistent timestamps versions and unbounded or invalid JSON at the database boundary', function (string $invalid): void {
    $row = merlinPersistenceRow(merlinPersistenceCase());
    match ($invalid) {
        'terminal without timestamp' => [$row['status'] = 'failed', $row['stop_reason'] = 'TEST'],
        'active with stop reason' => $row['stop_reason'] = 'TEST',
        'running without start' => $row['status'] = 'running',
        'cancelled without cancellation timestamp' => [$row['status'] = 'cancelled', $row['stop_reason'] = 'TEST', $row['completed_at'] = '2026-10-13 08:02:00'],
        'invalid configuration' => $row['configuration'] = 'not json',
        'oversized configuration' => $row['configuration'] = json_encode(['data' => str_repeat('x', 65535)], JSON_THROW_ON_ERROR),
        'invalid request' => $row['canonical_request'] = 'not json',
        'oversized request' => $row['canonical_request'] = json_encode(['data' => str_repeat('x', 65535)], JSON_THROW_ON_ERROR),
        'oversized result' => [
            $row['status'] = 'failed', $row['stop_reason'] = 'TEST', $row['completed_at'] = '2026-10-13 08:02:00',
            $row['result_schema_version'] = 1, $row['result'] = json_encode(['data' => str_repeat('x', 65535)], JSON_THROW_ON_ERROR),
        ],
        'invalid version' => $row['request_schema_version'] = 0,
        'invalid fingerprint length' => $row['request_fingerprint'] = 'short',
        'result without version' => $row['result'] = '{}',
        default => throw new InvalidArgumentException('Unknown invalid storage fixture.'),
    };
    expect(fn () => DB::table('merlin_runs')->insert($row))->toThrow(QueryException::class);
})->with([
    'terminal without timestamp', 'active with stop reason', 'running without start',
    'cancelled without cancellation timestamp', 'invalid configuration', 'oversized configuration',
    'invalid request', 'oversized request', 'oversized result', 'invalid version',
    'invalid fingerprint length', 'result without version',
]);

it('keeps canonical request bytes unchanged and bounds Unicode storage by bytes', function (): void {
    $row = merlinPersistenceRow(merlinPersistenceCase(), 'failed');
    $row['canonical_request'] = '{ "b":2, "a":1 }';
    $row['result_schema_version'] = 1;
    $row['result'] = '{"schema_version":"1","findings":[]}';
    DB::table('merlin_runs')->insert($row);
    $run = MerlinRun::query()->findOrFail($row['id']);
    expect($run->canonical_request)->toBe($row['canonical_request'])
        ->and($run->result)->toBe(['schema_version' => '1', 'findings' => []]);
    $row['id'] = (string) Str::uuid();
    $row['idempotency_key'] = (string) Str::uuid();
    $row['configuration'] = json_encode(['data' => str_repeat('á', 32768)], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    expect(fn () => DB::table('merlin_runs')->insert($row))->toThrow(QueryException::class);
});

it('generates model UUIDs and rejects changed historical binding and terminal results', function (): void {
    $row = merlinPersistenceRow(merlinPersistenceCase(), 'failed');
    unset($row['id'], $row['created_at'], $row['updated_at']);
    $row['configuration'] = ['limits' => ['model_turns' => 8]];
    $run = MerlinRun::query()->create($row);
    expect(Str::isUuid($run->id))->toBeTrue();
    expect(fn () => $run->update(['historical_actor_id' => $row['historical_actor_id'] + 1]))->toThrow(LogicException::class);
    $run->refresh();
    expect(fn () => $run->update(['stop_reason' => 'CHANGED']))->toThrow(LogicException::class);
});

it('rejects a model creation with a different live and historical actor', function (): void {
    $row = merlinPersistenceRow(merlinPersistenceCase());
    unset($row['id'], $row['created_at'], $row['updated_at']);
    $row['configuration'] = [];
    $row['historical_actor_id']++;
    expect(fn () => MerlinRun::query()->create($row))->toThrow(LogicException::class);
});
