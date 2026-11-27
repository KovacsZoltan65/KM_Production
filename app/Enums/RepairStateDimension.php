<?php

namespace App\Enums;

/** Persisted values supported by the initial RMA schema. */
enum RepairStateDimension: string
{
    case Case = 'case';
    case Claim = 'claim';
    case Warranty = 'warranty';
}
