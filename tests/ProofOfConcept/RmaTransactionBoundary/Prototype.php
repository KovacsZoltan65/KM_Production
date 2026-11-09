<?php

declare(strict_types=1);

namespace RmaTransactionBoundaryPoc;

use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Database\MySqlConnection;
use PDO;
use RuntimeException;
use Throwable;

require_once dirname(__DIR__, 3).'/vendor/autoload.php';

final class UnknownCommitOutcome extends RuntimeException {}

final class Prototype
{
    /** @var list<array<string, mixed>> */
    public array $trace = [];

    public function __construct(
        private readonly \Closure $factory,
        private readonly \Closure $callerConnections,
    ) {}

    /** @return array<string, mixed> */
    public static function configuration(): array
    {
        $json = getenv('RMA_POC_PRIVATE_CONFIG');
        if (! is_string($json) || $json === '') {
            throw new RuntimeException('Explicit private PoC configuration required');
        }
        $c = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        if (($c['database'] ?? null) !== 'rma_c302_disposable'
            || ($c['host'] ?? null) !== '127.0.0.1'
            || ! is_int($c['port'] ?? null) || $c['port'] < 20000
            || ! is_string($c['runtime_password'] ?? null) || strlen($c['runtime_password']) < 32
            || ($c['isolation_gate'] ?? null) !== 'owned-fresh-server-listener-verified') {
            throw new RuntimeException('PoC isolation configuration refused');
        }

        return $c;
    }

    /** @param array<string, mixed> $c */
    public static function connect(array $c, bool $admin = false, bool $database = true): PDO
    {
        if ($admin && (! isset($c['admin_password']) || strlen($c['admin_password']) < 32)) {
            throw new RuntimeException('Explicit disposable administrator configuration required');
        }
        $dsn = 'mysql:host='.$c['host'].';port='.$c['port'].';charset=utf8mb4';
        if ($database) {
            $dsn .= ';dbname='.$c['database'];
        }

        return new PDO($dsn, $admin ? 'root' : 'poc_actor101',
            $admin ? $c['admin_password'] : $c['runtime_password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
                PDO::ATTR_TIMEOUT => 3,
            ]);
    }

    public static function connection(PDO $pdo): MySqlConnection
    {
        $connection = new MySqlConnection($pdo, 'rma_c302_disposable', '', ['name' => 'isolated-poc']);
        $connection->setTransactionsManager(new DatabaseTransactionsManager);

        return $connection;
    }

    /** @return array<string, mixed> */
    public static function state(MySqlConnection $c): array
    {
        $pdo = $c->getPdo();

        return [
            'connection_id' => (int) $pdo->query('SELECT CONNECTION_ID()')->fetchColumn(),
            'laravel_depth' => $c->transactionLevel(),
            'pdo_active' => $pdo->inTransaction(),
            'autocommit' => (int) $pdo->query('SELECT @@SESSION.autocommit')->fetchColumn(),
        ];
    }

    /** @return array<string, mixed> */
    public static function call(PDO $pdo, string $key, int $amount = 1, int $actor = 101, string $fault = ''): array
    {
        $hash = hash('sha256', "$key:$amount:$actor:$fault");
        $statement = $pdo->prepare('CALL poc_post(?,?,?,?,?)');
        try {
            $statement->execute([$key, $hash, $actor, $amount, $fault]);
            $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
            while ($statement->nextRowset()) {
                // Drain all CALL result sets before another statement or commit.
            }
        } finally {
            $statement->closeCursor();
        }
        if (count($rows) !== 1 || $rows[0]['payload_hash'] !== $hash || $rows[0]['evidence_hash'] !== $hash) {
            throw new RuntimeException('Incomplete canonical result');
        }

        return $rows[0];
    }

    /**
     * One fresh Laravel-owned root per complete command. No routine transaction control.
     *
     * @return array<string, mixed>
     */
    public function execute(string $key, int $amount = 1, string $fault = '', ?\Closure $before = null,
        bool $loseAcknowledgement = false, ?\Closure $retryHook = null, ?\Closure $afterCall = null): array
    {
        foreach (($this->callerConnections)() as $caller) {
            if ($caller->transactionLevel() !== 0 || $caller->getPdo()->inTransaction()) {
                throw new RuntimeException('Unsupported caller transaction');
            }
        }
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $c = ($this->factory)();
            if ($c->transactionLevel() !== 0 || $c->getPdo()->inTransaction()) {
                throw new RuntimeException('Fresh exclusive root connection required');
            }
            $this->trace[] = ['phase' => 'entry', 'attempt' => $attempt] + self::state($c);
            try {
                $result = $c->transaction(function () use ($c, $key, $amount, $fault, $before, $attempt, $afterCall): array {
                    $this->trace[] = ['phase' => 'root', 'attempt' => $attempt] + self::state($c);
                    if ($before !== null) {
                        $before($c, $attempt);
                    }

                    $result = self::call($c->getPdo(), $key, $amount, fault: $fault);
                    if ($afterCall !== null) {
                        $afterCall($c);
                    }

                    return $result;
                }, 1);
                $this->trace[] = ['phase' => 'committed', 'attempt' => $attempt] + self::state($c);
                if ($loseAcknowledgement) {
                    // Deterministic lost-ack simulation, NOT a real network/COMMIT fault.
                    throw new UnknownCommitOutcome('Commit acknowledgement withheld by harness');
                }

                return $result;
            } catch (Throwable $error) {
                $this->trace[] = ['phase' => 'error', 'attempt' => $attempt,
                    'sqlstate' => $error instanceof \PDOException ? $error->getCode() : null] + self::state($c);
                $vendor = $error instanceof \PDOException ? ($error->errorInfo[1] ?? null) : null;
                if (in_array($vendor, [1205, 1213], true) && $attempt < 2) {
                    $c->disconnect();
                    if ($retryHook !== null) {
                        $retryHook();
                    }

                    continue;
                }
                throw $error;
            } finally {
                $c->disconnect();
            }
        }
        throw new RuntimeException('Retry budget exhausted');
    }
}
