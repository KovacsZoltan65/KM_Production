# Frontend automatizált tesztelés

## Mire való a frontendteszt?

A frontendteszt azt ellenőrzi, hogy a felület egy része a kapott adatokra és a
felhasználó műveleteire a várt módon reagál-e. Például megjelenik-e a megfelelő
gomb, elküldi-e a megerősített művelet adatait, vagy visszaáll-e az űrlap
feldolgozás után. Így számos hiba gyorsan, teljes alkalmazásindítás
nélkül észrevehető.

A projekt Vue-komponenseket, oldalakat, composable-okat és segédfüggvényeket
vizsgál Vitest és Vue Test Utils segítségével. A tesztelt szerződés lehet
bemeneti tulajdonság (prop), kibocsátott esemény (emit), megjelenítés,
felhasználói művelet, űrlapállapot vagy egy helyettesített függőség meghívása.
A valódi böngészős folyamatokat az [E2E-útmutató](e2e-testing.md) kezeli.

A frontendteszt önmagában nem bizonyítja a backend üzleti szabályait, az
adatbázis viselkedését, a szerver útvonalkezelését, a teljes böngészős folyamatot
vagy a termelési telepítés helyességét. Az Inertia/backend adatszerződésből is
csak a tesztadatban és elvárásokban megjelenített részt ellenőrzi. Egy helyes
mock nem bizonyítja, hogy a valódi szerver ugyanazt küldi. A szerveroldali
vizsgálatokat a [backendeljárás](backend-quality-gate.md) írja le.

## Szabály: mit és milyen terjedelemben ellenőrizzünk?

A tartós teszttervezési elvárásokat a
[tesztelési szabályok](../.kiro/steering/testing.md), az ellenőrzési szintet a
[rétegezett útmutató](development/quality-gates.md), a készültséget a
[Definition of Done](project-management/definition-of-done.md) határozza meg.
Ez a dokumentum a frontend konkrét megvalósítását és futtatási eljárását adja.

Először a módosított viselkedést teszteld, majd a kapcsolódó regressziós
kockázatot. Kis, elkülönült komponensváltozáshoz elegendő lehet célzott teszt.
Modul működésének lezárásakor a modul és kapcsolatai vizsgálata kell. Közös
komponens vagy composable változásakor vizsgáld a használó oldalakat is;
több modult vagy oldalak együttműködését érintő közös alkalmazásműködéshez
Integration szint tartozik. A frontend teszt-, build- és konfigurációs
infrastruktúra változása Full kockázatú. A célzott siker nem váltja ki az
érintett nagyobb működés regressziós ellenőrzését.

**GOVERNANCE / IMPLEMENTATION MISMATCH:** a
[kiválasztó konfigurációja](../config/quality-gates.php) továbbra sem sorolja a
`vitest.config.js` fájlt a `full_risk_patterns` közé. A kiválasztó ezért
Integration szintre esik vissza, miközben a szabály Full szintet követel.
Ilyen változásnál kifejezetten `composer qa:full` szükséges; a további ismert
eltérések és a parancs korlátai a rétegezett útmutatóban találhatók.

Csak dokumentációt érintő változás nem igényel automatikusan frontendtesztet,
buildet vagy E2E-t. Ha a dokumentum futtatható viselkedést határoz meg, a DoD
szerint kell értékelni a hatását.

## Megvalósítás: Vitest és tesztkönyvtárak

A [vitest.config.js](../vitest.config.js) önálló konfiguráció; a
[vite.config.js](../vite.config.js) a build és a fejlesztői szerver beállítása.
Nincs külön `vitest.config.ts`, és a Vitest-beállítás nem a Vite-fájlba van ágyazva.

- Környezet: `jsdom`, `http://localhost/` URL-lel; ez nem valódi böngésző.
- Előkészítés: [tests/frontend/setup/setup.js](../tests/frontend/setup/setup.js).
- Tesztkiválasztás: `tests/frontend/**/*.test.js`. A `resources/js` alá tett
  `.test.*` vagy `.spec.*` fájl nem része ennek a mintának; ott jelenleg nincs
  ilyen teszt. A frontendteszteket az alábbi közös struktúrában tartsd.
- Vue plugin és `@` → `resources/js` alias.
- `pool: "forks"`, `maxWorkers: 2`, `clearMocks: true`, `restoreMocks: true`.
  A fájlszintű párhuzamosítás és izoláció az alapértelmezés szerint aktív.

```text
tests/frontend/
  components/   komponensek viselkedése
  composables/  újrahasznált Vue-működés
  pages/        oldalak adatszerződése és műveletei
  utils/        tiszta segédfüggvények
  fixtures/     kisméretű, felülírható tesztadatok
  helpers/      közös komponens-előkészítés
  mocks/        helyettesített Inertia-felület
  setup/        közös jsdom/Vitest előkészítés
```

Az Integration és Full futtató a teljes frontend-, illetve lefedettségi lépést
`--maxWorkers=1` kapcsolóval indítja; a célzott Fast és Module futás a konfigurált
két workert használja. Ez végrehajtási beállítás, nem sikerigazolás. Az aktuális
eszközverziók forrása a [package-lock.json](../package-lock.json).

## Eljárás: parancsok

A telepített npm-függőségekkel, a projekt gyökeréből futtass. A pontos scriptek
forrása a [package.json](../package.json).

| Parancs                          | Mit végez?                                                            |
| -------------------------------- | --------------------------------------------------------------------- |
| `npm test`                       | A `test:frontend` aliasa.                                             |
| `npm run test:frontend`          | Egyszeri teljes Vitest-futás (`vitest run`).                          |
| `npm run test:frontend:watch`    | Változásfigyelő Vitest fejlesztéshez.                                 |
| `npm run test:frontend:coverage` | Teljes Vitest-futás V8-lefedettségi méréssel.                         |
| `npm run build`                  | Vite build; nem tesztfutás.                                           |
| `npm run format:check`           | A scriptben felsorolt források és konfigurációk Prettier-ellenőrzése. |
| `npm run i18n:check`             | Fordítási kulcsok ellenőrzése.                                        |

Nincs külön `format`, `test:unit` vagy `test:component` npm script. A
`format:check` a `resources/js` JS/Vue-fájljait, a frontend- és E2E-tesztek,
valamint a `scripts` JS-fájljait, a gyökérbeli `*.config.js` fájlokat és a
`package.json` fájlt vizsgálja. Markdown nincs benne; dokumentációhoz a
módosított fájlokra külön Prettier-ellenőrzés kell.

Célzott futás egy meglévő tesztfájllal:

```bash
npm run test:frontend -- tests/frontend/components/DocumentUploadForm.test.js
```

A teljes lefedettségi futás magában foglalja a teljes Vitest-csomagot; nem kell
utána ugyanazt indok nélkül ismételni. A függőségaudit külön vizsgálat:
`npm audit` a teljes, `npm audit --omit=dev` a termelési függőségi fát vizsgálja.
Az alkalmazandó audit talált biztonsági hibáját a DoD szerint jelentsd;
a tesztek sikere nem helyettesíti az auditot. A böngészős parancsokat az
[E2E-eljárás](e2e-testing.md) tartalmazza.

## Lefedettség: mit jelent a százalék?

A lefedettség azt mutatja, hogy a mért kód utasításai, elágazásai, függvényei
és sorai közül melyeket érintette a futás. Segít megtalálni a nem vizsgált
területeket és követni a tesztcsomag fejlődését. Nem bizonyítja az elvárások
helyességét vagy minden üzleti eset ellenőrzését. A százalék önmagában nem
DoD-teljesítés.

A konfigurált szolgáltató `v8`, a riportok `text`, `html` és `json-summary`
formátumban készülnek, a `coverage/frontend` könyvtárba. A mérés hatóköre:

```text
resources/js/Components/**/*.vue
resources/js/Composables/**/*.js
resources/js/Utils/**/*.js
resources/js/Layouts/AdminLayout.vue
resources/js/Pages/Admin/Inventory/StockReservations/Index.vue
resources/js/Pages/Admin/Documents/**/*.vue
```

A kizárási lista `resources/js/app.js` és `resources/js/bootstrap.js`.
A százalék tehát nem az összes frontendfájlra vonatkozik. Nincs konfigurált
lefedettségi küszöb (`thresholds`); történeti százalékból ne alkoss kötelező
célt. A változás által indokolt kritikus regressziót akkor is tesztelni kell,
ha a mért összesített lefedettség magas.

## Tesztadatok és helyettesített függőségek

A közös előkészítés az Inertia és a fordítási könyvtár helyettesítését adja.
Az [Inertia mock](../tests/frontend/mocks/inertia.js) routermetódusokat,
`usePage`, `useForm`, `Head` és `Link` felületet biztosít. A tesztek az átadott
URL-t, HTTP-műveletet és adatokat vizsgálják; valódi backend útvonalfeloldás vagy
hálózati kérés nem indul.

A fordítási mock alapértelmezetten a kulcsot adja vissza. Magyar feliratot
vizsgáló teszthez helyi fordítási mock vagy teszt-i18n példány szükséges.
A közös előkészítés a `matchMedia`, `ResizeObserver` és `IntersectionObserver`
böngésző API-kat is helyettesíti, teszt előtt visszaállítja az Inertia mockot,
üríti a böngészős tárolókat, és teszt után visszaállítja a valódi időzítőket.

A `mountWithApp` valódi PrimeVue pluginnal készíti elő a komponenst, és
felülírható mockot vagy `provide` értéket fogad. Kis szerződésteszthez a
`shallowMount` és kifejezett PrimeVue-helyettesítők használhatók. A saját
komponens publikus működését vizsgáld, ne a PrimeVue, Vue vagy Inertia belső
DOM-szerkezetét vagy nagy, törékeny pillanatképeket.

A tesztadat legyen kicsi és érvényes, hívásonként új objektummal. Csak a
vizsgált szerződés mezőit tartalmazza. Opcionális kapcsolatot konkrét `null`
adattal is ellenőrizz; kötelező backendmezőt ne tegyél opcionálissá a teszt
kedvéért. Értelmes elvárást ne gyengíts, tesztet ne törölj a hiba eltüntetésére.
Előbb döntsd el, hogy a termékkód, a teszt vagy a környezet hibás; a téves tesztet
javítsd, az indokolt regressziós lefedettséget pótold a tesztelési szabályok szerint.

### Példák a meglévő tesztekből

- Navigáció: üres, részleges és teljes jogosultságkészlet, `super-admin`, csak
  látható gyermek mellett megjelenő csoportcím, pontos és alútvonal-egyezés.
- Készletfoglalás: `inventory.release`, aktív állapot, megerősítés előtti
  kérésmentesség, PATCH-adatok és feldolgozás alatti tiltás. A felület elrejtett
  gombja önmagában nem bizonyít szerveroldali jogosultságvédelmet.
- Dokumentumfeltöltés: `File` objektum, fájlcsere, üres kiválasztás,
  `forceFormData`, feldolgozási állapot, kapott validációs hibák és sikeres reset.
  jsdom alatt az input `files` értéke `Object.defineProperty` segítségével
  állítható, majd `change` eseménnyel vizsgálható. Az Inertia belső
  FormData-konverzióját nem kell lemásolni.
- Dokumentumműveletek: `documents.update`, `documents.delete`,
  `documents.download`, `documents.approve`, `documents.version`; törlés előtt
  megerősítés, a megadott jogosultság szerinti megjelenítés.
- Dashboard és diagram: a `0` megőrzése, üres és részleges rekord, számformázás,
  a `buildStatusChart()` bemeneti normalizálása és kimenete. A saját adatátadást
  és propváltozásra frissülést ellenőrizd, ne az SVG belső részleteit.

## Megvalósítás: jelenlegi CI

A [Frontend workflow](../.github/workflows/frontend.yml) minden pull requestre
és a `main` ágra történő pushra indul, dokumentációs útvonalszűrés nélkül.
Hat külön jobja van:

| Job neve                                    | Ellenőrzés                                                                |
| ------------------------------------------- | ------------------------------------------------------------------------- |
| `Frontend Unit Tests`                       | `npm run test:frontend`, lefedettségi mérés nélkül.                       |
| `Frontend i18n Check`                       | `npm run i18n:check`.                                                     |
| `Frontend Production Build`                 | `npm run build`.                                                          |
| `Frontend Dependency Audit`                 | `npm audit` és `npm audit --omit=dev`.                                    |
| `Playwright E2E`                            | A `chromium` projekt tesztjei.                                            |
| `Playwright cross-browser and mobile smoke` | A `firefox`, `webkit`, `mobile-chromium` projektek korlátozott tesztköre. |

A jobok Node 24-et és `npm ci` telepítést használnak. A két E2E-job PHP 8.4-et,
Composer-függőségeket, Playwright-böngészőket, izolált SQLite adatbázist és külön
buildet készít elő. Részletek az E2E-útmutatóban.

A frontend workflow nem futtat lefedettségi mérést vagy Prettiert. Sem ebben,
sem a vizsgált [backend workflow-ban](../.github/workflows/backend-quality.yml)
nincs `composer audit` lépés. Ez eltér a helyi `composer qa:full` tartalmától,
amely lefedettséget, formázást és Composer-auditot is futtat. A CI-konfiguráció
nem bizonyít sikeres futást vagy GitHub required check beállítást.

## Bizonyíték és korábbi mérések

Aktuális futásnál rögzítsd a parancsot, dátumot vagy futásazonosítót, környezetet,
tesztkört és eredményt a DoD szerint. A tényleges siker `PASSED`, a talált
hiba `FAILED`, igazolt környezeti akadály `BLOCKED`, el nem indított szükséges
vizsgálat `NOT RUN`. Workerösszeomlást vagy időtúllépést előbb vizsgálj ki.
A nem sikeres eredmények okát, hatását, felelősét és következő lépését is add meg.

A [2026-07-28-i frontend-audit](audits/frontend-quality-gates-2026-07-28.md)
20 fájlt és futásonként 166 tesztet rögzített. A
[2026-07-29-i E2E-audit](audits/playwright-e2e-quality-gate-2026-07-29.md)
frontendmérése 190 sikeres tesztet, 80,80% utasítás-, 81,43% elágazás-, 65,37%
függvény- és 80,69% sorlefedettséget jegyzett fel. Ezek az akkori kódra és
környezetre vonatkozó adatok, nem mai tesztszámok vagy kötelező célértékek.

A kétworkeres döntés előzménye a
[2026-07-28-i worker-audit](audits/frontend-worker-stability-2026-07-28.md),
az Integration/Full egyworkeres felülírásé a
[2026-08-26-i stabilizálási audit](audits/project-stabilization-0015-5-2026-08-26.md).
Az auditokat történeti forrásként kezeld; egy korábbi siker nem bizonyítja az
aktuális változás helyességét.
