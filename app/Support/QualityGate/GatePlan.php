<?php

declare(strict_types=1);

namespace App\Support\QualityGate;

final readonly class GatePlan
{
    /**
     * @param  list<string>  $modules
     * @param  list<string>  $changedFiles
     * @param  list<GateCommand>  $commands
     * @param  array<string, list<string>>  $explanations
     */
    public function __construct(
        public string $level,
        public array $modules,
        public array $changedFiles,
        public array $commands,
        public array $explanations = [],
    ) {}
}
