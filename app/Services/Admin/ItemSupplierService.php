<?php

namespace App\Services\Admin;

use App\Models\ItemSupplier;
use App\Models\User;
use App\Repositories\Contracts\ItemSupplierRepositoryInterface;
use App\Services\AuditLogService;
use App\Services\BusinessCacheInvalidator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** A beszerzési források életciklusát és preferred invariánsát kezeli. */
class ItemSupplierService
{
    public function __construct(
        private readonly ItemSupplierRepositoryInterface $itemSuppliers,
        private readonly AuditLogService $auditLogService,
        private readonly BusinessCacheInvalidator $cacheInvalidator,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, ItemSupplier>
     */
    public function paginateForAdminIndex(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->itemSuppliers->paginateForAdminIndex($filters, $perPage);
    }

    /**
     * @return Collection<int, array{id: int, item_number: string, name: string, unit: string, label: string}>
     */
    public function itemOptions(): Collection
    {
        return $this->itemSuppliers->itemOptions()
            ->map(fn (array $item): array => [
                ...$item,
                'label' => "{$item['item_number']} - {$item['name']}",
            ]);
    }

    /**
     * @return Collection<int, array{id: int, code: string, name: string, label: string}>
     */
    public function supplierOptions(): Collection
    {
        return $this->itemSuppliers->supplierOptions()
            ->map(fn (array $supplier): array => [
                ...$supplier,
                'label' => "{$supplier['code']} - {$supplier['name']}",
            ]);
    }

    /**
     * @return Collection<int, ItemSupplier>
     */
    public function activeApprovedForItem(int $itemId): Collection
    {
        return $this->itemSuppliers->activeApprovedForItem($itemId);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes, ?User $causer = null): ItemSupplier
    {
        $attributes = $this->normalizeAttributes($attributes);

        $source = DB::transaction(function () use ($attributes, $causer): ItemSupplier {
            $this->preparePreferredChange($attributes);

            /** @var ItemSupplier $source */
            $source = $this->itemSuppliers->create($attributes);
            $this->auditLogService->logCreated('item_supplier_created', $source, $causer);

            return $source;
        });

        $this->cacheInvalidator->procurementChanged();

        return $source;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(ItemSupplier $source, array $attributes, ?User $causer = null): ItemSupplier
    {
        $attributes = $this->normalizeAttributes($attributes);

        $source = DB::transaction(function () use ($source, $attributes, $causer): ItemSupplier {
            $original = $source->getRawOriginal();
            $this->preparePreferredChange($attributes, $source->id);

            /** @var ItemSupplier $updated */
            $updated = $this->itemSuppliers->update($source, $attributes);
            $this->auditLogService->logUpdated('item_supplier_updated', $updated, $original, $causer);

            return $updated;
        });

        $this->cacheInvalidator->procurementChanged();

        return $source;
    }

    /** A törlési művelet a történeti értelmezhetőség miatt inaktivál. */
    public function deactivate(ItemSupplier $source, ?User $causer = null): ItemSupplier
    {
        $source = DB::transaction(function () use ($source, $causer): ItemSupplier {
            $original = $source->getRawOriginal();

            /** @var ItemSupplier $inactive */
            $inactive = $this->itemSuppliers->update($source, [
                'is_active' => false,
                'is_preferred' => false,
            ]);
            $this->auditLogService->logUpdated('item_supplier_inactivated', $inactive, $original, $causer);

            return $inactive;
        });

        $this->cacheInvalidator->procurementChanged();

        return $source;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function preparePreferredChange(array $attributes, ?int $exceptId = null): void
    {
        if (! ($attributes['is_preferred'] ?? false)) {
            return;
        }

        $itemId = (int) $attributes['item_id'];
        $this->itemSuppliers->lockItem($itemId);
        $this->itemSuppliers->clearActivePreferredForItem($itemId, $exceptId);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function normalizeAttributes(array $attributes): array
    {
        $attributes['currency'] = isset($attributes['currency'])
            ? strtoupper((string) $attributes['currency'])
            : null;

        if (! (bool) ($attributes['is_active'] ?? false)) {
            $attributes['is_preferred'] = false;
        }

        return $attributes;
    }
}
