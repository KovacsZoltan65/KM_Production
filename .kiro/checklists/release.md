# Kiadás előtti és utáni ellenőrzőlista

A lista a kiválasztott változások kiadását és telepítését kíséri végig.
A készültség, az alkalmazhatóság és az eredmények mérvadó forrása a
[Definition of Done](../../docs/project-management/definition-of-done.md) (DoD);
az ellenőrzési szintet a [rétegezett útmutató](../../docs/development/quality-gates.md)
alapján válaszd ki. A kiadás önmagában nem tesz minden projektellenőrzést kötelezővé.
A nem alkalmazandó pontoknál rögzítsd az indokot.

A szükséges ellenőrzés `FAILED`, `BLOCKED` vagy `NOT RUN` eredménye mellett
nincs igazolt készültség. A kivétel leírása nem jóváhagyás és nem `PASSED`:
eltéréshez külön, kifejezett, felhatalmazott kockázatelfogadó döntés szükséges
a DoD szerint, az eredeti eredmény megőrzésével.

## 1. Kód és minőség

- [ ] Minden bevont változás merge-ready a DoD és a [merge előtti lista](before-merge.md) szerint.
- [ ] Az alkalmazandó kötelező ellenőrzések rendezettek; eredményük és bizonyítékuk elérhető a [minőségi ellenőrzőlista](quality-gates.md) szerint.
- [ ] Az érintett jogosultságok, fordítások, eseménynaplózás, biztonság és teljesítmény ellenőrzött.
- [ ] Az eltérések, fennmaradó kockázatok és az esetleges külön felhatalmazott döntés láthatók. A tiltott sérülékenységet találó audit `FAILED`, nem `BLOCKED`.

## 2. Adatbázis és migráció

- [ ] Az adatbázis-változásoknál meghatározott a szükséges ellenőrzés, a migráció sorrendje, adatbiztonsága és kiesési kockázata.
- [ ] A szükséges MySQL-ellenőrzés sikeres. SQLite vagy helyi `qa:full` nem helyettesíti; elérhetetlen szükséges MySQL tesztkörnyezet esetén `BLOCKED`, a kiadási készültség nem igazolt.
- [ ] A jogosultságok és szerepkörök módosítása szándékos; a seederek az adott környezetben biztonságosak.
- [ ] A mentési és visszaállítási terv kezeli az adatokat és a vissza nem fordítható migrációkat. Készletmennyiség csak készletmozgással változik; a nyomon követhetőség megmarad.

## 3. Build és csomag

- [ ] A kiadás tartalma és verziója azonosítható; a szükséges build és csomagellenőrzés sikeres.
- [ ] Az érintett frontend assetek elkészültek; a csomag és a függőségek megfelelnek a célkörnyezet tervének.
- [ ] Forrásexport használatakor a [telepítési útmutató](../../docs/deployment.md) exportkorlátait ellenőrizték; a forrás-ZIP önmagában nem telepíthető kiadás.

## 4. Konfiguráció és titkok

- [ ] A célkörnyezet szükséges beállításai és hozzáférései rendelkezésre állnak; titkok nem kerülnek forrásba, csomagba vagy dokumentációba.
- [ ] A queue workerek, ütemezett feladatok, dokumentumtárolás és az érintett AI/OCR feldolgozás konfigurációs hatása ismert.

## 5. Telepítés előkészítése

- [ ] A változáskészlet release-ready: merge-ready, és minden alkalmazandó kiadási követelmény teljesült.
- [ ] A célkörnyezet működési előfeltételei külön ellenőrzöttek; ezek teljesülésével igazolható a deployment-ready állapot.
- [ ] A kiadási megjegyzések, telepítési sorrend, felelősök, utóellenőrzések és visszaállítási vagy eszkalációs feltételek rögzítettek.
- [ ] A szükséges felhatalmazás rendelkezésre áll az [AGENTS.md](../../AGENTS.md) szerint; a lista kipipálása nem ad műveleti engedélyt.

## 6. Telepítés utáni ellenőrzés

Ezeket a pontokat csak a telepítés után lehet igazolni, a
[telepítési útmutató](../../docs/deployment.md) alapján.

- [ ] Az alkalmazás elindul, a szükséges állapotellenőrzések sikeresek, a migrációk a várt állapotban vannak.
- [ ] A célfunkció működik, nincs közvetlen kritikus regresszió, a beállított naplózás ellenőrzése nem mutat kritikus hibát.
- [ ] A visszaállítási lehetőség az ellenőrzés lezárásáig rendelkezésre áll, vagy az előre azonosított korlátot a felhatalmazott terv kezeli.
- [ ] A végrehajtás és az utóellenőrzés eredménye külön rögzített. Csak a befejezett telepítés és a sikeres kötelező utóellenőrzések együtt jelentenek sikeres telepítést.
