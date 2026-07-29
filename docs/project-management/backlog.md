# KM_Production központi backlog

## Dokumentumadatok

- Baseline dátuma: 2026-07-27
- Kanonikus backlog: ez a dokumentum
- Konvenciók: `docs/project-management/backlog-conventions.md`
- Aktuális sorrend: `docs/project-management/next-actions.md`
- Auditforrás: `docs/audits/project-audit-2026-07-27.md`
- Frissítés: minden állapot-, prioritás-, scope- vagy célverzió-változáskor

## Backlog használata

A backlog kizárólag jóváhagyott, konkrét és ellenőrizhető munkát tartalmaz.
Specifikáció, Git-ág vagy jövőbeni ötlet önmagában nem aktív feladat. A már
implementált MES-modulok a „Lezárt területek” szakaszban szerepelnek, nem
kerülnek vissza tervezett feature-ként.

A mezők, állapotátmenetek és lezárási szabályok részletes definícióját a
`docs/project-management/backlog-conventions.md` tartalmazza.

## Prioritások

- `P0`: kritikus adat-, biztonsági, release- vagy követhetőségi kockázat;
- `P1`: stabilitási kapu vagy a következő fejlesztési fázis előfeltétele;
- `P2`: tervezett, nem blokkoló üzleti/technikai érték;
- `P3`: későbbi, kutatási vagy nagy előfeltételű termékirány.

## Állapotok

`planned`, `ready`, `in-progress`, `blocked`, `review`, `done`, `cancelled`.

## Célverziók

- `v1.x Stabilizálás`
- `Learning Center v1.0`
- `Document Intelligence v1.0`
- `Learning Center v1.1`
- `Learning Center v1.2`
- `Manufacturing Intelligence v2`
- `Future / Unscheduled`

## Összesítés

| Kategória                     | Planned |  Ready | Review | Blocked |  Done | Összesen |
| ----------------------------- | ------: | -----: | -----: | ------: | ----: | -------: |
| Projektvezetés és Git         |       1 |      1 |      0 |       0 |     6 |        8 |
| CI és release                 |       2 |      5 |      1 |       0 |     2 |       10 |
| Tesztelés és statikus elemzés |       2 |      2 |      0 |       0 |     1 |        5 |
| Learning Center               |      18 |      1 |      0 |       0 |     0 |       19 |
| Document Intelligence és OCR  |      11 |      0 |      0 |       1 |     0 |       12 |
| Manufacturing Intelligence    |       5 |      0 |      0 |       0 |     0 |        5 |
| Üzemeltetés                   |       4 |      7 |      0 |       0 |     0 |       11 |
| UX és skálázhatóság           |       0 |      0 |      0 |       0 |     0 |        0 |
| **Összesen**                  |  **43** | **16** |  **1** |   **1** | **9** |   **70** |

| Prioritás | Darabszám |
| --------- | --------: |
| P0        |         5 |
| P1        |        43 |
| P2        |        17 |
| P3        |         5 |

| Célverzió                     | Feladatok száma |
| ----------------------------- | --------------: |
| v1.x Stabilizálás             |              34 |
| Learning Center v1.0          |              19 |
| Document Intelligence v1.0    |              12 |
| Learning Center v1.1          |               0 |
| Learning Center v1.2          |               0 |
| Manufacturing Intelligence v2 |               5 |
| Future / Unscheduled          |               0 |

## Aktív végrehajtási sorrend

Az első tíz aktív feladat részletes sorrendje:
`docs/project-management/next-actions.md`.

Röviden: `CI-002`, `CI-004`, `CI-005`, `GOV-005`, `CI-006`, `CI-007`,
`CI-009`, `OPS-003`, `LC-001`, `OPS-001`.

## Backlog tételek

### Projektvezetés és Git

#### GOV-001 — Központi backlog és karbantartási szabályok létrehozása

- **Állapot:** done
- **Prioritás:** P0
- **Kategória:** Projektvezetés és Git
- **Célverzió:** v1.x Stabilizálás
- **Összefoglaló:** Egységes backlog, konvenció, végrehajtási terv és audit
  baseline létrehozása.
- **Indoklás:** Korábban nem volt kanonikus forrás a státuszhoz, prioritáshoz,
  függőséghez és kész definíciójához.
- **Scope:** A négy projektirányítási dokumentum és a validálható kezdő backlog.
- **Scope-on kívül:** Feature-implementáció, GitHub issue-k, commit és push.
- **Függőségek:** Nincs.
- **Elfogadási feltételek:** A négy dokumentum létezik; minden aktív elemnek van
  kötelező mezője; az ID-k és függőségek validak.
- **Tesztelési követelmények:** Duplikált ID-, hivatkozás-, állapot-, prioritás-,
  célverzió- és dependency-ellenőrzés.
- **Kapcsolódó fájlok és dokumentáció:** `docs/project-management/backlog.md`,
  `docs/project-management/backlog-conventions.md`,
  `docs/project-management/next-actions.md`,
  `docs/audits/project-audit-2026-07-27.md`.
- **Becsült méret:** M
- **Kockázat:** Karbantartás nélkül a backlog gyorsan elavul.

#### GOV-002 — Az origin alapértelmezett ágának átállítása `main` ágra

- **Állapot:** done
- **Prioritás:** P1
- **Kategória:** Projektvezetés és Git
- **Célverzió:** v1.x Stabilizálás
- **Összefoglaló:** A remote default branch és `origin/HEAD` igazítása az aktív
  `main` ághoz.
- **Indoklás:** Az auditkor `origin/HEAD -> origin/master`, miközben a fejlesztés
  és CI a `main` ágat használja.
- **Scope:** GitHub default branch módosítása és friss klón/fetch ellenőrzése.
- **Scope-on kívül:** A `master` automatikus törlése és ágak átírása.
- **Függőségek:** Nincs.
- **Elfogadási feltételek:** A remote default branch `main`; fetch után
  `origin/HEAD -> origin/main`; friss klón a `main` ágon nyílik meg.
- **Tesztelési követelmények:** `git remote show origin`, `git branch -a -vv` és
  izolált klónozási smoke.
- **Kapcsolódó fájlok és dokumentáció:** `docs/deployment.md`,
  `docs/audits/project-audit-2026-07-27.md`.
- **Becsült méret:** XS
- **Kockázat:** Adminisztrátori jogosultság szükséges; hibás váltás automatizmust
  érinthet.
- **Eredmény 2026-07-28:** A `git remote set-head origin --auto` a szerver
  beállítása alapján változatlanul `origin/master` értéket adott. Helyi explicit
  felülírás nem történt, mert az elfedné a szerveroldali eltérést.
- **Lezárás 2026-07-28:** A GitHub szerveroldali HEAD-je `main`; fetch után
  `origin/HEAD -> origin/main`, a `main` az `origin/main` ágat követi, és a
  korábbi remote `master` ág már nincs jelen.

#### GOV-003 — Beolvadt branchek felülvizsgálata és takarítása

- **Állapot:** done
- **Prioritás:** P1
- **Kategória:** Projektvezetés és Git
- **Célverzió:** v1.x Stabilizálás
- **Összefoglaló:** A beolvadt feature/maintenance ágak bizonyítékalapú
  archiválása vagy törlése.
- **Indoklás:** A megmaradt ágak félkész munka látszatát keltik.
- **Scope:** Minden lokális és remote ág merge-, unique-commit- és
  megőrzési ellenőrzése.
- **Scope-on kívül:** Nem beolvadt commit törlése vagy force push.
- **Függőségek:** GOV-002.
- **Elfogadási feltételek:** Áganként döntési lista készül; csak nulla egyedi
  commitú vagy bizonyítottan kiváltott ág törlődik; a `main` változatlan.
- **Tesztelési követelmények:** `git branch --merged main`,
  `git log main..<ág>` és törlés utáni remote leltár.
- **Kapcsolódó fájlok és dokumentáció:** `docs/audits/project-audit-2026-07-27.md`,
  `docs/deployment.md`.
- **Becsült méret:** S
- **Kockázat:** Téves törlés elveszíthet nehezen visszakereshető referenciát.
- **Eredmény 2026-07-28:** Tíz helyi és ugyanaz a tíz remote feature/maintenance
  ág normál törléssel megszűnt. Minden tip a `main` őse volt, egyedi commitjuk
  nulla, `git cherry` eredményük üres, worktree- és tip-tag kapcsolatuk nem volt.
  A `main` megmaradt. A remote `master` az ellenőrzéskor még a szerveroldali
  default branch miatt megmaradt; a későbbi `GOV-002` lezárásakor már nem volt
  jelen.

#### GOV-004 — Commitüzenet-konvenció elfogadása

- **Állapot:** done
- **Prioritás:** P1
- **Kategória:** Projektvezetés és Git
- **Célverzió:** v1.x Stabilizálás
- **Összefoglaló:** Kötelező típus, scope, tárgy és opcionális backlog/issue
  hivatkozás meghatározása.
- **Indoklás:** A nem beszédes commitok akadályozzák az auditot és release note-ot.
- **Scope:** Konvenció, példák, tiltott minták és merge-commit szabály.
- **Scope-on kívül:** Régi commitok history rewrite-ja.
- **Függőségek:** Nincs.
- **Elfogadási feltételek:** Dokumentált formátum és legalább öt
  projekt-specifikus jó/rossz példa; az `AGENTS.md`, a hozzájárulási útmutató és
  a commit előtti checklist hivatkozik rá.
- **Tesztelési követelmények:** Dokumentum-, hivatkozás- és
  formázásellenőrzés, valamint a bevezető commit manuális megfelelőségi
  ellenőrzése. A következő négy nem merge commit külön, nem blokkoló
  bevezetési review-t kap.
- **Kapcsolódó fájlok és dokumentáció:**
  `docs/project-management/commit-conventions.md`, `AGENTS.md`,
  `CONTRIBUTING.md`, `.kiro/checklists/before-commit.md`,
  `docs/project-management/backlog-conventions.md`.
- **Becsült méret:** XS
- **Kockázat:** Automatizált ellenőrzés nélkül a szabály következetlen maradhat.
- **Eredmény 2026-07-28:** Az angol Conventional Commits-alapú formátum,
  projekt-scope-ok, breaking change, atomi commit, merge/squash és AI-agent
  szabályok dokumentáltak. Az automatizálás auditja új dependency telepítése
  nélkül, fokozatos CI-bevezetési javaslattal lezárult.

#### GOV-005 — Branch protection és required check szabályok bevezetése

- **Állapot:** planned
- **Prioritás:** P1
- **Kategória:** Projektvezetés és Git
- **Célverzió:** v1.x Stabilizálás
- **Összefoglaló:** A `main` védelme PR-, review- és quality-check kapukkal.
- **Indoklás:** A workflow-k jelenléte nem garantálja, hogy merge előtt kötelezők.
- **Scope:** Required check lista, review-szám, force-push/deletion és admin
  bypass döntések.
- **Scope-on kívül:** CI workflow-k újraírása.
- **Függőségek:** GOV-006, GOV-008, CI-005.
- **Elfogadási feltételek:** Közvetlen normál push tiltott; a dokumentált
  backend/frontend kritikus checkek nélkül PR nem merge-elhető; bypass szabály
  dokumentált.
- **Tesztelési követelmények:** Teszt-PR hiányzó és sikeres checkkel, valamint
  jogosulatlan direct-push próba.
- **Kapcsolódó fájlok és dokumentáció:** `.github/workflows/backend-quality.yml`,
  `.github/workflows/frontend.yml`, `.kiro/checklists/before-merge.md`.
- **Becsült méret:** S
- **Kockázat:** Hibás required check név blokkolhat minden merge-et.

#### GOV-006 — Pull request sablon létrehozása

- **Állapot:** done
- **Prioritás:** P1
- **Kategória:** Projektvezetés és Git
- **Célverzió:** v1.x Stabilizálás
- **Összefoglaló:** Egységes PR-sablon backlog ID-val, scope-pal, kockázattal és
  tesztbizonyítékkal.
- **Indoklás:** A quality gate és architekturális szabályok review-bizonyítéka
  jelenleg nem egységes.
- **Scope:** GitHub pull request sablon, reviewer útmutató, merge-stratégia,
  required-check és branch-protection javaslat, valamint AI-agent PR-szabályok.
- **Scope-on kívül:** Automatikus GitHub app vagy bot.
- **Függőségek:** GOV-004.
- **Elfogadási feltételek:** Új PR-nél megjelenik a backlog, üzleti hatás,
  migráció, jogosultság, teszt, dokumentáció és rollback mező; a review,
  merge- és AI-agent szabályok, valamint a tényleges checkjavaslat dokumentált.
- **Tesztelési követelmények:** Három eltérő változástípus helyi
  mintakitöltése, Markdown-, hivatkozás- és checklist-tartalmi validáció.
- **Kapcsolódó fájlok és dokumentáció:** `.github/pull_request_template.md`,
  `docs/project-management/code-review-guide.md`, `CONTRIBUTING.md`,
  `AGENTS.md`, `.kiro/checklists/before-merge.md`,
  `.kiro/checklists/security.md`, `.kiro/workflows/feature-development.md`.
- **Becsült méret:** XS
- **Kockázat:** Túl hosszú sablon formális, de tartalmatlan kitöltéshez vezethet.
- **Eredmény 2026-07-28:** Egyetlen általános PR-sablon, változástípus-alapú
  tesztelés, reviewer severity, merge-stratégia, required-check és
  branch-protection javaslat készült. A három helyi mintakitöltés és a
  dokumentációs validáció sikeres; GitHub-beállítás nem változott.

#### GOV-007 — GitHub issue sablonok létrehozása

- **Állapot:** ready
- **Prioritás:** P2
- **Kategória:** Projektvezetés és Git
- **Célverzió:** v1.x Stabilizálás
- **Összefoglaló:** Külön feature, bug és maintenance issue-beviteli szerkezet.
- **Indoklás:** A későbbi issue-leképezéshez azonosítható backlogkapcsolat kell.
- **Scope:** Típusonként scope, reprodukció/érték, elfogadás, kockázat és backlog
  ID mező.
- **Scope-on kívül:** Meglévő backlog automatikus issue-konverziója.
- **Függőségek:** GOV-004.
- **Elfogadási feltételek:** Mindhárom sablon elérhető; kötelező a backlog
  kapcsolat vagy az „új triage” jelölés; security hibát nem kér nyilvánosan.
- **Tesztelési követelmények:** GitHub issue-form preview és egy mintakitöltés
  típusonként.
- **Kapcsolódó fájlok és dokumentáció:** `docs/project-management/backlog-conventions.md`,
  `.kiro/workflows/bug-fix.md`, `.kiro/workflows/feature-development.md`.
- **Becsült méret:** S
- **Kockázat:** A sablon a backlog megkerülésének párhuzamos forrásává válhat.

#### GOV-008 — Projekt Definition of Done bevezetése

- **Állapot:** done
- **Prioritás:** P1
- **Kategória:** Projektvezetés és Git
- **Célverzió:** v1.x Stabilizálás
- **Összefoglaló:** Egységes lezárási kapu kódra, tesztre, biztonságra,
  dokumentációra és backlogfrissítésre.
- **Indoklás:** A `done` állapot közös bizonyítási szabály nélkül félreérthető.
- **Scope:** Kanonikus, változástípushoz igazodó DoD; backlog-, PR-, merge- és
  release-checklist összehangolása; bizonyíték- és kivételszabályok.
- **Scope-on kívül:** Minden kategória egyedi teszttervének részletes leírása.
- **Függőségek:** Nincs.
- **Elfogadási feltételek:** A backlog, PR, merge és release felület ugyanarra
  az elsődleges minimumkapura hivatkozik; környezeti kivétel legfeljebb
  `review` állapotot enged.
- **Tesztelési követelmények:** Hét eltérő változástípus helyi mintatétele,
  Markdown-, relatív hivatkozás-, parancs-, checklist- és backlogkonzisztencia
  validáció.
- **Kapcsolódó fájlok és dokumentáció:**
  `docs/project-management/definition-of-done.md`,
  `docs/project-management/backlog-conventions.md`,
  `.github/pull_request_template.md`, `.kiro/checklists/before-commit.md`,
  `.kiro/checklists/before-merge.md`, `.kiro/checklists/release.md`.
- **Becsült méret:** S
- **Kockázat:** Ellentmondó checklisták esetén a csapat eltérően értelmezheti.
- **Eredmény 2026-07-28:** A magyar nyelvű elsődleges DoD elkülöníti az AC,
  DoR, review-ready, merge-ready és release-ready fogalmakat, tíz
  változástípusra ad arányos feltételeket, és konkrét evidence-et követel. A
  governance belépési pontok ugyanarra a szabályzatra hivatkoznak; hét helyi
  mintafeladat és a dokumentációs validáció sikeres. CI workflow, required
  check és repository-beállítás nem változott.

### CI és release

#### CI-001 — A Vitest worker-timeout okának reprodukálása

- **Állapot:** done
- **Prioritás:** P1
- **Kategória:** CI és release
- **Célverzió:** v1.x Stabilizálás
- **Összefoglaló:** Kontrollált futásokkal azonosítani a párhuzamos worker
  timeout okát.
- **Indoklás:** `npm test` alatt 7 worker timeout történt, egyszálasan 166/166
  teszt sikeres volt.
- **Scope:** Node/npm verzió, pool, worker-szám, erőforrás és tesztfájl-indítás
  vizsgálata.
- **Scope-on kívül:** Assertionök gyengítése vagy tesztfájlok kihagyása.
- **Függőségek:** Nincs.
- **Elfogadási feltételek:** Legalább három reprodukció és kontrollfutás
  dokumentált; a gyökérok vagy bizonyított konfigurációs tartomány ismert.
- **Tesztelési követelmények:** Alapértelmezett, egyworkeres és fokozatos
  worker-számú Vitest futások azonos dependency state mellett.
- **Kapcsolódó fájlok és dokumentáció:** `vitest.config.js`, `package.json`,
  `docs/frontend-testing.md`, `tests/frontend/`,
  `docs/audits/frontend-worker-stability-2026-07-28.md`.
- **Becsült méret:** S
- **Kockázat:** Gépfüggő hiba miatt Linux CI-n nem feltétlen reprodukálható.
- **Eredmény 2026-07-28:** A korábbi hét worker-timeoutot a folyamat-alapú
  jsdom workerek erőforrás-versenye magyarázza. A 4 workeres `forks` profil
  914,6 MB, a 2 workeres 570,0 MB peak Node working setet használt; a 2
  workeres profil 3/3 kontrollban stabil és gyorsabb volt. A fájlizoláció és
  párhuzamosítás megmaradt, timeout, retry, tesztkihagyás, assertion- vagy
  dependency-változás nem történt. A végleges teljes suite stabilitási,
  build-, i18n- és formatting evidence a kapcsolódó auditban található.

#### CI-002 — Frontend unit, i18n és build quality gate stabilizálása

- **Állapot:** done
- **Prioritás:** P1
- **Kategória:** CI és release
- **Célverzió:** v1.x Stabilizálás
- **Összefoglaló:** A lokálisan stabil Vitest-konfiguráció és a frontend
  quality gate Linux CI-bizonyítása, szükség esetén a unit, i18n és build
  checkkontextus szétválasztása.
- **Indoklás:** A Windows worker-stabilitás bizonyított, de a GitHub-hosted Node
  24 futás és az összetett frontend job hibahatára még nem igazolt.
- **Scope:** GitHub Actions frontend job, Node 24 futás, checkkontextus és
  artifact/hibaelkülönítés legkisebb szükséges módosítása.
- **Scope-on kívül:** Tesztek törlése, timeout önkényes nagyítása.
- **Függőségek:** CI-001.
- **Elfogadási feltételek:** `npm test` három egymást követő Windows-futtatásban
  20/20 fájl és 166/166 teszt worker-timeout nélkül; Linux CI zöld.
- **Tesztelési követelmények:** Három lokális futás, GitHub Actions frontend job
  és változatlan tesztszám ellenőrzése.
- **Kapcsolódó fájlok és dokumentáció:** `vitest.config.js`, `package.json`,
  `.github/workflows/frontend.yml`.
- **Becsült méret:** S
- **Kockázat:** Túl konzervatív beállítás indokolatlanul lassíthatja a CI-t.
- **Eredmény 2026-07-28:** A unit, i18n és production build önálló, `needs`
  nélküli Node 24 jobot és stabil checknevet kapott. A helyi unit suite 5/5
  alkalommal 20/20 fájllal és 166/166 teszttel sikeres; az i18n 2/2, a build
  2/2 és a coverage 1/1 sikeres. A GitHub Actions
  [30365414060](https://github.com/KovacsZoltan65/KM_Production/actions/runs/30365414060)
  futásában mindhárom céljob sikeres volt, de a külön megőrzött dependency
  audit `npm audit` lépése exit code 1-gyel hibázott, és a production audit
  kimaradt. A feladat ezért a DoD szerint `review`; a lezárás feltétele a
  `CI-007` alatt rendezett audit és egy zöld teljes frontend workflow.

#### CI-003 — A teljes MySQL quality gate aktuális futtatása

- **Állapot:** done
- **Prioritás:** P1
- **Kategória:** CI és release
- **Célverzió:** v1.x Stabilizálás
- **Összefoglaló:** A jelenlegi `main` teljes teszt- és migrációs igazolása MySQL
  8.4-en.
- **Indoklás:** A gate implementált, de a baseline auditban nem futott.
- **Scope:** Tesztsuite, migration round-trip, kétszeri seed és DB-setting
  bizonyíték.
- **Scope-on kívül:** Fejlesztői vagy production adatbázis használata.
- **Függőségek:** Nincs.
- **Elfogadási feltételek:** A teljes MySQL suite és round-trip zöld; az
  idempotens seed kétszer sikeres; charset/collation/sql-mode rögzített.
- **Tesztelési követelmények:** `composer quality:backend:mysql` dedikált
  `km_production_testing` adatbázison.
- **Kapcsolódó fájlok és dokumentáció:** `composer.json`,
  `scripts/backend-test-environment.php`, `docs/backend-quality-gate.md`,
  `.github/workflows/backend-quality.yml`.
- **Becsült méret:** S
- **Kockázat:** Hibás környezetválasztás adatvesztést okozhat; guard kötelező.
- **Eredmény 2026-07-29:** A teljes helyi suite SQLite-on 3/3 baseline és 5/5
  módosítás utáni, MySQL 8.4-en 3/3 futásban 348/348 teszttel és 955
  assertionnel sikeres. A MySQL migration round-trip 2/2, az SQLite round-trip
  1/1 alkalommal teljes rollback/re-migrate és kétszeri idempotens seed
  smoke-kal zöld. A Linux CI-ben feltárt Vite artifact-függés, Inertia
  page-path case eltérés és opcionális PHPStan exclude javítva. A
  [30428224526](https://github.com/KovacsZoltan65/KM_Production/actions/runs/30428224526)
  futás mind a négy stabil backend jobja sikeres; a MySQL settings és JUnit
  artifactok létrejöttek.

#### CI-004 — A teljes Playwright E2E-kapu aktuális futtatása

- **Állapot:** partially done
- **Prioritás:** P1
- **Kategória:** CI és release
- **Célverzió:** v1.x Stabilizálás
- **Összefoglaló:** Chromium, accessibility, keyboard, Firefox/WebKit és mobile
  projektek teljes aktuális futtatása.
- **Indoklás:** A kapu determinisztikus resetje, readiness-e, failure
  artifactjai és kibővített feedback-lefedettsége helyben igazolt. A zöld
  GitHub Actions futás commit/push/PR engedély hiányában még nem bizonyítható.
- **Scope:** Izolált E2E előkészítés, build, minden projekt és artifact.
- **Scope-on kívül:** Windows Firefox compositorhiba alkalmazáskóddal történő
  elfedése.
- **Függőségek:** Nincs.
- **Elfogadási feltételek:** Minden projekt zöld vagy a lokális Firefox-kivétel
  Linux CI-bizonyítékkal dokumentált; nincs `test.only`/kihagyott kritikus teszt.
- **Tesztelési követelmények:** `npm run test:e2e` és a dokumentált a11y,
  keyboard, cross-browser, mobile parancsok.
- **Kapcsolódó fájlok és dokumentáció:** `playwright.config.js`, `package.json`,
  `docs/e2e-testing.md`,
  `docs/audits/playwright-e2e-quality-gate-2026-07-29.md`, `tests/e2e/`.
- **Becsült méret:** M
- **Kockázat:** Böngésző- és OS-függő eltérés hamis negatívot adhat.

#### CI-005 — GitHub Actions quality gate és required check mátrix auditja

- **Állapot:** ready
- **Prioritás:** P1
- **Kategória:** CI és release
- **Célverzió:** v1.x Stabilizálás
- **Összefoglaló:** A workflow jobok, triggerek, artifactok és merge-kapuk
  megfeleltetése.
- **Indoklás:** A workflow-k léteznek, de a required státusz nincs dokumentálva.
- **Scope:** Backend, frontend, MySQL, migráció és E2E check-mátrix.
- **Scope-on kívül:** Új tesztframework bevezetése.
- **Függőségek:** CI-002, CI-003, CI-004.
- **Elfogadási feltételek:** Minden release-kritikus parancsnak van CI-jobja,
  PR-triggerje, timeoutja és required-check döntése; hiánylista nulla vagy
  külön backlogelem.
- **Tesztelési követelmények:** Workflow syntax review és mintapull request
  teljes checklistája.
- **Kapcsolódó fájlok és dokumentáció:** `.github/workflows/backend-quality.yml`,
  `.github/workflows/frontend.yml`, `docs/backend-quality-gate.md`,
  `docs/frontend-testing.md`.
- **Becsült méret:** S
- **Kockázat:** Jobnévváltás megszakíthatja a branch-protection hivatkozást.

#### CI-006 — Composer security audit release-kapu igazolása

- **Állapot:** ready
- **Prioritás:** P1
- **Kategória:** CI és release
- **Célverzió:** v1.x Stabilizálás
- **Összefoglaló:** Aktuális PHP dependency audit és kötelező release-kezelés.
- **Indoklás:** A backend workflow nem futtat Composer security auditot.
- **Scope:** `composer audit`, találatok triage-ja és blokkolási szabály.
- **Scope-on kívül:** Automatikus major dependency upgrade.
- **Függőségek:** Nincs.
- **Elfogadási feltételek:** Audit exit code és riport rögzített; kritikus/magas
  találat blokkol vagy dokumentált kivétellel rendelkezik; CI-döntés elkészült.
- **Tesztelési követelmények:** `composer audit` tiszta lockfile-on és a
  workflow-változás syntax/PR ellenőrzése, ha bekerül.
- **Kapcsolódó fájlok és dokumentáció:** `composer.json`, `composer.lock`,
  `.github/workflows/backend-quality.yml`, `.kiro/checklists/security.md`.
- **Becsült méret:** S
- **Kockázat:** Külső advisory szolgáltatás átmeneti hibája blokkolhat release-t.

#### CI-007 — npm security audit release-kapu felülvizsgálata

- **Állapot:** ready
- **Prioritás:** P1
- **Kategória:** CI és release
- **Célverzió:** v1.x Stabilizálás
- **Összefoglaló:** A meglévő teljes és production npm audit policy-jának
  bizonyítása.
- **Indoklás:** A workflow futtatja az auditokat, de kivétel- és triage-szabály
  nincs központilag dokumentálva.
- **Scope:** Severity policy, dev/production különbség, kivétel lejárat.
- **Scope-on kívül:** Kockázatos automatikus `npm audit fix --force`.
- **Függőségek:** Nincs.
- **Elfogadási feltételek:** Mindkét audit eredménye rögzített; findinghez owner,
  döntés és határidő tartozik; production high/critical találat blokkol.
- **Tesztelési követelmények:** `npm audit` és `npm audit --omit=dev`, majd
  workflow PR-futás.
- **Kapcsolódó fájlok és dokumentáció:** `package.json`, `package-lock.json`,
  `.github/workflows/frontend.yml`, `docs/frontend-testing.md`.
- **Becsült méret:** S
- **Kockázat:** Dev dependency finding túl szigorú kezelése indokolatlan blokk.

#### CI-008 — Kritikus MES referencia-E2E bővítése

- **Állapot:** planned
- **Prioritás:** P1
- **Kategória:** CI és release
- **Célverzió:** v1.x Stabilizálás
- **Összefoglaló:** Egy determinisztikus, end-to-end referenciafolyamat a
  rendeléstől a minőségellenőrzött késztermékig.
- **Indoklás:** A jelenlegi workflow tesztek több részfolyamatot fednek, de a
  teljes traceability lánc külön referencia-gate-ként nincs dokumentálva.
- **Scope:** Vevőrendelés, terv/rendelés, feladat, anyagmozgás, quality és
  befejezett állapot összekapcsolt E2E ellenőrzése.
- **Scope-on kívül:** Kiszállítási feature létrehozása vagy üzleti szabálymódosítás.
- **Függőségek:** CI-004.
- **Elfogadási feltételek:** Egy izolált E2E scenario ellenőrzi a teljes
  meglévő láncot, serial/stock/audit nyomot; ismételt futás determinisztikus.
- **Tesztelési követelmények:** Chromium kötelező; kritikus permission-negatív
  eset; E2E seeder idempotencia.
- **Kapcsolódó fájlok és dokumentáció:** `tests/e2e/workflows/`,
  `database/seeders/E2ETestSeeder.php`, `docs/e2e-testing.md`,
  `.kiro/knowledge/production.md`.
- **Becsült méret:** M
- **Kockázat:** Túl nagy scenario flakységet és nehezen diagnosztizálható hibát okoz.

#### CI-009 — Egységes release gate és evidence-csomag

- **Állapot:** planned
- **Prioritás:** P1
- **Kategória:** CI és release
- **Célverzió:** v1.x Stabilizálás
- **Összefoglaló:** Egy release-hez szükséges ellenőrzések és bizonyítékok
  egységes végrehajtása.
- **Indoklás:** A kapuk több workflow-ban és dokumentumban szétszórva vannak.
- **Scope:** Backend/frontend/security/E2E/migration/export eredmény, rollback és
  jóváhagyás.
- **Scope-on kívül:** Automatikus production deployment.
- **Függőségek:** CI-002, CI-003, CI-004, CI-005, CI-006, CI-007, CI-010.
- **Elfogadási feltételek:** Egy verziójelöltre kitöltött release evidence
  tartalmaz minden kapueredményt és rollback tervet; hiány esetén release tiltott.
- **Tesztelési követelmények:** Dry-run release a checklist szerint, hibás kapu
  esetén bizonyított megállással.
- **Kapcsolódó fájlok és dokumentáció:** `.kiro/checklists/release.md`,
  `.kiro/workflows/release.md`, `docs/deployment.md`.
- **Becsült méret:** M
- **Kockázat:** Duplikálhatja a CI-t, ha nem hivatkozásalapú evidence készül.

#### CI-010 — Projekt-export ellenőrzés beemelése a release-folyamatba

- **Állapot:** ready
- **Prioritás:** P2
- **Kategória:** CI és release
- **Célverzió:** v1.x Stabilizálás
- **Összefoglaló:** A meglévő biztonságos exportscript és teszt kötelező
  release-lépéssé tétele.
- **Indoklás:** Az exportmegoldás elkészült, de nincs a release gate-hez kötve.
- **Scope:** Script teszt, ZIP tartalomellenőrzés és evidence.
- **Scope-on kívül:** Deploy artifact publikálása külső tárhelyre.
- **Függőségek:** Nincs.
- **Elfogadási feltételek:** Az exportteszt sikeres; titok, `.env`, log,
  dependency és helyi DB nincs az artifactban; release checklist hivatkozik rá.
- **Tesztelési követelmények:** `scripts/test-export-project.ps1` és generált
  ZIP tiltott/engedélyezett fájlmintáinak ellenőrzése.
- **Kapcsolódó fájlok és dokumentáció:** `scripts/export-project.ps1`,
  `scripts/test-export-project.ps1`, `.gitattributes`, `docs/deployment.md`.
- **Becsült méret:** S
- **Kockázat:** Platformfüggő PowerShell-futtatás Linux CI-n külön kezelést kér.

### Tesztelés és statikus elemzés

#### AUD-001 — Rekordállapot-alapú activity log bevezetése

- **Állapot:** review
- **Prioritás:** P0
- **Kategória:** Tesztelés és statikus elemzés
- **Célverzió:** v1.x Stabilizálás
- **Összefoglaló:** Biztonságosan szűrt create állapot és dirty-only update
  diff a service-alapú Spatie activity naplóban.
- **Indoklás:** Az egyszerű CRUD activityk korábban nem tartalmaztak
  rekordállapotot, régi/új értéket vagy változottmező-listát.
- **Scope:** Központi audit API, admin CRUD, egyszerű egyedi service-ek,
  releváns állapotváltások, érzékeny mezők, no-op és tranzakciós konzisztencia.
- **Scope-on kívül:** Automatikus `LogsActivity` minden modellen, audit UI és
  törlés előtti teljes rekordállapot.
- **Függőségek:** Nincs.
- **Elfogadási feltételek:** Create állapot és update old/new dirty diff;
  jelszó/token/secret kizárás; business properties elkülönítés; nincs
  duplikáció vagy no-op activity; SQLite és MySQL gate zöld.
- **Tesztelési követelmények:** Célzott activity 3/3 SQLite és MySQL; teljes
  SQLite 3/3, teljes MySQL 2/2; Pint, Larastan és tranzakciós rollback.
- **Kapcsolódó fájlok és dokumentáció:**
  `app/Services/AuditLogService.php`,
  `tests/Feature/RecordStateActivityLoggingTest.php`,
  `docs/audits/record-state-activity-logging-2026-07-29.md`.
- **Becsült méret:** L
- **Kockázat:** Régi activityk eltérő szerkezete, retention és személyes adatok
  megőrzési szabálya.
- **Eredmény:** A központi és egyedi service-integráció, a dirty-only diff,
  érzékenymező-szűrés, role-kapcsolati diff és tranzakciós rollback elkészült.
  A célzott SQLite/MySQL és a teljes SQLite 3/3, MySQL 2/2 quality gate zöld.

#### TEST-001 — PHPStan level 6 alkalmassági emelés

- **Állapot:** ready
- **Prioritás:** P2
- **Kategória:** Tesztelés és statikus elemzés
- **Célverzió:** v1.x Stabilizálás
- **Összefoglaló:** Level 6 futtatása, hibák valódi javítása és baseline nélküli
  gate fenntartása.
- **Indoklás:** A level 5 zöld; a következő dokumentált minőségi lépcső level 6.
- **Scope:** Hibakategória-leltár, típusjavítás és konfigurációemelés.
- **Scope-on kívül:** Baseline generálása vagy üzleti viselkedés változtatása.
- **Függőségek:** Nincs.
- **Elfogadási feltételek:** Level 6 futás 0 hibával, baseline/ignore-bővítés
  nélkül; a teljes backend suite zöld.
- **Tesztelési követelmények:** `composer analyse`, `composer
quality:backend:sqlite` és MySQL gate releváns része.
- **Kapcsolódó fájlok és dokumentáció:** `phpstan.neon.dist`, `composer.json`,
  `docs/static-analysis.md`.
- **Becsült méret:** M
- **Kockázat:** Rejtett PHPDoc-adósság nagyobb lehet az előzetes becslésnél.

#### TEST-002 — Frontend coverage baseline rögzítése

- **Állapot:** ready
- **Prioritás:** P2
- **Kategória:** Tesztelés és statikus elemzés
- **Célverzió:** v1.x Stabilizálás
- **Összefoglaló:** A jelenlegi célzott Vitest coverage mért és verziózott
  baseline-ja.
- **Indoklás:** Coverage riport létezik, de nincs dokumentált kiindulási érték.
- **Scope:** Statements/branches/functions/lines modulonként és kritikus
  területenként.
- **Scope-on kívül:** Önkényes globális threshold az első mérés előtt.
- **Függőségek:** CI-002.
- **Elfogadási feltételek:** Sikeres coverage futás; JSON-summary archivált
  összesítése; kritikus lefedetlenségi lista backlogkapcsolattal.
- **Tesztelési követelmények:** `npm run test:frontend:coverage` stabil
  konfiguráción, változatlan 166 teszttel.
- **Kapcsolódó fájlok és dokumentáció:** `vitest.config.js`, `package.json`,
  `docs/frontend-testing.md`.
- **Becsült méret:** S
- **Kockázat:** A százalék optimalizálása fontos viselkedési tesztek helyett.

#### TEST-003 — Kockázatalapú frontend coverage küszöbök

- **Állapot:** planned
- **Prioritás:** P2
- **Kategória:** Tesztelés és statikus elemzés
- **Célverzió:** v1.x Stabilizálás
- **Összefoglaló:** Kritikus komponensekre fokozatos, indokolt coverage minimum.
- **Indoklás:** A dokumentáció szerint globális threshold még tudatosan nincs.
- **Scope:** Dokumentum-, készlet-, gyártási és jogosultsági frontend határok.
- **Scope-on kívül:** Az összes egyszerű CRUD-oldal mesterséges 100%-os lefedése.
- **Függőségek:** TEST-002.
- **Elfogadási feltételek:** Modulonként indokolt threshold; baseline alá esés
  blokkol; kizárások dokumentáltak és üzleti kódot nem rejtenek el.
- **Tesztelési követelmények:** Egy szándékos threshold-alatti kontroll és egy
  sikeres CI coverage futás.
- **Kapcsolódó fájlok és dokumentáció:** `vitest.config.js`,
  `docs/frontend-testing.md`, `tests/frontend/`.
- **Becsült méret:** S
- **Kockázat:** Rossz threshold flaky vagy értéktelen teszteket ösztönöz.

#### TEST-004 — Dokumentált frontend edge-case teszthézagok lezárása

- **Állapot:** planned
- **Prioritás:** P2
- **Kategória:** Tesztelés és statikus elemzés
- **Célverzió:** v1.x Stabilizálás
- **Összefoglaló:** Procurement approval, production planning, quality ágak és
  fájlletöltési hibák célzott tesztjei.
- **Indoklás:** Ezeket a `docs/frontend-testing.md` további bővítési irányként
  azonosítja.
- **Scope:** Meglévő publikus prop/emit/route/permission szerződések edge case-ei.
- **Scope-on kívül:** PrimeVue belső DOM, CSS vagy backend üzleti logika tesztje.
- **Függőségek:** CI-002, TEST-002.
- **Elfogadási feltételek:** Mind a négy terület legalább egy pozitív és egy
  negatív regressziós esettel bővül; teljes frontend suite zöld.
- **Tesztelési követelmények:** `npm test`, célzott Vitest futások és coverage
  változás review.
- **Kapcsolódó fájlok és dokumentáció:** `docs/frontend-testing.md`,
  `tests/frontend/pages/`, `tests/frontend/components/`.
- **Becsült méret:** M
- **Kockázat:** A backend szerződés tesztkedvéért történő fellazítása tilos.

### Learning Center

#### LC-001 — Learning Center v1.0 scope lezárása

- **Állapot:** ready
- **Prioritás:** P1
- **Kategória:** Learning Center
- **Célverzió:** Learning Center v1.0
- **Összefoglaló:** A v1.0 kötelező képességeinek, nem céljainak és első
  támogatott oldalainak véglegesítése.
- **Indoklás:** A specifikáció nyitott kérdései eltérő adatmodellt és UI-scope-ot
  eredményezhetnek.
- **Scope:** Knowledge Unit, Lesson, Course, Learning Path, progress,
  asszisztenciaszint, help és admin minimum.
- **Scope-on kívül:** v1.1 keresés/analitika, v1.2 média, v2 AI coach.
- **Függőségek:** Nincs.
- **Elfogadási feltételek:** Döntés születik minden README nyitott kérdésről;
  támogatott oldalak és szerepköri útvonalak listája jóváhagyott; non-goal lista
  explicit.
- **Tesztelési követelmények:** Scope review termék-, domain-, security- és
  engineering checklistával.
- **Kapcsolódó fájlok és dokumentáció:** `docs/specifications/learning-center/README.md`,
  `docs/specifications/learning-center/roadmap.md`,
  `docs/specifications/learning-center/decisions.md`.
- **Becsült méret:** M
- **Kockázat:** Scope-zárás nélkül a rétegek egymással inkompatibilisen készülnek.

#### LC-002 — A `Draft` specifikáció review-ja és v1.0 baseline-ja

- **Állapot:** planned
- **Prioritás:** P1
- **Kategória:** Learning Center
- **Célverzió:** Learning Center v1.0
- **Összefoglaló:** A specifikációk ellentmondás-, döntés- és
  implementálhatósági review-ja.
- **Indoklás:** A dokumentáció részletes, de továbbra is `Draft`.
- **Scope:** README, Knowledge Unit, Graph, Course Model, Context Engine, UI,
  permission és error handling konzisztencia.
- **Scope-on kívül:** Runtime kód létrehozása.
- **Függőségek:** LC-001.
- **Elfogadási feltételek:** Nincs megválaszolatlan v1.0-blokkoló kérdés; az
  elfogadott döntések ADR-ben vannak; a baseline verziózott és review-zott.
- **Tesztelési követelmények:** Dokumentációs link- és terminológiaellenőrzés,
  domain/security review.
- **Kapcsolódó fájlok és dokumentáció:** `docs/specifications/learning-center/`,
  `docs/architecture/knowledge-graph.md`, `docs/architecture/course-model.md`.
- **Becsült méret:** M
- **Kockázat:** Fogalmi döntés adatbázistervként való félreértelmezése.

#### LC-003 — Learning Center v1.0 logikai és fizikai adatmodell

- **Állapot:** planned
- **Prioritás:** P1
- **Kategória:** Learning Center
- **Célverzió:** Learning Center v1.0
- **Összefoglaló:** A jóváhagyott fogalmi modell táblákra, kulcsokra,
  státuszokra és retention szabályokra fordítása.
- **Indoklás:** A jelenlegi `data-model.md` tervezett entitáslista, nem
  implementációs adatbázisterv.
- **Scope:** Entitások, kapcsolatok, indexek, unique szabályok, soft delete,
  locale, versioning, progress és event retention.
- **Scope-on kívül:** Migráció és Eloquent implementáció.
- **Függőségek:** LC-002.
- **Elfogadási feltételek:** Minden v1.0 use case leképezhető; cardinality,
  lifecycle és törlési szabály explicit; adatvédelmi retention jóváhagyott.
- **Tesztelési követelmények:** ER-review, normalizáció/index review és három
  reprezentatív user-flow adatwalkthrough.
- **Kapcsolódó fájlok és dokumentáció:** `docs/specifications/learning-center/data-model.md`,
  `docs/specifications/learning-center/knowledge-unit.md`,
  `docs/specifications/learning-center/permissions.md`.
- **Becsült méret:** L
- **Kockázat:** Hibás verziózás publikált tudás vagy progress elvesztését okozhatja.

#### LC-004 — Learning Center migrációk és Eloquent modellek

- **Állapot:** planned
- **Prioritás:** P1
- **Kategória:** Learning Center
- **Célverzió:** Learning Center v1.0
- **Összefoglaló:** A jóváhagyott adatmodell biztonságos Laravel perzisztencia
  rétege.
- **Indoklás:** Jelenleg nincs Learning Center runtime adatbázisréteg.
- **Scope:** Migrációk, modellek, enumok, castok, relációk, factoryk és
  visszagörgetés.
- **Scope-on kívül:** Query orchestration és üzleti workflow.
- **Függőségek:** LC-003.
- **Elfogadási feltételek:** SQLite/MySQL migráció round-trip sikeres; modellek
  minden jóváhagyott kapcsolatot és constraintet tükröznek; factoryk validak.
- **Tesztelési követelmények:** Migráció-, constraint-, relation-, cast- és
  factory tesztek mindkét adatbázison.
- **Kapcsolódó fájlok és dokumentáció:** `database/migrations/`,
  `database/factories/`, `app/Models/`,
  `docs/specifications/learning-center/data-model.md`.
- **Becsült méret:** L
- **Kockázat:** Korai migráció későbbi destruktív sémaváltást kényszeríthet ki.

#### LC-005 — Learning Center repository réteg

- **Állapot:** planned
- **Prioritás:** P1
- **Kategória:** Learning Center
- **Célverzió:** Learning Center v1.0
- **Összefoglaló:** Interfészek és query implementációk admin-, publikált-,
  progress- és kontextuslekérdezésekhez.
- **Indoklás:** A projekt rétegzett architektúrája szerint a query logika
  repositoryban marad.
- **Scope:** Szűrés, lapozás, eager loading, publikált verzió és user progress
  lekérdezések.
- **Scope-on kívül:** Publikálási és ajánlási üzleti döntés.
- **Függőségek:** LC-004.
- **Elfogadási feltételek:** Minden service use case-hez explicit interfész
  tartozik; nincs controller query; listák lapozottak és query count bounded.
- **Tesztelési követelmények:** Repository filter/sort/pagination, N+1 és
  published/draft szeparációs tesztek.
- **Kapcsolódó fájlok és dokumentáció:** `app/Repositories/`,
  `app/Repositories/Contracts/`, `.kiro/playbooks/create-repository.md`,
  `.kiro/steering/architecture.md`.
- **Becsült méret:** L
- **Kockázat:** Túl általános repository elrejtheti a domain szemantikát.

#### LC-006 — Learning Center domain- és service-réteg

- **Állapot:** planned
- **Prioritás:** P1
- **Kategória:** Learning Center
- **Célverzió:** Learning Center v1.0
- **Összefoglaló:** Tudás-életciklus, kurzusstruktúra, progress és segítség
  üzleti workflow-k.
- **Indoklás:** Az üzleti szabályok nem kerülhetnek controllerbe vagy modellbe.
- **Scope:** Draft/review/publish/archive, course composition, progress update,
  assistance preference és audit események.
- **Scope-on kívül:** AI-generálás és v1.1 adaptív ajánlás.
- **Függőségek:** LC-005.
- **Elfogadási feltételek:** Tiltott állapotátmenetek elutasítva; tranzakciók
  konzisztensen kezelik a kapcsolódó írásokat; fontos műveletek auditáltak.
- **Tesztelési követelmények:** Service success/failure, idempotencia,
  tranzakciós és lifecycle tesztek.
- **Kapcsolódó fájlok és dokumentáció:** `app/Services/`,
  `docs/specifications/learning-center/knowledge-engine.md`,
  `docs/specifications/learning-center/learning-engine.md`.
- **Becsült méret:** L
- **Kockázat:** Progress és tartalom-életciklus összekeverése nehezen javítható.

#### LC-007 — Learning Center permissionök és policy-k

- **Állapot:** planned
- **Prioritás:** P1
- **Kategória:** Learning Center
- **Célverzió:** Learning Center v1.0
- **Összefoglaló:** Tartalomkezelési, publikálási, megtekintési és
  progress-láthatósági jogosultságok.
- **Indoklás:** A tudástartalom és user progress nem védhető pusztán frontend
  láthatósággal.
- **Scope:** Permission registry, role mapping, policy-k és negatív esetek.
- **Scope-on kívül:** Szakmai szerepkörök autorizációs szerepkörré alakítása.
- **Függőségek:** LC-004.
- **Elfogadási feltételek:** Viewer csak publikált/jogosult tartalmat lát;
  szerkesztés/publikálás külön permission; progress hozzáférés adatvédelmileg
  korlátozott; super-admin viselkedés explicit.
- **Tesztelési követelmények:** Policy és HTTP 403 teszt minden művelethez,
  szerepkör/permission seeder idempotencia.
- **Kapcsolódó fájlok és dokumentáció:** `app/Policies/`,
  `database/seeders/RolesAndPermissionsSeeder.php`,
  `docs/specifications/learning-center/permissions.md`.
- **Becsült méret:** M
- **Kockázat:** Túl széles progress-hozzáférés munkavállalói adatvédelmi kockázat.

#### LC-008 — Learning Center controllerek, FormRequestek és route-ok

- **Állapot:** planned
- **Prioritás:** P1
- **Kategória:** Learning Center
- **Célverzió:** Learning Center v1.0
- **Összefoglaló:** Vékony HTTP-koordináció az admin és felhasználói use case-ekhez.
- **Indoklás:** Jelenleg nincs Learning Center route vagy runtime endpoint.
- **Scope:** REST/Inertia route-ok, policy authorization, validáció és service
  delegáció.
- **Scope-on kívül:** Query vagy üzleti logika controllerben.
- **Függőségek:** LC-006, LC-007.
- **Elfogadási feltételek:** Minden írás FormRequestet és policy-t használ;
  route nevek konzisztens namespace-ben; controllerben nincs domain döntés.
- **Tesztelési követelmények:** Route, authorization, validation és Inertia prop
  feature tesztek.
- **Kapcsolódó fájlok és dokumentáció:** `routes/web.php`,
  `app/Http/Controllers/`, `app/Http/Requests/`,
  `.kiro/steering/backend.md`.
- **Becsült méret:** L
- **Kockázat:** Túl nagy controller megkerülheti a service-réteget.

#### LC-009 — Knowledge Unit admin CRUD és publikálás

- **Állapot:** planned
- **Prioritás:** P2
- **Kategória:** Learning Center
- **Célverzió:** Learning Center v1.0
- **Összefoglaló:** Knowledge Unit létrehozás, szerkesztés, review, publikálás és
  archiválás adminfelülete.
- **Indoklás:** A Knowledge Unit a specifikáció alapegysége, runtime kezelés nincs.
- **Scope:** Lapozott lista, form, lifecycle action, kapcsolatok és audit.
- **Scope-on kívül:** Markdown teljes WYSIWYG szerkesztő és AI tartalomgenerálás.
- **Függőségek:** LC-008.
- **Elfogadási feltételek:** Jogosult admin létrehoz, review-z és publikál egy
  unitot; hibás lifecycle tiltott; publikált verzió felhasználónak látható.
- **Tesztelési követelmények:** Backend CRUD/lifecycle, Vue form/lista és
  permission-negatív tesztek.
- **Kapcsolódó fájlok és dokumentáció:** `docs/specifications/learning-center/knowledge-unit.md`,
  `docs/specifications/learning-center/ui-ux.md`,
  `.kiro/playbooks/create-admin-page.md`.
- **Becsült méret:** L
- **Kockázat:** Draft tartalom véletlen publikálása hibás üzleti útmutatást adhat.

#### LC-010 — Lesson admin kezelés

- **Állapot:** planned
- **Prioritás:** P2
- **Kategória:** Learning Center
- **Célverzió:** Learning Center v1.0
- **Összefoglaló:** Lecke és rendezett lépések kezelése Knowledge Unit
  hivatkozásokkal.
- **Indoklás:** A Course Model specifikált, de Lesson runtime nincs.
- **Scope:** CRUD, step-sorrend, unit-kapcsolat, publikálhatósági validáció.
- **Scope-on kívül:** Interaktív v1.2 walkthrough és tudásellenőrzés.
- **Függőségek:** LC-009.
- **Elfogadási feltételek:** Admin legalább egy unitot tartalmazó leckét hoz
  létre; step-sorrend egyedi; nem publikálható érvénytelen vagy draft függőséggel.
- **Tesztelési követelmények:** Sorrend/duplikáció, lifecycle, permission és Vue
  editor tesztek.
- **Kapcsolódó fájlok és dokumentáció:** `docs/architecture/course-model.md`,
  `docs/specifications/learning-center/learning-engine.md`.
- **Becsült méret:** M
- **Kockázat:** Tartalomverzió és leckeverzió eltérése törött tananyagot okozhat.

#### LC-011 — Course admin kezelés

- **Állapot:** planned
- **Prioritás:** P2
- **Kategória:** Learning Center
- **Célverzió:** Learning Center v1.0
- **Összefoglaló:** Kurzus és rendezett Lesson kapcsolatok adminisztrációja.
- **Indoklás:** A felhasználói tanulási egységhez publikálható kurzus kell.
- **Scope:** CRUD, lecke-sorrend, státusz, előfeltétel és publikálás.
- **Scope-on kívül:** Tanúsítvány és adaptív sorrend.
- **Függőségek:** LC-010.
- **Elfogadási feltételek:** Admin legalább egy leckés kurzust publikál; hibás
  lecke/státusz blokkol; felhasználó csak jogosult publikált kurzust lát.
- **Tesztelési követelmények:** Course composition, ordering, lifecycle,
  authorization és frontend editor tesztek.
- **Kapcsolódó fájlok és dokumentáció:** `docs/architecture/course-model.md`,
  `docs/specifications/learning-center/learning-paths.md`.
- **Becsült méret:** M
- **Kockázat:** Ciklikus előfeltételek használhatatlan kurzust eredményezhetnek.

#### LC-012 — Learning Path admin kezelés

- **Állapot:** planned
- **Prioritás:** P2
- **Kategória:** Learning Center
- **Célverzió:** Learning Center v1.0
- **Összefoglaló:** Szerepkör-orientált, rendezett kurzus- vagy leckesorozatok.
- **Indoklás:** A v1.0 alap tanulási útvonalakat ígér.
- **Scope:** CRUD, szakmai célcsoport, kötelező/ajánlott jelleg és sorrend.
- **Scope-on kívül:** Automatikus adaptív útvonal és mentor mód.
- **Függőségek:** LC-011.
- **Elfogadási feltételek:** Jóváhagyott szerepkörhöz útvonal rendelhető; sorrend
  determinisztikus; több szakmai szerep összevonási szabálya dokumentált.
- **Tesztelési követelmények:** Role mapping, ordering, visibility és admin UI
  tesztek.
- **Kapcsolódó fájlok és dokumentáció:** `docs/specifications/learning-center/learning-paths.md`,
  `docs/specifications/learning-center/onboarding.md`.
- **Becsült méret:** M
- **Kockázat:** Szakmai és authorization szerepkör összekeverése jogosultsági hibát okoz.

#### LC-013 — Felhasználói progresskövetés

- **Állapot:** planned
- **Prioritás:** P2
- **Kategória:** Learning Center
- **Célverzió:** Learning Center v1.0
- **Összefoglaló:** Lecke-, kurzus- és útvonalszintű előrehaladás idempotens
  rögzítése és megjelenítése.
- **Indoklás:** Progress nélkül a Learning Engine alapfunkciója nem teljes.
- **Scope:** Start/complete, százalék, időbélyeg, folytatás és user saját nézet.
- **Scope-on kívül:** Munkavállalói teljesítményértékelés és heatmap.
- **Függőségek:** LC-004, LC-006, LC-011, LC-012.
- **Elfogadási feltételek:** Lecke teljesítése egyszer frissíti a progresszt;
  kurzus/útvonal aggregáció helyes; más user adata nem olvasható engedély nélkül.
- **Tesztelési követelmények:** Ismételt/concurrent complete, aggregáció,
  authorization és Inertia prop tesztek.
- **Kapcsolódó fájlok és dokumentáció:** `docs/specifications/learning-center/learning-engine.md`,
  `docs/specifications/learning-center/permissions.md`.
- **Becsült méret:** L
- **Kockázat:** Nem idempotens event duplikált teljesítést és hibás analitikát okoz.

#### LC-014 — Oldalhoz kötött kontextuális súgó

- **Állapot:** planned
- **Prioritás:** P2
- **Kategória:** Learning Center
- **Célverzió:** Learning Center v1.0
- **Összefoglaló:** Oldalregiszter és permission/state alapján szűrt help drawer.
- **Indoklás:** A v1.0 fő értéke az aktuális workflow-hoz kapcsolódó segítség.
- **Scope:** Támogatott route-oldalak, help topic kiválasztás, biztonságos
  kontextus payload és üres állapot.
- **Scope-on kívül:** Teljes chatbot és automatikus üzleti művelet.
- **Függőségek:** LC-006, LC-008, LC-009.
- **Elfogadási feltételek:** Első oldalregiszter minden oldalán releváns,
  publikált és jogosult segítség jelenik meg; tiltott adat nem szivárog.
- **Tesztelési követelmények:** Context matrix, permission/state/locale, empty
  state, frontend drawer és route-váltási tesztek.
- **Kapcsolódó fájlok és dokumentáció:** `docs/specifications/learning-center/context-engine.md`,
  `docs/specifications/learning-center/live-documentation.md`.
- **Becsült méret:** L
- **Kockázat:** Hibás context mapping téves vagy jogosulatlan tanácsot adhat.

#### LC-015 — Oldalankénti asszisztenciaszintek

- **Állapot:** planned
- **Prioritás:** P2
- **Kategória:** Learning Center
- **Célverzió:** Learning Center v1.0
- **Összefoglaló:** Kezdő, Haladó és Profi szint tárolása, jelzése és kézi váltása.
- **Indoklás:** A specifikáció szerint a szint oldalanként, nem globálisan működik.
- **Scope:** Alapérték, user/page preference, selector és segítség intenzitása.
- **Scope-on kívül:** Automatikus szintváltás user jóváhagyása nélkül.
- **Függőségek:** LC-004, LC-014.
- **Elfogadási feltételek:** Támogatott oldalon látható és módosítható a szint;
  beállítás oldalanként megmarad; kritikus warning Profi módban sem tűnik el.
- **Tesztelési követelmények:** Preference persistence, három szint viselkedése,
  critical warning és frontend selector tesztek.
- **Kapcsolódó fájlok és dokumentáció:** `docs/specifications/learning-center/assistance-levels.md`,
  `docs/specifications/learning-center/ui-ux.md`.
- **Becsült méret:** M
- **Kockázat:** Elrejtett kritikus segítség manufacturing hibát okozhat.

#### LC-016 — Learning Center lokalizáció és kezdő tartalomcsomag

- **Állapot:** planned
- **Prioritás:** P2
- **Kategória:** Learning Center
- **Célverzió:** Learning Center v1.0
- **Összefoglaló:** HU/EN UI-kulcsok és az első támogatott oldalak publikálható
  tudástartalma.
- **Indoklás:** Runtime struktúra tartalom és locale-szabály nélkül nem használható.
- **Scope:** JSON translation key, locale fallback, meglévő Markdown újrahasználat
  és seed/import döntés.
- **Scope-on kívül:** v2 kiforrott többnyelvű content workflow.
- **Függőségek:** LC-009, LC-010, LC-011, LC-012, LC-013, LC-014, LC-015.
- **Elfogadási feltételek:** Első oldalregiszter HU tartalma teljes, EN fallback
  dokumentált; nincs hardcoded UI-szöveg; i18n kulcsok szinkronban.
- **Tesztelési követelmények:** `npm run i18n:check`, locale-váltás, fallback és
  tartalom-link ellenőrzés.
- **Kapcsolódó fájlok és dokumentáció:** `lang/hu.json`, `lang/en.json`,
  `docs/i18n.md`, `docs/user-guides/`.
- **Becsült méret:** L
- **Kockázat:** Duplikált Markdown és DB-tartalom eltérő igazságforrást hozhat létre.

#### LC-017 — Learning Center backend tesztcsomag

- **Állapot:** planned
- **Prioritás:** P1
- **Kategória:** Learning Center
- **Célverzió:** Learning Center v1.0
- **Összefoglaló:** A teljes perzisztencia, service, authorization és HTTP
  viselkedés regresszióvédelme.
- **Indoklás:** Üzletkritikus lifecycle és progress csak automatizált kapuval kész.
- **Scope:** Modell/repository/service/feature/policy tesztek SQLite és MySQL alatt.
- **Scope-on kívül:** Frontend DOM és E2E böngészőteszt.
- **Függőségek:** LC-004, LC-005, LC-006, LC-007, LC-008, LC-009, LC-010,
  LC-011, LC-012, LC-013, LC-014, LC-015, LC-016.
- **Elfogadási feltételek:** Minden lifecycle, permission, progress és context
  főág pozitív/negatív tesztet kap; teljes backend gate zöld.
- **Tesztelési követelmények:** SQLite/MySQL suite, migration round-trip,
  PHPStan és Pint.
- **Kapcsolódó fájlok és dokumentáció:** `tests/Feature/`, `tests/Unit/`,
  `.kiro/steering/testing.md`, `docs/backend-quality-gate.md`.
- **Becsült méret:** L
- **Kockázat:** Csak HTTP-tesztek esetén a domain edge case-ek rejtve maradnak.

#### LC-018 — Learning Center frontend tesztcsomag

- **Állapot:** planned
- **Prioritás:** P1
- **Kategória:** Learning Center
- **Célverzió:** Learning Center v1.0
- **Összefoglaló:** Admin editorok, help drawer, assistance selector és progress
  publikus Vue-szerződéseinek tesztje.
- **Indoklás:** A komplex form- és permission-viselkedés regresszióveszélyes.
- **Scope:** Prop/emit, Inertia request, validation, permission, empty/loading és
  locale állapotok.
- **Scope-on kívül:** PrimeVue belső DOM és backend üzleti logika.
- **Függőségek:** LC-009, LC-010, LC-011, LC-012, LC-013, LC-014, LC-015, LC-016.
- **Elfogadási feltételek:** Minden új interaktív komponens fő szerződése és
  negatív permission esete tesztelt; teljes Vitest suite zöld.
- **Tesztelési követelmények:** `npm test`, coverage baseline összevetés és i18n
  check.
- **Kapcsolódó fájlok és dokumentáció:** `tests/frontend/`,
  `docs/frontend-testing.md`, `vitest.config.js`.
- **Becsült méret:** L
- **Kockázat:** Túl részletes DOM-teszt törékennyé teszi a csomagot.

#### LC-019 — Learning Center v1.0 E2E referenciafolyamat

- **Állapot:** planned
- **Prioritás:** P1
- **Kategória:** Learning Center
- **Célverzió:** Learning Center v1.0
- **Összefoglaló:** Admin publikálás és felhasználói tanulás teljes böngészős
  igazolása.
- **Indoklás:** A rétegek közötti működés csak E2E-ben bizonyítható.
- **Scope:** Knowledge Unit → Lesson → Course publikálás, user megnyitás,
  teljesítés és progressfrissítés.
- **Scope-on kívül:** v1.1 keresés/analitika és v1.2 walkthrough.
- **Függőségek:** LC-017, LC-018.
- **Elfogadási feltételek:** Admin publikál legalább egy unitot és leckét
  tartalmazó kurzust; jogosult user teljesíti; progress frissül; jogosulatlan
  user nem fér hozzá.
- **Tesztelési követelmények:** Chromium E2E, permission-negatív eset,
  accessibility scan és determinisztikus E2E seeder.
- **Kapcsolódó fájlok és dokumentáció:** `tests/e2e/`,
  `database/seeders/E2ETestSeeder.php`, `docs/e2e-testing.md`.
- **Becsült méret:** M
- **Kockázat:** Túl sok seedelt tartalom lassíthatja és instabillá teheti a tesztet.

### Document Intelligence és OCR

#### OCR-001 — Reprezentatív OCR POC-korpusz és mérési protokoll

- **Állapot:** blocked — blokkoló ok: nincs jóváhagyott, anonimizált
  reprezentatív dokumentumkorpusz; feloldás: domain owner biztosítja és
  adatvédelmileg jóváhagyja a mintákat.
- **Prioritás:** P1
- **Kategória:** Document Intelligence és OCR
- **Célverzió:** Document Intelligence v1.0
- **Összefoglaló:** Mérhető POC inputkészlet szállítólevél, tanúsítvány és
  minőségügyi dokumentum kategóriákból.
- **Indoklás:** Valódi OCR backend nem választható filename-heurisztika vagy
  `.txt` stub alapján.
- **Scope:** Anonimizált fájlok, ground truth, nyelv, minőségkategória,
  pontosság/idő/hiba metrika.
- **Scope-on kívül:** Production dokumentumok kontroll nélküli másolása.
- **Függőségek:** Nincs.
- **Elfogadási feltételek:** Legalább három jóváhagyott dokumentumtípushoz
  ground truth és mérési script/protokoll tartozik; érzékeny adat nincs.
- **Tesztelési követelmények:** Korpusz checksum, hozzáférés-, anonimizálás- és
  ground-truth mintavételes review.
- **Kapcsolódó fájlok és dokumentáció:** `docs/ai/ocr-adapter.md`,
  `docs/ai/document-intelligence.md`, `.kiro/steering/manufacturing-ai.md`.
- **Becsült méret:** M
- **Kockázat:** Nem reprezentatív korpusz hamis technológiaválasztást eredményez.

#### OCR-002 — Tesseract alkalmassági mérés

- **Állapot:** planned
- **Prioritás:** P1
- **Kategória:** Document Intelligence és OCR
- **Célverzió:** Document Intelligence v1.0
- **Összefoglaló:** Tesseract POC pontosság-, nyelv-, teljesítmény- és
  üzemeltetési értékelése.
- **Indoklás:** A dokumentáció Tesseractot valószínű első backendként nevezi meg,
  implementáció azonban nincs.
- **Scope:** Lokális sandbox, HU/EN, PDF/kép, confidence, idő/memória és hibamódok.
- **Scope-on kívül:** Production bekapcsolás és adapter véglegesítése.
- **Függőségek:** OCR-001.
- **Elfogadási feltételek:** A teljes korpusz mért; elfogadási küszöb és
  go/no-go döntés indokolt; alternatíva csak sikertelen mérés esetén vizsgált.
- **Tesztelési követelmények:** Ismételhető benchmark legalább két futással és
  hibás/nem támogatott fájlokkal.
- **Kapcsolódó fájlok és dokumentáció:** `docs/ai/ocr-adapter.md`,
  `python/adapters/ocr_backends/base.py`, `python/README.md`.
- **Becsült méret:** M
- **Kockázat:** Natív függőség és nyelvi modell környezetenként eltérhet.

#### OCR-003 — Valódi OCR backend adapter implementációja

- **Állapot:** planned
- **Prioritás:** P1
- **Kategória:** Document Intelligence és OCR
- **Célverzió:** Document Intelligence v1.0
- **Összefoglaló:** A kiválasztott engine illesztése a meglévő stabil JSON és
  plugin szerződéshez.
- **Indoklás:** Jelenleg csak stub backend és plain-text fallback létezik.
- **Scope:** Backend modul, registry, normalizált text/page/language/confidence és
  strukturált hibák.
- **Scope-on kívül:** Laravel üzleti szabály vagy adatbázis-hozzáférés Pythonból.
- **Függőségek:** OCR-002.
- **Elfogadási feltételek:** PDF/kép korpuszon valós OCR fut; a Laravel-facing
  JSON változatlan; ismeretlen/hiányzó backend biztonságosan hibázik.
- **Tesztelési követelmények:** Python adapter contract, Laravel pipeline,
  korpusz-integráció és backend-unavailable regresszió.
- **Kapcsolódó fájlok és dokumentáció:** `python/adapters/ocr.py`,
  `python/adapters/ocr_backends/`, `docs/ai/ocr-adapter.md`.
- **Becsült méret:** L
- **Kockázat:** Natív processz erőforrás-kimerülést vagy rossz encodingot okozhat.

#### OCR-004 — OCR-konfiguráció és capability validation

- **Állapot:** planned
- **Prioritás:** P1
- **Kategória:** Document Intelligence és OCR
- **Célverzió:** Document Intelligence v1.0
- **Összefoglaló:** Environment-, backend-, language- és binary-beállítások
  biztonságos induláskori ellenőrzése.
- **Indoklás:** Az `AI_OCR_ENABLED` és backend név önmagában nem bizonyítja a
  runtime képességet.
- **Scope:** Config schema, binary/language availability, safe defaults és
  diagnosztika titokszivárgás nélkül.
- **Scope-on kívül:** Automatikus rendszerfüggőség-telepítés.
- **Függőségek:** OCR-003.
- **Elfogadási feltételek:** Hibás binary/backend/language egyértelmű, biztonságos
  hibát ad; kikapcsolt OCR nem indít processzt; dokumentált env példa teljes.
- **Tesztelési követelmények:** Missing binary, unknown backend, disabled és
  sikeres capability teszt.
- **Kapcsolódó fájlok és dokumentáció:** `config/ai.php`, `.env.example`,
  `docs/ai/python-ai-engine.md`.
- **Becsült méret:** S
- **Kockázat:** Túl részletes diagnosztika fájlrendszer-információt szivárogtathat.

#### OCR-005 — OCR fájl-, oldal- és erőforráskorlátok

- **Állapot:** planned
- **Prioritás:** P1
- **Kategória:** Document Intelligence és OCR
- **Célverzió:** Document Intelligence v1.0
- **Összefoglaló:** Production korlátok feltöltésre, MIME-ra, oldalszámra,
  pixelméretre, szövegre és erőforrásra.
- **Indoklás:** A jelenlegi stub csak text byte limitet bizonyít; valódi OCR
  ellenséges fájlokat dolgozna fel.
- **Scope:** MIME/magic validation, max size/pages/dimensions, temporary files,
  memória/CPU és cleanup.
- **Scope-on kívül:** Vírusirtó termék kiválasztása.
- **Függőségek:** OCR-003.
- **Elfogadási feltételek:** Minden limit konfigurált és szerveroldali; túllépés
  strukturált hibával, processzindítás vagy maradék temp fájl nélkül zárul.
- **Tesztelési követelmények:** Túlméretes, hibás MIME, page bomb, corrupt és
  valid határérték fixture tesztek.
- **Kapcsolódó fájlok és dokumentáció:** `app/Http/Requests/Admin/StoreDocumentRequest.php`,
  `app/Jobs/AI/ProcessDocumentJob.php`, `docs/ai/document-intelligence.md`.
- **Becsült méret:** M
- **Kockázat:** Elégtelen korlát denial-of-service kockázat.

#### OCR-006 — Queue timeout, retry és idempotencia production hardening

- **Állapot:** planned
- **Prioritás:** P1
- **Kategória:** Document Intelligence és OCR
- **Célverzió:** Document Intelligence v1.0
- **Összefoglaló:** A meglévő queue/retry alap production értékeinek és
  idempotenciájának igazolása valós OCR-rel.
- **Indoklás:** A stub gyors; valós OCR idő- és erőforrásprofilja eltér.
- **Scope:** Job timeout/tries/backoff, retry_after összhang, duplicate dispatch,
  safe failure és after-commit.
- **Scope-on kívül:** Queue backend teljes cseréje mérés nélkül.
- **Függőségek:** OCR-003, OCR-005, OPS-001.
- **Elfogadási feltételek:** Timeout után nincs részleges üzleti állapot;
  ismételt job nem duplikál telemetryt vagy eredményt; értékek benchmarkoltak.
- **Tesztelési követelmények:** Timeout, crash, retry success, duplicate dispatch
  és worker-kill integrációs tesztek.
- **Kapcsolódó fájlok és dokumentáció:** `app/Jobs/AI/ProcessDocumentJob.php`,
  `config/queue.php`, `docs/ai/document-intelligence.md`.
- **Becsült méret:** M
- **Kockázat:** `retry_after`/timeout eltérés párhuzamos duplikált feldolgozást okoz.

#### OCR-007 — AI processing telemetry admin dashboard

- **Állapot:** planned
- **Prioritás:** P2
- **Kategória:** Document Intelligence és OCR
- **Célverzió:** Document Intelligence v1.0
- **Összefoglaló:** Aggregált futási státusz-, backend-, confidence-, idő- és
  hibakód monitoring nézet.
- **Indoklás:** A telemetry adatmodell kész, a dashboard dokumentált jövőbeni cél.
- **Scope:** Filter, lapozás, summary KPI, retention jelzés és jogosultság.
- **Scope-on kívül:** Raw OCR text vagy dokumentumtartalom megjelenítése.
- **Függőségek:** OCR-006.
- **Elfogadási feltételek:** Jogosult admin szűr státusz/backend/idő szerint;
  dashboard aggregált; raw content és secret nem jelenik meg; nagy lista lapozott.
- **Tesztelési követelmények:** Repository query, permission, no-raw-content,
  frontend filter és empty state tesztek.
- **Kapcsolódó fájlok és dokumentáció:** `app/Models/AiProcessingRun.php`,
  `docs/ai/ai-processing-telemetry.md`,
  `app/Services/AI/AiProcessingTelemetryService.php`.
- **Becsült méret:** L
- **Kockázat:** Telemetry kontroll nélkül személyes vagy dokumentumadatot fedhet fel.

#### OCR-008 — Valódi dokumentumosztályozási baseline

- **Állapot:** planned
- **Prioritás:** P2
- **Kategória:** Document Intelligence és OCR
- **Célverzió:** Document Intelligence v1.0
- **Összefoglaló:** Filename-heurisztika helyett mért, strukturált klasszifikációs
  POC a jóváhagyott kategóriákra.
- **Indoklás:** A jelenlegi classifier stub nem üzleti pontosságú.
- **Scope:** Címkekészlet, ground truth, baseline módszer, precision/recall,
  unknown kategória és verzió.
- **Scope-on kívül:** Automatikus üzleti adatmutáció.
- **Függőségek:** OCR-001.
- **Elfogadási feltételek:** Jóváhagyott címkék és mérőszámok; reprodukálható
  baseline; bizonytalan dokumentum `other`/review irányba kerül.
- **Tesztelési követelmények:** Train/test szétválasztás, confusion matrix,
  unknown és adversarial filename kontroll.
- **Kapcsolódó fájlok és dokumentáció:** `python/ai_engine.py`,
  `docs/ai/document-intelligence.md`, `tests/Feature/DocumentIntelligencePipelineTest.php`.
- **Becsült méret:** L
- **Kockázat:** Kis vagy torz korpusz túlbecsüli a valós pontosságot.

#### OCR-009 — Confidence és review-küszöb policy

- **Állapot:** planned
- **Prioritás:** P1
- **Kategória:** Document Intelligence és OCR
- **Célverzió:** Document Intelligence v1.0
- **Összefoglaló:** Completed, review-required és failed döntési határok
  dokumentált kalibrációja.
- **Indoklás:** Jelenleg technikai küszöbviselkedés tesztelt, de valós modellre
  nincs üzleti policy.
- **Scope:** Feladattípusonkénti threshold, magyarázat, override és verziózás.
- **Scope-on kívül:** AI által végrehajtott jóváhagyás.
- **Függőségek:** OCR-008.
- **Elfogadási feltételek:** Küszöbök mért adaton alapulnak; közepes confidence
  review-t kér; alacsony confidence nem ír üzleti mezőt; policy verziózott.
- **Tesztelési követelmények:** Határérték alatti/egyenlő/feletti és missing
  confidence tesztek.
- **Kapcsolódó fájlok és dokumentáció:** `app/Enums/AiProcessingRunStatus.php`,
  `docs/ai/document-intelligence.md`,
  `tests/Feature/DocumentIntelligencePipelineTest.php`.
- **Becsült méret:** M
- **Kockázat:** Rossz küszöb túl sok manuális munkát vagy téves elfogadást okoz.

#### OCR-010 — Emberi felülvizsgálati workflow és admin UI

- **Állapot:** planned
- **Prioritás:** P2
- **Kategória:** Document Intelligence és OCR
- **Célverzió:** Document Intelligence v1.0
- **Összefoglaló:** Review-required eredmény megtekintése, elfogadása vagy
  elutasítása jogosult felhasználóval.
- **Indoklás:** Az AI csak tanácsadó; low/medium confidence emberi döntést igényel.
- **Scope:** Review queue, eredmény/provenance, döntés, megjegyzés és státusz.
- **Scope-on kívül:** AI-javaslat közvetlen készlet-, quality- vagy rendelésmódosítása.
- **Függőségek:** OCR-009.
- **Elfogadási feltételek:** Jogosult reviewer dönthet; döntés előtt üzleti adat
  nem változik; ismételt döntés blokkolt; provenance látható raw secret nélkül.
- **Tesztelési követelmények:** Policy, lifecycle, concurrent review, Vue UI és
  E2E review flow.
- **Kapcsolódó fájlok és dokumentáció:** `docs/ai/document-intelligence.md`,
  `.kiro/decisions/0005-document-ai.md`, `app/Policies/DocumentPolicy.php`.
- **Becsült méret:** L
- **Kockázat:** Review megkerülése megsértheti az audit- és felelősségi határt.

#### OCR-011 — Document AI adatvédelmi és biztonsági review

- **Állapot:** planned
- **Prioritás:** P1
- **Kategória:** Document Intelligence és OCR
- **Célverzió:** Document Intelligence v1.0
- **Összefoglaló:** Fájlbizalom, process isolation, logging, retention és
  hozzáférés teljes threat review-ja.
- **Indoklás:** Valós dokumentum és natív OCR új támadási felületet hoz.
- **Scope:** Upload, temp file, command arguments, telemetry, raw output, log,
  deletion és dependency provenance.
- **Scope-on kívül:** Külső security minősítés garantálása.
- **Függőségek:** OCR-003, OCR-005, OCR-008.
- **Elfogadási feltételek:** Threat model és security checklist kész; high
  finding nulla vagy blokkoló backlog; raw text nincs telemetryben/logban.
- **Tesztelési követelmények:** Malformed file, path injection, secret logging,
  permission és retention ellenőrzés.
- **Kapcsolódó fájlok és dokumentáció:** `.kiro/checklists/security.md`,
  `.kiro/steering/manufacturing-ai.md`, `docs/ai/ocr-adapter.md`.
- **Becsült méret:** M
- **Kockázat:** Natív parser sebezhetőség dokumentumon keresztül kihasználható.

#### OCR-012 — AI review audit trail és traceability integráció

- **Állapot:** planned
- **Prioritás:** P1
- **Kategória:** Document Intelligence és OCR
- **Célverzió:** Document Intelligence v1.0
- **Összefoglaló:** AI futás, modell/backend verzió, emberi döntés és üzleti
  következmény összekapcsolt auditnyoma.
- **Indoklás:** A telemetry nem helyettesíti az üzleti auditnaplót.
- **Scope:** Correlation ID, reviewer/döntés/idő, document version és safe summary.
- **Scope-on kívül:** Raw OCR text másolása activity logba.
- **Függőségek:** OCR-010, OCR-011.
- **Elfogadási feltételek:** Egy review eseményből visszakereshető a dokumentum,
  AI run, verzió és reviewer; log nem tartalmaz raw tartalmat; törlés szabályozott.
- **Tesztelési követelmények:** End-to-end trace query, no-raw-content assertion,
  permission és audit immutability teszt.
- **Kapcsolódó fájlok és dokumentáció:** `app/Services/AuditLogService.php`,
  `app/Models/AiProcessingRun.php`, `docs/ai/ai-processing-telemetry.md`.
- **Becsült méret:** M
- **Kockázat:** Hiányos correlation miatt auditnál nem bizonyítható a döntés eredete.

### Manufacturing Intelligence

A jelenlegi szabályalapú bottleneck-, forecast-, risk-, quality- és supplier
elemzések lezártak. Az alábbi tételek kizárólag a termékvízió későbbi,
prediktív/adaptív szintjét fedik.

#### MI-001 — Prediktív adatalkalmassági és governance vizsgálat

- **Állapot:** planned
- **Prioritás:** P3
- **Kategória:** Manufacturing Intelligence
- **Célverzió:** Manufacturing Intelligence v2
- **Összefoglaló:** Annak mérése, hogy van-e elég teljes, címkézett és torzítatlan
  történeti adat prediktív modellekhez.
- **Indoklás:** Modellfejlesztés megbízható manufacturing memory nélkül korai.
- **Scope:** Adatmennyiség, hiány, label, drift, retention, hozzáférés és
  magyarázhatóság.
- **Scope-on kívül:** Modell implementálása.
- **Függőségek:** Nincs.
- **Elfogadási feltételek:** Domainenként go/no-go és hiánylista; minimum
  adathorizont és quality threshold jóváhagyott; személyes adatkezelés tisztázott.
- **Tesztelési követelmények:** Reprodukálható profiling query-k és mintavételes
  domain-validáció.
- **Kapcsolódó fájlok és dokumentáció:** `docs/vision/manufacturing-intelligence-platform.md`,
  `.kiro/knowledge/artificial-intelligence.md`.
- **Becsült méret:** L
- **Kockázat:** Torz vagy kevés adat megtévesztő predikciót ad.

#### MI-002 — Prediktív késési és hiánykockázati modell POC

- **Állapot:** planned
- **Prioritás:** P3
- **Kategória:** Manufacturing Intelligence
- **Célverzió:** Manufacturing Intelligence v2
- **Összefoglaló:** A szabályalapú kockázatok mért prediktív baseline-nal való
  összehasonlítása.
- **Indoklás:** A Phase 3 célja a korábbi kockázatészlelés, nem a szabályok kiváltása.
- **Scope:** Offline modell, baseline comparison, calibration, explanation és
  shadow output.
- **Scope-on kívül:** Automatikus ütemezési vagy beszerzési művelet.
- **Függőségek:** MI-001.
- **Elfogadási feltételek:** Előre rögzített metrikán mérve jobb vagy elvetett;
  output magyarázható és csak javaslat; emberi jóváhagyás határa dokumentált.
- **Tesztelési követelmények:** Időalapú holdout, leakage check, calibration és
  szabályalapú baseline összevetés.
- **Kapcsolódó fájlok és dokumentáció:** `app/Services/Admin/ProductionRiskService.php`,
  `app/Services/Admin/MaterialForecastService.php`,
  `docs/vision/manufacturing-intelligence-platform.md`.
- **Becsült méret:** L
- **Kockázat:** Data leakage irreálisan jó offline eredményt okozhat.

#### MI-003 — Kapacitásoptimalizációs javaslat POC

- **Állapot:** planned
- **Prioritás:** P3
- **Kategória:** Manufacturing Intelligence
- **Célverzió:** Manufacturing Intelligence v2
- **Összefoglaló:** What-if eredményekből magyarázható, nem végrehajtó
  ütemezési javaslatok.
- **Indoklás:** A jelenlegi kapacitásszimuláció determinisztikus alapot ad.
- **Scope:** Célfüggvény, constraint, alternatívák, magyarázat és human approval.
- **Scope-on kívül:** Autonóm production schedule módosítás.
- **Függőségek:** MI-001.
- **Elfogadási feltételek:** Reprezentatív scenariohoz legalább két megvalósítható
  alternatíva, constraint-sértés nélkül; javaslat nem ír adatot.
- **Tesztelési követelmények:** Constraint, infeasible, determinism és auditált
  acceptance/rejection tesztek.
- **Kapcsolódó fájlok és dokumentáció:** `app/Services/Admin/SchedulingService.php`,
  `app/Services/Admin/CapacityPlanningService.php`,
  `docs/vision/manufacturing-intelligence-platform.md`.
- **Becsült méret:** L
- **Kockázat:** Hiányos constraint gyárthatatlan tervet javasolhat.

#### MI-004 — Manufacturing Copilot discovery és biztonsági szerződés

- **Állapot:** planned
- **Prioritás:** P3
- **Kategória:** Manufacturing Intelligence
- **Célverzió:** Manufacturing Intelligence v2
- **Összefoglaló:** Engedélyezett kérdések, retrieval, jogosultság és
  auditálhatóság specifikációja.
- **Indoklás:** A Copilot termékvízió, de runtime követelmény és biztonsági modell nincs.
- **Scope:** Read-only use case-ek, permission-scoped context, citation,
  uncertainty és prompt-injection határ.
- **Scope-on kívül:** Chat UI vagy modellintegráció implementálása.
- **Függőségek:** MI-001.
- **Elfogadási feltételek:** Jóváhagyott use case/non-goal lista; minden válasz
  forráshoz köthető; üzleti mutáció tiltott; threat model elkészült.
- **Tesztelési követelmények:** Jogosultsági context matrix, hallucination és
  prompt-injection tesztterv review.
- **Kapcsolódó fájlok és dokumentáció:** `docs/vision/manufacturing-intelligence-platform.md`,
  `.kiro/steering/manufacturing-ai.md`.
- **Becsült méret:** M
- **Kockázat:** Jogosulatlan context vagy hallucinált workflow súlyos üzleti hibát okoz.

#### MI-005 — Képalapú minőségellenőrzés alkalmassági POC

- **Állapot:** planned
- **Prioritás:** P3
- **Kategória:** Manufacturing Intelligence
- **Célverzió:** Manufacturing Intelligence v2
- **Összefoglaló:** Egyetlen jóváhagyott defect use case adat- és modellalkalmassága.
- **Indoklás:** Vision inspection a hosszú távú vízió része, implementáció nincs.
- **Scope:** Defect definíció, képminőség, címkézés, baseline és human review.
- **Scope-on kívül:** Minőségellenőrzési döntés automatikus felülírása.
- **Függőségek:** MI-001, OCR-011.
- **Elfogadási feltételek:** Egy use case-re címkézési és mérési protokoll,
  go/no-go eredmény és false-negative tolerancia; AI csak javasol.
- **Tesztelési követelmények:** Label agreement, holdout, lighting/device
  robustness és reviewer override terv.
- **Kapcsolódó fájlok és dokumentáció:** `docs/vision/manufacturing-intelligence-platform.md`,
  `.kiro/steering/manufacturing-ai.md`, `.kiro/decisions/0003-quality-control.md`.
- **Becsült méret:** L
- **Kockázat:** False negative hibás termék elfogadását ösztönözheti.

### Üzemeltetés

#### OPS-001 — Queue konfiguráció és worker lifecycle audit

- **Állapot:** ready
- **Prioritás:** P1
- **Kategória:** Üzemeltetés
- **Célverzió:** v1.x Stabilizálás
- **Összefoglaló:** Database queue, workerindítás, restart, retry_after,
  after-commit és kapacitás production review-ja.
- **Indoklás:** Queue és AI job létezik, de production workerfolyamat nincs
  dokumentálva.
- **Scope:** Connection, queue neve, process manager, timeout, deploy restart és
  graceful shutdown.
- **Scope-on kívül:** Queue backend cseréje mérés nélkül.
- **Függőségek:** Nincs.
- **Elfogadási feltételek:** Dokumentált worker command és supervisor/service
  lifecycle; timeout/retry_after összhang; deploy restart és crash recovery
  próbált.
- **Tesztelési követelmények:** Feldolgozás, retry, restart közbeni job, graceful
  stop és duplicate prevention smoke.
- **Kapcsolódó fájlok és dokumentáció:** `config/queue.php`,
  `app/Jobs/AI/ProcessDocumentJob.php`, `.kiro/checklists/release.md`.
- **Becsült méret:** M
- **Kockázat:** Hibás timeout duplikált vagy elveszettnek látszó jobot okoz.

#### OPS-002 — Scheduler feladat- és futtatási audit

- **Állapot:** ready
- **Prioritás:** P1
- **Kategória:** Üzemeltetés
- **Célverzió:** v1.x Stabilizálás
- **Összefoglaló:** Annak rögzítése, mely karbantartási feladatok igényelnek
  schedulert, és hogyan fut productionben.
- **Indoklás:** A `routes/console.php` csak `inspire` parancsot tartalmaz; a
  release checklist mégis scheduler review-t kér.
- **Scope:** Szükségletlista, schedule:run/work modell, overlap, timezone és
  failure reporting.
- **Scope-on kívül:** Nem indokolt periodikus business job kitalálása.
- **Függőségek:** Nincs.
- **Elfogadási feltételek:** Minden szükséges/indokolatlan task döntése rögzített;
  ha nincs app schedule, ez explicit; production cron/service és timezone
  ellenőrzött.
- **Tesztelési követelmények:** `php artisan schedule:list`, dry-run és overlap
  policy review.
- **Kapcsolódó fájlok és dokumentáció:** `routes/console.php`,
  `.kiro/checklists/release.md`, `docs/deployment.md`.
- **Becsült méret:** S
- **Kockázat:** Hallgatólagosan feltételezett scheduler miatt karbantartás elmarad.

#### OPS-003 — Backup policy, scope, RPO és RTO jóváhagyása

- **Állapot:** ready
- **Prioritás:** P0
- **Kategória:** Üzemeltetés
- **Célverzió:** v1.x Stabilizálás
- **Összefoglaló:** Adatbázis, private dokumentumok, konfiguráció és
  kulcsfüggőségek mentési követelménye.
- **Indoklás:** Nincs projekt-specifikus backup vagy helyreállítási cél.
- **Scope:** Backup scope, gyakoriság, retention, titkosítás, offsite, owner,
  RPO/RTO és törlési policy.
- **Scope-on kívül:** Backup tool implementáció.
- **Függőségek:** Nincs.
- **Elfogadási feltételek:** Domain owner és üzemeltetés jóváhagyja az RPO/RTO-t;
  DB és dokumentum storage konzisztens snapshot követelménye explicit.
- **Tesztelési követelmények:** Tabletop loss scenariók rendelésre, stockra,
  auditlogra és dokumentumverzióra.
- **Kapcsolódó fájlok és dokumentáció:** `docs/deployment.md`,
  `config/filesystems.php`, `.kiro/steering/manufacturing.md`.
- **Becsült méret:** M
- **Kockázat:** Külön időpontú DB/fájl backup megszakítja a dokumentum traceabilityt.

#### OPS-004 — Automatizált és ellenőrzött backup megvalósítása

- **Állapot:** planned
- **Prioritás:** P0
- **Kategória:** Üzemeltetés
- **Célverzió:** v1.x Stabilizálás
- **Összefoglaló:** A jóváhagyott policy szerinti adatbázis- és storage-mentés.
- **Indoklás:** Policy mentés nélkül nem csökkenti az adatvesztési kockázatot.
- **Scope:** Ütemezés, titkosítás, checksum, retention, offsite másolat és
  sikertelenségi riasztás.
- **Scope-on kívül:** Fejlesztői gép projekt-ZIP-jének backupként kezelése.
- **Függőségek:** OPS-003, OPS-002.
- **Elfogadási feltételek:** Automatizált backup a policy szerint fut; artifact
  titkosított és checksummal igazolt; hibás futás riaszt; retention bizonyított.
- **Tesztelési követelmények:** Sikeres és szándékosan hibás backup, checksum és
  retention dry-run elkülönített környezetben.
- **Kapcsolódó fájlok és dokumentáció:** `docs/deployment.md`,
  `config/filesystems.php`.
- **Becsült méret:** L
- **Kockázat:** Hibás secret/retention backupvesztést vagy adatszivárgást okoz.

#### OPS-005 — Izolált restore próba és bizonyíték

- **Állapot:** planned
- **Prioritás:** P0
- **Kategória:** Üzemeltetés
- **Célverzió:** v1.x Stabilizálás
- **Összefoglaló:** Teljes adatbázis- és dokumentumtár-helyreállítás productiontől
  elkülönítve.
- **Indoklás:** Nem tesztelt backup nem tekinthető helyreállíthatónak.
- **Scope:** Restore runbook, időmérés, integritás, user/permission, stock,
  auditlog és document checksum.
- **Scope-on kívül:** Production felülírása próba céljából.
- **Függőségek:** OPS-004.
- **Elfogadási feltételek:** Izolált restore teljesül a jóváhagyott RTO-n belül;
  adatpont megfelel az RPO-nak; referenciarekordok és fájlchecksumok egyeznek.
- **Tesztelési követelmények:** SQL count/constraint, dokumentum download/checksum,
  login/permission és kritikus MES read-only smoke.
- **Kapcsolódó fájlok és dokumentáció:** `docs/deployment.md`,
  `docs/reference/sample-data.md`, `.kiro/checklists/release.md`.
- **Becsült méret:** L
- **Kockázat:** Izolációs hiba production adatot módosíthat; célpont guard kötelező.

#### OPS-006 — Production health-check szerződés

- **Állapot:** ready
- **Prioritás:** P1
- **Kategória:** Üzemeltetés
- **Célverzió:** v1.x Stabilizálás
- **Összefoglaló:** Az alap `/up` liveness route és szükséges readiness jelek
  felelősségének meghatározása.
- **Indoklás:** A Laravel liveness létezik, de DB, storage, queue és build
  readiness nincs projekt-specifikusan definiálva.
- **Scope:** Liveness/readiness szétválasztás, biztonságos response, dependency
  timeout és hozzáférés.
- **Scope-on kívül:** Érzékeny config, stack trace vagy secret health outputban.
- **Függőségek:** Nincs.
- **Elfogadási feltételek:** Dokumentált endpoint/jelelem mátrix; dependency
  hiba megfelelő státuszt ad; response nem fed fel érzékeny adatot.
- **Tesztelési követelmények:** Healthy, DB down, storage unwritable és queue
  degraded contract tesztek.
- **Kapcsolódó fájlok és dokumentáció:** `bootstrap/app.php`, `config/queue.php`,
  `config/filesystems.php`, `docs/deployment.md`.
- **Becsült méret:** M
- **Kockázat:** Túl mély liveness check hibás restart vihart okozhat.

#### OPS-007 — Production monitoring és alerting minimum

- **Állapot:** planned
- **Prioritás:** P1
- **Kategória:** Üzemeltetés
- **Célverzió:** v1.x Stabilizálás
- **Összefoglaló:** Alkalmazás, HTTP, queue, DB, storage és AI-futások minimális
  mérőszám- és riasztáskészlete.
- **Indoklás:** Nincs dokumentált production monitoring vagy on-call jelzés.
- **Scope:** Availability, latency, error rate, queue depth/age, failed jobs,
  disk és backup állapot.
- **Scope-on kívül:** Konkrét vendor kiválasztása jóváhagyás nélkül.
- **Függőségek:** OPS-006, OPS-001, OPS-004.
- **Elfogadási feltételek:** Metrikánként küszöb, owner és riasztási csatorna;
  tesztriasztás célba ér; dashboard nem tartalmaz érzékeny adatot.
- **Tesztelési követelmények:** Synthetic health, queue backlog, failed backup és
  exception riasztási próbák.
- **Kapcsolódó fájlok és dokumentáció:** `config/logging.php`,
  `docs/ai/ai-processing-telemetry.md`, `.kiro/checklists/release.md`.
- **Becsült méret:** L
- **Kockázat:** Rossz küszöb riasztási zajt vagy észrevétlen hibát okoz.

#### OPS-008 — Failed-job operációs folyamat

- **Állapot:** ready
- **Prioritás:** P1
- **Kategória:** Üzemeltetés
- **Célverzió:** v1.x Stabilizálás
- **Összefoglaló:** Failed job észlelés, triage, retry/forget, audit és retention.
- **Indoklás:** `failed_jobs` tárolás létezik, de operátori eljárás nincs.
- **Scope:** Owner, severity, retry előfeltétel, idempotencia, cleanup és alert.
- **Scope-on kívül:** Minden failed job automatikus vak újrapróbálása.
- **Függőségek:** OPS-001.
- **Elfogadási feltételek:** Dokumentált parancsok és döntési fa; retry előtt
  side-effect ellenőrzés; stale failed job retention és riasztás működik.
- **Tesztelési követelmények:** Szándékosan hibázó job, triage, sikeres biztonságos
  retry és nem retry-zandó eset.
- **Kapcsolódó fájlok és dokumentáció:** `config/queue.php`,
  `database/migrations/0001_01_01_000002_create_jobs_table.php`,
  `app/Jobs/AI/ProcessDocumentJob.php`.
- **Becsült méret:** S
- **Kockázat:** Vak retry duplikált feldolgozást vagy állapotváltozást okoz.

#### OPS-009 — Logrotáció, retention és érzékenyadat-policy

- **Állapot:** ready
- **Prioritás:** P1
- **Kategória:** Üzemeltetés
- **Célverzió:** v1.x Stabilizálás
- **Összefoglaló:** Production log channel, rotáció, megőrzés, hozzáférés és
  redaction meghatározása.
- **Indoklás:** Default `stack` a `single` csatornát használja; daily retention
  alapértéke csak egy nap, ha egyáltalán kiválasztott.
- **Scope:** Channel, days/size, centralization, PII/secret/raw OCR tiltás és owner.
- **Scope-on kívül:** Auditlog törlése alkalmazáslog-retention alapján.
- **Függőségek:** Nincs.
- **Elfogadási feltételek:** Production env napi/centralizált rotációt használ;
  retention jóváhagyott; secret/raw document tesztlogban sem jelenik meg.
- **Tesztelési követelmények:** Rotation simulation, permission, redaction és
  disk-growth kontroll.
- **Kapcsolódó fájlok és dokumentáció:** `config/logging.php`,
  `.kiro/steering/security.md`, `docs/ai/ai-processing-telemetry.md`.
- **Becsült méret:** S
- **Kockázat:** Túl rövid retention auditot, túl hosszú retention adatvédelmet sért.

#### OPS-010 — Dokumentumtárolási integritás és retention audit

- **Állapot:** ready
- **Prioritás:** P1
- **Kategória:** Üzemeltetés
- **Célverzió:** v1.x Stabilizálás
- **Összefoglaló:** Private disk, checksum, verzió, orphan, törlés, kapacitás és
  backup kapcsolat felülvizsgálata.
- **Indoklás:** Verziózott dokumentumkezelés kész, production storage policy nincs.
- **Scope:** Visibility, path, checksum verify, orphan detection, quota,
  retention és restore kapcsolat.
- **Scope-on kívül:** S3 migráció automatikus eldöntése.
- **Függőségek:** OPS-003.
- **Elfogadási feltételek:** Nincs public hozzáférés private fájlhoz; orphan és
  hiányzó blob report készül; retention megőrzi az auditált verziókat.
- **Tesztelési követelmények:** Permission/download, checksum, orphan dry-run,
  backup scope és storage-full failure teszt.
- **Kapcsolódó fájlok és dokumentáció:** `config/filesystems.php`,
  `app/Services/Admin/DocumentService.php`, `docs/deployment.md`.
- **Becsült méret:** M
- **Kockázat:** DB/blob eltérés megszakítja a dokumentum traceabilityt.

#### OPS-011 — Disaster recovery runbook és gyakorlat

- **Állapot:** planned
- **Prioritás:** P1
- **Kategória:** Üzemeltetés
- **Célverzió:** v1.x Stabilizálás
- **Összefoglaló:** Szerepkörök, döntési pontok és sorrend alkalmazás-, DB-,
  storage- és queue-incidensre.
- **Indoklás:** Backup és monitoring önmagában nem koordinál helyreállítást.
- **Scope:** Incidensosztály, kommunikáció, fail/restore, integritás, reopen és
  postmortem.
- **Scope-on kívül:** Nem tesztelt automatikus failover.
- **Függőségek:** OPS-005, OPS-007, OPS-008, OPS-009, OPS-010.
- **Elfogadási feltételek:** Runbook ownerrel és elérhetőséggel; tabletop
  gyakorlat teljesül; RPO/RTO és traceability ellenőrzés dokumentált.
- **Tesztelési követelmények:** Éves vagy release előtti tabletop, izolált restore
  evidence és monitoring alert drill.
- **Kapcsolódó fájlok és dokumentáció:** `docs/deployment.md`,
  `.kiro/workflows/hotfix.md`, `.kiro/checklists/release.md`.
- **Becsült méret:** M
- **Kockázat:** Elavult kontakt vagy lépés incidens közben késlelteti a helyreállítást.

### UX és skálázhatóság

A 2026-07-27-i audit nem igazolt külön, jelenleg vállalt UX- vagy
skálázhatósági hibát. A nagy listák lapozása, a sidebar görgetése, mobil smoke
és a megosztott admin CRUD minták implementáltak vagy teszteltek. Új `UX-*`
tétel csak reprodukálható hiba, mérési eredmény vagy jóváhagyott scope alapján
vehető fel; általános „UX javítás” nem backlogelem.

## Lezárt területek

Az alábbi területek jelentős funkcionális és tesztbizonyítékkal rendelkeznek.
Nem kerülnek több száz külön `done` tételként a backlogba:

- felhasználók, szakmai/authorization szerepkörök és permissionök;
- gyártási törzsadatok;
- termékek, alapanyagok, BOM és verziózott műveleti sorrendek;
- vevőrendelések, gyártási tervek, rendelések és feladatok;
- készlet, stock movement, foglalás, hiány és anyagszükséglet;
- beszerzés, áruátvétel és stock posting;
- minőségellenőrzés és auditált állapotátmenetek;
- dokumentumkezelés, verzió, approval és download;
- riportok, kapacitástervezés, scheduling és simulation;
- szabályalapú Manufacturing Intelligence;
- HU/EN lokalizáció és translation audit;
- üzleti eseményalapú cache-invalidation;
- PHPStan level 5 baseline nélkül;
- SQLite/MySQL backend quality-gate infrastruktúra;
- frontend unit-, accessibility-, cross-browser és Playwright infrastruktúra;
- Learning Center Knowledge Unit/Graph/Course fogalmi specifikációs alap;
- Python AI Engine JSON-határ, queue pipeline, OCR plugin boundary és telemetry
  alap — ezek nem azonosak valódi OCR-rel vagy klasszifikációval.

## Nem vállalt ötletek

A `docs/specifications/learning-center/future-ideas.md` kifejezetten
ötlet-inkubátor, nem backlog. Az alábbiak ezért jelenleg nem aktív tételek:

- mentor és instruktor mód;
- vállalati tudásbázis-integráció;
- AI coach és AI hibakereső;
- hangalapú útmutató;
- tudásszint heatmap és tanulási idővonal;
- sandbox tanulási mód;
- teljesítési tanúsítványok;
- adaptív tanulás és automatikus asszisztenciaszint-váltás;
- screenshot/video/walkthrough és tudásellenőrzés a Learning Center v1.2
  scope-zárásáig;
- Learning Center v1.1 keresés, analitika és content review részletes feladatai
  a v1.0 lezárásáig.

Ezek csak külön scope-jóváhagyás, célverzió és mérhető elfogadási feltétel után
kerülhetnek az aktív backlogba.
