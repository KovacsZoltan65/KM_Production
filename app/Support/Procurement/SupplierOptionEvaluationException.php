<?php

namespace App\Support\Procurement;

use RuntimeException;

/** Integrity/snapshot failures are not missing business information. */
final class SupplierOptionEvaluationException extends RuntimeException {}
