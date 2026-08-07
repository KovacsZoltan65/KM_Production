<?php

namespace Tests\Support;

use App\Repositories\Contracts\AdminRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use LogicException;

/**
 * Meghatározott adatbázishibát dobó teszt-repository.
 */
final class FailingCodeRepository implements AdminRepositoryInterface
{
    public int $createCalls = 0;

    public function __construct(private readonly QueryException $exception) {}

    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        throw new LogicException('A tesztben nem támogatott művelet.');
    }

    public function create(array $attributes): Model
    {
        $this->createCalls++;

        throw $this->exception;
    }

    public function update(Model $model, array $attributes): Model
    {
        throw new LogicException('A tesztben nem támogatott művelet.');
    }

    public function delete(Model $model): void
    {
        throw new LogicException('A tesztben nem támogatott művelet.');
    }
}
