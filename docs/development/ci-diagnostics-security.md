# CI-diagnosztika és érzékeny adatok

## Hatókör

A CI-hardening első szakasza az SQL-kivételüzeneteket, a publikált JUnit-riportokat
és a Playwright-diagnosztikát védi. A jóváhagyott második szakasz a workflow-k
SHA-rögzítését, a MySQL-kliens hitelesítését és a checkout-token kezelését rendezi.

## SQL-hibák

Az `AppServiceProvider` regisztrációjakor a `TestDiagnostics` kizárólag
`APP_ENV=testing` mellett kapcsolja be minden adatbázis-kapcsolat
`mask_bindings_in_exception_messages` beállítását. A production- és E2E-konfiguráció
változatlan marad. Nincs kivétel-elnyelés; a hibatípus, kapcsolati kontextus és
paraméterhelyeket tartalmazó SQL továbbra is elérhető.

A beállítás **nem** redaktálja a nyers SQL-literálokat, a driver eredeti
hibaüzenetét, az exception objektum külön kiolvasott bindingjait vagy tetszőleges
teszt-stdoutot. Ezekbe valódi titok továbbra sem kerülhet. A regressziós teszt
adatbázis-kapcsolat nélkül, szintetikus PDO-hibával és memóriabeli loggerrel fut.

## JUnit

A `scripts/report-junit-failures.php <raw.xml> <published.xml>` közös
`PublishedDiagnostics` implementációja új XML-t épít engedélyezett mezőkből.
A teszteset SHA-256-alapú azonosítója, a projektben létező tesztfájl, a sorszám,
az összesítés és a fix hibakategória megmarad. A nyers tesztnevek, kivételtípusok,
assertion-üzenetek, XML-properties, stdout és stderr elmaradnak az annotációból
és az artifactból is. A cím és az üzenet workflow-escape-et kap.

Ez szándékosan minimális diagnosztika: ismeretlen formájú érzékeny assertion-adatot
sem enged át pusztán azért, mert nem illeszkedik egy tokenmintára. A nyers JUnit
helyi fájl marad; a backend workflow kizárólag az új `test-results/published/`
XML-fájlt publikálja. A CI cache- és teljes backend tesztjeit a
`scripts/run-ci-backend-tests.php` futtatja: a nyers stdout/stderr kizárólag a
privát `storage/framework/testing/ci-backend-<engine>.log` fájlba kerül, a gyermek
valódi kilépési kódja megmarad. A közvetlenül, helyileg indított Pest/PHPUnit
konzolkimenete nem ezen a szűrőn halad át. A MySQL-settings artifact csak a korábbi, korlátozott SQL-metaadatokat
tartalmazza; a kliens hitelesítését a második szakasz alább részletezi.

## Playwright

CI-ben kizárólag a saját minimális reporter fut. Tesztazonosítót, ellenőrzött
forráshelyet, állapotot és runnerhibaszámot ír; az exception-, attachment-,
browser-console- és teszt-stdout/stderr tartalmat nem publikálja.
A workflow explicit engedélylistája egyetlen fájl:
`test-results/published/playwright-summary.json`.

CI-ben minden trace, screenshot és videó kikapcsolt. A bejelentkezési helper számos
tesztben szerepel, ezért a hitelesítést tartalmazó trace-ek megbízható utólagos
tisztítása helyett ezek felvétele is tiltott. Helyileg megmarad a hibakori privát
trace, screenshot, videó és HTML/JUnit riport; ezeket nem szabad kézzel publikálni
előzetes felülvizsgálat nélkül.

A PHP-szerver stdout/stderr naplója a nem publikált
`storage/framework/testing/e2e-server.log` fájlba kerül. A CI readiness-hibák
fix üzenetet adnak; helyileg a részletes diagnosztika megmarad.

## Ellenőrzés és visszaállítás

A célzott Pest unit tesztek és a Vitest `ciDiagnostics.test.js` kizárólag
szintetikus adatokat használnak. Hibás állítás esetén is logikai eredményt
ellenőriznek, nem a nyers szintetikus értéket írják a tesztkimenetbe.
A workflow-k teljes GitHub-futását a helyi unit ellenőrzés nem helyettesíti.

A változtatások önállóan visszafordíthatók a diagnosztikai helper, a providerhívás,
a reporter és a workflow-publikálási útvonalak korábbi állapotának visszaállításával;
ehhez adatbázis-művelet nem szükséges. A visszaállítás újra megnyitná az auditban
jelzett adatkiírási útvonalakat.

## Első ellenőrzési pont – 2026-11-10

Ág: `fix/ci-security-hardening`, kiinduló commit: `630a79a`.

| Megállapítás | Státusz     | Ellenőrzött eredmény                                                            |
| ------------ | ----------- | ------------------------------------------------------------------------------- |
| CI-01        | FIXED       | Kizárólag testing környezetben aktív binding-maszkolás; production változatlan. |
| CI-02        | FIXED       | Minimális annotáció/XML; privát CI-tesztkimenet; eredeti kilépési kód.          |
| CI-03        | FIXED       | Explicit JSON-artifact; nincs CI-felvétel vagy nyers szervernapló-publikálás.   |
| CI-04–CI-06  | NOT STARTED | A második szakasz külön jóváhagyást igényel.                                    |

Lefutott ellenőrzések:

- `php -d xdebug.mode=off scripts/backend-test-environment.php sqlite test tests/Unit/Support/TestDiagnosticsTest.php`: 2 sikeres teszt, 11 assertion.
- `php -d xdebug.mode=off scripts/backend-test-environment.php sqlite test tests/Unit/Support/PublishedDiagnosticsTest.php`: 13 sikeres teszt, 51 assertion.
- Célzott Vitest: `ciDiagnostics.test.js` és `useRequestError.test.js`: 12 sikeres teszt. A Vite sandboxbeli `spawn EPERM` hibája miatt a sikeres futás sandboxon kívül történt.
- A `tests/fixtures/ci-diagnostics/playwright.config.js` valódi Playwright-folyamatpróbája: két böngésző nélküli szintetikus eset, egy szándékos hiba; a gyermek kilépési kódja 1, a vizsgálat sikeres. A marker sem a konzolkimenetben, sem a publikált JSON-ban nincs jelen.
- PHP-szintaxis: 8 fájl; JavaScript-szintaxis: 6 fájl; célzott Pint, Prettier, PHPStan, workflow YAML/anchor-validáció és `git diff --check`: sikeres.
- A workflow-k action-hivatkozásai és hitelesítési sorai változatlanok maradtak.

`BLOCKED`: a teljes quality gate a dry-run szerint migrációs smoke-ot és
E2E-adatbázis-előkészítést is indítana; ezek a feladat jelenlegi korlátai miatt
nem futottak. Teljes alkalmazás-E2E és GitHub Actions futás nem indult;
helyi célzott sikerből nem következik teljes CI-siker. `actionlint` nem érhető el,
új eszköz telepítése nem történt. A nyers SQL-literálok és driverüzenetek fent
leírt korlátai fennmaradnak.

Érintett fájlok csoportonként:

- CI-01: `app/Support/Testing/TestDiagnostics.php`, `app/Providers/AppServiceProvider.php`, `tests/Unit/Support/TestDiagnosticsTest.php`.
- CI-02: `app/Support/Diagnostics/PublishedDiagnostics.php`, `scripts/report-junit-failures.php`, `scripts/run-ci-backend-tests.php`, `.github/workflows/backend-quality.yml`, `tests/Unit/Support/PublishedDiagnosticsTest.php`, `tests/fixtures/ci-diagnostics/SyntheticFailureTest.php`.
- CI-03: `scripts/ci-playwright-reporter.js`, `scripts/e2e-global-setup.js`, `playwright.config.js`, `.github/workflows/frontend.yml`, `tests/frontend/utils/ciDiagnostics.test.js`, `tests/fixtures/ci-diagnostics/playwright.config.js`, `tests/fixtures/ci-diagnostics/synthetic.spec.js`.
- Dokumentáció: ez a fájl, `docs/e2e-testing.md`, `.kiro/steering/security.md`.

Commit és push nem történt. Az első szakasz ellenőrzési pontján a munka megállt.

## Második ellenőrzési pont – 2026-11-11

Az első szakasz 19 fájlja megmaradt. A második szakasz két workflow-t és ezt a
dokumentumot egészíti ki, továbbá két új fájlt ad hozzá:
`scripts/ci-mysql-client.sh` és `tests/Unit/Support/CiMysqlClientTest.php`.
Üzleti logika, dependency lock, production-konfiguráció és `.env` nem változott.

### CI-04: ellenőrzött action-azonosság

A GitHub integráció az upstream repository commits API-ján az eredeti major
tageket oldotta fel. Mind a 27 szöveges action-hivatkozás teljes commit-SHA-t
kapott az eredeti `# v4` vagy `# v2` kommenttel. A YAML-aliasok feloldásával
30 action-lépés futna.

| Upstream action és eredeti tag | Ellenőrzött commit                                                                                                                       |
| ------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------- |
| `actions/checkout@v4`          | [`11d5960a326750d5838078e36cf38b85af677262`](https://github.com/actions/checkout/commit/11d5960a326750d5838078e36cf38b85af677262)        |
| `actions/cache@v4`             | [`0057852bfaa89a56745cba8c7296529d2fc39830`](https://github.com/actions/cache/commit/0057852bfaa89a56745cba8c7296529d2fc39830)           |
| `actions/upload-artifact@v4`   | [`ea165f8d65b6e75b540449e92b4886f43607fa02`](https://github.com/actions/upload-artifact/commit/ea165f8d65b6e75b540449e92b4886f43607fa02) |
| `actions/setup-node@v4`        | [`49933ea5288caeca8642d1e84afbd3f7d6820020`](https://github.com/actions/setup-node/commit/49933ea5288caeca8642d1e84afbd3f7d6820020)      |
| `shivammathur/setup-php@v2`    | [`eb7c497e18156a6bbabfef1d3a82760b9eda3962`](https://github.com/shivammathur/setup-php/commit/eb7c497e18156a6bbabfef1d3a82760b9eda3962)  |

A major verziók változatlanok. SHA-rögzítés mellett az új upstream kiadásokhoz
tudatos SHA-frissítés kell. Nincs meglévő Dependabot-konfiguráció; a
`github-actions` ökoszisztéma támogatná e hivatkozások frissítését, de automatikus
PR-készítési stratégiát e javítás nem vezet be.
Forrás: [GitHub – támogatott Dependabot ökoszisztémák](https://docs.github.com/en/code-security/reference/supply-chain-security/supported-ecosystems-and-repositories).

### CI-05: védett MySQL-klienskonfiguráció

A három klienshívás a Bash helperen keresztül fut. A helper a konkrét
hostot, tesztadatbázis-nevet, portformátumot és dedikált felhasználót ellenőrzi,
majd a meglévő Laravel tesztkörnyezet-ellenőrzést is lefuttatja.
Kizárólag a rögzített `probe` és `settings` SELECT-ek engedélyezettek.

A klienskonfiguráció a runner ideiglenes könyvtárának egy saját, `mktemp` által
létrehozott 0700 alkönyvtárába kerül, 0600 fájljoggal és 0077 umaskkal.
A jelszót Bash builtin írja idézett, escape-elt MySQL option-file értékként.
Az első kliensargumentum `--defaults-file`; a jelszó nincs az argumentumokban.
Az örökölt `MYSQL_PWD` és login-path fájlok nem írhatják felül ezt.
A helper a nyers klienshibát visszatartja, és nem nulla kóddal tér vissza.

Az EXIT trap siker és hiba esetén törli a konfigurációt, az eredményfájlt és a
saját alkönyvtárat; INT/TERM is ezen az útvonalon lép ki. A következő tesztlépés
már nem kap megőrzött klienskonfigurációt, ezért teszthiba után sincs mit
artifactként feltölteni. SIGKILL vagy runnerleállás nem kezelhető trapből;
a GitHub által kezelt eldobható runner tárolójának megszüntetésére támaszkodunk.
A konfiguráció nem a repository vagy az artifactok könyvtárába kerül.

A két szolgáltatás-healthcheck jelszó nélküli `mysqladmin ping`.
Ez csak a szerver futását igazolja: a MySQL szerint Access denied mellett is
sikeres lehet. A külön helperes, hitelesített `SELECT 1` változatlanul kötelező
a teszt vagy migrációs job folytatásához.
Forrás: [MySQL 8.4 mysqladmin](https://dev.mysql.com/doc/refman/8.4/en/mysqladmin.html).
Az option-file escape-ek forrása:
[MySQL option files](https://dev.mysql.com/doc/refman/8.4/en/option-files.html).

### CI-06: checkout-hitelesítés

Mind a tíz checkout `persist-credentials: false` beállítást kapott.
A jobok nem végeznek hitelesített Git push/pull/fetch műveletet a checkout után;
a statikus job `git diff --check` parancsa lokális. A Composer dist telepítést,
az npm pedig lock-alapú `npm ci` telepítést használ. A repository checkout-tokenjére
egyiknek sincs deklarált szüksége. A tényleges hosted install/post-job működés
GitHub Actions futtatás nélkül még nincs teljes körűen igazolva.

### Státusz és ellenőrzési korlátok

| Megállapítás | Implementáció | Bizonyíték                                                                             |
| ------------ | ------------- | -------------------------------------------------------------------------------------- |
| CI-01        | FIXED         | Változatlan testing binding-maszkolás, 2 regressziós teszt.                            |
| CI-02        | FIXED         | Változatlan minimális JUnit/annotáció, 13 regressziós teszt.                           |
| CI-03        | FIXED         | Változatlan artifact-engedélylista, Vitest és valódi reporter-próba.                   |
| CI-04        | FIXED         | 27/27 szöveges referencia, öt hiteles upstream SHA, eredeti major kommentek.           |
| CI-05        | FIXED         | Nincs CLI-jelszó; védett kliensfájl, sikeres/hibás cleanup-próba, valódi SELECT.       |
| CI-06        | FIXED         | 10/10 checkoutnál nincs hitelesítés-megőrzés; nincs utólagos hitelesített Git-művelet. |

Lefutott ellenőrzések:

- `php -d xdebug.mode=off scripts/backend-test-environment.php sqlite test tests/Unit/Support/CiMysqlClientTest.php tests/Unit/Support/TestDiagnosticsTest.php tests/Unit/Support/PublishedDiagnosticsTest.php`: 18 sikeres teszt, 79 assertion. Kizárólag unit/szintetikus adatok, nincs migráció. A végleges MySQL-helper külön ismétlése: 3/3 teszt, 17 assertion.
- `node node_modules/vitest/vitest.mjs run tests/frontend/utils/ciDiagnostics.test.js tests/frontend/composables/useRequestError.test.js`: 12/12 teszt sikeres.
- A valódi Playwright CLI a szintetikus fixture konfigurációjával: két böngésző nélküli eset, egy szándékos hiba, gyermek-exit 1; a marker hiányzik a stdout/stderr és publikált JSON tartalmából. A vizsgálat sikeres.
- `php scripts/backend-test-environment.php mysql guard`, majd `bash scripts/ci-mysql-client.sh probe` és `bash scripts/ci-mysql-client.sh settings`: sikeres a helyi `127.0.0.1:3306/km_production_testing` célon, `km_testing` felhasználóval. A guard minden kapcsolatnyitás előtt lefutott. Eredmény: SELECT 1 = 1; MySQL 8.4.7, utf8mb4, utf8mb4_unicode_ci. Kizárólag SELECT, nem történt adatbázis-módosítás.
- Kilenc PHP-fájl szintaxisa, hat JavaScript-fájl `node --check` ellenőrzése és `bash -n scripts/ci-mysql-client.sh`: sikeres.
- Célzott Pint és nyolc PHP-fájl PHPStan/Larastan ellenőrzése: sikeres. A kezdeti, kizárólag új tesztet érintő statikus típushiba javítva, az ismétlés hibamentes.
- Célzott Prettier: sikeres. A workflow-knál az eredeti két szóközös YAML-behúzás maradt meg (`--tab-width 2`); a projekt npm formázási parancsa nem tartalmaz YAML-t.
- Workflow YAML és aliasfeloldás: 30/30 action-lépés SHA-ja megegyezik az upstreamből ellenőrzött értékkel; 10/10 checkout védett; a korábbi artifact-engedélylista változatlan; nulla jelszóargumentum a workflow-kban.
- `composer validate --strict --no-check-publish`, `node scripts/check-translations.js`, `git diff --check`: sikeres; 732 szinkron fordítási kulcs.
- Az első szakasz 19 fájljának induló SHA-256 lenyomata alapján 16 byte szerint változatlan; csak a két workflow és ez a dokumentum változott a második szakaszban. A dependency lockfájlok változatlanok; minden Composer source URL publikus GitHub HTTPS-hivatkozás, npm Git-alapú resolved csomag nincs.
- A Bash/Vite/Playwright folyamatpróbákhoz a Windows sandbox folyamatindítási korlátai miatt sandboxon kívüli futás kellett. A szintetikus tesztadatok nem kerültek a jelentésbe.

`BLOCKED` / nem futott: teljes backend/frontend quality gate, alkalmazás-E2E és
hosted GitHub Actions. A `php tools/quality-gate.php affected --dry-run --explain`
full szintet választott; ez migrációs round-tripet és E2E-adatbázis-előkészítést
is indítana, amit a feladat tilt. Csak a fent felsorolt célzott ellenőrzések
futottak. `actionlint` nincs telepítve, új ellenőrzőeszköz nem került telepítésre.
Dependency-frissítés, csomagtelepítés, RMA PoC és külső CI-indítás nem történt.

CI-01–CI-06 implementációja FIXED; a teljes CI elfogadása BLOCKED a lent jelzett
futtatások hiánya miatt. A második ellenőrzési ponton nincs commit vagy push.
A célzott helyi eredmények és a még nem futott kapuk nem helyettesítik egymást.

A POSIX 0600/0700 jogosultságokhoz a regressziós teszt Linuxon külön assertiont
tartalmaz. A Windows/Git Bash helyi futás ezt nem igazolja; a Linux-assertionök
csak a következő engedélyezett Linux CI-futáskor lesznek ellenőrizhetők.
A helper Linux CI-ra készült; helyi próba csak eldobható teszthitelesítéssel történt.

Visszaállítás: kizárólag a második szakasz SHA-, checkout- és MySQL-helper
módosításai fordíthatók vissza, a két új fájl eltávolításával.
Az első szakasz diagnosztikai javításait meg kell őrizni. Adatbázis-visszaállítás
nem szükséges. A visszaállítás újra megnyitná a CI-04–CI-06 kockázatokat.
