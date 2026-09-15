<?php

namespace App\Enums;

/**
 * Az egyedileg nyomon követett cikkpéldányok gyártási, készlet- és
 * minőségi életciklusának állapotait határozza meg.
 *
 * Az állapot jelzi, hogy a cikkpéldány a tervezéstől és gyártástól
 * kezdve a minőség-ellenőrzésen és készletezésen keresztül milyen
 * aktuális életciklus-állapotban található.
 */
enum ItemInstanceStatus: string
{
    /** A cikkpéldány létrehozása vagy gyártása megtervezett, de még nem kezdődött el. */
    case Planned = 'planned';

    /** A cikkpéldány gyártása folyamatban van. */
    case InProduction = 'in_production';

    /** A cikkpéldány minőség-ellenőrzésre vár. */
    case WaitingForCheck = 'waiting_for_check';

    /** A cikkpéldány minőség-ellenőrzése megtörtént. */
    case Checked = 'checked';

    /** A cikkpéldány a minőség-ellenőrzés során nem felelt meg. */
    case Rejected = 'rejected';

    /** A cikkpéldány készleten van és készletként nyilvántartott. */
    case InStock = 'in_stock';

    /** A cikkpéldányt egy másik termék vagy folyamat felhasználta. */
    case Consumed = 'consumed';

    /** A cikkpéldányt kiszállították. */
    case Shipped = 'shipped';

    /** A cikkpéldányt selejtezték, ezért további felhasználásra nem alkalmas. */
    case Scrapped = 'scrapped';
}