# PR #2 – célzott Chromium E2E-javítások

Dátum: 2026-11-13. Ág: `fix/ci-security-hardening`.
Kiinduló HEAD: `837b6bd4050e44a962db3016e6723984a1cb2b68`.
PR: [#2](https://github.com/KovacsZoltan65/KM_Production/pull/2).

## Hatókör és megoldások

- A készletfoglalási keyboard-teszt az aktuális seeder hosszú, egyedi
  cikkszámával kezdődő cella alapján választ sort. Egyetlen találatot és az
  `E2E-LOC` lokációt is ellenőrzi; a Release/Enter/Escape és az Active
  állapot elvárásai megmaradnak.
- A két dátumos folyamat a szűk `fillDatePicker()` segédet használja:
  dátumkitöltés, Escape, `aria-expanded=false`, az `aria-controls` által
  azonosított popup eltűnése és a dátumérték változatlansága. Nincs forced
  kattintás vagy CSS-es elrejtés. Az Escape működését a telepített PrimeVue
  4.5.5 forrása és a böngészős futások is igazolják; a publikus billentyűzetes
  szerződés a [DatePicker dokumentációjában](https://primevue.org/datepicker/#accessibility)
  szerepel.
- Az `AdminCrudField` valódi HTML-labelt és Vue `useId()` alapú, példányonként
  egyedi, frissítéskor stabil vezérlőazonosítót használ. A Select/UnitSelect
  comboboxok explicit `aria-labelledby` kapcsolatot és label-click fókuszt
  kapnak. A checkboxnak egyetlen címkéje van. A kötelezőség, invalid/disabled
  propok, modellfrissítés, ikonok és action slotok megmaradnak; a címke
  blokkos elrendezése és betűstílusa változatlan.
- Az új ID-k miatt a CRUD-oldal validációs fókusza a saját űrlapján belüli
  mezőnév alapján keresi a tényleges vezérlőt. Nem fókuszál egy másik form
  azonos nevű elemére.
- A customer-order `withPausedPost()` segédje legfeljebb 7,5 másodpercig
  tartja nyitva a kérésgátat. Hiányzó kérésnél fix hibával zár; page-close
  és hibás assertion esetén feloldja/megszakítja a gátat, eltávolítja a saját
  route-handlerét, törli a timert és close-listenert, és megvárja a függő
  handlerfeladatok lezárását. A Save letiltását továbbra is a visszatartott
  kérés alatt ellenőrzi a teszt. Bezárt page cleanupja nem fedi el az
  eredeti hibát; nyitott page routinghibája továbbra is továbbadódik.

Üzleti logika, adatbázisséma, dependency manifest/lock, workflow és publikált
CI-riportformátum nem változott. A CI-01–CI-06 implementációs fájljain nincs diff.
A globális timeout 60 másodperc, a worker 1, a retry 0.

## Módosított fájlok

- `resources/js/Components/Admin/AdminCrudField.vue`
- `resources/js/Components/Admin/AdminCrudPage.vue`
- `tests/e2e/helpers/forms.js`
- `tests/e2e/helpers/paused-request.js` (új)
- `tests/e2e/keyboard/keyboard.spec.js`
- `tests/e2e/workflows/customer-orders.spec.js`
- `tests/e2e/workflows/production-plans.spec.js`
- `tests/e2e/regressions/paused-request.spec.js` (új)
- `tests/frontend/components/AdminCrudField.test.js`
- `tests/frontend/components/AdminCrudFieldAccessibility.test.js` (új)
- `tests/frontend/components/AdminCrudPage.test.js`
- `tests/frontend/utils/pausedRequest.test.js` (új)
- Ez a jelentés (új).

## Regressziós lefedés

A szemantikai tesztek valódi PrimeVue-komponenseket renderelnek tíz
mezőtípussal: text, email, number, textarea, password, date, checkbox,
select, unit és multiselect. Ellenőrzik a label célpontját, a nem natív
combobox akadálymentes nevét és címkekattintási fókuszát, az ismételt
mezőnevek egyediségét, ID-stabilitást és a validációs/modellállapotokat.
A régi, `IftaLabel`-t valódi labelként utánzó stub eltűnt.

A megszakítási unit tesztek a normál gátfeloldást, a hiányzó kérés határolt
várakozását, page-close utáni abortot és handler-drain-t, valamint a kérés
előtti hibát ellenőrzik. A valódi Chromium-regresszió szintetikus oldallal,
hitelesítés nélkül bezárja a page-et a visszatartott POST közben. Ez először
feltárta a bezárt page `unroute()` hibájával elfedett eredeti kivételt; a
célzott cleanup-javítás után a regresszió sikeres.

## Ellenőrzések

A helyi környezet Windows, PHP 8.4, Node 26; a hosted Node 24/Linux eredményt
ezek nem helyettesítik. Az E2E-futtató minden kör előtt Laravelből ellenőrzi
az `e2e` környezetet, a pontos `database/e2e.sqlite` célt, az E2E-diszket,
a szinkron queue-t és a cache hiányát, majd olvasási kapcsolati próbát végez.
A dokumentált előkészítés csak ezután fut. A meglévő E2E-környezetfájl,
adatbázis és privát segédfájlok visszaállítását SHA-256 ellenőrzés igazolja.

A tesztek átmeneti, helyi reporterrel futnak: csak forráshely, állapot,
időtartam és engedélylistás hibakategória jelenik meg. Trace, screenshot és
videó kikapcsolt; nyers napló, kérés/válasz és DOM-tartalom nem publikált.
A production build saját szokásos, gitignore-olt assetjei nem részei a diffnek.

- Célzott frontend csomag: 59/59 sikeres; cleanup-javítás után a négy
  megszakítási unit teszt külön is sikeres.
- Teljes frontend: 348/348 teszt, 32/32 fájl, exit 0.
- `npm run i18n:check`: 732 szinkron kulcs, exit 0.
- `npm run build`: 965 feldolgozott modul, exit 0.
- Friss online `npm audit`: 0 sérülékenység, exit 0.
- Friss online `npm audit --omit=dev`: 0 sérülékenység, exit 0.
- Prettier, módosított JavaScript-fájlok szintaxisa és `git diff --check`: sikeres.
- Workflow YAML-parse: mindkét fájl sikeres; minden Action teljes SHA-ra
  rögzített, mind a tíz checkoutnál `persist-credentials: false`.

| Célzott Chromium eset        | Egyenként    | Közös soros kör |
| ---------------------------- | ------------ | --------------- |
| Készletfoglalási keyboard    | PASS, 10,6 s | PASS, 8,8 s     |
| Customer order               | PASS, 11,4 s | PASS, 12,6 s    |
| Production plan              | PASS, 9,7 s  | PASS, 12,5 s    |
| Angol feedback               | PASS, 11,3 s | PASS, 13,1 s    |
| Magyar feedback              | PASS, 13,1 s | PASS, 17,1 s    |
| Megszakított POST regresszió | PASS, 0,8 s  | PASS, 0,2 s     |

Az egyenkénti futásokban mind az öt eredeti eset elsőre sikeres volt; az új
regresszió a fent dokumentált cleanup-hiba célzott javítása utáni önálló
futásban sikeres. A végleges közös soros kör 6/6 sikeres, exit 0, 69,3 s
Playwright-folyamatidő. A végleges ellenőrzésekben nincs teszt-retry.

A teljes helyi Chromium suite **49 esetből 46 sikeres, 2 failed és 1 timedOut**,
nincs kihagyott teszt; exit **1**, Playwright-folyamatidő 443,4 s. Az öt javított
eset és az új regresszió ebben a körben is sikeres. A fennmaradó hibák:

| Teszt                                          | Eredmény         | Megfigyelt hibapont                                            |
| ---------------------------------------------- | ---------------- | -------------------------------------------------------------- |
| `accessibility/a11y.spec.js:6`                 | failed, 12,7 s   | `helpers/a11y.js:18`, axe-eredmények `toEqual([])` assertionje |
| `keyboard/keyboard.spec.js:27`                 | failed, 12,8 s   | 61. sor, Customer combobox `toBeFocused()` assertionje         |
| `workflows/production-tasks-quality.spec.js:6` | timedOut, 60,1 s | `helpers/forms.js:10`, opciókiválasztás                        |

Ezért a teljes Chromium gate nem zöld, az implementáció státusza **PARTIAL**.

Az accessibility eset külön, változatlan assertionnel végzett diagnosztikai
futtatása is failed: 19,6 s, exit 1. Engedélylistás axe-szabályazonosító és
szakasz alapján **`color-contrast`, customer order modal** a konkrét jelzés.
DOM, selectorlista vagy nyers assertion-adat nem került publikálásra.

## Külön fennmaradó vizsgálatok

Az accessibility, keyboard-navigation és production-task-quality korábbi
CI-only hibáján, illetve az 53,3 másodperces fixture-kiugráson nem történt
változtatás. A három teszthiba a mostani teljes helyi körben is reprodukálódott.
Külön javaslat: izolált Linuxon szakaszonként
mérni a login/fókusz/axe/gyártási lépések idejét és állapotát, a fixture-nél
pedig külön a PHP-indítást, bootstrapet, seedert és összesített SQL-időt.
Csak engedélylistás adatokkal, globális timeout-emelés és retry nélkül.

A quality-esetnél valószínű állapotszivárgás látszik: az
`admin/employees-partial-reload.spec.js` az `e2eData.employeeId` dolgozóját
átnevezi; a seeder ugyanezt az `EMP-WELDER-001` dolgozót csak visszakeresi,
nevét nem állítja vissza. Az inspector opció felirata a controllerben a
dolgozónévből épül, a quality-teszt pedig a régi nevet keresi. Következő
ellenőrzésként a két eset önálló és egymás utáni futtatása, majd szükség
esetén a fixture állapot-visszaállításának célzott javítása javasolt.
A keyboard-esetnél az induló fókusz és a dialog-animáció állapotát kell
mérni a nyolc Tab-lépés előtt; az attempts-szám vak növelése nem javasolt.
Az accessibility esetnél a modal érintett szövegének/témaállapotának
kontrasztmérése és szükség esetén a színek célzott javítása javasolt, az axe
assertion és szabályellenőrzés megtartásával. Ezek a javítások külön
jóváhagyást igényelnek.

A közös mezőkomponens miatt a merge előtt hosted Linuxon is szükséges a
teljes frontend és Chromium gate, valamint a cross-browser/mobile smoke.
Hosted CI-t, commitot, push-t és merge-et ebben a szakaszban nem indítunk.

COMMIT: NOT CREATED

PUSH: NOT PERFORMED

MERGE: NOT PERFORMED
