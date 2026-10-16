<?php

use App\Enums\CustomerOrderStatus;
use App\Enums\MerlinAdmissionOutcome as Outcome;
use App\Enums\ProblemCaseLifecycle;
use App\Models\MaterialRequirement;
use App\Models\MerlinRun;
use App\Models\ProblemCase;
use App\Models\ProblemCaseEvaluation;
use App\Models\StockBalance;
use App\Models\User;
use App\Repositories\Admin\MerlinRunRepository;
use App\Repositories\Contracts\MerlinRunRepositoryInterface;
use App\Services\Merlin\MerlinAdmissionAuditWriter;
use App\Services\Merlin\MerlinAdmissionAuditWriterInterface;
use App\Services\Merlin\StartMerlinInvestigationService;
use App\Support\Merlin\MaterialShortageCurrentEvaluation;
use App\Support\Merlin\MerlinAdmissionInfrastructureException;
use App\Support\Merlin\MerlinAdmissionTransaction;
use App\Support\Merlin\MerlinAdmissionUniqueCollision;
use App\Support\Merlin\MerlinRunConfiguration;
use App\Support\Merlin\StartInvestigationRequest;
use Illuminate\Database\Connection;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PHPUnit\Framework\Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

uses(DatabaseMigrations::class);

beforeEach(function (): void {
    if (DB::connection()->getDriverName() === 'sqlite') {
        $directory = storage_path('framework/testing');
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        $path = tempnam($directory, 'merlin-start-');
        if ($path === false) {
            throw new RuntimeException('Cannot create file-backed admission fixture.');
        }
        DB::connection()->getPdo()->exec('VACUUM INTO '.DB::connection()->getPdo()->quote($path));
        Schema::clearResolvedInstance('db.schema');
        DB::purge('sqlite');
        config(['database.connections.sqlite.database' => $path]);
        // Dedicated disposable testing storage; Windows PDO handles may outlive teardown.
    }
});

/** @return array{User, ProblemCase, MaterialRequirement, StartInvestigationRequest} */
function trustedAdmissionFixture(): array
{
    $actor = User::factory()->create();
    foreach (['inventory.view', 'item-suppliers.view'] as $permission) {
        $actor->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    Auth::setUser($actor);
    $requirement = MaterialRequirement::factory()->create(['required_quantity' => '12.000', 'missing_quantity' => '999.000']);
    $requirement->customerOrderItem->customerOrder->update(['status' => CustomerOrderStatus::Confirmed]);
    $case = ProblemCase::query()->create([
        'type' => 'material_shortage', 'material_requirement_id' => $requirement->id, 'lifecycle' => 'open',
        'detection_snapshot' => ['unchanged' => true], 'current_evaluation' => 'resolved',
        'current_evaluated_at' => '2026-10-14 08:00:00', 'current_evaluation_evidence' => ['old' => true],
    ]);
    $request = StartInvestigationRequest::fromArray(['problem_case_id' => $case->id, 'idempotency_key' => (string) Str::uuid()]);

    return [$actor, $case, $requirement, $request];
}

function expectAdmissionCounts(int $count): void
{
    expect(MerlinRun::query()->count())->toBe($count)
        ->and(ProblemCaseEvaluation::query()->count())->toBe($count)
        ->and(DB::table('merlin_run_admission_audits')->count())->toBe($count);
}

it('creates atomic pending admission with starting evidence and unchanged current projection', function (): void {
    [$actor, $case, , $request] = trustedAdmissionFixture();
    Queue::fake();
    Http::preventStrayRequests();
    $original = $case->fresh()->getAttributes();
    $queries = [];
    DB::listen(function (QueryExecuted $event) use (&$queries): void {
        if (str_starts_with($event->connectionName, 'merlin_admission_')) {
            $queries[] = [$event->connectionName, $event->sql];
        }
    });
    $result = app(StartMerlinInvestigationService::class)->start($request);
    expect($result->outcome)->toBe(Outcome::Created);
    $run = MerlinRun::query()->findOrFail($result->runId);
    $history = $run->startingEvaluation;
    $audit = DB::table('merlin_run_admission_audits')->sole();
    expectAdmissionCounts(1);
    expect($case->fresh()->getAttributes())->toBe($original)
        ->and($run->status->value)->toBe('pending')->and($run->getAttribute('started_at'))->toBeNull()
        ->and($run->initiating_user_id)->toBe($actor->id)->and($run->historical_actor_id)->toBe($actor->id)
        ->and($run->configuration['paid_execution_enabled'])->toBeFalse()
        ->and($run->canonical_request)->toBe($request->canonicalRequest())
        ->and($history->problem_case_id)->toBe($case->id)->and($history->result->value)->toBe('active')
        ->and($history->recorded_for)->toBe('merlin_investigation_start')
        ->and($history->evidence['netting']['net_requirement'])->toBe('12.000')
        ->and($audit->run_id)->toBe($run->id)->and((int) $audit->starting_evaluation_id)->toBe($history->id)
        ->and((int) $audit->historical_actor_id)->toBe($actor->id)->and($audit->admission_outcome)->toBe('CREATED')
        ->and($audit->event_type)->toBe('merlin_run.admitted.v1')->and(Str::isUuid($audit->correlation_id))->toBeTrue()
        ->and($queries[0][1])->toContain('problem_cases')
        ->and(array_unique(array_column($queries, 0)))->toHaveCount(1);
    expect(DB::transactionLevel())->toBe(0);
    Queue::assertNothingPushed();
    Http::assertNothingSent();
});

it('appends starting history without changing earlier business observations', function (): void {
    [, $case, , $request] = trustedAdmissionFixture();
    $earlier = $case->evaluations()->create([
        'result' => 'resolved', 'evaluated_at' => '2026-10-01 08:00:00',
        'recorded_for' => 'existing_business_history', 'evidence' => ['preserved' => true],
    ]);
    $original = $earlier->fresh()->getAttributes();
    $result = app(StartMerlinInvestigationService::class)->start($request);
    expect($result->outcome)->toBe(Outcome::Created)
        ->and($earlier->fresh()->getAttributes())->toBe($original)
        ->and(ProblemCaseEvaluation::query()->count())->toBe(2);
});

it('replays before active and eligibility checks including terminal investigations without new writes', function (bool $terminal): void {
    [, $case, , $request] = trustedAdmissionFixture();
    $first = app(StartMerlinInvestigationService::class)->start($request);
    if ($terminal) {
        DB::table('merlin_runs')->where('id', $first->runId)->update(['status' => 'failed', 'stop_reason' => 'TEST_TERMINAL', 'completed_at' => now()]);
    }
    $case->update(['lifecycle' => ProblemCaseLifecycle::Closed]);
    $replay = app(StartMerlinInvestigationService::class)->start($request);
    expect($replay->outcome)->toBe(Outcome::Replayed)->and($replay->runId)->toBe($first->runId);
    expectAdmissionCounts(1);
})->with([false, true]);

it('does not disclose an active run to another identity even for super-admin', function (bool $superAdmin): void {
    [, $case, , $request] = trustedAdmissionFixture();
    app(StartMerlinInvestigationService::class)->start($request);
    $other = User::factory()->create();
    if ($superAdmin) {
        $other->assignRole(Role::findOrCreate('super-admin', 'web'));
    } else {
        $other->givePermissionTo(['inventory.view', 'item-suppliers.view']);
    }
    Auth::setUser($other);
    $result = app(StartMerlinInvestigationService::class)->start($request);
    expect($result->outcome)->toBe(Outcome::ActiveRunExists)->and($result->runId)->toBeNull();
    expectAdmissionCounts(1);
})->with([false, true]);

it('denies stale authenticated actors before start or replay disclosure', function (string $denial, bool $replay): void {
    [$actor, , , $request] = trustedAdmissionFixture();
    if ($replay) {
        app(StartMerlinInvestigationService::class)->start($request);
    }
    match ($denial) {
        'deleted' => $actor->delete(),
        'anonymous' => auth()->logout(),
        default => $actor->revokePermissionTo($denial),
    };
    $result = app(StartMerlinInvestigationService::class)->start($request);
    expect($result->outcome)->toBe(Outcome::Denied)->and($result->runId)->toBeNull();
    expectAdmissionCounts($replay ? 1 : 0);
})->with(['inventory.view', 'item-suppliers.view', 'deleted', 'anonymous'])->with([false, true]);

it('refreshes permissions revoked after preflight but before opening the owned connection', function (): void {
    [$actor, , , $request] = trustedAdmissionFixture();
    app()->instance(MerlinAdmissionTransaction::class, new class($actor) extends MerlinAdmissionTransaction
    {
        public function __construct(private User $actor) {}

        protected function makeConnection(string $name, array $config): Connection
        {
            $this->actor->revokePermissionTo('item-suppliers.view');

            return parent::makeConnection($name, $config);
        }
    });
    expect(app(StartMerlinInvestigationService::class)->start($request)->outcome)->toBe(Outcome::Denied);
    expectAdmissionCounts(0);
});

it('rejects missing unsupported and current ineligible cases without admission writes', function (string $state): void {
    [, $case, $requirement, $request] = trustedAdmissionFixture();
    match ($state) {
        'missing' => $request = StartInvestigationRequest::fromArray(['problem_case_id' => (string) Str::uuid(), 'idempotency_key' => $request->idempotencyKey]),
        'unsupported' => DB::table('problem_cases')->where('id', $case->id)->update(['type' => 'unsupported']),
        'closed' => $case->update(['lifecycle' => ProblemCaseLifecycle::Closed]),
        'invalid' => $requirement->customerOrderItem->customerOrder->update(['status' => CustomerOrderStatus::Cancelled]),
        'deleted' => $requirement->delete(),
        'resolved' => StockBalance::factory()->create(['item_id' => $requirement->required_item_id, 'quantity' => '12.000']),
        default => throw new InvalidArgumentException('Unknown test eligibility state'),
    };
    expect(app(StartMerlinInvestigationService::class)->start($request)->outcome)->toBe(Outcome::CaseNotEligible);
    expectAdmissionCounts(0);
})->with(['missing', 'unsupported', 'closed', 'invalid', 'deleted', 'resolved']);

it('fails closed on corrupt stored protocol instead of reporting business conflict', function (string $field): void {
    [, , , $request] = trustedAdmissionFixture();
    $first = app(StartMerlinInvestigationService::class)->start($request);
    DB::table('merlin_runs')->where('id', $first->runId)->update([$field => $field === 'request_fingerprint' ? str_repeat('0', 64) : '{}']);
    expect(fn () => app(StartMerlinInvestigationService::class)->start($request))
        ->toThrow(MerlinAdmissionInfrastructureException::class, 'STORED_IDENTITY_INVALID_OR_UNSUPPORTED');
    expectAdmissionCounts(1);
})->with(['request_fingerprint', 'canonical_request']);

it('rolls back every admission write when durable audit fails at its persistence boundary', function (string $failure): void {
    [, $case, , $request] = trustedAdmissionFixture();
    $original = $case->fresh()->getAttributes();
    app()->instance(MerlinAdmissionAuditWriterInterface::class, new class($failure) extends MerlinAdmissionAuditWriter
    {
        public function __construct(private string $failure) {}

        public function write(string $connection, MerlinRun $run, Carbon $eventAt): void
        {
            if ($this->failure === 'before_insert') {
                throw new RuntimeException('AUDIT_TEST_FAILURE');
            }
            if ($this->failure === 'insert_constraint') {
                DB::connection($connection)->table('merlin_run_admission_audits')->insert([
                    'run_id' => $run->id, 'problem_case_id' => (string) Str::uuid(),
                    'historical_actor_id' => $run->historical_actor_id,
                    'starting_evaluation_id' => $run->starting_evaluation_id,
                    'event_type' => 'test.invalid_provenance', 'schema_version' => 1,
                    'event_at' => $eventAt, 'admission_outcome' => 'CREATED',
                    'correlation_id' => (string) Str::uuid(),
                ]);
            }
            parent::write($connection, $run, $eventAt);
            throw new RuntimeException('AUDIT_TEST_FAILURE');
        }
    });
    expect(fn () => app(StartMerlinInvestigationService::class)->start($request))
        ->toThrow($failure === 'insert_constraint' ? QueryException::class : RuntimeException::class);
    expectAdmissionCounts(0);
    expect(DB::transactionLevel())->toBe(0)->and($case->fresh()->getAttributes())->toBe($original);
})->with(['before_insert', 'insert_constraint', 'after_insert']);

it('rolls back real history and run FK insert failures without collision reconciliation', function (string $target): void {
    [, $case, , $request] = trustedAdmissionFixture();
    $original = $case->fresh()->getAttributes();
    app()->instance(MerlinRunRepositoryInterface::class, new class($target) extends MerlinRunRepository
    {
        public function __construct(private string $target) {}

        public function appendStarting(string $connection, MaterialShortageCurrentEvaluation $evaluation, Carbon $observedAt): ProblemCaseEvaluation
        {
            if ($this->target === 'history') {
                DB::connection($connection)->table('problem_case_evaluations')->insert([
                    'problem_case_id' => (string) Str::uuid(), 'result' => 'active', 'evaluated_at' => $observedAt,
                    'recorded_for' => 'test', 'evidence' => '{}', 'created_at' => $observedAt,
                ]);
            }

            return parent::appendStarting($connection, $evaluation, $observedAt);
        }

        public function createPending(string $connection, int $actorId, StartInvestigationRequest $request, int $evaluationId, MerlinRunConfiguration $configuration, Carbon $createdAt): MerlinRun
        {
            return parent::createPending($connection, $actorId, $request, 999999999, $configuration, $createdAt);
        }
    });
    expect(fn () => app(StartMerlinInvestigationService::class)->start($request))->toThrow(QueryException::class);
    expectAdmissionCounts(0);
    expect($case->fresh()->getAttributes())->toBe($original);
})->with(['history', 'run']);

it('preserves caller transaction ownership and writes nothing', function (): void {
    [, , , $request] = trustedAdmissionFixture();
    DB::beginTransaction();
    try {
        expect(fn () => app(StartMerlinInvestigationService::class)->start($request))->toThrow(MerlinAdmissionInfrastructureException::class, 'CALLER_TRANSACTION');
        expect(DB::transactionLevel())->toBe(1);
        expectAdmissionCounts(0);
    } finally {
        DB::rollBack();
    }
});

it('reconciles a real unique collision in a new authorized transaction without another write attempt', function (): void {
    [, , , $request] = trustedAdmissionFixture();
    $first = app(StartMerlinInvestigationService::class)->start($request);
    $repository = new class extends MerlinRunRepository
    {
        public int $reads = 0;

        public function findIdentity(string $connection, int $actorId, StartInvestigationRequest $request): ?MerlinRun
        {
            return ++$this->reads === 1 ? null : parent::findIdentity($connection, $actorId, $request);
        }

        public function hasActive(string $connection, string $caseId): bool
        {
            return false; // Fault injection: simulate an old observation; the actual DB unique constraint still applies.
        }
    };
    app()->instance(MerlinRunRepositoryInterface::class, $repository);
    $result = app(StartMerlinInvestigationService::class)->start($request);
    expect($result->outcome)->toBe(Outcome::Replayed)->and($result->runId)->toBe($first->runId)
        ->and($repository->reads)->toBe(2);
    expectAdmissionCounts(1);
});

/** Skip only the first observations; INSERT still encounters the real database constraint. */
class AdmissionCollisionTestRepository extends MerlinRunRepository
{
    public int $identityReads = 0;

    public int $activeReads = 0;

    public int $writeAttempts = 0;

    public int $collisions = 0;

    public function findIdentity(string $connection, int $actorId, StartInvestigationRequest $request): ?MerlinRun
    {
        return ++$this->identityReads === 1 ? null : parent::findIdentity($connection, $actorId, $request);
    }

    public function hasActive(string $connection, string $caseId): bool
    {
        return ++$this->activeReads === 1 ? false : parent::hasActive($connection, $caseId);
    }

    public function createPending(string $connection, int $actorId, StartInvestigationRequest $request, int $evaluationId, MerlinRunConfiguration $configuration, Carbon $createdAt): MerlinRun
    {
        $this->writeAttempts++;
        try {
            return parent::createPending($connection, $actorId, $request, $evaluationId, $configuration, $createdAt);
        } catch (QueryException $exception) {
            if (MerlinAdmissionUniqueCollision::matches($exception)) {
                $this->collisions++;
            }
            throw $exception;
        }
    }
}

it('reconciles a real unique collision as denied after fresh authorization fails', function (string $boundary): void {
    [$actor, $case, , $request] = trustedAdmissionFixture();
    app(StartMerlinInvestigationService::class)->start($request);
    $original = $case->fresh()->getAttributes();
    $repository = new AdmissionCollisionTestRepository;
    app()->instance(MerlinRunRepositoryInterface::class, $repository);
    $transaction = new class($actor, $boundary) extends MerlinAdmissionTransaction
    {
        public int $attempts = 0;

        public function __construct(private User $actor, private string $boundary) {}

        public function run(string $problemCaseId, Closure $callback): mixed
        {
            $this->attempts++;
            try {
                return parent::run($problemCaseId, $callback);
            } catch (QueryException $exception) {
                // parent has already rolled back, released its actor locks and purged its connection.
                if ($this->boundary === 'preflight' && MerlinAdmissionUniqueCollision::matches($exception)) {
                    $this->actor->revokePermissionTo('item-suppliers.view');
                }
                throw $exception;
            }
        }

        protected function makeConnection(string $name, array $config): Connection
        {
            if ($this->boundary === 'owned' && $this->attempts === 2) {
                $this->actor->revokePermissionTo('item-suppliers.view');
            }

            return parent::makeConnection($name, $config);
        }
    };
    app()->instance(MerlinAdmissionTransaction::class, $transaction);
    $result = app(StartMerlinInvestigationService::class)->start($request);
    expect(get_object_vars($result))->toBe(['outcome' => Outcome::Denied, 'runId' => null])
        ->and($repository->collisions)->toBe(1)->and($repository->writeAttempts)->toBe(1)
        ->and($repository->identityReads)->toBe(1)->and($transaction->attempts)->toBe($boundary === 'preflight' ? 1 : 2)
        ->and($case->fresh()->getAttributes())->toBe($original);
    expectAdmissionCounts(1);
})->with(['preflight', 'owned']);

it('reconciles a real unique collision as active run exists without another actors disclosure or duplicate writes', function (): void {
    [, $case, , $request] = trustedAdmissionFixture();
    $first = app(StartMerlinInvestigationService::class)->start($request);
    $originalRun = MerlinRun::query()->findOrFail($first->runId)->getAttributes();
    $originalCase = $case->fresh()->getAttributes();
    $other = User::factory()->create();
    $other->givePermissionTo(['inventory.view', 'item-suppliers.view']);
    Auth::setUser($other);
    $repository = new AdmissionCollisionTestRepository;
    app()->instance(MerlinRunRepositoryInterface::class, $repository);
    $result = app(StartMerlinInvestigationService::class)->start($request);
    expect(get_object_vars($result))->toBe(['outcome' => Outcome::ActiveRunExists, 'runId' => null])
        ->and($repository->collisions)->toBe(1)->and($repository->writeAttempts)->toBe(1)
        ->and($repository->identityReads)->toBe(2)->and($repository->activeReads)->toBe(2)
        ->and(MerlinRun::query()->findOrFail($first->runId)->getAttributes())->toBe($originalRun)
        ->and($case->fresh()->getAttributes())->toBe($originalCase);
    expectAdmissionCounts(1);
});

it('does not claim success or rollback after a lost commit acknowledgement and resolves a later authorized replay', function (): void {
    [, , , $request] = trustedAdmissionFixture();
    app()->instance(MerlinAdmissionTransaction::class, new class extends MerlinAdmissionTransaction
    {
        public function run(string $problemCaseId, Closure $callback): mixed
        {
            parent::run($problemCaseId, $callback);
            throw new PDOException('Simulated lost acknowledgement after actual commit');
        }
    });
    expect(fn () => app(StartMerlinInvestigationService::class)->start($request))
        ->toThrow(MerlinAdmissionInfrastructureException::class, 'COMMIT_UNCERTAIN');
    expectAdmissionCounts(1);
    app()->instance(MerlinAdmissionTransaction::class, new MerlinAdmissionTransaction);
    expect(app(StartMerlinInvestigationService::class)->start($request)->outcome)->toBe(Outcome::Replayed);
    expectAdmissionCounts(1);
});

/** Accumulate process output without re-entering pipe reads from an output callback on Windows. */
function startAdmissionTestProcessAtMarker(Process $process, string $marker): void
{
    $id = bin2hex(random_bytes(8));
    $path = storage_path('framework/testing/admission-diagnostic-'.$id.'.jsonl');
    $process->setEnv([
        'MERLIN_TEST_DIAGNOSTIC_ID' => $id,
        'MERLIN_TEST_DIAGNOSTIC_PATH' => $path,
        'MERLIN_TEST_DIAGNOSTIC_START' => (string) hrtime(true),
        'MERLIN_TEST_DIAGNOSTIC_PHP' => PHP_BINARY,
    ]);
    admissionProcessCheckpoint($process, 'parent_start', ['parent_pid' => getmypid()]);
    $output = '';
    $received = false;
    $wireMarker = '@@MERLIN:'.$id.':'.$marker;
    try {
        $process->start(function (string $type, string $data) use (&$output, &$received, $process, $wireMarker): void {
            if ($type === Process::OUT) {
                $output .= $data;
                if (! $received && str_contains($output, $wireMarker)) {
                    $received = true;
                    admissionProcessCheckpoint($process, 'parent_marker_received');
                }
            }
        });
    } catch (Throwable $exception) {
        admissionProcessCheckpoint($process, 'parent_start_failed', ['exception' => $exception::class]);
        throw $exception;
    }
    admissionProcessCheckpoint($process, 'parent_started', ['process_pid' => $process->getPid()]);
    // Startup before the lock marker exceeded the former 25-second deadline in a measured failure.
    // Use the existing whole-process budget (60 seconds), including all later lock waits.
    Assert::assertNotNull($process->getTimeout(), 'Admission child must have a finite process timeout.');
    try {
        while (! $received && $process->isRunning()) {
            $process->checkTimeout();
            usleep(10000);
        }
    } catch (ProcessTimedOutException $exception) {
        admissionProcessCheckpoint($process, 'parent_process_timeout');
        throw $exception;
    }
    admissionProcessCheckpoint($process, $received ? 'parent_marker_parsed' : 'parent_marker_missing', ['running' => $process->isRunning()]);
    Assert::assertTrue($received, 'Missing admission test marker '.$marker.'; diagnostic '.$id);
}

/** @param array<string, mixed> $details */
function admissionProcessCheckpoint(Process $process, string $checkpoint, array $details = []): void
{
    $env = $process->getEnv();
    $record = ['id' => $env['MERLIN_TEST_DIAGNOSTIC_ID'], 'checkpoint' => $checkpoint,
        'ms' => round((hrtime(true) - (int) $env['MERLIN_TEST_DIAGNOSTIC_START']) / 1e6, 3)] + $details;
    file_put_contents($env['MERLIN_TEST_DIAGNOSTIC_PATH'], json_encode($record, JSON_THROW_ON_ERROR)."\n", FILE_APPEND | LOCK_EX);
}

/** Report only our bounded protocol, never application stderr, SQL, actor IDs or credentials. */
function stopAdmissionTestProcess(Process $process): void
{
    $env = $process->getEnv();
    if (! isset($env['MERLIN_TEST_DIAGNOSTIC_PATH'])) {
        $process->stop();

        return;
    }
    if (! $process->isStarted()) {
        fwrite(STDERR, "\nMERLIN_DIAGNOSTIC\n".file_get_contents($env['MERLIN_TEST_DIAGNOSTIC_PATH']));
        unlink($env['MERLIN_TEST_DIAGNOSTIC_PATH']);

        return;
    }
    $running = $process->isRunning();
    admissionProcessCheckpoint($process, 'parent_cleanup', ['forced_stop' => $running,
        'exit_code' => $process->getExitCode(), 'stdout_bytes' => strlen($process->getOutput()),
        'stderr_bytes' => strlen($process->getErrorOutput()),
        'stdout_protocol' => preg_match_all('/LOCK_ATTEMPT|HOLDING|ACTOR_READ_ATTEMPT|LOCK_TIMEOUT|DEADLOCK|CASE_NOT_ELIGIBLE|DENIED|CREATED|REPLAYED|ACTIVE_RUN_EXISTS/', $process->getOutput(), $matches) ? array_slice($matches[0], 0, 12) : [],
        'stderr_excerpt' => $process->getErrorOutput() === '' ? '' : '[non-protocol stderr redacted]']);
    $process->stop();
    admissionProcessCheckpoint($process, 'parent_stopped', ['exit_code' => $process->getExitCode()]);
    $records = file_get_contents($env['MERLIN_TEST_DIAGNOSTIC_PATH']);
    fwrite(STDERR, "\nMERLIN_DIAGNOSTIC\n".substr($records === false ? '' : $records, 0, 16000));
    unlink($env['MERLIN_TEST_DIAGNOSTIC_PATH']);
}

/** Boots a separate trusted test security boundary; no actor argument is accepted by the service. */
function admissionChildCode(): string
{
    return <<<'PHP'
        $diagnosticStart = (int) getenv('MERLIN_TEST_DIAGNOSTIC_START');
        function admissionCheckpoint(string $checkpoint, array $details = []): void {
            global $diagnosticStart;
            $record = ['id' => getenv('MERLIN_TEST_DIAGNOSTIC_ID'), 'checkpoint' => $checkpoint,
                'ms' => round((hrtime(true) - $diagnosticStart) / 1e6, 3), 'child_pid' => getmypid()] + $details;
            file_put_contents(getenv('MERLIN_TEST_DIAGNOSTIC_PATH'), json_encode($record, JSON_THROW_ON_ERROR)."\n", FILE_APPEND | LOCK_EX);
        }
        function admissionMarker(string $marker): void {
            admissionCheckpoint('marker_write_before', ['marker' => explode(':', $marker)[0]]);
            $bytes = fwrite(STDOUT, '@@MERLIN:'.getenv('MERLIN_TEST_DIAGNOSTIC_ID').':'.$marker."\n");
            $flushed = fflush(STDOUT);
            admissionCheckpoint('marker_write_after', ['marker' => explode(':', $marker)[0], 'bytes' => $bytes, 'flushed' => $flushed]);
        }
        admissionCheckpoint('child_start', ['cwd_matches' => getcwd() === $argv[1], 'php_matches' => PHP_BINARY === getenv('MERLIN_TEST_DIAGNOSTIC_PHP')]);
        register_shutdown_function(function () {
            $error = error_get_last();
            admissionCheckpoint('child_shutdown', ['fatal_type' => $error['type'] ?? null]);
        });
        require $argv[1].'/vendor/autoload.php';
        admissionCheckpoint('autoload_complete');
        $app = require $argv[1].'/bootstrap/app.php';
        admissionCheckpoint('application_created');
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        admissionCheckpoint('bootstrap_complete');
        App\Support\Testing\TestEnvironmentGuard::assertLaravelConfiguration($app->make('config'));
        admissionCheckpoint('guard_complete');
        Illuminate\Support\Facades\DB::connection()->getPdo();
        admissionCheckpoint('database_connected');
        Illuminate\Support\Facades\Auth::loginUsingId((int) $argv[2]);
        admissionCheckpoint('actor_loaded');
        Illuminate\Support\Facades\Event::listen(Illuminate\Database\Events\QueryExecuted::class, function ($event) {
            if (str_contains($event->sql, 'problem_cases') && str_contains($event->sql, 'for update')) {
                admissionCheckpoint('case_lock_acquired');
            }
        });
        if ($argv[5] === 'hold') {
            $app->instance(App\Services\Merlin\MerlinAdmissionAuditWriterInterface::class, new class('merlin_test_release_'.$argv[3]) extends App\Services\Merlin\MerlinAdmissionAuditWriter {
                public function __construct(private string $barrier) {}
                public function write(string $connection, App\Models\MerlinRun $run, Illuminate\Support\Carbon $at): void {
                    parent::write($connection, $run, $at);
                    admissionMarker('HOLDING');
                    // Test-only session lock; no STDIN pipe is kept open on Windows.
                    $pdo = Illuminate\Support\Facades\DB::connection($connection)->getPdo();
                    $lock = $pdo->prepare('SELECT GET_LOCK(?, 45)');
                    $lock->execute([$this->barrier]);
                    if ((int) $lock->fetchColumn() !== 1) { throw new RuntimeException('Test barrier not released'); }
                    $release = $pdo->prepare('SELECT RELEASE_LOCK(?)');
                    $release->execute([$this->barrier]);
                }
            });
        } else {
            Illuminate\Support\Facades\Event::listen(Illuminate\Database\Events\ConnectionEstablished::class, function ($event) use ($argv) {
                if (str_starts_with($event->connection->getName(), 'merlin_admission_')) {
                    admissionCheckpoint('owned_connection_created');
                    $event->connection->getPdo();
                    admissionCheckpoint('owned_database_connected');
                    if ($argv[5] === 'timeout') { $event->connection->getPdo()->exec('SET SESSION innodb_lock_wait_timeout = 1'); }
                    $event->connection->beforeExecuting(function ($sql) use ($argv, $event) {
                        if (str_contains($sql, 'problem_cases') && str_contains($sql, 'for update')) {
                            admissionCheckpoint('before_lock');
                            $suffix = $argv[5] === 'revoke' ? ':'.$event->connection->getPdo()->query('SELECT CONNECTION_ID()')->fetchColumn() : '';
                            admissionMarker('LOCK_ATTEMPT'.$suffix);
                        }
                        if ($argv[5] === 'deadlock' && str_contains($sql, 'users') && str_contains($sql, 'share')) {
                            admissionMarker('ACTOR_READ_ATTEMPT');
                        }
                    });
                }
            });
        }
        $request = App\Support\Merlin\StartInvestigationRequest::fromArray(['problem_case_id' => $argv[3], 'idempotency_key' => $argv[4]]);
        try {
            admissionCheckpoint('service_enter');
            $result = $app->make(App\Services\Merlin\StartMerlinInvestigationService::class)->start($request);
            admissionCheckpoint('service_return', ['outcome' => $result->outcome->value]);
            echo json_encode(['outcome' => $result->outcome->value, 'run_id' => $result->runId], JSON_THROW_ON_ERROR);
        } catch (Illuminate\Database\QueryException $e) {
            $code = $e->errorInfo[1] ?? null;
            if (!in_array($code, [1205, 1213], true)) { throw $e; }
            admissionCheckpoint('database_error', ['code' => $code]);
            echo $code === 1205 ? 'LOCK_TIMEOUT' : 'DEADLOCK';
        }
        PHP;
}

it('serializes real MySQL same-key and different-key concurrent admission atomically', function (bool $sameKey): void {
    if (DB::connection()->getDriverName() !== 'mysql') {
        Assert::markTestSkipped('Real verified disposable MySQL concurrency evidence only.');
    }
    [$actor, $case, , $request] = trustedAdmissionFixture();
    $config = DB::connection()->getConfig();
    $releaseConnection = new PDO("mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4", $config['username'], $config['password']);
    $barrier = 'merlin_test_release_'.$case->id;
    $acquire = $releaseConnection->prepare('SELECT GET_LOCK(?, 0)');
    $acquire->execute([$barrier]);
    expect((int) $acquire->fetchColumn())->toBe(1);
    $release = $releaseConnection->prepare('SELECT RELEASE_LOCK(?)');
    $first = new Process([PHP_BINARY, '-d', 'xdebug.mode=off', '-r', admissionChildCode(), base_path(), (string) $actor->id, $case->id, $request->idempotencyKey, 'hold'], base_path(), null, null, 60);
    $second = new Process([PHP_BINARY, '-d', 'xdebug.mode=off', '-r', admissionChildCode(), base_path(), (string) $actor->id, $case->id, $sameKey ? $request->idempotencyKey : (string) Str::uuid(), 'observe'], base_path(), null, null, 60);
    try {
        startAdmissionTestProcessAtMarker($first, 'HOLDING');
        startAdmissionTestProcessAtMarker($second, 'LOCK_ATTEMPT');
        expect($second->isRunning())->toBeTrue();
        $release->execute([$barrier]);
        expect($first->wait())->toBe(0)->and($second->wait())->toBe(0);
        $created = json_decode(explode("\n", $first->getOutput(), 2)[1], true, 512, JSON_THROW_ON_ERROR);
        $other = json_decode(explode("\n", $second->getOutput(), 2)[1], true, 512, JSON_THROW_ON_ERROR);
        expect($created['outcome'])->toBe('CREATED')
            ->and($other['outcome'])->toBe($sameKey ? 'REPLAYED' : 'ACTIVE_RUN_EXISTS')
            ->and($other['run_id'])->toBe($sameKey ? $created['run_id'] : null);
        expectAdmissionCounts(1);
    } finally {
        stopAdmissionTestProcess($first);
        stopAdmissionTestProcess($second);
        $release->execute([$barrier]);
    }
})->with([true, false]);

it('uses fresh eligibility after a real MySQL case lock wait and propagates timeout without retry', function (bool $timeout): void {
    if (DB::connection()->getDriverName() !== 'mysql') {
        Assert::markTestSkipped('Real MySQL lock wait evidence only.');
    }
    [$actor, $case, $requirement, $request] = trustedAdmissionFixture();
    $config = DB::connection()->getConfig();
    $writer = new PDO("mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4", $config['username'], $config['password']);
    $process = new Process([PHP_BINARY, '-d', 'xdebug.mode=off', '-r', admissionChildCode(), base_path(), (string) $actor->id, $case->id, $request->idempotencyKey, $timeout ? 'timeout' : 'observe'], base_path(), null, null, 60);
    $writer->beginTransaction();
    try {
        $statement = $writer->prepare('SELECT id FROM problem_cases WHERE id = ? FOR UPDATE');
        $statement->execute([$case->id]);
        $update = $writer->prepare('UPDATE material_requirements SET required_quantity = 0 WHERE id = ?');
        $update->execute([$requirement->id]);
        startAdmissionTestProcessAtMarker($process, 'LOCK_ATTEMPT');
        if ($timeout) {
            expect($process->wait())->toBe(0)->and($process->getOutput())->toContain('LOCK_TIMEOUT');
            expect(substr_count($process->getOutput(), 'LOCK_ATTEMPT'))->toBe(1);
            $writer->rollBack();
        } else {
            $writer->commit();
            expect($process->wait())->toBe(0);
            $result = json_decode(explode("\n", $process->getOutput(), 2)[1], true, 512, JSON_THROW_ON_ERROR);
            expect($result['outcome'])->toBe('CASE_NOT_ELIGIBLE')->and($result['run_id'])->toBeNull();
            admissionProcessCheckpoint($process, 'eligibility_assertions_passed');
        }
        expectAdmissionCounts(0);
    } finally {
        try {
            stopAdmissionTestProcess($process);
        } finally {
            if ($writer->inTransaction()) {
                $writer->rollBack();
            }
        }
    }
})->with([false, true]);

it('propagates a real MySQL deadlock victim without retry or partial admission', function (): void {
    if (DB::connection()->getDriverName() !== 'mysql') {
        Assert::markTestSkipped('Real MySQL deadlock evidence only.');
    }
    [$actor, $case, , $request] = trustedAdmissionFixture();
    $requirements = MaterialRequirement::factory()->count(20)->create();
    $config = DB::connection()->getConfig();
    $writer = new PDO("mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4", $config['username'], $config['password']);
    $process = new Process([PHP_BINARY, '-d', 'xdebug.mode=off', '-r', admissionChildCode(), base_path(), (string) $actor->id, $case->id, $request->idempotencyKey, 'deadlock'], base_path(), null, null, 60);
    $writer->beginTransaction();
    try {
        // Make this external transaction heavier than the read-only admission victim.
        $update = $writer->prepare('UPDATE material_requirements SET required_quantity = 1 WHERE id = ?');
        foreach ($requirements as $requirement) {
            $update->execute([$requirement->id]);
        }
        $userLock = $writer->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE');
        $userLock->execute([$actor->id]);
        startAdmissionTestProcessAtMarker($process, 'ACTOR_READ_ATTEMPT');
        // Child owns case and waits on user; writer owns user and requests case.
        $caseLock = $writer->prepare('SELECT id FROM problem_cases WHERE id = ? FOR UPDATE');
        $caseLock->execute([$case->id]);
        expect($process->wait())->toBe(0)->and($process->getOutput())->toContain('DEADLOCK')
            ->and(substr_count($process->getOutput(), 'LOCK_ATTEMPT'))->toBe(1);
        $writer->rollBack();
        expectAdmissionCounts(0);
    } finally {
        try {
            stopAdmissionTestProcess($process);
        } finally {
            if ($writer->inTransaction()) {
                $writer->rollBack();
            }
        }
    }
});

it('denies permissions revoked during verified real MySQL case lock contention', function (string $permission): void {
    if (DB::connection()->getDriverName() !== 'mysql') {
        Assert::markTestSkipped('Requires real MySQL case-row contention and an independent authenticated process.');
    }
    [$actor, $case, , $request] = trustedAdmissionFixture();
    $original = $case->fresh()->getAttributes();
    $config = DB::connection()->getConfig();
    $writer = new PDO("mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4", $config['username'], $config['password']);
    $process = new Process([PHP_BINARY, '-d', 'xdebug.mode=off', '-r', admissionChildCode(), base_path(), (string) $actor->id, $case->id, $request->idempotencyKey, 'revoke'], base_path(), null, null, 60);
    $writer->beginTransaction();
    try {
        $lock = $writer->prepare('SELECT id FROM problem_cases WHERE id = ? FOR UPDATE');
        $lock->execute([$case->id]);
        startAdmissionTestProcessAtMarker($process, 'LOCK_ATTEMPT:');
        Assert::assertSame(1, preg_match('/LOCK_ATTEMPT:(\d+)/', $process->getOutput(), $matches));
        $connectionId = (int) $matches[1];
        $waiting = false;
        $deadline = microtime(true) + 5;
        do {
            // Without PROCESS privilege this lists only this dedicated test user's sessions.
            // A one-row PK SELECT still executing after >= 1 second under our held row lock proves contention.
            foreach ($writer->query('SHOW FULL PROCESSLIST')->fetchAll(PDO::FETCH_ASSOC) as $session) {
                if ((int) $session['Id'] === $connectionId && (int) $session['Time'] >= 1
                    && str_contains((string) $session['Info'], 'problem_cases')
                    && str_contains((string) $session['Info'], 'for update')) {
                    $waiting = true;
                    break;
                }
            }
            if (! $waiting) {
                usleep(20000);
            }
        } while (! $waiting && $process->isRunning() && microtime(true) < $deadline);
        expect($waiting)->toBeTrue();
        // Preflight has passed, the child is blocked before its fresh locked actor read.
        // Revoke on the parent's independent default connection while the writer keeps the case lock.
        $actor->revokePermissionTo($permission);
        expect(User::query()->findOrFail($actor->id)->hasPermissionTo($permission))->toBeFalse();
        $writer->commit();
        expect($process->wait())->toBe(0);
        $result = json_decode(explode("\n", $process->getOutput(), 2)[1], true, 512, JSON_THROW_ON_ERROR);
        expect($result)->toBe(['outcome' => 'DENIED', 'run_id' => null])
            ->and(substr_count($process->getOutput(), 'LOCK_ATTEMPT:'))->toBe(1)
            ->and($case->fresh()->getAttributes())->toBe($original);
        expectAdmissionCounts(0);
    } finally {
        try {
            stopAdmissionTestProcess($process);
        } finally {
            if ($writer->inTransaction()) {
                $writer->rollBack();
            }
        }
    }
})->with(['inventory.view', 'item-suppliers.view']);
