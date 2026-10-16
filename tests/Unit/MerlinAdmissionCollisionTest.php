<?php

use App\Support\Merlin\MerlinAdmissionUniqueCollision;
use Illuminate\Database\QueryException;

it('reconciles only the exact admission unique constraints and never arbitrary integrity failures', function (int $code, string $message, string $table, bool $expected): void {
    $previous = new PDOException('Constraint test');
    $previous->errorInfo = ['23000', $code, $message];
    $exception = new QueryException('test_connection', 'insert into "'.$table.'" ("id") values (?)', ['uuid'], $previous);
    expect(MerlinAdmissionUniqueCollision::matches($exception))->toBe($expected);
})->with([
    [1062, "Duplicate entry '1' for key 'merlin_runs.merlin_runs_active_unique'", 'merlin_runs', true],
    [1062, "Duplicate entry '1' for key 'merlin_runs_idempotency_unique'", 'merlin_runs', true],
    [19, 'UNIQUE constraint failed: merlin_runs.problem_case_id, merlin_runs.active_marker', 'merlin_runs', true],
    [2067, 'UNIQUE constraint failed: merlin_runs.historical_actor_id, merlin_runs.problem_case_id, merlin_runs.idempotency_key', 'merlin_runs', true],
    [1062, "Duplicate entry '1' for key 'merlin_runs.PRIMARY'", 'merlin_runs', false],
    [1452, 'Cannot add or update a child row: a foreign key constraint fails', 'merlin_runs', false],
    [19, 'FOREIGN KEY constraint failed', 'merlin_runs', false],
    [19, 'CHECK constraint failed: merlin_runs_status_check', 'merlin_runs', false],
    [3819, "Check constraint 'merlin_runs_status_check' is violated.", 'merlin_runs', false],
    [19, 'UNIQUE constraint failed: merlin_run_admission_audits.run_id', 'merlin_run_admission_audits', false],
    [1213, 'Deadlock found when trying to get lock', 'merlin_runs', false],
    [1205, 'Lock wait timeout exceeded', 'merlin_runs', false],
]);
