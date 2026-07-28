# Hozzájárulási útmutató

## Fejlesztési környezet

A projekt technológiáit és ellenőrzött dokumentációs belépési pontjait a
`README.md` tartalmazza. Nem ellenőrzött telepítési parancs helyett kövesd a
repository aktuális dokumentációját és scriptjeit.

## Branch használat

- Az elsődleges integrációs ág a `main`.
- Ne írj át megosztott Git-történetet, és ne használj force push-t jóváhagyás
  nélkül.
- Egy branch egy összefüggő feladatot vagy javítást képviseljen.

## Commitüzenetek

Minden új commit kövesse a
[commitüzenet-konvenció](docs/project-management/commit-conventions.md)
szabályait. A commitüzenet angol, a magyarázó projekt-dokumentáció magyar
nyelvű.

## Tesztek

Csak a `package.json` és `composer.json` létező scriptjeit használd. Futtasd a
változás kockázatához illő fókuszált teszteket és a releváns
`.kiro/checklists/before-commit.md` ellenőrzéseket. A nem futtatott kaput az
átadásban vagy pull requestben indokold.

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
- Nyitott `BLOCKER` vagy `REQUIRED` megjegyzéssel ne történjen merge.

## Definition of Done

A központi backlog lezárási szabálya a
[backlog-konvenciókban](docs/project-management/backlog-conventions.md)
található.
