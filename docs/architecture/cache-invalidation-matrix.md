# Cache-invalidation mátrix

## Hatókör és jelölések

Ez a dokumentum a 2026-07-30-i teljes alkalmazáskód-audit eredménye. A Laravel
default cache store-jában 18 üzleti bejegyzés és 15 verziózott kulcscsalád
található. A Spatie Permission külön package cache-t kezel. Repository-, selector-
és option-cache nincs engedélyezve; a `CapacitySlotFinder` csak egy service-példány
élettartamáig memoizál.

- ✅: már helyesen működött.
- 🔧: ebben a feladatban javítva vagy pontosítva.
- ⚠️: tudatosan TTL-alapú.
- N/A: az eseményhez nincs érintett alkalmazáscache.
- Közvetlen: egy repository-aggregátumot tárol.
- Összetett: több cache-elt vagy közvetlen aggregátumot fog össze.

## Cache-leltár

| Cache | Típus | Létrehozás és fogyasztó | Kulcsképző | TTL | Olvasott adatok | Érvénytelenítő család | Rollback | Automatikus bizonyíték |
| --- | --- | --- | --- | ---: | --- | --- | --- | --- |
| Fő dashboard | többmodulos, közvetlen | `DashboardService::summary()`; admin dashboard | `dashboardSummary()` | 60 s | customer/production order és task, plan, PO, GR, material requirement, stock balance, document, QC | customer order, production, inventory, procurement, quality, document | generáció nem lép | `ReportingAnalyticsTest`, `BusinessCacheInvalidationTest` |
| Vevőrendelés-riport | közvetlen, filteres | `ReportingService::customerOrdersSummary()`; reports | `customerOrdersReport($filters)` | 60 s | `customer_orders`, `customers` | customer order, customer master | generáció nem lép | `BusinessCacheInvalidationTest` |
| Gyártási riport | többtáblás, közvetlen | `ReportingService::productionSummary()` | `productionReport()` | 60 s | production orders/tasks, items, sequence steps, factory units | production, item/factory/sequence master | generáció nem lép | `ReportingAnalyticsTest`, `BusinessCacheInvalidationTest` |
| Készletriport | többtáblás, közvetlen | `ReportingService::inventorySummary()` | `inventoryReport()` | 60 s | stock balances/reservations, items, locations | inventory és érintett master data | generáció nem lép | `BusinessCacheInvalidationTest` |
| Beszerzési riport | többtáblás, közvetlen | `ReportingService::procurementSummary()` | `procurementReport()` | 60 s | suppliers, purchase orders, goods receipts | procurement, supplier master | generáció nem lép | `ReportingAnalyticsTest`, `BusinessCacheInvalidationTest` |
| Minőségi riport | többtáblás, közvetlen | `ReportingService::qualitySummary()` | `qualityReport()` | 60 s | production orders/tasks, quality checks | quality, production | generáció nem lép | `ReportingAnalyticsTest`, `BusinessCacheInvalidationTest` |
| Shop-floor riport | többtáblás, közvetlen | `ReportingService::shopFloorSummary()` | `shopFloorReport()` | 60 s | employees, production tasks | workforce, production | generáció nem lép | `ReportingAnalyticsTest` |
| Intelligence dashboard | összetett, többmodulos | `ManufacturingIntelligenceService::dashboard()` | `intelligenceDashboard()` | 5 perc | minden intelligence részaggregátum, lead time | minden érintett részdomain eseménye | generáció nem lép | `ManufacturingIntelligenceTest` |
| Bottleneck analysis | többmodulos, közvetlen | `BottleneckAnalysisService::analyze()` | `bottleneckAnalysis()` | 5 perc | capacity reservations, calendars, tasks, orders, units | production, capacity, factory/sequence master | generáció nem lép | `ManufacturingIntelligenceTest` |
| Material forecast | többmodulos, közvetlen | `MaterialForecastService::forecast()` | `materialForecast()` | 5 perc | stock balances/reservations/movements, items | inventory, item master | generáció nem lép | `BusinessCacheInvalidationTest`, `ManufacturingIntelligenceTest` |
| Supplier performance | beszerzési, közvetlen | `SupplierPerformanceService::analyze()` | `supplierPerformance()` | 5 perc | suppliers, purchase orders, goods receipts | procurement, supplier master | generáció nem lép | `BusinessCacheInvalidationTest` |
| Quality trends | többmodulos, közvetlen | `QualityTrendService::analyze()` | `qualityTrends()` | 5 perc | quality checks, tasks, orders, items | quality, production, item master | generáció nem lép | `BusinessCacheInvalidationTest`, `ManufacturingIntelligenceTest` |
| Production risks | többmodulos, közvetlen | `ProductionRiskService::score()` | `productionRisks()` | 5 perc | customer orders/customers, material requirements, QC, production orders | customer, production, inventory, procurement, quality, capacity | generáció nem lép | `BusinessCacheInvalidationTest`, `ManufacturingIntelligenceTest` |
| Procurement recommendations | többmodulos, közvetlen | `ProcurementRecommendationService::recommendations()` | `procurementRecommendations()` | 5 perc | material requirements, PO items, customer orders, items | customer order, inventory, procurement | generáció nem lép | `ManufacturingIntelligenceTest` |
| Capacity dashboard | összetett, többmodulos | `CapacityPlanningService::dashboard()` | `capacityDashboard()` | 60 s | három capacity részcache, customer orders, lead time | customer order, production, capacity, workforce és érintett master data | generáció nem lép | `CapacityPlanningTest`, `BusinessCacheInvalidationTest` |
| Factory-unit load | közvetlen | `CapacityPlanningService::factoryUnitLoads()` | `capacityFactoryUnits()` | 60 s | factory units/calendars, reservations, tasks | production, capacity, factory/sequence master | generáció nem lép | `CapacityPlanningTest` |
| Employee load | közvetlen | `CapacityPlanningService::employeeLoads()` | `capacityEmployees()` | 60 s | employees/work calendars, roles, reservations, tasks | production, capacity, workforce | generáció nem lép | `CapacityPlanningTest` |
| Schedule rows | közvetlen | `CapacityPlanningService::scheduleRows()` | `capacitySchedule()` | 60 s | reservations, tasks, orders, unit/role/operation type | production, capacity, workforce, operation type | generáció nem lép | `CapacityPlanningTest`, `BusinessCacheInvalidationTest` |
| Permission cache | package | Spatie Permission API | `spatie.permission.cache` | 24 óra | roles, permissions, model-role és model-permission pivotok | Spatie mutációs API | package szerződés | `BusinessCacheInvalidationTest` |

Egyik üzleti cache sem locale- vagy userfüggő. A jelenlegi filteres
vevőrendelés-riport globális, de a filterek determinisztikus SHA-256 lenyomata a
kulcs része. A `null`, üres és hiányzó érték különbözik; asszociatív és
listaértékek kanonikus sorrendet kapnak.

## Üzleti eseménymátrix

| Üzleti esemény | Forrásmodul | Módosuló adatok | Érintett cache | Kulcs vagy kulcscsalád | Érvénytelenítés helye | Hatókör | Tranzakció után | Teszt |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Factory Unit create/update/delete | törzsadat | `factory_units` | production report, capacity, bottleneck és intelligence dashboard | `reports-production`, `capacity`, `intelligence-bottlenecks`, `intelligence-dashboard` | `FactoryUnitAdminService::afterWrite()` | célzott, többmodulos ✅ | igen | backend suite |
| Location create/update/delete | törzsadat | `locations` | inventory report és inventory-alapú intelligence | inventory család | `LocationAdminService::afterWrite()` | domain-szintű ✅ | igen | backend suite |
| Employee create/update/delete | workforce | `employees` | shop-floor és capacity | `reports-shop-floor`, `capacity` | `EmployeeAdminService::afterWrite()` / `workforceChanged()` | szűkítve 🔧 | igen | backend suite |
| Professional Role create/update/delete | workforce | `professional_roles` | capacity címkék és load | `capacity` | `ProfessionalRoleAdminService::afterWrite()` | célzott ✅ | igen | backend suite |
| Operation Type create/update/delete | törzsadat | `operation_types` | capacity schedule címke és lead-time input | `capacity` | `OperationTypeAdminService::afterWrite()` | hiány pótolva 🔧 | igen | `BusinessCacheInvalidationTest` |
| Customer create/update/delete | partner | `customers` | CO report, production risks, intelligence dashboard | `reports-customer-orders`, `intelligence-risks`, `intelligence-dashboard` | `CustomerAdminService::afterWrite()` / `customersChanged()` | capacity túl-invalidation megszüntetve 🔧 | igen | `BusinessCacheInvalidationTest` |
| Supplier create/update/delete | partner | `suppliers` | procurement report, supplier performance, intelligence dashboard | `reports-procurement`, `intelligence-supplier-performance`, `intelligence-dashboard` | `SupplierAdminService::afterWrite()` / `suppliersChanged()` | inventory/risk túl-invalidation megszüntetve 🔧 | igen | `BusinessCacheInvalidationTest` |
| Item create/update/delete, aktív állapot | törzsadat | `items` | inventory/production/quality riportok és item-alapú intelligence | inventory és production családok | `ItemAdminService::afterWrite()` | széles, mert több aggregátum nevet és típust olvas ✅ | igen | backend suite |
| BOM és BOM item create/update/delete | törzsadat | `boms`, `bom_items` | material requirementből származó inventory/risk/recommendation lánc | inventory család | `BomAdminService` write metódusok | domain-szintű ✅ | igen | backend suite |
| Operation Sequence/step create/update/delete | törzsadat | sequences, steps | production, capacity, bottleneck és kapcsolt intelligence | production család | `OperationSequenceAdminService` | többmodulos ✅ | igen | backend suite |
| Customer Order create/update/confirm/cancel/delete | értékesítés | orders és items | dashboard, CO report, risks, recommendations, capacity | customer-order eseménycsoport | `CustomerOrderService` | többmodulos ✅ | igen | `BusinessCacheInvalidationTest` |
| Production Plan create/update/approve/delete | gyártás | plans és plan items | dashboard, production riport, risks, capacity | production eseménycsoport | `ProductionPlanService` | többmodulos ✅ | igen | production suite |
| Production Order generálás | gyártás | production orders | dashboard, production, risks, capacity/bottleneck | production eseménycsoport | `ProductionPlanService::generateProductionOrders()` | többmodulos ✅ | igen | production suite |
| Production Task CRUD/generate/start/finish | gyártás | tasks, item instances, orders | dashboard, production/shop-floor/quality, capacity, intelligence | production eseménycsoport | `ProductionTaskService` | többmodulos ✅ | igen | `BusinessCacheInvalidationTest` |
| Anyagfelhasználás | gyártás/raktár | task materials, balances, movements, reservations | dashboard, inventory report, material forecast, risks, recommendations | inventory eseménycsoport | `ProductionTaskMaterialService::store()` | hiány pótolva 🔧 | igen | `BusinessCacheInvalidationTest` |
| Quality Check létrehozás és eredmény | minőség | QC, task, item instance, order | dashboard, quality/production, trends, risks, capacity | quality + production eseménycsoport | `QualityCheckService::store()` | többmodulos ✅ | igen | `BusinessCacheInvalidationTest` |
| Material Requirement számítás/újraszámítás | raktár | material requirements | dashboard, inventory, forecast, risks, recommendations | inventory eseménycsoport | `MaterialRequirementService` | többmodulos ✅ | igen | inventory suite |
| Stock Reservation reserve/release/consume | raktár | reservations, material requirements | inventory, forecast, risks, recommendations, dashboard | inventory eseménycsoport | `StockReservationService`, material consumption service | többmodulos ✅/🔧 | igen | `BusinessCacheInvalidationTest` |
| Goods Receipt létrehozás | beszerzés | receipt és receipt items | dashboard, procurement report és supplier aggregátumok | procurement eseménycsoport | `GoodsReceiptService::create()` | beszerzési ✅ | igen | procurement suite |
| Goods Receipt post/partial post | beszerzés/raktár | receipt, PO items/status, balances, movements, batches | procurement + inventory összes cache és kapcsolt intelligence | procurement és inventory eseménycsoport | `GoodsReceiptService::post()` | széles, indokolt ✅ | igen | `BusinessCacheInvalidationTest` |
| Purchase Requisition CRUD/approve | beszerzés | requisitions és items | N/A: a jelenlegi cache-ek nem olvasnak PR táblát | N/A | jelenleg konzervatív `procurementChanged()` | túl-invalidationként ismert | igen | procurement suite |
| Purchase Requisitionből PO generálás | beszerzés | PO és PO items | dashboard, procurement, supplier performance, risks, recommendations | procurement eseménycsoport | `PurchaseRequisitionService::generatePurchaseOrder()` | beszerzési ✅ | igen | procurement suite |
| Purchase Order CRUD/approve/close | beszerzés | PO és items | dashboard, procurement, supplier performance, risks, recommendations | procurement eseménycsoport | `PurchaseOrderService` | többmodulos ✅ | igen | `BusinessCacheInvalidationTest` |
| Schedule mentés/felülírás | kapacitás | capacity reservations | capacity, bottleneck, risks, intelligence dashboard | capacity eseménycsoport | `SchedulingService` → `CapacityPlanningService::forgetCapacityCache()` | kapacitás ✅ | `DB::afterCommit()` | `CapacityPlanningTest` |
| Factory/employee calendar változás | kapacitás | calendar táblák | capacity, bottleneck | capacity eseménycsoport | nincs jelenlegi write UI/service | N/A jelenlegi alkalmazási belépési pont nélkül | N/A | factory alapú repository tesztek |
| Document create/update/delete/approve/current | dokumentum | documents és verzióállapot | fő dashboard approval KPI | dashboard család | `DocumentService` | egy cache ✅ | igen | document suite |
| Role/permission/user-role változás | jogosultság | Spatie táblák/pivotok | Spatie permission cache | package kulcs | `RoleAdminService` és Spatie API | package-szintű ✅ | package API | `BusinessCacheInvalidationTest` |
| Locale/preference változás | beállítás | user preference | N/A: nincs locale- vagy userfüggő üzleti cache | N/A | nincs teendő | nincs globális invalidálás ✅ | N/A | kulcsscope unit contract |
| Idő múlása: napváltás, gördülő időablak | rendszeridő | nincs DB-write; `days_open`, mai teljesítések, 7/14 napos horizon, fogyási és quality ablak változik | CO/shop-floor riport, capacity, minden időablakos intelligence elem | érintett riport/intelligence/capacity család | nincs üzleti write esemény | dokumentált TTL-only ⚠️ | N/A | TTL contract és szolgáltatástesztek |

## Stratégiai döntés

A dinamikus filterek miatt domainenkénti generációs kulcsot használunk:
`km-production:{domain}:g{generation}:{name}:{parameter-hash}`. Az invalidálás
csak a megfelelő generációs countert növeli; nincs cache tag, Redis-függés vagy
teljes flush. A régi payloadok legfeljebb a saját 60 másodperces vagy 5 perces
TTL-jükig maradnak fizikailag a store-ban, de invalidálás után nem olvashatók.

Nyitott tranzakciónál a `BusinessCacheInvalidator` `DB::afterCommit()` callbacket
regisztrál. Külső vagy nested rollback esetén sem adat, sem generáció nem változik.
Ha az invalidáció a commit után hibázik, a már commitolt üzleti adat integritása
megmarad, a hiba pedig látható marad az üzemeltetés számára.

## Tudatos korlátozások

- A Purchase Requisition mutációk jelenleg konzervatívan forgatják a procurement
  családot, bár közvetlen cache-forrásuk nincs. Ennek szűkítése külön,
  teljesítményméréssel igazolt változtatás lehet.
- A puszta időmúlásból eredő értékek explicit üzleti esemény nélkül, a 60
  másodperces vagy 5 perces TTL alapján frissülnek. Ez az egyetlen tudatos
  TTL-only szabály; adatbázis-write helyességét nem bízzuk TTL-re.
- Nincs cache prewarm vagy distributed stampede lock. A legdrágább aggregátumok
  legfeljebb ötpercesek, és jelenlegi mérés nem igazolt olyan terhelést, amely a
  lock összetettségét indokolná.
- Selector- és option-lista nincs alkalmazáscache-ben; ezért ezekhez nincs stale
  selector invalidáció. Új selector cache bevezetése nem része ennek a munkának.
