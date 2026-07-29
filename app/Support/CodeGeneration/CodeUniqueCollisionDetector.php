<?php

namespace App\Support\CodeGeneration;

use Illuminate\Database\QueryException;

/**
 * Kizárólag a célzott üzleti kód unique indexének ütközését ismeri fel.
 */
final class CodeUniqueCollisionDetector
{
    public function isCodeCollision(QueryException $exception, CodeDefinition $definition): bool
    {
        $message = strtolower($exception->getMessage());
        $constraint = strtolower($definition->table.'_'.$definition->column.'_unique');
        $qualifiedColumn = strtolower($definition->table.'.'.$definition->column);
        $isUniqueViolation = str_contains($message, 'duplicate entry')
            || str_contains($message, 'unique constraint failed');

        return $isUniqueViolation
            && (str_contains($message, $constraint) || str_contains($message, $qualifiedColumn));
    }
}
