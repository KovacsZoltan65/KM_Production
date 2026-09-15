<?php

namespace App\Enums;

/**
 * A beszállító által visszaigazolt szállítási dátum és a beszerzői
 * kiinduló dátum közötti eltérés lehetséges eredményeit határozza meg.
 *
 * Az eredmény jelzi, hogy a visszaigazolt dátum megegyezik-e a kiinduló
 * dátummal, annál korábbi vagy későbbi, illetve hogy az összehasonlítás
 * valamely szükséges feltétel hiánya miatt nem végezhető el.
 */
enum SupplierAcknowledgementDeliveryDateVariance: string
{
    /** A beszállító által visszaigazolt dátum megegyezik a kiinduló dátummal. */
    case Matched = 'matched';

    /** A beszállító a kiinduló dátumnál korábbi szállítási dátumot igazolt vissza. */
    case Earlier = 'earlier';

    /** A beszállító a kiinduló dátumnál későbbi szállítási dátumot igazolt vissza. */
    case Later = 'later';

    /** A beszállító nem igazolt vissza szállítási dátumot. */
    case NotConfirmed = 'not_confirmed';

    /** Az összehasonlításhoz nem áll rendelkezésre beszerzői kiinduló dátum. */
    case NoBuyerBaseline = 'no_buyer_baseline';

    /** A beszállító a szállítást elutasította. */
    case Rejected = 'rejected';
}