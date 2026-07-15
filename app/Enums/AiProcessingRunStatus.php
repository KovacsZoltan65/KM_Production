<?php

namespace App\Enums;

/**
 * Az AI-feldolgozási futások végrehajtási és felülvizsgálati állapotait reprezentálja.
 */
enum AiProcessingRunStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case ReviewRequired = 'review_required';
}
