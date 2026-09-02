<?php

namespace App\Enums;

enum SupplierAcknowledgementSource: string
{
    case Email = 'email';
    case Phone = 'phone';
    case Manual = 'manual';
    case Other = 'other';
}
