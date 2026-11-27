<?php

namespace App\Enums;

/** Persisted values supported by the initial RMA schema. */
enum RepairRequestStatus: string
{
    case Open = 'open';
}
