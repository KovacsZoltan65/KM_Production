<?php

use App\Support\Testing\TestDiagnostics;
use Illuminate\Config\Repository;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\QueryException;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

it('masks query bindings in logged test exceptions without opening a database', function () {
    $config = new Repository(['app' => ['env' => 'testing'], 'database' => ['connections' => ['mysql' => ['driver' => 'mysql', 'database' => 'synthetic_database', 'name' => 'synthetic']]]]);
    TestDiagnostics::configure($config);
    $pdo = new class extends PDO
    {
        public function __construct() {}

        public function prepare(string $query, array $options = []): PDOStatement|false
        {
            throw new PDOException('Synthetic database failure', 23000);
        }
    };
    $connection = new MySqlConnection($pdo, 'synthetic_database', '', $config->get('database.connections.mysql'));
    $secret = 'CI_SYNTHETIC_'.bin2hex(random_bytes(16));
    $caught = null;
    try {
        $connection->select('select * from synthetic_table where token = ?', [$secret]);
    } catch (QueryException $exception) {
        $caught = $exception;
    }
    expect($caught instanceof QueryException)->toBeTrue();
    if (! $caught instanceof QueryException) {
        throw new RuntimeException('Expected query exception was not raised.');
    }
    $stream = fopen('php://memory', 'w+');
    $logger = new Logger('synthetic', [new StreamHandler($stream)]);
    $logger->error($caught->getMessage());
    rewind($stream);
    $logged = stream_get_contents($stream);
    expect(str_contains($logged, $secret))->toBeFalse()
        ->and(str_contains($logged, 'token = ?'))->toBeTrue()
        ->and(str_contains($logged, 'synthetic_database'))->toBeTrue()
        ->and($caught->getPrevious() instanceof PDOException)->toBeTrue()
        ->and((int) $caught->getCode())->toBe(23000);
    fclose($stream);
});

it('configures every test connection and leaves production configuration untouched', function () {
    foreach (['testing', 'production', 'local', 'e2e'] as $environment) {
        $original = ['app' => ['env' => $environment], 'database' => ['connections' => ['sqlite' => ['driver' => 'sqlite'], 'mysql' => ['driver' => 'mysql']]]];
        $config = new Repository($original);
        TestDiagnostics::configure($config);
        if ($environment !== 'testing') {
            expect($config->all())->toBe($original);
        } else {
            foreach (['sqlite', 'mysql'] as $connection) {
                expect($config->get("database.connections.{$connection}.mask_bindings_in_exception_messages"))->toBeTrue();
            }
        }
    }
});
