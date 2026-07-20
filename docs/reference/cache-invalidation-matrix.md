# Cache-invalidation leltár és üzleti eseménymátrix

## Jelölések

- ✅ lefedett: explicit, szinkron, commit utáni invalidáció.
- 🟡 részleges: nincs minden külső write belépési pont bizonyítva.
- ⚪ nem érint cache-t: a jelenlegi implementáció nem cache-eli az eredményt.
- 🔵 package cache: támogatott külső API kezeli.

## Cache-leltár

| Cache | Domain / kulcsnév | Tulajdonos | Adatforrás | TTL | Invalidation |
| --- | --- | --- | --- | ---: | --- |
| Fő dashboard | `dashboard/summary` | `DashboardService` | CO, tervek, orders, tasks, PO, GR, MR, stock, document, QC | 60 s | minden érintett eseménycsalád |
| Vevőrendelés riport | `reports-customer-orders/summary:{filters}` | `ReportingService` | customer orders, customers | 60 s | customer order |
| Gyártási riport | `reports-production/summary` | `ReportingService` | production orders/tasks, items, steps, units | 60 s | production |
| Készletriport | `reports-inventory/summary` | `ReportingService` | balances, reservations, items, locations | 60 s | inventory |
| Beszerzési riport | `reports-procurement/summary` | `ReportingService` | suppliers, PO, GR | 60 s | procurement |
| Minőségriport | `reports-quality/summary` | `ReportingService` | production orders/tasks, QC | 60 s | quality/production |
| Shop-floor riport | `reports-shop-floor/summary` | `ReportingService` | employees, tasks | 60 s | workforce/production |
| Intelligence dashboard | `intelligence-dashboard/summary` | `ManufacturingIntelligenceService` | minden intelligence részdomain, lead-time | 5 perc | minden érintett család |
| Bottleneck | `intelligence-bottlenecks/analysis` | `BottleneckAnalysisService` | reservations, calendars, tasks, orders, units | 5 perc | production/capacity/workforce |
| Material forecast | `intelligence-material-forecast/forecast` | `MaterialForecastService` | stock, reservations, movements, items | 5 perc | inventory |
| Supplier performance | `intelligence-supplier-performance/analysis` | `SupplierPerformanceService` | suppliers, PO, GR | 5 perc | procurement |
| Quality trends | `intelligence-quality-trends/analysis` | `QualityTrendService` | QC, tasks, orders, items | 5 perc | quality/production |
| Production risks | `intelligence-risks/analysis` | `ProductionRiskService` | CO, MR, QC, production orders, PO | 5 perc | customer/production/inventory/procurement/quality/capacity |
| Recommendations | `intelligence-recommendations/analysis` | `ProcurementRecommendationService` | MR, PO items, CO, items | 5 perc | customer/inventory/procurement |
| Capacity dashboard | `capacity/dashboard` | `CapacityPlanningService` | capacity részcache-ek, lead-time | 60 s | production/capacity/workforce/customer |
| Factory unit load | `capacity/factory-units` | `CapacityPlanningService` | calendars, reservations, tasks | 60 s | production/capacity/workforce |
| Employee load | `capacity/employees` | `CapacityPlanningService` | employees, reservations, tasks | 60 s | production/capacity/workforce |
| Schedule rows | `capacity/schedule` | `CapacityPlanningService` | reservations, tasks, orders | 60 s | production/capacity |

Minden bejegyzés a default Laravel cache store-t használja. Production alapérték `database`, teszt alapérték `array`. A Prettus repository cache kikapcsolt.

## Üzleti eseménymátrix

| Domain | Üzleti esemény | Forrás | Érintett cache | Stratégia | Commit után | Teszt | Állapot |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Factory unit | create/update/delete | `FactoryUnitAdminService` | production, capacity, bottleneck, dashboard | generation | igen | backend + scope contract | ✅ |
| Location | create/update/delete | `LocationAdminService` | inventory, forecast | generation | igen | backend | ✅ |
| Employee | create/update/delete, role/unit | `EmployeeAdminService` | shop-floor, capacity, bottleneck | generation | igen | backend | ✅ |
| Professional role | create/update/delete | `ProfessionalRoleAdminService` | capacity/shop-floor | generation | igen | backend | ✅ |
| Item | create/update/delete | `ItemAdminService` | inventory, production, forecast, quality | generation | igen | backend | ✅ |
| BOM | create/update/delete, item change | `BomAdminService` | material/risk/recommendation inputs | generation | igen | backend | ✅ |
| Operation sequence | create/update/delete, step change | `OperationSequenceAdminService` | production, capacity, bottleneck | generation | igen | backend | ✅ |
| Customer | create/update/delete | `CustomerAdminService` | customer report, risks | generation | igen | backend | ✅ |
| Customer order | create/update/confirm/cancel/delete | `CustomerOrderService` | dashboard, CO report, risks, recommendations, capacity | generation | igen | empty-result integration | ✅ |
| Production plan/order | create/update/approve/generate/delete | `ProductionPlanService` | production, inventory, capacity, risks | generation | igen | production suite | ✅ |
| Production task | CRUD/generate/start/finish | `ProductionTaskService` | production, shop-floor, capacity, intelligence | generation | igen | production suite | ✅ |
| Quality check | accept/reject/rework | `QualityCheckService` | quality, production, trends, risks | generation | igen | quality/production suite | ✅ |
| Material requirement | calculate/recalculate | `MaterialRequirementService` | dashboard, inventory, forecast, risks, recommendations | generation | igen | critical chain | ✅ |
| Stock reservation | reserve/release | `StockReservationService` | inventory, forecast, risks, recommendations | generation | igen | rollback/scope contract | ✅ |
| Goods receipt | create/post/partial | `GoodsReceiptService` | inventory, procurement, supplier, forecast, risks, recommendations | generation | igen | goods receipt → inventory | ✅ |
| Purchase requisition | CRUD/approve/generate | `PurchaseRequisitionService` | procurement, recommendations, dashboard | generation | igen | procurement suite | ✅ |
| Purchase order | CRUD/approve/close | `PurchaseOrderService` | procurement, supplier, risk, recommendation | generation | igen | procurement suite | ✅ |
| Capacity | schedule/override | `SchedulingService` | capacity, bottleneck, risks | generation | igen | capacity suite | ✅ |
| Document | upload/update/delete/approve/current | `DocumentService` | dashboard approval count | generation | igen | document suite | ✅ |
| Permission és user role | attach/detach/sync | Spatie Permission API | `spatie.permission.cache` | package reset | package által | permission contract | 🔵 |
| Inventory operatív listák | stock és master-data események | repository | nincs alkalmazáscache | nincs teendő | – | feature suite | ⚪ |
| Procurement dashboard | PR/PO/GR/MR események | `ProcurementDashboardService` | nincs alkalmazáscache | nincs teendő | – | procurement suite | ⚪ |
| Lead-time | order/task/capacity input | `LeadTimeEstimator` | nincs önálló cache; capacity consumer invalidálódik | generation | igen | capacity suite | ✅ |

## Függőségi láncok

```text
GoodsReceipt posted
├── StockBalance és StockMovement változik
├── PurchaseOrder progress változik
├── inventory/procurement riport új generációt kap
├── material forecast, supplier performance és risks új generációt kap
└── intelligence és fő dashboard új generációt kap

ProductionTask vagy QualityCheck változik
├── production és shop-floor állapot változik
├── capacity/bottleneck input változik
├── quality trend és production risk input változik
└── intelligence és fő dashboard új generációt kap
```

## Regressziós védelem

Elsődleges contract: `tests/Feature/BusinessCacheInvalidationTest.php`.

Lefedett viselkedések: determinisztikus filterkulcs, empty-result invalidáció, goods receipt utáni készletfrissülés, unrelated domain megőrzése, idempotencia, rollback, file driver és Spatie permission reset. A teljes SQLite/MySQL backend suite védi a konkrét write workflow-kat.
