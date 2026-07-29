# Backend MySQL és migrációs quality gate audit — 2026-07-28

## Döntés

A `CI-003` elfogadási feltételei teljesültek, státusza `done`. A lokális
SQLite/MySQL ismétlések, mindkét motor migration round-tripje és a
`c089d3cc4aabfd5abe94393337fc21e5c86f1b52` commit valós GitHub Actions
`Backend quality gate` futása sikeres.

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
- A közös backend `TestCase` kikapcsolja a Vite asset-feloldást, ezért a
  backend feature suite nem függ ignorált lokális dev-server vagy build
  artifacttól.
- Az Inertia page-finder projektkonfigurációja a tényleges, case-sensitive
  `resources/js/Pages` útvonalat használja.
- A PHPStan feltételesen létező `node_modules` és `public/build` kizárásai
  opcionális útvonalként szerepelnek; baseline és hibaelnémítás nem került be.
- Sikertelen suite esetén legfeljebb tíz JUnit hiba publikus GitHub
  annotációként jelenik meg. A Larastan nem nulla exitje rövidített
  stdout/stderr annotációt is ad, miközben a gate blokkoló marad.

## Lokális pozitív evidence

| Ellenőrzés | Ismétlés | Eredmény |
| --- | ---: | --- |
| SQLite baseline, módosítás előtt | 3/3 | 348 teszt, 955 assertion, exit 0 minden körben |
| MySQL 8.4 teljes suite | 3/3 | 348 teszt, 955 assertion, exit 0 minden körben |
| MySQL migration round-trip | 2/2 | teljes rollback és re-migrate, 2/2 seed smoke, exit 0 |
| SQLite teljes suite, módosítás után | 5/5 | 348 teszt, 955 assertion, exit 0 minden körben |
| SQLite migration round-trip | 1/1 | teljes rollback és re-migrate, 2/2 seed smoke, exit 0 |
| SQLite suite, Linux/CI-fixek után | 1/1 | 348 teszt, 955 assertion, exit 0 |
| MySQL suite, Linux/CI-fixek után | 1/1 | 348 teszt, 955 assertion, exit 0 |
| Friss-checkout szimuláció | 2/2 célzott fájl | Vite hot/manifest nélkül 30/30 teszt zöld |

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

A 2026-07-29-én befejezett
[Backend quality gate #30428224526](https://github.com/KovacsZoltan65/KM_Production/actions/runs/30428224526)
futás a `c089d3cc4aabfd5abe94393337fc21e5c86f1b52` commiton:

| Stabil jobnév | Eredmény |
| --- | --- |
| `Backend Static Analysis` | success |
| `Backend Tests / SQLite` | success |
| `Backend Tests / MySQL` | success |
| `Database Migrations / MySQL` | success |

A MySQL kapcsolatpróba, teljes suite, settings-rögzítés és artifactfeltöltés
külön-külön sikeres lépés volt. A futás létrehozta a hét napig megőrzött
`backend-mysql-results` artifactot (ID `8714474696`) és a
`backend-sqlite-junit` artifactot (ID `8714448544`). A MySQL artifact a
workflow szerint JUnit riportot és a verzió/charset/collation/session
SQL-mode/timezone lekérdezés kimenetét tartalmazza; credential, `.env` vagy
adatbázisdump nem kerül bele.

Az első távoli futások két, Windows alatt rejtve maradó friss-checkout hibát
tártak fel és bizonyítottak:

1. a backend tesztek ignorált Vite hot/manifest fájltól függtek;
2. az Inertia vendor alapútvonala kisbetűs `resources/js/pages` volt, miközben
   a Gitben követett könyvtár `resources/js/Pages`;
3. a Larastan nem létező, de kizárt frontend könyvtárakat kötelező útvonalként
   validált.

Mindhárom gyökérok javítása után a teljes workflow zöld lett.

## Következő feladat

A végrehajtási sorrend következő önálló eleme a `CI-004`, a teljes Playwright
E2E-kapu aktuális futtatása.
