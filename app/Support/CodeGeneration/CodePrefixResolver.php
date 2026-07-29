<?php

namespace App\Support\CodeGeneration;

/**
 * A kódprefixet a konfigurációból, majd biztonságos alapértékből oldja fel.
 *
 * A későbbi céges beállítás-provider ezen osztály előtt vagy ebben az
 * osztályban illeszthető be anélkül, hogy a generátort módosítani kellene.
 */
final class CodePrefixResolver
{
    /**
     * @var array<string, string>
     */
    private const FALLBACKS = [
        'factory_unit' => 'FU',
        'employee' => 'EMP',
        'location' => 'LOC',
        'professional_role' => 'ROLE',
        'product' => 'PRD',
        'material' => 'MAT',
        'operation_type' => 'OP',
        'customer' => 'CUST',
        'supplier' => 'SUP',
    ];

    public function resolve(string $prefixKey): string
    {
        $configured = trim((string) config("code_generation.prefixes.{$prefixKey}", ''));

        return $configured !== '' ? $configured : (self::FALLBACKS[$prefixKey] ?? strtoupper($prefixKey));
    }
}
