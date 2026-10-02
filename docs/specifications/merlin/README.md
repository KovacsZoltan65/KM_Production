# KM_Production – Merlin / IMF / Nagy Könyv

## 7. koncepciómentés – Implementáció előtti baseline

Státusz: a felhasználó által elfogadott koncepció feladatban átadott lényegének repository-változata. Eredeti dátum: 2026-09-26. Egyeztetés: 2026-09-27, a `5725a14` candidate alapján. Kanonikus fejlesztési ág: `feature/merlin-candidate`. A régi `feature/merlin` megőrzött referencia.

Ez tervezési baseline, nem megvalósult funkció vagy implementációs engedély. Forrása a „KM_Production – Merlin – Branch Bootstrap & Implementation Preparation” feladatban megadott koncepció; külön teljes eredeti 7. koncepciómentés nem állt rendelkezésre. Az alábbi fogalmak tervezett szerződések, létezésüket az alkalmazáskódban külön kell igazolni.

## Mi és miért

Merlin a KM_Production része, nem külön alkalmazás. Az IMF – Impossible Missions Force a problémás és veszélyeztetett esetek kezelési felülete. Merlin megoldási lehetőségeket keres, hogy az ember bizonyítékokra támaszkodva dönthessen.

> A KM_Production felismeri és bizonyítja a problémát.
> Merlin megoldási lehetőségeket keres.
> Az ember dönt.
> A KM_Production végrehajt és újraellenőriz.

A Problem fennálló, bizonyított probléma; a Risk egy lehetséges jövőbeli kedvezőtlen kimenetel kockázata. A kockázat nem bizonyítja, hogy a probléma már bekövetkezett. A jelzés eredete (problem source): SYSTEM, HUMAN vagy MERLIN. Az eredet nem helyettesíti a bizonyítást: a HUMAN és MERLIN jelzést is a KM_Production domainje ellenőrzi.

## Felelősségi határok

| Fogalom  | Felelősség                                                                    |
| -------- | ----------------------------------------------------------------------------- |
| Service  | AI-független alkalmazási/domain működés és determinisztikus üzleti szabályok. |
| Tool     | Ellenőrzött adapter egy meglévő service képességéhez; nem új üzleti logika.   |
| Workflow | Előre meghatározható lépések és szabályok végrehajtása.                       |
| Agent    | AI + cél + eszközök + állapot + döntési ciklus.                               |

> Amit determinisztikusan megbízhatóan meg tudunk oldani, arra nem használunk AI-agentet.

Merlin nem kap közvetlen SQL/MySQL/database hozzáférést:

```text
Merlin
    ↓
Tool
    ↓
Application / Domain Service
    ↓
Repository / Model
    ↓
Database
```

Az AI nem trusted backend. A tool call kérés, nem parancs. A backend validálja a bemenetet, a jogosultságot és az üzleti kontextust.

```text
Merlin effective permission = User Permission ∩ AI Permission ∩ Case Context
```

Authorization történik a toolset assembly során és minden tool execution során ismét. A korábbi ellenőrzés nem jogosít fel egy későbbi hívást változatlanul.

## Toolok és emberi döntés

A tool-kategóriák: READ; ANALYZE / SIMULATE; PROPOSE; EXECUTE. Merlin v1 számára az autonóm EXECUTE nincs engedélyezve.

```text
Merlin
    ↓
Action Proposal
    ↓
Human decision
    ↓
KM_Production Application / Domain Service
    ↓
Business state change
```

A végrehajtás után a KM_Production determinisztikusan újraellenőrzi a problémát.

> A problémát a KM_Production bizonyítja, és a probléma megszűnését is a KM_Production bizonyítja.

## Futás és vezérlés

Minden Merlin-vizsgálat külön technikai futás: MerlinRun. A Merlin Orchestrator nem AI, hanem a KM_Production komponense, amely kontrollálja Merlin futását. A Tool Registry központi tool-definíciós forrás; a tool továbbra is adapter, nem második üzleti implementáció.

> Merlin nem vezérli a KM_Productiont. A KM_Production vezérli Merlin futását.

## Information Gap és emberi közreműködés

Ha egy szükséges információ nincs a KM_Productionben, és determinisztikusan sem állítható elő, Merlin nem találhatja ki. Közölnie kell, mi hiányzik, miért szükséges, és hogyan szerezhető meg, ha ez meghatározható. Az information gap önmagában nem system error.

Merlinnek képesnek kell lennie megállni és emberi információt vagy döntést kérni: HUMAN_INPUT_REQUIRED. Hiányzó adatot nem helyettesít önkényes alapérték, confidence százalék vagy kitalált üzleti tény.

## Nagy Könyv és technikai audit

A Nagy Könyv a problémamegoldás szervezeti memóriája, nem chat history és nem technikai audit log:

```text
Problem → Context → Investigation → Advice → Human decision → Action → Outcome → Lesson
```

A Nagy Könyvtől külön technikai Audit Trail szükséges. A futás, toolkérés, jogosultság-ellenőrzés és végrehajtás technikai visszakövethetősége nem helyettesíti az üzleti tanulságot; az üzleti tanulság sem helyettesíti az auditot.

## Első tool: get_supplier_options

Kategória: READ. Egy Problem Case-ben érintett vásárolt cikk ismert beszállítói forrásait és determinisztikusan megállapítható beszerzési feltételeit tárja fel. Nem választ, nem rangsorol, nem nevez meg „legjobb” beszállítót, és nem módosít üzleti állapotot.

Mögötte AI-független, read-only SupplierOptionService álljon. Nem tud Merlinről, IMF-ről vagy Problem Case-ről, nem hív AI-modellt, nem végez MRP/netting számítást és nem választ beszállítót.

Koncepcionális bemenet:

```text
SupplierOptionQuery
    item_id
    required_quantity
    required_date
    unit
    evaluation_date
    provenance
```

Az evaluation_date explicit üzleti értékelési nap. A required_quantity a hívó által igazolt, beszerzésként értékelendő alapmennyiség; a provenance megkülönbözteti annak eredetét. Material Requirement hiányvizsgálatnál ez a 0009 netting eredményének netRequirement mezője. A jelenlegi Supply Proposal manuális proposed_quantity értéke önmagában nem bizonyított nettó igény; nincs implementált automatikus netting → proposal kapcsolat. PR esetén a planned_quantity és a replenishment után kapott quantity sem cserélhető fel.

A service egyik bemeneti eredetnél sem vonhat le készletet, foglalást vagy nyitott rendelést. Nem olvas pegginget beszerzési igényként. A unit az igazolt alapegység, amelynek egyeznie kell a jelenlegi Item.unit értékével; eltérésnél nincs hallgatólagos konverzió. A required_date szükségleti dátum, lehet ismeretlen; nem szállítói visszaigazolás. A provenance adapter-szerződés, nem új adatbázismező.

## Beszállítói források megőrzendő jelentése

Known source != Eligible source. Known source esetén van ItemSupplier kapcsolat; eligible source esetén a jelenlegi procurement domain szabályai szerint használható a forrás. Az aktuális strict eligibility az aktív cikket és beszállítót, aktív és approved kapcsolatot és az értékelési napon érvényes intervallumot ellenőrzi.

Az ismert, de nem eligible forrás megjelenhet az ineligibility okával. Emiatt nem lazítható fel meglévő strict eligibility query. Az audit alapján külön knownSourcesForItem(...) read path szükséges; a jelenlegi admin lista nem szolgáltatásfüggetlen teljes forráslista.

A repository az authoritative source az active, approved, preferred, priority, validity, purchase unit, conversion factor, MOQ, order multiple, reference price, currency és lead time jelentésére. A lead_time_days és unit_price szemantikája nem található ki. A reference price nem supplier-confirmed price.

A MOQ/order multiple és procurement timing szabályokat újra kell használni; második, Merlin-specifikus algoritmus nem készülhet. Ha extraction indokolt, az AI-független és viselkedésmegőrző legyen, külön jóváhagyott implementációban.

## Hatókör és kizárások

Ez a baseline nem vezet be supplier BLOCKED státuszt, QUALITY block reasont, supplier-confirmed delivery date-et vagy price-ot, price history-t, freshness thresholdot, AI confidence százalékot, supplier rankinget vagy supplier risk score-t. Ami a vizsgált domainben nincs, annak minősítése MISSING_DOMAIN_CONCEPT; ez önmagában nem implementációs hiba.

Ebben a menetben kizárólag baseline-egyeztetés és domain audit dokumentáció készül. Nincs runtime, SDK, UI, migráció, adatbázistábla, Tool Registry vagy Orchestrator implementáció. Az implementáció külön emberi review és jóváhagyás után indulhat.

## Dokumentációs illeszkedés

A modul-specifikáció a meglévő [Learning Center specifikáció](../learning-center/README.md) mintáját követi; a [rétegindex](../../../.kiro/index.md) szerint a termékdokumentáció a docs alatt él. A baseline egyetlen kanonikus forrás, nem másoljuk meg knowledge és ADR dokumentumban.

Új dokumentáció magyar, technikai azonosítók angolok a [projektkonvenciók](../../architecture/project-conventions.md) szerint. A meglévő angol README és steering fájlok örökölt eltérését ez a menet nem fordítja át.

A [manufacturing AI steering](../../../.kiro/steering/manufacturing-ai.md) és a [document AI ADR](../../../.kiro/decisions/0005-document-ai.md) OCR/Python-központú mintát ír le, az általános AI guidance confidence-t is előír. Merlinre a feladat explicit confidence-tilalma érvényes; ebből nem következik a teljes régi AI/OCR guidance módosítása vagy egy új futtatási technológia kiválasztása. A későbbi integráció előtt a hatókört tisztázni kell.

## Candidate baseline reconciliation

A `c6619c0` dokumentációját szelektíven vettük át: a koncepciót és a két navigációs hivatkozást megőriztük, a teljes régi ágat és az `e3de936` auditot nem importáltuk. A jelenlegi domainbizonyíték a candidate kódja és tesztjei, nem a régi audit.

| Téma                                              | Egyeztetés eredménye                                                                                                                               |
| ------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| Felelősség, permission, emberi döntés, Nagy Könyv | Változatlan koncepció; nem létező runtime-ként dokumentálva.                                                                                       |
| Bemeneti mennyiség                                | A korábbi általános „már nettó” állítást forrástípushoz kötöttük. Nettó igény csak igazolt netting-eredményből; proposal mennyisége manuális terv. |
| Pegging                                           | Coverage és lineage, nem procurement shortage.                                                                                                     |
| Mértékegység és provenance                        | Explicit szerződés szükséges; nincs implicit konverzió vagy kitalált nettingkapcsolat.                                                             |
| Reuse                                             | Strict ItemSupplier eligibility és a publikus calculateQuantity újrahasználható; időzítési szabályhoz közös, tiszta extraction kell.               |
| Kereskedelmi adatok                               | Reference price/currency létezik; az ár egységbázisa nem bizonyított, ezért becsült összérték nem vállalható.                                      |
| Állapot                                           | Dokumentációs előkészítés; nincs production implementáció, dependency-változtatás vagy implementációindítási engedély.                             |

## Audit és következő implementációs szelet

- [Procurement input audit és readiness bizonyítékok](procurement-input-audit.md)
- [SupplierOptionService input/output contract v0.1 és reuse map](supplier-options-contract-v0.1.md)
- [Material Shortage Problem Case Foundation – lifecycle és evaluation](material-shortage-problem-case.md)
