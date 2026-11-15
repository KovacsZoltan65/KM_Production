# PR #2 – frontend dependency-javítás, első ellenőrzési pont

Dátum: 2026-11-12. Ág: `fix/ci-security-hardening`.
Kiinduló commit: `42155e3b9a2a116701eba4a9abee4f0b1b40b8f7`.
PR: [#2](https://github.com/KovacsZoltan65/KM_Production/pull/2).

## Hatókör és kiindulás

A preflight tiszta munkakönyvtárat és azonos helyi/távoli commitot igazolt.
Csak az npm dependency-javítási szakasz engedélyezett. A CI-01–CI-06 elfogadott
implementációja, a workflow-k és a production-konfiguráció változatlanok.
A Playwright telepítési/DNS-probléma második szakasz; most nem módosult.

A baseline az eredeti `package-lock.json`, nem a korábban telepített
`node_modules`: utóbbiban voltak eltérő verziók és extraneous csomagok.
A kért `npm ci` a végleges lockfájlból tiszta telepítést végzett.

Az audit **18 érintett csomagot/meta-találatot** jelentett, nem 18 egymástól
független CVE-t. Például a `shell-quote` hibája a `concurrently` critical
besorolását is okozta; a `vue` és az `@vue/server-renderer` találata szintén
összefügg. A teljes és production JSON mezői alapján készült az alábbi térkép.

## Eredmények súlyosság szerint

| Audit                     | Critical előtte → utána | High  | Moderate | Low / Info | Összes |
| ------------------------- | ----------------------- | ----- | -------- | ---------- | ------ |
| Teljes                    | 2 → 0                   | 9 → 0 | 7 → 0    | 0 → 0      | 18 → 0 |
| Production (`--omit=dev`) | 0 → 0                   | 6 → 0 | 0 → 0    | 0 → 0      | 6 → 0  |

A végleges két online JSON-audit kilépési kódja 0, a `vulnerabilities` objektum
üres. Ez az aktuális registry-adatbázis szerinti eredmény, nem általános
garancia arra, hogy a függőségeknek soha nem lesz új ismert hibájuk.

## A 18 baseline találat és javítási útvonala

K = közvetlen, T = tranzitív. P = a production auditban is szerepelt,
D = csak a teljes/dev auditban. A nyilak csomagfüggőséget jelölnek;
a közösen használt csomagok több útvonalon is elérhetők.

| Csomag                 | Súlyosság; típus | Lock-verzió előtte | Audit szerinti érintett tartomány          | Javított verzió / kimenet              | Függőségi út                                                                     |
| ---------------------- | ---------------- | ------------------ | ------------------------------------------ | -------------------------------------- | -------------------------------------------------------------------------------- |
| `@vitest/coverage-v8`  | moderate; K/D    | 4.1.10             | 2.1.0-beta.1–4.1.10, Vitest-meta           | 4.1.12                                 | projekt → coverage → vitest                                                      |
| `@vitest/mocker`       | moderate; T/D    | 4.1.10             | ≤4.1.11                                    | 4.1.12                                 | vitest → mocker                                                                  |
| `@vue/server-renderer` | high; T/P        | 3.5.38             | ≤3.5.41                                    | 3.5.42                                 | vue → server-renderer; Test Utils opcionális peer                                |
| `axios`                | high; K/P        | 1.18.0             | 1.0.0–1.19.0                               | 1.20.0                                 | projekt; inertia/vue3 → core, illetve laravel-precognition → axios               |
| `brace-expansion`      | high; T/D        | 2.1.4              | 2.0.0–2.1.6                                | 5.0.13                                 | Test Utils → js-beautify → glob/editorconfig → minimatch → brace-expansion       |
| `concurrently`         | critical; K/D    | 9.2.4              | 9.2.3–9.2.4 vagy 10.0.0–10.0.5             | 9.2.5                                  | projekt → concurrently → shell-quote                                             |
| `editorconfig`         | moderate; T/D    | 1.0.7              | 1.0.3–3.0.2, minimatch-meta                | 1.0.7 marad; minimatch 11.0.4 override | Test Utils → js-beautify → editorconfig                                          |
| `follow-redirects`     | high; T/P        | 1.16.0             | 1.16.0–1.16.1                              | 1.16.3                                 | axios → follow-redirects                                                         |
| `glob`                 | moderate; T/D    | 10.5.0             | 9.0.0–14.0.1                               | 14.0.2 override                        | Test Utils → js-beautify → glob                                                  |
| `js-beautify`          | moderate; T/D    | 1.15.4             | ≥1.14.9, glob/editorconfig-meta            | 1.15.4 marad; a két alág javítva       | Test Utils → js-beautify                                                         |
| `minimatch`            | moderate; T/D    | 9.0.9              | 7.0.0–11.0.0                               | 11.0.4 (editorconfig), 11.0.8 (glob)   | js-beautify → editorconfig/glob → minimatch                                      |
| `nanoid`               | high; T/P        | 3.3.17             | <3.3.18                                    | 3.3.20                                 | vue → compiler-sfc → postcss → nanoid; Vite → postcss is                         |
| `shell-quote`          | critical; T/D    | 1.9.0              | 1.8.4–1.10.0                               | 1.12.0, a concurrently 9.2.5 előírása  | concurrently → shell-quote                                                       |
| `source-map-js`        | high; T/P        | 1.2.1              | 1.0.0–1.2.1                                | 1.2.2                                  | Vue compiler/postcss; Vite; Tailwind node; jsdom → css-tree; coverage → magicast |
| `undici`               | high; T/D        | 7.29.0             | 7.0.0–7.30.0                               | 7.30.2                                 | jsdom → undici                                                                   |
| `vite`                 | high; K/D        | 8.0.16             | 8.0.0–8.1.5                                | 8.1.6                                  | projekt; Vitest, plugin-vue, Tailwind Vite és Laravel Vite peer/függőség         |
| `vitest`               | moderate; K/D    | 4.1.10             | 2.1.0-beta.1–4.1.10, saját és örökölt hiba | 4.1.12                                 | projekt; coverage peer                                                           |
| `vue`                  | high; K/P        | 3.5.38             | 3.2.13–3.5.41, server-renderer-meta        | 3.5.42                                 | projekt; Inertia, PrimeVue, vue-table, Vuelidate, i18n és Test Utils peer        |

Az elsődleges advisory-határok például:
[shell-quote: 1.11.0](https://github.com/advisories/GHSA-pqg4-j6r4-53mv),
[minimatch: 11.0.1](https://github.com/advisories/GHSA-g3hv-75wg-m249),
[glob: 14.0.2](https://github.com/advisories/GHSA-3g3m-2fjr-6pw4).
A kiválasztott verziókat az npm registry és a végleges audit is ellenőrizte.

## Célzott változtatások és kompatibilitás

Hat közvetlen alsó verzióhatár emelkedett:
`concurrently ^9.2.5`, `axios ^1.20.0`, `vue ^3.5.42`,
`vite ^8.1.6`, `vitest ^4.1.12`, `@vitest/coverage-v8 ^4.1.12`.
A lock pontosan ezeket a közvetlen verziókat rögzíti.
Vue 3, PrimeVue 4, Inertia 3, Vitest 4, Test Utils 2 és jsdom 29 maradt.
Az `npm outdated` által jelzett többi frissítés nem került automatikusan bele.

A `follow-redirects`, `nanoid`, `source-map-js`, `undici` külön, a meglévő
szülői semver-tartományokon belül frissült. Nincs új közvetlen production-csomag.

Két szűk override szükséges, kizárólag a Test Utils formázó ágán:

- `js-beautify > glob = 14.0.2`: az eredeti `^10.4.2` nem fogad el javított
  verziót; a legújabb js-beautify 2.0.3 is csak `^13.0.6` tartományt kér.
- `js-beautify > editorconfig > minimatch = 11.0.4`: az eredeti `^9.0.1`
  tartományban nincs megfelelő javítás. Az editorconfig 3-as ága is sérülékeny
  minimatch-tartományt kér; az editorconfig 4 bevezetése további API-/runtime-
  főverzióváltás lenne. A működő szülői API megmaradt.

Az override-ok tranzitív főverziókat lépnek át; nem állítunk általános,
minden fogyasztóra érvényes kompatibilitást.
A projekt által használt `glob.hasMagic`/`globSync` és
`editorconfig.parseSync`/`Minimatch.match` útvonalakat valódi CLI-próba,
brace és dupla csillagos minták, ignorált könyvtárak és indentálási ellenőrzés
vizsgálja. A Vue Test Utils HTML-formázás és a concurrently sikeres/hibás
névvel ellátott parancsai szintén regressziós tesztet kaptak.
A [glob 14 changelog](https://github.com/isaacs/node-glob/blob/v14.0.2/changelog.md)
alapján a belső matcher és egyes minták szemantikája változott; az override-okat
felül kell vizsgálni, amikor a szülők saját támogatott javítása elérhető.

A Vue compiler/runtime/server-renderer csomagok együtt 3.5.42-re váltottak.
A Vitest és a coverage provider együtt 4.1.12. A Vite minor frissítése újabb,
összetartozó Rolldown csomagokat igényel. A lock-eltérésben ezek, a Vue compiler
Babel-függőségei és a frissített teszt/formázó ág tranzitív csomagjai szerepelnek.
296-ról 276-ra változott a lock-bejegyzések száma (a projektbejegyzéssel együtt):
23 régi glob-ágbejegyzés eltűnt, 3 új bejegyzés került be.
A Linux és Windows Rolldown 1.1.8 platformbejegyzések megmaradtak.

## Auditparancsok és kilépési kódok

| Szakasz / parancs                                       | Exit | Eredmény                                                |
| ------------------------------------------------------- | ---- | ------------------------------------------------------- |
| Baseline `npm audit --json`                             | 1    | 18: 2 critical, 9 high, 7 moderate                      |
| Baseline `npm audit --omit=dev --json`                  | 1    | 6 high                                                  |
| Online baseline `npm audit --json`                      | 1    | a 18 találat megerősítve                                |
| Hat közvetlen frissítés után, online `npm audit --json` | 1    | 4 high: follow-redirects, nanoid, source-map-js, undici |
| Ugyanekkor online `npm audit --omit=dev --json`         | 1    | 3 high: follow-redirects, nanoid, source-map-js         |
| Végleges online `npm audit --json`                      | 0    | 0 találat                                               |
| Végleges online `npm audit --omit=dev --json`           | 0    | 0 találat                                               |
| Végleges online `npm audit`                             | 0    | 0 találat                                               |
| Végleges online `npm audit --omit=dev`                  | 0    | 0 találat                                               |

A sandbox kezdetben cache-only npm elérést engedett:
az első `npm outdated --json` ENOTCACHED hibával, 1-es kóddal állt le;
az online ismétlés 1-es kódja már csak az elavult csomagokat jelezte.
Egy köztes cache-korlátos audit 0-t adott, miközben az online ismétlés 4 high
találatot jelzett; a cache-korlátos 0-t nem fogadtuk el biztonsági bizonyítékként.
A végleges elfogadási auditok online, folyamatra korlátozott
`npm_config_offline=false` környezettel futottak. A projekt `.npmrc` fájlja
(`ignore-scripts=true`, `audit=true`) és a biztonsági kapuk változatlanok.

`npm audit fix --force`, audit-kikapcsolás, globális telepítés nem történt.
A sikertelen vagy cache-korlátos kísérletek nem szerepelnek sikeres kapuként.

## Quality gate bizonyíték

- `npm ci`: exit 0; 232 telepített csomag, 0 jelzett sérülékenység.
- `npm run test:frontend`: végleges exit 0; 29 fájl, 306/306 sikeres teszt (a 302 meglévő és 4 új kompatibilitási eset).
- `node node_modules/vitest/vitest.mjs run tests/frontend/utils/dependencyCompatibility.test.js tests/frontend/utils/ciDiagnostics.test.js --coverage --maxWorkers=1`: exit 0, 8/8 teszt. A frissített Vitest–coverage-v8 páros betöltése és riportkészítése igazolt. Ez célzott provider-kompatibilitási próba, nem teljes alkalmazás-coverage mérés; lefedettségi küszöb vagy konfiguráció nem változott.
- Az első futás exit 1 volt (305 sikeres, 1 hibás): az új CLI-próba stdoutban kereste az alapértelmezetten a fixture-fájlokba írt formázott szöveget. Az ellenőrzés a tényleges fájlkimenetre került át, és az ignorált fájlok változatlanságának assertionjeivel bővült; a 302 meglévő teszt változatlan maradt.
- `npm run i18n:check`: exit 0; 732 szinkron kulcs.
- `npm run build`: exit 0; Vite 8.1.6, 968 feldolgozott modul.
- `npm run format:check`: exit 0, teljes projekt frontend Prettier-kapuján.
- Új JS-teszt szintaxisa: sikeres.
- A dokumentum, package.json és az új teszt célzott Prettier-ellenőrzése, valamint `git diff --check`: exit 0.
- `npm ls --all --json`: exit 0, üres `problems` lista.
- Node 24.0.0 engine-tartomány ellenőrzés minden lock-bejegyzésre: 0 ütközés; ez nem helyettesíti a Node 24-en végzett tényleges futtatást.
- Végső diff és integritásellenőrzés: az npm-scriptek változatlanok; minden resolved csomag a hivatalos HTTPS npm registryre mutat, integritásadattal. Kizárólag package.json, package-lock.json, ez a jelentés és az új kompatibilitási teszt változott.

A helyi futtatókörnyezet Windows, Node 26.10.0, npm 11.19.1.
Node 26 a tesztfutásban nem blokkoló experimental localStorage figyelmeztetést adott.
A Vite/Vitest és a szintetikus gyermekfolyamatok futtatása a Windows sandbox
folyamatindítási korlátai miatt sandboxon kívül történt.
A build generált kimenete és a tesztfixture-k nem részei a változtatásoknak.

## Fennmaradó validáció és első ellenőrzési pont

Hosted Node 24/Linux futás az új lockkal még nem történt, teljes CI-siker
nem állítható. A Playwright APT/DNS-javítás és az alkalmazás-E2E a külön
jóváhagyandó második szakasz része. A teljes `qa:full` nem futott: migrációt
és E2E-előkészítést is indítana; ebben a szakaszban az előírt frontend-kapuk
futnak. PHP-/üzleti logika nem változott, ezért új backend-futtatás nem indokolt.

A CI-01–CI-06 fájljai változatlanok; új GitHub Actions futás nem indult.
A helyi HEAD és a végül újraellenőrzött origin ág továbbra is a kiinduló commit;
stagingbe nem került változás. Az új dependency-diff még nincs a PR távoli ágán.
A végleges normál és JSON-auditban fennmaradó sérülékenységek listája: üres.

Visszaállítás: a package.json/package-lock.json jelenlegi, még nem commitolt
dependency-diffjének célzott visszavonása és az eredeti lockból történő
`npm ci`; ez újranyitná a fenti ismert hibákat. Az elfogadott hardening
commitot meg kell őrizni.

`COMMIT: NOT CREATED`
`PUSH: NOT PERFORMED`
`MERGE: NOT PERFORMED`

## JSON-advisory hivatkozások csomagonként

A meta-találatok öröklődő nevei és a közvetlen advisory URL-ek az eredeti
online audit `via` mezőjéből, tartalmi átírás nélkül kerültek ide.

- `@vitest/coverage-v8`: örökölt: `vitest`.
- `@vitest/mocker`: [GHSA-82fw-gwwq-j7x9](https://github.com/advisories/GHSA-82fw-gwwq-j7x9), [GHSA-qxg6-89mg-rqv8](https://github.com/advisories/GHSA-qxg6-89mg-rqv8).
- `@vue/server-renderer`: [GHSA-g2v6-rqmx-r4w6](https://github.com/advisories/GHSA-g2v6-rqmx-r4w6), örökölt: `vue`.
- `axios`: [GHSA-vh66-26gq-q6x8](https://github.com/advisories/GHSA-vh66-26gq-q6x8), [GHSA-9fr6-4gfg-395g](https://github.com/advisories/GHSA-9fr6-4gfg-395g), [GHSA-c29m-xwm3-cm6r](https://github.com/advisories/GHSA-c29m-xwm3-cm6r), [GHSA-mghh-pgcx-3jjj](https://github.com/advisories/GHSA-mghh-pgcx-3jjj), [GHSA-x97p-jq2g-jp4f](https://github.com/advisories/GHSA-x97p-jq2g-jp4f), [GHSA-3pq3-5fj3-cg6v](https://github.com/advisories/GHSA-3pq3-5fj3-cg6v), [GHSA-542g-h47m-68v8](https://github.com/advisories/GHSA-542g-h47m-68v8), [GHSA-j8rh-479h-cp32](https://github.com/advisories/GHSA-j8rh-479h-cp32), [GHSA-4hqw-qxg8-jxx2](https://github.com/advisories/GHSA-4hqw-qxg8-jxx2), [GHSA-m8m8-qj5v-23w3](https://github.com/advisories/GHSA-m8m8-qj5v-23w3), [GHSA-44g4-m2mj-wpvx](https://github.com/advisories/GHSA-44g4-m2mj-wpvx), [GHSA-r4gj-5m52-g5wh](https://github.com/advisories/GHSA-r4gj-5m52-g5wh).
- `brace-expansion`: [GHSA-q2hr-2g5m-vwhr](https://github.com/advisories/GHSA-q2hr-2g5m-vwhr), [GHSA-qhr7-859c-m2p7](https://github.com/advisories/GHSA-qhr7-859c-m2p7), [GHSA-6j4f-fj2g-mc7p](https://github.com/advisories/GHSA-6j4f-fj2g-mc7p).
- `concurrently`: örökölt: `shell-quote`.
- `editorconfig`: örökölt: `minimatch`.
- `follow-redirects`: [GHSA-q3jh-wrc6-jgq8](https://github.com/advisories/GHSA-q3jh-wrc6-jgq8), [GHSA-8r9p-f939-6h3c](https://github.com/advisories/GHSA-8r9p-f939-6h3c), [GHSA-mrwp-f3q8-3g9p](https://github.com/advisories/GHSA-mrwp-f3q8-3g9p).
- `glob`: [GHSA-3g3m-2fjr-6pw4](https://github.com/advisories/GHSA-3g3m-2fjr-6pw4), örökölt: `minimatch`.
- `js-beautify`: örökölt: `editorconfig`, örökölt: `glob`.
- `minimatch`: [GHSA-g3hv-75wg-m249](https://github.com/advisories/GHSA-g3hv-75wg-m249).
- `nanoid`: [GHSA-2v37-7h3g-55p8](https://github.com/advisories/GHSA-2v37-7h3g-55p8).
- `shell-quote`: [GHSA-pqg4-j6r4-53mv](https://github.com/advisories/GHSA-pqg4-j6r4-53mv).
- `source-map-js`: [GHSA-68fv-2mgg-jv7q](https://github.com/advisories/GHSA-68fv-2mgg-jv7q).
- `undici`: [GHSA-3wwx-pv8p-q78v](https://github.com/advisories/GHSA-3wwx-pv8p-q78v), [GHSA-pmjh-fq2x-6v4x](https://github.com/advisories/GHSA-pmjh-fq2x-6v4x), [GHSA-r53p-7pc4-xj5r](https://github.com/advisories/GHSA-r53p-7pc4-xj5r), [GHSA-rfgv-xxqx-mfg5](https://github.com/advisories/GHSA-rfgv-xxqx-mfg5), [GHSA-3xpg-4rpp-hhhm](https://github.com/advisories/GHSA-3xpg-4rpp-hhhm), [GHSA-2jfj-6hjv-fm6j](https://github.com/advisories/GHSA-2jfj-6hjv-fm6j), [GHSA-2gqq-gqf2-x968](https://github.com/advisories/GHSA-2gqq-gqf2-x968), [GHSA-w293-vg96-wgc3](https://github.com/advisories/GHSA-w293-vg96-wgc3), [GHSA-8436-99hf-9mmv](https://github.com/advisories/GHSA-8436-99hf-9mmv), [GHSA-rx4f-c7p8-82vq](https://github.com/advisories/GHSA-rx4f-c7p8-82vq), [GHSA-4qv8-2p9j-2685](https://github.com/advisories/GHSA-4qv8-2p9j-2685), [GHSA-f72v-6w45-q472](https://github.com/advisories/GHSA-f72v-6w45-q472), [GHSA-xjj6-f33q-929c](https://github.com/advisories/GHSA-xjj6-f33q-929c), [GHSA-4w7w-5xrc-9h26](https://github.com/advisories/GHSA-4w7w-5xrc-9h26), [GHSA-2m5v-pch6-74hx](https://github.com/advisories/GHSA-2m5v-pch6-74hx), [GHSA-qrxm-fqhp-jwvm](https://github.com/advisories/GHSA-qrxm-fqhp-jwvm), [GHSA-9fm3-97px-rjf6](https://github.com/advisories/GHSA-9fm3-97px-rjf6), [GHSA-55wc-3pg8-jfw6](https://github.com/advisories/GHSA-55wc-3pg8-jfw6).
- `vite`: [GHSA-698v-4r3x-3vcv](https://github.com/advisories/GHSA-698v-4r3x-3vcv), [GHSA-r825-w8mm-5pxw](https://github.com/advisories/GHSA-r825-w8mm-5pxw), [GHSA-587h-7wmj-h5pm](https://github.com/advisories/GHSA-587h-7wmj-h5pm), [GHSA-9jrq-w75r-8gcw](https://github.com/advisories/GHSA-9jrq-w75r-8gcw), [GHSA-rq7h-c2jc-7f22](https://github.com/advisories/GHSA-rq7h-c2jc-7f22).
- `vitest`: örökölt: `@vitest/coverage-v8`, örökölt: `@vitest/mocker`, [GHSA-82fw-gwwq-j7x9](https://github.com/advisories/GHSA-82fw-gwwq-j7x9).
- `vue`: örökölt: `@vue/server-renderer`.
