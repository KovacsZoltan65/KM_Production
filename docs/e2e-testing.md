# Böngészős folyamatellenőrzés Playwrighttal

## Mire való az E2E teszt?

Az E2E teszt azt vizsgálja, hogy egy fontos felhasználói folyamat végig működik-e
a futó alkalmazásban. Például a felhasználó bejelentkezik, megnyit egy rendelést,
elvégzi a műveletet, és a várt eredményt látja. A vizsgálat átlépi a böngésző,
a frontend és a folyamatban részt vevő backend határait.

Ez kiegészíti a unit, Feature és [frontendkomponens-teszteket](frontend-testing.md).
Nem helyettesíti őket, és csak a lefutott forgatókönyveket, böngészőprojekteket
és környezetet igazolja. Az SQLite-ra épülő E2E nem bizonyítja a termelési
MySQL saját viselkedését vagy a termelési telepítés helyességét; a szükséges
adatbázis-vizsgálat külön [backendellenőrzés](backend-quality-gate.md).

## Szabály: mikor szükséges?

E2E különösen fontos kritikus üzleti műveleteknél, több oldalon átívelő
folyamatoknál, a felhasználó számára látható frontend/backend együttműködésnél,
bejelentkezésnél és jogosultságoknál. Böngészőfüggő megjelenítés, billentyűzetes
használat vagy akadálymentesség változásakor a megfelelő böngészős vizsgálatot
válaszd. A tesztkör az érintett működés kockázatát kövesse.

A kiválasztást a [rétegezett ellenőrzések](development/quality-gates.md), a
teszttervezést a [tesztelési szabályok](../.kiro/steering/testing.md), a lezárást
és eredményjelentést a [Definition of Done](project-management/definition-of-done.md)
határozza meg. Nem minden módosítás igényel E2E-t. Csak dokumentációs változásnál
nem automatikus követelmény; futtatható viselkedést meghatározó dokumentumnál
viszont a tényleges hatást kell értékelni.

A `composer qa:full` jelenleg csak a `tests/e2e/admin` könyvtárat futtatja a
`chromium` projektben. Nem indítja az összes Playwright-projektet, és a teljes
Chromium-tesztkört sem. Az alkalmazandó további folyamatokat vagy böngészőket
külön kell ellenőrizni. A pontos összeállítás forrása a
[GatePlanner](../app/Support/QualityGate/GatePlanner.php); a
[tools/quality-gate.php](../tools/quality-gate.php) a futtató belépési pontja.

## Megvalósítás: konfiguráció és böngészőprojektek

A [playwright.config.js](../playwright.config.js) jelenlegi beállításai:

| Beállítás             | Érték                                                                                                |
| --------------------- | ---------------------------------------------------------------------------------------------------- |
| Tesztkönyvtár         | `tests/e2e`                                                                                          |
| Alap URL              | `E2E_BASE_URL`, alapértelmezésben `http://127.0.0.1:8001`                                            |
| Szerverindítás        | Nincs `webServer` beállítás; saját `globalSetup` és `globalTeardown` kezeli.                         |
| Párhuzamosítás        | `workers: 1`, `fullyParallel: false`                                                                 |
| Újrapróbálás          | `retries: 0`, helyben és CI-ben is                                                                   |
| Teszt időkorlátja     | 60 másodperc                                                                                         |
| Elvárás időkorlátja   | 7,5 másodperc                                                                                        |
| Böngésző időzónája    | `Europe/Budapest`                                                                                    |
| Hibakeresési felvétel | `trace: "retain-on-failure"`, `screenshot: "only-on-failure"`, `video: "retain-on-failure"`          |
| Riportok              | Helyben `list`, CI-ben `line`; HTML: `playwright-report`, JUnit: `test-results/playwright-junit.xml` |
| CI-védelem            | `forbidOnly: Boolean(process.env.CI)`                                                                |

A projekt neve és tényleges tesztköre együtt értelmezendő:

| Projekt           | Böngészőprofil    | Konfigurált tesztkör                                                 |
| ----------------- | ----------------- | -------------------------------------------------------------------- |
| `chromium`        | `Desktop Chrome`  | Minden kiválasztott E2E-teszt, a `smoke/mobile.spec.js` kivételével. |
| `firefox`         | `Desktop Firefox` | `smoke/application.spec.js` és `smoke/cross-browser.spec.js`.        |
| `webkit`          | `Desktop Safari`  | Ugyanez a két rövid kompatibilitási vizsgálat.                       |
| `mobile-chromium` | `Pixel 7`         | Csak `smoke/mobile.spec.js`.                                         |

Egy projekt sikere nem bizonyítja a többi projektet. Még mind a négy projekt
sikere sem jelenti minden üzleti folyamat minden böngészőben való ellenőrzését:
a Firefox, WebKit és mobil projekt szándékosan szűkebb. A mobilprofil emuláció,
nem fizikai telefonon végzett mérés.

## Környezet és biztonságos előkészítés

A futtatáshoz telepített npm- és Composer-fejlesztői függőségek, elérhető PHP,
SQLite-bővítmény, Node, a kiválasztott Playwright-böngészők és azok rendszerbeli
függőségei szükségesek. A CI PHP 8.4-et és Node 24-et használ.

A tesztek kizárólag az `e2e` Laravel környezetet, a `database/e2e.sqlite`
adatbázist és az `e2e` fájlrendszer-diszket használhatják. Fejlesztői vagy
termelési adatbázist ne adj meg. A [prepare-e2e.js](../scripts/prepare-e2e.js)
a [.env.e2e.example](../.env.e2e.example) alapján létrehozza a hiányzó `.env.e2e`
fájlt, ellenőrzi az `APP_ENV`, `DB_CONNECTION` és `DB_DATABASE` értékét,
majd a gyermekfolyamatban is beállítja az izolált környezetet. Az előkészítés
kulcsot generál, és `migrate:fresh` művelettel, az `E2ETestSeeder` használatával
újraépíti a tesztadatbázist.

A közös [e2eData fixture](../tests/e2e/helpers/test.js) automatikusan törli a
cookie-kat, és minden teszt előtt a
[tesztadat-visszaállító](../tests/e2e/helpers/database.js) segítségével újraseedel.
A saját tesztadatokat és állapotokat a
[seeder](../database/seeders/E2ETestSeeder.php) kezeli. Minden teszt új
böngészőkontextust kap; nincs közös `storageState` fájl. A normál üzleti folyamatok a
valódi bejelentkezési felületet használják dedikált tesztfelhasználóval.
Az alapnyelv angol; a nyelvváltási tesztek a valódi választót vizsgálják.

Az egy worker az egyetlen SQLite adatbázis és a PHP beépített szervere miatti
közös erőforrásokat kíméli. A beállítás önmagában nem igazolja az izolációt;
azt az adott tesztek ismételhető viselkedése támasztja alá.

## Eljárás: futtatás

Első telepítés és az összes konfigurált projekt futtatása:

```bash
composer install --no-interaction --prefer-dist --no-progress
npm ci
npm run test:e2e:install
npm run test:e2e
```

A [package.json](../package.json) szerinti `test:e2e:install` Chromiumot,
Firefoxot és WebKitet telepít. Linux CI-ben a workflow `--with-deps` kapcsolót
is használ a rendszerfüggőségekhez. A `test:e2e` előkészíti az adatbázist,
buildet készít, majd projektszűrés nélkül indítja a Playwrightot.

Célzott futás előtt is szükséges az előkészített adatbázis és az aktuális build:

```bash
npm run test:e2e:prepare
npm run build
npx playwright test --project=chromium tests/e2e/workflows/customer-orders.spec.js
```

A teljes Chromium-projekthez a parancsból hagyd el a tesztútvonalat. További
projektekhez ugyanilyen előkészítés után:

```bash
npx playwright test --project=firefox --project=webkit --project=mobile-chromium
```

További meglévő npm scriptek:

| Parancs                          | Hatókör és előfeltétel                                                                          |
| -------------------------------- | ----------------------------------------------------------------------------------------------- |
| `npm run test:e2e:a11y`          | `chromium`, `tests/e2e/accessibility`; előkészítés és build külön szükséges.                    |
| `npm run test:e2e:keyboard`      | `chromium`, `tests/e2e/keyboard`; előkészítés és build külön szükséges.                         |
| `npm run test:e2e:cross-browser` | Firefox és WebKit, csak `smoke/cross-browser.spec.js`; nem a projektek teljes konfigurált köre. |
| `npm run test:e2e:mobile`        | `mobile-chromium`, `smoke/mobile.spec.js`; előkészítés és build külön szükséges.                |
| `npm run test:e2e:headed`        | Előkészítés, build, majd látható böngészős futás.                                               |
| `npm run test:e2e:ui`            | Előkészítés, build, majd Playwright kezelőfelület.                                              |
| `npm run test:e2e:report`        | Meglévő HTML-riport megnyitása; nem új tesztfutás.                                              |

A `test:e2e:cross-browser` sem végez előkészítést vagy buildet. A közvetlen
`npx playwright test` parancs elindítja a konfigurált globális előkészítést,
de az nem helyettesíti az adatbázis-előkészítő scriptet és a buildet.

## Szerverindítás és működőképességi próba

A [globális előkészítés](../scripts/e2e-global-setup.js) helyi HTTP-címet fogad
el, alapértelmezésben `127.0.0.1:8001` címen. A PHP beépített szerverét
kifejezett host-, port- és `scripts/e2e-server.php` routerparaméterrel indítja.
Az `E2E_SKIP_SERVER_START=1` kihagyja a saját szerver indítását; ilyenkor is
ugyanazokat az ellenőrzéseket végzi, és megfelelően izolált helyi E2E-szerver
szükséges. Ez nem általános fejlesztői szerver újrahasználati mód.

A tesztek indulása előtt ellenőrzi, hogy:

- az E2E SQLite adatbázis létezik, és nincs függőben maradt migráció;
- a Vite manifest létezik és érvényes JSON;
- a `/up` és `/login` 200 választ ad;
- a bejelentkezési oldal hivatkozik buildelt fájlra, és az 200 választ ad.

A szerverre 250 milliszekundumos időközönként vár, alapértelmezésben legfeljebb
60 másodpercig. Az `E2E_READINESS_TIMEOUT_MS` megengedett értéke 1000–60000.
Időtúllépéskor a hiba tartalmazza az alap URL-t és a
`test-results/e2e-server.log` helyét. A sikeres működőképességi próba még nem
sikeres felhasználói folyamat.

A [globális lezárás](../scripts/e2e-global-teardown.js) a rögzített PID alapján
állítja le a szervert: Windowson `taskkill /T /F`, POSIX alatt a PID-re küldött
`SIGTERM` segítségével. A mentett `public/hot` fájlt visszaállítja, ha a mentés
létezik és a cél hiányzik. A szerverre várás hibakezelése is végez takarítást.
Váratlan megszakítás után a lezárás sikerét külön ellenőrizni kell; a kód
létezése nem bizonyítja, hogy minden körülmény között lefutott.

## Hibák: előbb az okot állapítsd meg

A naplóban különítsd el a parancs indítását, az alkalmazásszerver indítását,
a böngésző indítását, a navigációt és tesztadat-előkészítést, a folyamat
elvárásainak ellenőrzését, végül a lezárást. A korábbi szakasz sikere nem
bizonyítja a későbbit.

- `FAILED`: az alkalmazás hibás működése miatt nem teljesül egy elvárás, vagy
  kódhiba miatt már induláskor összeomlik a szerver. A téves teszt is sikertelen
  ellenőrzés; ilyenkor a tesztet kell javítani.
- `BLOCKED`: igazolt külső vagy környezeti előfeltétel miatt az alkalmazandó
  vizsgálat érdemben nem végezhető el. Példa a nem elérhető böngészőfuttató vagy
  a szerver indulásához szükséges, igazoltan hiányzó külső függőség.
- `NOT RUN`: a parancsot nem indították el, vagy egy korábbi hiba után a későbbi
  vizsgálat kimaradt. Az előkészítés puszta elmaradása nem környezeti bizonyíték.
- `PASSED`: a megnevezett ellenőrzés ténylegesen lefutott és sikeres volt.

Böngészőindítási hibát, szerverindítási hibát és időtúllépést ne minősíts
automatikusan alkalmazáshibának vagy `BLOCKED` eredménynek. Vizsgáld meg a
hibakódot, szerverlogot és a rendelkezésre álló felvételeket. Ahol a folyamat
még nem indult el, ne állíts sikeres vagy hibás üzleti működést bizonyíték nélkül.

A konfiguráció nem próbálja újra automatikusan a hibás tesztet (`retries: 0`).
Egy kézi újrafuttatás sikere nem törli az előző hibát és nem magyarázza meg az
ingadozást. Őrizd meg mindkét eredményt, és vizsgáld ki az okot. Ne gyengíts
értelmes elvárást és ne törölj tesztet a siker kedvéért; a hibás teszt javítását
és az indokolt regressziós lefedettséget a tesztelési szabályok szerint kezeld.

### Böngésző- és hálózati hibák figyelése

A [browserErrors fixture](../tests/e2e/helpers/test.js) a teszt által kifejezetten
kért függőség, nincs `auto: true` beállítása. Használatakor figyeli:

- a nem kezelt oldalhibákat és a konzol hibáit;
- a sikertelen `document/script/stylesheet/xhr/fetch` kéréseket;
- az alkalmazás 4xx/5xx `document/xhr/fetch` válaszait;
- a 4xx/5xx `script/stylesheet/font` válaszokat.

Szándékos 403/422/404 esetben a várt hibát konkrét mintával engedélyezd;
általános elnémítást ne használj. A jelenlegi `allow(pattern)` megvalósítás a
kapott reguláris kifejezést alkalmazza, a minta szűkségét nem ellenőrzi.
Egyes régebbi tesztek tágabb mintát használnak, például
`/403|Forbidden|Failed to load resource/`. Ez eltérés a szűk kivétel szabályától,
nem követendő minta; javítása külön tesztmódosítási feladat.

## Megvalósítás: CI és böngészőlefedés

A [Frontend workflow](../.github/workflows/frontend.yml) hat jobjából kettő
böngészős; a teljes leltár a [frontend-útmutatóban](frontend-testing.md) van.
Minden pull requestre és `main` pushra indul, dokumentációs útvonalszűrés nélkül.
Ez nem változtatja meg a helyi ellenőrzések arányossági szabályát.

A `Playwright E2E` job Ubuntu környezetben Node 24-et, PHP 8.4-et és SQLite-ot
használ. A telepítés, Chromium rendszerfüggőségeinek telepítése, E2E-adatbázis
előkészítése és build után `npx playwright test --project=chromium` fut.
Ez tartalmazza a Chromiumhoz kiválasztott akadálymentességi és billentyűzetes
teszteket is.

A `Playwright cross-browser and mobile smoke` job ugyanilyen előkészítés után,
egyetlen parancsban futtatja a `firefox`, `webkit` és `mobile-chromium`
projekteket. A konfigurált szűk tesztkörüket végzi el, nem minden folyamatot
mindhárom profilban. Mindkét job időkorlátja 35 perc, egy workert használ,
és a létrejött `playwright-report` és `test-results` kimeneteket hét napra,
a futási kísérlet számával elnevezve tölti fel.

A workflow nem futtat Prettiert vagy frontend-lefedettségi mérést; külön npm
audit jobja van, Composer-audit lépése nincs. A konfigurált jobokból nem
következik tényleges siker, GitHub required státusz vagy branch protection.

## Bizonyíték és hibakeresési kimenetek

A DoD szerinti jelentésben add meg a parancsot, időpontot vagy futásazonosítót,
környezetet, Playwright-projektet, tesztkört és eredményt. Hiba esetén nevezd meg
az érintett forgatókönyvet és a megállás szakaszát. A nem sikeres tételekhez ok,
hatás, felelős és következő lépés is kell; hiányzó kötelező vizsgálat mellett a
feladat nem teljesen kész.

A `test-results` tartalmazhat képernyőképet, videót, végrehajtási nyomot
(trace), JUnit-riportot és szerverlogot. A felvételek a konfiguráció szerinti
hibákhoz maradnak meg; korai indítási hiba előtt nem feltétlenül készülnek el.
A HTML-riport helye `playwright-report`. Csak létrejött kimenetre hivatkozz.
A jelentéseket és felvételeket a Git kizárja; csak izolált tesztadatot használj.

```bash
npm run test:e2e:report
npx playwright show-trace test-results/<test>/trace.zip
```

A [2026-07-29-i E2E-audit](audits/playwright-e2e-quality-gate-2026-07-29.md)
Windows Firefox alatt az alkalmazás betöltése előtti `RenderCompositorSWGL`
és alfolyamat-indítási hibát rögzített. Ez egy korábbi gép és futás megfigyelése,
nem minden mai Windows-futás állapota és nem automatikus felmentés. Új hibánál
új diagnózis kell; egy Linux CI-siker csak a saját tesztkörét és környezetét
igazolja. A történeti audit változatlan forrás, nem aktuális sikerbizonyíték.
