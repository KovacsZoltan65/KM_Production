<?php

namespace App\Models\Concerns;

use LogicException;

trait AppendOnly
{
    public static function bootAppendOnly(): void
    {
        static::updating(static function (): never {
            throw new LogicException('Append-only domain records cannot be updated.');
        });

        static::deleting(static function (): never {
            throw new LogicException('Append-only domain records cannot be deleted.');
        });
    }
}
