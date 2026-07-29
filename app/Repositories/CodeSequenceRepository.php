<?php

namespace App\Repositories;

use App\Support\CodeGeneration\CodeDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * A generátor számára hordozható módon olvassa ki az adott prefix kódjait.
 */
final class CodeSequenceRepository
{
    /**
     * @return array<int, string>
     */
    public function values(CodeDefinition $definition, string $prefix, string $separator): array
    {
        /** @var Builder<Model> $query */
        $query = $definition->modelClass::query();

        if ($definition->usesSoftDeletes) {
            $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        return $query
            ->where($definition->column, 'like', $prefix.$separator.'%')
            ->pluck($definition->column)
            ->map(static fn (mixed $value): string => (string) $value)
            ->all();
    }
}
