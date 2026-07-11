# Knowledge Unit Specification v1.0

## Bevezetés

A Knowledge Unit a Learning Center önállóan értelmezhető, újrahasznosítható tudásegysége. Egy konkrét fogalmat, műveletet, üzleti szabályt, döntési pontot, hibát vagy tanulási lépést ír le úgy, hogy abból több megjelenési forma épülhessen.

A KM_Production gyártási rendszerben ugyanaz a tudás több helyen is szükséges: dokumentációban, oldalspecifikus súgóban, tooltipben, onboardingban, hibakeresőben, FAQ-ban, oktatási anyagban és később AI válaszban. Ha ezek külön szövegként készülnek, a tartalom gyorsan duplikálódik és ellentmondásossá válhat.

A Knowledge Unit azért szükséges, hogy a Learning Centernek legyen stabil fogalmi alapegysége. Nem a képernyő, nem az adatbázistábla és nem a technikai komponens a kiindulópont, hanem az a tudás, amelyet a felhasználónak meg kell értenie vagy alkalmaznia kell.

Ezért a Knowledge Unit a Learning Center alapegysége:

- egy tudástartalomnak egy kanonikus forrása lehet
- több felület és csatorna ugyanarra a tudásra hivatkozhat
- a szakmai review és verziózás kezelhetőbbé válik
- a lokalizáció következetesebb marad
- az AI-ready tudásforrás ellenőrzött alapokra épülhet
- a Knowledge Graph kapcsolatai stabil egységekre mutathatnak

Ez a dokumentum fogalmi specifikáció. Nem adatbázisterv, nem Eloquent modellek terve, nem migrációs terv, és nem implementációs szerződés.

## Alapelv

A Knowledge Unit nem oldal, nem komponens, nem adatbázistábla, hanem egy önálló, újrahasznosítható tudásegység.

Egy Knowledge Unit akkor jó, ha a tartalma önmagában értelmezhető, de több kontextusban is felhasználható. A megjelenítési forma változhat, a mögöttes jelentés nem. Ugyanabból az egységből készülhet hosszú dokumentációs magyarázat, rövid tooltip, onboarding lépés, hibamagyarázat, FAQ válasz vagy AI által idézhető válaszforrás.

A Knowledge Unit fogalmi stabilitást ad. Egy gyártási folyamat, egy készletkezelési művelet vagy egy minőségügyi állapot nem attól válik tudásegységgé, hogy melyik Vue oldalon jelenik meg, hanem attól, hogy a felhasználónak önállóan megérthető és újra alkalmazható tudást ad.

## Mi NEM Knowledge Unit?

Nem Knowledge Unit:

- Vue oldal
- Laravel controller
- tooltip önmagában
- FAQ sor önmagában
- dokumentációs fejezet önmagában
- adatbázistábla puszta leképezése
- Eloquent modell vagy migrációs terv
- validációs szabály technikai implementációja
- jogosultsági ellenőrzés technikai részlete
- naplóbejegyzés vagy audit esemény
- egyszeri support válasz
- felhasználói progress rekord

Ezek kapcsolódhatnak Knowledge Unithoz, de önmagukban nem azok. Például egy tooltip lehet egy Knowledge Unit rövid nézete, egy FAQ válasz lehet egy Knowledge Unit kérdés-válasz formája, egy dokumentációs fejezet pedig több Knowledge Unitból épülhet fel.

## Knowledge Unit példák

Tipikus Knowledge Unitok a KM_Production domainben:

- Termék létrehozása
- Raktár létrehozása
- Készlet feltöltése
- BOM létrehozása
- Gyártás indítása
- Minőségellenőrzés rögzítése
- Megrendelés kiszállítása

További lehetséges példák:

- készletfoglalás értelmezése
- minőségügyi zárolás feloldása
- műveleti sorrend verziójának kiválasztása
- sorozatszám vagy lot traceability ellenőrzése
- kapacitástervezési késés magyarázata
- hiányzó jogosultság értelmezése
- dokumentum AI review eredményének értelmezése

## Knowledge Unit tartalma

Egy Knowledge Unit fogalmi szinten az alábbi tartalmi elemekből állhat. Ez a lista nem adatbázismező-lista, hanem tervezési keret.

### Cím

Ember által olvasható, rövid név. A cím mondja ki, miről szól az egység.

Példa: Gyártás indítása

### Rövid cél

Egy-két mondatos összefoglaló arról, mire használható a tudásegység, és milyen felhasználói helyzetben releváns.

### Részletes magyarázat

A fogalom, művelet vagy szabály teljes szakmai leírása. Ez a kanonikus tudásmag, amelyből a rövidebb nézetek is származhatnak.

### Előfeltételek

Azok a szakmai, adatállapotbeli, jogosultsági vagy folyamatbeli feltételek, amelyeknek teljesülniük kell a művelet megértéséhez vagy végrehajtásához.

### Lépések

Felhasználói vagy tanulási lépések fogalmi szinten. A lépések nem technikai route-ok vagy komponensműveletek, hanem a felhasználó által értelmezhető folyamatlépések.

### Példák

Konkrét, domainhez kötött példák, amelyek segítik az értelmezést. A példák lehetnek kezdőbarátak vagy szerepkörspecifikusak.

### Gyakori hibák

Olyan visszatérő elakadások, amelyek ehhez a tudásegységhez kapcsolódnak.

### Javítási javaslatok

Cselekvésorientált útmutatás arra, hogyan tudja a felhasználó megszüntetni a hibát vagy továbblépni.

### Tippek

Rövid, hasznos kiegészítések. A tippek nem helyettesítik a magyarázatot, hanem hatékonyabb munkát vagy jobb megértést támogatnak.

### Kapcsolódó tudáselemek

Más Knowledge Unitok vagy tudástartalmak, amelyek előfeltételként, következő lépésként, kapcsolódó fogalomként vagy hiba magyarázataként relevánsak.

### Érintett szerepkörök

Azok a szakmai szerepkörök, amelyek számára a tudásegység különösen fontos lehet.

Példa: Termelésvezető, Raktáros, Minőségellenőr, Adminisztrátor.

### Érintett oldalak

Azok az oldalak vagy workflow-területek, ahol a tudásegység megjelenhet. Ez nem köti a Knowledge Unitot közvetlenül Vue komponenshez, csak segíti a kontextuális kiválasztást.

### Szükséges jogosultságok

Azok a jogosultságok, amelyek a művelet végrehajtásához szükségesek lehetnek. A Knowledge Unit ezt csak magyarázza; nem helyettesíti a Laravel jogosultsági ellenőrzést.

### Ajánlott tudásszint

Jelzi, hogy a tudásegység alapvetően Kezdő, Haladó vagy Profi szinten milyen részletességgel jelenjen meg. Ugyanaz a Knowledge Unit több szinthez is rendelkezhet eltérő nézettel.

### AI kulcsszavak

Olyan keresési, visszakeresési és prompt-segítő kulcsszavak, amelyek később támogatják az AI integrációt. Ezek nem adnak engedélyt az AI-nak üzleti műveletek végrehajtására.

### Lokalizáció

A tartalom felhasználói nyelve, lokalizált változatai, terminológiai szabályai és fordítási státusza. A magyar projektoldali dokumentáció elsődleges, de a technikai azonosítók angolul maradhatnak.

### Verzió

A tudásegység tartalmi verziója. A verzió segít követni, hogy melyik magyarázat melyik üzleti folyamat-, dokumentáció- vagy alkalmazásállapothoz tartozik.

### Státusz

A tartalom életciklus-állapota. A státusz jelzi, hogy a Knowledge Unit vázlat, review alatt áll, jóváhagyott, publikált, elavult vagy archivált.

## Felhasználási nézetek

Ugyanaz a Knowledge Unit több nézetben jelenhet meg. A nézetek nem külön igazságforrások, hanem a kanonikus tudás alkalmazásai.

### Dokumentációban

Teljes magyarázatként, háttérrel, előfeltételekkel, lépésekkel, példákkal és kapcsolódó témákkal jelenik meg.

### Oldalspecifikus súgóban

Az aktuális oldalhoz, workflow-hoz, szerepkörhöz és állapothoz igazított rövid magyarázatként jelenhet meg.

### Tooltipként

Nagyon rövid, egy-két mondatos nézetként jelenik meg. A tooltip célja nem teljes oktatás, hanem gyors értelmezési segítség.

### Onboarding lépésként

Feladatközpontú, vezetett formában jelenik meg. A kezdő felhasználó számára megmutatja, mit kell ellenőrizni, mit kell megtenni, és honnan látszik a siker.

### Hibakeresőben

Hiba vagy elakadás esetén a Knowledge Unit magyarázza, mi történt, miért történt, mit jelent, mit tegyen a felhasználó, és hogyan kerülhető el legközelebb.

### FAQ-ban

Kérdés-válasz formában jelenik meg. A FAQ nézet egy gyakori kérdésre ad rövid választ, és szükség esetén a teljes Knowledge Unitra mutat.

### AI válaszként

Az AI kontrollált forrásként használhatja a publikált, jogosultság szerint elérhető Knowledge Unitot. Az AI válasz a tudásegységből épülhet, de nem írhatja felül a validációt, jogosultságot vagy üzleti szabályt.

### Oktatási anyagban

Tanulási útvonal, lecke vagy szerepköralapú képzés részeként jelenhet meg. Ilyenkor a Knowledge Unit kapcsolódhat előfeltételhez, gyakorló feladathoz és ellenőrző kérdéshez.

### Későbbi videós tananyagban

A Knowledge Unit forgatókönyv-alapként szolgálhat. A videós tananyag nem külön igazságforrás, hanem a jóváhagyott Knowledge Unit narrált és vizuális feldolgozása.

## Részletes példa: Gyártás indítása

### Cél

A "Gyártás indítása" Knowledge Unit célja annak elmagyarázása, hogyan kerül egy előkészített gyártási rendelés tényleges végrehajtási állapotba, és milyen feltételeknek kell teljesülniük az indítás előtt.

Ez a tudásegység különösen fontos termelésvezetőknek, műszakvezetőknek, raktárosoknak és minőségellenőröknek, mert a gyártás indítása készlet-, traceability-, minőségügyi és jogosultsági következményekkel járhat.

### Előfeltételek

A gyártás indítása előtt jellemzően ezeknek kell teljesülniük:

- létezik előkészített gyártási rendelés
- a rendelés indítható workflow állapotban van
- a termékhez rendelkezésre áll a szükséges BOM
- a szükséges műveleti sorrend ismert és érvényes
- az alapanyagok elérhetők vagy megfelelően foglaltak
- nincs blokkoló minőségügyi zárolás
- a traceability követelmények értelmezhetők
- a felhasználónak van jogosultsága a gyártás indítására
- a kapcsolódó kötelező adatok nem hiányoznak

### Felhasználói lépések

1. Nyisd meg az előkészített gyártási rendelést.
2. Ellenőrizd a rendelés állapotát és a termék adatait.
3. Ellenőrizd, hogy a BOM és a műveleti sorrend rendelkezésre áll.
4. Nézd át az anyagelérhetőséget és a foglalásokat.
5. Ellenőrizd, hogy nincs-e minőségügyi zárolás vagy blokkoló figyelmeztetés.
6. Ellenőrizd a szükséges traceability adatokat.
7. Indítsd el a gyártást, ha minden előfeltétel teljesül.
8. Ellenőrizd, hogy a gyártási rendelés végrehajtási állapotba került.

### Sikeres állapot

Sikeres indítás után a gyártási rendelés már nem pusztán előkészített terv, hanem aktív végrehajtási folyamat része. A rendszernek egyértelműen jeleznie kell az új állapotot, a kapcsolódó műveletek elérhetőségét, valamint azokat a további feladatokat, amelyek a gyártás során következnek.

A sikeres állapot fogalmilag azt jelenti, hogy:

- a rendelés indított vagy végrehajtás alatti állapotba került
- az indítás auditálható eseményként értelmezhető
- a következő gyártási lépések elérhetővé válhatnak
- a további készlet-, minőség- és traceability műveletek az aktív gyártáshoz kapcsolódnak

### Gyakori hibák

- A gyártási rendelés nincs indítható állapotban.
- Hiányzik a BOM.
- Hiányzik vagy nem érvényes a műveleti sorrend.
- Nincs elegendő elérhető vagy foglalható alapanyag.
- Egy alapanyag, lot vagy termék minőségügyi zárolás alatt áll.
- A felhasználónak nincs megfelelő jogosultsága.
- Hiányzik kötelező traceability adat.
- A rendeléshez kapcsolódó termék vagy raktári adat nem teljes.

### Javítások

- Ha a rendelés állapota nem megfelelő, térj vissza az előkészítési lépéshez, és ellenőrizd a workflow státuszt.
- Ha hiányzik a BOM, hozd létre vagy hagyasd jóvá a termékhez tartozó BOM-ot.
- Ha hiányzik a műveleti sorrend, válaszd ki vagy készítsd elő a megfelelő verziót.
- Ha nincs elegendő alapanyag, ellenőrizd a készletszinteket, foglalásokat és raktári elérhetőséget.
- Ha minőségügyi zárolás blokkol, ellenőrizd a zárolás okát, és vond be a minőségellenőrzésért felelős szerepkört.
- Ha jogosultsági hiba jelenik meg, kérj megfelelő szerepkört vagy jogosultságot az adminisztrátortól.
- Ha traceability adat hiányzik, rögzítsd a szükséges lot-, sorozatszám- vagy gyártási adatokat.

### Tippek

- A gyártás indítása előtt érdemes az előfeltételek panelt végignézni, mert a legtöbb indítási hiba ott előre látható.
- Kezdő felhasználónál az onboarding ne csak az indítás gombot mutassa, hanem az ellenőrzési gondolkodást is tanítsa.
- Minőségügyi zárolás esetén ne kerülőutat keress, hanem a zárolás okát kell megérteni és kezelni.
- Ha ugyanaz az indítási hiba többször előfordul, a Learning Center javasolhat részletesebb asszisztenciaszintet az adott oldalra.

### Megjelenés dokumentációban

A dokumentációs nézet részletesen leírja a gyártás indításának folyamatát, előfeltételeit, következményeit és tipikus hibáit. A dokumentáció összekapcsolja a témát a BOM, műveleti sorrend, készletfoglalás, minőségügyi zárolás, traceability és jogosultság témáival.

Példa dokumentációs bekezdés:

"A gyártás indítása akkor végezhető el, amikor a gyártási rendelés előkészített állapotban van, a szükséges termék- és folyamatadatok rendelkezésre állnak, az alapanyagok elérhetők, és nincs blokkoló minőségügyi vagy jogosultsági akadály. Az indítás után a rendelés aktív végrehajtási folyamattá válik."

### Megjelenés onboardingban

Az onboarding nézet vezetett ellenőrzési listaként jelenik meg:

1. Keresd meg az előkészített gyártási rendelést.
2. Ellenőrizd a BOM és műveleti sorrend meglétét.
3. Nézd meg az anyagelérhetőséget.
4. Ellenőrizd a minőségügyi figyelmeztetéseket.
5. Nézd meg, hogy van-e hiányzó traceability adat.
6. Indítsd el a gyártást.
7. Ellenőrizd az új állapotot.

Kezdő szinten az onboarding minden lépéshez rövid magyarázatot ad. Haladó szinten csak eltérés vagy hiány esetén jelez. Profi szinten csak kérésre nyitja meg ezt a segítséget.

### Megjelenés AI válaszban

Az AI válasz a publikált Knowledge Unitból és a jogosultság szerint szűrt kontextusból épülhet.

Példa AI válasz:

"A gyártás indítása azért nem érhető el, mert az indításhoz szükséges előfeltételek közül legalább egy hiányzik. Ellenőrizd a rendelés állapotát, a BOM és műveleti sorrend meglétét, az anyagelérhetőséget, a minőségügyi zárolásokat és a jogosultságodat. Ha megnyitod az előfeltételek panelt, láthatod, melyik feltétel blokkol."

Az AI nem indít gyártást, nem módosít készletet, nem old fel zárolást és nem ad megkerülő tanácsot jogosultsági vagy minőségügyi blokkolásra.

### Megjelenés hibakeresőben

Hibakereső nézetben a Knowledge Unit cselekvésorientált formában jelenik meg:

- Mi történt? A gyártás indítása nem hajtható végre.
- Miért történt? Egy vagy több előfeltétel hiányzik, vagy blokkoló állapot áll fenn.
- Mit jelent? A rendszer védi a gyártási folyamat, készlet, minőség és traceability integritását.
- Mit tegyen a felhasználó? Ellenőrizze az előfeltételeket, és rendezze a hiányzó vagy blokkoló elemeket.
- Hogyan kerülhető el legközelebb? A gyártás előkészítésekor ellenőrizni kell a BOM-ot, műveleti sorrendet, anyagelérhetőséget, zárolásokat és jogosultságokat.

## Tudásszintek kapcsolata

Ugyanaz a Knowledge Unit eltérő részletességgel jelenhet meg Kezdő, Haladó és Profi szinten.

### Kezdő szinten

A Kezdő nézet magyarázó és vezetett. Több háttérinformációt ad, lépésről lépésre bontja a folyamatot, és megmagyarázza, miért fontosak az előfeltételek.

Példa:

"A gyártás indítása előtt a rendszer ellenőrzi, hogy a rendelés valóban készen áll-e a végrehajtásra. Ez azért fontos, mert hiányzó BOM, alapanyag vagy minőségügyi zárolás esetén a gyártás hibás készlet- vagy traceability adatokat eredményezhet."

### Haladó szinten

A Haladó nézet rövidebb, helyzetfüggő és figyelmeztető jellegű. Feltételezi, hogy a felhasználó ismeri az alapfolyamatot, ezért főleg eltérésekre és hiányokra hívja fel a figyelmet.

Példa:

"Az indítás még nem lehetséges: ellenőrizd a BOM-ot, a műveleti sorrendet, az anyagelérhetőséget és a zárolásokat."

### Profi szinten

A Profi nézet minimális és kérésre nyíló. Csak kritikus figyelmeztetés vagy felhasználói kérés esetén jelenik meg, és gyors diagnosztikai információt ad.

Példa:

"Indítás blokkolva: hiányzó előfeltétel. Részletek az előfeltételek panelen."

## Knowledge Unit életciklus

### Draft

A Knowledge Unit vázlatos állapotban van. A tartalom még nem tekinthető szakmailag véglegesnek, és végfelhasználói felületen nem jelenhet meg éles segítségként.

### Review

A Knowledge Unit szakmai, dokumentációs vagy biztonsági ellenőrzés alatt áll. Ilyenkor a cél a pontosság, érthetőség, jogosultsági határok és kapcsolódó tudáselemek validálása.

### Approved

A Knowledge Unit jóváhagyott tartalom, de még nem feltétlenül publikált. A jóváhagyás azt jelenti, hogy szakmailag megfelelő, és készen áll publikálásra vagy további csatornákba illesztésre.

### Published

A Knowledge Unit publikált és végfelhasználói nézetekben használható. Csak publikált, jogosultság szerint elérhető és lokalizáció szerint megfelelő tartalom jelenhet meg a felhasználóknak vagy AI válaszforrásként.

### Deprecated

A Knowledge Unit elavult, de átmenetileg még hivatkozható lehet. Ilyen állapotban jelezni kell, hogy újabb tudásegység, folyamat vagy dokumentáció váltja ki.

### Archived

A Knowledge Unit archivált. Aktív felhasználói segítségben nem használható, de történeti, auditálási vagy dokumentációs okból visszakereshető maradhat.

## Verziókezelés

### Mikor kell új verzió?

Új verzió kell, ha a tudásegység szakmai jelentése vagy felhasználói következménye megváltozik.

Példák:

- megváltozik a gyártásindítás előfeltételrendszere
- új kötelező minőségügyi ellenőrzés kerül be
- a traceability követelmény tartalmilag módosul
- új workflow állapot befolyásolja a műveletet
- jogosultsági logika változik a felhasználói magyarázat szempontjából
- AI válaszforrásként már csak új, ellenőrzött tartalom használható

### Mikor elég módosítani?

Egyszerű módosítás elég, ha a tudás jelentése nem változik.

Példák:

- nyelvi pontosítás
- elírás javítása
- példa érthetőbbé tétele
- kapcsolódó link frissítése
- lokalizációs megfogalmazás javítása

### Kapcsolódás Git dokumentációhoz

Markdown-alapú tudás esetén a Git biztosítja a változások áttekinthetőségét, review-ját és történetét. A Knowledge Unit verziója kapcsolódhat dokumentációs commitokhoz, pull requestekhez és ADR-ekhez.

A Git nem feltétlenül váltja ki a későbbi tartalmi verziómezőt, de v1.0 tervezési szinten elegendő lehet a dokumentációs változások nyomon követéséhez.

### Későbbi adatbázis-kezelés

Ha a Knowledge Unit később adatbázisban is megjelenik, a verziókezelésnek támogatnia kell:

- publikált és draft változatok elkülönítését
- lokalizált változatok szinkronját
- jóváhagyási státusz követését
- AI számára engedélyezett forrásverzió jelölését
- elavult verziók visszakereshetőségét
- kapcsolatok verziófüggő értelmezését

Ez továbbra sem jelenti azt, hogy a jelen specifikáció adatbázisterv lenne.

## Kapcsolatok

A Knowledge Unit más Knowledge Unitokhoz és tudáselemekhez kapcsolódhat. A kapcsolatok készítik elő a Knowledge Graph működését.

### Előfeltétel

Egy Knowledge Unit megértéséhez vagy végrehajtásához előbb egy másik egység ismerete szükséges.

Példa: a "Gyártás indítása" előfeltétele lehet a "BOM létrehozása" és a "Készlet feltöltése".

### Következő lépés

Egy tudásegység után természetes következő tanulási vagy workflow lépés ajánlható.

Példa: a "Gyártás indítása" után következhet a "Minőségellenőrzés rögzítése".

### Kapcsolódó fogalom

Lazább szakmai kapcsolat, amely segít megérteni a tágabb kontextust.

Példa: a "Készlet feltöltése" kapcsolódhat a "Raktár létrehozása" és "Készletfoglalás értelmezése" egységekhez.

### Hiba magyarázata

Egy Knowledge Unit magyarázhat olyan hibát vagy validációs akadályt, amely a felhasználói munkában megjelenik.

Példa: a "Hiányzó BOM" hiba kapcsolódhat a "BOM létrehozása" egységhez.

### Alternatív út

Egy célhoz több szakmailag érvényes folyamat vezethet. A Knowledge Unit jelezheti, mikor melyik út ajánlott.

Példa: készlet pótlása történhet beszerzéssel, belső áthelyezéssel vagy gyártással, ha az adott domain szabály ezt lehetővé teszi.

### Szerepkörhöz kötött ajánlás

Ugyanaz a tudásegység más szerepkör számára más hangsúllyal lehet releváns.

Példa: a "Gyártás indítása" termelésvezetőnek workflow-fókuszú, raktárosnak anyagelérhetőségi, minőségellenőrnek zárolási és ellenőrzési szempontból fontos.

## Knowledge Atom későbbi lehetősége

A Knowledge Atom egy Knowledge Unitnál kisebb, elemi tudáselem lehet. Ilyen lehet például egy definíció, egy mező rövid magyarázata, egy egyetlen validációs ok, egy rövid tipp, egy terminológiai fordítás vagy egy apró figyelmeztetés.

A Knowledge Atom abban különbözik a Knowledge Unittól, hogy önmagában nem feltétlenül ír le teljes felhasználói célt, műveletet vagy értelmezhető tanulási egységet. Inkább építőelem, amelyből tooltip, mezőmagyarázat, FAQ-részlet vagy AI retrieval részlet készülhet.

Nem biztos, hogy v1.0-ban szükséges bevezetni, mert:

- bonyolítaná a fogalmi modellt
- túl korán adatmodell-szerű gondolkodásba tolhatná a specifikációt
- növelné a szerkesztési és review terhet
- a Knowledge Unit önmagában elég lehet az első tudásstruktúra kialakításához

Knowledge Atom bevezetése akkor lehet indokolt, ha:

- sok mezőszintű tooltipet kell következetesen kezelni
- AI retrieval túl nagy egységekkel dolgozik
- ugyanazok a rövid definíciók sok Knowledge Unitban ismétlődnek
- többnyelvű terminológiai kezelés válik hangsúlyossá
- admin UI-ban finomabb tartalmi újrafelhasználásra van szükség

## Határok

A Knowledge Unit nem implementációs részlet.

A Knowledge Unit nem kötődik közvetlenül Laravelhez vagy Vue-hoz. Kapcsolódhat oldalakhoz, jogosultságokhoz és workflow-khoz, de nem azonos controllerrel, komponenssel, route-tal vagy adatbázistáblával.

A Knowledge Unit nem helyettesíti a jogosultsági rendszert. Csak elmagyarázhatja, milyen jogosultság szükséges egy művelethez, de az engedélyezést továbbra is az alkalmazás végzi.

A Knowledge Unit nem helyettesíti a validációt. Csak értelmezhetővé teszi a validációs hibákat, de nem dönt adatérvényességről.

A Knowledge Unit nem helyettesíti a felhasználói dokumentációt, hanem annak forrása lehet. A dokumentáció, onboarding, FAQ, tooltip és oktatási anyag a Knowledge Unitból építkezhet, de a végső megjelenési forma külön szerkesztői és UX döntés marad.

A Knowledge Unit nem módosíthat üzleti logikát, nem írhat készletet, nem oldhat fel minőségügyi zárolást, nem változtathat workflow állapotot, és nem kerülheti meg a traceability vagy audit követelményeket.

## Nyitott kérdések

- Mely mezők kerüljenek ténylegesen v1.0-ba, és melyek maradjanak későbbi bővítésre?
- Legyen-e admin UI a Knowledge Unitok szerkesztéséhez már v1.0-ban?
- Markdownból vagy adatbázisból induljon-e a Knowledge Unitok elsődleges forrása?
- Hogyan történjen a szakmai jóváhagyás és publikálás?
- Ki legyen egy Knowledge Unit szakmai tulajdonosa?
- Hogyan kapcsolódjon az AI-hoz: csak keresési metaadatként, válaszforrásként vagy vezetett asszisztenciaként?
- Kell-e Knowledge Atom már az első verzióban, vagy elég későbbi lehetőségként kezelni?
- Hogyan legyen kezelve a lokalizációs review több nyelv esetén?
- Mely kapcsolattípusok legyenek kötelezőek v1.0-ban?
- Milyen minimum státusz szükséges ahhoz, hogy egy Knowledge Unit AI válaszforrás lehessen?

## Kapcsolódó témák

- [Knowledge Graph](knowledge-graph.md)
- [Knowledge Engine](knowledge-engine.md)
- [Context Engine](context-engine.md)
- [Learning Engine](learning-engine.md)
- [Asszisztenciaszintek](assistance-levels.md)
- [Hibakezelés](error-handling.md)
- [AI integráció](ai-integration.md)
- [Döntések](decisions.md)
