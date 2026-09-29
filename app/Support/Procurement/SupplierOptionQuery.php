<?php

namespace App\Support\Procurement;

final readonly class SupplierOptionQuery
{
    public function __construct(public ProcurementRequirementInput $requirement) {}
}
