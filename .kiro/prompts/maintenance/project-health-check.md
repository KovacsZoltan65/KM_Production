# KM_Production — Project Health Check

## Cél és szabályforrások

Mérd fel a projekt állapotát a kért területeken, futtasd az alkalmazandó
ellenőrzéseket, és adj bizonyítékkal alátámasztott jelentést. Javítást csak az
alább meghatározott módban és a felhasználói felhatalmazás keretében végezz.

Először olvasd az [AGENTS.md](../../../AGENTS.md) és az
[index](../../index.md) alapján a kapcsolódó steering-, ADR-, tudás- és
eljárásdokumentumokat. Ne tölts be automatikusan minden dokumentációs réteget.

- Az [AGENTS.md](../../../AGENTS.md) szabályozza a felhatalmazást.
- A [Definition of Done](../../../docs/project-management/definition-of-done.md)
  szabályozza az alkalmazhatóságot, eredményeket és lezárást.
- A [Layered Quality Gates](../../../docs/development/quality-gates.md)
  határozza meg a validálás körét és a futtató korlátait.
- A [Testing Steering](../../steering/testing.md) adja a tesztelési elveket.
- Ez a prompt a projektfelmérés eljárása; a
  [Test Maintenance](test-maintenance.md) a tesztkarbantartás részletes menete.

## 1. Válaszd ki a kért módot és hatókört

| Mód                 | Teendő és módosítási határ                                                                                                                                                                               |
| ------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Audit / assessment  | Olvass, futtass alkalmazandó ellenőrzéseket, és jelents megállapításokat. Ne javíts projektfájlt vagy üzleti viselkedést. Az izolált tesztfuttatás saját átmeneti adatai és riportjai az eljárás részei. |
| Execution / autofix | A kifejezetten kért javítási körben diagnosztizálj, javíts és ellenőrizz. A mód neve nem bővíti a felhasználó felhatalmazását.                                                                           |

A felhasználó aktuális kérése alapján válassz. Puszta állapotfelméréshez az
audit módot használd. Ha a javítási kör nem állapítható meg, a felmérést
folytathatod, de a kérdéses módosítás előtt tisztázd a határt. Meglévő explicit
felhatalmazást ne kérj újra. A Test Maintenance hivatkozása audit módban nem
kapcsolja be az autofixet.

Rögzítsd a kért célt, az engedélyezett módosítási kört, az érintett modulokat és
fájlokat, valamint az ellenőrzési tervet. Szélesebb összefüggés olvasása nem
engedély kapcsolódó refaktorra. A „biztonságos javítás” megnevezés sem önálló
felhatalmazás: cast, factory, seeder vagy konfiguráció változása is érinthet
üzleti működést.

Üzleti hiba gyanújánál azonosítsd az elvárt szabályt, gyűjts reprodukálható
bizonyítékot, és mutasd meg az eltérést. Üzleti logikát csak akkor módosíts,
ha a jelenlegi felhasználói kérés ezt a hatókört kifejezetten engedélyezi.
Enélkül jelentsd a hibát és állj meg az üzleti viselkedés módosítása előtt.
Az, hogy a kódot hibásnak ítéled, nem helyettesíti az AGENTS.md szerinti
felhatalmazást. A független, engedélyezett vizsgálatot és javítást folytasd.

## 2. Védd a meglévő munkát

A projekt gyökerében futtasd:

```bash
git branch --show-current
git status --short
git log -1 --oneline
git diff
git diff --cached
git ls-files --others --exclude-standard
```

Azonosítsd a meglévő módosításokat és nem követett fájlokat. Módosítás előtt
rögzítsd a kiinduló diffet és szükség szerint a fájlhash-eket. Nem kell tiszta
munkafa. Idegen változást ne állíts vissza, ne törölj, ne írj felül és ne
stage-elj. Átfedő célfájlnál jelentsd az átfedést, őrizd meg a felhasználó
munkáját, és bizonytalan összeillesztés előtt kérj tisztázást.

Ne használj `git reset --hard`, `git clean` vagy széles restore műveletet a
munkafa tisztítására. Audit módban ellenőrző formázást válassz; autofix módban
is csak az engedélyezett fájlokat formázd. A tesztekhez kizárólag az eljárások
szerinti izolált adatbázist és futási környezetet használd.

## 3. Állíts össze arányos vizsgálati tervet

A cél és kockázat szerint válassz az alábbi területekből. A táblázat nem minden
feladatnál kötelező teljes ellenőrzési lista.

| Terület                        | Mit vizsgálj, ha alkalmazandó?                                                                                                                                                                                                                                                                             |
| ------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Architektúra és üzleti védelem | Controller → Service → Repository → Model határok, validáció, policy, tranzakció, készletmozgás, traceability és auditnapló.                                                                                                                                                                               |
| Backend és adatbázis           | Érintett Unit/Feature tesztek, cache-regresszió, SQLite/MySQL-eltérések, migráció és visszaállítás; [backendeljárás](../../../docs/backend-quality-gate.md).                                                                                                                                               |
| Frontend                       | Komponens- és Inertia-szerződés, lokalizáció, build és indokolt lefedettség; [frontendeljárás](../../../docs/frontend-testing.md).                                                                                                                                                                         |
| E2E                            | Érintett felhasználói folyamatok, jogosultság, akadálymentesség, billentyűzet és böngészők; [E2E-eljárás](../../../docs/e2e-testing.md).                                                                                                                                                                   |
| Statikus elemzés és formázás   | Konfigurált Larastan/PHPStan, Pint és az érintett fájlok Prettier-ellenőrzése; [statikus elemzés](../../../docs/static-analysis.md).                                                                                                                                                                       |
| Biztonság és függőségek        | Jogosultság, nem megbízható feltöltés, titkok és naplózás, tényleges lockfile-ok és alkalmazandó auditok. Függőséget ne frissíts a felhatalmazott körön kívül.                                                                                                                                             |
| AI/Python/OCR                  | Meglévő kód és tesztek, JSON-szerződés, Laravel oldali validálás, queue, időkorlát, telemetria és adatvédelem az index kapcsolódó útmutatói szerint. Python ne érje el az alkalmazás adatbázisát; AI ne írjon közvetlenül üzleti adatot. Az opcionális OCR-motort ne tedd minden vizsgálat előfeltételévé. |
| Teljesítmény                   | N+1, lapozás, nagy lekérdezések, queue- és telemetria-növekedés. Javulást csak összehasonlítható méréssel állíts.                                                                                                                                                                                          |
| Eszközök és dokumentáció       | Script/config/CI összhang, linkek, elavult állítások, szabály–megvalósítás eltérések. Workflow-fájlból ne következtess GitHub required státuszra.                                                                                                                                                          |

Válassz a rétegezett útmutató szerint Affected/Fast, Module, Integration vagy
Full szintet. Célzott diagnózis után a javítás hatásának megfelelő regresszió
következzen. Több modult érintő közös alkalmazásműködés Integration,
teszt-/build-/projektinfrastruktúra vagy tesztkonfiguráció változása Full
kockázatú. Nem kell minden szintet sorban futtatni.

Alkalmazás- vagy eszközváltozásnál vizsgáld meg a
`php tools/quality-gate.php affected --dry-run --explain` tervét. A kiválasztó
idegen munkafaváltozásokat is figyelembe vehet; ez nem engedély azok javítására.
A tiszta munkafa sem bizonyítja egy teljes ág ellenőrzését: a vizsgálat céljához
válassz alapot és terjedelmet az útmutató szerint. A dokumentált alulbesorolást
jelentsd, és a szabály szerinti szintet válaszd. A dry-run nem tesztbizonyíték.

## 4. Ellenőrizd a parancsokat és futtasd a kiválasztott lépéseket

Indítás előtt vesd össze a parancsot a [composer.json](../../../composer.json),
a [package.json](../../../package.json) és a fenti eljárások aktuális tartalmával.
A következő meglévő parancsokból csak az alkalmazandókat válaszd:

- Backend: `composer test:backend:sqlite`, `composer test:backend:mysql`;
  migráció: `composer test:backend:migrations:sqlite`,
  `composer test:backend:migrations:mysql`. Célzott futáshoz a Test Maintenance
  és a backendeljárás védett indítóscriptjét használd.
- Frontend: `npm run test:frontend`, indokolt méréshez
  `npm run test:frontend:coverage`; lokalizáció: `npm run i18n:check`;
  build: `npm run build`.
- Formázás/elemzés: `vendor/bin/pint --test`, `composer analyse`,
  `npm run format:check`; függőség-leírás: `composer validate --strict`.
- Biztonsági audit: `composer audit`, `npm audit`, `npm audit --omit=dev`.
- Böngészős vizsgálat: `npm run test:e2e`; célzott projektet és az előkészítést
  az E2E-eljárásból válaszd. Ne találj ki hiányzó teszt- vagy Python-scriptet.
- Rétegezett futás: `composer qa:fast`, `composer qa:module procurement`,
  `composer qa:integration` vagy `composer qa:full`; a modul a tényleges körhöz igazodjon.

SQLite nem helyettesíti az alkalmazandó MySQL-vizsgálatot. Frontendteszt nem
E2E; egy Playwright-projekt nem bizonyítja az összes projektet. Minden projekt
sikere is csak a konfigurált tesztkörét igazolja. A `composer qa:full` nem
futtat MySQL-t, csak admin Chromium E2E-t tartalmaz, és nem bizonyít cspell-t,
minden Markdown-ellenőrzést, GitHub required checket vagy release-készséget.
Sorold fel és végezd el a külön alkalmazandó vizsgálatokat.

Csak dokumentáció vagy prompt módosításakor a formázás, helyi linkek,
parancspéldák, terminológia és `git diff --check` ellenőrzése szükséges;
alkalmazás-QA nem automatikus. Futtatható működés vagy eszközváltozás esetén
a tényleges hatást a DoD szerint értékeld. A `npm run format:check` nem
ellenőriz Markdown-fájlokat; azokra célzott Prettier-vizsgálat kell.

## 5. Diagnosztizáld és kezeld a megállapításokat

Javítás előtt különítsd el az alkalmazáskód, teszt, fixture/factory/seeder,
konfiguráció, környezet/infrastruktúra, külső függőség és ingadozó működés
hibáját. Teszthiba nem automatikusan termékhiba. Indítási hibánál nézd meg a
hibakódot, naplót és a megállás szakaszát; ne címkézd automatikusan környezetinek.

Audit módban jelents bizonyítékot és javítási javaslatot. Autofix módban a
felhatalmazott körben javítsd a bizonyított okot; a körön kívüli munkát
megállapításként add át. Ne gyengíts elvárást, ne törölj értelmes tesztet,
ne hagyj ki csendben hibát, és ne adj általános ignore/suppression szabályt.
Bizonyítottan téves tesztet javíthatsz a kért körben; helyes üzleti működést
ne írj át a teszt kedvéért. Indokolt regressziós lefedettséget a Testing Steering
és a Test Maintenance szerint pótolj. Memória- vagy dokumentációfrissítés is
csak a feladat módosítási keretében végezhető.

A DoD eredményeit használd: `PASSED`, `FAILED`, `BLOCKED`, `NOT RUN`.
Assertionhiba, kódhiba miatti alkalmazásindítási hiba és tiltott advisoryt
jelző audit `FAILED`. Igazolt külső/környezeti előfeltétel-hiány `BLOCKED`,
például az alkalmazandó MySQL tesztszerver elérhetetlensége. Egy el nem
indított ellenőrzés `NOT RUN`; előkészítés elmaradásából ne következtess
környezeti akadályra. Az auditforrás tényleges elérhetetlenségét különítsd el
az audit által talált hibától.

Fail-fast leállásnál csak a sikeres korábbi lépések `PASSED` eredményűek.
A hibás lépést a diagnózis szerint minősítsd; a későbbi el nem indított
kötelező lépéseket `NOT RUN` értékkel jelentsd. Ne állítsd, hogy a teljes sor
lefutott. Javítás után ellenőrizd újra a hibás és érintett részeket, pótold a
kimaradt kötelező lépéseket. Megfelelő sikeres bizonyítékot ne ismételj indok
nélkül; az ingadozó eredmények mindkét futását őrizd meg.

## 6. Jelents és értékeld a készültséget

A jelentésben add meg:

- a módot, vizsgált hatókört, érintett fájlokat és választott validálási szintet;
- a bizonyított megállapításokat, kockázatot és a még bizonytalan állításokat;
- a módosított fájlokat, végrehajtott javításokat és új/módosított teszteket;
- a tényleges parancsokat, időpontot/futásazonosítót, környezetet és eredményeket;
- minden nem `PASSED` ellenőrzés nevét, okát, hatását, felelősét és következő lépését;
- a nem alkalmazandó vizsgálatok rövid indokát, a körön kívüli javításokat és
  nyitott felhatalmazási kérdéseket;
- a megőrzött idegen változásokat és a végső `git status --short` eredményét.

A saját teljes diffet és az esetleges staginget nézd át. A jelentés elkészülte,
a javítás implementálása és a feladat lezárása külön állítás. Auditban a
felmérés eredményét add át; ez nem jelenti a feltárt hibák kijavítását vagy a
projekt teljes egészségét. A kért feladat készültségét a DoD szerint értékeld.
Feloldatlan alkalmazandó kötelező ellenőrzés mellett ne állíts teljes készültséget.
Leírt ok nem `PASSED`; történeti zöld tesztszám, coverage vagy CI-futás nem mai
bizonyíték. A [backlogállapot](../../../docs/project-management/backlog-conventions.md)
nem ellenőrzési eredmény.

Ez a prompt és egy sikeres health check nem ad commit-, push-,
PR-létrehozási/frissítési, merge-, release- vagy deploy-felhatalmazást.
Ezekhez az AGENTS.md és a jelenlegi felhasználói kérés szerinti explicit
engedély kell. Felhatalmazott Git/PR-műveletnél alkalmazd a
[commit előtti](../../checklists/before-commit.md),
[merge előtti](../../checklists/before-merge.md) és
[code review](../../../docs/project-management/code-review-guide.md) eljárást;
a lista nem helyettesíti az engedélyt.
