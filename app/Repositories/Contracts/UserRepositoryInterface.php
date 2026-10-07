<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface extends AdminRepositoryInterface
{
    /** Fresh actor and permission relations; never reuse an AI context's hydrated User. */
    public function findForSupplierOptionsTool(int $userId): ?User;

    /** @return LengthAwarePaginator<int, User> */
    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator;
}
