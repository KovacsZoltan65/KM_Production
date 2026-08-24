<?php

namespace App\Enums;

/** Stable machine-readable blocker and warning codes for PR execution readiness. */
enum PurchaseRequisitionExecutionReadinessReason: string
{
    case PrNotApproved = 'PR_NOT_APPROVED';
    case SupplierMissing = 'SUPPLIER_MISSING';
    case SupplierInactive = 'SUPPLIER_INACTIVE';
    case ItemsMissing = 'ITEMS_MISSING';
    case ItemInactive = 'ITEM_INACTIVE';
    case ItemUnitInvalid = 'ITEM_UNIT_INVALID';
    case ItemSupplierInvalid = 'ITEM_SUPPLIER_INVALID';
    case ItemSupplierAmbiguous = 'ITEM_SUPPLIER_AMBIGUOUS';
    case ReplenishmentNotCalculated = 'REPLENISHMENT_NOT_CALCULATED';
    case ReplenishmentStale = 'REPLENISHMENT_STALE';
    case QuantityInvariantFailed = 'QUANTITY_INVARIANT_FAILED';
    case ProposalLineageMismatch = 'PROPOSAL_LINEAGE_MISMATCH';
    case RequiredDateMissing = 'REQUIRED_DATE_MISSING';
    case RequiredDatePassed = 'REQUIRED_DATE_PASSED';
    case ExpectedLateSupply = 'EXPECTED_LATE_SUPPLY';
    case PriceMissing = 'PRICE_MISSING';

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
