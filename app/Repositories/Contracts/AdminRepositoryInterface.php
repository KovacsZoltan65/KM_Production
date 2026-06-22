<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

interface AdminRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Model;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Model $model, array $attributes): Model;

    public function delete(Model $model): void;
}
