<?php

namespace App\Enums;

enum DocumentProcessingStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case ReviewRequired = 'review_required';
}
