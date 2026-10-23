# Merlin L1 / TC-02 — jogosultsági és üzleti védelmi szerződéstervezet

- **Azonosító:** `MERLIN_TC02_AUTHORIZATION_BUSINESS_GUARD_CONTRACT_DRAFT`.
- **Dátum:** 2026-10-23.
- **Dokumentumstátusz: DRAFT. A TC-02 egésze: BLOCKED.**
- **Kiindulás:** `feature/merlin-candidate`, helyi HEAD, helyi követőág és `git ls-remote` szerinti távoli HEAD: `f89c3742a0d778f782c52de5848dddff07370d67`; tiszta munkakönyvtár, üres staging, nincs untracked fájl.
- **Hatókör:** read-only forrásaudit és egy új magyar dokumentum. Nincs implementáció, DB-kísérlet, csomagtelepítés, staging, commit, push vagy providerhívás.
- **Ajánlás:** a C hibrid alternatíva további szerződéstervezése; **PROPOSED**, nem elfogadott vagy már működő protokoll.

## 1. Cél, források és bizonyítékszintek

A cél olyan `FinalizeRun`-protokoll előkészítése, amelyben a sikeres lezárás
határidőfeltétele egy szerializált tranzakciós döntési ponton teljesül,
a szükséges jogosultsági és üzleti feltételek pedig COMMIT-ig védettek.
Az időszemantikát nem tárgyaljuk újra. A vizsgálati eredmény továbbra is tanács,
nem üzleti intézkedés vagy a Problem Case megoldása.

Forrásprioritás:

1. [TC-02 / 1. döntés](MERLIN_TC02_DECISION_003.md), különösen 2–5. rész: az időszemantikában irányadó, **ACCEPTED**.
2. [L1 döntési baseline](MERLIN_L1_DECISION_BASELINE.md), [Recovery 002](MERLIN_RECOVERY_002.md), [ADR 0017](../../../.kiro/decisions/0017-merlin-investigation-foundation.md), [ADR 0018](../../../.kiro/decisions/0018-merlin-execution-lifecycle.md): a többi elfogadott határ és a nyitott kapuk.
3. [L1 Technical Contract DRAFT](MERLIN_L1_TECHNICAL_CONTRACT_DRAFT.md), különösen 3.3., 5–6., 8–10. rész: tervezet, nem új jóváhagyás. A régi fizikai COMMIT-deadline szöveget a Decision 003 kifejezetten módosítja.
4. [ADR 0009 netting](../../../.kiro/decisions/0009-material-requirement-netting.md), [ADR 0016 eligibility](../../../.kiro/decisions/0016-material-requirement-demand-eligibility.md), [projektkonvenciók](../../architecture/project-conventions.md), [tesztelési steering](../../../.kiro/steering/testing.md), [backend gate](../../backend-quality-gate.md), [rétegezett gate-ek](../../development/quality-gates.md), [Definition of Done](../../project-management/definition-of-done.md).

### 1.1. Minősítések és vizsgálati határ

| Jelölés           | Jelentés ebben a dokumentumban                                                                   |
| ----------------- | ------------------------------------------------------------------------------------------------ |
| ACCEPTED          | Kizárólag a korábban ténylegesen elfogadott követelmény; nem implementációs igazolás.            |
| PROPOSED          | Minden új guard, sorrend, SQL-vázlat, tárolási vagy hibakezelési részlet és tesztterv.           |
| OPEN              | Felhasználói, domain-, DB- vagy üzemeltetési döntés kell.                                        |
| BLOCKED           | Hiányzó előfeltétel/bizonyíték miatt nem fogadható el végrehajtható szerződésként.               |
| SOURCE_CONFIRMED  | A rögzített HEAD helyi forrásában ellenőrzött viselkedés vagy létező tesztforrás.                |
| ENGINE_DOCUMENTED | Hivatalos motorleírás; nem a helyi konfiguráció vagy driver futási bizonyítéka.                  |
| TEST_CONFIRMED    | E feladatban végrehajtott teszt bizonyítaná. **Alkalmazás-/DB-protokollra nincs ilyen állítás.** |
| INFERRED          | Forrásból levezetett kockázat, lehetséges garancia vagy tervezési következtetés.                 |
| UNKNOWN           | A teljesség, runtime környezet vagy konkrét útvonal nem bizonyított.                             |

A mátrixok olvasó/író hivatkozásai `SOURCE_CONFIRMED` tények, a kockázatok
`INFERRED`, a guard oszlopok **PROPOSED / INFERRED** tartalmak, kivéve ahol
`UNKNOWN` szerepel. A 6–15. rész összes új műszaki szerződése **PROPOSED /
INFERRED**. A motorállítások külön `ENGINE_DOCUMENTED` jelölést kapnak.
Ezzel a státusz és a bizonyíték két külön tengely marad.

Vizsgált terület: `app`, `bootstrap`, `routes`, `config`, releváns migrációk,
seederek, tesztforrások és telepített framework/package forrás. Keresési
minták: modellhivatkozások, `create/update/delete/save/upsert`, kapcsolati
írások, permission API-k, nyers táblanevek, transaction és cache útvonalak.
Nem indult Laravel-bootstrap, teszt, seeder vagy adatbázis-kapcsolat.
Éles DB-séma/index, triggerek, DB-jogok, más processzek, kézi SQL, külső
import és dinamikus extension útvonalak teljessége **UNKNOWN**.
Ezért a felsorolt writerkatalógus ellenőrzött minimum, nem zárt világbeli bizonyítás.

`SOURCE_CONFIRMED`: a [composer.lock:1134][LOCK] és [composer.lock:3681][LOCKP],
valamint a `vendor/composer/installed.json` egyezik: Laravel `v13.23.0`
(`92a707229148e57f08a249211c8a5a194159c619`), Spatie Permission `8.0.0`
(`70a6ab04108616b438e0839598f473b513281644`). A vendor-hivatkozások ezekre
a rögzített upstream commitokra mutatnak; a sorszámokat helyben ellenőriztük.

## 2. ACCEPTED — változatlan követelmények

Forrás: [Decision 003](MERLIN_TC02_DECISION_003.md), `SOURCE_CONFIRMED`.

- A claimhez rendelt deadline 180 másodperc, immutable; UTC epoch seconds és DB-óra az authority.
- A szerializált, tranzakción belüli döntési ponton `db_now < execution_deadline_epoch` szükséges. Egyenlőségnél nincs sikeres FinalizeRun.
- A fizikai COMMIT bekövetkezhet a deadline után is.
- A szükséges jogosultsági és üzleti feltételek COMMIT-ig védettek maradnak.
- State és kötelező audit/receipt ugyanazon tranzakcióban atomikus.
- Az első érvényes terminális állapot immutable.
- Unknown COMMIT után nincs vak retry; hiányzó receipt önmagában nem rollbackbizonyíték.
- Jogosultságvesztés nem teremthet új AI-, tool- vagy üzleti végrehajtási jogot.

Az L1-D02 két lifecycle-kiegészítő táblája nem engedélyez automatikusan
harmadik guard táblát vagy más modulok sémaátépítését. Az alábbi guard/version
tárolás **OPEN külön keresztmodulos architekturális döntés**, nem a baseline
hallgatólagos bővítése. A konkrét guard-protokoll továbbra sem ACCEPTED.

## 3. Authorization dependency és writer mátrix

### 3.1. A jelenlegi pozitív döntés adatútja

`SOURCE_CONFIRMED`: a [Start service:143–145][STARTAUTH] két
`Gate::forUser($actor)->allows(...)` hívást használ. A
[Laravel Gate:428][GATE] először a before callbackeket értékeli; az első
nem-null eredmény dönt a before láncban ([Gate:558][GATEBEFORE]). A
[Spatie registrar:114–123][REGGATE] a `checkPermissionTo` útvonalat regisztrálja.
Az [AppServiceProvider:184–186][SUPER] a `super-admin` szerepkört true-val
kezeli; ez a permission-részt helyettesítheti, a kezdeményezőazonosságot,
case/claim bindinget és üzleti gate-et nem.

`SOURCE_CONFIRMED`: [HasPermissions:173–213][HPCHECK] szöveges névből
`Permission::findByName`-ot hív. Ez [Permission:103–170][PERMFIND] szerint
a registrar permission-kollekcióját olvassa. A role-ág a feloldott Permission
`roles` kapcsolatát használja ([HasPermissions:300–321][HPROLE]), ezért a User
friss `roles.permissions` eager loadja önmagában nem iktatja ki ezt a cache-ágat.
A registrar [178–210][REGCACHE] processzen belüli kollekciót és cache store-t
használ; ez nem DB-tranzakciós permission-snapshot.

| Adat / kapcsolat                                 | Reader                                                                                      | Writer                                                                                                                                    | Cache                                                                                                         | Jelenlegi lock                                                                       | Hiányzó védelem                                                                                        | Javasolt guard                                                                                                   |
| ------------------------------------------------ | ------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------ | ---------------------------------------------------------------------------------------------------------------- |
| Live User, ID, initiating/historical kötés       | [UserRepository:18–24][USERREAD], [Start:131][STARTIDENT]                                   | [UserController:52][USERCTRL] → [UserRepository:61–79][USERWRITE]; [UserAdminService:27–42][USERDELETE] → [Abstract repository:102][ABSR] | Auth-principal lehet korábbi objektum; külön friss User van                                                   | Admissionben User shared lock                                                        | Nem teljes lifecycle protokoll; törlés előtti pivot detach külön megvédendő                            | `A(actor)` S finalizernek, X minden identity/delete és user-pivot writernek; egy tranzakció                      |
| Közvetlen `model_has_permissions`                | [HasPermissions:83][HPREL], [HasPermissions:316–321][HPROLE]; [UserRepository:21][USERREAD] | [HasPermissions:387,442,459][HPWRITE]; [E2ETestSeeder:86][E2E]                                                                            | User `permissions` relation cache; permission névfeloldás registrar                                           | Eager-load query sharedLock, hiányzó pivot/scope SQL-locklefedettség nem bizonyított | Direct grant/revoke és hiányzó sor, `model_type`, guard                                                | `A(actor)` X minden attach/detach/sync előtt; Finalize S mellett cache-független pivot olvasás                   |
| `model_has_roles`                                | [HasRoles:56][HRREL], [UserRepository:22][USERREAD]                                         | [UserRepository:53,76][USERWRITE]; [HasRoles:218,271,298][HRWRITE]; [AdminUserSeeder:29][ADMINSEED]                                       | User `roles` loaded relation                                                                                  | Admission shared query; pivot lock tényleges SQL-je nincs mérve                      | Új role-tagság, eltávolítás, super-admin tagság                                                        | `A(actor)` stabilizálja a teljes tagsághalmazt; writer X                                                         |
| Role sor: név, guard, létezés                    | [HasRoles:321][HRHAS], [App provider:184][SUPER]                                            | [RoleController:43–60][ROLECTRL] → [RoleAdminService:29–49][ROLESVC] → [RoleRepository:29–57][ROLEWRITE]                                  | User role objektum lehet stale                                                                                | Admission meglévő role sorokra shared query                                          | Role átnevezése `super-admin`-ra/arról, delete előtti detach                                           | Minden hozzárendelt `R(role)` S; átnevezés/törlés X, guard alatt újraolvasás                                     |
| `role_has_permissions`, role-alapú permission    | [HasPermissions:300–306][HPROLE], [Role:85][ROLEREL]                                        | [RoleRepository:37,54][ROLEWRITE]; [RoleAdminService:34][ROLESVC]; [RolesAndPermissionsSeeder:179][ROLESEED]                              | Registrarból hidratált Permission→roles kulcsfontosságú; nem csak User cache                                  | `roles.permissions` shared query, de nem bizonyított cache-egyezés/range védelem     | Meleg registrar, role-grant/revoke, hiányzó pivot                                                      | `R(role)` X minden sync/give/revoke előtt; Finalize S, friss SQL                                                 |
| Permission sor: név, ID, guard                   | [Permission:103–170][PERMFIND], [config:16][PCONFIG]                                        | [PermissionRepository:8][PERMREPO] örökölt CRUD [78–104][ABSR]; [RolesAndPermissionsSeeder:147][ROLESEED]; vendor model API               | Registrar kollekció, 24 órás konfigurált TTL                                                                  | Admission permissions queryre shared lock; cache más kapcsolatból is tölthet         | Átnevezés/törlés/új név és pivot cascade                                                               | `P(guard,ability)` S a két névre; catalog mutation X régi és új névre, permission sor locking read               |
| User/role/permission törlés, pivot cascade       | [User:66][USERMODEL], [HasRoles:27–38][HRDELETE], [HasPermissions:33–46][HPDELETE]          | User/role service fent; vendor deleting hookok; [permission migration:54–114][PMIG] cascade                                               | Model hookok invalidálhatnak, bulk query nem azonos model eventtel                                            | Service delete [Abstract service:96–100][ABSS] nem saját transaction wrapper         | Detach megelőzheti a parent tényleges DELETE-zárát; polymorphic pivotban nincs user FK                 | X guard a legelső detach előtt, teljes törlés egy owned writer-tranzakció                                        |
| Permission registrar, store és invalidation      | [Registrar:66][REGCONFIG], [178][REGCACHE]; [config:206][PCACHE]                            | [RefreshesPermissionCache:9–17][REFCACHE], [HasPermissions:537][HPFORGET], seederek                                                       | Processzmemória + `spatie.permission.cache`; default store [cache config:18][CACHECFG] szerint konfigurálható | Nincs közös authorization-tranzakciós cache-lock                                     | In-transaction invalidation után más processz régi DB-adatból újratölthet; rollback sem cache-rollback | Pozitív lifecycle döntésből cache kizárva; commit utáni invalidation külön általános cache-konzisztencia feladat |
| Guard/team/morph konfiguráció, új Gate-hook      | [config permission:16–176][PCONFIG], [Gate:558][GATEBEFORE]                                 | Konfiguráció/deployment; alkalmazásban az egyedi Gate-before [184][SUPER] azonosított                                                     | Hosszú életű worker konfigurációja                                                                            | Nem DB-row lock                                                                      | Runtime config/extension-váltás és teljes külső hookleltár UNKNOWN                                     | Rögzített policyverzió/deployment kapu; változáskor parity review, nem fiktív DB-guard                           |
| Külső admin SQL, import, dinamikus custom writer | UNKNOWN; nincs bizonyított teljes belépési leltár                                           | UNKNOWN                                                                                                                                   | UNKNOWN                                                                                                       | UNKNOWN                                                                              | Guardot megkerülő írás mindent érvényteleníthet                                                        | Zárt írási jogosultság és writerleltár bizonyítása előtt BLOCKED                                                 |

`SOURCE_CONFIRMED`: a [PermissionController:16–23][PERMCTRL] csak indexet ad;
nem találtunk benne direct permission revoke endpointot. Ez nem jelenti a
vendor API, örökölt repository vagy seeder írási képességének hiányát.
User és role controller meglévő UI útvonala nem teljes authorization-writer lista.

`SOURCE_CONFIRMED`: [permission config][PCONFIG] a vendor Role/Permission
modelleket használja; teams és wildcard tiltott, a Gate-integráció engedélyezett,
events és Octane reset listener tiltott. [User:66][USERMODEL] HasRoles-t használ.
A [permission migráció][PMIG] `name,guard_name` unique-ot, pivot PK-kat és
model-ID/morph indexeket ad. Ezek nem azonosak egy teljes revocation-protokollal.

## 4. Business dependency és writer mátrix

### 4.1. A tényleges resolver és a scope határa

`SOURCE_CONFIRMED`: [Resolver:20–52][RESOLVER] case-t és requirementet olvas,
source validityt értékel, majd paraméter nélküli `netting->calculate()` eredményéből
választja ki a case requirementjét. [NettingRepository:38–45][NETREAD]
minden nem soft-deleted requirementet lekér; [25–35][NETREAD] betölti a
customer item/order, production order és BOM item kapcsolatokat.
[Eligibility:20–81][ELIG] szűri a lifecycle-t, törlést és lineage-konzisztenciát.
[NettingService:26–64][NETCALC] itemenként, dátum/id sorrendben fogyó poolokat
épít; nem a `missing_quantity` snapshot az authority.

`INFERRED`: a kiválasztott eredmény közvetlen versenyköre a teljes azonos
`required_item_id` demand/supply halmaz, minden locationnel és batch-csel;
case-, warehouse-, dátumnap- vagy egyetlen requirement guard túl szűk.
Az egy itemre szűkített olvasó ekvivalenciája még nem `TEST_CONFIRMED`;
a jelenlegi kód globális lekérdezése nem lesz automatikusan szűkített.

| Üzleti adat                                                                                                     | Befolyásolt feltétel                                                     | Reader                                                                                          | Writer                                                                                                                                                                                                                    | Phantom / scope risk                                                                               | Javasolt guard                                                                                                      |
| --------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------ | ----------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------- |
| ProblemCase type, lifecycle, requirement binding                                                                | Material shortage, OPEN, helyes case                                     | [ProblemCaseRepository:27–39][CASE], [Resolver:22–27][RESOLVER]                                 | [ProblemCaseRepository:42–65][CASEWRITE] create; további trusted lifecycle writer UNKNOWN                                                                                                                                 | Future close/invalidate, rebind; létrehozás önmagában nem Finalize                                 | Case X + run X, binding recheck; ismeretlen lifecycle writer befogadása külön kapu                                  |
| MaterialRequirement teljes azonos itemű halmaz, quantity, date, unit, lineage, deleted_at                       | Demand validity, sorrend, exact nettó mennyiség és evidence              | [NettingRepository:38][NETREAD], [NettingService:30–47,66–120][NETCALC], [Eligibility:20][ELIG] | [MaterialRequirementService:31–88][MRSVC] → [MR repository:62–88][MRWRITE]; inherited CRUD [ABSR]; seeder [E2E:427,462][E2E]                                                                                              | Új/restore/átsorolt korábbi igény elviszi a supplyt; updateOrCreate hiányzó sora                   | `I(item)` S Finalize, X minden MR writer; régi+új item; membership parent gate                                      |
| CustomerOrder és CustomerOrderItem status, delete, FK                                                           | Minden hozzájuk kötött competing requirement eligible lehet vagy kieshet | [Eligibility:22–51][ELIG], [Case repository:35–36][CASE]                                        | [CustomerOrderController:87–163][COCTRL] → [CustomerOrderService:64–178][COSVC] → [CustomerOrderRepository:62–108][COWRITE]; inherited delete                                                                             | Parent cancellation több itemet érint; items delete/create új identitásokat ad                     | A parent teljes érintett MR-item halmazának `I` X; tagságstabilizálás és recheck, soft/hard delete is               |
| ProductionOrder status, customer item, BOM ID, delete                                                           | Valid production lineage, nem terminális production                      | [Eligibility:24,33–34,68–78][ELIG]                                                              | [ProductionPlanService:146–172][PPSVC] → [ProductionPlanRepository:116–139][PPWRITE]; [ProductionTaskService:137,171,255–276][TASK]; [QualityCheckService:39–52][QUALITY] közvetve; [OrderProductionSeeder:98][ORDERSEED] | Befejezés minden komponensigényt kizárhat; új production parent/lineage                            | Érintett komponens-itemek `I` X és parent membership gate; nem csak gyártott termék item                            |
| BOM Item ID, BOM ID és required item egyezés                                                                    | Lineage trustworthy/contradictory/missing                                | [Eligibility:63–71][ELIG], [NettingRepository:35][NETREAD]                                      | [BomAdminService:32–76][BOMSVC] → [BomRepository:19–35][BOMWRITE]; [ProductionStructureSeeder:60][BOMSEED]                                                                                                                | BOM children delete/create; item scope váltás és régi hivatkozás eltűnése                          | Régi+új komponens-itemek `I` X, BOM membership gate; teljes fanout leltár                                           |
| BOM quantity és ProductionPlan/Item dátumok, ProductionOrder quantity/start date                                | Közvetett: MR újraszámítás quantity/date/unit értéke                     | [MaterialRequirementService:33–40,59,84–89][MRSVC]                                              | [ProductionPlanService:91–121][PPSVC], [ProductionPlanRepository:74–85][PPWRITE], BOM writer fent                                                                                                                         | Nem a resolver közvetlen bemenete; későbbi MR-write változtat eredményt                            | MR-persistálásnál kötelező `I` X; upstream writer csak ha közvetlen olvasást vagy MR-rebuildet is végez             |
| Item sor, base unit, soft delete                                                                                | Incoming unit-egyezés; material calculation active item gate             | [NettingRepository:105–121][NETSUPPLY], [MaterialRequirementService:53][MRSVC]                  | [ItemAdminService:13,46–75][ITEMSVC] → [Abstract service:69–100][ABSS]/[Abstract repository:90–104][ABSR]; [ItemsSeeder:66][ITEMSEED]                                                                                     | Unit változása több PO-itemet érint; hiányzó item join kiesik                                      | `I(item)` X item módosításkor/törléskor; nincs új unit-conversion policy                                            |
| StockBalance pozitív fizikai quantity, item                                                                     | Használható on-hand pool                                                 | [NettingRepository:64–69][NETSTOCK]                                                             | [GoodsReceiptService:84–149][GR] → [StockBalanceRepository:73–84][BALWRITE]; [ProductionTaskMaterialService:28–79][CONSUME]; [InventorySeeder:25][INVSEED]                                                                | Új balance/0→pozitív, bármely location/batch; supply növekedés megszüntetheti a hiányt             | `I(item)` X a teljes stock-movement tranzakció előtt; Finalize S; traceability változatlan                          |
| StockReservation active status, pozitív quantity, item, delete                                                  | Item összes aktív reservationje levonódik                                | [NettingRepository:71–89][NETSTOCK]                                                             | [StockReservationService:33–113][RESSVC] → [ReservationRepository:79–90][RESWRITE]; [ProductionTaskMaterialService:67–81][CONSUME]; customer/production parent hard delete cascade [reservation migration:20–21][RESMIG]  | Beszúrás, release/consume/restore és cascade bármely locationön                                    | `I(old/new item)` X a teljes műveletben; parent cascade minden érintett itemre                                      |
| PurchaseOrder status, delivery date, deleted_at; PurchaseOrderItem item/status/unit/ordered/received/deleted_at | Firm incoming, cutoff és maradék quantity                                | [NettingRepository:99–143][NETSUPPLY]                                                           | [PurchaseOrderService:38–139][POSVC]; [GoodsReceiptService:154–187][GRUPDATE]; [PurchaseRequisitionService:198–298][PRPO]; [ProcurementSeeder:86][PROCSEED]                                                               | Draft→Ordered belépés, új sor, dátum/unit/status váltás; header több itemre hat                    | Minden érintett PO-item `I` X, old+new scope, PO membership gate; multi-item lock sorrend                           |
| GoodsReceipt és GoodsReceiptItem                                                                                | Közvetett: stock növekedés + incoming received quantity/status változás  | A netting nem GR-t olvas; [GoodsReceiptService:96–139][GR] az átvezetés                         | [GoodsReceiptController:74–93][GRCTRL] → [GoodsReceiptService:46–149][GR]                                                                                                                                                 | Külön tranzakciós oldalak double countot vagy hiányzó supplyt okoznának                            | Egy stock/PO/receipt tranzakció, teljes érintett itemhalmaz `I` X; puszta GR draft csak későbbi input               |
| ProductionTask, ItemInstance, QualityCheck és operation lineage                                                 | Közvetett: ProductionOrder befejezés, material consumption               | [TaskService:224–276][TASK], [QualityService:39–52][QUALITY], [MaterialService:28][CONSUME]     | [TaskService:154–205][TASK], Quality, consumption                                                                                                                                                                         | A task nem közvetlen netting input; státusz továbbgyűrűzik a production parentre                   | Csak a transzitív MR/stock/parent hatásnál guard; önálló task-lock nem elegendő                                     |
| PurchaseRequisition, SupplyProposal, Peg, Supplier, location/batch metadata, capacity                           | Nem közvetlen authoritative netting input a vizsgált útvonalon           | [NettingRepository:58–143][NETSTOCK], [NettingService:26–122][NETCALC]                          | PR→PO [PRPO]; [SchedulingService:31–63][SCHEDULE]; peg/source kapcsolatok                                                                                                                                                 | Új input bevezetése scope-bővítés; location/batch FK-null változás önmagában nem quantity-változás | Nincs indokolatlan teljesmodul-zár; PO/stock/MR/cascade hatás guardolása; egyéb hatás UNKNOWN audit szükség szerint |
| Soft-delete globális scope-ok, FK cascade, bulk/raw write, seed/import                                          | Sorkiesés/újra-belépés, parent hiány                                     | [MR model:80–99][MRMODEL], [Case repo:34–39][CASE], [netting:120–121][NETSUPPLY]                | [Customer item migration:17][COIMIG], [BOM item migration:16][BOMMIG], [reservation migration][RESMIG]; seederek fent; külső írás UNKNOWN                                                                                 | Model observer nem fog minden bulk/cascade/raw utat; seeder updateOrCreate/upsert                  | Teljes writer-enrollment és operatív tiltás/maintenance kapu; nincs igazolt lefedettség → BLOCKED                   |

`SOURCE_CONFIRMED`: a case selected-forrását `withTrashed` olvasások is
vizsgálják, míg a netting competing source relationjei normál model-scope-ot
használnak. Ezért kiválasztott case source invalidity és competing eligibility
nem helyettesíthető egyetlen cached `current_evaluation` mezővel.

`SOURCE_CONFIRMED`: a stock pool minden aktív reservationt item szerint
összegez; a PO incoming SQL a `items` táblához nyers joinnal kapcsolódik.
Az item `is_active` flagje és az `items.deleted_at` nem szerepel ebben az
incoming predicate-ben. Nem vezetünk be észrevétlenül új eligibility-szűrőt.
Külön mértékegység-törzstábla vagy konverziós service nincs a vizsgált
resolver útvonalán; unit stringként szerepel. Új konverziós policy **OPEN**,
e feladatban nem javasolt üzletilogika-módosítás.

### 4.2. Seed/bulk kiegészítés és teljességi korlát

`SOURCE_CONFIRMED`: az [ItemMasterDataSeeder:17][ITEMMASTERSEED] és
[E2ETestSeeder:135–238][E2EITEM] item/balance állapotot ír; az
[OrderProductionSeeder:47–98][ORDERSEEDALL] customer/plan/production parenteket,
a [ProcurementSeeder:46–112][PROCSEEDALL] PO/PO-item/GR állapotot készít.
Az [E2E cleanup:543–591][E2ECLEAN] bulk role/permission/item/balance törlési
útvonalakat tartalmaz; fixture PO/GR írás [606–680][E2EFIXTURE] alatt is van.
A [ProductionExecutionSeeder:68–103][EXECSEED] task/material/quality rekordokat
ír közvetlenül; e közvetlen seedírás önmagában nem azonos a production service
stock movementjével vagy a quality workflow automatikus lefutásával.
Ezek nem futottak. A későbbi bevezetési tervnek vagy igazoltan izolált,
leállított lifecycle mellett kell engednie őket, vagy ugyanabba a writer
guard-szerződésbe kell bevonnia a tényleges authoritative hatásukat.

A generic repository örökölt CRUD-metódusai létező írási képességek;
minden konkrét modellre a teljes hívólefedettség **UNKNOWN**, nem állítjuk,
hogy valamennyihez létezik aktív HTTP-módosító végpont. A keresésben nem
azonosított import/cron/job writer hiánya nem a lehetetlenségének bizonyítéka.
Tesztfactory és kézi SQL éles adatbázison történő használatának kizárása
üzemeltetési/privilégium kapu, nem model observerből következő garancia.

## 5. MySQL és SQLite tranzakciós modell

### 5.1. Jelenlegi infrastruktúra — SOURCE_CONFIRMED

Az [admission wrapper:27–85][TX] elutasítja a már nyitott caller-tranzakciót,
külön connection/PDO-t készít, saját transaction managert állít be,
tiltja a reconnectet és `transaction(..., 1)`-et használ. MySQL-en a policy
`REPEATABLE READ, READ WRITE`; SQLite-on file-backed `IMMEDIATE`, FK=1 és
read_uncommitted=0 szükséges. A globális SQLite default
[database config:44][DBCONFIG] szerint DEFERRED marad.

Az [állapotguard:21–25][TXSTATE] PDO-azonosságot, egyetlen Laravel transaction
szintet és PDO `inTransaction()`-t ellenőriz. [Context:43–63][CTX] explicit
connectionnevet visz a resolver/repository és fresh actor útvonalra.
Az [admission audit writer:13–22][AUDIT] az adott connectionön ír szinkron
admission auditot. Ez nem a még nem létező lifecycle receipt implementációja.

A [Laravel ManagesTransactions:26–74][LTX] callback után PDO commitot hív;
a [232–250][LTXERROR] commit exception kezelése nem bizonyítja az eredeti
tranzakció kimenetét. [Start service:73–76][STARTUNKNOWN] a callback befejezése
utáni hibát konzervatívan uncertainnek minősíti. A wrapper `finally` cleanupja,
purge és az eredeti PDO rollback-kísérlete nem unknown-COMMIT bizonyíték.
A [transaction manager:67][LTXMAN] after-commit callback-kezelő, nem DB-lock
vagy permission-serialization mechanizmus.

### 5.2. Motorhatárok — ENGINE_DOCUMENTED

| Terület                      | MySQL 8.4 / InnoDB                                                                                         | SQLite                                                                                     |
| ---------------------------- | ---------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------ |
| Olvasás                      | RR snapshot nem védi a writerektől a kiolvasott tényeket; locking read friss committed sorra vár           | Read snapshot önmagában nem tilt konkurens írást                                           |
| S/X védelem                  | `FOR SHARE` / `FOR UPDATE` tranzakciós row/index lock                                                      | Nincs MySQL-szerű row-level `FOR UPDATE`; IMMEDIATE előzetes writer intent                 |
| Phantom                      | RR megfelelő indexelt range/next-key lockkal védhet; pontos terv szükséges                                 | Ugyanazon DB-fájlban egyszerre egy write transaction; nincs külön sor/range-lock stratégia |
| Hiba                         | Deadlock egész tranzakciót rollbackol; lock timeout alapértelmezésben csak statementet                     | BUSY lehet kezdéskor és COMMIT-kor is; COMMIT BUSY mellett a tranzakció aktív maradhat     |
| Új javasolt lifecycle policy | C esetén explicit READ COMMITTED + minden releváns writerre kiterjedő guard; A esetén RR locking-read terv | Saját file-backed IMMEDIATE a legelső domain read előtt, ugyanaz a logikai guard-fegyelem  |

Források: [MySQL locking reads][MYSQLLOCK], [isolation][MYSQLISO],
[phantom/next-key][MYSQLPHANTOM], [hibakezelés][MYSQLERROR],
[SQLite transaction][SQLITETX]. Az engine-leírásból nem következik helyi
`TEST_CONFIRMED` garancia. A C READ COMMITTED javaslata eltér az admission RR
policyjától; csak saját lifecycle connectionön, külön elfogadással értendő.

`UNKNOWN`: helyi MySQL verzió, InnoDB tényleges táblabeállítások, indextervek,
read/write routing és deployment override-ok; SQLite journal/driver verzió és
valós lock/busy viselkedés. A [DB-konfiguráció][DBCONFIG] olvasása nem ezek
runtime bizonyítása. Több DB-fájl/külön szerver atomikus védelmét nem állítjuk.

## 6. A–D alternatívák összehasonlítása

Minden alternatíva **PROPOSED / INFERRED**; egyik sem TC-02 PASS.

| Szempont           | A — teljes locking-read                                                                          | B — közös guard/version                                                            | C — hibrid                                                                                                  | D — snapshot/as-of advisory                                          |
| ------------------ | ------------------------------------------------------------------------------------------------ | ---------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------- |
| Alapelv            | Minden predicate teljes sor- és hiány/range halmazának közvetlen zárolása                        | Tartós, scope-hoz kötött guard és tranzakciós version; minden writer együttműködik | Célzott row-lock a run/case/identityn, scope-guard/version az auth és item graphon, cache-független olvasás | Eredmény egy múltbeli megfigyeléshez kötött                          |
| COMMIT-ig védelem  | Lehetséges megfelelő predicate-lock fedéssel; itt nem bizonyított                                | Csak minden writer guardolása és a finalizer lockjának COMMIT-ig tartása mellett   | Ugyanaz a feltételes garancia, kevesebb közvetlen child/range lock                                          | **Nem teljesíti** a jelenlegi üzleti védelmet                        |
| Hiány/phantom      | Minden indexelt hiány/range külön; pivot, soft-delete, join és scope-beléptetés is               | Üres halmazhoz is létező stabil guard; insert előtt X                              | Ugyanez; selected és competing membership stabilitásának külön terve                                        | Nem akadályozza a konkurens változást                                |
| Version veszély    | N/A                                                                                              | Végső version-összehasonlítás lock nélkül után is van rés COMMIT-ig                | Version csak S/X guard mellett bizonyít; cache generation nem helyettesíti                                  | Snapshotverzió csak provenance                                       |
| Writer változtatás | Kevesebb explicit együttműködés lehet, de minden cascade/predicate és valódi SQL-lock feltárandó | Nagy keresztmodulos enrollment és új tárolás                                       | Writer-enrollment továbbra kötelező; célzott item-scope és auth graph                                       | Kevesebb blokkolás, eltérő termékgarancia                            |
| Deadlock/terhelés  | Nagy join/range halmaz; RR broad scan gyakorlatilag táblaszintű hatást okozhat                   | Item/actor/role szintű contention, multi-item writer szélesedhet                   | Runonként szűk guardhalmaz; széles szülőműveletek költsége marad                                            | Kisebb zárolás, stale eredmény megengedésével                        |
| MySQL / SQLite     | Index- és query-plan függő InnoDB; SQLite külön IMMEDIATE                                        | InnoDB guard row S/X; SQLite natív single-writer                                   | Külön adapter, közös invariánsok                                                                            | Mindkettőn megfigyelhető snapshot, de nem azonos authority           |
| Bevezetési ár      | Kétmotoros teljes SQL/read átírás, indexaudit és intenzív locktesztek                            | Guard DDL, backfill/bootstrap, minden writer, version túlcsordulás/policy          | Ugyanez célzott leltárral; nem bizonyított, hogy összességében kisebb munka                                 | Új üzleti/authorization határdöntés, provenance és UI/read szerződés |
| Státusz            | BLOCKED: teljes predicate- és motorbizonyíték hiányzik                                           | BLOCKED: minden writer részvétele nem igazolt                                      | **További tervezésre ajánlott; BLOCKED** a bevezetési kapukig                                               | Új felhasználói döntés nélkül nem választható                        |

A esetén a [meglévő MR-index][MRINDEX], [reservation index][RESMIG],
[PO-item index][POIMIG] és [permission index][PMIG] csak kiindulás.
A `SUM/GROUP BY`, join, eager-load és nullable scope megfigyelésének zárolását
nem szabad egyetlen `lockForUpdate()`-ból levezetni. Alap rows/pivotok/range-ek
külön olvasási terve és kontrollált EXPLAIN/lock-bizonyíték kell később.
RC-ben az A hiány/range garanciáját nem feltételezzük.

D alkalmazásához külön **OPEN felhasználói döntés** kellene arról, hogy a
COMMIT-ig érvényes current business gate helyett elegendő a rögzített korábbi
állapot. E döntésnek rendeznie kellene az authorization aktuális határát,
stale eredmény olvasását, provenance-t és revocation utáni disclosure-t is.
Ez nem az elfogadott TC-02 / 1. időszemantika értelmezése vagy jelen ajánlás.

## 7. PROPOSED — a C guard-protokoll szerződésvázlata

### 7.1. Guardazonosság, módok és tárolás

Tartós guardkulcsok, backend-owned, determinisztikus rendezéssel:

| Jel | Stabil scope                                                                                  | FinalizeRun                                                 | Scope-ot befolyásoló writer                                                |
| --- | --------------------------------------------------------------------------------------------- | ----------------------------------------------------------- | -------------------------------------------------------------------------- |
| `P` | `(web, inventory.view)` és `(web, item-suppliers.view)` névtér                                | S                                                           | Permission catalog rename/delete/create: X a régi/új névre                 |
| `A` | Historical/live actor ID                                                                      | S                                                           | User delete/identity és direct permission vagy role membership változás: X |
| `R` | Az actorhoz A alatt frissen tartozó összes Role ID                                            | S                                                           | Role permission/name/guard/delete: X; nem minden user külön lockja         |
| `I` | Required Item ID, teljes item-szintű demand/supply pool                                       | S                                                           | Bármely érintett demand/supply/lineage/base-unit writer: X                 |
| `M` | Parent membership: customer order/item, production order, BOM, PO, receipt stabil ID és típus | Csak szükséges scope-validálásnál S, a 9. rész sorrendjében | Parent hatás fanoutjának és child scope-beléptetésnek stabilizálása        |

**OPEN:** a guard tárolás neve/DDL-je, numeric version típusa és határa,
FK/tombstone/retention, bootstrap és migráció. Ez külön közös infrastruktúra,
nem harmadik lifecycle operation ledger. A két lifecycle tábla mellé új
guard tábla csak külön explicit scope-döntéssel kerülhetne.
Alternatíva az existing User/Role/Item parent row mint guard anchor; ezzel
elkerülhető lehet új tábla, de a névtér-hiány, törlés és version tárolás
külön bizonyítása szükséges. Ez sem automatikusan elfogadott C-változat.

A guardot nem lehet opportunista módon csak FinalizeRun közben létrehozni.
Objektum/névtér létrehozása előtt vagy ugyanazon tranzakcióban, láthatóság előtt
biztosított, unique és tartós anchor szükséges. Hiányzó guardnál nincs pozitív
finalization. Entity törlése nem törölheti újrahasználhatóvá a guard identityt.
Bootstrapkor a régi writerek kizárása és a teljes guardhalmaz létrejötte
bizonyítandó; nincs automatikus production backfill e feladatban.

### 7.2. Writer szerződés és version

Minden releváns writer a business/pivot módosítás **előtt** szerzi meg a
teljes érintett guardhalmaz X módját ugyanazon DB-tranzakcióban, és a végéig
tartja. Módosítás és az érintett version növelése közösen commitol vagy
rollbackol. Ugyanazon scope több belső változása legalább egy verziónövelést
igényel; a pontos növelési policy OPEN. Nincs időbélyeg-alapú version és
nincs reset/újrafelhasználás; overflow fail-closed.

Item-, role-, permission- vagy parent scope-váltásnál a régi és új scope-ok
uniója kötelező. Írás után végzett invalidation/version bump túl késő.
Nem elég observer: bulk update, pivot detach, cascade, seeder és import is
része a writer-szerződésnek. Modell eventtel nem fedett útvonal külön belépési
korlátot vagy célzott DB-enforcementet igényel; megoldása **OPEN / BLOCKED**.

A FinalizeRun a guard versiont S lock alatt olvassa és COMMIT-ig tartja a
zárat. Version újraellenőrzés önmagában nem védi a COMMIT előtti rést.
Előzetes snapshot/digest csak routing hint vagy diagnosztika; authority csak
az összes szükséges guard megszerzése utáni friss readből keletkezik.

### 7.3. Feltételes helyességi érv és aktiválási kapu

`INFERRED`: ha minden pozitív predicate változtatása valamely tartott S
guarddal ütköző X guardot igényel, a finalizer friss megfigyelése és COMMIT-ja
között ilyen writer nem commitolhat. A run X lock az egymással versengő
terminális commandokat sorosítja. Ez **feltételes érv**, nem teljes rendszerbizonyítás.

Jelenleg nincs guard implementáció vagy teljes writer-enrollment bizonyíték.
**PROPOSED követelmény csak feltárt és igazoltan bevont writerre támaszkodhat;
egyetlen releváns UNKNOWN útvonal mellett nincs pozitív engedélyezés.**
A teljes scope lezárása, kétmotoros tesztek és jóváhagyott bevezetési kapu
előtt a protokoll nem tekinthető implementációra késznek.

## 8. PROPOSED — a linearization point pontos definíciója

Javasolt döntési esemény `LP`: az owned DB-connectionön végrehajtott,
külön, nem blokkoló DB-óra SELECT értékének kiértékelése, amikor már minden
szükséges auth/business guard és case/run/execution lock aktív,
az összes friss predicate és a validált result bizonyított, és
`t < execution_deadline_epoch`. A terminális siker csak e tranzakció sikeres
COMMIT-jával válik tartós ténnyé; abortált tranzakciónak nincs sikeres LP-je.

Javasolt sorrend:

1. Trusted command identity, run/case/actor routing hint, titkos claim proof és result elővalidáció; nincs külső hívás. Előzetes adat nem authority.
2. Caller-tranzakció elutasítása; saját primary connection és azonos PDO, egyetlen próbálkozás, reconnect/replay nélkül.
3. Guardok a 9. rész szerinti sorrendben. MySQL C: saját READ COMMITTED/READ WRITE; SQLite IMMEDIATE már az első domain read előtt.
4. Case/run/execution X; hint teljes újraellenőrzése. Fresh auth után receipt lookup. Azonos command replay külön read outcome, nem új LP vagy végrehajtási jog.
5. Új műveletnél RUNNING, owner/claim, immutable deadline, expected version/sequence, bound OPEN case és minden szükséges validity/current netting predicate; friss, cache-független olvasás.
6. A receipt identity/fingerprint és az audit envelope előkészítése memóriában; az esetleg szükséges további domain/guard lock megszerzése még az LP előtt. Nincs előre publikált vagy más connectionön mentett success receipt.
7. **LP:** külön `SELECT` azonos PDO-n, minden korábbi lock-wait után: MySQL-javaslat `TIMESTAMPDIFF(SECOND, '1970-01-01 00:00:00', UTC_TIMESTAMP())`; SQLite-javaslat `unixepoch('now')`. Integer UTC epoch validálás; `t < deadline`, egyenlőség vagy későbbi idő → teljes rollback, nincs success receipt.
8. Feltételes terminal update: várt RUNNING + claim + deadline identity + sequence/version; pontosan egy érintett run és várt execution update; egy audit/receipt insert LP-idővel, ugyanazon tranzakcióban. Bármely eltérés teljes rollback. Nincs új domain read, domain/guard lockhalmaz-bővítés, tetszőleges alkalmazáscallback vagy AI/business hívás az LP után. A felsorolt saját DML-ek szükségszerű row/index/unique lockjai megengedettek; várakozásuk alatt a már megszerzett auth/business guardok továbbra aktívak.
9. Guardok és ownership COMMIT-ig megmaradnak. Csak sikeres commit-ack után APPLIED; COMMIT-ack hiba a 12. rész szerint uncertain. COMMIT deadline utáni időpontja megengedett.

**OPEN:** az LP és a conditional write közötti konkrét adapterhatár, az
event insert pontos sorrendje/sequence constraintje és a guard fingerprint
receiptbe kötése. A fenti LP-javaslat nem azonos „az UPDATE vagy az utolsó SQL
= COMMIT” állítással. A run X miatt Cancel/Recover nem kerülhet közé;
az auth/business S guardok miatt a védett tények sem változhatnak.
Ha később mégis új domain/guard lock vagy predicate kell, az LP-t el kell vetni és a teljes
tranzakciót leállítani; nincs régi idővel végzett új döntés.

`ENGINE_DOCUMENTED`: a [MySQL időfüggvények][MYSQLTIME] query-kezdeti időt
használnak; a [SQLite `now`][SQLITETIME] egy sqlite3_step alatt azonos értékű.
Ezért kell a korábbi blokkolások után külön óra-statement. Az óraolvasás
végrehajtási időpontja a javasolt időhatár, nem az alkalmazás későbbi wall clockja.

**PROPOSED clock policy:** `t < claimed_at_epoch` vagy korábbi tartós
event-idő → completion tiltás, technikai clock-policy hiba. Ez csak egyes
visszalépéseket detektál; a még nagyobbnak látszó, de visszaállított DB-idő
nem észlelhető pusztán e két értékből. **OPEN / BLOCKED:** óra-health,
rollback incidens, kétmotoros adapter/precision, operatív fail-closed policy.
Nincs DB-óra monotonicitásra vagy fizikai 180 s elapsed időre új bizonyíték.

## 9. PROPOSED — lock acquisition order, scope discovery és hibák

Globális logikai rang: `P` név/guard rendezve → `A` actor ID rendezve →
`R` role ID rendezve → `I` item ID rendezve → `M` parent típus/ID rendezve →
case → run → execution → receipt/event. Több scope esetén minden rangon
kanonikus sorrend; módok előre meghatározva, S→X upgrade tilos.
A writer csak a szükséges rangokat használja, de visszafelé nem kérhet új zárat.
Az A alatt felfedezett rolehalmaz stabil: user-role writer A X-et igényel;
role-delete/rename és permission writer R/P guardját külön is fel kell venni.

Az item és szülőfanout előzetes felderítése nem authority. Szülőheader writer
(CustomerOrder cancellation, PO header status/date, BOM update) terve:

1. Preflightból jelölt régi/új itemhalmaz, csak hint; minden jelölt I X rendezve.
2. M parent X és a szükséges tényleges parent row lock; a child-scope writer ugyanennek az M-nek legalább S módját is igényli, I után.
3. Friss current readből újra felsorolni minden érintett child/MR/reservation/supply itemet, soft/hard-delete hatásokkal és mindkét régi/új scope-pal.
4. Ha bármely szükséges item hiányzik a tartott guardhalmazból: **teljes rollback**, új önálló felderítés lehetséges később. M tartása közben tilos visszafelé I-t kérni.
5. Csak teljes, stabil fanout mellett módosítás és version bump; új child ugyanazon M mellett már nem léphet be.

Ez külön bizonyítandó fanout-protokoll, nem a jelenlegi repository viselkedése.
Az összetett parent láncok M-halmazának lezárása és a BOM/production/customer
cross-linkek felderítése **UNKNOWN / BLOCKED**. Nem oldható meg vakon minden
item globális zárolásával. Üres childhalmazhoz is M kell; különben egy új
child kimarad a header writer I-halmazából.

Az [admission:78–80][TX] ma case-first. A fenti I/auth-before-case sorrend
ezzel eltérhet; admission és minden más case-t majd más domain sort lockoló
útvonal teljes inversion-auditja szükséges. Nem módosítjuk az admissiont
e dokumentumban, és nem állítjuk, hogy az új sorrend már kompatibilis.
A [GoodsReceipt:87–101][GR] receipt→PO-item sorrendje és a
[task generation:95–137][TASK] production-first sorrendje konkrét átrendezési pont.

**Hibák:** lock timeout, deadlock, busy, guardhiány, stale hint vagy nulla
affected row nem siker. Egy teljes rollback-kísérlet és az eredeti PDO/tx
kimenetének osztályozása kell. MySQL timeout után a teljes tranzakciót le
kell zárni, nem csak a hibás statementet elengedni. Sikertelen rollback,
kapcsolatvesztés vagy bizonytalan commit → unresolved/unknown. Nincs rejtett
Laravel transaction retry; a deadlock teljes megelőzését a sorrend sem garantálja.
SQLite COMMIT BUSY aktív tranzakciója sem APPLIED: jelen javaslatban nincs
vak wrapper retry, hanem kontrollált outcome-kezelés és igazolt cleanup kell.

## 10. PROPOSED — cache-független authorization

**Válasz az 1–3. kérdésre:** lehetséges új, belső, owned-connectionön
végzett relációs evaluator tervezése a jelenlegi User/Spatie séma mellett.
Ez **INFERRED**, még nincs implementálva vagy parity-teszttel igazolva.
A meglévő `Gate::allows(string)` és puszta cache flush nem elégséges bizonyíték.

Javasolt formula a rögzített V1 policyhoz:

`live_actor && same_initiator && (super_admin_role || (inventory.view && item-suppliers.view))`

Az egyes permissionök külön-külön teljesülhetnek direct vagy hozzárendelt
role útvonalon, nem kötelező egy role-ban lenniük. Az evaluator ugyanazon
PDO-n, guardok alatt olvassa `users`, `permissions`, `roles`,
`model_has_permissions`, `model_has_roles`, `role_has_permissions` sorait;
pontos `web` guard, User morph class és konfigurált pivot kulcsok szükségesek.
Nincs registrar, globális Gate-before, cached model relation vagy default
connectionre visszaeső `Permission::findByName` a pozitív döntésben.
Ismeretlen policy-hook, teams/wildcard változás vagy eltérő guard/morph
konfiguráció esetén fail-closed, külön szerződésfrissítés.

P S a két ability catalog névterét, A S a live actort és teljes tagságát,
R S az összes hozzárendelt role permission/name/delete hatását védi.
Direct revoke A X-en, role revoke R X-en vár; permission rename/delete P X-en
vár. Ezeket a writereknek **detach előtt** kell kérniük. A super-admin role
törlése/átnevezése és membership revoke ugyanígy védendő, nincs authority
a worker tokenből, historical identityből vagy receiptből.

A két permission definíció hiánya normál ágon denial/infra-blocker, nem
cache-ből kikövetkeztetett grant. Super-admin permission-kivételének
parityje és catalog-hiány melletti pontos technikai kimenet **OPEN**, a
hiányzó guard minden ágban blokkolja a pozitív finalizationt.

További azonosított auth-readerek, `SOURCE_CONFIRMED`: a
[supplier tool:134–143][TOOLAUTH] a [UserRepository:13–15][TOOLUSER]
friss eager-loadját használja, majd ugyanazt a két Gate permissiont kéri;
a [HandleInertiaRequests:42–47][INERTIAAUTH] permission/role-listát ad a UI-nak.
Ezek nem FinalizeRun guardok, a menü/lista nem execution authority.
Az összes általános policy és külső extension pozitív decision-útjának
parityje nincs e célzott lifecycle auditban teljes körűen bizonyítva: UNKNOWN.

`INFERRED`: Role/Permission model eventtel történő cache invalidálás még
tranzakción belül futhat; egy másik worker régi committed gráfot tölthet
vissza. Ezért a cache flush nem COMMIT-ig védő protokoll. A
[BusinessCacheInvalidator:123–141][BCACHE] after-commit üzleti generationje
is cache célú, nem az authorization graph vagy domain writer guardja.
Az általános Gate/cache működés megváltoztatása külön scope; az új belső
evaluator nem írhat át észrevétlenül más felületek jogosultsági szemantikáját.

## 11. PROPOSED — business scope és phantom-védelem

**Válasz a 4–7. kérdésre:** a legkisebb jelen forrásból indokolható stabil
pool-scope **Item ID**, minden azonos itemű potenciálisan eligible demanddel,
on-handdal, aktív reservationnel és firm incominggal. A kiválasztott requirement
előtti versenyzők minimális prefixe adatfüggő; új korábbi dátumú beszúrás
miatt önmagában nem stabil guard-scope. Unit/dátum/location szerinti
szűkítéshez új bizonyítás kell; itemenként eltérő poolok izolációja INFERRED.

Finalize I S-t tart. Új MR, balance, reservation vagy PO-item insert, restore,
soft-delete, status/date/quantity/unit és item-scope váltás I X-et igényel.
Így az üres aktuális eredményhalmaz is védhető stabil guarddal, nem kell
minden lehetséges hiányzó child sort egyesével létezőnek tekinteni.
Header és lineage writer minden érintett itemre köteles, M recheckkel.

**Nincs jelenlegi teljesítési állítás:** a mostani netting reader globális
`requirements()` lekérdezése és kapcsolatai nem guardolt lifecycle reader.
Javasolt célzott repository-út azonos item összes versengő igényével, változatlan
eligibility/sorrend/decimal/supply policyval; ezt a normál teljes netting
kiválasztott eredményéhez ekvivalenciatesztnek kell kötnie. Új üzleti szűrő
nem vezethető be e technikai optimalizálás részeként.

Szükséges writer-csoportok a 4. mátrixban szerepelnek. Különösen nagy változás
a CustomerOrder/ProductionOrder/BOM/PO header fanout, a GoodsReceipt összetett
stock+incoming tranzakciója, valamint a generic repository, bulk write és
seed/import útvonal. Ezek arányossága mérés és teljes callgraph nélkül
**UNKNOWN**, de nem helyettesíthetők kihagyott guarddal. Ha bevonásuk nem
vállalható, a C nem választható; A részletes bizonyítása vagy D új termékdöntése
marad. Nincs bizonyított „kis Merlin-only javítás”.

## 12. PROPOSED — audit, receipt, unknown COMMIT és retry

**Válasz a 12–13. kérdésre:** egy command identity/fingerprint, conditional
state transition és egy kötelező atomi lifecycle event/receipt; a tárolási
részletek a régi Technical Contracttal összehangolandó javaslatok.
LP siker után event/receipt írási hiba → teljes rollback, nem névleges
COMPLETED vagy külön audit nélküli FAILED. Guardok csak a tranzakció lezárásával
engedhetők el. Nincs másik connectionön előre mentett success audit.

Unknown COMMIT protokoll:

1. Nincs APPLIED/rollback állítás, nincs új Finalize más command ID-val, nincs új provider/tool/business művelet.
2. Eredeti run/command/fingerprint és titokmentes correlation megőrzése. Claim token/hash nem auditadat.
3. Külön, frissen autorizált reconciliation a writer primaryn; az eredeti tranzakció lezárásának és a run lockjának rendezése, nem read replica vagy puszta cache lookup.
4. Egyező unique receipt és koherens state/result/event lánc → eredeti commit feloldható. Jogosulatlan caller nem kap advice-ot vagy új execution jogot.
5. Nincs receipt → még nem bizonyított rollback. Aktív eredeti tx, elérhetetlen primary, identity/chain eltérés → unresolved, kontrollált operatív eljárás, további írás nélkül.
6. Csak bizonyított teljes rollback és rendezett eredeti transaction után tervezhető azonos command/fingerprint kontrollált újrapróbálása, új auth/business/deadline döntéssel. Paid/externális call ebbe nem kerülhet.

A FinalizeRun nem hívhat AI-t; a tranzakció callbackjének újrafuttatása nem
futtathatja újra a vizsgálatot. Paid execution továbbra tiltott; későbbi
call identity, attempt accounting és unknown provider outcome külön szerződés.
`SOURCE_CONFIRMED`: [MerlinRun:45–59][RUNMODEL] model guardja védi a terminal
update-et Eloquent úton; ez nem raw SQL/DB immutable enforcement.
Az [admission ack-loss teszt:413][ACKTEST] forrása létezik, de itt nem futott,
és nem a lifecycle valódi mid-COMMIT hálózati hibatesztje.

## 13. PROPOSED — versenyhelyzetek és ellenpéldák

Jelölés: F=FinalizeRun, W=konkurens writer, LP=a 8. rész döntési pontja,
C=COMMIT. Mindegyik teszt **terv**, eredménye `UNKNOWN`, nem `TEST_CONFIRMED`.
Mindkét sorrendet barrierrel kell kényszeríteni: W előbb commitol, illetve
F már tartja a guardot. Sleep önmagában nem igazol interleavinget.

| Verseny                                      | Veszélyes idővonal guard nélkül és következmény                                                      | Guardolt javasolt idővonal                                                                                     | Ellenőrizhető PASS / FAIL                                                                                                                                        |
| -------------------------------------------- | ---------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| F vs direct revoke                           | F grantet olvas → W detach+C → F LP+C; jogosulatlan completion                                       | F A S → W A X vár → F C → W C; fordított sorrendben F friss denial                                             | PASS: W nem commitol F S és C között; W-first esetben nincs finalization receipt. FAIL: revoke commitja megelőzi F LP-t, mégis grant                             |
| F vs role-permission revoke, meleg cache     | Processz A cache-ében role-grant; W másik processzben revoke+C → F Gate stale true                   | F R S + saját SQL; W R X vár; W-first: új pivot nincs, F deny függetlenül cache-től                            | PASS: előmelegített registrar és User relation mellett is deny W-first. FAIL: cache-től függő pozitív kimenet                                                    |
| F vs user delete                             | F usert olvas → W pivot detach, majd DELETE → F C                                                    | W A X-et még detach előtt kér; F A S; nullable live FK és run binding külön recheck                            | PASS: user-first esetben nulla success; F-first delete vár. FAIL: detach guard előtt végbemegy                                                                   |
| F vs role delete/rename                      | F super-admin/role-grant → W detach/rename+C → F C                                                   | R S vs R X a legelső role/pivot mutation előtt, A alatt stabil roleset                                         | PASS: role-first új graph alapján deny vagy másik igazolt grant; F-first writer vár. FAIL: régi név/pivot dönt                                                   |
| F vs GoodsReceipt post                       | F stockot olvas → W stock+PO received átvezet+C → F régi incominggal számol; double/missing coverage | W minden GR/PO hatás I X-e → atomikus post; F I S előtt/után teljes friss megfigyelés                          | PASS: kizárólag teljes post előtti vagy utáni pool; W nem commitol F guardja alatt. FAIL: stock új + PO régi vegyes input                                        |
| F vs reservation create/release/consume      | F SUM → W insert/release+C → F régi nettóval C                                                       | I S/X üres halmazra is; consume stock+reservation egy tranzakcióban                                            | PASS: insert/release és quantity/status mindkét sorrend; teljes guard-intervallumban nincs W commit. FAIL: új reservation kimarad vagy release után régi levonás |
| F vs competing MR insert                     | F megfigyel → W korábbi required_at/id scope-ba új demandet tesz+C → F elavult allocationnal C       | W I X és parent M tagság; F I S alatt teljes azonos item scope                                                 | PASS: W-first esetben referencia teljes nettinggel egyező kiválasztott eredmény; F-first insert vár. FAIL: egyetlen selected MR lockja mellett W befér           |
| F vs CustomerOrder cancellation              | F selected/competing source valid → W parent cancel+C → F régi eligibilityvel C                      | W teljes fanout I X, M X, recheck; F I S                                                                       | PASS: kiválasztott order cancellationje tilt; competing order cancellationje friss nettinget eredményez. FAIL: csak selected order guardolt                      |
| F vs PO status/date változás                 | F incomingot lát → W close/date change+C → F régi supplyval C                                        | W összes PO-item I X + M, F I S                                                                                | PASS: Draft→Ordered, Ordered→Received, date NULL/cutoff mindkét sorrend; nincs mixed header/item. FAIL: új eligible supply vagy header kiesés kimarad            |
| F vs CancelRun                               | F RUNNING-et olvas → cancel C → F vak terminal update                                                | Közös case/run X; az első érvényes terminal commit után nincs új transition                                    | PASS: egy terminal transition és ahhoz egy receipt; loser NO_CHANGE/reject. FAIL: terminal overwrite vagy két applied terminal command                           |
| F vs RecoverRun                              | F LP deadline előtt → hosszú commitvárás → Recover lejáratot lát és felülírná                        | F run X-et tart C-ig; Recover vár; F C után terminal immutable. Recover-first esetben F reject                 | PASS: LP<deadline, C>deadline mellett egy érvényes siker megmarad; equality LP esetén nincs siker. FAIL: fizikai commit deadline miatt utólagos overwrite        |
| F vs unknown COMMIT                          | F COMMIT elküldve → ack elvész → vak retry új eventet vagy külső hatást dupláz                       | Eredeti identity stop → primary/run-serialized reconciliation → receipt/chain alapján feloldás vagy unresolved | PASS: applied receipt legfeljebb egy, nincs külső replay; no-receipt+aktív tx unresolved. FAIL: no receipt automatikus rollback vagy új paid call                |
| F vs permission catalog rename és üres pivot | F cache-ben ismert név → W rename/delete vagy hiányzó pivot insert+C → F régi névvel enged           | P névtér S/X, A/R S/X; guard jelen van akkor is, ha child nincs                                                | PASS: old/new név guard és scope-váltás tesztelt; hiányzó guard tilt. FAIL: csak meglévő pivot sorok zártak                                                      |
| F vs parent fanout bővülés                   | Header writer hintje üres → új MR más itemre C → header writer kihagyja annak I-jét                  | I hint → M X → current fanout recheck → hiány esetén rollback; child writer I→M S                              | PASS: új item felismerésekor nincs header mutation; nincs késői lockhalmaz-bővítés. FAIL: hiányzó item guarddal parent commit                                    |

Guardhiányt mutató negatív kontroll külön izolált fixture-ben szükséges:
a veszélyes interleaving legyen reprodukálható, majd ugyanaz a schedule
guardoltan blokkoljon vagy friss kimenetet adjon. Ez későbbi, külön
engedélyezett adatot módosító tesztmunka, nem jelen feladat.

## 14. PROPOSED — célzott tesztelési és bizonyítási terv

**Válasz a 8–11. és 14. kérdésre:** a 8–9. rész definiálja az LP-t, sorrendet,
hiba- és deadlockkezelést; az alábbi kapuknak ezek konkrét implementációját
kell bizonyítaniuk. Motorfüggetlen az invariáns és a command outcome;
motorfüggő a lock, hiányvédelem, busy/timeout és clock adapter.

| Kapu                        | Konkrét bizonyítandó feltétel                                                                                                                                                                         | Környezet / bizonyíték                                                                                                               |
| --------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------ |
| AUTH-1 parity               | Direct, role, vegyes kétpermission, super-admin, hiányzó/törölt user, más actor, más guard/morph, permission név/ID váltás; az új evaluator egyezik a rögzített V1 policyval                          | Unit + integráció; cache cold/warm, két független processz; nincs pozitív cache-read SQL trace szerint                               |
| AUTH-2 revocation           | A/R/P writer guard a legelső pivot/sorírás előtt; user/role delete hook, direct/role sync, seeder/bulk/catalog útvonalak                                                                              | MySQL valódi process/connection; SQLite file-backed; 13. rész barrieres idővonalai                                                   |
| BUS-1 ekvivalencia          | Egy item összes demandje vs normál teljes netting kiválasztott eredménye: dated/null dates, ID tie-break, exact 0.000, több location/batch, reservation összeg, same-day incoming, unit/status/delete | Kétmotoros, cache nélküli reference comparison; külön item változása nem változtat selected resultot                                 |
| BUS-2 phantom/fanout        | Üres scope, új korábbi competitor, restore, item/date átsorolás, parent cancel/delete, BOM children újraépítés, PO/GR több item, cascade és null FK                                                   | Régi+új guardhalmaz ellenőrzése, M stabilizálás, hiányos hint rollback; direct raw/bulk útvonal negatív kontroll                     |
| LP-1 időhatár               | `t=deadline-1` sikeres lehet, `t=deadline` és `t=deadline+1` tilt; lock wait és process pause LP előtt friss DB-időt használ                                                                          | Valódi DB-óra és kontrollált barrier; puszta Carbon fake nem elég                                                                    |
| LP-2 késői commit           | LP előtt minden guard aktív; LP<deadline után pause és C>deadline megengedett, releváns writer mindvégig vár                                                                                          | MySQL külön connections + SQLite külön processz; state/event/receipt és DB-idő artifact                                              |
| LP-3 óra/adapter            | UTC epoch integer, session timezone/DST, másodperchatár, visszalépés policy, clock-health hiba fail-closed                                                                                            | Kétmotoros adapterteszt és kontrollált környezet; operatív rollback-kísérlet külön engedéllyel                                       |
| TX-1 tulajdonjog            | Nincs caller nesting, PDO-csere, default connectionre kiszökés, replica read, reconnect, implicit retry vagy külső call                                                                               | Query/connection trace, callback-attempt count=1, file-backed SQLite; [meglévő infra teszt:79,137][INFTEST] mintaforrás, nem új PASS |
| TX-2 hiba                   | Valódi deadlock/lock timeout/BUSY; teljes rollback vagy unknown, nincs részleges success, audit insert/unique fault state-et is rollbackol                                                            | Mindkét motor saját hibája, rollback proof, új connection check; nem csak kivétel mock                                               |
| TERM-1 terminális verseny   | Finalize/Cancel/Recover mindkét lock-sorrendje; terminal immutable; command replay/fingerprint conflict, NULL/legacy claim guard                                                                      | Kétmotoros conditional affected rows és receipt uniqueness; nincs új claim vagy deadline hosszabbítás                                |
| REC-1 reconciliation        | Proven rollback, post-commit ack-loss, aktív eredeti tx + no-receipt, unavailable primary, chain mismatch és revoked caller                                                                           | Külön scenario; valódi mid-COMMIT connection-loss külön infrastruktúra-bizonyíték; nincs vak retry                                   |
| ROLLOUT-1 writer coverage   | Minden azonosított endpoint, service/repository, pivot hook, seeder/import/cascade ugyanazt a guardcontractot követi; hiányzó guard és bypass tilt                                                    | Zárt writer-inventory + runtime trace + DB privilégium audit; UNKNOWN esetén aktiválás tiltott                                       |
| QG-1 kiválasztás/regresszió | Merlin suite ténylegesen kiválasztva, kapcsolódó auth/inventory/mrp/procurement/production/master-data regressziók benne                                                                              | [GatePlanner:108–121][QGPLAN] mai integration-lista nem proof; külön L1-D10 selectorjavítás, nem kézi suite-futtatással kiváltva     |

Későbbi ellenőrzési sorrend: legszűkebb adapter/guard teszt és affected gate,
majd érintett modulok; közös infrastruktúra miatt integration, migráció vagy
gate-infrastruktúra változásakor a projekt szerinti full/migration kapu,
és külön guardolt MySQL. A jelen munkában egyik alkalmazáskapu sem futott.

Bizonyítékcsomag: HEAD, motor/driver verzió, journal/isolation/access mód,
connection/PDO identity, lock/request/release marker, LP DB-idő és deadline,
writer commit-sorrend, guardverziók, state/event/receipt darabszám,
teszt/assertion/skip szám, exit code, külső call count=0; titkok nélkül.
`TEST_CONFIRMED` csak ilyen tényleges futás után rendelhető hozzá.

**BLOCKED / UNKNOWN runtime:** a hiányzó `km_production_test` a Decision 003
nyitott előfeltétele; most nem ellenőriztük vagy hoztuk létre. A
[backend gate](../../backend-quality-gate.md) guardja dedikált teszt-DB-t és
tesztfelhasználót követel; egy másik engedett adatbázis létezése sincs itt
igazolva. SQLite :memory: nem megfelelő többprocesszes konkurenciabizonyíték.

## 15. PROPOSED — keresztmodulos változtatási hatókör

| Terület                      | Később szükséges munka                                                                                                    | Scope-határ                                                                                   |
| ---------------------------- | ------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------- |
| Auth/admin                   | A/R/P guard adapter, friss evaluator, User/Role delete tranzakcióba fogása, pivot API/service/repository/seed útvonalak   | Generic Gate átírása és új user-aktiválási policy nincs elfogadva                             |
| Közös guard persistence      | Tárolási döntés, scope identity, version, hiány/retention/bootstrap és writer enforcement                                 | Új séma külön felhasználói döntés; L1-D02 nem automatikus engedély                            |
| MRP/master data              | I/M scope, MR insert/update/restore és BOM/item változás, célzott ekvivalens reader                                       | Netting eligibility/quantity/date üzleti szemantika változatlan                               |
| Inventory/production/quality | Reservation/consumption/stock-movement, production lifecycle és quality→completion guardhalmaz                            | Traceability és készletmozgás kötelező; nincs közvetlen készletkorrekció                      |
| Customer orders/procurement  | Parent fanout, item replace/delete, PO státusz/dátum, GR stock+incoming atomicitás, PR→PO belépési út                     | A meglévő workflow üzleti szabályát nem cseréli le a locktechnika                             |
| Merlin lifecycle             | Saját transaction adapter, LP, conditional terminal update, receipt, replay/reconciliation                                | Admission wrapper nem automatikusan reuse; meglévő admission garanciák regressziója szükséges |
| Cache/üzemeltetés            | Pozitív lifecycle döntés cache-kizárása; általános invalidation külön review; import/seed/DDL policy és primary routing   | Éles környezeti változtatás külön engedély; nincs paid activation                             |
| Quality Gate/teszt           | L1-D10 selection repair külön változtatás; teljes auth/business writer coverage és kétmotoros contention/fault bizonyíték | Tesztterv nem tesztfutás; adatot módosító kísérlet most tilos                                 |

Az előző táblázat tervezési hatókör, nem megkezdett implementáció és nem
kapott felhatalmazás. A generic CRUD és seeder/raw/cascade útvonalak miatt
a C bevezetési költsége nem becsülhető Merlin-only változtatásként.

## 16. OPEN / BLOCKED — döntések és fennmaradó bizonytalanságok

| Azonosító                            | Nyitott kérdés / blokk                                                                           | Feloldási bizonyíték vagy döntés                                                                  |
| ------------------------------------ | ------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------- |
| G-01 / OPEN                          | A vagy C további kidolgozása; javaslat C, B tisztán verziózott ellenőrzése lock nélkül elégtelen | Felhasználói architekturális választás, az ACCEPTED időszemantika változatlan                     |
| G-02 / OPEN                          | Új közös guard/version storage vagy existing parent anchor; új séma viszonya L1-D02-höz          | Explicit keresztmodulos scope és tárolási döntés, nem rejtett harmadik ledger                     |
| G-03 / BLOCKED                       | Teljes authorization/business writer- és permission-hook lefedettség UNKNOWN                     | Zárt callgraph, bulk/cascade/seed/import/DB-privilégium audit, enrollment és negatív bypass teszt |
| G-04 / BLOCKED                       | Parent fanout/scope discovery stabilitása, összetett M-halmazok                                  | Pontos query/lock terv, hint-bővülés rollback és old/new scope konkurenciabizonyíték              |
| G-05 / OPEN                          | Cache-független evaluator parity, super-admin és catalog-hiány, guard/morph policyverzió         | Felhasználói/security review és AUTH-1 teszt; nincs új permission vagy fiókflag                   |
| G-06 / BLOCKED                       | Inversion-audit, új lifecycle RC és admission RR/case-first együttélése                          | Minden érintett writer sorrendje, SQL-plan és valódi deadlock/timeout tesztek                     |
| G-07 / BLOCKED                       | LP/event/conditional SQL pontos adaptere és DB-óra/clock rollback policy                         | Jóváhagyott részletes contract + kétmotoros boundary és late-COMMIT bizonyíték                    |
| G-08 / BLOCKED                       | Unknown COMMIT authority és valódi connection-loss reconciliation                                | Primary/run/receipt rendezési terv és kontrollált mid-COMMIT bizonyíték                           |
| G-09 / BLOCKED                       | Tesztadatbázis és futtatási környezet                                                            | Külön engedélyezett guarded infra-rendezés; nincs DB-létrehozás ebben a munkában                  |
| G-10 / BLOCKED                       | Merlin Quality Gate selection                                                                    | Külön implementációs engedély és L1-D10 szerinti selector/regressziós futási bizonyíték           |
| G-11 / OPEN                          | Bevezetési sorrend, régi writer processzek kizárása, version bootstrap/overflow/retention        | Üzemeltethető, auditálható rollout és fail-closed aktiválási kapu                                 |
| G-12 / OPEN, jelenleg nem választott | D as-of advisory alternatíva eltérő üzleti garanciája                                            | Új felhasználói termék/authorization döntés; nem vezethető le Decision 003-ból                    |

**A TC-02 teljes státusza BLOCKED.** E dokumentum átadható felülvizsgálatra,
de nem állít teljes writerlefedettséget, implementációs készültséget vagy
bizonyított kétmotoros védelmet. A régi DRAFT, baseline és ADR-ek változatlanok;
az összehangolás külön engedélyezett munka marad. A feltárt blokkok a
tervezés eredményei, nem a dokumentációs audit végrehajtásának elhallgatott hibái.

## 17. Következő mérföldkő és dokumentációs ellenőrzés

A kötelező kérdések visszakeresése:

| Kérdés                                                | Válasz helye                                                                                     |
| ----------------------------------------------------- | ------------------------------------------------------------------------------------------------ |
| 1. Friss, cache-független authorization lehetséges-e? | 3.1. és 10.: igen, új belső evaluatorral javasolható; még nem bizonyított.                       |
| 2. Permission graph COMMIT-ig védelme?                | 7. és 10.: P/A/R tartós S/X guard, teljes writer-részvétellel.                                   |
| 3. Konkurens role-permission módosítás?               | 10. és 13.: R X ütközik F R S-sel, meleg cache nem authority.                                    |
| 4. Hiányzó sor/phantom?                               | 6., 7.1., 9. és 11.: stabil scope guard és parent membership; A-nál külön range-lock bizonyítás. |
| 5. Legkisebb indokolható netting scope?               | 4.1. és 11.: teljes Item ID pool; ekvivalencia még tesztelendő.                                  |
| 6. Mely business writerek változnának?                | 4. mátrix és 15. keresztmodulos hatókör.                                                         |
| 7. Aránytalan writer-változtatás?                     | 11. és 15.: parent fanout, bulk/cascade/seed a legnagyobb kockázat; arányosság UNKNOWN.          |
| 8. Lock inversion/deadlock?                           | 9.: kanonikus rang, hint-recheck, nincs késői lockbővítés; teljes audit még BLOCKED.             |
| 9. Timeout hamis siker nélkül?                        | 5.2. és 9.: teljes rollback/outcome-osztályozás, nincs APPLIED vagy vak retry.                   |
| 10. Közös és motorfüggő feltételek?                   | 5. és 14.: közös invariánsok, eltérő InnoDB row/range és SQLite IMMEDIATE/BUSY.                  |
| 11. Guardok utáni LP?                                 | 8.: friss feltételek után külön DB-óra statement, minden guard COMMIT-ig aktív.                  |
| 12. Unknown COMMIT?                                   | 12.: primary/eredeti tranzakció/receipt reconciliation, no-receipt nem rollback.                 |
| 13. Paid AI ismétlésének kizárása?                    | 12.: Finalize callbackben nincs AI; külső call nem transaction retry része, paid tiltott.        |
| 14. Milyen konkrét tesztek?                           | 13–14.: barrieres versenyhelyzetek, AUTH/BUS/LP/TX/TERM/REC/ROLLOUT/QG bizonyítási kapuk.        |

**PROPOSED:** `MERLIN_TC02_GUARD_PROTOCOL_SCOPE_AND_WRITER_COVERAGE_REVIEW`.
Cél: felhasználói választás A/C között; guard tárolási határ és teljes
writer-enrollment terv; parent fanout és lock-order proof kötelezettségeinek
lezárható, fájlonkénti listája. Ez is read-only audit + külön jóváhagyandó
szerződés legyen, implementáció, migráció vagy adatot módosító próba nélkül.
Ezt követheti jóváhagyott részletes SQL/adapter és külön tesztinfrastruktúra-scope;
a jelen feladat egyikre sem ad felhatalmazást.

A dokumentációs DoD itt: magyar tartalom, mindkét dependency mátrix,
négy alternatíva, 14 kérdésre visszakövethető válaszok, 13. rész versenyhelyzetei,
státusz/bizonyítékszint elválasztás, fájl/sor hivatkozások, linkek, Prettier,
whitespace és az egyetlen új untracked dokumentum ellenőrzése.
Alkalmazás-, migration-, E2E-, build-, Pint/PHPStan- és DB-protokollteszt `N/A`
e dokumentációs változáshoz; jövőbeli protokolligazolásként továbbra szükségesek.
A tényleges dokumentációs ellenőrzések eredménye és a sorok száma a
zárójelentésben szerepel; nincs commit vagy push.

### Forráshivatkozások

A relatív kódlinkek `#L` horgonyai a rögzített HEAD fájlsorszámait jelölik.
A range a link szövegében szerepel; a cél az első releváns sor.
Az ismeretlen külső writerhez szándékosan nincs kitalált fájlhivatkozás.

[LOCK]: ../../../composer.lock#L1134
[LOCKP]: ../../../composer.lock#L3681
[STARTAUTH]: ../../../app/Services/Merlin/StartMerlinInvestigationService.php#L143
[STARTIDENT]: ../../../app/Services/Merlin/StartMerlinInvestigationService.php#L131
[STARTUNKNOWN]: ../../../app/Services/Merlin/StartMerlinInvestigationService.php#L73
[USERREAD]: ../../../app/Repositories/Admin/UserRepository.php#L18
[TOOLUSER]: ../../../app/Repositories/Admin/UserRepository.php#L13
[TOOLAUTH]: ../../../app/Services/Merlin/GetSupplierOptionsTool.php#L134
[INERTIAAUTH]: ../../../app/Http/Middleware/HandleInertiaRequests.php#L42
[USERWRITE]: ../../../app/Repositories/Admin/UserRepository.php#L44
[USERDELETE]: ../../../app/Services/Admin/UserAdminService.php#L27
[USERCTRL]: ../../../app/Http/Controllers/Admin/UserController.php#L52
[USERMODEL]: ../../../app/Models/User.php#L66
[SUPER]: ../../../app/Providers/AppServiceProvider.php#L184
[ROLECTRL]: ../../../app/Http/Controllers/Admin/RoleController.php#L43
[ROLESVC]: ../../../app/Services/Admin/RoleAdminService.php#L29
[ROLEWRITE]: ../../../app/Repositories/Admin/RoleRepository.php#L29
[PERMREPO]: ../../../app/Repositories/Admin/PermissionRepository.php#L8
[PERMCTRL]: ../../../app/Http/Controllers/Admin/PermissionController.php#L16
[ABSR]: ../../../app/Repositories/Admin/AbstractAdminRepository.php#L78
[ABSS]: ../../../app/Services/Admin/AbstractAdminService.php#L42
[PCONFIG]: ../../../config/permission.php#L16
[PCACHE]: ../../../config/permission.php#L206
[CACHECFG]: ../../../config/cache.php#L18
[PMIG]: ../../../database/migrations/2026_06_22_143508_create_permission_tables.php#L54
[ROLESEED]: ../../../database/seeders/RolesAndPermissionsSeeder.php#L147
[ADMINSEED]: ../../../database/seeders/AdminUserSeeder.php#L29
[E2E]: ../../../database/seeders/E2ETestSeeder.php#L75
[RESOLVER]: ../../../app/Services/Admin/MaterialShortageProblemCaseResolver.php#L20
[ELIG]: ../../../app/Services/Admin/MaterialRequirementDemandEligibilityService.php#L20
[NETREAD]: ../../../app/Repositories/Admin/MaterialRequirementNettingRepository.php#L25
[NETSTOCK]: ../../../app/Repositories/Admin/MaterialRequirementNettingRepository.php#L58
[NETSUPPLY]: ../../../app/Repositories/Admin/MaterialRequirementNettingRepository.php#L99
[NETCALC]: ../../../app/Services/Admin/MaterialRequirementNettingService.php#L24
[CASE]: ../../../app/Repositories/Admin/ProblemCaseRepository.php#L27
[CASEWRITE]: ../../../app/Repositories/Admin/ProblemCaseRepository.php#L42
[MRWRITE]: ../../../app/Repositories/Admin/MaterialRequirementRepository.php#L62
[MRSVC]: ../../../app/Services/Admin/MaterialRequirementService.php#L31
[MRMODEL]: ../../../app/Models/MaterialRequirement.php#L80
[COCTRL]: ../../../app/Http/Controllers/Admin/CustomerOrderController.php#L87
[COSVC]: ../../../app/Services/Admin/CustomerOrderService.php#L64
[COWRITE]: ../../../app/Repositories/Admin/CustomerOrderRepository.php#L62
[PPSVC]: ../../../app/Services/Admin/ProductionPlanService.php#L91
[PPWRITE]: ../../../app/Repositories/Admin/ProductionPlanRepository.php#L74
[TASK]: ../../../app/Services/Admin/ProductionTaskService.php#L92
[QUALITY]: ../../../app/Services/Admin/QualityCheckService.php#L33
[BOMSVC]: ../../../app/Services/Admin/BomAdminService.php#L32
[BOMWRITE]: ../../../app/Repositories/Admin/BomRepository.php#L19
[ITEMSVC]: ../../../app/Services/Admin/ItemAdminService.php#L13
[GR]: ../../../app/Services/Admin/GoodsReceiptService.php#L84
[GRUPDATE]: ../../../app/Services/Admin/GoodsReceiptService.php#L154
[GRCTRL]: ../../../app/Http/Controllers/Admin/GoodsReceiptController.php#L74
[BALWRITE]: ../../../app/Repositories/Admin/StockBalanceRepository.php#L73
[RESSVC]: ../../../app/Services/Admin/StockReservationService.php#L33
[RESWRITE]: ../../../app/Repositories/Admin/StockReservationRepository.php#L79
[CONSUME]: ../../../app/Services/Admin/ProductionTaskMaterialService.php#L28
[POSVC]: ../../../app/Services/Admin/PurchaseOrderService.php#L38
[PRPO]: ../../../app/Services/Admin/PurchaseRequisitionService.php#L198
[SCHEDULE]: ../../../app/Services/Admin/SchedulingService.php#L31
[INVSEED]: ../../../database/seeders/InventorySeeder.php#L25
[ITEMSEED]: ../../../database/seeders/ItemsSeeder.php#L66
[ITEMMASTERSEED]: ../../../database/seeders/ItemMasterDataSeeder.php#L17
[E2EITEM]: ../../../database/seeders/E2ETestSeeder.php#L135
[E2ECLEAN]: ../../../database/seeders/E2ETestSeeder.php#L543
[E2EFIXTURE]: ../../../database/seeders/E2ETestSeeder.php#L606
[EXECSEED]: ../../../database/seeders/ProductionExecutionSeeder.php#L68
[ORDERSEEDALL]: ../../../database/seeders/OrderProductionSeeder.php#L47
[PROCSEEDALL]: ../../../database/seeders/ProcurementSeeder.php#L46
[ORDERSEED]: ../../../database/seeders/OrderProductionSeeder.php#L98
[BOMSEED]: ../../../database/seeders/ProductionStructureSeeder.php#L60
[PROCSEED]: ../../../database/seeders/ProcurementSeeder.php#L86
[COIMIG]: ../../../database/migrations/2026_06_21_000018_create_customer_order_items_table.php#L17
[BOMMIG]: ../../../database/migrations/2026_06_21_000013_create_bom_items_table.php#L16
[RESMIG]: ../../../database/migrations/2026_06_21_000024_create_stock_reservations_table.php#L15
[POIMIG]: ../../../database/migrations/2026_06_21_000029_create_purchase_order_items_table.php#L15
[MRINDEX]: ../../../database/migrations/2026_08_13_000001_harden_material_requirement_foundation.php#L18
[TX]: ../../../app/Support/Merlin/MerlinAdmissionTransaction.php#L22
[TXSTATE]: ../../../app/Support/Merlin/MerlinAdmissionTransactionState.php#L21
[CTX]: ../../../app/Support/Merlin/MerlinAdmissionContext.php#L43
[DBCONFIG]: ../../../config/database.php#L20
[AUDIT]: ../../../app/Services/Merlin/MerlinAdmissionAuditWriter.php#L13
[RUNMODEL]: ../../../app/Models/MerlinRun.php#L37
[BCACHE]: ../../../app/Services/BusinessCacheInvalidator.php#L123
[ACKTEST]: ../../../tests/Feature/MerlinAdmissionServiceTest.php#L413
[INFTEST]: ../../../tests/Feature/MerlinAdmissionInfrastructureTest.php#L79
[QGPLAN]: ../../../app/Support/QualityGate/GatePlanner.php#L108
[GATE]: https://github.com/laravel/framework/blob/92a707229148e57f08a249211c8a5a194159c619/src/Illuminate/Auth/Access/Gate.php#L428
[GATEBEFORE]: https://github.com/laravel/framework/blob/92a707229148e57f08a249211c8a5a194159c619/src/Illuminate/Auth/Access/Gate.php#L558
[LTX]: https://github.com/laravel/framework/blob/92a707229148e57f08a249211c8a5a194159c619/src/Illuminate/Database/Concerns/ManagesTransactions.php#L26
[LTXERROR]: https://github.com/laravel/framework/blob/92a707229148e57f08a249211c8a5a194159c619/src/Illuminate/Database/Concerns/ManagesTransactions.php#L232
[LTXMAN]: https://github.com/laravel/framework/blob/92a707229148e57f08a249211c8a5a194159c619/src/Illuminate/Database/DatabaseTransactionsManager.php#L67
[REGGATE]: https://github.com/spatie/laravel-permission/blob/70a6ab04108616b438e0839598f473b513281644/src/PermissionRegistrar.php#L114
[REGCONFIG]: https://github.com/spatie/laravel-permission/blob/70a6ab04108616b438e0839598f473b513281644/src/PermissionRegistrar.php#L66
[REGCACHE]: https://github.com/spatie/laravel-permission/blob/70a6ab04108616b438e0839598f473b513281644/src/PermissionRegistrar.php#L178
[HPCHECK]: https://github.com/spatie/laravel-permission/blob/70a6ab04108616b438e0839598f473b513281644/src/Traits/HasPermissions.php#L173
[HPROLE]: https://github.com/spatie/laravel-permission/blob/70a6ab04108616b438e0839598f473b513281644/src/Traits/HasPermissions.php#L300
[HPREL]: https://github.com/spatie/laravel-permission/blob/70a6ab04108616b438e0839598f473b513281644/src/Traits/HasPermissions.php#L83
[HPWRITE]: https://github.com/spatie/laravel-permission/blob/70a6ab04108616b438e0839598f473b513281644/src/Traits/HasPermissions.php#L387
[HPDELETE]: https://github.com/spatie/laravel-permission/blob/70a6ab04108616b438e0839598f473b513281644/src/Traits/HasPermissions.php#L33
[HPFORGET]: https://github.com/spatie/laravel-permission/blob/70a6ab04108616b438e0839598f473b513281644/src/Traits/HasPermissions.php#L537
[HRREL]: https://github.com/spatie/laravel-permission/blob/70a6ab04108616b438e0839598f473b513281644/src/Traits/HasRoles.php#L56
[HRWRITE]: https://github.com/spatie/laravel-permission/blob/70a6ab04108616b438e0839598f473b513281644/src/Traits/HasRoles.php#L218
[HRHAS]: https://github.com/spatie/laravel-permission/blob/70a6ab04108616b438e0839598f473b513281644/src/Traits/HasRoles.php#L321
[HRDELETE]: https://github.com/spatie/laravel-permission/blob/70a6ab04108616b438e0839598f473b513281644/src/Traits/HasRoles.php#L27
[PERMFIND]: https://github.com/spatie/laravel-permission/blob/70a6ab04108616b438e0839598f473b513281644/src/Models/Permission.php#L103
[ROLEREL]: https://github.com/spatie/laravel-permission/blob/70a6ab04108616b438e0839598f473b513281644/src/Models/Role.php#L85
[REFCACHE]: https://github.com/spatie/laravel-permission/blob/70a6ab04108616b438e0839598f473b513281644/src/Traits/RefreshesPermissionCache.php#L9
[MYSQLLOCK]: https://dev.mysql.com/doc/refman/8.4/en/innodb-locking-reads.html
[MYSQLISO]: https://dev.mysql.com/doc/refman/8.4/en/innodb-transaction-isolation-levels.html
[MYSQLPHANTOM]: https://dev.mysql.com/doc/refman/8.4/en/innodb-next-key-locking.html
[MYSQLERROR]: https://dev.mysql.com/doc/refman/8.4/en/innodb-error-handling.html
[MYSQLTIME]: https://dev.mysql.com/doc/refman/8.4/en/date-and-time-functions.html
[SQLITETX]: https://www.sqlite.org/lang_transaction.html
[SQLITETIME]: https://www.sqlite.org/lang_datefunc.html
