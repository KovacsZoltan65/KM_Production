# Backend MySQL és migrációs quality gate audit — 2026-07-28

## Döntés

A `CI-003` lokális elfogadási feltételei teljesültek. A végleges `done` státusz
feltétele a jelen módosítást tartalmazó GitHub Actions `Backend quality gate`
workflow sikeres lefutása. Az audit lezárásakor a távoli futás még nem állt
rendelkezésre.

## Hatókör és baseline

- Kiinduló commit: `d0e10047c3825f3b3e8ca71ed6827f4d5ed1508f`.
- PHP: 8.4.15; Composer: 2.8.5.
- Helyi adatbázis: MySQL 8.4.7.
- Tesztsuite: 348 teszt, 955 assertion.
- Migrációk: 47 verziókezelt migráció.
- Használt adatbázis: kizárólag `km_production_testing`, dedikált
  `km_testing` felhasználóval.

A guardot az adatbázis-kapcsolat és minden destruktív migrációs futás előtt
ellenőriztem. Docker nem volt telepítve, ezért a helyi WAMP MySQL 8.4.7
szolgáltatás kapott külön tesztadatbázist és kizárólag arra érvényes
jogosultságot. Fejlesztői vagy production adatbázis nem volt scope-ban.

## Auditmegállapítások

### Workflow

A workflow már külön SQLite- és MySQL-tesztjobot, MySQL 8.4 service-t,
health checket, dedikált tesztcredentialt és JUnit artifactokat használt.
Valamennyi job `needs` nélkül, egymástól függetlenül indul.

A feltárt szerkezeti rés az volt, hogy a `Backend Migrations` job egymás után
futtatta az SQLite és MySQL round-tripet. Emiatt a check neve nem jelezte,
melyik adatbázismotor migrációja hibázott, és az SQLite felelősség indokolatlanul
a MySQL service-es jobban maradt.

### Teszt- és migrációs infrastruktúra

- A Composer-scriptek külön SQLite-, MySQL- és motoronkénti migrációs
  belépési pontot adnak.
- A wrapper kizárólag `TEST_MYSQL_*` változókból építi fel a MySQL
  tesztkapcsolatot; nincs csendes SQLite fallback.
- A `TestEnvironmentGuard` csak lokális, név szerint tesztcélú adatbázist,
  dedikált felhasználót és izolált cache/session/queue/filesystem beállítást
  enged.
- Mind a 47 migrációnak van `down()` metódusa; üres rollback implementációt
  és migrációba ágyazott nyers SQL-t nem találtam.
- A driverfüggő dátum- és aggregációs kifejezések repository-szinten,
  explicit SQLite/MySQL ágakban vannak. A Laravel MySQL kapcsolat
  `strict => true`, `utf8mb4` és `utf8mb4_unicode_ci` beállítású.

## Megvalósítás

- Az SQLite migration round-trip és kétszeri seeder smoke a
  `Backend Tests / SQLite` jobba került.
- A külön migrációs job azonosítója `migrations-mysql`, stabil megjelenített
  neve `Database Migrations / MySQL`.
- A külön migrációs job kizárólag MySQL-t futtat, ezért nem telepít felesleges
  SQLite PHP-extensionöket.
- Mindkét MySQL job explicit `SELECT 1` kapcsolatpróbát kapott a teljes suite,
  illetve a destruktív round-trip előtt.
- A jobok továbbra is függetlenek; nincs `needs` és nincs
  `continue-on-error`.

## Lokális pozitív evidence

| Ellenőrzés | Ismétlés | Eredmény |
| --- | ---: | --- |
| SQLite baseline, módosítás előtt | 3/3 | 348 teszt, 955 assertion, exit 0 minden körben |
| MySQL 8.4 teljes suite | 3/3 | 348 teszt, 955 assertion, exit 0 minden körben |
| MySQL migration round-trip | 2/2 | teljes rollback és re-migrate, 2/2 seed smoke, exit 0 |
| SQLite teljes suite, módosítás után | 5/5 | 348 teszt, 955 assertion, exit 0 minden körben |
| SQLite migration round-trip | 1/1 | teljes rollback és re-migrate, 2/2 seed smoke, exit 0 |

Az első, Xdebuggal indított SQLite baseline próbát a futási idő miatt
megszakítottam, és nem számítottam bele a 3/3 eredménybe. A megismételt
futások `XDEBUG_MODE=off` mellett készültek.

A harmadik MySQL suite alatt a host rendszerórája nagyot ugrott. A futás
348/348 teszttel és exit 0-val befejeződött, de annak időtartama nem
használható teljesítménymérésként. A stabilitási döntés teszt- és
assertionszámon, valamint exit code-on alapul.

## Adatbázis-beállítások

| Beállítás | Helyi evidence |
| --- | --- |
| Verzió | MySQL 8.4.7 |
| Database charset | `utf8mb4` |
| Database collation | `utf8mb4_unicode_ci` |
| Kliens session SQL mode | üres |
| Kliens session timezone | `SYSTEM` |
| Laravel connection | strict mód bekapcsolva, alkalmazás UTC |

A helyi kliens session üres SQL mode-ja környezeti eltérés. A Laravel által
nyitott kapcsolat strict konfigurációját a teljes 3/3 MySQL suite gyakorolta.
A GitHub Actions MySQL artifact a CI-kapcsolat tényleges verzióját,
charsetjét, collationjét, session SQL mode-ját és timezone-ját rögzíti.

## Kontrollált hibaszimulációk

| Forgatókönyv | Elvárt | Tényleges |
| --- | --- | --- |
| SQLite nem létező tesztfájl | blokkoló nem nulla exit | exit 2 |
| MySQL suite elérhetetlen tesztporton | kapcsolat hiba, nincs fallback | 10 teszt hibázott, exit 2 |
| MySQL migration nem létező, tesztnevű adatbázison | guardolt kapcsolat/migráció blokkol | jogosultsági hiba, exit 1 |

Mindhárom negatív próba nem nulla exit code-dal állt le, és nem érintett
fejlesztői vagy production adatbázist.

## Ismert eltérések és maradék kockázat

- A lokális környezet MySQL session SQL mode-ja eltérhet az ephemeral CI
  service-től; a távoli artifact az irányadó CI-evidence.
- A projekt munkafájában három, a `CI-003`-tól független felhasználói
  frontend-módosítás volt jelen. Ezeket nem stage-eltem és nem módosítottam.
- A globális `git diff --check` két trailing whitespace hibát jelez a
  felhasználói `AdminCrudPage.vue` változtatásban. A `CI-003` saját fájljaira
  szűkített whitespace-ellenőrzés sikeres.
- Repository-szintű required check beállítás továbbra is a `CI-005` és
  `GOV-005` feladata.

## GitHub Actions evidence

Függőben: a push után a workflow URL-je, commit SHA-ja, négy stabil jobneve,
eredménye és a MySQL settings artifact ellenőrzése ide kerül.

## Következő feladat

A végrehajtási sorrend következő önálló eleme a `CI-004`, a teljes Playwright
E2E-kapu aktuális futtatása.
