# KM_Production aktuális végrehajtási terv

## Dokumentumadatok

- Baseline: 2026-07-28
- Forrás: `docs/project-management/backlog.md`
- Hatókör: a következő tíz végrehajtható projektfeladat
- Frissítési szabály: feladat lezárásakor, blokkolásakor vagy prioritásváltásakor

Ez a dokumentum nem ismétli meg a teljes backlogot. A részletes scope,
kockázat és tesztkövetelmény a hivatkozott backlogelemnél található.

## Lezárt governance-előfeltételek

### GOV-002 — Az origin alapértelmezett ágának átállítása `main` ágra

- **Állapot:** done
- **Eredmény:** a GitHub szerveroldali HEAD-je `main`, a helyi szimbolikus
  referencia `origin/HEAD -> origin/main`, és a `main` az `origin/main` ágat
  követi.

### GOV-004 — Commitüzenet-konvenció elfogadása

- **Állapot:** done
- **Eredmény:** az elsődleges szabályzat, az emberi és AI-agent hivatkozások,
  valamint az automatizálási döntés dokumentált.

### GOV-006 — Pull request sablon létrehozása

- **Állapot:** done
- **Eredmény:** az általános PR-sablon, code review és merge-folyamat,
  required-check javaslat, valamint az AI-agent PR-szabályok dokumentáltak.

### GOV-008 — Projekt Definition of Done bevezetése

- **Állapot:** done
- **Eredmény:** az elsődleges, változástípushoz igazodó DoD, a bizonyíték- és
  kivételszabályok, valamint a backlog-, PR-, merge- és release-hivatkozások
  dokumentáltak és hét mintafeladattal ellenőrzöttek.

### CI-001 — A Vitest worker-timeout okának reprodukálása

- **Állapot:** done
- **Eredmény:** a 4 workeres `forks` profil 914,6 MB-os peak Node working
  setjéhez kötött erőforrás-verseny bizonyított; a két workeres, továbbra is
  izolált és fájlpárhuzamos konfiguráció stabilitási és teljesítményevidence-et
  kapott.

### CI-003 — Backend MySQL és migrációs quality gate stabilizálása

- **Állapot:** done
- **Eredmény:** a helyi SQLite/MySQL suite-ok és migration round-trip
  ismételten zöldek; a Linux Vite-, Inertia page-path és PHPStan
  friss-checkout hibák javítva. A GitHub Actions
  [30428224526](https://github.com/KovacsZoltan65/KM_Production/actions/runs/30428224526)
  futásának mind a négy backend jobja sikeres.

### AUD-001 — Rekordállapot-alapú activity log bevezetése

- **Állapot:** done
- **Eredmény:** A service-alapú Spatie naplózás create állapotot, dirty-only
  update diffet és explicit User role-diffet rögzít; az érzékeny mezők
  allowlisttel és központi kizárással védettek. A célzott SQLite/MySQL, a teljes
  SQLite 3/3 és MySQL 2/2 backend gate sikeres.

A lezárt elemek nem részei az alábbi tíz végrehajtási lépésnek.

## Végrehajtási sorrend

### 1. CI-002 — Frontend unit, i18n és build quality gate stabilizálása

- **Állapot:** review
- **Prioritás / méret:** P1 / S
- **Miért most:** a három külön Node 24 céljob már sikeres, de a teljes
  frontend workflow-t a külön dependency audit hibája pirosan tartja, és a
  production auditlépés kimarad.
- **Előfeltétel:** CI-001.
- **Kész definíciója:** a frontend workflow Node 24-en zöld, a unit, i18n és
  build eredménye külön azonosítható; a stabil Vitest workerszám és tesztszám
  változatlan.
- **Következő lépés:** a `CI-007` alatt az `npm audit` finding és a két audit
  független kiértékelésének rendezése, majd a teljes workflow újraellenőrzése.

### 2. CI-004 — A teljes Playwright E2E-kapu aktuális futtatása

- **Prioritás / méret:** P1 / M
- **Miért most:** a helyi Chromium, accessibility, keyboard, WebKit és mobile
  készlet stabilizálva; a Linux GitHub Actions igazolás és run URL még hiányzik.
- **Előfeltétel:** izolált `.env.e2e`, SQLite adatbázis, E2E filesystem és
  telepített Playwright böngészők.
- **Kész definíciója:** minden konfigurált Playwright projekt lefut; eltérés
  esetén artifact és dokumentált környezeti kivétel készül, tiltott teszt nincs.
- **Aktuális állapot:** partially done; részletes evidence:
  `docs/audits/playwright-e2e-quality-gate-2026-07-29.md`.

### 3. CI-005 — A GitHub Actions quality gate és required check mátrix auditja

- **Prioritás / méret:** P1 / S
- **Miért most:** a workflow-k jelen vannak, de nincs repository-szintű
  bizonyíték arról, hogy valamennyi release-kritikus job kötelező merge-feltétel.
- **Előfeltétel:** CI-002, CI-003 és CI-004.
- **Kész definíciója:** dokumentált job/trigger/required-check mátrix készül,
  és egyetlen P1 quality gate sem kerülhető meg normál PR-merge során.

### 4. GOV-005 — Branch protection és required check szabályok bevezetése

- **Prioritás / méret:** P1 / S
- **Miért most:** a release-ág védelme csak az elfogadott PR-, DoD- és
  quality-gate szabályok ismeretében állítható be pontosan.
- **Előfeltétel:** GOV-006, GOV-008 és CI-005.
- **Kész definíciója:** a `main` ágon kötelező PR-review és required check
  szabályok élnek, a force push és a közvetlen törlés tiltott, az admin bypass
  szabály és a teszt-PR eredménye dokumentált.

### 5. CI-006 — Composer security audit release-kapu igazolása

- **Prioritás / méret:** P1 / S
- **Miért most:** a backend workflow jelenleg nem futtat Composer security
  auditot, ezért a PHP dependency-kockázat nem igazolt release-kapu.
- **Előfeltétel:** nincs.
- **Kész definíciója:** a `composer audit` exit code-ja és riportja rögzített; a
  kritikus/magas találat blokkol vagy dokumentált kivétellel rendelkezik; a
  CI-be emelésről végrehajtható döntés született.

### 6. CI-007 — npm security audit release-kapu felülvizsgálata

- **Prioritás / méret:** P1 / S
- **Miért most:** a workflow futtat teljes és production npm auditot, de a
  findingek kivétel- és triage-szabálya nincs központilag dokumentálva.
- **Előfeltétel:** nincs.
- **Kész definíciója:** mindkét audit eredménye rögzített; minden findinghez
  owner, döntés és határidő tartozik; production high/critical találat blokkol.

### 7. CI-009 — Egységes release gate és evidence-csomag

- **Prioritás / méret:** P1 / M
- **Miért most:** a release-kapuk és bizonyítékok több workflow-ban és
  dokumentumban szétszórva vannak.
- **Előfeltétel:** CI-002, CI-003, CI-004, CI-005, CI-006, CI-007 és CI-010.
- **Kész definíciója:** egy verziójelölt evidence-csomagja tartalmaz minden
  backend-, frontend-, security-, E2E-, migráció- és rollbackeredményt; hiányzó
  kapu mellett nincs release.

### 8. OPS-003 — Backup policy, scope, RPO és RTO jóváhagyása

- **Prioritás / méret:** P0 / M
- **Miért most:** a rendszer gyártási, készlet-, audit- és privát
  dokumentumadataihoz nincs jóváhagyott helyreállítási cél.
- **Előfeltétel:** nincs.
- **Kész definíciója:** a domain owner és az üzemeltetés jóváhagyja a backup
  scope-ot, RPO/RTO-t, retentiont és felelőst; a DB és dokumentum storage
  konzisztens snapshot követelménye, valamint a tabletop loss scenariók
  eredménye dokumentált.

### 9. LC-001 — Learning Center v1.0 scope lezárása

- **Prioritás / méret:** P1 / M
- **Miért most:** a v1.0 adatmodellje, jogosultsága és UI-ja csak jóváhagyott
  kötelező képességekre és nem célokra építhető.
- **Előfeltétel:** nincs.
- **Kész definíciója:** a v1.0 use case-ek, szerepkörök, első támogatott
  oldalak, kontextuális súgó és mérhető sikerkritériumok jóváhagyottak; a nem
  célok és nyitott döntések explicit listában szerepelnek.

### 10. OPS-001 — Queue konfiguráció és worker lifecycle audit

- **Prioritás / méret:** P1 / M
- **Miért most:** database queue és AI job létezik, de nincs dokumentált
  production workerindítás, deploy restart vagy crash recovery folyamat.
- **Előfeltétel:** nincs.
- **Kész definíciója:** a worker command, process manager lifecycle,
  timeout/retry_after összhang, graceful shutdown, deploy restart és
  duplicate prevention smoke dokumentált és próbált.

## Következő frissítés

A lista első elemeinek lezárása után a következő jelöltek:

- `OPS-002` scheduler feladat- és futtatási audit.
