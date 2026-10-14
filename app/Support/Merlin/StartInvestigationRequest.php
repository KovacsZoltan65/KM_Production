<?php

namespace App\Support\Merlin;

use Illuminate\Support\Str;
use InvalidArgumentException;

/** Pure client request identity; never actor binding or authorization. */
final readonly class StartInvestigationRequest
{
    public const SCHEMA_VERSION = 1;

    public const FINGERPRINT_VERSION = 1;

    private function __construct(
        public string $problemCaseId,
        public string $idempotencyKey,
    ) {}

    /** @param array<string, mixed> $input */
    public static function fromArray(array $input): self
    {
        if (count($input) !== 2 || ! array_key_exists('problem_case_id', $input)
            || ! array_key_exists('idempotency_key', $input)
            || ! Str::isUuid($input['problem_case_id']) || ! Str::isUuid($input['idempotency_key'])) {
            throw new InvalidArgumentException('Invalid investigation start request.');
        }

        return new self(strtolower($input['problem_case_id']), strtolower($input['idempotency_key']));
    }

    public function canonicalRequest(): string
    {
        return json_encode([
            'schema_version' => self::SCHEMA_VERSION,
            'problem_case_id' => $this->problemCaseId,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    public function fingerprint(): string
    {
        return hash('sha256', $this->canonicalRequest());
    }

    /** @return array{problem_case_id: string, idempotency_key: string, request_schema_version: int, canonical_request: string, fingerprint_version: int, request_fingerprint: string} */
    public function persistenceAttributes(): array
    {
        return [
            'problem_case_id' => $this->problemCaseId,
            'idempotency_key' => $this->idempotencyKey,
            'request_schema_version' => self::SCHEMA_VERSION,
            'canonical_request' => $this->canonicalRequest(),
            'fingerprint_version' => self::FINGERPRINT_VERSION,
            'request_fingerprint' => $this->fingerprint(),
        ];
    }
}
