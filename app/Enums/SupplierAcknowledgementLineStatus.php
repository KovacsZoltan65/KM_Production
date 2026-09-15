<?php

namespace App\Enums;

/**
 * A beszállítói visszaigazolásban szereplő beszerzési
 * rendeléstételek elfogadási állapotait határozza meg.
 *
 * Az állapot jelzi, hogy a beszállító az adott rendeléstétel
 * teljesítését elfogadta vagy elutasította.
 */
enum SupplierAcknowledgementLineStatus: string
{
    /** A beszállító elfogadta a rendeléstétel teljesítését. */
    case Accepted = 'accepted';

    /** A beszállító elutasította a rendeléstétel teljesítését. */
    case Rejected = 'rejected';
}