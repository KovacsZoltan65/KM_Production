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

A lezárt elemek nem részei az alábbi tíz végrehajtási lépésnek.

## Végrehajtási sorrend

### 1. CI-001 — A Vitest worker-timeout okának reprodukálása

- **Prioritás / méret:** P1 / S
- **Miért most:** az alapértelmezett `npm test` 7 worker-timeouttal hibázott,
  miközben egyszálas futásban 166/166 teszt sikeres.
- **Előfeltétel:** a baseline gép Node/npm verziójának és erőforrásadatainak
  rögzítése.
- **Kész definíciója:** legalább három kontrollált futásból származó bizonyíték
  azonosítja a worker-, pool-, erőforrás- vagy környezeti okot, és kizárja a
  tesztlogikai regressziót.

### 2. CI-002 — Az alapértelmezett Vitest-konfiguráció stabilizálása

- **Prioritás / méret:** P1 / S
- **Miért most:** a fejlesztői alapértelmezett parancsnak megbízható quality
  gate-ként kell működnie.
- **Előfeltétel:** CI-001.
- **Kész definíciója:** az `npm test` három egymást követő Windows-futtatásban
  20/20 fájllal és 166/166 teszttel zárul worker-timeout nélkül; a Linux CI is
  zöld.

### 3. CI-003 — A teljes MySQL quality gate aktuális futtatása

- **Prioritás / méret:** P1 / S
- **Miért most:** a workflow létezik, de a 2026-07-27-i audit nem adott
  aktuális helyi MySQL-bizonyítékot.
- **Előfeltétel:** kizárólag dedikált `km_production_testing` adatbázis és a
  test-environment guard sikeres ellenőrzése.
- **Kész definíciója:** a MySQL tesztsuite, migráció round-trip és kétszeri
  alapseeder smoke sikeres, a charset/collation/sql-mode adatok rögzítettek.

### 4. CI-004 — A teljes Playwright E2E-kapu aktuális futtatása

- **Prioritás / méret:** P1 / M
- **Miért most:** az E2E-infrastruktúra elkészült, de az audit során a teljes
  Chromium, accessibility, keyboard, cross-browser és mobile készlet nem
  futott.
- **Előfeltétel:** izolált `.env.e2e`, SQLite adatbázis, E2E filesystem és
  telepített Playwright böngészők.
- **Kész definíciója:** minden konfigurált Playwright projekt lefut; eltérés
  esetén artifact és dokumentált környezeti kivétel készül, tiltott teszt nincs.

### 5. CI-005 — A GitHub Actions quality gate és required check mátrix auditja

- **Prioritás / méret:** P1 / S
- **Miért most:** a workflow-k jelen vannak, de nincs repository-szintű
  bizonyíték arról, hogy valamennyi release-kritikus job kötelező merge-feltétel.
- **Előfeltétel:** CI-002, CI-003 és CI-004.
- **Kész definíciója:** dokumentált job/trigger/required-check mátrix készül,
  és egyetlen P1 quality gate sem kerülhető meg normál PR-merge során.

### 6. GOV-005 — Branch protection és required check szabályok bevezetése

- **Prioritás / méret:** P1 / S
- **Miért most:** a release-ág védelme csak az elfogadott PR-, DoD- és
  quality-gate szabályok ismeretében állítható be pontosan.
- **Előfeltétel:** GOV-006, GOV-008 és CI-005.
- **Kész definíciója:** a `main` ágon kötelező PR-review és required check
  szabályok élnek, a force push és a közvetlen törlés tiltott, az admin bypass
  szabály és a teszt-PR eredménye dokumentált.

### 7. CI-006 — Composer security audit release-kapu igazolása

- **Prioritás / méret:** P1 / S
- **Miért most:** a backend workflow jelenleg nem futtat Composer security
  auditot, ezért a PHP dependency-kockázat nem igazolt release-kapu.
- **Előfeltétel:** nincs.
- **Kész definíciója:** a `composer audit` exit code-ja és riportja rögzített; a
  kritikus/magas találat blokkol vagy dokumentált kivétellel rendelkezik; a
  CI-be emelésről végrehajtható döntés született.

### 8. CI-007 — npm security audit release-kapu felülvizsgálata

- **Prioritás / méret:** P1 / S
- **Miért most:** a workflow futtat teljes és production npm auditot, de a
  findingek kivétel- és triage-szabálya nincs központilag dokumentálva.
- **Előfeltétel:** nincs.
- **Kész definíciója:** mindkét audit eredménye rögzített; minden findinghez
  owner, döntés és határidő tartozik; production high/critical találat blokkol.

### 9. CI-009 — Egységes release gate és evidence-csomag

- **Prioritás / méret:** P1 / M
- **Miért most:** a release-kapuk és bizonyítékok több workflow-ban és
  dokumentumban szétszórva vannak.
- **Előfeltétel:** CI-002, CI-003, CI-004, CI-005, CI-006, CI-007 és CI-010.
- **Kész definíciója:** egy verziójelölt evidence-csomagja tartalmaz minden
  backend-, frontend-, security-, E2E-, migráció- és rollbackeredményt; hiányzó
  kapu mellett nincs release.

### 10. OPS-003 — Backup policy, scope, RPO és RTO jóváhagyása

- **Prioritás / méret:** P0 / M
- **Miért most:** a rendszer gyártási, készlet-, audit- és privát
  dokumentumadataihoz nincs jóváhagyott helyreállítási cél.
- **Előfeltétel:** nincs.
- **Kész definíciója:** a domain owner és az üzemeltetés jóváhagyja a backup
  scope-ot, RPO/RTO-t, retentiont és felelőst; a DB és dokumentum storage
  konzisztens snapshot követelménye, valamint a tabletop loss scenariók
  eredménye dokumentált.

## Következő frissítés

A lista első elemeinek lezárása után a következő jelöltek:

- `LC-001` Learning Center v1.0 scope-zárás.
