<?php

namespace App\Support\CodeGeneration;

use Illuminate\Database\Eloquent\Model;

/**
 * Egy támogatott üzleti kódtípus technikai definíciója.
 */
final readonly class CodeDefinition
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public function __construct(
        public string $type,
        public string $modelClass,
        public string $table,
        public string $column,
        public string $prefixKey,
        public bool $usesSoftDeletes,
    ) {}
}
