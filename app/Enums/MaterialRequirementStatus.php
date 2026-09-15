<?php

namespace App\Enums;

/**
 * A gyártási anyagszükségletek ellátottsági és beszerzési
 * életciklusának állapotait határozza meg.
 *
 * Az állapot jelzi, hogy az anyagszükségletet kiszámították,
 * készletből lefoglalták, csak részben áll rendelkezésre, hiányzik,
 * beszerzés alatt áll, beérkezett vagy megszüntették.
 */
enum MaterialRequirementStatus: string
{
    /** Az anyagszükségletet kiszámították, de ellátása még nem rendezett. */
    case Calculated = 'calculated';

    /** A szükséges mennyiséget rendelkezésre álló készletből lefoglalták. */
    case Reserved = 'reserved';

    /** A szükséges mennyiségnek csak egy része áll rendelkezésre. */
    case PartiallyAvailable = 'partially_available';

    /** A szükséges mennyiség nem biztosítható a rendelkezésre álló készletből. */
    case Missing = 'missing';

    /** A hiányzó mennyiség beszerzése megrendelés alatt áll. */
    case Ordered = 'ordered';

    /** A szükséglet kielégítésére szolgáló megrendelt mennyiség beérkezett. */
    case Received = 'received';

    /** Az anyagszükségletet megszüntették, ezért további ellátása nem szükséges. */
    case Cancelled = 'cancelled';
}