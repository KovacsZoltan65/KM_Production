<?php

namespace App\Repositories\Admin;

use App\Models\Document;
use App\Repositories\Contracts\DocumentRepositoryInterface;
use App\Support\DocumentableRegistry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class DocumentRepository extends AbstractAdminRepository implements DocumentRepositoryInterface
{
    protected string $modelClass = Document::class;

    protected array $sortable = ['id', 'document_type', 'version', 'is_current', 'approved', 'created_at'];

    protected array $with = ['uploader', 'approver'];

    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->query();

        if (($filters['document_type'] ?? null) !== null) {
            $query->where('document_type', $filters['document_type']);
        }

        if (($filters['documentable_type'] ?? null) !== null) {
            $query->where('documentable_type', DocumentableRegistry::classFrom((string) $filters['documentable_type']));
        }

        foreach (['approved', 'is_current'] as $booleanFilter) {
            if (($filters[$booleanFilter] ?? null) !== null && $filters[$booleanFilter] !== '') {
                $query->where($booleanFilter, filter_var($filters[$booleanFilter], FILTER_VALIDATE_BOOL));
            }
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('original_filename', 'like', "%{$search}%")
                    ->orWhere('checksum', 'like', "%{$search}%")
                    ->orWhere('document_type', 'like', "%{$search}%")
                    ->orWhere('documentable_type', 'like', "%{$search}%")
                    ->orWhere('documentable_id', $search)
                    ->orWhereHas('uploader', fn (Builder $userQuery) => $userQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $sort = \in_array($filters['sort'] ?? null, $this->sortable, true) ? (string) $filters['sort'] : 'id';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction)->paginate($perPage)->withQueryString();
    }

    public function findForShow(Document $document): Document
    {
        return $document->load(['uploader', 'approver', 'documentable']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateDocument(Document $document, array $attributes): Document
    {
        $document->update($attributes);

        return $document->refresh();
    }

    public function versionsFor(Document $document): Collection
    {
        return Document::query()
            ->with(['uploader', 'approver'])
            ->where('documentable_type', $document->documentable_type)
            ->where('documentable_id', $document->documentable_id)
            ->where('document_type', $document->document_type->value)
            ->orderByDesc('version')
            ->get();
    }
}
