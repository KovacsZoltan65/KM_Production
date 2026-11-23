<?php

declare(strict_types=1);

namespace RmaTransactionBoundaryPoc\Integrity;

use InvalidArgumentException;

/** Acquisition evidence only; raw content remains unverified and untrusted. */
final readonly class ManifestSnapshot
{
    public string $assurance;

    public string $executionAuthorization;

    public bool $canLaunchPoC;

    /** @param list<array{stage: string, path: string, code: string, message: string}> $errors */
    private function __construct(
        public bool $acquired,
        public ?string $rawBytes,
        public ?int $byteCount,
        public ?string $errorCode,
        public ?string $verdict,
        public array $errors,
    ) {
        $this->assurance = 'CONTENT_ONLY';
        $this->executionAuthorization = 'NOT_EVALUATED';
        $this->canLaunchPoC = false;
    }

    /** Concrete final reader executes acquisition; caller-supplied facts cannot create success. */
    public static function fromReading(ManifestSnapshotReader $reader, ManifestByteSource $source, string $requestedAssurance): self
    {
        return new self(...$reader->evaluate($source, $requestedAssurance));
    }

    public function __unserialize(array $data): void
    {
        throw new InvalidArgumentException('Acquisition results cannot be unserialized.');
    }
}
