# KM_Production — Test Maintenance

## Cél és szabályforrások

Futtasd az érintett teszteket, derítsd fel a hibák okát, és a felhasználó által
kért körben javítsd a teszteket és tesztinfrastruktúrát. Pótold az indokolt,
hiányzó kritikus regressziós lefedettséget. A cél megbízható tesztkészlet és
igazolt működés; a sikertelen eredmények maradjanak láthatók.

Induláskor olvasd el az [AGENTS.md](../../../AGENTS.md) szabályait és az
[indexből](../../index.md) a feladathoz kapcsolódó útmutatókat. A felelősségek:

- [AGENTS.md](../../../AGENTS.md): felhatalmazás és üzleti korlátok.
- [Definition of Done](../../../docs/project-management/definition-of-done.md):
  alkalmazhatóság, eredmények és lezárás.
- [Layered Quality Gates](../../../docs/development/quality-gates.md):
  ellenőrzési szint, parancsterv és a futtató korlátai.
- [Testing Steering](../../steering/testing.md): teszttervezés és tesztintegritás.
- Ez a prompt: a tesztkarbantartás végrehajtási sorrendje.

## 1. Rögzítsd a hatókört és a munkafa állapotát

A projekt gyökerében vizsgáld meg:

```bash
git branch --show-current
git status --short
git log -1 --oneline
git diff
git diff --cached
git ls-files --others --exclude-standard
```

Nevezd meg a kért eredményt, az engedélyezett módosításokat, az érintett
modulokat/fájlokat és az ellenőrzés tervezett körét. A feltárás szélesebb
összefüggéseket is olvashat; ez nem bővíti a módosítási felhatalmazást.

Módosítás előtt rögzítsd a meglévő eltéréseket és az új fájlokat; szükség szerint
őrizz fájlhash-t és diffet a későbbi összevetéshez. Az idegen munkát ne állítsd
vissza, ne töröld, ne írd felül és ne stage-eld. Átfedésnél jelentsd az érintett
fájlt és őrizd meg a felhasználó módosítását; bizonytalan összeillesztés előtt
kérj tisztázást. Ne használj `git reset --hard`, `git clean` vagy széles körű
restore műveletet tiszta munkafa előállítására. Formázást csak az engedélyezett
fájlokra alkalmazz.

## 2. Térképezd fel a teszteket és válassz ellenőrzést

Olvasd a [composer.json](../../../composer.json) és
[package.json](../../../package.json) aktuális scriptjeit. Vizsgáld meg az
érintett teszteket és konfigurációt: backendnél `tests/Unit`, `tests/Feature`,
`tests/Pest.php`, `phpunit.xml`; frontendnél `tests/frontend` és
`vitest.config.js`; böngészős folyamatnál `tests/e2e` és `playwright.config.js`.
A frontend aktuális mintája `tests/frontend/**/*.test.js`; ne feltételezz
`resources/js` alatti tesztkiválasztást.

1. Azonosítsd a megváltozott viselkedést és a kapcsolódó regressziós kockázatot.
2. Válassz szűk diagnosztikai tesztet, ha segít reprodukálni a hibát.
3. A javítás tényleges hatásához válassz Affected/Fast, Module, Integration
   vagy Full szintet a rétegezett útmutatóból. Ezek nem sorban lefuttatandó kapuk.
4. Alkalmazás- vagy eszközváltozásnál vizsgáld meg a
   `php tools/quality-gate.php affected --dry-run --explain` tervét. Vedd
   figyelembe, hogy idegen munkafaváltozást is kiválaszthat. A terv nem ad
   módosítási engedélyt és nem sikeres ellenőrzés.
5. Vesd össze a tervet a szabállyal. Tesztinfrastruktúra és tesztkonfiguráció
   változásakor Full szükséges; a dokumentált kiválasztási eltérés, például
   a `vitest.config.js` Integration besorolása nem felmentés.
6. Sorold fel a tervből hiányzó, külön alkalmazandó vizsgálatokat is.

A célzott siker nem váltja ki a szükséges szélesebb regressziót. Csak
promptot vagy dokumentációt érintő munkánál ellenőrizd a formázást, linkeket,
parancsokat, szóhasználatot és whitespace-t. Alkalmazás-QA nem automatikus;
futtatható működés vagy eszközváltozás hatását a DoD szerint értékeld.

## 3. Futtasd a kiválasztott vizsgálatokat

A következő parancsok választási lehetőségek, nem kötelező teljes futási lista.
Futtatás előtt ellenőrizd az eljárás környezeti és izolációs előfeltételeit.

| Vizsgálat             | Meglévő parancs és eljárás                                                                                                                                                                          |
| --------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Backend SQLite        | `composer test:backend:sqlite` — [backendeljárás](../../../docs/backend-quality-gate.md).                                                                                                           |
| Backend MySQL         | `composer test:backend:mysql` — csak dedikált, védett tesztadatbázison.                                                                                                                             |
| Migráció              | `composer test:backend:migrations:sqlite`, `composer test:backend:migrations:mysql`.                                                                                                                |
| Célzott backend       | `php scripts/backend-test-environment.php sqlite test tests/Feature/InventoryTest.php` — az útvonalat a tényleges tesztre cseréld; a teljes Composer-aliasok nem továbbítanak további argumentumot. |
| Frontend              | `npm run test:frontend`; célzott példája: `npm run test:frontend -- tests/frontend/components/DocumentUploadForm.test.js`.                                                                          |
| Lefedettség           | `npm run test:frontend:coverage` — magában foglalja a teljes Vitest-futást; [frontendeljárás](../../../docs/frontend-testing.md).                                                                   |
| E2E                   | `npm run test:e2e` — előkészítés, build és az összes konfigurált projekt; célzott böngészők és előfeltételek az [E2E-eljárásban](../../../docs/e2e-testing.md).                                     |
| Statikus elemzés      | `composer analyse` — [statikus elemzési eljárás](../../../docs/static-analysis.md).                                                                                                                 |
| Rétegezett regresszió | `composer qa:fast`, `composer qa:module procurement`, `composer qa:integration` vagy `composer qa:full`; a modulnevet a tényleges érintettséghez válaszd.                                           |

SQLite-siker nem MySQL-igazolás. Frontendteszt nem E2E, és egy Playwright-projekt
nem az összes böngészőprojekt. A `composer qa:full` csak admin Chromium E2E-t
futtat; nem tartalmaz MySQL-t, minden böngészős folyamatot, cspell-t vagy minden
Markdown-ellenőrzést. GitHub required checket és release-készséget sem bizonyít.
A szükséges további ellenőrzéseket külön végezd el az útmutatók szerint.

AI/Python/OCR érintettségnél az indexből olvasd a kapcsolódó szerződést, és
keresd meg a ténylegesen meglévő tesztet/futtatót. Ne találj ki Python- vagy
AI-tesztparancsot. Őrizd meg a Laravel–Python JSON-szerződést, a Laravel oldali
validációt, queue-működést, telemetriát és auditnyomot. Opcionális OCR-motor vagy
külső API nem válik minden teszt előfeltételévé; az alkalmazandó integráció
hiányzó környezetét viszont ne nevezd sikernek.

## 4. Diagnosztizálj, majd javíts a felhatalmazott körben

Gyűjtsd össze a ténylegesen lefutott vizsgálatok hibáit. Javítás előtt sorold be
az okot: alkalmazáskód, tesztkód, fixture/factory/seeder, konfiguráció,
környezet/infrastruktúra, külső függőség vagy ingadozó, nem determinisztikus
működés. Rögzíts reprodukciót, hibakódot és a szükséges naplórészletet; ne
publikálj titkot vagy valós üzleti adatot. Indítási hibánál különítsd el az
alkalmazás, a böngésző és a teszt előkészítésének hibáját.

A bizonyított ok határozza meg a javítás helyét, ne előre rögzített rétegsorrend.
Javítsd az engedélyezett tesztkódot vagy tesztinfrastruktúrát, és ellenőrizd
újra az eredményt. Alkalmazáskódot csak a kért módosítási körben érints.
Feltételezett üzleti hibánál gyűjts bizonyítékot és vesd össze az elvárt
szabállyal; üzleti logikát kizárólag akkor javíts, ha a jelenlegi felhasználói
felhatalmazás ezt kifejezetten megengedi. Egy „nyilvánvaló hiba” nem engedély.
Enélkül jelentsd a megállapítást, és állj meg az üzleti viselkedés módosítása
előtt; a független, engedélyezett munkát folytasd.

Ne bővítsd önkényesen a feladatot refaktorral, függőségfrissítéssel vagy
memória-/dokumentációs fájl módosításával. Ha ezek szükségesek, de kívül esnek
a felhatalmazáson, add meg a konkrét hiányt és következő lépést.

### A tesztek hitelessége

- Ne gyengíts értelmes elvárást és ne törölj értelmes tesztet a hiba elrejtésére.
- Ne hagyj ki csendben sikertelen alkalmazandó tesztet. Javítsd az okot a
  felhatalmazott körben, vagy jelentsd a feloldatlan eredményt.
- Ne gyárts sikert általános ignore/suppression szabállyal, PHPStan baseline-nal,
  kivétel elnyelésével vagy jogosultsági/adatbázis-védelem megkerülésével.
- Ha az üzleti szabály és a bizonyíték szerint a teszt téves, a tesztet javítsd;
  helyes üzleti működést ne változtass meg a téves teszt kedvéért.
- Hibajavítást védj indokolt regressziós teszttel a Testing Steering szerint;
  meglévő megfelelő lefedettséget ne másolj le. Tartsd determinisztikusan az
  időt, adatokat és külső szerződéseket. Ingadozásnál őrizd meg a hibás és a
  sikeres újrafutást is; az ismételt siker nem magyarázza meg az eredeti hibát.

## 5. Értékeld az eredményt és a hátralévő munkát

Alkalmazd a DoD `PASSED`, `FAILED`, `BLOCKED`, `NOT RUN` eredményeit. Talált
hiba, hibás assertion, kódhiba miatti alkalmazásindítási hiba vagy tiltott
advisory `FAILED`. Csak igazolt külső/környezeti előfeltétel-hiány `BLOCKED`,
például az alkalmazandó MySQL-vizsgálat elérhetetlen tesztszervere. El nem
indított vizsgálat `NOT RUN`; az előkészítés puszta elmaradása nem `BLOCKED`.

Fail-fast leállásnál a sikeres korábbi lépések maradhatnak `PASSED` állapotúak;
a megálló lépést diagnózis alapján minősítsd, a későbbi el nem indított kötelező
lépések `NOT RUN` eredményt kapnak. Ne állíts teljes végrehajtást vagy dry-run
alapján sikert. Javítás után ismételd a hibás és érintett vizsgálatokat, pótold
a kimaradt kötelező lépéseket. Sikeres teljes csomagot csak új indokkal ismételj.

## 6. Add át a bizonyítékot

A javítás elkészülte nem a feladat készültsége. A DoD szerint értékelj; leírt
ok vagy kivétel nem `PASSED`. Feloldatlan alkalmazandó kötelező ellenőrzés
mellett ne állíts teljes készültséget. Történeti tesztszám, lefedettség vagy
régi CI-siker nem aktuális bizonyíték. A munkaállapotot az eredménytől külön,
a [backlog-konvenciók](../../../docs/project-management/backlog-conventions.md)
szerint kezeld, ha a feladat érinti.

A végső jelentés röviden tartalmazza:

- a vizsgált és engedélyezett hatókört, fájlokat és választott ellenőrzési szintet;
- a megállapításokat, bizonyítékukat, az elvégzett javításokat és új/módosított teszteket;
- a tényleges parancsokat, dátumot/futásazonosítót, környezetet, tesztkört és eredményt;
- minden nem `PASSED` ellenőrzés nevét, okát, hatását, felelősét és következő lépését;
- a nem alkalmazandó vizsgálatok rövid indokát és a nyitott felhatalmazási kérdéseket;
- a módosított fájlokat, a megőrzött idegen változásokat és a végső `git status --short` eredményét.

Átadás előtt nézd át a saját teljes diffet, a linkeket és a `git diff --check`
eredményét. Ez a prompt nem ad commit-, push-, PR-létrehozási/frissítési,
merge-, release- vagy deploy-felhatalmazást. Ezekhez az AGENTS.md és a jelenlegi
felhasználói kérés szerinti explicit engedély kell; sikeres teszt nem helyettesíti.
