<?php

namespace App\Repositories\Admin;

use App\Repositories\Contracts\AdminRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class AbstractAdminRepository implements AdminRepositoryInterface
{
    /**
     * @var class-string<Model>
     */
    protected string $modelClass;

    /**
     * @var array<int, string>
     */
    protected array $searchable = [];

    /**
     * @var array<int, string>
     */
    protected array $sortable = ['id', 'created_at'];

    /**
     * @var array<int, string>
     */
    protected array $with = [];

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        $query = $this->query();
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '' && $this->searchable !== []) {
            $query->where(function (Builder $query) use ($search): void {
                foreach ($this->searchable as $column) {
                    $query->orWhere($column, 'like', "%{$search}%");
                }
            });
        }

        $sort = in_array($filters['sort'] ?? null, $this->sortable, true)
            ? (string) $filters['sort']
            : 'id';
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return $query
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Model
    {
        return $this->query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Model $model, array $attributes): Model
    {
        $model->update($attributes);

        return $model->refresh();
    }

    public function delete(Model $model): void
    {
        $model->delete();
    }

    /**
     * @return Builder<Model>
     */
    protected function query(): Builder
    {
        return $this->modelClass::query()->with($this->with);
    }
}
