<?php

namespace App\Enums;

/**
 * Az áruátvételek rögzítési és készletre könyvelési
 * állapotait határozza meg.
 *
 * Az állapot jelzi, hogy az áruátvétel még előkészítés alatt áll,
 * vagy már véglegesítették és készletre könyvelték.
 */
enum GoodsReceiptStatus: string
{
    /** Az áruátvétel még előkészítés vagy módosítás alatt áll. */
    case Draft = 'draft';

    /** Az áruátvételt véglegesítették és készletre könyvelték. */
    case Posted = 'posted';
}