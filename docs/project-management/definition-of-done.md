# KM_Production Definition of Done

## Cél és hatókör

Ez a dokumentum a KM_Production feladatlezárásának elsődleges szabályzata.
Minden emberi és AI-agent által végzett változtatásra alkalmazandó. A
változtatás csak akkor `done`, ha a rá vonatkozó általános és
változástípus-specifikus feltételek bizonyítottan teljesülnek.

A szabályzat szigorú, de arányos: nem kell minden feladatra minden tesztet
futtatni. A szerző a módosított felület, az üzleti kockázat és a lehetséges
hiba hatása alapján választ ellenőrzést, az irreleváns pontokat pedig röviden
`N/A`-ként indokolja.

## Fogalmak és állapotok

| Fogalom                   | Jelentés                                                                                                                                                 |
| ------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Acceptance Criteria (AC)  | A backlogelem megfigyelhető, feladatspecifikus üzleti vagy technikai eredménye. Azt mondja meg, **mit** kell szállítani.                                 |
| Definition of Ready (DoR) | A munka megkezdésének feltétele: érthető scope, AC, függőségek, kockázat és ellenőrzési igény. Nem lezárási kapu.                                        |
| Definition of Done (DoD)  | A feladattípustól független minimum és a releváns szakági feltételek együttese. Azt mondja meg, **milyen minőségben és bizonyítékkal** kész az eredmény. |
| Review-ready              | Az implementáció és az önellenőrzés elkészült, a diff és a bizonyíték átadható reviewernek; ismert eltérés nyíltan dokumentált.                          |
| Merge-ready               | A review lezárult, nincs nyitott `BLOCKER` vagy `REQUIRED`, a releváns ellenőrzések sikeresek, a PR és a backlog naprakész.                              |
| Release-ready             | A merge-kész változás egy konkrét verziójelölt részeként az üzemeltetési, migrációs, security, rollback és release-evidence kapukat is teljesíti.        |

A „code complete” csak azt jelenti, hogy az implementáció elkészült. Nem
jelenti azt, hogy a feladat tesztelt, dokumentált, review-kész, merge-kész,
release-kész vagy `done`.

A backlogban kizárólag a
[backlog-konvenciók](backlog-conventions.md) állapotai használhatók:
`planned`, `ready`, `in-progress`, `blocked`, `review`, `done`, `cancelled`.
A „részben kész” nem külön állapot: az ilyen munka `in-progress`, `review` vagy
`blocked`, a hiányzó AC-val vagy DoD-ponttal és a következő lépéssel együtt.

## Minden változtatás kötelező minimuma

### Scope és elfogadási feltételek

- A megvalósítás a jóváhagyott scope-ra korlátozódik; a scope-on kívüli
  változás külön feladat vagy dokumentált döntés.
- Minden AC teljesül, és megfigyelhető eredménnyel igazolt.
- Nincs elhallgatott ismert hiba, regresszió, blocker vagy félbehagyott
  követelmény.

### Üzleti és architekturális helyesség

- A releváns domain-, ADR-, architecture- és steering-szabályok teljesülnek.
- A gyártási traceability, serial number, operation sequence verzió,
  auditnapló, permission és stock movement invariánsok nem sérülnek.
- Alkalmazáskódnál megmarad a `Controller -> Service -> Repository -> Model`
  rétegzés; üzleti logika nem kerül controllerbe.

### Kód- és tartalomminőség

- A diff fókuszált, érthető, és nem tartalmaz kapcsolódás nélküli módosítást,
  debugkódot, titkot, lokális artifactot vagy indokolatlan dead code-ot.
- A releváns formatter, statikus elemzés és whitespace-ellenőrzés sikeres.
- A hiba elrejtése széles ignore-ral, baseline-nal vagy gyengített teszttel nem
  elfogadható.

### Tesztelés és regresszió

- A legszűkebb, a változást ténylegesen bizonyító automatizált teszt lefut.
- A megosztott vagy nagy kockázatú felülethez arányosan szélesebb regressziós
  ellenőrzés tartozik.
- Sikeres és fontos hibás/jogosulatlan út is ellenőrzött, ha van ilyen.
- Manuális ellenőrzés csak akkor helyettesít automatizált tesztet, ha a teszt
  nem ésszerűen automatizálható; a lépések és eredmények ekkor is rögzítettek.

### Biztonság, adat és jogosultság

- A bemenet, authorization, érzékeny adat, naplózás, fájlkezelés és dependency
  hatása a módosítás mértékében felülvizsgált.
- Jogosultságot érintő változásnál a tiltott közvetlen hozzáférés bizonyítottan
  elutasított.
- Adatváltozásnál az integritás, tranzakció, idempotencia és visszaállítás
  hatása ismert.

### Dokumentáció és lokalizáció

- A felhasználói, üzemeltetési, architekturális és governance dokumentáció a
  tényleges viselkedést írja le.
- A módosított relatív hivatkozások célja létezik.
- UI-szöveg közös Laravel JSON translation key-t használ; a magyar és angol
  fordítás együtt frissül.
- A backlog, a végrehajtási terv és a release note csak tényszerűen frissül.

### Git és review

- A staging célzott, a teljes diff átnézett, a commit és a PR címe követi a
  [commitüzenet-konvenciót](commit-conventions.md).
- A PR a [PR-sablon](../../.github/pull_request_template.md) és a
  [code review útmutató](code-review-guide.md) szerint tartalmaz scope-ot,
  kockázatot, rollbacket és bizonyítékot.
- Csak ténylegesen lefutott ellenőrzés jelölhető sikeresnek.

## Ellenőrzési mátrix

Az alábbi parancsok a repository jelenlegi scriptjei vagy ténylegesen használt
eszközei. A mátrix nem teszi őket minden változtatásra kötelezővé.

| Terület                           | Releváns ellenőrzés                                                                                                                   | Megjegyzés                                                                   |
| --------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------- |
| Dokumentáció                      | `npx prettier --check <files>`; `git diff --check`                                                                                    | A módosított linkek külön ellenőrzendők; nincs dedikált link-check script.   |
| Backend formázás/statikus elemzés | `vendor/bin/pint --test`; `composer analyse`; `composer validate --strict`                                                            | A `Backend Static Analysis` workflow-ban is futnak.                          |
| Backend teszt                     | `composer test:backend:sqlite`; `composer test:backend:mysql`                                                                         | MySQL csak dedikált, guardolt tesztadatbázison.                              |
| Cache regresszió                  | `composer test:cache`                                                                                                                 | Cache-elt adatforrást módosító write műveletnél.                             |
| Migráció                          | `composer test:backend:migrations:sqlite`; `composer test:backend:migrations:mysql`                                                   | Előre, rollback és seeder hatás.                                             |
| Frontend                          | `npm run test:frontend`; `npm run i18n:check`; `npm run build`                                                                        | A változás kockázatához igazítva.                                            |
| E2E                               | `npm run test:e2e`; `npm run test:e2e:a11y`; `npm run test:e2e:keyboard`; `npm run test:e2e:cross-browser`; `npm run test:e2e:mobile` | Izolált E2E-környezetet és telepített böngészőket igényel.                   |
| Dependency security               | `npm audit`; `npm audit --omit=dev`; `composer audit`                                                                                 | A Composer audit még nem CI-kapu (`CI-006`); az npm policy auditja `CI-007`. |

A GitHub Actions jobok pull requestre futnak, de required státuszuk nem
repository-fájlból igazolható. A tényleges jobneveket és a javasolt
branch-protection mátrixot a [code review útmutató](code-review-guide.md)
tartalmazza. A Vitest stabilitás (`CI-001`, `CI-002`), a MySQL- és E2E-kapu
aktuális bizonyítása (`CI-003`, `CI-004`), valamint a required-check audit
(`CI-005`) nyitott backlogmunka; ezek hiánya nem írható le sikeres
ellenőrzésként.

## Változástípus-specifikus feltételek

Több típust érintő változásnál minden releváns rész alkalmazandó.

### 1. Dokumentáció

- A tartalom a repository aktuális működésével és terminológiájával egyezik.
- A módosított relatív linkek, címsorok, kódpéldák és parancsok ellenőrzöttek.
- Formázás és `git diff --check` sikeres; alkalmazáskódteszt csak indokolt
  beágyazott példa vagy generált viselkedés esetén kell.

### 2. Backend

- A réteghatárok, FormRequest, policy, tranzakció és activity log hatása
  felülvizsgált.
- A változást célzott Pest/feature teszt bizonyítja; kritikus közös felületnél
  szélesebb backend suite fut.
- Pint és Larastan sikeres a releváns változáshoz.

### 3. Frontend

- Az Inertia prop-, Vue prop/event- és route-szerződés konzisztens.
- Loading, empty, success, validation és error állapotok a releváns flow-ban
  kezeltek.
- Célzott Vitest, i18n-check és indokolt esetben production build futott;
  felhasználói flow-nál reszponzív és billentyűzetes ellenőrzés történt.

### 4. Adatbázis-migráció

- Az előre irányú migráció, a meglévő adatok, indexek, constraint-ek,
  zárolás és deployment-sorrend hatása dokumentált.
- A rollback valódi adatvesztési és kompatibilitási hatása elemzett; egy
  `down()` metódus létezése önmagában nem bizonyíték.
- A releváns SQLite- és MySQL-migrációs kapu guardolt környezetben sikeres,
  vagy a feladat nem lehet `done`.

### 5. Biztonság és authorization

- Engedélyezett, tiltott, eltérő szerepkörű és közvetlen route-hozzáférési eset
  tesztelt.
- A policy mellett backend authorization is érvényesül; menüelrejtés nem
  biztonsági kontroll.
- Érzékeny adat, log, cache, upload/download és audit trail hatása
  felülvizsgált.

### 6. Refaktor

- A scope nem tartalmaz elrejtett feature-t vagy breaking change-et.
- A refaktor előtti viselkedést teszt vagy összehasonlítható bizonyíték védi.
- A publikus szerződés és a domain-invariánsok változatlansága igazolt.

### 7. Teljesítmény

- A kiindulási és a módosított állapot azonos módszerrel mért.
- Az állítás query counttal, futási idővel, memória- vagy más releváns
  mérőszámmal alátámasztott; puszta benyomás nem bizonyíték.
- Az index-, cache-, nagy adathalmaz- és jogosultsági izolációs hatás elemzett.

### 8. Lokalizáció

- A felhasználói szöveg nem hardcoded, és a közös Laravel JSON kulcsot használja.
- A magyar és angol kulcskészlet szinkronban van, az `npm run i18n:check`
  sikeres.
- Dinamikus helyettesítés, pluralizáció és UI-helyigény a releváns nézetben
  ellenőrzött.

### 9. CI- vagy tesztinfrastruktúra

- A módosított script, trigger, jobnév, környezet, timeout és artifact hatása
  dokumentált.
- Van sikeres és szándékosan hibás próba, amely bizonyítja, hogy a kapu valóban
  blokkol.
- Required check csak GitHub-beállítással igazoltan nevezhető requirednak; a
  javaslat és a tényleges beállítás külön fogalom.

### 10. Dependency-frissítés

- A lockfile célzottan változik, a csomag oka, verzióhatása és licenc/security
  kockázata ismert.
- A releváns audit, teszt és build lefut; breaking vagy runtime-követelmény
  dokumentált.
- Audit finding nem hallgatható el; ownerrel, döntéssel és határidővel kezelt
  kivétel nélkül a releváns magas kockázat blokkol.

## Review-ready

A változtatás akkor review-ready, ha:

- az implementáció és a szerző önellenőrzése elkészült;
- az AC-k állapota, a teljes diff, a kockázat és a rollback leírt;
- a releváns ellenőrzések futottak, eredményük és környezetük rögzített;
- minden kihagyott ellenőrzéshez indok és hatás tartozik;
- nincs ismert, elhallgatott hiba.

Környezeti okból nem futtatható releváns kapu mellett a backlogelem legfeljebb
`review` állapotú. A review megkezdhető az eltérés vizsgálatára, de a feladat
nem `done`.

## Merge-ready

A változtatás akkor merge-ready, ha a review-ready feltételeken túl:

- a teljes PR diff review-zott;
- nincs nyitott `BLOCKER` vagy `REQUIRED`;
- az új érdemi módosítások után a releváns review és ellenőrzés megismétlődött;
- minden alkalmazandó DoD-pont és AC teljesült;
- a PR-leírás, migráció, rollback, dokumentáció és backlog naprakész.

A workflow jelenléte nem bizonyítja a GitHub required státuszt, és a merge-ready
állapot nem azonos a release-ready állapottal.

## Release-ready

Egy verziójelölt akkor release-ready, ha minden benne lévő változás merge-ready,
és ezen felül:

- a [release-checklist](../../.kiro/checklists/release.md) releváns pontjai
  teljesültek;
- a migráció, permission, seeder, konfiguráció, queue/scheduler és storage hatás
  ismert;
- a deployment és a rollback lépései, felelőse és szükséges evidence-e
  rendelkezésre áll;
- nincs kezeletlen release-blocker vagy indokolatlanul kihagyott kapu.

A teljes, egységes release evidence-csomag kialakítása `CI-009`; addig a
meglévő workflow- és checklist-eredményeket kell tételesen összegyűjteni.

## Bizonyítékalapú lezárás

Elfogadható bizonyíték:

```text
Parancs: npm run i18n:check
Környezet: Windows, Node 24
Eredmény: exit code 0; a magyar és angol kulcskészlet egyezik.
```

```text
Manuális ellenőrzés: jogosultság nélküli felhasználó közvetlenül megnyitja
az /admin/... route-ot.
Eredmény: 403; az esemény nem módosított adatot.
```

Nem elfogadható:

```text
Minden működik.
A tesztek rendben vannak.
Biztonságos és production ready.
```

A bizonyíték legalább a parancsot vagy reprodukálható lépést, a releváns
környezetet és a mérhető eredményt tartalmazza. A CI-link, artifact,
képernyőkép vagy mérési riport kiegészítő bizonyíték lehet, de nem helyettesíti
az eredmény értelmezését.

## Kivételek, blokkolt és részleges munka

- Kötelező DoD-pont csak dokumentált, feladatspecifikus `N/A` indokkal lehet
  irreleváns; kényelmi ok nem kivétel.
- Sikertelen vagy nem futtatott releváns kapuhoz ok, hatás, owner és következő
  lépés tartozik. Ilyen feladat nem `done`.
- Külső vagy környezeti akadálynál a tétel `blocked`, ha érdemi továbblépés nem
  lehetséges; egyébként `in-progress` vagy `review`.
- A blocker leírása megnevezi a konkrét akadályt és a feloldás feltételét.
- Hotfixnél az arányosság változhat, de a kockázat, célzott ellenőrzés,
  rollback és utánkövető feladat nem hagyható el.
- Kivétel nem teheti elfogadhatóvá az adatvesztést, jogosultságmegkerülést,
  titokkiszivárgást vagy hamis tesztbizonyítékot.

## AI-agent szabályok

Az AI-agent:

- feladatkezdéskor azonosítja az AC-ket, a releváns DoD-típusokat és a várható
  bizonyítékot;
- nem bővíti önkényesen a scope-ot és nem módosít üzleti logikát explicit kérés
  nélkül;
- csak ténylegesen futtatott parancsot, ellenőrzött fájlt és megfigyelt
  eredményt jelent;
- nem jelöl `done` állapotot ismert blocker, sikertelen releváns kapu vagy
  hiányzó bizonyíték mellett;
- nem állít review-t, approvalt, required checket vagy production readiness-t
  külső igazolás nélkül;
- átadás előtt ellenőrzi a teljes diffet, a staginget, a titkokat, a backlogot
  és a módosított hivatkozásokat;
- commitot, push-t, PR-t, merge-et vagy repository-beállítást csak az adott
  műveletre vonatkozó felhatalmazással végez.

## Újrahasználható DoD-checklist

- [ ] A scope és minden AC teljesült.
- [ ] A releváns domain- és architektúraszabályok teljesültek.
- [ ] A diff fókuszált; nincs titok, debugkód vagy kapcsolódás nélküli fájl.
- [ ] A releváns változástípus-specifikus DoD-pontok teljesültek.
- [ ] A célzott és kockázatarányos regressziós ellenőrzések sikeresek.
- [ ] A statikus elemzés, formázás és build releváns része sikeres.
- [ ] A security, authorization, adat- és rollback-hatás ellenőrzött.
- [ ] A dokumentáció, lokalizáció és relatív hivatkozások naprakészek.
- [ ] A bizonyíték parancsot/lépést, környezetet és mérhető eredményt tartalmaz.
- [ ] A kihagyott pontok `N/A` indoka vagy eltérése dokumentált.
- [ ] A teljes diff, staging, commit/PR cím és backlogállapot ellenőrzött.
- [ ] Nincs nyitott blocker, `BLOCKER` vagy `REQUIRED`.

## Kapcsolódó szabályok

- [Backlog-konvenciók](backlog-conventions.md)
- [Commitüzenet-konvenció](commit-conventions.md)
- [Code review útmutató](code-review-guide.md)
- [Pull request sablon](../../.github/pull_request_template.md)
- [Commit előtti checklist](../../.kiro/checklists/before-commit.md)
- [Merge előtti checklist](../../.kiro/checklists/before-merge.md)
- [Release-checklist](../../.kiro/checklists/release.md)
- [Hozzájárulási útmutató](../../CONTRIBUTING.md)
