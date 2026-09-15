<?php

namespace App\Enums;

/**
 * A beszerzési igény végrehajtási készenlétének vizsgálata során
 * használható stabil, géppel feldolgozható okkódokat határozza meg.
 *
 * Az okkódok olyan blokkoló hibákat és figyelmeztetéseket azonosítanak,
 * amelyek befolyásolhatják a beszerzési igény további végrehajtását.
 * Az enum értékei külső vagy tartós hivatkozásokban is használható
 * stabil azonosítók.
 */
enum PurchaseRequisitionExecutionReadinessReason: string
{
    /** A beszerzési igényt még nem hagyták jóvá. */
    case PrNotApproved = 'PR_NOT_APPROVED';

    /** A beszerzési igényhez nincs beszállító meghatározva. */
    case SupplierMissing = 'SUPPLIER_MISSING';

    /** A meghatározott beszállító nem aktív. */
    case SupplierInactive = 'SUPPLIER_INACTIVE';

    /** A beszerzési igény nem tartalmaz feldolgozható tételeket. */
    case ItemsMissing = 'ITEMS_MISSING';

    /** A beszerzési igény egyik érintett cikke nem aktív. */
    case ItemInactive = 'ITEM_INACTIVE';

    /** A cikkhez tartozó mértékegység nem megfelelő a végrehajtáshoz. */
    case ItemUnitInvalid = 'ITEM_UNIT_INVALID';

    /** A cikk és a beszállító közötti beszerzési forrás nem érvényes. */
    case ItemSupplierInvalid = 'ITEM_SUPPLIER_INVALID';

    /** A cikkhez több, egyértelműen nem feloldható beszerzési forrás tartozik. */
    case ItemSupplierAmbiguous = 'ITEM_SUPPLIER_AMBIGUOUS';

    /** Az utánpótlási számítás még nem történt meg. */
    case ReplenishmentNotCalculated = 'REPLENISHMENT_NOT_CALCULATED';

    /** A korábbi utánpótlási számítás már nem tekinthető aktuálisnak. */
    case ReplenishmentStale = 'REPLENISHMENT_STALE';

    /** A mennyiségekre vonatkozó üzleti invariánsok valamelyike nem teljesül. */
    case QuantityInvariantFailed = 'QUANTITY_INVARIANT_FAILED';

    /** A beszerzési javaslat eredetére vagy származási láncára vonatkozó kapcsolat nem megfelelő. */
    case ProposalLineageMismatch = 'PROPOSAL_LINEAGE_MISMATCH';

    /** A szükséges teljesítési dátum nincs meghatározva. */
    case RequiredDateMissing = 'REQUIRED_DATE_MISSING';

    /** A szükséges teljesítési dátum már elmúlt. */
    case RequiredDatePassed = 'REQUIRED_DATE_PASSED';

    /** A várható ellátás később történik meg, mint amikor az anyagra szükség lenne. */
    case ExpectedLateSupply = 'EXPECTED_LATE_SUPPLY';

    /** A végrehajtáshoz szükséges ár nincs meghatározva. */
    case PriceMissing = 'PRICE_MISSING';

    /**
     * Meghatározza az okkód determinisztikus kiértékelési vagy megjelenítési sorrendjét.
     *
     * Az alacsonyabb értékű okok előrébb kerülnek. A számozás közötti
     * hézagok lehetővé teszik új okok későbbi beszúrását a meglévő
     * sorrend megváltoztatása nélkül.
     */
    public function order(): int
    {
        return match ($this) {
            self::PrNotApproved => 10,
            self::SupplierMissing => 20,
            self::SupplierInactive => 30,
            self::ItemsMissing => 40,
            self::ItemInactive => 100,
            self::ItemUnitInvalid => 110,
            self::ItemSupplierInvalid => 120,
            self::ItemSupplierAmbiguous => 130,
            self::ReplenishmentNotCalculated => 140,
            self::ReplenishmentStale => 150,
            self::QuantityInvariantFailed => 160,
            self::ProposalLineageMismatch => 170,
            self::RequiredDateMissing => 300,
            self::RequiredDatePassed => 310,
            self::ExpectedLateSupply => 320,
            self::PriceMissing => 330,
        };
    }
}