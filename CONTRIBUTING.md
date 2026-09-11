# Hozzájárulási útmutató

## Fejlesztési környezet

A projekt technológiáit és ellenőrzött dokumentációs belépési pontjait a
[README.md](README.md) tartalmazza. A parancsokat az aktuális
[composer.json](composer.json), [package.json](package.json) és a kapcsolódó
eljárás alapján válaszd ki.

## A munka megkezdése

Tisztázd a kért eredményt, az elfogadási feltételeket és a módosítás hatókörét.
A [projektdokumentáció indexéből](.kiro/index.md) olvasd a kapcsolódó tartós
szabályokat, domainismereteket és architektúradöntéseket. Csak a feladathoz
szükséges rétegeket válaszd ki, majd vizsgáld meg az érintett megvalósítást.

Módosítás előtt nézd át a Git-ágat, a munkafa eltéréseit, a staginget és a nem
követett fájlokat. Őrizd meg a meglévő munkát; ne töröld, írd felül vagy formázd
az idegen változásokat a tiszta állapot kedvéért. Átfedésnél jelezd az érintett
fájlt, és tisztázd a bizonytalan módosítási határt.

A feladat jellege szerint válassz eljárást:

- [Új funkció fejlesztése](.kiro/workflows/feature-development.md): új, engedélyezett működés.
- [Hibajavítás](.kiro/workflows/bug-fix.md): bizonyított ok javítása a kért körben.
- [Refaktorálás](.kiro/workflows/refactoring.md): belső szerkezet javítása változatlan külső működéssel.

Ha a munka nem illik egyértelműen valamelyik eljárásba, viselkedésmódosítás
előtt rögzítsd a hatókört. A megvalósítás maradjon ezen belül; az érintett
teszteket és dokumentációt szükség szerint frissítsd.

A [backlog](docs/project-management/backlog.md) a munka és állapotának
nyilvántartása, a [következő lépések](docs/project-management/next-actions.md)
a közeli teendőket mutatják. A listából való kimaradás nem jelent lezárást.

## Emberi közreműködők és AI-agentek

Ez az útmutató mindkét körnek szól. A Git- és felülvizsgálati eljárások a
szükséges jogosultság és felhatalmazás birtokában alkalmazhatók; egy elkészült
módosítás vagy kitöltött lista önmagában nem műveleti engedély.

AI-agentnél az [AGENTS.md](AGENTS.md) és a jelenlegi felhasználói kérés
határozza meg a felhatalmazást. Commit, push, PR létrehozása vagy frissítése,
merge, kiadás és telepítés csak explicit felhasználói engedéllyel végezhető.
Az itt hivatkozott eljárások ezt nem írják felül. Üzleti logikát az agent csak
kifejezetten kért körben módosíthat; egy feltételezett hiba vagy új funkció
nem engedély új üzleti szabály kitalálására.

## Branch használat

- Az elsődleges integrációs ág a `main`.
- Ne írj át megosztott Git-történetet, és ne használj force push-t jóváhagyás
  nélkül.
- Egy branch egy összefüggő feladatot vagy javítást képviseljen.

## Commitüzenetek

Minden új commit kövesse a
[commitüzenet-konvenció](docs/project-management/commit-conventions.md)
szabályait. A commitüzenet angol. A dokumentáció nyelvét és érthetőségét a
[kódolási stílus útmutatója](.kiro/steering/coding-style.md#documentation-language-and-readability)
szabályozza; egy meglévő angol dokumentumot nem kell emiatt teljesen lefordítani.

Commit előkészítésekor kövesd a
[commit előtti ellenőrzőlistát](.kiro/checklists/before-commit.md).
A commit köztes munkapont is lehet; önmagában nem feladatlezárás.

## Ellenőrzés és dokumentáció

A [rétegezett ellenőrzések útmutatója](docs/development/quality-gates.md)
alapján válassz a változás kockázatához illő ellenőrzést, és végezd el a
választott parancson kívüli, külön alkalmazandó vizsgálatokat is.
A `composer qa:full` nem minden projektellenőrzés összessége. Commit vagy
merge önmagában nem teszi kötelezővé a Full futást.

A [tesztelési szabályok](.kiro/steering/testing.md) a teszttervezést, a
[backend](docs/backend-quality-gate.md), [frontend](docs/frontend-testing.md),
[E2E](docs/e2e-testing.md) és [statikus elemzési](docs/static-analysis.md)
útmutatók a futtatást segítik. SQLite-siker nem helyettesíti az alkalmazandó
MySQL-vizsgálatot, és frontendteszt nem igazol teljes E2E-folyamatot.

Az eredményeket a [Definition of Done](docs/project-management/definition-of-done.md)
szerinti `PASSED`, `FAILED`, `BLOCKED`, `NOT RUN` értékekkel és tényleges
bizonyítékkal jelentsd. A leírt kihagyási ok vagy kivétel nem `PASSED`;
a nyitott kötelező követelmény rendezését a DoD szerint kell kezelni.

Dokumentációhoz használd a [dokumentációs ellenőrzőlistát](.kiro/checklists/documentation.md)
és az [index elhelyezési útmutatását](.kiro/index.md#cross-reference-guidance).
Előbb egyszerűen magyarázd el, mit és miért teszünk, majd a technikai működést.
Csak dokumentációs változásnál a formázás, linkek, parancspéldák, szóhasználat
és whitespace vizsgálata szükséges; alkalmazásellenőrzést a DoD szerinti
alkalmazhatóság indokoljon.

## Pull request

- Használd a [pull request sablont](.github/pull_request_template.md).
- A PR címe kövesse a commitkonvenció tárgysor-formátumát.
- Tartsd a változást fókuszáltan, és add meg a célt, kockázatot, rollbacket,
  valamint a tényleges tesztbizonyítékot.
- Hivatkozd a backlog ID-t, ha a munkához tartozik backlogelem.
- Kövesd a [merge előtti ellenőrzőlistát](.kiro/checklists/before-merge.md) és a
  [code review útmutatót](docs/project-management/code-review-guide.md).
- Az alapértelmezett javaslat squash merge; a végső cím kövesse a
  commitkonvenciót.

A nyitott review-megállapítások rendezését és a merge feltételeit a fenti
elsődleges források szabályozzák. Review-jóváhagyás vagy merge önmagában nem
igazolja a kiadás üzemeltetési feltételeit.

## Definition of Done

A feladatlezárás elsődleges szabálya a
[projektszintű Definition of Done](docs/project-management/definition-of-done.md).
A változás csak a releváns általános és változástípus szerinti feltételek
igazolt teljesülésével `done`; rendezetlen kötelező követelmény mellett nem
állítható teljes készültség. A backlog
állapotkezelését a
[backlog-konvenciók](docs/project-management/backlog-conventions.md) rögzítik.
