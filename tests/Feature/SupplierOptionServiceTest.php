<?php

use App\Enums\ItemType;
use App\Models\Item;
use App\Models\ItemSupplier;
use App\Models\PurchaseOrderItem;
use App\Models\StockBalance;
use App\Models\StockReservation;
use App\Models\Supplier;
use App\Repositories\Admin\ItemSupplierRepository;
use App\Repositories\Contracts\ItemSupplierRepositoryInterface;
use App\Services\Admin\PurchaseRequisitionReplenishmentService;
use App\Services\Admin\SupplierOptionService;
use App\Services\AuditLogService;
use App\Support\Procurement\ProcurementRequirementInput;
use App\Support\Procurement\ProcurementRequirementProvenance;
use App\Support\Procurement\SupplierOptionEvaluationException;
use App\Support\Procurement\SupplierOptionQuery;
use App\Support\Procurement\SupplierOptionReadSnapshot;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Assert;

// Own transactions on MySQL: exercise the actual snapshot boundary, not a test savepoint.
uses(DatabaseMigrations::class);

afterEach(fn () => Carbon::setTestNow());

function supplierOptionQuery(Item $item, string $quantity = '3.000', ?string $date = '2026-09-30', ?string $unit = null): SupplierOptionQuery
{
    return new SupplierOptionQuery(new ProcurementRequirementInput(
        $item->id, $quantity, $date, $unit ?? $item->unit, '2026-09-28',
        new ProcurementRequirementProvenance('material_requirement_netting', 42, 'net_requirement', '2026-09-28T10:00:00+02:00', 'all_requirements'),
    ));
}

function supplierOptionSource(Item $item, array $attributes = []): ItemSupplier
{
    return ItemSupplier::factory()->approved()->create([
        'item_id' => $item->id,
        'purchase_unit' => 'bag',
        'conversion_factor' => '25.000000',
        'minimum_order_quantity' => null,
        'order_multiple' => null,
        'lead_time_days' => 2,
        'unit_price' => '12.5000',
        'currency' => 'HUF',
        ...$attributes,
    ]);
}

function supplierOptionPayload(SupplierOptionQuery $query): array
{
    return json_decode(json_encode(app(SupplierOptionService::class)->evaluate($query), JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
}

it('keeps every ineligible source and derives the bool only from strict membership', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    $cases = [
        'SUPPLIER_INACTIVE' => [], 'SUPPLIER_DELETED' => [],
        'SOURCE_INACTIVE' => ['is_active' => false],
        'SOURCE_NOT_APPROVED' => ['is_approved' => false],
        'BEFORE_VALID_FROM' => ['valid_from' => '2026-09-29'],
        'AFTER_VALID_UNTIL' => ['valid_until' => '2026-09-27'],
    ];
    $expected = [];
    foreach ($cases as $reason => $attributes) {
        $source = supplierOptionSource($item, [...$attributes, 'is_preferred' => true, 'priority' => 1]);
        if ($reason === 'SUPPLIER_INACTIVE') {
            $source->supplier->update(['is_active' => false]);
        }
        if ($reason === 'SUPPLIER_DELETED') {
            $source->supplier->delete();
        }
        $expected[$source->id] = $reason;
    }
    $boundary = supplierOptionSource($item, ['valid_from' => '2026-09-28', 'valid_until' => '2026-09-28']);
    $result = supplierOptionPayload(supplierOptionQuery($item));
    $strict = app(ItemSupplierRepositoryInterface::class)->eligibleForItemsAt([$item->id], Carbon::parse('2026-09-28'))->pluck('id')->all();
    expect($result['options'])->toHaveCount(7)->and($strict)->toBe([$boundary->id]);
    foreach ($result['options'] as $option) {
        $id = $option['relationship']['id'];
        expect($option['eligibility']['is_eligible'])->toBe(in_array($id, $strict, true));
        if ($id === $boundary->id) {
            expect($option['eligibility']['reasons'])->toBe([]);

            continue;
        }
        expect($option['eligibility']['reasons'])->toBe([$expected[$id]])
            ->and($option['requirement_fit']['is_usable'])->toBeFalse()
            ->and($option['requirement_fit']['objectively_feasible'])->toBe(['state' => 'KNOWN', 'value' => false, 'reason' => null])
            ->and($option['ordering']['effective_order_quantity']['reason'])->toBe('UNUSABLE_SOURCE')
            ->and($option['delivery']['expected_date']['reason'])->toBe('UNUSABLE_SOURCE')
            ->and($option['commercial']['reference_unit_price']['value'])->toBe('12.5000');
    }
    $item->update(['is_active' => false]);
    foreach (supplierOptionPayload(supplierOptionQuery($item))['options'] as $option) {
        expect($option['eligibility']['is_eligible'])->toBeFalse()->and($option['eligibility']['reasons'])->toContain('ITEM_INACTIVE');
    }
});

it('rejects invalid item scope and current unit mismatches even with no known sources', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    $service = app(SupplierOptionService::class);
    expect(fn () => $service->evaluate(supplierOptionQuery($item, unit: 'db')))->toThrow(ValidationException::class);
    $item->update(['item_type' => ItemType::FinishedProduct]);
    expect(fn () => $service->evaluate(supplierOptionQuery($item)))->toThrow(ValidationException::class);
    $item->update(['item_type' => ItemType::PurchasedMaterial]);
    $query = supplierOptionQuery($item);
    $item->delete();
    expect(fn () => $service->evaluate($query))->toThrow(ValidationException::class);
    $missing = new Item;
    $missing->id = 999999;
    $missing->unit = 'kg';
    expect(fn () => $service->evaluate(supplierOptionQuery($missing)))->toThrow(ValidationException::class);
});

it('uses the replenishment result unchanged for every strategy in base units', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    $cases = [
        [null, null, '3.000', '0.000', 'exact'],
        ['5.000', null, '5.000', '2.000', 'moq'],
        [null, '2.000', '4.000', '1.000', 'order_multiple'],
        ['5.000', '2.000', '6.000', '3.000', 'moq_and_order_multiple'],
        ['0.000', null, '3.000', '0.000', 'exact'],
    ];
    foreach ($cases as [$moq, $multiple]) {
        supplierOptionSource($item, ['minimum_order_quantity' => $moq, 'order_multiple' => $multiple]);
    }
    $result = supplierOptionPayload(supplierOptionQuery($item));
    foreach ($result['options'] as $index => $option) {
        expect($option['ordering']['effective_order_quantity']['value'])->toBe($cases[$index][2])
            ->and($option['ordering']['excess_quantity']['value'])->toBe($cases[$index][3])
            ->and($option['ordering']['strategy']['value'])->toBe($cases[$index][4])
            ->and($option['ordering']['purchase_unit']['value'])->toBe('bag')
            ->and($option['ordering']['conversion_factor']['value'])->toBe('25.000000');
    }
    expect($result['requirement']['required_quantity'])->toBe('3.000')->and($result['requirement']['unit'])->toBe('kg');
    foreach (supplierOptionPayload(supplierOptionQuery($item, '0.000'))['options'] as $option) {
        expect($option['requirement_fit']['is_usable'])->toBeTrue()
            ->and($option['ordering']['effective_order_quantity']['value'])->toBe('0.000')
            ->and($option['ordering']['excess_quantity']['value'])->toBe('0.000')
            ->and($option['requirement_fit']['objectively_feasible']['state'])->toBe('NOT_APPLICABLE')
            ->and($option['delivery']['date_fit']['reason'])->toBe('NO_PROCUREMENT_REQUIRED');
    }
});

it('preserves decimal edges and makes overflow a source policy failure', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    supplierOptionSource($item, ['order_multiple' => '0.005']);
    $small = supplierOptionPayload(supplierOptionQuery($item, '0.001'))['options'][0];
    expect($small['ordering']['effective_order_quantity']['value'])->toBe('0.005')->and($small['ordering']['excess_quantity']['value'])->toBe('0.004');
    $overflow = supplierOptionPayload(supplierOptionQuery($item, '999999999999999.999'))['options'][0];
    expect($overflow['eligibility']['is_eligible'])->toBeTrue()->and($overflow['requirement_fit']['is_usable'])->toBeFalse()
        ->and($overflow['requirement_fit']['reasons'])->toContain('INVALID_POLICY');
});

it('keeps policy failures separate from eligibility and query failures', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    foreach ([['conversion_factor' => '0.000000'], ['purchase_unit' => ' '], ['minimum_order_quantity' => '-1.000'], ['order_multiple' => '0.000']] as $policy) {
        supplierOptionSource($item, $policy);
    }
    foreach (supplierOptionPayload(supplierOptionQuery($item, '0.000'))['options'] as $option) {
        expect($option['eligibility']['is_eligible'])->toBeTrue()->and($option['requirement_fit']['is_usable'])->toBeFalse()
            ->and($option['requirement_fit']['objectively_feasible']['value'])->toBeFalse()
            ->and($option['requirement_fit']['reasons'])->toContain('INVALID_POLICY')
            ->and($option['data_quality']['inconsistent'])->not->toBeEmpty();
    }
});

it('uses calendar day estimates without changing eligibility when late or unknown', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    $source = supplierOptionSource($item);
    foreach ([
        [2, '2026-09-30', '2026-09-30', true, 'on_time', null],
        [3, '2026-09-30', '2026-10-01', false, 'late', null],
        [null, '2026-09-30', null, null, null, 'MISSING_LEAD_TIME'],
        [0, '2026-09-28', '2026-09-28', true, 'on_time', null],
        [0, '2026-09-27', '2026-09-28', false, 'late', null],
        [2, null, '2026-09-30', null, null, 'MISSING_REQUIRED_DATE'],
        [null, null, null, null, null, 'MISSING_REQUIRED_DATE'],
    ] as [$lead, $required, $expected, $feasible, $fit, $reason]) {
        $source->update(['lead_time_days' => $lead]);
        $option = supplierOptionPayload(supplierOptionQuery($item, date: $required))['options'][0];
        expect($option['eligibility']['is_eligible'])->toBeTrue()->and($option['requirement_fit']['is_usable'])->toBeTrue()
            ->and($option['delivery']['expected_date']['value'])->toBe($expected)
            ->and($option['delivery']['date_fit']['value'])->toBe($fit)
            ->and($option['requirement_fit']['objectively_feasible']['value'])->toBe($feasible)
            ->and($option['requirement_fit']['objectively_feasible']['reason'])->toBe($reason);
    }
});

it('never invents commercial totals or an undefined price basis', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    $source = supplierOptionSource($item);
    foreach ([[null, null], ['0.0000', 'HUF'], [null, 'HUF'], ['10.0000', null]] as [$price, $currency]) {
        $source->update(['unit_price' => $price, 'currency' => $currency]);
        $option = supplierOptionPayload(supplierOptionQuery($item))['options'][0];
        expect($option['commercial']['reference_unit_price']['value'])->toBe($price)
            ->and($option['commercial']['reference_unit_price']['state'])->toBe($price === null ? 'UNKNOWN' : 'KNOWN')
            ->and($option['commercial']['currency']['value'])->toBe($currency)
            ->and($option['commercial']['price_basis']['reason'])->toBe('PRICE_BASIS_UNDEFINED')
            ->and($option['commercial']['estimated_reference_value']['value'])->toBeNull()
            ->and($option['requirement_fit']['objectively_feasible']['value'])->toBeTrue()
            ->and($option['data_quality']['inconsistent'])->toHaveCount(($price === null) !== ($currency === null) ? 1 : 0)
            ->and($option['data_quality']['restricted'])->toBe([]);
    }
});

it('returns the complete stable ID ordered list with bounded query count and an empty success', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    expect(supplierOptionPayload(supplierOptionQuery($item))['options'])->toBe([]);
    $suppliers = Supplier::factory()->count(505)->create();
    foreach ($suppliers->reverse() as $supplier) {
        supplierOptionSource($item, ['supplier_id' => $supplier->id]);
    }
    DB::enableQueryLog();
    DB::flushQueryLog();
    $result = supplierOptionPayload(supplierOptionQuery($item));
    $queries = DB::getQueryLog();
    DB::disableQueryLog();
    expect($result['options'])->toHaveCount(505)
        ->and(array_column(array_column($result['options'], 'supplier'), 'id'))->toBe($suppliers->pluck('id')->all())
        ->and(count($queries))->toBeLessThanOrEqual(9);
    foreach ($queries as $query) {
        expect(strtolower($query['query']))->not->toContain('for update');
    }
});

it('does not write audit cache or workflow state and never reads supply to net again', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    supplierOptionSource($item, ['minimum_order_quantity' => '5.000', 'order_multiple' => '2.000']);
    $query = supplierOptionQuery($item);
    Carbon::setTestNow('2026-09-28T10:00:00+02:00');
    // Unconfigured mocks reject any business audit/cache call.
    app()->instance(AuditLogService::class, Mockery::mock(AuditLogService::class));
    $cache = Cache::getFacadeRoot();
    $cacheSpy = Cache::spy();
    DB::enableQueryLog();
    DB::flushQueryLog();
    $before = supplierOptionPayload($query);
    $firstQueries = DB::getQueryLog();
    foreach (['put', 'add', 'increment', 'forget', 'flush', 'forever', 'remember'] as $method) {
        $cacheSpy->shouldNotHaveReceived($method);
    }
    Cache::swap($cache);
    DB::disableQueryLog();
    // Independent fixtures represent a different supply state, outside the evaluated read.
    StockBalance::factory()->create(['item_id' => $item->id, 'quantity' => '100.000']);
    StockReservation::factory()->create(['item_id' => $item->id, 'reserved_quantity' => '50.000']);
    PurchaseOrderItem::factory()->create(['item_id' => $item->id, 'ordered_quantity' => '75.000', 'unit' => 'kg']);
    DB::enableQueryLog();
    DB::flushQueryLog();
    $cacheSpy = Cache::spy();
    $after = supplierOptionPayload($query);
    foreach (['put', 'add', 'increment', 'forget', 'flush', 'forever', 'remember'] as $method) {
        $cacheSpy->shouldNotHaveReceived($method);
    }
    Cache::swap($cache);
    $queries = [...$firstQueries, ...DB::getQueryLog()];
    DB::disableQueryLog();
    expect($after)->toBe($before)->and($after['options'][0]['ordering']['effective_order_quantity']['value'])->toBe('6.000')
        ->and($after['options'][0]['ordering']['excess_quantity']['value'])->toBe('3.000');
    foreach ($queries as $entry) {
        $sql = strtolower($entry['query']);
        expect($sql)->not->toMatch('/\b(insert|update|delete|replace)\b/');
        expect($sql)
            ->not->toMatch('/stock_|purchase_order|purchase_requisition|supply_proposal|material_requirement|pegging|activity_log|cache/');
    }
});

it('rejects contradictory membership instead of trusting diagnostics', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    supplierOptionSource($item);
    app()->instance(ItemSupplierRepositoryInterface::class, new class extends ItemSupplierRepository
    {
        public function eligibleForItemsAt(array $itemIds, Carbon $date): Collection
        {
            return collect();
        }
    });
    expect(fn () => app(SupplierOptionService::class)->evaluate(supplierOptionQuery($item)))
        ->toThrow(SupplierOptionEvaluationException::class, 'SUPPLIER_OPTION_DIAGNOSTICS_MISMATCH');
});

it('rejects a physically missing supplier explicitly', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    supplierOptionSource($item);
    app()->instance(ItemSupplierRepositoryInterface::class, new class extends ItemSupplierRepository
    {
        public function knownSourcesForItem(int $itemId): Collection
        {
            return parent::knownSourcesForItem($itemId)->each(fn (ItemSupplier $source) => $source->setRelation('supplier', null));
        }
    });
    expect(fn () => app(SupplierOptionService::class)->evaluate(supplierOptionQuery($item)))
        ->toThrow(SupplierOptionEvaluationException::class, 'SUPPLIER_OPTION_SOURCE_INTEGRITY');
});

it('fails explicitly for duplicate orphaned or drifted source snapshots', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    supplierOptionSource($item);
    foreach ([
        'duplicate' => 'SUPPLIER_OPTION_DUPLICATE_SOURCE',
        'wrong item' => 'SUPPLIER_OPTION_SOURCE_INTEGRITY',
        'missing item' => 'SUPPLIER_OPTION_SOURCE_INTEGRITY',
        'extra eligible' => 'SUPPLIER_OPTION_MEMBERSHIP_MISMATCH',
        'false positive' => 'SUPPLIER_OPTION_DIAGNOSTICS_MISMATCH',
    ] as $fault => $reason) {
        app()->instance(ItemSupplierRepositoryInterface::class, new class($fault) extends ItemSupplierRepository
        {
            public function __construct(private readonly string $fault) {}

            public function knownSourcesForItem(int $itemId): Collection
            {
                $sources = parent::knownSourcesForItem($itemId);
                $source = $sources->sole();
                match ($this->fault) {
                    'duplicate' => $sources->push($source),
                    'wrong item' => $source->item_id = $itemId + 1,
                    'missing item' => $source->setRelation('item', null),
                    'extra eligible' => $sources = collect(),
                    'false positive' => $source->is_active = false,
                    default => null,
                };

                return $sources;
            }
        });
        expect(fn () => app(SupplierOptionService::class)->evaluate(supplierOptionQuery($item)))
            ->toThrow(SupplierOptionEvaluationException::class, $reason);
    }
});

it('returns immutable detached business results with explicit missing-date provenance', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    $source = supplierOptionSource($item);
    $query = supplierOptionQuery($item, date: null);
    $result = app(SupplierOptionService::class)->evaluate($query);
    expect(fn () => (new ReflectionProperty($result->options[0], 'supplier'))->setValue($result->options[0], []))->toThrow(Error::class);
    expect(fn () => (new ReflectionProperty($result->options[0]->ordering['effective_order_quantity'], 'value'))->setValue($result->options[0]->ordering['effective_order_quantity'], '9.000'))->toThrow(Error::class);
    $source->update(['minimum_order_quantity' => '10.000']);
    expect($result->options[0]->ordering['effective_order_quantity']->value)->toBe('3.000');
    $wire = json_decode(json_encode($result, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
    expect($wire['schema_version'])->toBe('0.1')
        ->and($wire['requirement']['required_date'])->toBe(['state' => 'UNKNOWN', 'value' => null, 'reason' => 'MISSING_REQUIRED_DATE'])
        ->and($wire['requirement']['provenance']['source_id'])->toBe(42)
        ->and($wire['requirement']['provenance']['quantity_basis'])->toBe('net_requirement')
        ->and($wire['requirement']['provenance']['netting_scope']['value'])->toBe('all_requirements');
});

it('matches the existing calculator result for an unchanged authoritative net requirement', function (): void {
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    $source = supplierOptionSource($item, ['minimum_order_quantity' => '5.000', 'order_multiple' => '2.000']);
    $query = supplierOptionQuery($item);
    $calculated = app(PurchaseRequisitionReplenishmentService::class)->calculateQuantity(
        $query->requirement->requiredQuantity, $query->requirement->unit, $source,
    );
    $option = supplierOptionPayload($query)['options'][0];
    expect($calculated->baseRequiredQuantity)->toBe('3.000')
        ->and($option['ordering']['effective_order_quantity']['value'])->toBe($calculated->adjustedQuantity)
        ->and($option['ordering']['excess_quantity']['value'])->toBe($calculated->excessQuantity)
        ->and($option['ordering']['strategy']['value'])->toBe($calculated->strategy);
});
it('keeps MySQL known sources and strict membership on the same read-only snapshot', function (): void {
    if (DB::getDriverName() !== 'mysql') {
        Assert::markTestSkipped('Requires the dedicated guarded MySQL test database.');
    }
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    $source = supplierOptionSource($item);
    config(['database.connections.supplier_option_writer' => config('database.connections.'.DB::getDefaultConnection())]);
    $writer = DB::connection('supplier_option_writer');
    app()->instance(ItemSupplierRepositoryInterface::class, new class($writer, $source->id) extends ItemSupplierRepository
    {
        public function __construct(private readonly Connection $writer, private readonly int $sourceId) {}

        public function knownSourcesForItem(int $itemId): Collection
        {
            $known = parent::knownSourcesForItem($itemId);
            // A separately committed master change between the two authoritative reads.
            $this->writer->table('item_suppliers')->where('id', $this->sourceId)->update(['is_active' => false]);

            return $known;
        }
    });
    try {
        $option = supplierOptionPayload(supplierOptionQuery($item))['options'][0];
        expect($option['eligibility'])->toBe(['is_eligible' => true, 'reasons' => []])
            ->and($source->fresh()->is_active)->toBeFalse()
            ->and(DB::transactionLevel())->toBe(0);
        $next = supplierOptionPayload(supplierOptionQuery($item))['options'][0];
        expect($next['eligibility'])->toBe(['is_eligible' => false, 'reasons' => ['SOURCE_INACTIVE']]);
        expect(fn () => app(SupplierOptionReadSnapshot::class)->evaluate(
            fn () => DB::table('item_suppliers')->where('id', $source->id)->update(['is_active' => true]),
        ))->toThrow(QueryException::class);
        expect($source->fresh()->is_active)->toBeFalse()->and(DB::transactionLevel())->toBe(0);
    } finally {
        DB::purge('supplier_option_writer');
    }
});
