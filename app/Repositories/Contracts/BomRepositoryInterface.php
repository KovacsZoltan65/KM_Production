<?php

namespace App\Repositories\Contracts;

use App\Models\Bom;

interface BomRepositoryInterface extends AdminRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $items
     */
    public function createWithItems(array $attributes, array $items): Bom;

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $items
     */
    public function updateWithItems(Bom $bom, array $attributes, array $items): Bom;
}
