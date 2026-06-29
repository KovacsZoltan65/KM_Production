<?php

namespace App\Enums;

enum AiProcessingRunStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case ReviewRequired = 'review_required';
}
