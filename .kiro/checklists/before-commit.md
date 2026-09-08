# Commit előtti ellenőrzőlista

## Cél és hatály

Commit előtt ellenőrizd, hogy szándékosan kiválasztott, érthető és biztonságosan
rögzíthető változást készítesz elő. A commit lehet köztes munkapont; önmagában
nem jelenti a feladat befejezését. A készültséget és az ellenőrzési eredmények
jelentését a [Definition of Done](../../docs/project-management/definition-of-done.md)
határozza meg.

Az AI-agent commit- és push-jogosultságát az [AGENTS.md](../../AGENTS.md) és a
[commitüzenet-konvenció](../../docs/project-management/commit-conventions.md)
szabályozza: mindkét művelethez explicit felhasználói felhatalmazás kell.
A lista kitöltése ezt nem helyettesíti.

## A változás előkészítése

- [ ] A commit egy logikailag összetartozó változást rögzít a jóváhagyott feladatból.
- [ ] Az alkalmazandó DoD-pontokat azonosítottam; a nem alkalmazandó részek
      rövid `N/A` indoklást kaptak.
- [ ] Az érintett réteghatárokat, jogosultságokat, validációt, tranzakciókat és
      auditnaplót átnéztem. Készletmennyiség csak készletmozgással változik.
- [ ] Adatbázis-változásnál a migráció, indexek, idegen kulcsok, törlés és
      visszaállítás hatása ismert; nincs elrejtett adatvesztési kockázat.
- [ ] Frontendváltozásnál a közös komponensek, PrimeVue, fordítások, betöltési,
      üres és hibás állapotok ellenőrzöttek. A lapcím Inertia `<Head>` és a központi
      címformázó segítségével készül; nincs második head-kezelő.
- [ ] Az érintett dokumentáció, ADR, tudásanyag és eljárás frissült, vagy a
      hiányzó rész és következő lépése látható.

## Ellenőrzések és nyitott követelmények

- [ ] A [rétegezett útmutató](../../docs/development/quality-gates.md) és az
      [ellenőrzési lista](quality-gates.md) alapján kiválasztottam és elvégeztem a
      változáshoz szükséges vizsgálatokat; a hiányzó futások tételesen szerepelnek.
- [ ] Csak dokumentációt érintő munkánál a DoD szerinti formázást,
      hivatkozásokat, szóhasználatot és whitespace-hibákat vizsgáltam; nem írtam elő
      automatikusan alkalmazástesztet.
- [ ] A szükséges tesztek a sikeres, hibás és jogosulatlan eseteket is vizsgálják.
      Gyorsítótárazott adatforrás írásának változásánál a cache-mátrixot és a
      `composer test:cache` eredményét ellenőriztem.
- [ ] Az alkalmazandó Pint-, Larastan- és Composer-validálás eredménye rögzített.
      Hiba nem lett PHPStan baseline-nal vagy általános elnémítással elfedve.
- [ ] Az alkalmazandó `npm audit` és `npm audit --omit=dev` eredménye rögzített;
      a meglévő nulla sérülékenységi követelmény teljesülése vagy hiánya látható.
- [ ] A szükséges SQLite-, MySQL- és migrációs eredmények külön szerepelnek a
      [backend-eljárás](../../docs/backend-quality-gate.md) szerint. SQLite nem
      helyettesíti az alkalmazandó MySQL-ellenőrzést.
- [ ] Minden alkalmazandó ellenőrzést a DoD szerinti `PASSED`, `FAILED`,
      `BLOCKED` vagy `NOT RUN` eredménnyel jelentettem. Sikertelen futás, igazolt
      környezeti akadály és elmaradt futás nem szerepel sikeres ellenőrzésként.
- [ ] Minden nem sikeres ellenőrzéshez név, eredmény, parancs vagy eljárás, ok,
      hatás, felelős és következő lépés tartozik. A kihagyás indoka nem teljesítés.
- [ ] Köztes commitnál a nyitott követelmények a commit leírásából vagy a
      hivatkozott feladatból visszakereshetők. A feladat állapota és az átadás nem
      állít DoD szerinti készültséget, amíg alkalmazandó követelmény rendezetlen.

## Staging és commitüzenet

- [ ] Csak a szándékolt fájlokat stage-eltem; idegen vagy korábban meglévő
      módosítást nem vettem bele.
- [ ] A stage-elt fájlneveket, statisztikát és teljes diffet külön átnéztem.
- [ ] Nincs titok, helyi generált fájl, hibakereső kód, kikommentelt holt kód,
      `TODO` vagy `FIXME` a rögzítendő változásban.
- [ ] A stage-elt whitespace-ellenőrzés sikeres.
- [ ] A commitüzenet követi a konvenciót, a tényleges változást írja le, és
      csak valóban igazolt ellenőrzési eredményt állít.
- [ ] AI-agentként rendelkezem az adott commitra vonatkozó felhatalmazással.

A staging átnézéséhez használd a commitkonvenció parancsait:

```bash
git status --short
git diff --cached --name-status
git diff --cached --stat
git diff --cached --check
git diff --cached
```

Az előkészített commit és a teljes feladat állapotát külön jelentsd. Egy
`FAILED`, `BLOCKED` vagy `NOT RUN` ellenőrzés köztes commit után is nyitott
marad; rendezését a DoD szerint kell igazolni.
