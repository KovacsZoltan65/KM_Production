<?php

use App\Support\Procurement\SupplierOptionEvaluationException;
use App\Support\Procurement\SupplierOptionReadSnapshot;
use Illuminate\Database\MySqlConnection;
use Illuminate\Support\Facades\DB;
use Mockery\CompositeExpectation;

it('keeps SQLite reads in one snapshot across an independent committed update', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'supplier-options-');
    $original = DB::getDefaultConnection();
    config(['database.connections.supplier_option_snapshot' => [
        'driver' => 'sqlite', 'database' => $path, 'foreign_key_constraints' => true,
    ]]);
    DB::setDefaultConnection('supplier_option_snapshot');
    $writer = new PDO('sqlite:'.$path);
    try {
        $writer->exec('PRAGMA journal_mode = WAL');
        $writer->exec('CREATE TABLE snapshot_probe (id INTEGER PRIMARY KEY, value INTEGER)');
        $writer->exec('INSERT INTO snapshot_probe VALUES (1, 1)');
        $values = app(SupplierOptionReadSnapshot::class)->evaluate(function () use (&$writer): array {
            $before = DB::table('snapshot_probe')->value('value');
            $writer->exec('UPDATE snapshot_probe SET value = 2 WHERE id = 1');

            return [$before, DB::table('snapshot_probe')->value('value')];
        });
        expect($values)->toBe([1, 1])->and(DB::table('snapshot_probe')->value('value'))->toBe(2)
            ->and(DB::transactionLevel())->toBe(0);
        DB::statement('PRAGMA read_uncommitted = 1');
        expect(fn () => app(SupplierOptionReadSnapshot::class)->evaluate(fn () => true))
            ->toThrow(SupplierOptionEvaluationException::class, 'SUPPLIER_OPTION_SNAPSHOT_UNAVAILABLE');
    } finally {
        $writer = null;
        DB::purge('supplier_option_snapshot');
        DB::setDefaultConnection($original);
        // Release connection/closure cycles before unlinking an open SQLite file on Windows.
        gc_collect_cycles();
        unlink($path);
    }
});

it('rejects caller-owned MySQL transactions without changing their isolation', function (): void {
    $connection = Mockery::mock(MySqlConnection::class);
    foreach (['getDriverName' => 'mysql', 'transactionLevel' => 1] as $method => $value) {
        $expectation = $connection->shouldReceive($method);
        if (! $expectation instanceof CompositeExpectation) {
            throw new RuntimeException('Expected a concrete connection expectation.');
        }
        $expectation->__call('once', []);
        $expectation->andReturn($value);
    }

    $connection->shouldNotReceive('statement');
    $connection->shouldNotReceive('transaction');
    DB::shouldReceive('connection')->once()->andReturn($connection);

    expect(fn () => app(SupplierOptionReadSnapshot::class)->evaluate(fn () => true))
        ->toThrow(SupplierOptionEvaluationException::class, 'SUPPLIER_OPTION_CALLER_TRANSACTION_UNVERIFIED');
});

it('rejects a reconnected PDO before reading and rolls back its transaction', function (string $reconnectAt): void {
    // SQLite PDOs exercise Laravel's real transaction/reconnect lifecycle here.
    // SET TRANSACTION is mocked; this is not proof of MySQL runtime isolation.
    $originalPdo = new class($reconnectAt === 'begin') extends PDO
    {
        public function __construct(private readonly bool $loseConnection)
        {
            parent::__construct('sqlite::memory:');
        }

        public function beginTransaction(): bool
        {
            if ($this->loseConnection) {
                throw new PDOException('MySQL server has gone away');
            }

            return parent::beginTransaction();
        }
    };
    $replacementPdo = new PDO('sqlite::memory:');
    $connection = Mockery::mock(MySqlConnection::class.'[statement]', [$originalPdo, '', '', ['driver' => 'mysql']]);
    $reconnects = 0;
    $connection->setReconnector(function (MySqlConnection $connection) use ($replacementPdo, &$reconnects): void {
        $reconnects++;
        $connection->setPdo($replacementPdo);
    });
    $connection->shouldReceive('statement')->once()
        ->with('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ, READ ONLY')
        ->andReturnUsing(function () use ($connection, $reconnectAt): bool {
            if ($reconnectAt === 'policy') {
                $connection->reconnect();
            }

            return true;
        });
    DB::shouldReceive('connection')->once()->andReturn($connection);
    $reads = 0;

    expect(fn () => app(SupplierOptionReadSnapshot::class)->evaluate(function () use (&$reads): void {
        $reads++;
    }))->toThrow(SupplierOptionEvaluationException::class, 'SUPPLIER_OPTION_CONNECTION_CHANGED');

    expect($reconnects)->toBe(1)
        ->and($reads)->toBe(0)
        ->and($connection->transactionLevel())->toBe(0)
        ->and($replacementPdo->inTransaction())->toBeFalse()
        ->and($originalPdo->inTransaction())->toBeFalse();
})->with(['policy', 'begin']);

it('reads and commits when the MySQL policy PDO stays unchanged', function (): void {
    $pdo = new PDO('sqlite::memory:');
    $connection = Mockery::mock(MySqlConnection::class.'[statement]', [$pdo, '', '', ['driver' => 'mysql']]);
    $connection->shouldReceive('statement')->once()
        ->with('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ, READ ONLY')->andReturnTrue();
    DB::shouldReceive('connection')->once()->andReturn($connection);

    $result = app(SupplierOptionReadSnapshot::class)->evaluate(function () use ($pdo, $connection): string {
        expect($pdo->inTransaction())->toBeTrue()->and($connection->transactionLevel())->toBe(1);

        return 'read result';
    });

    expect($result)->toBe('read result')
        ->and($connection->transactionLevel())->toBe(0)
        ->and($pdo->inTransaction())->toBeFalse();
});

it('preserves a caller-owned PDO transaction that Laravel does not track', function (): void {
    $pdo = new PDO('sqlite::memory:');
    $connection = Mockery::mock(MySqlConnection::class.'[statement,transaction]', [$pdo, '', '', ['driver' => 'mysql']]);
    $connection->shouldNotReceive('statement');
    $connection->shouldNotReceive('transaction');
    DB::shouldReceive('connection')->once()->andReturn($connection);
    $pdo->beginTransaction();

    try {
        expect(fn () => app(SupplierOptionReadSnapshot::class)->evaluate(fn () => true))
            ->toThrow(SupplierOptionEvaluationException::class, 'SUPPLIER_OPTION_CALLER_TRANSACTION_UNVERIFIED');
        expect($pdo->inTransaction())->toBeTrue()->and($connection->transactionLevel())->toBe(0);
    } finally {
        $pdo->rollBack();
    }
});
