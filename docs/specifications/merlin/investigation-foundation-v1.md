# Merlin Investigation Foundation V1 — specifikáció

- **Dátum:** 2026-10-08
- **Baseline:** `feature/merlin-candidate` / `45544b19017d1f31564cb3e66c5bc3f74d4071b8`
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
→ case access és start authorization
→ fresh material-shortage current evaluation
→ OPEN + ACTIVE eligibility
→ backend-bound investigation context
→ authorized allowed toolset assembly
→ persisted investigation és supervised execution
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
A fresh worker-entry/finalization check konkrét mechanizmusa PROPOSED a 7. részben.

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

MerlinRun és investigation-owned evidence/results retentionje **365 nap**,
későbbi legal/operational retention constraint mellett. A retention clock kezdete,
cleanup és dependency/FK kezelés PROPOSED. Nem terjed ki automatikusan a Problem
Case detection snapshotjára vagy Evaluation Historyjára; üzleti history törlést
nem engedélyez. Shared activity log retentionjét sem írja át ez a dokumentum.

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
követelmény és konfiguráció külön döntendő. A [logging config](../../../config/activitylog.php)
disabled/buffered módot is enged, a cleanup 365 napja nem automatikus run cleanup.

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

## 7. PROPOSED — technikai döntések és blockerek

Minden alábbi sor **PROPOSED**, nem ACCEPTED. A stop reason nevek is javaslatok.
Az „indítás” jelölés a következő persistence/start szelet előtti döntési blockert,
a „runtime” a későbbi execution előtti blockert jelenti.

| Tétel                          | Ajánlott opció és eldöntendő részlet                                                                                                                                                  | Blokkolt határ                                            |
| ------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------- |
| Start idempotency              | Actorhoz és case-hez scoped key; ugyanaz a key ugyanazt a runt adja, eltérő requestre conflict; megőrzés és replay közbeni fresh disclosure döntendő                                  | Indítás                                                   |
| Atomic admission/history link  | Run és egy jelentős start evaluation mentése egy rövid write transactionben; stabil history FK/reference; snapshot és write közti stale admission kezelése eldöntendő                 | Indítás                                                   |
| Concurrency enforcement        | Case row lock és aktív-run ellenőrzés minden admission útvonalon; unique admission slot alternatíva; idempotent replay nem új run, más aktív run conflict nem disclosure              | Indítás                                                   |
| Worker claim/crash recovery    | Atomic PENDING→RUNNING claim és fencing/lease; dead worker safe terminal recovery, automatikus paid replay nélkül; PENDING orphan/dispatch failure recovery is szükséges              | Claim interfész indítás előtt; runtime mechanizmus később |
| Case change/revocation mapping | Fresh worker-entry és tool-boundary check; current ACTIVE hiányában nincs további supplier execution; outcome párok az alábbi javaslat szerint                                        | Runtime; admission failure szerződés indítás előtt        |
| Limit mapping                  | FAILED + megfelelő limit reason; partial result külön jelöléssel vagy üres advice; limit/cancel race precedence döntendő                                                              | Runtime                                                   |
| Fail-closed audit/durability   | Run/evidence írási hiba esetén nincs sikeres result delivery; kötelező enabled/unbuffered vagy bizonyított durable evidence path; atomic audit és failure recovery döntendő           | Indítás audit contract; runtime tool correlation          |
| Finalization check             | Fresh actor/permission és resolver observation provider híváson kívül; changed state esetén historical tool evidence és explicit stop outcome                                         | Runtime                                                   |
| Retention expiry/dependency    | 365 nap a terminal timestamptől; active run nem törölhető; run-owned evidence törlése kontrollált sorrendben, business history nem cascade; hold/backup/reference kezelése eldöntendő | FK/dependency contract indítás előtt; cleanup később      |
| Actor deletion/FK              | Nullable initiator FK + immutable minimális actor reference, deletion után deny access/execution; restrict deletion alternatíva; személyes adatok és audit megtartása döntendő        | Indítás                                                   |

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

PROPOSED candidate persistence: UUID, immutable bound case/initiator,
status/stop_reason, creation/start/terminal timestamps, versioned backend limits
snapshot, linked start evaluation, versioned result/reference, idempotency és
cancellation/claim metadata. Ez nem jóváhagyott oszloplista. A user permissiont
nem snapshotoljuk tartós authorityként. Provider hívás és existing tool execution
nem lehet nyitott caller-owned transactionben; a tool ezt jelenleg elutasítja.
Queue dispatch explicit after-commit legyen; existing `after_commit: false`
nem ad garanciát. Worker timeout és reservation interval összehangolandó.

## 8. Implementációs szeletek

**Egyetlen ajánlott következő implementációs szelet: MerlinRun persistence és
trusted investigation start**, az indításra jelölt PROPOSED döntések feloldásával.
E dokumentum létrehozása nem ad implementációs engedélyt.

| Szelet                                 | Scope és függőségek                                                                                                                                                                    | Szükséges tesztek és DoD                                                                                                                                                                                                                                 | Kizárások/döntések                                                                                                                                    |
| -------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| Következő: persistence/start           | MerlinRun model/repository/migration, authorized start service, fresh resolver composition, linked history, bounded atomic admission és idempotency; existing case/resolver/Gate reuse | Allowed/denied start, other-user read/cancel denial és super-admin ownership; exact binding; stale/invalid/undetermined cases; run/history rollback; duplicate és concurrent admission; retention/FK impact; project DoD és migration SQLite/MySQL gates | Idempotency/admission/FK/durability döntések előbb; nincs live provider, model loop, generic registry, IMF UI, Nagy Könyv vagy autonomous procurement |
| Később: supervised backend execution   | Investigation job, claim/recovery, explicit single-tool dispatch, call correlation; fake model adapter                                                                                 | Revocation/case changes; malformed/unknown requests; 8/4/180 limits; cooperative cancellation/late response; duplicate worker/call és audit failure; bizonyított terminal immutability                                                                   | Runtime mappings/claim/recovery előbb; nincs paid inference vagy business write                                                                       |
| Később: provider és validated advisory | Replaceable adapter a meglévő AI isolation határ egyeztetésével; enforceable cost policy, result validator/finalization                                                                | Schema/correlation rejection; usage/deadline/budget; fabricated evidence; exact decimal/unknown semantics; protected historical read; nincs external call DB transactionben                                                                              | Provider/accounting OPEN feloldása; nincs ranking/selection/procurement                                                                               |
| Később: IMF és Nagy Könyv              | Protected result megjelenítés, majd külön learning integration                                                                                                                         | UI/route authorization, i18n, error/pending/cancel states; history preservation; külön project DoD                                                                                                                                                       | Külön scope és engedély; V1 foundationből kizárva                                                                                                     |

A persistence/start nem tartalmaz model tool-calling loopot, generic tool registryt,
live LLM/provider integrationt, IMF UI-t, Nagy Könyvet vagy autonomous actiont.
Az admission/cancel/read szerződés szolgáltatás-szinten is backend-enforced;
frontend visibility nem authorization.

## 9. Korábbi döntésekhez való viszony és verification

A [Problem Case foundation](problem-case-foundation-slice-1.md) és
[resolver slice](material-shortage-resolver-slice-2.md) immutable detection,
append-only history és read-only evaluation határa változatlan. Az induló
evaluation megőrzésének korábbi követelménye megmarad; csak technikai atomicity/link
módja PROPOSED. A korábban nyitott authorization mapping V1 részét ADR 0017 oldja fel.

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
