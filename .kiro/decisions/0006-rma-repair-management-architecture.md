# ADR-0006: RMA- és javításkezelés architektúrája

## Státusz

Accepted

- Feladat: RMA-0002/A tervezet, RMA-0002/B felülvizsgálat, RMA-0002/C véglegesítés és elfogadás; előzmény: RMA-0001 domainaudit.
- Létrehozás: 2026-10-27; felülvizsgálat és architekturális elfogadás: 2026-10-28.
- Ellenőrzött branch: `feature/returns-repair-management`.
- C1 Closure amendment-baseline (2026-10-29): `ea568deded91a1c256edbefdfbec7cf5be2ce096`; a korábbi audit-baseline alább történeti.
- Ellenőrzött baseline: `9424578a03606b7218530ecac3f7ab417b4da726`.
- Az RMA-0002/A kezdetén a munkafa tiszta volt; az RMA-0002/B és RMA-0002/C kezdetén kizárólag ez az untracked ADR szerepelt. A fájl helyben, változatlan sorszámmal véglegesítve.
- A következő szabad ADR-szám `0006`; a `0020` szám használata nem indokolt.

Ez elfogadott architekturális döntés, nem implementációs vagy éles működési
bizonyíték. RMA-0003/C1 lezárás, 2026-10-29: a projektgazda elfogadta
RMA-D6–D11 döntéseit és jóváhagyta AM-01–AM-04-et, az AM-02-G1–G4 kötelező
integritási feltételekkel. Az amendment-történet és aktuális döntések a 3.2–3.4
fejezetben követhetők; az ADR azonosítója és Accepted státusza megmarad. Az elfogadás alapja az RMA-0002/C felhasználói jóváhagyása: az
RMA-BD-001–020 követelmények, az F1–F4 korrekciók és az alábbi D1–D5 döntések
következetesen együtt érvényesek. Az architekturális határok és invariánsok
kötelezőek; a konkrét táblák, mezők, permissionök és szolgáltatási szerződések
részletes kialakítását a 14. fejezet implementációs kapui szerint kell igazolni.
Alkalmazás, migráció, adatjavítás és számlázás nem része e véglegesítésnek.
A 15.1 eredeti véglegesítési/mentési feltételei a 2026-10-28-i feladat történeti
hatókörét írják le; a mostani closure feladat kizárólag dokumentációs, staging,
commit, push és merge nélkül, az ADR/A/B/C1/C2 konzisztencia átvezetésével.

## 1. Kontextus és ellenőrzött kiinduló állapot

Az RMA saját gyártású, sorozatszámos termékek visszavételét, diagnosztikáját,
garanciális vagy fizetős javítását, cseréjét és dokumentált végső elhelyezését
kezeli. Egy ügy több terméket foghat össze, de nem olvaszthatja össze azok
állapotát és végrehajtását.

Az RMA-0001 releváns megállapításait a baseline-on újraellenőriztük:

| Ellenőrzött tény                                                                               | Repository-bizonyíték                                                                                                                                                                                                                              | Tervezési következmény                                           |
| ---------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------- |
| A példány serialja kötelező és egyedi; a gyártásirendelés-azonosító nullable és csak indexelt. | [item_instances migráció](../../database/migrations/2026_06_21_000010_create_item_instances_table.php), [ItemInstance](../../app/Models/ItemInstance.php)                                                                                          | A serial nem elegendő eredetigazolás.                            |
| A példány a gyártási feladatok generálásakor, még a gyártás előtt létrejön.                    | [ProductionTaskService](../../app/Services/Admin/ProductionTaskService.php): `generateFromOrder()`, `nextSerialNumber()`                                                                                                                           | A serial létezése nem bizonyít befejezett gyártást vagy átadást. |
| A serialigény kapcsoló nem azonos a saját gyártás bizonyítékával.                              | [ItemType](../../app/Enums/ItemType.php), [ItemSerialNumberRequirementTest](../../tests/Feature/Admin/ItemSerialNumberRequirementTest.php)                                                                                                         | Az RMA saját alkalmassági szolgáltatást igényel.                 |
| A feladat kötelezően gyártási rendeléshez és műveletsorlépéshez kapcsolódik.                   | [production_tasks migráció](../../database/migrations/2026_06_21_000032_create_production_tasks_table.php), `ProductionTaskService::start()`, `finish()`                                                                                           | Önálló javítási végrehajtás szükséges.                           |
| Az elérhetőség és foglalási jelöltek nem szűrnek ügyféltulajdonra vagy minőségi elkülönítésre. | [StockBalanceRepository](../../app/Repositories/Admin/StockBalanceRepository.php): `totalQuantityForItem()`, `balancesForReservation()`                                                                                                            | Egy külön raktárhely nem elégséges védelem.                      |
| A fogyasztás cikk/batch/hely és mennyiség alapján választ egyenleget.                          | [ProductionTaskMaterialService](../../app/Services/Admin/ProductionTaskMaterialService.php): `balanceForConsumption()`                                                                                                                             | A visszáru nem kerülhet normál fogyasztható egyenlegbe.          |
| A mozgás példányt és polimorf üzleti forrást is tárolhat.                                      | [stock_movements migráció](../../database/migrations/2026_06_21_000025_create_stock_movements_table.php)                                                                                                                                           | Megtartható a közös mozgási eseményforrás.                       |
| A QC gyártási feladathoz kötött és gyártási állapotokat változtat.                             | [QualityCheckService](../../app/Services/Admin/QualityCheckService.php): `store()`                                                                                                                                                                 | A javításnak külön ellenőrzési rekord és workflow kell.          |
| Az anyagigény és a beszerzési forráskapcsolat vevőirendelés-alapú.                             | [material_requirements migráció](../../database/migrations/2026_06_21_000022_create_material_requirements_table.php), [beszerzési forráskapcsolat](../../database/migrations/2026_06_24_000002_create_purchase_requisition_item_sources_table.php) | Javítási hiányhoz explicit igényadapter kell.                    |
| Az audit, policy és cache-invalidation infrastruktúra létezik.                                 | [AuditLogService](../../app/Services/AuditLogService.php), [AppServiceProvider](../../app/Providers/AppServiceProvider.php), [BusinessCacheInvalidator](../../app/Services/BusinessCacheInvalidator.php)                                           | Célzottan újrafelhasználható infrastruktúra.                     |
| A dokumentumkapcsolható típusok listája korlátozott.                                           | [DocumentableRegistry](../../app/Support/DocumentableRegistry.php): `aliases()`                                                                                                                                                                    | Az RMA és bizonyítékai még nem támogatott célmodellek.           |

A vizsgált `app/Models`, `app/Services`, `database/migrations` és `routes`
állományokban nincs dedikált RMA-, garancia- vagy javítási ajánlatimplementáció.
Ez forráskódra vonatkozó megállapítás; az éles adatállomány és külső rendszerek
teljessége nem ellenőrzött. A kért rendelési szállítási dátum nem igazolt
termékátadás.

Kapcsolódó elfogadott ADR-ek:

- [ADR-0001](0001-stock-movements.md): minden mennyiségi változás mozgással magyarázható; közvetlen egyenlegjavítás tilos.
- [ADR-0002](0002-operation-sequence-versioning.md): az eredeti gyártási műveletsor verziója megőrzendő.
- [ADR-0003](0003-quality-control.md): a kötelező QC nem kerülhető meg, a hibás eredmény nem törölhető.
- [ADR-0004](0004-serial-numbering.md): a sorozatszám nem generálható újra és nem rendelhető másik termékhez.
- [ADR-0005](0005-document-ai.md): az üzleti döntés és adatírás Laravel-felelősség; AI/OCR nem dönthet garanciáról vagy kiadásról.

## 2. Problémafelvetés

A gyártási feladat RMA-célú használata újranyitná vagy módosítaná az eredeti
gyártási életciklust. A visszáru normál egyenlegre bevételezése pedig szabad
készletté tehetné az ügyféltulajdonú terméket. Az egyetlen összevont ügyállapot
nem tudná helyesen kezelni a részleges átvételt, eltérő garanciális döntéseket,
ajánlatokat, javítási próbálkozásokat és visszaadásokat.

Önálló javítási domain szükséges, amely megőrzi az eredeti gyártást, fizikai
őrzést követ, és a normál készletből kizárólag a javítás anyagait használja fel.

## 3. Jóváhagyott üzleti döntések és lefedettség

Az alábbi követelmények jóváhagyottak; a hivatkozott megvalósítási módok tervezetek.

| Azonosító  | Jóváhagyott üzleti szabály                                                                                                                                                                                                                                                                                                                                                                                                                                                                  | Tervezési hely            |
| ---------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------- |
| RMA-BD-001 | Több serial egy ügyben; termékenként önálló munkalap és életciklus.                                                                                                                                                                                                                                                                                                                                                                                                                         | 6.1, 7.1–7.3              |
| RMA-BD-002 | Csak érvényes serialú saját gyártású késztermék és komponens; garanciális időszakban javítás vagy csere.                                                                                                                                                                                                                                                                                                                                                                                    | 5.2, 6.1, 7.5             |
| RMA-BD-003 | Kezdetben jogosult belső dolgozó rögzít; bejelentő és tulajdonos külön szerep.                                                                                                                                                                                                                                                                                                                                                                                                              | 6.1, 9                    |
| RMA-BD-004 | Egy serialhoz egy aktív RMA; új eset csak az előző ügy lezárása után; történet megmarad.                                                                                                                                                                                                                                                                                                                                                                                                    | 6.2, 10                   |
| RMA-BD-005 | Manuális `warranty`, `non_warranty`, `pending_assessment`; jogosultság és indoklás.                                                                                                                                                                                                                                                                                                                                                                                                         | 6.4, 7.5, 9               |
| RMA-BD-006 | Fizetős javítás csak jóváhagyott ajánlat alapján indulhat.                                                                                                                                                                                                                                                                                                                                                                                                                                  | 7.3, 7.6                  |
| RMA-BD-007 | A visszáru ügyféltulajdon; fizikai követés és normál készlettől elkülönítés.                                                                                                                                                                                                                                                                                                                                                                                                                | 8                         |
| RMA-BD-008 | Nem javítható termék: csere, javítás nélküli visszaadás, engedélyezett selejt vagy függő döntés; selejthez dokumentált felhatalmazás.                                                                                                                                                                                                                                                                                                                                                       | 6.5, 7.2, 10              |
| RMA-BD-009 | Sikertelen QC után új próbálkozás; korábbi műveletek és vizsgálatok változatlanok; nincs automatikus próbálkozási korlát.                                                                                                                                                                                                                                                                                                                                                                   | 6.3, 7.3                  |
| RMA-BD-010 | Több dolgozó, műveletek, kezdés/befejezés, tényleges munkaidő és anyagok követése.                                                                                                                                                                                                                                                                                                                                                                                                          | 6.3                       |
| RMA-BD-011 | Ügyzárás csak minden termék dokumentált végső rendelkezése után; javítási készültség önmagában elégtelen.                                                                                                                                                                                                                                                                                                                                                                                   | 7.1, 10                   |
| RMA-BD-012 | Garanciakezdet igazolt átadás; cikkszintű konfigurálható időtartam; bizonyítékhiány függő; indokolt jogosult felülbírálás.                                                                                                                                                                                                                                                                                                                                                                  | 6.4, 7.5                  |
| RMA-BD-013 | Anyag-, munka- és járulékos díjas ajánlat, benyújtás, ügyféljóváhagyás, elutasítás, lejárat; számlázás nem kezdeti scope.                                                                                                                                                                                                                                                                                                                                                                   | 6.4, 7.6                  |
| RMA-BD-014 | Ingyenes, fix vagy időalapú diagnosztika; díja az ajánlatba beszámítható.                                                                                                                                                                                                                                                                                                                                                                                                                   | 6.4, 7.6                  |
| RMA-BD-015 | Teljes javítási történet a serialhoz kötve; új ügy nyitásakor előzmények kiemelve.                                                                                                                                                                                                                                                                                                                                                                                                          | 5.2, 6.1, 11              |
| RMA-BD-016 | Átvétel ellenőrzi az ügytagságot, serialt, állapotot és korábbi átvételt; készletkövetés és audit keletkezik.                                                                                                                                                                                                                                                                                                                                                                               | 7.4, 10                   |
| RMA-BD-017 | Visszáru nem eladható, normál gyártásra nem foglalható/fogyasztható, szabad készletbe nem számít; helye követett.                                                                                                                                                                                                                                                                                                                                                                           | 8, 11                     |
| RMA-BD-018 | Javítási anyag normál készletből; cikk, mennyiség, batch/serial, munkalap és mozgás kapcsolata; hiányból beszerzési igény.                                                                                                                                                                                                                                                                                                                                                                  | 6.3, 8.4                  |
| RMA-BD-019 | Kiadáskor címzett, dátum, szállítási/átadási referencia, bizonyíték és végállapot; kiadás megszünteti a javítási őrzést.                                                                                                                                                                                                                                                                                                                                                                    | 6.5, 7.4, 10              |
| RMA-BD-020 | Csere: külön foglalás, fizikai előkészítés és bizonyított átadás. Foglalás/előkészítés alatt vállalati tulajdon, más célra nem allokálható; minden valódi fizikai shipment kiadása egyszeri (AM-01), tulajdonátszállás csak igazolt szerződéses feltétel szerint. Átadás előtti jogosult megszakítás csak hely-/állapot-/alkalmasság-ellenőrzés után oldhat foglalást; teljes történet megmarad, mesterséges korrekció nincs. Az eredeti visszáru végső rendelkezése függetlenül szükséges. | 6.5–6.6, 7.7, 8.2–8.3, 10 |

### 3.1. RMA-0002/C – elfogadott architekturális döntések

Az alábbi öt döntés státusza külön-külön **Accepted**. Nem váltják ki és nem
számozzák át az RMA-BD-001–020 üzleti követelményeket.

#### D1 – Készlethatókörök

**Accepted.** Az elfogadott hatókörök: `company_stock`, `customer_custody`,
`external`. Az ügyféltulajdonú visszáru nem növelheti a normál készletegyenleget.
Az eredeti visszáru elkülönített custody-projekciója közös `StockMovement`
eseményekből épül; a mennyiségi hatókör nem a jogi tulajdon automatikus jelölése.
Érvényesítés: 6.5, 7.4, 8.2–8.3, 10–11. fejezet.

#### D2 – Saját gyártású komponensek

**Accepted.** A `semi_finished_product` típusú komponens is jogosult lehet
RMA-kezelésre, ha saját gyártású, egyedi sorozatszámmal rendelkezik és gyártási
eredete bizonyítható. A terméktípus önmagában nem igazolja az alkalmasságot;
az 5.2 szerinti eredetellenőrzés minden támogatott kategóriánál kötelező.
Érvényesítés: 5.2, 6.1, 11. és 15.2 fejezet.

#### D3 – Tulajdonosi modell

**Accepted.** Egy RMA-ügy egy tulajdonoshoz tartozik. A bejelentő és a tulajdonos
külön szereplő lehet; eltérő tulajdonosok termékei külön ügyekbe kerülnek.
Az ügy összes tagsága a rögzített tulajdonoshoz tartozik, termékenként önálló
munkalappal. Érvényesítés: 6.1, 7.1, 10. és 15.2 fejezet.

#### D4 – Cseregarancia

**Accepted.** Csere esetén nem indul automatikusan új garanciális időszak.
Az eredeti és cserepéldány garanciatörténete külön identitáshoz kötve megmarad.
A rendszer az alkalmazandó jogszabályi és szerződéses szabályok szerinti,
bizonyítékos döntést támogatja, nem helyettesíti. Ez nem tiltja az alkalmazandó
szabályból következő döntést; azt nem vezeti le automatikusan a csere tényéből.
Érvényesítés: 6.4, 6.6, 7.5, 11. és 15.2 fejezet.

#### D5 – Fizikai kiadás és tulajdonjog

**Accepted.** A fizikai készletkivezetés és a tulajdonjog átszállása külön
nyilvántartott esemény. A tulajdonváltozás időpontja az alkalmazandó feltételekből
következik; a rendszer nem vélelmezheti, hogy a kiadás időpontja egyben
jogi tulajdonátruházás. A két esemény időpontja bizonyított feltétel alapján
megegyezhet, de ettől nyilvántartásuk külön marad. Előkészítés nem ownership-
esemény; a tulajdonátszállás nem okoz második fizikai készletcsökkentést.
Érvényesítés: 6.5, 7.7, 8.2–8.3 és 10–11. fejezet.

A D1–D5 feloldja a korábbi T01/T03/T04/T05/T07 architekturális elfogadási
kérdéseit. A konkrét szerződéses és garanciaszabályok konfigurálása továbbra is
az érintett működés bevezetési előfeltétele; az ADR elfogadása nem igazolja
annak elkészültét vagy egy konkrét ügy jogi minősítését.

### 3.2. RMA-0003/C1 – elfogadott üzleti döntések

RMA-D6–D11 külön-külön **ACCEPTED**, a projektgazda C1-döntései alapján.
Nem számozzák át az RMA-BD-001–020 és D1–D5 döntéseket.

#### RMA-D6 – Diagnosztikai kredit foglalása

A diagnosztikai díj önálló, igazolt identitású tény. A javítási ajánlat legfeljebb
az igazolt, ugyanazon munkalaphoz tartozó díjat számíthatja be. Kreditet csak
az approved ajánlat foglal; draft/submitted nem effektív foglalás. Ugyanaz
a díjforrás nem számítható be kétszer; a díj-, ajánlat- és kredittörténet megmarad.
Érvényesítés: 6.4, 7.6, 10–11.

#### RMA-D7 – Diagnosztikai kredit felszabadítása

Cancel vagy supersede nem törli a történetet és nem automatikus release.
Release előtt ellenőrizni kell a tényleges végrehajtást és a pénzügyi rendezést.
Unused, unsettled kredit csak jogosult, auditált tranzakcióval szabadítható fel.
Settled kredithez az eredetire hivatkozó külön korrekciós eljárás szükséges;
korábbi ajánlat és döntés immutable. Ismeretlen settlement nem igazolt unsettled.
A settlement observation az RMA-döntés bizonyítéka, nem számlázási/fizetési vagy
könyvelési alrendszer. Érvényesítés: 6.4, 7.6, 9–11.

#### RMA-D8 – Sikertelen kézbesítés és fizikai visszaérkezés

Failed delivery önálló esemény, nem készletnövekedés. Fizikai returnhez igazolt
serialazonosság és dokumentált átvétel kell; minden visszaérkezett replacement
kötelező karanténba kerül. Production, sales, repair-consume, unrelated reserve
és normal availability kizárt. Identity, condition, ownership és korábbi shipment
bizonyítékait ellenőrizni kell; további használathoz authorized disposition kell.
Physical return önmagában nem ownership reversal. Érvényesítés: 3.3, 7.7, 8–11.

#### RMA-D9 – Felhatalmazott újraküldés

Ugyanazon replacement serial több valódi fizikai shipmentben szerepelhet.
Mindegyik önálló immutable identity/proof és stabil üzleti occurrence mellett,
egyszer könyvelhető. Redispatchhez igazolt physical return, identity, condition,
ownership/disposition right, explicit authorization és megfelelően rendezett
előző shipment kell. Minden shipment/delivery előzmény elérhető marad.
Érvényesítés: 6.5–6.6, 7.7, 8.2–8.3, 10–11.

#### RMA-D10 – Tulajdonjog visszafordítása

Physical return nem okoz tulajdonváltozást. Reversalhez dokumentált jogi vagy
szerződéses alap, jogosult actor és supporting evidence kell. A reversal új
immutable esemény, kötelezően az eredeti ownership eventre hivatkozik;
az eredeti tény és a korrekciós történet megmarad. Fizikai custody és jogi
ownership külön rekonstruálható. Érvényesítés: 6.5, 7.7, 8.2, 9–11.

#### RMA-D11 – Beszerzési fedezet felszabadítása

RMA-demand cancellation nem procurement commitment cancellation. Demand-only igény
kontrolláltan visszavonható; draft PR review-val igazítható, approved PR jogosult
review-t igényel. Kiküldött vagy supplier-acknowledged PO nem törölhető vagy
szüntethető meg csendben. Received goods tényleges receipt/ownership/inventory
szabályok szerint maradnak követettek. Coverage release/reassignment/cancellation
auditált üzleti művelet, procurement history és eredeti source rekordok megmaradnak.
Az újraszámítás és coverage-változás sem generálhat dupla beszerzést.
Érvényesítés: 8.4, 9–11 és 14.2.

### 3.3. AM-01–AM-04 – jóváhagyott amendmentek és kötelező AM-02 feltételek

AM-01–AM-04 státusza **APPROVED**; projektgazdai jóváhagyás: **2026-10-29**,
az RMA-0003/C1 Closure feladatban. Az elfogadás architekturális, nem DDL- vagy
tesztigazolás. A fizikai hely, jogi ownership és disposition/allocation független.

- **AM-01:** a logikai replacement obligation különbözik a fizikai shipment
  attempttől, physical returntől, delivery evidencetől és ownership transfertől.
  Több valódi shipment engedett D9 szerint, mind saját immutable identity/movement
  proof, stabil occurrence és idempotens posting mellett. Az eredeti termék
  végső dispositionje független marad.
- **AM-02:** minden replacement return kétlépcsős: physical intake karanténba,
  majd indokolt esetben külön verified inventory admission. Unresolved ownership
  nem gátolhatja a valódi intake rögzítését. Pending admission az existing external
  könyvelési oldalon, ténylegesen belső helyen is követhető; nem negyedik scope.
  A kivétel kizárólag az alábbi négy kötelező feltétellel alkalmazható.
- **AM-03:** önálló verified diagnostic fee; credit csak approvalkor, egy effektív
  allocation díjforrásonként. Unused/unsettled release jogosult auditált döntés;
  settled külön correction. Quote és credit history immutable. Settlement
  observation kizárólag RMA-bizonyíték, nem általános pénzügyi alrendszer.
- **AM-04:** reversal új immutable tény az original ownership event és a
  jogalap/proof megőrzésével. Demand, commitment és receipt önálló lifecycle;
  coverage release/reassign auditált, a commitment és source history nem tűnik el.

| Kötelező feltétel                                 | Elfogadott integritási szerződés                                                                                                                                                                                                  |
| ------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| AM-02-G1 – Physical evidence                      | Intake csak igazolt serial, valós hely, timestamp, source shipment és supporting evidence mellett; a tény és a karantén/helyprojekció egy tranzakció.                                                                             |
| AM-02-G2 – No unauthorized company stock increase | Return-intake normal company balance-hatása 0. Company admission csak az alkalmazandó befogadási követelmények teljesülése után; ismeretlen ownership nem company tulajdonvélelem.                                                |
| AM-02-G3 – Quarantine isolation                   | Karantén nem production-consumable, sales-allocatable, repair-material-consumable, unrelated reservation vagy normal available stock. Minden releváns writer és reader kötelezően kizárja, nem csak egy raktárhely vagy UI-szűrő. |
| AM-02-G4 – No duplicate receipt                   | Intake és admission eltérő identity és idempotenciavédelem; admission nem második fizikai receipt. Return/shipment kapcsolat, serial és egyszeri projectionhatás védendő.                                                         |

Az external -> external kivétel kizárólag kifejezetten engedélyezett
mozgástípus–purpose–RMA return kontextusban érvényes. A jóváhagyott alapeset
a bizonyított replacement return-intake, pending-admission/mandatory-quarantine
céllal, normál balance-hatás nélkül. Általános transferre nem terjeszthető ki.
Minden további típus/cél pontos engedélyezése és CHECK/permission szerződése C2-ben
külön felsorolandó; az általános scrap/transfer enum önmagában nem felhatalmazás.
A disposition/admission jogcím nem írja át a fizikai érkezés idejét és nem
hozhat létre új példányt vagy fiktív tulajdonváltozást.

### 3.4. Amendment-történet és megőrzött indoklás

Az eredeti A/B készletstratégia összevetése, önálló repair-domain és eredeti
gyártási traceability indokai változatlanok. A korábbi szóhasználatot az alábbi
jóváhagyott kiterjesztések pontosítják; a régi indoklást nem töröltük.

| Amendment | Jóváhagyás / forrás                                                     | Érintett ADR-fejezetek                      | Korábbi megfogalmazás és változtatás oka                                                                                                                                                                                                    |
| --------- | ----------------------------------------------------------------------- | ------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| AM-01     | 2026-10-29; projektgazda kifejezett RMA-0003/C1 Closure jóváhagyása     | 3, 6.5–6.6, 7.1/7.7, 8.2–8.3, 10–11, 15.2   | A „cserefolyamat egyszeri kiadása” az idempotenciát védte, de kizárhatta a valódi újraküldést. Most shipmentenként egyszeri posting, logikai obligationtől külön.                                                                           |
| AM-02     | 2026-10-29; projektgazda jóváhagyása AM-02-G1–G4 kötelező feltételeivel | 3.3, 6.5, 7.1/7.7, 8.2–8.3, 9–11, 14.2/15.2 | A közvetlen external->company return és a hiányos bizonyíték miatti általános átvételblokkolás helyett intake+karantén, majd verified admission. A régi on-site képlet pending admissionnel bővül; nincs új scope vagy automatikus készlet. |
| AM-03     | 2026-10-29; projektgazda kifejezett jóváhagyása                         | 3.2–3.3, 6.4, 7.6, 9–11, 14.2               | A díjszabás/ajánlati sor nem önálló verified fee. Approval-only allocation és release review szükséges; a korábbi pénzügyi scope-kizárás az általános accountingra vonatkozik, nem az RMA-proof megfigyelésére.                             |
| AM-04     | 2026-10-29; projektgazda kifejezett jóváhagyása                         | 3.2–3.3, 6.5, 7.7, 8.4, 9–11, 14.2          | A fizikai visszatérésből nem következhet jogi reversal; a demand törléséből nem következhet PO-cancel. Külön immutable ownership és coverage history védi mindkettőt.                                                                       |

A korábbi 2026-10-28 Accepted baseline hash-e:
`3704c2047110ed8030c2ac9cf6acef14dd3f7acde989aab165abe11369053bfc`.
Részletes korábbi review: [RMA-0003/C1](../designs/rma-0003c1-business-decisions-integrity-gates.md).
A következő fázis követelményei: [C2 előkészítési terv](../designs/rma-0003c2-detailed-schema-review-plan.md).
A 25 táblás javaslat és a konkrét FK/trigger/grant/mezők továbbra is részletes
tervezési tárgyak, nem az amendmenttel végrehajtott séma.

## 4. Architektúra-alternatívák

1. **Gyártási feladatok kibővítése:** nem javasolt. A meglévő FK-k,
   állapotváltozások és eredeti műveletsor nem javítási szemantikájúak.
2. **Önálló javítás, integrált készletegyenleggel (A):** megvalósítható, de
   központi tulajdon- és rendelkezésreállási dimenzió bevezetését igényli az
   összes készletfogyasztónál. Részleges átállás nem biztonságos.
3. **Önálló javítás, elkülönített custody-projekcióval (B):** elfogadott irány. A
   visszáru nem része a vállalati szabad készletnek; fizikai eseményeit ugyanaz
   a `StockMovement` infrastruktúra rögzíti. A két állomány jelentése explicit.
4. **Független, mozgás nélküli javítási darabszám:** elvetendő, mert két
   elsődleges igazságforrást hozna létre és sértené az ADR-0001-et.

## 5. Elfogadott architektúra és domainhatárok

### 5.1. Felelősségek

| Határ              | Birtokolt üzleti állapot                                           | Integráció                                          |
| ------------------ | ------------------------------------------------------------------ | --------------------------------------------------- |
| Ügykezelés         | Bejelentés, szereplők, terméktagság, aktív esetfoglalás, ügyzárás  | Ügyfél, belső dolgozó, dokumentum                   |
| Javítás            | Termékenkénti munkalap, próbálkozások, műveletek, idő, anyagok, QC | `Employee`, `OperationType`, inventory, procurement |
| Fizikai őrzés      | Beérkezés, helyváltozás, kiadás, custody-projekció                 | `StockMovement`, `Location`, eredeti példány        |
| Garanciális döntés | Bizonyíték, szabálypillanatkép, manuális minősítés és felülbírálás | Termékátadás, cikktörzs, dokumentum                 |
| Ajánlat            | Verziózott ajánlati tartalom és ügyfél döntési bizonyítéka         | Javítási munkalap, idő- és anyagbecslés             |

A rétegzés `Controller -> Service -> Repository -> Model`; bemeneti
ellenőrzés FormRequestben, hozzáférés policyban, üzleti guard a service-ben,
lekérdezés és zárolás repositoryban legyen. A javasolt `RepairRequestService`,
`RepairOrderService`, `RepairCustodyService`, `WarrantyAssessmentService` és
`RepairQuotationService` domainműveleteket koordináljon. Ezek még nem léteznek.

Az ügy és a termékszintű javítás külön aggregátum. Egy ügy lezárása és a fizikai
mozgás integrációs tranzakcióban több aggregátumot is érinthet. Az eredeti
gyártási rendelés nem a javítás aggregátumának része, és nem nyílik újra.

### 5.2. Saját gyártás és történet igazolása

A javasolt `RepairEligibilityService` az eredeti `ItemInstance` azonosítót oldja
fel; serialformátum vagy `ItemType` önmagában nem elfogadható. Ellenőrizendő:

1. Nem üres, létező, egyértelmű serial és a ténylegesen átvett egységgel egyezés.
2. Létező eredeti `ProductionOrder`; a példány `item_id` értéke egyezik vele.
3. A példányhoz és rendeléshez összetartozó gyártási feladatok, megőrzött
   műveletsorverzió és befejezési bizonyíték; a kötelező QC elfogadott eredményei.
4. A gyártott kategória engedélyezett: `finished_product`, `manufactured_part`
   és saját gyártású komponensként `semi_finished_product` (D2). Mindháromnál
   külön igazolandó az egyedi serial és a gyártási eredet; a kategória vagy a
   serialigény-kapcsoló önmagában nem jogosít RMA-ra.
5. Nincs másik aktív ügyfoglalás, selejtezett vagy inkonzisztensen őrzött példány.

Hiányos vagy ellentmondó eredetadat nem automatikus elfogadás. RMA-tagság előtt
ellenőrzendő és rendezendő; az RMA nem generálhat pótló serialt, gyártási
feladatot vagy hamis készletmozgást az eredet bizonyítására. A régi adatok
bizonyítási/korrekciós eljárása külön technikai előfeltétel.

Az eredeti rendelés, műveletsor, anyagok, QC és dokumentumok csak olvasható
történeti kapcsolatok. A javítási és csere-előzmény `item_instance_id` szerint
lekérdezhető; új eset rögzítésekor az összes korábbi ügy látszik, lezárt ügyekkel
együtt. Csere esetén az eredeti és az új példány külön identitás marad.

## 6. Javasolt minimális adatmodell

Ez az elfogadott architektúra logikai adatmodellje, nem végleges migrációterv. Minden új rekord PK-ja `id`, kivéve az
egyediségi foglalások természetes kulcsát. Az FK-k alapesetben kötelezőek és
`RESTRICT` törlésűek. Az alábbi nullable mezők csak jelzett életciklusban
engedettek. A kereszt-rekordos egyezéseket lehetőség szerint összetett FK,
egyébként zárolt tranzakcióban ellenőrzött service-guard védi; a sima FK önmagában
nem igazolja a közös tulajdonost, cikket vagy munkalapot.

### 6.1. Ügy és terméktagság

| Entitás                | Lényeges mezők és kapcsolatok                                                                                                                                                 | Korlát                                                                                                                                                                  |
| ---------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `repair_requests`      | `case_number`, `created_by` -> `users`, `reporter_employee_id` -> `employees`, `owner_customer_id` -> `customers`, bejelentő elérhetőségi pillanatképe, `status`, `closed_at` | Egyedi `case_number`; a rögzítő belső dolgozó, a bejelentő lehet külső személy. A `reporter_employee_id` emiatt nullable; nem azonos a létrehozóval vagy tulajdonossal. |
| `repair_request_items` | `repair_request_id`, `item_instance_id`, hibaleírás, eredetellenőrzési bizonyítékhivatkozás és időpont                                                                        | Egyedi `(repair_request_id, item_instance_id)`; egy sor egy fizikai termék, mennyisége 1.                                                                               |
| `repair_orders`        | `repair_request_item_id`, `work_order_number`, `execution_status`, `current_attempt_number`, `diagnostic_fee_mode`, díj-/díjszabás-pillanatkép                                | Egyedi tagság és munkalapszám; minden felvett termékhez egy munkalap, ismételt próbálkozások ugyanazon munkalapon.                                                      |

D3 szerint egy ügy egy tulajdonoshoz tartozik; eltérő tulajdonosú termékek külön
ügybe kerülnek, nem veszítik el az önálló munkalapjukat. Az `owner_customer_id`
az ügy kötelező tulajdonosi FK-ja, nem helyettesíthető a bejelentő vagy a
létrehozó azonosítójával. Termékfelvételkor az ügy tulajdonosával való egyezést
az ügysorzár alatt ellenőrizni kell; a historikus tulajdonosi bizonyíték
megőrzendő. Az ügy lezárásáig a
tagság nem törölhető; már végrehajtással érintett termék nem mozgatható másik
ügybe. A tulajdonosi adat historikus pillanatképe megmarad.

### 6.2. Aktív ügyfoglalás

Az `active_repair_claims` kis integritási tábla, nem új üzleti aggregátum:
`item_instance_id` PK/FK, `repair_request_item_id` egyedi FK. Létrehozása az
ügytagsággal azonos tranzakcióban történik. A tagság példánya és a claim példánya
egyezzen; az összetett kulcsos kapcsolat ezt lehetőség szerint DB-szinten védje.

A claim az egész ügy lezárásáig él, akkor is, ha egy terméket már visszaadtak.
Csak ügyzárás törölheti. Az üzleti történet a tagságban, mozgásokban és
állapottörténetben marad. Ez hordozhatóbb alap, mint MySQL-ben nem általánosan
használható feltételes egyedi indexre építeni az aktív állapotok listáját.

### 6.3. Végrehajtás, munkaidő, anyag, vizsgálat

| Entitás                        | Kulcsok, adatok                                                                                                                                                       | Integritás és történet                                                                                                                                                      |
| ------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `repair_operations`            | `repair_order_id`, `attempt_number`, `sequence_number`, `operation_type_id`, műveleti utasítás/QC-követelmény pillanatképe, státusz, `started_at`, `finished_at`      | Egyedi `(repair_order_id, attempt_number, sequence_number)`; a megkezdett művelet nem írható át. El nem kezdett művelet jogosultan, verziózottan módosítható a 7.3 szerint. |
| `repair_operation_assignments` | `repair_operation_id`, `employee_id`, szerep és hozzárendelési idő                                                                                                    | Egyedi `(repair_operation_id, employee_id)`; több dolgozó támogatott.                                                                                                       |
| `repair_time_entries`          | `repair_operation_assignment_id`, `started_at`, nullable `finished_at`, korrekciós indok és előzményhivatkozás                                                        | Egy munkaszakasz egy sor; a szünet lezárja, folytatás új sort nyit. Tényleges idő a lezárt szakaszok összege, nem a művelet teljes átfutása.                                |
| `repair_materials`             | `repair_order_id`, nullable `repair_operation_id`, `item_id`, nullable `item_batch_id`/`item_instance_id`, mennyiség, mértékegység, műveleti cél, `stock_movement_id` | Egy fogyasztási könyvelés egy sor, egyedi mozgás-FK; mennyiség pozitív, serialos komponensnél 1. A még tervezett anyag az ajánlat/igény sora, nem tényleges fogyasztás.     |
| `repair_inspections`           | `repair_order_id`, `attempt_number`, nullable `repair_operation_id`, `inspection_type`, `checked_by` -> `employees`, eredmény, időpont, jegyzet, bizonyítékok         | `diagnostic` és `final_quality` külön típus; eredmények append-only; több sikertelen és követő ellenőrzés tárolható.                                                        |

Az assignmenthez egyidejűleg egy nyitott időszakasz engedett; ezt zárolás és
egyedi aktívszakasz-kulcs védi. Ugyanazon assignment szakaszai nem fedhetik
egymást. A több külön munkán párhuzamos időkönyvelés szervezeti szabálya még
nyitott; abból nem vezetünk le nem jóváhagyott globális tiltást.

Nincs külön `repair_attempts` tábla: az új próbálkozás munkalapon belüli,
monoton növekvő sorszám. A korábbi próbálkozás műveletei, ideje, fogyasztása és
QC-je változatlan marad. Nincs automatikus darabszámkorlát. Nincs közvetlen
`ProductionTaskService`-hívás; a meglévő gyártási időpontokat nem használjuk
javítási munkaidőként.

Kiszerelt serialos komponens nem törölhető a genealógiából. A ki-/beépülés
append-only változásként, érintett serialokkal és javítási forrással követendő;
a meglévő `item_instance_relations` helyben felülírása nem elég. Ennek pontos
kapcsolati sémája a részletes anyag-/genealógiaterv része.

### 6.4. Garancia és ajánlat

| Entitás                      | Kulcsok és adatok                                                                                                                                         | Miért szükséges?                                                                                                                                                |
| ---------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `product_handover_evidences` | `item_instance_id`, `recipient_customer_id`, `handed_over_at`, referencia, bizonyíték-dokumentumverzió, `verified_by`, `verified_at`                      | Jelenleg nincs igazolt serialos átadási adatforrás; nem pótolható rendelési dátummal. Minden átadás külön bizonyíték, a minősítés kiválasztja az alkalmazottat. |
| `warranty_assessments`       | `repair_order_id`, verzió, minősítés, `assessed_by`, időpont, indok, nullable átadási bizonyíték, időtartam-/szabálypillanatkép, felülbírálási indok      | Manuális döntések és későbbi felülbírálások külön, változatlan sorok. Egyedi `(repair_order_id, version)`.                                                      |
| `repair_quotations`          | `repair_order_id`, verzió, státusz, pénznem, érvényesség, benyújtási idő, elfogadott tartalom ujjlenyomata, ügyfélválasz szereplője/időpontja/bizonyítéka | Egyedi `(repair_order_id, version)`; a benyújtott verzió nem szerkeszthető.                                                                                     |
| `repair_quotation_items`     | `repair_quotation_id`, sorszám, `line_type`, nullable `item_id`, leírás, mennyiség, mértékegység, egységdíj                                               | Egyedi `(repair_quotation_id, line_number)`; anyag, munkadíj, járulékos díj, diagnosztika és jóváírás.                                                          |

A garancia időtartama cikkszinten konfigurálható, explicit egységgel és
hatályossággal. A minősítés az alkalmazott beállítást megőrzi; későbbi cikktörzs-
módosítás nem változtatja meg a történeti döntést. Az időtartam-konfiguráció
verziójának tárolási módja a 14. fejezetben marad nyitott.

Az aktuális garanciális döntést az adott munkalap és tárgyalt serial
szűrésében legnagyobb, zároltan kiosztott verzió adja; a global order-version
egyedisége megmarad.
A munkalap nem tárol második, önállóan szerkeszthető garanciastátuszt.
D4 szerint csere nem indít automatikusan új időszakot: az eredeti és a
cserepéldányhoz kapcsolt garanciadöntések, szabályverziók és átadási bizonyítékok
külön serialhoz kötötten megmaradnak, a 6.6 szerinti csereláncon lekérdezhetők.
A konkrét jogszabályi/szerződéses szabály értelmezése nem a serialkapcsolatból
következik; annak igazolt alkalmazását a rendszer dokumentálja. Az aktuális
ajánlat szintén a legutolsó verzió; új verzió megnyitása letiltja a korábbi
ajánlat további munkára használatát, de az addigi munka bizonyítéka megmarad.

Pénzügyi érték fix pontosságú decimális összeg vagy pénzegységben tárolt egész
legyen, nem float. A végleges pontosság, kerekítés és nettó/bruttó kezelés még
technikai specifikációt igényel. Számla, követelés és általános fizetettségi/elszámolási alrendszer nem
része az induló modellnek. D7/AM-03 szerint konkrét kreditdöntéshez settlement
observation és annak bizonyítéka szükséges; ez nem általános pénzügyi státuszmodell.

D6/AM-03 szerint a verified diagnostic fee önálló, ajánlatverziótól független
identitás. A részletes terv `repair_diagnostic_fees` és
`repair_diagnostic_credit_allocations` táblákat javasol; a végleges oszlopok,
same-order composite FK-k és active-source egyediség C2 tárgya.
Draft/submitted quote csak becslést tárolhat, effektív kreditet approved foglal.
A díjszabás, a verified fee, a business allocation és a settlement observation
külön fogalom; egyik sem bizonyít automatikusan számlázást vagy rendezést.

### 6.5. Őrzés, kiadás, végső rendelkezés és történet

| Entitás                   | Kulcsok és adatok                                                                                                                                                                                                                                                                                                             | Korlát                                                                                                                                                                                                                         |
| ------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `repair_custodies`        | `item_instance_id` egyedi FK, `repair_request_item_id` egyedi FK, `location_id`, `last_stock_movement_id`                                                                                                                                                                                                                     | Kizárólag az eredeti ügyféltulajdonú visszáru aktuális belső őrzésének mozgásból épülő projekciója. A serial egyezik a tagságéval. Csere-előkészítés nem hoz létre ilyen sort.                                                 |
| `repair_releases`         | `repair_request_item_id`, `released_item_instance_id`, nullable egyedi `stock_reservation_id`, `recipient`, `released_at`, szállítási referencia, kiadási bizonyíték, végállapot, egyedi `stock_movement_id`; nullable `handed_over_at`, átadási bizonyíték, tulajdonátszállási eseményhivatkozás; szerződéses feltételverzió | A `released_at` a fizikai kiadás, a `handed_over_at` a bizonyított átadás. Utóbbi és a tulajdonátszállás bizonyítéka később is érkezhet; a kiadás ilyenkor nem teljesült átadás. Eredeti és cserepéldány kiadása külön rekord. |
| `repair_dispositions`     | Egyedi `repair_request_item_id`, típus, időpont, `authorized_by`, indok és bizonyíték, nullable cserefoglalás-/cserepéldány-/kiadáshivatkozás; csere esetén kötelező `original_outcome` és annak bizonyítékai                                                                                                                 | Az eredeti termék `returned_unrepaired` vagy `scrapped` sorsa csere mellett is explicit. A `replaced` eredmény csak a csere átadása és az eredeti termék független végső sorsa után végleges.                                  |
| `repair_status_histories` | Nullable `repair_request_id` vagy `repair_order_id`, `state_dimension`, régi/új állapot, actor, indok, idő, command; nullable `stock_reservation_id`, `repair_release_id`, `superseded_operation_id`, változtatás előtti/utáni becslés és döntési hivatkozás                                                                  | Pontosan egy ügy-/munkalapcél-FK; a kapcsolt foglalás/kiadás ugyanahhoz a munkalaphoz és termékhez tartozik. Append-only, index cél+idő szerint.                                                                               |

A custody-projekció megszüntetése nem történettörlés: a mozgásokból újraépíthető.
A claim is technikai aktívfoglalás; a többi történeti rekord nem törölhető
kaszkáddal. Ügy, tagság, munkalap, idő, anyag, QC, ajánlat, kiadás és bizonyíték
korrekciója indokolt, append-only esemény; a későbbi átadási igazolás a kiadási
rekordhoz kapcsolódó új tény, nem korábbi időpontok felülírása.

A `pending_decision` nem végső disposition. A végső típusok: `repaired_returned`,
`returned_unrepaired`, `replaced`, `scrapped`; át nem vett, visszavont termékhez
`cancelled_before_receipt`, dokumentált indokkal és bizonyítottan nulla őrzéssel.
A beérkezés utáni megszakítás önmagában nem végső rendelkezés.

AM-01/02/04 szerint a release a fizikai shipment identitása, a return intake
és admission külön kapcsolt tény, a delivery/ownership pedig külön esemény.
A részletes terv ezekhez release/delivery/ownership historyt és occurrence
kulcsokat javasol. A korábbi logikai nullable handover mezők nem jelentik
a release utólagos felülírásának engedélyét. Reversal kötelezően original-event
és proof hivatkozással új immutable sor; a supersedes korrekció külön kapcsolat.
Replacement karantén nem eredeti repair_custodies sor; tényleges helye közös
movement/instance projekcióból és return evidence-ből rekonstruálható.

### 6.6. Cserefoglalás és előkészítés: meglévő infrastruktúra bővítése

Nincs szükség külön `replacement_reservations`, `replacement_preparations`
vagy `replacement_items` üzleti táblára. Javasolt a meglévő `StockReservation`
bővítése: `repair_order_id` FK, `reservation_purpose` (`repair_material` vagy
`replacement`), nullable `item_instance_id` FK és nullable egyedi
`active_item_instance_id` FK. A jelenlegi migráció még nem tartalmaz serialos
foglalást: [stock_reservations](../../database/migrations/2026_06_21_000024_create_stock_reservations_table.php).
Ez tervezett változás, nem már használható képesség.

Cserefoglalásnál `item_instance_id`, hely, cikk és `repair_order_id` kötelező,
`reserved_quantity=1`. Aktív serialfoglalásnál az `active_item_instance_id`
egyezik a serial-FK-val, terminális foglalásnál NULL. A nullable UNIQUE kulcs
csak egy aktív foglalást enged ugyanarra a példányra minden cél összességében;
ezt CHECK és service-guard is védi. Egy munkalaphoz legfeljebb egy nyitott
cserefolyamat tartozik, munkalapzárral és nullable egyedi `active_replacement_order_id`
kulccsal védve. Lezárt/törölt foglalás nem használható újra: új kiválasztás új sor.

Az igénytulajdonos javítási munkalap, vagy meglévő vevői/gyártási igény; a
korábbi, összetartozó vevői és gyártási hivatkozás együttes jelenléte megmaradhat.
Javítási és nem javítási igénytulajdonos nem keverhető. A cikk, batch/serial és
hely összetartozása kötelezően ellenőrzött. A pontos hordozható FK/CHECK-megoldás
implementációs kapu, nem pusztán application-level szándék.

A csere állapotai a foglaláshoz kötött `replacement` dimenziójú történetből
származnak; helyváltozás a `StockMovement` `source` kapcsolatán a foglalásra
hivatkozik. A történet és a mozgás `RESTRICT`-tel védett kapcsolat; foglalás
hard delete-je tilos. Előkészítési státuszból nem következik tulajdonváltás.
A fizikai kiadási rekord egyedi foglalás-FK-ja és egyedi mozgás-FK-ja biztosítja,
hogy ugyanaz a fizikai shipment egyszer vezethető ki. AM-01 szerint egy
logikai cserekötelezettséghez több valódi shipment tartozhat; új attempthez
új reservation kell, az előző fulfillment nem aktiválható újra. A foglalás
terminális oka `fulfilled_by_release` történeti esemény, nem javítási anyagfogyasztás.
Shipment identity, stabil üzleti occurrence és command/movement egyediség együtt
védi a duplázást; új commandkulcs nem jelent új fizikai shipmentet.

A helyek és foglalások újrafelhasználása csak az összes érintett fogyasztó
átállítása után biztonságos. A meglévő normál fogyasztó nem von le minden
aktív foglalást a kiválasztáskor, ezért a cserefoglalás önmagában még nem védelem.
Termelési fogyasztásnak, értékesítési allokációnak, más foglalásnak és RMA-
alkalmasságnak is zárolás alatt tiszteletben kell tartania a serialfoglalást,
az egyenleghez tartozó összes aktív lekötést és az elkülönített előkészítési helyet.

A foglalás tartósan összeköti az eredeti `repair_order -> repair_request_item
-> ItemInstance` láncot a külön csere-`ItemInstance` rekorddal. Minden
kiválasztás, előkészítés, visszavonás, kiadás, átadás és tulajdonátszállás ehhez
az immutábilis serialpárhoz tartozó esemény. A visszavont próbálkozás is megmarad;
az új serialhoz új foglalás tartozik. A történet mindkét serial oldaláról
lekérdezhető, az eredeti gyártás vagy RMA áthelyezése nélkül.

Az alkalmazott manuális garanciadöntés, ajánlat és átadási bizonyíték konkrét
verzióhivatkozással kerül a csereeseményhez. A csere nem nulláz, hosszabbít vagy
indít újra automatikusan garanciát; a cserepéldányra alkalmazott döntés külön
bizonyítékos esemény. Ezt az üzleti/jogi garanciapolicy határozza meg, nem a
serialkapcsolat vagy a szállítási dátum.

## 7. Életciklusok, átmenetek és guardok

### 7.1. RMA-ügy

`open -> closed`; az ügy létrehozása atomikusan legalább egy jogosult terméket
és munkalapot hoz létre. Nincs külön mentett `all_repaired` vagy `partly_received`
állapot: ezek a tételekből számított jelzők. Új termék csak `open` ügybe vehető
fel a teljes alkalmassági és claim-ellenőrzéssel.

A `close` nem kizárólag státuszmezőket vizsgál, hanem az alábbi kapcsolt
bizonyítékokat ugyanazon tranzakcióban ellenőrzi:

1. Minden tagsághoz végleges, jogosultan rögzített disposition tartozik; csere
   esetén az eredeti termék független `original_outcome` bizonyítéka is megvan.
2. A beérkezési, belső mozgatási, kiadási vagy selejtmozgások léteznek,
   serial/mennyiség/hely/hatókör szerint egyeznek és nem vonták vissza őket.
   Custody-projekció hiánya önmagában nem bizonyít végső kiadást.
3. Az eredeti visszáruhoz nincs rendezetlen custody, mozgatás vagy átadás.
4. Minden cserefoglalás vagy igazolt visszavonással lezárt, vagy az adott
   shipment egyszeri fizikai kiadásával teljesített. A logikai kötelezettség
   összes attemptje/returnje rendezett; nincs nyitott előkészítés, serialfoglalás,
   karantén, pending-admission, úton lévő vagy bizonyíték nélküli átadás,
   függő tulajdonátszállási kötelezettség.
5. A címzett, kiadási/átadási dátum, referencia, végállapot és a szerződéses
   feltételek szerinti kötelező bizonyítékok érvényes dokumentumverziói léteznek.
6. Nincs aktív javítási művelet, nyitott időszakasz, rendezetlen javítási
   anyagfoglalás, igény vagy fogyasztás; megszakított munka maradványai is rendezettek.
7. A végső eredményhez szükséges javítási/csere-/selejtjogosultság, garanciadöntés,
   fizetős munka ügyféljóváhagyása és az adott eredményhez kötelező QC bizonyított.
   Javítás nélküli visszaadás nem kényszerít valótlan sikeres javítási QC-t.

Az ügy sorzárát minden gyermekművelet is kéri; a releváns példányok, munkalapok,
foglalások, kiadások és bizonyíték-hivatkozások ellenőrzése konzisztens sorzárak
mellett történik. Lezárás, történet, audit és claimfeloldás egy commit; bármely
hiány vagy írási hiba mindet visszagörgeti. Külső bizonyíték ellenőrzése nem
nyitott DB-tranzakcióban történő hálózati kérés: előre ellenőrzött, megőrzött
hivatkozás szükséges, a 10. fejezet szerint.

A lezárás egyszeri, terminális; új probléma új RMA, nem történeti ügy újranyitása.
Csak ez oldja fel az eredeti termékek aktív claimjeit. Javítás elkészülte vagy
csere lefoglalása/előkészítése egyik guardot sem helyettesíti.

### 7.2. Egyedi visszaküldött termék

A termék életciklusa nem újabb önálló státuszoszlop: a munkalap, custody és
disposition együtteséből számított állapot, ugyanazon termékazonossággal.

| Számított állapot  | Feltétel / megengedett következő esemény                                                                  |
| ------------------ | --------------------------------------------------------------------------------------------------------- |
| `awaiting_return`  | Tagság és claim létezik, még nem érkezett be; átvétel vagy dokumentált átvétel előtti visszavonás.        |
| `received`         | Első átvételi mozgás és custody létezik; diagnosztika indítható.                                          |
| `under_assessment` | Diagnosztika/garancia/ajánlat döntése függő; javítás nem indul automatikusan.                             |
| `under_repair`     | Munkalap `ready`, `repairing` vagy `quality_control`; a részletes állapot a munkalapé.                    |
| `ready_for_return` | `completed` munkalap vagy engedélyezett javítás nélküli visszaadás/csere, minden kiadási guard teljesül.  |
| `disposed`         | Végső rendelkezés teljes és az eredeti termék őrzése lezárult; az aktív claim az ügy lezárásáig megmarad. |

A kiválasztott, de még végre nem hajtott `replacement`, `return_unrepaired` vagy
`scrap` döntés a munkalap dokumentált döntési eseménye; nem végső disposition.
Selejtig az engedélyezett tárolás és helyváltoztatás követett marad.

### 7.3. Javítási végrehajtás

`awaiting_receipt -> assessment -> awaiting_authorization -> ready -> repairing
-> quality_control -> completed`.

- `assessment -> awaiting_authorization`: diagnózis rögzített; garancia és
  szükség esetén az ajánlat még elbírálás alatt.
- `awaiting_authorization -> ready`: `warranty` indokolt döntés vagy
  `non_warranty` és aktuális, nem lejárt, ügyfél által jóváhagyott ajánlat;
  műveletterv és szükséges készlet biztosított. `pending_assessment` nem elég.
- `ready -> repairing`: a termék őrzésben, jogosult dolgozó hozzárendelve,
  műveleti sorrend és jóváhagyott munkatartalom megfelelő.
- `repairing -> quality_control`: minden szükséges művelet és időszakasz lezárt,
  fogyasztások könyveltek; új munkatartalomhoz az ajánlati gate újraértékelendő.
- `quality_control -> completed`: az aktuális próbálkozás kötelező vég-QC-je
  elfogadott. Korábbi próbálkozás sikeres eredménye nem használható helyette.
- `quality_control -> ready`: hibás/rework QC után jogosult újrapróbálkozási
  parancs, új `attempt_number`, új műveletek, újraellenőrzött ajánlati fedezet.
- `assessment`/`awaiting_authorization -> stopped`: nem javítható, elutasított
  ajánlat vagy engedélyezett megszakítás; végső rendelkezés ettől még szükséges.
- `ready`/`repairing`/`quality_control -> stopped`: indokolt megszakítás, lezárt
  időszakaszok, könyvelt tényleges anyagok és rendezett foglalások mellett.

Engedélyezett műveletterv-módosítás:

- El nem kezdett (`planned`/`ready`, `started_at` nélküli és tényleges idővel,
  fogyasztással vagy inspectionnel még nem érintett) művelet jogosultan
  módosítható. A régi műveletet `superseded` esemény jelöli; utódművelet új
  sorban, `supersedes_operation_id` nullable self-FK-val és új sorszámmal készül.
  A régi sorszám nem használható újra, a becslés, terv és döntés megmarad.
- Megkezdett/befejezett művelet eredeti utasítása, becslése, időadata és eredménye
  nem írható át. További feladat új művelet; indokolt megszakítás eseményként
  történik, az addigi tényleges munka lezárásával.
- `repair-orders.plan` permission, munkalapzár és aktuális terv-/ajánlatverzió
  ellenőrzése szükséges. A változtatás és indoka a történetbe és auditba kerül.
- Minden scope-változás újraértékeli a jóváhagyott ajánlatot. Kereskedelmi keret
  túllépése vagy lényeges tartalmi eltérés új ajánlatot és ügyféljóváhagyást
  követel. `ready`/`repairing -> awaiting_authorization` engedett; a korábbi
  műveleti státuszok és időszakaszok nem állnak vissza.
- A függő engedély alatt új/felfüggesztett munka nem indítható és anyag nem
  fogyasztható. A már futó munka csak dokumentált biztonságos megállításig
  zárható; ez nem általános továbbdolgozási engedély. Jóváhagyás után a még
  szükséges tervlépések kerülnek `ready` állapotba.

A `completed` és `stopped` a munkalap végrehajtási terminális állapotai, nem
ügyzárás. Újrapróbálkozás QC-hiba esetén a lezárás előtt történik. A műveletek
saját állapotai `planned -> ready -> in_progress -> completed`, illetve
indokolt `cancelled`; az elkezdett és lezárt adatok korrekciója append-only.

### 7.4. Fizikai őrzés

Az állapot a mozgásokból származik: `not_received -> in_custody -> released`
vagy `in_custody -> scrapped`. Belső áthelyezés `in_custody` mellett helyet
változtat. A megszüntetett őrzés ugyanazon tagság alatt nem vehető újra át;
új ügy csak a korábbi lezárása után lehetséges.

Átvétel: ügytagság, beolvasott serial, aktuális életciklus, korábbi bevételezés
és aktív claim ellenőrzése; pontosan egy bejövő, 1 darabos mozgás. Belső
helyváltoztatásnál egyező kiinduló hely és custody kötelező.

Kiadás: rögzített címzett, dátum, referencia, bizonyíték és végső állapot;
nincs kötelezően függő QC vagy engedély. A kiadási rekord és kimenő mozgás
ugyanabban a tranzakcióban megszünteti a custody-projekciót. Selejtnél külön
jogosultság, dokumentált felhatalmazás és mozgás kell; nem elég a státusz átírása.

A fenti custody-átmenetek az eredeti visszárura vonatkoznak. A vállalati
cserepéldány előkészítését, kiszállítását és átadását a 7.7 kezeli; annak
előkészítése nem `in_custody` állapot.

### 7.5. Garancia

Kezdeti `pending_assessment -> warranty | non_warranty`; későbbi minősítésváltás
vagy visszavétel `pending_assessment` állapotba kizárólag új verziójú, indokolt,
jogosult döntés. A korábbi döntés nem írható felül.

A kezdőesemény igazolt termékátadás, nem rendelés-létrehozás vagy gyártási
befejezés. Bizonyítékhiány `pending_assessment`; eltérés csak külön override
jogosultsággal, indokkal és bizonyítékkal. A minősítés manuális marad, a
konfigurációból számított időszak csak döntéstámogatás. D4 alapján a csere
eseménye nem indít automatikusan új garanciát. Az eredeti és a cserepéldány
korábbi döntései változatlanok; az alkalmazandó jogszabályi és szerződéses
feltételek szerinti új döntés külön, indokolt és bizonyítékos esemény.
E feltételek konkrét beállítása implementációs előfeltétel, nem nyitott
architekturális kérdés vagy a rendszer által helyettesíthető jogi döntés.

### 7.6. Javítási ajánlat és diagnosztikai díj

`draft -> submitted -> approved | rejected | expired`; a benyújtott verzió
újabb verzióval `superseded` lehet. Az `approved`, `rejected`, `expired` és
`superseded` tartalmilag lezárt verziók; módosítás új verzió.

A benyújtás guardjai: pénznem, teljes tételsor, anyag-/munka-/járulékos díjak,
lejárat és diagnosztikai feltételek. A jóváhagyás konkrét verzióra/tartalomra,
azonosított ügyfélre és bizonyítékra szól. Kezdetben jogosult belső dolgozó
rögzíti a külső jóváhagyást; ez nem belső jóváhagyás ügyfélválasz helyett.

Lejárt vagy elutasított ajánlat nem indíthat fizetős javítást. Az indulási guard
időpontban is ellenőrzi a lejáratot, nem hagyatkozik egy időzített jobra.
Elfogadás után új tartalom vagy a jóváhagyott keret túllépése új ajánlatot és
ügyféljóváhagyást igényel. Futó művelet ilyenkor biztonságosan lezárható, de új
nem kezdhető a fedezet rendezése előtt.

A `diagnostic_fee_mode` értéke `free`, `fixed` vagy `time_based`; az alkalmazott
díj és a tényleges idő bizonyítható. Fizetős diagnosztikához is előzetesen
rögzített ügyfélhozzájárulás szükséges. Ez csak a diagnosztikát engedélyezi,
nem a későbbi javítást. A beszámítás külön, az eredeti díjra hivatkozó
`diagnostic_credit` ajánlati sor; egyszer alkalmazható, legfeljebb az igazolt
beszámítható díjig. Ingyenes diagnosztikára nincs mesterséges jóváírás.

D6/D7 szerint az approved quote foglalását source-order/fee zárolás és DB-egyediség
védi; a credit nem haladhatja meg az igazolt fee-t, más munkalaphoz nem tartozhat.
Új draft vagy supersede nem oldja a korábbi allocationt. Release előtt execution
és settlement evidence ellenőrzött; unused/unsettled állapotban jogosult auditált
release, settled állapotban külön correction szükséges. Unknown nem unsettled;
hiányos proofnál nincs automatikus felszabadítás. Az előzményverzió és allokáció
nem törölhető vagy írható át új felhasználás érdekében.

### 7.7. Cserepéldány foglalása, előkészítése, átadása

AM-01 szerint az alábbi alapeset egy fizikai shipmentre vonatkozik; nem az
összes valódi újraküldést tiltó, egyetlen logikai cserefolyamatra.
`reserved -> prepared -> dispatched -> handed_over`; helyi átadásnál
`prepared -> handed_over` egy atomikus kiadási paranccsal. A `reserved` és
`prepared` nem kész csere. Mindkettőben vállalati tulajdon és aktív foglalás
marad; helye követett, más célra nem használható.

- Foglalás: saját gyártás, serial, állapot, hely és szabad készlet igazolt;
  nincs aktív RMA/custody vagy más serialfoglalás. Példány- és balance-zár,
  majd egyedi aktív foglalás; nincs fizikai kiadás vagy tulajdonátszállás.
- Előkészítés: a hely és állapot ellenőrzött; ha valóban áthelyezik, vállalati
  belső transfer és a foglalás helyprojekciójának atomikus frissítése történik.
  Ugyanazon helyen előkészítés csak történeti esemény, mennyiségi mozgás nélkül.
- Kiszállítás: kiadási és fuvarozói bizonyíték, címzett, dátum, referencia és
  végállapot szükséges; shipmentenként egyszeri raktári kivezetés. Ha a címzetti átvétel
  még nem igazolt, `dispatched`, nyitott átadási kötelezettséggel. A foglalás
  ekkor csak a már kikönyvelt egység lekötéseként szűnik meg, nem szabadul fel
  készletként. Úton lévő serial új allokációja tiltott.
- Igazolt átadás: a konkrét kiadáshoz kötött új bizonyíték és időpont,
  `handed_over`; nincs második egyenlegcsökkentés. Tulajdonátszállást kizárólag
  az igazolt szerződéses policy eseménye vált ki, nem ez az állapot önmagában.
- `reserved`/`prepared -> cancelled`: csak jogosult parancs, tényleges hely,
  sértetlenség és szabad készletre való alkalmasság ellenőrzésével. Szükség
  esetén valós belső visszamozgatás; a foglalás csak ezekkel egy tranzakcióban
  oldható fel. Sikertelen ellenőrzéskor `cancellation_pending`, a foglalás él,
  a termék elkülönített és az ügy nem zárható.
- Kiszállítás után, de igazolt átadás előtt jogosult megszakítási igény
  `cancellation_pending` lehet, de a fizikai kiadás nem törölhető vagy
  visszadátumozható. Visszahívás után csak igazolt valódi visszaérkezés,
  kötelező karantén, szerződéses tulajdonellenőrzés és alkalmassági vizsgálat
  rendezheti a folyamatot. Az intake és a későbbi admission külön egyedi esemény,
  admission nem második fizikai receipt (AM-02-G4). A tényleges érkezésig transit,
  utána a valós helyén quarantined/pending-admission; unresolved ownership nem
  akadályozhatja az intake rögzítését. Lezárt átadás nem visszavonható előkészítésként.

Hibás fuvarozói válasz vagy sikertelen átadás nem generál fiktív balance-növelést.
Ha a szerződés alapján a tulajdon korábban átszállt, a visszahívás tulajdonosi
rendezése külön, engedélyezett esemény; nem feltételezhető automatikus visszaszállás.
A termék a feltételek rendezéséig nem szabad készlet.

A `handed_over` az adott shipment fizikai átadásának eredménye, a `cancelled`
a kiválasztás terminális eredménye; későbbi igazolt return új tény, nem átírás; az eredeti visszáru egyikből sem kap
végső rendelkezést automatikusan. A cserefolyamat csak a tulajdonosi és átadási
kötelezettségek rendezésével lezárt a 7.1 szerinti ügyzáráshoz.

AM-02 szerint a kötelező return út: failed delivery (önálló, balance0) ->
physical intake+quarantine -> identity/condition/ownership/disposition-right
ellenőrzés -> authorized disposition. Fizikai intake unresolved owner mellett
is követett. Verified admission nem szükségszerű, nem második receipt és nem
automatikus elérhetőség: karanténból további használat csak engedélyezett döntés
után lehet. A tényleges hely, a jogi owner és az allocation állapot külön marad.

D9/AM-01 szerint redispatch csak a korábbi shipment megfelelő rendezése,
igazolt return, identity/condition/right és explicit authorization után lehetséges.
Új, immutable shipment+occurrence+movement és új mennyiségi reservation kell;
régi fulfillment nem nyitható újra. Igazolt custody-disposition-right esetén
redispatch nem követel fiktív company ownershipt vagy reversalt; company balance
ilyenkor nem csökken. A nem-company ág pontos purpose/FK/CHECK-je C2 feladata.
Physical return önmagában D10 szerint nem reversal: dokumentált jogalappal,
jogosult actorral és original-event FK-val új ownership esemény kell.

## 8. Készletstratégiák összehasonlítása

### 8.1. A és B összevetése

| Szempont             | A – integrált inventory                                                                 | B – elkülönített custody, közös mozgások                                                        |
| -------------------- | --------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------- |
| Adatintegritás       | Egy közös egyenlegmodell; tulajdon/állapot dimenzió és minden írási út átállítása kell. | Egyedi serialos custody-projekció; mozgásból újraépíthető, nem második elsődleges ledger.       |
| Meglévő architektúra | A jelenlegi cikk/hely/batch egyenleg és kulcsok mély módosítása.                        | A normál egyenleg jelentése megmarad; mozgáskönyvelés és összesítések explicit hatókört kapnak. |
| MRP és nettósítás    | Minden számításnak központi használhatósági szűrőt kell alkalmaznia.                    | A visszáru eleve nincs a normál egyenlegben; a javítási anyagigény ettől külön nettósítható.    |
| Foglalás             | Tulajdon és serial figyelembevételével minden foglalási út átírandó.                    | Visszáru nem normál foglalási jelölt; a javítás saját anyagokat foglal normál készletből.       |
| Gyártási fogyasztás  | Egy kihagyott szűrő ügyfélterméket fogyaszthat el.                                      | Nincs fogyasztható balance; emellett példányszintű custody-guard is szükséges.                  |
| Riportok             | Egységes adatforrás, kötelező dimenzióhelyes összesítés.                                | Külön vállalati készlet és ügyféltulajdonú őrzés; mozgásriport scope-pal.                       |
| Traceability         | Közös mozgáslánc, tulajdonosi eseményekkel.                                             | Ugyanaz a mozgáslánc, RMA-forrással és custody-projekcióval.                                    |
| Tranzakció           | Több általános invariáns és közös írási felület.                                        | Átvétel/kiadás lokálisabb; csere két hatókör összehangolt tranzakciója.                         |
| Migrációs kockázat   | Magas: meglévő egyenlegek, nullable batch-kulcs, minden fogyasztó.                      | Kisebb: új custody, de scope-backfill és mozgásriport-vizsgálat itt is kötelező.                |
| Bővíthetőség         | Hosszabb távon egységes tulajdonosi készletmodell alapja lehet.                         | Jó induló szervizmodell; későbbi közös inventoryhoz explicit migráció kell.                     |

### 8.2. Elfogadott készletstratégia és mozgási szerződés

**B elfogadott az eredeti visszárura (D1).** A `repair_custodies` kizárólag annak
`StockMovement` eseményekből épülő, újraépíthető aktuális őrzési projekciója,
normál `StockBalance` növelése nélkül. A cserepéldány foglalása és előkészítése
közben a normál vállalati készletben marad, aktív serialfoglalással és követett
hellyel; nincs `company_stock -> customer_custody` előkészítési könyvelés.

A mennyiségi hatókör és jogi tulajdon külön dimenzió. A `company_stock`,
`customer_custody`, `external` a készletkönyvelési oldalakat jelöli; az
`external` nem mondja meg a tulajdonost. AM-02 szűk pending-admission kivételében
external könyvelési oldalon belső karanténhelyen is lehet a példány; a fizikai
helyet, a jogi tulajdont és a disposition/allocation állapotot külön követjük. Az úton lévő, de még vállalati tulajdonú
cserepéldány külön kimutatható a kiadás és a hiányzó tulajdonátszállási esemény
alapján, nem szabad készlet és nem ügyféltulajdonú repair-custody.

| Üzleti esemény                                            | Forrás -> cél hatókör                                                                | Vállalati helyi mennyiség / foglalás / tulajdon                                                                                                                 |
| --------------------------------------------------------- | ------------------------------------------------------------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Eredeti RMA-átvétel                                       | `external -> customer_custody`                                                       | Normál balance 0; ügyféltulajdon megmarad.                                                                                                                      |
| Eredeti visszáru belső áthelyezése                        | `customer_custody -> customer_custody`                                               | Normál balance 0; custody helye változik.                                                                                                                       |
| Eredeti visszaadás/engedélyezett selejt                   | `customer_custody -> external` megfelelő jogcímen                                    | Normál balance 0; őrzés megszűnik, önmagában nem állít vállalati tulajdonszerzést.                                                                              |
| Javítási anyag fogyasztása                                | `company_stock -> external` fogyasztási jogcímen                                     | Normál balance negatív a tényleges mennyiséggel; saját anyag felhasználása.                                                                                     |
| Csere lefoglalása                                         | Nincs fizikai mozgás                                                                 | Balance 0 változás; aktív serialfoglalás +1, szabad mennyiség -1; vállalati tulajdon.                                                                           |
| Csere előkészítése másik belső helyre                     | `company_stock -> company_stock` valós transfer                                      | Forráshely -1, célhely +1, összesen 0; ugyanaz a foglalás követi, szabad mennyiség összesen nem nő; vállalati tulajdon.                                         |
| Csere előkészítése ugyanazon helyen                       | Nincs fizikai mozgás                                                                 | Balance és foglalás változatlan; történeti esemény, vállalati tulajdon.                                                                                         |
| Csere kiszállítása vagy azonnali helyi átadása            | `company_stock -> external` egyszeri kiadás az adott shipmentre                      | Helyi balance -1, foglalás teljesítettként megszűnik: szabad mennyiség nem nő. Tulajdonváltozás csak szerződéses esemény alapján.                               |
| Kiszállított csere utólag igazolt átadása                 | Nincs új mennyiségi mozgás                                                           | Balance 0 változás; kiadáshoz kötött bizonyíték. Tulajdonváltozás kizárólag az alkalmazandó policy szerint.                                                     |
| Előkészítés visszavonása kiadás előtt                     | Szükség esetén valós `company_stock -> company_stock` visszatransfer                 | Összes balance 0; foglalás -1 csak igazolt hely/állapot/alkalmasság után, ekkor szabad mennyiség +1.                                                            |
| Replacement physical return-intake                        | Szűk engedélyezett external -> external intake, pending-admission/karantén céllal    | Normal balance 0, igazolt serial/hely/idő/source shipment/proof; minden return quarantined, ownership unresolved is lehet (AM-02-G1–G3).                        |
| Verified company-stock admission                          | Ugyanazon returnhez external -> company_stock, teljesülő befogadási követelményekkel | Physical balance +1 egyszer; nem második fizikai receipt, karantén nem oldódik automatikusan. Authorized disposition kell a felhasználhatósághoz (AM-02-G2/G4). |
| Igazolt nem-company admission / custody-rights redispatch | Igazolt custody könyvelési oldal; redispatchkor customer_custody -> external         | Normal balance 0; disposition right és D9 authorization, nincs automatikus ownership reversal vagy original custody-sor.                                        |
| Karantén feloldása / végső rendelkezés                    | Valós jogcím és szükség szerint mozgás, nem fiktív receipt                           | Identity/condition/ownership-right/previous shipment és jogosultság ellenőrzött; unresolved állapot nem available.                                              |

D5 szerint a fizikai kiadás, a fuvarozói átvétel, a címzetti átvétel és a jogi
ownership-transfer eltérő időpontok lehetnek. Azonos időpont is csak igazolt
alkalmazandó feltételből következhet, automatikus azonosság nincs. A kötelező `handover_policy`
verzió, szerződés-/feltételhivatkozás, trigger és szükséges bizonyíték a
cserefolyamat pillanatképe. Jogosult, ellenőrzött szabály írja elő az eseményt;
a rendszer nem talál ki jogi tulajdonátszállási feltételt. Ismeretlen policy
blokkolja a csere kiadását; függő trigger/bizonyíték blokkolja az ügyzárást.
A tulajdonátszállás append-only `ownership` esemény, az érintett serial,
kiadás, korábbi/új tulajdonos, időpont, feltételverzió és bizonyíték hivatkozásával.
Ez nem második fizikai készletmozgás.

D1 szerint a három hatókör elfogadott: `company_stock`, `customer_custody`,
`external`. A fizikai mozgás és a könyvelési oldal AM-02 szerinti intake/admission
különbségét explicit típusszerződés kezeli; a jogi tulajdonváltozás D5 szerint
külön nyilvántartott esemény. A konkrét oszlopok, új mozgástípusok és a régi
típusok mappingje a részletes implementációs terv része.
A régi mozgásokat ellenőrzött mappinggel kell besorolni; ismeretlen típus
blokkol. A custody nem szerepelhet vállalati egyenleg-újraszámításban. A még
vállalati tulajdonú úton lévő egységek külön jelentése nem adható hozzá a
raktári elérhetőséghez. Az ADR-0001 továbbra is érvényes: mennyiségi változást
csak valós mozgás magyarázhat; előkészítési vagy jogi státusz nem helyettesíti.

### 8.3. Dupla számlálás kizárása

1. Egy serialhoz legfeljebb egy aktuális custody-sor tartozhat; minden
   példányszintű mozgatás ugyanazt az `ItemInstance` sort zárolja.
2. Original átvétel és replacement return-intake nem növel normál egyenleget,
   nem hoz létre új példányt. Későbbi igazolt company admission egyszer növelhet
   normál physical balance-t, de nem ismételheti meg a fizikai receiptet.
3. Company-stock admission csak igazolt korábbi kivezetés és befogadási
   feltételek alapján lehet. Hiányos aggregált outbound/origin vagy ownership
   esetén admission és felhasználás blokkolt, de a tényleges, serialazonosított
   fizikai return-intake AM-02-G1 szerint rögzítendő, karanténban. Nincs
   automatikus „kiegyenlítő” korrekció vagy vélelmezett company tulajdon.
4. A vállalati mozgási és fogyasztási parancsok elutasítják a custodyban lévő
   serialt; mennyiségi aggregátumból nem lehet egyedi tulajdont kitalálni.
5. `physical_on_site = company_stock_on_site + customer_custody_on_site + pending_admission_on_site`
   egymást kizáró példánybesorolással, külön feliratozott fizikai riportban
   jelenhet meg. A pending intake tulajdona lehet unresolved; az összeget
   szabad készlet, MRP, értékelés vagy sales-availability nem használhatja.
   Quarantine physical mennyiség nem available és nem vonható le kétszer.
6. Egy company-stock shipment egyszer lép ki a helyi vállalati készletből a
   tényleges kiadáskor, nem előkészítéskor; későbbi átadási igazolás vagy ownership-esemény nem von
   le újabb mennyiséget. Az úton lévő példány és a foglalás feloldási oka
   megakadályozza a visszaallokálást. Az eredeti termék nem válik automatikusan
   vállalati tulajdonná; visszaadása/selejtje függetlenül bizonyított.
7. Előkészítéskor a transfer után a foglalás új helyre mutatása ugyanazon
   tranzakció része. Másik fogyasztó nem láthat szabad célhelyi készletet a
   foglalás áthelyezése előtt. A sikertelen visszavonás nem hagyhat aktív
   történeti előkészítéshez tartozó, de gazdátlan vagy tévesen feloldott foglalást.

### 8.4. Normál javítási anyagok és beszerzés

A javítási anyagok `company_stock` állományból származnak. A foglalás kapjon
explicit javítási tulajdonost; a meglévő `StockReservation` javasolt nullable
`repair_order_id` kapcsolata a 6.6 szerinti igénytulajdonosi korlátot kövesse.
Nem készül fiktív `ProductionOrder` vagy `CustomerOrderItem`.

A fogyasztás ellenőrzi a munkalap, foglalás, cikk, batch, serial, hely és
hátralévő mennyiség összetartozását. Részfogyasztás csak a tényleges részt oldja;
mások aktív foglalását nem fogyaszthatja el. A `repair_materials` rekord és a
mozgás ugyanabban a tranzakcióban készül, az egyenlegprojekcióval együtt.

Hiányhoz javasolt a meglévő `MaterialRequirement` igénytulajdonosának általánosítása:
nullable `customer_order_item_id` vagy `repair_order_id`, pontosan az egyik
kitöltve, tulajdonos+cikk szerint megfelelő egyediséggel. A beszerzési
`purchase_requisition_item_sources` így továbbra is valódi anyagigényhez köt.
Az igénylést a javítási anyagbecslésből kell számítani, nem a gyártási BOM
szolgáltatását meghívni. Azonos hiány újraszámítása nem generálhat dupla beszerzést.

D11/AM-04 alapján a demand, a procurement commitment és a goods receipt önálló
életciklus. Demand-only withdraw kontrollált; draft PR review-val módosítható,
approved PR jogosult review-t igényel. Kiküldött vagy supplier-ack PO nem
törölhető/cancelled csendben RMA-cancel miatt. Coverage release/reassign auditált,
forrástörténet megmarad; received supply csak valós receipt, ownership és
inventory tény szerint kezelhető. A source rekord törlése nem regenerálási guard.

A részletes terv `procurement_coverage_events` ledgerrel javasolja a részleges
coverage, demand revision, commitment és receipt közötti history megőrzését.
C2-ben source/commitment zár alatti conservation és delta-only generálás,
stable generation identity, részreceipt és reassignment specifikáció kell.
A received stockot nem szabad open coverage-ként még egyszer nettósítani.
A PR-line direct requirement pointer nem második demand a sources mellett.

## 9. Biztonság és auditkövetelmények

Javasolt policyk: `RepairRequestPolicy`, `RepairOrderPolicy`,
`RepairQuotationPolicy`, `WarrantyAssessmentPolicy`. Az alábbi permissionök
újak, nincsenek seedelve ebben a feladatban.

| Művelet                               | Permission                                                             |
| ------------------------------------- | ---------------------------------------------------------------------- |
| Ügy megtekintése/létrehozása          | `repair-requests.view`, `repair-requests.create`                       |
| Munkalap megtekintése                 | `repair-orders.view`                                                   |
| Átvétel és custody-áthelyezés         | `repair-orders.receive`, `repair-orders.transfer`                      |
| Diagnosztika                          | `repair-orders.inspect`                                                |
| Garanciális döntés/felülbírálás       | `repair-orders.warranty`, `repair-orders.warranty-override`            |
| Ajánlat készítése/benyújtása          | `repair-quotations.create`, `repair-quotations.submit`                 |
| Ügyfélválasz bizonyítékos rögzítése   | `repair-quotations.record-customer-response`                           |
| Javítás belső engedélyezése           | `repair-orders.approve`                                                |
| El nem kezdett műveletterv módosítása | `repair-orders.plan`                                                   |
| Végrehajtás és anyagfogyasztás        | `repair-orders.execute`, `repair-orders.materials`                     |
| QC                                    | `repair-orders.check`                                                  |
| Csere/selejt/megszakítás              | `repair-orders.replace`, `repair-orders.scrap`, `repair-orders.cancel` |
| Kiadás és ügyzárás                    | `repair-orders.release`, `repair-requests.close`                       |

A belső rögzítő aktív Employee-kapcsolata és az engedély egyaránt szükséges.
A szakmai szerep nem permission. A permission nem helyettesíti az állapot-,
ajánlat-, tulajdonosi és QC-guardokat; ezek super-admin esetén is kötelezőek,
függetlenül a meglévő `Gate::before` felülbírálástól.

Listák, serialkeresés, dokumentumfeltöltés/letöltés és történet is policy-védett.
A közös `DocumentableRegistry` bővítésekor a célügyhöz való hozzáférést is
vizsgálni kell, nem elegendő a `documents.view` vagy a cél létezése. A
garanciabizonyíték, ügyféljóváhagyás és selejtfelhatalmazás konkrét, megőrzött
dokumentumverzióra hivatkozik. Fájlkezelés a meglévő biztonságos infrastruktúrán.

Az `AuditLogService` actorral, célobjektummal és parancsazonosítóval naplózza a
nyitást, átvételt, diagnózist, garanciát, override-ot, ajánlatot, ügyfélválaszt,
indítást, tervváltoztatást, fogyasztást, QC-t, cserefoglalást, előkészítést,
visszavonást, kiszállítást, igazolt átadást, tulajdonátszállást, selejtet és lezárást. A rekorddiff
az `attribute_changes`, üzleti kontextus a `properties`; csak szükséges személyes
adat kerülhet logba. Az állapottörténet nem másolja a teljes auditpayloadot.

D7/D8/D9/D10/D11 szerinti credit release, karanténfeloldás, redispatch, ownership
reversal és coverage release/reassign külön jogosult, indokolt és auditált művelet.
A konkrét permission-azonosítók, DB-jogok és retention C2-s szerződések,
nem már seedelt vagy kikényszerített képességek. AM-02-G3 minden inventory
writerre/readerre érvényes, a super-admin üzleti guardokat sem kerülhet meg.

## 10. Tranzakció, idempotencia és konkurenciavédelem

Minden üzleti parancsnál az állapotot a tranzakción belüli zárolás után kell
újraolvasni. A frontend dupla kattintás elleni védelme nem idempotencia.
Az aktív claim egyedi kulcsa és a domainállapot guardjai együtt kötelezőek.

A javasolt tartós parancsregiszter `repair_commands`: egyedi
`(command_type, idempotency_key)`, payloadhash, actor, cél és eredményhivatkozás.
Ez technikai deduplikációs rekord, nem új workflow. Ugyanaz a kulcs+payload
ugyanazt az eredményt adja; eltérő payload ugyanazon kulccsal konfliktus.
A fizikai kiadás, átadási bizonyíték és ownership-esemény külön parancs;
ugyanaz a shipment/delivery/ownership üzleti occurrence csak egyszer könyvelhető,
stabil forrásazonosítóval és payload-egyezéssel. Egy szerződéses trigger szövege
nem eseményazonosság: valódi későbbi transfer/reversal külön identitású lehet.
A command, a fizikai shipment, a return-intake és admission saját deduplikációt
kap. Az ownership reversal eredeti-event kapcsolata és egyedisége külön védendő.
A sikeres regisztráció és üzleti írás közös tranzakció; rollbacknél nincs sikeres
parancsrekord. Másik kulccsal ismételt átvételt a domain és egyediség tiltja.

| Parancs                                              | Zárolás és atomikus írások                                                                                                                                                                                    | Rollback / ismétlés                                                                                                                                                                   |
| ---------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| RMA létrehozása / termékfelvétel                     | Ügy, növekvő ID szerinti példányzárak; eredet, tagság, munkalap, claim, történet, audit, command; aktív cserefoglalás és függő kiadás kizárása                                                                | Egy termék hibája az egész beküldést visszagörgeti.                                                                                                                                   |
| Eredeti visszáru átvétele                            | Ügy, példány, munkalap; tagság/serial/állapot/korábbi receipt; mozgás, custody, helyprojekció, történet, audit                                                                                                | Nincs részleges vagy dupla átvétel.                                                                                                                                                   |
| Anyagfogyasztás                                      | Ügy, munkalap, anyagserialok, minden érintett foglalás és balance; saját igény és más lekötések ellenőrzése; anyag, mozgás, részfoglalás, projekció, audit                                                    | Aktív cserefoglalásból fogyasztás tiltott; hiba esetén nincs részfogyasztás.                                                                                                          |
| Vizsgálat/QC vagy tervváltoztatás                    | Ügy, munkalap, aktuális próbálkozás/műveletek, quote-verzió; inspection vagy superseding terv és előzmények, keretvizsgálat, állapot, audit                                                                   | Elkezdett történet nem módosul; jóváhagyásra váró scope-ból új munka nem indul.                                                                                                       |
| Csere lefoglalása                                    | Ügy, eredeti és cserepéldány, munkalap, aktív foglalások, balance; jogosultság, origin, hely és nettó készlet; egyedi serialfoglalás, serialpár, történet, audit                                              | Egy példány két ügynek vagy más célnak nem foglalható; nincs balance-csökkentés.                                                                                                      |
| Csere előkészítése / kiadás előtti visszavonása      | Ugyanazok a zárak; fizikai hely/állapot/alkalmasság; szükség esetén valós belső transfer és foglaláshely-váltás; preparation/cancellation történet                                                            | Összmennyiség 0 változás; hiba esetén nincs feloldott vagy gazdátlan foglalás.                                                                                                        |
| Eredeti vagy csere fizikai kiadása                   | Ügy, serialok, munkalap, custody vagy cserefoglalás/balance; szükséges QC/engedély/policy és kiadási bizonyíték; release és egyszeri mozgás; eredeti custody megszűnése vagy cserefoglalás teljesítése, audit | Company-stock shipment balance -1 csak itt; custody-rights shipment normal balance 0; hiányos bizonyíték esetén nincs kivezetés. Helyi azonnali átadás igazolása ugyanitt rögzíthető. |
| Kiszállítás utáni igazolt átadás / tulajdonátszállás | Ügy, serial, munkalap, release és feltételverzió; ellenőrzött bizonyíték, esemény-egyediség, történet, audit                                                                                                  | Nincs második készletcsökkentés. Ha a trigger már kiadáskor teljesült, nem rögzíthető még egyszer.                                                                                    |
| Kiszállított csere megszakítása / return             | Ügy, serial, order, shipment, stabil intake/admission identity; physical intake+karantén majd külön proofos admission/disposition, AM-02-G1–G4 szerint                                                        | Nincs távoli hiba miatti stocknövelés; intake nem admission, return nem reversal, korábbi outbound nem törlődik.                                                                      |
| Selejt / végső rendelkezés                           | Ügy, eredeti serial, munkalap, custody, összes cserefoglalás és release; felhatalmazás, mozgások és mindkét serial sorsának bizonyítéka; disposition, audit                                                   | `replaced` nem fedheti el az eredeti visszáru nyitott sorsát; selejtmozgás-hibánál nincs lezárt őrzés.                                                                                |
| Ügyzárás                                             | Ügy, tagságok, serialok, munkalapok, foglalások és kiadások; a 7.1 összes kapcsolt bizonyítéka, majd státusz, claim-feloldás, történet, audit                                                                 | Nincs részleges zárás; proof/custody/reservation/authorization/QC hiányakor rollback.                                                                                                 |

Az ügyzárással versenyző termékfelvétel, átvétel, új művelet és anyagfoglalás
valamint a csere előkészítése, visszavonása és kiadása ugyanazt az ügysorzárat is kéri. Így a lezáró ellenőrzés alatt nem keletkezhet
új gyermekmunka. Javasolt zárolási sorrend: ügy -> érintett példányok növekvő
ID -> munkalapok -> műveletek -> foglalások -> egyenlegek -> kiadási/bizonyítékrekordok. A különböző service-ek
ugyanazt a sorrendet követik; az ütköző általános inventoryparancsokkal is
egyeztetni kell. Deadlock újrapróbálás csak korlátozottan és idempotens parancsra.

Ajánlati és garanciális verziók kiosztása munkalapzár alatt történik;
jóváhagyás és indítás ugyanazt a munkalapot zárolja, így nincs elavult ajánlat
alapján indulás. Lezárt ügy nem nyitható újra általános `update` hívással.

Auditinsert-hiba üzleti rollbacket okoz. Fájlfeldolgozás, e-mail, fuvarozói API
és egyéb külső mellékhatás nem fut nyitott tranzakcióban; commit utáni,
újrapróbálható feldolgozás szükséges. Kiszállítási szándék vagy címkenyomtatás
nem bizonyít tényleges outboundot: a könyveléshez ellenőrzött fizikai esemény
kell. Utólag érkező kézbesítési webhook a kiadáshoz kötött, deduplikált új tény;
nem ismételheti meg a raktári kiadást. A fájl nem tranzakcionális, ezért előre
feltöltött/ellenőrzött bizonyíték-hivatkozás és árva fájlok kontrollált kezelése
kell. Cache-invalidálás commit után történik a meglévő mintával.

## 11. Tesztelési stratégia

A meglévő [Pest-konfiguráció](../../tests/Pest.php) és
[PHPUnit-konfiguráció](../../phpunit.xml) alapjára készüljenek factorys,
`RefreshDatabase` feature tesztek. A projektben Pest és PHPUnit stílus is van;
új teszt a steering szerinti Pest-mintát kövesse. Az auditot és rollbacket a
[ProcurementManagementUiTest](../../tests/Feature/ProcurementManagementUiTest.php)
`test_goods_receipt_post_rolls_back_when_final_audit_fails()` és
`test_goods_receipt_post_rolls_back_when_stock_movement_creation_fails()`
tesztjeihez hasonló hibainjektálással is ellenőrizni kell.

| Szint                  | Kötelező bizonyíték                                                                                                                                                                                    |
| ---------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Unit                   | Állapotguardok, díj/credit számítás, pénzkerekítés, időszakösszeg, garanciahatárnap és hiányzó bizonyíték.                                                                                             |
| SQLite feature         | Mind a 20 üzleti döntés; többtermékes ügy részleges beérkezéssel és eltérő eredménnyel; origin/serial elutasítás; claim az ügyzárásig; történet megjelenítése.                                         |
| Inventory integration  | Átvétel/áthelyezés/kiadás/selejt mozgása és custody-rebuild; nincs normál balance-növelés; MRP, foglalás, gyártási fogyasztás és sale-availability kizárja a visszárut.                                |
| Materials/procurement  | Részfogyasztás, konkurens foglalás, batch/serial egyezés, hiányból pontosan egyszeri igény és beszerzési forráskapcsolat; nincs fiktív gyártási rendelés.                                              |
| Workflow/quote         | Függő garancia tilt; fizetős javítás jóváhagyás nélkül tilt; lejárat versenye az indítással; verzióváltás; fix/időalapú/ingyenes diagnózis és egyszeri credit.                                         |
| QC és idő              | Több dolgozó, szünet/folytatás, nyitott időszakasz tiltja a lezárást; hibás QC után korlát nélküli új sorszám, változatlan előzmények.                                                                 |
| Security               | Minden művelet engedélyezett és tiltott szerepkörrel; közvetlen route és dokumentumletöltés; permission nem kerülheti meg az üzleti guardot super-adminként sem.                                       |
| Rollback               | Mozgás-, audit-, custody-, release- vagy disposition-írási hiba után nincs részleges állapot; claim és idempotenciaeredmény sem marad tévesen sikeres.                                                 |
| MySQL concurrency      | Két valódi kapcsolat/tranzakció szinkronizált indítással: ugyanaz a serial két ügyben, dupla átvétel, párhuzamos anyagfogyasztás, cserepéldány két ügynek, QC-verseny, ügyzárás kontra termékfelvétel. |
| Migráció és regresszió | SQLite/MySQL FK-, UNIQUE-, CHECK-, rollback- és scope-backfill vizsgálat; régi saját készlet és reporteredmények megőrzése, ismeretlen mozgásmapping blokkolása.                                       |

D1–D5 további elfogadási esetei: mindhárom készlethatókör helyes könyvelése;
ügyféltulajdonú visszáru normál balance-hatásának kizárása; `semi_finished_product`
pozitív eredetbizonyítás és típus önmagában történő elfogadásának tiltása;
eltérő tulajdonos termékfelvételének elutasítása; csere utáni automatikus
új garanciakezdet kizárása; kiadás és ownership-esemény külön rögzítése,
azonos vagy eltérő, de minden esetben bizonyított időpontokkal.

Az F1–F4 és RMA-BD-020 külön regressziós esetei:

- Foglaláskor és előkészítéskor balance-összeg változatlan, ownership vállalati,
  replacement custody nincs. Más foglalás, termelési fogyasztás és sales
  allocation ugyanazt a serialt/lekötött mennyiséget nem használhatja.
- Belső transfer után a foglalás helye egyezik; konkurens kérés nem fér hozzá
  átmenetileg szabad célhelyi mennyiséghez. Két ügy egyszerre egy serialra:
  egy siker, a másik mellékhatás nélküli konfliktus, valódi MySQL-tranzakciókkal.
- Előkészítés visszavonásakor ellenőrzési hiba után a foglalás él és az ügy
  nyitott; siker után valós hely, history és egyszeri feloldás. Kiszállított,
  de át nem adott termék visszahívása nem teremt készletet visszaérkezés nélkül.
- Company-stock shipment balance -1 egyszer, későbbi proof/ownership balance 0;
  új kulccsal ismételt kiadás, duplikált webhook és trigger sem duplázhat.
  Ismeretlen szerződéses policy kiadást tilt; preparation nem ownership-esemény.
- Zárás elutasított, ha csak a státuszok teljesek, de egy mozgás, eredeti
  disposition, cserefoglalás-rendezés, átadási proof, QC vagy felhatalmazás hiányzik.
  Zárás kontra preparation/kiadás/munkaidő indítás konzisztens és atomikus.
- Eredeti és csereserial mindkét oldali előzménykeresése megőrzi a két gyártást,
  RMA-t, foglalás/kiadás kapcsolatát, garanciaverziót és átadási bizonyítékot;
  csere önmagában nem állít új garanciakezdetet.
- El nem kezdett művelet jogosult módosítása megőrzi a korábbi becslést/döntést;
  megkezdett művelet eredeti adata változatlan. Lényeges scope-változás új
  ajánlatot kér, függő jóváhagyás alatt indítás/fogyasztás tiltott; új approval
  csak az arra hivatkozó terv folytatását engedi.

Az SQLite nem bizonyítja a MySQL `lockForUpdate` és deadlock viselkedését.
A konkurenciateszt nem két egymás utáni HTTP-hívás. Dedikált, guardolt MySQL
tesztadatbázis szükséges; éles adatbázis nem használható.

Az RMA későbbi bevezetésekor a quality-gate modulmátrix frissítendő. A
[rétegezett kapuk](../../docs/development/quality-gates.md) szerint affected/module
ellenőrzés, shared inventory/policy/route változásnál integration, migrációnál
full és külön MySQL-kapu szükséges. UI esetén Vitest, HU/EN i18n és célzott E2E
is kell. Ez az ADR nem állítja e tesztek létezését vagy sikeres futását.

RMA-D6–D11 és AM-01–04 további kötelező tesztterületek: approval-only credit,
fee-cap/same-order/dupla allocation, execution/settlement release-review;
intake proof AM-02-G1, balance0 AM-02-G2, minden consumer quarantine exclusion
AM-02-G3, külön intake/admission idempotencia AM-02-G4; valódi redispatch és
dupla shipment tiltás; reversal original/proof/permission; demand cancel vs
PR/PO/partial receipt, coverage conservation és újraszámítási idempotencia.
Ezek még követelmények, nem lefutott ellenőrzések. A B történeti SQL-eredményei
nem bizonyítják az új C1/C2 teljes sémát vagy workflowt.

## 12. Következmények, kockázatok és kezelésük

Előny: a javítás nem módosítja az eredeti gyártást, a visszáru nem válik szabad
készletté, és a termékenkénti döntések önállóan auditálhatók. Költség: külön
végrehajtás, custody-projekció, ajánlat- és bizonyítékkezelés szükséges; a
mozgásriportok scope-helyes átállítása így is kötelező.

| Súlyosság | Kockázat                                                                        | Kezelés / bevezetési feltétel                                                                            |
| --------- | ------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------- |
| Critical  | Ügyféltermék normál balance-ba vagy mozgásalapú szabadkészlet-számításba kerül. | B stratégia, explicit mozgási hatókör, minden fogyasztó inventory-regressziója.                          |
| High      | Legacy serial aggregált készletben még szerepel, kivezetése nem igazolható.     | Beérkezési könyvelés blokkolása, dokumentált egyeztetés, nincs automatikus korrekció.                    |
| High      | Gyártási eredet vagy átadás hiányos.                                            | Alkalmassági gate; külön eredetkorrekció; garancia pending, indokolt override.                           |
| High      | Párhuzamos nyitás, fogyasztás, csere vagy lezárás.                              | DB-egyediség, közös sorzárak, parancsidempotencia, valódi MySQL-tesztek.                                 |
| High      | Korábbi QC, művelet vagy beépülési történet felülíródik.                        | Append-only tények, próbálkozásszám, törlési RESTRICT, korrekciós esemény.                               |
| High      | Belső jóváhagyást ügyfélválasznak tekintenek.                                   | Konkrét ajánlatverzióhoz tartozó ügyfél-bizonyíték és elkülönített permission.                           |
| High      | Csere után az eredeti termék őrzése vagy tulajdona tisztázatlan.                | Végső disposition csak mindkét serial fizikai sorsának bizonyítása után.                                 |
| Medium    | Meglévő műveletsor-adminisztráció nem teljesen változatlan.                     | A javítás megkezdett terve snapshot; eredeti gyártási verzióhoz nem ír. A meglévő eltérés külön feladat. |
| Medium    | Ajánlati pénz és időmérés pontatlan vagy átfedő.                                | Fix pontosság, szabálypillanatkép, munkaszakaszok és validált korrekció.                                 |
| Medium    | Dokumentum másik hozzáférési útvonalon kiszivárog.                              | Célügyre is kiterjedő download/upload policy, negatív tesztek.                                           |
| Medium    | Custody és mozgás külön elsődleges ledgerként eltér.                            | Csak közös könyvelési tranzakció írhat projekciót, rebuild és egyeztetés.                                |
| Low       | Új állapotok következetlenül jelennek meg.                                      | Angol azonosítók, közös `lang/hu.json` és `lang/en.json` kulcsok.                                        |

Az ADR nem oldja fel a korábbi ADR-ek szabályait. Különösen nem enged közvetlen
egyenlegmódosítást, serialregenerálást, eredeti műveletsorátírást vagy QC-törlést.

### RMA-0002/B felülvizsgálati eredmény

| Finding | Tervezeti korrekció                                                                                                         | Nyitva maradó előfeltétel                                                   |
| ------- | --------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------- |
| F1      | Foglalás és preparation vállalati inventoryban; külön kiszállítás, átadás és ownership-esemény; idempotens egyszeri kiadás. | Szerződéses policy és serialos foglalás minden fogyasztóban kikényszerítve. |
| F2      | Státusz helyett mozgás-/proof-/reservation-/QC-/authorization-alapú, zárolt és atomikus ügyzárás.                           | Hordozható constraint-ek és valódi konkurenciateszt megvalósítása.          |
| F3      | Eredeti–csere serialpár a megőrzött foglaláson, kiadáson és döntési eseményeken; eredeti sors külön.                        | Garanciális cserepolicy; automatikus újrakezdés nincs.                      |
| F4      | Csak el nem kezdett művelet változtatható jogosultan, megőrzött előzménnyel; scope-gate újraértékeli az ajánlatot.          | Lényeges keretváltozás és szerepszétválasztás pontos szabálya.              |

Az F1–F4 korrekciókat az RMA-0002/C véglegesítés változatlan invariánsokkal
megőrzi. D1–D5-tel együtt az architektúra elfogadott; ez nem implementációs
vagy konkrét jogi minősítésre vonatkozó jóváhagyás. A kritikus bevezetési
előfeltételeket a 14. fejezet rögzíti. AM-01 az F1 egyszeri postingját fizikai
shipmentenként pontosítja, F2/F3/F4 invariánsait nem oldja fel.

## 13. Megvalósítási fázisok

1. Architekturális alap lezárva az RMA-0002/C keretében: B stratégia, F1–F4 és
   D1–D5 elfogadott. Következő előkészítés: elfogadási tesztmátrix és részletes
   séma a 14. fejezet implementációs kapuihoz; ez nem új architekturális elfogadás.
2. Adatintegritási előfeltételek: eredet-/átadásbizonyítás, legacy készlet-
   egyeztetés, mozgásscope és migrációs terv; normál inventory-regresszió.
3. Ügy és termék: többtermékes tagság, önálló munkalapok, claim, permission,
   audit, serialos előzmények; még fizikai bevételezés nélkül.
4. Átvétel és custody: könyvelés, helyváltozás, idempotencia, izoláció,
   projekció-újraépítés és scope-helyes riport.
5. Garancia és ajánlat: átadási bizonyíték, manuális döntés, termékszabály,
   díjak, verziózás és ügyfélválasz; számlázás nélkül.
6. Végrehajtás: több dolgozó, tényleges munkaidő, anyagfoglalás/-fogyasztás,
   hiány és procurement-adapter, újrapróbálkozás és QC.
7. Végső rendelkezés: cserefoglalás, vállalati előkészítés, visszavonás,
   kiszállítás és igazolt átadás/ownership; eredeti termék független visszaadása
   vagy selejtje, bizonyítékos őrzéslezárás és csak ezután ügyzárás.
8. Integrációs lezárás: MySQL-konkurencia, migration és full gate, i18n/E2E,
   üzemeltetési egyeztetés, helyreállítási és rollback-eljárások.

Minden fázis önálló, jóváhagyott implementációs feladatot igényel. Az ADR
elkészítése nem felhatalmazás ezek végrehajtására.

## 14. Lezárt architekturális kérdések és nyitott implementációs kapuk

Az RMA-BD-001–020, D1–D5 és RMA-D6–D11 elfogadott; AM-01–AM-04 projektgazdai
jóváhagyása 2026-10-29-én lezárta a C1 architekturális egyeztetést, AM-02-G1–G4
kötelező feltételeivel. Nem maradt függő amendment-jóváhagyás. Az alábbi implementációs feltételek nem jelentenek
kész működést, és nem gyengíthetik az elfogadott invariánsokat.

### 14.1. Az RMA-0002/C keretében lezárt kérdések

| Korábbi kérdés                              | Elfogadott döntés                                                                | Fennmaradó részletes munka                                                             |
| ------------------------------------------- | -------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------- |
| T01: hatókörök és ownership szétválasztása  | D1 és D5; `company_stock`, `customer_custody`, `external`, külön tulajdonesemény | Mezők, mozgástípusok, legacy mapping és riportfogyasztók ellenőrzése.                  |
| T03: `semi_finished_product` alkalmasság    | D2; saját gyártású, serialos, bizonyítható eredetű komponens támogatható         | Eredetellenőrzési tesztek; nincs további típusmapping-jóváhagyásra váró üzleti kérdés. |
| T04: cseregarancia alapelve                 | D4; nincs automatikus új időszak, mindkét serial története megmarad              | Alkalmazandó szabályok bizonyítékos konfigurációja és verziózása.                      |
| T05: tulajdonosi modell                     | D3; egy ügy egy tulajdonos, külön bejelentő                                      | Mezők, felhatalmazások, historikus bizonyíték és adatmegőrzés.                         |
| T07: kiadás és jogi tulajdonváltás viszonya | D5; külön esemény, alkalmazandó feltételből következő időpont                    | Konkrét feltételverzió, trigger, bizonyíték és visszahívási eljárás bevezetés előtt.   |

### 14.2. Érintett implementációs fázis előtt kötelező

| Pont | Fázis                             | Nyitott részlet / szükséges bizonyíték                                                                                                                                                                                                                         | Védett integritás                        |
| ---- | --------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------- |
| T01  | 2–4.                              | StockMovement-mezők és típusok, ellenőrzött legacy scope-backfill, teljes riport-/egyenlegfogyasztólista és regresszió. Az elfogadott három scope nem nyitott újra.                                                                                            | Készlet, ügyféltulajdon, dupla számlálás |
| T02  | 2–3.                              | Legacy saját gyártás bizonyítékai, hibás gyártásirendelés-kapcsolatok kontrollált rendezése; hiányos adatnál nincs automatikus alkalmasság.                                                                                                                    | Serial traceability                      |
| T04  | 5.                                | Garanciaidőtartam-konfiguráció verziója, határnap/időzóna, alkalmazandó igazolt átadás kiválasztása, cseregarancia szabályainak indokolt és auditált alkalmazása.                                                                                              | Garanciatörténet                         |
| T05  | 3.                                | Egy tulajdonost kikényszerítő kapcsolatok és guardok; bejelentő/címzett, felhatalmazás, megőrzés és adatvédelem konkrét mezői.                                                                                                                                 | Tulajdonosi bizonyíték                   |
| T06  | 5.                                | Pénzpontosság, kerekítés, nettó/bruttó/adó, diagnosztikai díjelfogadás és időegység kifejezett specifikációja.                                                                                                                                                 | Pénzügyi approval                        |
| T07  | 7.                                | Az adott ügyre alkalmazandó átadási feltételek ellenőrzött forrása, felelőse, verziója, ownership-trigger és szükséges bizonyíték. Ismeretlen feltétellel nincs kiadás; függő kötelezettséggel nincs ügyzárás. Nem garanciális csere díjazása nem vélelmezett. | Jogi tulajdon, kiadás                    |
| T08  | 2., 6–7.                          | MaterialRequirement/StockReservation igénytulajdonosi és serialos FK/UNIQUE-kulcsok; részfoglalás, serialgenealógia és minden készletfogyasztó felülvizsgálata.                                                                                                | Készlet, beépülési történet              |
| T09  | 2–3., 6–7.                        | MySQL/SQLite CHECK/UNIQUE/összetett FK, serialfoglalás, nyitott időszakasz egyedisége, globális időátfedés szervezeti szabályának pontosítása.                                                                                                                 | Claim és konkurencia                     |
| T10  | 3.; külső eseménynél legkésőbb 7. | Idempotenciakulcs, retention, deadlock retry, esemény-egyediség, megbízható commit utáni feldolgozás.                                                                                                                                                          | Egyszeri kiadás és audit                 |
| T11  | 5–6.                              | Jóváhagyott ajánlat érvényessége, lényeges munkatartalom-/keretváltozás részletes ellenőrzése, QC-szerepszétválasztás. Az F4 approval-gate nem halasztható el.                                                                                                 | Pénzügyi jóváhagyás, QC                  |
| T12  | 7.                                | Kiadási/átadási bizonyíték ellenőrzése, későbbi proof és sikertelen handover tényleges visszaérkezési útja; nincs fiktív korrekció.                                                                                                                            | Készlet, tulajdon, lezárás               |

Ezek részletes implementációs vagy működési előfeltételek. Az ADR elfogadása
nem helyettesíti teljesítésüket. Különösen T01/T02/T08/T09/T10 nélkül nem
állítható adatintegritás vagy konkurenciabiztonság; T04/T07/T12 nélkül nem
engedhető el bizonyítékos garanciális/átadási kontroll. A konkrét jogszabályi
és szerződéses szabályokat nem ez az ADR találja ki.

### 14.2.1. RMA-0003/C1 lezárás utáni technikai kapuk

D6–D11 és AM-01–04 nem nyitott üzleti/amendment-jóváhagyási pont többé.
G1: DESIGN RESOLVED / IMPLEMENTATION UNVERIFIED; G2: IMPACT MAPPED / ROLLOUT BLOCKED;
G3: LIFECYCLE SPECIFIED / DDL UNVERIFIED. C1: ACCEPTED WITH CONDITIONS.
AM-02-G1–G4 teljes bizonyítása az érintett séma és rollout előfeltétele.
A T01/T04/T06/T07/T08/T09/T10/T12 implementációs részletei ezek szerint
pontosítandók; a régi táblázat nem újra megnyitott döntéslista.

Nyitott: teljes 25-táblás és existing-change leltár; claim acquire/finalizer/grant
DDL és negatív jogosultsági bizonyíték; occurrence/return/admission/reversal
kulcsok; fee-credit és coverage ledger; G2 minden fogyasztója, cache és közös
zársorrend; reprezentatív legacy audit, stock baseline, migration/mixed-version/
rollback és környezeti ellenőrzések. A
[C2 előkészítési terv](../designs/rma-0003c2-detailed-schema-review-plan.md)
csak a következő munka terve, nem elvégzett C2 vagy implementáció.

### 14.3. Dokumentált későbbi bővítések

T13: ügyfélportál, automatikus garanciális döntéstámogatás és számlázás külön
jövőbeli scope, külön felhatalmazással. Közös outbox későbbi általánosítás
lehet, de a kezdeti külső mellékhatások megbízhatósága nem halasztható el.
Egyik bővítés sem helyettesítheti a belső bizonyítékos ügyfélapprovalt.

## 15. Elfogadási feltételek

### 15.1. Az RMA-0002/C dokumentációs feladat átadása (történeti)

- A meglévő ADR helyben, változatlan `0006` sorszámmal véglegesített;
  magyar nyelvű, státusza az RMA-0002/C döntései alapján `Accepted`.
- D1–D5 külön azonosítható elfogadott döntésként szerepel, a kapcsolódó
  adatmodellben, életciklusban, készlet- és garanciaszabályban következetesen.
- Mind a 20 jóváhagyott döntés explicit szerepel és a részletes tervhez kapcsolódik.
- A meglévő és javasolt működés elkülönül; a releváns tények fájlhivatkozással
  ellenőrizhetők, a meglévő ADR-ekkel való viszony kifejtett.
- A két inventory-alternatíva mind a tíz kért szempont szerint összehasonlított;
  a választott B stratégia nem hoz létre mozgástól független készletigazságot.
- Sémakorlátok, hat külön életciklusdimenzió, permissionök, kritikus
  tranzakciós műveletcsoportok, rollback, konkurencia, tesztelés és besorolt nyitott kérdések szerepelnek.
- Csak ez a dokumentum változik; nincs alkalmazáskód, migráció, teszt,
  konfiguráció, Git-branch vagy dependency módosítás. A felhasználó által
  engedélyezett mentés csak ezt az ADR-t stage-eli és commitolja
  `docs(rma): accept repair management architecture ADR` üzenettel, majd a meglévő
  feature branchre pusholja. Staged teljes diff és végső Git-státusz ellenőrzendő;
  merge nem része a feladatnak.
- Relatív fájlhivatkozások és mind a 20 azonosító ellenőrzött; az első 19
  jóváhagyott döntés tartalma megőrzött, a 20. döntéssel együtt. Az F1–F4 korrekciók, készletmozgás,
  csere-életciklus és ügyzárás egymással összhangban ellenőrzött.
- Markdown-formázás és `git diff --check` lefut; untracked fájlnál a tényleges
  tartalom whitespace- és hivatkozásellenőrzése is szükséges. A végső
  `git status --short --branch` átadva. Alkalmazásteszt/build N/A: csak dokumentáció.

### 15.2. Későbbi implementáció üzleti elfogadása

- Egy ügy több serialt kezel, önálló munkalapokkal; korábbi javítások látszanak.
- Saját gyártás és serial érdemben igazolt; nincs puszta formátumalapú vagy
  cikktípusalapú elfogadás. D2 szerint a saját gyártású, egyedi serialú,
  bizonyított eredetű `semi_finished_product` komponens is támogatható.
- D3 szerint egy ügy egy tulajdonoshoz tartozik; külön bejelentő megengedett,
  eltérő tulajdonos terméke nem kerülhet ugyanabba az ügybe.
- Egy példányhoz egy aktív claim van, amely csak az ügy lezárásakor szabadul.
- Visszáru normál elérhetősége, értékesíthetősége, foglalhatósága és
  fogyaszthatósága nulla; fizikai hely és minden őrzési esemény követett.
- Garancia manuális, bizonyítékos, indokolt és jogosultságvédett; bizonyítékhiány
  függő. Fizetős javítás nem indul jóváhagyott aktuális ajánlat nélkül.
- Több dolgozó tényleges ideje és anyaga követett, hiányhoz traceable beszerzés
  rendelhető. Sikertelen QC nem veszít történetet és új próbálkozást enged.
- Csere, javítás nélküli visszaadás és selejt végrehajtása dokumentált; selejt
  csak felhatalmazással. Kiadás a kötelező átadási adatokkal őrzést zár.
- Csere foglalása és előkészítése nem customer-custody és nem tulajdonátszállás;
  vállalati tulajdon marad, minden más allokáció tiltott. Minden company-stock shipment fizikai release-e egyszer
  csökkenti a készletet; későbbi handover/ownership igazolás nem von le újra.
  Több valódi shipment csak D9/AM-01 guardokkal és rendezett előző úttal lehetséges.
- Jogosult visszavonás csak ellenőrzött hely/állapot/alkalmasság után old foglalást;
  sikertelen kiszállítás nem okoz fiktív korrekciót vagy gazdátlan preparationt.
- Eredeti és csere serial külön történet, kapcsolt csereesemény és garanciális
  döntés; nincs automatikus garanciareset vagy -hosszabbítás.
- El nem kezdett terv jogosultan változhat, minden becslés és döntés megőrzésével;
  started/completed művelet nem írható felül. Scope-túllépés új approvalt követel,
  a függő engedély megakadályozza a jogosulatlan folytatást.
- Ügy csak a 7.1 kapcsolt mozgás-/proof-/reservation-/QC-/authorization-
  bizonyítékainak atomikus ellenőrzése után zárható; kész javítás vagy státusz nem elég.
- Hibainjektáláskor nincs részleges állapot, ismétléskor nincs dupla mozgás,
  MySQL-versenyhelyzetben is megmarad az egyediség és a készletintegritás.
- E kritériumok tényleges teljesítése csak az implementáció és a kapcsolódó
  tesztbizonyíték után állítható; az architektúra elfogadása egyiket sem minősíti késznek.

### 15.3. Az RMA-0003/C1 Closure dokumentációs átadása

Az ADR Accepted identitása, RMA-BD-001–020, D1–D5 és F1–F4 megmarad;
RMA-D6–D11, AM-01–AM-04 és AM-02-G1–G4 explicit szerepel. A korábbi
shipment/return/availability megfogalmazások amendment-történettel pontosítottak.
C1 ACCEPTED WITH CONDITIONS, G1 implementation és G3 DDL még nem igazolt,
G2 rollout blokkolt. C2-WP1–WP7 kizárólag előkészítési terv.

Módosítások kizárólag az ADR/A/B/C1 dokumentáció és az új C2 előkészítési terv;
a B verification artifactok változatlanok. Formázás, relatív hivatkozások,
döntéslefedettség, actual-file whitespace, git diff --check és végső Git-státusz
ellenőrzendő. Nincs staging, commit, push, merge, alkalmazásimplementáció,
migráció, DB-írás, új executable vagy dependency-változás.

## Kapcsolódó projektkonvenciók

- [ADR-sablon](../templates/decision.template.md)
- [Architektúra](../steering/architecture.md)
- [Dokumentációs nyelv és auditkonvenció](../../docs/architecture/project-conventions.md)
- [Definition of Done](../../docs/project-management/definition-of-done.md)
- [Rétegezett quality gate-ek](../../docs/development/quality-gates.md)
