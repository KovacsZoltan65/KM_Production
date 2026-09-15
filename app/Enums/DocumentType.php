<?php

namespace App\Enums;

/**
 * A gyártási és beszerzési folyamatokhoz kapcsolódó dokumentumok
 * üzleti típusait határozza meg.
 *
 * A dokumentumtípus segítségével megkülönböztethető például egy műszaki rajz,
 * műveleti leírás, minőségi jelentés vagy beszállítói dokumentum.
 */
enum DocumentType: string
{
    /** Műszaki rajz vagy egyéb grafikus műszaki dokumentáció. */
    case Drawing = 'drawing';

    /** Egy gyártási művelet végrehajtását leíró dokumentum. */
    case OperationDescription = 'operation_description';

    /** A munkavégzéshez vagy annak eredményéhez kapcsolódó feljegyzés. */
    case WorkNote = 'work_note';

    /** Minőség-ellenőrzéshez vagy minőségi eredményhez kapcsolódó jelentés. */
    case QualityReport = 'quality_report';

    /** A folyamathoz, termékhez vagy eseményhez kapcsolódó fénykép. */
    case Photo = 'photo';

    /** Áru átadását vagy átvételét kísérő szállítólevél. */
    case DeliveryNote = 'delivery_note';

    /** Beszállítótól származó vagy beszállítóhoz kapcsolódó dokumentum. */
    case SupplierDocument = 'supplier_document';

    /** Más, külön dokumentumtípusba nem sorolható dokumentum. */
    case Other = 'other';
}