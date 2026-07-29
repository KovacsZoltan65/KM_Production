<?php

namespace App\Services;

use App\Repositories\Contracts\AdminRepositoryInterface;
use App\Support\CodeGeneration\CodeCreationResult;
use App\Support\CodeGeneration\CodeDefinitionRegistry;
use App\Support\CodeGeneration\CodeUniqueCollisionDetector;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * A generált és kézi kódok eltérő mentéskori ütközésfolyamatát kezeli.
 */
final class CodeCreationService
{
    public function __construct(
        private readonly CodeDefinitionRegistry $registry,
        private readonly CodeGeneratorService $generator,
        private readonly CodeUniqueCollisionDetector $collisionDetector,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(
        string $type,
        array $attributes,
        AdminRepositoryInterface $repository,
    ): CodeCreationResult {
        $definition = $this->registry->resolve($type, $attributes);
        $generated = (bool) ($attributes['_code_was_generated'] ?? false);
        unset($attributes['_code_was_generated']);

        $originalCode = (string) $attributes[$definition->column];
        $attemptLimit = max(1, (int) config('code_generation.max_create_attempts', 3));

        for ($attempt = 1; $attempt <= $attemptLimit; $attempt++) {
            try {
                $model = $repository->create($attributes);

                return new CodeCreationResult(
                    $model,
                    $attributes[$definition->column] === $originalCode ? null : $originalCode,
                    $attributes[$definition->column] === $originalCode
                        ? null
                        : (string) $attributes[$definition->column],
                );
            } catch (QueryException $exception) {
                if (! $this->collisionDetector->isCodeCollision($exception, $definition)) {
                    throw $exception;
                }

                $suggestedCode = $this->generator->generate($type, $attributes);

                if (! $generated) {
                    throw ValidationException::withMessages([
                        $definition->column => __('code_generation.errors.manual_collision', [
                            'code' => $originalCode,
                            'suggested' => $suggestedCode,
                        ]),
                        'code_suggestion' => $suggestedCode,
                    ]);
                }

                $attributes[$definition->column] = $suggestedCode;
            }
        }

        Log::warning('A generált üzleti kód mentési retry limitje elfogyott.', [
            'code_type' => $type,
            'attempts' => $attemptLimit,
        ]);

        throw ValidationException::withMessages([
            $definition->column => __('code_generation.errors.retry_exhausted'),
        ]);
    }
}
