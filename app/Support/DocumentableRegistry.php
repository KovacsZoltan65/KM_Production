<?php

namespace App\Support;

use App\Models\Item;
use App\Models\ProductionOrder;
use App\Models\ProductionTask;
use App\Models\QualityCheck;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class DocumentableRegistry
{
    /**
     * @return array<string, class-string<Model>>
     */
    public static function aliases(): array
    {
        return [
            'item' => Item::class,
            'production_order' => ProductionOrder::class,
            'production_task' => ProductionTask::class,
            'quality_check' => QualityCheck::class,
        ];
    }

    /**
     * @return array<int, array{label: string, value: string, class: class-string<Model>}>
     */
    public static function options(): array
    {
        return [
            ['label' => 'Item', 'value' => 'item', 'class' => Item::class],
            ['label' => 'Production Order', 'value' => 'production_order', 'class' => ProductionOrder::class],
            ['label' => 'Production Task', 'value' => 'production_task', 'class' => ProductionTask::class],
            ['label' => 'Quality Check', 'value' => 'quality_check', 'class' => QualityCheck::class],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function allowedValues(): array
    {
        return array_values(array_unique(array_merge(array_keys(self::aliases()), array_values(self::aliases()))));
    }

    /**
     * @return class-string<Model>
     */
    public static function classFrom(string $value): string
    {
        return self::aliases()[$value] ?? $value;
    }

    /**
     * @return class-string<Model>
     */
    public static function resolveExisting(string $type, int $id): string
    {
        $class = self::classFrom($type);

        if (! \in_array($class, array_values(self::aliases()), true)) {
            throw ValidationException::withMessages(['documentable_type' => 'Unsupported documentable type.']);
        }

        if (! $class::query()->whereKey($id)->exists()) {
            throw ValidationException::withMessages(['documentable_id' => 'The selected linked entity does not exist.']);
        }

        return $class;
    }

    public static function aliasFor(string $class): string
    {
        return array_search($class, self::aliases(), true) ?: $class;
    }

    public static function labelFor(string $class): string
    {
        $alias = self::aliasFor($class);

        return str($alias)->replace('_', ' ')->title()->toString();
    }
}
