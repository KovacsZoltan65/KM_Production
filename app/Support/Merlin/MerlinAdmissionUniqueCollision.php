<?php

namespace App\Support\Merlin;

use Illuminate\Database\QueryException;

/** Only the two admission identity constraints may trigger authorized reconciliation. */
final class MerlinAdmissionUniqueCollision
{
    public static function matches(QueryException $exception): bool
    {
        if (! preg_match('/^insert into [`"]?merlin_runs[`"]?\s/i', $exception->getSql())) {
            return false;
        }
        $code = (int) ($exception->errorInfo[1] ?? 0);
        $message = (string) ($exception->errorInfo[2] ?? '');
        if ($code === 1062) {
            return preg_match("/for key ['`](?:merlin_runs\.)?merlin_runs_(?:active|idempotency)_unique['`]/", $message) === 1;
        }
        if (in_array($code, [19, 2067], true)) {
            return in_array($message, [
                'UNIQUE constraint failed: merlin_runs.problem_case_id, merlin_runs.active_marker',
                'UNIQUE constraint failed: merlin_runs.historical_actor_id, merlin_runs.problem_case_id, merlin_runs.idempotency_key',
            ], true);
        }

        return false;
    }
}
