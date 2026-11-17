<?php

declare(strict_types=1);

namespace RmaTransactionBoundaryPoc\Integrity;

use InvalidArgumentException;

final readonly class ValidationResult
{
    /** @param list<array{code: string, path: string, message: string}> $errors */
    private function __construct(
        public bool $valid,
        public array $errors,
        public ?Manifest $manifest,
        public bool $diagnosticsTruncated = false,
    ) {}

    public static function success(Manifest $manifest): self
    {
        return new self(true, [], $manifest);
    }

    /** @param list<array{code: string, path: string, message: string}> $errors */
    public static function failure(array $errors, bool $diagnosticsTruncated = false): self
    {
        if ($errors === []) {
            throw new InvalidArgumentException('An invalid result requires at least one error.');
        }

        usort($errors, static function (array $left, array $right): int {
            foreach (['path', 'code', 'message'] as $key) {
                $order = strcmp($left[$key], $right[$key]);
                if ($order !== 0) {
                    return $order;
                }
            }

            return 0;
        });

        return new self(false, $errors, null, $diagnosticsTruncated);
    }

    /** @return array{valid: bool, errors: list<array{code: string, path: string, message: string}>, diagnosticsTruncated?: true} */
    public function toArray(): array
    {
        $result = ['valid' => $this->valid, 'errors' => $this->errors];
        if ($this->diagnosticsTruncated) {
            $result['diagnosticsTruncated'] = true;
        }

        return $result;
    }
}
