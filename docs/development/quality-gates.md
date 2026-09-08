# Rétegezett ellenőrzések – quality gate-ek

## Cél és felelősség

A változás lehetséges hatása alapján válassz ellenőrzést. Egy elkülönült
módosításhoz célzott vizsgálat kell; több modult vagy a projekt működési alapjait
érintő változáshoz szélesebb ellenőrzés szükséges.

Ez az útmutató az ellenőrzési szint kiválasztásának és a helyi futtató
használatának elsődleges leírása. A készültség, az alkalmazhatóság, az
ellenőrzési eredmények és a hiányzó igazolások szabályait a
[Definition of Done](../project-management/definition-of-done.md) határozza meg.
A gyakorlati lépésekhez használd az
[ellenőrzőlistát](../../.kiro/checklists/quality-gates.md).

## Szabály: melyik szint szükséges?

A legszűkebb olyan ellenőrzést válaszd, amely lefedi a változás kockázatát.
Az ellenőrzési szintet a műszaki hatás indokolja. A merge vagy kiadás ténye
önmagában nem teszi kötelezővé a `composer qa:full` parancsot.

| Szint         | Mikor használd?                                                                                                                                                                                                                   |
| ------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Affected/Fast | Fejlesztés közben, elkülönült változás célzott ellenőrzésére. A futtató a fájlok alapján magasabb szintre is válthat.                                                                                                             |
| Module        | Egy üzleti modul változásainak lezárásakor, a modul és kapcsolódó moduljai regressziójához.                                                                                                                                       |
| Integration   | Ha az alkalmazás működésének változása több modult vagy azok együttműködését érintheti.                                                                                                                                           |
| Full          | Projekt-, teszt-, build- vagy ellenőrzési infrastruktúra, függőségkezelés, globális keretrendszer-beállítás, illetve a konfigurációban Full kockázatúnak jelölt rész változásakor. Kifejezett felhasználói kérésre is futtatható. |

### Integration és Full megkülönböztetése

Közös alkalmazásszolgáltatás, domainkód, middleware, repository vagy Vue-komponens
változása Integration szintet igényel, ha több modul viselkedését érintheti.
A „közös” fájlmegjelölés önmagában nem jelent Full kockázatot.

A tesztelés, build, függőségkezelés, ellenőrző eszköz vagy globális
keretrendszer-konfiguráció változása Full kockázatú. Minden tesztinfrastruktúra-
és tesztkonfiguráció-változás ide tartozik, így a `vitest.config.js` is.
A jelenlegi konfiguráció az adatbázis-migrációkat is Full szintre sorolja.

Nagy refaktornál vagy célzott tesztben feltárt, több modult érintő hibánál a
feltárt hatás szerint bővíts: közös alkalmazásműködésnél Integration,
projekt- vagy ellenőrzési infrastruktúránál Full szükséges.

### Csak dokumentációt érintő változás

Ellenőrizd a módosított dokumentumok formázását, linkjeit, parancspéldáit,
szóhasználatát és whitespace-hibáit. Alkalmazásteszt nem automatikus követelmény,
merge előtt sem. Akkor válik szükségessé, ha alkalmazáskód vagy konfiguráció is
változik, vagy más alkalmazandó szabály indokolja a DoD szerint.

A Markdown-fájlok változására a jelenlegi Fast terv Prettier- és
whitespace-ellenőrzést állít össze. Nincs dedikált linkellenőrző script:
a módosított hivatkozásokat külön vizsgáld meg. Példa a dokumentációs
ellenőrzések közvetlen futtatására, a tényleges fájlnevekkel:

```bash
npx prettier --check <files>
git diff --check
```

## Megvalósítás: hol találhatók a pontos szabályok?

- A [konfiguráció](../../config/quality-gates.php) tartalmazza a modulokat,
  fájlmintákat, kapcsolódó teszteket és időkorlátokat.
- A [belépési pont](../../tools/quality-gate.php) értelmezi a parancsot és kapcsolóit.
- Az [AffectedSelector](../../app/Support/QualityGate/AffectedSelector.php)
  sorolja be a megváltozott fájlokat.
- A [GatePlanner](../../app/Support/QualityGate/GatePlanner.php) állítja össze a
  parancsokat; a [GateRunner](../../app/Support/QualityGate/GateRunner.php)
  sorban végrehajtja őket.
- A [composer.json](../../composer.json) és [package.json](../../package.json)
  adja a hivatkozott scripteket.

A konfiguráció a tényleges automatikus választást írja le. Ha alacsonyabb
szintet választ az elfogadott szabálynál, az eltérést jelenteni kell, és a
szabály szerinti ellenőrzést kell választani. A hiányos besorolás nem ad
felmentést. Az eszköz javítása külön feladat lehet.

### Ismert szabály–megvalósítás eltérések

**GOVERNANCE / IMPLEMENTATION MISMATCH:** a jelenlegi `full_risk_patterns`
nem fedi le a `vitest.config.js` fájlt. Az elfogadott szabály szerint Full
kockázatú, de a kiválasztó jelenleg az ismeretlen fájlokra használt Integration
szintre vált. A javításig ilyen változásnál kifejezetten Full ellenőrzést válassz.

Ugyanez a hiány érinti például a `.github/workflows/backend-quality.yml`,
`.github/workflows/frontend.yml` és `config/app.php` fájlokat: a jelenlegi
minták ezekhez sem rendelnek Full szintet, így Integration a visszaesési
választás. A CI-infrastruktúra és a globális keretrendszer-beállítás változására
az elfogadott Full szabály vonatkozik. A fájlminták és kiválasztási tesztek
korrekcióját külön eszközfeladatban kell elvégezni.

A futtató nem állít elő teljes, négyállapotú jelentést: `PASS`, hibakód és
`TIMEOUT` kimenetet használ, a kihagyott későbbi lépéseket nem címkézi külön.
A DoD szerinti `PASSED`, `FAILED`, `BLOCKED`, `NOT RUN` jelentést ezért a
futás tényeiből kell összeállítani. Ez dokumentált megvalósítási korlát,
nem automatikusan végrehajtott állapotosztályozás.

### Mit tartalmaznak a jelenlegi tervek?

Az alábbiak a jelenlegi parancstervek, nem egy konkrét futás eredményei.
Minden terv végén `git diff --check` szerepel; korábbi hibánál ez sem fut le.

| Terv        | Jelenlegi tartalom                                                                                                                                                                                                                                                    |
| ----------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Fast        | Módosított PHP-fájlok Pint-ellenőrzése; módosított `app/` PHP-fájlok PHPStan-elemzése; közvetlenül érintett és modulhoz rendelt backend/frontend tesztek; szükséges i18n; módosított Markdown Prettier-ellenőrzése; kiválasztott Chromium E2E és az ahhoz kért build. |
| Module      | A modul és kapcsolataihoz rendelt SQLite- és frontendtesztek; teljes Pint/PHPStan; i18n; konfiguráció szerint build és modulhoz rendelt Chromium E2E.                                                                                                                 |
| Integration | A kiválasztott és kapcsolódó modulok backendtesztjei, kiegészítve a közös regressziós listával; teljes frontendteszt; Pint/PHPStan; i18n; build; admin Chromium E2E. Modul nélküli, így közvetlen indításnál minden modulból választ.                                 |
| Full        | Composer-validálás és audit; Pint/PHPStan; teljes SQLite-tesztcsomag és SQLite-migráció; frontendtesztek lefedettségi méréssel; i18n; a `format:check` által felsorolt fájlok Prettier-ellenőrzése; mindkét npm audit; build; admin Chromium E2E.                     |

A Fast, Module és Integration backendtesztjeit a
`scripts/backend-test-environment.php sqlite test` indítja a kiválasztott
útvonalakkal. A Full a `composer test:backend:sqlite` parancsot használja.
A Unit és Feature tesztek ebben egyszer futnak le; nem kell mindkettőt még
külön teljes csomagként megismételni. A frontend lefedettségi futás szintén
magában foglalja a teljes Vitest-futást.

A `related_modules` kapcsolatok Module és Integration tervben tranzitívan
bővülnek: a kapcsolódó modul további kapcsolatai is bekerülnek, körkörös
hivatkozás esetén is végesen. A Fast terv nem végez ilyen bővítést.
Az összegyűjtött azonos tesztútvonalak és azonos parancsazonosítók ismétléseit
a tervező kiszűri.

### A qa:full korlátai

A `composer qa:full` a teljes **helyi rétegezett** parancs, nem minden létező
ellenőrzés összessége. Sikeres futása önmagában nem bizonyítja:

- a MySQL-teszteket vagy MySQL-migrációkat;
- az összes Playwright-tesztet és böngészőprojektet: csak a `tests/e2e/admin`
  Chromium-vizsgálata szerepel benne;
- a cspell-ellenőrzést;
- minden dokumentum formázását, hivatkozásait és szóhasználatát: a
  `npm run format:check` nem tartalmaz Markdown-fájlokat;
- a felülvizsgálat, a GitHub required checkek vagy a kiadás üzemeltetési
  követelményeinek teljesülését.

A Module és Integration sem ad hozzá külön Markdown-formázást. Vegyes kód-
és dokumentációs változásnál ezt külön kell elvégezni, ha a terv nem tartalmazza.
A cspell telepített csomag, de nem része a runnernek vagy a CI-nek; a jelenlegi
`cspell.json` a Markdown-ellenőrzést is kikapcsolja. Az alkalmazandó
szóhasználati és helyesírási vizsgálatot ezért nem lehet a Full eredményéből levezetni.

SQLite-siker nem helyettesíti a szükséges MySQL-sikert. A külön parancsok:

```bash
composer test:backend:mysql
composer test:backend:migrations:mysql
```

Ezeket csak a [backend-útmutató](../backend-quality-gate.md) szerinti dedikált,
védett tesztadatbázison futtasd. Hiányzó MySQL-infrastruktúránál az alkalmazandó
ellenőrzés `BLOCKED`, és a feladat nem teljesen kész. A további böngészős
ellenőrzéseket az [E2E-útmutató](../e2e-testing.md) szerint kell előkészíteni.

## Eljárás: parancs kiválasztása és indítása

Alkalmazásfejlesztésnél a futtató tervéből indulj ki. Vizsgáld meg, hogy a
besorolás megfelel-e a fenti kockázati szabálynak, és tartalmazza-e a szükséges
ellenőrzéseket. Az összesített szintek alternatívák: nem kell sorban mindet
lefuttatni. A külön szükséges vizsgálatok továbbra is hozzáadandók.

Közvetlen parancsok:

```bash
php tools/quality-gate.php affected
php tools/quality-gate.php affected --base=HEAD~1
php tools/quality-gate.php module procurement
php tools/quality-gate.php integration
php tools/quality-gate.php full
```

Ugyanezek Composerrel:

```bash
composer qa:fast
composer qa:affected -- --base=HEAD~1
composer qa:module procurement
composer qa:integration
composer qa:full
```

A `qa:fast` és `qa:affected` egyaránt az `affected` módot indítja. A név nem
kényszerít Fast tervet: a fájlok alapján Integration vagy Full is kiválasztható.
A közvetlen `module` parancs a megadott modult használja; nem sorolja be újra
a teljes változást. Ne használd magasabb kockázatú változás ellenőrzésének kiváltására.

### Változott fájlok és tervezési futás

Alapértelmezésben a kiválasztó a nem stage-elt, a stage-elt és a `HEAD`-hez
képest megváltozott fájlokat, valamint az új, még nem követett fájlokat egyesíti.
`--base=<commit>` esetén a megadott alap és `HEAD` közötti hárompontos diffet,
a munkafa eltéréseit, a staginget és az új fájlokat veszi figyelembe.
Már commitolt változás ellenőrzéséhez megfelelő alapot adj meg; a tiszta
munkafa önmagában nem a teljes ág diffje.

```bash
php tools/quality-gate.php affected --dry-run --explain
php tools/quality-gate.php module procurement --dry-run
php tools/quality-gate.php integration --dry-run
php tools/quality-gate.php full --dry-run
```

A `--dry-run` csak a tervet mutatja meg, nem futtat tesztet vagy minőségi
ellenőrzést. Az affected mód ehhez Git-parancsokat használ. Az `--explain`
fájlonként kiírja a kiválasztási okot; a közvetlen szinteknél nincs ilyen
fájlbesorolás. A tervezési parancs sikeres kilépése nem `PASSED` teszteredmény.

A fájlminták nem értelmezik a módosítás üzleti vagy nyelvi jelentését.
Ismeretlen fájlnál Integration a jelenlegi választás, de a fenti Full szabály
elsőbbséget élvez. Egy PHP-fájl kizárólag PHPDoc-ot érintő szerkesztését sem
ismeri fel külön a futtató; útvonal alapján alkalmazástesztet vagy E2E-t is választhat.

### Modulok és példák

A pontos modulneveket ezzel listázhatod:

```bash
php tools/quality-gate.php modules
```

A konfigurált nevek: `admin`, `authentication`, `bom`, `capacity`,
`code-generation`, `customer-orders`, `documents`, `inventory`,
`manufacturing-intelligence`, `master-data`, `mrp`, `procurement`, `production`,
`production-planning`, `quality`, `reports`. A pontos tesztútvonalak elsődleges
forrása a konfiguráció.

Az `mrp` a Material Requirement, Item Supplier, Supply Proposal, netting,
pegging és Purchase Requisition előkészítési, készültségi, illetve Purchase
Order-generálási területek tesztjeit fogja össze. Module szinten az `inventory`,
`procurement` és `production-planning` kapcsolatai is bekerülnek.

| Változás                                  | Választási példa                                                                                                                                             |
| ----------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Csak Markdown-dokumentáció                | Formázás, hivatkozások, terminológia, whitespace; nincs automatikus alkalmazásteszt.                                                                         |
| `GoodsReceiptService.php`                 | A jelenlegi szabály procurement és inventory teszteket, valamint célzott folyamat-E2E-t választ.                                                             |
| BOM Vue-oldal                             | A jelenlegi szabály bom és production modulokat, frontendteszteket és i18n-ellenőrzést választ; a build szükségességét és a terv tartalmát külön ellenőrizd. |
| `lang/*.json`                             | Fast és i18n.                                                                                                                                                |
| Közös `AdminCrudPage.vue`                 | Integration.                                                                                                                                                 |
| Adatbázis-migráció vagy függőségfrissítés | Full; az alkalmazandó MySQL-ellenőrzés külön marad.                                                                                                          |
| `vitest.config.js`                        | A szabály szerint Full; az automatikus besorolás ismert eltérését külön jelenteni kell.                                                                      |

### Időkorlát és folyamatok leállítása

A `qa:*` scriptek kikapcsolják a Composer általános 300 másodperces
időkorlátját. A parancsokat továbbra is a futtató felügyeli. A konfigurált
időkorlátok másodpercben:

| Csoport    | Alapérték | Környezeti változó                |
| ---------- | --------- | --------------------------------- |
| Általános  | 120       | `QUALITY_GATE_TIMEOUT_DEFAULT`    |
| Backend    | 600       | `QUALITY_GATE_TIMEOUT_BACKEND`    |
| Frontend   | 240       | `QUALITY_GATE_TIMEOUT_FRONTEND`   |
| Playwright | 900       | `QUALITY_GATE_TIMEOUT_PLAYWRIGHT` |
| Build      | 180       | `QUALITY_GATE_TIMEOUT_BUILD`      |
| PHPStan    | 240       | `QUALITY_GATE_TIMEOUT_PHPSTAN`    |

A környezeti változó az adott csoport alapértékét írja felül. A pozitív,
másodpercben megadott `--timeout` minden parancsra elsőbbséget élvez:

```bash
php tools/quality-gate.php module procurement --timeout=900
```

Időkorlátot csak mért és megindokolt esetben emelj. Időtúllépéskor a futtató
kiírja a parancsot, 124-es hibakódot ad, és leállítja a saját folyamatfáját.
A kód önmagában nem bizonyít külső akadályt; az okot ki kell vizsgálni.

Windowson a `tools/quality-gate-process.ps1` PowerShell 7-tel és .NET-tel
felügyeli a folyamatfát. POSIX alatt a közvetlen gyermek és felderített
leszármazottai kapnak leállítási jelzést; ez a lezárás a felderítés korlátai
között működik. A futtató nem állít le idegen fejlesztői szervert.

Az Integration és Full teljes frontend-, illetve lefedettségi lépése
`--maxWorkers=1` beállítással fut. A célzott Fast és Module frontend-futás a
konfigurált két workert használja. A Playwright előtt a futtató előkészíti az
izolált E2E-adatbázist. Ahol a terv nem épít új asseteket, azoknak az
[E2E-eljárás](../e2e-testing.md) szerint rendelkezésre kell állniuk.

## Eredmény: mi történt a futásban?

A futtató sorban indítja a parancsokat, és az első sikertelen lépésnél leáll.
Ez a fail-fast működés. A jelentést a DoD szerinti eredményekből állítsd össze:

- ténylegesen sikeres korábbi ellenőrzés: `PASSED`;
- lefutott és sikertelen ellenőrzés: `FAILED`;
- igazolt külső vagy környezeti előfeltétel miatt érdemben nem végezhető
  ellenőrzés: `BLOCKED`, a technikai hibakód és ok megőrzésével;
- végre nem hajtott későbbi ellenőrzés: `NOT RUN`.

Például ha a `composer audit` tiltott sérülékenységet talál, az audit `FAILED`.
A korábban sikeres Composer-validálás `PASSED`, a még el nem indított Pint,
PHPStan, tesztek és további lépések `NOT RUN` eredményt kapnak. A hiba
javításának nehézsége nem teszi az auditot `BLOCKED`-dé.

Minden `FAILED`, `BLOCKED` és `NOT RUN` eredményhez név, eredmény, ok, hatás,
felelős és következő lépés tartozik. Az összes alkalmazandó kötelező követelmény
rendezéséig a feladat nem teljesen kész. A kivétel leírása önmagában nem
elfogadás; a merge és a kiadás feltételeit a DoD határozza meg.

### Javítás utáni ellenőrzés

Őrizd meg a sikeres lépések bizonyítékát. A hibajavítás után a hibás és a
javítás által érintett ellenőrzéseket ismételd meg, és pótold a kimaradt
kötelező lépéseket. A teljes sikeres tesztcsomagokat ne futtasd újra indok nélkül.

A runnernek jelenleg nincs folytatási vagy „csak a hibás lépés” kapcsolója.
Az összesített parancs újraindítása elölről végrehajtja a tervet. Ha a tervben
szereplő parancsokat külön futtatod, tartsd meg az előfeltételeiket és
paramétereiket, és mindegyik eredményét külön jelentsd. Ezekből nem állítható,
hogy az összesített parancs maga sikeresen végigfutott.

Ismeretlen modulnál a `modules` paranccsal ellenőrizd a nevet; a futtató nem
vált automatikusan Full szintre. Téves besorolásnál rögzítsd az eltérést.
A konfiguráció és a hozzá tartozó unit tesztek javítása csak az arra kiterjedő
feladat részeként történjen; a szükséges ellenőrzést addig is végezd el.

## CI és korábbi bizonyíték

A jelenlegi [backend workflow](../../.github/workflows/backend-quality.yml)
négy külön jobban végzi a statikus elemzést, SQLite-tesztelést, MySQL-tesztelést
és MySQL-migrációt. A [frontend workflow](../../.github/workflows/frontend.yml)
hat jobja a frontendteszt, i18n, build, npm audit, Chromium E2E és további
böngészős/mobil vizsgálat. Ezek közvetlen parancsokat használnak, nem a rétegezett
runner CI-be kapcsolását bizonyítják.

A workflow-k jelenleg pull requestre és `main` pushra indulnak, dokumentációs
útvonalszűrés nélkül. Emiatt dokumentációs változásra is indulhat alkalmazás-QA.
Ez a jelenlegi automatizmus; nem változtatja meg a DoD arányossági szabályát,
és nem ad felhatalmazást required check megkerülésére. A tényleges GitHub
branch-protection beállítás repository-fájlokból nem igazolható.

Ha később a CI a rétegezett futtatóra vált, előbb a required checkeket kell
felmérni. Az affected besoroláshoz letöltött alapág és megfelelő Git-előzmény
kell, például `fetch-depth: 0` után:

```text
php tools/quality-gate.php affected --base=origin/${{ github.base_ref }}
```

A CI-szintet ekkor is a kockázat alapján kell választani; a `main` push vagy
release nem automatikus Full indok. A külön MySQL- és további böngészős
követelmények megmaradnak. Meglévő required checket csak felhatalmazott
beállításmódosítással és az új ellenőrzés igazolása után lehet kiváltani.

A korábbi időkorlát- és worker-döntések bizonyítéka a
[0015.5 stabilizálási auditban](../audits/project-stabilization-0015-5-2026-08-26.md)
és a [worker-stabilitási auditban](../audits/frontend-worker-stability-2026-07-28.md)
található. Ezek adott környezetben végzett mérések. Nem jelentenek új futást,
és nem helyettesítik az aktuális változásra alkalmazandó ellenőrzéseket.

## A mátrix karbantartása

Új modul vagy átnevezett teszt esetén a konfigurációt és a kiválasztó érintett
unit tesztjét együtt frissítsd. Több Feature teszt gyökérszinten található,
ezért a mátrix néhol konkrét fájlnevekre hivatkozik. Az útmutató a működés
magyarázata; a pontos tesztlista a konfigurációban marad.

Az ellenőrző eszköz változásánál annak saját unit tesztjeit, hibakódját,
időtúllépését és az érintett folyamatkezelést is igazolni kell. Ezek nem
általános követelmények minden alkalmazás- vagy dokumentációs módosításhoz.
