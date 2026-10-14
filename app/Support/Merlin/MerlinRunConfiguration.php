<?php

namespace App\Support\Merlin;

/** Backend-owned V1 limits; unavailable cost accounting can never enable a paid run. */
final readonly class MerlinRunConfiguration
{
    public const SCHEMA_VERSION = 1;

    /** @return array{max_model_turns: int, max_tool_call_attempts: int, max_running_seconds: int, cost_ceiling: array{state: string}, paid_execution_enabled: bool} */
    public function toArray(): array
    {
        return [
            'max_model_turns' => 8,
            'max_tool_call_attempts' => 4,
            'max_running_seconds' => 180,
            'cost_ceiling' => ['state' => 'NOT_CONFIGURED'],
            'paid_execution_enabled' => false,
        ];
    }

    /** @return array{configuration_schema_version: int, configuration: array<string, mixed>} */
    public function persistenceAttributes(): array
    {
        return ['configuration_schema_version' => self::SCHEMA_VERSION, 'configuration' => $this->toArray()];
    }
}
