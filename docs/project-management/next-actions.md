# KM_Production aktuális végrehajtási terv

## Dokumentumadatok

- Baseline: 2026-07-27
- Forrás: `docs/project-management/backlog.md`
- Hatókör: a következő tíz végrehajtható projektfeladat
- Frissítési szabály: feladat lezárásakor, blokkolásakor vagy prioritásváltásakor

Ez a dokumentum nem ismétli meg a teljes backlogot. A részletes scope,
kockázat és tesztkövetelmény a hivatkozott backlogelemnél található.

## Végrehajtási sorrend

### 1. GOV-002 — Az origin alapértelmezett ágának átállítása `main` ágra

- **Prioritás / méret:** P1 / XS
- **Miért most:** az `origin/HEAD -> origin/master` eltérés hibás klónozási és
  automatizációs alapértelmezést okozhat.
- **Előfeltétel:** repository-adminisztrátori jogosultság és a `main` aktív
  release-ágként történő megerősítése.
- **Kész definíciója:** a távoli default branch `main`, friss fetch után az
  `origin/HEAD -> origin/main`, és a klónozási ellenőrzés `main` ágra érkezik.

### 2. GOV-003 — Beolvadt ágak felülvizsgálata és takarítása

- **Prioritás / méret:** P1 / S
- **Miért most:** a megmaradt feature/maintenance ágak félkész munka látszatát
  keltik és rontják a projektállapot olvashatóságát.
- **Előfeltétel:** GOV-002; minden törlendő ágnál merge- és egyedi
  commitellenőrzés.
- **Kész definíciója:** a felülvizsgálati lista rögzíti a megtartott/törölt
  ágakat és indokukat; csak bizonyítottan beolvadt ág törlődik.

### 3. GOV-004 — Commitüzenet-konvenció elfogadása

- **Prioritás / méret:** P1 / XS
- **Miért most:** a `mentés`, `workspace` és `Hiba javítás` üzenetekből nem
  állapítható meg megbízhatóan a változás scope-ja.
- **Előfeltétel:** nincs.
- **Kész definíciója:** dokumentált típus/scope/tárgy formátum, jó és tiltott
  példák, valamint backlog/issue ID-hivatkozási szabály áll rendelkezésre.

### 4. GOV-006 — Pull request sablon létrehozása

- **Prioritás / méret:** P1 / XS
- **Miért most:** a CI és Definition of Done csak egységes PR-bizonyítékkal
  tehető kötelezővé.
- **Előfeltétel:** GOV-004.
- **Kész definíciója:** a GitHub új PR-nél automatikusan megjeleníti a scope,
  backlog ID, kockázat, migráció, teszteredmény és dokumentáció mezőket.

### 5. GOV-008 — Projekt Definition of Done bevezetése

- **Prioritás / méret:** P1 / S
- **Miért most:** az aktív backlogelemek lezárási állapota egységes quality
  gate nélkül nem összehasonlítható.
- **Előfeltétel:** nincs.
- **Kész definíciója:** a backlog-konvenció, PR-sablon és release-checklist
  ugyanazokat a kötelező kód-, teszt-, biztonsági és dokumentációs kapukat
  használja.

### 6. CI-001 — A Vitest worker-timeout okának reprodukálása

- **Prioritás / méret:** P1 / S
- **Miért most:** az alapértelmezett `npm test` 7 worker-timeouttal hibázott,
  miközben egyszálas futásban 166/166 teszt sikeres.
- **Előfeltétel:** a baseline gép Node/npm verziójának és erőforrásadatainak
  rögzítése.
- **Kész definíciója:** legalább három kontrollált futásból származó bizonyíték
  azonosítja a worker-, pool-, erőforrás- vagy környezeti okot, és kizárja a
  tesztlogikai regressziót.

### 7. CI-002 — Az alapértelmezett Vitest-konfiguráció stabilizálása

- **Prioritás / méret:** P1 / S
- **Miért most:** a fejlesztői alapértelmezett parancsnak megbízható quality
  gate-ként kell működnie.
- **Előfeltétel:** CI-001.
- **Kész definíciója:** az `npm test` három egymást követő Windows-futtatásban
  20/20 fájllal és 166/166 teszttel zárul worker-timeout nélkül; a Linux CI is
  zöld.

### 8. CI-003 — A teljes MySQL quality gate aktuális futtatása

- **Prioritás / méret:** P1 / S
- **Miért most:** a workflow létezik, de a 2026-07-27-i audit nem adott
  aktuális helyi MySQL-bizonyítékot.
- **Előfeltétel:** kizárólag dedikált `km_production_testing` adatbázis és a
  test-environment guard sikeres ellenőrzése.
- **Kész definíciója:** a MySQL tesztsuite, migráció round-trip és kétszeri
  alapseeder smoke sikeres, a charset/collation/sql-mode adatok rögzítettek.

### 9. CI-004 — A teljes Playwright E2E-kapu aktuális futtatása

- **Prioritás / méret:** P1 / M
- **Miért most:** az E2E-infrastruktúra elkészült, de az audit során a teljes
  Chromium, accessibility, keyboard, cross-browser és mobile készlet nem
  futott.
- **Előfeltétel:** izolált `.env.e2e`, SQLite adatbázis, E2E filesystem és
  telepített Playwright böngészők.
- **Kész definíciója:** minden konfigurált Playwright projekt lefut; eltérés
  esetén artifact és dokumentált környezeti kivétel készül, tiltott teszt nincs.

### 10. CI-005 — A GitHub Actions quality gate és required check mátrix auditja

- **Prioritás / méret:** P1 / S
- **Miért most:** a workflow-k jelen vannak, de nincs repository-szintű
  bizonyíték arról, hogy valamennyi release-kritikus job kötelező merge-feltétel.
- **Előfeltétel:** GOV-005 branch-protection döntés előkészítése; CI-002,
  CI-003 és CI-004 eredményei.
- **Kész definíciója:** dokumentált job/trigger/required-check mátrix készül,
  és egyetlen P1 quality gate sem kerülhető meg normál PR-merge során.

## Következő frissítés

A lista első elemeinek lezárása után a következő jelöltek:

- `CI-006` és `CI-007` dependency security audit;
- `CI-009` egységes release gate;
- `OPS-003` backup policy és RPO/RTO;
- `LC-001` Learning Center v1.0 scope-zárás.
