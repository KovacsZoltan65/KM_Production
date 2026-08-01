<?php

namespace App\Enums;

/**
 * A gyártási művelettípusok stabil, adatbázisban tárolt azonosítókódjait reprezentálja.
 */
enum OperationTypeCode: string
{
    case CUTTING = 'VÁGÁS';
    case WELDING = 'ÖSSZEÁLLÍTÁS';
    case GRINDING = 'CSISZOLÁS';
    case PAINTING = 'FESTÉS';
    case WIRING = 'VEZETÉKEZÉS';
    case ASSEMBLY = 'ÖSSZESZERELÉS';
    case QUALITY_CHECK = 'MINŐSÉGELLENŐRZÉS';
    case PACKAGING = 'CSOMAGOLÁS';
    case PRODUCTION = 'GYÁRTÁS';
    /*
    case CUTTING = 'CUTTING';
    case WELDING = 'WELDING';
    case GRINDING = 'GRINDING';
    case PAINTING = 'PAINTING';
    case WIRING = 'WIRING';
    case ASSEMBLY = 'ASSEMBLY';
    case QUALITY_CHECK = 'QUALITY_CHECK';
    case PACKAGING = 'PACKAGING';
    */
}
