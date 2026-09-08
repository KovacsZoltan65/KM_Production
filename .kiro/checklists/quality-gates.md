# Rétegezett ellenőrzések listája

## Az ellenőrzés kiválasztása

- [ ] A [Definition of Done](../../docs/project-management/definition-of-done.md)
      alapján azonosítottam a változásra alkalmazandó követelményeket.
- [ ] A [rétegezett ellenőrzések útmutatója](../../docs/development/quality-gates.md)
      alapján választottam szintet: Affected/Fast, Module, Integration vagy Full.
- [ ] Csak dokumentációt érintő munkánál a formázást, hivatkozásokat,
      szóhasználatot és whitespace-hibákat ellenőrzöm. A merge önmagában nem
      indokol teljes alkalmazástesztelést.
- [ ] Több modult érintő közös alkalmazáskódnál megvizsgáltam az Integration
      szükségességét; projekt-, teszt-, build- vagy ellenőrzési infrastruktúránál
      a Full követelményét alkalmaztam.
- [ ] Alkalmazás- vagy eszközváltozásnál átnéztem a
      `php tools/quality-gate.php affected --dry-run --explain` tervét. Az eltérő
      besorolást jelentettem; a tervezést nem tekintettem sikeres tesztfutásnak.
- [ ] A szükséges, de a választott parancsból hiányzó ellenőrzéseket külön
      felsoroltam. A `composer qa:full` korlátait és a dokumentált konfigurációs
      eltéréseket figyelembe vettem.

## Futtatás és eredmény

- [ ] Lefuttattam a kiválasztott és külön szükséges ellenőrzéseket, vagy
      tételesen rögzítettem, mi akadályozta a futást.
- [ ] Az alkalmazandó MySQL-ellenőrzést dedikált, védett tesztadatbázison
      végeztem; SQLite-eredménnyel nem helyettesítettem.
- [ ] Minden alkalmazandó ellenőrzés eredményét a DoD szerinti `PASSED`,
      `FAILED`, `BLOCKED` vagy `NOT RUN` értékkel jelentettem.
- [ ] A futtató korai leállása után a végre nem hajtott lépések `NOT RUN`
      eredményt kaptak; csak a ténylegesen sikeres lépéseket jelöltem `PASSED`-nek.
- [ ] A tiltott sérülékenységet találó auditot `FAILED`-ként jelentettem.
      `BLOCKED` eredményt csak igazolt külső vagy környezeti akadályhoz használtam.
- [ ] Javítás után ellenőriztem a hibás és a javítás által érintett részeket,
      valamint pótoltam a kimaradt kötelező lépéseket. A sikeres teljes
      tesztcsomagokat nem ismételtem szükségtelenül.

## Csak az ellenőrző eszköz módosításakor

- [ ] A futtató érintett unit tesztjei sikeresek.
- [ ] Az érintett parancsok hibakódját és időtúllépés utáni leállását igazoltam.
- [ ] A folyamatkezelést érintő változásnál igazoltam, hogy nem marad saját
      PHP-, Node-, Playwright- vagy böngészőfolyamat a leállítás után.
- [ ] A módosított mátrix, a kiválasztási tesztek és az útmutató összhangban vannak.

## Átadás és lezárás

- [ ] A bizonyíték tartalmazza a parancsot vagy lépést, a környezetet és a
      megfigyelt eredményt.
- [ ] Minden `FAILED`, `BLOCKED` és `NOT RUN` ellenőrzésnél megadtam a nevét,
      eredményét, okát, hatását, felelősét és következő lépését.
- [ ] Az ellenőrzési eredményeket elkülönítettem a feladat állapotától.
      Feloldatlan, alkalmazandó kötelező ellenőrzés mellett nem állítok teljes
      készültséget, merge- vagy release-készséget; a leírt kivétel önmagában nem
      elfogadás. A lezárást a DoD alapján értékeltem.
