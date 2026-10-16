<?php

namespace App\Services\Merlin;

use App\Models\MerlinRun;
use Illuminate\Support\Carbon;

interface MerlinAdmissionAuditWriterInterface
{
    public function write(string $connection, MerlinRun $run, Carbon $eventAt): void;
}
