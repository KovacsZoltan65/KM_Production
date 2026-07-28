# KM_Production projekt-audit baseline — 2026-07-27

## Cél

Ez a dokumentum a központi backlog létrehozásának mérési alapállapotát rögzíti.
Nem helyettesíti a backlogot, és a mérési eredményeket csak új, dátumozott
audit módosíthatja.

## Vizsgált területek

- projekt- és AI-agent belépési dokumentumok;
- `.kiro` steering, döntések, tudás, workflow-k és checklisták;
- Learning Center specifikáció és roadmap;
- AI, OCR és telemetry dokumentáció;
- Laravel route-ok, kontrollerek, szolgáltatások, repositoryk, modellek,
  policy-k, requestek és migrációk;
- Vue/Inertia oldalak és frontend tesztkonfiguráció;
- backend, frontend és Playwright tesztek;
- Composer, npm, PHPStan és GitHub Actions konfiguráció;
- deployment, queue, logging, storage és health konfiguráció;
- Git-ágak, tagek és közelmúltbeli commitok.

## Git-állapot

| Tétel                     | Eredmény                                     |
| ------------------------- | -------------------------------------------- |
| Aktív ág                  | `main`                                       |
| Követett ág               | `origin/main`                                |
| Main eltérés              | `0/0`                                        |
| Munkafa az audit kezdetén | Tiszta                                       |
| Remote HEAD               | `origin/master`, miközben az aktív ág `main` |
| Beolvadt, megmaradt ágak  | Több feature és maintenance ág               |

A látható feature és maintenance ágak commitjai a `main` történetében
szerepelnek; ezek nem tekintendők félkész fejlesztésnek. A `master` remote ág
régebbi, mint a `main`.

## Alkalmazásleltár

- 147 alkalmazásútvonal;
- 43 controllerfájl;
- 46 service-fájl;
- 56 repository és repository-interface fájl;
- 41 modell;
- 25 policy;
- 59 FormRequest;
- 69 Vue-oldalfájl;
- 27 backend feature tesztfájl;
- 2 backend unit tesztfájl;
- 24 frontend tesztsegéd- és tesztfájl;
- 22 E2E teszt- és támogatófájl.

## Lezárt fő területek

- autentikáció, felhasználók, szerepkörök, permissionök és auditnapló;
- gyártási törzsadatok;
- termékek, alapanyagok, BOM és verziózott műveleti sorrendek;
- vevőrendelések, gyártási tervek, rendelések és feladatok;
- készletmozgások, foglalások, hiányok és anyagszükséglet;
- beszerzés, beszerzési rendelések és áruátvétel;
- minőségellenőrzés;
- verziózott dokumentumkezelés;
- riportok, kapacitástervezés és szimuláció;
- szabályalapú Manufacturing Intelligence;
- HU/EN lokalizáció;
- üzleti cache-invalidation;
- PHPStan level 5 baseline nélkül;
- SQLite/MySQL backend quality-gate infrastruktúra;
- frontend unit- és Playwright E2E-infrastruktúra;
- Learning Center fogalmi és architekturális specifikációs alap.

## Félkész területek

### Learning Center

A specifikáció státusza `Draft`. A Knowledge Unit, Knowledge Graph, Course
Model, Context Engine és UI-koncepció dokumentált, de a runtime réteghez nem
található Learning Center migráció, modell, repository, service, controller,
route vagy Vue-adminfelület.

### Document Intelligence

A queue-alapú Laravel–Python JSON-folyamat, a telemetry és az OCR
pluginhatár implementált. Az osztályozó fájlnév-heurisztika, az OCR backend
stub, és az OCR alapértelmezetten kikapcsolt. Valódi Tesseract/EasyOCR backend,
emberi review UI és telemetry dashboard nincs.

### Üzemeltetési érettség

A Laravel alap `/up` health route, database queue és failed-job tárolás
létezik. Nem található projekt-specifikus backup–restore eljárás, production
monitoring/alerting, scheduler-feladat, failed-job operációs folyamat,
dokumentumtárolási retention vagy disaster-recovery runbook.

## Minőségi ellenőrzések

| Ellenőrzés                      | Eredmény                          |
| ------------------------------- | --------------------------------- |
| Composer validáció              | Sikeres                           |
| Pint                            | Sikeres                           |
| PHPStan level 5                 | Sikeres, 0 hiba                   |
| Backend SQLite                  | 348 sikeres teszt, 955 assertion  |
| SQLite migráció round-trip      | Sikeres                           |
| Alapseeder idempotencia smoke   | Sikeres                           |
| Alapértelmezett `npm test`      | Sikertelen: 7 worker timeout      |
| Frontend egyszálas újrafuttatás | 20/20 fájl, 166/166 teszt sikeres |
| Produkciós frontend build       | Sikeres                           |
| i18n ellenőrzés                 | 690 kulcs szinkronban             |
| MySQL quality gate              | Az audit során nem futott         |
| Teljes Playwright E2E           | Az audit során nem futott         |

A frontend eredmény környezeti/futtatási stabilitási problémát igazol, nem
bizonyított alkalmazáshibát. A teljes suite egyszálas futásban zöld.

## CI-állapot

- `.github/workflows/backend-quality.yml` tartalmaz statikus elemzést, SQLite
  és MySQL tesztet, valamint mindkét adatbázis migrációs round-tripját.
- `.github/workflows/frontend.yml` tartalmaz npm auditot, frontend teszteket,
  i18n/build kaput, Chromium E2E-t, accessibility/keyboard teszteket és
  cross-browser/mobile smoke-ot.
- A repositoryban nem található PR-sablon, issue-sablon vagy dokumentált
  branch-protection beállítás.
- A helyi és CI-eredmények összevetéséhez nincs dátumozott release evidence
  dokumentum.

## Ismert kockázatok

1. Nincs korábbi központi backlog, ezért a prioritás és célverzió nem volt
   kanonikus helyen követhető.
2. Az `origin/HEAD` az elavult `master` ágra mutat.
3. Több beolvadt ág megmaradt és aktív munkának tűnhet.
4. A `mentés`, `workspace` és `Hiba javítás` commitcímek gyenge
   visszakövethetőséget adnak.
5. Az alapértelmezett Vitest workerbeállítás ezen a Windows környezeten nem
   determinisztikus.
6. A MySQL és teljes Playwright kapu aktuális helyi bizonyítéka hiányzik.
7. A Learning Center dokumentációs készültsége összetéveszthető a runtime
   készültséggel.
8. Az OCR és osztályozás jelenlegi stubjai nem produkciós AI-képességek.
9. Nincs bizonyított backup–restore és production monitoring folyamat.
10. A Playwright Firefox helyi Windows futásán dokumentált compositor-hiba
    jelentkezhet; a Linux CI eredménye az irányadó.

## Javasolt prioritások

1. Központi backlog, konvenciók és Definition of Done fenntartása.
2. Git default branch, ág- és commit/PR-governance rendezése.
3. Vitest stabilizálás és a teljes aktuális quality-gate bizonyítása.
4. Backup–restore, queue/scheduler és monitoring üzemeltetési alapok.
5. Learning Center v1.0 scope-zárás, majd rétegenkénti implementáció.
6. Reprezentatív, biztonságos OCR-korpusz és valós backend POC.
7. Prediktív Manufacturing Intelligence csak adatalkalmassági vizsgálat után.

## Kapcsolódó dokumentumok

- `docs/project-management/backlog.md`
- `docs/project-management/backlog-conventions.md`
- `docs/project-management/next-actions.md`
- `docs/specifications/learning-center/README.md`
- `docs/specifications/learning-center/roadmap.md`
- `docs/ai/document-intelligence.md`
- `docs/ai/ocr-adapter.md`
- `docs/ai/ai-processing-telemetry.md`
- `docs/backend-quality-gate.md`
- `docs/frontend-testing.md`
- `docs/e2e-testing.md`
- `docs/deployment.md`
