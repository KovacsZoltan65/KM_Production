<?php

namespace App\Support\Procurement;

use Closure;
use Illuminate\Support\Facades\DB;

/** No business writes or row locks; all repository reads use the default connection. */
final class SupplierOptionReadSnapshot
{
    /**
     * @template T
     *
     * @param  Closure(): T  $read
     * @return T
     */
    public function evaluate(Closure $read): mixed
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if ($driver === 'mysql') {
            // A caller-owned transaction may have a per-transaction isolation override.
            // Do not infer its isolation from the session default or change it mid-flight.
            if ($connection->transactionLevel() !== 0 || $connection->getPdo()->inTransaction()) {
                throw new SupplierOptionEvaluationException('SUPPLIER_OPTION_CALLER_TRANSACTION_UNVERIFIED');
            }
            $connection->statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ, READ ONLY');
        } elseif ($driver === 'sqlite') {
            $isolation = $connection->selectOne('PRAGMA read_uncommitted', [], false);
            if ($isolation === null || (int) $isolation->read_uncommitted !== 0) {
                throw new SupplierOptionEvaluationException('SUPPLIER_OPTION_SNAPSHOT_UNAVAILABLE');
            }
        } else {
            throw new SupplierOptionEvaluationException('SUPPLIER_OPTION_UNSUPPORTED_DATABASE');
        }

        // MySQL/InnoDB consistent reads and SQLite's read transaction share one snapshot.
        // No retry: on failure the caller must obtain a fresh authoritative input if needed.
        return $connection->transaction($read, 1);
    }
}
