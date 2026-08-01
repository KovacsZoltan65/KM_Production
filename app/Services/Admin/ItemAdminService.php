<?php

namespace App\Services\Admin;

use App\Enums\ItemType;
use App\Repositories\Contracts\ItemRepositoryInterface;
use App\Services\AuditLogService;
use App\Services\BusinessCacheInvalidator;
use App\Services\CodeCreationService;

class ItemAdminService extends CodeAwareAdminService
{
    public function __construct(ItemRepositoryInterface $repository, AuditLogService $auditLogService, CodeCreationService $codeCreationService, private readonly BusinessCacheInvalidator $cacheInvalidator)
    {
        parent::__construct($repository, $auditLogService, $codeCreationService);
    }

    protected function codeType(array $attributes): string
    {
        return 'item';
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function normalizeAttributes(array $attributes): array
    {
        $itemType = $attributes['item_type'] instanceof ItemType
            ? $attributes['item_type']
            : ItemType::from((string) $attributes['item_type']);

        $attributes['item_type'] = $itemType->value;

        return $attributes;
    }

    protected function createdEvent(): string
    {
        return 'admin_item_created';
    }

    protected function updatedEvent(): string
    {
        return 'admin_item_updated';
    }

    protected function deletedEvent(): string
    {
        return 'admin_item_deleted';
    }

    protected function afterWrite(): void
    {
        $this->cacheInvalidator->inventoryChanged();
        $this->cacheInvalidator->productionChanged();
    }
}
