<?php

declare(strict_types=1);

namespace App\Support\QualityGate;

final readonly class GateCommand
{
    /**
     * @param  list<string>  $arguments
     */
    public function __construct(
        public string $id,
        public string $label,
        public array $arguments,
        public string $timeoutGroup = 'default',
        public ?int $timeout = null,
    ) {}

    public function display(): string
    {
        return implode(' ', array_map(self::quote(...), $this->arguments));
    }

    private static function quote(string $argument): string
    {
        if ($argument !== '' && preg_match('/^[A-Za-z0-9_\.\/:\\=-]+$/', $argument) === 1) {
            return $argument;
        }

        return '"'.str_replace('"', '\\"', $argument).'"';
    }
}
