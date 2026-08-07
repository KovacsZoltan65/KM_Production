<?php

namespace Tests\Support;

use App\Support\QualityGate\GateCommand;
use App\Support\QualityGate\ProcessExecutor;
use App\Support\QualityGate\ProcessResult;

final class FakeQualityGateExecutor implements ProcessExecutor
{
    /** @var list<GateCommand> */
    public array $executed = [];

    public function __construct(private readonly int $exitCode = 0) {}

    public function execute(GateCommand $command, int $timeout): ProcessResult
    {
        $this->executed[] = $command;

        return new ProcessResult($this->exitCode, 0.01);
    }
}
