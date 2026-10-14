<?php

namespace App\Support\Merlin;

use App\Enums\MerlinAdmissionOutcome;
use Illuminate\Support\Str;
use InvalidArgumentException;

/** A future authorized service constructs this; infrastructure does not return admission success. */
final readonly class MerlinAdmissionResult
{
    public function __construct(public MerlinAdmissionOutcome $outcome, public ?string $runId = null)
    {
        $success = in_array($outcome, [MerlinAdmissionOutcome::Created, MerlinAdmissionOutcome::Replayed], true);
        if (($success && ! Str::isUuid($runId)) || (! $success && $runId !== null)) {
            throw new InvalidArgumentException('Run identity is allowed only for authorized admission success.');
        }
    }
}
