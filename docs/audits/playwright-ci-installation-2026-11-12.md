# Playwright CI telepítési blokkolók – második ellenőrzési pont

Dátum: 2026-11-12. Ág: `fix/ci-security-hardening`. Kiinduló HEAD:
`42155e3b9a2a116701eba4a9abee4f0b1b40b8f7`. PR: [#2](https://github.com/KovacsZoltan65/KM_Production/pull/2).
Állapot: helyileg ellenőrzött implementáció, hosted Linux-validációra vár.

## Bizonyított hibák és korlátok

A [frontend futás](https://github.com/KovacsZoltan65/KM_Production/actions/runs/43052617267)
két telepítési lépésének naplóit olvasási művelettel vizsgáltuk. Nyers
napló, hitelesítő adat vagy érzékeny diagnosztika nem került a jelentésbe.

| Job                                                        | Telepítési lépés            | Bizonyíték                                                                                             | Eredmény  |
| ---------------------------------------------------------- | --------------------------- | ------------------------------------------------------------------------------------------------------ | --------- |
| Playwright E2E (`130633535530`)                            | Install Playwright Chromium | APT: `Temporary failure resolving`; Ubuntu csomagtükrök névfeloldása sikertelen; APT kilépési kód: 100 | Failure   |
| Playwright cross-browser and mobile smoke (`130633535409`) | Install Playwright browsers | Azonos APT/DNS-hibák; a job 35 perc 16 másodperc után megszakadt                                       | Cancelled |

Érintett DNS-nevek: `azure.archive.ubuntu.com`, `archive.ubuntu.com`,
`security.ubuntu.com`. A keresztböngészős job időtartama összhangban van a
35 perces joblimittel, de a megszakítás pontos oka önmagában ebből nem bizonyított.

A „Some index files failed to download” üzenet APT-indexekre vonatkozik.
Böngészőbináris-letöltés megkezdésére vagy CDN-hibára nincs bizonyíték.
Hiányzó csomagra, feloldhatatlan csomagfüggőségre vagy futásidejű megosztott
könyvtár hiányára sem találtunk bizonyítékot. Az E2E előkészítési és
tesztlépések ezekben a jobokban nem futottak le.

A DNS-hiba kiváltó hálózati oka és átmeneti jellege nem igazolt. A retry
csak az átmeneti eseteket kezelheti; tartós hibánál a workflow továbbra is hibás.

## Minimális javítás

- Csak a két böngészőtelepítési lépés használ új Node-segédet:
  `node scripts/install-playwright-ci.js chromium`, illetve
  `node scripts/install-playwright-ci.js chromium firefox webkit`.
- A helyi, lockfile alapján telepített Playwright CLI fut, változatlan
  `install --with-deps` argumentumokkal. Nincs függőségellenőrzés-megkerülés.
- Legfeljebb három próbálkozás, köztük 10 és 20 másodperc várakozás.
  Csak felismert DNS-, kapcsolatmegszakadási, időtúllépési vagy egyes 5xx
  hálózati hibák indokolnak ismétlést. Felismert állandó hiba elsőbbséget élvez;
  ismeretlen hibánál és jelzéssel megszakított gyermekfolyamatnál nincs retry.
- A végső normál gyermekfolyamat-kilépési kód továbbadódik; nincs
  `continue-on-error`. A workflow-lépés teljes időkerete 12 perc, beleértve
  a próbálkozásokat és várakozásokat. A segéd önálló futtatásának nincs
  külön időkorlátja; CI-ben a GitHub Actions lépéslimit biztosítja azt.
- A kimenet csak próbálkozásszámot, biztonságos hibakategóriát és kilépési
  kódot közöl. A gyermekfolyamat nyers kimenete nem kerül konzolra vagy fájlba;
  a felismerés rövid memóriapuffert és összesített jelzőket használ.

Az `ubuntu-latest`, a 35 perces joblimit, a tesztparancsok, böngészők,
tesztlefedettség és a Playwright teszt-retry beállítása változatlan.
Az immutable Action SHA-k, a checkout `persist-credentials: false` értékei
és a kizárólag minimális JSON-ra korlátozott Playwright artifactok megmaradtak.
A CI-01–CI-06 implementációs fájlokat nem dolgoztuk át.

## Módosított fájlok ebben a szakaszban

- `.github/workflows/frontend.yml`
- `scripts/install-playwright-ci.js` (új)
- `tests/frontend/utils/playwrightInstall.test.js` (új)
- `docs/audits/playwright-ci-installation-2026-11-12.md` (új)

Az előző szakasz négy fájljának SHA-256 értéke a preflight és az ellenőrzés
során azonos volt: `package.json`, `package-lock.json`,
`tests/frontend/utils/dependencyCompatibility.test.js`,
`docs/audits/frontend-dependency-security-2026-11-12.md`.

## Helyi ellenőrzések

Windows, Node 26.10.0, npm 11.19.1; a CI Node 24-et használ.

| Ellenőrzés                                                                                                                                                                               | Eredmény                                                                                                   |
| ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------- |
| `node node_modules/vitest/vitest.mjs run tests/frontend/utils/playwrightInstall.test.js tests/frontend/utils/ciDiagnostics.test.js tests/frontend/utils/dependencyCompatibility.test.js` | 33/33 teszt, 3/3 fájl sikeres; exit 0                                                                      |
| `npm run format:check`                                                                                                                                                                   | Sikeres; exit 0                                                                                            |
| `node node_modules/prettier/bin/prettier.cjs --check --tab-width 2 .github/workflows/frontend.yml`                                                                                       | Sikeres; exit 0                                                                                            |
| `node --check` mindkét új JavaScript-fájlon                                                                                                                                              | Sikeres; exit 0                                                                                            |
| Workflow YAML-parse és szemantikai regresszióteszt                                                                                                                                       | Sikeres; telepítési limit, runner, joblimit, tesztparancsok, SHA-k, checkout és artifactkorlát ellenőrizve |
| Helyi CLI `install --dry-run chromium firefox webkit`, a `runInstaller` biztonságos kimenetkezelésén keresztül                                                                           | Sikeres; exit 0; nincs telepítés vagy letöltés                                                             |
| Friss online `npm audit --json`                                                                                                                                                          | 0 sérülékenység minden súlyosságon; exit 0                                                                 |
| Friss online `npm audit --omit=dev --json`                                                                                                                                               | 0 sérülékenység minden súlyosságon; exit 0                                                                 |
| `git diff --check`                                                                                                                                                                       | Sikeres; exit 0                                                                                            |

A tesztek igazolják a korlátozott retryt, a végső hibakód továbbadását,
a nem ismétlendő hibákat, a darabolt diagnosztika felismerését és a szintetikus
érzékeny jelölő visszatartását. A korábbi diagnosztikai és dependency
kompatibilitási tesztek is sikeresek. A Node helyi localStorage figyelmeztetése
nem teszthiba. Az audit első segédparancsa téves npm CLI-útvonal miatt nem
indult el; a táblázat a javított útvonallal végzett tényleges online auditokat rögzíti.

Hosted Linux APT-, böngészőtelepítési és teljes alkalmazás-E2E eredmény:
**NOT VERIFIED**. A Windows dry-run nem bizonyít Linux rendszerfüggőségeket.
Új GitHub Actions-futást nem indítottunk. Teljes quality gate-et és adatbázist
módosító előkészítést nem futtattunk; ezen módosítás célzott ellenőrzései
nem igényeltek adatbázist. A korábbi dependency-szakasz teszteredményeit az
előző auditjelentés tartalmazza, azokat nem állítjuk most újrafuttatottnak.

## Fennmaradó kockázatok és következő lépések

Külön jóváhagyott commit/push után a szokásos automatikus Actions-futásban
mindkét telepítést és az összes meglévő E2E projektet ellenőrizni kell.
Igazolni kell a tényleges telepítési időt, a 12 perces keret megfelelőségét,
valamint a retry utáni sikeres vagy egyértelműen hibás lezárást.
Nem szabad sikerként jelenteni kihagyott teszteket. Tartós DNS-hibánál
a korlátozott retry nem oldja meg a kiváltó okot.

A [hivatalos Playwright CI-útmutató](https://playwright.dev/docs/ci) és
a [böngészőtelepítési útmutató](https://playwright.dev/docs/browsers)
alapján megmarad a támogatott `--with-deps` telepítés.
A [hivatalos konténer](https://playwright.dev/docs/docker) előnye az előre
telepített böngésző- és rendszerfüggőség-készlet, ami csökkentené a futás közbeni
APT-igényt. Hátránya az alkalmazás Playwright-verziójával egyező image
karbantartása, a PHP/Composer/Laravel környezet külön integrációja és az
image-letöltés továbbra is fennálló hálózati igénye. Bevezetése nagyobb
workflow-változás és külön jóváhagyást igényel; ebben a szakaszban nem történt meg.

COMMIT: NOT CREATED

PUSH: NOT PERFORMED

MERGE: NOT PERFORMED
