<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

/**
 * Az adminisztrációs adatkezelés közös repository-szerződése.
 */
interface AdminRepositoryInterface
{
    /**
     * Visszaadja a modell szűrt és lapozott adminisztrációs listáját.
     *
     * @param  array<string, mixed>  $filters
     * @param  int  $perPage  Az oldalanként visszaadott rekordok száma.
     * @return LengthAwarePaginator<int, covariant Model> A lapozott modellpéldányok.
     */
    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator;

    /**
     * Létrehoz egy modellt a validált attribútumokból.
     *
     * @param  array<string, mixed>  $attributes  A perzisztálandó attribútumok.
     * @return Model A létrehozott modell.
     */
    public function create(array $attributes): Model;

    /**
     * Frissíti és visszaadja a megadott modellt.
     *
     * @param  Model  $model  A módosítandó modell.
     * @param  array<string, mixed>  $attributes  A mentendő attribútumok.
     * @return Model A frissített modell.
     */
    public function update(Model $model, array $attributes): Model;

    /**
     * Törli a megadott modellt.
     *
     * @param  Model  $model  A törlendő modell.
     */
    public function delete(Model $model): void;
}
