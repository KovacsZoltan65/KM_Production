<?php

declare(strict_types=1);

namespace RmaTransactionBoundaryPoc\Integrity;

use InvalidArgumentException;

/** Parsing only. The readonly envelope does not freeze a returned stdClass graph. */
final readonly class JsonParseResult
{
    private const MESSAGES = [
        'MANIFEST_SIZE_EXCEEDED' => 'Raw input exceeds 16777216 bytes.',
        'JSON_INVALID' => 'Invalid JSON grammar.',
        'JSON_DUPLICATE_KEY' => 'Duplicate decoded object key.',
        'ENCODING_INVALID' => 'Invalid encoding or surrogate sequence.',
        'JSON_NON_ASCII' => 'Decoded non-ASCII is outside the restricted domain.',
        'JSON_DEPTH_EXCEEDED' => 'Container depth exceeds 16.',
        'JSON_INTEGER_OVERFLOW' => 'Integer exceeds the supported signed maximum.',
        'JSON_NUMBER_INVALID' => 'Numeric form is outside the restricted domain.',
        'JSON_KEY_INVALID' => 'Decoded object key must be printable ASCII.',
        'JSON_COMPLEXITY_LIMIT' => 'Restricted parser resource budget exceeded.',
    ];

    /** @param null|array{code: string, offset: int, message: string} $error */
    private function __construct(public bool $valid, public mixed $value, public ?array $error) {}

    public static function fromParsing(StrictJsonTokenizer $parser, string $rawBytes): self
    {
        $facts = $parser->evaluate($rawBytes);
        if ($facts['valid']) {
            return new self(true, $facts['value'], null);
        }

        return new self(false, null, [
            'code' => $facts['code'], 'offset' => $facts['offset'],
            'message' => self::MESSAGES[$facts['code']],
        ]);
    }

    public function __unserialize(array $data): void
    {
        throw new InvalidArgumentException('JSON parse results cannot be unserialized.');
    }
}
