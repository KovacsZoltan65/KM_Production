<?php

namespace App\Enums;

/**
 * A gyártási és kapcsolódó művelettípusok stabil,
 * adatbázisban tárolt azonosítókódjait határozza meg.
 *
 * Az enum értékei az OperationType rekordok üzleti azonosítására szolgálnak,
 * ezért tartósan tárolt adatokban is hivatkozási alapként használhatók.
 * Az értékek módosítása meglévő adatbázis-rekordokat és üzleti
 * hivatkozásokat érinthet.
 */
enum OperationTypeCode: string
{
    /** Darabolási vagy vágási művelet. */
    case CUTTING = 'VÁGÁS';

    /** Hegesztési vagy összeállítási művelet. */
    case WELDING = 'ÖSSZEÁLLÍTÁS';

    /** Csiszolási vagy felületmegmunkálási művelet. */
    case GRINDING = 'CSISZOLÁS';

    /** Festési vagy bevonatképzési művelet. */
    case PAINTING = 'FESTÉS';

    /** Elektromos vezetékezési művelet. */
    case WIRING = 'VEZETÉKEZÉS';

    /** Alkatrészek vagy részegységek összeszerelési művelete. */
    case ASSEMBLY = 'ÖSSZESZERELÉS';

    /** Minőség-ellenőrzési művelet. */
    case QUALITY_CHECK = 'MINŐSÉGELLENŐRZÉS';

    /** Termékek csomagolási művelete. */
    case PACKAGING = 'CSOMAGOLÁS';

    /** Általános gyártási művelet. */
    case PRODUCTION = 'GYÁRTÁS';

    /** Anyagok vagy termékek tárolási művelete. */
    case STORE = 'TÁROLÁS';

    /** Termékek kiszállításához kapcsolódó művelet. */
    case DELIVERY = 'KISZÁLLÍTÁS';
}