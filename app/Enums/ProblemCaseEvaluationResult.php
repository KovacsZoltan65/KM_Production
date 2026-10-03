<?php

namespace App\Enums;

enum ProblemCaseEvaluationResult: string
{
    case Active = 'active';
    case Resolved = 'resolved';
    case Undetermined = 'undetermined';
}
