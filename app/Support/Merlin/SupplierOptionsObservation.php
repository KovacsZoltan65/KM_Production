<?php

namespace App\Support\Merlin;

use App\Support\Procurement\SupplierOptionResult;

/** Detached data only; no models, relations or database snapshot handle. */
final readonly class SupplierOptionsObservation
{
    public function __construct(
        public string $code,
        public ?string $observedAt = null,
        public ?SupplierOptionResult $result = null,
    ) {}
}
