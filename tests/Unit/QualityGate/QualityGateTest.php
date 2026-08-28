<?php

use App\Support\QualityGate\AffectedSelector;
use App\Support\QualityGate\GateCommand;
use App\Support\QualityGate\GatePlan;
use App\Support\QualityGate\GatePlanner;
use App\Support\QualityGate\GateRunner;
use App\Support\QualityGate\ModuleMatrix;
use App\Support\QualityGate\PathMatcher;
use App\Support\QualityGate\SelectionResult;
use App\Support\QualityGate\SystemProcessExecutor;
use Tests\Support\FakeQualityGateExecutor;

function qualityGateConfiguration(): array
{
    return require dirname(__DIR__, 3).'/config/quality-gates.php';
}

it('lets the gate runner own command timeouts instead of Composer', function () {
    $composer = json_decode(
        file_get_contents(dirname(__DIR__, 3).'/composer.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    foreach (['qa:fast', 'qa:affected', 'qa:module', 'qa:integration', 'qa:full'] as $script) {
        expect($composer['scripts'][$script])
            ->toBeArray()
            ->and($composer['scripts'][$script][0])
            ->toBe('Composer\\Config::disableProcessTimeout');
    }
});

it('caps full frontend gates to one worker after the backend regression', function () {
    $planner = new GatePlanner(new ModuleMatrix(qualityGateConfiguration()));

    foreach ([$planner->integration(), $planner->full()] as $plan) {
        $frontend = collect($plan->commands)
            ->first(fn (GateCommand $command): bool => str_starts_with($command->id, 'frontend-'));

        expect($frontend)->toBeInstanceOf(GateCommand::class)
            ->and($frontend->arguments)->toContain('--maxWorkers=1');
    }
});

it('maps a goods receipt service to procurement and inventory with workflow E2E', function () {
    $selection = (new AffectedSelector(qualityGateConfiguration()))
        ->select(['app/Services/Admin/GoodsReceiptService.php']);

    expect($selection->modules)
        ->toBe(['inventory', 'procurement'])
        ->and($selection->requiresPlaywright)->toBeTrue()
        ->and($selection->requiredLevel)->toBe('fast');
});

it('routes MRP foundation files to the focused backend and frontend suites', function (string $path, string $expectedTest): void {
    $configuration = qualityGateConfiguration();
    $selection = (new AffectedSelector($configuration))->select([$path]);
    $plan = (new GatePlanner(new ModuleMatrix($configuration)))->fast($selection);
    $arguments = collect($plan->commands)->flatMap(fn (GateCommand $command): array => $command->arguments);

    expect($selection->modules)->toContain('mrp')
        ->and($arguments)->toContain($expectedTest);
})->with([
    'supply proposal service' => ['app/Services/Admin/SupplyProposalService.php', 'tests/Feature/SupplyProposalTest.php'],
    'item supplier service' => ['app/Services/Admin/ItemSupplierService.php', 'tests/Feature/ItemSupplierTest.php'],
    'material requirement repository' => ['app/Repositories/Admin/MaterialRequirementRepository.php', 'tests/Feature/InventoryManagementUiTest.php'],
    'supply proposal frontend' => ['resources/js/Pages/Admin/SupplyProposals/Index.vue', 'tests/frontend/pages/SupplyProposalIndex.test.js'],
    'item supplier frontend' => ['resources/js/Pages/Admin/ItemSuppliers/Index.vue', 'tests/frontend/pages/ItemSupplierIndex.test.js'],
    'netting service' => ['app/Services/Admin/MaterialRequirementNettingService.php', 'tests/Feature/MaterialRequirementNettingTest.php'],
    'pegging service' => ['app/Services/Admin/MaterialRequirementPeggingService.php', 'tests/Feature/MaterialRequirementPeggingTest.php'],
    'requisition consolidation service' => ['app/Services/Admin/PurchaseRequisitionConsolidationService.php', 'tests/Feature/PurchaseRequisitionConsolidationTest.php'],
    'requisition consolidation request' => ['app/Http/Requests/Admin/ConsolidatePurchaseRequisitionsRequest.php', 'tests/Feature/PurchaseRequisitionConsolidationTest.php'],
    'proposal source model' => ['app/Models/PurchaseRequisitionItemProposalSource.php', 'tests/Feature/PurchaseRequisitionConsolidationTest.php'],
    'supplier selection service' => ['app/Services/Admin/SupplierSelectionService.php', 'tests/Feature/SupplierSelectionTest.php'],
    'supplier selection request' => ['app/Http/Requests/Admin/SelectPurchaseRequisitionSupplierRequest.php', 'tests/Feature/SupplierSelectionTest.php'],
    'replenishment service' => ['app/Services/Admin/PurchaseRequisitionReplenishmentService.php', 'tests/Feature/PurchaseRequisitionReplenishmentTest.php'],
    'replenishment result' => ['app/Support/Procurement/ReplenishmentQuantityResult.php', 'tests/Feature/PurchaseRequisitionReplenishmentTest.php'],
    'replenishment request' => ['app/Http/Requests/Admin/CalculatePurchaseRequisitionReplenishmentRequest.php', 'tests/Feature/PurchaseRequisitionReplenishmentTest.php'],
    'execution readiness service' => ['app/Services/Admin/PurchaseRequisitionExecutionReadinessService.php', 'tests/Feature/PurchaseRequisitionExecutionReadinessTest.php'],
    'execution readiness result' => ['app/Support/Procurement/PurchaseRequisitionExecutionReadinessResult.php', 'tests/Feature/PurchaseRequisitionExecutionReadinessTest.php'],
    'execution readiness reason' => ['app/Enums/PurchaseRequisitionExecutionReadinessReason.php', 'tests/Feature/PurchaseRequisitionExecutionReadinessTest.php'],
    'PO generation regression' => ['tests/Feature/PurchaseOrderGenerationTest.php', 'tests/Feature/PurchaseOrderGenerationTest.php'],
    'PO generation request' => ['app/Http/Requests/Admin/GeneratePurchaseOrderRequest.php', 'tests/Feature/PurchaseOrderGenerationTest.php'],
    'legacy PR generation service' => ['app/Services/Admin/PurchaseRequisitionService.php', 'tests/Feature/PurchaseOrderGenerationTest.php'],
    'PO execution model' => ['app/Models/PurchaseOrder.php', 'tests/Feature/PurchaseOrderGenerationTest.php'],
    'supplier selection frontend' => ['resources/js/Pages/Admin/PurchaseRequisitions/Show.vue', 'tests/frontend/pages/PurchaseRequisitionShow.test.js'],
]);

it('maps a BOM Vue page to BOM and production modules', function () {
    $selection = (new AffectedSelector(qualityGateConfiguration()))
        ->select(['resources/js/Pages/Admin/Boms/Index.vue']);

    expect($selection->modules)
        ->toBe(['bom', 'production'])
        ->and($selection->requiresI18n)->toBeTrue()
        ->and($selection->requiresBuild)->toBeTrue();
});

it('raises migrations to the full gate', function () {
    $selection = (new AffectedSelector(qualityGateConfiguration()))
        ->select(['database/migrations/2026_08_06_000000_example.php']);

    expect($selection->requiredLevel)->toBe('full');
});

it('preserves dotfile names while normalizing Windows paths', function () {
    expect(PathMatcher::normalize('.\\.prettierrc.json'))->toBe('.prettierrc.json')
        ->and(PathMatcher::normalize('.kiro\\checklists\\quality-gates.md'))
        ->toBe('.kiro/checklists/quality-gates.md');
});

it('raises AdminCrudPage changes to the integration gate', function () {
    $selection = (new AffectedSelector(qualityGateConfiguration()))
        ->select(['resources/js/Components/Admin/AdminCrudPage.vue']);

    expect($selection->requiredLevel)->toBe('integration');
});

it('selects i18n for translation changes', function () {
    $selection = (new AffectedSelector(qualityGateConfiguration()))
        ->select(['lang/en.json']);

    expect($selection->requiresI18n)->toBeTrue()
        ->and($selection->requiredLevel)->toBe('fast');
});

it('uses the safe integration fallback for an unknown file', function () {
    $selection = (new AffectedSelector(qualityGateConfiguration()))
        ->select(['unclassified/runtime/bridge.xyz']);

    expect($selection->requiredLevel)->toBe('integration')
        ->and($selection->explanations['unclassified/runtime/bridge.xyz'])
        ->toContain('no safe module rule -> integration fallback');
});

it('does not execute child commands during dry-run', function () {
    $executor = new FakeQualityGateExecutor;
    $plan = new GatePlan('fast', [], [], [
        new GateCommand('one', 'One', [PHP_BINARY, '-v']),
    ]);

    $exitCode = (new GateRunner($executor, ['default' => 10]))->run($plan, true);

    expect($exitCode)->toBe(0)
        ->and($executor->executed)->toBe([]);
});

it('rejects an unknown module and lists it as unavailable', function () {
    $configuration = qualityGateConfiguration();
    $planner = new GatePlanner(new ModuleMatrix($configuration));

    expect(fn () => $planner->module('not-a-module'))
        ->toThrow(InvalidArgumentException::class, 'Unknown quality gate module');
});

it('returns a non-zero exit code when a child command fails', function () {
    $executor = new FakeQualityGateExecutor(7);
    $plan = new GatePlan('fast', [], [], [
        new GateCommand('failure', 'Intentional failure', [PHP_BINARY, '-r', 'exit(7);']),
    ]);

    expect((new GateRunner($executor, ['default' => 10]))->run($plan))->toBe(7);
});

it('deduplicates test files and terminates related-module cycles', function () {
    $configuration = [
        'modules' => [
            'alpha' => [
                'backend' => ['tests/Feature/SharedTest.php'],
                'frontend' => [],
                'playwright' => [],
                'related_modules' => ['beta'],
                'build' => false,
            ],
            'beta' => [
                'backend' => ['tests/Feature/SharedTest.php'],
                'frontend' => [],
                'playwright' => [],
                'related_modules' => ['alpha'],
                'build' => false,
            ],
        ],
        'integration' => ['backend' => [], 'playwright' => []],
    ];
    $matrix = new ModuleMatrix($configuration);
    $plan = (new GatePlanner($matrix))->module('alpha');
    $backendCommand = collect($plan->commands)->firstWhere('id', 'backend-module');

    expect($plan->modules)->toBe(['alpha', 'beta'])
        ->and($backendCommand)->toBeInstanceOf(GateCommand::class)
        ->and(array_count_values($backendCommand->arguments)['tests/Feature/SharedTest.php'])->toBe(1);
});

it('times out and closes its process instead of waiting for completion', function () {
    $marker = tempnam(sys_get_temp_dir(), 'quality-gate-timeout-');
    expect($marker)->toBeString();
    unlink($marker);
    $command = new GateCommand('timeout', 'Timeout fixture', [
        PHP_BINARY,
        '-r',
        'sleep(30); file_put_contents($argv[1], "survived");',
        $marker,
    ]);

    try {
        $result = (new SystemProcessExecutor)->execute($command, 1);
        usleep(500_000);

        expect($result->timedOut)->toBeTrue()
            ->and($result->exitCode)->toBe(124)
            ->and($result->processId)->toBeInt()
            ->and(file_exists($marker))->toBeFalse();
    } finally {
        if (file_exists($marker)) {
            unlink($marker);
        }
    }
});

it('keeps one command per command id in a generated plan', function () {
    $configuration = qualityGateConfiguration();
    $planner = new GatePlanner(new ModuleMatrix($configuration));
    $selection = new SelectionResult(['resources/js/Pages/Admin/GoodsReceipts/Show.vue']);
    $selection->modules = ['procurement'];
    $selection->requiresPlaywright = true;
    $selection->requiresBuild = true;
    $plan = $planner->fast($selection);
    $ids = array_map(static fn (GateCommand $command): string => $command->id, $plan->commands);

    expect($ids)->toHaveCount(count(array_unique($ids)));
});

it('references only existing module and integration test paths', function () {
    $configuration = qualityGateConfiguration();
    $root = dirname(__DIR__, 3);

    foreach ($configuration['modules'] as $module => $settings) {
        foreach (['backend', 'frontend', 'playwright'] as $kind) {
            foreach ($settings[$kind] as $path) {
                $testPath = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);

                expect($testPath)->toBeFile();
            }
        }
    }

    foreach (['backend', 'playwright'] as $kind) {
        foreach ($configuration['integration'][$kind] as $path) {
            $testPath = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);

            expect($testPath)->toBeFile();
        }
    }
});
