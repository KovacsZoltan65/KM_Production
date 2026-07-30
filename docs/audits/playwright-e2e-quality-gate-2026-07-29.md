# Playwright E2E quality gate audit — 2026-07-29

## Scope

A teljes Playwright architektúra, adat- és locale-izoláció, PHP szerver,
readiness, user feedback lefedettség, failure artifactok és a `Frontend`
GitHub Actions workflow `CI-004` szerinti vizsgálata. Kiindulás:
`messages` branch, `1957168 Add centralized user feedback notifications`,
tiszta working tree.

## Initial architecture and inventory

| Terület | Kiinduló állapot |
| --- | --- |
| Tesztfájlok | 15 fájl |
| Projektpéldányok | 26 teszt: Chromium 21, Firefox 2, WebKit 2, mobile Chromium 1 |
| Auth | valódi UI login, tesztuser, storage state nélkül |
| Adat | suite-szintű `migrate:fresh`; több, de nem minden teszt előtt részleges újraseed |
| Locale | `.env.e2e` angol alapnyelv, explicit minden-teszt ellenőrzés nélkül |
| Szerver | PHP built-in server, 1 worker, stdout/stderr a konzolra |
| Readiness | kizárólag `HEAD /login` |
| Artifact | trace és screenshot; video, JUnit és külön szerverlog nélkül |
| Retry | helyben 0, CI-ben 1 |

Lefedett felhasználói területek: login/logout, permission, head kezelés,
customer order, production plan, production task + quality, goods receipt,
stock reservation, document versioning, CRUD feedback, accessibility,
keyboard, cross-browser és mobile smoke.

## Baseline

Az első változtatás előtti `npm run test:e2e` 4,6 perc alatt 21/26 tesztet
teljesített. Hibák:

| Hiba | Osztály | Bizonyíték és gyökérok |
| --- | --- | --- |
| mobile spec Chromium és mobile Chromium alatt | projekt-hozzárendelés + locator | A desktop Chromium tévesen felvette a mobile-only fájlt. A `Dashboard` részszöveg hat raw translation-key headinget talált; a stabil user-facing cím `Admin Dashboard`. |
| goods receipt post | locator | A széles `getByText("Posted")` egyszerre találta a státuszt és a `Goods receipt posted.` toastot. |
| Firefox két smoke | lokális browser/OS | Az oldal létrehozása előtt `RenderCompositorSWGL failed mapping default framebuffer` és tab-subprocess spawn hiba; WebKit és Chromium ugyanabban a körben működött. |
| korábbi közös notification batch | adat-, locale- és readiness-izoláció | Az újraseed nem törölte a létrehozott goods receiptet, stock movementet, sessiont és notification itemet; a tesztek egy része részleges mutable baseline-t kapott. |

Az audit közben külön valódi alkalmazáshiba igazolódott: amikor Laravel üres
filtereket JSON tömbként adott át, az `AdminCrudPage` az örökölt
`Array.prototype.sort` függvényt küldte a query `sort` mezőjében. Ez
`sort=function sort()...` URL-t és megszakított Inertia kérést okozott.

## Architecture decisions and fixes

- Chromium kizárja a mobile-only specet; a mobile projekt az egyetlen gazdája.
- Retry minden környezetben 0, worker 1, `fullyParallel` false.
- Minden teszt automatikus fixture resetet, új contextet és cookie-törlést kap.
- Az E2E seeder környezetvédett, törli a teszttulajdonú session-, audit-,
  item-, document-, goods receipt- és stock movement állapotot, majd
  determinisztikusan visszaállítja a domain fixture-öket.
- Az auth helper ellenőrzi az angol locale landmarkot; a locale scenario a
  valódi, akadálymentesen elnevezett selectort használja hu → en irányban.
- A readiness migrációstátuszt, Vite manifestet, `/up`, `/login` és egy
  production assetet ellenőriz bounded pollinggal.
- A PHP szerver stdout/stderr a `test-results/e2e-server.log` fájlba kerül; a
  teardown hibaágon is leállít és visszaállítja a `public/hot` fájlt.
- Az automatikus browser monitor váratlan page/console/request hibán kívül az
  alkalmazás 4xx/5xx és a kritikus asset 4xx/5xx válaszokat is blokkolja.
- A failure policy: trace retain-on-failure, screenshot only-on-failure, video
  retain-on-failure, HTML és JUnit riport.

## Test and locator changes

- `goods-receipts.spec.js`: exact `Posted` státusz.
- `mobile.spec.js`: level-1, exact user-facing `Admin Dashboard`.
- Minden korábban közvetlenül resetelő spec az automatikus `e2eData` fixture-t
  használja; az ID-t igénylők abból kapják determinisztikus azonosítójukat.
- `user-feedback.spec.js`: fix tesztkódok, stabil global-toast gyökér,
  explicit request completion, kereséssel célzott saját rekord, duplikált toast
  count, stale flash reload, védett self-delete és hu → en locale.
- `AdminCrudPage.vue`: csak string filter fogadható el kezdeti sortként;
  frontend regressziós teszt védi az üres Laravel tömb esetét.
- `LocaleSelector.vue`: stabil, user-facing accessible name és
  `data-test="locale-selector"`.

Business logic nem változott. Az egyetlen application-code viselkedésjavítás a
hibás rendezési query normalizálása; a data-test/aria attribútum nem módosít
üzleti szabályt.

## User-feedback coverage

| Követelmény | Scenario |
| --- | --- |
| Create | angol és magyar item létrehozás, modal záródás, toast, célzott row |
| Validation | modal nyitva marad, mezőhiba és pontosan egy generikus error toast |
| Update | saját angol rekord neve frissül, success toast |
| Delete | confirmation, success toast, row eltűnik |
| Controlled failure | bejelentkezett admin saját userének törlése tiltott; egy safe toast, user megmarad, oldal használható |
| Hungarian | `Sikeresen létrehozva.` |
| English | create/update/delete és locale success |
| Duplicate toast | validation toast count pontosan 1 |
| Stale flash | delete utáni reloadon a korábbi toast count 0 |

## Parallelism and retry

Az egyetlen SQLite fájl és a PHP built-in szerver miatt az elsődleges kapu
szándékosan egy worker. Ezt nem order dependency elfedésére használja: minden
teszt saját resetet kap, és a korábbi hibás batch háromszoros közös futása külön
bizonyíték. Retry nincs; minden rögzített zöld eredmény első próbálkozás.

## Local evidence

| Futás | Eredmény | Időtartam |
| --- | --- | --- |
| Baseline teljes | 21/26 | 4,6 perc |
| Első javított teljes Chromium | 22/22 | 5,3 perc |
| Korábban hibás batch, `repeat-each=3` | 27/27 | 7,7 perc |
| WebKit + mobile smoke | 3/3 | az 5 tesztes kör része, 58,4 mp |
| Firefox smoke | 0/2 | browser context előtti lokális compositor/subprocess hiba |
| Végső teljes Chromium #1 | 22/22 | 5,5 perc |
| Végső teljes Chromium #2 | 22/22 | 4,1 perc |
| Végső teljes Chromium #3 | 22/22 | 3,5 perc |
| Explicit fordított `--test-list` kiválasztás | 22/22 | 4,9 perc |
| Friss dependency/prepare/build utáni teljes Chromium | 22/22 | 3,4 perc |

A három egymást követő teljes Chromium-kör retry nélkül zöld. A fordított
`--test-list` mind a 22 tesztet kiválasztotta és zöld lett, de Playwright a
végrehajtást a saját kanonikus sorrendjére rendezte; ezért ez nem tekinthető
igazolt, ténylegesen fordított végrehajtási sorrendnek.

## Regression and clean-setup evidence

| Kapu | Eredmény |
| --- | --- |
| `npm ci` | sikeres, 248 package |
| Composer install, Xdebug nélkül | sikeres, lockfile-változás nélkül; package discovery sikeres |
| `composer validate --strict` | érvényes `composer.json` és `composer.lock` |
| `npm run test:e2e:prepare` | sikeres `migrate:fresh` + seed |
| `vendor/bin/pint --test` | sikeres |
| `vendor/bin/phpstan analyse --memory-limit=1G --no-progress` | nincs hiba |
| `php artisan test` | 379 passed, 1067 assertion |
| `npm run test` | 22 fájl, 190 teszt passed |
| `npm run test:frontend:coverage` | 190 passed; statement 80,80%, branch 81,43%, function 65,37%, line 80,69% |
| `npm run i18n:check` | 727 kulcs szinkronban |
| `npm run build` | sikeres, 957 modul |

A Composer autoload egy meglévő `FailingCodeRepository` tesztosztályra adott
PSR-4 figyelmeztetést; ez nem ebből a változásból származik és a package
discoveryt vagy a regressziós teszteket nem blokkolta. MySQL-kör nem volt
szükséges: a változás nem érint sémát vagy MySQL-specifikus logikát.

## Negative simulations

| Injektált hiba | Tényleges eredmény | Diagnosztika | Revert |
| --- | --- | --- | --- |
| nem létező heading assertion | exit 1 | pontos locator/call log, screenshot, video, trace, HTML/JUnit | igen |
| szerverindítás kihagyva, nem elérhető local port | global setup exit 1, teszt nem indult | bounded readiness hiba base URL-lel és server-log útvonallal | env törölve |
| E2E SQLite ideiglenesen félretéve | global setup exit 1, teszt nem indult | `E2E database is missing` | fájl visszaállítva |
| e2e-router szándékos HTTP 500 | browser monitor exit 1 | response 500 + console error, screenshot, video, trace, HTML/JUnit | ideiglenes teszt és env eltávolítva |

## GitHub Actions implementation

Workflow: `Frontend`.

- Kötelezőkapu-jelölt job: `Playwright E2E`.
- Node 24, PHP 8.4, SQLite, Chromium, 1 worker, 0 retry.
- Composer install, `npm ci`, package-kompatibilis browser install, E2E prepare,
  production build, readiness és teljes Chromium.
- A cross-browser job egy invocationben futtatja a Firefox, WebKit és mobile
  Chromium projekteket.
- Mindkét job hét napos, run-attempttel névterezett HTML/JUnit/test-results és
  server-log artifactot tölt fel.

Ehhez a nem commitolt változásállapothoz GitHub Actions futás nem hozható létre
commit/push/PR nélkül. Ezek a műveletek kifejezetten tiltottak voltak, ezért
aktuális run URL és zöld Actions-bizonyíték nincs.

## Remaining limitations

- A Windows Firefox browser subprocess/compositor hiba alkalmazásbetöltés előtt
  fennáll; tesztet nem hagytunk ki és assertiont nem gyengítettünk.
- A módosítások GitHub-hosted Linux futása nincs igazolva, mert commit/push/PR
  nem volt engedélyezett.
- Repository branch protection required-check beállítása külön `CI-005` /
  `GOV-005` feladat és repository-konfiguráció.

## Status

`partially done`: a három végső teljes helyi Chromium-kör rendelkezésre áll, de
a zöld GitHub Actions URL commit/push/PR jogosultság hiányában nem.
