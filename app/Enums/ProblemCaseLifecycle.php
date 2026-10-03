<?php

namespace App\Enums;

enum ProblemCaseLifecycle: string
{
    case Open = 'open';
    case Closed = 'closed';
    case Invalidated = 'invalidated';
}
