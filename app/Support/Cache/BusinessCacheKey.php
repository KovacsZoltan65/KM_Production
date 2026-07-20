<?php

namespace App\Support\Cache;

use Illuminate\Support\Facades\Cache;

final class BusinessCacheKey
{
    private const string PREFIX = 'km-production';

    /**
     * @param  array<string, mixed>  $parameters
     */
    public static function make(BusinessCacheDomain $domain, string $name, array $parameters = []): string
    {
        $normalized = self::normalize($parameters);
        $suffix = $normalized === []
            ? ''
            : ':'.hash('sha256', json_encode($normalized, JSON_THROW_ON_ERROR));

        return sprintf(
            '%s:%s:g%d:%s%s',
            self::PREFIX,
            $domain->value,
            self::generation($domain),
            $name,
            $suffix,
        );
    }

    public static function generationKey(BusinessCacheDomain $domain): string
    {
        return sprintf('%s:cache-generation:%s', self::PREFIX, $domain->value);
    }

    public static function generation(BusinessCacheDomain $domain): int
    {
        $generation = Cache::get(self::generationKey($domain), 1);

        return is_int($generation) && $generation > 0 ? $generation : 1;
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    private static function normalize(array $parameters): array
    {
        $normalized = array_filter(
            $parameters,
            static fn (mixed $value): bool => $value !== null && $value !== '',
        );
        ksort($normalized);

        return $normalized;
    }
}
