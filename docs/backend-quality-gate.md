# Backend quality gate

## Cél

A backend quality gate ugyanazt a Laravel/Pest tesztcsomagot futtatja PHP 8.4 alatt SQLite és MySQL 8.4 adatbázismotoron. Az SQLite gyors, izolált visszajelzést ad, a MySQL pedig a productionhöz közeli strict SQL-, collation-, tranzakciós és séma-viselkedést ellenőrzi. Egyik motor sikere sem helyettesíti a másikat.

A blokkoló ellenőrzési lánc:

```text
Composer validate → Pint → Larastan level 5 → teljes SQLite suite
→ teljes MySQL suite → migration round-trip → alapseeder smoke → git diff check
```

## Biztonsági guard

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

## Környezeti fájlok

A verziókezelt [.env.testing.example](../.env.testing.example) kizárólag tesztértékeket tartalmaz. Másold `.env.testing` néven, ha lokális felülírás kell; a valódi fájlt a `.gitignore` kizárja. Valódi adatbázis-, SMTP-, Redis-, S3- vagy API-credential nem kerülhet bele.

A wrapper a MySQL kapcsolathoz kizárólag a `TEST_MYSQL_*` változókat olvassa, majd felülírja a Laravel `DB_*` értékeit. Így egy lokális `.env` fejlesztői kapcsolata nem szivároghat át a tesztfutásba.

## Helyi futtatás

SQLite teljes suite:

```bash
composer test:cache
composer test:backend:sqlite
composer test:backend:migrations:sqlite
```

MySQL teljes suite Dockerrel:

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

Linux/macOS alatt:

```bash
TEST_MYSQL_PORT=3306 composer test:backend:mysql
TEST_MYSQL_PORT=3306 composer test:backend:migrations:mysql
```

Ha a MySQL nem érhető el, a parancs hibával áll le; nem vált vissza SQLite-ra.

## Összesített parancsok

```bash
composer quality:backend:sqlite
composer quality:backend:mysql
composer quality:backend:all
```

A `quality:backend:all` nem hagyja ki csendben a MySQL-t. A `test:backend:quality` ennek aliasa. A MySQL service indítása szándékosan külön lépés, hogy egy hiányzó vagy hibás service ne adjon félrevezetően zöld eredményt.

## Migráció és seeder smoke

A két `test:backend:migrations:*` parancs egyetlen, már guardolt Laravel-processzben hajtja végre:

```text
migrate:fresh → teljes rollback → migrate → InitialInstallationSeeder kétszer
```

Az ismételt seeder futás bizonyítja az alap role-, permission- és adminfelhasználó-seedelés elvárt idempotenciáját. A parancs ellenőrzi a kötelező rekordok létrejöttét is. Teljes demo- vagy E2E-seedelés nem része ennek a smoke-nak.

## Adatbázismotorok eltérései

| Terület | SQLite | MySQL | Projektmegoldás |
| --- | --- | --- | --- |
| Foreign key | PRAGMA-alapú | InnoDB | Mindkét gate-ben bekapcsolva |
| Dátum/idő | SQLite függvények | MySQL függvények | UTC és izolált driver-specifikus expression helper |
| JSON | szöveges/JSON1 viselkedés | natív JSON | Eloquent cast és Query Builder preferált |
| Group by | megengedőbb lehet | strict `ONLY_FULL_GROUP_BY` | strict MySQL gate |
| Decimal | gyakran numerikus/string konverzió | DECIMAL precision | modell cast és kétmotoros suite |
| Case sensitivity | collationfüggő | `utf8mb4_unicode_ci` | felhasználói keresésnél dokumentált collation |
| Index rollback | SQLite tábla-újraépítés | natív ALTER | index eltávolítása oszlop előtt |

A reporting és manufacturing-intelligence repositoryk néhány dátum- és aggregációs expressiont driver szerint izolálnak. A többi lekérdezés adatbázisfüggetlen Query Builder/Eloquent formát használ.

## Időzóna, izoláció és párhuzamosítás

Az alkalmazás, PHP wrapper és MySQL session UTC-t használ. A suite a Carbon tesztidőt a Laravel teardown során visszaállítja; dátumfüggő teszteknek fix dátumot vagy explicit munkanapot kell használniuk.

A backend gate sorosan fut. A párhuzamos MySQL-futtatás csak külön worker adatbázisokkal (`km_production_testing_1`, stb.) lenne biztonságos; ez nincs bekapcsolva. Az SQLite minden processzben külön `:memory:` adatbázist használ. A filesystem alapértelmezett gyökere `storage/framework/testing/backend`, a célzott feltöltési tesztek továbbra is `Storage::fake()` disket használnak.

## GitHub Actions

A [.github/workflows/backend-quality.yml](../.github/workflows/backend-quality.yml) stabil jobnevei:

- `Backend Static Analysis` — 15 perc;
- `Backend Tests / SQLite` — 40 perc;
- `Backend Tests / MySQL` — 50 perc;
- `Backend Migrations` — 20 perc.

A MySQL job és migration job ephemeral `mysql:8.4` service-t, dedikált tesztadatbázist, health checket, `utf8mb4` charsetet és `utf8mb4_unicode_ci` collationt használ. A Composer cache kulcsa tartalmazza az operációs rendszert, a PHP 8.4 verziót és a `composer.lock` hashét; a `vendor`, környezeti fájl és adatbázis nem kerül a cache-be.

Az SQLite és MySQL JUnit riportok hét napos artifactként töltődnek fel. A MySQL artifact ezen felül csak a verziót, charsetet, collationt, session SQL mode-ot és időzónát tartalmazza. `.env`, dump, storage vagy credential nem kerül artifactba.

Branch protection alatt mind a négy fenti checket required státuszra kell állítani. A workflow nem használ `continue-on-error` beállítást.

## Hibakeresés és biztonságos leállítás

- Guard hiba: a magyar üzenetben megnevezett drivert állítsd vissza a tesztértékre; ne lazítsd a guardot.
- MySQL connection refused: ellenőrizd a compose health állapotát és a `TEST_MYSQL_PORT` értékét.
- Collation vagy strict-mode hiba: a queryt vagy sémát javítsd; ne kapcsold ki a strict módot.
- Maradt tesztfájl: csak a `storage/framework/testing/backend` könyvtárat ellenőrizd; fejlesztői upload könyvtárat ne törölj.
- Leállítás: `docker compose -f compose.testing.yml down -v`. A helyi, manuálisan létrehozott tesztadatbázist csak név és guard ellenőrzése után szabad törölni.
