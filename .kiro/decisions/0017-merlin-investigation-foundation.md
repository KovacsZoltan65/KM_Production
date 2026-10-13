# Merlin Investigation Foundation V1

- **Állapot:** ACCEPTED a kifejezetten elfogadott V1 szerződésre; a PROPOSED és OPEN részek nem elfogadott döntések
- **Dátum:** 2026-10-08
- **Persistence/start szerződés véglegesítése:** 2026-10-13; `feature/merlin-candidate`, `cf589ac0817cd58b1d26b1267c6e72b8380ff627`, tiszta kiinduló worktree
- **Ellenőrzött baseline:** `feature/merlin-candidate`, `45544b19017d1f31564cb3e66c5bc3f74d4071b8`, tiszta worktree
- **Hatókör:** V1 architekturális szerződés; Slice 1A séma/model implementációs határa a részletes specifikációban
- **Részletes szerződés:** [Investigation Foundation V1](../../docs/specifications/merlin/investigation-foundation-v1.md)

## Kontextus

A Problem Case persistence, a read-only current resolver és a
`get_supplier_options` backend tool már implementált. Vizsgálati job,
model tool-calling ciklus és vizsgálati cancellation nincs implementálva.
A Slice 1A már MerlinRun sémát/modelt és külön admission audit táblát ad;
ez önmagában nem trusted start vagy végrehajtási authority. Az implementált
DB-határt és bizonyítékait a részletes specifikáció 7.7. része rögzíti.
A korábbi [Problem Case specifikáció](../../docs/specifications/merlin/material-shortage-problem-case.md)
nyitott authorization mappingjét ez a döntés V1-re konkretizálja.

> Merlin may decide how to investigate, but KM_Production controls what Merlin is permitted to execute.

A Laravel backend marad az authorization, üzleti állapot, tool dispatch,
limitek, persistence és végrehajtás felelőse. Merlin outputja tanács;
nem üzleti tény és nem végrehajtási engedély.

## ACCEPTED — jogosultsági szerződés

| Művelet                                        | V1 szabály                                                          |
| ---------------------------------------------- | ------------------------------------------------------------------- |
| Case láthatósága                               | `inventory.view`                                                    |
| Vizsgálat indítása                             | `inventory.view` AND `item-suppliers.view`                          |
| Saját eredmény olvasása, történeti eredmény is | Mindkét permission aktuálisan teljesül AND az olvasó a kezdeményező |
| Más felhasználó eredménye                      | Tiltott                                                             |
| Cancellation                                   | Kezdeményező AND aktuálisan érvényes vizsgálati hozzáférés          |
| Más felhasználó runjának cancellationje        | Tiltott                                                             |

Nincs új permission. A case láthatósága nem jogosít supplier evidence vagy
persistált advice megismerésére. A super-admin a meglévő Laravel Gate szerint
teljesítheti a permission-részt; az explicit kezdeményezőazonosságot nem
kerülheti meg. Tool allowlist, bound case és aktuális üzleti gate sem kerülhető meg.

A toolset assembly és minden tool execution egymástól független authorizationt
igényel. A [supplier tool elfogadott szerződése](../../docs/specifications/merlin/supplier-options-tool-slice-1.md)
változatlan: backend allowlist, fresh actor, bound case és fresh current state
kötelező. A runhoz kötött actor/case nem származhat model outputból.

## ACCEPTED — lifecycle és execution profil

Állapotok: `PENDING`, `RUNNING`, `COMPLETED`, `FAILED`, `CANCELLED`.
V1-ben nincs resumable `WAITING_FOR_HUMAN`. A `HUMAN_INPUT_REQUIRED` terminális
stop reason; pontos status-párja PROPOSED. A `COMPLETED` vizsgálati lezárást
jelent, nem a Problem Case megoldását.

A megengedett transitionök és a terminális immutability részletei a
[lifecycle szerződésben](../../docs/specifications/merlin/investigation-foundation-v1.md#3-accepted--lifecycle) szerepelnek.

| Kontroll          | Elfogadott érték/szabály                                                                  |
| ----------------- | ----------------------------------------------------------------------------------------- |
| Model turn        | Maximum 8; rejected/malformed turn is fogyasztja                                          |
| Tool-call attempt | Maximum 4; denied/malformed/failed kérés is fogyasztja                                    |
| Execution idő     | Maximum 180 másodperc az actual run starttól                                              |
| Queue wait        | A PENDING várakozás nem fogyasztja a 180 másodpercet                                      |
| Concurrency       | Case-enként maximum egy aktív run; PENDING és RUNNING egyaránt aktív                      |
| Retry             | Nincs korlátlan retry vagy automatikus fizetős replay                                     |
| Cancellation      | Kooperatív; late response nem dispatcholhat toolt és nem írhat felül terminális eredményt |

A backend érvényesíti a limiteket; model instrukció nem enforcement.

## ACCEPTED és OPEN — költség

ACCEPTED: konfigurálható per-run cost ceiling és token/request/output-size
védelem kötelező az élő fizetős inference engedélyezése előtt. Konfigurált,
enforceable budget policy nélkül nincs live paid run. Usage accounting és az
accounting hiányára kontrollált viselkedés szükséges.

OPEN: provider/model, monetary ceiling értéke, tokenértékek, accounting és
reservation szemantikája. Ez az ADR nem választ providert és nem állít be számértéket.

## ACCEPTED — evidence és advisory eredmény

A MerlinRun és vizsgálat-owned evidence/result retentionje a terminális
lezárástól számított 365 nap, jogszerű megőrzési kötöttségek figyelembevételével.
Minden terminális state-hez lezárási timestamp tartozik. Aktív run pusztán
kora miatt nem törölhető. A run törlése az idempotenciaazonosságot is törli;
V1-ben nincs állandó tombstone. A cleanup nem törölheti a Problem Case-t vagy
üzleti Evaluation Historyt. A cleanup megvalósítása és az operatív legal-hold
kezelés OPEN; ez nem definiálja az üzleti history retentionjét.

Minimális, timestampelt, visszakereshető evidence és versioned advisory result
szükséges. Nincs alapértelmezett full prompt/raw model response tárolás,
duplikált teljes supplier-commercial payload, raw exception vagy SQL trace.
Evaluation History külön üzleti evidence; Nagy Könyv nincs V1-ben.

Az [advisory szerződés](../../docs/specifications/merlin/investigation-foundation-v1.md#6-accepted--versioned-advisory-result)
elkülöníti a backend-verified állapotot, evidence-t, model interpretationt,
information gapet, next stepet, emberi jóváhagyást igénylő actiont és terminal
outcome-ot. Evidence reference validáció, exact decimal és
known/unknown/not-applicable szemantika kötelező. Nincs automatikus ranking,
supplier selection, PR/PO creation vagy más business write.

## ACCEPTED — persistence és trusted start

A cél egy auditálható indulási megfigyelés és egyetlen aktív vizsgálat per case.
A [részletes persistence/start szerződés](../../docs/specifications/merlin/investigation-foundation-v1.md#7-persistencestart-szerződés-és-nyitott-technikai-részletek)
rögzíti a mezőinvariánsokat, sorrendet, hibakezelést és tesztkövetelményeket.

- MerlinRun UUID primary key, kötelező Problem Case UUID FK és ugyanahhoz a
  case-hez tartozó starting Evaluation History kapcsolat szükséges. Status és
  stop reason külön mező; timestamps és versioned, bounded config/result kötelező.
- MySQL alatt explicit InnoDB szükséges. Az egy aktív run invariánst a statusból
  generált, tárolt `active_marker` (`pending`/`running` → 1, egyébként NULL) és
  a `UNIQUE (problem_case_id, active_marker)` biztosítja. A status megengedett
  értékeit adatbázis-korlát védi; MySQL 8.4 és SQLite kompatibilitás követelmény.
- Idempotenciaazonosság: immutable eredeti actor ID + kanonikus case UUID +
  kliens által generált idempotency UUID, adatbázis-unique constrainttel.
  Kanonikus request identity és fingerprint megőrzendő. Fresh authorization
  megelőzi a replayt; azonos kérés ugyanazt a runt adja, eltérő kérés konfliktus.
  Replay nem ír új historyt, nem futtat toolt és nem indít újra lezárt runt.
  Más actor aktív runjának ütközése nem fedhet fel tulajdonost vagy evidence-t.
- MySQL admission saját read-write REPEATABLE READ tranzakció. Trusted actor és
  fresh permission ellenőrzése után a caller-owned transaction elutasítandó;
  case-zár, idempotencia, aktív-run ellenőrzés, fresh authoritative resolver és
  OPEN + ACTIVE + VALID source gate következik. Starting history, PENDING run
  és kötelező tartós audit ugyanabban a tranzakcióban commitol; audithiba rollback.
- A garancia a koherens indulási megfigyelés és kapcsolt rekordok közös commitja.
  Nem fagyasztja be a készletet, igényt, foglalást és supplyt commitig vagy a
  workerig. Worker entry új authorizationt és eligibilityt igényel.
  Admissionben nincs LLM-, supplier-tool- vagy providerhívás. SQLite alatt
  biztonságos írási admission mód és külön tesztstratégia szükséges;
  izolációja nem azonos a MySQL-ével.
- Nullable live user FK és immutable minimális historical actor ID szükséges.
  A live FK törléskor NULL-lá válhat; történeti identity nem authority.
  Hiányzó live actor mellett hozzáférés és execution tiltott; név/email
  snapshot helyett csak audit/idempotencia célú minimum őrizhető meg.
- Stale PENDING/RUNNING run nem oldható fel pusztán eltelt idő alapján.
  Érvénytelen worker-lease/ownership bizonyítéka vagy külön definiált pending
  deadline túllépése kell. Recovery kontrollált FAILED terminalization,
  specifikus stop reasonnel, concurrency-safe módon és audittal. Late response
  nem írhat felül terminal resultot; nincs automatikus paid replay.

A kötelező audit nem függhet opcionális vagy pufferelt alkalmazásnaplótól.
Ha a meglévő infrastruktúra nem igazolja az azonos tranzakcióban történő tartós
írást, minimális dedikált persistence szükséges. Slice 1A külön audit táblát
implementál; a kötelező auditírás és admission atomicity a következő szelet feladata.
E döntések tervezési szerződések. Slice 1A célzott SQLite/MySQL migration,
constraint és MySQL index-ütközéstesztjei a sémát igazolják; a teljes trusted
admission tranzakció és annak konkurens viselkedése még nincs implementálva.

## PROPOSED és OPEN — további technikai döntések

PROPOSED marad a runtime revocation/case-change/limit outcome mapping,
tool-audit correlation, finalization-time recheck és counter reservation.
A belső admission outcome-nevek PROPOSED; API mapping nincs elfogadva.
Slice 1A a migráció/constraint/FK megoldást, durable audit sémát és user FK
`SET NULL` viselkedést konkretizálja a specifikáció szerint.
OPEN a kanonizálás és fingerprint verziózás, audit writer, SQLite transaction
ownership/IMMEDIATE integráció, constraint-hiba osztályozás, lawful actor deletion,
terminal transition enforcement és retention cleanup/legal hold.
Worker claim/lease, queue deadline, recovery implementation és időértékek a
későbbi worker-szelethez tartoznak. Provider és provider-specifikus
költségszemantika továbbra is OPEN. A specifikáció külön jelöli e részleteket;
a helyreállítási koncepciómentés nem írhatja felül az ADR-t vagy a specifikációt.

## Következmények és mérlegelt irányok

A saját eredményhez kötött hozzáférés adatminimalizálást ad, de V1-ben nem
támogat közös investigation result megtekintést. Az egy aktív run/case korlát
korlátozza a párhuzamos költséget; adatbázis-szintű enforcementje elfogadott,
de végrehajtással még nem igazolt.
A kooperatív cancellation nem ígér azonnali provider-megszakítást vagy nulla
további díjat. A korlátos evidence nem teljes conversation replay.

Nem választott irányok: broader case visibilityből supplier disclosure;
AI-supplied actor/allowlist; resumable human wait; direkt AI database access;
providerhez kötött business logic; autonomous procurement; full prompt alapú audit.
Generic tool registryre az egyetlen támogatott tool nem ad konkrét szükségletet.

## Illeszkedés és következő szelet

A [Merlin baseline](../../docs/specifications/merlin/README.md) és
[ADR 0005](0005-document-ai.md) Laravel-owned business/authorization határa megmarad.
A Python/provider transport nincs újra eldöntve. A baseline Merlin-specific
confidence-tilalma megmarad; az OCR confidence guidance nem kerül át az advice-ba.
Az [ADR 0016](0016-material-requirement-demand-eligibility.md) source validity
és full competing-scope netting szabályai nem változnak.

**Egyetlen ajánlott következő implementációs szelet:** MerlinRun persistence és
trusted investigation start, az OPEN implementációs részletek feloldásával és
SQLite/valós MySQL bizonyítékokkal; worker orchestration nélkül.
Scope, tesztek, DoD és kizárások a [roadmapben](../../docs/specifications/merlin/investigation-foundation-v1.md#8-implementációs-szeletek).
