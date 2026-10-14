<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface extends AdminRepositoryInterface
{
    /** Fresh actor and permission relations; never reuse an AI context's hydrated User. */
    public function findForSupplierOptionsTool(int $userId): ?User;

    /** Current reads on the owned connection, to be called only after the case lock. */
    public function findForMerlinAdmission(int $userId, string $connectionName): ?User;

    /** @return LengthAwarePaginator<int, User> */
    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator;
}
