<?php

namespace App\Enums;

/** Persisted values supported by the initial RMA schema. */
enum RepairExecutionStatus: string
{
    case AwaitingReceipt = 'awaiting_receipt';
}
