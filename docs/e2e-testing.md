# Playwright E2E, accessibility és böngésző smoke tesztek

## Cél

Az E2E csomag a kritikus Inertia/Vue/Laravel felhasználói folyamatokat védi valódi böngészőben. A tesztek külön `e2e` Laravel környezetet, SQLite adatbázist és izolált fájlrendszer diszket használnak.

## Futtatás

```bash
npm run test:e2e:install
npm run test:e2e:prepare
npm run build
npx playwright test --project=chromium
npm run test:e2e:a11y
npm run test:e2e:keyboard
npm run test:e2e:cross-browser
npm run test:e2e:mobile
```

Teljes Chromium E2E:

```bash
npm run test:e2e
```

## Tesztadat és szerver

- `.env.e2e.example` a biztonságos alapkonfiguráció.
- `scripts/prepare-e2e.js` ellenőrzi, hogy `APP_ENV=e2e`, SQLite, `database/e2e.sqlite`, `FILESYSTEM_DISK=e2e` és sync queue legyen használatban.
- `Database\Seeders\E2ETestSeeder` determinisztikus admin, korlátozott inventory viewer, rendelés, gyártási terv/feladat, quality és procurement fixture adatokat készít.
- A Playwright global setup PHP built-in szervert indít, a teardown leállítja.
- Ha `public/hot` létezik, az E2E futás ideiglenesen félreteszi, hogy a tesztek a production build asseteket használják.

## Lefedett területek

- smoke: login, asset/backend hibák, Inertia navigáció, logout;
- auth és permission: hibás login, logout redirect `/login`, jogosultság nélküli művelet tiltása;
- workflow: customer order, production plan, production task + quality check, goods receipt;
- inventory és documents: stock reservation release, document upload/version visibility;
- accessibility: axe serious/critical violation gate kritikus oldalakon;
- keyboard: login, admin modal és confirmation dialog billentyűzettel;
- browser smoke: WebKit, Firefox projekt, mobile Chromium.

## Ismert lokális kockázat

Ezen a Windows gépen a Playwright Firefox headless indítása lokális compositor hibával elbukott még app-betöltés előtt:

```text
RenderCompositorSWGL failed mapping default framebuffer
```

WebKit és mobile Chromium ugyanitt zöld. A CI Linuxon `npx playwright install --with-deps chromium firefox webkit` paranccsal telepít böngészőfüggőségeket, ezért a Firefox smoke ott is érdemi jelzést ad.
