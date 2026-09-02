<?php

namespace App\Support\Procurement;

use InvalidArgumentException;

/** Exact three-decimal procurement quantity backed by integer thousandths. */
final readonly class ProcurementQuantity
{
    private function __construct(private int $thousandths) {}

    public static function from(int|float|string $value): self
    {
        $normalized = \is_float($value)
            ? rtrim(rtrim(sprintf('%.10F', $value), '0'), '.')
            : (string) $value;

        if (preg_match('/\A([+-]?)(\d+)(?:\.(\d*))?\z/', trim($normalized), $matches) !== 1) {
            throw new InvalidArgumentException('Invalid procurement quantity.');
        }

        $fraction = $matches[3] ?? '';
        if (strlen(ltrim($matches[2], '0')) > 15
            || (strlen($fraction) > 3 && trim(substr($fraction, 3), '0') !== '')) {
            throw new InvalidArgumentException('Procurement quantity exceeds decimal(18,3).');
        }

        $scaled = ((int) $matches[2] * 1000)
            + (int) str_pad(substr($fraction, 0, 3), 3, '0');

        return new self($matches[1] === '-' ? -$scaled : $scaled);
    }

    public static function fromThousandths(int $thousandths): self
    {
        return new self($thousandths);
    }

    public function thousandths(): int
    {
        return $this->thousandths;
    }

    public function compare(self $other): int
    {
        return $this->thousandths <=> $other->thousandths;
    }

    public function minus(self $other): self
    {
        return new self($this->thousandths - $other->thousandths);
    }

    public function isPositive(): bool
    {
        return $this->thousandths > 0;
    }

    public function decimal(): string
    {
        $absolute = abs($this->thousandths);
        $sign = $this->thousandths < 0 ? '-' : '';

        return sprintf('%s%d.%03d', $sign, intdiv($absolute, 1000), $absolute % 1000);
    }
}
