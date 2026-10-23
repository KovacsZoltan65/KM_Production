# Merlin L1 — technikai szerződéstervezet / Draft 001

- **Dokumentumstátusz: DRAFT.** Dátum: 2026-10-21.
- **Ellenőrzött kiindulás:** `feature/merlin-candidate`, `00e017729bc6f454dc35fe9bfbe149110168bd80`.
  A helyi HEAD, a helyi követőág és a `git ls-remote` szerinti GitHub branch
  egyezett; a munkakönyvtár és a staging a kezdéskor tiszta volt.
- **Cél:** implementációra előkészített, felülvizsgálható L1 contract;
  sem implementációs engedély, sem működő lifecycle vagy elfogadott új ADR.

## 1. Hatókör, minősítések és ellenőrzött források

`ACCEPTED`: korábban elfogadott döntés. `PROPOSED`: kidolgozott, de még
jóváhagyandó megoldás. `OPEN`: vizsgálat vagy döntés szükséges. `BLOCKED`:
hiányzó előfeltétel miatt nem véglegesíthető. A dokumentum egésze `DRAFT`
akkor is, ha elfogadott alapelvet idéz. **Minden új mező, index, kódérték,
commandrészlet és tesztterv PROPOSED**, kivéve a kifejezetten hivatkozott
korábbi döntést. Egy javasolt reason neve nem teszi elfogadottá a mappingjét.

### 1.1. Forrásjegyzék és tényleges állapot

| Forrás                                                                                                                                                                                                                                                      | Ellenőrzött jelentőség                                                                                                                                               |
| ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| [L1 döntési alapállapot](MERLIN_L1_DECISION_BASELINE.md), [Recovery 002](MERLIN_RECOVERY_002.md)                                                                                                                                                            | A 14 döntés és a mentés határa; a nyitott kérdéseket nem zárják le.                                                                                                  |
| [ADR 0017](../../../.kiro/decisions/0017-merlin-investigation-foundation.md)                                                                                                                                                                                | Öt állapot, topology, friss authorization, 8/4/180 limitek, egy aktív run/case, advisory és retention.                                                               |
| [ADR 0018](../../../.kiro/decisions/0018-merlin-execution-lifecycle.md)                                                                                                                                                                                     | Egyszeri claim/fencing, conditional transition, atomi audit, nincs takeover/resume; több technikai részlet továbbra javaslat.                                        |
| [Investigation specifikáció](investigation-foundation-v1.md)                                                                                                                                                                                                | Admission/result szerződés; 7.6. régi mappingjeit a 10. rész külön kezeli.                                                                                           |
| [Run-migráció](../../../database/migrations/2026_10_13_000001_create_merlin_run_foundation.php), [MerlinRun](../../../app/Models/MerlinRun.php), [status enum](../../../app/Enums/MerlinRunStatus.php)                                                      | Lowercase tárolt status, DATETIME mezők, terminal/result CHECK, active unique, immutable admission identity; a model guard nem minden raw SQL út védelme.            |
| [Start service](../../../app/Services/Merlin/StartMerlinInvestigationService.php), [run repository](../../../app/Repositories/Admin/MerlinRunRepository.php)                                                                                                | Trusted admission, kezdő history + PENDING run + audit közös commitja; nem worker vagy lifecycle service.                                                            |
| [Admission transaction](../../../app/Support/Merlin/MerlinAdmissionTransaction.php), [context](../../../app/Support/Merlin/MerlinAdmissionContext.php)                                                                                                      | Saját connection/PDO, caller transaction tiltás, case-lock-first, MySQL REPEATABLE READ/READ WRITE, file-backed SQLite IMMEDIATE; nincs automatikus retry/reconnect. |
| [User repository](../../../app/Repositories/Admin/UserRepository.php), [User](../../../app/Models/User.php), [Gate-konvenció](../../../app/Providers/AppServiceProvider.php)                                                                                | Friss User/permission/role olvasás; super-admin permission-kivétel. Nincs igazolt `is_active`/`disabled_at` user predicate.                                          |
| [ProblemCase](../../../app/Models/ProblemCase.php), [resolver](../../../app/Services/Admin/MaterialShortageProblemCaseResolver.php), [evaluation DTO](../../../app/Support/Merlin/MaterialShortageCurrentEvaluation.php)                                    | Run és üzleti case elkülönül; full competing-scope netting, source authority és uncertainty; általános supersession predicate nincs.                                 |
| [Supplier tool](../../../app/Services/Merlin/GetSupplierOptionsTool.php), [run configuration](../../../app/Support/Merlin/MerlinRunConfiguration.php)                                                                                                       | Tool saját friss gate-tel és külön audittal; nem runhoz kötött attempt engine. Paid false, költségkeret NOT_CONFIGURED.                                              |
| [Gate-konfiguráció](../../../config/quality-gates.php), [selector](../../../app/Support/QualityGate/AffectedSelector.php), [planner](../../../app/Support/QualityGate/GatePlanner.php), [gate-tesztek](../../../tests/Unit/QualityGate/QualityGateTest.php) | A Merlin lifecycle/admission regresszió nincs explicit modulba sorolva; az integration terv nem egyesíti a changed backend tesztlistát.                              |

Az eredeti readiness/L1 audit önálló jelentése a baseline szerint nem volt
azonosítható; az ADR hivatkozása nem eredeti auditbizonyíték. A jelen munka
forrásolvasás és tervezés, nem új runtime-, konkurencia- vagy DB-tesztbizonyíték.

### 1.2. L1 felelősségi határ

**ACCEPTED — L1-D01–D14 és ADR 0017/0018:** Laravel tulajdonú lifecycle,
Service → Repository → Model rétegzés. Admission hozza létre a PENDING runt
és a starting Evaluation Historyt; az L1 nem írja át az admission identityt,
nem ismétli meg az indulást, nem módosít Problem Case lifecycle-t, current
projectiont vagy üzleti historyt. A COMMITTED recommendation nem üzleti
végrehajtási jog és nem emberi jóváhagyás.

**PROPOSED:** L1 a persistence-et, command/value-object contractokat és
validációs invariánsokat készíti elő. A hat command alábbi végrehajtási
szerződése a későbbi L2–L3 service implementációt is meghatározza; L1 puszta
schema/value-object elkészülte nem jelenti e service-ek megvalósulását.
Tervezett neveik: `MerlinRunLifecycleService`,
`MerlinRunExecutionRepositoryInterface`, `MerlinLifecycleTransaction`;
ezek jelenleg nem létező komponensek, nem implementált API-k.

Felhasználói cancellation, worker ownership és belső termination/recovery
három külön authority. Egyik technikai capability sem helyettesíti a user
friss permissionjét új munkához. Nincs új user permission vagy employee flag.

L1 nem implementál workert, queue dispatchot, pending scannert, model/tool
loopot, attempt reservationt, HTTP/UI endpointot, provider adaptert,
retention cleanupot vagy business actiont. Nem fogyaszt model/tool attemptet.
Paid inference alapállapotban tiltott; a `paid_execution_enabled=false` és
`cost_ceiling.state=NOT_CONFIGURED` megmarad (L1-D14).

## 2. Adatbázis-szerződés — PROPOSED

### 2.1. Tárolási alapelv és típusszótár

**ACCEPTED — L1-D02:** csak két új kiegészítő tábla;
`merlin_runs` szerkezetét nem építjük át. **PROPOSED:** a run marad a status,
stop reason és advisory result egyetlen forrása. Az execution nem másolja
ezeket; a command audit eseménye egyúttal az operation receipt tartós rekordja.
Nincs harmadik operation ledger, párhuzamos status vagy külön receipt-tábla.

Execution sor lazán, az első **sikeres** lifecycle command tranzakciójában
keletkezik. Ez ClaimRun vagy egy engedett PENDING terminalizálás lehet.
Admissiont nem módosítunk; elutasított command nem hagy execution sort.
Egy runhoz legfeljebb egy execution sor és legfeljebb egy sikeres claim tartozik.

| Jelölés     | MySQL 8.4 / SQLite tervezett fizikai típus és validáció                                                                                                                                                                                                                               |
| ----------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `UUID`      | MySQL `CHAR(36)` a parent `merlin_runs.id` tényleges charset/collation beállításával egyezően; SQLite `TEXT COLLATE BINARY`. Canonical lowercase UUID, SQL hossz/alsóbetű CHECK és alkalmazási UUID-validáció. A MySQL FK oszlopra nem választható önkényesen eltérő ASCII collation. |
| `EPOCH`     | MySQL signed `BIGINT`; SQLite `INTEGER` és `typeof(column)='integer'` CHECK. Egész UTC epoch seconds, `0..253402300799`; a meglévő DATETIME-ba való veszteségmentes UTC konverzió külön tesztelendő. Nullable mezőnél explicit NULL ág.                                               |
| `COUNT`     | MySQL `INTEGER`; SQLite `INTEGER` és integer storage CHECK; mezőspecifikus zárt tartomány.                                                                                                                                                                                            |
| `CODE(n)`   | MySQL `VARCHAR(n) COLLATE utf8mb4_bin`; SQLite `TEXT COLLATE BINARY`; pontos zárt értékkészlet és hossz CHECK, nem szabad szöveg.                                                                                                                                                     |
| `HASH`      | MySQL `CHAR(64)` bináris összehasonlítással; SQLite `TEXT COLLATE BINARY`; 64 lowercase hex karakter. SQL hossz és hex-validáció, alkalmazási SHA-256 formátumellenőrzés.                                                                                                             |
| `JSON_TEXT` | Mindkét motoron `TEXT`; JSON_VALID és UTF-8 bájtméret CHECK, alkalmazási zárt séma. MySQL OCTET_LENGTH, SQLite LENGTH(CAST(value AS BLOB)); nem karakterhossz.                                                                                                                        |

Az EPOCH felső korlát technikai proposal, nem időzítési policy. A támogatott
SQL függvények, SQLite JSON-funkciók és MySQL timezone/konverzió tényleges
verziókon ellenőrizendők. A meglévő DATETIME mezők kompatibilitási vetületek;
az új lifecycle időszámítás authorityje az adatbázisóra és az epoch-adat.

### 2.2. `merlin_run_executions` mezőszótár

A „nincs” alapérték kötelező explicit insertet jelent. A táblázat minden
mezője PROPOSED; a döntésoszlop a követelmény eredetét jelöli.

| Mező                       | Típus | NULL | Alapérték | Jelentés                                       | DB-korlát                                       | Index                                                     | Döntés        |
| -------------------------- | ----- | ---- | --------- | ---------------------------------------------- | ----------------------------------------------- | --------------------------------------------------------- | ------------- |
| `run_id`                   | UUID  | nem  | nincs     | Az egyetlen kapcsolt MerlinRun                 | PK; FK → merlin_runs.id, RESTRICT update/delete | PK                                                        | D02, D09      |
| `schema_version`           | COUNT | nem  | 1         | Execution séma                                 | =1                                              | nincs                                                     | D02, D05      |
| `created_at_epoch`         | EPOCH | nem  | nincs     | Az első sikeres lifecycle command DB-ideje     | EPOCH-tartomány; immutable service-szabály      | nincs                                                     | D03, D04      |
| `claim_id`                 | UUID  | igen | NULL      | Egyszeri backend ownership identity            | nullable UNIQUE; claim tuple CHECK              | `mre_claim_uq`                                            | D01, D09      |
| `claim_token_hash`         | HASH  | igen | NULL      | Titkos claim token SHA-256 ellenőrző lenyomata | claim tuple CHECK; nem public adat              | nincs                                                     | D05, D07      |
| `claimed_at_epoch`         | EPOCH | igen | NULL      | Sikeres ClaimRun DB-ideje                      | ≥created_at_epoch; claim tuple CHECK            | nincs                                                     | D03           |
| `execution_deadline_epoch` | EPOCH | igen | NULL      | Immutable claim-idő +180                       | =claimed_at_epoch+180; claim tuple CHECK        | `mre_deadline_idx` (`execution_deadline_epoch`, `run_id`) | D03, D12      |
| `model_turns_used`         | COUNT | nem  | 0         | Tartós model-attempt számláló                  | 0..8                                            | nincs                                                     | ADR 0017; D02 |
| `tool_call_attempts_used`  | COUNT | nem  | 0         | Tartós tool-attempt számláló                   | 0..4                                            | nincs                                                     | ADR 0017; D02 |
| `last_event_sequence`      | COUNT | nem  | nincs     | Utolsó commitolt lifecycle eseménysorszám      | 1..2147483647                                   | nincs                                                     | D04, D11      |

`claim tuple CHECK`: a négy claim mező mind NULL, vagy mind nem NULL;
utóbbi esetben a deadline-egyenlőség és minden részkorlát teljesül.
Explicit IS NULL / IS NOT NULL ágak szükségesek: SQL UNKNOWN nem lehet
kiskapu. Claim nélküli executionnél a két attempt counter nulla.

A claim tuple NULL → kitöltött változása csak PENDING → RUNNING mellett,
egyetlen atomi commandban engedett. Utána a tuple immutable, a számlálók
csak növekedhetnek. L1–L3-ban nulla maradnak; a hat lifecycle command egyike
sem foglal attemptet és nem nullázza a számlálókat.

A CHECK csak soron belüli tartományt véd: korábbi értékkel összevetett
monotonitást és cross-table status/claim összhangot nem. Ezeket a service
feltételes írása és célzott teszt védi; trigger/DB-write-privilege erősítés
OPEN a TC-03 kapuban. A `last_event_sequence` = MAX(event.sequence)
szintén tranzakciós, nem önmagában FK/CHECK-garancia.

### 2.3. `merlin_run_execution_events` mezőszótár

| Mező                      | Típus                   | NULL | Alapérték | Jelentés                                               | DB-korlát                                                            | Index                                      | Döntés        |
| ------------------------- | ----------------------- | ---- | --------- | ------------------------------------------------------ | -------------------------------------------------------------------- | ------------------------------------------ | ------------- |
| `event_id`                | UUID                    | nem  | nincs     | Backend esemény/receipt azonosító                      | PK                                                                   | PK                                         | D04, D11      |
| `run_id`                  | UUID                    | nem  | nincs     | Runhoz kötés az executionön keresztül                  | FK → executions.run_id, RESTRICT update/delete                       | unique kulcsok prefixe                     | D02, D04      |
| `sequence`                | COUNT                   | nem  | nincs     | Runon belüli növekvő sorrend, 1-től                    | 1..2147483647                                                        | `mree_sequence_uq` (`run_id`, `sequence`)  | D04           |
| `schema_version`          | COUNT                   | nem  | 1         | Eseményboríték verziója                                | =1                                                                   | nincs                                      | D05           |
| `command_id`              | UUID                    | nem  | nincs     | Backend, retry alatt stabil command identity           | runonként egy receipt                                                | `mree_command_uq` (`run_id`, `command_id`) | D01, D04, D11 |
| `command_kind`            | CODE(32)                | nem  | nincs     | Hat command egyikének neve                             | 7.1. értékkészlet                                                    | nincs                                      | D01, D05      |
| `fingerprint_version`     | COUNT                   | nem  | 1         | Canonical command protocol                             | =1                                                                   | nincs                                      | D11           |
| `command_fingerprint`     | HASH                    | nem  | nincs     | Command immutable tartalmának hash-e                   | HASH-korlát                                                          | nincs                                      | D04, D11      |
| `event_type`              | CODE(40)                | nem  | nincs     | Commandhoz kötött lifecycle tény                       | 7.1.; command/event páros CHECK                                      | nincs                                      | D04, D05      |
| `event_at_epoch`          | EPOCH                   | nem  | nincs     | A transition DB-ideje, nem a fizikai COMMIT időbélyege | EPOCH-korlát                                                         | nincs                                      | D03, D04      |
| `authority_kind`          | CODE(32)                | nem  | nincs     | User/worker/belső authority osztály                    | 7.1.; command-authority páros CHECK                                  | nincs                                      | D07, D13      |
| `authority_ref`           | UUID                    | nem  | nincs     | Backend authority/correlation referencia               | UUID; nem titok és nem capability önmagában                          | nincs                                      | D05, D07      |
| `authority_actor_id`      | signed BIGINT / INTEGER | igen | NULL      | USER/WORKER esetén a történeti kezdeményező ID         | >0; SQLite integer type; USER/WORKER-nél kötelező, SYSTEM-nél NULL   | nincs                                      | D07, D13      |
| `claim_id`                | UUID                    | igen | NULL      | Az esemény által látott claim                          | UUID; claim előtt NULL                                               | nincs                                      | D04, D09      |
| `from_status`             | CODE(30)                | nem  | nincs     | Előző állapot lowercase értéke                         | csak pending/running; transition CHECK                               | nincs                                      | D01, D06      |
| `to_status`               | CODE(30)                | nem  | nincs     | Új állapot lowercase értéke                            | running/completed/failed/cancelled; transition CHECK                 | nincs                                      | D01, D06      |
| `terminal_reason`         | CODE(100)               | igen | NULL      | Elsődleges terminális ok                               | runningnál NULL, terminálisnál kötelező és status/reason páros CHECK | nincs                                      | D05–D08       |
| `command_result`          | CODE(20)                | nem  | APPLIED   | Tartós receipt eredménye                               | =APPLIED; más outcome nem kerül ide                                  | nincs                                      | D04, D11      |
| `metadata_schema_version` | COUNT                   | nem  | 1         | Commandfüggő zárt metadata séma                        | =1                                                                   | nincs                                      | D05           |
| `metadata`                | JSON_TEXT               | nem  | nincs     | Minimális technikai bizonyíték                         | érvényes JSON object; 2..4096 UTF-8 bájt                             | nincs                                      | D05           |

Az esemény nem kap `updated_at` mezőt, tetszőleges payloadot vagy tokenhash-t.
Nincs külön user FK az immutable actor provenance-en: usertörlés nem írhatja
át az auditot. A runon lévő meglévő nullable live user FK megmarad.

A command/event/status/authority/reason kombinációk SQL CHECK-ekkel a 3–4.
és 7. rész zárt mátrixát követik. A hash-ek teljes összevetése és a claim/run
azonosság service-követelmény; egy `claim_id` FK önmagában nem bizonyítana
helyes owner-authenticationt. A jelen terv nem ad ilyen hamis garanciát.

### 2.4. Append-only, indexek, migráció és rollback

**PROPOSED:** normál runtime csak INSERT-et enged az event repositoryn;
nincs UPDATE/DELETE API. Ez nem DBA-ellenálló vagy kriptográfiai tamper-proof
napló. DB-szintű append-only enforcement módja TC-03 alatt nyitott;
a jelen rendszer model guardja önmagában nem védi a raw SQL-t.

Az indexnevek fenti javaslatok; nincs JSON-index vagy új business-parent
unique. Az execution → run FK és event → execution FK miatt a normál
run-törlés RESTRICT. Későbbi, külön engedélyezett retention cleanup:
event → execution → run sorrend, terminális lezárás +365 nap és lawful hold
ellenőrzésével; business history/case nem törölhető. Admission audit meglévő
cascade-je nem változik. Aktív legacy run nem törölhető a kora alapján.

Migrációs terv: driver/version/FK/JSON/CHECK előfeltétel vizsgálata; execution
tábla, majd event tábla létrehozása explicit MySQL InnoDB-vel. SQLite CHECK
az eredeti CREATE része, nem utólagos ALTER-feltételezés. Nincs adatmásolás,
backfill, új admission write vagy módosítás a régi migráción/`merlin_runs` DDL-en.
MySQL DDL több lépésének atomi rollbackja nincs feltételezve; félbemaradt
up után diagnosztika és kontrollált eljárás szükséges.

`down`: event, majd execution drop. Ez **adatvesztő**, ha már történt
lifecycle command; a régi run status/result/started_at nem gördül vissza.
Csak üres új táblákon vagy külön jóváhagyott backup/export és leállított
writer mellett engedhető. Aktív claim vagy megőrzendő receipt mellett
deployment rollback BLOCKED; kódkikapcsolás nem indok a receipt eltávolítására.
Üres round-trip és adatokkal a rollback refusal külön tesztelendő.

## 3. Lifecycle command-szerződések — részleteik PROPOSED

### 3.1. Közös envelope, receipt és visszatérés

Backend-owned envelope: `schema_version=1`, `command_id` UUID,
`command_kind`, `run_id` UUID, `authority_ref`, commandfüggő payload.
Actor/authority/case bindinget nem kliens vagy modell állítja; trusted entry
építi. Worker inputban a `claim_id` és titkos token csak memóriában utazhat
a belső hívásig; queue payloadban, JSON logban és receiptben nem.

Canonical fingerprint V1: rendezett kulcsú, szigorú, UTF-8 JSON az envelope
verziójából, run/command/kind/authority_ref és ellenőrzött authority_kind/
actor identityból, claim_id-ból és normalizált payloadból. Nincsenek floatok,
ismeretlen kulcsok vagy implicit típuskonverziók. Resultnál a validált canonical
result SHA-256 digestje része, nem a raw result. Token és tokenhash kimarad;
a token bizonyítása külön kötelező, a replay sem kerüli meg.
Fingerprint-verzióütközés elutasítás, nem best-effort összevetés.

A command identity, authority_ref és normalizált payload egy invocation/retry
alatt immutable. A lock alatti újraellenőrzés nem írhatja át a fingerprintelt
observation időpontját vagy payloadját; megváltozott üzleti bizonyíték új
commandot igényel. Receipt lookup után, csak új transitionnél történik a
szükséges fresh evaluation. ClaimRun eredeti commandjának replayjéhez a
token proof kötelező, de a deadline/aktív status új munkára vonatkozó gate-je
nem teszi olvashatatlanná az eredeti receiptet; a replay soha nem futási jog.

Közös legacy guard: **RUNNING execution sor vagy teljes claim tuple nélkül
mind a hat módosító command számára unmanaged**; nincs automatikus
execution-létrehozás, terminalizálás, token vagy deadline pótlás. Ilyenkor
REJECTED / LEGACY_EXECUTION_UNMANAGED és külön TC-07 eljárás szükséges.
A lazán létrehozott claim nélküli execution csak engedett PENDING
terminalizáláshoz használható. Terminális legacy runon nincs új írás vagy
kitalált receipt; az aktuálisan jogosított caller NO_CHANGE eredményt kaphat.

**ACCEPTED D04/D11, PROPOSED leképezés:** sikeres command = egy state transition
és egy tartós event/receipt ugyanabban a tranzakcióban. Receipt:
`event_id`, `run_id`, `command_id`, `command_kind`, fingerprint és verzió,
`sequence`, `event_type`, `from_status`, `to_status`, `terminal_reason`,
`event_at_epoch`, `command_result=APPLIED`. Nincs secret vagy advice a receiptben.

Válasz: `{outcome, error_code, receipt?}`; `APPLIED` és `REPLAYED` esetén
azonos minimal receipt. `REPLAYED` csak transport outcome, nem új event.
`NO_CHANGE` esetén nincs receipt az új commandhoz; terminális runon új ID
nem állíthatja, hogy az új parancs is alkalmazva lett. Elutasított command
nem kap success receiptet. Protected result-read külön authorized boundary.
Az existence/ownership védelme megelőzi a részletes hiba és receipt felfedését.

Azonos `(run_id, command_id)` + fingerprint: friss authority után receipt
replay, új írás, eligibility evaluation vagy mellékhatás nélkül. Eltérő
fingerprint: `COMMAND_ID_CONFLICT`. Másik command ID nem ugyanannak az
operationnek retryja. Unique ütközés után nincs általános automatikus retry;
új, jogosított read-only reconciliation kell. Az identity feloldása megelőzi
az új transition eligibility vizsgálatát, de nem előzi meg az authorizationt.

### 3.2. ClaimRun

| Szerződéselem              | Javasolt szabály                                                                                                                                                                                                                                      |
| -------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Kezdeményező / jogosultság | Trusted worker entry; backend technikai authority + friss live initiating user, két permission, bound case és eligibility. Nem Auth-session impersonation.                                                                                            |
| Bemenet                    | Közös envelope; backend előre generált `claim_id`, 32 kriptográfiailag véletlen bájtos claim token; nincs felhasználói deadline.                                                                                                                      |
| Előfeltétel                | Run PENDING, nincs claim; live/historical actor egyezik; material_shortage, OPEN case, authoritative VALID source és ACTIVE pozitív exact net requirement. Unknown evaluation esetén nincs claim. Pending policy, ha később elfogadott, szintén gate. |
| Átmenet                    | Kizárólag PENDING → RUNNING.                                                                                                                                                                                                                          |
| Tranzakció                 | 5.1. lock order; új execution sor; DB t időből claimed_at=t, deadline=t+180, tokenhash; run.status=running és started_at=UTC(t), updated_at=UTC(t); sequence=1; event insert, commit.                                                                 |
| Audit / receipt            | `run.claimed.v1`, reason NULL, authority WORKER; egy APPLIED receipt. Deadline és claim_id a minimális metadatában visszaadható, token soha nem része.                                                                                                |
| Idempotencia               | Az eredeti worker a már birtokolt tokennel és ugyanazzal a commanddal oldhat fel receiptet. Elveszett token nem generálható újra a hash-ből és nem küldhető ki új workernek.                                                                          |
| Konkurencia                | Case/run zár és run_id PK; két claimből legfeljebb egy sikeres. RUNNING újraclaim/takeover tiltott.                                                                                                                                                   |
| Hiba / visszatérés         | APPLIED/REPLAYED; DENIED, CASE_NOT_ELIGIBLE, CLAIM_ALREADY_EXISTS, STATE_NOT_ALLOWED, COMMIT_UNKNOWN vagy ROLLED_BACK. Unknown claim után nincs új worker vagy automatikus munka.                                                                     |

Failed eligibility esetén a ClaimRun nem terminalizál hallgatólagosan:
a trusted belső réteg külön StopRunForContextot kérhet új command identityval,
új authority/bizonyíték-ellenőrzéssel. Az sikertelen claim receiptjének nem
tüntethető fel.

### 3.3. FinalizeRun

| Szerződéselem              | Javasolt szabály                                                                                                                                                                                                                                |
| -------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Kezdeményező / jogosultság | Az eredeti worker, aktuális claim_id + token; friss initiating user és mindkét permission; friss bound case/current gate.                                                                                                                       |
| Bemenet                    | Envelope + claim proof + versioned advisory result + reason csak COMPLETED vagy HUMAN_INPUT_REQUIRED; backend validation digest.                                                                                                                |
| Előfeltétel                | RUNNING; claim egyezik; `db_now < execution_deadline_epoch`; nincs érvényes korábbi terminalizálás; schema és evidence reference valid; trustworthy ACTIVE/VALID basis. COMMIT-kori követelmények: TC-02.                                       |
| Átmenet                    | Csak RUNNING → COMPLETED; nem PENDING → COMPLETED.                                                                                                                                                                                              |
| Tranzakció                 | Lockok, replay check, fresh authority/current state, validation, conditional run update, completed_at/updated_at=UTC(t), stop_reason, bounded result/schema_version, execution sequence+1 és egy event. started_at megmarad, cancelled_at NULL. |
| Audit / receipt            | `run.finalized.v1`, megfelelő completed reason; result digest és schema version, nem result payload.                                                                                                                                            |
| Idempotencia               | Ugyanaz a canonical result és command ugyanazt a receiptet adja; eltérő result ugyanazon ID-val konfliktus. Replay nem ír új resultot.                                                                                                          |
| Konkurencia                | Korábban commitolt terminal állapotot nem ír felül. Zár után új DB-idő kell; lock wait előtt vett idő elégtelen.                                                                                                                                |
| Hiba / visszatérés         | APPLIED/REPLAYED vagy NO_CHANGE; DEADLINE_EXCEEDED, DENIED, CONTEXT_STOP_REQUIRED, RESULT_INVALID, COMMIT_GUARANTEE_UNAVAILABLE, COMMIT_UNKNOWN, ROLLED_BACK.                                                                                   |

A szükséges user/business feltételek COMMIT-ig tartó védelme és a fizikai
COMMIT-deadline nem bizonyított (TC-02). A pozitív FinalizeRun implementáció
**BLOCKED**, nem helyettesíthető „utolsó SQL ellenőrzés = COMMIT” állítással.
Hiba esetén nincs ugyanazon command alá rejtett FAILED/CANCELLED transition;
a megfelelő külön termination command következhet, ha az outcome rendezett.

### 3.4. TerminateFailedRun

| Szerződéselem              | Javasolt szabály                                                                                                                                                                                                 |
| -------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Kezdeményező / jogosultság | Érvényes, nem lejárt owner worker friss live userrel és két permissionnel ismert runtime hibára; vagy szűk SYSTEM_TERMINATION authority igazolt technikai hibára. A user hiánya nem ad workernek új jogot.       |
| Bemenet                    | Envelope, WORKER esetén claim proof; zárt technikai reason, bounded backend failure evidence. Nem raw exception.                                                                                                 |
| Előfeltétel                | RUNNING; ismert transaction outcome és működő audit; worker esetén db_now<deadline. SYSTEM esetben igazolt failure predicate, nem puszta tetszőleges exception. PENDING indulási hiba csak SYSTEM authorityvel.  |
| Átmenet                    | RUNNING → FAILED; PENDING → FAILED kizárólag igazolt indulási hibára.                                                                                                                                            |
| Tranzakció                 | Conditional terminal update, completed_at/updated_at=UTC(t), stop_reason; result NULL; pendingnél started_at NULL, runningnál megőrzött; sequence+event. Szükség esetén claim nélküli execution sor létrehozása. |
| Audit / receipt            | `run.failed.v1`; WORKER vagy SYSTEM_TERMINATION; egy APPLIED receipt.                                                                                                                                            |
| Idempotencia               | Azonos command/technikai reason/evidence digest ugyanaz a receipt; eltérő tartalom konfliktus.                                                                                                                   |
| Konkurencia                | Terminális run változatlan; expired worker helyett timeoutot RecoverRun kezel. Nem jogosult business uncertaintyt tetszőleges technikai hibának átnevezni.                                                       |
| Hiba / visszatérés         | APPLIED/REPLAYED/NO_CHANGE; DENIED, FAILURE_NOT_PROVEN, OWNER_MISMATCH, DEADLINE_EXCEEDED, STATE_NOT_ALLOWED, COMMIT_UNKNOWN, ROLLED_BACK.                                                                       |

### 3.5. CancelRun

| Szerződéselem              | Javasolt szabály                                                                                                                                                                                                                                             |
| -------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Kezdeményező / jogosultság | Hitelesített kezdeményező user friss `inventory.view` ÉS `item-suppliers.view` jogosultsággal; saját run. Super-admin sem cancelálhat más user helyett.                                                                                                      |
| Bemenet                    | Envelope; nincs worker token, arbitrary reason vagy model input. Reason backendből CANCELLED_BY_USER.                                                                                                                                                        |
| Előfeltétel                | PENDING vagy RUNNING; friss live user és kezdeményezőazonosság. Aktuális ACTIVE shortage nem feltétel az explicit saját cancellationhöz.                                                                                                                     |
| Átmenet                    | PENDING/RUNNING → CANCELLED.                                                                                                                                                                                                                                 |
| Tranzakció                 | Lockok/authorization/replay után conditional update; cancelled_at=completed_at=UTC(t), cancellation_requested_at meglévő értéke megmarad, hiányában UTC(t); updated_at=UTC(t), stop_reason; result NULL. started_at a pendingnél NULL marad. Sequence+event. |
| Audit / receipt            | `run.cancelled.v1`, USER, CANCELLED_BY_USER; receipt. Ez a backend állapot lezárása, nem külső provider-megszakítás bizonyítéka.                                                                                                                             |
| Idempotencia               | Azonos command receipt replayje; új ID terminális runon NO_CHANGE új event nélkül.                                                                                                                                                                           |
| Konkurencia                | Nem kell worker token; deadline lejárta önmagában nem írja át a korábban commitolt cancellationt. Versengő okok új döntését a 4.2. proposal rendezi.                                                                                                         |
| Hiba / visszatérés         | APPLIED/REPLAYED/NO_CHANGE; DENIED, STATE_NOT_ALLOWED, COMMIT_UNKNOWN, ROLLED_BACK. Visszavont permission esetén e user command nem engedélyezett; belső lezárás külön.                                                                                      |

### 3.6. StopRunForContext

| Szerződéselem              | Javasolt szabály                                                                                                                                                                                                                                                |
| -------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Kezdeményező / jogosultság | Trusted backend observer, szűk SYSTEM_CONTEXT authority; worker csak megfigyelést jelezhet, nem állíthat elő authorityt/evidence-t.                                                                                                                             |
| Bemenet                    | Envelope + zárt context stop kategória és trusted evidence referencia/digest. Case identity a runból, nem callerből.                                                                                                                                            |
| Előfeltétel                | Aktív run; lock után újra ellenőrzött revocation, user hiány, bizonyított basis megszűnés/érvénytelenség vagy authoritative uncertainty. User-jogvesztés után csak minimális belső audit/lezárás; supplier tool vagy disclosure tilos.                          |
| Átmenet                    | PENDING/RUNNING → CANCELLED bizonyított megszűnés/érvénytelenségre; → FAILED bizonytalan authoritative state-re. Revocation/user hiány CANCELLED mappingje részletes proposal.                                                                                  |
| Tranzakció                 | Belső authority és bounded bizonyítékellenőrzés; run terminal update, stop_reason, completed_at/updated_at; CANCELLED-nél cancelled_at=completed_at, cancellation_requested_at megőrzött, explicit user request nélkül NULL marad; result NULL; sequence+event. |
| Audit / receipt            | `run.context_stopped.v1`, SYSTEM_CONTEXT, egy elsődleges reason; további már ismert okok zárt metadata listája.                                                                                                                                                 |
| Idempotencia               | Azonos trusted observation identity és command stabil fingerprint; replaykor nem készül új üzleti evaluation. Új megfigyelés új command.                                                                                                                        |
| Konkurencia                | Nem kell használható worker token; már terminális állapotot nem módosít. Arbitrary timestamp vagy identity mismatch nem supersession bizonyíték.                                                                                                                |
| Hiba / visszatérés         | APPLIED/REPLAYED/NO_CHANGE; DENIED, CONTEXT_STOP_NOT_PROVEN, CONTEXT_EVIDENCE_INVALID, STATE_NOT_ALLOWED, COMMIT_UNKNOWN, ROLLED_BACK.                                                                                                                          |

Trustworthy uncertainty is rögzíthető: egy jogosult backend evaluation
hiányos/unavailable eredménye nem resolution. Ha viszont a DB/audit nem
használható, nincs garantált FAILED mentés; előbb infrastruktúra-helyreállítás.

### 3.7. RecoverRun

| Szerződéselem              | Javasolt szabály                                                                                                                                                                             |
| -------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Kezdeményező / jogosultság | Kontrollált belső recovery entry, SYSTEM_RECOVERY, runhoz kötött ellenőrzött capability; operatív owner/entry hozzáférés TC-08 alatt nyitott.                                                |
| Bemenet                    | Envelope + recovery kategória; expired claimnél elvárt claim_id és deadline; unresolved commandnál az eredeti command identity a diagnosztikához. Nincs tokenátadás/takeover.                |
| Előfeltétel                | RUNNING, létező claim tuple, `db_now >= execution_deadline_epoch`; vagy PENDING és később elfogadott, tartós pending policy. Bizonytalan command outcome előbb reconciliationnel rendezendő. |
| Átmenet                    | RUNNING → FAILED / TIME_LIMIT_REACHED; PENDING → FAILED / PENDING_DEADLINE_EXCEEDED csak TC-08 lezárása után. Legacy RUNNING execution nélkül nem automatikus ág.                            |
| Tranzakció                 | Fresh authority, receipt lookup, expiry/policy ellenőrzés; conditional terminal write, timestamps, result NULL, változatlan claim/deadline/counters; sequence+event.                         |
| Audit / receipt            | `run.recovered.v1`, SYSTEM_RECOVERY; eredeti claim identity és igazolt cutoff idő, egy APPLIED receipt.                                                                                      |
| Idempotencia               | Azonos command replay; új ID lezárt runon NO_CHANGE; az expiry újraellenőrzése nem új claim.                                                                                                 |
| Konkurencia                | A végső state az első érvényes commité; késő worker result elutasított. Scanner előzetes találata nem authority, lock alatt ismételt ellenőrzés kell.                                        |
| Hiba / visszatérés         | APPLIED/REPLAYED/NO_CHANGE; RECOVERY_NOT_DUE, LEGACY_EXECUTION_UNMANAGED, PENDING_POLICY_UNDEFINED, RECONCILIATION_REQUIRED, DENIED, COMMIT_UNKNOWN, ROLLED_BACK.                            |

Receipt feloldása olvasási művelet; nem szükségképpen RecoverRun transition.
Ha az eredeti FinalizeRun már commitolt, azt kell feloldani, nem új failure-t
írni. Tokenét elvesztett worker runja nem folytatható; expiry és kontrollált
recovery következhet, új investigationhöz új authorized admission szükséges.

## 4. Teljes állapotátmeneti mátrix

**ACCEPTED:** az ADR 0017 topologyja és a terminális immutability.
**PROPOSED:** a command-hozzárendelés és reason/prioritás.
Az alábbi nagybetűk fogalmi állapotok, a DB lowercaset tárol.

| Forrás \ Cél | PENDING               | RUNNING          | COMPLETED                        | FAILED                                                         | CANCELLED                        |
| ------------ | --------------------- | ---------------- | -------------------------------- | -------------------------------------------------------------- | -------------------------------- |
| PENDING      | tiltott új transition | ClaimRun         | tiltott                          | TerminateFailedRun / StopRunForContext / feltételes RecoverRun | CancelRun / StopRunForContext    |
| RUNNING      | tiltott               | tiltott új claim | FinalizeRun                      | TerminateFailedRun / StopRunForContext / RecoverRun            | CancelRun / StopRunForContext    |
| COMPLETED    | tiltott               | tiltott          | csak receipt replay, nincs write | tiltott                                                        | tiltott                          |
| FAILED       | tiltott               | tiltott          | tiltott                          | csak receipt replay, nincs write                               | tiltott                          |
| CANCELLED    | tiltott               | tiltott          | tiltott                          | tiltott                                                        | csak receipt replay, nincs write |

Aktív állapotban azonos command receipt replayje szintén olvasás. Más ID-val
terminális runon NO_CHANGE nem transition és nem eredeti siker a callernek.
Nincs `WAITING_FOR_HUMAN`, RUNNING → PENDING, restart vagy terminal overwrite.

### 4.1. Körülmény → command → status

| Körülmény                                    | Command és tervezett outcome                            | Minősítés                                                      |
| -------------------------------------------- | ------------------------------------------------------- | -------------------------------------------------------------- |
| Deadline előtt, minden feltétel teljesül     | FinalizeRun → COMPLETED                                 | D03/D06 feltétel ACCEPTED; részletes mechanizmus TC-02 BLOCKED |
| db_now = deadline vagy később                | FinalizeRun elutasítva; RecoverRun → FAILED             | Szigorú határ ACCEPTED; reason/mapping PROPOSED                |
| User permission visszavonva                  | Új munka tilos; StopRunForContext → CANCELLED           | D07 alapelv ACCEPTED; pontos predicate/reason PROPOSED         |
| Bizonyított RESOLVED / INVALID basis         | StopRunForContext → CANCELLED                           | D08 classification ACCEPTED; evidence predicate PROPOSED       |
| Authoritative business state nem igazolható  | StopRunForContext → FAILED                              | D08 classification ACCEPTED                                    |
| Bizonyított supersession nélkül újabb adat   | Nem CANCELLED bizonyíték; uncertainty/mismatch ág       | D08 alapelv ACCEPTED                                           |
| Ismert runtime failure, rendezett DB-outcome | TerminateFailedRun → FAILED                             | PROPOSED                                                       |
| Bizonytalan COMMIT                           | Nincs új transition addig, előbb receipt reconciliation | D11 ACCEPTED                                                   |

### 4.2. Konkurens lezárások és okprioritás — PROPOSED

Először minden esetben azonos command receiptje és a már commitolt terminal
állapot dönt. Az első **érvényes** terminális commit megmarad (D06); a zár
nem teszi utólag érvényessé a feltételeket megsértő FinalizeRunt.

Ha egy trusted belső döntési pont több aktuális okot is hitelesen ismer,
javasolt elsődleges sorrend: (1) authorization/user elvesztése, (2) timeout,
(3) bizonyított basis megszűnés/érvénytelenség, (4) authoritative uncertainty,
(5) ismert technikai failure. Egy csoporton belül a 7.1. reason felsorolási
sorrendje a tie-break. Ez külön jóváhagyandó TC-04, nem új elfogadott policy.

Ez nem globális ütemezői prioritás: két külön commandnál a lock/commit
sorrendje dönthet; egy később észlelt „magasabb” ok nem írhat terminal state-et.
CancelRun a saját igazolt user requestjét rögzíti; nem végez rejtett
timeout/context transitiont. Az észlelt további okok csak akkor kerülnek az
egyetlen context-stop event `additional_reasons` metadatájába, ha már
rendelkezésre állnak és biztonságosan naplózhatók. A többi event sémája
nem enged ilyen listát; kibővítésük külön TC-01 döntés. Terminális runhoz
új stop-eventet ez a draft nem vezet be;
a később felfedezett ok külön auditigénye TC-01 alatt OPEN.

## 5. Tranzakciós és konkurenciakezelési szerződés

### 5.1. Owned transaction és lock order — PROPOSED

1. Envelope szintaktikai validálás és disclosure nélküli preflight; run→case
   előzetes lookup csak routing hint. Nincs trusted snapshot a zár előtt.
2. Caller-owned Laravel/PDO transaction elutasítása; saját dedikált connection
   és ugyanaz a PDO, nincs reconnect, implicit driver retry vagy külső hívás.
3. MySQL saját InnoDB REPEATABLE READ / READ WRITE; első domain-zár:
   `problem_cases` sor → `merlin_runs` sor → execution sor, ha létezik.
   SQLite file-backed IMMEDIATE writer intent a domain read előtt;
   logikai sorrend ugyanaz, de nincs feltételezett MySQL row lock.
4. Run/case binding újraellenőrzése; friss szükséges user/technical authority,
   majd command/event lookup. Replay feloldása authorization után, új
   evaluation nélkül. Új operationnél terminal check, majd szükséges fresh
   authoritative observation és result validation ugyanazon connectionön.
5. Zárak után friss DB-idő; conditional írható állapot/claim/deadline/sequence
   predicate. Pontosan egy érintett run sor és a várt execution update kell.
   Nulla sor nem success. Execution létrehozás csak sikeres command része.
6. Run state/timestamps/result, execution és pontosan egy event együtt íródik;
   event insert failure az egész transition rollbackját igényli.
7. Közvetlenül commit előtt authority/clock/üzleti ellenőrzés az 5.2. korlátaival;
   sikeres COMMIT-ack előtt nincs APPLIED válasz. A connection/PDO identity
   a callback után is ellenőrzendő; purge nem minősíthet uncertain COMMIT-ot rollbackká.

A meglévő admission transaction mintaként használható, de nem automatikusan
újrafelhasználható lifecycle wrapper: még nincs benne run/execution lock,
DB-óra, command receipt vagy terminal arbitration. Bővítésének scope-ja
külön implementációs feladat. Lock order változtatása teljes deadlock auditot igényel.

Az execution CAS frissítés mindig a zár alatt olvasott
`last_event_sequence=n` értéket várja és `n+1`-re állítja; az event ugyanazzal
az `n+1` értékkel íródik. Új executionnél az első event sequence=1.
A sikeres íráshoz az alábbi további predicate-ek szükségesek:

| Command            | Kötelező conditional write feltétel a közös binding/authority mellett                                                                                      |
| ------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------- |
| ClaimRun           | run.status=pending; execution/claim még nincs; cancellation_requested_at NULL; t DB-időből tuple és +180 határ.                                            |
| FinalizeRun        | run.status=running; azonos claim_id és ellenőrzött tokenhash; változatlan deadline; t<deadline; érvényes result/current gate; TC-02 teljesülése szükséges. |
| TerminateFailedRun | run.status a jóváhagyott pending/running ág; runningnál managed claim; WORKER esetén azonos owner és t<deadline, SYSTEM esetén igazolt failure capability. |
| CancelRun          | run.status IN (pending,running); saját live/historical actor; runningnál managed execution; nincs worker tokenfeltétel.                                    |
| StopRunForContext  | run.status IN (pending,running); runningnál managed execution; backend által újraigazolt, engedett context ok és szűk capability.                          |
| RecoverRun         | run.status=running és azonos elvárt claim_id/deadline, t>=deadline; a pending ág csak később elfogadott tartós cutoff/policy alapján.                      |

Csak az eredeti binding és admission config megtartásával írható a run.
Nem cancellation command nem tüntethet el korábbi cancellation requestet;
CancelRun meglévő request időpontját megőrzi, ennek hiányában állítja t-re.
Az alkalmazásba kerülő pontos SQL és affected-row driver-szemantika
kétmotoros tesztet igényel; a fenti logikai predicate nem lefutott SQL-bizonyíték.

### 5.2. DB-idő, fizikai COMMIT és a garancia határa

**ACCEPTED — D03:** egész UTC epoch seconds; claimkor t és t+180,
immutable deadline. Javasolt adapterkifejezés MySQL-en
`TIMESTAMPDIFF(SECOND, '1970-01-01 00:00:00', UTC_TIMESTAMP())`, SQLite-on
`unixepoch('now')`. Ezek **PROPOSED**, az adott motoron még nem tesztelt
óra/precision-kontraktusok. Az időt a blokkoló lock után, külön statementben
kell olvasni; egy statement elején rögzült idő nem bizonyítja a lock wait
végén vagy a COMMIT-kor érvényes időt. Monotonic process óra legfeljebb
külső blocking budgetre használható később; tartós authority nem lehet.

A `merlin_runs.started_at/completed_at/cancelled_at/updated_at` értékei az
ugyanebben a tranzakcióban kiválasztott epoch t UTC DATETIME vetületei.
Éjfél, session timezone, DST és másodperchatár tesztelendő. Nem használunk
application Carbon::now értéket lifecycle authorityként; az admission
történeti időkezelését e dokumentum nem módosítja.

**BLOCKED — TC-02:** a „db_now<deadline a final UPDATE-ben” és az utolsó
pre-commit check nem bizonyítja, hogy a fizikai COMMIT is deadline előtt
történt. A folyamat a statement után felfüggeszthető, a COMMIT várhat.
Ugyanígy a jelenlegi case-lock nem foglal készletet és nem zárja az összes
permission pivotot, szerepkörváltozást vagy competing-scope üzleti adatot.
Újraolvasás önmagában nem jelent az összes writerrel sorosított revocationt.

Ezért az L1-D06 szigorú COMMIT-kori követelményét a mostani források és
egyszerű conditional UPDATE **nem igazolják**. Javaslat: kétmotoros
feasibility vizsgálat; a védendő business/permission writerkör és lock/version
protocol pontos leltára, fizikai commit-deadline megvalósíthatóságának külön
döntése. Ha csak transition linearization-time garantálható, az eltéréshez
explicit felhasználói architekturális döntés kell; nem minősíthető D06
teljesítésnek. Safety margin, timeout, utólagos timestamp vagy kompenzáló
terminal overwrite nem bizonyít szigorú COMMIT-garanciát.

TC-02 lezárásáig a pozitív ClaimRun/FinalizeRun teljes authorization/business
garanciája nem fogadható el implementációs szerződésként, worker nem aktiválható.
A draft bemutatható review-ra; az érintett implementációs kapu BLOCKED marad.
Óra-visszalépésnél `db_now < claimed_at` vagy utolsó event-idő esetén
`CLOCK_POLICY_VIOLATION`, nincs completion. A nem detektált óraugrások és
clock-health operatív policyja TC-02 része; nem állítunk monotonic DB-clockot.

### 5.3. Ownership, uncertain COMMIT, rollback és worker crash

Token: backend CSPRNG 32 bájt, tartósan SHA-256 hash; konstans idejű
ellenőrzés, run/claim bindinggel. `claim_id` nem titok és önmagában nem
authority. A worker nem veheti át más claimjét, nem hosszabbíthat deadline-t,
nem claimelhet RUNNING sort. Token nem queue payload, nem UI prop, nem audit
és nem loggable DTO. Elveszett tokenhez nincs reset/takeover endpoint.

**ACCEPTED — D11:** proven rollback után ellenőrzött retry lehetséges;
unknown COMMIT után nem. **PROPOSED:** alapból nincs automatikus
transaction retry. Rollback acknowledgement és megszűnt eredeti transaction,
külső hatás hiánya mellett azonos command/fingerprint új owned transactionben,
új authority és időellenőrzéssel manuálisan/kontrolláltan újrapróbálható.
Rollback failure vagy elveszett connection nem rollbackbizonyíték.

Uncertain protokoll:

1. `COMMIT_UNKNOWN` / `COMMIT_UNCERTAIN`; nincs új külső munka, új commanddal
   vak ismétlés vagy „sikerült/rollbackolt” kijelentés.
2. Eredeti run/command/fingerprint és authority_ref megőrzése titokmentes
   diagnosztikához; worker token csak a birtokló folyamat memóriájában.
3. Új connectionön friss authorized read a **writer primaryn**, nem laggoló
   replicán; case/run lock és receipt lookup. Az eredeti transaction
   kimenetét/zárainak megszűnését is rendezni kell.
4. Egyező receipt + koherens state/event chain: az eredeti operation alkalmazva.
   Régebbi ClaimRun receipt mellett a run lehet már terminális; replay nem
   ad futási jogot. FinalizeRun receipthez megőrzött megfelelő terminal result kell.
5. Nincs receipt: önmagában nem proven rollback. Constraint/state eltérés,
   még futó eredeti transaction vagy elérhetetlen primary esetén
   `RECONCILIATION_REQUIRED`, kontrollált operatív vizsgálat, további írás nélkül.

Belső reconciler csak technikai existence/outcome ellenőrzést végezhet;
advice/supplier disclosure-t nem. Visszavont user/lejárt worker nem kap új
execution jogot receiptből. Crash a claim előtt nem találhat ki startot;
crash utána a változatlan deadline lejártáig nem ad új claimet. Legacy
RUNNING execution nélkül `LEGACY_EXECUTION_UNMANAGED`: sor, timestamp és
state megőrzése, külön eljárás, nincs backfill (D09).

## 6. Jogosultsági és üzleti evidence szerződés

### 6.1. Authority-határok — PROPOSED részletezés

| Authority          | Engedett határ                                                                                                                                           | Tiltott                                                                            |
| ------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------- |
| USER               | Saját CancelRun; protected saját receipt/result read friss két permissionnel                                                                             | Más user runja super-adminként; revocation utáni cancellation saját jogon          |
| WORKER             | Egyszeri ClaimRun, saját FinalizeRun/owned TerminateFailedRun; friss live user/two-permission és run binding; claim/finalize esetén friss eligibility is | Token authorityvel user permission pótlása, deadline után írás, business execution |
| SYSTEM_CONTEXT     | Bizonyított context stop és minimális audit                                                                                                              | Supplier/advice olvasás vagy új investigation indítása revocation után             |
| SYSTEM_TERMINATION | Igazolt technikai failure szűk terminalizálása                                                                                                           | Általános exceptionből önkényes terminal overwrite vagy új tool call               |
| SYSTEM_RECOVERY    | Igazolt expired ownership/pending policy és technikai reconciliation                                                                                     | Worker takeover/resume, tokenújrakibocsátás, új AI/üzleti munka                    |

Belső authorityt backend által létrehozott, runhoz és command familyhez kötött
in-process capability igazol; egy bemeneti `authority_kind` string vagy
`authority_ref` UUID nem. Új publikus endpoint nincs. Operational entry access,
owner és auditálható indítás TC-08 alatt még OPEN.

Friss user: ténylegesen létező `User`, `initiating_user_id=historical_actor_id`,
mindkét permission a meglévő Gate-konvencióval. Nincs feltételezett `is_active`,
`disabled_at`, employee-active vagy új email-verification szabály.
A NULL live user FK mellett csak a historical actor ID őrzi a provenance-t,
nem a NULL FK ad authorityt. Super-admin csak a
permission-részen segít, own-run/case/claim/allowlist feltételt nem kerül meg.

Ellenőrzési pontok: trusted entry preflight; lock után; új claim és
finalization; később toolset discovery, minden invocation, response acceptance;
protected replay/result read. Utóbbiak L4 és későbbi határok, L1 nem hív toolt.
Ha friss lookup revocationt jelez, nincs új AI/üzleti művelet; belső observer
csak sanitizált lezárást és auditot végezhet. E szabály nem ígér már elküldött
provider request azonnali megszakítását vagy nulla további költséget.

### 6.2. Authoritative evidence — PROPOSED

Claim/finalize esetén a resolver DTO runhoz kötött case/requirement identityje,
`sourceValidity=VALID`, `sourceValidityAuthoritative=true`,
`nettingAuthoritative()=true`, evaluation ACTIVE, pontos pozitív decimal
net requirement szükséges. Persistált projection és detection snapshot nem
helyettesíti a current full competing-scope observationt.

Context stop bizonyítékai:

- RESOLVED: authoritative VALID source és authoritative netting exact `0.000`,
  helyes case/requirement binding → CANCELLED / CASE_RESOLVED.
- Source INVALID: trusted eligibility assessment authoritative invalidity,
  nem null evaluationből következtetés → CANCELLED / CASE_INVALID.
- Case INVALIDATED: aktuális hiteles case lifecycle → CANCELLED / CASE_INVALID.
  CLOSED → CASE_CLOSED proposal; nem resolution-bizonyíték. A jelenlegi
  resolver csak OPEN case-en hívható, lezárt case-nél külön lifecycle olvasás kell.
- Evaluation hiányos/unavailable vagy source authority bizonytalan → FAILED;
  nem `COMPLETED/HUMAN_INPUT_REQUIRED` és nem CANCELLED feltételezés.
- Identity mismatch → FAILED / CASE_CONTEXT_MISMATCH, nem supersession.
- Supersession/no-longer-applicable → csak külön igazolt trusted predicate
  alapján CANCELLED. Ilyen általános jelenlegi predicate nincs; az ág TC-05
  lezárásáig BLOCKED. Újabb időbélyeg vagy másik case nem bizonyíték.

A metadata csak observation kindot, időt, kötött azonosítókat és zárt
eredménykódot őriz. Nem ment új Evaluation Historyt, netting snapshotot vagy
supplier payloadot. Elegendő bizonyíték tartós, későbbi felülvizsgálhatósága
TC-05 része: egy hash nem pótolja a már nem létező eredeti evidence-t.

## 7. Audit, receipt, hibakódok és metadata — PROPOSED

### 7.1. Zárt értékkészletek V1

| Készlet                            | Pontos javasolt értékek                                                                                                                                                                                                                                                                                            |
| ---------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| command kind                       | `ClaimRun`, `FinalizeRun`, `TerminateFailedRun`, `CancelRun`, `StopRunForContext`, `RecoverRun`                                                                                                                                                                                                                    |
| event type és párosítása           | ClaimRun=`run.claimed.v1`; FinalizeRun=`run.finalized.v1`; TerminateFailedRun=`run.failed.v1`; CancelRun=`run.cancelled.v1`; StopRunForContext=`run.context_stopped.v1`; RecoverRun=`run.recovered.v1`                                                                                                             |
| authority kind                     | `USER`, `WORKER`, `SYSTEM_CONTEXT`, `SYSTEM_TERMINATION`, `SYSTEM_RECOVERY`                                                                                                                                                                                                                                        |
| command result / transport outcome | `APPLIED`, `REPLAYED`, `NO_CHANGE`, `REJECTED`, `ROLLED_BACK`, `COMMIT_UNKNOWN`; DB receiptben csak APPLIED                                                                                                                                                                                                        |
| COMPLETED terminal reason          | `COMPLETED`, `HUMAN_INPUT_REQUIRED` (utóbbi csak igazolt eligibility mellett advisory information gapre)                                                                                                                                                                                                           |
| CANCELLED terminal reason          | `AUTHORIZATION_REVOKED`, `USER_UNAVAILABLE`, `CASE_RESOLVED`, `CASE_INVALID`, `CASE_CLOSED`, `INVESTIGATION_BASIS_SUPERSEDED`, `CANCELLED_BY_USER`                                                                                                                                                                 |
| FAILED terminal reason             | `TIME_LIMIT_REACHED`, `AUTHORITATIVE_STATE_UNDETERMINED`, `EVALUATION_UNAVAILABLE`, `ELIGIBILITY_UNDETERMINED`, `ACTOR_BINDING_INVALID`, `CASE_CONTEXT_MISMATCH`, `START_FAILED`, `STEP_LIMIT_REACHED`, `TOOL_DENIED`, `TOOL_FAILED`, `MODEL_FAILED`, `RUNTIME_INFRASTRUCTURE_FAILED`, `PENDING_DEADLINE_EXCEEDED` |

Command/reason restriction: FinalizeRun csak COMPLETED-ok; CancelRun csak
CANCELLED_BY_USER; StopRunForContext csak context/user/identity/uncertainty
okok; TerminateFailedRun csak START_FAILED, STEP_LIMIT_REACHED, TOOL_DENIED,
TOOL_FAILED, MODEL_FAILED, RUNTIME_INFRASTRUCTURE_FAILED (START_FAILED csak
PENDING/SYSTEM); RecoverRun csak a két deadline reason.
StopRunForContext FAILED-ok: AUTHORITATIVE_STATE_UNDETERMINED,
EVALUATION_UNAVAILABLE, ELIGIBILITY_UNDETERMINED, ACTOR_BINDING_INVALID,
CASE_CONTEXT_MISMATCH. USER_UNAVAILABLE és CASE_CLOSED részletes policy
jóváhagyandó; SUPERSEDED és pending timeout ág BLOCKED a megfelelő kapuig.
L1 nem implementál model/tool hibaforrást vagy attempt reservationt;
e reasonök jövőbeli lifecycle fogyasztókhoz kötött contracthelyek.

Zárt `error_code` készlet (`APPLIED`/`REPLAYED` esetén NULL):
`INVALID_COMMAND`, `UNSUPPORTED_SCHEMA`, `DENIED`, `STATE_NOT_ALLOWED`,
`CASE_NOT_ELIGIBLE`, `CLAIM_ALREADY_EXISTS`, `OWNER_MISMATCH`,
`DEADLINE_EXCEEDED`, `COMMAND_ID_CONFLICT`, `CONTEXT_STOP_REQUIRED`,
`RESULT_INVALID`, `FAILURE_NOT_PROVEN`, `CONTEXT_STOP_NOT_PROVEN`,
`CONTEXT_EVIDENCE_INVALID`, `RECOVERY_NOT_DUE`, `LEGACY_EXECUTION_UNMANAGED`,
`PENDING_POLICY_UNDEFINED`, `COMMIT_GUARANTEE_UNAVAILABLE`,
`CLOCK_POLICY_VIOLATION`, `TRANSACTION_POLICY_VIOLATION`, `LOCK_CONFLICT`,
`AUDIT_WRITE_FAILED`, `PERSISTENCE_FAILED`, `COMMIT_UNCERTAIN`,
`RECONCILIATION_REQUIRED`, `ALREADY_TERMINAL`.

Mapping: REJECTED a validáció/authority/state/known-precondition hibákra;
NO_CHANGE + ALREADY_TERMINAL új commandnál; ROLLED_BACK + LOCK_CONFLICT /
AUDIT_WRITE_FAILED / PERSISTENCE_FAILED csak bizonyított teljes rollbacknál;
COMMIT_UNKNOWN + COMMIT_UNCERTAIN / RECONCILIATION_REQUIRED ismeretlen
outcome-nál. Audit write hiba nem önálló terminal reason: nem mentünk
FAILED/AUDIT_FAILED state-et a hiányzó kötelező audit nélkül.

### 7.2. Metadata V1 és érzékeny adatok kizárása

**ACCEPTED D05:** maximum 4096 UTF-8 bájt/event, zárt verziózott séma.
**PROPOSED:** JSON object, duplicate key tiltott, unknown key elutasított;
canonical kulcssorrend, unescaped Unicode, pontos integer/boolean/string/null
típusok, nincs float. A limit a ténylegesen tárolt szerializált UTF-8 JSON
bájtjaira vonatkozik, escape-ekkel együtt; karakterhossz nem elég.

| Event                  | Engedett kötelező és opcionális metadata mezők                                                                                       |
| ---------------------- | ------------------------------------------------------------------------------------------------------------------------------------ |
| run.claimed.v1         | kötelező `claimed_at_epoch`, `execution_deadline_epoch`; opcionális nincs                                                            |
| run.finalized.v1       | kötelező `result_schema_version` (>0 integer), `result_digest` (HASH), `observation`                                                 |
| run.failed.v1          | kötelező `failure_code` (azonos az engedett technikai terminal reasonnel); opcionális `related_command_id` UUID                      |
| run.cancelled.v1       | üres `{}`; a request tényét az authority és event hordozza                                                                           |
| run.context_stopped.v1 | kötelező `observation`; opcionális `additional_reasons` (legfeljebb 4 különböző engedett terminal reason)                            |
| run.recovered.v1       | kötelező `recovery_basis` (`EXECUTION_EXPIRED` vagy későbbi `PENDING_EXPIRED`), `cutoff_epoch`; opcionális `related_command_id` UUID |

`observation` pontos objektum: `kind` a következők egyike:
`ACTIVE_VERIFIED`, `AUTHORIZATION_LOST`, `USER_MISSING`, `RESOLVED_VERIFIED`,
`BASIS_INVALID_VERIFIED`, `CASE_CLOSED_VERIFIED`, `SUPERSESSION_VERIFIED`,
`STATE_UNDETERMINED`, `EVALUATION_UNAVAILABLE`, `IDENTITY_MISMATCH`;
`observed_at_epoch` EPOCH; `problem_case_id` UUID;
`material_requirement_id` pozitív integer; opcionális `evidence_ref` UUID.
A kind és reason konzisztenciáját sémaellenőrzés védi. A `SUPERSESSION_VERIFIED`
és `evidence_ref` konkrét trusted feloldója TC-05 alatt OPEN; kitalált
Evaluation History FK vagy nem létező audit locator nem adható meg.

Mélység legfeljebb 3, nincs tetszőleges tömb vagy szabad szöveges reason.
Limit felett reject és rollback; nincs csendes truncation, amely invalidálja
az evidence-t. A DB JSON/méret CHECK nem teljes metaséma-validátor;
utóbbi szinkron backend validator feladata, teszttel.

Tilos: claim token **és tokenhash** auditba/receiptbe, jelszó, API key, session,
név/email snapshot, raw prompt/model output, teljes supplier/pricing payload,
SQL, exception message/stack trace, korlátlan üzleti snapshot. A result a
védett run.resultban, maximum 65535 UTF-8 bájttal és validált verzióval él;
receiptből nem kérhető le permission nélkül. Audit/hash sem jogosít disclosure-ra.

## 8. Tesztelési és Quality Gate szerződés — jövőbeli terv

Ebben a feladatban **nem készült és nem futott alkalmazásteszt**. Az alábbi
új fájlnevek PROPOSED célhelyek; a hivatkozott meglévő tesztek valódi források,
olvasásuk nem jelenti jelenlegi PASS állítását.

### 8.1. Tesztmátrix

| Csoport / javasolt új fájl                             | Bizonyítandó esetek                                                                                                                                                                                                 | Motor / szakasz                                                 |
| ------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------- |
| `tests/Unit/MerlinLifecycleContractTest.php`           | Hat command envelope, canonical bytes/fingerprint, zárt kódok, mind a 25 status-pár, allowed/forbidden command mátrix, receipt outcome mapping                                                                      | Determinisztikus unit; L1                                       |
| `tests/Feature/MerlinExecutionMigrationTest.php`       | Üres up/down/up; régi run/admission/case/history változatlan; populated rollback refusal; FK/collation/index/CHECK/JSON/int type; nincs legacy backfill                                                             | SQLite és guardolt MySQL; L1                                    |
| `tests/Feature/MerlinExecutionPersistenceTest.php`     | Claim tuple NULL/részleges/teljes; +180 immutable contract; 8/4 counter bounds; unique run/claim/command/sequence; metadata 4096/4097 UTF-8 bájt, multibyte/escaped JSON; append-only és cross-table védelem határa | Mindkét motor; L1–L2                                            |
| `tests/Feature/MerlinLifecycleCommandTest.php`         | Mind a hat command pozitív útja; PENDING cancel/fail started_at NULL; result/timestamp CHECK-ek; minden tiltott transition nulla írás; két terminal reason classification                                           | Mindkét motor; L2–L3                                            |
| `tests/Feature/MerlinLifecycleIdempotencyTest.php`     | Azonos command azonos receipt; eltérő fingerprint reject; más ID terminal NO_CHANGE; replay auth előtt nem fed adatot; elveszett token nem reissue; replay nem fresh eligibility vagy új event                      | File-backed SQLite és MySQL; L2–L3                              |
| `tests/Feature/MerlinLifecycleConcurrencyTest.php`     | Két független process claimje; finalize/cancel/recover verseny; több stop reason; lock wait után fresh idő/auth; audit insert fault minden write rollback; affected rows=0                                          | MySQL valódi contention; SQLite writer/busy külön; L2–L3        |
| `tests/Feature/MerlinLifecycleAuthorizationTest.php`   | Permission direkt/role útú revoke, user delete, super-admin más runja, forged authority_ref, elavult token, context mismatch, current full-scope evaluation, nincs supplier/business mellékhatás                    | Mindkét motor; L2–L4                                            |
| `tests/Feature/MerlinLifecycleDeadlineTest.php`        | t+179/t+180/t+181, lock wait határátlépés, DB/app clock eltérés, timezone/DST, clock rollback, **utolsó check utáni pause majd COMMIT**, párhuzamos permission/business változás commit előtt                       | TC-02 feasibility és kétmotoros bizonyíték; nem csak fake clock |
| `tests/Feature/MerlinLifecycleReconciliationTest.php`  | Proven rollback retry; post-commit ack-loss receipt feloldás; no-receipt nem rollback; eredeti tx még aktív; primary unavailable; revoked caller nem kap resultot; nincs külső replay                               | Két motor + fault injection; L3                                 |
| Külön `merlin-mid-commit-connection-loss` tesztcsoport | Valódi kapcsolatvesztés a COMMIT körül, nem post-commit exception; eredeti connection outcome és unique receipt rendezése                                                                                           | Kontrollált dedikált infra; worker előtt                        |
| `tests/Feature/MerlinLegacyExecutionTest.php`          | Régi RUNNING execution nélkül megmarad; Claim/Recover nem backfillel; terminal legacy nem újranyitható; PENDING szabály nincs feltételezve                                                                          | Két motor; L1–L3                                                |
| Meglévő gate-tesztfájl bővítése később                 | Merlin source/test/migration útvonalak; integration tervbe changed backend merge; alapregresszió; dedupe, Windows paths, ismeretlen source fallback; dry-run nem futtat child tesztet                               | Külön QG változtatás, L1-D10                                    |

A counter valódi monoton reservationje és call idempotencia külön L4
contract/tesztcsoport, nem a hat lifecycle commandba rejtett új művelet.
TC-01-ben kell új command/event határról dönteni, mielőtt attempt dispatch
implementálható. Paid teszt csak fake adapterrel, élő providerhívás nélkül.

Meglévő regresszió kötelező minimuma későbbi implementációhoz:

- [MerlinRunPersistenceTest](../../../tests/Feature/MerlinRunPersistenceTest.php),
  [MerlinRunMigrationTest](../../../tests/Feature/MerlinRunMigrationTest.php).
- [MerlinAdmissionInfrastructureTest](../../../tests/Feature/MerlinAdmissionInfrastructureTest.php),
  [MerlinAdmissionServiceTest](../../../tests/Feature/MerlinAdmissionServiceTest.php).
- [MerlinAdmissionContractTest](../../../tests/Unit/MerlinAdmissionContractTest.php),
  [MerlinAdmissionCollisionTest](../../../tests/Unit/MerlinAdmissionCollisionTest.php).
- [SupplierOptionsToolTest](../../../tests/Feature/SupplierOptionsToolTest.php),
  a resolver/supplier domain kapcsolódó regressziójával a gate-tervben.

Az admission service ack-loss tesztje sikeres parent transaction után dob
kivételt. Ez hasznos minta, de nem valódi mid-COMMIT hálózati fault-bizonyíték.
SQLite :memory: teszt nem helyettesíti a file-backed writer contentiont;
SQLite siker nem MySQL lock/commit bizonyíték. MySQL csak dedikált guardolt
tesztadatbázison, soha production adatokkal.

### 8.2. Quality Gate: kötelező külön javítás

**ACCEPTED D10:** a kiválasztás javítása kötelező, nem váltható ki kézi
Merlin suite-futtatással. A jelen `GatePlanner::integration()` csak a
module/integration listát egyesíti. Az ismeretlen Merlin source integration
fallbackja ezért nem bizonyítja a Merlin tesztek tényleges futását.

**PROPOSED javítás:** explicit Merlin modul/source/test mapping és alapvető
regressziós lista; integration backend tervbe a selected changed backend
tesztek unionja dedupe-pal. Külön selector/planner tesztek igazolják mind
az explicit integrationt, mind a fallbackot és a változott tesztet.
A meglévő supplier-option mapping nem tekinthető teljes Merlin coverage-nek.

A javítás külön azonosítható implementáció legyen. A gate-infrastruktúra
módosítása a [rétegezett gate-ek](../../development/quality-gates.md) szerint
full kockázati kategória; migrationnél releváns full/migration gate és
külön guardolt MySQL bizonyíték kell. Szűk fejlesztői futás után a szükséges
széles kapu egyszer futtatandó; indokolatlan ismétlés nélkül.

Elfogadási bizonyíték: tényleges gate-plan és parancslista, kiválasztott
tesztfájlok, motor/verzió/környezet, lefutott teszt/assertion és skip számok,
exit code, fault típusa, megőrzött artifact/riport. Dry-run csak kiválasztási
bizonyíték; nem runtime PASS. TC-02, TC-03 és TC-05 nyitott garanciáit
tesztterv vagy régi PASS nem zárja le.

## 9. Nyitott kérdések és jóváhagyási kapuk

A határidő az adott implementációs vagy aktiválási mérföldkő; nem kitalált
naptári vállalás. A jóváhagyó megjelölése szükséges szerep, nem megszerzett approval.

| ID / státusz                                        | Probléma                                                                                       | Javaslat                                                                                            | Alternatívák                                                                         | Kockázat                                                       | Döntési határidő                                                               | Szükséges jóváhagyás                                                  |
| --------------------------------------------------- | ---------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------ | -------------------------------------------------------------- | ------------------------------------------------------------------------------ | --------------------------------------------------------------------- |
| TC-01 / OPEN                                        | Egy event/command, receipt, további stop okok és későbbi attempt eventek felosztása            | E draft hat commandja egy-egy event; event=receipt; nincs post-terminal új event                    | Több event/command külön receipt contracttal; külön jövőbeli attempt commandok       | Dedupe ütközés, hiányzó audit vagy párhuzamos ledger           | L1 séma/command véglegesítése előtt; L4 reservation előtt újra                 | Felhasználó mint architekturális döntéshozó + audit/security review   |
| TC-02 / BLOCKED                                     | Fizikai COMMIT-deadline és commitig védett permission/business feltételek nincsenek bizonyítva | Kétmotoros feasibility és writer/lock/version leltár; pozitív completion blokkolt                   | Bizonyított erősebb protokoll; explicit új döntés linearization-time követelményről  | Határidő utáni/jogosulatlan completion, hamis DB-garancia      | Pozitív Claim/Finalize implementáció előtt, L1 contract elfogadásának kapuja   | Felhasználó + DB/architecture/security felelős; bizonyítékkal         |
| TC-03 / OPEN                                        | Append-only, immutable claim és counter monotonitás raw SQL útvonalon                          | Service conditional writes + tiltott repository műveletek; DB trigger/privilege lehetőség felmérése | Kétmotoros triggerekkel erősítés; explicit runtime-only threat model                 | Raw update megkerüli a modell/service guardot                  | L1 DDL és persistence enforcement elfogadása előtt                             | Architecture/DB/security review + felhasználó                         |
| TC-04 / OPEN                                        | Terminal reason prioritás, revocation/user hiány/CLOSED részletes mapping                      | 4.2. sorrend és 7.1. reason family                                                                  | Deadline-first; csak tényleges command commit sorrendje                              | Eltérő elsődleges ok, uservesztés félreosztályozása            | L1 command contract, L2–L3 előtt                                               | Felhasználói policyjóváhagyás                                         |
| TC-05 / BLOCKED a supersession ágra; egyébként OPEN | Authoritative invalidation/uncertainty bizonyíték és tartós evidence ref                       | 6.2. szűk DTO predicate, minimális metadata; unsupported supersession tiltva                        | Külön trusted üzleti evidence record; feature elhalasztása                           | Feltételezett megszűnés vagy később nem auditálható bizonyíték | L1 evidence contract előtt; konkrét worker predicate worker előtt bizonyítandó | Domain/architecture/security felelős + felhasználó                    |
| TC-06 / OPEN                                        | Régi UNDETERMINED és AUDIT_FAILED specifikációs mapping                                        | Külön dokumentációs alignment az elfogadott FAILED/atomi rollback elvhez                            | Régi mapping történeti archiválása explicit jelöléssel                               | Eltérő dokumentum alapján hibás implementáció                  | Runtime contract elfogadása előtt                                              | Felhasználói dokumentációmódosítási engedély                          |
| TC-07 / OPEN; automatikus legacy recovery BLOCKED   | Legacy RUNNING claim/deadline nélkül                                                           | Megőrzés, diagnostic-only, külön jóváhagyott manuális eljárás                                       | Üzemi karantén/operátori vizsgálat; nincs automatikus backfill alternatíva D09 alatt | Újrafuttatás, traceabilityvesztés, hamis expiry                | Legacy érintő deployment/recovery előtt                                        | Üzemeltető + felhasználó, auditálható eljárás                         |
| TC-08 / OPEN; pending recovery BLOCKED              | PENDING/dispatch határidő, tartós policy, scanner owner/entry/visibility                       | Külön worker/dispatch szerződés; e sémában nincs kitalált pending deadline                          | Későbbi additive schema; külön outbox-döntés, ha szükséges                           | Végtelen várakozás, túl korai lezárás, queue duplicate         | Worker/dispatch tervezésekor, éles aktiválás előtt                             | Felhasználó + üzemeltetés; schema-változáshoz külön scope             |
| TC-09 / OPEN; L1 implementáció elfogadása BLOCKED   | Gate Merlin-kiválasztási hiányossága                                                           | 8.2. mapping/union és külön selector/planner regresszió                                             | Eltérő modulstruktúra, ugyanazzal a bizonyított coverage követelménnyel              | Hamis zöld integration, kihagyott lifecycle teszt              | L1 implementáció elfogadása előtt                                              | Külön implementációs engedély + tényleges tesztbizonyíték             |
| TC-10 / OPEN; worker aktiválás BLOCKED              | Unknown COMMIT/call outcome authority és valódi connection-loss bizonyíték                     | 5.3. primary receipt reconciliation + kontrollált fault környezet                                   | Worker aktiválás halasztása                                                          | Dupla külső hatás, hamis rollback                              | Authority contract L1 előtt; fault-bizonyíték worker előtt                     | DB/üzemeltetés/security + felhasználó                                 |
| TC-11 / OPEN; paid activation BLOCKED               | Monetary ceiling, hívásszám, accounting, unknown usage és provider                             | Paid false marad; később külön költség/enforcement contract                                         | Csak determinisztikus fake/local tesztadapter, élő paid nélkül                       | Korlátlan vagy duplikált költés                                | Bármely paid hívás előtt                                                       | Felhasználói architekturális és külön üzemeltetői engedély            |
| TC-12 / OPEN                                        | Exact DDL, epoch tartomány, rollback/retention, version upgrade                                | 2. rész explicit kétmotoros schema, populated down tiltás; dependency-order cleanup később          | FK delete policy eltérő, külön jóváhagyva; forward-only üzemi rollback               | Receiptvesztés, FK/collation hiba, jogszerű törlés elakadása   | L1 migráció előtt; retention eljárás előtt újra                                | DB/architecture review, retentionhez megfelelő üzemeltetői jóváhagyás |

Javasolt sorrend: (1) TC-02 feasibility és garanciahatár, TC-04/05 policy;
(2) TC-01 receipt/event és TC-03/12 schema/enforcement; (3) a teljes draft
felhasználói elfogadása és TC-06 külön alignment; (4) új implementációs
engedély, TC-09 külön Quality Gate javítás, L1 persistence és valódi kapuk;
(5) L2–L3 command service, TC-07/08/10 operatív feltételek;
(6) L4/L5 fake runtime/dispatch; TC-11 paid aktiválás ettől külön döntés.

## 10. Forráseltérések és döntési visszakövetés

| Forráseltérés                                                                             | A draft kezelése                                                                                                                       |
| ----------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------- |
| ADR 0018 execution metadata a run körül, nyitott schema                                   | D02 két kiegészítő táblája; régi run DDL változatlan; tárolt run state/timestamps kompatibilis frissítése szükséges.                   |
| ADR 0018 ELV1-06 több event/command és javasolt `(run_id, command_id, event_type)` unique | D04 alapján itt `(run_id, command_id)` unique, egy event=receipt; a különbség TC-01, nem hallgatólagos ADR-átírás.                     |
| ADR 0018 clock és metadata bounds OPEN                                                    | D03/D05 már eldöntötte az epoch/DB-óra/4096 bájt alapot; konkrét SQL/DDL/commit mechanizmus itt továbbra PROPOSED/BLOCKED.             |
| Specifikáció 7.6. UNDETERMINED → COMPLETED/HUMAN_INPUT_REQUIRED                           | D08/ADR 0018 szerint authoritative uncertainty FAILED; advisory information gap külön marad. TC-06 alatt régi forrás még nincs átírva. |
| Specifikáció 7.6. AUDIT_FAILED → FAILED javaslat                                          | Audit failure rollbackot vagy unknown outcome-ot jelenthet, nem garantált mentett FAILED state-et.                                     |
| ADR 0018 gate-finding: javítás vagy explicit coverage                                     | D10 kötelező selectorjavítást és külön teszteket ír elő; kézi futás nem elég.                                                          |
| Friss boundary check vs. D06 COMMIT-kori feltétel                                         | Külön TC-02 BLOCKED kapu; nem állítjuk, hogy a meglévő case/user lockok az összes business/permission writert lefedik.                 |
| Régi admission dokumentum commit/review függő szövegei                                    | Történeti állítások, nem új review/production readiness bizonyíték.                                                                    |

| Elfogadott döntés | Kidolgozott szerződéshely / fennmaradó kapu                       |
| ----------------- | ----------------------------------------------------------------- |
| L1-D01            | 3. rész hat önálló command; 4. rész teljes mátrix                 |
| L1-D02            | 2. rész két tábla, run DDL változatlan; TC-03/12                  |
| L1-D03            | 2. és 5.2. DB-óra/epoch/180/egyenlőség; TC-02                     |
| L1-D04            | 2.3., 3.1., 7. rész egy event=receipt; TC-01                      |
| L1-D05            | 2. és 7. rész zárt készlet/verzió/bájthatár/titokvédelem          |
| L1-D06            | 4–5. rész terminal immutability; TC-02/04                         |
| L1-D07            | 3.6., 6. rész friss auth és szűk belső lezárás; TC-02/04          |
| L1-D08            | 4.1., 6.2. bizonyított vs. bizonytalan basis; TC-05/06            |
| L1-D09            | 2.4., 3.7., 5.3., 8. rész nincs backfill/takeover; TC-07          |
| L1-D10            | 8.2. külön gate javítás és tesztbizonyíték; TC-09                 |
| L1-D11            | 3.1., 5.3. uncertain COMMIT/receipt/no blind replay; TC-10        |
| L1-D12            | 3.7., 9. rész pending/dispatch külön worker policy; TC-08         |
| L1-D13            | 1.1., 6.1. tényleges User/Gate, nincs kitalált flag               |
| L1-D14            | 1.2., 9. rész paid false és külön költség/üzemeltetői kapu; TC-11 |

## 11. A jelen dokumentációs feladat ellenőrzési és átadási határa

Csak ez az új Markdown-fájl készül. A régi ADR/specifikáció/baseline,
alkalmazáskód, teszt, migráció és konfiguráció változatlan. Nincs staging,
commit, push, providerhívás vagy implementáció. A dokumentum felhasználói
review-ra adható; a BLOCKED sorok implementációs előfeltételek, nem lezárt
követelmények.

A [projekt Definition of Done](../../project-management/definition-of-done.md)
dokumentációs változástípusa alkalmazandó: teljes untracked tartalom,
14 döntés-visszakövetés, 9 kért témakör, mező- és commandteljesség,
belső konzisztencia, relatív hivatkozások, Prettier és Git whitespace/scope.
Nincs dedikált repository link-check script; célzott helyi linkellenőrzés kell.
Alkalmazás-, migration-, E2E-, build-, Pint/PHPStan- és dependency-audit futás
itt `N/A`: kizárólag nem végrehajtott tervezési dokumentáció változik.
Más dokumentációs réteg, API vagy UI-fordítás módosítása ebben a draftban
nem szükséges; a forrásalignment külön feladat marad.

A tényleges ellenőrzési eredmények a zárójelentésben szerepelnek;
a tesztterv egyik sora sem sikeres tesztfuttatás állítása.
