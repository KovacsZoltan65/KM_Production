<?php

use App\Enums\CustomerOrderStatus;
use App\Enums\ProblemCaseEvaluationResult;
use App\Models\MaterialRequirement;
use App\Models\ProblemCase;
use App\Models\User;
use App\Services\Admin\MaterialShortageProblemCaseResolver;
use App\Support\Merlin\MerlinAdmissionContext;
use App\Support\Merlin\MerlinAdmissionInfrastructureException;
use App\Support\Merlin\MerlinAdmissionTransaction;
use Illuminate\Database\Connection;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PHPUnit\Framework\Assert;
use Spatie\Permission\Models\Permission;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

uses(DatabaseMigrations::class);

beforeEach(function (): void {
    if (DB::connection()->getDriverName() === 'sqlite') {
        $directory = storage_path('framework/testing');
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        $path = tempnam($directory, 'merlin-admission-');
        if ($path === false) {
            throw new RuntimeException('Cannot create admission test database.');
        }
        // Windows may retain framework PDO handles until process exit.
        // These disposable files stay in the project's dedicated testing storage.
        // Copy the already migrated memory fixture into an independent file-backed test DB.
        DB::connection()->getPdo()->exec('VACUUM INTO '.DB::connection()->getPdo()->quote($path));
        Schema::clearResolvedInstance('db.schema');
        DB::purge('sqlite');
        config(['database.connections.sqlite.database' => $path]);
    }
    Schema::create('merlin_admission_probe', function ($table): void {
        $table->id();
        $table->integer('value');
    });
    DB::table('merlin_admission_probe')->insert(['id' => 1, 'value' => 1]);
});

/** No history, run or admission audit is created by this fixture. @return array{ProblemCase, MaterialRequirement} */
function admissionInfrastructureCase(): array
{
    $requirement = MaterialRequirement::factory()->create(['required_quantity' => '12.000', 'missing_quantity' => '999.000']);
    $requirement->customerOrderItem->customerOrder->update(['status' => CustomerOrderStatus::Confirmed]);
    $case = ProblemCase::query()->create([
        'type' => 'material_shortage', 'material_requirement_id' => $requirement->id, 'lifecycle' => 'open',
        'detection_snapshot' => [], 'current_evaluation' => 'active',
        'current_evaluated_at' => '2026-10-14 08:00:00', 'current_evaluation_evidence' => [],
    ]);

    return [$case, $requirement];
}

it('rejects tracked and untracked caller transactions without ending them', function (bool $tracked): void {
    [$case] = admissionInfrastructureCase();
    $base = DB::connection();
    $pdo = $base->getPdo();
    $tracked ? $base->beginTransaction() : $pdo->beginTransaction();
    try {
        expect(fn () => (new MerlinAdmissionTransaction)->run($case->id, fn () => throw new LogicException('Callback must not execute')))
            ->toThrow(MerlinAdmissionInfrastructureException::class, 'MERLIN_ADMISSION_CALLER_TRANSACTION');
        expect($pdo->inTransaction())->toBeTrue()->and($base->transactionLevel())->toBe($tracked ? 1 : 0);
    } finally {
        $tracked ? $base->rollBack() : $pdo->rollBack();
    }
})->with([true, false]);

it('locks first and binds every resolver and fresh actor read to the owned connection', function (): void {
    [$case] = admissionInfrastructureCase();
    $ordinary = app(MaterialShortageProblemCaseResolver::class)->resolve($case->id);
    $user = User::factory()->create();
    foreach (['inventory.view', 'item-suppliers.view'] as $permission) {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }
    $default = DB::getDefaultConnection();
    $configuration = config('database.connections');
    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        $queries[] = [$query->connectionName, $query->sql];
    });
    $escaped = null;
    $name = null;
    $result = (new MerlinAdmissionTransaction)->run($case->id, function (MerlinAdmissionContext $context) use ($ordinary, $user, &$escaped, &$name): string {
        $escaped = $context;
        $name = $context->connectionName();
        $connection = DB::connection($name);
        expect($connection->transactionLevel())->toBe(1)->and($connection->getPdo()->inTransaction())->toBeTrue();
        $fresh = $context->freshActor($user->id);
        expect($fresh)->not->toBe($user)->and(Gate::forUser($fresh)->allows('inventory.view'))->toBeTrue()
            ->and(Gate::forUser($fresh)->allows('item-suppliers.view'))->toBeTrue();
        $evaluation = $context->resolveCurrent();
        expect($evaluation->evaluation)->toBe(ProblemCaseEvaluationResult::Active)
            ->and($evaluation->netRequirement())->toBe($ordinary->netRequirement());

        return $evaluation->netRequirement();
    });
    expect($result)->toBe('12.000')->and($queries[0][0])->toBe($name)
        ->and($queries[0][1])->toContain('problem_cases')
        ->and(array_unique(array_column($queries, 0)))->toBe([$name])
        ->and(DB::getDefaultConnection())->toBe($default)->and(config('database.connections'))->toBe($configuration)
        ->and(array_key_exists($name, DB::getConnections()))->toBeFalse()
        ->and(DB::transactionLevel())->toBe(0);
    expect(fn () => $escaped->connectionName())->toThrow(MerlinAdmissionInfrastructureException::class);
    expect(DB::table('merlin_runs')->count())->toBe(0)
        ->and(DB::table('problem_case_evaluations')->count())->toBe(0)
        ->and(DB::table('merlin_run_admission_audits')->count())->toBe(0);
});

it('rolls back a callback exception without retry and cleans up the owned connection', function (): void {
    [$case] = admissionInfrastructureCase();
    $attempts = 0;
    $name = null;
    expect(function () use ($case, &$attempts, &$name): void {
        (new MerlinAdmissionTransaction)->run($case->id, function (MerlinAdmissionContext $context) use (&$attempts, &$name): void {
            $attempts++;
            $name = $context->connectionName();
            DB::connection($name)->table('merlin_admission_probe')->insert(['value' => 2]);
            throw new RuntimeException('Callback failure');
        });
    })->toThrow(RuntimeException::class, 'Callback failure');
    expect($attempts)->toBe(1)->and(DB::table('merlin_admission_probe')->count())->toBe(1)
        ->and(array_key_exists($name, DB::getConnections()))->toBeFalse();
});

it('rejects lost transaction state and unsafe reconnect while cleaning the original PDO', function (string $loss): void {
    [$case] = admissionInfrastructureCase();
    $original = null;
    expect(function () use ($case, $loss, &$original): void {
        (new MerlinAdmissionTransaction)->run($case->id, function (MerlinAdmissionContext $context) use ($loss, &$original): void {
            $connection = DB::connection($context->connectionName());
            $original = $connection->getPdo();
            DB::connection($context->connectionName())->table('merlin_admission_probe')->insert(['value' => 2]);
            match ($loss) {
                'rollback' => $original->rollBack(),
                'replacement' => $connection->setPdo(new PDO('sqlite::memory:')),
                'reconnect' => $connection->reconnect(),
                default => throw new LogicException('Unknown failure fixture'),
            };
            $context->resolveCurrent();
        });
    })->toThrow(MerlinAdmissionInfrastructureException::class);
    expect($original->inTransaction())->toBeFalse()->and(DB::table('merlin_admission_probe')->count())->toBe(1);
    $original = null;
})->with(['rollback', 'replacement', 'reconnect']);

it('takes SQLite write intent before callback writes and preserves the global deferred configuration', function (): void {
    if (DB::connection()->getDriverName() !== 'sqlite') {
        Assert::markTestSkipped('SQLite IMMEDIATE evidence only.');
    }
    [$case] = admissionInfrastructureCase();
    $path = config('database.connections.sqlite.database');
    $second = new PDO('sqlite:'.$path);
    $second->exec('PRAGMA busy_timeout = 50');
    (new MerlinAdmissionTransaction)->run($case->id, function (MerlinAdmissionContext $context) use ($second): void {
        expect(DB::connection($context->connectionName())->getConfig('transaction_mode'))->toBe('IMMEDIATE')
            ->and(config('database.connections.sqlite.transaction_mode'))->toBe('DEFERRED');
        expect(fn () => $second->exec('UPDATE merlin_admission_probe SET value = 2 WHERE id = 1'))->toThrow(PDOException::class, 'locked');
    });
    $second->exec('UPDATE merlin_admission_probe SET value = 2 WHERE id = 1');
    expect(DB::table('merlin_admission_probe')->value('value'))->toBe(2);
});

it('does not open an isolated empty SQLite memory connection as an admission scope', function (): void {
    if (DB::connection()->getDriverName() !== 'sqlite') {
        Assert::markTestSkipped('SQLite memory isolation only.');
    }
    $original = config('database.connections.sqlite.database');
    DB::purge('sqlite');
    config(['database.connections.sqlite.database' => ':memory:']);
    try {
        expect(fn () => (new MerlinAdmissionTransaction)->run((string) Str::uuid(), fn () => null))
            ->toThrow(MerlinAdmissionInfrastructureException::class, 'MERLIN_ADMISSION_FILE_BACKED_SQLITE_REQUIRED');
    } finally {
        DB::purge('sqlite');
        config(['database.connections.sqlite.database' => $original]);
    }
});

it('overrides MySQL next transaction isolation and access mode on the identical PDO', function (): void {
    if (DB::connection()->getDriverName() !== 'mysql') {
        Assert::markTestSkipped('Real MySQL runtime evidence required.');
    }
    [$case] = admissionInfrastructureCase();
    $transaction = new class extends MerlinAdmissionTransaction
    {
        public ?PDO $policyPdo = null;

        protected function makeConnection(string $name, array $config): Connection
        {
            $connection = parent::makeConnection($name, $config);
            $this->policyPdo = $connection->getPdo();
            $this->policyPdo->exec('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED, READ ONLY');

            return $connection;
        }
    };
    $configuration = DB::connection()->getConfig();
    $writer = new PDO("mysql:host={$configuration['host']};port={$configuration['port']};dbname={$configuration['database']};charset=utf8mb4", $configuration['username'], $configuration['password']);
    $transaction->run($case->id, function (MerlinAdmissionContext $context) use ($transaction, $writer): void {
        $owned = DB::connection($context->connectionName());
        expect($owned->getPdo())->toBe($transaction->policyPdo);
        // A next-transaction READ WRITE override is proven by a real write, not @@session.
        $owned->table('merlin_admission_probe')->insert(['value' => 3]);
        expect($owned->table('merlin_admission_probe')->where('id', 1)->value('value'))->toBe(1);
        $writer->exec('UPDATE merlin_admission_probe SET value = 2 WHERE id = 1');
        expect($owned->table('merlin_admission_probe')->where('id', 1)->value('value'))->toBe(1);
    });
    expect(DB::table('merlin_admission_probe')->where('id', 1)->value('value'))->toBe(2);
});

it('waits on the real MySQL case lock and observes fresh demand on a new owned attempt', function (): void {
    if (DB::connection()->getDriverName() !== 'mysql') {
        Assert::markTestSkipped('Requires the verified disposable MySQL database and independent process.');
    }
    [$case, $requirement] = admissionInfrastructureCase();
    $configuration = DB::connection()->getConfig();
    $writer = new PDO("mysql:host={$configuration['host']};port={$configuration['port']};dbname={$configuration['database']};charset=utf8mb4", $configuration['username'], $configuration['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $childCode = <<<'PHP'
        require $argv[1].'/vendor/autoload.php';
        $app = require $argv[1].'/bootstrap/app.php';
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        App\Support\Testing\TestEnvironmentGuard::assertLaravelConfiguration($app->make('config'));
        Illuminate\Support\Facades\Event::listen(Illuminate\Database\Events\ConnectionEstablished::class, function ($event) {
            if (str_starts_with($event->connection->getName(), 'merlin_admission_')) {
                $event->connection->getPdo()->exec('SET SESSION innodb_lock_wait_timeout = 1');
            }
        });
        $queries = [];
        Illuminate\Support\Facades\DB::listen(function ($event) use (&$queries) { $queries[] = [$event->connectionName, $event->sql]; });
        $callbacks = 0;
        $transaction = new App\Support\Merlin\MerlinAdmissionTransaction;
        try {
            $transaction->run($argv[2], function () use (&$callbacks) { $callbacks++; });
            exit(2);
        } catch (Illuminate\Database\QueryException $e) {
            if (($e->errorInfo[1] ?? null) !== 1205 || $callbacks !== 0) { exit(3); }
            echo "LOCK_WAITED\n";
            flush();
        }
        if (trim(fgets(STDIN)) !== 'COMMITTED') { exit(4); }
        $result = $transaction->run($argv[2], function ($context) {
            return ['net' => $context->resolveCurrent()->netRequirement(), 'connection' => $context->connectionName()];
        });
        $result['first_sql'] = $queries[0][1];
        $result['connections'] = array_values(array_unique(array_column($queries, 0)));
        echo json_encode($result, JSON_THROW_ON_ERROR);
        PHP;
    $input = new InputStream;
    $process = new Process([PHP_BINARY, '-r', $childCode, base_path(), $case->id], base_path(), null, $input, 20);
    $writer->beginTransaction();
    try {
        $lock = $writer->prepare('SELECT id FROM problem_cases WHERE id = ? FOR UPDATE');
        $lock->execute([$case->id]);
        $update = $writer->prepare('UPDATE material_requirements SET required_quantity = ? WHERE id = ?');
        $update->execute(['17.000', $requirement->id]);
        $process->start();
        expect($process->waitUntil(fn (string $type, string $output): bool => str_contains($output, 'LOCK_WAITED')))->toBeTrue();
        $writer->commit();
        $input->write("COMMITTED\n");
        $input->close();
        expect($process->wait())->toBe(0);
        $result = json_decode(explode("\n", $process->getOutput(), 2)[1], true, 512, JSON_THROW_ON_ERROR);
        expect($result['net'])->toBe('17.000')->and($result['first_sql'])->toContain('problem_cases', 'for update')
            ->and($result['connections'])->toBe([$result['connection']]);
    } finally {
        if ($writer->inTransaction()) {
            $writer->rollBack();
        }
        $input->close();
        $process->stop();
    }
});
