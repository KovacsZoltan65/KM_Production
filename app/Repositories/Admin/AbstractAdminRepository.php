<?php

namespace App\Repositories\Admin;

use App\Repositories\Contracts\AdminRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Az adminisztrációs listázás és alap perzisztencia közös implementációja.
 *
 * A konkrét repository-k adják meg a modellhez tartozó kereshető, rendezhető
 * és előtöltendő mezőket; üzleti döntéseket ez az osztály nem végez.
 */
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
     * Összeállítja és végrehajtja az adminisztrációs lapozott lekérdezést.
     *
     * A keresést a konkrét repository mezőlistájára, a rendezést pedig az
     * engedélyezett oszlopokra korlátozza.
     *
     * @param  array<string, mixed>  $filters  A keresési és rendezési szűrők.
     * @param  int  $perPage  Az oldalanként visszaadott rekordok száma.
     * @return LengthAwarePaginator<int, covariant Model> A lapozott modellpéldányok.
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

        $sort = \in_array($filters['sort'] ?? null, $this->sortable, true)
            ? (string) $filters['sort']
            : 'id';
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        return $query
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Létrehoz egy modellpéldányt a megadott attribútumokból.
     *
     * @param  array<string, mixed>  $attributes  A perzisztálandó attribútumok.
     * @return Model A létrehozott modell.
     */
    public function create(array $attributes): Model
    {
        return $this->query()->create($attributes);
    }

    /**
     * Frissíti a modellt, majd az adatbázisból újratöltve adja vissza.
     *
     * @param  Model  $model  A módosítandó modell.
     * @param  array<string, mixed>  $attributes  A mentendő attribútumok.
     * @return Model A frissített modell.
     */
    public function update(Model $model, array $attributes): Model
    {
        $model->update($attributes);

        return $model->refresh();
    }

    /**
     * Törli a megadott modellpéldányt.
     *
     * @param  Model  $model  A törlendő modell.
     */
    public function delete(Model $model): void
    {
        $model->delete();
    }

    /**
     * Létrehozza a konkrét modell alaplekérdezését az előtöltésekkel.
     *
     * @return Builder<Model> Az Eloquent alaplekérdezés.
     */
    protected function query(): Builder
    {
        return $this->modelClass::query()->with($this->with);
    }
}
