<?php

namespace App\Support\CodeGeneration;

use Illuminate\Database\Eloquent\Model;

/**
 * Egy kódérzékeny létrehozás eredménye és opcionális kódcseréje.
 */
final readonly class CodeCreationResult
{
    public function __construct(
        public Model $model,
        public ?string $originalCode = null,
        public ?string $actualCode = null,
    ) {}

    public function codeWasReplaced(): bool
    {
        return $this->originalCode !== null && $this->actualCode !== null;
    }
}
