# RMA-0004 / C3-01 — Transaction Ownership, Database Integrity & Verification Planning

Státusz: **PROPOSED / NOT IMPLEMENTATION READY**. Dátum: 2026-11-04.
Dokumentációs technikai terv; minden jövőbeli profil, fixture és próba **PROPOSED / NOT EXECUTED**.
Projektgazdai review szükséges; ez a dokumentum nem engedélyez implementációt vagy kísérletet.

## 1. Executive summary

A **WP7-F01 BLOCKER** és **WP7-F02 HIGH** nyitott marad. A vizsgálat eredménye egy szűkített
tranzakciótulajdonlási szerződés, három alternatíva összevetése és később végrehajtható
próbaspecifikáció. Nincs új elfogadott architekturális döntés vagy DB-bizonyíték.

Feltételes ajánlás: **Model C, dedikált alkalmazási parancsvégrehajtó, service-owned roottal**.
Egy teljes üzleti parancs, egy kizárólagosan birtokolt kapcsolat és root tranzakció; belső
COMMIT nincs. A routine teljes root-visszagörgetése csak e saját parancsra kifejezetten
delegált abortjog lehet. Ez eltér Model A routine-oldali önálló rollbacket is kizáró modelljétől.
Sem a dedikált kapcsolat neve, sem egy PHP-osztály, sem az autocommit értéke nem jogosultsági
vagy tiszta tranzakciótulajdonlási bizonyíték. A konkrét belépésvédelem és izoláció még nincs igazolva.

Model A a jelenlegi Laravel-szerkezethez közel áll, de a hibát elkapó közvetlen CALL-kliens
részleges eredményt commitolhat, ha a DB nem biztosít külön totalitást. Model B erős parancshatárt
adhatna, de belső COMMIT-ja ellentétes az elfogadott C1 szerződéssel. Model C ígéretes
review-irány; **egyik modell sem bizonyítottan teljesíti ma az összes elfogadott követelményt**.
Ez nem bizonyítja, hogy Model C megvalósíthatatlan, és nem igazol jelenlegi production hibát.

A C2 leltár változatlan: 25 új táblajelölt, 18 érintett meglévő tábla, ezekből 7 közvetlen
bővítés. A WP7 48 kanonikus követelménycsaládja megmarad; az itt megadott részpróbák nem
új, azokat helyettesítő követelmények. A történeti B 35 prototípussikere nem C3-01 tesztsiker.

## 2. Repository baseline

| Tétel                      | Ellenőrzött állapot                                                                                                        |
| -------------------------- | -------------------------------------------------------------------------------------------------------------------------- |
| Repository                 | KM_Production; munkakönyvtár a projektgyökér, abszolút környezeti útvonal nem kerül az artifactba                          |
| Branch                     | `feature/returns-repair-management`                                                                                        |
| HEAD                       | `e21e83da44e7ec2228efa6c5c6f8d1021340b73b`                                                                                 |
| Kezdő tracked diff / index | Mindkettő üres                                                                                                             |
| Kezdő untracked            | Kizárólag az alábbi hét C2-WP dokumentum                                                                                   |
| Engedélyezett változás     | Kizárólag ez az egy új Markdown; záráskor 7 → 8 untracked fájl                                                             |
| Bizonyítékhatár            | Git/source/telepített library statikus olvasása, PHP CLI verzió/modullista; nincs alkalmazásindítás, DB-kapcsolat vagy SQL |

### 2.1. Forrásregiszter és elsőbbség

| Forrás                                                              | Egyeztetett jelentés / jelen feladat határa                                                                      |
| ------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------- |
| [ADR-0006](../decisions/0006-rma-repair-management-architecture.md) | **Accepted**; RMA-BD-001–020, D1–D11, F1–F4, AM-01–04 változatlan                                                |
| [ADR-0001](../decisions/0001-stock-movements.md)                    | StockMovement a készletváltozás SoT-ja; projekció csak kapcsolt könyvelési tranzakcióban                         |
| [RMA-0003/A](rma-0003a-data-model-integrity-design.md)              | Történeti adatmodell és tranzakció/konkurencia-terv; C1/C2 pontosításai elsőbbséget élveznek                     |
| [RMA-0003/B](rma-0003b-integrity-verification.md)                   | Megőrzött részleges prototípusbizonyíték, nem teljes jelenlegi séma vagy jogosultsági profil                     |
| [C1](rma-0003c1-business-decisions-integrity-gates.md)              | **ACCEPTED WITH CONDITIONS**; service root, finalizer no COMMIT, audit-hibánál teljes rollback; G1–G3 feltételek |
| [C2 előkészítés](rma-0003c2-detailed-schema-review-plan.md)         | A részletes terv és verifikáció elválasztva; nem implementációengedély                                           |
| [WP1](rma-0003c2-wp1-schema-inventory.md)                           | 25/18/7 séma- és invariánsleltár; a leltár nem executable DDL                                                    |
| [WP2](rma-0003c2-wp2-mysql-schema-integrity-review.md)              | §12–13, EX03/06/08; WP2-F02 nyitott; mode0+full-root-handler csak feltételes javaslat                            |
| [WP3](rma-0003c2-wp3-inventory-mrp-consumer-contracts.md)           | E/R/P/M/Q/C/L közös szerződések, 282 metódus hatásleltár; nem 282 igazolt átállítás                              |
| [WP4](rma-0003c2-wp4-return-intake-stock-admission.md)              | I/MI intake és A/MA admission külön, quarantine/ownership/disposition külön                                      |
| [WP5](rma-0003c2-wp5-fee-credit-procurement-coverage.md)            | Credit és coverage teljes atomic command; multi-source-line elfogadott, ledger conservation bizonyítandó         |
| [WP6](rma-0003c2-wp6-legacy-data-migration-rollout-recovery.md)     | Legacy audit, A–F rollout, grants/DB/files/tail recovery továbbra végrehajtatlan                                 |
| [WP7](rma-0003c2-wp7-verification-migration-readiness.md)           | V01–V48 kanonikus; §6 tranzakciós ellenpéldák, §12 WP7-F01/F02, §13 kapuk megmaradnak                            |

Az eredeti findingok ID/severity/státusza nincs átírva. WP2-F02 **BLOCKER**,
WP7-F01 **BLOCKER**, WP7-F02 **HIGH**; tervezési javaslat egyiküket sem zárja le.
C1-G1: DESIGN RESOLVED / IMPLEMENTATION UNVERIFIED; G2: IMPACT MAPPED / ROLLOUT BLOCKED;
G3: LIFECYCLE SPECIFIED / DDL UNVERIFIED. A későbbi WP2 O1 current baseline/O2 future historical
MRP integration/O3 coexistence not selected és az elfogadott REV-02 multi-line jelentés megmarad.

### 2.2. Megőrzési baseline

| Kezdő untracked fájl                                       | SHA-256                                                                         |
| ---------------------------------------------------------- | ------------------------------------------------------------------------------- |
| `rma-0003c2-wp1-schema-inventory.md`                       | `f4d6682d9bb0b8d58882263199919d9af394cfee58c0c5f0ea704ef6859fa932`              |
| `rma-0003c2-wp2-mysql-schema-integrity-review.md`          | `eae6ead3724a4ed4aac69efa38ef08aa5bf988f25ee2bb25be05106124f296dd`              |
| `rma-0003c2-wp3-inventory-mrp-consumer-contracts.md`       | `1a340ac30113417672869ff0d71bc854ce02f81d4ae2dfd2a5c09ff5ec22d994`              |
| `rma-0003c2-wp4-return-intake-stock-admission.md`          | `d7a1fc49dc37eadfeba6cfaaa0ee6abeb7319ef34cb4adde5f9a7a4a58538023`              |
| `rma-0003c2-wp5-fee-credit-procurement-coverage.md`        | `73be78b8a02de54d99ed39cb69fddabceda5b134d696feee4cd46bdfdc0e058d`              |
| `rma-0003c2-wp6-legacy-data-migration-rollout-recovery.md` | `5e4c012222c8bd13beedcf837f56f3dd7c4cdd750c1feab0eacf6d5e39f5f8ae`              |
| `rma-0003c2-wp7-verification-migration-readiness.md`       | Záráskor a teljes 921 fájlos kezdő halmaz összesített hash-ellenőrzésének része |

A kezdetben Git által ismert tracked+untracked **921 fájl** rendezett `(relatív útvonal,SHA-256)`
listájának UTF-8 JSON-kódolású összesített SHA-256-a:
`e1b2f2538580c1a6bb8240e77fd7c8db54a55985f0ed1c6cd2db3691ff25f4a5`.
Ez összehasonlítási baseline, nem futási integritásteszt. A történeti
[B JSON](verification/rma-0003b-results.json) hash-e továbbra
`d1b4443724984cbd21c9852c4611fa659411e09924192d7b3b9296164e33a6e6`.
A hardening utáni [B PHP](verification/rma-0003b-verify.php) nem lett futtatva vagy módosítva;
nem követelünk tőle régi, hardening előtti script-hash-t.

## 3. Current transaction architecture

### 3.1. Statikus discovery és bizonyítékhatár

Az `app/**/*.php` forrásban **49 `DB::transaction(...)` hívás**: 17 service-fájlban 40,
4 repositoryban 9. A vizsgált `app`, `bootstrap`, `routes` forrásban nem találtunk explicit
`DB::beginTransaction/commit/rollBack` alkalmazási hívást, kézi SAVEPOINT-ot vagy dedikált
RMA executor/connection definíciót. Az `app`, `database`, `scripts`, `.github` keresésben
nem találtunk implementált stored-procedure CALL/CREATE PROCEDURE/SQL SECURITY/GRANT/REVOKE
szerződést. Ez repositorybeli forráshiány, nem élő DB-routine/grant-hiány bizonyítéka.
Külső cron/BI, deploy hook, proxy, tényleges DB grants és más kliens **UNVERIFIED**.

A jelenlegi app-tranzakcióhívások closure-t adnak meg, explicit többkísérletes attempts nélkül;
külön alkalmazási deadlock retry orchestrationt nem találtunk. A framework tud retryt,
de az alapértelmezett attempts=1. A jelenlegi Inventory/Procurement tesztfájlok léte nem
új RMA/PDO/principal protokoll bizonyítéka; e feladatban egyik sem futott.

A teljes statikus belépőleltár (a sorjel a transaction hívásé, nem a metódusdeklarációé):

| Fájl                                                                                        | Hívások | Metódus és transaction híváshely                                                                                            |
| ------------------------------------------------------------------------------------------- | ------- | --------------------------------------------------------------------------------------------------------------------------- |
| [BomRepository](../../app/Repositories/Admin/BomRepository.php)                             | 2       | `createWithItems` (L21), `updateWithItems` (L32)                                                                            |
| [CustomerOrderRepository](../../app/Repositories/Admin/CustomerOrderRepository.php)         | 2       | `createWithItems` (L64), `updateWithItems` (L78)                                                                            |
| [OperationSequenceRepository](../../app/Repositories/Admin/OperationSequenceRepository.php) | 2       | `createWithSteps` (L26), `updateWithSteps` (L42)                                                                            |
| [ProductionPlanRepository](../../app/Repositories/Admin/ProductionPlanRepository.php)       | 3       | `createWithItems` (L62), `updateWithItems` (L76), `generateProductionOrders` (L118)                                         |
| [AbstractAdminService](../../app/Services/Admin/AbstractAdminService.php)                   | 2       | `create` (L45), `update` (L72)                                                                                              |
| [BomAdminService](../../app/Services/Admin/BomAdminService.php)                             | 2       | `create` (L37), `update` (L58)                                                                                              |
| [CodeAwareAdminService](../../app/Services/Admin/CodeAwareAdminService.php)                 | 1       | `create` (L34)                                                                                                              |
| [CustomerOrderService](../../app/Services/Admin/CustomerOrderService.php)                   | 4       | `create` (L71), `update` (L99), `confirm` (L125), `cancel` (L151)                                                           |
| [DocumentApprovalService](../../app/Services/Admin/DocumentApprovalService.php)             | 1       | `approve` (L17)                                                                                                             |
| [DocumentService](../../app/Services/Admin/DocumentService.php)                             | 2       | `create` (L66), `update` (L120)                                                                                             |
| [DocumentVersionService](../../app/Services/Admin/DocumentVersionService.php)               | 1       | `makeCurrent` (L40)                                                                                                         |
| [GoodsReceiptService](../../app/Services/Admin/GoodsReceiptService.php)                     | 2       | `create` (L48), `post` (L86)                                                                                                |
| [OperationSequenceAdminService](../../app/Services/Admin/OperationSequenceAdminService.php) | 2       | `create` (L42), `update` (L63)                                                                                              |
| [ProductionPlanService](../../app/Services/Admin/ProductionPlanService.php)                 | 4       | `create` (L75), `update` (L111), `approve` (L134), `generateProductionOrders` (L164)                                        |
| [ProductionTaskMaterialService](../../app/Services/Admin/ProductionTaskMaterialService.php) | 1       | `store` (L30)                                                                                                               |
| [ProductionTaskService](../../app/Services/Admin/ProductionTaskService.php)                 | 5       | `create` (L54), `update` (L73), `generateFromOrder` (L94), `start` (L160), `finish` (L192)                                  |
| [PurchaseOrderService](../../app/Services/Admin/PurchaseOrderService.php)                   | 4       | `create` (L40), `update` (L80), `approve` (L109), `close` (L132)                                                            |
| [PurchaseRequisitionService](../../app/Services/Admin/PurchaseRequisitionService.php)       | 5       | `create` (L46), `update` (L82), `approve` (L109), `generateFromMaterialRequirements` (L126), `generatePurchaseOrder` (L184) |
| [QualityCheckService](../../app/Services/Admin/QualityCheckService.php)                     | 1       | `store` (L39)                                                                                                               |
| [SchedulingService](../../app/Services/Admin/SchedulingService.php)                         | 1       | `schedule` (L33)                                                                                                            |
| [StockReservationService](../../app/Services/Admin/StockReservationService.php)             | 2       | `reserveForProductionOrder` (L35), `release` (L99)                                                                          |

### 3.2. Releváns forrásmegállapítások

Minden sor **CONFIRMED CURRENT source behavior**, kivéve ahol UNVERIFIED szerepel.
A kockázat és szükséges módosítás jövőbeli integrációs szerződés; nem új hibajavítási engedély.

| Forrás: fájl és metódus                                                                                                                                                                                                                                                              | Jelenlegi működés                                                                                                                                   | Bizonyíték                                                                                              | RMA-integrációs kockázat                                                                                           | Szükséges jövőbeli szerződés                                                                                                            | Kanonikus verifikáció   |
| ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------- | ----------------------- |
| [CustomerOrderService](../../app/Services/Admin/CustomerOrderService.php) `create/update`; [repository](../../app/Repositories/Admin/CustomerOrderRepository.php) `createWithItems/updateWithItems`                                                                                  | Service transaction → repository transaction; update törli/újraépíti a line-okat                                                                    | Egymásba hívó metódustörzsek                                                                            | Nested savepoint nem független root; history/owner kapcsolatok régi törlési útja érintett                          | Protected RMA rootba nem hívható változatlanul; source/history törlésvédelem külön átállítás                                            | V14/V38/V42/V43/V48     |
| [BomAdminService](../../app/Services/Admin/BomAdminService.php) `create/update`; [BomRepository](../../app/Repositories/Admin/BomRepository.php) `createWithItems/updateWithItems`                                                                                                   | Service és repository is DB::transaction                                                                                                            | Két réteg konkrét closure-je                                                                            | Generic service composition RMA-finalizert nested scope-ba vihet                                                   | Entry root-only vagy külön jóváhagyott transaction-neutral persistence adapter                                                          | V35/V42/V48             |
| [OperationSequenceAdminService](../../app/Services/Admin/OperationSequenceAdminService.php) `create/update`; [repository](../../app/Repositories/Admin/OperationSequenceRepository.php) `createWithSteps/updateWithSteps`                                                            | Azonos nesting pattern                                                                                                                              | Metódusok hívásai                                                                                       | Aktív operation/proof közben verzió/lock összefésülés nem automatikus                                              | Eredeti sequence megőrzése, started scope guard, új command boundary                                                                    | V35/V37/V42             |
| [ProductionPlanService](../../app/Services/Admin/ProductionPlanService.php) `create/update/generateProductionOrders`; [repository](../../app/Repositories/Admin/ProductionPlanRepository.php) `createWithItems/updateWithItems/generateProductionOrders`                             | Root closure-ben repository closure; generation után requirements és reservation service                                                            | Teljes hívási lánc source-ból                                                                           | RMA executor dedikált kapcsolaton nested hívva függetlenül commitolhatna az outerhez képest                        | Részt vevő caller-tranzakció felismerése és belépés előtti elutasítás; nem elég a dedicated connection depth0                           | V08/V14/V42/V43/V48     |
| [StockReservationService](../../app/Services/Admin/StockReservationService.php) `reserveForProductionOrder/release/availableOnBalance`                                                                                                                                               | Reserve tranzakcióban számol és ír; release státuszprecheck a transaction előtt; kalkuláció kétszer; balance-kiválasztás közös mutex nélkül         | Metódustörzsek és delegált repo                                                                         | Close/consume/release race, stale státusz/availability; production outer alatt nested                              | Current locking reads, owner+pool mutex, exact remaining és közös root                                                                  | V08–V12/V42/V48         |
| [StockReservationRepository](../../app/Repositories/Admin/StockReservationRepository.php) `activeReservedQuantity/activeReservedQuantityForBalance/createReservation/release`                                                                                                        | Teljes active reserved SUM; opcionális NULL szűrő wildcard; státusz-only release                                                                    | Query és update source                                                                                  | Partial fulfilled újra available, idegen owner/pool                                                                | `remaining=reserved-consumed-released`, explicit NULL-pool identity és védett mutáció                                                   | V08–V12/V14/V48         |
| [StockBalanceRepository](../../app/Repositories/Admin/StockBalanceRepository.php) `increaseQuantity/balancesForReservation/totalQuantityForItem`                                                                                                                                     | `firstOrCreate` nullable batch-pool, increment; full physical SUM; nincs saját transaction                                                          | Query source                                                                                            | Több writer közös lock/identity nélkül, custody/quarantine vak olvasás                                             | Normalizált U+valódi batch FK, E szerződés, csak movementtel közös protected posting                                                    | V04/V08/V22/V48         |
| [ProductionTaskMaterialService](../../app/Services/Admin/ProductionTaskMaterialService.php) `store/balanceForConsumption/reservation`                                                                                                                                                | Tranzakció, balance FOR UPDATE, anyag+decrement+movement+audit; reservation teljes Consumed részfogyasztásnál is; fallback első active sort frissít | Metódustörzsek; causer nullable                                                                         | Új counters és owner guards nem jelennek meg automatikusan, balance előbb záródik mint proposed L                  | Egy shared protected consume command, exact q/owner/remaining, root és audit együtt                                                     | V09/V12/V14/V38/V48     |
| [MaterialRequirementService](../../app/Services/Admin/MaterialRequirementService.php) `calculateForProductionOrder/calculateBomItem`                                                                                                                                                 | Nincs saját DB::transaction; upsert és audit caller contextben vagy anélkül; float/full stock/netting                                               | Metódustörzsek                                                                                          | Multi-write nem minden belépőn atomi, stale snapshot és owner mixing                                               | Transaction-neutral belső számítás + explicit owned outer command, current demand/coverage                                              | V14–V17/V22/V38/V48     |
| [PurchaseRequisitionService](../../app/Services/Admin/PurchaseRequisitionService.php) `generateFromMaterialRequirements`                                                                                                                                                             | Requirement lock, missing>0/no source szűrés; item/unit grouping, igényenként egy source/futás                                                      | Konkrét select és source insert                                                                         | Új generation budget/multi-line nélkül partial vagy duplagenerálás                                                 | Ghead és stable source-line külön; összes line+ledger+audit együtt                                                                      | V15–V17/V38/V40/V48     |
| Ugyanott `generatePurchaseOrder/approve/delete`                                                                                                                                                                                                                                      | PR lock PO-generálásnál; approve státuszprecheck kívül; delete audit és soft-delete saját transaction nélkül                                        | Source metódusok                                                                                        | Supplier kötelezettség/history/cancel és raw writer fence hiányzik az új modellhez                                 | Revalidate lock alatt, source/PO/coverage atomic bridge; history és privilege contract                                                  | V16/V22/V33/V38/V48     |
| [PurchaseOrderService](../../app/Services/Admin/PurchaseOrderService.php) `create/update/approve/close/delete`                                                                                                                                                                       | Create/update/approve/close transaction; close Received státuszt ír, nem GR; delete audit+soft-delete                                               | Source, nincs stock posting close-ban                                                                   | Státusz nem receipt/settlement proof, cancellation nem coverage release                                            | Valós commitment/GR/owner mapping; protected transition és source-history megőrzés                                                      | V22/V27/V28/V38/V48     |
| [GoodsReceiptService](../../app/Services/Admin/GoodsReceiptService.php) `post/updatePurchaseOrderItemReceivedQuantity`                                                                                                                                                               | GR header lock → PO item lock → balance increase+movement+received counter+audit; movement morph GR headerre; cache kívül                           | Teljes posting closure                                                                                  | L-sorrend eltérés; GR-line owner-distribution és ledger conversion hiányzik                                        | Előre tervezett teljes lockset, exact GR-item identity, coverage→physical átvezetés egyszer, ugyanazon root                             | V14/V22/V38/V40/V48     |
| [ProductionTaskService](../../app/Services/Admin/ProductionTaskService.php) `finish/advanceNextTaskOrFinalize/completeProductionOrderWhenReady`; [QualityCheckService](../../app/Services/Admin/QualityCheckService.php) `store`                                                     | Task/QC transactionből delegált order/serial állapotírások; InStock státusz nem stock movement                                                      | Source hívási lánc                                                                                      | További delegált írólánc kimaradhat egy routine-szigetből; státuszból nem vélelmezhető fizikai output              | Shared writer fence, teljes mutation/audit/lock closure; eredeti traceability megmarad                                                  | V14/V37/V38/V48         |
| [DocumentService](../../app/Services/Admin/DocumentService.php) `create/approve/makeCurrent`; [DocumentApprovalService](../../app/Services/Admin/DocumentApprovalService.php) `approve`; [DocumentVersionService](../../app/Services/Admin/DocumentVersionService.php) `makeCurrent` | File store a DB-transaction előtt; approval/current saját transactionben                                                                            | Source, nem fájlrendszerteszt                                                                           | DB rollback nem file undo; proof version és bytes később eltűnhet                                                  | Előzetesen durable immutable file/version manifest, DB typed proof ugyanazon commandban, külön cleanup/retention                        | V28/V33/V38/V39/V48     |
| [AuditLogService](../../app/Services/AuditLogService.php) `log/logCreated/logUpdated`; [Spatie LogActivityAction](../../vendor/spatie/laravel-activitylog/src/Actions/LogActivityAction.php) `execute/save`                                                                          | ActivityLogger alapértelmezett mentés vagy config szerinti buffering; nem typed RMA proof                                                           | Source + [activitylog config](../../config/activitylog.php), buffering default false, enabled env-függő | Dedikált RMA connection mellett default audit másik connectionre kerülhet; buffering/disable fél commandot hagyhat | Kötelező audit DB command része és azonos kapcsolat; sem késleltetés, sem kapcsoló nem teheti opcionálissá; tényleges config UNVERIFIED | V28/V31/V33/V38/V41/V43 |
| [BusinessCacheInvalidator](../../app/Services/BusinessCacheInvalidator.php) `invalidate/invalidateNow`                                                                                                                                                                               | Default DB depth>0 esetén afterCommit, különben azonnal; generation increment hibát dobhat                                                          | Metódustörzsek                                                                                          | Dedicated connection rootját default façade nem látja; cache hiba retry-duplikációt indíthat                       | Az owning connection commitjához kötött callback, tartós command outcome és külön cache retry                                           | V06/V38/V40/V42/V48     |
| [Database config](../../config/database.php) connection definitions                                                                                                                                                                                                                  | Default env-függő, source fallback SQLite; MySQL InnoDB/strict/UTC/utf8mb4, nincs dedikált RMA pool vagy role topology                              | Kizárólag verziózott konfiguráció, nem feloldott env                                                    | Default connection/grants/proxy tisztasága nem bizonyítható, magas fallback nem runtime grant-audit                | Külön név/principal/secret access, explicit nonpersistent lifecycle, biztonságos startup refusal                                        | V41/V42/V43/V48         |

### 3.3. Telepített Laravel/PDO mechanizmusok

[composer.lock](../../composer.lock) és a lokális `vendor/composer/installed.json` egyezően Laravel
**v13.38.0**, source `c42f9abd4a3d2f9a3cd6be109255ddce8092ceb8`. Ez lokális metadata,
nem deploy bizonyíték. A `composer.json` PHP-korlátja és README baseline-ja nem helyettesíti
a tényleges lockplatform ellenőrzését. A `php -v` helyben **8.4.27 CLI**-t jelzett; `php -m`
PDO/pdo_mysql/mysqlnd jelenlétet jelzett, kapcsolódás nélkül. A modullista egy meglévő
Xdebug log-hozzáférési figyelmeztetést adott; nem DB-probahiba. A mysqlnd pontos buildje,
PDO runtime attribútumai, FPM/Apache/worker/CI azonossága és server version **UNVERIFIED**.

| Lokális library: metódus                                                                                                                                       | Ellenőrzött mechanizmus                                                                                                             | Integrációs következmény                                                                                                   |
| -------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------- |
| [ManagesTransactions](../../vendor/laravel/framework/src/Illuminate/Database/Concerns/ManagesTransactions.php) `transaction/createTransaction/createSavepoint` | attempts=1 default; level0 PDO BEGIN, nested `transN` savepoint; root commit csak level1                                            | Laravel depth nem független server transaction; nested RMA tilalmat a valódi belépőn kell bizonyítani                      |
| Ugyanott `handleTransactionException/performRollBack/handleCommitTransactionException`                                                                         | Nested concurrency exception kifelé dobódik; root rollback PDO-state szerint; inner rollback SAVEPOINT-ra; commit-hiba külön branch | Routine full rollback után counter/callback/savepoint összhang nem feltételezhető; unknown commit nem automatikus rollback |
| [Connection](../../vendor/laravel/framework/src/Illuminate/Database/Connection.php) `handleQueryException/tryAgainIfCausedByLostConnection`                    | Level>=1 esetén query exception továbbdobódik; level0 lost connectionnél reconnect és query újrafuttatás lehetséges                 | Különösen Model B raw CALL level0 újrajátszási kockázat; minden modell stable command key-t igényel                        |
| Ugyanott `disconnect/setPdo/executeBeginTransactionStatement`                                                                                                  | Disconnect transaction-manager rollbackot kér és nulláz PDO-kat; setPdo depth0; BEGIN PDO-n át                                      | Nem bizonyítja élő session minden állapotának biztonságos pool-resetjét; stale Model/repository reference is ellenőrizendő |
| [DatabaseManager](../../vendor/laravel/framework/src/Illuminate/Database/DatabaseManager.php) `connection/purge/reconnect`                                     | Connection objektum cache, purge disconnect+eltávolítás; reconnect friss PDO-t köthet meglévőhöz                                    | Új connection név nem biztosít process/principal elkülönítést; callback/default/read connection összhang kell              |
| [Connector](../../vendor/laravel/framework/src/Illuminate/Database/Connectors/Connector.php) `getOptions/createPdoConnection`                                  | ERRMODE_EXCEPTION, native prepares default; PHP>=8.4 PDO::connect út                                                                | Explicit runtime attribútumellenőrzés kell; CALL összes resultset/cursor ürítését külön próba igazolja                     |
| [ConcurrencyErrorDetector](../../vendor/laravel/framework/src/Illuminate/Database/ConcurrencyErrorDetector.php) `causedByConcurrencyError`                     | SQLSTATE40001 és több message, köztük lock timeout felismerés                                                                       | Framework retry-kategória nem domain retry engedély; eredeti SQLSTATE/vendor code megőrzendő és sanitizálandó              |

## 4. Critical integrity requirements

Az accepted döntések érvényesek minden alternatívában. A technikai javaslat nem adhat új
üzleti jogot, nem vezethet be negyedik scope-ot vagy automatikus warranty/ownership/credit döntést.

| Követelmény                             | Megőrzött invariáns                                                                                                                       | Bizonyítási család          |
| --------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------- | --------------------------- |
| C1-G1; BD-004/011; F2                   | Claim PK(serial), acquire DB-védelem, release csak teljes proofos case-close-ban; finalizer nem commitol                                  | V07/V28–V33/V38/V41–V43     |
| C1-G2; ADR-0001; BD-007/017/018/020; F1 | Shared movement/projection/counter/owner/availability/audit egy szerződés; régi scope-vak writer nem marad aktív                          | V04/V08–V17/V22/V48         |
| C1-G3; D5/D8–D10; AM-01/02              | Fizikai dispatch, intake, admission, ownership, quarantine release és redispatch nem egymás szinonimái                                    | V13/V18–V24/V38             |
| AM-02-G1–G4 MANDATORY                   | Proofos intake; company0 amíg nincs verified admission; quarantine exclusion minden consumerből; intake/admission egyszer, külön kulccsal | V18–V23/V48                 |
| D1–D3; BD-001/002/003/016               | Három scope; own manufacture+serial+origin; egy owner/case, reporter külön; eredeti egyszeri receipt                                      | V07/V13/V14/V18–V24         |
| D4; BD-005/012/015; F3                  | Nincs automatikus cseregarancia-reset; mindkét serial története és legal basis megmarad                                                   | V14/V24/V37/V39/V44         |
| D6/D7; AM-03; BD-006/013/014            | Approved-only fee-cap/same-order credit; cancel nem release; unused+unsettled bizonyíték vagy retained/correction                         | V25–V27/V33/V38             |
| D11; AM-04; accepted REV-02             | Multi-line generation, conserved coverage; demand cancel nem supplier cancel; GR nem consume/handoff                                      | V15–V17/V22/V27–V30/V38     |
| BD-008/009/010/019; F4                  | Eredeti disposition külön, actual execution/QC/time/history, started scope immutable; recipient/time/proof                                | V13/V23/V28–V39             |
| Transaction/security totality           | Critical integrity nem kizárólag app-validáció; tiltott direct DML/partial helper/raw CALL és hibás actor/proof nem tartható meg          | V01–V03/V31/V33/V38/V40–V43 |

A CHECK/FK/U, trigger, routine és service/policy egymás felelősségét nem helyettesíti.
MySQL-ben a compound BEGIN…END nem automatikus atomic block; ezt a
[MySQL compound-block dokumentáció](https://dev.mysql.com/doc/refman/8.4/en/begin-end.html) is rögzíti.
A különálló SQL-állítások sikeres lefutása önmagában nem teljes parancsatomikusság.

## 5. Transaction model alternatives

### 5.1. Model A — Laravel-owned root transaction

Service indítja és commitolja/rollbackeli a rootot; restricted routine sem COMMIT-ot,
sem önálló ROLLBACK-ot nem végez. Error propagation SQLSTATE+exception, service abortál.
Előnye a jelenlegi boundary-khez való közelség. Hátránya: a PHP catch/rollback szerződés
nem kényszeríti a jogosult közvetlen CALL-kliens viselkedését. Ha az több állításból álló
routine hibáját elkapja, majd COMMIT-ol, részmunka megmaradhat. FK/CHECK csak a konkrét
állítások invariánsait védi; nem pótol egy hiányzó auditot/mandatory childot minden összetett parancsnál.

Nesting csak teljesen specifikált savepoint-composition mellett lehetne támogatott;
e terv első proofjában nem támogatott. A routine-ba rejtett savepoint-rollback vagy full rollback
már nem az itt definiált szigorú Model A. A protected execution-only principal önmagában sem
tiltja a kliens COMMIT-ját. A raw-call totality hiánya mellett A nem ajánlott.

### 5.2. Model B — Database-owned atomic business command

Laravel aktív alkalmazási transaction nélkül hívna egy teljes command wrapper routine-t;
az a START/COMMIT/ROLLBACK tulajdonosa. Egyetlen complete API, restricted DML és mandatory
DB-audit lehetséges. De C1 szerint a service közös tranzakciója tulajdonolja a rootot és a
finalizer nem commitol. B tehát **nem választható változatlan accepted C1 mellett**.
Kizárólag explicit későbbi architekturális amendment-review mérlegelhetné; e feladat nem kér
C1 üzleti döntéseinek újranyitását és nem állít ilyen jóváhagyást.

Egy aktív caller transactionbe csúszó START/COMMIT unrelated munkát is érinthet; Model B
nem „belső független transaction”. Level0 Laravel reconnect query-replay és commit-válasz
elvesztése miatt command-idempotencia továbbra kötelező. DB routine commit után Laravel
afterCommit esemény sem lesz automatikusan megfelelő callback. Külön outcome-driven dispatch kellene.

### 5.3. Model C — Dedicated RMA command execution boundary

Dedikált alkalmazási executor szolgáltatási rétegben, repository-n keresztüli typed commanddal.
A trusted service/executor marad a root/COMMIT tulajdonosa. A routine no COMMIT; explicit
root-only szerződésben a teljes **kizárólag e commandhoz tartozó** root abortját delegálhatja
DB error handlerre, RESIGNAL-lal. Ez nem routine-local rollback, nem általános composable helper.
Fizikai abort esetén az executor unwind/dispose után csak új rooton folytathat.

A C-javaslat a WP2 §13.2 profil célját őrzi, de nem fogadja el bizonyítékként a mode0 guardot.
Két driverprofil vizsgálandó: (1) WP2 dedicated mode0 lifecycle; (2) normál autocommit1+explicit
Laravel BEGIN egy **ténylegesen bizonyított** safe DB entry contracttal. Nincs itt választott
szerverváltozó, feltételezett `@@in_transaction`, P_S instrumentation vagy SQL DDL.
Ha a DB-belépési feltétel nem bizonyítható, a raw-no-BEGIN és caught-error+COMMIT tesztnek
blokkolnia kell C elfogadását. Nem fogadunk el app-only atomicityt „trusted executor” átnevezéssel.

A gateway hitelesítési/authorization határának és a connection-state bizonyítékának külön kell
elkészülnie. A posting credentialt ugyanazon process minden kódja elérheti, ha csak configkulccsal
választjuk le: ez **nem** credential-isolation proof. Deployment/process secret access, alternate
connection/helper tilalom, actor-binding és direct principal tesztek szükségesek. Compromised
privilegizált gateway/DBA nem válik ártalmatlanná egy routine-tól; a threat scope-t explicit rögzíteni kell.

### 5.4. Teljes működési összehasonlítás

| Szempont                       | Model A                                                | Model B                                                | Model C — proposed preferred profile                                                           |
| ------------------------------ | ------------------------------------------------------ | ------------------------------------------------------ | ---------------------------------------------------------------------------------------------- |
| Root / commit owner            | Laravel service                                        | DB public wrapper                                      | Dedikált service/executor, egy command/root                                                    |
| Routine rollback authority     | Nincs; caller abort                                    | Saját teljes command root                              | Csak delegált teljes saját root abort; soha nem unrelated caller                               |
| Laravel/PDO                    | Normál BEGIN/COMMIT, default manager                   | CALL level0; routine tranzakciója frameworken kívül    | Explicit connection, root depth/state/callback contract; actual proof hiányzik                 |
| Autocommit                     | Mode1 explicit root lehet valid; nem transaction-hiány | Clean no-active caller kell; mód nem ownership proof   | Mode0 vs mode1 explicit profile OPEN; érték nem authorization/clean-state proof                |
| Nesting                        | Csak külön bizonyított contracttal; első proofban deny | Aktív caller tilos                                     | RMA entry nested tilos, több connection caller-scope is vizsgálandó                            |
| Savepoints                     | Nem independent tx; domain rollback a calleré          | Nincs composable outer savepoint                       | `transN` nem használható root-helyettesítőként; rejection a CALL előtt                         |
| Exceptions                     | RESIGNAL → caller abort; catch+COMMIT kockázat         | Handler full rollback → error                          | Handler/executor abort + full unwind/disposal; guard-error külön teszt                         |
| Deadlock retry                 | Csak root tulajdonos, whole command                    | Új invocation ugyanazzal a key-jel, clear outcome után | Executor bounded whole root, fresh locks/guards, explicit classification                       |
| Unknown commit                 | Canonical outcome lookup, nem vak retry                | Ugyanez, Laravel level0 replay külön veszély           | Canonical primary lookup + WAIT/UNKNOWN, új command key tilos                                  |
| Idempotency                    | Namespace/key/hash és actual occurrence U szükséges    | Ugyanaz a wrapperen belül                              | Egy typed command/occurrence, audit/result ugyanazon T; replay authorization külön             |
| Restricted DML                 | FK/trigger/grants fontos, CALL partiality nem oldott   | Complete routine-only API                              | Complete API-only; helper DML/EXECUTE és alternate principal deny bizonyítandó                 |
| Connection reuse               | Nested/current service state megmaradhat               | Routine befejezését PDO-state nem mindig tükrözi       | Fresh nonpersistent lease, no shared transaction; failure után discard, success reset is teszt |
| Audit/evidence                 | Caller oldali hiány nem feltétlen DB-totalitás         | DB wrapperben mandatory, file külön                    | Mandatory audit+typed proof a protected commandban, ugyanazon connection                       |
| Existing service compatibility | Közel áll a jelenlegi closure-khöz                     | Nagy boundary-váltás, C1 konfliktus                    | Explicit adapter/orchestrator/fence; meglévő nested service nem hívható változatlanul          |

A transaction/autocommit megkülönböztetéshez az
[Oracle InnoDB leírás](https://dev.mysql.com/doc/refman/8.4/en/innodb-autocommit-commit-rollback.html)
az alap: mode1 mellett explicit root lehetséges, mode0 pedig nem bizonyítja a command tiszta kezdetét.
Root rollback minden savepointot elveszít; a
[savepoint dokumentáció](https://dev.mysql.com/doc/refman/8.4/en/savepoint.html) alapján nincs belőle
routine-local undo. A START/autocommit-váltás implicit boundary kockázatát az
[implicit-commit szabályok](https://dev.mysql.com/doc/refman/8.4/en/implicit-commit.html) alapján kell tesztelni.

## 6. Security and privilege architecture

### 6.1. Principals és hatáskörök — symbolic roles, nem account- vagy secret-konfiguráció

| Contract             | Model A                                                                            | Model B                                                                          | Model C                                                                                                |
| -------------------- | ---------------------------------------------------------------------------------- | -------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------ |
| Runtime ordinary `R` | Public complete routines EXECUTE; védett table DML nincs; raw catch kockázat marad | Csak complete wrapper EXECUTE                                                    | Ordinary HTTP/worker/report R-nincs posting/helper EXECUTE; csak engedélyezett read és gateway request |
| Posting `P`          | Ha külön van, caller-owned routine execution; ownership guard még kell             | Wrapper EXECUTE; saját DML nincs                                                 | Csak isolated gateway hozzáférése; complete command EXECUTE, saját table DML/DDL/helper EXECUTE nincs  |
| Migration `M`        | DDL/grant provisioning külön offline hozzáférés                                    | Ugyanez, wrapper install                                                         | Ugyanez, app/worker nem érheti el; törlés/restore külön jóváhagyott operátori feladat                  |
| Definer `D`          | Minimális routine DML/read; nem actor                                              | Wrapper minimális teljes command DML                                             | Posting és finalizer minimális scoped jogok; nem interaktív login, nem migration/admin principal       |
| Trigger              | Acquire claim, immutable UPDATE/DELETE guard, row shape; nincs sessionflag         | Ugyanez                                                                          | Ugyanez; close-only DELETE a bizonyított closed/completed/history tényekre támaszkodik                 |
| FK/CHECK/U           | Typed composite ownership, real FK, row shape/NULL, occurrence U                   | Ugyanez                                                                          | Ugyanez; aggregate quantityt current parent locks alatt complete routine kényszeríti                   |
| Finalizer            | No COMMIT/no rollback; raw partiality blocker                                      | Wrapper COMMIT-ja C1-conflict, belső finalizer sem kaphat általános delete jogot | Service rootban no COMMIT; complete close operation részeként csak own case claimjeit oldja            |
| Immutable history    | Runtime UPDATE/DELETE/TRUNCATE/ALTER tiltott                                       | Ugyanez                                                                          | Ugyanez; migration authority nem runtime bypass; correction új esemény                                 |
| Failure totality     | Caller rollback nem raw-client guarantee                                           | Saját wrapper root, C1 incompatibility                                           | DB error abort + executor unwind; no-BEGIN/dirty/nested/raw tests kötelezők                            |
| Lock order           | Shared L és minden író szükséges                                                   | Ugyanez                                                                          | Ugyanez, command entry+parent lock order és előre felderített shared scope                             |
| Cross-service        | Ugyanazon root, testelt nesting nélkül nem composable                              | Külön DB command nem atomi a korábbi callerrel                                   | Root orchestrator alakítja át a hívókat; külön connection commit nem tehető atomikusnak elnevezéssel   |

Minden jogprofilnál a globális/schema/table/column/routine grant, inherited/default/mandatory role,
definer és alternatív login/connection együttes auditja kell. Nincs általános DML-proxy vagy
dinamikus SQL-bemenet. Runtime nem kaphat TRIGGER/ALTER/DROP/TRUNCATE/CREATE ROUTINE/GRANT
jogot vagy migration credentialt. Az
[Oracle stored-object security](https://dev.mysql.com/doc/refman/8.4/en/stored-objects-security.html)
szerint a definer és invoker külön authority; a definer a szükséges minimumot kapja.

### 6.2. Actor, command identity és proof

DB login, routine definer, hitelesített alkalmazási actor és üzleti owner külön azonosítók.
`actor_id` bemenet létező FK-ja nem bizonyítja a hívó személyét. A backend policy és trusted
gateway kösse az actort operation/case/owner/payload/version kontextushoz; a DB ellenőrizze
az összetartozó typed kapcsolatokat és aktuális mechanikus jogosultsági tényeket. E binding
konkrét interfésze **OPEN**. Shared principal mellett önmagában egy tetszőleges actor ID nem elfogadható.
Sem `@allow_delete`, sem `@actor_id`, sem autocommit mód nem authorization-kapu.

Command identity: meglévő proposed `repair_commands` namespace+stable key, canonical payload hash,
operation/owner/actor/version binding; actual physical/legal occurrence külön U-kulcs. Azonos
command azonos payload mellett ugyanazt a committed resultot adhatja, más payload/owner/actor
nem írja át a korábbit. A read/replay hozzáférés is policyhoz kötött. Egy abortált T nem hagyhat
`completed` commandot. Nem vezetünk be új command-state enumot, retention vagy key-expiry szabályt.

Close finalizer a case-t current lock alatt ellenőrzi: összes member/order, original outcome,
replacement attempt/return/admission/quarantine/disposition/ownership/procurement/credit
obligation és typed proof, QC/time/material/authorization/history összetartozik. Nem `proof_ok=true`
vagy státuszlista az authority. Close history + completed command + closed state + all claims
release + mandatory audit együtt commitol vagy együtt eltűnik. Ugyanazon T-n belül létrehozott
completed fact még nem külső sikerválasz; audit-hibával az is rollbackel.

### 6.3. Locks és shared writers

WP2 L megmarad: command idempotency serialization → case ID-k → items → item_instances →
repair/production orders → operation/fee/quote → requirements/coverage → reservations → balances →
release/proof. Minden csoportban növekvő stabil ID. Proposed procurement alcsoport:
requirements → Ghead → PR header/line/source → PO header/item → GR header/item.
Implicit FK/trigger/unique lockok is a terv részei; nem állítunk deadlock-mentességet.

Az érintett case/owner halmazt még későbbi lockok előtt kell felfedezni; lock alatt újraellenőrizni,
változáskor egész root retry. FOR UPDATE current reads, parent mutex az üres gyermekhalmazra is;
RR snapshot SUM és SKIP LOCKED nem rejthet el close/quantity obligationt. A mostani GR-header-first
és production balance-first írókat nem lehet a proposed L mellé változatlanul bekötni.
Shared táblák DML-revoke-ja az át nem állított production/GR útvonalat eltörheti; ezért G2 teljes
writer fence/adaptáció és privilege cutover előfeltétel, nem későbbi optimalizáció.

## 7. Laravel/PDO/MySQL integration

### 7.1. Root ownership állapotgép — leíró protokoll, nem executable pszeudokód

1. **Belépés:** typed command, backend authorization és actor-binding ellenőrzése; nincs business DML.
   A request/worker részt vevő default és explicit connection caller-tranzakcióit is ellenőrizni kell.
   Nested vagy unrelated caller scope esetén a gateway megtagadja a hívást a routine előtt;
   az addigi caller-munka nem rollbackelhető/commitolható a megtagadás mellékhatásaként.
2. **Lease:** kizárólagosan birtokolt, nonpersistent kapcsolat a megfelelő principalon; nincs pending
   resultset, callback, korábbi transaction vagy borrowed Eloquent kapcsolat. Secret/principal/server/
   driver/profile identity rögzítése sanitizált metadata formában. Depth0 önmagában nem elég.
3. **Root start:** a jóváhagyásra kerülő driverprofil szerint a service indítja a gyökértranzakciót.
   Laravel level/PDO-state/server mód és tényleges boundary együtt mérendő; sem mode0, sem mode1
   nem proof egyedül. Az exact DB safe entry guard specifikációja és MySQL próbája blokkoló előfeltétel.
4. **Complete CALL:** csak engedélyezett typed complete operation; helper nem publikus. Same-T
   command serialization, current guards, összes domain/fact/projection/proof/history/audit írás.
   A CALL összes resultsetjét üríteni kell a következő query/commit előtt. Nincs külső I/O vagy DDL.
5. **Success:** routine nem commitol; service ellenőrzi a canonical resultot és egyedül commitol.
   A business result a committed command alapján közölhető. AfterCommit/cache/queue csak ezután,
   az owning connectionhöz kötve. Ezek nem mandatory DB-audit helyettesítői.
6. **Error:** SQL/domain/audit/proof-hiba esetén a delegált handler az owned rootot abortálja és
   az eredeti okot továbbadja. Executor nem folytatja az abortált scope-ot; Laravel/PDO/callback
   bookkeeping teljes unwind, disposal. Cleanup-hiba nem fedheti el az eredeti SQLSTATE-t és nem
   jelenthet sikert. Elutasított entrynél a handler nem abortálhat idegen előmunkát.
7. **Outcome resolution:** COMMIT körüli kapcsolatvesztés UNKNOWN. Friss, megbízható primary
   kapcsolaton canonical key/hash/result lookup; pending competing commitet is meg kell várni
   vagy UNKNOWN marad. Replica hiányzó sora nem rollback proof. External dispatch külön egyeztetendő.
8. **Retry/dispose:** csak teljes command retry, azonos key/occurrence/hash; fresh root és friss
   authorization/current guards. Discard/reset minden hibás vagy bizonytalan connection után;
   következő job/request sem callbacket, sem session módot nem örökölhet. Sikeres reuse is tesztelendő.

### 7.2. Hibaosztályok és rollback hatáskör

| Helyzet                       | Tulajdonos teendője                                                    | Tiltott következtetés                                      | Kötelező evidence                                             |
| ----------------------------- | ---------------------------------------------------------------------- | ---------------------------------------------------------- | ------------------------------------------------------------- |
| Domain/FK/U/CHECK/audit error | Teljes saját command abort, safe domain error; exact layer-state trace | SIGNAL önmagában visszavonta a korábbi routine-állításokat | V38/V43 minden write-fault point és caught-error+COMMIT       |
| 1213 deadlock                 | Teljes root elveszettként kezelni, bounded fresh-root retry            | Savepoint csak a routine részét vonta vissza               | V40/V42/V43 két session, minden előmunka és callback állapota |
| 1205 lock timeout             | Explicit egész root abort, majd csak policy szerinti bounded retry     | A statement rollback teljes command rollback volt          | V40/V43 actual timeout/rollback setting és partial rows       |
| Nested caller rejection       | Még CALL/transaction-váltás előtt refuse, caller U érintetlen          | Új connection miatt független commit biztonságos           | V42/V43/V48 caller U egy és több connectionön                 |
| Unknown commit                | Canonical result resolution; nincs új üzleti command key               | Network exception=rollback; hiányzó replica sor=nem futott | V06/V40/V43 primary outcome, payload és actual occurrence     |
| AfterCommit cache failure     | Committed result megtartása, külön cache-helyreállítás                 | Cache exception visszagörgette a business T-t              | V38/V40/V48 command, audit és cache generation külön trace    |

Az engine rollback-különbségeket az
[Oracle InnoDB error handling](https://dev.mysql.com/doc/refman/8.4/en/innodb-error-handling.html)
alapján kezeljük. A konkrét retry limit/backoff/deadline **OPEN**, nem új üzleti szabály;
fault injection és measured latency után technical owner rögzíti. Validation/permission/proof-hibát
nem alakítunk automatikusan retryvá. A PHP
[beginTransaction](https://www.php.net/manual/en/pdo.begintransaction.php) és
[inTransaction](https://www.php.net/manual/en/pdo.intransaction.php) dokumentáció driverállapotot
ír le; nem bizonyít application ownershipot vagy teljes schema-tranzakcióképességet.

### 7.3. Cross-service és audit korlátok

Nem elfogadható outer production transaction U mellett külön RMA connectionen commitolni, majd
az outert rollbackelni és közös atomikusságot állítani. A közös üzleti műveletet az engedélyezett
root orchestratornak kell végrehajtania ugyanazon connectionön, transaction-neutral belső
persistence műveletekkel és teljes locktervvel; vagy külön jövőbeli üzleti kompenzációs döntés kell.
Ilyen saga/új üzleti szabály itt nincs elfogadva. A jelenlegi nested service-ek átalakítását G2
hatásleltár és teszt köteles megelőzni. Pusztán `DB::setDefaultConnection` átállítás process-global
szivárgási kockázat; nincs ilyen shortcut kiválasztva.

A mandatory RMA audit protected DB commandon belül készül, nem opcionális afterCommit activity.
Ha a meglévő Activitylogot használjuk, a dedicated connection, enabled/buffer/retention és grants
konkrét illesztése bizonyítandó; kettős vagy másik connectionön autocommitolt audit nem atomikus.
A byte-proof staging/retention fájlrendszer-oldala nem DB rollback; létező immutable file manifest
és typed DB proof szükséges, sikertelen attempt maradványai külön, jóváhagyott cleanup tárgyai.

## 8. Verification environment

### 8.1. Jövőbeli izolált környezet és jelenlegi kompatibilitási rés

| Elem        | Előírt jövőbeli contract                                                                                                                                              | Jelen bizonyíték / gap                                                                                                            |
| ----------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------- |
| Engine      | MySQL **8.4**, InnoDB minden részt vevő táblán; exact patch/image digest, charset/collation/strict SQL mode/UTC/isolation rögzítve                                    | Nincs szerverkapcsolat; B 8.4.7 történeti, nem current environment proof                                                          |
| App/driver  | Exact composer lock+vendor ref, PHP/PDO/mysqlnd build, native prepares/error mode, resultset handling, persistence/proxy és worker lifecycle manifest                 | Lokális Laravel/PHP forrás/verzió ismert; deploy/CI driverprofil UNVERIFIED                                                       |
| Isolation   | Eldobható dedikált server/container és explicit allowlisted test DB; development/production adat és network elérés tiltott; DB_URL/prod fallback nem használható      | Nincs provision; új környezethez külön engedély kell                                                                              |
| Principals  | R/P/report/other-owner és D/M külön; M csak setup/teardown workerben, runtime secret-hozzáférés nélkül                                                                | Nincs actual SHOW GRANTS vagy login próba                                                                                         |
| Connections | Két valódi független writer + harmadik read-only observer, szükség szerint külön primary outcome lookup                                                               | Nem két egymás utáni HTTP kérés; server connection ID/barrier/lock trace szükséges                                                |
| Schema      | Exact reviewed minimal proof schema/routines/triggers/constraints hash; később exact full25+18/7 catalog; sem reduced prototype, sem production migráció nem inferált | Complete executable RMA DDL hiányzik; minimal proof sikere sem zár minden domaint                                                 |
| Faults      | Determinisztikus harness barrier/fault schedule; after each write, audit/proof failure, 1213/1205, process kill, COMMIT előtti/utáni válaszvesztés                    | Fault hook csak később izolált fixtureben, nem production flag vagy session bypass                                                |
| Secrets     | Runner secret store/process injection, kötelező explicit konfiguráció; no fallback/reuse Laravel prod credentials; artifact redaction                                 | Nincs secret read vagy új config; jövőbeli változónevek/secret mapping külön review                                               |
| CI          | Meglévő layered gates mellett külön guardolt MySQL-integrity job, ephemeral DB/roles, timeout, always artifact; multi-connection/non-wrapper execution                | [backend-quality workflow](../../.github/workflows/backend-quality.yml) MySQL8.4/PHP8.4 alapot ad, nem RMA role/transaction proof |
| SQLite      | Csak portable shape/FK/feature regresszió; MySQL grants/routines/row locks/deadlocks nem igazolhatók vele                                                             | V47 külön compatibility evidence, MySQL-specifikus N/A nem MySQL PASS                                                             |

Kritikus jelenlegi kompatibilitási rés: a
[TestEnvironmentGuard](../../app/Support/Testing/TestEnvironmentGuard.php)
`assertSafeMysqlConfiguration` explicit test DB névmintát, loopback/CI hostot és **egyetlen
dedikált test principal nevét** engedi. A [TestCase](../../tests/TestCase.php) `createApplication`
is ezt hívja. A [runner](../../scripts/backend-test-environment.php) ugyanarra a single-principal
konfigurációra épül. Ez jó meglévő guard, de nem kész multi-role security harness.

Nem szabad a külön principalokat ugyanannak a felhasználónévnek álcázni vagy a guardot kikapcsolni.
Későbbi, külön review-zott bővítés kell: explicit allowlist minden test principalra, külön setup és
runtime scope, kapcsolatonként guardolt DB/host/role, negatív guard-tesztek. A széles `testing`
environment flag nem safety proof. A jelenlegi CI tesztfixture account-/password-definícióit nem
másoljuk új secret-kontraktusként. E feladat sem runner-, sem workflow-, sem guardmódosítást nem végez.

### 8.2. Fixture katalógus

| Fixture label | Kötelező tartalom, baseline és tiltott helyettesítés                                                                                                                                    |
| ------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| TX            | Minimális complete command + fact/projection/history/audit/completed result, minden write után injektálható hibapont; typed owner/actor; sem happy-only counter demo                    |
| U             | Unrelated caller sentinel row+callback, kizárólag saját izolált test adat; before/after primary visibility, same és different connection                                                |
| CLAIM         | Két serial, két case/owner, több member/order; teljes és minden kötelező proofból egyenként hiányos close; claim PK/FK/trigger/finalizer                                                |
| STOCK         | Egy exact pool Q=10, reservation=6, consume=2, release=1 → remaining=3; verified eligible physical 8, unrelated availability5 azonos cohortban; quarantine/custody/pending serial külön |
| RETURN        | Valódi shipment és separate I/MI, A/MA, proof/rights/inspection; pending owner unknown; duplicate occurrences és admission company+1 csak igazolt esetben                               |
| CREDIT        | Ugyanazon source order két quote/fee-version, approved-only effective allocation, unused/unsettled vs unknown/executed/settled observations                                             |
| COVER         | Egy requirement/generation több source-line; conserved budget, PR/PO/GR-item partial receipt és owner-distribution; source snapshot nem plusz ledger delta                              |
| AUTH          | R/P/report/other owner/invalid actor/revoked permission; same ID más case context, disabled proof/audit; nem valódi személyes adat                                                      |
| FILE          | Disposable byte objects, immutable version/checksum manifest és typed proof; absent/tampered/retained/deleted-access variáns                                                            |
| REUSE         | Request és long-lived worker két egymást követő commanddal; dirty mode/cursor/callback/connection-loss variánsok                                                                        |

A STOCK számok egyetlen engedélyezett synthetic tesztállapotot írnak le, nem legacy backfillt.
A fixture factory a választott állapotokat az elfogadott creation order és protected commandokkal
hozza létre; privileged invalid seeding kizárólag setup principalon, elkülönítve a runtime
negative operationtől. Nem szabad privilegizált fixture-mentést runtime PASS-nak számolni.

### 8.3. Evidence és cleanup protokoll

Minden jövőbeli case kap run ID-t, canonical V ID + leíró subcase labelt, commit/dirty-doc hashes,
schema/routine/trigger/grant hash, driver/server/setting manifest, principal role label, seed és
barrier/fault schedule, expected assertions, actual SQLSTATE/vendor code, application exception,
before/after DB rows/decimal totals és observer visibility, Laravel depth/PDO/server/session ID trace,
commit/rollback/callback order, redacted logs, elapsed timeout és cleanup result mezőt.
No connection string/password/token/full personal data a query bindingsban vagy artifactban.
Az actor proof minimalizált synthetic azonosító. Secrets és privilegizált audit metadata védett csatornán,
megosztható artifactban csak szükséges szerep/jog/type/hash.

Cleanup: először mindkét writer teljes abort/disconnect, callback és cursor lezárása, observer snapshot
és evidence manifest tartósítása; majd kizárólag verified run-owned allowlisted DB/container/files
destroy a setup operátorral. Nem rollback-wrapperbe csomagolt teszt, amely elfedi a commitot vagy
összekeveri a nested depth-et. Recovery/unknown-outcome evidence export előtt nincs törlés.
Elakadt worker/cleanup failure esetén karantén és operátori jelzés, nem tágított DROP/kill parancs.
CI always-artifact redaction után is kell; timeout FAIL/BLOCKED, nem sikerként elnyelt exception.

## 9. Test and evidence matrix

### 9.1. Végrehajtási szabály és assertion-oracle

Minden alábbi sor státusza **PLANNED / NOT EXECUTED**, az execution jelenleg **BLOCKED**
WP7-F01/F02 előfeltételeken. A leíró labels nem új kanonikus requirement-ID-k.
Alapmodell C; ahol szerepel, A/B counterexample/profilpróba külön, approved disposable sandboxban.
Minden sorra a §8.3 evidence/cleanup szerződés érvényes; a cleanup oszlop konkrét kiegészítéseket ad.

Két eredményt külön kell vezetni: **operation outcome** (SUCCESS/REJECTED/ERROR/UNKNOWN) és
**invariant verdict** (PASS/FAIL/BLOCKED/UNVERIFIED). Tiltott írás REJECTED + sértetlen adat lehet
PASS; exception részleges commit mellett FAIL; happy-path SUCCESS hibás balance mellett FAIL.
Régi B PASS nem tölthető át új runtime eredményként.

Az observer minden összehasonlításhoz friss tranzakciót/current readet használ: egy korábban
megnyitott REPEATABLE READ snapshot nem bizonyítja, hogy a másik session commitja hiányzik.
A command részleges láthatóságát primaryn, minden érintett táblán és azonos run/occurrence
azonosítón kell ellenőrizni; a hibás művelet előtti saját U sentinel külön assertion.

### 9.2. Ownership, nesting, PDO és security

| Subcase / kapcsolódó WP7 ID                      | Preconditions                                                    | Test principal                                 | Fixture         | Operation                                                                                     | Expected result                                                                                                     | Required DB evidence                                                    | Required application evidence                                            | Cleanup                                                 |
| ------------------------------------------------ | ---------------------------------------------------------------- | ---------------------------------------------- | --------------- | --------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------- | ------------------------------------------------------------------------ | ------------------------------------------------------- |
| root success — V38/V42/V43                       | Approved exact profile, fresh lease, depth0, no caller scope     | P via gateway; observer                        | TX              | Start root, complete CALL, pause before commit, service commit                                | SUCCESS; observer előtt0, commit után teljes fact+projection+history+audit+result                                   | Before/after rows és visibility, egy command/occurrence, boundary trace | Level0→1→0, PDO/state/profile és callback csak commit után               | Observer snapshot után close/reset                      |
| root caller rollback — V38/V42/V43               | Valid complete CALL, root még nincs commitolva                   | P via gateway                                  | TX              | Service kifejezetten abortál a CALL sikere után                                               | No durable business/result/audit; SQL success nem business commit                                                   | Minden row absent/unchanged, lock feloldás                              | Rollback trace, nincs success callback                                   | Kapcsolat discard, new-root probe                       |
| mode1 no BEGIN — V42/V43                         | Fresh raw client, mode1/PDO inactive                             | P direct negative probe                        | TX              | Protected CALL BEGIN nélkül; success és fault branch                                          | Exact safe contract refusal no effect; ha félírás vagy téves success lehet, profil FAIL                             | Persisted all-table delta0 hibás úton, guard/handler order              | Direct-client trace és exception sanitized                               | Discard; exact committed snapshot őrzendő               |
| mode1 explicit root — V42/V43                    | Explicit Laravel BEGIN, mode1; separately caller U variant       | P via gateway/raw                              | TX+U            | Invoke chosen profile, valid és guard rejection                                               | Mode1 nem tx-hiány; selected profile szerinti accept/refuse, U váratlan rollback/commit nélkül                      | U és TX visibility, active boundary/settings                            | Depth/PDO/exception/handler timing                                       | U-t csak saját caller zárja; P discard                  |
| mode0 clean — V42/V43                            | Approved mode0 fresh session, no prior DML                       | P via planned adapter                          | TX              | Actual BEGIN/driver sequence, success+abort                                                   | Csak tesztelt driverlifecyle fogadható el; nincs implicit caller commit                                             | SQL/session boundary, atomic rows                                       | PDO state és Laravel manager count/callback minden lépésnél              | Mode reset helyett szükség esetén connection destroy    |
| mode0 dirty — V42/V43                            | Mode0 sessionen U előmunka, framework depth0 is variáns          | P direct/gateway negative                      | U+TX            | Kísérlet borrowed/dirty connectionnel belépni                                                 | Refusal before destructive boundary; ha guard ezt nem képes garantálni, boundary not acceptable                     | U nem válhat committed/rollbacked mellékhatássá, TX0                    | Lease origin és refusal helye, nem puszta mode check                     | Caller-owned U saját lezárása, dirty connection discard |
| Laravel nested rejection — V42/V43               | Default outer root U, inner trans2 savepoint                     | R caller → gateway                             | U               | Entry, még CALL előtt                                                                         | REJECTED, existing U/savepoint/callback sértetlen; nincs RMA commit                                                 | U láthatóság és savepoint saját caller általi használhatóság            | Guard depth/connection identity, exception catch/unwind                  | Caller saját rollbackje; no executor rollback of U      |
| alternate connection nesting — V42/V43/V48       | Default conn U aktív, dedicated conn depth0                      | R caller → P gateway                           | U+TX            | RMA command request same workflowből                                                          | Reject unexpected independent commit; explicit redesigned orchestration nélkül TX0                                  | U/TX külön session outcome                                              | Caller-scope inventory minden participating connectionre                 | Mindkét sessiont saját owner abortálja                  |
| savepoint invalidation probe — V42/V43           | Izolált adversarial nested embedding, full-root abort profile    | P direct experiment                            | U+TX            | Body fault, caller inner rollback/savepoint használat                                         | Full root abort kimutatva; composition tilos; observed1305 ha van rögzítendő, nem előre feltételezett PASS          | Root/U/savepoint state és committed0                                    | Laravel depth/callback/PDO disagreement, eredeti és cleanup error külön  | Kötelező teljes unwind/discard; tiltott folytatás       |
| routine exception propagation — V38/V43          | Valós routine guard/body error                                   | P via gateway                                  | TX+AUTH         | Controlled SIGNAL/FK/duplicate fault                                                          | Error fajtája megmarad, nincs swallowed-success vagy félállapot                                                     | SQLSTATE/vendor code és zero retained effect                            | Domain mapping sanitized, original cause és cleanup elkülönítve          | Error lease discard                                     |
| direct DML denial — V31/V33/V41                  | Exact actual privileges, legal existing rows                     | R/P/report                                     | CLAIM+CREDIT+TX | INSERT/UPDATE/DELETE protected tables, claim DELETE, close update, TRUNCATE/DDL attempts      | Tiltott minden alternatív entryn; no changed row/object                                                             | Effective grants/roles, denied codes, catalog/hash unchanged            | Direct driver outcomes nem policy mock                                   | No DDL effect; privilege fixture külön teardown         |
| authorized routine/helper — V31/V41/V43          | Scoped complete API installed, valid command                     | P; R/report; direct P helper                   | TX+CLAIM        | Valid public command; R/report CALL; P helper CALL                                            | Csak scoped P complete operation SUCCESS; más login/helper reject                                                   | Grants/definer graph, full result és denied helper delta0               | Same connection audit/actor, returned result                             | Complete command artifact után dispose                  |
| invalid actor/owner — V14/V28/V41/V43            | Valid IDs rossz authority/case/owner kontextussal, revoked actor | P with invalid bound request; R forged request | AUTH+CLAIM      | Complete command forged actor/owner/payload                                                   | Refuse no effects; definer identity nem actor; no policy-only PASS                                                  | Typed tuples/ACL binding/current guards és all-table delta0             | Gateway auth provenance, direct forged CALL negatív trace                | Fixture scope megőrzés, principals revoke teardown      |
| principal isolation — V41/V43                    | R ordinary process, secret isolation policy, alternate aliases   | R/report + P                                   | AUTH            | Access gateway secret/connection, inherited role, generic SQL/helper paths; P command control | Ordinary paths deny; P csak complete contract; unknown secret topology BLOCKED                                      | Effective privilege closure minden loginon, allowed/denied object list  | Deploy/worker secret access evidence redacted, alias/default route trace | Temporary role/access teardown M-mel                    |
| entry rejection handler — V42/V43                | Wrong mode/type/actor, korábban U a veszélyes callerben          | P direct negative                              | U+TX            | Guard error handler külön fault path                                                          | No unexpected U abort/commit; nem elég TX partial0; ha nincs reliable boundary, FAIL/BLOCKED                        | U és TX beforeafter, rollback timing                                    | CALL előtti vs belső rejection, full cause trace                         | U saját owner rendezi, P discard                        |
| autocommit/implicit boundary abuse — V41/V42/V43 | P session root aktív, malicious direct client                    | P direct negative                              | U+TX            | Mode change/START/COMMIT attempts a profilon kívül                                            | Nincs generic SQL transaction-control deny feltételezés; elérhető út explicit threat scope és DB totality vizsgálat | Boundary/row trace és grants; profile-bypass nem success                | Allowed credential exposure és actual guard outcomes                     | Teljes sandbox discard evidence után                    |

### 9.3. Atomicity, concurrency, retry és integráció

| Subcase / kapcsolódó WP7 ID                                      | Preconditions                                                                | Test principal                  | Fixture                         | Operation                                                                                                     | Expected result                                                                                | Required DB evidence                                                             | Required application evidence                                                | Cleanup                                                  |
| ---------------------------------------------------------------- | ---------------------------------------------------------------------------- | ------------------------------- | ------------------------------- | ------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------- | ---------------------------------------------------------------------------- | -------------------------------------------------------- |
| each-write fault — V01/V38/V43                                   | Reviewed complete command write-set és fault schedule                        | P gateway/direct                | TX plus domain fixture          | Hiba minden fact/projection/child/counter/result írás után, külön run                                         | Owned root all-or-none; nincs completed partial state                                          | Exact row/q baseline minden write-faultnál                                       | Exception és all-layer unwind, nincs callback                                | Runonként fresh DB state/lease                           |
| audit failure — V28/V38/V43                                      | Business/projection már megírva, audit kötelező                              | P                               | TX+CLAIM                        | Audit insert/constraint/permission fault a completed marker körül is                                          | All rollback; audit nem opcionális, no successful claim release                                | Claim/case/history/command/audit teljes snapshot                                 | Same connection, buffered/disabled audit nem megengedett profile             | Abort/dispose; evidence export                           |
| partial evidence failure — V28/V38/V39/V43                       | Több typed proofból egy hiányzó/idegen/invalid version                       | P                               | FILE+CLAIM/RETURN               | Missing DB proof vagy retained bytes ellenőrzési hiba, köztes DML után is                                     | No partial close/admission, prior immutable file nem módosul                                   | Typed proof/effect0 és file manifest                                             | Validation/fault trace, file vs DB failure külön                             | Csak új owned orphan file külön guarded cleanup          |
| caught error then COMMIT — V38/V43                               | Legit P direct CALL, handlerben/mid-body forced error                        | P direct                        | TX                              | Kliens catch után COMMIT, új statement/command kísérlet                                                       | No partial retained work; aborted root nem folytatható successful commandként                  | Minden table committed snapshot, result/audit0 vagy teljes legális külön command | Raw trace plus framework negative variant                                    | Connection discard, snapshot csak utána cleanup          |
| duplicate same command — V05/V06/V40                             | Canonical bytes/hash és committed result, same actor authority               | P two sessions                  | TX+RETURN/COVER                 | Azonos key+payload szekvenciálisan és barrierrel concurrent                                                   | Egy effect/result; retry meglévő eredmény vagy várakozás után replay                           | U occurrence/command cardinality1, q egyszer                                     | Same result identity, no duplicate external dispatch                         | Both sessions terminal, observer snapshot                |
| duplicate changed payload — V05/V06/V14                          | Committed key, actor/owner/q/hash egyike eltér                               | P                               | AUTH+TX                         | Ugyanaz key, eltérő request                                                                                   | Conflict/no overwrite; lookup sem szivárogtat más owner resultot                               | Eredeti hash/result/history változatlan                                          | Conflict és replay policy trace                                              | Lease reset, eredeti fixture megmarad                    |
| concurrent domain conflict — V07–V12/V15–V17/V20/V21/V25/V30/V40 | Két valódi writer, predetermined barrier és ugyanazon parent/pool            | P1/P2; owner variánsok          | CLAIM/STOCK/COVER/RETURN/CREDIT | Mindkét commit-sorrend, same és distinct command keys                                                         | Csak engedett serialization, quantity/claim/credit cap nem sérül; loser safe retry/conflict    | Wait/lock/outcome/cardinality/exact totals, parent current reads                 | Barrier timeline és két app trace                                            | Both roots closed before teardown                        |
| deadlock1213 — V38/V40/V42/V43                                   | Tudatos barrier-cycle, M előkészítve, unrelated command kizárt               | P1/P2                           | TX+STOCK                        | Invertált test lock acquisition; whole-root retry                                                             | Victim full root abort; bounded same-key retry, at most one business occurrence                | Actual1213, both roots state/locks, durable counts                               | Detector/attempt timeline, PDO/depth cleanup, no partial retry               | Surviving root terminal; fresh lease                     |
| timeout1205 — V38/V40/V43                                        | Writer1 lock tart, writer2 rövid test timeout, actual rollback mode rögzítve | P1/P2                           | TX+STOCK                        | Writer2 partial writes után lock timeout                                                                      | Whole command abort akkor is, ha engine csak failed statementet rollbackelte; retry bounded    | Actual1205/settings, preceding writes absent                                     | Root abort és retry classification explicit, safe timeout result             | Writer1 release; both leases dispose                     |
| unknown COMMIT outcome — V06/V40/V43                             | Network fault hook explicit commit boundaryn, canonical lookup primaryn      | P + observer/lookup             | TX+RETURN                       | Válaszvesztés commit előtt, server commit után és competing in-flight variáns                                 | UNKNOWN amíg nincs proof; full result vagy biztonságos same-key reexecute, no duplicate        | Primary committed command/occurrence, wait és actual domain effect               | Outcome resolution és external ack reconciliation; no blind new key          | Nincs cleanup amíg outcome unresolved                    |
| retry policy/guard drift — V06/V14/V40/V43                       | Retry előtt permission/owner/qty guard változik                              | P                               | AUTH+STOCK                      | Deadlock/timeout retry fresh rooton, majd validation failure                                                  | Current guard újraértékelve; invalid út nem retryzik végtelenül                                | New current lock/read/result0 vagy korábbi canonical result                      | Attempt limit/backoff/deadline és no unsafe replay                           | Fresh connections, clocks/fault controls reset           |
| connection reuse — V38/V42/V43                                   | Error/success/disconnect után következő request/worker command               | P, majd más actor               | REUSE+TX                        | Cursor, mode, callback, depth szennyezés; reconnect/read alias váltás                                         | No prior actor/tx/callback/cursor; bad lease discarded, fresh command only                     | Session IDs/state, second command izolált q/history                              | Pool/manager/reference/callback lifecycle trace, resultsets drained          | Both leases és worker clean shutdown                     |
| cross-service root composition — V22/V38/V42/V43/V48             | ProductionPlan/GR/reserve lánc, default+dedicated variants                   | R orchestrator/P                | STOCK+COVER+U                   | RMA hívás outer closure alatt; később authorized redesigned single-root path, forced last-step error          | Unadapted nested deny; redesigned összes service írás egy rooton rollbackel                    | Movement/balance/reservation/coverage/order/U/audit együtt                       | Connection identity minden model/repo/audit/callback írásnál                 | Saját rootok unwind, no independent RMA commit           |
| afterCommit cache fault — V06/V38/V40/V48                        | Business commit biztosan megtörtént, callback fault                          | P gateway                       | TX+REUSE                        | Invalidation/notification hiba, caller retry same key                                                         | Committed business egyszer; cache failure külön helyreállítandó                                | Command+domain+audit egy complete instance                                       | Commit vs callback timestamp, result lookup és cache retry, no posting retry | Cache-only cleanup, DB fact megőrzés evidence-ig         |
| shared inventory remaining — V04/V08–V12/V14/V48                 | STOCK exact values, production és repair ugyanazon pool                      | P1/P2+report                    | STOCK                           | Partial consume/release, consume vs release, saját/idegen owner és quarantine selection                       | Counters bounded, remaining3; no double deduction; custody/pending/quarantine E0               | Exact decimal movement/projection/reservation fold és raw writer denial          | Minden affected consumer export/cache/backend result egyezik                 | Original proof/historyt nem seed cleanup mutálja         |
| return/credit/coverage atomic bridge — V13/V15–V27/V38/V43/V48   | Typed I/MI,A/MA; fee/quote/source ledger fixturek                            | P plus invalid owner            | RETURN+CREDIT+COVER             | Intake/admission külön, credit approval/cancel/release, multi-line/partial GR minden mandatory write faulttal | Accepted AM-G1–G4/D6–D11 megmarad; no automatic owner/warranty/credit/supplier cancellation    | Each occurrence+ledger+movement+projection+audit once, exact source qty          | Domain guard/current approval/proof/connection trace                         | Facts export, sandbox-only teardown                      |
| finalizer protection — V07/V28–V33/V38/V41–V43                   | Több member, összes obligation; egyenként hiányos proof                      | P/R/report + concurrent child P | CLAIM+RETURN+CREDIT+COVER+FILE  | Raw claim delete/close; valid close vs child writer; repeated close és fault                                  | Raw deny, invalid close no effect; valid full close exactly once, no reopen                    | All case claims/case/history/command/audit atomic, FK/trigger protection         | Full proof-set/current lock/policy trace, no COMMIT inside finalizer         | Close result evidence előtt nincs DB destroy             |
| environment guard/CI evidence — V41/V42/V43/V46/V48              | Explicit multi-role guard contract és CI manifest approved                   | M setup, R/P runtime            | AUTH+REUSE                      | Wrong DB/host/role/secret absence refusal; valid ephemeral job és injected failed assertion                   | Unsafe target még connect/write előtt deny; test-failure job nem zöld; no secrets in artifacts | Only selected disposable DB/objects accessed, privilege separation               | Guard reports, exit codes, redaction/artifact/cleanup manifest               | M csak run-owned resources, cleanup failure külön status |

### 9.4. V01–V48 teljes traceability és scope

Az alábbi family-k mind a [WP7 kanonikus mátrixára](rma-0003c2-wp7-verification-migration-readiness.md#44-canonical-evidence-matrix)
utalnak; az ottani státuszokat e dokumentum nem frissíti. A fókuszált próbák sem a full schema,
sem a teljes RMA lifecycle bizonyítékát nem helyettesítik.

| Kanonikus család | C3-01 kapcsolódás és későbbi bizonyíték                                                                                               |
| ---------------- | ------------------------------------------------------------------------------------------------------------------------------------- |
| V01–V03          | FK cycle/totality, CHECK/action/NULL shape; root+each-write rollback prerequisite, exact catalog nélkül blokkolt                      |
| V04–V06          | Pool identity és machine-key codec; duplicate command/unknown outcome proof                                                           |
| V07              | Claim acquire és finalizer protection complete transactionben                                                                         |
| V08–V12          | Reserve/partial consume/release/terminal/race shared root, counters és owner/remaining                                                |
| V13–V14          | Inverse material/shipment FK és owner/item/unit tuples; mandatory-child/audit atomicity                                               |
| V15–V17          | Multi-line generation, budget/missing line és shared-owner race; nem U(req,gen) source constraint                                     |
| V18–V21          | Intake/admission/dedup/quarantine release a complete operation és concurrent writers alatt                                            |
| V22–V24          | GR conversion/redispatch/ownership/reversal separate facts és retry/rollback                                                          |
| V25–V27          | Approval-only fee credit, usage/correction/D7 release; partial proof failure és current guards                                        |
| V28–V32          | Hiányos proof/multiple member/child race/raw claim/close replay no reopen, DB finalizer scope                                         |
| V33              | Raw credit és immutable history bypass deny minden principalon                                                                        |
| V34–V37          | Assignment/started-scope/genealogy constraints későbbi full lifecycle regression; V36 global employee overlap nem új accepted tilalom |
| V38–V40          | Fókusz: audit/constraint fault atomicity, file evidence interface, deadlock/timeout/unknown commit                                    |
| V41–V43          | Fókusz: principal/definer/actor boundary, actual PDO/autocommit/root profile és raw CALL partial commit attack                        |
| V44–V45          | O1/O2/O3 boundary és decimal/codec exactness; current source-only fixtures, historical MRP nem importálódik                           |
| V46–V48          | Környezet/rollout/recovery prerequisites, SQLite eltérés, minden shared consumer és cache compatible proof                            |

A WP7 V42/V43 tizenöt eredeti subcase-e lefedett: mode1 noBEGIN/explicit root, mode0 clean/dirty,
nested, pool/reconnect, entry rejection, mid-body error, audit/proof, caught error+COMMIT,
1213,1205,unknown commit, alternate principal és afterCommit cache. A jelen spec további
cross-connection/actor/helper/guard részleteket ad, nem csökkenti az eredeti assertionöket.

## 10. Model comparison

| Dimenzió                | A — Laravel root, routine no rollback                                | B — DB-owned command                                                | C — dedicated service-owned boundary                                                                       |
| ----------------------- | -------------------------------------------------------------------- | ------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------- |
| Integrity strength      | Deklaratív védelem erősíthető, raw partial CALL jelenleg megoldatlan | Saját complete wrapper erős lehet, exact DDL nélkül nem bizonyított | Strong conditional cél: declarative+protected complete operation+same-T audit; entry proof hiányzik        |
| Transaction safety      | Jól viselkedő callerrel ígéretes, malicious catch+COMMIT blocker     | Caller-free root mellett ígéretes, nested implicit boundary veszély | Root-only explicit ownership és delegated abort mellett ígéretes; nesting deny és state reset bizonyítandó |
| Laravel compatibility   | Legközelebb a jelenlegi patternhöz, nem automatikus safety           | Framework tx manager/afterCommit kívül marad, replay risk           | Saját adapter/root orchestration; jelenlegi default DB/audit/cache illesztés változtatandó                 |
| Security enforceability | Grants/trigger/finalizer lehetséges, call totality hiányzik          | Complete public API enforceable lehet; actor trust továbbra külön   | Principal/secret gateway isolation + grants + DB invariants szükséges; PHP osztály önmagában elégtelen     |
| Complexity              | Kevesebb kezdeti adapter, nagy unresolved totality gap               | DB orchestration és app result/callback bridge, C1 amendment        | Magas: executor/driver/security/shared-service boundary; explicit proof scope                              |
| Maintainability         | Meglévő closure minták, composability félreérthető                   | DB üzleti command és app orchestration kettős szakértelem           | Typed API/manifest contract jól auditálható lehet; pool/grant verziózás terhe                              |
| Testability             | App happy path könnyű, adversarial raw CALL mandatory                | Direct SQL fault/race jó lehet, Laravel-level zero replay külön     | Dedikált minimal proof+raw/app multi-role matrix jól izolálható, harness még hiányzik                      |
| Existing code impact    | Nested/audit/locks akkor is javítandók; nem zero impact              | Legnagyobb ownership-váltás, no silent drop-in                      | Explicit shared writer cutover, no nested direct service reuse; inventory/procurement/audit/cache érintett |
| Operational risk        | Partial commit és callback/connection mismatch                       | Unexpected internal commit/unknown outcome/C1 conflict              | Credential/process topology, root lifecycle és mixed-version writers; mérhető kontrollok szükségesek       |
| Accepted C1 fit         | Outer owner/no COMMIT igen, totality még nem                         | **Nem kompatibilis jelenlegi contracttal**                          | Feltételesen kompatibilis; service commit, routine csak owned-root error abort                             |
| Jelen értékelés         | Nem ajánlott totality proof nélkül                                   | Nem selected; explicit amendment nélkül kizárt                      | **Provisional recommendation; UNVERIFIED, nem accepted**                                                   |

## 11. Provisional recommendation

**C modellt javasoljuk további review-ra és később külön engedélyezett minimális proofra.**
A választás indoka a C1 service-owned root/no-internal-COMMIT megőrzése, a meglévő nested
Laravel composition kizárása a kritikus belépőből, és a DB totalitás célzott bizonyíthatósága.
Ez technikai hipotézis, nem implementation-readiness vagy accepted ADR.

A legszűkebb lehetséges proof egy typed complete command ugyanazon kapcsolatban létrehozott
fact/projection/history/mandatory audit/result elemekkel, scoped roles, full-body és audit-fault
visszagörgetéssel. Ennek sem szabad production DDL-nek vagy teljes 25 táblás proofnak látszania.
Actual negative raw CALL és actual Laravel/PDO state vizsgálat nélkül a proof nem fogadható el.

| Feltételezés — mind UNVERIFIED, ha runtime tény                                 | Kijárati bizonyíték                                                                                                     |
| ------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------- |
| Isolated gateway secret/principal authority üzemeltethető                       | Deploy/worker threat model, secret access/role graph és negatív alternate route test                                    |
| Safe DB entry contract kizárja unsafe autocommit/no-BEGIN hívást                | Exact profile/DDL és V42/V43 raw direct principal próbák; nincs feltételezett server flag                               |
| Fresh root lease kizárja borrowed U munkát                                      | Origin/state/lease trace, dirty mode0 és multi-connection nested rejection                                              |
| Routine full abort után framework/PDO/callback tisztítható                      | Root error/deadlock/disconnect/reuse actual trace; cleanup error külön preserved                                        |
| Mandatory audit/proof DB-atomic a dedicated connectionön                        | Fault minden mandatory write után és app audit default/buffer mismatch negatív teszt                                    |
| Stable key/occurrence kezel unknown outcome-ot is                               | Primary lookup/wait/replay actual network faulttal, no duplicate physical effect                                        |
| Minden shared writer átállítható ugyanazon ownership/lock/projection contractra | WP3 teljes consumer disposition, source és deploy regression/fence evidence                                             |
| Harness guard képes multi-role isolated tesztre                                 | Guard extension review+negative tests és provisioned ephemeral environment, nem meglévő single-role suite újracímkézése |

Ha C bármely mandatory entry/totality/security feltételét nem tudja bizonyítani, a javaslat
**nem fogadható el**. Következő technical review választ új megoldást vagy explicit architecture
amendmentet; a sikertelen C proof nem ad automatikus engedélyt B-re vagy app-only fallbackre.

## 12. Remaining blockers

| Eredeti finding / függés              | Megmaradó probléma és closure contract                                                                                                                                                                               | Owner szerep / státusz                                                     |
| ------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------- |
| WP2-F02 **BLOCKER**                   | Exact complete public routines, grants/actor binding és root/PDO/CALL-failure totality hiányzik; raw/helper/no-BEGIN út se tarthasson meg fél parancsot                                                              | DB/security architect+backend+QA; OPEN                                     |
| WP7-F01 **BLOCKER**                   | Provisional C elfogadott technikai szerződése, driverprofil, safe entry+caller ownership, nested/dirty/multi-connection refusal, delegated rollback/commit/callback/retry/unknown outcome minden §9 negatív próbával | Architecture/backend/DB/security+owner; OPEN, tényleges proof NOT EXECUTED |
| WP7-F02 **HIGH**                      | Exact schema/security version+isolated multi-role environment+fixtures/harness/guard/fault/evidence/cleanup implementáció és futási manifest hiányzik                                                                | QA lead+DBA+release/security; OPEN, controlled execution BLOCKED           |
| C1-G1 / WP2-F01/F05/F12               | Full proof graph/finalizer/typed evidence és full schema engine-tested catalog nincs                                                                                                                                 | Domain/DB/security; DESIGN RESOLVED nem implementation proof               |
| C1-G2 / WP3-F01/F02/F14 / WP6-F02/F12 | Shared writer/reader/cache/worker/BI/grant cutover nincs igazolva; a jelen source inventory nem deployed audit                                                                                                       | Inventory/production/procurement/release; ROLLOUT BLOCKED                  |
| C1-G3 / WP4-F01/F02                   | I/MI és A/MA, rights/disposition/quarantine/attempt full lifecycle DDL és actual evidence még nyitott                                                                                                                | Repair/DB+QA; DDL UNVERIFIED                                               |
| WP6-F01/F09/F10/F11                   | Reprezentatív legacy quality, recovery DB/files/grants/tail, RPO/RTO és exact rollout interruption evidence nincs                                                                                                    | DBA/storage/release/domain; UNVERIFIED vagy original BLOCKED gate          |

Ezek nem új finding ID-k és nem összevont severity-k. A WP7 77 current WP findingja, a tíz
történeti B finding és külső REV-01–05 megfigyelések megmaradnak eredeti névterükben.
E célzott dokumentum nem bizonyítja vagy zárja le a többi schema/business/operational kaput.

**WP7-F01 lezárási csomag:** owner által review-zott explicit C1-kompatibilis contract; actual
minimal majd selected full command DDL; principalként allowed/denied és actor-binding evidence;
V38/V40–V43 összes subcase raw+Laravel/PDO trace-szel; same-T audit; state disposal/retry/unknown
commit; nincs unexpected unrelated rollback/commit, nincs partial caught-CALL commit, nincs helper
bypass. A specifikáció véglegesítése és az implementált megoldás bizonyítása két külön kapu.

**WP7-F02 lezárási csomag:** elkülönített/provisioned MySQL8.4 environment, exact app/driver/image/
schema/grant manifest, multi-role safety guard, versioned synthetic fixtures, barrier/fault harness,
canonical48-hoz trace-elt jövőbeli tesztek és actual reviewed artifacts, redaction, cleanup és CI
failure visibility. Első minimal proofra való readiness nem a teljes canonical48 sikeres lefutása;
full verification/readiness csak a megfelelő összes releváns actual eredménnyel állítható.

## 13. Implementation dependencies

Minden sor **jövőbeli, külön engedélyezendő** munka. Nem indul automatikusan.

| Sorrend / munkatípus                     | Függés                                    | Konkrét kimenet és kilépési feltétel                                                                                                                  |
| ---------------------------------------- | ----------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1. Documentation decision                | Ez a review-terv                          | Root owner, routine abort/commit authority, no nesting/cross-connection caller contract és C1 compatibility explicit owner/architect review           |
| 2. Security design decision              | 1                                         | Principals/definers/grants/actor binding/threat scope, gateway secret isolation és helper deny; deploy owner vállalja a topologyt                     |
| 3. Verification planning approval        | 1–2                                       | Exact selected driver entry profile és minimal command/fault/fixture/evidence spec; külön experiment-authorization                                    |
| 4. Disposable environment implementation | 3                                         | Isolated server/DB/roles, multi-role guard/harness, startup refusal és cleanup proof; nincs production migration                                      |
| 5. Minimal command proof experiment      | 4                                         | V41/V42/V43 valid root és deny/no-BEGIN/nested/dirty actual evidence; ha unsafe, STOP/review                                                          |
| 6. Atomic rollback/idempotency proof     | 5                                         | V05/V06/V38/V40/V43 each-write/audit/proof/caught-COMMIT/unknown outcome tesztek, complete result vagy teljes abort                                   |
| 7. Concurrency/locking proof             | 6                                         | Két független writer, parent/current-read lock plan, 1213/1205/retry, exact conservation és no duplicate occurrence                                   |
| 8. Finalizer proof implementation        | 5–7 + typed schema/proof contract         | C1-G1; claim acquire/close-only release/multi-member/missing proof/child-vs-close és audit fault actual proof                                         |
| 9. Shared integration implementation     | 7–8 + WP3 inventory/consumer review       | E/R/P/M/Q/C/L összes szükséges writer/reader/audit/cache/worker átállítás vagy indokolt no-change; G2 fence és regression                             |
| 10. Full RMA lifecycle integration tests | 8–9 + WP4/WP5 final contracts             | V01–V48 releváns teljes domain assertions, AM-G1–G4, financial/procurement/return history és policy paths                                             |
| 11. Production migration prerequisites   | 10 + WP6 legacy/recovery/rollout approval | Exact deploy/migration plans, representative audits, paired restore/rehearsal, compatible versions/grants, owner go/no-go; ez nem migration execution |

A disposable SQL-kísérlet, executable test/harness írás, alkalmazásintegráció és production
migráció külön feladat/engedély. Minimal SQL success nem indít automatikusan app-fejlesztést.
A későbbi implementáció a [layered quality gates](../../docs/development/quality-gates.md)
megfelelő affected/module/integration/full+MySQL kapuit követi; itt csak dokumentációs gate fut.

## 14. Project-owner decisions required

Az elfogadott RMA üzleti döntéseket nem kérjük újra jóváhagyni. A következő, még nem eldöntött
technikai scope-ot kell későbbi owner review-n konkréttá tenni:

| Review-kérdés                                                                   | Javasolt irány és döntés előtti bizonyíték                                                                               | Döntés hiányának hatása                         |
| ------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------ | ----------------------------------------------- |
| C modell root-only irány elfogadható-e további proof tervezési alapjának?       | C1 outer owner/no COMMIT megmarad; routine csak saját root abort; exact entry mechanism még nyitott                      | Implementation approval továbbra nincs          |
| Mi a gateway/process/credential threat boundary, ki provisionálja és auditálja? | Ordinary runtime ne tudjon posting secretet/helper/DDL jogot elérni; shared-process-only isolation elégtelen             | V41/V43 controlled proof BLOCKED                |
| Mely explicit mode0 vagy mode1+BEGIN driverprofilt bizonyítsuk először?         | Exact adapter/DB entry contract DB+backend review után; autocommit önmagában nem döntési alap                            | V42/V43 és root implementation BLOCKED          |
| Ki tulajdonolja a multi-role environment/guard/harness/evidence munkát?         | QA+DBA+security, versioned fixture/manifests és negative guard coverage                                                  | WP7-F02 nyitott                                 |
| Milyen külön scope-ban engedélyezhető minimal disposable proof?                 | Csak reviewed schema/routines/fixtures, isolated DB, no production connection/migration; explicit jövőbeli authorization | Sem SQL-kísérlet, sem executable test nem indul |
| Ki review-zza shared service boundary és rollout hatását?                       | Inventory/production/procurement/audit/cache owners és release; existing nesting nem implicit supported                  | C1-G2 továbbra BLOCKED                          |

Retry budget, exact driver adapter, guard interface, artifact retention és RPO/RTO technikai/
üzemeltetési részletei felelősökhöz kerülnek, nem kitalált alapértékekhez. Model B nem pótlólagosan
engedélyezett opció; külön architecture amendment nélkül C1-incompatible marad.

## 15. Final readiness assessment

| Kérdés                                    | Verdikt és bizonyítékhatár                                                                                |
| ----------------------------------------- | --------------------------------------------------------------------------------------------------------- |
| Dokumentációs tervezési feladat           | Review-ra elkészített focused contract/options/test/evidence terv; nem accepted architecture              |
| WP7-F01 lezárt?                           | **Nem.** Provisional C és konkrét closure-próbák megadva; actual transaction/principal/driver proof nincs |
| WP7-F02 lezárt?                           | **Nem.** Környezet/fixtures/harness/guard/evidence terv megadva; nincs provisioning vagy execution        |
| Minimal proof execution authorized/ready? | **Nem.** További owner review és külön explicit scope/authorization, exact profile és guard prerequisite  |
| Application implementation ready?         | **NOT IMPLEMENTATION READY**                                                                              |
| Migration / production readiness          | **NOT MIGRATION READY**, nem deployment approval                                                          |
| Accepted döntések                         | Változatlan ADR Accepted, C1 ACCEPTED WITH CONDITIONS; minden mandatory kapu megmarad                     |

### 15.1. Dokumentációs validáció és DoD

A releváns változástípus dokumentáció, a
[Definition of Done](../../docs/project-management/definition-of-done.md) szerint.
Formázás: a meglévő lokális Prettierrel kizárólag az új dokumentum formázása és célzott checkje.
Relatív fájl/anchor ellenőrzés, 15 főfejezet, A/B/C és V01–V48 traceability, a két §9 mátrix
kötelező 9 mezője, UTF-8/BOM/whitespace/fence és sensitive-pattern scan ellenőrzendő.
`git diff --check` mellett az untracked új fájl tartalmát külön kell vizsgálni.
Tracked/index diff és a teljes kezdő 921 fájlos halmaz hash-egyezése igazolja a megőrzési scope-ot.

PHP syntax/Pint/PHPStan/build/app/SQL/migration/backup/restore teszt **nem futott**:
nem keletkezett executable vagy runtime változás, és a feladat kifejezetten tiltja az executiont.
A `php -v/-m` kizárólag CLI/platform metadata, nem alkalmazásteszt vagy DB-probe.
Új dependencies/script/config/credential file nincs. Az előzménydokumentumok és a történeti B
eredmények változatlanok maradnak. Nincs staging/commit/push/merge/rebase/branchváltás.

| Tényleges dokumentációs ellenőrzés | Eredmény                                                                                                                                                          |
| ---------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Lokális Prettier célzott check     | PASS, exit0; csak az új fájl kapott formatter write-ot                                                                                                            |
| Relatív linkek és anchorok         | PASS, 75 létező cél/anchor; külső technikai állítások 8 hivatalos Oracle/PHP forrásra hivatkoznak                                                                 |
| Szerkezet és traceability          | PASS, 15 főfejezet; teljes 49-call source registry; 34 tervezett részpróba 9 kötelező mezővel; V01–V48 lefedettség                                                |
| Encoding/Markdown/whitespace       | PASS, strict UTF-8, nincs BOM/replacement character/trailing whitespace; táblák mezőszáma ellenőrzött                                                             |
| Sensitive-pattern ellenőrzés       | PASS, mind a nyolc pending dokumentumban nincs talált credential assignment/token/private key/email/abszolút environment path minta; ez nem teljes security audit |
| Megőrzés                           | PASS, a kezdő 921 Git-inventory fájl összesített hash-e pontosan egyezik §2.2 baseline-nal; B JSON és mind a hét WP benne van                                     |
| Git scope/whitespace               | PASS, `git diff --check` exit0, tracked/index üres; branch/HEAD változatlan; kizárólag egy új untracked dokumentum, összesen nyolc                                |

README/index/steering/ADR/memory és más dokumentum frissítése e szűk feladatban N/A:
a projektgazda kifejezetten egy új tervet engedélyezett és minden existing tartalmat megőriztet.
E dokumentációs ellenőrzések nem jelentenek runtime, principal, schema vagy migration gate PASS-t.

**Átadás: PROPOSED / NOT IMPLEMENTATION READY. Következő lépés projektgazdai review;
implementáció, DB-kísérlet és migráció nem indul automatikusan.**
