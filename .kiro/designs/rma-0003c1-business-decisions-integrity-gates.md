# RMA-0003/C1 – Elfogadott üzleti döntések és integritási kapuk

Státusz: **ACCEPTED WITH CONDITIONS** – RMA-0003/C1 Closure, 2026-10-29.
Dátum: 2026-10-29. Branch: `feature/returns-repair-management`.
HEAD: `ea568deded91a1c256edbefdfbec7cf5be2ce096`.

Autoritatív: [Accepted ADR-0006](../decisions/0006-rma-repair-management-architecture.md);
előzmény: [A adatmodell](rma-0003a-data-model-integrity-design.md),
[B review](rma-0003b-integrity-verification.md).
D6–D11 üzleti jelentése a projektgazda C1-feladatával **ACCEPTED**.
Az alábbi T-C1-\_ technikai döntések **PROPOSED**, nem végrehajtott változások.
AM-01–AM-04: **APPROVED**, projektgazdai jóváhagyás 2026-10-29.
AM-02-G1–G4: **MANDATORY**. Az elfogadott jelentést az ADR 3.2–3.4 rögzíti;
a T-C1 technikai DDL/mező/jogosultság-részletek ettől nem válnak végrehajtottá.

## 1. Összefoglaló és baseline

Kezdéskor a várt branch és HEAD, kizárólag az A/B dokumentumok és a két B
verification artifact volt nem követett az eredeti C1-tervezéskor. Staging üres
volt. Az akkori ADR-0006 Accepted és változatlan maradt; a mostani Closure
feladat jóváhagyott AM-01–04 tartalmával módosítja, Accepted státusz mellett. A/B történeti tartalma megmarad; csak dátumozott eligazító
kiegészítést kapnak, e dokumentumra mutatva.

G1-re konkrét DB-jogosultság/trigger/finalizer javaslat készült, a természetes
claim-PK megőrzésével. G2 minden feltárt író/olvasó átállítását kötelező rollout
kapuvá teszi. G3 külön kezeli a logikai cserekötelezettséget, a fizikai kiadásokat,
a visszaérkezést, a kötelező karantént és a tulajdonjogot. Nincs negyedik scope.

A B `submit -> held` kreditprotokollja **nem aktuális ajánlás**:
D6 szerint az **approved ajánlat foglal**. A B közvetlen visszatérésből
elérhetőséget megengedő ága **nem aktuális**: D8 szerint előbb kötelező karantén.
Az ownership reversal új, az eredeti eseményre kötelezően hivatkozó tény.

Két további táblát javaslunk indokolt önálló identitásra:
`repair_diagnostic_fees`, `procurement_coverage_events`.
A B 23 új táblás baseline-ja így **25 javasolt új tábla**.
Shipment-attempt, quarantine, replacement-obligation és ownership-reversal
számára nincs külön új tábla. A teljes oszlop/DDL-leltár C2 feladata.

**Verdikt:** G1 tervszinten megoldott, G2 feltárt és rollout-blokkoló,
G3: LIFECYCLE SPECIFIED / DDL UNVERIFIED. AM-02 jóváhagyott, G1–G4 feltételei kötelezőek.
C2 specifikációs munka előkészített; nem migration-ready és
nem implementációs engedély. Új SQL-próba és adatbázis-hozzáférés nem történt.

## 2. D6–D11 – elfogadott döntésregiszter

| Azonosító | Kötelező üzleti jelentés                                                                                                                                                                                                                                                                                                                                      | Modell / kapu / ellenőrzés                                                         |
| --------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------- |
| RMA-D6    | Diagnosztikai díj önálló tény; ajánlat beszámíthatja legfeljebb az igazolt díjig, ugyanazon munkalapon. Approved ajánlat foglal, ugyanaz a díj nem számítható be kétszer; minden előzmény megmarad.                                                                                                                                                           | Fee identity + quote-line + allocation, order/fee zár; 7. fejezet, K1–K4           |
| RMA-D7    | Cancel/supersede nem töröl kredittörténetet. Release előtt execution és settlement ellenőrzés; rendezetlen kredit csak jogosult auditált tranzakcióval oldható. Settled kredit külön korrekciós eljárás; régi ajánlat/döntés immutable.                                                                                                                       | Release observation és guard, nem invoice státusz; 7. fejezet, K3–K5               |
| RMA-D8    | Failed delivery külön esemény, nincs automatikus available növelés. Serialazonosított, dokumentált fizikai return kötelező karanténba; production/sales/repair-consume/idegen reservation kizárt. Identity/condition/ownership/előző shipment ellenőrzött; további használathoz authorized disposition. Return nem ownership reversal.                        | 3., 6. fejezet, Q1–Q6; AM-02                                                       |
| RMA-D9    | Ugyanazon replacement serialnak több valódi fizikai shipmentje lehet, mind saját immutable identity/proof mellett. Ugyanaz a shipment nem könyvelhető kétszer. Redispatch csak igazolt return/identity/condition/ownership vagy disposition right és explicit authorization, rendezett előző shipment után. Teljes history megmarad.                          | Release id = shipment identity; attempt+business-occurrence U; Q4/Q5, AM-01        |
| RMA-D10   | Fizikai return nem ownership-változás. Reversal dokumentált szerződéses/jogi alappal, jogosult actortól új immutable esemény, eredeti eventre és proofra hivatkozva. Custody és ownership külön rekonstruálható.                                                                                                                                              | Ownership related-event FK, 8. fejezet, O1–O4                                      |
| RMA-D11   | RMA-demand cancel nem procurement-cancel. Demand-only kontrolláltan visszavonható; draft PR felülvizsgálható/módosítható; approved PR jogosult review. Kiküldött/visszaigazolt PO nem törölhető vagy szüntethető meg csendben. Receipt/ownership/inventory tény marad. Coverage release/reassign/cancel auditált, history megmarad és duplagenerálás tiltott. | Requirement state + source + coverage ledger + PO/receipt proof; 9. fejezet, P1–P6 |

E döntéseket nem nyitjuk újra üzleti jóváhagyásra. Továbbra is külön specifikálandó
a konkrét pénzkerekítés, retention, permission és alkalmazandó szerződéses feltétel;
ezek nem helyettesítik a most elfogadott döntéseket.

## 3. Fizikai hely, tulajdon és rendelkezés

**T-C1-01 – három független dimenzió**, meglévő táblák bővítésével.

| Dimenzió               | Elsődleges tény / újraépíthető projekció                                                                                                                                                                                      | Nem következhet belőle                                      |
| ---------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------- |
| Physical               | StockMovement + release/delivery intake proof; item_instances.current_location_id, last_stock_movement_id; original custody csak repair_custodies                                                                             | Tulajdon vagy allocatability                                |
| Ownership              | Immutable repair_ownership_events, korábbi igazolt owner és verified basis. ItemInstance-en latest ownership-event pointer és verification_state=verified/unresolved projekció; unresolved mellett korábbi owner nem törlődik | Fizikai hely, automatikus return/reversal vagy availability |
| Disposition/allocation | ItemInstance.allocation_state: available/reserved/in_transit/quarantined/blocked/consumed/scrapped; reservationök és append-only döntési események. Hiányos legacy állapot blocked                                            | Tulajdonjog; authorizáció pusztán hely vagy scope alapján   |

A felsorolt state-értékek technikai javaslatok, nem új üzleti jogosultságok.
A projekciót csak az azt alátámasztó tényekkel közös tranzakció frissítheti.
`last_disposition_history_id` valódi history-FK; karanténfeloldás strukturált
inspection/return/authorization kapcsolatokkal, nem puszta JSON-ok.

A három készlethatókör változatlan: `company_stock`, `customer_custody`,
`external`. Ezek a könyvelési oldalak; a helyet és a jogi ownert külön kell
lekérdezni. Original customer custody soha nem normal balance. Replacement
preparation soha nem original custody. A return-karantén nem új scope vagy
gyártási foglalás. Karantén vagy unresolved ownership/disposition mellett
minden allocation-cél tiltott, akkor is, ha valamely aggregált qty pozitív.

**T-C1-02 – AM-02 szerint jóváhagyott pending-admission jelentés; DDL-je még javaslat:**
először dokumentált fizikai intake, `replacement_return_intake` movement,
external -> external, quantity=1, to_location=karantén, kapcsolt előző release
és egyedi fizikai return event. Mind a normál balance, mind original custody
hatása 0. ItemInstance helye ténylegesen belső, allocation_state=quarantined;
inventory-scope external mint még be nem sorolt könyvelési oldal, nem
„biztosan épületen kívül”. Ownership-verification unresolved is lehet.

Ez **eltér** az A 4.2 „legalább egyik scope nem external” technikai CHECK-jétől,
és az ADR scope/on-site összefoglalóját AM-02 most kifejezetten pontosítja.
A korábbi CHECK-ajánlás e szűk kivételben **SUPERSEDED**; az új típus/cél
allowlist és AM-02-G1–G4 bizonyítása C2-feladat, nem végrehajtott DDL.
Nem nevezünk ismeretlen ownert ügyfélnek, nem tételezünk company tulajdont,
és nem használunk NULL-t új scope-értékként.

Az intake utáni igazolt besorolás `return_stock_admission` tény: azonos serial/hely,
ugyanazon returnre hivatkozó external -> company_stock vagy igazolt customer
őrzési oldal. Ez valódi nyilvántartási befogadás, nem második fizikai receipt
vagy „kiegyenlítő” correction. Normal company balance csak igazolt company jogcímmel
változhat. Company admission quarantined állapotban csak igazolt besorolási jogcímmel
rögzíthető; nem állít hibátlan állapotot vagy felhasználhatóságot. Admission után
is karantén marad az authorized dispositionig; AM-02 e korábbi „tulajdon és
alkalmasság együtt” return-szabály kétlépcsős pontosítására is kiterjed.
A nem-company ág nem hoz original repair_custodies sort a replacementhez;
helye az instance/movement/return ledgerből követett. Ha nincs igazolt besorolás,
az intake követett és blokkolt marad; nincs kötelező fiktív admission.

Egy returnre egy intake és legfeljebb egy admission; ezek eltérő idempotenciakulcsok.
A fizikailag on-site, még be nem sorolt karantént külön oszlopban kell riportolni,
nem szabad kétszer számolni vagy company/custody ownershipként feltüntetni.
`physical_on_site` riport: company + customer custody + pending-admission,
egymást kizáró instance-besorolással; availability nem e három összege.
A company quarantine gross stockját availabilityből egyszer zárjuk ki;
annak rendszerholdját nem vonjuk le másodszor reservationként.

## 4. G1 – aktív RMA-claim törlésvédelme

**T-C1-03 – természetes PK + privilegizált finalizer + DB-trigger védelem.**
Az ADR 6.2 modellje marad: PK(item_instance_id), U(repair_request_item_id),
composite FK(member,serial) -> members(id,serial), RESTRICT, nincs soft delete.
Nem alakítjuk új claim-history aggregátummá.

| Réteg                     | Konkrét felelősség                                                                                                                                                                                                                                                       |
| ------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| DB-egyediség              | Egy aktív serial egy claim; rossz member/serial tiltott                                                                                                                                                                                                                  |
| DB atomikus acquire       | Member AFTER INSERT integritási trigger ugyanazon transactionben claimet hoz létre a member membership_command/actorral; konfliktus a member INSERT-et is rollbackeli. Trigger a parent nyitott állapotát zároltan ellenőrzi. Nyitáskor nincs második kézi claim INSERT. |
| DB raw-write védelem      | Runtime principalnak nincs claim INSERT/UPDATE/DELETE, TRIGGER/ALTER/DROP/TRUNCATE joga; nincs örökölt magasabb grant. Claim UPDATE mindig tiltott, member case/serial átírás és törlés tiltott; történeti FK-k RESTRICT.                                                |
| DB kizárólagos release út | Nem bejelentkezhető, szűk jogosultságú DEFINER finalizer az egyetlen claim DELETE-jogosult; runtime csak a célzott finalizer EXECUTE-ját kapja. Case status/closed mezők közvetlen update-je tiltott a runtime számára.                                                  |
| DB defense in depth       | BEFORE DELETE trigger csak closed parent, azonos completed closing_command és immutable close-history mellett enged; máskor elutasít. Nem user-variable/sessionflag kapcsolja ki.                                                                                        |
| Service                   | Policy/actor/employee, origin/owner és teljes proofhalmaz; közös tranzakciót nyit, repositoryn át finalizál; üzleti szabályokat nem controller/repository dönt el                                                                                                        |
| History                   | Nyitás és zárás typed member/serial/command/actor historyval és audittal; a claim eltűnése nem törli a foglalási időszak bizonyítékát                                                                                                                                    |

A finalizer nem dönt új üzleti feltételekről: a már definiált zárási invariánsok
mechanikus DB-ellenőrzését és az egyszeri állapotváltást kényszeríti ki.
Nem elég a service-től kapott „proof_ok=true”. A case sort zárolja, ellenőrzi a
members/orders/claims teljes lefedettségét, a disposition/movement/custody/return/
replacement/QC/authorization/idő/anyag kapcsolatok fennállását és a nyitott
kötelezettségek hiányát; service-oldali policy és tartalmi proof-validáció is kell.
Rendezetlen kreditkorrekció/karantén vagy repairhez rendelt procurement-maradvány
nem tűnhet el puszta státuszváltással.

Sorrend egy service transactionben: current locking reads és guardok; finalizer
close-history + closing_command completed tény + case closed; minden case-claim
DELETE; audit; commit. Nincs finalizer COMMIT, külső hívás vagy külön kapcsolaton
autocommitolt close. Bármely DB-/audit-hiba mindent visszagörget.
A trigger számára szükséges close-rekordok ugyanazon transactionben már láthatók;
a completed command más kapcsolatnak csak a teljes commit után látszik.

`repair_requests.closing_command_id` FK+U, a member `membership_command_id` FK
és a history member/serial typed FK-i minimális kiegészítések. A member-trigger
az aktuális membership command actorát használja; későbbi termékfelvételnél nem
a case eredeti létrehozóját tünteti fel új claim-actorként. A finalizer nem módosíthat unrelated case-t,
nem fogad tetszőleges SQL-t, és nem lehet általános DELETE proxy.

Konkurencia: az összes gyermekíró case-lockot kér; minden serial-módosító
ItemInstance-lockot is, ID szerinti rendezéssel. Másik case nyitása/close-ja
ugyanazon serialon e mutex és a PK miatt sorosodik. Close rollback után a régi
claim él; commit után új, másik case claimje jöhet létre. A tag már lezárt
munkalapja vagy visszaadása nem old claimet.

**Korlát és kapu:** a MySQL grant/trigger/routine pontos DDL-je és a teljes
proof-lekérdezés C2-ben specifikálandó/tesztelendő. SQLite nem ad MySQL-szerű
DB principal/routine védelmet; feature tesztjeihez trigger/guard megfeleltetés
kell, de raw-file hozzáférés elleni vagy production grant-bizonyíték nem várható tőle.
A DB nem hitelesíti a HTTP-felhasználót: trusted backend actor/ACL és policy marad;
runtime kompromittálás/DBA DDL ellen nem állítunk abszolút védelmet.
Ha a deployment nem tudja kizárni a direkt claim- és close-field írást,
**G1 implementációsan BLOCKED**, service-guard önmagában nem elfogadott helyettesítés.

## 5. G2 – inventory/MRP integrációs hatásmátrix

Keresés: table-nevek, Eloquent model-hívások és repository/service dependencyk
az `app/` alatt; tényleges metódusok olvasása. Ez baseline-kódvizsgálat,
nem annak állítása, hogy az átállítás már megtörtént.

Közös tranzakciós protokoll (T-C1-04): érintett RMA case-ek ID szerint -> items
-> item_instances -> repair/production order -> operations/fee/quote ->
requirements és coverage -> reservations -> balances -> release/proof.
Több sor minden csoportban ID szerint. Általános production/parancs nem vehet
fel később case-lockot item-lock után: érintett case-ket előre meghatározza,
vagy az RMA-blokkolt serialt elutasítja, majd azonos sorrenddel próbálkozik.
Hiányzó balance-nál létező item a mutex; SUM önmagában nem zár.
PO/receipt command is ugyanebbe rendezett root/requirement/commitment protokollba
illesztendő, nem tarthat PO-lockot, miközben fordított sorrendben case-re vár.
C2-ben két-domaines zársorrend-próba szükséges.

| Komponens / forrás                                                                                                                                                                                                                                   | Most                                                                                                                                            | Elvárt és regressziós kockázat                                                                                                                                                           | Transaction/lock; előírt teszt                                                        | Rollout blokk                   |
| ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------- | ------------------------------- |
| [StockBalanceRepository](../../app/Repositories/Admin/StockBalanceRepository.php) increaseQuantity/totalQuantityForItem/balancesForReservation/paginate                                                                                              | Nullable batch firstOrCreate; teljes quantity és active reserved SUM                                                                            | Sentinel U+CHECK+FK; company physical és allocatable külön, quarantine/custody kizárva. Dupla NULL-batch vagy szabadnak látszó karantén veszély.                                         | Item/serial/balance lock; N1,N2,Q2,C4/C5                                              | Igen                            |
| [StockReservationRepository](../../app/Repositories/Admin/StockReservationRepository.php) create/release/active sums                                                                                                                                 | Active státuszon teljes reserved SUM; release státuszírás; nincs serial/repair owner                                                            | Remaining=reserved-consumed-released; repair XOR legacy, idegen owner tiltott, serial active U; rendszerkarantént nem oldhat user release.                                               | Owner+serial+balance current read; N3,N4,C4/C5                                        | Igen                            |
| [StockReservationService](../../app/Services/Admin/StockReservationService.php) reserveForProductionOrder/release                                                                                                                                    | Tranzakció, elérhető balance-iteráció explicit közös mutex nélkül; release status check transaction előtt                                       | Közös lock, csak eligible company; partial consume után csak maradék release; hely/QC/return gate megőrzött.                                                                             | Case/owner/item/serial/reservation/balance; N3,Q2,C4                                  | Igen                            |
| [ProductionTaskMaterialService](../../app/Services/Admin/ProductionTaskMaterialService.php) store/balanceForConsumption/reservation                                                                                                                  | Physical qty>=q FOR UPDATE; reservation lookup ID; egész consumed státusz akár részfelhasználásnál                                              | Owner/item/batch/serial/hely és más lekötés védett; partial counters, quarantine/custody/in_transit tiltott. Ártalmatlan új oszlop nem elég.                                             | Közös mutex+movement+material+partial reservation+audit atomic; N3,N4,Q2,C5           | Igen                            |
| [MaterialRequirementService](../../app/Services/Admin/MaterialRequirementService.php), [MaterialRequirementRepository](../../app/Repositories/Admin/MaterialRequirementRepository.php)                                                               | Customer-order-item+cikk updateOrCreate; PO qty-ból számol, float; physical-active reserve nettósítás                                           | Legacy ownerkulcs megmarad; repair+cikk külön, exact qty; revision, withdrawn/superseded history; quarantine és coverage nem double count. Több PO felülírási jelentése tisztázandó.     | Owner/item/requirement lock; N5,P1/P5,C8                                              | Igen                            |
| [PurchaseRequisitionRepository](../../app/Repositories/Admin/PurchaseRequisitionRepository.php), [PurchaseRequisitionService](../../app/Services/Admin/PurchaseRequisitionService.php) generateFromMaterialRequirements/create/update/approve/delete | Missing>0 és whereDoesntHave(sources), requirement lock, cikk+unit grouping; direct pointer első source, source-ok teljes lista; generic delete | Hatályos delta/coverage; source nem törlési kapcsoló. Draft/requested/approved módosítás D11 szerint; owner és source conservation. Count+1 dokumentumszám retry is vizsgálandó.         | Requirement+PR/source/coverage zár; P1–P6,C8; generated és kézi PR út                 | Igen                            |
| [PurchaseRequisitionService](../../app/Services/Admin/PurchaseRequisitionService.php) generatePurchaseOrder                                                                                                                                          | PR lock; approved -> PO/items ordered, PR ordered; linekapcsolat megmarad                                                                       | Coverage PR->PO átvezetés egyszer, nem két supply. RMA-cancel nem PO-cancel.                                                                                                             | Requirement/PR/PO/source egységes sorrend; P2/P4/C8                                   | Igen                            |
| [PurchaseOrderService](../../app/Services/Admin/PurchaseOrderService.php), [PurchaseOrderRepository](../../app/Repositories/Admin/PurchaseOrderRepository.php) create/update/approve/delete/close                                                    | Draft approve ordered; általános soft delete, update supplier; close received-re ír tényleges receipt nélkül                                    | Kiküldés/ack bizonyíték, authorized review; receive státusz nem bizonyít receiptet. Kötelezettség nem tűnhet el soft delete/close útján.                                                 | Commitment+source locks, status újraolvasás bent; P2/P3/P4 és tiltott delete/close    | Igen                            |
| [GoodsReceiptService](../../app/Services/Admin/GoodsReceiptService.php), [GoodsReceiptRepository](../../app/Repositories/Admin/GoodsReceiptRepository.php) post/create                                                                               | GR és PO-item lock, balance increase+purchase_receive, received_quantity/státusz; nincs coverage ledger                                         | Purchase receipt nem RMA return; actual receipt, ownership/admission, ledger conversion és megfelelő ownerlekötés együtt; draft input önmagában nem goods-receipt tény.                  | Item/requirement/PO/GR/source/balance közös protokoll; P3/P4,N1; audit/movement fault | Igen                            |
| [StockMovementRepository](../../app/Repositories/Admin/StockMovementRepository.php) + [InventoryQueryService](../../app/Services/Admin/InventoryQueryService.php)                                                                                    | Generic listák/delegálás, régi típusok                                                                                                          | Scope/type/return/admission jelentés és független dimenziók megjelenítése; intake nem inbound company qty.                                                                               | Snapshot read, after-commit cache; N2,Q1/Q6                                           | Igen, közös szerződés igazolása |
| [ReportingRepository](../../app/Repositories/Admin/ReportingRepository.php), [ProcurementDashboardService](../../app/Services/Admin/ProcurementDashboardService.php)                                                                                 | Direct quantity SUM, full active reserved SUM; shortage és item+unit aggregálás                                                                 | Physical/available/quarantine/customer/pending-intake/transit külön; remaining és hatályos demand/coverage. Hibás összegből hibás beszerzés.                                             | Konzisztens read-snapshot, friss cache; N2,N5,P5,Q6                                   | Igen                            |
| [ManufacturingIntelligenceRepository](../../app/Repositories/Admin/ManufacturingIntelligenceRepository.php) materialForecast/materialShortages/procurementRecommendations                                                                            | Direct balance/reserve; consumption production_consume only; customer-order inner join; item-szintű open PO levonás                             | Repair igény nem tűnhet el inner join miatt; quarantine nem elérhető; assigned open coverage és received supply egyszer, owner/unit izoláció. Production mutató scope-ja explicit marad. | Read-snapshot, helyes coverage-projekció+cache; N2,N5,P3/P5                           | Igen                            |
| [ProductionPlanService](../../app/Services/Admin/ProductionPlanService.php)                                                                                                                                                                          | Requirement és reservation orchestration production orderből                                                                                    | Több-PO és konkurens repair demand nem ír idegen ownerre, közös zárprotokoll                                                                                                             | N4/N5/C4/C8                                                                           | Igen                            |
| Delegáló reporting/intelligence/dashboard szolgáltatások, lásd lent                                                                                                                                                                                  | A repositoryeredményt használják                                                                                                                | Új meaning, szűrés/permission és cache; nem feltétlen minden delegálóban kódváltozás                                                                                                     | Azonos eredmény és stale-cache regresszió; N2,N5,Q6                                   | Igen, igazolásig                |

Delegáló fogyasztók (a B leltár megmarad): ReportingService, DashboardService,
ManufacturingIntelligenceService, MaterialForecastService,
ProcurementRecommendationService, ProductionRiskService, BottleneckAnalysisService,
QualityTrendService, SupplierPerformanceService. A
[B 8. fejezete](rma-0003b-integrity-verification.md#8-inventory-consumer-rollout--rma-rev-03)
a konkrét helyi fájlhivatkozásokat tartalmazza.

További érintett felületek: StockBalance/StockReservation/MaterialRequirement és
procurement modellek, source relations/pivot casts, repository contractok;
StoreProductionTaskMaterialRequest ma csak exists reservation ellenőrzést ír elő;
ReleaseStockReservationRequest/Policy és inventory/procurement controllerek
továbbra is service-hez delegálnak. Generic CRUD/delete sem kerülheti meg az
új invariantokat. AuditLogService és BusinessCacheInvalidator commit-szemantikája
része a kapunak; queue/export/direct DB riportolvasók ugyanazt az availability
szerződést használják. Jelen keresés az app baseline-jának leltára; új consumer
hozzáadásakor a mátrix bővítendő.

### G2 közös mennyiségi szerződés

Eligible company physical = normál company stockból a karantén/blocked/in_transit
és unresolved rights kizárása. Available = eligible physical mínusz az arra
vonatkozó összes **fennmaradó**, aktív, nem már kizárt mennyiségi reservation.
Saját lekötést a demandhez egyszer rendeljük; más owner tartalékát nem fogyasztjuk.
Egy serial minden cél összességében legfeljebb egyszer allokált. Owner-XOR repair
ágban kizárólagos; legacy customer+production hivatkozás együtt megtartható,
ha összetartozik.

Batch nélküli balance identity=0 CHECK-kel, U(item,location,batch_identity);
valódi batch-FK nem sentinelre mutat. Legacy duplikáció és mapping nem javítható
automatikusan. Scope/karantén-szűrés az összes writerben és aggregált readerben
is kötelező; a serial kiválasztásánál végzett utólagos ellenőrzés nem védi az
MRP-t a hibás aggregált availabilitytől.

## 6. G3 – teljes return/quarantine/redispatch életciklus

**T-C1-05 – a repair_releases.id maga a shipment-attempt identity.**
Külön attempt tábla nem szükséges: előzetes foglalás/preparation már a
stock_reservations és history felelőssége, release csak a megtörtént kiadást jelenti.

Logikai replacement obligation: `repair_order -> repair_request_item` és a
replacement dimenziójú történet; nem a fogyasztható reservation mennyisége.
Minden fizikai attempt **új mennyiségi reservation**; immutable serialpár,
`replacement_root_reservation_id` és `previous_reservation_id` self-FK-val
kapcsolt lánc. A gyökérsorban root_id és previous_id egyaránt NULL: ez a saját identitású
gyökeret jelenti. Utódsorban mindkettő nem-NULL; az effektív gyökér root_id vagy
gyökérsornál saját id. Ezt explicit NULL-páros CHECK és parentzáras guard védi;
nincs önmagára előre mutató INSERT vagy feltételezett deferred FK. Previous
reservation U-ja az elágazó kísérletláncot is tiltja.
Új serial kiválasztása új gyökér; redispatch ugyanazon gyökér+serial marad.
A gyökér/serial/order egyezést composite FK és parentzáras guard védi.

`active_replacement_order_id` a **logikai nyitott kötelezettség** egyedi kulcsa,
`active_item_instance_id` az aktuális mennyiségi serialfoglalásé. Dispatchkor
a quantity fulfillment végleges, az order-obligation marad. Return/inspection
alatt a régi láncfej hordozhatja a nyitott obligationt, de nem ad mennyiségi
availability levonást. Új attempt előtt a kulcs átadása a következő reservationre
atomikus; korábbi consumed/released mennyiséget nem nullázunk.

| Lépés                       | Tény és állapot                                                                                                             | Könyvelés / guard                                                                                                                              |
| --------------------------- | --------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| 1. Reserve                  | Saját gyártás+eredet+company right, elérhető serial, nyitott case/order                                                     | Aktív serial és order U, balance 0; global claim/custody/quarantine kizárás                                                                    |
| 2. Prepare                  | Azonos serial, ellenőrzött hely/állapot                                                                                     | Másik helyre valós transfer -1/+1 és reservation-hely atomikus; azonos helyen history. Nincs ownership/custody                                 |
| 3. Dispatch N               | Immutable release/proof és shipment occurrence                                                                              | Company -> external, -1 egyszer; reservation fulfilled, allocation in_transit. Delivery/ownership ettől külön                                  |
| 4. Failed delivery          | delivery_failed child-event saját source identityval                                                                        | 0 balance, nincs receipt/reversal vagy automatikus reservation-release                                                                         |
| 5. Physical return          | Beolvasott serial, előző release, actual receipt idő/hely/proof                                                             | AM-02 intake movement + physically_returned event + location/quarantined atomikus; 0 company balance                                           |
| 6. Quarantine               | Mindig kötelező, akkor is ha biztosan company-owned                                                                         | Sem production, sales, repair consume, unrelated reservation, sem redispatch nem lehetséges; csak kontrollált quarantine transfer/inspection   |
| 7. Verify                   | Identity, condition, ownership/disposition rights és előző shipment összevetése; repair_inspections return_quarantine típus | Hiány/eltérés: blocked/quarantined. Ownership verification új proof, nem automatikus ownership-változás                                        |
| 8. Authorized disposition   | Külön karanténdöntési history: release_to_company_stock / prepare_redispatch / scrap, actor+inspection+rights+proof         | Nem repair_dispositions final original-outcome; az csak a teljes ügy eredeti termékének végső sorsa                                            |
| 9a. Available company stock | Igazolt company tulajdon, állapot és autorizáció                                                                            | Egyedi admission external -> company +1 vagy korábbi company admission után 0 qty állapotfeloldás; nincs automatikus új reservation            |
| 9b. Redispatch              | D9 szerint disposition right, rendezett korábbi út és explicit authorization                                                | Ha company-owned, admission után új company reservation; felszabadítás+új reservation atomikus, nincs köztes szabad állapot; új release N+1    |
| 9c. Scrap                   | Dokumentált selejt- és disposition right, nem szükségszerűen company tulajdon                                               | Tényleges scrap movement a jelenlegi könyvelési oldalról; pending admissionnél 0 company/custody hatás. Eredeti/outbound/return/proof megmarad |

**Nem-company redispatch:** D9 disposition right is elegendő lehet; nem vezetünk
be kötelező ownership reversal szabályt. Igazolt customer right és kiadási
felhatalmazás esetén replacement custody oldalról -> external, normal balance 0.
A hozzá tartozó új reservation `replacement_redispatch_custody` purpose-ú
workflow/allocation sor, 1 serial; nem normál company mennyiségi tartalék és nem
vonható le a company reserved SUM-ból. Ennek scope és CHECK mappingje AM-02/C2
része. Unresolved owner/right esetén ez az ág is tiltott. Scrap nem jogosít
fel company tulajdon vagy adózási következmény kitalálására.

Új shipment nem írja át az előzőt. Régi delivered event későn érkezhet: az adott
attempthez rögzül, nem teheti teljesítetté az új attemptet és nem oldhat karantént.
Ha a késői proof ellentmond a returnnek, konfliktus és blokkolás, nem automatikus
„legutolsó timestamp nyer”. Előző shipment rendezése: fizikai return egyeztetve,
átadási/ownership obligation igazoltan rendezett vagy külön engedélyezett
rendelkezéssel kezelve; failure státusz önmagában nem elég.

### Shipment/return egyediség

- Megmarad U(stock_movement_id), U(command_id), U(previous_release_id);
  **U(stock_reservation_id)** ismét szükséges, mert minden fizikai attempt új
  reservationt kap. A B U(reservation,attempt) önmagában gyengébb.
- U(member,serial,role,shipment_attempt), shipment_attempt>=1; previous-release
  composite FK azonos member/serial/role-ra. Original továbbra is egyszeri
  átvétel/kiadás, az újraküldési kivétel csak replacement.
- **U(shipment_source_namespace,shipment_source_event_key)** a fizikai üzleti
  occurrence-re. A command-kulcs cseréje nem engedhet ugyanazon valódi
  shipmentre új attemptet. Az identityt ellenőrzött dispatch-intent/forrás adja,
  retrykor stabil; címkenyomtatás önmagában nem fizikai proof.
- Return eventhez release-id és serial composite egyezés, U(return intake movement),
  U(release,physical_return occurrence); U(return_event,admission purpose).
  U(reverses_stock_movement_id) az egyszeri teljes admissionre, nem az intake-ra.
  Mindkét kapcsolódó movement lehet egyedi, nem ugyanarra a „reverse” kulcsra versenyez.
- A B U(related_return_event_id) túl erős több child inspection/disposition
  mellett. Típusonkénti aktív/effective key vagy U(return_event,event_kind,decision_version)
  és külön egyszeri intake/admission-key kell; raw nullable tuple ágak explicit.
  Több valódi részleges ownership-változás nincs ebből automatikusan engedélyezve.

G3 nem enged el nem történt return vagy felhatalmazás nélküli új shipmentet.
Ügyzárás csak az összes attempt/return/quarantine/ownership obligation és az
eredeti termék független végső rendelkezése után. Újraépítés nem új könyvelés.

## 7. Diagnosztikai díj és kredit – D6/D7

**T-C1-06 – díjtény és üzleti allokáció külön.**
Új `repair_diagnostic_fees` a tényleges, igazolt díj identitása/verziója,
nem ajánlat vagy számla. Minimum: repair_order_id, version, amount_units,
currency, money_scale, calculation_snapshot, consent_document_id,
verified_by/at, evidence_document_id, supersedes_fee_id?, command_id.
U(order,version), U(supersedes_fee_id), U(command_id), U(id,repair_order_id);
minden FK RESTRICT, fee>=0, explicit NULL/scale CHECK. Insert után immutable,
korrekció új verzió igazolt alappal. Az aktuális díjat orderzár alatt választjuk.
Az order korábbi free/fixed/time/rate adata továbbra is díjszabási snapshot,
nem kész díjtény. A javaslat nem vezet be több külön számlázható díjat vagy
adónemet egy munkalaphoz; a meglévő diagnosztikai díj verziózott bizonyítéka.

A B allocation bővül diagnostic_fee_id FK-val, composite fee/order és
quote/order/line kapcsolatokkal. Egy quote kreditje a megnevezett fee-verzióra
hivatkozik, de az **active_source_order_id U megmarad** a teljes orderbeli
díjláncra: új fee-version nem nyit második aktív kreditet ugyanarra a diagnosztikára.
A fee-correction nem ír jóvá újra és nem léptet át automatikusan korábbi allocationt.

| Quote / allokáció         | Kötelező viselkedés                                                                                                                                                              |
| ------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Draft / submitted         | Több becslés megengedett, nem effektív foglalás. Submitted tartalom immutable.                                                                                                   |
| Approved                  | Order/fee/quote zár alatt összeg<=igazolt eligible fee; pénznem/skála/owner/order/line egyezés; allocation state=reserved és active key kitöltött, approval proof+audit atomikus |
| Másik quote approval      | Meglévő reserved/retained forrás mellett konfliktus; új verzió nem írja át a korábbi összeget vagy historyt                                                                      |
| Cancel/supersede          | Régi quote tartalma és credit tény megmarad; execution/settlement review szükséges; nincs automatikus release                                                                    |
| Authorized unused release | Ellenőrzött execution és settlement proof, unsettled és fel nem használt keret: state=released, active key NULL, actor/time/reason/proof és history ugyanazon transactionben     |
| Execution már történt     | Nem automatikusan settled, de nem bizonyítottan unused; kredit retained/blocked, szükséges külön jogosult rendelkezés. D7 nem ad automatikus újrafelhasználási engedélyt         |
| Settlement settled        | Active key nem szabadul fel a cancellationtől; külön, az eredeti allocationra hivatkozó korrekciós eljárás szükséges                                                             |
| Settlement unknown        | A hiányzó státusz nem unsettled; release tiltott, bizonyítékig blokkolt                                                                                                          |

Allocation állapotok javaslata: reserved / retained / released; active key
reserved/retained esetén source orderrel egyezik, released esetén NULL.
A pénzügyi összeg/proof immutable; projekcióváltás typed credit-history esemény.
`execution_review_document_id`, `settlement_review_document_id`,
`settlement_observation` (unknown/unsettled/settled), reviewed_by/at és
related allocation-history FK teszi auditálhatóvá a release-döntés alapját.
Minden review új history, nem a régi observation átírása. A státusz külső/belső
ellenőrzött megfigyelés; **nem invoice, fizetettségi vagy könyvelési alrendszer**.

Unknown/executed/settled ágban a rendszer csak a korrekció szükségességét és a
bizonyítékát követi. A settled korrekció könyvelési/elszámolási menete kívül marad;
annak későbbi igazolásáig nincs új allocation. Sem B applied, sem új reserved
állapotból nem következik automatikusan accounting settlement.

Amount cap és accumulated effective use cross-row service-guard exact integer
unitsban; CHECK nem tud fee-sort összegezni. Az active unique DB védi a két
konkurens approvalt. Guard+allocation+quote approval+history+audit egy commit;
ha összeg/proof/approval/audit hibás, egyik sem maradhat részlegesen sikeres.

## 8. Ownership reversal – D10

**T-C1-07 – kauzális reversal FK külön a korrekciós supersedes-től.**
Új `reverses_ownership_event_id` self-FK; reversal esetén kötelező, más
event_type-nál NULL. Composite FK (original_event_id,member,serial) azonos
subjecthez; parent U(id,member,serial). Teljes reversalnél U(reverses_ownership_event_id)
az eredeti eseményre két effektív reversal ellen. Részleges/ismételt reversal
jogi jelentését nem találjuk ki: ha később kell, külön specifikáció.
A reversal korrekciója új correction event a reversalre supersedes FK-val,
nem második reversal ugyanarra az originalra.

Megmarad occurrence U(namespace,source_event_key), command U és serialos
event_sequence; ugyanazon contractual_trigger későbbi valódi transferje
reprezentálható. Egy új transfer új event identity és megfelelő új predecessor;
a visszatérés nem trigger. `previous_ownership_event_id` self-FK az effektív
jogi lánc előzménye; rögzítési sequence és hatályossági occurred_at külön.
Késői/bizonytalan proof nem írja át a láncot automatikusan.

Service-guard case/serial/order zár alatt: original létezik, nem idegen
serial/case, azonos érintett ownership-kapcsolat, hatályos sorrend értelmes,
nincs már effektív reversal; from/to owner és dokumentált szerződés/jogalap
összevetése, külön ownership permission, audit+command atomic. A self-FK
nem ellenőrzi a jogalapot vagy a jogosultságot. Korábbi bizonyíték és event
nem törölhető vagy nullázható. Fizikai return, failed delivery, scrap vagy
custody-áthelyezés ettől továbbra is külön tény.

## 9. Procurement coverage – D11

**T-C1-08 – demand, procurement commitment és receipt három külön állapot.**
A material_requirements ownerkulcsa marad legacy customer-order-item+cikk,
vagy repair-order+cikk XOR. Kiegészítés: demand_revision, demand_state
(active/superseded/withdrawn), procurement_generation és current_revision_event_id.
Üzleti igényváltozás új immutable revíziós tényt rögzít; a stabil requirement
owner-sor és korábbi sources nem törlődik. A régi required/missing átírása
csak aktuális projekció, régi quantity/unit/owner/reason snapshot az új ledgerben.
Más ownerre „átírni” a requirementet tilos.

**Új procurement_coverage_events** szükséges: a meglévő source a PR-line és
requirement eredeti mennyiségi kapcsolata, nem részleges release/receipt/reassignment
ledger. A repair_status_histories kötelező repair/case célja nem megfelelő
a legacy procurementnek; generic activity_log pedig nem FK-s mennyiségi ledger.
Ez indokolja az önálló, append-only táblát az eredeti beszerzési architektúra mellett.

Minimum: id; material_requirement_id; demand_revision; event_type;
purchase_requisition_item_source_id?; purchase_order_item_id?; goods_receipt_item_id?;
related_allocation_event_id?; transfer_key?; transfer_side?; quantity; unit;
before/after demand_snapshot; actor/time; evidence_document_id?;
idempotency_key, event_sequence, payload_hash; supersedes_event_id?.
Eventtípus: demand_revised/withdrawn, coverage_allocated/released/reassigned,
commitment_confirmed/cancelled, supply_received és correction.
U(idempotency_key,event_sequence), U(supersedes_event_id), U(transfer_key,transfer_side);
FK-k RESTRICT, typefüggő explicit NULL CHECK és quantity előjel/pozitivitás.
A cross-row available coverage nem CHECK, hanem source/commitment mutex melletti guard.
RMA eredetnél optional repair_command_id valós FK lehet; legacy eseményt nem
kényszerítünk fiktív repair_commands/repair_order alá.

| Történés                    | Demand / coverage                                                                         | Commitment / supply és előírt intézkedés                                                                        |
| --------------------------- | ----------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------- |
| Demand-only withdraw        | Current active demand=0; withdrawn event, history megmarad                                | Nincs külső commitment; retry nem generálhat új PR-t withdrawn igényre                                          |
| Superseded demand           | Régi revízió és source immutable; új hatályos igény külön snapshot                        | Aktív coverage átvezetése/delta, nem új teljes igény minden revisionre                                          |
| Draft PR                    | Jogosult review, line/source mennyiségi adjustment auditált; részleges release lehetséges | Kapcsolódó commitmentek hiányát ellenőrizni kell, draft státusz önmagában nem elég                              |
| Requested / approved PR     | D11 szerinti review és authorization; approved nem automatikusan törölhető                | Requested konzervatívan review-köteles technikai védelem, nem új üzleti cancel-jog                              |
| Kiküldött / supplier-ack PO | RMA igény visszavonható, hozzárendelés felülvizsgálható                                   | PO commitment fennmarad, amíg dokumentált supplier/business cancel vagy reassignment nincs; nincs silent delete |
| Partial / full receipt      | Supply_received actual GR-itemhez kötve                                                   | Received qty nem „release-elt PO”; valódi movement/ownership/inventory szabályok szerint kezelt supply          |
| Released coverage           | Requirementhez rendelt fedezet megszűntét esemény igazolja                                | Önmagában nem PO-cancel és nem stockváltozás; aktív commitment maradhat unassigned poolban, külön review-val    |
| Reassigned coverage         | Régi owner -q és új owner +q egy tranzakcióban, paired eseményekkel                       | Ugyanaz a source/commitment total nem nő; új source-anchor csak valódi új ownerhez, régi nem törlődik           |

Reassignmentnél transfer_key nem-NULL, transfer_side from/to; más eseménynél
mindkettő NULL, explicit CHECK-kel. A két oldal ugyanazon transactionben jön létre;
nincs körkörös paired self-FK vagy immutable sor utólagos összelinkelése. A guard
commit előtt mindkét oldalt és azonos quantity/item/unit értéket ellenőriz,
a DB U azonos oldal duplázását tiltja.
Reassignment: item/unit/remaining qty/eredeti commitment és új owner egyezése,
jogosult döntés, conservation és dupla felhasználás kizárása. Source history
változatlan; a source.quantity a keletkezési snapshot, nem időtlenül aktív coverage.
Az aktuális coverage mennyisége kizárólag az egyszer rögzített ledger-deltákból
származik: a source.quantity az eredeti allokáció audit-snapshotja, nem még egyszer
hozzáadandó mennyiség. Source-létrehozás és első coverage_allocated egy transaction;
régi source-hoz első baseline-event csak igazolt audit alapján, egyszer képezhető.
Nyitott bejövő fedezet = allocated + reassigned_in - released - reassigned_out

- supply_received; minden releváns gyökérre és unitra nemnegatív. Commitment cancel
  csak dokumentált release/átvezetési eseményekkel változtatja a coverage-et.
  Azonos PR-line/target requirement anchor újra használható eseményekhez; nem kell
  duplicate source vagy a régi U(PR-line,requirement) törlése.

Az A U(requirement,demand_revision) generálási kulcsa önmagában túl szigorú és
nem teljes idempotenciaszerződés: ugyanazon igényhez authorized coverage-release
után is lehet valódi új delta. Helyette U(requirement,procurement_generation)
az egyszeri **jóváhagyott generálási esemény** kulcsa, stabil command identityval.
Demand_revision csak üzleti igényváltozás; generation csak ellenőrzött tényleges
delta/coverage-döntés után növelhető; puszta retry/újraszámítás egyiket sem lépteti.
Megmarad source generation_key U és U(PR-line,requirement). Ha egy generation
több PR-line-ra bontása szükséges, a key line-azonossággal bővítése külön C2-döntés;
az első modell cikk+unit csoportosítása generationönként egy source-ot feltételez.

Nettósítás current lock alatt: a még teljesítendő hatályos igényből saját eligible
remaining reservation, egyszer allokált szabad company qty és saját, még be nem
érkezett committed coverage vonható le. Received supply már fizikai stock/
saját reservation; önmagában nem bizonyít javítási anyagfogyasztást vagy teljesítést; nem számítható még egyszer open PO
coverage-ként. Közös cikkmutex és tényleges sorzárak védik, hogy több igény ugyanazt
a free stockot vagy unassigned supplyt egyszerre ne ossza ki. Gazdátlan, még aktív
commitmentet új beszerzés előtt review/allokáció alá kell venni; nem szabad
automatikusan eltüntetni vagy minden más ownerhez rendelkezésre állónak tekinteni.

PO dispatched/ack állapotot a jelenlegi enum nem tud külön bizonyítani.
A ledger commitment_confirmed tényében kind=sent/supplier_ack, actor/time és
dokumentum szükséges; unknown legacy evidence review-t blokkol, nem jelent draftot.
PO-item/PR-line/GR-item forráskapcsolat marad, új PO üzleti aggregátum nem készül.
GR posting a supply_received eseményt, open coverage csökkentést és fizikai
projekciót atomikusan könyveli; nem keletkezik külön virtuális készlet.
A kézi PO close „received” státusza nem helyettesíti a tényleges GR-t.

Ügyzárásnál RMA-hoz rendelt rendezetlen anyagigény/coverage nem maradhat rejtve.
D11 szerint tovább élő procurement commitment explicit, auditált átadás/reassignment
után más, megnevezett responsibility alá kerülhet; nem kell fiktíven törölni
vagy fogadottnak minősíteni a repair case bezárásához.

## 10. Sémakorrekciók és minimális új identitások

| Tábla / csoport                                                         | Javaslat és indok                                                                                                                                                                                                                                                                                                     |
| ----------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| active_repair_claims, repair_requests, members, histories               | Természetes claim PK marad; closing_command, membership_command és typed claim-history; acquire trigger, close-only finalizer/jogok. Nincs claim ledger tábla.                                                                                                                                                        |
| item_instances                                                          | Allocation_state, ownership verification és latest-event pointer, disposition-history pointer; tényleges quarantine location. Legacy unknown fail-closed.                                                                                                                                                             |
| stock_movements                                                         | Intake/admission/redispatch scope és type; return/release typed FK és purpose-key; external->external kivétel az explicit return-intake célra; további típus/purpose csak kifejezett engedélyezéssel, nem általános scrap/transfer kiskapu. AM-02-G1–G4 DDL/proof és consumer-verifikációja előtt nem engedélyezhető. |
| stock_reservations                                                      | Root/previous kapcsolat; terminal quantity vs logikai obligation külön; egy current order-key; új purpose és reservation_inventory_scope a rights-alapú custody redispatchhez, type/owner/serial CHECK-kel. Company active SUM csak company mennyiségi foglalás.                                                      |
| repair_releases                                                         | U(reservation) erősítés; shipment source identity; attempt/previous/authorization és külön proof. A release maga az attempt, nincs új shipments tábla.                                                                                                                                                                |
| repair_delivery_events, repair_inspections, histories                   | Return intake/admission/inspection/disposition typed kapcsolatok, típusfüggő egyediség; mandatory quarantine. Új inspection_type=return_quarantine és credit/quarantine history dimenziók javasoltak.                                                                                                                 |
| repair_ownership_events                                                 | Reversal és effective predecessor FK-k, eredeti subject composite U/FK; correction külön.                                                                                                                                                                                                                             |
| repair_diagnostic_fees – új                                             | Önálló verified fee, quote-tól független verzió/proof; a díjszabás és az allokáció nem díjtény. Nincs settlement/invoice tábla.                                                                                                                                                                                       |
| repair_diagnostic_credit_allocations + quotation_items                  | Fee-FK/same-order composite; approval-only reserved/retained/released, review proof és typed history. B held/applied nem aktuális protokoll.                                                                                                                                                                          |
| material_requirements, sources                                          | Demand state/revision, procurement_generation; source history immutable, nem whereDoesntHave vezérli az újragenerálást.                                                                                                                                                                                               |
| procurement_coverage_events – új                                        | Független procurement history és részleges quantity conservation: igényrevízió, release, reassignment, commitment/receipt; nem fér a repair-only historyba.                                                                                                                                                           |
| purchase_requisition_items / purchase_order_items / goods_receipt_items | Meglévő identitások/kapcsolatok új ledger FK-s céljai, direct pointer reprezentáns marad. Új oszlop csak a teljes C2 FK/index review indokával, nem automatikus táblaátalakítás.                                                                                                                                      |

A B „7 módosítandó meglévő tábla” leltára történeti minimum; C1 a kapcsolt új RMA
táblákon is korrekciókat tervez. A két további új táblán kívül nem szaporítjuk
az aggregátumokat. Pénz integer units, mennyiség exact decimal, FK-k RESTRICT/
NO ACTION, append-only tények és nincs új soft delete. A régi cascade/null-delete
és legacy adattisztaság továbbra is migráció előtti kapu.

## 11. DB és service felelősségi mátrix

| Invariáns                          | DB-kikényszerítés javaslata                                                     | Service / authorization / bizonyíték                                                   |
| ---------------------------------- | ------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------- |
| Claim serial exclusivity           | PK+composite FK; acquire trigger; restricted delete/finalizer                   | Case-open atomikus members/orders/command/audit; eligibility/owner                     |
| Claim csak teljes close-kor old    | Direct close/delete jog tiltva; finalizer+DELETE trigger és typed close-command | Teljes proofhalmaz tartalma, policy/actor, audit rollback                              |
| Inventory scope / batch / owner    | Enum/NULL-safe CHECK, sentinel U, owner-XOR, aktív serial U                     | Minden consumer eligible qty és rights; legacy mapping                                 |
| Quarantine                         | Allocation CHECK és history-FK; protected projection writes                     | Minden író/olvasó explicit kizárás; condition/identity/authorized disposition          |
| Shipment egyszer                   | Source occurrence U + move/command/reservation U + attempt/previous FK          | Valódi fizikai proof, previous shipment rendezett, új attempt nem retry                |
| Return egyszer / admission egyszer | Purpose-specifikus U és release/serial composite                                | Actual receipt és condition/ownership verification; három dimenzió külön               |
| Fee/credit                         | Fee/order/version és same-order FK, active source U, állapot-CHECK              | Approved verzió, összegcap/exact arithmetic, execution/settlement review               |
| Reversal                           | Required original FK, same subject, U(reversed original), event U               | Jogalap, permission, effective lánc és correction tartalma                             |
| Coverage conservation              | Source/FK/idempotency U; immutable ledger; type/qty CHECK                       | Source/commitment zár alatti több sor összege, item/unit/owner és review               |
| History / proof                    | RESTRICT, korlátozott UPDATE/DELETE, célzott trigger/procedure                  | Konkrét verzió/checksum/retention, dokumentum target policy, külső proof előellenőrzés |

CHECK nem olvas más táblát és nem bizonyít jogosultságot. A B által igazolt
RESTRICT/NO ACTION+CHECK kombináció nem bizonyítja az összes új FK DDL-jét.
SQLite nem MySQL concurrency/grant-bizonyíték. Új pénzügyi szabály vagy
közvetlen quantity update egyik rétegben sem megengedett.

## 12. Előírt regresszió és konkurencia – még nem futtatott C1-esetek

Minden alábbi eset **DESIGNED / NOT EXECUTED**. Nem készült új executable script.
Valódi service/HTTP/policy teszt és MySQL kétkapcsolatos barrier kell; SQLite
constraint/workflow bizonyítás külön, más motor konkurenciáját nem helyettesíti.

| ID    | Kötelező eset és elvárt eredmény                                                                                                                                                |
| ----- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| N1    | NULL batch insert-race, sentinel/FK/NULL-ágak; egy balance, hibás tuple tiltott                                                                                                 |
| N2    | Company/custody/pending-intake/quarantine/transit riport és forecast; available csak eligible, nincs dupla hold-levonás                                                         |
| N3    | Reserve10, consume3 -> remaining7; release legfeljebb7; több batch/location/idegen owner elutasítva                                                                             |
| N4    | Serial reserve kontra production/repair/sales; egy global owner, custody/quarantine nem jelölt                                                                                  |
| N5    | Legacy customer-order-item több PO-val; repair külön; unchanged demand retry nem új revision vagy procurement_generation                                                        |
| G1-A  | Két case ugyanarra a serialra; többtagú open egyik hibás tagja mindent rollbackel, triggerből claim atomikus                                                                    |
| G1-B  | Runtime DELETE/UPDATE/TRUNCATE, member átírás, direct case-close elutasítva; illetéktelen finalizer/policy hívás tiltott                                                        |
| G1-C  | Close kontra open/child/time/return; incomplete proof tiltott; audit/finalizer failure után régi claim él, nincs fél close                                                      |
| G1-D  | Claimhistory nyitás/zárás rekonstruálható későbbi új ügy mellett; jogosult close az egyetlen release                                                                            |
| Q1    | Failed delivery 0 stock; serialos dokumentált intake mindig quarantined, unknown ownerrel is; duplicate return más commanddal tiltott                                           |
| Q2    | Karanténból production/sales/repair/unrelated reserve tiltott; dedicated location és allocation guard együtt; direct release nem bypass                                         |
| Q3    | Identity/condition/rights proof hiányzik vagy rossz serial: nincs admission/feloldás; actual physical hely továbbra is látható                                                  |
| Q4    | Authorized company redispatch: dispatch -1, intake0, admission+1, új dispatch-1; két delivery proof nem további mennyiség                                                       |
| Q5    | Custody-rights redispatch 0 company stock; nincs kikényszerített reversal; régi attempt proofja nem zárja az újat                                                               |
| Q6    | Karantén -> available / scrap, permission és fault rollback; receipt/admission/rebuild nem dupláz; unresolved case nem close                                                    |
| K1    | Draft/submitted nem foglal; approved foglal, free/túlzott/idegen order/eltérő currency tiltott                                                                                  |
| K2    | Két quote approval ugyanarra a fee-re; egy allocation; fee correction nem nyit új forrást                                                                                       |
| K3    | Cancel/supersede nem release; authorized unused+unsettled release őrzi régi quote/credit/fee történetet                                                                         |
| K4    | Release vs execution-start vagy settlement observation; azonos order-lock, unknown/executed/settled nem lesz automatikusan unused                                               |
| K5    | Settled correction az eredeti allocationra hivatkozik; accounting nélkül nem állít pénzügyi lezárást vagy új kreditjogot                                                        |
| O1–O4 | Actual return nem transfer; reversal original/proof nélkül tiltott; idegen subject tiltott; duplicate reversal/command tiltott, correction és későbbi valódi transfer megőrzött |
| P1    | Demand-only withdraw vs generate; egy hatályos eredmény, withdrawn igény nem generál                                                                                            |
| P2    | Draft/requested/approved PR review vs PO generation; sent/ack PO silent delete/cancel tiltott                                                                                   |
| P3    | Partial receipt vs release/reassign; actual GR és source qty conserved; received nem open coverage még egyszer                                                                  |
| P4    | RMA cancel nem törli PO-t; ledger+source+receipt+movement/audit fault rollback; kézi received státusz nem receipt proof                                                         |
| P5    | Több demand/owner és új revision ugyanarra a cikkre; delta-only, unit/mapping, free supply egyszeri allokáció                                                                   |
| P6    | Coverage release vs generation/reassignment két kapcsolattal; új command nem dupláz, paired -q/+q együtt rollbackel vagy commitol                                               |

Az eredeti C1–C10 B-próbák közül G1-A/C a claim/close, Q a release/return/
reservation, K az approval/start, P a demand-generation, N a consume-regresszió
teljes követelményeit bővíti. Genealógia C9, actual idő C10, warranty subject,
F4 scope/approval és document policy tesztjei változatlanul szükségesek.
Deadlock1213 és lock-timeout retry teljes transactionre, korlátozott és idempotens;
audit/proof/movement hiba soha nem hagy részleges sikeres commandot.

## 13. RMA-BD-001–020, D1–D5 és F1–F4 megfelelés

| Követelmény | Megőrzött invariáns / C1 kapcsolódás                                                       |
| ----------- | ------------------------------------------------------------------------------------------ |
| RMA-BD-001  | Többtermékes ügy, egy order/termék; G1 atomikus nyitás                                     |
| RMA-BD-002  | Saját gyártás+serial+bizonyított eredet; D2 nem puszta típusszűrő                          |
| RMA-BD-003  | Jogosult belső rögzítő; reporter és owner külön                                            |
| RMA-BD-004  | Egy aktív RMA/serial egész ügyclose-ig; G1                                                 |
| RMA-BD-005  | Manuális warranty/pending/indokolt override változatlan                                    |
| RMA-BD-006  | Aktuális approved ajánlat startkor; D6 nem helyettesít végrehajtási approvalt              |
| RMA-BD-007  | Original receipt normál balance0; új quarantine nem company tulajdonvélelem                |
| RMA-BD-008  | Független original disposition; G3 nem takarja el                                          |
| RMA-BD-009  | Javítási operation/QC próbálkozások historyja; shipment attempt nem repair attempt         |
| RMA-BD-010  | Több dolgozó, actual time és anyag; G2 partial consume                                     |
| RMA-BD-011  | Proofos teljes case-close, nem státuszösszeg; G1/G3                                        |
| RMA-BD-012  | Igazolt handover+rule/version; return nem warranty reset                                   |
| RMA-BD-013  | Immutable submitted/approved quote és ügyfélválasz                                         |
| RMA-BD-014  | Free/fixed/time díj, igazolt egyszeri credit; D6/D7                                        |
| RMA-BD-015  | Serial mindkét oldalról elérhető teljes history                                            |
| RMA-BD-016  | Original receipt membership/serial/state/prior receipt gate megmarad                       |
| RMA-BD-017  | Customer custody/karantén nem sale/production/repair consumption                           |
| RMA-BD-018  | Normál javítási anyagigény/stock, hiányból PR; D11                                         |
| RMA-BD-019  | Shipment immutable címzett/dátum/reference/proof és condition                              |
| RMA-BD-020  | Company reserve/prepare, valódi dispatch egyszer/shipment; D9 szövegszerű pontosítás AM-01 |

D1: a három scope változatlan, customer stock nem normal balance; AM-02 nem
negyedik scope. D2: semi_finished_product saját gyártás+serial+eredet alapján
jogosult lehet. D3: egy case egy owner, reporter külön, más owner külön case.
D4: eredeti/csere warranty-history külön, nincs automatikus új időszak.
D5: physical return/dispatch és ownership/reversal külön idő és bizonyíték.

F1: company reserve/preparation, külön dispatch/delivery/ownership, idempotens
egyszeri posting egy valódi shipmentre. F2: teljes proofhalmazos atomikus close.
F3: eredeti–csere serialpár és warranty/gyártási history megmarad. F4: csak el nem
kezdett operation jogosult supersedingje, új scope-nál ajánlati authorization gate.

## 14. ADR-egyeztetés és jóváhagyott amendment-történet

D6–D11 elfogadott; a D1–D5 és a húsz BD üzleti invariánsa nem kerül lecserélésre.
Az alábbi **korábbi szövegszerű ütközéseket/pontosításokat** az AM-01–04
projektgazdai jóváhagyása lezárta. Az ADR 3.4 amendment-története és érintett
fejezetei átvezetve. A táblázat a C1 review eredeti indokait és az approved
amendment tartalmát őrzi; nem hátramaradt jóváhagyási kérelem.

| Javaslat                                 | Érintett ADR-pont                      | 2026-10-29-én jóváhagyott tartalom / korábbi indok                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            |
| ---------------------------------------- | -------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| AM-01 – D9 shipment identity             | 6.5/6.6, 7.7, 8.3.6, 10 és 15.2        | „A logikai cserefolyamat több, bizonyított visszatéréssel elválasztott fizikai shipmentet tartalmazhat. Egy shipment egyszer könyvelhető; mindegyik új, immutable kiadási identitás és új reservation. Redispatch D9 guardjai kötelezők. Az eredeti termék dispositionje független.” A korábbi „ugyanaz a cserefolyamat egyszer” szóhasználatot valódi shipmentre kell pontosítani.                                                                                                                                                                                                                                                                                                                                                                           |
| AM-02 – D8 karantén és pending admission | 7.7, 8.2 return-sor, 8.3.5, 10/11      | „Minden replacement physical return dokumentált intake+karantén. Ismeretlen owner nem akadályozhatja a fizikai érkezés blokkolt rögzítését és nem növelhet company balance-t. Pending intake external könyvelési oldal mellett belső helyen is követett lehet; a pontos type-scoped external->external intake és későbbi admission külön esemény, nem új scope és nem második receipt. Felhasználás csak igazolt authorized disposition után. Physical-on-site riport a pending admissiont külön jelöli. Igazolt nem-company disposition-right alapú redispatch külön custody-oldali út, company balance-hatás nélkül; nincs kikényszerített ownership reversal.” Ez a T-C1-02 elfogadott jelentése, kötelező AM-02-G1–G4 mellett; G3 DDL-je még nem igazolt. |
| AM-03 – D6/D7 és settlement határ        | 3.1 új döntésregiszter, 6.4, 7.6, 14.2 | „Önálló verified diagnostic fee, approved quote foglal, unused unsettled release jogosult auditált review-val; settled külön korrekció. Settlement megfigyelés/proof szükséges, számla/követelés/fizetettség alrendszer továbbra sincs az induló scope-ban.” Az ADR fizetettségi státusz kizárását és az új D7 settlement observationt kifejezetten el kell különíteni.                                                                                                                                                                                                                                                                                                                                                                                       |
| AM-04 – D10/D11 történeti integritás     | 3.1, 8.4, 9/10, 14.2                   | „Ownership reversal original-event FK-val, új immutable tény. RMA-demand cancel nem procurement-cancel; coverage release/reassign és actual receipt külön auditált esemény, forrástörténet megmarad.” D10/D11 regisztrálása és a végrehajtási kapuk aktualizálása.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            |

D6 approval-foglalás a B technikai ajánlásával ütközik, nem az ADR korábbi
explicit üzleti döntésével. D8 kötelező karantén a korábbi return-to-stock
ágat szigorítja. D9 az egyetlen shipmentre értett posting-invariánst őrzi,
de a korábbi korlátozó megfogalmazást kiterjeszti. AM-02 on-site/scope
értelmezése már jóváhagyott, négy kötelező integritási feltétellel. A régi
korlátozó szövegek amendment-történettel pontosítva, nem párhuzamos normatív
szerződések. Az elfogadás nem teljes DDL- vagy működési bizonyíték.

## 15. Nyitott technikai kapuk és C2-készültség

| Azonosító / állapot                               | Felelős szerep                 | C2 előfeltétel / következő bizonyíték                                                                                                                 |
| ------------------------------------------------- | ------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| T-ADR – CLOSED / APPROVED                         | Projektgazda/architect         | AM-01–04 jóváhagyva és ADR-be átvezetve; AM-02-G1–G4 kötelező, a DDL/teszt továbbra is C2-kapu                                                        |
| T-DB – SPECIFIED / UNVERIFIED                     | DBA/security/backend           | Claim grant/trigger/finalizer DDL, privilege-denial és teljes close-query terv; SQLite eltérések explicit                                             |
| T-CHAIN – SPECIFIED / DDL OPEN                    | Backend/DBA                    | Root/previous reservation tuple és commit-time guard, active order-key átadás, return/admission occurrence indexek pontos DDL-je                      |
| T-CREDIT – BUSINESS CLOSED / PROOF INTERFACE OPEN | Üzleti/pénzügyi owner+backend  | Execution és settlement review hiteles forrása, szerepköre, retention; unknown fail-closed; settled korrekció interfésze nem könyvelési implementáció |
| T-COVERAGE – SPECIFIED / DDL OPEN                 | MRP/procurement/backend        | Új event type CHECK-ek, partial conservation, generation line-kulcs és authoritative PO sent/ack/receipt evidence                                     |
| T-LEGACY – BLOCKED                                | Adatgazda/DBA                  | Reprezentatív read-only snapshot+opening stock baseline; több-PO legacy jelentés egyeztetése; nem automatikus backfill                                |
| T-ROLLOUT – BLOCKED FOR ENABLEMENT                | Backend/QA                     | G2 minden sora adaptált és regresszióval igazolt; két-domaines zársorrend, cache, mixed-version deployment/rollback                                   |
| T-MONEY/RETENTION – OPEN                          | Illetékes üzleti/szakmai owner | Money scale/rounding/időegység, proof retention, employee globális időátfedés/QC scope materiality régi kérdései                                      |

G1: **DESIGN RESOLVED / IMPLEMENTATION UNVERIFIED**.
G2: **IMPACT MAPPED / ROLLOUT BLOCKED**.
G3: **LIFECYCLE SPECIFIED / DDL UNVERIFIED**.
C1: **ACCEPTED WITH CONDITIONS**; D6–D11 ACCEPTED, AM-01–04 APPROVED,
AM-02-G1–G4 MANDATORY.
A business gate D6–D11 ismert és lefedett; nincs új ügyféloldali szabály kitalálva.

**C2 verdict: PREPARATION PLAN READY; C2 NOT EXECUTED.**
A következő fázist a [C2 előkészítési terv](rma-0003c2-detailed-schema-review-plan.md)
WP1–WP7 csomagjai írják elő. Nincs több amendment-jóváhagyási blocker; a teljes
séma/DDL, jogosultság, proof és regressziós kapuk továbbra is nyitottak.
Ez a closure feladat nem jelenti a C2 specifikáció végrehajtását vagy migration readiness-t. Migrációfuttatás, új verifikációs script vagy alkalmazásimplementáció
külön engedély nélkül nem következik e dokumentumból.
A reprezentatív adataudit és G2 regresszió rollout/migration kapu, nem ebben
a documentation-only feladatban elvégzendő kódmunka.

## 16. Történeti C1-tervezési bizonyítékok és ellenőrzés

A [B script](verification/rma-0003b-verify.php) és
[B eredmény](verification/rma-0003b-results.json) változatlan történeti artifact.
A mentett B futás 35/35 SQL-mechanizmus-prototípusa (24 MySQL, 11 SQLite),
MySQL 8.4.7/InnoDB/REPEATABLE-READ és SQLite 3.53.4 eredménye megmarad.
C1-ben **nem futott újra**, nem bizonyítja az új grant/finalizer, quarantine,
fee/approval-only vagy procurement-ledger sémát. A B audit hét vizsgált táblája
üres volt; ebből nincs reprezentatív legacy minősítés.

Az eredeti C1-tervezéshez rögzített SHA-256 baseline (nyers fájlbájtok).
Az ADR hash-e a Closure előtti történeti állapot; most AM-01–04 szerint módosult.
A két B artifact továbbra is bájtszinten változatlan:

| Fájl           | Eredeti C1 baseline hash                                           |
| -------------- | ------------------------------------------------------------------ |
| ADR-0006       | `3704c2047110ed8030c2ac9cf6acef14dd3f7acde989aab165abe11369053bfc` |
| B verify.php   | `adcd728435a47165594562101b1a2c6920d3bf326abb1e719d5204c0b25daefa` |
| B results.json | `d1b4443724984cbd21c9852c4611fa659411e09924192d7b3b9296164e33a6e6` |

DoD-típus: dokumentáció. Kötelező átadásellenőrzés: D6–D11, 20/20 BD, D1–D5,
F1–F4 tartalmi traceability; relatív linkek és anchorok, Markdown/Prettier,
actual-file whitespace, git diff --check és staged/final Git-státusz.
Alkalmazás-/migráció-/adatbázisteszt N/A e read-only scope-ban; az előírt jövőbeli
tesztek nem jelenthetők sikeresnek. Nincs alkalmazáskód, migráció, adatírás,
új executable, dependency, commit, push vagy merge.

Az eredeti C1-tervezési átadás történeti eredménye: 6/6 új döntés és 20/20 BD mindhárom tervben; D1–D5 és
F1–F4 tartalmi ellenőrzése megtörtént. 123 relatív hivatkozás (az anchorral együtt)
ellenőrzött; Prettier és actual-file whitespace, valamint git diff --check sikeres.
Az A/B eredeti törzsét az új olvasási kiegészítés eltávolításával az induló
SHA-256-tal összevetettük: teljesen egyezik. Az eredeti C1-tervezési átadáskor ADR és mindkét B artifact nyers
hash-e egyezett az akkori indulóval; e megállapítás történeti, nem az új ADR-re vonatkozik. Staging üres; végül csak a három terv és a két
megőrzött B artifact nem követett. Branch és HEAD nem változott.

Kapcsolódó ellenőrzési szabály:
[Definition of Done](../../docs/project-management/definition-of-done.md),
[quality gates](../../docs/development/quality-gates.md).

## 17. RMA-0003/C1 Closure – formális elfogadás (2026-10-29)

A projektgazda kifejezett Closure-jóváhagyásával AM-01–AM-04 **APPROVED**,
az [ADR-0006](../decisions/0006-rma-repair-management-architecture.md)
3.2–3.4 és érintett életciklus/készlet/kredit/procurement fejezeteiben átvezetve.
C1 végső státusza **ACCEPTED WITH CONDITIONS**, nem elvégzett implementáció.

| Tétel       | Végső állapot                                                                                                                         |
| ----------- | ------------------------------------------------------------------------------------------------------------------------------------- |
| RMA-D6–D11  | ACCEPTED                                                                                                                              |
| AM-01–AM-04 | APPROVED; projektgazda, 2026-10-29                                                                                                    |
| AM-02-G1    | MANDATORY: verified serial/location/timestamp/source shipment/supporting evidence                                                     |
| AM-02-G2    | MANDATORY: intake normal company stock0; company admission csak alkalmazandó befogadási követelmények teljesülése után                |
| AM-02-G3    | MANDATORY: quarantine kizárt production/sales/repair-consume/unrelated reserve/normal availability célokra minden writerben/readerben |
| AM-02-G4    | MANDATORY: külön intake/admission identity és idempotencia; admission nem második fizikai receipt                                     |
| G1          | DESIGN RESOLVED / IMPLEMENTATION UNVERIFIED                                                                                           |
| G2          | IMPACT MAPPED / ROLLOUT BLOCKED                                                                                                       |
| G3          | LIFECYCLE SPECIFIED / DDL UNVERIFIED                                                                                                  |
| C1          | ACCEPTED WITH CONDITIONS                                                                                                              |
| C2          | Előkészítési terv kész; részletes specifikáció és verifikáció NOT EXECUTED                                                            |

A `external -> external` kivétel nem általános inventory transferjog:
explicit type/purpose, igazolt return és karantén kontextus szükséges.
Jóváhagyott alapeset a return-intake. A korábbi intake/scrap típusjavaslatból
nem következik általános scrap-kivétel; más típus/cél engedélylistája és
DDL/proof/permission szerződése C2-ben tisztázandó.

Az amendment-jóváhagyási kapu lezárt; a 15. fejezet többi technikai/legacy/
rollout kapuja megmarad. A részletes [C2 előkészítési terv](rma-0003c2-detailed-schema-review-plan.md)
WP1–WP7 csomagjai még végrehajtandó munkát írnak elő. A B script és eredményfájl
változatlan, nem futott új SQL vagy alkalmazásteszt; ezek nem új C1/C2 bizonyítékok.
A jelen Closure ellenőrzései a dokumentációra és a meglévő artifactok
változatlanságára korlátozódnak. Nincs staging/commit/push/merge, alkalmazáskód,
migráció, adatbázis-írás, új executable vagy dependency-változás.

Closure dokumentációs ellenőrzés (2026-10-29): ADR Accepted; D6–D11 6/6,
AM-01–04 4/4 és AM-02-G1–G4 4/4 lefedett. Mind az öt ADR/terv dokumentumban
20/20 BD szerepel; D1–D5 és F1–F4 eredeti szövege változatlan, BD-020 csak az
AM-01 jóváhagyott shipmentenkénti pontosításával módosult. 174 relatív fájl-/anchor
hivatkozás létező célra mutat. C2-ben 25 különböző táblajelölt és WP1–WP7 szerepel.
A/B korábbi törzse az új Closure olvasási kiegészítés leválasztása után az induló
hash-sel egyezik; mindkét B executable/result artifact nyers hash-e változatlan.
Prettier, actual-file whitespace és git diff --check sikeres. A B futást csak
statikusan ellenőriztük; új verifikációs vagy alkalmazási sikert nem állítunk.
