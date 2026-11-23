<?php

declare(strict_types=1);

namespace RmaTransactionBoundaryPoc\Integrity;

use RuntimeException;

/** Trusted adapter/test boundary; an implementation is not provenance evidence. */
interface ManifestByteSource
{
    public function open(): void;

    public function expectedLength(): ?int;

    public function read(int $maximumBytes): string|false;

    public function eof(): bool;

    /** @return array{size: int, mtime: int, ctime: int}|false Advisory observations only. */
    public function stat(): array|false;

    public function close(): bool;
}

/** Local CONTENT_ONLY adapter. PHP path/stat checks are not native identity proof. */
final class LocalManifestByteSource implements ManifestByteSource
{
    /** @var resource|null */
    private mixed $handle = null;

    private bool $attempted = false;

    public function __construct(private readonly string $approvedRoot, private readonly string $sourcePath) {}

    public function open(): void
    {
        if ($this->attempted) {
            throw new ManifestAcquisitionFailure('CONFIG_INVALID');
        }
        $this->attempted = true;
        foreach (['fopen', 'fread', 'feof', 'fstat', 'fclose', 'realpath', 'lstat', 'is_link', 'clearstatcache'] as $function) {
            if (! function_exists($function)) {
                throw new ManifestAcquisitionFailure('CAPABILITY_UNAVAILABLE');
            }
        }
        // Inspect the original file spelling before directory-style normalization.
        if (str_ends_with($this->sourcePath, '/') || str_ends_with($this->sourcePath, '\\')) {
            throw new ManifestAcquisitionFailure('CONFIG_INVALID');
        }
        $root = self::configurationPath($this->approvedRoot);
        $path = self::configurationPath($this->sourcePath);
        if (! self::within($path, $root)) {
            throw new ManifestAcquisitionFailure('CONFIG_INVALID');
        }
        self::inspectComponents($root);
        self::inspectComponents($path);
        $resolvedRoot = self::io(fn () => realpath($root));
        $resolvedPath = self::io(fn () => realpath($path));
        if ($resolvedRoot === false || $resolvedPath === false) {
            throw new ManifestAcquisitionFailure('IO_READ_FAILED');
        }
        if (! self::within(str_replace('\\', '/', $resolvedPath), str_replace('\\', '/', $resolvedRoot))) {
            throw new ManifestAcquisitionFailure('CONFIG_INVALID');
        }
        $rootStat = self::io(fn () => lstat($resolvedRoot));
        $fileStat = self::io(fn () => lstat($resolvedPath));
        if ($rootStat === false || $fileStat === false) {
            throw new ManifestAcquisitionFailure('IO_READ_FAILED');
        }
        if (($rootStat['mode'] & 0170000) !== 0040000 || ($fileStat['mode'] & 0170000) !== 0100000) {
            throw new ManifestAcquisitionFailure('CONFIG_INVALID');
        }
        $handle = self::io(fn () => fopen($resolvedPath, 'rb'));
        if ($handle === false) {
            throw new ManifestAcquisitionFailure('IO_READ_FAILED');
        }
        $this->handle = $handle;
        $ready = false;
        try {
            $openedStat = self::io(fn () => fstat($this->handle));
            if ($openedStat === false) {
                throw new ManifestAcquisitionFailure('IO_READ_FAILED');
            }
            if (($openedStat['mode'] & 0170000) !== 0100000) {
                throw new ManifestAcquisitionFailure('CONFIG_INVALID');
            }
            $ready = true;
        } finally {
            if (! $ready) {
                $this->close();
            }
        }
    }

    public function expectedLength(): ?int
    {
        // fstat size is advisory, not a declared transport length.
        return null;
    }

    public function read(int $maximumBytes): string|false
    {
        return self::io(fn () => fread($this->handle, $maximumBytes));
    }

    public function eof(): bool
    {
        return feof($this->handle);
    }

    public function stat(): array|false
    {
        $stat = self::io(fn () => fstat($this->handle));

        return $stat === false ? false : ['size' => $stat['size'], 'mtime' => $stat['mtime'], 'ctime' => $stat['ctime']];
    }

    public function close(): bool
    {
        if (! is_resource($this->handle)) {
            return true;
        }
        $closed = self::io(fn () => fclose($this->handle));
        if ($closed) {
            $this->handle = null;
        }

        return $closed;
    }

    private static function configurationPath(string $path): string
    {
        if ($path === '' || preg_match('/[\x00-\x1f\x7f]/', $path) === 1) {
            throw new ManifestAcquisitionFailure('CONFIG_INVALID');
        }
        $path = str_replace('\\', '/', $path);
        $windows = PHP_OS_FAMILY === 'Windows';
        $absolute = $windows ? preg_match('~\A[A-Za-z]:/~', $path) === 1 : str_starts_with($path, '/');
        $tail = $windows ? substr($path, 3) : substr($path, 1);
        if (! $absolute || str_starts_with($path, '//') || str_contains($tail, ':') || str_contains($tail, '//')) {
            throw new ManifestAcquisitionFailure('CONFIG_INVALID');
        }
        $tail = rtrim($tail, '/');
        foreach ($tail === '' ? [] : explode('/', $tail) as $segment) {
            if ($segment === '.' || $segment === '..' || preg_match('/[. ]\z/', $segment) === 1
                || preg_match('/\A(?:CON|PRN|AUX|NUL|CONIN\$|CONOUT\$|COM[1-9]|LPT[1-9])(?:\.|\z)/i', $segment) === 1) {
                throw new ManifestAcquisitionFailure('CONFIG_INVALID');
            }
        }

        return ($windows ? substr($path, 0, 3) : '/').$tail;
    }

    private static function within(string $path, string $root): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $path = strtr($path, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
            $root = strtr($root, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
        }

        return str_starts_with($path, rtrim($root, '/').'/');
    }

    private static function inspectComponents(string $path): void
    {
        $prefix = PHP_OS_FAMILY === 'Windows' ? substr($path, 0, 3) : '/';
        $tail = substr($path, strlen($prefix));
        foreach ($tail === '' ? [] : explode('/', $tail) as $segment) {
            $prefix = rtrim($prefix, '/').'/'.$segment;
            clearstatcache(true, $prefix);
            $stat = self::io(fn () => lstat($prefix));
            if ($stat === false) {
                throw new ManifestAcquisitionFailure('IO_READ_FAILED');
            }
            if (($stat['mode'] & 0170000) === 0120000 || is_link($prefix)) {
                throw new ManifestAcquisitionFailure('REPARSE_FOUND');
            }
        }
    }

    /** Scope warning suppression to this checked I/O call; restore the caller's handler. */
    private static function io(callable $operation): mixed
    {
        set_error_handler(static fn (): bool => true);
        try {
            return $operation();
        } finally {
            restore_error_handler();
        }
    }
}

/** @internal Only explicit acquisition failures are converted to result data. */
final class ManifestAcquisitionFailure extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct('Manifest acquisition failed.');
    }
}
