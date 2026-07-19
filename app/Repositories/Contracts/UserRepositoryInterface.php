<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface extends AdminRepositoryInterface
{
    /** @return LengthAwarePaginator<int, User> */
    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator;
}
