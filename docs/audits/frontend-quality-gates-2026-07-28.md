# Frontend quality gate szétválasztási audit

## Cél

A `CI-002` célja a frontend unit teszt, a fordítási szerződés és a production
build önálló GitHub Actions-jobjainak kialakítása úgy, hogy egyik gate hibája se
akadályozza a másik kettő elindulását. A változtatás nem állít be required
checket vagy branch protection szabályt, és nem módosít frontend üzleti
logikát.

## Kiinduló állapot

- Dátum: 2026-07-28
- Ág: `main`, követett ág: `origin/main`
- Kiinduló commit: `84747bf`
- Kiinduló eltérés: `main...origin/main` = `0/0`
- Workflow: `.github/workflows/frontend.yml`, megjelenített név: `Frontend`
- Triggerek: minden pull request és a `main` ágra történő push
- Jogosultság: `contents: read`
- Runner: `ubuntu-latest`
- CI Node: 24; helyi Node: `v26.5.0`; helyi npm: `11.17.0`
- Node-verziót rögzítő `.nvmrc`, `.node-version` vagy `package.json#engines`
  nincs; a meglévő frontend és E2E-jobok egységesen Node 24-et használnak.
- Dependency telepítés: `npm ci`, gyökérszintű `package-lock.json` és npm cache
- Lokális frontend verziók: Vitest `4.1.10`, Vite `8.0.16`,
  `@vitejs/plugin-vue` `6.0.7`

## Korábbi workflow felépítése

Az egyetlen `frontend` job `Frontend unit, i18n and build` néven, 15 perces
timeouttal az alábbi sorrendben futott:

1. `npm ci`;
2. `npm audit`;
3. `npm audit --omit=dev`;
4. `npm run test:frontend`;
5. `npm run i18n:check`;
6. `npm run build`.

| Quality gate | Korábbi job                   | Parancs                 | Önálló check | Korábbi hibától független |
| ------------ | ----------------------------- | ----------------------- | ------------ | ------------------------- |
| Unit         | Frontend unit, i18n and build | `npm run test:frontend` | nem          | nem                       |
| i18n         | Frontend unit, i18n and build | `npm run i18n:check`    | nem          | nem                       |
| Build        | Frontend unit, i18n and build | `npm run build`         | nem          | nem                       |

A lépések alapértelmezett fail-fast viselkedése miatt bármely korábbi audit
vagy gate hibája megakadályozhatta a későbbi eredmények létrejöttét. A három
hibakategória egyetlen checknév alatt jelent meg. Coverage nem volt része ennek
a CI-jobnak; a repository külön diagnosztikai
`npm run test:frontend:coverage` scriptet tart fenn.

## Célarchitektúra

| Jobazonosító                | Megjelenített név         | Felelősség                 | Függőség |
| --------------------------- | ------------------------- | -------------------------- | -------- |
| `frontend-unit`             | Frontend Unit Tests       | Vitest unit suite          | nincs    |
| `frontend-i18n`             | Frontend i18n Check       | Fordítási szerződés        | nincs    |
| `frontend-build`            | Frontend Production Build | Vite production build      | nincs    |
| `frontend-dependency-audit` | Frontend Dependency Audit | Meglévő npm security audit | nincs    |

A négy job közvetlenül a közös workflow-triggerből indul. A dependency audit
különválasztása megőrzi a meglévő két security parancsot anélkül, hogy azokat
a három célgate felelősségébe keverné. Az audit severity-, kivétel- és
triage-policy továbbra is a `CI-007` hatóköre.

## Stabil checknevek

| Workflow   | Jobazonosító     | Megjelenített checknév    | Jelenlegi required állapot |
| ---------- | ---------------- | ------------------------- | -------------------------- |
| `Frontend` | `frontend-unit`  | Frontend Unit Tests       | nem igazolt                |
| `Frontend` | `frontend-i18n`  | Frontend i18n Check       | nem igazolt                |
| `Frontend` | `frontend-build` | Frontend Production Build | nem igazolt                |

A jobok stabil checkkontextust biztosítanak a későbbi branch protection
beállításhoz. Required státuszukat repository-beállításból kell külön
igazolni. A workflow-konfiguráció önmagában nem teszi őket required checkké.

## Végrehajtott módosítások

- Az összetett frontend job három önálló quality gate-re vált szét.
- A meglévő npm auditok önálló dependency-audit jobba kerültek; a parancsok és
  sorrendjük változatlan.
- Mind a négy job Node 24-et, npm cache-t és `npm ci` telepítést használ.
- A unit és build job timeoutja 10 perc, az i18n jobé 5 perc. A helyi mért
  maximumok rendre 112,30, 36,69 és 4,29 másodperc voltak.
- A széles pull request- és `main` push-trigger, a minimális read permission,
  valamint a Playwright-jobok változatlanok.
- Nem jött létre jobok közötti `needs`, retry, `continue-on-error`, worker
  override, artifact vagy deployment.
- A `vitest.config.js` változatlanul `forks` poolt és legfeljebb két workert
  használ, fájlizoláció mellett.

## Helyi validáció

### Módosítás előtti baseline

| Gate  | Futtatások | Siker | Átlagos wall idő | Eredmény                 |
| ----- | ---------: | ----: | ---------------: | ------------------------ |
| Unit  |          3 |   3/3 |         102,47 s | 20 fájl, 166 teszt/futás |
| i18n  |          1 |   1/1 |           2,20 s | 690 szinkronizált kulcs  |
| Build |          1 |   1/1 |          24,69 s | 954 transzformált modul  |

### Módosítás utáni quality gate

| Gate     | Futtatások | Siker | Átlagos wall idő | Részletes eredmény                                            |
| -------- | ---------: | ----: | ---------------: | ------------------------------------------------------------- |
| Unit     |          5 |   5/5 |          96,03 s | minden futás 20/20 fájl és 166/166 teszt                      |
| i18n     |          2 |   2/2 |           2,89 s | 690 kulcs és minden statikus hivatkozás érvényes              |
| Build    |          2 |   2/2 |          23,89 s | mindkét futás 954 modult fordított                            |
| Coverage |          1 |   1/1 |         106,82 s | 79,81% statement; 79,49% branch; 64,45% function; 79,53% line |

Az `npm ci --dry-run` sikeres volt, ezért a `package.json` és a lockfile
konzisztens. A teljes és production npm audit helyi hálózati lekérdezése a
sandboxból nem érte el az npm advisory endpointot; az engedélyezett hálózati
újrafuttatást a környezet adatkiáramlási szabálya elutasította. Ebből nem
következik audit-siker vagy findingmentesség.

## Hibafüggetlenség

| Próba | Módszer                                       | Exit code | Helyreállítás                   |
| ----- | --------------------------------------------- | --------: | ------------------------------- |
| Unit  | nem létező, célzott Vitest-fájl               |         1 | nem módosított fájlt            |
| i18n  | ideiglenes, csak angol fordításkulcs          |         1 | a JSON pontosan visszaállítva   |
| Build | ideiglenes, nem feloldható Vite config import |         1 | a config pontosan visszaállítva |

A végső diffben egyik hibapróba sem maradt meg. A jobgráf statikusan négy
egymástól független gyökérjobot tartalmaz; egyik frontend quality gate sem
használ `needs` vagy másik job eredményére épülő `if` feltételt.

## GitHub Actions validáció

- Workflow: `Frontend`
- Run ID: [`30365414060`](https://github.com/KovacsZoltan65/KM_Production/actions/runs/30365414060)
- Run number: 15
- Trigger: `push`
- Commit: `33864e229b00cdb86aaebf9c3d57ba256cd973ff`
- Run attempt: 1

| Job                       | Eredmény | Jobidő | Gate-lépés |
| ------------------------- | -------- | -----: | ---------: |
| Frontend Unit Tests       | success  |   25 s |       12 s |
| Frontend i18n Check       | success  |   11 s |        1 s |
| Frontend Production Build | success  |   13 s |        1 s |
| Frontend Dependency Audit | failure  |   11 s |        1 s |

A három `CI-002` céljob egymástól és a dependency audittól függetlenül
elindult és sikeresen befejeződött GitHub-hosted Node 24 környezetben. Nem
történt workerhiba, timeout vagy másik frontend quality gate miatti kihagyás.
A nyilvános API a sikeres unit job tesztszámát tartalmazó loghoz
adminjogosultságot kért, ezért a CI-beli 166-os tesztszám nem olvasható vissza;
a helyi 5/5 futás mindegyikében 166 teszt futott.

A teljes workflow nem tekinthető sikeresnek: a megőrzött `npm audit` parancs
exit code 1-gyel zárult, ezért ugyanabban a dependency-audit jobban az
`npm audit --omit=dev` lépés kimaradt. A nyilvános check API a konkrét
findinget nem közli, csak a nem nulla exit code-ot. A security policy és a
finding javítása a `CI-007` scope-ja; ezt a `CI-002` nem előzi meg.

Mind a négy új job warning annotációt kapott, mert az `actions/checkout@v4` és
az `actions/setup-node@v4` Node 20 action runtime-ját a GitHub 2026-ban Node
24-en kényszerítve futtatja. A jobok ettől sikeresen elindultak; az
actionverziók célzott felülvizsgálata külön karbantartási feladat.

## CI-001 regresszióellenőrzés

- Pool: `forks`
- Maximum worker: 2
- Izoláció: bekapcsolva
- Teljes suite: 5/5 sikeres
- Aktuális tesztkészlet: minden futásban 20 fájl és 166 teszt
- Workerhiba: 0
- Timeout: 0
- Workflow worker override, retry vagy timeoutemelés: nincs
- Coverage: sikeres, a CI-001-ben rögzített százalékokkal azonos

A helyi Node 26 környezetben az ismert `localStorage` experimental warning
megjelent. A CI-001 audit szerint ez nem a worker-timeout gyökéroka.

## Biztonsági ellenőrzés

A módosított frontend workflow-ban nincs `continue-on-error`, `|| true`,
`exit 0`, `set +e`, `--passWithNoTests`, `--force`, `--legacy-peer-deps`,
retry, secret, `pull_request_target`, `write-all` vagy írási jogosultság.
Tesztben nem történt `.skip`, `.only`, `.todo` vagy assertionmódosítás.

## Fennmaradó kockázatok

- A három check required státusza nincs beállítva vagy igazolva; ez a `CI-005`
  és `GOV-005` későbbi feladata.
- A dependency audit valós CI-futása hibás, a production auditlépés kimaradt;
  a policy és minden aktuális finding kezelése `CI-007`.
- A használt v4 checkout és setup-node actionök Node 20 runtime deprecation
  warningot adnak a GitHub Node 24 kényszerített futtatása mellett.
- A coverage külön CI-kapuvá vagy artifacttá emelése nem része a `CI-002`
  feladatnak.
- A Prettier önálló CI-gate-je és a teljes release-folyamat konszolidációja
  külön backlogmunka.

## Következő lépések

1. A `CI-007` alatt a dependency-audit finding és a két auditlépés független
   kiértékelésének rendezése.
2. Zöld teljes frontend workflow után a `CI-002` lezárása.
3. A `CI-003` előkészítése a végrehajtási terv szerint.
