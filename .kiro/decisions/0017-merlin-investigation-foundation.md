# Merlin Investigation Foundation V1

- **Állapot:** ACCEPTED a kifejezetten elfogadott V1 szerződésre; a PROPOSED és OPEN részek nem elfogadott döntések
- **Dátum:** 2026-10-08
- **Ellenőrzött baseline:** `feature/merlin-candidate`, `45544b19017d1f31564cb3e66c5bc3f74d4071b8`, tiszta worktree
- **Hatókör:** specifikáció; nincs runtime-, adatbázis-, permission- vagy alkalmazáskód-változás
- **Részletes szerződés:** [Investigation Foundation V1](../../docs/specifications/merlin/investigation-foundation-v1.md)

## Kontextus

A Problem Case persistence, a read-only current resolver és a
`get_supplier_options` backend tool már implementált. MerlinRun, vizsgálati job,
model tool-calling ciklus és vizsgálati cancellation nincs implementálva.
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

A MerlinRun és vizsgálat-owned evidence/result retentionje 365 nap, későbbi
jogi/üzemeltetési megőrzési kötöttségek figyelembevételével. A kezdőpont,
expiry törlés és dependency handling PROPOSED. Ez nem határozza meg a
Problem Case üzleti history retentionjét.

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

## PROPOSED — külön elfogadást igénylő technikai döntések

A részletes [döntési lista](../../docs/specifications/merlin/investigation-foundation-v1.md#7-proposed--technikai-döntések-és-blockerek)
tartalmazza az idempotencyt, atomic admissiont/history linket, worker claimet és
crash recoveryt, revocation/case-change/limit outcome mappinget, fail-closed auditot,
finalization-time rechecket, expiry/dependency kezelést, concurrency mechanismot és
actor deletion/FK viselkedést. Ezek nem válnak ACCEPTED döntéssé az ADR elfogadott
V1 részeitől. A provider-specifikus költségszerződés OPEN marad.

## Következmények és mérlegelt irányok

A saját eredményhez kötött hozzáférés adatminimalizálást ad, de V1-ben nem
támogat közös investigation result megtekintést. Az egy aktív run/case korlát
korlátozza a párhuzamos költséget; adatbázis-szintű enforcement még döntendő.
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
trusted investigation start, a hozzá szükséges PROPOSED döntések feloldása után.
Scope, tesztek, DoD és kizárások a [roadmapben](../../docs/specifications/merlin/investigation-foundation-v1.md#8-implementációs-szeletek).
