# Frontend Vitest worker-stabilitási audit

## Hatókör és környezet

- Dátum: 2026-07-28
- Backlog: `CI-001`
- Platform: Windows x64, 4 elérhető logikai futási hely
- Memória a diagnosztika kezdetén: 7,87 GB összesen, 0,92 GB szabad
- Node: `v26.5.0`
- npm: `11.17.0`
- Vitest: `4.1.10`
- Vite: `8.0.16`
- Vue plugin: `6.0.7`
- DOM-környezet: `jsdom 29.1.1`
- Tesztkészlet: 20 fájl, 166 teszt
- Helyi és CI-parancs: `npm run test:frontend`

A kiinduló konfiguráció `jsdom` környezetet, fájlonkénti izolációt,
fájlpárhuzamosítást, a Vitest alapértelmezett `forks` poolját és legfeljebb
négy workert használt. A teszt-, hook- és teardown-timeout nem volt felülírva;
a Vitest alapértékei 5, 10 és 10 másodperc. Retry nem volt beállítva.

## Korábbi reprodukciós bizonyíték

A [2026-07-27-i projektaudit](project-audit-2026-07-27.md) ugyanazon
tesztkészlet alapértelmezett futásában hét worker-timeoutot rögzített, míg az
egyszálas kontroll 20/20 fájllal és 166/166 teszttel sikerült. A megőrzött
audit nem tartalmazza a Vitest teljes hibaüzenetét, ezért a pontos worker RPC
al-eset nem állapítható meg. A hiba worker-szintű timeout volt, nem bizonyított
teszt- vagy hook-timeout és nem alkalmazáslogikai regresszió.

## Teszt- és állapotszivárgási audit

- A közös setup minden teszt után visszaállítja a valódi timereket.
- A tesztekben nincs `setInterval`, kihagyott timer-visszaállítás vagy
  szándékosan pending hálózati kérés.
- Minden vizsgált `trigger`, `setValue` és `setProps` hívás awaitelt.
- A normál `forks` profil `--detectAsyncLeaks` futása 20/20 fájllal és 166/166
  teszttel, leak finding nélkül zárult.
- A `threads` diagnosztika 25, főként modulimporthoz kötött `TickObject`
  findinget jelzett, ezért a poolváltás nem lett végleges javítás.
- A `--no-isolate` kontroll 3 locale/mock állapotszivárgási hibát okozott. Az
  izoláció kikapcsolása ezért nem elfogadható.

A tesztfájlok vagy assertionök módosítása nem indokolt.

## Reprodukciós mátrix

Az idők a Vitest által jelentett suite-idők; minden sor változatlan dependency
state mellett futott.

| Mód                  | Workers | Pool      | Futtatások | Siker | Hiba | Átlagidő | Leglassabb |
| -------------------- | ------: | --------- | ---------: | ----: | ---: | -------: | ---------: |
| Kiinduló alapmód     |       4 | `forks`   |          3 |     3 |    0 |  76,90 s |    81,76 s |
| Egy worker           |       1 | `forks`   |          3 |     3 |    0 | 100,80 s |   104,95 s |
| Korlátozott          |       2 | `forks`   |          3 |     3 |    0 |  52,25 s |    52,73 s |
| Pool-kontroll        |       2 | `threads` |          3 |     3 |    0 |  52,66 s |    57,95 s |
| Pool-kontroll        |       4 | `threads` |          3 |     3 |    0 |  39,11 s |    40,38 s |
| Izoláció kikapcsolva |       2 | `forks`   |          1 |     0 |    1 |  10,70 s |    10,70 s |

A három alapfuttatás wall ideje 82,740, 73,633 és 85,116 másodperc volt. A
2 workeres `forks` kontrollok wall ideje 56,894, 56,302 és 55,092 másodperc
volt.

## Memória- és folyamatmérés

Azonos `forks` pool mellett a Node-folyamatok összes working setjét 500 ms-os
mintavétellel mértük:

| Workers | Peak Node-folyamat | Peak working set | Teszteredmény             |
| ------: | -----------------: | ---------------: | ------------------------- |
|       4 |                  7 |         914,6 MB | 20/20 fájl, 166/166 teszt |
|       2 |                  5 |         570,0 MB | 20/20 fájl, 166/166 teszt |

A két workeres profil 344,6 MB-tal, körülbelül 37,7%-kal alacsonyabb mért peak
working set mellett futott. A négy workeres profil a négy logikai futási helyet
teljesen lekötötte, miközben minden fork külön jsdom környezetet tartott fenn.
Az egyszálas profil a kisebb párhuzamosság ellenére lényegesen lassabb volt.

## Gyökérok és döntés

A bizonyított konfigurációs tartomány alapján a worker-timeout oka a
folyamat-alapú jsdom workerek túl magas párhuzamos erőforrásigénye volt a
korlátozott Windows környezetben. Ezt támasztja alá:

1. a korábbi hét worker-timeout és az egyszálas siker;
2. a 4 workeres profil 914,6 MB-os peak Node working setje;
3. a 2 workeres profil 37,7%-kal kisebb peak working setje;
4. a 2 workeres profil 3/3 stabil és a 4 workeres baseline-nál gyorsabb futása;
5. a normál `forks` async-leak kontroll sikere.

A javítás megtartja a fájlpárhuzamosítást, az izolációt és a `forks` poolt, de
a maximumot négyről két workerre csökkenti. Nem emel timeoutot, nem vezet be
retry-t, nem hagy ki tesztet, és nem változtat assertiont. Az explicit pool
rögzíti a mért végrehajtási modellt a későbbi Vitest-alapértelmezés változása
ellen.

## Javítás utáni stabilitás

| Ellenőrzés                                       | Futtatások | Sikeres | Hibás | Átlagos wall idő | Leglassabb |
| ------------------------------------------------ | ---------: | ------: | ----: | ---------------: | ---------: |
| `AdminCrudPage.test.js` (14 teszt)               |          3 |       3 |     0 |          27,07 s |    57,23 s |
| `tests/frontend/components` (11 fájl, 104 teszt) |          3 |       3 |     0 |          47,19 s |    67,56 s |
| Teljes frontend suite (20 fájl, 166 teszt)       |          5 |       5 |     0 |          73,25 s |    82,58 s |

Az első célzott futást egyszeri hideg transform/cache költség terhelte; a
Vitest által jelentett idők 18,58, 7,89 és 6,84 másodperc voltak. A teljes
suite öt Vitest-ideje 59,98, 59,01, 78,56, 79,68 és 71,53 másodperc, átlaguk
69,75 másodperc. Egyik futásban sem történt workerhiba vagy timeout.

| Mérőszám                      |   Előtte |    Utána |                           Változás |
| ----------------------------- | -------: | -------: | ---------------------------------: |
| Átlagos wall idő              |  80,50 s |  73,25 s |                    -7,25 s (-9,0%) |
| Leglassabb wall idő           |  85,12 s |  82,58 s |                            -2,54 s |
| Workerhibák a mért futásokban |        0 |        0 |                                  0 |
| Timeoutok a mért futásokban   |        0 |        0 |                                  0 |
| Workerszám                    |        4 |        2 | -2, a fájlpárhuzamosítás megmaradt |
| Peak Node working set         | 914,6 MB | 570,0 MB |                 -344,6 MB (-37,7%) |

## Quality gate eredmények

| Ellenőrzés            | Eredmény                                                                                 |
| --------------------- | ---------------------------------------------------------------------------------------- |
| Teljes frontend suite | 5/5 sikeres; minden futás 20/20 fájl és 166/166 teszt                                    |
| Coverage              | 20/20 fájl, 166/166 teszt; 79,81% statement, 79,49% branch, 64,45% function, 79,53% line |
| Production build      | Sikeres; 954 modul, 11,25 s build                                                        |
| i18n                  | Sikeres; 690 szinkronizált kulcs és érvényes statikus hivatkozások                       |
| Prettier              | A módosított konfigurációs és dokumentációs fájlokon sikeres                             |
| Whitespace            | `git diff --check` sikeres                                                               |

Az `npm ci` két próbában ugyanazon, egy már futó külső Node-folyamat által
zárolt `lightningcss.win32-x64-msvc.node` fájlon Windows `EPERM` hibával
megállt. Az első próba részlegesen eltávolította a `node_modules` tartalmát; a
meglévő `package-lock.json` alapján futtatott `npm install` helyreállította a
dependency fát. Az ellenőrzött verziók ismét `Vitest 4.1.10`, `Vite 8.0.16`,
`@vitejs/plugin-vue 6.0.7` és `jsdom 29.1.1`; sem `package.json`, sem
`package-lock.json` nem változott. A helyreállítás után a coverage suite és a
production build is sikeres volt.

Az `npm audit --omit=dev` egy magas súlyosságú, javítható `postcss` findinget
jelzett. Dependency-változás és automatikus audit-fix nem része ennek a
feladatnak; a finding policy-ja és javítása a már nyitott `CI-007` feladata.

## CI-hatás és fennmaradó kockázat

A GitHub Actions ugyanazt az `npm run test:frontend` scriptet használja, ezért
a repository-konfiguráció helyben és CI-ben is érvényesül. A workflow nem
változott. A jelen végrehajtásból GitHub-hosted Node 24 eredmény nem áll
rendelkezésre; ezt a `CI-002` feladatnak kell igazolnia.

Node 26 alatt minden jsdom környezet létrehozásakor megjelenik a
`localStorage is not available because --localstorage-file was not provided`
experimental warning. A tesztek saját `window.localStorage` objektuma működik,
és a warning mindegyik vizsgált poolban megjelent, ezért nem azonosítható a
worker-timeout gyökérokaként. A projekt CI-je Node 24-et használ.
