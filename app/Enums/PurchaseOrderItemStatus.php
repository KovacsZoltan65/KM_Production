<?php

namespace App\Enums;

/**
 * A beszerzési rendeléstételek mennyiségi beérkezési
 * életciklusának állapotait határozza meg.
 *
 * Az állapot jelzi, hogy a rendeléstételből még nem történt beérkezés,
 * részleges mennyiség érkezett be, a megrendelt mennyiség beérkezett,
 * vagy a rendeléstételt megszakították.
 */
enum PurchaseOrderItemStatus: string
{
    /** A rendeléstétel megrendelt állapotban van, beérkezése még nem teljesült. */
    case Ordered = 'ordered';

    /** A megrendelt mennyiségnek csak egy része érkezett be. */
    case PartiallyReceived = 'partially_received';

    /** A rendeléstétel megrendelt mennyisége beérkezett. */
    case Received = 'received';

    /** A rendeléstételt megszakították, ezért további beérkezése nem szükséges. */
    case Cancelled = 'cancelled';
}