<?php

namespace App\Support\Merlin;

use App\Repositories\Admin\ProblemCaseRepository;
use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PDO;

/** Internal database boundary only. No authorization, run/history/audit inserts or external calls. */
class MerlinAdmissionTransaction
{
    /**
     * @template T
     *
     * @param  Closure(MerlinAdmissionContext): T  $callback
     * @return T
     */
    public function run(string $problemCaseId, Closure $callback): mixed
    {
        if (! Str::isUuid($problemCaseId)) {
            throw new \InvalidArgumentException('Invalid admission case UUID.');
        }
        $base = DB::connection();
        foreach (DB::getConnections() as $existing) {
            $raw = $existing->getRawPdo();
            if ($existing->transactionLevel() !== 0 || ($raw instanceof PDO && $raw->inTransaction())) {
                throw new MerlinAdmissionInfrastructureException('MERLIN_ADMISSION_CALLER_TRANSACTION');
            }
        }

        $config = $base->getConfig();
        $driver = $base->getDriverName();
        if (! in_array($driver, ['mysql', 'sqlite'], true)) {
            throw new MerlinAdmissionInfrastructureException('MERLIN_ADMISSION_UNSUPPORTED_DATABASE');
        }
        if ($driver === 'sqlite' && ($config['database'] ?? null) === ':memory:') {
            // An independent :memory: PDO would not contain the application's schema/data.
            throw new MerlinAdmissionInfrastructureException('MERLIN_ADMISSION_FILE_BACKED_SQLITE_REQUIRED');
        }
        if ($driver === 'sqlite') {
            $config['transaction_mode'] = 'IMMEDIATE';
        }
        $name = 'merlin_admission_'.Str::uuid();
        $config['name'] = $name;
        $state = null;
        $connection = null;
        $pdo = null;
        try {
            $connection = $this->makeConnection($name, $config);
            $connection->setTransactionManager(new DatabaseTransactionsManager);
            $connection->setReconnector(function (): never {
                throw new MerlinAdmissionInfrastructureException('MERLIN_ADMISSION_RECONNECT_UNSAFE');
            });
            $pdo = $connection->getPdo();
            if ($connection->transactionLevel() !== 0 || $pdo->inTransaction()) {
                throw new MerlinAdmissionInfrastructureException('MERLIN_ADMISSION_CONNECTION_ALREADY_ACTIVE');
            }
            if ($driver === 'mysql') {
                // PDO directly avoids Laravel retrying a lost policy statement on a replacement PDO.
                $pdo->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ, READ WRITE');
            } else {
                $foreignKeys = $pdo->query('PRAGMA foreign_keys')->fetchColumn();
                $readUncommitted = $pdo->query('PRAGMA read_uncommitted')->fetchColumn();
                if ((int) $foreignKeys !== 1 || (int) $readUncommitted !== 0) {
                    throw new MerlinAdmissionInfrastructureException('MERLIN_ADMISSION_SQLITE_POLICY_UNAVAILABLE');
                }
            }

            $state = new MerlinAdmissionTransactionState($connection, $pdo);
            $connection->beforeExecuting(fn () => $state->assertActive());

            return $connection->transaction(function () use ($name, $problemCaseId, $callback, $state): mixed {
                $state->activate();
                // First domain SQL: primary-row lock, with no eager-loaded relations.
                $case = (new ProblemCaseRepository($name))->lockForAdmission(strtolower($problemCaseId));
                $context = new MerlinAdmissionContext($name, $case, $state->assertActive(...));
                $result = $callback($context);
                $state->assertActive();

                return $result;
            }, 1);
        } finally {
            $state?->release();
            try {
                // Also end the original owned PDO if a callback replaced the connection's PDO.
                if ($pdo instanceof PDO && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
            } finally {
                // A dedicated connection never changes the caller's defaults/config or transaction.
                DB::purge($name);
                $pdo = null;
            }
        }
    }

    /** @param array<string, mixed> $config */
    protected function makeConnection(string $name, array $config): Connection
    {
        $connection = DB::connectUsing($name, $config);
        if (! $connection instanceof Connection) {
            throw new MerlinAdmissionInfrastructureException('MERLIN_ADMISSION_UNSUPPORTED_CONNECTION');
        }

        return $connection;
    }
}
