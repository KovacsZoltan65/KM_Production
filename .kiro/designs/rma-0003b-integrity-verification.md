# RMA-0003/B – Integritási döntések és adatbázis-ellenőrzés

## RMA-0003/C1 Closure – aktuális státusz (2026-10-29)

Az alábbi dokumentum megőrzött **történeti baseline és review-bizonyíték**.
A korábbi „ADR változatlan / AM-jóváhagyás függő” szövegek a saját korábbi
feladatuk állapotát jelentik; aktuális státuszként **SUPERSEDED**.
RMA-D6–D11 ACCEPTED; AM-01–AM-04 APPROVED, AM-02-G1–G4 MANDATORY az
[aktuális Accepted ADR-0006](../decisions/0006-rma-repair-management-architecture.md)
3.2–3.4 szerint. C1 lezárása:
[C1 döntés- és kaputerv](rma-0003c1-business-decisions-integrity-gates.md);
következő munka: [C2 előkészítési terv](rma-0003c2-detailed-schema-review-plan.md).

A submit-held credit-javaslatot az approval-only D6/AM-03, a közvetlen returnből
available ágat a kötelező quarantine D8/AM-02 váltja fel. Az external->external
általános tiltásának kizárólag explicit return-intake type/purpose kivétele van,
AM-02-G1–G4 mellett; nem általános transfer/scrap-jog. A shipmentenként egyszeri
posting nem tilt több valódi, D9 szerint engedélyezett újraküldést.
Az alábbi régi findingok és teszteredmények nem lettek újraírt C1/C2 sikerállítások.

Státusz: **REVIEW – részleges SQL-bizonyítékkal; NOT MIGRATION READY**.
Dátum: 2026-10-29. Branch: `feature/returns-repair-management`.
HEAD: `ea568deded91a1c256edbefdfbec7cf5be2ce096`.

Autoritatív: [Accepted ADR-0006](../decisions/0006-rma-repair-management-architecture.md).
Felülvizsgált terv: [RMA-0003/A](rma-0003a-data-model-integrity-design.md).
Az ADR változatlan; az A terv továbbra is PROPOSED. Alkalmazáskód, alkalmazásmigráció,
szolgáltatás és meglévő üzleti adat nem változott. Nincs commit, push vagy merge.
Kezdéskor csak az A dokumentum volt előzetesen nem követett változás.

## RMA-0003/C1 – történeti eredmények és aktuális döntések (2026-10-29)

E review findingjai és lefutott ellenőrzései változatlan történeti bizonyítékok.
Az új **ACCEPTED RMA-D6–D11** döntéseket és az aktuális **PROPOSED** technikai
megoldásokat a [C1 kaputerv](rma-0003c1-business-decisions-integrity-gates.md) tartalmazza.
A D6 szerinti foglalás approvalkor történik; a korábbi submit-held protokoll
felülírt javaslat. D8 alapján minden fizikai replacement return karanténba kerül,
közvetlen available visszavétel nincs. D7/D9/D10/D11 a korábbi üzleti OPEN
pontokat az ott rögzített értelemben lezárta; a technikai proof/DDL/rollout kapuk
maradnak. G1/G2/G3 aktuális státusza C1-ben található.
A B 35/35 eredményét nem futtattuk újra és nem írtuk át; az új C1 séma,
claim-finalizer vagy üzleti folyamat sikerét ezek nem bizonyítják.
Az ADR változatlan; az AM-01–04 javaslat külön jóváhagyást igényel.

## 1. Vezetői összefoglaló

**35/35 végrehajtott SQL-prototípus sikeres: 24 MySQL, 11 SQLite.**
Ebből tíz MySQL-próba két külön, egyidejűleg nyitott kapcsolattal és átfedő
tranzakcióval ellenőrzi az egyedi kulcsokat vagy a parentzáras guard alapját.
Ezek nem a még nem létező RMA-szolgáltatások integrációs tesztjei.

A tíz finding közül **kettő tervszinten feloldott**: FND-03 és RMA-REV-02.
Nyolc feltételes/részben igazolt vagy OPEN marad; ez nem nyolc elhallgatott
ADR-ellentmondás, hanem implementációs és adatátállási kapu. Kritikus marad
a claim törlésének védelme, a shared inventory fogyasztók átállítása,
a visszaérkezés/karantén rendezése, a kredit felszabadítási policy és a
reprezentatív legacy audit.

A terv indokolt változásai: warranty composite FK-k; release-kísérletenkénti
egyediség és előzmény; külön return-megfigyelés és rendezés; ownership occurrence
identity; egyetlen új `repair_diagnostic_credit_allocations` tábla. Így **23 új
és 7 bővítendő meglévő tábla** a javaslat. Üzleti döntést e review nem fogad el
a megrendelő helyett.

## 2. Bizonyíték és reprodukálás

- [Önálló PDO-verifikáció](verification/rma-0003b-verify.php):
  Laravel bootstrap nélkül, kizárólag dedikált tesztadatbázisra.
- [Gépi futási eredmények](verification/rma-0003b-results.json):
  verziók, kapcsolat-ID-k, minden teszt, audit-SQL, tesztadat-számok és cleanup.
- A prototípusok a javasolt invariánsok szűkített SQL-mechanizmusait vizsgálják;
  a mezőnevek és INT-típusok nem a teljes Laravel-migráció másolatai.

A történeti B-futáskor használt PowerShell-parancs (nem a hardening utáni konfiguráció):

```powershell
$env:TEST_MYSQL_PORT='3306'
php -d xdebug.mode=off .kiro/designs/verification/rma-0003b-verify.php
```

A történeti futás régi tesztkörnyezeti konfigurációját az alábbi rögzített
metaadatok írják le. A jelenlegi script aktuális, kizárólag környezeti változós
konfigurációja a 2.1 fejezetben található.

MySQL: **8.4.7**, **REPEATABLE-READ**, **InnoDB**; adatbázis
`km_production_testing`, account `km_testing@localhost`.
SQLite: **3.53.4**, `:memory:`, `PRAGMA foreign_keys=1`.
Minden MySQL CREATE véletlen, futásonként új prefixet használ, IF NOT EXISTS nélkül.
Csak a sikeresen létrehozott saját táblákat törli fordított függőségi sorrendben.
FK-ellenőrzést nem kapcsol ki; a saját ciklikus próba FK-ját célzottan bontja.
**A mentett futás mind a 33 saját tábláját eltávolította.**
Normál táblák szükségesek a két kapcsolat közös láthatóságához; nem alkalmazástáblák.

### 2.1. Aktuális, credential nélküli konfiguráció

A credential-hardening után mind az öt változó **kötelező**, alapérték nélkül:

| Környezeti változó       | Követelmény                                                                                                     |
| ------------------------ | --------------------------------------------------------------------------------------------------------------- | ------------------------------------- |
| `RMA_VERIFY_DB_HOST`     | Kizárólag helyi loopback IPv4-cím; a meglévő izolációs korlát megmarad.                                         |
| `RMA_VERIFY_DB_PORT`     | Explicit egész TCP-port, 1–65535.                                                                               |
| `RMA_VERIFY_DB_NAME`     | Explicit, eldobható verification adatbázis; `^km*production*(?:test                                             | testing)(?:\_[a-z0-9]+)?$` allowlist. |
| `RMA_VERIFY_DB_USER`     | Helyben konfigurált, dedikált verification principal, kizárólag az izolált tesztadatbázisra szükséges jogokkal. |
| `RMA_VERIFY_DB_PASSWORD` | A dedikált principal helyi titka; nincs beégetett érték vagy jelszó-fallback.                                   |

Helyileg a hívó folyamat környezetébe add át a beállításokat megbízható helyi
titokkezelőből vagy szülőfolyamat-konfigurációból. Credential ne kerüljön
repositoryba, dokumentációba, shell-historyba vagy parancssori argumentumba.
A script nem olvas és nem módosít `.env` fájlt, nem bootolja a Laravelt,
nem használ `DB_*` vagy régi `TEST_MYSQL_*` fallbacket, és nem veheti át
automatikusan a production hozzáférést.

Az adatbázis kizárólag eldobható és izolált legyen; az allowlistelt név
önmagában nem bizonyítja a jogosultságok izolációját. A korábbi username-fixálás
helyett a helyileg biztosított dedikált principal szükséges. Hiányzó/hibás
beállítás értékek nélkül, csak változónévvel/teendővel jelez hibát.
Connection/driver hibák nyers szövege és stack argumentumai nem kerülnek
verifikációs kimenetbe; a script a jövőbeli eredményből az account, DB-név és
port kapcsolati metaadatokat is elhagyja. A SQL-próbák és állításaik megmaradtak.

A credential-hardening során **nem futott** a verification script, MySQL vagy
SQLite kapcsolat nem jött létre. A történeti eredmény-JSON **nem regenerált,
bájtszinten változatlan**. A script korábbi dokumentumokban rögzített hash-ei
a hardening előtti történeti baseline-t jelentik; a jelen korrekció a credential-
és hibakimenet-kezelést módosította, nem új SQL-futás eredménye. Az alábbi korábbi futási eredmények és account
metaadatok történeti bizonyítékok, nem aktuális kapcsolati konfiguráció.

## 3. Findingok

A „feloldott” itt tervszintű technikai megoldást jelent. Üzleti működést csak
későbbi service/policy/migrációs teszt bizonyíthat.

| Finding    | Súlyosság | Besorolás       | Bizonyíték és érintett táblák                                                                                                                          | Megoldás és implementációs kapu                                                                                                                                                      |
| ---------- | --------- | --------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| FND-01     | Kritikus  | PARTIAL         | A 3.3; mindkét motor FND01-composite, MySQL C1/C6. `active_repair_claims`, requests/items/orders. Raw claim DELETE engedett.                           | PK(serial)+U(member)+member/serial FK; atomikus nyitás és proof-alapú close parentzár alatt. Korlátozott törlési út és negatív jogosultságteszt nélkül nincs élesítés.               |
| FND-02     | Kritikus  | PARTIAL         | Mindkét motor nullable/sentinel/sentinel-fk és scope-owner; C2/C4. Balances/movements/reservations/custodies. Üres tesztadat-audit.                    | Explicit nem-null sentinel, receipt-history U és custody izoláció. Reprezentatív duplikátum/owner audit és minden consumer átállítása előfeltétel.                                   |
| FND-03     | Magas     | RESOLVED-DESIGN | A 3.11–3.12; mindkét motor warranty. Assessments, instances, evidences, rules, orders/reservations.                                                    | Serial–item–evidence–rule composite FK-k, globális order-version és serialszűrt current. Order-subject guard és D4 service-tesztek kötelezők.                                        |
| FND-04     | Kritikus  | OPEN/PARTIAL    | Régi U(member,serial,role), U(reservation) kizárta a redispatch-et; attempt-próba mindkét motoron. Releases/delivery/ownership/movements/reservations. | Attempt+previous+command/move U; proofos return és külön reconciliation. Karantén/ismeretlen tulajdon/felhatalmazás policy és teljes visszaút előtt csereposting tiltott.            |
| FND-05     | Magas     | OPEN/PARTIAL    | A korábbi order credit-pointer nem történeti allokáció; aktív kulcs próba mindkét motoron. Orders/quotes/items/credit_allocations/histories.           | Új minimális allokációs tábla, source-order mutex. Draft nem felhasználás. Held/applied felszabadítás kereskedelmi döntése nélkül az érintett út blokkolt.                           |
| FND-06     | Kritikus  | PARTIAL         | Tényleges repository/service-kód, lásd 7. fejezet; C8 csak mutex-prototípus. Requirements/reservations/PR items/sources.                               | Legacy customer-order-item+cikk és repair-owner+cikk külön; source mint lefedettség, nem új teljes igény minden revíziónál. Nettósítás/aggregáció regresszió előtt nincs repair MRP. |
| FND-07     | Magas     | PARTIAL         | 25 constraint/atomicity prototípus és C1–C10; konkrét action/error/index/cycle eredmény. Minden FK/U/CHECK érintett.                                   | Részmechanizmusok igazoltak. Teljes production-típusú Laravel DDL, SQLite rebuild, migration rollback, collation/overflow és query-plan még kapu.                                    |
| RMA-REV-01 | Kritikus  | PARTIAL         | Trigger UPDATE/DELETE elutasítás és correction INSERT mindkét motoron, atomic rollback. EVENT táblák, quotes/proof/audit/claim.                        | Append-only+korlátozott runtime jogok+parentzáras szolgáltatás. Trigger/procedure és üzemeltetési jogosultságok teljes tervét alkalmazásfázis előtt igazolni kell.                   |
| RMA-REV-02 | Magas     | RESOLVED-DESIGN | Régi üzleti szövegkulcs túlszigorú; ismételt trigger új occurrence sikeres, retry elutasított. Ownership events/commands.                              | U(namespace,source_event_key), U(member,serial,event_sequence), payload-hash és teljes korrekciós lánc. Késői proof nem ír régi tényt.                                               |
| RMA-REV-03 | Kritikus  | PARTIAL         | Tényleges shared író/olvasó leltár a 8. fejezetben. Balance/reservation/MRP/PR/receipt/report.                                                         | Közös posting szerződés és minden availability olvasó átállítása, regresszió+cache igazolás. RMA posting csak az utolsó kompatibilitási kapu után.                                   |

### FND-01 – Claim és ügyzárás

A PK kizárja ugyanazon serial két claimjét; a composite FK rossz member/serial
párt tilt. A FK nem bizonyítja, hogy minden nyitott taghoz van claim, és nem
tiltja a child claim önálló DELETE-jét. A kész termék visszaadása után is az
**egész ügy lezárásáig** él a claim.

Nyitás: case/member/order/claim/command/audit egy transaction. Egy serialütközés
az egész ügy nyitását rollbackeli. Zárás: case-lock elsőként; gyermekírók ugyanazt
a parentet zárolják, majd aktuális nyitott állapotot ellenőriznek. Disposition,
eredeti custody, minden shipment/return/ownership obligation, reservation,
művelet/idő/anyag, authorization és QC bizonyítékait kell ellenőrizni; státusz
önmagában elégtelen. Claim törlés csak ezután, ugyanazon transactionben.
Raw SQL DELETE-hez a runtime szerepkör nem kaphat általános jogosultságot;
ellenőrzött close-út szükséges (REV-01).

### FND-02 – NULL-safe készletkulcsok

A jelenlegi U(item,location,nullable_batch) mindkét motoron több batch nélküli
sort enged. Javaslat: `batch_identity NOT NULL`, NULL batchhez 0, ismert pozitív
batchhez az ID; explicit CHECK mindkét ágra, U(item,location,batch_identity).
A 0 nem batchrekord/FK. A valódi nullable batch-FK RESTRICT/NO ACTION marad.
Együttes CHECK+FK+U létrejött mindkét motoron; hibás sentinel/hiányzó parent tiltott.
Tárolt sentinel javasolt a két motor eltérő generated-column DDL-je helyett.
Ez nem automatikus legacy backfill-jóváhagyás.

U(receipt_request_item_id) a movement történetben marad custody törlése után is;
nem elegendő az aktuális custody U. Egy eredeti átvétel és a csere visszaérkezése
külön eseményazonosság, az utóbbi nem kap új original receipt-kulcsot.
Custody nem normal stock, nem sale/production/MRP-forrás.

Reservationnél repair owner kizárólagos; legacy customer+production együtt
megengedett, ha összetartozik. Owner nélküli sor tiltott. Aktív lekötés
`reserved-consumed-released`; serial/company-scope/origin és más foglalások
vizsgálata service-guard. Az XOR CHECK nem igazolja a két legacy hivatkozás egyezését.

### FND-03 – Garanciális tárgy

A 3.12-ben `subject_item_id` és három composite FK került a tervbe.
Handover U(id,serial), instance U(id,item), rule U(id,item) a parent kulcsok.
A subject lehet eredeti vagy bizonyított cserefoglaláshoz tartozó serial;
más order idegen serialja nem engedhető meg. Ezt a membership-guard ellenőrzi,
nem a három FK.

U(order,version) megmarad; current = maximum version **az adott order+serial
szűrésében**, nem pusztán order maximum. Mindkét serial története megőrzött.
Másik serial átadási bizonyítéka vagy másik cikk rule-ja raw INSERT-ben elutasított.
Hiányzó evidence/rule pending döntést engedhet; nem vált ki vélelmezett warranty-t.
Csere nem indít automatikusan új időszakot (D4).

### FND-04 – Visszatérés és újraküldés

| Esemény                                       | Fizikai könyvelés                                         | Tulajdon / obligation                                        |
| --------------------------------------------- | --------------------------------------------------------- | ------------------------------------------------------------ |
| Reserved / prepared                           | Reserve 0; valódi belső transfer forrás -1/cél +1         | Company; nincs ownership-transfer                            |
| Dispatched attempt N                          | Company -> external, -1 egyszer                           | Külön ownership-tény, alkalmazandó feltétellel               |
| Delivery failed / recall requested            | 0                                                         | Nem fizikai return, nem cancellation, nem reversal           |
| Physically returned                           | Megfigyelés+proof; önmagában nem szabad balance           | Hely/állapot/tulajdon ellenőrzésére vár                      |
| Return reconciled, igazolt vállalati tulajdon | Valódi inbound +1 egyszer, az előző outboundra hivatkozva | Csak ellenőrzött elérhetőség vagy blokkolt karantén          |
| Ownership reversal                            | 0                                                         | Külön igazolt jogi esemény; returnből nem következik         |
| Authorized redispatch attempt N+1             | Új valódi outbound -1 egyszer                             | Korábbi return/ownership/QC/authorization ellenőrzött        |
| Final disposition                             | Nem helyettesítő stock adjustment                         | Eredeti termék sorsa és az utolsó csereút egyaránt rendezett |

Az A terv attemptenkénti U-val váltja a túlszigorú release-kulcsokat; U(move),
U(command), U(previous_release) megmarad, a retry nem lehet új attempt.
Két külön commanddal előállított attemptet a parentzár és a megengedett előzmény
guardja is védi. A kulcsok önmagukban nem engedélyeznek újraküldést.

A korábbi fogyasztott reservation mennyisége nem nullázható vagy aktiválható újra:
történeti tény. Új fizikai kiadáshoz új mennyiségi reservation kell ugyanazon
order/serial megőrzött láncában; az aktív order-obligation átadása atomikus.
E részletes reservation-stage átmenet teljes DDL/service-próbája még kapu.

**OPEN:** ismeretlen vagy ügyféltulajdonban visszaérkező replacement fizikai
karanténprojekciója; a karanténból felszabadítás szerepköre/QC-je; redispatch
kereskedelmi felhatalmazása; tényleges ownership reversal feltétele. A három
elfogadott scope nem bővül negyedikkel. Igazolt company-stock karantén csak
minden availability fogyasztó által tisztelt blokkolással engedhető; a jelenlegi
normál balance-olvasók ezt nem biztosítják. Ismeretlen tulajdonú fizikai érkezés
proofja/helye megőrzendő, de nem könyvelhető vélelmezett szabad company stockba.
Ezek rendezéséig a visszaútra épülő csereposting bevezetése tiltott.

### FND-05 – Diagnosztikai kredit

Free/fixed/time-based díj változatlan. Time-based díj igazolt actual idő és
jóváhagyott egység/rounding alapján; becslés nem felhasználható díjbizonyíték.
Két draft becslése nem dupla tényleges credit. A submitted tartalom immutable;
a régi approval további munka engedélyezésére nem használható új draft mellett.

Az egyetlen order-pointer nem tudja megőrizni a régi allocation/release történetet,
és a quote-line FK önmagában nem tilt két forrásfelhasználást. Minimális új tábla:
A 3.23. U(active_source_order_id) + explicit NULL/state CHECK; composite quote/order
és line/quote/source FK. A pénzügyi mezők rögzülnek, állapotváltozás append-only
status_historyval atomikus. Egy forrásra egy held/applied, több released előzmény.

Technikai javaslat: submitkor held, approvalkor applied; draftban nincs allocation.
Reject/expiry/supersede/cancel során csak a külön elfogadott szabály szerinti
release, bizonyítékkal. Approved/applied allokációt új draft vagy cancellation
nem szabadíthat fel automatikusan. Ugyanazon source-order zár alatt történik
régi release és új allocation. Nem vezetünk be részleges jóváírási, adózási,
számlázási vagy visszatérítési politikát; ezek nem DB-egyediségi döntések.
**OPEN:** aktiválási és felszabadítási üzleti feltételek, különösen már applied
credit esetén. A prototípus a kulcsot igazolta, a release jogszerűségét nem.

## 4. Constraint-verifikáció

| Mechanizmus                             | MySQL                                                                     | SQLite                           | Korlát                                              |
| --------------------------------------- | ------------------------------------------------------------------------- | -------------------------------- | --------------------------------------------------- |
| Nullable UNIQUE                         | Több NULL elfogadott, duplikált nem-NULL tiltott                          | Ugyanez                          | Inaktív keyhez jó, balance batchhez rés             |
| Sentinel CHECK + U + valódi FK          | Pozitív és negatív próba sikeres                                          | Ugyanez                          | Legacy deduplikálás és exact típusok később         |
| Claim composite FK                      | Rossz tuple/parent DELETE tiltott                                         | Ugyanez                          | Child DELETE megengedett                            |
| Rész-NULL composite + CHECK UNKNOWN     | Bypass reprodukált; explicit NULL ág tiltja                               | Ugyanez                          | Minden opcionális tuple-ra alkalmazandó             |
| Warranty composite és version U         | Subject/item/evidence/rule mismatch tiltott                               | Ugyanez                          | Order membership guard                              |
| Shipment attempt / ownership occurrence | Új valós occurrence engedett; retry tiltott                               | Ugyanez                          | Recall és ownership policy nélkül nem posting       |
| Credit active key + state CHECK         | Dupla aktív tiltott, released történet megmarad                           | Ugyanez                          | Line/amount/policy teljes service-teszt hiányzik    |
| Owner XOR, scope enum                   | Pozitív/negatív sikeres                                                   | Ugyanez                          | Nem bizonyít scope-helyes balance írást             |
| Append-only trigger                     | UPDATE/DELETE tiltott; correction INSERT sikeres                          | Ugyanez                          | Csak eldobható prototípus; nincs alkalmazástrigger  |
| Transaction fault rollback              | Nincs részlegesen megmaradt fact                                          | Ugyanez                          | Nem valódi domain/audit integráció                  |
| CHECK-elt FK + RESTRICT / NO ACTION     | DDL elfogadva                                                             | Alap immediate FK tesztelve      | SQLite actionmátrix külön nem futott                |
| CHECK-elt FK + CASCADE / SET NULL       | Mindkettő elutasított, driver 3823                                        | E kombináció külön nem futott    | Nem lehet Laravel cascade/nulldelete default        |
| CHECK(auto_increment PK)                | Elutasított, driver 3818                                                  | MySQL-specifikus                 | PK nem CHECK operandus                              |
| Nullable FK-ciklus                      | Create, ALTER FK, insert-null, link, invalid-target sikeresen ellenőrzött | ALTER/rebuild nem futott         | Teljes migrációs sorrend még gate                   |
| Child FK-index, engine                  | SHOW INDEX: claim PRIMARY/member/member_2; InnoDB                         | Production query-plan nem futott | Teljes explicit indexmátrix/duplaindex-audit később |

A mechanizmusok tesztszáma nem azonos az összes megvizsgált negatív INSERT/UPDATE
számával; egy prototípus több állítást tartalmaz. Generated-column stratégiát nem
futtattunk; a választott explicit sentinelhez nem szükséges. Bigint/decimal
határok, ASCII-binary collation, JSON/UTC precision és Laravel schema builder
fordítás még nem igazolt. SQLite integer/numeric affinity nem bizonyít MySQL
DECIMAL pontosságot vagy MySQL unsigned tartományt.

## 5. Konkurencia – C1–C10

A mentett eredmény külön connection-ID-ket tartalmaz. A tranzakció A előbb
létrehozza a konfliktust és nyitva marad; B közben ugyanarra az egyedi kulcsra
ír vagy ugyanazt a parentet zárolja. B lock timeout (1205), rollback; A commit;
B új transactionben retry után duplicate (1062) vagy aktuális tiltó állapot.
A mért várakozások a JSON-ban vannak; mind legalább 0,8 másodperc.
A 1205 timeout és retry igazolt, deadlock 1213 kezelés külön nem futott.

| Eset                   | Végrehajtott SQL-prototípus                                                     | Még nem végrehajtott valódi domain-ellenőrzés                                        |
| ---------------------- | ------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------ |
| C1 claim               | U(serial)-analóg konfliktus, retry duplicate                                    | Többtagú atomikus ügy, FK+claim+audit és permission együtt                           |
| C2 receipt             | Receipt-key-analóg U, egy tény marad                                            | Pontosan egy movement/custody; normal balance 0, későbbi új receipt tiltott          |
| C3 release             | Attempt-key-analóg U                                                            | Egy outbound/levonás, ismételt command és külön command, proof/redispatch guard      |
| C4 replacement reserve | Aktív serial-key-analóg U                                                       | Verseny production consume-val, company scope és más owner foglalása                 |
| C5 material consume    | Balance parent-lock, 10-ből 7 után current read=3; második 7 guardoltan tiltott | Több balance/batch, aktív reservationök, exact qty, movement/audit rollback          |
| C6 close/child         | Case-mutex analóg; retry closed állapotot lát                                   | Minden gyermekíró ugyanazt a parentet zárja; teljes proofos close és claim delete    |
| C7 quote/start         | Order-mutex analóg; retry nem-approved állapotot lát                            | Approved/rejected/expired/superseded race és scopeváltozás                           |
| C8 procurement         | Demand-mutex analóg; retry már lefedett állapotot lát                           | Tényleges source U/revision/delta/grouping/cancellation és PR-számozás               |
| C9 install             | Child-mutex analóg; retry már beépült állapotot lát                             | Két külön parent, install/remove correction, gráfciklus és több child zársorrend     |
| C10 time               | Active-assignment-key-analóg U                                                  | Valódi assignment/employee, finish/start, intervallum- és esetleges globális átfedés |

**Executed:** tíz átfedő MySQL SQL-mechanizmus-próba. **Designed, not executed:**
a jobb oszlop valódi alkalmazástesztjei és az A 13. fejezete. **BLOCKED for rollout:**
az RMA-szolgáltatások/migrációk nem részei e feladatnak, a nyitott policyk hiányoznak.
MySQL elérhetőség nem blokkolt. SQLite concurrency nem futott, eredménye egyébként
sem volna MySQL sorzárolási bizonyíték. A prototípus timeoutos barrierje nem vizsgálja
a B írásának automatikus folytatását A commitja után; azt integrációban külön kell mérni.

## 6. Történeti változatlanság és jogosultság – RMA-REV-01/02

Javasolt production megoldás: rétegzett védelem. EVENT táblák append-only
korrekciós lánccal, runtime INSERT/SELECT joggal; UPDATE/DELETE és DDL csak külön
üzemeltetési/migrációs principalnak. Immutable EVENT táblákon kisméretű, tesztelt
tiltó triggerek második védelemként alkalmazhatók. A konkrét deployment még javaslat,
nem létrehozott trigger/jogosultság. DBA által végezhető DDL ellen nem abszolút védelem;
hozzáférés- és DDL-audit kell.

Mutable táblákon általános UPDATE tiltás hibás lenne. Claim delete ellenőrzött
close-procedure vagy más elkülönített, szűk írási út; minden parentzár/proof guard
megtartásával. Trigger önmagában nem valósít teljes ügyengedélyezést. Submitted quote,
lezárt time és credit állapotmezők célzott oszlop/átmenet-védelmet igényelnek.
Production credentialek/grantok ellenőrzése nem történt, ezért a jogvédelmi kapu nyitott.

Command/domain/movement/projection/history/audit ugyanazon transaction és adatbázis;
audit-írási hiba rollback. Külső küldés csak commit után megbízható retryval/outbox
tervvel. Új domain táblán nincs soft delete; dokumentumok meglévő soft delete-je,
verzió/fájlcsere és direkt letöltési permission külön retention guardot kér.
Correction új tény, az eredeti sor és ok/proof/actor megmarad.

Ownershipnél a contractual_trigger üzleti feltétel, nem occurrence ID.
U(namespace,source_event_key) a retry-védelem, U(member,serial,event_sequence)
a tartós rögzítési sorozat. Egy későbbi jogszerű transfer/reversal/correction
azonos triggerrel új occurrence lehet. Az idempotenciakulcs újragenerálása
nem oldhat fel authorization- vagy hatályossági guardot. A history lekérdezés
a rögzítési sorrendet, hatályos időt és supersedes láncot egyaránt megőrzi.

## 7. Meglévő MRP szemantikája – FND-06

Ellenőrzött források:
[MaterialRequirementRepository](../../app/Repositories/Admin/MaterialRequirementRepository.php),
[MaterialRequirementService](../../app/Services/Admin/MaterialRequirementService.php),
[StockReservationRepository](../../app/Repositories/Admin/StockReservationRepository.php),
[PurchaseRequisitionService](../../app/Services/Admin/PurchaseRequisitionService.php),
[PurchaseRequisitionRepository](../../app/Repositories/Admin/PurchaseRequisitionRepository.php).

- Requirement updateOrCreate kulcsa **customer_order_item_id+required_item_id**,
  nem production_order_id. A kiszámított mennyiség egy konkrét production orderből
  származik; több PO ugyanarra az ownerre történő aggregációja nem bizonyított.
  Repair bevezetése nem írhatja át e jelentést és nem duplázhatja ownerenként.
- Reservationnél customer/production filter együtt is él; legacy hivatkozásokat
  nem lehet egyszerű „pontosan egy nem-NULL owner oszlop” CHECK-kel elrontani.
  Repair ág kizárólagos, legacy két mező egyezését külön guard ellenőrzi.
- A jelenlegi MRP fizikai stock mínusz összes aktív reservation, majd saját
  demand-reservation figyelembevételével számol. Új modellnél csak fennmaradó
  lekötés és company stock számít; saját lekötést egyszer nettósítunk.
- PR generation missing>0, source nélküli requirementeket zárol, cikk+unit szerint
  csoportosít. A PR item direct material_requirement_id az első source képviselője;
  a sources tábla minden requirementet külön mennyiséggel rögzít. A direct pointer
  nem új, másodszor hozzáadandó demand.
- Jelenleg whereDoesntHave(sources) kizár minden korábban generált requirementet,
  akkor is, ha hiánya megnő. Revision önmagában nem megoldás: minden új revisionre
  teljes missing generálása dupla beszerzés lehet. Stabil generation identity és
  parent-lock mellett a hatályos, még nem duplán beszámolt procurement coverage
  levonásával képzett **különbözet** tervezendő.
- Cancelled/deleted/source korrekció és receipt mikor old coverage-et, valamint
  több PO aggregációja külön jóváhagyandó; source törlése nem retry-mechanizmus.
  Soft-deleted legacy sorok owner-U ütközése auditálandó; élő sort nem szabad
  history törlésével „felszabadítani”. A meglévő PR-számozás count+1 mintája is
  teljes concurrency regressziót igényel; e review nem refaktorálja.

## 8. Inventory consumer rollout – RMA-REV-03

A leltár közvetlen table/model keresésből és dependency-használatból készült.
Az alábbi meglévő service/repository csoportok mind a feature-engedélyezés előtti
regressziós kapu részei; delegáló szolgáltatásnál nem feltétlenül kódmódosítás,
hanem az új szerződés átvezetésének és kimenetének bizonyítása kell.

| Forrás                                                                                                                                                                                                                                                                                                                                                             | Átvezetendő invariáns / regresszió                                                                                      |
| ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ----------------------------------------------------------------------------------------------------------------------- |
| [StockBalanceRepository](../../app/Repositories/Admin/StockBalanceRepository.php)                                                                                                                                                                                                                                                                                  | firstOrCreate sentinel; company-only írás/összeg, batch/hely, remaining reservation, serial egyeztetés                  |
| [StockReservationRepository](../../app/Repositories/Admin/StockReservationRepository.php), [StockReservationService](../../app/Services/Admin/StockReservationService.php)                                                                                                                                                                                         | Owner, serial aktív U, remaining quantity, cél és release/consume idempotencia; ügyfüggetlen release tiltása            |
| [ProductionTaskMaterialService](../../app/Services/Admin/ProductionTaskMaterialService.php)                                                                                                                                                                                                                                                                        | Fizikai qty>=q nem elég; más foglalás védelme, pontos owner/batch/serial, partial consume és audit                      |
| [MaterialRequirementService](../../app/Services/Admin/MaterialRequirementService.php), [MaterialRequirementRepository](../../app/Repositories/Admin/MaterialRequirementRepository.php)                                                                                                                                                                             | Legacy kulcs/aggregáció, repair owner, új nettósítás, kapcsolatok/szűrők                                                |
| [PurchaseRequisitionService](../../app/Services/Admin/PurchaseRequisitionService.php), [PurchaseRequisitionRepository](../../app/Repositories/Admin/PurchaseRequisitionRepository.php)                                                                                                                                                                             | Coverage-delta, source grouping, unit, revision, retry, representative direct pointer                                   |
| [GoodsReceiptService](../../app/Services/Admin/GoodsReceiptService.php)                                                                                                                                                                                                                                                                                            | Purchase receive scope+balance atomikus, új sentinel; nem eredeti customer receipt                                      |
| [StockMovementRepository](../../app/Repositories/Admin/StockMovementRepository.php)                                                                                                                                                                                                                                                                                | Új típusok, scope és valódi source/ref, történetlekérdezés nem scope-vak                                                |
| [InventoryQueryService](../../app/Services/Admin/InventoryQueryService.php)                                                                                                                                                                                                                                                                                        | Új owner/custody és remaining mennyiségek továbbadása, legacy listák                                                    |
| [ProductionPlanService](../../app/Services/Admin/ProductionPlanService.php)                                                                                                                                                                                                                                                                                        | Requirement/reservation orchestration több PO-val és repair demand mellett                                              |
| [ProcurementDashboardService](../../app/Services/Admin/ProcurementDashboardService.php)                                                                                                                                                                                                                                                                            | Közvetlen requirement SUM/count; repair/legacy unit és coverage jelentés                                                |
| [ReportingRepository](../../app/Repositories/Admin/ReportingRepository.php), [ReportingService](../../app/Services/Admin/ReportingService.php), [DashboardService](../../app/Services/Admin/DashboardService.php)                                                                                                                                                  | Direct balances/reservations/requirements összeg; physical/available/custody/transit külön, cache                       |
| [ManufacturingIntelligenceRepository](../../app/Repositories/Admin/ManufacturingIntelligenceRepository.php)                                                                                                                                                                                                                                                        | Balance/reservation netting, production-only movement típusok; customer-order inner join ne tüntesse el a repair igényt |
| [ManufacturingIntelligenceService](../../app/Services/Admin/ManufacturingIntelligenceService.php), [MaterialForecastService](../../app/Services/Admin/MaterialForecastService.php), [ProcurementRecommendationService](../../app/Services/Admin/ProcurementRecommendationService.php), [ProductionRiskService](../../app/Services/Admin/ProductionRiskService.php) | Delegált forecast/recommendation/risk adatforrás és cache-regresszió                                                    |
| [BottleneckAnalysisService](../../app/Services/Admin/BottleneckAnalysisService.php), [QualityTrendService](../../app/Services/Admin/QualityTrendService.php), [SupplierPerformanceService](../../app/Services/Admin/SupplierPerformanceService.php)                                                                                                                | Közös intelligence repository fogyasztói: a nem érintett jelentések változatlansága                                     |

További szerződésfelületek: model relations/casts, repository interface-ek,
FormRequest/Policy owner-validáció, inventory/procurement controller presenterek,
cache-invalidálás ([BusinessCacheInvalidator](../../app/Services/BusinessCacheInvalidator.php)).
Ezek nem kapnak üzleti logikát controllerben; a meglévő rétegzés megmarad.
Kapu: minden writer/reader együtt kompatibilis, majd scope backfill/contract,
és csak ezután engedélyezett RMA készletposting. Custody bevezetése mellett
scope-vak régi alkalmazásverzióra rollback nem biztonságos.

## 9. Read-only legacy audit

**Végrehajtva:** a script kilenc SELECT auditja, MySQL read-only transactionben;
minden hiba-count 0. **Lényeges korlát:** a hét számlált forrástábla mind üres.
Ez kizárólag a lekérdezések e tesztsémán való futtathatósága, nem legacy megfelelőség.

A JSON minden végrehajtott SQL-t és countot tartalmazza: orphan production,
duplicate balances, owner nélküli reservation, reserved<=0, hiányzó serial/origin,
orphan procurement source, genealogy self-edge/több parent, negatív balance.
A production_order_id megléte nem azonos a saját gyártás bizonyításával.

Reprezentatív snapshoton, olvasási joggal, egységes audit-időpontra előírt specifikáció:

| Audit                   | Read-only vizsgálat                                                                                                                                                                        | Kézi rendezés / blokkoló feltétel                                                                               |
| ----------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | --------------------------------------------------------------------------------------------------------------- |
| Orphan production       | LEFT JOIN instance -> production_orders; missing parent; soft-deleted parent; instance.item_id és PO.item_id eltérés                                                                       | Eredeti rendelés/gyártási tény bizonyítéka, nem feltételezett FK-backfill                                       |
| Duplicate balances      | GROUP BY item/location/item_batch, HAVING COUNT(\*)>1; NULL batch is egy csoport; quantity+unit+location és serial összegvetés                                                             | Nem automatikus SUM/DELETE; movement baseline és fizikai leltár alapján jóváhagyott rendezés                    |
| Reservation owner       | Mindkét legacy owner NULL; hiányzó/soft-deleted customer/production; PO.customer_order_item_id nem egyezik a reservation ownerrel                                                          | Célbizonyíték, aktív owner helyreállításának külön jóváhagyása                                                  |
| Reservation quantity    | reserved<=0; active reserve > fizikai készlet; owner/item/unit/location mismatch; több aktív foglalás ugyanazon serialra, ha már van serialmező                                            | Régi consumed/released qty nem következtethető pusztán státuszból; manuális egyeztetés                          |
| Movement reconciliation | Típusonként előjel/forrás/cél/qty/batch/serial/source ellenőrzés; igazolt nyitóállapot + company oldalankénti delta vs balance és serial fizikai projekció                                 | Ismeretlen correction/reservation jelentés vagy nyitóbaseline blokkol; nincs általános SUM(quantity) backfill   |
| Serial origin           | NULL/üres/orphan origin mellett PO/cikk/type, gyártási completion/QC és komponens genealógia bizonyíték                                                                                    | Típus/serialformátum önmagában nem saját gyártás; purchased és unknown nem automatikusan RMA-jogosult           |
| Procurement source      | Sources -> requirement/PR item orphan; eltérő item/unit; SUM(source.quantity) vs PR-line qty; representative direct pointer része-e a sourcehalmaznak; soft-delete/cancel/receipt coverage | Source és direct pointer nem két demand; bizonyított coverage és kézi feloldás, nincs source DELETE retryként   |
| Genealogy               | Self-edge, duplázott edge, több parent, recursive path cikluskeresés visited-settel; uninstall-időpont hiánya; origin/cikk-összefüggés                                                     | Jelenlegi relation nem történeti ledger; bizonyított legacy_install/correction csak külön jóváhagyott importban |

Kiegészítő, **megtervezett, nem futtatott** példák (production olvasás nincs):

```sql
SELECT r.id
FROM stock_reservations r
LEFT JOIN production_orders p ON p.id = r.production_order_id
LEFT JOIN customer_order_items c ON c.id = r.customer_order_item_id
WHERE (r.production_order_id IS NOT NULL AND p.id IS NULL)
   OR (r.customer_order_item_id IS NOT NULL AND c.id IS NULL)
   OR (r.production_order_id IS NOT NULL AND r.customer_order_item_id IS NOT NULL
       AND NOT (p.customer_order_item_id <=> r.customer_order_item_id));

SELECT s.purchase_requisition_item_id, SUM(s.quantity) AS source_qty, p.quantity
FROM purchase_requisition_item_sources s
JOIN purchase_requisition_items p ON p.id = s.purchase_requisition_item_id
GROUP BY s.purchase_requisition_item_id, p.quantity
HAVING SUM(s.quantity) <> p.quantity;
```

A NULL-safe `<=>` MySQL-specifikus; SQLite-hoz explicit NULL-ágak szükségesek.
Történeti correction, unit mapping és opening balance nélkül movement
reconciliation szándékosan nincs „sikeres” SQL-egyenlegtesztnek feltüntetve.
Teljes gráfciklus-/proof-audit és reprezentatív dataset **BLOCKED**: nincs
kiválasztott üzleti snapshot és igazolt baseline. Adatjavítás/backfill nem történt.

## 10. A terv szükséges pontosításai és függőségei

| Terület       | Átvezetett korrekció                                                              | Teljes bizonyítás még szükséges                                                |
| ------------- | --------------------------------------------------------------------------------- | ------------------------------------------------------------------------------ |
| Warranty      | Subject item; handover U(id,serial); három composite FK                           | Original/replacement membership, pending/override/policy                       |
| Shipment      | Attempt, previous_release, redispatch authorization, attempt U-k                  | Reservation per fizikai kísérlet, blocked return/quarantine, final disposition |
| Return        | Megfigyelés és külön return_reconciled esemény, unique inbound pointer            | Scope/owner/hely projekció és pontos NULL/type CHECK a teljes DDL-ben          |
| Ownership     | Üzleti szöveg-U helyett source occurrence és serialos sequence                    | Payload/auth/supersedes/historikus current lekérdezés                          |
| Credit        | Order-pointer helyett új allocation és active key, composite quote/line kapcsolat | Held/applied/released átmenetek és pénzügyi policy                             |
| Migration     | Allocation quote+items után; order->quote vissza-FK megszűnik                     | Members/movement és handover/release ciklusok kétlépéses FK-ja; SQLite rebuild |
| Inventory/MRP | Korábbi sentinel/receipt/owner/remaining/revision javaslatok megmaradnak          | Shared consumer rollout és nem teljes missing újragenerálása                   |

Nem készült teljes új séma, Laravel migráció vagy RMA service. A leírt extra
CHECK-ek és FK-k végleges DDL-je külön feladat; a deklaratív terv nem futtatott
migráció. A régi táblák üzleti adatait csak jóváhagyott reconciliation után
lehet contracttal megszorítani.

## 11. Accepted követelmények lefedettsége

Az A 13. fejezetének húszsoros mátrixa megmaradt; az ADR fájl változatlan.
A review hozzárendelése (terv, nem alkalmazási elfogadási teszteredmény):

| Döntés     | Megőrzött garancia / review                                                |
| ---------- | -------------------------------------------------------------------------- |
| RMA-BD-001 | Több serial, önálló order/lifecycle; FND-01/C6                             |
| RMA-BD-002 | Bizonyított saját gyártás+serial; origin audit, D2                         |
| RMA-BD-003 | Belső jogosult rögzítő, külön reporter/owner; D3                           |
| RMA-BD-004 | Claim egész ügyclose-ig; FND-01/C1/C6                                      |
| RMA-BD-005 | Manuális warranty/pending/indokolt override; FND-03                        |
| RMA-BD-006 | Aktuális approved keret startkor; C7                                       |
| RMA-BD-007 | Customer receipt nem normal balance; FND-02                                |
| RMA-BD-008 | Eredeti végső rendelkezés és csere külön; FND-04                           |
| RMA-BD-009 | Új attempt/QC, változatlan előzmény; REV-01                                |
| RMA-BD-010 | Több dolgozó, actual time, anyag; C5/C10                                   |
| RMA-BD-011 | Proof-alapú teljes close; FND-01/C6                                        |
| RMA-BD-012 | Igazolt handover és rule snapshot; FND-03                                  |
| RMA-BD-013 | Verziózott submitted/approved/rejected/expiry quote; FND-05/C7             |
| RMA-BD-014 | Free/fixed/time diagnosztika és egyszeri igazolt credit; FND-05            |
| RMA-BD-015 | Mindkét serial teljes history; REV-01/02                                   |
| RMA-BD-016 | Member/serial/state/korábbi receipt ellenőrzés; FND-02/C2                  |
| RMA-BD-017 | Custody nem sale/production/MRP stock; FND-02/REV-03                       |
| RMA-BD-018 | Javítási anyag normál stockból, hiány PR-hez; FND-06/C5/C8                 |
| RMA-BD-019 | Fizikai release proof és custody end; FND-04/C3                            |
| RMA-BD-020 | Reserve/prepare/dispatch/ownership külön, cancellation ellenőrzött; FND-04 |

D1: pontosan company_stock/customer_custody/external; karantén nem negyedik scope.
D2: semi_finished_product is jogosult lehet, kizárólag saját gyártás+serial+eredet.
D3: egy ügy egy owner, reporter eltérhet; külön owner külön ügy.
D4: nincs automatikus cseregarancia-reset, mindkét subject története megmarad.
D5: fizikai kiadás és tulajdonátszállás külön esemény/időpont/bizonyíték.

F1 változatlan: company reserve/preparation, külön dispatch/delivery/ownership,
egyszeri valódi outbound. F2: proof-/movement-/reservation-/QC-/authorization-alapú
atomikus ügyzárás. F3: eredeti–csere serialpár megőrzött foglalási/kiadási/döntési
kapcsolatokkal, külön eredeti sors és warranty-history. F4: el nem kezdett terv
jogosult superseding történettel, scopeváltozásnál új authorization.

## 12. Nyitott döntések és migrációs készültség

**Verdikt: NOT MIGRATION READY.** A documentation/SQL review átadható;
éles vagy fejlesztői RMA-séma létrehozása nincs engedélyezve.

| Kapu                                                         | Felelős szerep                                               | Feloldás                                                                                            |
| ------------------------------------------------------------ | ------------------------------------------------------------ | --------------------------------------------------------------------------------------------------- |
| Return/quarantine/redispatch és ownership reversal           | Üzleti owner + inventory/quality + jogi/szerződéses szakértő | Bizonyíték, szerepkör, állapot, hely, elérhetőség; nincs automatikus tulajdonvélelem                |
| Credit activation/release, díj időegység/rounding/skála      | Kereskedelmi/pénzügyi owner                                  | Submitted/approved/superseded/cancelled esetek explicit policyja, számlázási scope nélkül           |
| Tényleges warranty rule/contract trigger                     | Üzleti owner + illetékes szakértő                            | Verziózott alkalmazandó feltételek, evidence/retention; D4/D5 már Accepted                          |
| Több-PO legacy aggregáció, procurement coverage cancellation | MRP/procurement owner + architect                            | Ellenőrzött legacy jelentés és delta-netting döntés                                                 |
| Claim/history hardening és proof retention                   | DBA + security + architect                                   | Runtime/migration principal, procedure/trigger design, negatív grantteszt                           |
| Reprezentatív legacy audit/backfill                          | Adatgazda + DBA                                              | Read-only snapshot+opening baseline; eltérésekhez külön jóváhagyott rendezés                        |
| Teljes DDL és consumer regresszió                            | Backend + QA + DBA                                           | Mindkét motor migrate/rebuild/rollback; tényleges C1–C10, deadlock/retry és inventory/MRP/reporting |

További A-kori nyitott implementációs kérdések megmaradnak: employee globális
időátfedés, QC szerepszétválasztás, scope materiality, dokumentum retention és
commit utáni külső mellékhatások. Nem minősülnek új, elfogadott üzleti szabálynak.

**Következő javasolt fázis: RMA-0003/C – integritási kapuk lezárása és teljes
DDL/migrációs specifikáció.** Előbb a fenti policy és legacy baseline döntések,
utána guardolt teljes sémaprototípus és consumerenkénti regressziós terv;
alkalmazásimplementáció csak külön explicit jóváhagyás után.

## 13. Átadási ellenőrzések

DoD-típus: dokumentáció és izolált adatbázis-verifikáció. PHP syntax és célzott
prototípusfutás releváns; teljes backend/frontend/build/migration suite N/A,
mert alkalmazáskód és alkalmazásmigráció nem változott. E suite-ok sikerét
nem állítjuk. Nincs config-, dependency-, permission- vagy production-trigger változás.

Átadáskor ellenőrzött: 94 relatív fájlhivatkozás létező céllal; mindkét tervben
20/20 BD-lefedettség; D1–D5 és F1–F4 tartalmi megfelelőség; páros kódfence-ek;
Prettier, PHP lint/Pint és actual-file whitespace. `git diff --check` és a négy
nem követett fájl külön no-index whitespace-ellenőrzése sikeres.
ADR SHA-256 (normalizált LF):
`3704c2047110ed8030c2ac9cf6acef14dd3f7acde989aab165abe11369053bfc`.
A pre-existing A fájl továbbra is nem követett, a B review és verification
alkönyvtár új fájlok. Staging üres; HEAD és branch változatlan.
