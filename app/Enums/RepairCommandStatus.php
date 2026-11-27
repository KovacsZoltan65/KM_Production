<?php

namespace App\Enums;

/** Persisted values supported by the initial RMA schema. */
enum RepairCommandStatus: string
{
    case Processing = 'processing';
    case Completed = 'completed';
}
