<?php

namespace App\Enums;

enum OperationTypeCode: string
{
    case CUTTING = 'CUTTING';
    case WELDING = 'WELDING';
    case GRINDING = 'GRINDING';
    case PAINTING = 'PAINTING';
    case WIRING = 'WIRING';
    case ASSEMBLY = 'ASSEMBLY';
    case QUALITY_CHECK = 'QUALITY_CHECK';
    case PACKAGING = 'PACKAGING';
}
