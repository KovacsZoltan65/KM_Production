<?php

namespace App\Enums;

/**
 * A dokumentumok automatizált feldolgozásának és emberi felülvizsgálatának állapotait reprezentálja.
 */
enum DocumentProcessingStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case ReviewRequired = 'review_required';
}
