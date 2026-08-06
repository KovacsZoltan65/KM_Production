# Rétegezett quality gate-ek

## Cél

A quality-gate futtató a módosítás kockázatához igazítja a lokális
ellenőrzéseket. Nem kapcsol ki tesztet, nem alkalmaz kizárást, és nem
helyettesíti a merge vagy release előtt indokolt teljes ellenőrzést.

A központi modul- és kockázati mátrix a
[`config/quality-gates.php`](../../config/quality-gates.php) fájlban található.
A futtató belépési pontja a
[`tools/quality-gate.php`](../../tools/quality-gate.php).

## Gate-szintek

| Szint               | Mikor                                                                                    | Fő ellenőrzések                                                                                                                                    |
| ------------------- | ---------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| `fast` / `affected` | Fejlesztés közben, izolált változtatás után                                              | Módosított PHP-fájlok Pintje, megbízhatóan célozható PHPStan, érintett backend/frontend tesztek, szükséges i18n és E2E                             |
| `module`            | Egy üzleti modul lezárásakor                                                             | Modul és kapcsolódó modulok regressziója, teljes Pint/PHPStan, i18n, opcionális build és Chromium E2E                                              |
| `integration`       | Shared service, repository, middleware, route vagy közös Vue infrastruktúra változásakor | Érintett vagy explicit futtatásnál minden modul backend regressziója, teljes frontend, admin regresszió, Pint/PHPStan, i18n, build, admin Chromium |
| `full`              | Merge/release előtt, migráció, dependency, tesztkonfiguráció vagy nagy refaktor után     | Egy teljes backend futás, migration smoke, coverage-be foglalt teljes frontend, minden statikus/formázási kapu, build, auditok és admin Chromium   |

A full gate szándékosan nem futtatja külön a Unit, Feature és teljes backend
suite-ot: a `composer test:backend:sqlite` mindkettőt egyszer futtatja. A
frontend coverage parancs ugyancsak kiváltja a külön teljes Vitest futást.

## Parancsok

```bash
php tools/quality-gate.php affected
php tools/quality-gate.php affected --base=HEAD~1
php tools/quality-gate.php module procurement
php tools/quality-gate.php integration
php tools/quality-gate.php full
```

Composer aliasok:

```bash
composer qa:fast
composer qa:affected -- --base=HEAD~1
composer qa:module procurement
composer qa:integration
composer qa:full
```

Az elérhető modulnevek:

```bash
php tools/quality-gate.php modules
```

## Modulok és fő suite-ok

| Modul                        | Fő backend regresszió                                          | Frontend / Playwright fókusz                                          |
| ---------------------------- | -------------------------------------------------------------- | --------------------------------------------------------------------- |
| `admin`                      | admin foundation, partial reload, route authorization          | Admin CRUD komponensek és admin E2E                                   |
| `authentication`             | authentication/permission foundation, hardening                | auth és permission navigation                                         |
| `bom`                        | production structure és master UI                              | BOM oldal; kapcsolódó production regresszió                           |
| `capacity`                   | capacity planning                                              | schedule és dashboard komponensek                                     |
| `code-generation`            | code generation                                                | nincs külön frontend/E2E suite                                        |
| `customer-orders`            | customer order UI és order-to-production                       | workflow komponensek és customer-order E2E                            |
| `documents`                  | document UI, verziózás és intelligence pipeline                | dokumentum komponensek és E2E                                         |
| `inventory`                  | inventory, item/serial és cache invalidation                   | stock reservation frontend/E2E                                        |
| `manufacturing-intelligence` | intelligence és Python AI engine                               | intelligence/chart komponensek                                        |
| `master-data`                | partner, item és production master data                        | employee/admin CRUD                                                   |
| `procurement`                | requisition, order, receipt, supplier, cache és partial reload | három procurement frontend oldal és a teljes procurement E2E könyvtár |
| `production`                 | execution, structure és order production                       | production-task/quality workflow E2E                                  |
| `production-planning`        | production plans és capacity                                   | planning/schedule frontend és E2E                                     |
| `quality`                    | production execution quality útvonalai                         | workflow komponensek és task-quality E2E                              |
| `reports`                    | reporting analytics                                            | dashboard/chart komponensek                                           |

A `related_modules` kapcsolatok tranzitívan bővülnek, ciklusbiztosak, és az
azonos tesztfájl minden tervben csak egyszer jelenik meg.

## Affected kiválasztás

Alapértelmezésben a runner egyesíti a következőket:

- unstaged diff;
- staged diff;
- `HEAD`-hez viszonyított diff;
- új, még nem követett fájlok.

`--base=<commit>` esetén a base és `HEAD` közötti hárompontos diff is bekerül.
CI-ben a PR merge base vagy a base branch letöltött referenciája adható meg.

A kiválasztás könyvtár-, glob- és explicit shared/core szabályokat használ.
Nem besorolható fájl integration gate-re emel. Migráció, dependency lock,
PHPUnit/Pest/Playwright/Vite vagy maga a quality-gate infrastruktúra full
gate-et igényel.

Fő magas kockázatú példák:

```text
database/migrations/**                  -> full
composer.json, package-lock.json        -> full
routes/**, middleware, providers        -> integration
AdminCrudPage.vue                       -> integration
BusinessCacheInvalidator és Cache/**    -> integration
permission seeder és policy infra       -> integration
model trait-ek                          -> integration
```

## Dry-run és explain

```bash
php tools/quality-gate.php affected --dry-run --explain
php tools/quality-gate.php module procurement --dry-run
php tools/quality-gate.php integration --dry-run
php tools/quality-gate.php full --dry-run
```

A dry-run nem indít quality commandot vagy tesztet. Az affected módnak a Git
állapot beolvasásához Git parancsokat kell futtatnia. Az explain mód fájlonként
kiírja a talált szabályt, modult, kockázati emelést és E2E döntést.

## Timeout és process cleanup

Globális CLI timeout:

```bash
php tools/quality-gate.php module procurement --timeout=900
```

Csoportonkénti környezeti változók:

```text
QUALITY_GATE_TIMEOUT_DEFAULT
QUALITY_GATE_TIMEOUT_BACKEND
QUALITY_GATE_TIMEOUT_FRONTEND
QUALITY_GATE_TIMEOUT_PLAYWRIGHT
QUALITY_GATE_TIMEOUT_BUILD
QUALITY_GATE_TIMEOUT_PHPSTAN
```

A verziózott alapértékek rendre 120, 600, 240, 900, 180 és 240 másodperc;
ezek a projekt jelenlegi mért Windows futásaihoz igazodnak.

Timeout esetén a runner kiírja az elakadt parancsot, 124-es exit code-ot ad,
és lezárja a saját processzfáját. Windowson ezt a
`tools/quality-gate-process.ps1` watchdog végzi a .NET teljes processzfa-kill
funkciójával. POSIX rendszeren a közvetlen child és annak leszármazottai kapnak
lezárási jelzést. Külső, nem a runner által indított processzt nem érint.

## Gyakori példák

| Változás                                  | Választás                                                                                                 |
| ----------------------------------------- | --------------------------------------------------------------------------------------------------------- |
| `GoodsReceiptService.php`                 | procurement + inventory, célzott workflow E2E                                                             |
| BOM Vue oldal                             | bom + production, frontend/i18n/build                                                                     |
| migráció                                  | full                                                                                                      |
| `lang/*.json`                             | fast + i18n                                                                                               |
| közös `AdminCrudPage.vue`                 | integration                                                                                               |
| kizárólag PHPDoc egy besorolt service-ben | ugyanaz a biztonságos modul; workflow service útvonalnál a jelenlegi konzervatív szabály E2E-t is választ |
| dependency update                         | full                                                                                                      |

## Codex végrehajtási szabály

Fejlesztés közben először az affected/fast kaput és a közvetlenül módosított
infrastruktúra tesztjét kell használni. Modul lezárásakor module gate fut.
Integration gate csak shared/core vagy keresztmodul kockázatnál indokolt.

Full gate csak kifejezett kérésre, merge/release ellenőrzéskor, migration vagy
dependency változásnál, tesztfuttatási infrastruktúra módosításakor, nagy
refaktor után vagy célzott tesztekből látható keresztmodul gyanúnál fut.
Ugyanabban az ellenőrzési körben tilos a teljes backend, frontend, coverage és
Playwright suite-ok szükségtelen ismétlése.

## CI használat

A jelenlegi GitHub Actions workflow-k külön, párhuzamos backend SQLite/MySQL,
migration, frontend, i18n, build, audit és E2E jobokat használnak. Ezeket a
runner bevezetése nem kapcsolja ki, mert required státuszuk repositoryból nem
igazolható.

Javasolt aktiválási sorrend a branch-protection audit után:

1. PR-ben checkout `fetch-depth: 0`, majd
   `php tools/quality-gate.php affected --base=origin/${{ github.base_ref }}`.
2. Shared/core változásnál a selector automatikusan integrationre emel.
3. Main push és release workflow `php tools/quality-gate.php full` parancsot
   használ.
4. A meglévő guardolt MySQL job külön marad, mert lokális full gate nem
   feltételez futó MySQL tesztszolgáltatást.
5. A régi required checkek csak az új jobok több sikeres bizonyítéka és a
   branch-protection átállítása után vonhatók össze.

Éjszakai schedule ebben a változtatásban nem készült.

## Mátrix karbantartása és korlátozások

- Új modul vagy eltérően elnevezett teszt esetén a központi konfigurációt és a
  selector unit tesztjét együtt kell frissíteni.
- A jelenlegi Feature tesztek domain könyvtárak helyett részben gyökérszinten
  vannak; emiatt néhány mapping explicit fájlnevet tartalmaz.
- A PHPDoc-only felismerés útvonal- és kockázatalapú, nem próbál PHP diffet
  szemantikailag értelmezni. Emiatt egy workflow service dokumentációs
  módosítása a szükségesnél szélesebb, de biztonságos E2E-választást adhat.
- A Windows cleanup PowerShell 7-et igényel; ez a projekt Windows-first
  fejlesztői környezetében már követelmény. POSIX cleanup best-effort child-tree
  lezárást használ.
- A lokális full gate SQLite-ra épül. A guardolt MySQL gate továbbra is külön
  Composer parancs és CI job.
- A layered CI végrehajtás még nincs branch protectionben aktiválva.

## Hibaelhárítás

- Ismeretlen modulnál futtasd a `php tools/quality-gate.php modules` parancsot;
  a runner nem vált automatikusan fullra.
- Téves kiválasztásnál használd az `--explain --dry-run` kombinációt, majd
  javítsd a központi szabályt és annak unit tesztjét.
- 124-es kód timeoutot jelent. A kimenetben szereplő timeout-csoport értékét
  csak mért, indokolt esetben emeld.
- Playwright előtt a runner maga készíti elő az izolált E2E adatbázist; kézzel
  indított fejlesztői szervert nem állít le.

## Quality Gates

Development work must use the project's layered quality gate system.

Default policy:

- During implementation, use the smallest safe quality gate.
- Prefer `affected` or `module` quality gates for routine development.
- Use the `integration` quality gate only when shared infrastructure or multiple business modules are affected.
- Run the `full` quality gate only:
    - before merge or release;
    - after dependency updates;
    - after database migration changes;
    - after shared or core infrastructure changes;
    - when explicitly requested by the user.

When a quality gate fails:

- rerun only the failed quality gate after applying the fix, unless the scope of the change has expanded;
- do not rerun previously successful full test suites without evidence that the new changes affect them.

Agents must avoid duplicate executions of the same successful quality gates during a single development iteration.

Always use the project's quality gate runner instead of manually selecting test suites when it is available.

See:

- [Layered Quality Gates](docs/development/quality-gates.md)
