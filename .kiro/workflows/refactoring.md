# Refaktorálás

## Cél és szabályforrások

Ezt az eljárást a belső szerkezet javításakor használd, ha a szándékolt,
kívülről megfigyelhető működés változatlan marad. Ide tartozhat az ismétlés
csökkentése, érthetőbb elnevezés, belső kiemelés vagy átszervezés, biztonságosabb
réteghatár, pontosabb típusleírás és jobb karbantarthatóság.

A felhatalmazást az [AGENTS.md](../../AGENTS.md) és a jelenlegi felhasználói
kérés adja; a „refaktoráld” lépés nem bővíti a rögzített feladatot.
A domain- és architektúradöntések forrása a
[Domain Constitution](../steering/domain-constitution.md) és a kapcsolódó
[ADR-ek](../decisions/). A készültség forrása a
[Definition of Done](../../docs/project-management/definition-of-done.md) (DoD),
az ellenőrzési szinté a [rétegezett útmutató](../../docs/development/quality-gates.md),
a teszttervezésé a [tesztelési szabályok](../steering/testing.md).

## 1. Rögzítsd a javítandó szerkezetet és a munkafa állapotát

A projekt gyökerében vizsgáld meg:

```bash
git branch --show-current
git status --short
git log -1 --oneline
git diff
git diff --cached
git ls-files --others --exclude-standard
```

Nevezd meg a konkrét szerkezeti problémát, az érintett modulokat, fájlokat és
szerződéseket, az elfogadási feltételeket és a feladaton kívüli munkát.
Azonosítsd az alkalmazandó DoD-követelményeket és a regressziós kockázatot.

Nem kell tiszta munkafa. Rögzítsd a meglévő módosításokat és nem követett
fájlokat; ne írd felül, ne töröld, ne állítsd vissza, ne stage-eld véletlenül,
és ne formázd őket pusztán jelenlétük miatt. Átfedő célfájlnál előbb jelentsd
az átfedést, és őrizd meg a felhasználó munkáját; bizonytalan összeillesztés
előtt tisztázd a határt. Destruktív takarítás, például `git reset --hard`,
`git clean` vagy széles restore csak külön, kifejezett kérés és felhatalmazás
alapján végezhető.

## 2. Határozd meg a megőrzendő működést

Az [indexből](../index.md) olvasd a kapcsolódó útmutatókat, különösen a
[kódolási stílust](../steering/coding-style.md), az
[architektúrát](../steering/architecture.md), a domainismereteket, ADR-eket,
playbookokat és memóriát. Vizsgáld meg a tényleges kódot, hívókat, teszteket
és konfigurációt; a látszólag nem használt működésnek lehet közvetett használója.

Rögzítsd a megőrzendő bemenetet, kimenetet, API- és Inertia-szerződést,
jogosultságot és mellékhatást. A felhasználói folyamat, az üzleti jelentés,
a tárolási viselkedés és a nyilvános szerződések maradjanak változatlanok.
Őrizd meg a készletmozgásokat, gyártási nyomon követhetőséget, sorozatszámokat,
műveleti sorrend verzióit és auditnaplót.

Ha működésváltozás szükséges, a feladat már nem tiszta refaktor. Ne rejts el
új funkciót vagy hibajavítást a szerkezeti változásban. Sorold át a munkát
[fejlesztésnek](feature-development.md) vagy [hibajavításnak](bug-fix.md), illetve
rögzíts kifejezetten engedélyezett hatókörbővítést. Bizonytalan üzleti szabálynál
ne találgass; új vagy megváltozó domain-/architektúradöntést csak az arra is
kiterjedő felhatalmazással hozz. Enélkül az érintett részt állítsd meg és
jelentsd; a független, engedélyezett munkát folytasd.

## 3. Védd a kiinduló viselkedést, majd alakíts át kis lépésekben

A kockázatos átalakítás előtt erősítsd meg a meglévő lefedettséget.
Hiány esetén készíts célzott, a megfigyelhető viselkedést rögzítő tesztet.
A kiinduló és módosított állapotot összehasonlítható bizonyítékkal vizsgáld;
pusztán egy tesztfájl létezése nem bizonyítja a viselkedés megőrzését.
Teljesítményjavulást csak azonos módszerű előtte–utána méréssel állíts.

Kis, áttekinthető változáscsoportokban dolgozz. Tartsd meg a
`Controller -> Service -> Repository -> Model` felelősségeit: a controller
koordinál, a service kezeli az üzleti folyamatot, a repository a lekérdezést,
a model a kapcsolatokat és egyszerű modellműködést. Üzleti logika ne kerüljön
controllerbe; készletmennyiséget ne módosíts közvetlenül.
A validáció, tranzakciók, jogosultságok és közös Laravel JSON fordítási kulcsok
érintett szerződései maradjanak érvényesek.

Ne gyengíts értelmes tesztet és ne törölj lefedettséget a siker kedvéért.
Ne bővítsd a munkát közeli üzleti logika módosításával, kapcsolódás nélküli
formázással, átnevezéssel, holtkód-törléssel vagy függőségfrissítéssel.
A külön hasznos tisztítást követő feladatként jelentsd.

## 4. Igazold a viselkedés megőrzését

Célzott ellenőrzés után az érintett működés regresszióját vizsgáld, majd a
feltárt kockázat szerint bővíts. A rétegezett útmutatóból válassz Affected/Fast,
Module, Integration vagy Full szintet. Ezek nem kötelezően sorban futtatandó
lépcsők; a refaktor megnevezése önmagában nem ír elő Full futást.
Több modult érintő közös alkalmazásműködés Integration, projekt-, teszt-,
build- vagy ellenőrzési infrastruktúra változása Full kockázatú.
A további besorolási szabályokat a központi útmutató adja.

Alkalmazd a változáshoz kapcsolódó [szakági ellenőrzőlistákat](../checklists/) is.

Az [ellenőrzési lista](../checklists/quality-gates.md) szerint vesd össze a
futtató tervét a kockázattal. A dokumentált alulbesorolást jelentsd, és a
szabály szerinti szintet válaszd. A tervezési futás nem sikeres teszt.
A parancsokat és előfeltételeket az aktuális [composer.json](../../composer.json),
[package.json](../../package.json) és az eljárások alapján ellenőrizd.

Futtasd a célzott, illetve alkalmazandó modul- és integrációs teszteket,
[statikus elemzést](../../docs/static-analysis.md), formázást, buildet és külön
vizsgálatokat. A statikus siker önmagában nem bizonyít stabil üzleti működést.

- [Backend](../../docs/backend-quality-gate.md): SQLite-siker nem MySQL-igazolás.
  Az alkalmazandó MySQL-t dedikált, védett tesztadatbázison vizsgáld; igazolt
  külső/környezeti előfeltétel-hiánynál az eredmény `BLOCKED`.
- [Frontend](../../docs/frontend-testing.md) és [E2E](../../docs/e2e-testing.md):
  a komponensvizsgálat nem teljes böngészős folyamat. E2E-t az érintett
  felhasználói folyamat kockázata alapján válassz; egy Playwright-projekt
  nem igazolja az összes böngészőprojektet.
- A `composer qa:full` nem minden projektellenőrzés: MySQL, minden
  Playwright-projekt és minden dokumentációs vizsgálat nincs benne.
  A külön alkalmazandó ellenőrzéseket pótold.

## 5. Ellenőrizd a dokumentációs hatást

Változik-e valami, amit a későbbi fejlesztőnek, agentnek vagy felhasználónak
értenie kell? Belső átszervezésnél is változhat architektúraleírás,
fejlesztési vagy üzemeltetési eljárás és kódhivatkozás. Az API-, konfigurációs,
jogosultsági és domainleírás érintettségét is vizsgáld; ha üzleti működést
kell átírni bennük, ellenőrizd újra a feladat besorolását.

A [dokumentációs lista](../checklists/documentation.md) szerint frissíts az
engedélyezett körben. Ha a dokumentált tartalom nem változik, nem kell öncélú
módosítás; szükséges, de hatókörön kívüli frissítést hiányként jelents.
Csak dokumentációs átszervezéshez a DoD szerinti formázás-, link-,
parancspélda-, terminológia- és whitespace-ellenőrzés kell.
Alkalmazás-QA nem automatikus; alkalmazás- vagy eszközműködést érintő
változásnál a tényleges hatást értékeld.

Elfogadott ADR, README-állítás vagy dokumentált készültség nem bizonyít
megvalósítást. A működésre vonatkozó következtetést aktuális
repository-bizonyítékkal igazold. Dokumentációmódosítás nem alkalmazásjavítás.

## 6. Jelents eredményt és nyitott követelményt

A DoD `PASSED`, `FAILED`, `BLOCKED`, `NOT RUN` eredményeit használd.
Korai leállásnál a sikeres korábbi lépések maradhatnak `PASSED` eredményűek;
a megálló lépést a diagnózis alapján minősítsd, a későbbi el nem indított
kötelező lépések `NOT RUN` eredményt kapnak. Javítás után ismételd a hibás és
érintett vizsgálatokat, és pótold a kimaradt kötelező lépéseket.

Nézd át a teljes saját diffet és az esetleges staginget. Jelentsd a szerkezeti
változást, fájlokat, elfogadási feltételek teljesülését, megőrzött szerződéseket
és az összehasonlítható bizonyítékot. Add meg a választott ellenőrzési szintet,
tényleges parancsot/lépést, dátumot vagy futásazonosítót, környezetet és eredményt.
Külön jelezd a megőrzött idegen munkát és a végső `git status --short` kimenetét.

Minden `FAILED`, `BLOCKED`, `NOT RUN` tételhez név, ok, hatás, felelős és
következő lépés kell; valóban nem alkalmazandó vizsgálathoz rövid `N/A` indok.
Leírt ok nem `PASSED`. Feloldatlan alkalmazandó kötelező követelmény mellett
ne állíts teljes készültséget. Célzott teszt és történeti siker nem a teljes
feladat igazolása. Az érintett munkaállapotot külön, a
[backlog-konvenciók](../../docs/project-management/backlog-conventions.md) szerint kezeld.

Felhatalmazott Git-műveletnél a [commit előtti](../checklists/before-commit.md),
[merge előtti](../checklists/before-merge.md) és
[code review](../../docs/project-management/code-review-guide.md) eljárást használd.
Ez a workflow és sikere nem ad commit-, push-, pull request létrehozási/frissítési,
merge-, release- vagy deploy-felhatalmazást. Ezeket csak az AGENTS.md és a
jelenlegi felhasználói kérés szerinti explicit felhatalmazással végezd.
