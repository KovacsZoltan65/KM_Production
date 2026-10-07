<?php

use App\Enums\CustomerOrderStatus;
use App\Enums\ProblemCaseEvaluationResult;
use App\Enums\ProblemCaseLifecycle;
use App\Models\BomItem;
use App\Models\CustomerOrderItem;
use App\Models\Item;
use App\Models\ItemSupplier;
use App\Models\MaterialRequirement;
use App\Models\ProblemCase;
use App\Models\StockBalance;
use App\Models\User;
use App\Repositories\Admin\ProblemCaseRepository;
use App\Repositories\Contracts\ItemRepositoryInterface;
use App\Repositories\Contracts\ProblemCaseRepositoryInterface;
use App\Services\Admin\MaterialRequirementNettingService;
use App\Services\Admin\SupplierOptionService;
use App\Services\AuditLogService;
use App\Services\Merlin\GetSupplierOptionsTool;
use App\Services\Merlin\MaterialShortageSupplierOptionsRead;
use App\Support\Merlin\MaterialShortageDetectionSnapshot;
use App\Support\Merlin\SupplierOptionsToolContext;
use App\Support\Merlin\SupplierOptionsToolProjection;
use App\Support\Procurement\SupplierOption;
use App\Support\Procurement\SupplierOptionEvaluationException;
use App\Support\Procurement\SupplierOptionResult;
use App\Support\Testing\TestEnvironmentGuard;
use Carbon\CarbonImmutable;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery\CompositeExpectation;
use PHPUnit\Framework\Assert;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\PermissionRegistrar;

// No caller-owned test transaction: the composition owns its snapshot on MySQL.
uses(DatabaseMigrations::class);

beforeEach(fn () => Carbon::setTestNow('2026-10-06T10:00:00Z'));
afterEach(fn () => Carbon::setTestNow());

/** @return array{User, ProblemCase, MaterialRequirement, Item, SupplierOptionsToolContext} */
function supplierToolFixture(): array
{
    $user = User::factory()->create();
    $user->givePermissionTo(['inventory.view', 'item-suppliers.view']);
    $item = Item::factory()->purchasedMaterial()->create(['unit' => 'kg']);
    $orderItem = CustomerOrderItem::factory()->create();
    $orderItem->customerOrder->update(['status' => CustomerOrderStatus::Confirmed]);
    $requirement = MaterialRequirement::factory()->create([
        'customer_order_item_id' => $orderItem->id,
        'required_item_id' => $item->id,
        'required_quantity' => '12.125',
        'required_at' => '2026-10-10',
        'unit' => 'kg',
        'missing_quantity' => '999.000',
    ]);
    $netting = app(MaterialRequirementNettingService::class)->calculate()->firstWhere('requirementId', $requirement->id);
    $snapshot = new MaterialShortageDetectionSnapshot(
        $netting, $orderItem->id, $orderItem->customer_order_id, $item->item_number, $item->name,
        CarbonImmutable::now(), 'SYSTEM', ['producer' => 'test'], ['kind' => 'all_requirements'],
        ['private_evidence' => 'must not leak'],
    );
    // Deliberately stale stored projection: execution must derive ACTIVE independently.
    $case = app(ProblemCaseRepositoryInterface::class)->createMaterialShortage($snapshot, ProblemCaseEvaluationResult::Resolved);

    return [$user, $case, $requirement, $item, new SupplierOptionsToolContext($user, $case->id, [GetSupplierOptionsTool::NAME])];
}

/** @param array<string, mixed> $attributes */
function supplierToolSource(Item $item, array $attributes = []): ItemSupplier
{
    return ItemSupplier::factory()->approved()->create([
        'item_id' => $item->id, 'purchase_unit' => 'bag', 'conversion_factor' => '25.000000',
        'minimum_order_quantity' => null, 'order_multiple' => null, 'lead_time_days' => 2,
        ...$attributes,
    ]);
}

/** @return array{problem_case_id: string} */
function supplierToolInput(SupplierOptionsToolContext $context): array
{
    return ['problem_case_id' => $context->problemCaseId];
}

it('exposes only the closed READ capability and requires both existing permissions', function (?string $missing): void {
    [$user, , , , $context] = supplierToolFixture();
    if ($missing !== null) {
        $user->revokePermissionTo($missing);
    }
    $tool = app(GetSupplierOptionsTool::class);
    expect($tool->definition()['category'])->toBe('READ')
        ->and($tool->definition()['input_schema']['additionalProperties'])->toBeFalse()
        ->and($tool->availability($context, supplierToolInput($context))['available'])->toBe($missing === null);
    $result = $tool->execute($context, supplierToolInput($context));
    expect($result['status'])->toBe($missing === null ? 'success' : 'denied');
})->with([null, 'inventory.view', 'item-suppliers.view']);

it('rejects foreign or unknown calls before any case lookup including for super-admin', function (bool $superAdmin, string $mode): void {
    [$user, , , , $context] = supplierToolFixture();
    if ($superAdmin) {
        $user->assignRole('super-admin');
    }
    $context = new SupplierOptionsToolContext($user, $context->problemCaseId, $mode === 'allowlist' ? [] : [GetSupplierOptionsTool::NAME]);
    $cases = Mockery::mock(ProblemCaseRepositoryInterface::class);
    $cases->shouldNotReceive('findForCurrentEvaluation');
    app()->instance(ProblemCaseRepositoryInterface::class, $cases);
    $input = $mode === 'case' ? ['problem_case_id' => (string) Str::uuid()] : supplierToolInput($context);
    $name = $mode === 'name' ? 'App\\Services\\Admin\\SupplierOptionService::evaluate' : GetSupplierOptionsTool::NAME;
    $tool = app(GetSupplierOptionsTool::class);
    expect($tool->availability($context, $input, $name)['available'])->toBeFalse();
    $result = $tool->execute($context, $input, $name);
    expect($result['status'])->toBe('denied')->and($result['data'])->toBeNull()
        ->and($result['code'])->toBe(match ($mode) {
            'case' => 'CASE_CONTEXT_MISMATCH', 'allowlist' => 'AI_CAPABILITY_DENIED', default => 'UNKNOWN_TOOL',
        });
})->with([false, true])->with(['case', 'allowlist', 'name']);

it('rejects extra business arguments and malformed input without lookup or logging the raw input', function (mixed $input): void {
    [, , , , $context] = supplierToolFixture();
    if (is_array($input) && isset($input['quantity'])) {
        $input['problem_case_id'] = $context->problemCaseId;
    }
    $cases = Mockery::mock(ProblemCaseRepositoryInterface::class);
    $cases->shouldNotReceive('findForCurrentEvaluation');
    app()->instance(ProblemCaseRepositoryInterface::class, $cases);
    $result = app(GetSupplierOptionsTool::class)->execute($context, $input);
    expect($result['code'])->toBe('INVALID_TOOL_INPUT')->and($result['data'])->toBeNull()
        ->and(Activity::query()->latest('id')->firstOrFail()->properties->toJson())->not->toContain('raw_secret');
})->with([[null], [[]], [['problem_case_id' => 12]], [['problem_case_id' => 'raw_secret']], [['quantity' => 'raw_secret']]]);

it('re-authorizes a previously available context using a fresh actor', function (string $change): void {
    [$user, , , , $context] = supplierToolFixture();
    $tool = app(GetSupplierOptionsTool::class);
    expect($tool->availability($context, supplierToolInput($context))['available'])->toBeTrue();
    // Eagerly hydrated permissions in the original actor must not survive revocation.
    $user->getAllPermissions();
    if ($change === 'delete') {
        $user->delete();
    } else {
        $user->revokePermissionTo($change);
    }
    expect($tool->execute($context, supplierToolInput($context))['code'])->toBe('AUTHORIZATION_DENIED');
})->with(['inventory.view', 'item-suppliers.view', 'delete']);

it('re-resolves current domain state after availability and never evaluates suppliers for blocked cases', function (string $state, string $code): void {
    [, $case, $requirement, $item, $context] = supplierToolFixture();
    $tool = app(GetSupplierOptionsTool::class);
    expect($tool->availability($context, supplierToolInput($context))['available'])->toBeTrue();
    match ($state) {
        'closed' => $case->update(['lifecycle' => ProblemCaseLifecycle::Closed]),
        'invalidated' => $case->update(['lifecycle' => ProblemCaseLifecycle::Invalidated]),
        'invalid' => $requirement->customerOrderItem->customerOrder->update(['status' => CustomerOrderStatus::Cancelled]),
        'undetermined' => $requirement->update(['production_order_id' => null, 'bom_item_id' => BomItem::factory()->create()->id]),
        'resolved' => StockBalance::factory()->create(['item_id' => $item->id, 'quantity' => '20.000']),
        default => throw new InvalidArgumentException('Unknown fixture state.'),
    };
    $items = Mockery::mock(ItemRepositoryInterface::class);
    $items->shouldNotReceive('findForSupplierOptions');
    app()->instance(ItemRepositoryInterface::class, $items);
    // Resolve a new tool so its supplier dependency uses the rejecting repository.
    $result = app(GetSupplierOptionsTool::class)->execute($context, supplierToolInput($context));
    expect($result['code'])->toBe($code)->and($result['data'])->toBeNull()
        ->and($case->fresh()->evaluations()->count())->toBe(1);
})->with([
    ['closed', 'CASE_NOT_CURRENT'], ['invalidated', 'CASE_NOT_CURRENT'], ['invalid', 'SOURCE_INVALID'],
    ['undetermined', 'CURRENT_STATE_UNDETERMINED'], ['resolved', 'NO_CURRENT_SHORTAGE'],
]);

it('maps fresh full-scope netting exactly and emits detached JSON with a separate sanitized audit', function (): void {
    [, $case, $requirement, $item, $context] = supplierToolFixture();
    StockBalance::factory()->create(['item_id' => $item->id, 'quantity' => '2.000']);
    supplierToolSource($item, ['minimum_order_quantity' => '12.000', 'order_multiple' => '5.000']);
    $before = $case->fresh()->getRawOriginal();
    DB::enableQueryLog();
    DB::flushQueryLog();
    $result = app(GetSupplierOptionsTool::class)->execute($context, supplierToolInput($context));
    $queries = DB::getQueryLog();
    DB::disableQueryLog();
    expect($result['status'])->toBe('success')->and($result['data']['requirement']['required_quantity'])->toBe('10.125')
        ->and($result['data']['requirement']['provenance']['source_id'])->toBe($requirement->id)
        ->and($result['data']['requirement']['provenance']['quantity_basis'])->toBe('net_requirement')
        ->and($result['data']['requirement']['provenance']['netting_scope']['value'])->toBe('all_requirements')
        ->and($result['data']['requirement']['required_date']['value'])->toBe('2026-10-10')
        ->and($result['data']['requirement']['unit'])->toBe('kg')
        ->and($result['data']['options'][0]['ordering']['effective_order_quantity']['value'])->toBe('15.000')
        ->and($result['data']['options'][0]['ordering']['excess_quantity']['value'])->toBe('4.875')
        ->and($case->fresh()->getRawOriginal())->toBe($before)
        ->and($case->evaluations()->count())->toBe(1)
        ->and(json_decode(json_encode($result, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR))->toBe($result);
    $wire = json_encode($result, JSON_THROW_ON_ERROR);
    foreach (['allocations', 'stock_balance', 'purchase_order_item', 'private_evidence', 'detection_snapshot', 'customer_order_id', 'production_order_id', 'tax_number', 'address', 'bank', 'stack', 'sql'] as $forbidden) {
        expect($wire)->not->toContain('"'.$forbidden.'"');
    }
    foreach ($queries as $entry) {
        $sql = strtolower($entry['query']);
        if (preg_match('/\b(insert|update|delete|replace)\b/', $sql)) {
            expect($sql)->toContain('activity_log');
            expect($sql)->not->toMatch('/\b(update|delete|replace)\b/');
        }
        expect($sql)->not->toContain('for update');
    }
    $audit = Activity::query()->where('event', 'merlin_supplier_options_tool_called')->sole();
    expect($audit->causer_id)->toBe($context->userId)
        ->and($audit->properties['authorization_outcome'])->toBe('AUTHORIZED')
        ->and($audit->properties['resolver_gate_outcome'])->toBe('ACTIVE')
        ->and($audit->properties['option_count'])->toBe(1)
        ->and($audit->properties->has('supplier'))->toBeFalse()
        ->and($audit->properties->has('data'))->toBeFalse();
});

it('returns empty success or successful ineligible and unusable diagnostics', function (string $kind): void {
    [, , , $item, $context] = supplierToolFixture();
    if ($kind !== 'empty') {
        supplierToolSource($item, $kind === 'ineligible' ? ['is_approved' => false] : ['purchase_unit' => '']);
    }
    $result = app(GetSupplierOptionsTool::class)->execute($context, supplierToolInput($context));
    expect($result['status'])->toBe('success');
    if ($kind === 'empty') {
        expect($result['data']['options'])->toBe([]);
    } else {
        expect($result['data']['options'])->toHaveCount(1)
            ->and($result['data']['options'][0]['requirement_fit']['is_usable'])->toBeFalse();
    }
})->with(['empty', 'ineligible', 'unusable']);

it('returns not found only after disclosure authorization and hides technical failures', function (string $kind): void {
    [$user, , , , $context] = supplierToolFixture();
    if ($kind === 'missing') {
        $context = new SupplierOptionsToolContext($user, (string) Str::uuid(), [GetSupplierOptionsTool::NAME]);
    } else {
        $cases = Mockery::mock(ProblemCaseRepositoryInterface::class);
        $expectation = $cases->shouldReceive('findForCurrentEvaluation');
        if (! $expectation instanceof CompositeExpectation) {
            throw new RuntimeException('Expected concrete repository expectation.');
        }
        $expectation->__call('andThrow', [new RuntimeException('SQL raw_secret stack trace')]);
        app()->instance(ProblemCaseRepositoryInterface::class, $cases);
    }
    $result = app(GetSupplierOptionsTool::class)->execute($context, supplierToolInput($context));
    expect($result['code'])->toBe($kind === 'missing' ? 'CASE_NOT_FOUND' : 'SUPPLIER_OPTIONS_FAILED')
        ->and(json_encode($result))->not->toContain('raw_secret')
        ->and(Activity::query()->latest('id')->firstOrFail()->properties->toJson())->not->toContain('raw_secret');
})->with(['missing', 'failure']);

it('cannot enter the restricted supplier path without an active concrete observation', function (): void {
    $read = app(MaterialShortageSupplierOptionsRead::class);
    expect(fn () => app(SupplierOptionService::class)->evaluateMaterialShortageObservation($read))
        ->toThrow(SupplierOptionEvaluationException::class, 'SUPPLIER_OPTION_MATERIAL_SHORTAGE_SCOPE_REQUIRED');
    [, , , , $context] = supplierToolFixture();
    $read->observe($context->problemCaseId);
    expect(fn () => app(SupplierOptionService::class)->evaluateMaterialShortageObservation($read))
        ->toThrow(SupplierOptionEvaluationException::class, 'SUPPLIER_OPTION_MATERIAL_SHORTAGE_SCOPE_REQUIRED');
});

it('ends the read transaction before audit and fails closed without returning a supplier payload if audit fails', function (bool $fail): void {
    [, , , , $context] = supplierToolFixture();
    $audit = Mockery::mock(AuditLogService::class);
    $expectation = $audit->shouldReceive('log');
    if (! $expectation instanceof CompositeExpectation) {
        throw new RuntimeException('Expected concrete audit expectation.');
    }
    $expectation->__call('once', []);
    $expectation->__call('andReturnUsing', [function () use ($fail): void {
        expect(DB::transactionLevel())->toBe(0)->and(DB::connection()->getPdo()->inTransaction())->toBeFalse();
        if ($fail) {
            throw new RuntimeException('raw_secret audit failure');
        }
    }]);
    app()->instance(AuditLogService::class, $audit);
    if ($fail) {
        expect(fn () => app(GetSupplierOptionsTool::class)->execute($context, supplierToolInput($context)))
            ->toThrow(RuntimeException::class, 'SUPPLIER_OPTIONS_TOOL_AUDIT_FAILED');
    } else {
        expect(app(GetSupplierOptionsTool::class)->execute($context, supplierToolInput($context))['status'])->toBe('success');
    }
})->with([false, true]);

it('projects only approved fields even when domain arrays acquire additional sensitive fields', function (): void {
    [, , , $item, $context] = supplierToolFixture();
    supplierToolSource($item, ['lead_time_days' => null, 'unit_price' => null, 'currency' => null]);
    $result = app(MaterialShortageSupplierOptionsRead::class)->observe($context->problemCaseId)->result;
    $option = $result->options[0];
    $extended = new SupplierOption(
        [...$option->supplier, 'tax_number' => 'raw_secret'], $option->relationship,
        $option->eligibility, $option->requirementFit, [...$option->ordering, 'internal' => 'raw_secret'],
        $option->delivery, $option->commercial, $option->dataQuality,
    );
    $projection = app(SupplierOptionsToolProjection::class)->project(new SupplierOptionResult(
        [...$result->item, 'internal' => 'raw_secret'], $result->requirement, $result->evaluatedAt, [$extended],
    ));
    expect(json_encode($projection, JSON_THROW_ON_ERROR))->not->toContain('raw_secret')
        ->and($projection['options'][0]['delivery']['lead_time_days']['state'])->toBe('UNKNOWN')
        ->and($projection['options'][0]['ordering']['minimum_order_quantity']['state'])->toBe('NOT_APPLICABLE')
        ->and($projection['options'][0]['ordering']['excess_quantity'])->toBe(['state' => 'KNOWN', 'value' => '0.000', 'reason' => null]);
});

it('keeps case netting and supplier reads in one real MySQL snapshot', function (): void {
    if (DB::getDriverName() !== 'mysql') {
        Assert::markTestSkipped('MYSQL_RUNTIME_NOT_PROVEN: requires the dedicated guarded MySQL test database.');
    }
    [, $case, $requirement, $item, $context] = supplierToolFixture();
    $balance = StockBalance::factory()->create(['item_id' => $item->id, 'quantity' => '2.000']);
    $source = supplierToolSource($item);
    config(['database.connections.supplier_tool_writer' => config('database.connections.'.DB::getDefaultConnection())]);
    $writer = DB::connection('supplier_tool_writer');
    app()->instance(ProblemCaseRepositoryInterface::class, new class($writer, $balance->id, $source->id) extends ProblemCaseRepository
    {
        private bool $changed = false;

        public function __construct(private readonly Connection $writer, private readonly int $balanceId, private readonly int $sourceId) {}

        public function findForCurrentEvaluation(string $problemCaseId): ProblemCase
        {
            $case = parent::findForCurrentEvaluation($problemCaseId);
            if (! $this->changed) {
                $this->changed = true;
                $this->writer->table('stock_balances')->where('id', $this->balanceId)->update(['quantity' => '99.000']);
                $this->writer->table('item_suppliers')->where('id', $this->sourceId)->update(['is_approved' => false]);
            }

            return $case;
        }
    });
    try {
        $tool = app(GetSupplierOptionsTool::class);
        $result = $tool->execute($context, supplierToolInput($context));
        expect($result['status'])->toBe('success')
            ->and($result['data']['requirement']['required_quantity'])->toBe('10.125')
            ->and($result['data']['options'][0]['eligibility']['is_eligible'])->toBeTrue()
            ->and(DB::transactionLevel())->toBe(0);
        expect($tool->execute($context, supplierToolInput($context))['code'])->toBe('NO_CURRENT_SHORTAGE');
    } finally {
        DB::purge('supplier_tool_writer');
    }
});

it('preserves caller transactions and never attempts audit inside them', function (bool $tracked): void {
    [, , , , $context] = supplierToolFixture();
    $audit = Mockery::mock(AuditLogService::class);
    $audit->shouldNotReceive('log');
    app()->instance(AuditLogService::class, $audit);
    $pdo = DB::connection()->getPdo();
    if ($tracked) {
        DB::beginTransaction();
    } else {
        $pdo->beginTransaction();
    }
    try {
        expect(fn () => app(GetSupplierOptionsTool::class)->execute($context, supplierToolInput($context)))
            ->toThrow(RuntimeException::class, 'SUPPLIER_OPTIONS_TOOL_CALLER_TRANSACTION_UNVERIFIED');
        expect($pdo->inTransaction())->toBeTrue()->and(DB::transactionLevel())->toBe($tracked ? 1 : 0);
    } finally {
        if ($tracked) {
            DB::rollBack();
        } else {
            $pdo->rollBack();
        }
    }
})->with([false, true]);

it('retains super-admin user semantics while still applying fresh domain gates', function (): void {
    [$user, , , $item, $context] = supplierToolFixture();
    $user->revokePermissionTo(['inventory.view', 'item-suppliers.view']);
    $user->assignRole('super-admin');
    $tool = app(GetSupplierOptionsTool::class);
    expect($tool->execute($context, supplierToolInput($context))['status'])->toBe('success');
    StockBalance::factory()->create(['item_id' => $item->id, 'quantity' => '20.000']);
    expect($tool->execute($context, supplierToolInput($context))['code'])->toBe('NO_CURRENT_SHORTAGE');
});

it('maps the full competing scope and preserves a missing required date and application business day', function (): void {
    [, , $requirement, $item, $context] = supplierToolFixture();
    $requirement->update(['required_at' => null]);
    MaterialRequirement::factory()->create([
        'customer_order_item_id' => $requirement->customer_order_item_id,
        'required_item_id' => $item->id, 'required_quantity' => '3.000',
        'required_at' => '2026-10-09', 'unit' => 'kg',
    ]);
    StockBalance::factory()->create(['item_id' => $item->id, 'quantity' => '8.000']);
    config(['app.timezone' => 'Europe/Budapest']);
    Carbon::setTestNow('2026-10-06T23:30:00Z');
    $result = app(GetSupplierOptionsTool::class)->execute($context, supplierToolInput($context));
    expect($result['status'])->toBe('success')
        ->and($result['data']['requirement']['required_quantity'])->toBe('7.125')
        ->and($result['data']['requirement']['required_date'])->toBe(['state' => 'UNKNOWN', 'value' => null, 'reason' => 'MISSING_REQUIRED_DATE'])
        ->and($result['data']['evaluation_date'])->toBe('2026-10-07')
        ->and($result['source_observed_at'])->toBe('2026-10-07T01:30:00+02:00');
});

it('observes coherent SQLite source netting and supplier state across an independently committed change', function (): void {
    if (DB::getDriverName() !== 'sqlite') {
        Assert::markTestSkipped('SQLite-only composition test; not MySQL runtime evidence.');
    }
    $original = DB::getDefaultConnection();
    $directory = storage_path('framework/testing');
    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }
    $path = $directory.'/supplier-tool-'.Str::uuid().'.sqlite';
    $connection = DB::connection();
    $originalPdo = $connection->getPdo();
    $originalConfiguration = config('database.connections.'.$original);
    // Clone only the already-guarded SQLite fixture DB, keeping the default name
    // approved by TestEnvironmentGuard. No migration/seed reaches another DB.
    DB::statement('VACUUM INTO ?', [$path]);
    $configuration = [...$originalConfiguration, 'database' => $path];
    config(['database.connections.'.$original => $configuration,
        'database.connections.supplier_tool_sqlite_writer' => $configuration]);
    $filePdo = new PDO('sqlite:'.$path);
    $connection->setPdo($filePdo)->setReadPdo($filePdo);
    try {
        // Guard-approved test path, separate from development/production databases.
        TestEnvironmentGuard::assertLaravelConfiguration(app('config'));
        DB::statement('PRAGMA journal_mode = WAL');
        [, , $requirement, $item, $context] = supplierToolFixture();
        $balance = StockBalance::factory()->create(['item_id' => $item->id, 'quantity' => '2.000']);
        $source = supplierToolSource($item);
        $writer = DB::connection('supplier_tool_sqlite_writer');
        app()->instance(ProblemCaseRepositoryInterface::class, new class($writer, $balance->id, $source->id, $requirement->customerOrderItem->customer_order_id) extends ProblemCaseRepository
        {
            private bool $changed = false;

            public function __construct(private readonly Connection $writer, private readonly int $balanceId,
                private readonly int $sourceId, private readonly int $orderId) {}

            public function findForCurrentEvaluation(string $problemCaseId): ProblemCase
            {
                $case = parent::findForCurrentEvaluation($problemCaseId);
                if (! $this->changed) {
                    $this->changed = true;
                    $this->writer->table('customer_orders')->where('id', $this->orderId)->update(['status' => 'cancelled']);
                    $this->writer->table('stock_balances')->where('id', $this->balanceId)->update(['quantity' => '99.000']);
                    $this->writer->table('item_suppliers')->where('id', $this->sourceId)->update(['is_approved' => false]);
                }

                return $case;
            }
        });
        $tool = app(GetSupplierOptionsTool::class);
        $result = $tool->execute($context, supplierToolInput($context));
        expect($result['status'])->toBe('success')
            ->and($result['data']['requirement']['required_quantity'])->toBe('10.125')
            ->and($result['data']['options'][0]['eligibility']['is_eligible'])->toBeTrue()
            ->and($tool->execute($context, supplierToolInput($context))['code'])->toBe('SOURCE_INVALID')
            ->and(DB::transactionLevel())->toBe(0);
    } finally {
        DB::purge('supplier_tool_sqlite_writer');
        $connection->setPdo($originalPdo)->setReadPdo($originalPdo);
        config(['database.connections.'.$original => $originalConfiguration]);
        $filePdo = null;
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        gc_collect_cycles();
        unlink($path);
    }
});
