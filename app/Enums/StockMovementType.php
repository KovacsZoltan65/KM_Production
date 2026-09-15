<?php

namespace App\Enums;

/**
 * A készlet mennyiségét vagy nyomon követhetőségét érintő
 * készletmozgások üzleti típusait határozza meg.
 *
 * A mozgástípus jelzi, hogy a készletváltozás beszerzési bevételezéshez,
 * gyártási kiadáshoz vagy felhasználáshoz, gyártási eredményhez,
 * áthelyezéshez, selejtezéshez, korrekcióhoz vagy foglaláshoz kapcsolódik.
 */
enum StockMovementType: string
{
    /** Beszerzésből beérkezett készlet bevételezése. */
    case PurchaseReceive = 'purchase_receive';

    /** Anyag vagy alkatrész kiadása gyártási felhasználás céljából. */
    case ProductionIssue = 'production_issue';

    /** Anyag vagy alkatrész tényleges gyártási felhasználásának elszámolása. */
    case ProductionConsume = 'production_consume';

    /** Gyártás eredményeként létrejött készlet bevételezése. */
    case ProductionOutput = 'production_output';

    /** Készlet áthelyezése egyik fizikai helyről egy másikra. */
    case Transfer = 'transfer';

    /** Készlet selejtezés miatti kivezetése. */
    case Scrap = 'scrap';

    /** Készletadat korrekciójából származó változás. */
    case Correction = 'correction';

    /** Készletmennyiség lefoglalása meghatározott igény számára. */
    case Reservation = 'reservation';

    /** Korábban létrehozott készletfoglalás feloldása. */
    case ReservationRelease = 'reservation_release';
}