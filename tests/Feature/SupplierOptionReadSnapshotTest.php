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
