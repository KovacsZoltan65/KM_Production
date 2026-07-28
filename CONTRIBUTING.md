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
`docs/project-management/commit-conventions.md` szabályait. A commitüzenet
angol, a magyarázó projekt-dokumentáció magyar nyelvű.

## Tesztek

Csak a `package.json` és `composer.json` létező scriptjeit használd. Futtasd a
változás kockázatához illő fókuszált teszteket és a releváns
`.kiro/checklists/before-commit.md` ellenőrzéseket. A nem futtatott kaput az
átadásban vagy pull requestben indokold.

## Pull request

- Tartsd a változást fókuszáltan és review-zhatóan.
- Add meg az üzleti vagy technikai célt, a kockázatot és a tesztbizonyítékot.
- Hivatkozd a backlog ID-t, ha a munkához tartozik backlogelem.
- Kövesd a `.kiro/checklists/before-merge.md` ellenőrzőlistát.
- Squash merge esetén a végső commitcím is kövesse a commitkonvenciót.

## Definition of Done

A központi backlog lezárási szabálya a
`docs/project-management/backlog-conventions.md` dokumentumban található.
