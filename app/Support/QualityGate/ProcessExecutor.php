<?php

declare(strict_types=1);

namespace App\Support\QualityGate;

interface ProcessExecutor
{
    public function execute(GateCommand $command, int $timeout): ProcessResult;
}
