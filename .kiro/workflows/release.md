# Kiadás és telepítés menete

Ez az eljárás a kiválasztott változásokat a kiadás előkészítésétől a telepítés
ellenőrzött lezárásáig vezeti végig. A készültség mérvadó forrása a
[Definition of Done](../../docs/project-management/definition-of-done.md) (DoD).
A [kiadási ellenőrzőlista](../checklists/release.md) az alkalmazandó teendőket,
a [telepítési útmutató](../../docs/deployment.md) a működési feltételeket részletezi.

## Mit jelent a készültség?

| Állapot                          | Mit kell igazolni?                                                                                    |
| -------------------------------- | ----------------------------------------------------------------------------------------------------- |
| Task complete                    | A feladat teljesíti a DoD alkalmazandó követelményeit.                                                |
| Merge-ready                      | A DoD és a felülvizsgálat valamennyi alkalmazandó követelménye rendezett.                             |
| Release-ready                    | A kiválasztott változáskészlet merge-ready, és a kiadás saját alkalmazandó követelményei teljesülnek. |
| Deployment-ready                 | A kiválasztott kiadás kész, és a célkörnyezet valamennyi alkalmazandó működési előfeltétele teljesül. |
| Deployment successful / verified | A telepítés befejeződött, és a kötelező utóellenőrzések sikeresek.                                    |

A feladat elkészülte nem azonos a merge-ready állapottal; a merge-ready nem
jelent release-ready állapotot, az pedig nem jelent deployment-ready állapotot.
A telepítési lépések befejezése önmagában nem igazolja a telepítés sikerét.

## 1. Ellenőrizd a kiválasztott változásokat

Rögzítsd a kiadás tartalmát és verzióját. Ellenőrizd minden bevont változás
merge-ready állapotát a [merge előtti lista](../checklists/before-merge.md) és a
[felülvizsgálati útmutató](../../docs/project-management/code-review-guide.md)
szerint. A korábbi ellenőrzések bizonyítéka az adott változatot fedje le.

Az ellenőrzéseket a változás kockázata alapján válaszd ki a
[rétegezett útmutató](../../docs/development/quality-gates.md) szerint.
A kiadás nem követeli automatikusan minden projektellenőrzés futtatását;
a kizárólag dokumentációt érintő változás alkalmazhatóságát is a DoD határozza meg.
A `qa:full` nem tartalmaz minden projektellenőrzést.

## 2. Ellenőrizd a kiadás saját előfeltételeit

Alkalmazd a kiadási lista minőségi, adatbázis-, csomag- és konfigurációs pontjait.
Vizsgáld meg a migrációkat, jogosultságokat, seedereket, fordításokat,
naplózást, biztonságot és teljesítményt az érintett működés szerint.

Adatbázis-változásnál indokold a MySQL-ellenőrzés alkalmazhatóságát. A szükséges
MySQL-vizsgálatot SQLite vagy helyi `qa:full` nem helyettesíti. Ha a szükséges
MySQL tesztkörnyezet elérhetetlen, az eredmény `BLOCKED`, a kiadás nem kész.

Az alkalmazandó kötelező kiadási ellenőrzés `FAILED`, `BLOCKED` vagy `NOT RUN`
eredménye mellett nincs kiadási készültség. Az ok leírása nem jóváhagyás.
Eltéréshez a DoD szerinti külön, kifejezett, felhatalmazott kockázatelfogadó
döntés szükséges; az eredményt ez sem változtatja `PASSED`-re.
Ez az eljárás ilyen felhatalmazást nem ad.

## 3. Készítsd elő a kiadást

Készíts kiadási megjegyzéseket a viselkedésváltozásokról és a módosított fájlokról;
frissítsd az érintett dokumentációt. Rögzítsd a migrációk, jogosultságok,
seederek, konfiguráció, workerek, ütemezett feladatok és tárolás szükséges lépéseit.

Azonosítsd a célkörnyezetet, a telepítési sorrendet, a felelősöket, az
utóellenőrzést és a visszaállítási tervet, beleértve a vissza nem fordítható
adatváltozásokat. Ellenőrizd a szükséges csomagot, buildet, mentést és
hozzáféréseket. Csak a működési előfeltételek igazolása után deployment-ready a kiadás.

## 4. Hajtsd végre a telepítést

A célkörnyezethez rögzített eljárást kövesd a telepítési útmutató szerint.
Hiányzó végrehajtási vagy visszaállítási eljárás esetén előbb rendezd a hiányt;
ne találj ki éles parancsokat. Őrizd meg a végrehajtás eredményét és idejét.

Az [AGENTS.md](../../AGENTS.md) felhatalmazási szabályai érvényesek.
AI-ügynöknek commit, push, PR létrehozása vagy módosítása, merge, kiadás
létrehozása, telepítés és éles visszaállítás előtt kifejezett felhasználói
felhatalmazás kell. A munkafolyamat leírása nem helyettesíti ezt.

## 5. Ellenőrizd a telepítés eredményét

Végezd el a telepítési útmutató és a kiadási lista alkalmazandó utóellenőrzéseit:
indulás, állapotellenőrzés, migrációk, célfunkció, közvetlen regressziók és naplók.
Az ellenőrzés lezárásáig őrizd meg a tervezett visszaállítás lehetőségét.
A dokumentáció nem határoz meg automatizált monitorozási szerződést.

Ha az utóellenőrzés lefut és hibát talál, az eredménye `FAILED`, akkor is, ha
a telepítés technikailag befejeződött. Ezt ne jelentsd sikeres kiadásként.
Külső akadály vagy elmaradt ellenőrzés esetén sincs igazolt telepítési siker.

## 6. Rögzítsd a kimenetelt

A jelentés tartalmazza a verziót, környezetet, változásokat, végrehajtót,
felhatalmazást, időpontot, a futtatott ellenőrzéseket és bizonyítékaikat.
Külön szerepeljen a telepítés és az utóellenőrzés eredménye, a migrációs és
jogosultsági hatás, a visszaállítás, valamint a fennmaradó kockázat és következő lépés.

Az eredményeket a DoD szerint jelentsd: `PASSED`, ha az alkalmazandó ellenőrzés
lefutott és sikeres; `FAILED`, ha lefutott és sikertelen; `BLOCKED`, ha azonosított
külső vagy környezeti előfeltétel akadályozza; `NOT RUN`, ha nem futott le.
A tiltott sérülékenységet találó audit `FAILED`; valóban elérhetetlen külső
audit-szolgáltatás indokolhat `BLOCKED` eredményt. A fel nem készített környezet
önmagában nem ilyen bizonyíték.

A hiányos ellenőrzésnél rögzítsd az okot, hatást, felelőst és következő lépést.
A dátumozott korábbi audit csak korábbi bizonyíték, nem a jelen kiadás eredménye.

## 7. Hiba esetén állítsd meg a továbblépést

Ez a lépés bármely végrehajtási vagy ellenőrzési hibánál azonnal alkalmazandó.
Őrizd meg a bizonyítékot, mérd fel az adat- és üzemi hatást, és kövesd a
telepítési útmutató hibakezelését. Ha teljesül a terv visszaállítási feltétele,
a szükséges felhatalmazással hajtsd végre a visszaállítást, majd ellenőrizd azt is.
Ha a visszaállítás nem biztonságos vagy akadályozott, eszkaláld a helyzetet;
a szükséges döntés és javítás nélkül ne zárd sikeresként a kiadást.
