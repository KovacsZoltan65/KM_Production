<?php

declare(strict_types=1);

namespace App\Support\QualityGate;

final readonly class ProcessResult
{
    public function __construct(
        public int $exitCode,
        public float $durationSeconds,
        public bool $timedOut = false,
        public ?int $processId = null,
    ) {}

    public function successful(): bool
    {
        return $this->exitCode === 0 && ! $this->timedOut;
    }
}
