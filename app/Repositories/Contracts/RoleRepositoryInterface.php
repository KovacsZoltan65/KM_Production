<?php

namespace App\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Role;

interface RoleRepositoryInterface extends AdminRepositoryInterface
{
    /** @return LengthAwarePaginator<int, Role> */
    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator;
}
