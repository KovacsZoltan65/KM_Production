<?php

namespace App\Enums;

/** Internal service outcomes, independent of HTTP status mapping. */
enum MerlinAdmissionOutcome: string
{
    case Created = 'CREATED';
    case Replayed = 'REPLAYED';
    case Conflict = 'CONFLICT';
    case ActiveRunExists = 'ACTIVE_RUN_EXISTS';
    case Denied = 'DENIED';
    case CaseNotEligible = 'CASE_NOT_ELIGIBLE';
}
