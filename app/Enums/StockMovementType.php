<?php

namespace App\Enums;

/**
 * A készlet mennyiségét és nyomon követhetőségét érintő mozgások üzleti okát reprezentálja.
 */
enum StockMovementType: string
{
    case PurchaseReceive = 'purchase_receive';
    case ProductionIssue = 'production_issue';
    case ProductionConsume = 'production_consume';
    case ProductionOutput = 'production_output';
    case Transfer = 'transfer';
    case Scrap = 'scrap';
    case Correction = 'correction';
    case Reservation = 'reservation';
    case ReservationRelease = 'reservation_release';
}
