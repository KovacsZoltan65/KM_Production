# Hibajavítás

## Cél és szabályforrások

Ezt az eljárást hibás, váratlan vagy korábban helyes működés javításakor
használd. Módosítás előtt állapítsd meg az okot: a bejelentett tünet még nem
bizonyított gyökérok.

A felhatalmazást az [AGENTS.md](../../AGENTS.md) és a jelenlegi felhasználói
kérés adja; a „javítsd” lépés nem bővíti a rögzített feladatot. Az elvárt üzleti
szabályt a [Domain Constitution](../steering/domain-constitution.md), a kapcsolódó
[ADR-ek](../decisions/) és [domainismeretek](../knowledge/) alapján ellenőrizd.
A készültség forrása a [Definition of Done](../../docs/project-management/definition-of-done.md)
(DoD), az ellenőrzési szinté a [rétegezett útmutató](../../docs/development/quality-gates.md),
a teszttervezésé a [tesztelési szabályok](../steering/testing.md).

## 1. Rögzítsd a hatókört és a kiinduló állapotot

A projekt gyökerében vizsgáld meg:

```bash
git branch --show-current
git status --short
git log -1 --oneline
git diff
git diff --cached
git ls-files --others --exclude-standard
```

Nevezd meg a kért javítást, az elfogadási feltételeket, az érintett modulokat,
szerződéseket és fájlokat, a kizárt munkát és az alkalmazandó DoD-követelményeket.
Az [indexből](../index.md) olvasd a kapcsolódó steering-, ADR-, tudás-,
playbook-, checklist- és memóriafájlokat, majd a hibajelentést és a meglévő teszteket.

A meglévő módosítás és nem követett fájl önmagában nem hiba. Rögzítsd ezeket;
ne írd felül, ne töröld, ne állítsd vissza, ne stage-eld véletlenül, és ne
formázd őket pusztán jelenlétük miatt. Átfedő célfájlnál előbb jelentsd az
átfedést, és őrizd meg az idegen munkát; bizonytalan összeillesztés előtt
tisztázd a határt. Destruktív takarítás, például `git reset --hard`,
`git clean` vagy széles restore csak külön, kifejezett kérés és felhatalmazás
alapján végezhető.

## 2. Gyűjts bizonyítékot és különítsd el az okot

Írd le az elvárt és tényleges működést. Készíts lehetőleg kicsi, megbízható
reprodukciót; ha ez nem lehetséges, rögzíts ellenőrizhető naplót, bemenetet,
környezetet és a bizonytalanságot. A „nem sikerült reprodukálni” nem igazolt javítás.

Kövesd a hibát a felelős rétegig: controller, service, repository, model,
frontend, adatbázis, queue, jogosultság, fordítás vagy teszt. Vizsgáld az érintett
policyt, FormRequestet, tranzakciót, naplót és külső szerződést.
A javítás helyét a bizonyított ok határozza meg.

Különítsd el a termékhibát a téves teszttől, fixture/factory/seeder hibától,
konfigurációs, környezeti vagy függőséghibától és dokumentációs eltéréstől.
Indítási hibát vagy időtúllépést ne nevezz automatikusan környezeti akadálynak.
Őrizd meg a hibakódot és a diagnózist, titok vagy valódi üzleti adat közlése nélkül.
Teszthiba önmagában nem bizonyít hibás üzleti szabályt.

## 3. Ellenőrizd az üzleti döntést, majd javíts

Feltételezett üzleti hiba nem automatikus engedély az üzleti jelentés
módosítására. Vesd össze a javítást az ADR-ekkel, domainismerettel és a jelenlegi
felhasználói felhatalmazással. Üzleti logikát csak kifejezetten engedélyezett
körben módosíts. Ha a javítás egy elfogadott szabályt változtatna, a döntés
módosításának hatóköre is legyen engedélyezett.

Bizonytalan elvárt üzleti működésnél ne találgass. Nevezd meg az ellentmondó
forrásokat vagy hiányzó döntést, állj meg az érintett módosítás előtt, és
jelentsd a tisztázó lépést. A független, engedélyezett munkát folytasd.
Elfogadott ADR vagy dokumentált készültség nem bizonyít implementációt:
a következtetést aktuális kóddal, konfigurációval és tesztbizonyítékkal ellenőrizd.

A legkisebb, az okkal indokolt javítást végezd el. Tartsd meg a
`Controller -> Service -> Repository -> Model` rétegzést, a jogosultságokat,
validációt, szükséges tranzakciókat és közös Laravel JSON fordítási kulcsokat.
Készletet csak készletmozgással változtass; őrizd meg a nyomon követhetőséget,
sorozatszámokat, műveleti sorrend verzióit és auditnaplót.

A hibajavítás nem engedély közeli kód átírására, kapcsolódás nélküli
formázásra, átnevezésre, holtkód-törlésre vagy függőségfrissítésre.
Az önállóan hasznos, de kívül eső munkát követő feladatként jelentsd.

## 4. Védd regresszió ellen és ellenőrizz arányosan

A javítás előtt vagy azzal együtt adj hozzá vagy frissíts regressziós tesztet,
amely az elvárt viselkedést és a hiba okát fedi. Meglévő megfelelő tesztet ne
másolj le. Téves tesztet az igazolt szabály szerint javíts; helyes üzleti
működést ne írj át a teszt kedvéért. Értelmes elvárás gyengítése, teszt törlése
vagy hiba elrejtése kihagyással nem javítás.

A célzott diagnózist kövesse a javítás hatásához arányos regresszió, majd
kockázat szerinti bővítés. A rétegezett útmutató és az
[ellenőrzési lista](../checklists/quality-gates.md) szerint válassz Affected/Fast,
Module, Integration vagy Full szintet. Nem kell mind a négyet sorban futtatni.
Vesd össze a futtató tervét a szabállyal; az alulbesorolást jelentsd, a szükséges
vizsgálatot pótold. A tervezési futás nem sikeres ellenőrzés.

Alkalmazd a változáshoz kapcsolódó [szakági ellenőrzőlistákat](../checklists/) is.

A parancsokat az aktuális [composer.json](../../composer.json),
[package.json](../../package.json) és az eljárások alapján ellenőrizd.
Futtasd a kiválasztott teszteket, az alkalmazandó formázást,
[statikus elemzést](../../docs/static-analysis.md), buildet és külön vizsgálatokat:

- A [backendeljárás](../../docs/backend-quality-gate.md) szerinti SQLite-siker
  nem MySQL-igazolás. Az alkalmazandó MySQL-t dedikált, védett tesztadatbázison
  vizsgáld; igazolt külső/környezeti előfeltétel-hiánynál az eredmény `BLOCKED`.
- A [frontendteszt](../../docs/frontend-testing.md) nem [E2E](../../docs/e2e-testing.md).
  Böngészős vizsgálatot a javított felhasználói folyamat kockázata alapján válassz.
  Egy Playwright-projekt nem bizonyít minden böngészőprojektet.
- A `composer qa:full` nem minden projektellenőrzés: MySQL, minden
  Playwright-projekt és minden dokumentációs vizsgálat nincs benne.

A megszűnt tünet vagy sikeres célzott regresszió nem automatikus feladatlezárás.
Nem bizonyítja a modul, az integráció, a MySQL, az E2E vagy a merge készültségét.

## 5. Vizsgáld meg a dokumentációs hatást

Változik-e valami, amit a fejlesztőnek, agentnek vagy felhasználónak értenie kell?
A [dokumentációs lista](../checklists/documentation.md) alapján frissítsd az
érintett üzleti döntést, domainleírást, API-, konfigurációs, fejlesztési,
felhasználói, üzemeltetési vagy jogosultsági útmutatót a felhatalmazott körben.
Ha a dokumentált tartalom nem változik, nem kell öncélú frissítés.
A szükséges, de hatókörön kívüli dokumentációt hiányként jelentsd.

Csak dokumentációs eltérés javításakor formázás-, link-, parancspélda-,
terminológia- és whitespace-ellenőrzés szükséges a DoD szerint.
Alkalmazás-QA nem automatikus; alkalmazás- vagy eszközműködést érintő változásnál
a tényleges hatást értékeld. A leírás kijavítása nem javítja az alkalmazás hibáját.

## 6. Add át a bizonyítékot

Használd a DoD `PASSED`, `FAILED`, `BLOCKED`, `NOT RUN` eredményeit.
Korai leállásnál a sikeres korábbi ellenőrzések maradhatnak `PASSED` állapotúak;
a megálló lépést a diagnózis alapján minősítsd, a későbbi el nem indított
kötelező lépések `NOT RUN` eredményt kapnak. Javítás után ismételd a hibás és
érintett vizsgálatokat, és pótold a kimaradt kötelező lépéseket.

Nézd át a teljes saját diffet és az esetleges staginget. Jelentsd a tünetet,
reprodukciót, bizonyított okot, javítást, fájlokat, regressziós lefedettséget,
elfogadási feltételeket és választott ellenőrzési szintet. Add meg a tényleges
parancsot/lépést, dátumot vagy futásazonosítót, környezetet és eredményt.
Külön jelezd a megőrzött idegen munkát és a végső `git status --short` kimenetét.

Minden `FAILED`, `BLOCKED`, `NOT RUN` tételhez név, ok, hatás, felelős és
következő lépés kell; valóban nem alkalmazandó vizsgálathoz rövid `N/A` indok.
Leírt ok nem `PASSED`. Feloldatlan alkalmazandó kötelező követelmény mellett
ne állíts teljes készültséget; történeti siker nem mai igazolás.
Az érintett munkaállapotot a [backlog-konvenciók](../../docs/project-management/backlog-conventions.md)
szerint, az ellenőrzési eredménytől külön kezeld.

Felhatalmazott Git-műveletnél a [commit előtti](../checklists/before-commit.md),
[merge előtti](../checklists/before-merge.md) és
[code review](../../docs/project-management/code-review-guide.md) eljárás érvényes.
Ez a workflow és sikere nem ad commit-, push-, pull request létrehozási/frissítési,
merge-, release- vagy deploy-felhatalmazást. Ezeket csak az AGENTS.md és a
jelenlegi felhasználói kérés szerinti explicit felhatalmazással végezd.
