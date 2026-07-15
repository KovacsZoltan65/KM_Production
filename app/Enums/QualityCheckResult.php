<?php

namespace App\Enums;

/**
 * A gyártási minőségellenőrzések üzleti kimeneteit reprezentálja.
 */
enum QualityCheckResult: string
{
    case Accepted = 'accepted';
    case ReworkRequired = 'rework_required';
    case Rejected = 'rejected';
}
