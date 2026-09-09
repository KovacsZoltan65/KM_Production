# Backendellenőrzés SQLite és MySQL adatbázison

## Cél

Az SQLite gyors visszajelzést ad az alkalmazás működéséről, széles körű regressziós ellenőrzésre és megismételhető helyi tesztelésre használható. A termelési rendszer azonban MySQL-t használ. A sikeres SQLite-teszt ezért nem bizonyítja a MySQL saját SQL-, séma- vagy adatbázismotor-függő viselkedését. A két környezet eltérő kérdésekre ad választ.

Ez az útmutató a backend és az adatbázis ellenőrzésének eljárását írja le. A követelmények alkalmazhatóságát és a lezárást a [Definition of Done](project-management/definition-of-done.md), az ellenőrzési szintet a [rétegezett ellenőrzések](development/quality-gates.md), a tesztek tervezését a [tesztelési szabályok](../.kiro/steering/testing.md) határozzák meg.

## Szabály: mit kell igazolni?

Először a módosított működést, majd a kapcsolódó regressziós kockázatot ellenőrizd. SQLite elegendő lehet, ha a szükséges alkalmazástesztek lefedik ezt a hatást, és nincs alkalmazandó MySQL-követelmény. Nem kell minden kis változáshoz mindkét teljes tesztcsomagot lefuttatni.

MySQL-vizsgálat kell, ha a változás helyessége a termelésben használt adatbázis viselkedésétől függ. Ilyen a MySQL-specifikus lekérdezés, a séma és migráció kompatibilitása, illetve az érintett adatbázis-korlátozás vagy index működése. A lent felsorolt motoreltérések segítik a döntést. Migrációváltozásnál a jelenlegi besorolás Full; a SQLite-migráció mellett a termelési MySQL-séma kompatibilitását külön is igazolni kell.

Egy backendellenőrzés csak a ténylegesen lefutott lépéseket, az adott teszteseteket és környezetet igazolja. Nem bizonyít minden üzleti esetet, frontendműködést vagy teljes felhasználói folyamatot. A statikus elemzés külön korlátait a [saját útmutatója](static-analysis.md) írja le.

A `composer qa:full` tartalmazza a teljes SQLite-tesztcsomagot és a SQLite-migrációt, de **nem tartalmaz MySQL-ellenőrzést**. A szükséges MySQL-vizsgálatot külön add hozzá. Egyik motor eredménye sem írható a másik javára.

## Megvalósítás: parancsok és környezet

A pontos parancsok forrása a [composer.json](../composer.json). Az adatbázist kiválasztó [indítóscript](../scripts/backend-test-environment.php) ugyanazt a Pest Unit és Feature tesztcsomagot indítja a [phpunit.xml](../phpunit.xml) alapján, SQLite vagy MySQL környezettel.

| Parancs                                   | Tényleges tartalom                                                                                    |
| ----------------------------------------- | ----------------------------------------------------------------------------------------------------- |
| `composer test`                           | Konfigurációs cache törlése, majd `artisan test`; nem az adatbázist védetten kiválasztó indítóscript. |
| `composer test:backend:sqlite`            | Teljes Unit és Feature tesztcsomag, izolált SQLite `:memory:` adatbázissal.                           |
| `composer test:backend:mysql`             | Ugyanez a tesztcsomag dedikált MySQL tesztadatbázison.                                                |
| `composer test:backend:migrations:sqlite` | SQLite-migráció oda-vissza és az alapseeder ellenőrzése.                                              |
| `composer test:backend:migrations:mysql`  | Ugyanez MySQL-en.                                                                                     |
| `composer test:cache`                     | Célzott SQLite-futás: `tests/Feature/BusinessCacheInvalidationTest.php`.                              |
| `composer quality:backend:sqlite`         | `composer validate --strict`, Pint, `composer analyse`, teljes SQLite-tesztcsomag és SQLite-migráció. |
| `composer quality:backend:mysql`          | MySQL-tesztcsomag és MySQL-migráció; önmagában nem futtat statikus elemzést.                          |
| `composer quality:backend:all`            | Előbb a SQLite-, majd a MySQL-összesített parancs.                                                    |
| `composer test:backend:quality`           | A `quality:backend:all` aliasa.                                                                       |

A `composer test` alapbeállítása a `phpunit.xml` szerint SQLite, de nem kényszeríti ki az indítóscript környezetét. Adatbázismotor szerinti igazoláshoz a védett `test:backend:*` parancsokat használd. Ezek teszt- és migrációs aliasai `@no_additional_args` beállításúak; célzott tesztútvonalhoz közvetlenül az indítóscriptet használd.

A [tests/Pest.php](../tests/Pest.php) a Feature teszteket a közös [Tests\\TestCase](../tests/TestCase.php) osztályhoz rendeli. A PHPUnit bootstrapje a `vendor/autoload.php`; a Laravel alkalmazás indításakor a közös tesztosztály végzi a biztonsági ellenőrzést.

A jelenlegi CI négy, egymástól független GitHub Actions jobot indít PHP 8.4-en, MySQL esetén MySQL 8.4 szolgáltatással:

```text
Backend Static Analysis
Backend Tests / SQLite → teljes SQLite suite → SQLite migration round-trip
Backend Tests / MySQL → kapcsolatpróba → teljes MySQL suite
Database Migrations / MySQL → kapcsolatpróba → migration round-trip → alapseeder smoke
```

Egyik job sem használ `needs` függőséget. Az SQLite oda-vissza migráció az SQLite job része; a külön migrációs job a MySQL-sémát ellenőrzi. A workflow megléte nem bizonyítja a GitHub required check beállításait.

### Biztonsági védelem

Az `App\Support\Testing\TestEnvironmentGuard` a feature tesztek első alkalmazás-bootstrapje során, tehát a `RefreshDatabase` migrációi előtt ellenőrzi a tényleges Laravel-konfigurációt. A destruktív migration round-trip parancs ugyanazt a guardot közvetlenül a `migrate:fresh` előtt futtatja.

Engedélyezett adatbázisok:

- SQLite `:memory:`;
- SQLite fájl kizárólag a `storage/framework/testing` könyvtárban;
- MySQL `km_production_test`, `km_production_testing`, illetve ezek egy teszt-worker suffixszel képzett változata.

MySQL esetén csak a `km_testing` felhasználó, valamint a `127.0.0.1`, `localhost` vagy `mysql` host engedélyezett. Tiltott többek között a `km_production`, `production`, `prod`, `live`, `main`, bármely távoli host és minden `DB_URL` override.

A guard ezen felül megköveteli:

- `APP_ENV=testing`;
- array cache és session;
- sync queue;
- array mailer;
- dedikált `testing` filesystem disk;
- null/log broadcast és stderr/null log csatorna.

Hiba esetén magyar üzenettel, nem nulla exit code-dal áll le, nem ír ki jelszót és nem nyit adatbázis-kapcsolatot.

### Környezeti fájlok

A verziókezelt [.env.testing.example](../.env.testing.example) kizárólag tesztértékeket tartalmaz. Másold `.env.testing` néven, ha lokális felülírás kell; a valódi fájlt a `.gitignore` kizárja. Valódi adatbázis-, SMTP-, Redis-, S3- vagy API-credential nem kerülhet bele.

A wrapper a MySQL kapcsolathoz kizárólag a `TEST_MYSQL_*` változókat olvassa, majd felülírja a Laravel `DB_*` értékeit. Így egy lokális `.env` fejlesztői kapcsolata nem szivároghat át a tesztfutásba.

## Eljárás: helyi futtatás

Az alkalmazandó ellenőrzéseket válaszd ki; az alábbi példák nem minden változáshoz kötelező lépéssorok. A teljes SQLite-tesztcsomaghoz és a külön migrációvizsgálathoz:

```bash
composer test:backend:sqlite
composer test:backend:migrations:sqlite
```

Célzott cache-regresszióhoz a `composer test:cache` használható; ez a teljes SQLite-csomag részhalmaza, ezért sikeres teljes futás után nem kell indok nélkül megismételni. Más célzott teszt például:

```bash
php scripts/backend-test-environment.php sqlite test tests/Feature/InventoryTest.php
```

MySQL teljes tesztcsomag és migráció Dockerrel, telepített függőségekkel és a szükséges PHP-adatbázis-bővítménnyel:

```bash
docker compose -f compose.testing.yml up -d mysql-testing
composer test:backend:mysql
composer test:backend:migrations:mysql
docker compose -f compose.testing.yml down -v
```

A service MySQL 8.4-et futtat a host `33060` portján, `tmpfs` adattárral. Az adatbázis `km_production_testing`, a dedikált felhasználó `km_testing`; a repositoryban szereplő jelszavak kizárólag helyi/CI tesztértékek. A `down -v` csak ennek a compose projektnek az erőforrásait célozza, más MySQL volume-hoz nem nyúl.

Meglévő helyi MySQL 8.4 szerveren külön adatbázis és felhasználó hozható létre ugyanilyen nevekkel. Soha ne add meg a fejlesztői adatbázist vagy annak felhasználóját. Eltérő, de továbbra is tesztcélú port esetén például PowerShellben:

```powershell
$env:TEST_MYSQL_PORT = '3306'
composer test:backend:mysql
composer test:backend:migrations:mysql
```

WAMP használatakor a ténylegesen futó MySQL service portja tipikusan `3306`.
A fenti override előtt ellenőrizd, hogy a service MySQL 8.4-et futtat, és hogy
kizárólag a guard által elfogadott `km_production_testing` adatbázist használja
a dedikált `km_testing` felhasználóval. A repository alapértelmezett `33060`
portja a `compose.testing.yml` által indított service-é; a WAMP service-t a
repository nem indítja el automatikusan.

Linux/macOS alatt:

```bash
TEST_MYSQL_PORT=3306 composer test:backend:mysql
TEST_MYSQL_PORT=3306 composer test:backend:migrations:mysql
```

Ha a MySQL nem érhető el, a parancs hibával áll le; nem vált vissza SQLite-ra.

### Összesített parancsok

```bash
composer quality:backend:sqlite
composer quality:backend:mysql
composer quality:backend:all
```

A `quality:backend:all` nem hagyja ki csendben a MySQL-t. A `test:backend:quality` ennek aliasa. A MySQL service indítása szándékosan külön lépés, hogy egy hiányzó vagy hibás service ne adjon félrevezetően zöld eredményt.

## Mit igazol a migrációvizsgálat?

Az alkalmazásteszt azt vizsgálja, hogy egy kérés vagy üzleti művelet a várt eredményt adja-e. A migrációvizsgálat azt, hogy a séma létrehozható, visszabontható és ismét felépíthető-e az adott motoron. Az adatbázismotor ellenőrzése pedig azt jelenti, hogy a releváns alkalmazás- és sémavizsgálatok ténylegesen azon a motoron futnak. Ezek nem helyettesítik egymást: a Feature teszt adatbázis-előkészítése nem teljes migrációs visszaállítási próba.

A SQLite-változat az izolált tesztkörnyezet sémájának működését ellenőrzi; a MySQL-változat a termelési motorral való kompatibilitást. Az alkalmazandó MySQL-migrációt a SQLite-migráció sikere sem váltja ki.

A két `test:backend:migrations:*` parancs a [DatabaseRoundTrip](../app/Console/Commands/DatabaseRoundTrip.php) műveletét indítja egyetlen, már ellenőrzött Laravel-folyamatban:

```text
migrate:fresh → teljes rollback → migrate → InitialInstallationSeeder kétszer
```

Az ismételt seeder futás az alap szerepkörök, jogosultságok és adminfelhasználó ismételt létrehozásának biztonságát vizsgálja. Konkrétan mindkét futás sikerét, majd a `super-admin` szerepkör, legalább egy jogosultság és az `admin@example.com` felhasználó létezését ellenőrzi. Ez korlátozott idempotencia-ellenőrzés, nem minden rekord változatlanságának bizonyítása. Teljes demo- vagy E2E-seedelés és meglévő termelési adatok megőrzésének próbája nem része ennek a vizsgálatnak.

## Adatbázismotorok eltérései

| Terület          | SQLite                             | MySQL                       | Projektmegoldás                                    |
| ---------------- | ---------------------------------- | --------------------------- | -------------------------------------------------- |
| Foreign key      | PRAGMA-alapú                       | InnoDB                      | Mindkét gate-ben bekapcsolva                       |
| Dátum/idő        | SQLite függvények                  | MySQL függvények            | UTC és izolált driver-specifikus expression helper |
| JSON             | szöveges/JSON1 viselkedés          | natív JSON                  | Eloquent cast és Query Builder preferált           |
| Group by         | megengedőbb lehet                  | strict `ONLY_FULL_GROUP_BY` | strict MySQL gate                                  |
| Decimal          | gyakran numerikus/string konverzió | DECIMAL precision           | modell cast és kétmotoros suite                    |
| Case sensitivity | collationfüggő                     | `utf8mb4_unicode_ci`        | felhasználói keresésnél dokumentált collation      |
| Index rollback   | SQLite tábla-újraépítés            | natív ALTER                 | index eltávolítása oszlop előtt                    |

A reporting és manufacturing-intelligence repositoryk néhány dátum- és aggregációs expressiont driver szerint izolálnak. A többi lekérdezés adatbázisfüggetlen Query Builder/Eloquent formát használ.

## Időzóna, izoláció és párhuzamosítás

Az alkalmazás, PHP wrapper és MySQL session UTC-t használ. A suite a Carbon tesztidőt a Laravel teardown során visszaállítja; dátumfüggő teszteknek fix dátumot vagy explicit munkanapot kell használniuk.

A backend gate sorosan fut. A párhuzamos MySQL-futtatás csak külön worker adatbázisokkal (`km_production_testing_1`, stb.) lenne biztonságos; ez nincs bekapcsolva. Az SQLite minden processzben külön `:memory:` adatbázist használ. A filesystem alapértelmezett gyökere `storage/framework/testing/backend`, a célzott feltöltési tesztek továbbra is `Storage::fake()` disket használnak.

## GitHub Actions

A [.github/workflows/backend-quality.yml](../.github/workflows/backend-quality.yml) stabil jobnevei:

- `Backend Static Analysis` — 15 perc;
- `Backend Tests / SQLite` — 40 perc;
- `Backend Tests / MySQL` — 50 perc;
- `Database Migrations / MySQL` — 20 perc.

A MySQL job és migration job ephemeral `mysql:8.4` service-t, dedikált
tesztadatbázist, service health checket és külön SQL kapcsolatpróbát,
`utf8mb4` charsetet, valamint `utf8mb4_unicode_ci` collationt használ. A
Composer cache kulcsa tartalmazza az operációs rendszert, a PHP 8.4 verziót és
a `composer.lock` hashét; a `vendor`, környezeti fájl és adatbázis nem kerül a
cache-be.

Az SQLite és MySQL JUnit riportok hét napos artifactként töltődnek fel. A MySQL artifact ezen felül csak a verziót, charsetet, collationt, session SQL mode-ot és időzónát tartalmazza. `.env`, dump, storage vagy credential nem kerül artifactba.

Sikertelen suite esetén a workflow a JUnit riport első tíz hibáját GitHub
annotációként is megjeleníti, majd az eredeti nem nulla teszt-exit miatt
blokkol. A backend feature tesztek a közös `Tests\TestCase` `withoutVite()`
beállításával nem függenek `public/hot` vagy `public/build` artifacttól. Az
Inertia page-finder explicit a case-sensitive `resources/js/Pages` útvonalat
használja, ezért ugyanazt ellenőrzi Windows és Linux alatt.

A workflow nem használ `continue-on-error` beállítást. A required checkekre vonatkozó ajánlást és ellenőrzést a [code review útmutató](project-management/code-review-guide.md) kezeli; a tényleges GitHub-beállítás külön igazolást igényel. A CI jelenleg dokumentációs változásra is elindulhat, de ez nem teszi minden dokumentációs feladat helyi követelményévé az alkalmazásteszteket.

## Bizonyíték: a futás eredménye és korlátai

A [DoD](project-management/definition-of-done.md) szerint rögzítsd a parancsot, dátumot vagy futásazonosítót, környezetet, adatbázismotort és megfigyelt eredményt:

- `PASSED`: az adott ellenőrzés ténylegesen lefutott és sikeres volt.
- `FAILED`: az érdemi teszt vagy migrációvizsgálat lefutott és hibát talált.
- `BLOCKED`: az alkalmazandó MySQL-vizsgálat igazoltan nem végezhető el, például a szükséges tesztszerver nem érhető el. A technikai hibakódot is őrizd meg; SQLite-sikerből nem lesz MySQL-siker.
- `NOT RUN`: az ellenőrzést nem indították el, vagy egy korábbi hiba miatt már nem jutott rá sor. Az előkészítés puszta elmaradása nem igazolt környezeti akadály.

Az összesített parancs korai leállása után csak a ténylegesen sikeres lépéseket jelöld `PASSED`-nek. Minden `FAILED`, `BLOCKED` és `NOT RUN` tételnél add meg az okot, hatást, felelőst és következő lépést. Alkalmazandó kötelező MySQL-ellenőrzés hiányában a feladat nem teljesen kész.

A [2026-07-28-i MySQL-audit](audits/backend-mysql-quality-gates-2026-07-28.md) korábbi helyi és CI-futások bizonyítéka. Nem aktuális teszteredmény és nem minden feladatra kötelező futtatási lista.

## Hibakeresés és biztonságos leállítás

- Guard hiba: a magyar üzenetben megnevezett drivert állítsd vissza a tesztértékre; ne lazítsd a guardot.
- MySQL connection refused: ellenőrizd a compose health állapotát és a `TEST_MYSQL_PORT` értékét. Docker hiányában ellenőrizd külön a WAMP MySQL 8.4 service állapotát, majd használd a dokumentált `3306` port override-ot; ne válts SQLite-ra.
- Collation vagy strict-mode hiba: a queryt vagy sémát javítsd; ne kapcsold ki a strict módot.
- Maradt tesztfájl: csak a `storage/framework/testing/backend` könyvtárat ellenőrizd; fejlesztői upload könyvtárat ne törölj.
- Leállítás: `docker compose -f compose.testing.yml down -v`. A helyi, manuálisan létrehozott tesztadatbázist csak név és guard ellenőrzése után szabad törölni.
