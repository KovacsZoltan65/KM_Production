<?php

use App\Support\Testing\TestEnvironmentGuard;

function safeTestConfiguration(array $overrides = []): array
{
    return array_replace([
        'environment' => 'testing',
        'connection' => 'sqlite',
        'database' => ':memory:',
        'database_url' => null,
        'host' => null,
        'username' => null,
        'cache' => 'array',
        'session' => 'array',
        'queue' => 'sync',
        'mail' => 'array',
        'filesystem' => 'testing',
        'broadcast' => 'null',
        'log' => 'stderr',
    ], $overrides);
}

it('accepts the isolated SQLite memory configuration', function () {
    expect(fn () => TestEnvironmentGuard::assertSafe(safeTestConfiguration()))->not->toThrow(RuntimeException::class);
});

it('accepts the dedicated MySQL testing configuration', function () {
    expect(fn () => TestEnvironmentGuard::assertSafe(safeTestConfiguration([
        'connection' => 'mysql',
        'database' => 'km_production_testing',
        'host' => '127.0.0.1',
        'username' => 'km_testing',
    ])))->not->toThrow(RuntimeException::class);
});

it('rejects a development or production database name', function (string $database) {
    expect(fn () => TestEnvironmentGuard::assertSafe(safeTestConfiguration([
        'connection' => 'mysql',
        'database' => $database,
        'host' => '127.0.0.1',
        'username' => 'km_testing',
    ])))->toThrow(RuntimeException::class);
})->with(['km_production', 'production', 'prod', 'live', 'main', 'customer_data']);

it('rejects a non-testing application environment', function () {
    expect(fn () => TestEnvironmentGuard::assertSafe(safeTestConfiguration([
        'environment' => 'local',
    ])))->toThrow(RuntimeException::class);
});

it('rejects external stateful test services', function (array $overrides) {
    expect(fn () => TestEnvironmentGuard::assertSafe(safeTestConfiguration($overrides)))
        ->toThrow(RuntimeException::class);
})->with([
    'redis cache' => [['cache' => 'redis']],
    'database queue' => [['queue' => 'database']],
    'database session' => [['session' => 'database']],
    'smtp mail' => [['mail' => 'smtp']],
    's3 filesystem' => [['filesystem' => 's3']],
]);

it('rejects a database URL override and a non-local MySQL host', function (array $overrides) {
    expect(fn () => TestEnvironmentGuard::assertSafe(safeTestConfiguration(array_replace([
        'connection' => 'mysql',
        'database' => 'km_production_testing',
        'host' => '127.0.0.1',
        'username' => 'km_testing',
    ], $overrides))))->toThrow(RuntimeException::class);
})->with([
    'database URL' => [['database_url' => 'mysql://example.invalid/database']],
    'remote host' => [['host' => 'database.example.com']],
]);
