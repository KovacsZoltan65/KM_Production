# KM_Production Knowledge Graph Specification

Version: v1.0  
Status: Draft  
Scope: Project-level architecture

## Bevezetés

A Knowledge Graph a KM_Production üzleti és tudáskapcsolatainak fogalmi hálózata. Célja, hogy megmutassa, mely tudásegységek, folyamatok, szerepkörök, jogosultságok, oldalak, hibák, figyelmeztetések, tananyagok és dokumentációs elemek kapcsolódnak egymáshoz.

Ez projekt szintű architektúra, mert a tudáskapcsolatok nem kizárólag a Learning Center modulban élnek. A gyártási folyamatok, készletkezelés, minőségellenőrzés, beszerzés, dokumentáció, jogosultságok, hibakezelés, onboarding, tesztelés, AI asszisztencia és analitika ugyanarra a tudástérre hivatkozhatnak.

A Learning Center a Knowledge Graph egyik elsődleges felhasználója. A Learning Center a gráf alapján találhat kapcsolódó Knowledge Unitokat, onboarding lépéseket, oldalspecifikus súgókat, FAQ válaszokat, hibamagyarázatokat és ajánlott következő témákat.

Az AI asszisztens számára a Knowledge Graph kontrollált navigációs tér lehet. Az AI a gráf alapján értheti meg, hogy egy kérdés mely üzleti folyamatokhoz, jogosultságokhoz, hibákhoz és tudásegységekhez kapcsolódik. Ez csökkentheti a hallucinációt, mert az AI nem szabadon találgatja a kapcsolatokat, hanem a jóváhagyott tudáshálóból indul ki.

Később a Knowledge Graph kapcsolódhat teszteléshez, dokumentációhoz és analitikához is. Tesztelésnél segíthet megmutatni, mely kritikus folyamatokhoz tartozik dokumentált tudás és hibamagyarázat. Dokumentációnál jelezheti, mely témák hiányosak. Analitikánál később láthatóvá teheti, hol akadnak el a felhasználók, mely súgók népszerűek, és mely Knowledge Unitok igényelnek jobb magyarázatot.

Ez a dokumentum fogalmi és architekturális specifikáció. Nem adatbázisterv, nem implementációs terv, és nem technikai függőségi térkép.

## Alapelv

A Knowledge Graph nem Laravel-, Vue- vagy MySQL-specifikus.

Nem technikai függőségeket modellez. Nem controller-service-repository függési gráf, nem komponensfa, nem migrációs kapcsolattérkép és nem adatbázis foreign key dokumentáció.

A Knowledge Graph üzleti és tudáskapcsolatokat modellez. Azt írja le, hogy egy felhasználói cél, üzleti folyamat, dokumentációs elem, hiba, jogosultság vagy tanulási lépés milyen más tudáselemekhez kapcsolódik.

Példa:

- A "Gyártás indítása" Knowledge Unit igényelheti a BOM, készlet, műveletsor és jogosultság megértését.
- A "Nincs elég készlet" hiba magyarázható készletfoglalással, raktári elérhetőséggel és beszerzési pótlással.
- Egy "Raktáros" szerepkör számára más tudáskapcsolatok fontosak, mint egy "Termelésvezető" számára.

## v1.0 fókusz

A v1.0 statikus, kézzel definiált, típusos kapcsolatokkal dolgozik.

v1.0-ban:

- nincs automatikus tanulás
- nincs gráfsúlyozás
- nincs önmódosító AI
- nincs automatikus ajánlórendszer
- nincs Knowledge Intelligence

A v1.0 célja a fogalmi térkép stabilizálása. A node és edge típusok legyenek érthetők, review-zhatók és kézzel karbantarthatók. A későbbi adatmodell, admin UI, AI retrieval vagy analitikai réteg ebből indulhat ki, de ez a dokumentum nem tervezi meg ezek implementációját.

## Hosszú távú irány

### v1.0: Static Knowledge Graph

A v1.0 kézzel definiált csomópontokat és típusos kapcsolatokat használ. A kapcsolatok célja a dokumentáció, Learning Center, oldalspecifikus súgó, hibakeresés és AI kontextus előkészítése.

### v2.x: Weighted Knowledge Graph

A v2.x lehetőséget adhat súlyozott kapcsolatokra. A súly azt jelezheti, hogy egy kapcsolat mennyire gyakori, mennyire hasznos, mennyire kritikus vagy milyen erősen kapcsolódik valós felhasználói viselkedéshez.

### Later: Knowledge Intelligence

Későbbi irányként a Knowledge Intelligence a gráf, a felhasználói események, hibák, súgóhasználat és tanulási útvonalak alapján tudásminőségi és oktatási következtetéseket adhat. Ez már nem v1.0 scope.

## Node típusok

### Knowledge Unit

Önálló, újrahasznosítható tudásegység. A Learning Center alapegysége, de projekt szinten is hivatkozható tudásforrás.

Használható dokumentáció, tooltip, onboarding, hibakeresés, FAQ, AI válasz és oktatási anyag alapjaként.

Példák: "Gyártás indítása", "BOM létrehozása", "Készlet feltöltése".

### Business Process

Üzleti folyamat vagy workflow, amely több lépésből, szerepkörből és előfeltételből áll.

Használható annak jelölésére, hogy egy Knowledge Unit mely nagyobb folyamat része.

Példák: gyártási rendelés előkészítése, készletbevételezés, minőségellenőrzési folyamat, megrendelés kiszállítása.

### Role

Szakmai vagy alkalmazásbeli szerepkör, amely meghatározhatja, milyen tudás releváns egy felhasználónak.

Használható szerepkörspecifikus onboardinghoz, oldalsúgóhoz és ajánlásokhoz.

Példák: Termelésvezető, Raktáros, Minőségellenőr, Adminisztrátor, Beszerző.

### Permission

Jogosultság vagy jogosultsági képesség, amely egy művelet végrehajtását szabályozza.

Használható annak magyarázatára, hogy egy Knowledge Unit mely műveletekhez kötött, és milyen jogosultsági hiba kapcsolódhat hozzá.

Példák: gyártás indítása, készletmozgás rögzítése, minőségellenőrzés jóváhagyása, felhasználó kezelése.

### Page

Felhasználói oldal vagy workflow-terület, ahol tudás megjelenhet.

Használható oldalspecifikus súgó, onboarding és kontextuális AI válasz kiválasztására.

Példák: terméklista, gyártási rendelés részletei, raktárkészlet oldal, minőségellenőrzési oldal.

### Menu

Navigációs belépési pont vagy menücsoport.

Használható annak jelölésére, hogy egy tudáselem mely navigációs területhez kapcsolódik.

Példák: Termelés, Készlet, Minőség, Beszerzés, Adminisztráció.

### Feature

Funkcionális képesség vagy termékfunkció, amely több oldalon vagy folyamatban is megjelenhet.

Használható funkciószintű dokumentációhoz és tesztelési lefedettségi gondolkodáshoz.

Példák: készletfoglalás, sorozatszám-kezelés, dokumentum AI review, kapacitástervezés.

### Error

Felhasználói vagy workflow hiba, amely magyarázatra és javítási javaslatra szorul.

Használható hibakereső, FAQ, AI válasz és ismétlődő elakadás elemzés alapjaként.

Példák: nincs elég készlet, hiányzó BOM, jogosultság hiánya, minőségügyi zárolás blokkol.

### Warning

Figyelmeztetés, amely nem feltétlenül blokkolja a műveletet, de üzleti vagy minőségi kockázatot jelez.

Használható proaktív súgóhoz, oktatáshoz és kockázati magyarázathoz.

Példák: alacsony készletszint, közelgő lejárat, bizonytalan AI dokumentumeredmény, késési kockázat.

### FAQ

Gyakori kérdés és válasz rövid, célzott formában.

Használható keresési találatként, Learning Center gyors válaszként vagy AI válaszforrásként.

Példák: "Miért nem tudom elindítani a gyártást?", "Miért nem látszik a készlet?", "Mit jelent a minőségügyi zárolás?"

### Example

Konkrét példa, amely megmutatja egy fogalom vagy folyamat gyakorlati alkalmazását.

Használható kezdő oktatásban, dokumentációban, tesztadat-gondolkodásban és AI magyarázatban.

Példák: egy termék BOM-jának létrehozása, készlet feltöltése egy raktárba, minőségellenőrzés rögzítése gyártás után.

### Document

Dokumentációs oldal, útmutató, specifikáció vagy üzleti dokumentum.

Használható hosszabb magyarázatok és hivatalos források kapcsolására.

Példák: Learning Center specifikáció, gyártási útmutató, minőségellenőrzési dokumentáció, projektkonvenciók.

### Video

Későbbi videós tananyag vagy demonstráció.

Használható oktatási tartalomként, különösen komplex workflow-k kezdőbarát bemutatásához.

Példák: "Gyártás indítása lépésről lépésre", "Raktárkészlet feltöltése", "BOM létrehozása".

### Screenshot

Képernyőkép vagy vizuális referencia.

Használható dokumentációban, onboardingban, hibakeresőben és oktatóanyagban.

Példák: előfeltételek panel, gyártási rendelés státusza, készletmozgás űrlap, minőségellenőrzési eredmény.

### Learning Path

Tudáselemekből álló bejárási útvonal egy szerepkör, modul vagy cél megtanulásához.

Használható onboardinghoz, betanításhoz és későbbi adaptív tanuláshoz.

Példák: Raktáros alapútvonal, Termelésvezető alapútvonal, Minőségellenőr onboarding.

### Assistance Level

Oldalanként vagy kontextusonként alkalmazott segítségi szint.

Használható annak meghatározására, hogy egy Knowledge Unit milyen részletességgel jelenjen meg.

Példák: Kezdő, Haladó, Profi.

## Edge típusok

### requires

Jelentés: az egyik node megértéséhez vagy végrehajtásához szükséges egy másik node.

Irány: a függő node-ból az előfeltétel felé.

Használat: előfeltételek, tanulási sorrend, műveleti blokkolók jelölésére.

Példa: "Gyártás indítása" `requires` "BOM létrehozása".

### creates

Jelentés: egy folyamat vagy művelet létrehoz egy másik üzleti elemet vagy állapotot.

Irány: a létrehozó node-ból a létrejövő node felé.

Használat: workflow eredmények és fogalmi következmények jelölésére.

Példa: "Raktárkészlet feltöltése" `creates` "Készletnövekedés".

### uses

Jelentés: egy node használ egy másik fogalmat, dokumentumot, oldalt vagy funkciót.

Irány: a használó node-ból a használt node felé.

Használat: folyamatok és funkciók tudásigényének jelölésére.

Példa: "Megrendelés kiszállítása" `uses` "Raktárkészlet".

### explains

Jelentés: egy node magyaráz egy hibát, figyelmeztetést, fogalmat vagy döntést.

Irány: a magyarázó node-ból a magyarázott node felé.

Használat: hibakereséshez, FAQ-hoz és AI válaszforrásokhoz.

Példa: "Készletfoglalás értelmezése" `explains` "Nincs elég elérhető készlet".

### belongs_to

Jelentés: egy node nagyobb csoporthoz, folyamathoz, menühöz vagy dokumentációs területhez tartozik.

Irány: a rész node-ból a szülő node felé.

Használat: navigációs, dokumentációs és workflow csoportosításhoz.

Példa: "BOM létrehozása" `belongs_to` "Termelés".

### implements

Jelentés: egy dokumentált funkció vagy oldal üzleti szinten megvalósít egy képességet.

Irány: a megvalósító node-ból a képesség vagy folyamat felé.

Használat: oldal, feature és üzleti folyamat közötti fogalmi kapcsolat jelölésére.

Példa: "Gyártási rendelés részletei oldal" `implements` "Gyártás indítása".

### visible_for

Jelentés: egy node adott szerepkör vagy célközönség számára releváns vagy látható.

Irány: a tartalom node-ból a Role vagy Assistance Level node felé.

Használat: szerepköralapú dokumentációhoz és onboardinghoz.

Példa: "Raktárkészlet feltöltése" `visible_for` "Raktáros".

### recommended_after

Jelentés: egy node ajánlott egy másik node megismerése után.

Irány: az ajánlott node-ból az előtte ajánlott node felé.

Használat: tanulási ajánlásokhoz és dokumentációs navigációhoz.

Példa: "Gyártás indítása" `recommended_after` "BOM létrehozása".

### next_step

Jelentés: egy node után természetes következő workflow vagy tanulási lépés következik.

Irány: az aktuális node-ból a következő node felé.

Használat: onboardinghoz, hibakereséshez és folyamatnavigációhoz.

Példa: "Gyártás indítása" `next_step` "Minőségellenőrzés rögzítése".

### previous_step

Jelentés: egy node előtt természetes megelőző lépés áll.

Irány: az aktuális node-ból az előző node felé.

Használat: visszalépési javaslatokhoz és előfeltétel-magyarázathoz.

Példa: "Megrendelés kiszállítása" `previous_step` "Készlet ellenőrzése".

### alternative_to

Jelentés: két node alternatív út vagy megközelítés ugyanarra a célra.

Irány: kétirányúként értelmezhető, de rögzíthető irányított élként is.

Használat: eltérő workflow-k, pótlási utak vagy oktatási formák jelölésére.

Példa: "Készlet pótlása beszerzéssel" `alternative_to` "Készlet pótlása belső áthelyezéssel".

### warning_for

Jelentés: egy Warning node egy folyamatra, oldalra vagy műveletre figyelmeztet.

Irány: a figyelmeztetésből az érintett node felé.

Használat: kockázati magyarázatokhoz és proaktív segítséghez.

Példa: "Alacsony készletszint" `warning_for` "Gyártás indítása".

### error_for

Jelentés: egy Error node egy folyamat vagy művelet tipikus hibája.

Irány: a hiba node-ból az érintett node felé.

Használat: hibakereső, FAQ és AI diagnosztikai kontextus előkészítéséhez.

Példa: "Hiányzó BOM" `error_for` "Gyártás indítása".

### references

Jelentés: egy node hivatkozik egy dokumentumra, példára, képernyőképre vagy más forrásra.

Irány: a hivatkozó node-ból a hivatkozott node felé.

Használat: dokumentációs források, képek, videók és példák kapcsolására.

Példa: "Gyártás indítása" `references` "Gyártási rendelés előfeltételek screenshot".

### related_to

Jelentés: laza szakmai kapcsolat két node között.

Irány: kétirányúként értelmezhető, de rögzíthető irányított élként is.

Használat: kapcsolódó témák, további olvasnivalók és AI kontextus bővítésére.

Példa: "Készlet feltöltése" `related_to` "Készletfoglalás".

### role_specific

Jelentés: egy tudáselem adott szerepkör számára speciális nézettel, hangsúllyal vagy lépéssel bír.

Irány: a tudáselem node-ból a Role node felé.

Használat: szerepkörfüggő onboardinghoz és asszisztenciához.

Példa: "Gyártás indítása" `role_specific` "Termelésvezető".

### permission_required

Jelentés: egy művelethez, oldalhoz vagy tudásegységhez kapcsolódó végrehajtási jogosultság szükséges.

Irány: a művelet vagy Knowledge Unit node-ból a Permission node felé.

Használat: jogosultsági hiba magyarázatához és kontextuális súgó szűréséhez.

Példa: "Minőségellenőrzés rögzítése" `permission_required` "quality.inspections.create".

## Példák

### Példa 1: Gyártás indítása

A "Gyártás indítása" Knowledge Unit egy gyártási rendelés végrehajtási állapotba helyezését magyarázza.

Kapcsolatok:

- "Gyártás indítása" `requires` "BOM létrehozása"
- "Gyártás indítása" `requires` "Készlet feltöltése"
- "Gyártás indítása" `requires` "Műveletsor kiválasztása"
- "Gyártás indítása" `requires` "Dolgozó vagy erőforrás rendelkezésre állása"
- "Gyártás indítása" `next_step` "Minőségellenőrzés rögzítése"
- "Hiányzó BOM" `error_for` "Gyártás indítása"
- "Gyártás indítása" `permission_required` "gyártás indítása jogosultság"

### Példa 2: Raktárkészlet feltöltése

A "Raktárkészlet feltöltése" Knowledge Unit azt írja le, hogyan kerül készlet egy raktárba ellenőrzött készletmozgáson keresztül.

Kapcsolatok:

- "Raktárkészlet feltöltése" `requires` "Raktár létrehozása"
- "Raktárkészlet feltöltése" `requires` "Termék létrehozása"
- "Raktárkészlet feltöltése" `creates` "Elérhető készlet"
- "Raktárkészlet feltöltése" `uses` "Készletmozgás"
- "Alacsony készletszint" `warning_for` "Raktárkészlet feltöltése"
- "Raktárkészlet feltöltése" `visible_for` "Raktáros"

### Példa 3: BOM létrehozása

A "BOM létrehozása" Knowledge Unit a termékhez tartozó anyagjegyzék fogalmi szerepét és létrehozási folyamatát magyarázza.

Kapcsolatok:

- "BOM létrehozása" `requires` "Termék létrehozása"
- "BOM létrehozása" `belongs_to` "Termelés"
- "BOM létrehozása" `recommended_after` "Termék létrehozása"
- "Gyártás indítása" `requires` "BOM létrehozása"
- "Hiányzó BOM" `explains` "BOM létrehozása"
- "BOM létrehozása" `role_specific` "Termelésvezető"

### Példa 4: Minőségellenőrzés rögzítése

A "Minőségellenőrzés rögzítése" Knowledge Unit azt írja le, hogyan kerül dokumentálásra egy ellenőrzési eredmény, és milyen következménye lehet a gyártásra vagy készletre.

Kapcsolatok:

- "Minőségellenőrzés rögzítése" `recommended_after` "Gyártás indítása"
- "Gyártás indítása" `next_step` "Minőségellenőrzés rögzítése"
- "Minőségellenőrzés rögzítése" `uses` "Minőségügyi státusz"
- "Minőségellenőrzés rögzítése" `creates` "Ellenőrzési eredmény"
- "Minőségügyi zárolás" `warning_for` "Minőségellenőrzés rögzítése"
- "Minőségellenőrzés rögzítése" `permission_required` "minőségellenőrzés rögzítése jogosultság"

### Példa 5: Megrendelés kiszállítása

A "Megrendelés kiszállítása" Knowledge Unit a vevői vagy értékesítési megrendelés kiszállítási folyamatát magyarázza.

Kapcsolatok:

- "Megrendelés kiszállítása" `requires` "Elérhető készlet"
- "Megrendelés kiszállítása" `requires` "Megrendelés jóváhagyása"
- "Megrendelés kiszállítása" `uses` "Raktárkészlet"
- "Megrendelés kiszállítása" `creates` "Kiszállítási esemény"
- "Nincs elég készlet" `error_for` "Megrendelés kiszállítása"
- "Megrendelés kiszállítása" `visible_for` "Raktáros"

## Learning Center kapcsolat

A Learning Center a Knowledge Graphot tudásnavigációs térként használhatja. Amikor a felhasználó egy oldalon áll, hibát kap, onboardingot követ vagy segítséget kér, a Learning Center a gráf alapján választhat kapcsolódó tudáselemeket.

Kapcsolódó tudáselemek kiválasztásánál figyelembe vehető:

- aktuális oldal vagy workflow
- felhasználói szerepkör
- szükséges jogosultságok
- aktuális assistance level
- hiba vagy figyelmeztetés típusa
- Knowledge Unit előfeltételei
- következő vagy előző tanulási lépések

Onboarding esetén a Learning Center a gráfból választhat kezdőbarát útvonalat. Egy Raktáros például indulhat raktár, termék, készletmozgás és készletellenőrzés témákkal, míg egy Termelésvezető számára a termék, BOM, műveletsor és gyártásindítás lehet elsődleges.

Oldalankénti segítségnél a Page, Feature, Error, Warning, Role és Assistance Level node-ok segíthetnek eldönteni, hogy mely Knowledge Unit jelenjen meg rövid súgóként, tooltipként, hibamagyarázatként vagy teljes dokumentációs hivatkozásként.

Az Adaptive Learning későbbi verziója a gráfot bejárási térként használhatja. A v1.0 még nem automatikus ajánlórendszer, de a kapcsolatok előkészítik, hogy később a rendszer tanulási hiányokra, ismétlődő hibákra vagy szerepkörspecifikus célokra reagáljon.

## AI kapcsolat

Az AI a Knowledge Graphot kontrollált kontextusforrásként használhatja. A gráf segíthet kiválasztani, mely Knowledge Unit, FAQ, Error, Warning, Page, Permission vagy Learning Path releváns egy kérdéshez.

Kontextusérzékeny válaszoknál az AI nem pusztán kulcsszavak alapján keres, hanem figyelembe veheti a kapcsolatok típusát is. Egy "miért nem tudom elindítani a gyártást?" kérdésnél például a gráf megmutathatja a BOM, készlet, műveletsor, dolgozó, minőségügyi és jogosultsági előfeltételeket.

A hallucináció csökkentését az segíti, hogy az AI csak publikált, jogosultság szerint elérhető, jóváhagyott tudáselemekből és kapcsolatokból építhet választ. Ha a gráfban nincs támogatott kapcsolat vagy forrás, az AI-nak ezt korlátként kell kezelnie.

Hibakeresésnél az AI a `error_for`, `explains`, `requires` és `permission_required` kapcsolatok alapján javasolhat ellenőrzési sorrendet.

Következő lépés ajánlásánál az AI a `next_step`, `recommended_after`, `previous_step` és `role_specific` kapcsolatokra támaszkodhat.

Az AI v1.0-ban olvashatja a gráfot, de nem módosíthatja automatikusan. Nem hozhat létre új node-ot, nem írhat át kapcsolatot, nem súlyozhat kapcsolatot végleges adatként, és nem dönthet jogosultságról vagy üzleti műveletről.

## Knowledge Graph vs Knowledge Unit

A Knowledge Unit a tudásegység. Egy önállóan értelmezhető fogalom, művelet, szabály, hiba vagy tanulási elem kanonikus leírása.

A Knowledge Graph a tudásegységek és kapcsolódó elemek hálózata. Megmutatja, hogy a Knowledge Unitok hogyan kapcsolódnak üzleti folyamatokhoz, szerepkörökhöz, jogosultságokhoz, oldalakhoz, hibákhoz, dokumentumokhoz, példákhoz és tanulási útvonalakhoz.

Egyszerűen:

- Knowledge Unit: "mit kell tudni?"
- Knowledge Graph: "mi mihez kapcsolódik?"

## Knowledge Graph vs Learning Path

A Learning Path egy bejárási útvonal. Egy szerepkör, modul vagy cél megtanulásához kiválasztott sorrendben vezet végig tudáselemeken.

A Knowledge Graph a teljes térkép. Több lehetséges útvonalat, kapcsolódást, előfeltételt és alternatívát tartalmaz.

Egy Learning Path a gráf egy kiválasztott útvonala. Például a Termelésvezető onboarding útvonala bejárhatja a termék, BOM, műveletsor, gyártás indítása és minőségellenőrzés csomópontokat, de a teljes gráf ennél sokkal több kapcsolatot tartalmaz.

## Weighted Knowledge Graph későbbi lehetőség

A súlyozott kapcsolat azt jelenti, hogy egy edge nemcsak létezik, hanem erősséggel, gyakorisággal, relevanciával vagy kockázati értékkel is rendelkezik.

Később a súly számolható lehet például ezekből:

- gyakran együtt használt tudáselemek
- gyakori hibák
- sokszor megnyitott súgók
- gyakori útvonalak
- elakadási pontok
- szerepkörönként eltérő súgóhasználat
- ismételt validációs hibák
- elutasított vagy elfogadott tanulási javaslatok

Ez nem v1.0 scope, mert a súlyozás már megbízható eseményadatot, analitikát, adatvédelmi döntéseket és jól definiált értelmezést igényel. Túl korai bevezetése hamis pontosságot adhatna.

Később a Weighted Knowledge Graph segíthet:

- jobb kapcsolódó témákat ajánlani
- felismerni gyakori elakadásokat
- szerepkörönként finomítani az onboardingot
- priorizálni dokumentációs javításokat
- megmutatni, mely hibákhoz kell jobb magyarázat
- AI válaszoknál erősebb forrásokat előnyben részesíteni

## Knowledge Intelligence későbbi lehetőség

A Knowledge Intelligence a Knowledge Graph, súgóhasználat, tanulási események, hibák és felhasználói útvonalak elemzésére épülő későbbi képesség lehet.

Nem egyszerű gráfmegjelenítés, hanem tudásminőségi és oktatási visszacsatolás. Arra adhat választ, hogy hol gyenge a dokumentáció, hol kevés az onboarding, és hol akadnak el rendszeresen a felhasználók.

Lehetséges kérdések:

- Hol akadnak el a felhasználók?
- Mely dokumentáció nem elég jó?
- Mely modulhoz kell több oktatás?
- Mely Knowledge Unit túl bonyolult?
- Hol kell videó vagy interaktív segítség?
- Mely hibák ismétlődnek ugyanazon szerepköröknél?
- Mely tanulási útvonalak túl hosszúak vagy hiányosak?

Ez későbbi irány, mert analitikai adatokat, governance szabályokat és jól definiált mérőszámokat igényel.

## Határok

A Knowledge Graph nem helyettesíti az adatbázis kapcsolatokat. Az adatbázis integritását továbbra is a MySQL, migrációk, modellek, validációk és üzleti szabályok kezelik.

A Knowledge Graph nem helyettesíti a jogosultsági rendszert. A jogosultsági döntést továbbra is az alkalmazás végzi. A gráf csak magyarázhatja, hogy mely tudás vagy művelet milyen jogosultsághoz kapcsolódik.

A Knowledge Graph nem helyettesíti a validációt. A validáció továbbra is az alkalmazás felelőssége. A gráf a hibák értelmezését és javítási útmutatását segítheti.

A Knowledge Graph nem technikai dependency graph. Nem controller, service, repository, komponens, package vagy adatbázis-függőségek ábrázolására szolgál.

A Knowledge Graph nem AI döntéshozó rendszer. Az AI használhatja olvasási és kontextusválasztási segítségként, de nem módosíthatja automatikusan, és nem dönthet üzleti műveletekről.

## Nyitott kérdések

- Hol legyen a Knowledge Graph elsődleges forrása?
- Markdownból vagy adatbázisból induljon-e a v1.0?
- Hogyan történjen a node és edge változások review-ja?
- Kik szerkeszthetik a Knowledge Graph tartalmát?
- Milyen node típusok kerüljenek ténylegesen v1.0-ba?
- Milyen edge típusok kerüljenek ténylegesen v1.0-ba?
- Kell-e vizuális gráfnézet szerkesztőknek vagy adminoknak?
- Hogyan kapcsolódjon az AI-hoz: retrieval indexként, kontextusszűrőként vagy választervezési térként?
- Milyen státusz szükséges ahhoz, hogy egy gráfkapcsolat AI által olvasható legyen?
- Hogyan legyen kezelve a projekt szintű Knowledge Graph és a Learning Center specifikus nézet szinkronja?

## Kapcsolódó témák

- [Projekt architektúra](../architecture.md)
- [Projektkonvenciók](project-conventions.md)
- [Learning Center specifikáció](../specifications/learning-center/README.md)
- [Learning Center Knowledge Graph](../specifications/learning-center/knowledge-graph.md)
- [Knowledge Unit Specification v1.0](../specifications/learning-center/knowledge-unit.md)
- [Learning Center döntések](../specifications/learning-center/decisions.md)
