# Knowledge Unit

## Cél

Ez a dokumentum a Learning Center legfontosabb fogalmi alapegységét, a Knowledge Unitot definiálja. Nem adatmodellt ír le, hanem azt a gondolkodási keretet, amelyre a dokumentáció, a tooltip, az onboarding, a hibakeresés, a FAQ és a későbbi AI asszisztencia közösen épülhet.

## Tervezett tartalom

- Knowledge Unit fogalmi meghatározása.
- Határok: mi tartozik ide és mi nem.
- Életciklus, újrafelhasználás, kapcsolatok és verziózás.
- Lokalizációs és minőségbiztosítási elvek.
- Példa: "Gyártás indítása" több megjelenési formában.

## Mi a Knowledge Unit?

A Knowledge Unit egy önállóan értelmezhető, újrafelhasználható tudásegység egy konkrét fogalomról, műveletről, szabályról, döntési pontról vagy gyakori hibáról. Egy Knowledge Unit nem feltétlenül egy oldal, fejezet vagy adatbázisrekord. Inkább egy stabil tudásmag, amely több csatornán és több részletességi szinten jelenhet meg.

Példák Knowledge Unitra:

- gyártás indítása
- készletfoglalás jelentése
- minőségellenőrzési zárolás
- kapacitástervezési késés oka
- dokumentum AI review állapota
- jogosultság hiánya egy művelethez

A Knowledge Unit célja, hogy ugyanazt az üzleti tudást ne kelljen külön megírni hosszú dokumentációban, tooltipben, onboarding leckében, FAQ-ban és AI válaszban. A formátum eltérhet, de a mögöttes tudás azonos marad.

## Mi NEM Knowledge Unit?

Nem Knowledge Unit:

- egy teljes kézikönyv vagy hosszú modulfejezet
- egy UI komponens technikai implementációja
- egy konkrét felhasználó progress rekordja
- egy naplóbejegyzés vagy audit esemény
- egy egyszeri support válasz
- egy adatbázistábla puszta leképezése
- egy olyan szöveg, amelynek nincs stabil üzleti jelentése

Egy Knowledge Unit nem helyettesíti az adatmodellt, a jogosultsági rendszert, a validációt vagy az üzleti logikát. A feladata a tudás szervezése és következetes közvetítése.

## Miért erre épül a teljes Learning Center?

A KM_Production összetett gyártási rendszer. Ugyanaz a fogalom több helyen is előkerülhet: dokumentációban, oldalsúgóban, validációs hibánál, onboarding során, riportnál vagy AI kérdésben. Ha minden csatorna saját szöveget és saját logikát kap, a tudás gyorsan széttöredezik.

A Knowledge Unit közös alapot ad:

- egy fogalomnak egy stabil forrása van
- több megjelenési forma ugyanarra a tudásra hivatkozik
- könnyebb review-zni és karbantartani a tartalmat
- a későbbi AI válaszok ellenőrzött tudásból épülhetnek
- a Learning Engine tanulási útvonalakat tud építeni a tudásegységek köré
- a Context Engine helyzet alapján releváns egységeket tud kiválasztani

## Milyen problémát old meg?

A hagyományos dokumentációs megközelítés gyakran oldal- vagy funkcióközpontú. Ez rövid távon egyszerű, hosszú távon viszont ismétlődést okoz. Egy üzleti szabály több dokumentumban is megjelenik, és változáskor nehéz biztosítani, hogy mindenhol ugyanaz legyen az értelmezés.

A Knowledge Unit ezt úgy oldja meg, hogy a tudás elsődleges szervezési egysége nem a dokumentum, hanem maga a jelentés. A dokumentumok, tooltippek és tanulási elemek ebből az egységből építkeznek.

## Miért jobb, mint külön dokumentációkat fenntartani?

Külön dokumentációk esetén a rendszerben párhuzamos igazságok alakulhatnak ki. Egy onboarding szöveg mást mondhat, mint a FAQ, a tooltip vagy a hibaüzenet magyarázata. Ez különösen veszélyes gyártási környezetben, ahol a traceability, készletmozgások, minőségügyi állapotok és jogosultságok pontos értelmezése üzletileg kritikus.

A Knowledge Unit előnyei:

- csökkenti a tartalmi duplikációt
- támogatja a konzisztens szakmai nyelvet
- egyszerűbbé teszi a review-t és verziózást
- segíti a lokalizációt
- előkészíti az AI-ready tudásforrást
- egyértelmű kapcsolatot teremt a dokumentáció és a felhasználói kontextus között

## Filozófia

A Learning Center nem külön kézikönyv, hanem a rendszerbe ágyazott tudásréteg. A Knowledge Unit filozófiája ezért:

- a tudás legyen kicsi, stabil és újrafelhasználható
- a forma alkalmazkodjon a helyzethez
- a tartalom maradjon üzletileg pontos
- a felhasználó csak annyi részletet kapjon, amennyi az adott helyzetben hasznos
- a rendszer ne oktasson túl, de ne is hagyja magára a felhasználót

## Életciklus

Egy Knowledge Unit javasolt életciklusa:

1. Felismerés: ismétlődő kérdés, hiba, onboarding igény vagy üzleti fogalom azonosítása.
2. Vázlat: rövid fogalmi definíció és elsődleges felhasználási helyek rögzítése.
3. Szakmai review: domain owner ellenőrzi az üzleti pontosságot.
4. Publikálás: a tudásegység elérhetővé válik dokumentációban vagy asszisztenciában.
5. Újrafelhasználás: tooltip, onboarding, FAQ vagy hibakereső kapcsolódik hozzá.
6. Mérés: support kérdések, keresések és hibák alapján értékelhető a hasznosság.
7. Frissítés: workflow, jogosultság vagy üzleti szabály változásakor módosul.
8. Archiválás: elavult tudás esetén kivezethető, de történeti okból visszakereshető maradhat.

## Felhasználási módok

Egy Knowledge Unit több helyzetben jelenhet meg:

- hosszabb dokumentációs fejezetként
- rövid tooltipként
- onboarding lépésként
- hibaüzenet magyarázataként
- FAQ válaszként
- keresési találatként
- AI válasz forrásaként
- mentor vagy instruktor ajánlásként
- tanulási útvonal elemként

## Kapcsolatok

A Knowledge Unit kapcsolódhat:

- oldalakhoz és workflow-khoz
- szerepkörökhöz és jogosultságokhoz
- hibakódokhoz és validációs szabályokhoz
- entitásállapotokhoz
- tanulási útvonalakhoz
- más Knowledge Unitokhoz
- dokumentációs fejezetekhez
- AI kulcsszavakhoz és visszakeresési metaadatokhoz

Ezek a kapcsolatok készítik elő a Knowledge Graph szemléletet.

## Újrafelhasználhatóság

Az újrafelhasználhatóság nem azt jelenti, hogy mindenhol ugyanaz a szöveg jelenik meg. A Knowledge Unit több nézettel rendelkezhet:

- részletes magyarázat dokumentációhoz
- rövid definíció tooltiphez
- lépésről lépésre forma kezdőknek
- összefoglaló haladóknak
- minimális, kérésre nyíló forma profiknak
- strukturált forrás AI válaszhoz

A lényeg az, hogy ezek ugyanarra a szakmai tudásra épüljenek.

## Verziókezelés

A Knowledge Unitnak verziózhatónak kell lennie, mert a gyártási folyamatok, jogosultságok és rendszerfunkciók változhatnak. A verziózásnak legalább ezeket kell támogatnia:

- tartalmi változások követése
- szakmai review állapot
- elavult egységek jelölése
- kapcsolódó dokumentumok frissítési igénye
- lokalizált változatok szinkronja
- AI forrásként használható verziók elkülönítése

## Lokalizáció

A Knowledge Unit tartalma ember által olvasott dokumentáció, ezért magyar nyelvű. A technikai azonosítók, kulcsok és kódnevek angolul maradnak a projektkonvenciók szerint.

Lokalizációs elvek:

- a szakmai fogalmak legyenek következetesek
- a magyar magyarázat legyen elsődleges
- az angol technikai kulcsok ne kerüljenek felhasználói szövegbe indokolatlanul
- AI integráció esetén a forrásnyelv és válasznyelv legyen explicit kezelve
- későbbi többnyelvű dokumentáció esetén a Knowledge Unit azonosítója stabil maradjon

## Példa: Gyártás indítása

### Knowledge Unit

Név: Gyártás indítása

Fogalmi tartalom: A gyártás indítása az a művelet, amikor egy előkészített gyártási rendelés tényleges végrehajtási állapotba kerül. A művelet előtt ellenőrizni kell az előfeltételeket, például az anyagelérhetőséget, a jogosultságot, a minőségügyi zárolásokat és a szükséges gyártási adatok meglétét.

### Megjelenés dokumentációban

A dokumentáció részletesen leírja:

- milyen állapotból indítható a gyártás
- milyen előfeltételek szükségesek
- milyen készlet- vagy traceability hatások kapcsolódhatnak hozzá
- milyen hibák akadályozhatják az indítást
- mi történik sikeres indítás után

### Megjelenés tooltipben

Rövid magyarázat:

"A gyártás csak akkor indítható, ha a rendelés előkészített állapotban van, és minden szükséges előfeltétel teljesül."

### Megjelenés onboardingban

Kezdő felhasználónak lépésenként:

1. Nyisd meg az előkészített gyártási rendelést.
2. Ellenőrizd az anyagokat és zárolásokat.
3. Nézd meg, van-e hiányzó előfeltétel.
4. Indítsd el a gyártást.
5. Ellenőrizd az új állapotot.

### Megjelenés AI válaszként

Az AI válasz a Knowledge Unit ellenőrzött tartalmából épülhet:

"A gyártást azért nem tudod elindítani, mert a rendeléshez még hiányzik egy előfeltétel. Ellenőrizd az anyagelérhetőséget és a minőségügyi zárolásokat. Ha minden feltétel teljesül, az indítás gomb aktívvá válik."

### Megjelenés hibakeresőben

Hiba esetén:

- ok: hiányzó előfeltétel
- magyarázat: a rendszer nem engedi a gyártás indítását, amíg a szükséges előfeltételek nem teljesülnek
- javasolt lépés: nyisd meg az előfeltételek panelt, és rendezd a hiányzó elemeket

### Megjelenés FAQ-ban

Kérdés: Miért nem tudom elindítani a gyártást?

Válasz: A gyártás indítását jogosultsági, állapotbeli, anyagelérhetőségi vagy minőségügyi előfeltételek akadályozhatják. A Learning Center az aktuális rendelés alapján megmutathatja, melyik feltétel hiányzik.

## Kapcsolódó témák

- [Knowledge Graph](knowledge-graph.md)
- [Knowledge Engine](knowledge-engine.md)
- [Context Engine](context-engine.md)
- [Learning Engine](learning-engine.md)
- [AI integráció](ai-integration.md)
