<?php

namespace App\Enums;

/**
 * A beszerzési rendelések beszállító felé történő
 * kiküldésének lehetséges csatornáit határozza meg.
 *
 * A csatorna jelzi, hogy a beszerzési rendelést manuálisan,
 * e-mailben vagy más módon juttatták el a beszállítóhoz.
 */
enum PurchaseOrderDispatchChannel: string
{
    /** A beszerzési rendelést manuális folyamat keretében továbbították. */
    case Manual = 'manual';

    /** A beszerzési rendelést e-mailben továbbították. */
    case Email = 'email';

    /** A beszerzési rendelést más, külön nem nevesített csatornán továbbították. */
    case Other = 'other';
}