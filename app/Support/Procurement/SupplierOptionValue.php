<?php

namespace App\Support\Procurement;

use JsonSerializable;

/** Nullable business value: false and zero remain known values. */
final readonly class SupplierOptionValue implements JsonSerializable
{
    private function __construct(
        public string $state,
        public string|int|bool|null $value,
        public ?string $reason,
    ) {}

    public static function known(string|int|bool $value): self
    {
        return new self('KNOWN', $value, null);
    }

    public static function unknown(string $reason): self
    {
        return new self('UNKNOWN', null, $reason);
    }

    public static function notApplicable(string $reason): self
    {
        return new self('NOT_APPLICABLE', null, $reason);
    }

    /** @return array{state: string, value: string|int|bool|null, reason: ?string} */
    public function jsonSerialize(): array
    {
        return ['state' => $this->state, 'value' => $this->value, 'reason' => $this->reason];
    }
}
