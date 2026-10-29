# RMA-0003/C2 – Részletes sémareview előkészítési terve

Státusz: **PLANNED / NOT EXECUTED**. Előkészítési terv kész, nem kész C2-specifikáció.
Dátum: 2026-10-29. Branch: `feature/returns-repair-management`.
Ellenőrzött baseline HEAD: `ea568deded91a1c256edbefdfbec7cf5be2ce096`.

Kötelező alap: [Accepted ADR-0006](../decisions/0006-rma-repair-management-architecture.md),
[RMA-0003/C1 – ACCEPTED WITH CONDITIONS](rma-0003c1-business-decisions-integrity-gates.md).
Előzmény: [A adatmodell](rma-0003a-data-model-integrity-design.md),
[B review](rma-0003b-integrity-verification.md).

## 1. Cél, hatókör és belépési feltételek

A következő fázis célja a teljes séma, integritási DDL és integrációs szerződés
review-ja. A jelen dokumentum a munkát, eredménytermékeket és elfogadási
feltételeket írja elő; nem állítja elő az összes végleges oszlopot, migrációt
vagy végrehajtható verifikációt.

- RMA-BD-001–020, D1–D5 és F1–F4 változatlanul kötelező.
- RMA-D6–D11: **ACCEPTED**. AM-01–AM-04: **APPROVED**, projektgazda,
  2026-10-29. AM-02-G1–G4: **MANDATORY**, nem elhalasztható opció.
- G1: **DESIGN RESOLVED / IMPLEMENTATION UNVERIFIED**.
  G2: **IMPACT MAPPED / ROLLOUT BLOCKED**.
  G3: **LIFECYCLE SPECIFIED / DDL UNVERIFIED**.
- C1: **ACCEPTED WITH CONDITIONS**. Nincs függő amendment-jóváhagyás.
  A technikai kapuk ettől nem válnak sikeresen tesztelt képességgé.

Jelen feladatban csak ADR/dokumentáció változik. A C2 későbbi végrehajtásának
pontos scope-ja külön felhatalmazás tárgya. Izolált SQL-próba, executable script,
migráció, alkalmazáskód vagy adatjavítás nem készül/fut e terv részeként.
Az alkalmazásfunkció implementációja a séma review-jától is külön fázis.

## 2. Munkacsomagok, függőségek és eredménytermékek

| Csomag | Felelős szerep                         | Bemenet / függőség                         | Elvárt eredmény és exit gate                                                                                                 |
| ------ | -------------------------------------- | ------------------------------------------ | ---------------------------------------------------------------------------------------------------------------------------- |
| C2-WP1 | Backend architect + DBA                | ADR/C1; A/B leltár                         | Teljes 25-jelöltes + existing-change oszlop/FK/U/CHECK/IX/deletion/transaction mátrix, hiány és indokolatlan új tábla nélkül |
| C2-WP2 | DBA + security + backend               | WP1 kulcsok; WP4/WP5 invariánsok           | Motoronkénti DDL/jog/guard és negatív verifikációs specifikáció; DB/service határ explicit                                   |
| C2-WP3 | Inventory/MRP/procurement + QA         | C1 G2; WP1/WP4/WP5                         | Minden consumer konkrét adattartalom-/írás-/olvasás-/lock/cache szerződése és rollout függősége                              |
| C2-WP4 | Inventory/quality + architect          | D8–D10/AM-01/02/04; WP1/WP2                | Intake/admission/karantén/proof/redispatch DDL-review; AM-02-G1–G4 design-level próbái                                       |
| C2-WP5 | Backend + procurement + pénzügyi owner | D6/D7/D11/AM-03/04; WP1/WP2                | Fee-credit és coverage ledger teljes lifecycle, kulcsok, quantity/amount guard és proof boundary                             |
| C2-WP6 | DBA + adatgazda + release felelős      | WP1–WP5 hatás; reprezentatív adatok később | Audit/migration/deployment/backup/rollback checklist konkrét blokkolókkal; nincs adatjavítás                                 |
| C2-WP7 | QA + security + DBA                    | Összes WP és C1 tesztmátrix                | Követelményenkénti tesztkatalógus, külön tervezett/futtatott bizonyítékkal és migration-readiness döntéssel                  |

WP1 után WP4/WP5 és WP3 szerződései egyeztethetők, majd WP2 véglegesíthető;
nem kell hiányos WP1-re teljes DDL-készséget állítani. WP6 baseline-a korán
előkészíthető, de readiness csak WP7-ben, igazolt előfeltételekkel értékelhető.

## 3. C2-WP1 – teljes séma- és változásleltár

### 3.1. A 25 új tábla jelöltlistájának egyeztetése

Az eredeti A fázis 22 új táblája a B review-ban a tartós credit allocationnel
23-ra bővült; a jelenlegi A fejléc ezt a B baseline-t tükrözi. C1 két indokolt
önálló identitást javasol: verified diagnostic fee és procurement coverage event.
Így **22 + 1 + 2 = 25**. Ez tervezett leltár, nem 25 telepített vagy teljes
DDL-lel igazolt tábla. A következő lista darabszáma és identitásfelelőssége C2-ben
explicit ellenőrzendő; újabb tábla csak indokolt, nyomon követett tervváltozással.

| Sorszám | Jelölt tábla                         | Származás / fő review-feladat                                                        |
| ------- | ------------------------------------ | ------------------------------------------------------------------------------------ |
| 01      | repair_requests                      | A; owner, close-command és terminális case-állapot                                   |
| 02      | repair_request_items                 | A; owner/serial/origin, membership-command, egyszeri original receipt                |
| 03      | active_repair_claims                 | A; természetes serial-PK, atomikus acquire, kizárólag close-release                  |
| 04      | repair_orders                        | A; egy order/member, díjszabás és aktuális execution/attempt                         |
| 05      | repair_operations                    | A; repair attempt/version/superseding és F4                                          |
| 06      | repair_operation_assignments         | A; több dolgozó, operation/order-egyezés                                             |
| 07      | repair_time_entries                  | A; actual intervallum, active assignment key, korrekció                              |
| 08      | repair_materials                     | A; owner/batch/serial/movement és partial consumption                                |
| 09      | repair_inspections                   | A; diagnostic/final/return-quarantine proof és subject                               |
| 10      | product_warranty_rules               | A; cikkszintű immutable rule/version                                                 |
| 11      | product_handover_evidences           | A; serialhoz kötött igazolt átadás/verzió                                            |
| 12      | warranty_assessments                 | A/B; order-version, subject-item/evidence/rule composite kapcsolatok                 |
| 13      | repair_quotations                    | A; submitted immutable, version/approval/proof                                       |
| 14      | repair_quotation_items               | A; exact money, fee-credit source és line                                            |
| 15      | repair_custodies                     | A; kizárólag original custody projection, nem replacement preparation                |
| 16      | repair_releases                      | A/B/C1; egy fizikai shipment identity; occurrence/reservation/movement U             |
| 17      | repair_delivery_events               | A/B/C1; failure/intake/admission és attempthez kötött proof                          |
| 18      | repair_ownership_events              | A/B/C1; occurrence, effective chain, reversal original-FK                            |
| 19      | repair_dispositions                  | A; original-outcome és ügy végeredménye, nem minden quarantine decision              |
| 20      | repair_status_histories              | A; append-only typed history/claim/credit/quarantine/command                         |
| 21      | repair_commands                      | A; command identity/hash/auth/result, atomicity                                      |
| 22      | item_instance_genealogy_events       | A; install/remove/correction és régi relation megőrzés                               |
| 23      | repair_diagnostic_credit_allocations | B; effective allocation, approval-only, authorized release                           |
| 24      | repair_diagnostic_fees               | C1; önálló verified fee és correction/version, quote-tól független                   |
| 25      | procurement_coverage_events          | C1; önálló demand/commitment/receipt/coverage history, legacy procurement számára is |

Nincs külön replacement-obligation, shipment-attempt, quarantine vagy reversal
aggregátum/tábla-jelölt: C1 ezek felelősségét a meglévő jelöltekben helyezte el.
Ha C2 szerint ez elégtelen, az új identitás/lifecycle/constraint indoklása kötelező;
a táblaszámot nem szabad csendben változtatni.

### 3.2. Meglévő táblák és minden inventory consumer

A/B hét meglévő változásjelöltje:
item_instances, stock_movements, stock_reservations, stock_balances,
material_requirements, purchase_requisition_item_sources, purchase_requisition_items.

C2 minden további kapcsolt táblát külön sorban osztályozzon:
**schema change required / FK target only / consumer adaptation only / unchanged**.
Különösen: purchase_requisitions, purchase_orders, purchase_order_items,
goods_receipts, goods_receipt_items, locations, item_batches, production_orders,
customer_order_items, documents, activity_log, users/permissions.
Az új ledger FK-célja önmagában nem indokol új oszlopot a célban.
A meglévő deletion/soft-delete/nullable jelentés és az új history megőrzése
összevetendő; a korábbi hét tábla nem állítható feltétlen végleges teljességi listának.

### 3.3. Előírt táblánkénti specifikációs sablon

Minden új és módosítandó tábla eredményterméke tartalmazza:

1. Üzleti/technikai felelősség és accepted requirement hivatkozás.
2. Minden oszlop: név, pontos MySQL/Laravel/SQLite típus, signedness/precision,
   charset/collation, nullability, default és legacy expand/contract eltérés.
3. PK; minden U teljes oszlopsorrenddel, nullable viselkedéssel; minden FK
   pontos parent U/PK-val, típus/sorrenddel és delete/update akcióval.
4. Teljes CHECK-formula explicit NULL-ágakkal; típus- és lifecyclefüggő
   feltételek, motoronkénti eltérések. Cross-row service-guard külön.
5. Minden child/lookup/index oszlopsorrend és cél; redundáns index vizsgálata;
   query-plan igény, mérés nélkül nincs teljesítményállítás.
6. Immutable és módosítható mezők, correction/supersedes/proof retention,
   hard/soft-delete korlátok és runtime/migration/definer jogosultságok.
7. State transition, ugyanazon transaction írásai, root/child mutex és lock-sorrend,
   idempotencia, audit/command failure és projection rebuild postcondition.
8. Létrehozási/FK-ciklus sorrend; visszagörgetési/mixed-version korlát;
   pozitív/negatív/motor/concurrency tesztek ID-i.

Exit gate: mind a 25 jelölt és az összes existing-change sor kitöltött;
nincs „majd megfelelő constraint/index” helykitöltő. C2 új részletes sémadokumentuma
javasolt eredménytermék, e terv nem helyettesíti azt.

## 4. C2-WP2 – MySQL integritási specifikáció

Minden sorhoz kell pontos DDL/jog/guard terv, pozitív és tiltott raw SQL eset,
valamint eltérés SQLite-hoz. A DB nem hitelesíti a HTTP-felhasználót:
trusted backend actor/policy és domain guard megmarad.

| Terület                    | DB-szintű szerződés specifikálandó                                                                                   | Service/proof és szükséges bizonyítás                                                                         |
| -------------------------- | -------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------- |
| Claim acquisition          | Member acquire-trigger, serial PK/member U/composite FK; parent open és transaction                                  | Többtagú nyitás/actor/eligibility/order/history; trigger-hiba teljes rollback                                 |
| Restricted privileges      | Runtime direct claim/close mező DML és DDL/TRUNCATE tiltása; nincs örökölt grant; nem-login definer, célzott EXECUTE | Policy/actor/employee, SQL injection és általános delete proxy kizárása; külön principalon negatív grantpróba |
| Authorized close finalizer | Kizárólagos finalizer, parent/child proof mechanikus ellenőrzése; closing-command/history, no internal COMMIT        | Case-close minden üzleti guardja; nem fogad proof_ok flaget; audit failure után sem fél close                 |
| Claim deletion             | BEFORE DELETE defense in depth; member immutable; RESTRICT; nincs session-variable bypass                            | Close kontra child/open, történet reconstruct, closed case nem reopen                                         |
| Composite FK               | Minden tuple parent U-val, azonos típus/sorrend; optional tuple explicit NULL-ág                                     | Rossz owner/serial/subject/order/line és rész-NULL negative                                                   |
| Nullable U / batch         | Nem-null batch_identity sentinel CHECK+U; active-key NULL csak inactive ágon                                         | Legacy duplicate audit és verseny, 0 nem valódi batch-FK                                                      |
| Reservation exclusivity    | Global active serial U, logical order-key, remaining quantities és root/previous tuple                               | Owner izoláció, partial consume, custody/quarantine kizárás, transitzár                                       |
| Shipment occurrence        | Source occurrence U, command/movement/reservation U, attempt/previous FK                                             | Új command nem új shipment; új attempt csak tényleges return és D9 authorization után                         |
| Return / admission         | Külön intake/admission identity, unique purpose-key, release/serial proof és movement kapcsolatok                    | AM-02-G1–G4: real receipt vs accounting admission; rebuild és retry nem dupláz                                |
| Ownership reversal         | Original event required FK, azonos subject, effective reversal U/correction                                          | Jogalap/permission/effective predecessor; physical return nem ownership                                       |
| Diagnostic allocation      | Fee/quote/order/line composite és active-source U, state CHECK                                                       | Approval-only, fee-cap/currency/scale; execution/settlement review és release race                            |
| Coverage conservation      | Immutable ledger/FK/idempotency/generation és transfer-side U                                                        | Több sor összege, partial receipt, owner/item/unit, paired delta atomikus; received nem open coverage         |

A MySQL 8.4 célkörnyezet tényleges verziója, isolation, InnoDB, collation, strict
mode és grantjai majd minden új próba mellé rögzítendők. CHECK-elt FK-val az
A/B által jelzett referenciális akciók, auto-increment operandusok, nullable
tuple-k és SQLite ALTER/rebuild továbbra is külön ellenőrzendők.
A B szűk prototípus nem a teljes production-típusú Laravel DDL.
Generated/stored key megoldás választását motoronként indokolni kell;
MySQL deferred FK-ra vagy SQLite grant-ekvivalenciára nem lehet építeni.

Exit gate: DB/service/permission/proof felelősségek nem fedetlenek, a pontos
claim hardening nem puszta alkalmazási szándék. C2-ben még nem futtatott DDL
és negatív próbák nem minősíthetők sikeresnek.

## 5. C2-WP3 – inventory/MRP integráció és rollout

A C1 G2 mátrix minden során végleges, metódusszintű előtte/utána szerződés,
adatmező, transaction/lock, cache, teszt és rollout blokk készítendő.
A végrehajtási terv rétegzett marad: Controller -> Service -> Repository -> Model.

| Fogyasztócsoport                                           | C2-ben előírt részletes adaptációs specifikáció                                                                                                                                                                                                                       |
| ---------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| StockBalanceRepository                                     | Sentinel first-create, company physical vs eligible qty; custody/pending/quarantine/transit exclusion; batch/hely aggregálás                                                                                                                                          |
| StockReservationRepository / StockReservationService       | Owner XOR, legacy customer+production egyezés, global serial U, reserved-consumed-released, partial release; quarantine/custody purpose ne kerüljön company SUM-ba                                                                                                    |
| ProductionTaskMaterialService                              | Teljes reservation consumed státusz helyett actual partial counter; más owner védelme; serial/batch/hely/qty és exact arithmetic; movement+audit atomic                                                                                                               |
| MaterialRequirementService / MaterialRequirementRepository | Legacy customer-order-item+cikk jelentés és több-PO aggregáció; repair külön; active/superseded/withdrawn, revision, delta-only netting                                                                                                                               |
| PurchaseRequisitionRepository / PurchaseRequisitionService | Source existence guard helyett hatályos coverage; generated és manual PR create/update/approve/delete; source grouping és representative direct pointer; generatePurchaseOrder átvezetés egyszer                                                                      |
| PurchaseOrderService / PurchaseOrderRepository             | Draft/requested/approved PR előzmény, sent/ack proof, cancellation/close/deletion védelem; received státusz nem actual receipt                                                                                                                                        |
| GoodsReceiptService / GoodsReceiptRepository               | Valódi purchase receipt, PO/PR/source/GR-item egyezés; partial receipt és ledger conversion+stock/reservation; RMA return külön típus                                                                                                                                 |
| StockMovementRepository / InventoryQueryService            | Scope/type/purpose, intake vs admission, original custody vs replacement return; history, szűrők/exportok                                                                                                                                                             |
| ReportingRepository / ProcurementDashboardService          | Physical/available/quarantine/custody/pending/transit és commitment/received külön; remaining és unit/owner helyes aggregáció                                                                                                                                         |
| ManufacturingIntelligenceRepository                        | Forecast/reserve/consumption típusok; customer-order inner join ne tüntesse el repair demandet; item-level open PO ne fedezzen téves ownert                                                                                                                           |
| ProductionPlanService                                      | Több-PO requirement/reservation orchestration és konkurens repair igény; zársorrend                                                                                                                                                                                   |
| Delegáló szolgáltatások                                    | ReportingService, DashboardService, ManufacturingIntelligenceService, MaterialForecastService, ProcurementRecommendationService, ProductionRiskService, BottleneckAnalysisService, QualityTrendService, SupplierPerformanceService: eredményjelentés/cache regresszió |
| Közös felületek                                            | Model relation/cast, repository contract, request/policy, generic CRUD/delete, AuditLogService, BusinessCacheInvalidator, queue/export/report; új invariáns nem controllerlogika                                                                                      |

Közös acceptance: customer custody és pending intake available=0;
quarantine minden célra kizárt. Eligible company physicalből csak a rá vonatkozó
remaining reservation vonható le, nem a már kizárt karanténhold újra.
Own reservation és open procurement coverage egyszer nettósítandó; received
stock nem maradhat open coverage-ként is rendelkezésre álló supply.
Az új igényrevízió önmagában nem új teljes PR; withdrawn demand nem generál.

Rollout sorrend követelménye: read-only baseline -> shared expand -> minden
writer/reader és cache kompatibilis -> ellenőrzött mapping/backfill (külön feladat) ->
contract/constraints -> feature enablement. Régi scope-vak alkalmazásverzió nem
maradhat új RMA posting mellett. A case/item/order/requirement/PO/GR/source lock
sorrendjét minden két-domaines parancsra egy táblázatban egyeztetni kell.
G2 addig ROLLOUT BLOCKED, amíg minden közvetlen és delegált út bizonyított.

## 6. C2-WP4 – return, pending admission és kötelező karantén

DDL/proof terv készítendő a teljes C1 lifecycle-ra:
initial reserve -> preparation -> shipment -> failed delivery -> physical intake
-> quarantine -> inspection/ownership/disposition review -> authorized decision
-> company admission/available vagy authorized custody-rights redispatch vagy scrap.

A logikai obligation, a mennyiségi reservation és a fizikai shipment külön;
új valódi attempt új reservation, a régi fulfillment immutable. A release
a shipment identity, előzmény/occurrence/movement evidence és idempotencia együtt kell.
Úton lévő egység nem foglalható vissza. Késői delivery proof saját attempthez
tartozik; nem zárhat új attemptet és nem oldhat fel karantént.

A konkrét oszlopok/FK/CHECK-ek tervezésében külön szerepeljen a hely/idő, verified
serial, source shipment, return proof, inspection subject, ownership/right proof,
authorized disposition és actual movement. A fizikai intake időpontját admission
nem dátumozhatja át. A pending external könyvelési oldal nem geográfiai tény
és nem ownermegjelölés. Customer-custody/right redispatch nem teremthet fiktív
company tulajdont vagy original custody-sort; company balance-hatása 0.

### AM-02-G1–G4 design-level kötelező esetek

| Feltétel | Tervezett pozitív eset                                                                     | Tervezett negatív eset / elvárt postcondition                                                                                                                                                |
| -------- | ------------------------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| AM-02-G1 | Beolvasott serial, valós hely/idő, source shipment és evidence -> atomic intake+quarantine | Hiányzó/idegen serial, hely, idő vagy source/proof esetén nincs szabályos könyvelés; eltérés nem hamisít origin/owner adatot. Unresolved owner önmagában nem akadálya az igazolt intake-nak. |
| AM-02-G2 | Return-intake company balance0; verified admission csak teljes befogadási követelményekkel | Owner/admission proof hiányánál company+1 tiltott; failed delivery/API-hiba önmagában 0; ismeretlen ownerből nincs company vélelem                                                           |
| AM-02-G3 | Karantén belső helyen látható, minden availability forrásból kizárt                        | Production, sales, repair-consume, unrelated reserve és normal availability külön negatív tesztje; read/write/export/cache út sem kerülheti meg                                              |
| AM-02-G4 | Egy physical intake és később egy külön admission, stabil eltérő kulcsokkal                | Same key retry ugyanaz az eredmény; új command ugyanarra a receipt/admissionre konfliktus; nincs két physical receipt vagy dupla projection                                                  |

Minden sorhoz a sérülést megakadályozó DB-kulcs/guard és a visszagörgetendő írások
felsorolása kell; e táblázat **tesztterv**, nem futtatott siker.
Intake és admission külön idempotencia-domain; a pending fizikai on-site riport
egyszer számol, availabilitybe nem teszi. Authorized admission és quarantine
release két eltérő döntés is lehet; admission önmagában nem felhasználhatóság.

Az external -> external engedélylista típus+purpose+source-return+authorization
szerint tételes legyen. Jóváhagyott alapeset: bizonyított replacement return-intake
pending-admission/mandatory-quarantine céllal. Generic transfer/correction/scrap
nem kap automatikus kivételt. Más szükséges quarantine-mozgás vagy pending scrap
saját típus/cél szerződését C2-nek indokolnia és kifejezetten engedélyezettként
rögzítenie kell; általános external->external kiskapu nem keletkezhet.

Exit gate: G1–G4 mind pozitív/negatív design coverage-dzsel és pontos kulcsokkal
lefedett; a physical/ownership/disposition három dimenziója önállóan rebuildelhető.
Original-outcome és az összes replacement attempt/return rendezése szükséges a close-hoz.

## 7. C2-WP5 – diagnosztikai kredit és procurement coverage

### 7.1. Fee és allocation

Specifikálandó a repair_diagnostic_fees önálló verified identity/version, consent/
calculation/amount/currency/scale/proof és correction kapcsolata. Order rate snapshot
nem azonos a tényleges díjjal. Fee-version nem nyithatja meg újra ugyanazt a credit
forrást. Quote-line/fee/order/active-source composite és correction-history
kulcsai, nullability, immutability, authorized write és active key pontosak legyenek.

Approval-only reservation: draft/submitted nem effektív allocation. Exact fee-cap,
same order/currency/scale, egy effektív fee-source claim; két quote approval race
és approval/start/release race. Cancel/supersede nem automatikus release.
Release előtti execution/settlement review bizonyítéka, actor/permission,
unknown/unsettled/settled observation és protected transition külön tervezendő.
Settled külön correction folyamat, immutable régi fee/quote/credit historyval.
Nem specifikálunk adót, számlázási vagy általános payment/accounting alrendszert.

### 7.2. Coverage ledger és generation

A procurement_coverage_events minden event-típusára kell required/optional FK,
quantity/unit, type CHECK, stable identity/hash, actor/time/proof és correction.
Source az eredeti kapcsolat/quantity snapshot, a ledger az aktuális coverage;
a source.quantity nem adható még egyszer a ledger allocation összegéhez.
Requirement active/superseded/withdrawn revíziója, commitment sent/ack/cancel
bizonyítéka és actual GR-item receipt külön állapot.

Coverage release nem PO-cancel; reassignment régi owner -q/új owner +q ugyanazon
transactionben, source/commitment conservation, paired transfer-key/side védelemmel.
Azonos PR-line/requirement anchor história nem törlési kapcsoló. Partial receipt
open coverage-t csökkent, actual supplyt hoz; nem bizonyít repair-consumptiont.
GR és coverage/reservation/balance/movement/audit átvezetése atomikus.

Demand_revision nem retry-sorszám; procurement_generation valódi delta/coverage
döntéshez, stable generation key-hez kötött. A generation több line-ra bontásának
U-kulcsát, több PR/PO coverage ugyanazon igényét, partial cancel/receipt és
unassigned commitment review/allocationját véglegesíteni kell. A forrásrekordok
törlése vagy a ledger history átírása nem tehet igényt újra generálhatóvá.

Exit gate: minden lifecycle-átmenethez DB/service guard és pozitív/tiltott/
konkurens eset; nincs kettős supply vagy „received státusz = actual GR” feltételezés.
A konkrét settlement-proof interfész és procurement evidence hiteles forrása
nyitott technikai döntés marad, amíg nincs specifikálva.

## 8. C2-WP6 – legacy, migráció és deployment biztonság

A következő checklist a későbbi migration-readiness előfeltétele; minden ponthoz
owner, környezet, reprodukálható evidence és PASS/FAIL/BLOCKED/N/A indok kell.

- Reprezentatív, jóváhagyott read-only legacy snapshot, időpont és hozzáférési scope;
  orphan production/source és saját gyártás/serial/handover bizonyítékok.
- Minden meglévő FK/delete/update/soft-delete akció felmérése, history/proof
  cascade/null-delete kockázat és RESTRICT átállás ütközése.
- NULL-batch duplicate balances, owner nélküli/rossz owner reservation,
  quantity/unit/serial/genealógia eltérések; nincs automatikus SUM/delete/backfill.
- Opening stock baseline + movement-type mapping + fizikailag igazolt serial
  reconciliation; ismeretlen correction vagy outbound/admission jelentés blokkol.
- Több PR/PO/supplier commitment és partial GR coverage; representative direct
  pointer, sent/ack/received bizonyíték, legacy több-PO igényaggregáció jelentése.
- Expand/backfill/contract és FK-ciklus sorrend mindkét motorra; unknown érték
  nem kap vélelmezett company/eligible/settled állapotot.
- Mixed-version deployment: consumer kompatibilitás és feature-off kapu,
  queue/worker/export/cache verziók, read-only/report viselkedés és enablement.
- Rollback korlát: új audit/ownership/credit/coverage/RMA history nem törölhető;
  régi scope-vak kódra nem szabad új custody/quarantine mellett visszaállni.
- Szükséges adatbázis- és proof/document/storage backup, grant/routine/trigger
  definíciómentés, visszaállíthatósági próba és restore-időpont/felelős.
- Dedikált guardolt MySQL tesztkörnyezet legalább két külön kapcsolattal,
  SQLite FK-on kompatibilitási környezet, reprezentatív staging és elkülönített
  runtime/definer/migration credentialek; production adatbázison nincs kísérlet.

E pontok e feladatban nem futottak. Ha megfelelő adat vagy baseline nincs,
az audit és az abból függő contract/backfill readiness BLOCKED; az üres B
tesztadatbázis nulla hibaszáma nem bizonyít legacy megfelelést.
Adatjavítás, migrációfuttatás és backup/restore végrehajtása nem része e tervnek.

## 9. C2-WP7 – verifikáció, elfogadás és bizonyítékhatár

Minden előírt tesztesethez requirement-ID, fixture/környezet, pontos lépés és
postcondition, jogosult/tiltott actor, fault/retry és artifact kell.
A C1 N1–N5, G1-A–D, Q1–Q6, K1–K5, O1–O4, P1–P6, valamint a régi C1–C10
teljes domain-esetei bővítendők, nem mechanikus prototípusmásolatként jelentendők.

| Ellenőrzéstípus                  | Szükséges bizonyíték migration readiness előtt                                                                                    | Jelen állapot |
| -------------------------------- | --------------------------------------------------------------------------------------------------------------------------------- | ------------- |
| Static DDL review                | 25/new+existing teljes leltár, dependency/index/null/action/cycle és DB/service mátrix                                            | PLANNED       |
| MySQL constraint tests           | Production-típusú teljes DDL, raw valid/invalid INSERT/UPDATE/DELETE, FK/U/CHECK és grant/finalizer                               | NOT EXECUTED  |
| MySQL kétkapcsolatos concurrency | Barrierrel overlap, mindkét commit-sorrend, wait/duplicate/deadlock1213/timeout retry; claim/stock/quote/return/credit/coverage   | NOT EXECUTED  |
| SQLite compatibility             | FK-on, nullable U/CHECK, rebuild/migrate/rollback, típuseltérések; nem grant/row-lock ekvivalencia                                | NOT EXECUTED  |
| Laravel feature/service          | Teljes RMA/movement/custody/proof/quote/QC/time/genealogy/credit/coverage workflow, history és audit                              | NOT EXECUTED  |
| Policy/authorization             | HTTP/service/queue direct deny, document target access, quarantine/reversal/credit/coverage role; super-admin sem business bypass | NOT EXECUTED  |
| Inventory/MRP regression         | G2 minden reader/writer, partial reserve, own/other owner, batch/serial, NULL batch, source/PO/receipt/netting/cache              | NOT EXECUTED  |
| Migration tests                  | Guardolt forward/expand/backfill/contract/rollback és seed/snapshot; history megőrzés                                             | NOT EXECUTED  |
| Deployment verification          | Mixed versions, grant és feature gate, queue/cache, backup/restore, monitoring/reconciliation                                     | NOT EXECUTED  |

Kötelező hibainjektálás: movement/projection/claim/quote/ledger/history/audit hiba
teljes rollback, nincs completed command fél business state mellett. Redispatch
és admission retry sem állíthat második fizikai receiptet. Close kontra új
child/return/coverage írás ugyanazon case/root zár mellett konzisztens.
Runtime direct DELETE/close/TRUNCATE elutasítás tényleges runtime principalon
bizonyítandó, nem csak service mockban.

### 9.1. Migration-readiness elfogadási kapu

MIGRATION READY csak akkor mondható ki, ha WP1–WP7 összes releváns követelménye
teljes és a szükséges verifikáció ténylegesen lefutott/értékelt, nincsenek
rejtett vagy megkerült blocker-ek. Minden kihagyás kifejezett indokkal,
hatással és felelőssel dokumentálandó. A terv elkészülte nem migrációengedély.
Az ADR Accepted és C1 Accepted With Conditions nem tesztsiker vagy production readiness.

A [B script](verification/rma-0003b-verify.php) és
[B eredmény](verification/rma-0003b-results.json) **történeti bizonyíték**:
35/35 szűk SQL-prototípus a régi modellhez, MySQL 8.4.7/InnoDB/REPEATABLE-READ,
SQLite 3.53.4; külön MySQL kapcsolatokkal mechanizmuspróbák. Nem bizonyítja a
mostani claim hardening, AM-02, fee/approval-only és coverage ledger teljes
C1/C2 designját. Nem futtattuk újra és nem írtuk át.

## 10. Követelmény-traceability és előkészítési verdikt

| Követelmény                | Következő fázis fő csomagja                                                                                                                      |
| -------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| RMA-BD-001                 | WP1/WP2: többtermékes ügy, önálló munkalap/lifecycle                                                                                             |
| RMA-BD-002                 | WP1/WP2/WP6: saját gyártás+serial+eredet alkalmasság                                                                                             |
| RMA-BD-003                 | WP1/WP2/WP7: belső actor/permission, reporter és owner külön                                                                                     |
| RMA-BD-004                 | WP1/WP2/WP7: egy aktív claim/serial egész case-close-ig                                                                                          |
| RMA-BD-005                 | WP1/WP7: manuális warranty/pending/indokolt override                                                                                             |
| RMA-BD-006                 | WP1/WP5/WP7: fizetős execution current approved kerettel                                                                                         |
| RMA-BD-007                 | WP3/WP4: original customer custody normál balance0                                                                                               |
| RMA-BD-008                 | WP4/WP7: original végső disposition csere mellett is független                                                                                   |
| RMA-BD-009                 | WP1/WP7: új repair attempt/QC, változatlan előzmény                                                                                              |
| RMA-BD-010                 | WP1/WP3/WP7: több dolgozó actual ideje és anyaga                                                                                                 |
| RMA-BD-011                 | WP2/WP7: teljes proofhalmazos atomikus case-close                                                                                                |
| RMA-BD-012                 | WP1/WP7: igazolt handover/rule/version, serialonkénti history                                                                                    |
| RMA-BD-013                 | WP5/WP7: immutable submitted quote, customer approval/expiry                                                                                     |
| RMA-BD-014                 | WP5/WP7: verified free/fixed/time fee és egyszeri credit                                                                                         |
| RMA-BD-015                 | WP1/WP7: mindkét serial teljes javítási története                                                                                                |
| RMA-BD-016                 | WP2/WP4/WP7: egyszeri original receipt, member/serial/state/prior check                                                                          |
| RMA-BD-017                 | WP3/WP4: custody/quarantine nem normal available, sale vagy consume                                                                              |
| RMA-BD-018                 | WP3/WP5: javítási anyag normál stockból, hiányból PR/coverage                                                                                    |
| RMA-BD-019                 | WP2/WP4: fizikai shipment recipient/time/reference/condition/proof                                                                               |
| RMA-BD-020                 | WP2/WP4: company reserve/prepare, shipmentenkénti egyszeri dispatch                                                                              |
| D1–D5                      | WP1–WP4/WP7: három scope, bizonyított semi_finished_product, egy owner, nincs warranty reset, physical/ownership külön                           |
| RMA-D6/RMA-D7, AM-03       | WP5/WP2/WP7: fee, approval-only allocation, release/settlement evidence                                                                          |
| RMA-D8/RMA-D9, AM-01/AM-02 | WP4/WP2/WP3/WP7: shipment/return/karantén; AM-02-G1–G4 mind kötelező                                                                             |
| RMA-D10/RMA-D11, AM-04     | WP5/WP2/WP3/WP7: original reversal és procurement history/conservation                                                                           |
| F1–F4                      | WP2/WP3/WP4/WP7: company prepare és egyszeri shipment, proofos close, serialpár/warranty, nem started operation superseding és új scope approval |

A részletes húszsoros BD-mátrix az ADR/A/C1 invariánsaival egyeztetendő.
A C2-verifikáció minden azonosítóhoz konkrét teszt-/constraint-/guard-hivatkozást adjon.

**Előkészítési verdikt: PLAN READY / C2 NOT EXECUTED / NOT MIGRATION READY.**
Hátramaradt: végleges oszlop/FK/U/CHECK/index/grant/trigger/routine terv,
hiteles proof-interfészek és money/retention részletek, reprezentatív legacy audit,
consumer-adaptációs szerződések, teljes motor/regresszió/deployment bizonyíték.
Az amendment-jóváhagyás már nem blocker; G2 rollout és G3 DDL bizonyítás továbbra is az.

Kapcsolódó kapuk: [Definition of Done](../../docs/project-management/definition-of-done.md),
[layered quality gates](../../docs/development/quality-gates.md).
E dokumentum jelen ellenőrzése dokumentációs: formázás, linkek, döntéslefedettség
és Git/whitespace. Nem állít elvégzett C2-séma- vagy alkalmazásmunkát.
