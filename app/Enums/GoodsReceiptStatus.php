<?php

namespace App\Enums;

/**
 * Az áruátvétel rögzítési és készletre könyvelési állapotait reprezentálja.
 */
enum GoodsReceiptStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
}
