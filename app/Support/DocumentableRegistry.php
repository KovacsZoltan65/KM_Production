<?php

namespace App\Support;

use App\Models\Item;
use App\Models\ProductionOrder;
use App\Models\ProductionTask;
use App\Models\QualityCheck;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * A dokumentumhoz kapcsolható domainmodellek aliasait, osztályneveit és frontend opcióit kezeli.
 *
 * Az aliasok kizárólag a bemenet és a megjelenítés stabil kulcsai; a polymorphic kapcsolatban
 * a modellek teljes osztályneve kerül tárolásra.
 */
class DocumentableRegistry
{
    /**
     * Visszaadja a támogatott morph aliasok és Eloquent modellek teljes megfeleltetését.
     *
     * @return array{
     *     item: class-string<Item>,
     *     production_order: class-string<ProductionOrder>,
     *     production_task: class-string<ProductionTask>,
     *     quality_check: class-string<QualityCheck>
     * }
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
     * Összeállítja a frontend dokumentumkapcsolási választólistáját.
     *
     * @return list<array{
     *     label: non-empty-string,
     *     value: 'item'|'production_order'|'production_task'|'quality_check',
     *     class: class-string<Item>|class-string<ProductionOrder>|class-string<ProductionTask>|class-string<QualityCheck>
     * }>
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
     * Visszaadja a bemeneti validációban elfogadott aliasokat és teljes modell-osztályneveket.
     *
     * @return list<non-empty-string>
     */
    public static function allowedValues(): array
    {
        return array_values(array_unique(array_merge(array_keys(self::aliases()), array_values(self::aliases()))));
    }

    /**
     * Az ismert aliast modell-osztálynévre oldja, más értéket változatlanul ad vissza.
     */
    public static function classFrom(string $value): string
    {
        return self::aliases()[$value] ?? $value;
    }

    /**
     * Ellenőrzi a típust és az azonosítót, majd visszaadja a létező kapcsolható modell osztálynevét.
     *
     * @return class-string<Model>
     *
     * @throws ValidationException Ha a típus nem támogatott, vagy a hivatkozott modell nem létezik.
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

    /**
     * Visszaadja a modell regisztrált aliasát, ismeretlen osztálynévnél pedig az eredeti értéket.
     */
    public static function aliasFor(string $class): string
    {
        return array_search($class, self::aliases(), true) ?: $class;
    }

    /**
     * Ember számára olvasható angol címkét képez a modell aliasából vagy osztálynevéből.
     */
    public static function labelFor(string $class): string
    {
        $alias = self::aliasFor($class);

        return str($alias)->replace('_', ' ')->title()->toString();
    }
}
