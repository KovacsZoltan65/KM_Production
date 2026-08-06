<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Quality gate module and risk matrix
|--------------------------------------------------------------------------
|
| Keep module suites and file-to-module rules in this single file. Paths are
| repository-relative and use forward slashes. `*` matches one path segment;
| `**` matches any number of segments.
|
*/

return [
    'timeouts' => [
        'default' => 120,
        'backend' => 600,
        'frontend' => 240,
        'playwright' => 900,
        'build' => 180,
        'phpstan' => 240,
    ],

    'modules' => [
        'admin' => [
            'backend' => [
                'tests/Feature/AdminFoundationTest.php',
                'tests/Feature/AdminIndexPartialReloadTest.php',
                'tests/Feature/RouteParameterAuthorizationTest.php',
            ],
            'frontend' => [
                'tests/frontend/components/AdminControls.test.js',
                'tests/frontend/components/AdminCrudField.test.js',
                'tests/frontend/components/AdminCrudPage.test.js',
                'tests/frontend/pages/AdminIndexPartialReload.test.js',
            ],
            'playwright' => ['tests/e2e/admin'],
            'related_modules' => [],
            'build' => true,
        ],
        'authentication' => [
            'backend' => [
                'tests/Feature/AuthenticationPermissionFoundationTest.php',
                'tests/Feature/RouteParameterAuthorizationTest.php',
                'tests/Feature/SystemHardeningTest.php',
            ],
            'frontend' => [
                'tests/frontend/pages/HeadManagement.test.js',
                'tests/frontend/utils/navigation.test.js',
                'tests/frontend/utils/routes.test.js',
            ],
            'playwright' => [
                'tests/e2e/auth',
                'tests/e2e/navigation/permissions.spec.js',
            ],
            'related_modules' => [],
            'build' => true,
        ],
        'bom' => [
            'backend' => [
                'tests/Feature/ProductionStructureTest.php',
                'tests/Feature/ProductionMasterUiTest.php',
            ],
            'frontend' => [],
            'playwright' => [],
            'related_modules' => ['production'],
            'build' => true,
        ],
        'capacity' => [
            'backend' => ['tests/Feature/CapacityPlanningTest.php'],
            'frontend' => [
                'tests/frontend/pages/CapacitySchedule.test.js',
                'tests/frontend/components/DashboardComponents.test.js',
            ],
            'playwright' => [],
            'related_modules' => ['production-planning'],
            'build' => true,
        ],
        'code-generation' => [
            'backend' => ['tests/Feature/CodeGenerationTest.php'],
            'frontend' => [],
            'playwright' => [],
            'related_modules' => [],
            'build' => false,
        ],
        'customer-orders' => [
            'backend' => [
                'tests/Feature/CustomerOrdersUiTest.php',
                'tests/Feature/OrderProductionTest.php',
            ],
            'frontend' => ['tests/frontend/components/WorkflowComponents.test.js'],
            'playwright' => ['tests/e2e/workflows/customer-orders.spec.js'],
            'related_modules' => ['production-planning'],
            'build' => true,
        ],
        'documents' => [
            'backend' => [
                'tests/Feature/DocumentIntelligencePipelineTest.php',
                'tests/Feature/DocumentManagementUiTest.php',
                'tests/Feature/DocumentTest.php',
            ],
            'frontend' => [
                'tests/frontend/components/DocumentComponents.test.js',
                'tests/frontend/components/DocumentUploadForm.test.js',
                'tests/frontend/pages/DocumentIndex.test.js',
            ],
            'playwright' => ['tests/e2e/documents'],
            'related_modules' => [],
            'build' => true,
        ],
        'inventory' => [
            'backend' => [
                'tests/Feature/Admin/ItemSerialNumberRequirementTest.php',
                'tests/Feature/BusinessCacheInvalidationTest.php',
                'tests/Feature/InventoryManagementUiTest.php',
                'tests/Feature/InventoryTest.php',
                'tests/Feature/ItemMasterDataTest.php',
            ],
            'frontend' => [
                'tests/frontend/pages/StockReservations.test.js',
                'tests/frontend/components/UnitSelect.test.js',
            ],
            'playwright' => ['tests/e2e/inventory'],
            'related_modules' => [],
            'build' => true,
        ],
        'manufacturing-intelligence' => [
            'backend' => [
                'tests/Feature/ManufacturingIntelligenceTest.php',
                'tests/Feature/PythonAiEngineTest.php',
            ],
            'frontend' => [
                'tests/frontend/components/IntelligenceComponents.test.js',
                'tests/frontend/components/StatusDonutChart.test.js',
                'tests/frontend/utils/charts.test.js',
            ],
            'playwright' => [],
            'related_modules' => ['reports'],
            'build' => true,
        ],
        'master-data' => [
            'backend' => [
                'tests/Feature/BusinessPartnersUiTest.php',
                'tests/Feature/ItemMasterDataTest.php',
                'tests/Feature/ProductionMasterDataTest.php',
                'tests/Feature/ProductionMasterUiTest.php',
            ],
            'frontend' => [
                'tests/frontend/pages/EmployeeIndex.test.js',
                'tests/frontend/components/UnitSelect.test.js',
            ],
            'playwright' => ['tests/e2e/admin/employees-partial-reload.spec.js'],
            'related_modules' => ['admin'],
            'build' => true,
        ],
        'procurement' => [
            'backend' => [
                'tests/Feature/ProcurementManagementUiTest.php',
                'tests/Feature/ProcurementTest.php',
                'tests/Feature/BusinessPartnersUiTest.php',
                'tests/Feature/AdminIndexPartialReloadTest.php',
                'tests/Feature/BusinessCacheInvalidationTest.php',
            ],
            'frontend' => [
                'tests/frontend/pages/GoodsReceiptShow.test.js',
                'tests/frontend/pages/PurchaseOrderShow.test.js',
                'tests/frontend/pages/PurchaseRequisitionShow.test.js',
                'tests/frontend/pages/AdminIndexPartialReload.test.js',
            ],
            'playwright' => ['tests/e2e/procurement'],
            'related_modules' => ['inventory'],
            'build' => true,
        ],
        'production' => [
            'backend' => [
                'tests/Feature/OrderProductionTest.php',
                'tests/Feature/ProductionExecutionTest.php',
                'tests/Feature/ProductionExecutionUiTest.php',
                'tests/Feature/ProductionStructureTest.php',
            ],
            'frontend' => ['tests/frontend/components/WorkflowComponents.test.js'],
            'playwright' => [
                'tests/e2e/workflows/production-tasks-quality.spec.js',
                'tests/e2e/workflows/goods-receipts.spec.js',
            ],
            'related_modules' => ['quality'],
            'build' => true,
        ],
        'production-planning' => [
            'backend' => [
                'tests/Feature/CapacityPlanningTest.php',
                'tests/Feature/ProductionPlansUiTest.php',
            ],
            'frontend' => [
                'tests/frontend/pages/CapacitySchedule.test.js',
                'tests/frontend/components/WorkflowComponents.test.js',
            ],
            'playwright' => ['tests/e2e/workflows/production-plans.spec.js'],
            'related_modules' => [],
            'build' => true,
        ],
        'quality' => [
            'backend' => ['tests/Feature/ProductionExecutionTest.php'],
            'frontend' => ['tests/frontend/components/WorkflowComponents.test.js'],
            'playwright' => ['tests/e2e/workflows/production-tasks-quality.spec.js'],
            'related_modules' => [],
            'build' => true,
        ],
        'reports' => [
            'backend' => ['tests/Feature/ReportingAnalyticsTest.php'],
            'frontend' => [
                'tests/frontend/components/DashboardComponents.test.js',
                'tests/frontend/utils/charts.test.js',
            ],
            'playwright' => [],
            'related_modules' => [],
            'build' => true,
        ],
    ],

    'integration' => [
        'backend' => [
            'tests/Feature/AdminFoundationTest.php',
            'tests/Feature/AdminIndexPartialReloadTest.php',
            'tests/Feature/AuthenticationPermissionFoundationTest.php',
            'tests/Feature/BusinessCacheInvalidationTest.php',
            'tests/Feature/RecordStateActivityLoggingTest.php',
            'tests/Feature/RouteParameterAuthorizationTest.php',
            'tests/Feature/SystemHardeningTest.php',
        ],
        'playwright' => ['tests/e2e/admin'],
    ],

    'full_risk_patterns' => [
        'composer.json',
        'composer.lock',
        'package.json',
        'package-lock.json',
        'phpunit.xml',
        'phpunit.xml.dist',
        'playwright.config.*',
        'vite.config.*',
        'tests/Pest.php',
        'database/migrations/**',
        'scripts/e2e-*.js',
        'scripts/prepare-e2e.js',
        'config/quality-gates.php',
        'tools/quality-gate.php',
        'tools/quality-gate-process.ps1',
        'app/Support/QualityGate/**',
        'tests/Unit/QualityGate/**',
        '.prettierignore',
        '.prettierrc.json',
    ],

    'integration_risk_patterns' => [
        'routes/**',
        'app/Http/Middleware/**',
        'app/Providers/**',
        'app/Policies/**',
        'app/Models/Traits/**',
        'app/Repositories/Admin/AbstractAdminRepository.php',
        'app/Repositories/Contracts/AdminRepositoryInterface.php',
        'app/Services/Admin/AbstractAdminService.php',
        'app/Services/Admin/CodeAwareAdminService.php',
        'app/Services/BusinessCacheInvalidator.php',
        'app/Support/Cache/**',
        'database/seeders/RolesAndPermissionsSeeder.php',
        'resources/js/Components/Admin/AdminCrudPage.vue',
        'resources/js/Components/Admin/**',
        'resources/js/Components/Common/**',
        'resources/js/Layouts/**',
        'resources/js/app.js',
        'resources/js/bootstrap.js',
        'resources/js/Utils/**',
        'resources/js/utils/**',
        'resources/js/ziggy.js',
    ],

    'rules' => [
        [
            'name' => 'goods receipt workflow',
            'patterns' => [
                '**/GoodsReceipt*.php',
                '**/GoodsReceipts/**',
                'tests/**/goods-receipts*.js',
                'tests/**/GoodsReceipt*.js',
            ],
            'modules' => ['procurement', 'inventory'],
            'playwright' => true,
        ],
        [
            'name' => 'purchase order workflow',
            'patterns' => ['**/PurchaseOrder*.php', '**/PurchaseOrders/**', 'tests/**/purchase-orders*.js', 'tests/**/PurchaseOrder*.js'],
            'modules' => ['procurement'],
            'playwright' => true,
        ],
        [
            'name' => 'purchase requisition workflow',
            'patterns' => ['**/PurchaseRequisition*.php', '**/PurchaseRequisitions/**', 'tests/**/purchase-requisitions*.js', 'tests/**/PurchaseRequisition*.js'],
            'modules' => ['procurement'],
            'playwright' => true,
        ],
        [
            'name' => 'procurement dashboard and supplier',
            'patterns' => ['**/Procurement*.php', '**/Supplier*.php', '**/Procurement/**', '**/Suppliers/**', 'tests/**/Procurement*.php'],
            'modules' => ['procurement'],
        ],
        [
            'name' => 'inventory and stock',
            'patterns' => ['**/Inventory*.php', '**/Stock*.php', '**/MaterialRequirement*.php', '**/Inventory/**', '**/Stock*/**', 'tests/**/Inventory*.php'],
            'modules' => ['inventory'],
            'playwright' => true,
        ],
        [
            'name' => 'BOM and production structure',
            'patterns' => ['**/Bom*.php', '**/Boms/**', 'tests/**/ProductionStructureTest.php'],
            'modules' => ['bom', 'production'],
        ],
        [
            'name' => 'capacity planning',
            'patterns' => ['**/Capacity*.php', '**/Capacity/**', 'tests/**/Capacity*.js'],
            'modules' => ['capacity', 'production-planning'],
        ],
        [
            'name' => 'production planning',
            'patterns' => ['**/ProductionPlan*.php', '**/ProductionPlans/**', 'tests/**/production-plans*.js'],
            'modules' => ['production-planning'],
            'playwright' => true,
        ],
        [
            'name' => 'production execution',
            'patterns' => ['**/ProductionTask*.php', '**/ShopFloor*.php', '**/ProductionTasks/**', '**/ShopFloor/**', 'tests/**/production-tasks*.js'],
            'modules' => ['production'],
            'playwright' => true,
        ],
        [
            'name' => 'quality control',
            'patterns' => ['**/QualityCheck*.php', '**/QualityTrend*.php', '**/Quality/**', '**/QualityCheck*.vue', '**/QualityTrends.vue'],
            'modules' => ['quality', 'production'],
            'playwright' => true,
        ],
        [
            'name' => 'documents',
            'patterns' => ['**/Document*.php', '**/Documents/**', '**/Document*.vue', 'tests/e2e/documents/**'],
            'modules' => ['documents'],
            'playwright' => true,
        ],
        [
            'name' => 'reports',
            'patterns' => ['**/Report*.php', '**/Reports/**', '**/Report*.vue'],
            'modules' => ['reports'],
        ],
        [
            'name' => 'manufacturing intelligence',
            'patterns' => ['**/ManufacturingIntelligence*.php', '**/Intelligence/**', '**/Intelligence*.vue', '**/PythonAiEngine*.php'],
            'modules' => ['manufacturing-intelligence'],
        ],
        [
            'name' => 'customer orders',
            'patterns' => ['**/CustomerOrder*.php', '**/CustomerOrders/**', 'tests/**/customer-orders*.js'],
            'modules' => ['customer-orders'],
            'playwright' => true,
        ],
        [
            'name' => 'master data',
            'patterns' => ['**/Item*.php', '**/Employee*.php', '**/FactoryUnit*.php', '**/Location*.php', '**/OperationType*.php', '**/OperationSequence*.php', '**/ProfessionalRole*.php', '**/CustomerAdmin*.php', '**/CustomerController.php', '**/CustomersSeeder.php', '**/Items/**', '**/Employees/**', '**/FactoryUnits/**', '**/Locations/**', '**/OperationTypes/**', '**/OperationSequences/**', '**/ProfessionalRoles/**', '**/Customers/**'],
            'modules' => ['master-data'],
        ],
        [
            'name' => 'authentication and permissions',
            'patterns' => ['app/Http/Controllers/Auth/**', 'app/Http/Requests/Auth/**', '**/Permission*.php', '**/Role*.php', '**/Permissions/**', '**/Roles/**', 'resources/js/Pages/Auth/**', 'tests/e2e/auth/**', 'tests/e2e/navigation/permissions.spec.js'],
            'modules' => ['authentication'],
            'playwright' => true,
        ],
        [
            'name' => 'code generation',
            'patterns' => ['**/CodeGeneration/**', '**/CodeGenerator*.php', '**/CodeCreation*.php', '**/CodeSequence*.php', 'tests/**/CodeGenerationTest.php'],
            'modules' => ['code-generation'],
        ],
        [
            'name' => 'admin shared regression',
            'patterns' => ['tests/Feature/Admin*.php', 'tests/frontend/**/Admin*.test.js', 'tests/e2e/admin/**'],
            'modules' => ['admin'],
        ],
    ],
];
