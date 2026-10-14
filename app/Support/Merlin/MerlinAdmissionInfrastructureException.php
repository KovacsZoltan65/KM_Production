<?php

namespace App\Support\Merlin;

use RuntimeException;

/** Infrastructure failures must not be mapped to business admission outcomes. */
final class MerlinAdmissionInfrastructureException extends RuntimeException {}
