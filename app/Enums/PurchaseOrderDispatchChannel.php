<?php

namespace App\Enums;

enum PurchaseOrderDispatchChannel: string
{
    case Manual = 'manual';
    case Email = 'email';
    case Other = 'other';
}
