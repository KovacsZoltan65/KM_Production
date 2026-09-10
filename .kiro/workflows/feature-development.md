# Új funkció fejlesztése

## Cél és szabályforrások

Ezt az eljárást új, szándékolt alkalmazásműködés bevezetésekor használd, akár
felhasználói felületet, akár belső folyamatot érint. Először az üzleti célt és
az elvárt eredményt tisztázd, majd ezekhez tervezd a megvalósítást.

A felhatalmazást az [AGENTS.md](../../AGENTS.md) és a jelenlegi felhasználói
kérés adja; az eljárás lépései nem bővítik a rögzített feladatot. A domain- és
architektúradöntések forrása a [Domain Constitution](../steering/domain-constitution.md)
és a kapcsolódó [ADR-ek](../decisions/). A készültséget a
[Definition of Done](../../docs/project-management/definition-of-done.md) (DoD),
az ellenőrzési szintet a [rétegezett útmutató](../../docs/development/quality-gates.md),
a teszttervezést a [tesztelési szabályok](../steering/testing.md) határozzák meg.

## 1. Rögzítsd a célt és védd a meglévő munkát

A projekt gyökerében vizsgáld meg:

```bash
git branch --show-current
git status --short
git log -1 --oneline
git diff
git diff --cached
git ls-files --others --exclude-standard
```

Rögzítsd az üzleti eredményt, a megfigyelhető elfogadási feltételeket, az
érintett modulokat és fájlokat, valamint azt, mi nem része a feladatnak.
Azonosítsd az alkalmazandó DoD-követelményeket és a várható kockázatot.

Nem kell tiszta munkafa. A meglévő módosításokat és nem követett fájlokat
rögzítsd; ne írd felül, ne töröld, ne állítsd vissza, ne stage-eld véletlenül,
és ne formázd őket pusztán azért, mert jelen vannak. Átfedő célfájlnál előbb
jelentsd az átfedést, és őrizd meg a felhasználó munkáját. Bizonytalan
összeillesztés előtt tisztázd a határt. Destruktív takarítást, például
`git reset --hard`, `git clean` vagy széles restore műveletet csak külön,
kifejezett kérés és felhatalmazás alapján végezz.

## 2. Értsd meg a meglévő működést és a döntési határt

Az [indexből](../index.md) olvasd a kapcsolódó steering-, ADR-, tudás-,
eljárás- és memóriafájlokat. Vizsgáld meg a tényleges kódot, teszteket és
konfigurációt. Az elfogadott ADR, README-állítás vagy késznek jelölt dokumentum
nem bizonyít megvalósítást; a következtetéshez aktuális repository-bizonyíték kell.

Térképezd fel az érintett rétegeket, API- és Inertia-szerződéseket,
jogosultságokat, adatokat és külső kapcsolatokat. Őrizd meg a gyártási
nyomon követhetőséget, sorozatszámokat, műveleti sorrend verzióit és auditnaplót.

Új funkcióhoz nem mindig kell új ADR. Ha azonban a megoldás új architekturális
vagy üzleti szabályt igényel, előbb azonosítsd a döntési hiányt. Ne találj ki
életciklusállapotot, üzleti alapfeltételt, felelősségi határt, tárolási
szemantikát, jóváhagyási szabályt vagy beszerzési/gyártási jelentést.
A döntést csak akkor hozd létre vagy módosítsd, ha a jelenlegi feladat ezt is
engedélyezi. Máskülönben állj meg az érintett résznél, jelentsd a tisztázandó
kérdést és a következő lépést; a független, engedélyezett munkát folytasd.

## 3. Valósítsd meg a jóváhagyott működést

A terv nevezze meg a módosítandó szerződéseket, mellékhatásokat és indokolt
esetben a migrációs, kompatibilitási és visszaállítási következményeket.
Használd a kapcsolódó [playbookot](../playbooks/).

A változás szerint alkalmazd az [új modul](../checklists/new-module.md),
[biztonság](../checklists/security.md), [teljesítmény](../checklists/performance.md)
és [AI-funkció](../checklists/ai-feature.md) ellenőrzőlistáját. AI-érintettségnél
őrizd meg az ADR-ek szerinti Laravel–Python elválasztást.

Kövesd a `Controller -> Service -> Repository -> Model` rétegzést.
Üzleti logika ne kerüljön controllerbe; készletmennyiség csak készletmozgással
változzon. Az érintett működéshez biztosíts policy- és jogosultság-ellenőrzést,
FormRequest validációt, szükséges tranzakciót és tevékenységnaplót.
A felhasználói szöveg közös Laravel JSON fordítási kulcsot használjon.

Csak a feladatban engedélyezett üzleti logikát változtasd. Ne végezz kapcsolódás
nélküli formázást, átnevezést, holtkód-törlést, függőségfrissítést vagy közeli
kódot érintő refaktort. A külön hasznos javítást követő feladatként jelentsd.

## 4. Készíts teszteket és válassz arányos ellenőrzést

A tesztelési szabályok szerint adj hozzá vagy frissíts megfelelő tesztet a
sikeres, fontos hibás és jogosulatlan esetekre, valamint a kritikus
mellékhatásokra. Meglévő megfelelő lefedettséget ne másolj le, és ne gyengíts
értelmes elvárást a siker kedvéért.

Célzott működésellenőrzés után a kapcsolódó regressziót vizsgáld, majd a feltárt
kockázat szerint bővíts. A rétegezett útmutatóból válassz Affected/Fast, Module,
Integration vagy Full szintet; ezek nem kötelezően sorban futtatandó lépcsők.
Az [ellenőrzési lista](../checklists/quality-gates.md) szerint vesd össze a
futtató tervét a szabállyal, és jelentsd az esetleges alulbesorolást.
A tervezési futás nem tesztbizonyíték.

A pontos parancsokat és előfeltételeket az aktuális
[composer.json](../../composer.json), [package.json](../../package.json) és az
eljárások alapján ellenőrizd. Futtasd a kiválasztott és külön szükséges vizsgálatokat:

- [Backend](../../docs/backend-quality-gate.md): SQLite-siker nem MySQL-igazolás.
  Az alkalmazandó MySQL-vizsgálatot dedikált, védett tesztadatbázison végezd.
  Igazolt külső/környezeti előfeltétel-hiánynál az eredmény `BLOCKED`.
- [Frontend](../../docs/frontend-testing.md) és [E2E](../../docs/e2e-testing.md):
  komponensvizsgálat nem teljes böngészős folyamat. E2E-t az érintett
  felhasználói folyamat kockázata indokoljon; egy Playwright-projekt nem
  bizonyítja az összes böngészőprojektet.
- [Statikus elemzés](../../docs/static-analysis.md), formázás, build és további
  ellenőrzések: a változásra alkalmazandó körben szükségesek.

A `composer qa:full` nem minden projektellenőrzés: nem tartalmaz MySQL-t,
minden Playwright-projektet vagy minden dokumentációs vizsgálatot. A hiányzó,
alkalmazandó ellenőrzéseket külön végezd el.

## 5. Ellenőrizd a dokumentációs hatást

Változik-e valami, amit a későbbi fejlesztőnek, agentnek vagy felhasználónak
értenie kell? Ha igen, a [dokumentációs lista](../checklists/documentation.md)
alapján frissítsd az érintett leírást a felhatalmazott körben. Ilyen lehet
döntés, domainismeret, API-viselkedés, konfiguráció, fejlesztési eljárás,
felhasználói vagy üzemeltetési működés, jogosultság és munkafolyamat.
Ha a dokumentált tartalom nem változik, nem kell öncélú dokumentációmódosítás.
A szükséges, de hatókörön kívüli frissítést hiányként jelentsd.

Csak dokumentációs változásnál a DoD szerinti formázás-, link-, parancspélda-,
terminológia- és whitespace-ellenőrzés kell; alkalmazás-QA nem automatikus.
Alkalmazás- vagy eszközműködést érintő változásnál annak tényleges hatását értékeld.
A dokumentáció frissítése önmagában nem javítja és nem igazolja az alkalmazást.

## 6. Jelents bizonyítékot és értékeld a készültséget

A DoD `PASSED`, `FAILED`, `BLOCKED`, `NOT RUN` eredményeit használd.
Korai leállásnál a sikeres korábbi lépések maradhatnak `PASSED` eredményűek;
a megálló lépést a diagnózis alapján minősítsd, a későbbi el nem indított
kötelező lépések `NOT RUN` eredményt kapnak. Javítás után ismételd a hibás és
érintett vizsgálatokat, és pótold a kimaradt kötelező lépéseket.

Nézd át a teljes saját diffet és az esetleges staginget. A jelentés tartalmazza
az eredményt, fájlokat, elfogadási feltételek teljesülését, választott
ellenőrzési szintet, tényleges parancsokat/lépéseket, dátumot vagy futásazonosítót,
környezetet és megfigyelt eredményt. A megőrzött idegen munkát és a végső
`git status --short` kimenetét külön add meg.

Minden `FAILED`, `BLOCKED`, `NOT RUN` tételhez név, ok, hatás, felelős és
következő lépés kell. A nem alkalmazandó vizsgálathoz rövid `N/A` indok tartozik.
Leírt ok vagy kivétel nem `PASSED`; feloldatlan alkalmazandó kötelező
követelmény mellett ne állíts teljes készültséget. Célzott siker és történeti
audit nem helyettesíti a DoD teljesítését. Az érintett munkaállapotot külön,
a [backlog-konvenciók](../../docs/project-management/backlog-conventions.md) szerint kezeld.

Felhatalmazott Git-műveletnél a [commit előtti](../checklists/before-commit.md),
[merge előtti](../checklists/before-merge.md) és
[code review](../../docs/project-management/code-review-guide.md) eljárást használd.
Ez a workflow és sikeres befejezése nem ad commit-, push-, pull request
létrehozási/frissítési, merge-, release- vagy deploy-felhatalmazást.
Ezeket csak az AGENTS.md és a jelenlegi felhasználói kérés szerinti explicit
felhatalmazással végezd.
