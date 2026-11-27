<?php

namespace App\Enums;

/** Persisted values supported by the initial RMA schema. */
enum WarrantyClassification: string
{
    case Warranty = 'warranty';
    case NonWarranty = 'non_warranty';
    case PendingAssessment = 'pending_assessment';
}
