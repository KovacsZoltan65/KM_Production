<?php

namespace App\Support\Merlin;

use App\Models\User;
use Illuminate\Support\Str;
use InvalidArgumentException;

/** Trusted PHP caller only. Never hydrate this context from tool arguments or AI output. */
final readonly class SupplierOptionsToolContext
{
    public int $userId;

    /** @param list<string> $allowedTools Explicit backend policy, not an AI-supplied list. */
    public function __construct(User $user, public string $problemCaseId, public array $allowedTools)
    {
        if (! $user->exists || $user->id <= 0 || ! Str::isUuid($problemCaseId)) {
            throw new InvalidArgumentException('A persisted backend actor and bound case UUID are required.');
        }
        $this->userId = $user->id;
    }
}
