# Merlin Investigation Foundation V1 — specifikáció

- **Dátum:** 2026-10-08
- **Baseline:** `feature/merlin-candidate` / `45544b19017d1f31564cb3e66c5bc3f74d4071b8`
- **Persistence/start véglegesítés:** 2026-10-13; `feature/merlin-candidate` / `cf589ac0817cd58b1d26b1267c6e72b8380ff627`, tiszta kiinduló worktree
- **Döntés:** [ADR 0017](../../../.kiro/decisions/0017-merlin-investigation-foundation.md)
- **Státusz:** dokumentált V1 szerződés, nem implementált vizsgálati runtime

## 1. Hatókör és minősítések

ACCEPTED: a felhasználó által elfogadott domain/security szerződés.
PROPOSED: ajánlott technikai megoldás vagy outcome mapping, külön döntés szükséges.
OPEN: nincs elfogadott választás vagy számérték. Egy ACCEPTED fejezetben szereplő
kifejezetten PROPOSED rész sem tekinthető elfogadott implementációs döntésnek.

> Merlin may decide how to investigate, but KM_Production controls what Merlin is permitted to execute.

Az első képesség `get_supplier_options(problem_case_id)`. Merlin nem trusted
backend; tool requestje nem végrehajtási parancs. Laravel felel az authorizationért,
business state-ért, dispatchért, limitekért és persistence-ért. Rétegzés:
Controller → Service → Repository → Model. Nincs direkt model/Python database access.

### Ellenőrzött jelenlegi komponensek

| Meglévő komponens                                                                                                                               | Újrahasználható határ                                                               | Még hiányzó képesség                                                             |
| ----------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------- | -------------------------------------------------------------------------------- |
| [ProblemCase](../../../app/Models/ProblemCase.php), [repository](../../../app/Repositories/Admin/ProblemCaseRepository.php)                     | UUID case, immutable detection evidence, külön lifecycle/current projection/history | Authorized run start és investigation-history linkage                            |
| [Current resolver](../../../app/Services/Admin/MaterialShortageProblemCaseResolver.php)                                                         | Current source validity és full competing-scope netting, read-only DTO              | Archival/finalization orchestration                                              |
| [Supplier tool](../../../app/Services/Merlin/GetSupplierOptionsTool.php), [context](../../../app/Support/Merlin/SupplierOptionsToolContext.php) | Fresh actor, allowlist, bound UUID, fresh business gate, explicit projection        | Run/sequence correlation és supervised dispatch                                  |
| [Supplier read composition](../../../app/Services/Merlin/MaterialShortageSupplierOptionsRead.php)                                               | Egy observation snapshot; az authority a hívás után megszűnik                       | Run-wide state guarantee nincs                                                   |
| [Python engine](../../../app/Services/AI/PythonAiEngineService.php)                                                                             | JSON subprocess, timeout és safe failure envelope; classification stub/OCR          | Interactive structured model tool-calling, request correlation, usage accounting |
| [AI telemetry](../../../app/Services/AI/AiProcessingTelemetryService.php)                                                                       | Duration/status és minimal summary minták                                           | Nem MerlinRun; numeric polymorphic link nem kész UUID case kapcsolat             |
| [Audit service](../../../app/Services/AuditLogService.php), [queue config](../../../config/queue.php)                                           | Activity events és queue infrastruktúra                                             | Durable run/call evidence, ownership, cancellation és run recovery               |

MerlinRun, investigation job, model loop és generic registry nincs implementálva.
Az [existing tool slice](supplier-options-tool-slice-1.md) szerződése megmarad.
Egy explicit backend mapping elegendő; arbitrary class/method dispatch tilos.

## 2. ACCEPTED — authorization és indítási boundary

| Művelet                             | Permission-rész                            | Külön context/identity feltétel                           |
| ----------------------------------- | ------------------------------------------ | --------------------------------------------------------- |
| Case visibility                     | `inventory.view`                           | Nem ad supplier evidence/result disclosure-t              |
| Start                               | `inventory.view` AND `item-suppliers.view` | Backend-bound actor/case; fresh OPEN + ACTIVE eligibility |
| Saját run/result read, történeti is | Mindkét permission aktuálisan              | Kezdeményezőazonosság                                     |
| Más user run/result read            | Tiltott                                    | V1-ben nincs megosztás                                    |
| Cancel                              | Mindkét permission aktuálisan              | Kezdeményezőazonosság                                     |
| Más user run cancel                 | Tiltott                                    | V1-ben nincs delegált cancellation                        |

Nincs új permission. A super-admin meglévő Gate-konvenciója csak a permission-részt
teljesíti; az ownership check külön kötelező, más user eredményét/cancellationjét
nem engedi. Az AI allowlist, bound case és current business gate is külön kontroll.

Történeti olvasáskor fresh authorization szükséges; a korábbi start jogosultság
nem marad tartós disclosure authority. Ez vonatkozik az evidence-re, exportokra és
eredmény-referenciák feloldására is. Az `inventory.view` alapján elérhető case
válaszba nem kerülhet supplier evidence vagy advice. A történeti olvasás nem
követeli meg, hogy a case jelenleg ACTIVE legyen: a régi advice történeti adat.
A cancellation joga sem tesz egy időközben CLOSED case-t aktuálissá.

Indítási sorrend:

```text
Authenticated User
→ fresh actor, case access és start authorization
→ saját admission transaction és tényleges case-zár
→ idempotencia feloldása, majd aktív-run konfliktus ellenőrzése
→ fresh material-shortage current evaluation és OPEN + ACTIVE + VALID gate
→ atomikus starting history + PENDING run + durable audit
→ commit
→ későbbi worker-entry revalidation, toolset assembly és supervised execution
```

A [case szabály](material-shortage-problem-case.md) alapján csak OPEN,
material-shortage case, authoritative VALID source és authoritative pozitív
net requirement esetén indul current investigation. Identity/requirement egyezés
kötelező. Detection snapshot, persisted current projection és `missing_quantity`
nem current authority. Invalid source nem RESOLVED; UNDETERMINED nem nulla hiány.

Az actor a hitelesített backend user; case UUID-ja a backend által ellenőrzött
case-ből származik. Model input nem hozhat létre trusted contextet, actor ID-t vagy
allowlistet. Toolset assembly authorization és minden execution authorization
független. A tool ismét betölti az actort és current state-et; availability nem
ígér domain applicabilityt. A backend aktuális allowlistje nem fagyasztható be
engedélyként a run config snapshotjába.

Az observation csak timestampelt pillanatnyi bizonyíték, nem supply reservation
vagy revocationnel atomikusan összezárt authority. Spatie cache invalidation
konvenciók érvényesek; nincs azonnali, race-free permission revocation garancia.
A worker-entry authorization és eligibility revalidation ACCEPTED;
megvalósítása OPEN. A finalization check PROPOSED a 7. részben.

## 3. ACCEPTED — lifecycle

| Status    | Jelentés                                                           |
| --------- | ------------------------------------------------------------------ |
| PENDING   | Elfogadott run, actual execution még nem kezdődött el              |
| RUNNING   | A backend actual executiont indított                               |
| COMPLETED | A vizsgálat lezárult; nem bizonyítja a business problem megoldását |
| FAILED    | A vizsgálat hibával végződött                                      |
| CANCELLED | A vizsgálat megszakított terminális outcome-mal végződött          |

Megengedett transitionök:

| Forrás                       | Cél                          |
| ---------------------------- | ---------------------------- |
| PENDING                      | RUNNING, FAILED, CANCELLED   |
| RUNNING                      | COMPLETED, FAILED, CANCELLED |
| COMPLETED, FAILED, CANCELLED | Nincs további transition     |

Status és stop reason külön fogalom. Terminális state, stop reason és result
nem írható felül; nincs visszaállítás PENDING/RUNNING állapotba. Új vizsgálat új run.
V1-ben nincs resumable WAITING_FOR_HUMAN. HUMAN_INPUT_REQUIRED terminális stop
reason; nem aktív várakozás, és nem jogosít hiányzó adat feltételezésére.

PROPOSED: HUMAN_INPUT_REQUIRED → COMPLETED, az advice-ban explicit information
gap-pel. A COMPLETED/COMPLETED szokásos befejezés, FAILED/TOOL_FAILED és
FAILED/MODEL_FAILED, CANCELLED/CANCELLED_BY_USER párok szintén PROPOSED mappingek.
Az engedélyezett topology nem fogadja el automatikusan az egyes transitionök
triggerét. Case-change, revocation és limit mapping külön döntési tétel.

## 4. ACCEPTED — execution és cost profil

| Határ             | V1 érték                                      |
| ----------------- | --------------------------------------------- |
| Model turn        | Maximum 8                                     |
| Tool-call attempt | Maximum 4                                     |
| Execution time    | Maximum 180 másodperc actual starttól         |
| Aktív run/case    | Maximum 1, a PENDING és RUNNING együtt számít |

Rejected vagy malformed model turn fogyasztja a turn budgetet. Denied, malformed
vagy failed tool request fogyasztja a tool-attempt budgetet. Az egymástól külön
tool requestek külön attempts, akkor is, ha egy model response több kérést tartalmaz.
PENDING queue wait nem része a 180 másodpercnek. A turn/attempt/deadline enforcement
backend feladat, nem prompttal vagy model együttműködéssel biztosított kontroll.

Nincs korlátlan retry és automatikus paid replay. A cancellation kooperatív:
in-flight model hívás még befejeződhet és díjat okozhat. Late model response nem
dispatcholhat toolt és nem írhat felül terminális eredményt. Azonnali provider-kill
vagy exactly-once external inference garancia nincs.

PROPOSED: a turn/attempt countert dispatch előtt atomikusan lefoglalni, minden
blokkoló hívás timeoutját a maradék execution deadline-hoz igazítani, majd a választ
új cancellation/status/deadline checkkel fogadni. A már kimerült keret után nem
indul új hívás. A transport failure számítása és duplicate call kezelése a
technikai szerződésben véglegesítendő; nem adhat korlátlan extra próbálkozást.

ACCEPTED: konfigurálható per-run cost ceiling kötelező live paid inference előtt.
Konfigurált, enforceable budget policy nélkül nincs live paid run. Token-, request-
és output-size safeguards, usage accounting és az accounting hiányára kontrollált
viselkedés szükséges. A 8/4/180 limitek önmagukban nem monetary budget enforcement.

OPEN: provider/model, konkrét monetary/token/size értékek, usage availability,
reservation és reconciliation, bizonytalan vagy hiányzó usage outcome-ja.
PROPOSED: előzetes felső költségfoglalás és használat szerinti reconciliation;
ismeretlen accountingnál új paid call tiltása és safe terminal outcome. Ezek
elfogadása és provider-specifikus bizonyítása a live integration blockere.

## 5. ACCEPTED — evidence és retention

| Fogalom                         | Megőrzött tartalom                                                                    |
| ------------------------------- | ------------------------------------------------------------------------------------- |
| Problem Case Evaluation History | Jelentős business evaluation; a vizsgálat indulási állapota később a runhoz köthető   |
| MerlinRun                       | Investigation lifecycle, backend actor/case binding, limits snapshot és final outcome |
| Tool-call evidence/audit        | Requested capability, sorrend, authorization/execution outcome és minimal observation |
| Nagy Könyv                      | Későbbi organizational learning; V1-en kívül                                          |

MerlinRun és investigation-owned evidence/results retentionje **365 nap a
terminális lezárástól**, jogszerű megőrzési kötelezettségek mellett. Minden
terminális állapothoz kötelező terminal completion timestamp. Aktív run kora
nem alap automatikus törlésre. Run-owned evidence/results és idempotenciaazonosság
a runnal együtt, kontrollált dependency sorrendben törölhető; állandó idempotency
tombstone nincs V1-ben. A törölt run kulcsa többé nem garantál replayt.
Cleanup nem cascade-olhat Problem Case-re, detection snapshotra vagy üzleti
Evaluation Historyra. Shared activity log retentionjét sem írja át a szerződés.
A cleanup, backup/reference kezelés és operatív legal hold OPEN; az ellenőrzött
`app`, `database`, `config` forrásokban nem találtunk támogatott legal-hold
mechanizmust. Ez nem vezet be teljes legal-hold alrendszert.

Minimális, timestampelt, visszakereshető evidence és versioned advisory result
szükséges. Nincs default full prompt/raw model response tárolás, duplikált teljes
supplier-commercial payload, raw exception, SQL vagy stack trace investigation
recordban. Safe failure code megőrizhető; full supplier object nem.

PROPOSED minimal evidence: run UUID, call ID, sequence, tool/schema version,
bound case input, authorization/domain outcome, időpont/duration, option count,
compact observation és a findingshoz szükséges field reference. A reference
retrievable, runhoz kötött és ugyanazzal az authorizationnel védett legyen.
Hash önmagában nem olvasható evidence. Kereskedelmi adatból csak a findinghoz
szükséges minimum szerepeljen egy kanonikus helyen; ne legyen teljes payload
minden logban, call recordban és resultban megismételve.

Az existing tool audit invocation UUID-ja nem visszaadott run/call correlation.
Ennek explicit összekötése PROPOSED fejlesztés. AuditLogService önmagában nem
MerlinRun persistence, és tetszőleges properties argumentumot nem sanitizál
automatikusan. A tool jelenleg audit exceptionre fail closed; a durable run-evidence
követelmény admissionre ACCEPTED a 7. részben, runtime tool-correlationre még
PROPOSED. A [logging config](../../../config/activitylog.php) disabled/buffered
módot is enged, a cleanup 365 napja nem automatikus run cleanup.

## 6. ACCEPTED — versioned advisory result

Az alábbi logikai V1 envelope a szerződés, nem adatbázisséma vagy provider API.
A pontos JSON validation schema és evidence path grammar az implementációs
szerződésben véglegesítendő. A `schema_version` V1 jelölése `"1"`.

| Mező/csoport                       | Tartalom és authority                                                                                         |
| ---------------------------------- | ------------------------------------------------------------------------------------------------------------- |
| `schema_version`, `run_id`         | Backend által beállított version és run identity                                                              |
| `case`                             | Backend-verified `problem_case_id`, `type`, `material_requirement_id`                                         |
| `verified_state`                   | Timestampelt lifecycle, source validity, evaluation, authority jelzők; observation időpontja és eredete       |
| `evidence`                         | Runhoz tartozó references és timestampelt observations; supplier options minimális evidence-e és korlátai     |
| `findings`                         | Model-generated interpretation, evidence references-szel; explicit advisory authority                         |
| `information_gaps`                 | Determinisztikus UNKNOWN/NOT_APPLICABLE diagnostics és külön model által felvetett, nem igazolt kérdések      |
| `recommended_next_steps`           | Model advice, nem üzleti parancs                                                                              |
| `actions_requiring_human_approval` | Javasolt emberi döntések/actions; végrehajtási capability vagy engedély nélkül                                |
| `outcome`                          | Backend terminal status, stop reason, creation/start/termination timestamp; actual start hiányában start null |

Backend-verified state-nek a legutóbbi ténylegesen ellenőrzött állapotot kell
jelentenie, nem a válasz olvasásának időpontjában garantált igazságot. A kezdő
evaluation és tool observation külön timestampet/reference-t őriz. Finalization-time
újraellenőrzés PROPOSED; hiányában nem állítható, hogy finalizationkor is ACTIVE.
Historical result read nem változtatja a régi verified_state-et.

Evidence references validációja kötelező: létező, a runhoz/case-hez tartozó és
engedélyezett observationre mutassanak. Findingsben supplier identity, mennyiség,
ár és date állítás nem lehet model által fabrikált backend fact. Model inference
explicit interpretation marad; evidence-szel ellentmondó fact állítás nem
fogadható el. Supplier ranking/selection nincs, preferred/priority sem ranking.

Decimal quantity/price exact string; nincs float conversion vagy új netting.
Az existing tool `KNOWN`, `UNKNOWN`, `NOT_APPLICABLE` state/value/reason szerződése
megmarad. Reference price nem confirmed price, lead-time alapú dátum calendar-day
estimate. Hiányzó adat nem válik default értékké vagy confidence százalékká.

FAILED/CANCELLED esetben is megőrzendő a backend outcome; findings lehet üres,
nem kell model advice-t kitalálni. A partial advice engedélyezése és jelölése
PROPOSED. Minden actionhoz később külön emberi döntés és authorized backend
workflow kell; a tool result/advice nem jogosít PR/PO creationre vagy business write-ra.

## 7. Persistence/start szerződés és nyitott technikai részletek

### 7.1. ACCEPTED — adatbázis-invariánsok

A run az indulási állapot visszakereshető, actorhoz és case-hez kötött története.
Az alábbi logikai mezőszerződés kötelező; az oszlopnevek, típusok és Laravel
migrációs szintaxis pontosítása OPEN, kivéve az elfogadott `active_marker` és
`problem_case_id` nevet és az alábbi indexet.

| Adat                 | Kötelező szerződés                                                                                                                                                                                    |
| -------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Run identity         | UUID primary key; új vizsgálat új UUID                                                                                                                                                                |
| Case binding         | Nem nullable `problem_case_id` UUID FK, létrehozás után változatlan                                                                                                                                   |
| Starting evaluation  | Nem nullable FK/reference a `problem_case_evaluations.id` integer rekordra; ugyanahhoz a case-hez tartozzon, változatlan                                                                              |
| Live initiator       | Nullable `users.id` FK; törléskor NULL-lá válhat                                                                                                                                                      |
| Historical initiator | Nem nullable, immutable eredeti actor ID; audit és idempotencia minimuma, nem authorization                                                                                                           |
| Lifecycle            | Nem nullable status: `pending`, `running`, `completed`, `failed`, `cancelled`; DB-szintű értékvalidáció; a nagybetűs lifecycle-nevek ezek fogalmi megfelelői                                          |
| Stop reason          | Statustól külön mező; aktív runnál nincs terminális reason; terminális runnál kötelező, runtime mapping részben PROPOSED                                                                              |
| Timestamps           | Creation kötelező; actual `started_at` nullable; terminal completion minden terminális állapotnál kötelező; cancellation request és cancellation completion időpontja elkülöníthető, nem actual start |
| Configuration/result | Versioned, validált és méretkorlátos backend config/limits snapshot és structured result vagy védett reference; PENDING-nél még nincs final result; pontos séma és byte-limit OPEN                    |
| Request identity     | Kliens idempotency UUID, canonical request identity és fingerprint, explicit kanonizálási/fingerprint verzióval; nem prompt vagy supplier payload                                                     |

Aktív runnál a terminal completion timestamp NULL; actual start előtt
`started_at` NULL. Cancellation request időpontja nullable, és nem jelenti
önmagában a lezárást. CANCELLED állapotnál cancellation completion és terminal
completion kötelező, azonos lezárási eseményt jelölnek. A pontos mezőnevek és
DB/service transition enforcement OPEN; a timestamp-invariáns kötelező.

Az egy aktív run szabályt nem helyettesítheti csak alkalmazásszintű előzetes
lekérdezés. Elfogadott adatbázis-mechanizmus:

```sql
active_marker GENERATED ALWAYS AS (
    CASE
        WHEN status IN ('pending', 'running') THEN 1
        ELSE NULL
    END
) STORED
```

```sql
UNIQUE (problem_case_id, active_marker)
```

A generált mezőt caller nem állíthatja. Terminális runok NULL markerje több
történeti runt enged; nem nullable case FK és database-level status validation
kell, hogy hibás status ne kerülhesse meg az invariánst. MySQL 8.4 és a támogatott
SQLite környezet alatt egyaránt érvényes constraint-ek szükségesek, MySQL-en
explicit InnoDB motorral a kapcsolt admission táblákon is. A
[database config](../../../config/database.php) MySQL engine-je már `InnoDB`,
de ez nem bizonyítja új migráció vagy meglévő táblák tényleges motorját.

A [history migráció](../../../database/migrations/2026_10_03_000001_create_problem_case_foundation.php)
csak a history→case FK-t és `(problem_case_id, evaluated_at)` nem unique indexet
tartalmazza. A run→history azonos-case integritás kötelező. PROPOSED megoldás a
parent `(problem_case_id, id)` composite unique index és a run megfelelő composite
FK-ja; pontos FK/parent index, típus- és sorrendillesztés, delete/update action és
Laravel syntax OPEN, mindkét driveren bizonyítandó. Run cleanup nem mutathat
szülő üzleti history törlése felé. Nem állítunk igazolt migrációs portabilityt
vagy konkurens működést.

### 7.2. ACCEPTED — idempotencia és hozzáférés

Az adatbázisban unique identity tuple:

```text
(immutable original initiating actor ID, canonical Problem Case UUID,
 client-generated idempotency UUID)
```

Ez a historical actor ID-ra épül, nem a később NULL-lá váló live user FK-ra.
Actor nem származhat caller/model által megadott ID-ból. A service validálja
a kliens kulcsát és a case UUID-t, és tárolja a kanonikus request identityt és
fingerprintet. Azonos fingerprint önmagában nem helyettesíti a kanonikus kérés
egyezését; kanonizálás, engedélyezett request mezők, hash és verzióváltás OPEN.

| Helyzet                                                                  | Belső PROPOSED typed outcome | Kötelező viselkedés                                                                       |
| ------------------------------------------------------------------------ | ---------------------------- | ----------------------------------------------------------------------------------------- |
| Aktuális permission hiányzik vagy nincs live actor                       | DENIED                       | Nincs replay disclosure vagy új admission                                                 |
| Azonos identity, azonos kanonikus kérés                                  | REPLAYED                     | Ugyanaz a megtartott run; terminális run is replayelhető fresh read authorization mellett |
| Azonos identity, eltérő kanonikus kérés                                  | CONFLICT                     | Nem írhatja át a régi requestet vagy runt                                                 |
| Más kérés, már van PENDING/RUNNING run                                   | ACTIVE_RUN_EXISTS            | Nincs új run; más actor ownerét, identityjét és supplier evidence-ét nem fedi fel         |
| Nincs identity/active ütközés, unsupported vagy jelenleg ineligible case | CASE_NOT_ELIGIBLE            | Nincs új starting history/run                                                             |
| Eligible új kérés                                                        | CREATED                      | Atomikus starting history, PENDING run és audit                                           |

Az outcome-nevek belső service-javaslatok; HTTP/API mapping és malformed/not-found
bemenetek pontos besorolása OPEN. Infrastruktúrahiba nem domain siker vagy replay.
Fresh actor és mindkét permission ellenőrzése minden replay előtt kötelező.
Idempotencia a case current eligibility újraértékelése előtt oldódik fel:
korábban elfogadott run replayje történeti hozzáférés, nem új investigation.
Nincs új history, toolhívás vagy completed investigation restart. Ownership
ellenőrzés a super-admin számára is kötelező.

Identity/key a MerlinRun jogszerű törléséig él, azzal együtt törlődik. Nincs
permanent tombstone. Run törlését követően az új kérés teljes admissionre kerül;
a régi kulcs nem jelent örök exactly-once garanciát.

Nullable live FK és minimális historical actor ID megőrzése ACCEPTED.
User törlése nem törölheti kaszkáddal a megtartandó runt és idempotenciaidentityt.
Hiányzó live actor nem olvashat és nem indíthat/folytathat executiont; történeti
ID alapján nem rekonstruálható authorization. Név/email vagy permission snapshot
nem szükséges. Historical ID személyes adatként minimalizálandó, audit/idempotencia
célú jogszerű megőrzéssel; pontos referential action és törlési folyamat OPEN.

### 7.3. ACCEPTED — service-owned admission és korlátozott konzisztencia

A service saját, read-write REPEATABLE READ MySQL tranzakciót birtokol.
Elfogadott sorrend, a snapshot létrejöttét figyelembe véve:

1. Hitelesített actor megszerzése a trusted Laravel boundaryból; friss user és
   permission lookup a meglévő Gate-konvencióval. Nincs model/caller-owned actor.
2. Caller-owned context elutasítása Laravel transaction level és tényleges
   PDO transaction vizsgálatával; savepoint nem ad saját isolationt vagy commitot.
3. A következő MySQL tranzakcióhoz REPEATABLE READ, READ WRITE policy beállítása
   kezdés előtt, a write PDO-n. A tranzakció indulásakor azonos connection/PDO és
   policy kötelező; reconnect után korábbi policy nem feltételezhető.
4. A tényleges Problem Case primary row locking readje (`FOR UPDATE`) ugyanazon
   write connectionön, minden admission útvonalon. E lock előtt a tranzakcióban
   ne legyen ordinary consistent read vagy eager-loaded relation olvasás, amely
   snapshotot hoz létre. A transaction előtti auth lookup nem az admission snapshot.
5. A zár megszerzése után ismét friss actor/permission ellenőrzés; kontrolladatokat
   current readből kell ellenőrizni, nem korábbi, lock-várakozás előtti snapshotból.
   Idempotencia feloldása, majd csak új kérésnél active-run conflict ellenőrzése.
6. Új admissionre fresh resolver observation a lock után létrehozott egységes
   snapshotban: supported material-shortage type, OPEN lifecycle, authoritative
   VALID source, ACTIVE evaluation, egyező case/requirement identity és pozitív
   exact net requirement, teljes competing-scope nettinggel.
7. Starting Evaluation History append: ugyanennek az observationnek timestampje,
   ACTIVE resultja, minimal evidence-e és egyértelmű start céljelölése. Nem új
   detection snapshot; nem írja át a current projectiont vagy case lifecycle-t.
8. PENDING MerlinRun mentése, a létrejött historyra, trusted actorra és case-re
   kötve, kanonikus requesttel, idempotenciaidentityvel és backend configgal.
9. Kötelező admission audit tartós mentése ugyanazon DB connection/tranzakcióban.
10. Atomikus commit; csak utána adható CREATED siker. Replay/konfliktus/denial
    nem hozhat létre új historyt vagy run-owned admission evidence-t.

A MySQL REPEATABLE READ snapshotot az első ordinary consistent read alapozza
meg, a locking read current read. Ezért a case-zár előtti resolver/auth eager
read egy tranzakción belül elavult admission observationt okozhatna lock-várakozás
után. A fresh auth kontrolllekérdezéseinek és a domain snapshot olvasásainak pontos
repository compositionja OPEN; a fentiek sequencing követelmények, nem kész kód.
A vizsgált Laravel transaction manager
(`vendor/laravel/framework/src/Illuminate/Database/Concerns/ManagesTransactions.php`)
begin során reconnectelhet, ezért a policy érvényességét nem adja pusztán az
`attempts: 1`. Implicit retry vagy connectionváltás nem örökölhet korábbi
observationt; hiba után új saját admission és friss ellenőrzések szükségesek.

**Korlátozott garancia:** koherens starting observation és hozzá kötött
history/run/audit együtt commitol. A case-zár a case admissionjeit sorosítja,
nem zár minden készletet, igényt, foglalást és supply inputot. E mutable inputok
változhatnak a snapshot után, commit előtt vagy worker-entryig. Nem supply
reservation, nem run-wide authority és nem race-free permission revocation.

Worker-entry fresh authorization és business eligibility revalidation kötelező.
Admissionben nincs LLM, supplier tool vagy külső provider. A read-only
`SupplierOptionReadSnapshot` nem write admission helper: a meglévő tool caller
transactiont elutasít, külön saját read observationt és auditot kezel.

SQLite-nál nincs MySQL `FOR UPDATE` vagy azonos REPEATABLE READ szemantika.
A write admission előtt biztonságos írói transaction mód és FK enforcement kell.
PROPOSED az IMMEDIATE kezdés, hogy a write intent a domain olvasások előtt
megszerezhető legyen. A config jelenleg DEFERRED; a telepített Laravel
SQLite connection (`vendor/laravel/framework/src/Illuminate/Database/SQLiteConnection.php`)
PHP 8.4+ alatt konfigurált BEGIN módot használ. A pontos admission-specific
beállítás, visszaállítás, transaction ownership, busy/locked hibakezelés és
connection-integráció OPEN, nem változtatjuk meg globálisan a configot.
Tesztekben a service saját top-level tranzakciója szükséges: külső teszttranzakció
nem fedheti el caller-context denialt vagy commit/rollbackot. File-backed,
független connection/process teszt kell a párhuzamos SQLite admissionhöz;
in-memory feature teszt nem bizonyít MySQL konkurenciát.

Commit előtti constraint/write/audit failure esetén az admission rollbackol:
nem maradhat árva starting history, PENDING run vagy sikeres admission audit.
Commit acknowledgment elvesztésekor a commit kimenetele bizonytalan lehet;
nem állítható feltétlen rollback vagy CREATED siker. Új fresh-authorized
idempotenciaellenőrzés oldja fel, hogy teljes admission commitolt-e;
automatikus tool/model replay ekkor sincs. A unique-ütközés
pontos constraint alapján osztályozandó; nem minden integrity error idempotency
replay. Rollback után új, authorized ellenőrzéssel dönthető el identity conflict
vagy active-run conflict. FK/status/audit hiba nem maszkolható ilyen outcome-ként.
SQLSTATE/constraint-name és driver-specifikus besorolás OPEN.

### 7.4. ACCEPTED — kötelező durable audit és recovery elv

Minimum admission audit: run UUID, case UUID, historical initiating actor ID,
event type és schema version, event timestamp, sanitizált admission outcome,
observation/starting Evaluation History reference és correlation identifier.
Csak szükséges scalar/reference adat tárolható; titok, full prompt, raw model
response és teljes supplier-commercial payload nem.

Auditnak ugyanabban a DB-tranzakcióban tartósan létre kell jönnie; írási hiba
rollbackot okoz. AuditLogService `log()` void visszatérése nem persistence
bizonyíték: disabled/buffered konfiguráció és külön connection ezt gyengítheti.
A meglévő numeric polymorphic activity subject nem helyettesít UUID run/case FK-t.
Ha a meglévő út nem bizonyíthatóan alkalmas, kötelező minimális dedikált
admission event persistence ugyanazon connectionön, runhoz kötve, a fenti
mezőkkel, disabled/buffered application logtól függetlenül. PROPOSED külön
run-owned event tábla; pontos tábla/rekord, FK és schema OPEN. Az opcionális
activity log csak kiegészítés. A shared log creation-alapú 365 napos cleanupja
nem törölheti idő előtt a run terminal completionhez kötött required evidence-ét.
Rejected attempt naplózása külön policy; rollbackolt admissionről nem írható
sikeres tartós audit. Auditkihagyással nem engedélyezhető siker.

Stale PENDING/RUNNING run puszta életkora nem szabadíthatja fel az active slotot.
Recoveryhez bizonyíték kell, hogy a worker ownership/lease már nem érvényes,
vagy egy önállóan definiált pending deadline lejárt. Kontrollált FAILED lezárás
kötelező specifikus stop reasonnel, terminal completion timestamppel és audittal.
Concurrency-safe terminalization és late response fencing kell: késői
worker/provider response nem írhat felül terminal resultot és nem indíthat toolt.
Nincs automatikus fizetős replay. Worker claim, lease/fencing mechanizmus,
pending deadline, recovery implementation, stop reason pontos neve és időértékei
OPEN, az első persistence/start szeleten kívül. A 180 másodperc execution limit
nem pending deadline vagy önmagában worker-halál bizonyíték.

### 7.5. OPEN — implementációs részletek

| Nyitott részlet               | Kötelező határ és feloldás                                                                                                                                       |
| ----------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Migration syntax              | Generated STORED column, DB status CHECK/értékvalidáció, unique index, explicit InnoDB, SQLite/MySQL kompatibilis DDL; valódi migration/rollback teszt szükséges |
| History és egyéb FK           | Same-case history kötés, parent composite unique és composite FK pontos megoldása; delete/update action és típusillesztés; üzleti parent védelem                 |
| Canonical request/fingerprint | Engedélyezett mezők, UUID normalization, determinisztikus serialization, hash és version upgrade; identity unique driveren azonos jelentéssel                    |
| Durable audit                 | Pontos dedicated record/table vagy bizonyított existing path, connection és retention; kötelező audit nem opcionális                                             |
| Transaction composition       | Ownership ellenőrzés, fresh auth current reads/domain snapshot sorrend, reconnect policy és SQLite IMMEDIATE integráció/busy kezelés                             |
| Constraint classification     | Unique identity/active conflict elkülönítése FK/status/egyéb integrity hibától, rollback utáni authorized lookup                                                 |
| User deletion                 | Nullable live FK NULL-ra állítása, historical ID típusa/immutability és race/deletion referential folyamat; adatminimalizálás                                    |
| Terminal enforcement          | Atomic transitions, kötelező terminal timestamp/reason, immutable binding/result enforcement minden írási útvonalon                                              |
| Cleanup/legal hold            | Terminal-alapú 365 nap, run-owned dependency sorrend és idempotency törlés, backup/reference handling; operatív lawful hold kezelés                              |
| Worker/recovery               | Claim/ownership/lease/fencing, queue deadline, dispatch failure és recovery bizonyíték, audit, időértékek; későbbi szelet                                        |

### 7.6. PROPOSED — későbbi runtime döntések

Az alábbi javaslatok nem módosítják az elfogadott admission és recovery elveket:
atomic counter reservation, duplicate call kezelés, tool-audit correlation,
finalization-time recheck, partial advice és limit/cancel race precedence.
Fresh worker-entry és tool-boundary authorization/eligibility kötelező,
de revocation/case-change/limit események pontos terminal outcome mappingje
külön döntést igényel. Provider selection, accounting és orchestration mapping
OPEN, ahol nincs elfogadott szabály.

PROPOSED outcome mappingek:

| Esemény                                    | Status    | Stop reason                              |
| ------------------------------------------ | --------- | ---------------------------------------- |
| Szokásos advisory befejezés                | COMPLETED | COMPLETED                                |
| Emberi információ szükséges                | COMPLETED | HUMAN_INPUT_REQUIRED                     |
| Actor/permission megszűnik                 | CANCELLED | AUTHORIZATION_REVOKED                    |
| Current evaluation RESOLVED                | CANCELLED | CASE_RESOLVED                            |
| Source INVALID vagy case INVALIDATED       | CANCELLED | CASE_INVALID                             |
| Case CLOSED                                | CANCELLED | CASE_CLOSED                              |
| Current state UNDETERMINED                 | COMPLETED | HUMAN_INPUT_REQUIRED                     |
| Model-turn vagy tool-attempt keret elfogy  | FAILED    | STEP_LIMIT_REACHED                       |
| Execution deadline elfogy                  | FAILED    | TIME_LIMIT_REACHED                       |
| Enforceable cost budget elfogy             | FAILED    | COST_LIMIT_REACHED                       |
| Tool denial / tool failure / model failure | FAILED    | TOOL_DENIED / TOOL_FAILED / MODEL_FAILED |
| Authorized cancellation                    | CANCELLED | CANCELLED_BY_USER                        |
| Audit persistence failure                  | FAILED    | AUDIT_FAILED                             |

Source INVALID nem evaluation enum; resolver evaluation ilyenkor null lehet.
A fenti mapping nem módosít case lifecycle-t, current projectiont vagy a régi
business historyt. A terminal outcome és cancellation-request race kezelésének
precíz sorrendje, valamint a FAILED outcome audit-hiba melletti menthetősége
külön elfogadást igényel; adatbázis-kieséskor sikeres mentés nem garantálható.

Admission audit failure esetén rollback történik, nincs megőrzött FAILED run;
a táblázat AUDIT_FAILED párja kizárólag későbbi runtime-javaslat.
A persistence logikai szerződése a 7.1. részben ACCEPTED; worker claim metadata
továbbra is OPEN. A user permissiont nem snapshotoljuk tartós authorityként.
Provider hívás és existing tool execution
nem lehet nyitott caller-owned transactionben; a tool ezt jelenleg elutasítja.
Queue dispatch explicit after-commit legyen; existing `after_commit: false`
nem ad garanciát. Worker timeout és reservation interval összehangolandó.

## 8. Implementációs szeletek

**Egyetlen ajánlott következő implementációs szelet: MerlinRun persistence és
trusted investigation start**, a 7. rész ACCEPTED invariánsaival, az OPEN
implementációs részletek célzott feloldásával és bizonyításával.
E dokumentum létrehozása nem ad implementációs engedélyt.

| Szelet                                 | Scope és függőségek                                                                                                                                                                                   | Szükséges tesztek és DoD                                                                                                                                                               | Kizárások/döntések                                                                                                                                               |
| -------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Következő: persistence/start           | MerlinRun model/repository/migration, authorized start service, fresh resolver composition, linked history, bounded atomic admission, idempotency és durable audit; existing case/resolver/Gate reuse | Az alábbi checklist; SQLite feature/migration és valós MySQL migration/concurrency bizonyíték; project DoD                                                                             | OPEN implementation részletek feloldása; nincs worker orchestration, live provider, model loop, generic registry, IMF UI, Nagy Könyv vagy autonomous procurement |
| Később: supervised backend execution   | Investigation job, claim/recovery, explicit single-tool dispatch, call correlation; fake model adapter                                                                                                | Revocation/case changes; malformed/unknown requests; 8/4/180 limits; cooperative cancellation/late response; duplicate worker/call és audit failure; bizonyított terminal immutability | Runtime mappings/claim/recovery előbb; nincs paid inference vagy business write                                                                                  |
| Később: provider és validated advisory | Replaceable adapter a meglévő AI isolation határ egyeztetésével; enforceable cost policy, result validator/finalization                                                                               | Schema/correlation rejection; usage/deadline/budget; fabricated evidence; exact decimal/unknown semantics; protected historical read; nincs external call DB transactionben            | Provider/accounting OPEN feloldása; nincs ranking/selection/procurement                                                                                          |
| Később: IMF és Nagy Könyv              | Protected result megjelenítés, majd külön learning integration                                                                                                                                        | UI/route authorization, i18n, error/pending/cancel states; history preservation; külön project DoD                                                                                     | Külön scope és engedély; V1 foundationből kizárva                                                                                                                |

A persistence/start nem tartalmaz model tool-calling loopot, generic tool registryt,
live LLM/provider integrationt, IMF UI-t, Nagy Könyvet vagy autonomous actiont.
Az admission/cancel/read szerződés szolgáltatás-szinten is backend-enforced;
frontend visibility nem authorization.

### Első szelet — jövőbeli elfogadási checklist

Ezek implementációs és tesztkövetelmények, nem most elvégzett ellenőrzések.

- [ ] MerlinRun migration/model/repository: UUID identity, immutable binding,
      lifecycle/stop reason, timestamps, bounded versioned config/result és request.
- [ ] MySQL explicit InnoDB és SQLite-compatible generated active marker + unique
      `(problem_case_id, active_marker)`; DB status validation; két aktív insert
      elutasítása, több terminális run engedése, malformed status elutasítása.
- [ ] Nem nullable case/history FK, same-case history enforcement és idempotency
      identity unique index; hiányzó parent, más case history és parent törlési
      kísérlet negatív tesztje. Run cleanup ne töröljön üzleti historyt.
- [ ] Trusted authenticated actor binding; kliens/model actor spoof elutasítása;
      friss Gate és ownership check a start/read/replay határon, missing/deleted
      actor és permission revocation negatív tesztje; super-admin ownership kötött.
- [ ] Service-owned read-write MySQL REPEATABLE READ és biztonságos SQLite admission;
      Laravel/PDO caller transaction elutasítása, reconnect/policy-loss hibakezelés,
      case lock előtti snapshot tiltása és lock utáni fresh observation bizonyítása.
- [ ] Supported OPEN + ACTIVE + authoritative VALID source és exact identity/netting
      feltételek; unsupported type, CLOSED/INVALIDATED, invalid/undetermined source,
      RESOLVED, stale detection/current projection és identity mismatch negatív utak.
- [ ] Starting append-only Evaluation History, PENDING run és mandatory durable audit
      közös commitja; required audit minimum és sanitization, disabled/buffered
      shared log mellett is működő durable path. Commit előtti audit/history/run
      hibák rollbacktesztje: egyik részrekord sem maradhat meg. Bizonytalan commit
      acknowledgment után fresh-authorized idempotenciafeloldás tesztje.
- [ ] Typed admission result és pontos constraint classification; same-request
      replay pending/terminal runra, same-key different-request conflict, different-key
      active conflict; replay nem ír historyt és nem hív toolt. Cross-actor ütközés
      nem szivárogtat owner/identity/supplier evidence-t; denied replay nem disclosure.
- [ ] Minimális secure persistence/read access: saját történeti eredmény aktuális
      két permissionnel, más user és hiányzó live actor elutasítása; nullable user
      referential behavior megőrzi a historical identityt és idempotenciát.
- [ ] Timestamp/terminal immutability és 365 napos terminal-alapú retention előkészítése;
      nincs age-alapú active cleanup vagy permanent idempotency tombstone.
- [ ] SQLite migration/rollback és feature tesztek; top-level transaction és
      file-backed párhuzamos admission teszt, FK/check/generated constraint védelemmel.
- [ ] Valós, dedikált guardolt MySQL 8.4 migration/rollback és független connectionök
      konkurenciatesztje: azonos identity, eltérő identity ugyanazon case-re,
      lock-várakozás utáni observation, engine/constraint és rollback bizonyíték.
      SQLite siker önmagában nem MySQL bizonyíték.
- [ ] A projekt [Definition of Done](../../project-management/definition-of-done.md)
      és releváns SQLite/MySQL migration gate-ek tényleges bizonyítékai.

Explicit kizárás: live LLM/provider integration, model-driven tool calling,
worker orchestration/claim/lease/recovery implementation, IMF UI, Nagy Könyv
és autonomous procurement. Cancel runtime és cleanup ütemező sem az admission
implementációja; security/persistence határaikat ez a szelet nem gyengítheti.

## 9. Korábbi döntésekhez való viszony és verification

A [Problem Case foundation](problem-case-foundation-slice-1.md) és
[resolver slice](material-shortage-resolver-slice-2.md) immutable detection,
append-only history és read-only evaluation határa változatlan. Az induló
evaluation megőrzésének korábbi követelménye megmarad; admission atomicity és
same-case history linkage ACCEPTED, pontos FK implementation OPEN.
A korábban nyitott authorization mapping V1 részét ADR 0017 oldja fel.

A 3. koncepciómentés helyreállítási kontextus, nem normatív source of truth;
az ADR és e specifikáció ACCEPTED szerződését nem írhatja felül. A döntések
architekturális forrása ADR 0017, a pontos invariánsok és acceptance követelmények
forrása ez a specifikáció. `.kiro/index.md` meglévő linkjei helyesek, változtatása
nem szükséges. E véglegesítés nem hoz létre új ADR-t.

[ADR 0016](../../../.kiro/decisions/0016-material-requirement-demand-eligibility.md)
forrásérvényessége változatlan. Régi dokumentumok történeti „nem implementált”
állításai a saját baseline-jukra vonatkoznak; a jelenlegi source és slice notes
igazolják a már meglévő komponenseket.

[ADR 0005](../../../.kiro/decisions/0005-document-ai.md) Python inference és
Laravel-owned business boundaryja nem kerül felülírásra; provider/transport
OPEN. A [Merlin baseline](README.md) confidence-tilalma megmarad, az általános
OCR/AI guidance confidence elvárása nem indokol Merlin confidence százalékot.
A baseline generic registry koncepciója nem kötelező V1 implementáció: a jelenlegi
single-tool scope nem igényel registry frameworköt. Nincs megállapított domain
ellentmondás, új case transition vagy procurement business-rule változás.

Ez a feladat documentation-only. Ellenőrzés: relatív linkek, terminology,
ACCEPTED/PROPOSED/OPEN besorolás, célzott Prettier, `git diff --check`,
`git status --short`, `git diff --stat`. Alkalmazásteszt, migration és full quality
gate nem fut e specifikációs feladatban; runtime enforcement nincs igazolva.
