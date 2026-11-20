<?php

declare(strict_types=1);

namespace RmaTransactionBoundaryPoc\Integrity;

use RuntimeException;
use stdClass;

final class StrictJsonTokenizer
{
    public const MAX_RAW_BYTES = 16777216;

    public function parse(string $rawBytes): JsonParseResult
    {
        return JsonParseResult::fromParsing($this, $rawBytes);
    }

    /**
     * @internal Fresh evaluation data, never accepted by a public result constructor.
     *
     * @return array{valid: true, value: mixed}|array{valid: false, code: string, offset: int}
     */
    public function evaluate(string $rawBytes): array
    {
        if (PHP_INT_SIZE !== 8) {
            throw new RuntimeException('TD02 requires 64-bit PHP.');
        }
        if (PHP_VERSION_ID < 80400) {
            throw new RuntimeException('TD02 requires PHP 8.4+.');
        }
        if (strlen($rawBytes) > self::MAX_RAW_BYTES) {
            return ['valid' => false, 'code' => 'MANIFEST_SIZE_EXCEEDED', 'offset' => self::MAX_RAW_BYTES];
        }

        return (new StrictJsonScanState($rawBytes))->run();
    }
}

/** @internal One invocation owns its cursor, frames, graph and resource counters. */
final class StrictJsonScanState
{
    private int $cursor = 0;

    private int $length;

    /** @var list<stdClass> */
    private array $stack = [];

    private bool $complete = false;

    private mixed $root = null;

    private int $elements = 0;

    private int $containers = 0;

    private int $scalars = 0;

    private int $members = 0;

    public function __construct(private readonly string $raw)
    {
        $this->length = strlen($raw);
    }

    public function run(): array
    {
        try {
            while (true) {
                $this->whitespace();
                if ($this->stack === []) {
                    if ($this->complete) {
                        if ($this->cursor !== $this->length) {
                            $this->unexpected();
                        }

                        return ['valid' => true, 'value' => $this->root];
                    }
                    $this->value();

                    continue;
                }
                $frame = $this->stack[count($this->stack) - 1];
                $char = $this->raw[$this->cursor] ?? '';
                if ($frame->state === 'comma') {
                    if ($char === ($frame->object ? '}' : ']')) {
                        $this->close();
                    } elseif ($char === ',') {
                        $this->cursor++;
                        $frame->state = $frame->object ? 'key' : 'value';
                    } else {
                        $this->unexpected();
                    }
                } elseif ($frame->state === 'colon') {
                    if ($char !== ':') {
                        $this->unexpected();
                    }
                    $this->cursor++;
                    $frame->state = 'value';
                } elseif ($frame->object && in_array($frame->state, ['first', 'key'], true)) {
                    if ($char === '}' && $frame->state === 'first') {
                        $this->close();

                        continue;
                    }
                    if ($char !== '"') {
                        $this->unexpected();
                    }
                    $start = $this->cursor;
                    $this->budget($frame->members, 9, $start);
                    $this->budget($this->members, 400013, $start);
                    $key = $this->string(true);
                    $safe = 'k:'.$key;
                    if (isset($frame->seen[$safe])) {
                        $this->fail('JSON_DUPLICATE_KEY', $start);
                    }
                    $frame->seen[$safe] = true;
                    $frame->key = $key;
                    $frame->state = 'colon';
                } else {
                    if (! $frame->object) {
                        if ($char === ']' && $frame->state === 'first') {
                            $this->close();

                            continue;
                        }
                        $this->budget($this->elements, 100000, $this->cursor);
                    }
                    $this->value();
                }
            }
        } catch (StrictJsonInputFailure $failure) {
            return ['valid' => false, 'code' => $failure->reason, 'offset' => $failure->offset];
        }
    }

    private function value(): void
    {
        $start = $this->cursor;
        $char = $this->raw[$start] ?? '';
        if ($char === '{' || $char === '[') {
            if (count($this->stack) === 16) {
                $this->fail('JSON_DEPTH_EXCEEDED', $start);
            }
            $this->budget($this->containers, 100004, $start);
            $this->cursor++;
            $this->stack[] = (object) [
                'object' => $char === '{', 'state' => 'first',
                'value' => $char === '{' ? new stdClass : [],
                'seen' => [], 'members' => 0, 'key' => '',
            ];

            return;
        }
        $this->budget($this->scalars, 400010, $start);
        if ($char === '"') {
            $value = $this->string(false);
        } elseif ($char === '-' || ($char !== '' && $char >= '0' && $char <= '9')) {
            $value = $this->number();
        } else {
            $literal = match ($char) {
                't' => 'true', 'f' => 'false', 'n' => 'null', default => null,
            };
            if ($literal === null) {
                $this->unexpected();
            }
            for ($i = 0, $size = strlen($literal); $i < $size; $i++) {
                if (($this->raw[$this->cursor] ?? '') !== $literal[$i]) {
                    $this->unexpected();
                }
                $this->cursor++;
            }
            if (! $this->delimiter()) {
                $this->unexpected();
            }
            $value = match ($literal) {
                'true' => true, 'false' => false, default => null
            };
        }
        $this->emit($value);
    }

    private function close(): void
    {
        $this->cursor++;
        $frame = array_pop($this->stack);
        $this->emit($frame->value);
    }

    private function emit(mixed $value): void
    {
        if ($this->stack === []) {
            $this->root = $value;
            $this->complete = true;

            return;
        }
        $frame = $this->stack[count($this->stack) - 1];
        if ($frame->object) {
            $frame->value->{$frame->key} = $value;
        } else {
            $frame->value[] = $value;
        }
        $frame->state = 'comma';
    }

    private function string(bool $key): string
    {
        $this->cursor++;
        $decoded = '';
        $size = 0;
        $domain = null;
        while ($this->cursor < $this->length) {
            $start = $this->cursor;
            $byte = ord($this->raw[$this->cursor++]);
            if ($byte === 34) {
                if ($domain !== null) {
                    $this->fail($domain[0], $domain[1]);
                }

                return $decoded;
            }
            if ($byte < 32) {
                $this->fail('JSON_INVALID', $start);
            }
            if ($byte === 92) {
                $escape = $this->raw[$this->cursor++] ?? '';
                if ($escape === 'u') {
                    $point = $this->hexUnit($start);
                    if ($point >= 0xD800 && $point <= 0xDBFF) {
                        if (substr($this->raw, $this->cursor, 2) !== '\u') {
                            $this->fail('ENCODING_INVALID', $start);
                        }
                        $second = $this->cursor;
                        $this->cursor += 2;
                        $low = $this->hexUnit($second);
                        if ($low < 0xDC00 || $low > 0xDFFF) {
                            $this->fail('ENCODING_INVALID', $start);
                        }
                        $point = 0x10000 + (($point - 0xD800) << 10) + $low - 0xDC00;
                    } elseif ($point >= 0xDC00 && $point <= 0xDFFF) {
                        $this->fail('ENCODING_INVALID', $start);
                    }
                    $width = $point <= 0x7F ? 1 : ($point <= 0x7FF ? 2 : ($point <= 0xFFFF ? 3 : 4));
                } else {
                    $point = match ($escape) {
                        '"' => 34, '\\' => 92, '/' => 47, 'b' => 8, 'f' => 12,
                        'n' => 10, 'r' => 13, 't' => 9, default => -1,
                    };
                    if ($point === -1) {
                        $this->fail('JSON_INVALID', $start);
                    }
                    $width = 1;
                }
            } elseif ($byte >= 128) {
                $this->cursor = $start;
                $width = $this->utf8();
                $point = 128;
            } else {
                $point = $byte;
                $width = 1;
            }
            if ($size + $width > ($key ? 20 : 240)) {
                $this->fail('JSON_COMPLEXITY_LIMIT', $start);
            }
            $size += $width;
            if ($point > 127) {
                $domain ??= ['JSON_NON_ASCII', $start];
            } elseif ($key && ($point < 32 || $point === 127)) {
                $domain ??= ['JSON_KEY_INVALID', $start];
            }
            if ($point <= 127) {
                $decoded .= chr($point);
            }
        }
        $this->fail('JSON_INVALID', $this->length);
    }

    private function hexUnit(int $escapeStart): int
    {
        $number = 0;
        for ($i = 0; $i < 4; $i++) {
            $byte = ord($this->raw[$this->cursor] ?? "\0");
            $digit = $byte >= 48 && $byte <= 57 ? $byte - 48
                : ($byte >= 65 && $byte <= 70 ? $byte - 55 : ($byte >= 97 && $byte <= 102 ? $byte - 87 : -1));
            if ($digit < 0) {
                $this->fail('JSON_INVALID', $escapeStart);
            }
            $number = ($number << 4) + $digit;
            $this->cursor++;
        }

        return $number;
    }

    /** Validate one complete UTF-8 sequence before classifying its decoded domain. */
    private function utf8(): int
    {
        $start = $this->cursor;
        $lead = ord($this->raw[$start]);
        $width = $lead >= 0xC2 && $lead <= 0xDF ? 2
            : ($lead >= 0xE0 && $lead <= 0xEF ? 3 : ($lead >= 0xF0 && $lead <= 0xF4 ? 4 : 0));
        if ($width === 0 || $start + $width > $this->length) {
            $this->fail('ENCODING_INVALID', $start);
        }
        for ($i = 1; $i < $width; $i++) {
            $byte = ord($this->raw[$start + $i]);
            if ($byte < 0x80 || $byte > 0xBF
                || ($i === 1 && (($lead === 0xE0 && $byte < 0xA0) || ($lead === 0xED && $byte > 0x9F)
                    || ($lead === 0xF0 && $byte < 0x90) || ($lead === 0xF4 && $byte > 0x8F)))) {
                $this->fail('ENCODING_INVALID', $start);
            }
        }
        $this->cursor += $width;

        return $width;
    }

    private function number(): int
    {
        $start = $this->cursor;
        $forbidden = false;
        if ($this->raw[$this->cursor] === '-') {
            $forbidden = true;
            $this->cursor++;
        }
        $digits = $this->cursor;
        if (! $this->digit()) {
            $this->unexpected();
        }
        if ($this->raw[$this->cursor] === '0') {
            $this->cursor++;
            if ($this->digit()) {
                $this->fail('JSON_INVALID', $this->cursor);
            }
        } else {
            while ($this->digit()) {
                $this->cursor++;
            }
        }
        $integerLength = $this->cursor - $digits;
        if (($this->raw[$this->cursor] ?? '') === '.') {
            $forbidden = true;
            $this->cursor++;
            if (! $this->digit()) {
                $this->unexpected();
            }
            while ($this->digit()) {
                $this->cursor++;
            }
        }
        if (in_array($this->raw[$this->cursor] ?? '', ['e', 'E'], true)) {
            $forbidden = true;
            $this->cursor++;
            if (in_array($this->raw[$this->cursor] ?? '', ['+', '-'], true)) {
                $this->cursor++;
            }
            if (! $this->digit()) {
                $this->unexpected();
            }
            while ($this->digit()) {
                $this->cursor++;
            }
        }
        if (! $this->delimiter()) {
            $this->unexpected();
        }
        if ($forbidden) {
            $this->fail('JSON_NUMBER_INVALID', $start);
        }
        if ($integerLength > 19 || ($integerLength === 19 && strcmp(substr($this->raw, $start, 19), '9223372036854775807') > 0)) {
            $this->fail('JSON_INTEGER_OVERFLOW', $start);
        }

        return (int) substr($this->raw, $start, $integerLength);
    }

    private function digit(): bool
    {
        $char = $this->raw[$this->cursor] ?? '';

        return $char !== '' && $char >= '0' && $char <= '9';
    }

    private function delimiter(): bool
    {
        return $this->cursor === $this->length || str_contains(" \t\r\n,]}", $this->raw[$this->cursor]);
    }

    private function whitespace(): void
    {
        while ($this->cursor < $this->length && str_contains(" \t\r\n", $this->raw[$this->cursor])) {
            $this->cursor++;
        }
    }

    private function budget(int &$counter, int $maximum, int $offset): void
    {
        if ($counter === $maximum) {
            $this->fail('JSON_COMPLEXITY_LIMIT', $offset);
        }
        $counter++;
    }

    private function unexpected(): never
    {
        $start = min($this->cursor, $this->length);
        if ($start < $this->length && ord($this->raw[$start]) >= 128) {
            $this->utf8();
            if (substr($this->raw, $start, 3) === "\xef\xbb\xbf") {
                $this->fail('ENCODING_INVALID', $start);
            }
        }
        $this->fail('JSON_INVALID', $start);
    }

    private function fail(string $code, int $offset): never
    {
        throw new StrictJsonInputFailure($code, $offset);
    }
}

/** @internal Only this input-failure signal is caught; programming exceptions propagate. */
final class StrictJsonInputFailure extends RuntimeException
{
    public function __construct(public readonly string $reason, public readonly int $offset)
    {
        parent::__construct('Restricted JSON input failure.');
    }
}
