<?php

declare(strict_types=1);

$engine = $argv[1] ?? null;
$action = $argv[2] ?? null;
$additionalArguments = array_slice($argv, 3);

if (! in_array($engine, ['sqlite', 'mysql'], true) || ! in_array($action, ['guard', 'migrate', 'test'], true)) {
    fwrite(STDERR, "Használat: php scripts/backend-test-environment.php <sqlite|mysql> <guard|migrate|test> [teszt argumentumok]\n");
    exit(64);
}

$environment = [
    'APP_ENV' => 'testing',
    'APP_DEBUG' => 'false',
    'APP_KEY' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
    'APP_TIMEZONE' => 'UTC',
    'TZ' => 'UTC',
    'DB_URL' => '',
    'DB_FOREIGN_KEYS' => 'true',
    'DB_TIMEZONE' => '+00:00',
    'DB_CHARSET' => 'utf8mb4',
    'DB_COLLATION' => 'utf8mb4_unicode_ci',
    'CACHE_STORE' => 'array',
    'SESSION_DRIVER' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'QUEUE_FAILED_DRIVER' => 'null',
    'MAIL_MAILER' => 'array',
    'FILESYSTEM_DISK' => 'testing',
    'BROADCAST_CONNECTION' => 'log',
    'LOG_CHANNEL' => 'stderr',
    'PULSE_ENABLED' => 'false',
    'TELESCOPE_ENABLED' => 'false',
    'NIGHTWATCH_ENABLED' => 'false',
];

if ($engine === 'sqlite') {
    $environment += [
        'DB_CONNECTION' => 'sqlite',
        'DB_DATABASE' => ':memory:',
    ];
} else {
    $environment += [
        'DB_CONNECTION' => 'mysql',
        'DB_HOST' => testEnvironmentValue('TEST_MYSQL_HOST', '127.0.0.1'),
        'DB_PORT' => testEnvironmentValue('TEST_MYSQL_PORT', '33060'),
        'DB_DATABASE' => testEnvironmentValue('TEST_MYSQL_DATABASE', 'km_production_testing'),
        'DB_USERNAME' => testEnvironmentValue('TEST_MYSQL_USERNAME', 'km_testing'),
        'DB_PASSWORD' => testEnvironmentValue('TEST_MYSQL_PASSWORD', 'testing_only'),
    ];
}

foreach ($environment as $name => $value) {
    putenv("{$name}={$value}");
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
}

if ($action === 'test') {
    $pestArguments = ['pest', '--configuration=phpunit.xml', ...$additionalArguments];
    $argv = $pestArguments;
    $_SERVER['argv'] = $pestArguments;
    $_SERVER['argc'] = count($pestArguments);

    require dirname(__DIR__).'/vendor/pestphp/pest/bin/pest';
}

$artisanArguments = match ($action) {
    'guard' => ['artisan', 'testing:environment-check', '--no-interaction'],
    'migrate' => ['artisan', 'testing:database-round-trip', '--no-interaction'],
};

$argv = $artisanArguments;
$_SERVER['argv'] = $artisanArguments;
$_SERVER['argc'] = count($artisanArguments);

require dirname(__DIR__).'/artisan';

function testEnvironmentValue(string $name, string $default): string
{
    $value = getenv($name);

    return is_string($value) && $value !== '' ? $value : $default;
}
