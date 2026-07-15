<?php

namespace App\Enums;

/**
 * A gyártási és beszerzési folyamatokhoz csatolható dokumentumok üzleti típusait reprezentálja.
 */
enum DocumentType: string
{
    case Drawing = 'drawing';
    case OperationDescription = 'operation_description';
    case WorkNote = 'work_note';
    case QualityReport = 'quality_report';
    case Photo = 'photo';
    case DeliveryNote = 'delivery_note';
    case SupplierDocument = 'supplier_document';
    case Other = 'other';
}
