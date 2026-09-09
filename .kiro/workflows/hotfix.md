# Sürgős éles hibajavítás menete

A hotfix célja egy sürgős éles hiba gyors, szűk körű javítása, az adatbiztonság
és a nyomon követhetőség megőrzésével. A sürgősség nem felhatalmazás, és nem
jelent ellenőrzés nélküli munkát. A készültségre a
[Definition of Done](../../docs/project-management/definition-of-done.md) (DoD),
az ellenőrzések kiválasztására a [rétegezett útmutató](../../docs/development/quality-gates.md)
vonatkozik.

## 1. Tisztázd a hibát és szűkítsd a javítást

Rögzítsd az incidens sürgősségét, az érintett felhasználókat, folyamatokat,
adatokat és üzemi kockázatot. Gyűjts reprodukciót, naplókat vagy meglévő
tesztbizonyítékot; olvasd el az érintett irányelveket és architekturális döntéseket.
Csak az engedélyezett hibát javítsd, kapcsolódó takarítás nélkül.

Óvd a készlet-, gyártási, minőségi, sorozatszám- és audittörténetet.
Készletmennyiséget csak készletmozgással változtass. Tartsd meg a jogosultsági
ellenőrzéseket, validációt, tranzakciókat és eseménynaplózást, valamint a
`Controller -> Service -> Repository -> Model` rétegezést.

## 2. Rögzítsd a gyorsítás határait

A szűkebb javítás, gyorsabb felülvizsgálati út, célzott ellenőrzés és gyorsított
kiadás megengedhető. Az alkalmazandó DoD-követelmények, biztonsági ellenőrzések,
felhatalmazás, visszaállítási terv, telepítés utáni ellenőrzés és pontos
eredményközlés ettől még kötelezők.

Ha a szokásos eljárást szándékosan rövidíted, rögzítsd:

- pontosan melyik lépés rövidül;
- miért szükséges;
- milyen kockázat marad;
- ki és milyen felhatalmazással engedélyezi;
- milyen utómunka szükséges, ki felel érte és mikorra.

Ez az eljárás nem jelöl ki új jóváhagyói szerepkört. Ha a felhatalmazás nem
ismert, külön, kifejezett, felhatalmazott döntés szükséges. A kockázat vagy az
utómunka leírása önmagában nem engedély kötelező ellenőrzés elhagyására.

Az [AGENTS.md](../../AGENTS.md) szabályait kövesd: AI-ügynök csak kifejezett
felhasználói felhatalmazással commitolhat, pusholhat, hozhat létre vagy
módosíthat PR-t, végezhet merge-öt, hozhat létre kiadást, telepíthet vagy
állíthat vissza éles rendszert. A sürgősség ezt nem pótolja.

## 3. Tervezd meg a visszaállítást és a célzott javítást

Módosítás előtt tisztázd, hogyan állítható vissza biztonságosan az érintett
működés. Vizsgáld meg a migrációs és konfigurációs hatást, a mentési igényt és
a vissza nem fordítható adatváltozásokat. A
[telepítési útmutató](../../docs/deployment.md) alapján legyen végrehajtható
visszaállítási vagy eszkalációs terv, azután készítsd el a célzott javítást.

## 4. Ellenőrizd a javítást a kockázat szerint

A hotfix önmagában nem tesz minden projektellenőrzést kötelezővé. A rétegezett
útmutató alapján például elszigetelt helyi hibánál célzott vagy modulszintű
ellenőrzés lehet elegendő; közös alkalmazásműködésnél Integration, teszt-, build-,
függőség- vagy globális infrastruktúra-változásnál Full szint szükséges lehet.
A pontos besorolást az útmutató határozza meg, a `qa:full` nem minden projektellenőrzés.

Az alkalmazandó regressziós tesztet készítsd el és futtasd; szükség szerint
kézi ellenőrzés is kell. A teszt szükségességét a DoD és a változás kockázata
dönti el, nem a sürgősség. Ha szükséges ellenőrzés nem végezhető el, az okot
és a tényleges eredményt rögzítsd; az indoklás nem helyettesíti az ellenőrzést.

Adatbázis-változásnál határozd meg a MySQL-ellenőrzés alkalmazhatóságát.
A szükséges MySQL-ellenőrzés külön követelmény: SQLite és helyi `qa:full` nem
helyettesíti. Elérhetetlen szükséges MySQL tesztkörnyezetnél `BLOCKED` az eredmény,
és a kiadási készültség nem igazolt.

A DoD szerint a sikeresen lefutott ellenőrzés `PASSED`, a sikertelen `FAILED`,
az azonosított külső vagy környezeti akadály miatt érdemben nem végezhető
ellenőrzés `BLOCKED`, a le nem futott `NOT RUN`. Tiltott sérülékenységet találó
audit `FAILED`, nem `BLOCKED`; elérhetetlen külső szolgáltatás valódi akadály
lehet. A kötelező ellenőrzés `FAILED`, `BLOCKED` vagy `NOT RUN` eredménye mellett
nincs teljes készültség, kivéve a DoD szerinti külön, kifejezett, felhatalmazott
kockázatelfogadó döntést. Ez sem módosítja az eredményt `PASSED`-re.

## 5. Vizsgáltasd felül, készítsd elő és telepítsd

A gyorsított felülvizsgálat is teljesítse a
[felülvizsgálati útmutató](../../docs/project-management/code-review-guide.md)
alkalmazandó követelményeit. Kövesd a [kiadási folyamatot](release.md) és a
[kiadási ellenőrzőlistát](../checklists/release.md): a merge-ready, release-ready
és deployment-ready állapotot külön kell igazolni. Csak a szükséges
felhatalmazással hajtsd végre a célkörnyezethez előkészített telepítést.

## 6. Ellenőrizd az éles eredményt és zárd le az incidenst

A telepítési útmutató szerint ellenőrizd az indulást, a szükséges állapotjelzéseket,
a migrációkat, a javított funkciót, a közvetlen kritikus regressziókat és naplókat.
Őrizd meg a visszaállítás lehetőségét az ellenőrzés lezárásáig a terv szerint.
Hiba esetén állítsd meg a továbblépést, és alkalmazd a felhatalmazott
visszaállítási vagy eszkalációs tervet. A technikailag befejezett telepítés
sikertelen utóellenőrzéssel nem sikeres telepítés.

A jelentésben rögzítsd az incidens és a javítás összefoglalóját, a módosított és
létrehozott fájlokat, a verziót és környezetet, a felhatalmazást, a futtatott
ellenőrzéseket és bizonyítékaikat. A telepítés és az utóellenőrzés külön eredményt
kapjon. A hiányzó vizsgálatoknál szerepeljen az ok, hatás, felelős és következő lépés;
a rövidített eljárás, fennmaradó kockázat, visszaállítás és utómunka maradjon látható.

Az incidens eredményét mindig dokumentáld. Az érintett működés dokumentációját a
DoD szerint frissítsd; újrafelhasználható szabályt, hiányzó döntést vagy megváltozott
üzemeltetési eljárást a megfelelő tartós dokumentumban is rögzíts.
