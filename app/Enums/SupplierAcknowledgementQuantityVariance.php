<?php

namespace App\Enums;

/**
 * A beszállító által visszaigazolt és a megrendelt mennyiség
 * közötti eltérés lehetséges eredményeit határozza meg.
 *
 * Az eredmény jelzi, hogy a visszaigazolt mennyiség megegyezik-e
 * a megrendelt mennyiséggel, annál kisebb vagy nagyobb, illetve
 * hogy a beszállító a rendeléstétel teljesítését nem vállalta.
 */
enum SupplierAcknowledgementQuantityVariance: string
{
    /** A visszaigazolt mennyiség megegyezik a megrendelt mennyiséggel. */
    case Matched = 'matched';

    /** A visszaigazolt mennyiség kisebb a megrendelt mennyiségnél. */
    case Reduced = 'reduced';

    /** A visszaigazolt mennyiség nagyobb a megrendelt mennyiségnél. */
    case Increased = 'increased';

    /** A beszállító nem vállalta a rendeléstétel teljesítését. */
    case Rejected = 'rejected';
}