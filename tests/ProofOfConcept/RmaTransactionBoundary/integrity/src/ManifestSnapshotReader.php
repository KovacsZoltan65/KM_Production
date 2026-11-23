<?php

declare(strict_types=1);

namespace RmaTransactionBoundaryPoc\Integrity;

use InvalidArgumentException;

/** G03 acquisition only. Complete G01/G02-before-read orchestration is separate. */
final class ManifestSnapshotReader
{
    public const MAX_RAW_BYTES = 16777216;

    private const MESSAGES = [
        'CONFIG_INVALID' => 'Invalid explicit local source configuration.',
        'CAPABILITY_UNAVAILABLE' => 'Requested acquisition capability is unavailable.',
        'IO_READ_FAILED' => 'A complete stable acquisition could not be established.',
        'MANIFEST_SIZE_EXCEEDED' => 'Original raw manifest exceeds 16777216 bytes.',
        'PATH_INVALID' => 'Source path violates the approved path policy.',
        'REPARSE_FOUND' => 'A forbidden link was positively identified.',
        'FILE_CHANGED' => 'A file change was established by the source adapter.',
    ];

    public function read(ManifestByteSource $source, string $requestedAssurance): ManifestSnapshot
    {
        return ManifestSnapshot::fromReading($this, $source, $requestedAssurance);
    }

    /** @internal Fresh acquisition facts, never accepted as caller-supplied success evidence. */
    public function evaluate(ManifestByteSource $source, string $requestedAssurance): array
    {
        if ($requestedAssurance !== 'CONTENT_ONLY' || PHP_VERSION_ID < 80400 || PHP_INT_SIZE !== 8) {
            return self::failure('CAPABILITY_UNAVAILABLE');
        }
        $opened = false;
        $failure = null;
        $raw = '';
        $total = 0;
        try {
            $source->open();
            $opened = true;
            $expected = $source->expectedLength();
            if ($expected !== null && $expected < 0) {
                throw new ManifestAcquisitionFailure('CONFIG_INVALID');
            }
            $before = self::observation($source->stat());
            while (true) {
                $remaining = self::MAX_RAW_BYTES + 1 - $total;
                if ($remaining <= 0) {
                    throw new ManifestAcquisitionFailure('IO_READ_FAILED');
                }
                $requested = min(65536, $remaining);
                $chunk = $source->read($requested);
                if ($chunk === false || strlen($chunk) > $requested) {
                    throw new ManifestAcquisitionFailure('IO_READ_FAILED');
                }
                $length = strlen($chunk);
                $total += $length;
                if ($total > self::MAX_RAW_BYTES) {
                    throw new ManifestAcquisitionFailure('MANIFEST_SIZE_EXCEEDED');
                }
                $raw .= $chunk;
                if ($source->eof()) {
                    if ($expected !== null && $total !== $expected) {
                        throw new ManifestAcquisitionFailure('IO_READ_FAILED');
                    }
                    break;
                }
                if ($length === 0) {
                    throw new ManifestAcquisitionFailure('IO_READ_FAILED');
                }
            }
            if (self::observation($source->stat()) !== $before) {
                // TD05-D01=A: advisory metadata drift is not native FILE_CHANGED proof.
                throw new ManifestAcquisitionFailure('IO_READ_FAILED');
            }
        } catch (ManifestAcquisitionFailure $error) {
            $failure = $error->reason;
        } finally {
            if ($opened) {
                $closed = $source->close();
                if (! $closed && $failure === null) {
                    $failure = 'IO_READ_FAILED';
                }
            }
        }
        if ($failure !== null) {
            return self::failure($failure);
        }

        return ['acquired' => true, 'rawBytes' => $raw, 'byteCount' => $total, 'errorCode' => null, 'verdict' => null, 'errors' => []];
    }

    private static function observation(array|false $stat): array
    {
        if ($stat === false || ! isset($stat['size'], $stat['mtime'], $stat['ctime'])
            || ! is_int($stat['size']) || $stat['size'] < 0 || ! is_int($stat['mtime']) || ! is_int($stat['ctime'])) {
            throw new ManifestAcquisitionFailure('IO_READ_FAILED');
        }

        return ['size' => $stat['size'], 'mtime' => $stat['mtime'], 'ctime' => $stat['ctime']];
    }

    private static function failure(string $code): array
    {
        if (! isset(self::MESSAGES[$code])) {
            throw new InvalidArgumentException('Unsupported acquisition failure code.');
        }

        return [
            'acquired' => false, 'rawBytes' => null, 'byteCount' => null, 'errorCode' => $code,
            'verdict' => in_array($code, ['CAPABILITY_UNAVAILABLE', 'IO_READ_FAILED'], true) ? 'INCONCLUSIVE' : 'DENIED',
            'errors' => [['stage' => 'G03', 'path' => '$', 'code' => $code, 'message' => self::MESSAGES[$code]]],
        ];
    }
}
