# Frontend automatizált tesztelés

## Cél és eszközök

A frontend tesztrendszer a közös Vue-komponensek, az Inertia-hívási szerződések, a composable-ok, a kritikus oldalak és a fő felhasználói folyamatok regresszióit védi. A unit/component réteg Vitest 4-et, Vue Test Utils 2-t, jsdomot és a V8 coverage providert használ. A böngészős E2E, accessibility, keyboard, cross-browser és mobile smoke réteg Playwrighttal fut; részletek: [E2E testing](e2e-testing.md).

## Audit és prioritások

Az induló audit 104 Vue-fájlt talált: 69 Inertia page és 33 közös komponens, továbbá 1 composable és 2 utility/constants fájl. Korábban nem volt frontend teszt vagy tesztkonfiguráció. A projektben nincs általános frontend permission guard; a tényleges szerződések oldalankénti jogosultság propok (`canPlan`) és közös komponensállapotok (`readOnly`, `canEdit`, `canDelete`).

A leltár fő csoportjai:

- közös CRUD: `AdminCrudPage`, `AdminCrudField`, page header, search, action és status komponensek;
- select és form: `UnitSelect`, dokumentum-, anyagfelhasználási és minőségellenőrzési formok, dinamikus BOM/rendelés/terv szerkesztők;
- táblázatok és szűrők: admin listák, kapacitás-, kockázat- és terheléstáblák, riport filter;
- modalok: elsősorban page-be ágyazott PrimeVue dialogok és confirm műveletek;
- badge-ek: admin, dokumentum-, gyártási-, rendelési-, terv-, kockázat- és trendállapotok;
- layout és navigáció: `AdminLayout`, `GuestLayout`, locale switcherek;
- dashboard/intelligence: metrikakártyák, ajánlások, kockázatok, trendek és diagram körüli UI;
- composable/utility: `usePreferences`, route helper és mértékegység-konstansok;
- kritikus folyamatok: gyártási feladat indítása/befejezése, anyagfelhasználás, minőségellenőrzés, dokumentumverzió, készletfoglalás, kapacitástervezés.

Első prioritást a sok oldalt kiszolgáló CRUD és form szerződések kaptak. Második prioritás a route helper és a nyelvpreferencia. Harmadik prioritásként célzott gyártási, minőségi, dokumentum-, kapacitás- és intelligence szerződések készültek. Az egyszerű, ismétlődő CRUD page-ek nem kaptak másolt oldalszintű teszteket.

## Könyvtárszerkezet

```text
tests/frontend/
  components/   komponens- és workflow-tesztek
  composables/  composable tesztek
  pages/        oldalszintű szerződéstesztek
  utils/        tiszta helper tesztek
  fixtures/     kisméretű, felülírható domain factory-k
  helpers/      közös mount helper
  mocks/        Inertia mock
  setup/        közös jsdom/Vitest setup
```

Minden frontend teszt ebben a struktúrában kap helyet; komponens mellé helyezett második konvenciót ne használjunk.

## Futtatás

```bash
npm test
npm run test:frontend
npm run test:frontend:watch
npm run test:frontend:coverage
npm run test:e2e
npm run test:e2e:a11y
npm run test:e2e:keyboard
npm run test:e2e:cross-browser
npm run test:e2e:mobile
```

Az első két Vitest parancs egyszer fut és megfelelő exit kóddal leáll. A watch parancs fejlesztéshez használható. A coverage szöveges, HTML- és JSON-summary riportot ír a `coverage/frontend` könyvtárba. Globális threshold szándékosan nincs: előbb a kritikus területek célzott lefedését kell bővíteni. Az E2E parancsok előtt szükség esetén `npm run test:e2e:install` és mindig buildelt asset szükséges; a `npm run test:e2e` ezt előkészíti.

## Új teszt mintája

```js
import { shallowMount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";
import MyComponent from "@/Components/MyComponent.vue";

describe("MyComponent", () => {
    it("felhasználói műveletkor a dokumentált payloadot küldi", async () => {
        const wrapper = shallowMount(MyComponent, {
            props: { value: "draft" },
        });

        await wrapper.get("button").trigger("click");

        expect(wrapper.emitted("confirm")).toEqual([[{ value: "draft" }]]);
    });
});
```

Az assertion a saját komponens publikus viselkedését vagy integrációs határát védje. PrimeVue, Vue és Inertia belső DOM-szerkezetét ne tesztelje.

## Környezet és mockolás

Az Inertia mock a router összes használt metódusát, a `usePage`, `useForm`, `Head` és `Link` felületet biztosítja. A teszt a route nevet közvetetten a determinisztikus alkalmazás-URL-lel és a router metódusával ellenőrzi. Valós backend route-feloldás vagy hálózat nem indul.

A könnyű i18n mock alapértelmezetten a fordítási kulcsot adja vissza, így a kulcshasználat stabilan ellenőrizhető. Magyar felirat integrációs tesztjénél külön, lokális fordítási mockot vagy teszt-i18n példányt kell adni; a globális mockot nem szabad üzleti szövegtesztre használni.

A `mountWithApp` helper valódi PrimeVue pluginnal mountol, és felülírható mockot/provide-ot támogat. A legtöbb szerződéstesztnél a kisebb `shallowMount` előnyös, explicit PrimeVue stubokkal. Globálisan csak a közös böngésző API-k (`matchMedia`, `ResizeObserver`, `IntersectionObserver`) és az általános integrációs határok vannak pótolva.

A fixture factory-k kis, érvényes alapobjektumokat adnak, és minden mező felülírható. Ne másoljuk a teljes backend modellt; csak a vizsgált Vue-szerződéshez szükséges mezőket vegyük fel.

## CI

A `.github/workflows/frontend.yml` pull requestnél és a `main` branch pushainál három jobot futtat:

- frontend unit, i18n és build;
- Playwright Chromium E2E, accessibility és keyboard;
- Playwright WebKit/Firefox cross-browser smoke és mobile Chromium smoke.

Az E2E jobok PHP 8.4-et, Composer függőségeket, Node 24-et, Playwright böngészőket, SQLite E2E adatbázist és production build asseteket használnak. A Playwright riportok és `test-results` artifactként feltöltésre kerülnek.

## Mit teszteljünk

Tesztelendő a prop/emit szerződés, üres és hibás adat, form reset és processing állapot, backend validációs hibák, route és HTTP-művelet, szűrés/lapozás, megerősítés, valamint jogosultságfüggő megjelenítés. Ne teszteljük a PrimeVue belső működését, a Laravel route-feloldást, backend üzleti logikát, CSS részleteket vagy nagy komponens-snapshotokat.

## Playwright E2E réteg

A Playwright réteg már aktív. A részletes környezet, fixture, futtatási és ismert kockázati leírás a [docs/e2e-testing.md](e2e-testing.md) fájlban található.

## Második tesztelési ütem

A második ütem a permission-alapú admin navigációt, a készletfoglalás feloldását, a dokumentumfeltöltést és dokumentumműveleteket, a dashboard komponenseket, valamint a saját diagram-adattranszformációkat fedi le. Az infrastruktúra és a könyvtárkonvenció nem változott; új párhuzamos mock- vagy mount-rendszer nem készült.

### Layout és navigáció

A navigáció permission-leképezése és szűrése a `resources/js/Utils/navigation.js` tiszta helperben található. A layout tesztelésénél:

- a `usePage()` mock `auth.permissions` és `auth.roles` értékeit állítsuk be;
- ellenőrizzük az üres, részleges és teljes permission készletet;
- a `super-admin` szerepkört külön esettel védjük;
- a csoportcímet csak látható gyermek mellett várjuk;
- az aktív route tesztjénél pontos egyezést, alroute-ot, queryt és átfedő prefixet is használjunk;
- a sidebar önálló görgetését DOM- és class-szerződéssel, ne pixelpozícióval ellenőrizzük.

Példa permission-alapú page propsra:

```js
inertiaPage.props = makeAuthPageProps({
    auth: {
        permissions: ["inventory.view", "inventory.release"],
        roles: [],
    },
});
```

### Készletfoglalás feloldása

A reservation workflow tesztje az `inventory.release` permissiont, az aktív státuszt, a confirmation határt és az Inertia PATCH szerződést együtt védi. A confirmation mock `accept` callbackjének meghívása előtt nem indulhat kérés. Feldolgozás közben a rekord művelete loading/disabled állapotú, az `onFinish` után újra használható. Már feloldott, hibás státuszú vagy jogosultság nélkül kapott rekordnál a gomb nem jelenhet meg.

### Dokumentumfeltöltés

Fájlfeltöltési tesztben valódi böngésző `File` objektumot használjunk:

```js
const file = new File(["PDF"], "utasitas.pdf", {
    type: "application/pdf",
});
```

A file input `files` tulajdonsága jsdom alatt `Object.defineProperty` segítségével állítható, majd `change` eseményt kell kiváltani. A teszt a komponens formállapotát, a route-ot, a `forceFormData` opciót, a processing védelmet, a backend error bag megjelenítését és a sikeres resetet ellenőrzi. Az Inertia belső FormData-konverzióját nem teszteljük.

A jelenlegi UI nem jelenít meg upload százalékot, ezért progress mock nincs globálisan bevezetve. Ha később megjelenik progress UI, a `useForm` mock tesztenként felülírható `progress: { percentage: 50 }` értékkel; globális időzített feltöltésszimuláció nem szükséges.

A dokumentum actionök a megosztott `documents.update`, `documents.delete`, `documents.download`, `documents.approve` és `documents.version` permissionök alapján tesztelendők. Törlésnél a confirmation elfogadása előtt nem indulhat DELETE kérés.

### Dashboard kártyák és táblák

A kártyáknál a `0` külön regressziós eset: nem helyettesíthető üres szöveggel. A számformázás, kapacitási tónus és load severity tiszta helperből tesztelhető. A tábláknál a saját szerződés a kapott rekordlista, a formázott érték, az üres lista és a részleges rekord kezelése; a PrimeVue DataTable belső DOM-ja nem része a tesztnek.

Az új fixture factory-k reservationt, dokumentumot, dokumentumverziót, auth page propsot, dashboard metrikát, gyártóegység-terhelést és chart pontot állítanak elő. Minden factory-hívás új, felülírható objektumot ad.

### Diagram-adattranszformáció

A projekt status donut diagramja saját SVG-t használ, külső chart library nincs. Emiatt canvas/chart-library mock bevezetése helyett a `buildStatusChart()` transzformáció került tiszta helperbe. A helper normalizálja a stringként kapott számokat és a hibás/null/negatív értékeket, kiszámítja a teljes összeget, a körszegmenseket, offseteket és stabil színeket.

Tiszta helper kiemelése akkor indokolt, ha a komponens belsejében lévő transzformáció több elágazást tartalmaz, önálló input/output szerződése van, és a kiemelés nem változtatja meg a propokat, emiteket vagy a normál adatra készülő diagramkonfigurációt. A wrapper komponensnél csak az empty state-et, a saját átadott adatot és a propváltozásra történő frissülést teszteljük.

Ha később külső chart library kerül be, könnyű stubot használjunk, amely deklarálja a `data`, `options` és `type` propokat. Canvas vagy SVG belső struktúrát továbbra se ellenőrizzünk.

### Részleges backend adatok

Opcionális nested relation esetén explicit `null` fixture-rel teszteljünk. Csak a dokumentáltan opcionális mezőket tegyük nullbiztossá; kötelező backend szerződést ne lazítsunk fel teszt kedvéért. Ismeretlen numerikus értékhez a közös dashboard helper `-` értéket ad, a chart count helper pedig biztonságos nullát.

### További bővítési irányok

A következő körben érdemes tovább bővíteni a procurement jóváhagyási műveleteket, a production planning edge case-eket, a quality formágakat és a fájlletöltési hibakezelést. Új E2E teszt csak determinisztikus `E2ETestSeeder` adattal és elkülönített fájlrendszerrel kerüljön be.
