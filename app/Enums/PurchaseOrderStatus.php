<?php

namespace App\Enums;

/**
 * A beszerzési rendelések rendelési és beérkezési
 * életciklusának állapotait határozza meg.
 *
 * Az állapot jelzi, hogy a beszerzési rendelés még előkészítés alatt áll,
 * megrendelt állapotban van, részben vagy teljesen beérkezett,
 * illetve megszakították.
 */
enum PurchaseOrderStatus: string
{
    /** A beszerzési rendelés még előkészítés vagy módosítás alatt áll. */
    case Draft = 'draft';

    /** A beszerzési rendelés véglegesített, megrendelt állapotban van. */
    case Ordered = 'ordered';

    /** A beszerzési rendelés tételeinek megrendelt mennyisége csak részben érkezett be. */
    case PartiallyReceived = 'partially_received';

    /** A beszerzési rendelés tételeinek megrendelt mennyisége beérkezett. */
    case Received = 'received';

    /** A beszerzési rendelést megszakították, ezért további teljesítése nem szükséges. */
    case Cancelled = 'cancelled';
}