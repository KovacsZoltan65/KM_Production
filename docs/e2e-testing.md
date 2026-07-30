# Playwright E2E quality gate

## Cél és hatókör

Az E2E kapu valódi Chromium böngészőben védi a kritikus
Laravel/Inertia/Vue felhasználói folyamatokat, az akadálymentességi és
billentyűzetes ellenőrzéseket. A Firefox, WebKit és mobile Chromium projektek
külön kompatibilitási smoke-kaput alkotnak.

A tesztek kizárólag az `e2e` Laravel környezetet, a
`database/e2e.sqlite` adatbázist és az `e2e` fájlrendszer-diszket használhatják.
Az előkészítő script más adatbázis- vagy környezetbeállítást biztonsági hibával
elutasít.

## Első telepítés és teljes futtatás

```bash
composer install --no-interaction --prefer-dist --no-progress
npm ci
npm run test:e2e:install
npm run test:e2e
```

Az `npm run test:e2e` sorrendje:

1. `.env.e2e` létrehozása az example fájlból, ha hiányzik;
2. a környezeti védőfeltételek ellenőrzése;
3. `migrate:fresh` és a dedikált `E2ETestSeeder` futtatása;
4. production Vite build;
5. adatbázis-, manifest-, Laravel-, login- és asset-readiness;
6. minden konfigurált Playwright projekt egyszálú futtatása;
7. a PHP szerver leállítása és a `public/hot` visszaállítása.

Csak a kötelező Chromium-kapu:

```bash
npm run test:e2e:prepare
npm run build
npx playwright test --project=chromium
```

Cross-browser és mobil smoke:

```bash
npx playwright test --project=firefox --project=webkit --project=mobile-chromium
```

## Egy teszt, hibakeresés és riportok

```bash
npx playwright test --project=chromium tests/e2e/workflows/customer-orders.spec.js
npx playwright test --project=chromium --grep "authorized user"
npm run test:e2e:headed
npm run test:e2e:ui
npm run test:e2e:report
npx playwright show-trace test-results/<test>/trace.zip
```

Hiba esetén a `test-results/` tartalmazza a screenshotot, videót, trace-t, JUnit
XML-t és `e2e-server.log` fájlt. A `playwright-report/` a HTML riport. Ezek
gitignore-olt, kizárólag determinisztikus tesztadatot tartalmazó kimenetek.

## Izolációs modell

- A suite elején `migrate:fresh` készít ismert adatbázist.
- Minden teszt új browser contextet és explicit cookie-törlést kap.
- Az automatikus `e2eData` fixture minden teszt előtt újrafuttatja a szigorúan
  `e2e` + SQLite környezetre korlátozott seedert.
- A seeder törli a tesztek saját session-, audit-, dokumentum-, notification
  item- és goods receipt adatait, majd visszaállítja a készlet-, procurement-,
  rendelés-, gyártás- és quality fixture-állapotokat.
- A tesztek nem használnak közös `storageState` fájlt. A normál üzleti
  forgatókönyvek a valódi login UI-n jelentkeznek be dedikált tesztuserrel.
- Az alapnyelv az `.env.e2e` szerint angol, amit a login helper a dokumentum
  `lang` attribútumán ellenőriz. A nyelvváltó tesztek a valódi locale selector
  UI-t használják és mindkét irányt igazolják.
- `workers: 1` és `fullyParallel: false` szándékos: a helyi PHP built-in szerver
  és az egyetlen SQLite adatbázis közös erőforrás. Az izolációt ismételt és
  megváltoztatott sorrendű futások igazolják, nem a fájlsorrend.
- A retry értéke helyben és CI-ben is `0`; csak első próbálkozás számít zöldnek.

## Szerver és readiness

A global setup a PHP beépített szerverét explicit host/port/router
paraméterekkel indítja. Ez Windows és Linux alatt is azonos, és az egyszálú
Playwright workerrel együtt támogatott modell.

A tesztek csak akkor indulnak el, ha:

- az E2E SQLite fájl létezik, elérhető és minden migráció futott;
- a Vite manifest létezik és érvényes JSON;
- `GET /up` 200 választ ad;
- `GET /login` 200 választ ad;
- a login oldal által hivatkozott production asset 200 választ ad.

Nincs fix startup sleep. A bounded polling hibája tartalmazza a base URL-t és a
szerverlog helyét. A teardown PID alapján leállítja a teljes szerverfolyamatot,
és siker/hiba esetén is visszaállítja a `public/hot` fájlt.

## Browser- és hálózati hibák

Az automatikus `browserErrors` fixture hibának tekinti:

- az uncaught page és console errorokat;
- a sikertelen document/script/stylesheet/xhr/fetch kéréseket;
- az alkalmazás 4xx/5xx document/xhr/fetch válaszait;
- a 4xx/5xx script, stylesheet és font válaszokat.

Szándékos 403/422/404 teszt előtt a tesztnek konkrét regexszel engedélyeznie kell
az elvárt hibát. Általános whitelist nem megengedett.

## CI

A `Frontend` workflow `Playwright E2E` jobja Node 24, PHP 8.4, SQLite és egy
Chromium worker mellett fut. `npm ci`, Composer install,
`playwright install --with-deps chromium`, E2E prepare és production build után
indul. A HTML/JUnit riport, trace, screenshot, video és szerverlog hét napos,
run-attempttel névterezett artifactként feltöltődik.

A `Playwright cross-browser and mobile smoke` job ugyanilyen izolációval,
egyetlen Playwright invocationben futtatja a Firefox, WebKit és mobile Chromium
projekteket.

Windows alatt a Playwright Firefox ezen a fejlesztőgépen az alkalmazás
betöltése előtt `RenderCompositorSWGL` és tab-subprocess indítási hibával áll
meg. Ezt tesztskippel elfedni tilos; a támogatott bizonyíték a Linux GitHub
Actions futás.
