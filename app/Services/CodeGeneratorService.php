<?php

namespace App\Services;

use App\Repositories\CodeSequenceRepository;
use App\Support\CodeGeneration\CodeDefinition;
use App\Support\CodeGeneration\CodeDefinitionRegistry;
use App\Support\CodeGeneration\CodePrefixResolver;

/**
 * Következő, még nem lefoglalt üzleti kódjavaslatot állít elő.
 */
final class CodeGeneratorService
{
    public function __construct(
        private readonly CodeDefinitionRegistry $registry,
        private readonly CodePrefixResolver $prefixResolver,
        private readonly CodeSequenceRepository $repository,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function generate(string $type, array $context = []): string
    {
        $definition = $this->registry->resolve($type, $context);
        $prefix = $this->prefixResolver->resolve($definition->prefixKey);
        $separator = (string) config('code_generation.separator', '-');
        $maximum = $this->maximumSequence($definition, $prefix, $separator);
        $length = max(1, (int) config('code_generation.sequence_length', 4));

        return $prefix.$separator.str_pad((string) ($maximum + 1), $length, '0', STR_PAD_LEFT);
    }

    private function maximumSequence(CodeDefinition $definition, string $prefix, string $separator): int
    {
        $pattern = '/^'.preg_quote($prefix.$separator, '/').'(\d+)$/u';
        $maximum = 0;

        foreach ($this->repository->values($definition, $prefix, $separator) as $value) {
            if (preg_match($pattern, $value, $matches) === 1) {
                $maximum = max($maximum, (int) $matches[1]);
            }
        }

        return $maximum;
    }
}
