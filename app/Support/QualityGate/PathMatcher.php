<?php

declare(strict_types=1);

namespace App\Support\QualityGate;

final class PathMatcher
{
    public static function matches(string $path, string $pattern): bool
    {
        $path = self::normalize($path);
        $pattern = self::normalize($pattern);
        $quoted = preg_quote($pattern, '#');
        $quoted = str_replace('\\*\\*', '.*', $quoted);
        $quoted = str_replace('\\*', '[^/]*', $quoted);
        $quoted = str_replace('\\?', '[^/]', $quoted);

        return preg_match('#^'.$quoted.'$#i', $path) === 1;
    }

    public static function normalize(string $path): string
    {
        $normalized = str_replace('\\', '/', trim($path));

        while (str_starts_with($normalized, './')) {
            $normalized = substr($normalized, 2);
        }

        return ltrim($normalized, '/');
    }
}
