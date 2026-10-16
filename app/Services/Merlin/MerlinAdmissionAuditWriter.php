<?php

namespace App\Services\Merlin;

use App\Models\MerlinRun;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Dedicated synchronous persistence; independent of optional activity logging. */
class MerlinAdmissionAuditWriter implements MerlinAdmissionAuditWriterInterface
{
    public function write(string $connection, MerlinRun $run, Carbon $eventAt): void
    {
        DB::connection($connection)->table('merlin_run_admission_audits')->insert([
            'run_id' => $run->id, 'problem_case_id' => $run->problem_case_id,
            'historical_actor_id' => $run->historical_actor_id,
            'starting_evaluation_id' => $run->starting_evaluation_id,
            'event_type' => 'merlin_run.admitted.v1', 'schema_version' => 1,
            'event_at' => $eventAt, 'admission_outcome' => 'CREATED',
            'correlation_id' => (string) Str::uuid(),
        ]);
    }
}
