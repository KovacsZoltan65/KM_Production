# Merlin – Material Shortage Problem Case Foundation

Státusz: elfogadott koncepcionális döntések; nem implementált funkció. Dátum: 2026-10-01. Ellenőrzött kiinduló branch/HEAD: `feature/merlin-candidate` / `12475da`.

A dokumentum a [Merlin baseline](README.md) első Problem Case-típusát és annak lifecycle/evaluation szemantikáját rögzíti. Nem végleges adatmodell, nem resolver- vagy toolterv. A procurement határhoz lásd a [SupplierOptionService szerződését](supplier-options-contract-v0.1.md).

## Problem Case jelentése és azonossága

Az első támogatott típus a **Material Shortage Problem Case**. Egy ilyen case pontosan egy konkrét `MaterialRequirement` problémáját reprezentálja.

Egy problémás Customer Order nem azonos egy Problem Case-szel. Egy rendeléshez több külön case tartozhat. A case saját stabil azonosítót kap; a `problem_case_id` nem a `material_requirement_id` értéke vagy annak aliasa, mert ugyanahhoz a requirementhez időben több külön problémaesemény tartozhat. Az események létrehozási és elkülönítési szabályai itt nincsenek véglegesítve.

Koncepcionális lineage:

`Problem Case → MaterialRequirement → Production/Customer Order lineage`

## Authoritative üzleti adatútvonal

Az aktuális értékelés alapja:

`Customer Order → CustomerOrderItem → ProductionOrder/BOM → MaterialRequirement → full competing-scope netting → selected requirement result`

A netting a teljes versengő scope-ot értékeli, és csak ezután választható ki a case requirementjének eredménye. Az egyetlen requirementre szűkített számítás nem helyettesíti a közös supply poolért versengő igények figyelembevételét.

A tárolt `missing_quantity` vagy aggregált order-risk adat önmagában nem authoritative forrás az aktuális case evaluationhöz. A procurement-facing quantity a netting által előállított `netRequirement`; a SupplierOptionService nem végezhet újra nettinget vagy újabb supply-levonást.

A meglévő [netting service](../../../app/Services/Admin/MaterialRequirementNettingService.php) és [repository](../../../app/Repositories/Admin/MaterialRequirementNettingRepository.php) számítási alapot ad. A [SupplierOptionService](../../../app/Services/Admin/SupplierOptionService.php) már létezik, de nem tartalmaz Problem Case resolvert vagy lifecycle adaptert. A [provenance DTO](../../../app/Support/Procurement/ProcurementRequirementProvenance.php) állítása önmagában nem bizonyítja az üzleti forrás érvényességét.

## Lifecycle és evaluation külön fogalom

A lifecycle a case életciklusát, az evaluation a probléma aktuálisan bizonyítható állapotát írja le. Ezekből nem készül egyetlen összekevert `status` mező.

Koncepcionális lifecycle állapotok: `OPEN`, `CLOSED`, `INVALIDATED`.

Koncepcionális evaluation eredmények: `ACTIVE`, `RESOLVED`, `UNDETERMINED`.

### ACTIVE

`ACTIVE`, ha a forrás továbbra is üzletileg érvényes, az authoritative újraértékelés végrehajtható, és a konkrét requirement aktuális nettó beszerzési igénye `net_requirement > 0`. A KM_Production ekkor determinisztikusan bizonyítani tudja a problémát.

### RESOLVED

`RESOLVED`, ha a case továbbra is értelmezhető, az authoritative újraértékelés végrehajtható, és a hiány már nem áll fenn: `net_requirement = 0`.

A RESOLVED nem törlés és nem önmagában lifecycle-lezárás. A case és története megmarad.

### INVALIDATED

Az `INVALIDATED` lifecycle állapot nem jelent megoldott problémát. Az eredeti case üzleti alapja megszűnt vagy úgy változott meg, hogy a case eredeti jelentésében már nem értékelhető.

Lehetséges példák a forrás requirement vagy a kapcsolódó üzleti igény megszűnése, rendelés törlése, illetve az eredeti case-t alkalmazhatatlanná tevő lifecycle-változás. Ezek szemléltető példák, nem végleges transition- vagy reason-szabályok. Az invalidation oka később explicit és auditálható legyen; a konkrét reason-készlet nyitott.

### UNDETERMINED

`UNDETERMINED`, ha a case továbbra is értelmezhető, de az aktuális adatokból a KM_Production nem tudja determinisztikusan bizonyítani sem a probléma fennállását, sem annak megszűnését.

> „Nem tudjuk” nem ugyanaz, mint „nincs probléma”.

Merlin nem találhat ki hiányzó üzleti információt, és nem helyettesítheti feltételezéssel. A rendszernek később közölnie kell az information gap okát. A konkrét reason-készlet ebben a döntésben nincs meghatározva.

## Lifecycle / evaluation viszony

| Lifecycle | Evaluation | Jelentés |
| --- | --- | --- |
| OPEN | ACTIVE | Aktuálisan bizonyított probléma |
| OPEN | RESOLVED | A probléma jelenleg már nem áll fenn |
| OPEN | UNDETERMINED | Az aktuális állapot nem dönthető el megbízhatóan |
| CLOSED | — | Lezárt, történeti Problem Case |
| INVALIDATED | — | Az eredeti Problem Case már nem alkalmazható aktuális problémaként |

A „—” azt jelzi, hogy a case nem kezelhető aktuális problémaként; nem előírás történeti evaluation adatok törlésére. A lifecycle transitionök részletes szabályai, az automatikus vagy manuális lezárás és az esetleges újranyitás nincsenek itt eldöntve.

## Merlin indítás előtti újraértékelés

A „Mit tanácsolna Merlin?” művelet nem támaszkodhat kizárólag az IMF képernyő korábban betöltött állapotára.

`User action → authorization → Problem Case resolver → current evaluation → Merlin`

Merlin aktuális problémamegoldó vizsgálata csak `OPEN + ACTIVE` case esetén indulhat el. `OPEN + RESOLVED` esetén nincs aktuális material-shortage probléma, amelyhez supplier option vizsgálat szükséges. `OPEN + UNDETERMINED` esetén az információhiányt kell jelezni, találgatás nélkül. `CLOSED` és `INVALIDATED` case nem kezelhető aktuális problémaként.

Az authorization és a forrás lifecycle-validálása a backend felelőssége. A meglévő domain policyk és a SupplierOptionService trusted-caller szerződése nem alkotnak kész case-szintű boundaryt; annak implementációja külön feladat. Ez a dokumentum nem vezet be új permissiont és nem tervezi tovább a Merlin futtatását.

## Történeti és aktuális igazság

> A Problem Case megőrzi, hogy milyen problémát és milyen bizonyíték alapján észlelt a rendszer az adott időpontban. Az aktuális resolver azt állapítja meg, hogy a probléma most is fennáll-e.

> „Mit tudtunk akkor?” és „Mi igaz most?” külön adat.

Három külön fogalmat kezelünk:

| Fogalom | Jelentés | Megőrzési alapelv |
| --- | --- | --- |
| Detection Snapshot | Mit tudott a rendszer, milyen problémát, milyen üzleti adatok alapján, mekkora hiánnyal, mikor és milyen forrásból észlelt a case létrejöttekor? | Immutable |
| Current Evaluation | Mi a probléma állapota most, az aktuális authoritative adatok alapján? | Újraszámított |
| Evaluation History | Mely történetileg jelentős evaluationök bizonyítják a case alakulását? | Append-only |

> A detection snapshot immutable. Az aktuális állapot újraszámított. Az evaluation history append-only.

> Miért nyitottuk meg → mi történt vele → mi igaz most.

### Detection Snapshot – minimális történeti tartalom

> A Problem Case megőrzi, hogy miért kongattuk meg a vészharangot.

Koncepcionálisan legalább az alábbi információkat kell megőrizni, amennyiben a case létrejöttekor rendelkezésre állnak és a repository domainje támogatja őket:

- Problem Case stabil azonosító és típus;
- kapcsolódó `MaterialRequirement` azonosító;
- szükséges rendelési/termelési lineage azonosítók;
- `item_id`;
- az akkori üzleti item code / identifier;
- az akkori ember számára olvasható item megnevezés;
- akkori gross/required quantity;
- akkori `net_requirement`;
- `required_date`;
- `unit`;
- detection/evaluation időpont;
- detection source, például `SYSTEM`, később `HUMAN` vagy `MERLIN`;
- netting provenance;
- tényleges netting scope;
- a nettó eredmény értelmezéséhez szükséges kompakt evidence.

Nem snapshotoljuk automatikusan a teljes kapcsolódó adatmodellt. Az authoritative calculation data és a historical display snapshot külön szerepet tölt be: az előbbi az akkori számítás és bizonyítás alapját őrzi, az utóbbi a történet emberi olvashatóságát. A kód/név snapshotja nem válik authoritative számítási adattá.

A minimális történeti tartalom koncepcionális követelmény, nem végleges mezőlista vagy tárolási séma. Nem feltételez a jelenlegi domainben nem létező adatot vagy tartós netting-run azonosítót.

### Current Evaluation

A resolver az aktuális authoritative adatokból újraszámítja, hogy most `ACTIVE`, `RESOLVED` vagy `UNDETERMINED` eredmény áll-e fenn. A current evaluationt nem a detection snapshotból vezeti le, és az új eredmény nem írja felül az eredeti detektálási bizonyítékot.

Az aktuális értékelésre továbbra is a fent rögzített forrásérvényességi, teljes versengő scope netting- és lifecycle-szabályok vonatkoznak.

### Olvasható múlt

> A Nagy Könyv számára nem elég rekonstruálhatónak lennie a múltnak. A múltnak olvashatónak kell maradnia.

A case történetének később akkor is önmagában érthetőnek kell lennie, ha a kapcsolódó törzsadatok megváltoztak. Egy régi eset visszanézésekor közvetlenül érthető legyen:

`Mi volt a probléma? → Miért tekintettük problémának? → Mekkora volt? → Mit vizsgáltunk? → Mit tanácsolt Merlin? → Mit döntött az ember? → Mi történt? → Megoldódott-e? → Mit tanultunk?`

A Merlin advice, human decision, action és lesson konkrét modelljei nem részei ennek a dokumentációs döntésnek.

### Evaluation History – megőrzési szabály

> Evaluationt akkor őrzünk meg, amikor annak üzleti vagy auditértéke van, nem pusztán azért, mert számítást végeztünk.

Az újraértékelés és az evaluation archiválása két külön művelet. Az authoritative resolver futása önmagában nem feltétlenül hoz létre történeti rekordot. A történetileg jelentős evaluationök append-only sorozatot alkotnak.

Tartós evaluation indokolt legalább:

- Problem Case létrejöttekor;
- Merlin vizsgálatának megkezdése előtt;
- emberi döntéshez kapcsolódó állapot rögzítésekor;
- végrehajtott intézkedéshez kapcsolódó tényleges újraértékeléskor;
- érdemi evaluation-változáskor, például `ACTIVE → RESOLVED` vagy `ACTIVE → UNDETERMINED`, illetve egy később engedélyezett visszaaktiválási esetben;
- case lezárása vagy invalidálása előtti releváns utolsó állapot rögzítésekor.

Ezek megőrzési alkalmak, nem új lifecycle-transition szabályok. A visszaaktiválás nincs ezzel engedélyezve vagy véglegesítve. A releváns utolsó állapot megőrzése nem tesz egy CLOSED vagy INVALIDATED case-t aktuális problémává.

Önmagában nem keletkezik history rekord például:

- IMF oldalbetöltéskor;
- dashboard refreshkor;
- egyszerű read/API lekérdezéskor;
- változatlan eredményű technikai újraellenőrzéskor;
- pusztán azért, mert a resolver lefutott.

> Evaluation history akkor keletkezik, amikor később szükségünk lehet annak bizonyítására, hogy egy üzleti esemény vagy döntés pillanatában mit tudott a rendszer a problémáról.

Kerülni kell a redundáns history rekordokat. A létrejöttekori evaluation és a detection snapshot konkrét tárolási kapcsolata ebben a dokumentumban nincs megtervezve.

### Merlin és a történeti evaluation

A Merlin-vizsgálat megkezdése előtti evaluation később egyértelműen összekapcsolható legyen az adott Merlin futással.

> Ne csak azt tudjuk, mit tanácsolt Merlin, hanem azt is, milyen bizonyított helyzetben adta a tanácsot.

Ugyanez az elv vonatkozik később az emberi döntésre és az eredményre:

> Mit tudott a rendszer → mit tanácsolt Merlin → mit döntött az ember → mi történt → mi lett az eredmény.

A `MerlinRun` konkrét implementációja és a kapcsolatok tárolási szerkezete nincs itt megtervezve.

## Illeszkedés a jelenlegi repositoryhoz

A requirement lineage és a read-only netting támogatja az authoritative adatútvonal koncepcióját. A jelenlegi shortage lista és order-risk jelzés tárolt `missing_quantity` értékeket használ; ezek jelzések, nem a tervezett aktuális case evaluation implementációi.

Problem Case modell, saját case ID, lifecycle/evaluation réteg és resolver jelenleg nincs. A netting repository nem érvényesít rendelési lifecycle szerinti case-validitást, ezért az újraértékelés előtti üzleti érvényességi határ még megvalósítandó. Ez implementációs hiány, nem igazolt domainellentmondás; a konkrét invalidation szabályokat a meglévő lifecycle-val külön kell egyeztetni.

## Problem Case authorization alapelvek

`Effective Merlin Permission = User Permission ∩ AI Permission ∩ Case Context`

A Problem Case nem ad új jogosultságot. Csak kontextust ad a már meglévő jogosultságok alkalmazásához.

> The AI is not a trusted backend.

Az authorization a backend felelőssége. A double authorization elve:

- Toolset assembly → authorization
- Tool execution → authorization re-check

A toolset összeállításakor végzett ellenőrzés nem helyettesíti az egyes toolhívások végrehajtásakor szükséges újraellenőrzést.

Merlin nem kap közvetlen adatbázis-hozzáférést, és nem használhat több jogosultságot annál, amit az adott felhasználó az adott Problem Case kontextusában használhat. Ezek az alapelvek nem vezetnek be új permission-neveket vagy új authorization architektúrát.

## Következő nyitott tervezési kérdés

### Konkrét Problem Case authorization mapping

Az authorization architekturális alapelvei elfogadottak. A konkrét alkalmazási szabályokat még meg kell határozni:

- ki láthat Problem Case-t;
- ki indíthat Merlin-vizsgálatot;
- milyen permission szükséges a konkrét case üzleti adatainak eléréséhez;
- hogyan alkalmazandó a fenti jogosultsági metszet a konkrét case-re és toolokra;
- mi történik, ha Merlin olyan Toolt kér, amelyhez a felhasználó vagy az adott case context nem ad jogosultságot.

Ezek részletes kidolgozása külön tervezési lépés; a jelen dokumentum nem tervezi tovább.
