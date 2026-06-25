<?php

namespace App\Repositories\Contracts;

use App\Models\Document;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface DocumentRepositoryInterface extends AdminRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator;

    public function findForShow(Document $document): Document;

    /**
     * @return Collection<int, Document>
     */
    public function versionsFor(Document $document): Collection;
}
