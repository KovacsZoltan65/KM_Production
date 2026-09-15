<?php

namespace App\Enums;

/**
 * A beszerzési rendelések beszállító felé történő
 * kiküldési kísérleteinek eredményállapotait határozza meg.
 *
 * Az állapot jelzi, hogy a kiküldési kísérlet sikeresen
 * befejeződött vagy sikertelen volt.
 */
enum PurchaseOrderDispatchStatus: string
{
    /** A kiküldési kísérlet sikeresen befejeződött. */
    case Succeeded = 'succeeded';

    /** A kiküldési kísérlet sikertelen volt. */
    case Failed = 'failed';
}