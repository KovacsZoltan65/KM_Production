<?php

namespace App\Enums;

enum PurchaseOrderDispatchStatus: string
{
    case Succeeded = 'succeeded';
    case Failed = 'failed';
}
