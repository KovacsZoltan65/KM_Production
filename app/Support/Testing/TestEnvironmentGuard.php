<?php

namespace App\Support\Testing;

use Illuminate\Contracts\Config\Repository;
use RuntimeException;

final class TestEnvironmentGuard
{
    /**
     * @param  array{
     *     environment: mixed,
     *     connection: mixed,
     *     database: mixed,
     *     database_url: mixed,
     *     host: mixed,
     *     username: mixed,
     *     cache: mixed,
     *     session: mixed,
     *     queue: mixed,
     *     mail: mixed,
     *     filesystem: mixed,
     *     broadcast: mixed,
     *     log: mixed
     * }  $configuration
     */
    public static function assertSafe(array $configuration): void
    {
        self::requireValue($configuration, 'environment', ['testing'], 'Az APP_ENV kizárólag testing lehet.');
        self::requireValue($configuration, 'connection', ['sqlite', 'mysql'], 'Csak sqlite vagy mysql tesztkapcsolat engedélyezett.');

        if (self::string($configuration['database_url']) !== '') {
            self::fail('A DB_URL nem használható, mert felülírhatja az ellenőrzött kapcsolatbeállításokat.');
        }

        if ($configuration['connection'] === 'sqlite') {
            self::assertSafeSqliteDatabase(self::string($configuration['database']));
        } else {
            self::assertSafeMysqlConfiguration($configuration);
        }

        self::requireValue($configuration, 'cache', ['array'], 'A teszt cache csak array lehet.');
        self::requireValue($configuration, 'session', ['array'], 'A teszt session driver csak array lehet.');
        self::requireValue($configuration, 'queue', ['sync'], 'A teszt queue kapcsolat csak sync lehet.');
        self::requireValue($configuration, 'mail', ['array'], 'A teszt mailer csak array lehet.');
        self::requireValue($configuration, 'filesystem', ['testing'], 'A teszt filesystem csak a dedikált testing disk lehet.');
        self::requireValue($configuration, 'broadcast', ['null', 'log'], 'A teszt broadcast kapcsolat csak null vagy log lehet.');
        self::requireValue($configuration, 'log', ['stderr', 'null'], 'A teszt log csatorna csak stderr vagy null lehet.');
    }

    public static function assertLaravelConfiguration(Repository $config): void
    {
        $connection = self::string($config->get('database.default'));
        $databaseConfig = $config->get("database.connections.{$connection}", []);

        if (! is_array($databaseConfig)) {
            self::fail('A kiválasztott adatbázis-kapcsolat konfigurációja hiányzik.');
        }

        self::assertSafe([
            'environment' => $config->get('app.env'),
            'connection' => $connection,
            'database' => $databaseConfig['database'] ?? null,
            'database_url' => $databaseConfig['url'] ?? null,
            'host' => $databaseConfig['host'] ?? null,
            'username' => $databaseConfig['username'] ?? null,
            'cache' => $config->get('cache.default'),
            'session' => $config->get('session.driver'),
            'queue' => $config->get('queue.default'),
            'mail' => $config->get('mail.default'),
            'filesystem' => $config->get('filesystems.default'),
            'broadcast' => $config->get('broadcasting.default', 'null'),
            'log' => $config->get('logging.default'),
        ]);
    }

    private static function assertSafeSqliteDatabase(string $database): void
    {
        if ($database === ':memory:') {
            return;
        }

        $normalized = strtolower(str_replace('\\', '/', $database));

        if (str_contains($normalized, '..') || ! str_contains($normalized, '/storage/framework/testing/')) {
            self::fail('Az SQLite csak :memory: vagy a storage/framework/testing alatti dedikált tesztfájl lehet.');
        }
    }

    /**
     * @param  array<string, mixed>  $configuration
     */
    private static function assertSafeMysqlConfiguration(array $configuration): void
    {
        $database = strtolower(self::string($configuration['database']));

        if (preg_match('/^km_production_(?:test|testing)(?:_[a-z0-9]+)?$/', $database) !== 1) {
            self::fail('A MySQL adatbázis neve csak km_production_test vagy km_production_testing tesztváltozat lehet.');
        }

        if (in_array($database, ['km_production', 'production', 'prod', 'live', 'main'], true)) {
            self::fail('Fejlesztői vagy production jellegű MySQL adatbázis használata tilos.');
        }

        self::requireValue($configuration, 'host', ['127.0.0.1', 'localhost', 'mysql'], 'A MySQL teszthost csak loopback vagy a mysql CI-szolgáltatás lehet.');
        self::requireValue($configuration, 'username', ['km_testing'], 'A MySQL teszt kizárólag a dedikált km_testing felhasználót használhatja.');
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @param  list<string>  $allowed
     */
    private static function requireValue(array $configuration, string $key, array $allowed, string $message): void
    {
        if (! in_array(self::string($configuration[$key] ?? null), $allowed, true)) {
            self::fail($message);
        }
    }

    private static function string(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }

    private static function fail(string $reason): never
    {
        throw new RuntimeException("A backend tesztkörnyezet biztonsági ellenőrzése sikertelen: {$reason}");
    }
}
