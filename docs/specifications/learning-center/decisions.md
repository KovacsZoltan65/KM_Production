# Learning Center döntések

## Cél

Ez a dokumentum ADR jelleggel rögzíti a Learning Center v1.0 döntéseit. Minden döntés tartalmazza az aktuális kontextust, az elfogadott irányt, a következményeket és a nyitott kérdéseket.

## Tervezett tartalom

- Stabil architekturális döntések.
- Termékterminológiai döntések.
- Asszisztencia-viselkedési döntések.
- Dokumentációs és tudásforrás döntések.
- Későbbi, implementáció előtt tisztázandó döntések.

## ADR-001: Learning Center modulnév elfogadása

### Státusz

Accepted

### Kontextus

A modulnak stabil névre van szüksége, amely beépített tanulást, segítséget, onboardingot és tudásmenedzsmentet kommunikál anélkül, hogy külső kézikönyvnek tűnne.

### Döntés

A modul neve Learning Center.

### Következmények

A nevet következetesen kell használni dokumentációban, navigációban, jogosultságokban, route nevekben és UI szövegekben.

### Nyitott kérdések

- A magyar UI címke "Learning Center", "Tudásközpont" legyen, vagy locale szerint konfigurálható?

## ADR-002: Hibrid Markdown + adatbázis architektúra

### Státusz

Accepted

### Kontextus

A rendszernek hosszú, verziókövetett dokumentációra és dinamikus kontextuális asszisztenciára is szüksége van.

### Döntés

Hibrid megközelítés: Markdown a hosszabb dokumentációhoz, adatbázisrekordok a tippekhez, hibákhoz, metaadatokhoz, progresshez és asszisztencia-viselkedéshez.

### Következmények

A Markdown Gitben review-zható marad. Az adatbázis-alapú asszisztencia oldal, jogosultság, felhasználói előrehaladás és kontextus alapján szűrhető.

### Nyitott kérdések

- Szerkeszthető legyen-e a Markdown admin UI-ból v1.0-ban?
- Hogyan szinkronizáljuk a Markdown publikálási státuszt az adatbázis metaadataival?

## ADR-003: Oldalankénti asszisztenciaszint

### Státusz

Accepted

### Kontextus

Egy felhasználó lehet tapasztalt az egyik modulban és kezdő egy másikban. Egy globális asszisztenciaszint túl durva lenne.

### Döntés

Minden támogatott oldalnak felhasználónként saját asszisztenciaszintje van.

### Következmények

Az adatmodellnek tárolnia kell a felhasználó- és oldalspecifikus beállításokat. A UI-nak mutatnia kell az aktuális oldalszintet és engednie kell a módosítást.

### Nyitott kérdések

- Mi legyen az első támogatott oldalregiszter v1.0-ban?
- A page key-k egy az egyben route-okhoz, vagy tágabb workflow területekhez kapcsolódjanak?

## ADR-004: Manuális és automatikus szintkezelés kombinálása

### Státusz

Accepted

### Kontextus

A rendszer képes lehet ismétlődő hibákat és magabiztosságot érzékelni, de az automatikus változtatások zavarhatják a felhasználókat vagy megszakíthatják a munkát.

### Döntés

A felhasználók manuálisan módosíthatják az oldalszintű asszisztenciát. A rendszer javasolhat automatikus visszalépést vagy csökkentést, de a változtatáshoz felhasználói jóváhagyás szükséges.

### Következmények

Az ajánlási modellnek támogatnia kell a függő javaslatokat. A UX biztosítson elfogadás, halasztás és elutasítás műveletet.

### Nyitott kérdések

- Mely jelek indítanak visszalépési javaslatot v1.0-ban?
- Milyen gyakran ismételhet meg a rendszer egy elutasított javaslatot?

## ADR-005: Élő dokumentáció támogatása

### Státusz

Accepted

### Kontextus

A statikus súgó nem elég egy gyártási rendszerben, ahol a jogosultságok, adatállapot, előfeltételek és workflow állapot határozzák meg, mit tehet a felhasználó.

### Döntés

A Learning Center dokumentáció legyen élő, kontextusfüggő és oldalhoz kötött.

### Következmények

A Context Engine biztosítson route, jogosultsági, entitás, validációs és előfeltétel kontextust. A súgótartalmat megjelenítés előtt szűrni kell.

### Nyitott kérdések

- Mely kontextusmezők adhatók át később biztonságosan AI-nak?
- Mely modulok igényelnek elsőként élő dokumentációt?

## ADR-006: Knowledge Engine, Learning Engine, Context Engine felosztás

### Státusz

Accepted

### Kontextus

A Learning Center tartalomkezelést, tanulási viselkedést és futásidejű kontextust kombinál. Ezek összekeverése nehezen karbantartható modult eredményezne.

### Döntés

A modult koncepcionálisan Knowledge Engine, Learning Engine és Context Engine részekre bontjuk.

### Következmények

Az implementáció külön kezelheti a tartalom-visszakeresést, progress/ajánlási logikát és kontextusválasztást. Ez támogatja a későbbi AI integrációt és tesztelést.

### Nyitott kérdések

- Ezek az engine-ek már v1.0-ban külön service-ek legyenek, vagy maradjanak koncepcionális határok, amíg az implementáció nem nő meg?

## ADR-007: Knowledge Unit mint alapegység

### Státusz

Accepted

### Kontextus

A Learning Center több megjelenési formát támogat: hosszú dokumentációt, oldalspecifikus súgót, tooltipet, onboardingot, hibamagyarázatot, FAQ-t, oktatási anyagot és későbbi AI válaszokat. Ha ezek különálló tartalomként készülnek, gyorsan duplikáció és ellentmondás alakulhat ki.

A KM_Production gyártási domainje miatt ugyanaz a tudás több workflow-ban is megjelenhet. A gyártás indítása például kapcsolódhat BOM-hoz, műveleti sorrendhez, készlethez, minőségügyi zároláshoz, jogosultsághoz és traceability követelményekhez. A Learning Centernek ezért nem képernyő-, komponens- vagy táblaközpontú tudásmodellre, hanem újrahasznosítható fogalmi egységekre van szüksége.

### Döntés

A Learning Center alapegysége a Knowledge Unit. Ez fogalmi tudásmag, amely több csatornán és több részletességi szinten újrafelhasználható.

A Knowledge Unit nem oldal, nem Vue komponens, nem Laravel controller, nem tooltip önmagában, nem FAQ sor önmagában, nem dokumentációs fejezet önmagában és nem adatbázistábla. Egy Knowledge Unit önállóan értelmezhető tudásegység, amelyből dokumentációs, súgó-, onboarding-, hibakereső-, FAQ-, oktatási és AI nézetek származhatnak.

A Knowledge Unit részletes fogalmi modelljét a [Knowledge Unit Specification v1.0](knowledge-unit.md) rögzíti.

### Következmények

A dokumentációt, tanulási útvonalakat, tooltippeket, oldalspecifikus súgókat, hibamagyarázatokat, FAQ válaszokat és későbbi AI válaszforrásokat úgy kell tervezni, hogy visszavezethetők legyenek stabil Knowledge Unitokra. Ez támogatja a tartalmi review-t, verziózást, lokalizációt, Knowledge Graph kapcsolatokat és AI-ready működést.

A Knowledge Unitok tervezésekor fogalmi szinten rögzíteni kell legalább a célt, magyarázatot, előfeltételeket, lépéseket, gyakori hibákat, javítási javaslatokat, kapcsolódó tudáselemeket, érintett szerepköröket, érintett oldalakat, szükséges jogosultságokat, ajánlott tudásszintet, AI kulcsszavakat, lokalizációt, verziót és státuszt. Ezek nem jelentenek automatikus adatbázis- vagy Eloquent modelltervet.

### Nyitott kérdések

- v1.0-ban a Knowledge Unit csak dokumentációs fogalom legyen, vagy már adatbázisban is megjelenjen?
- Ki legyen egy Knowledge Unit szakmai tulajdonosa?
- Mely Knowledge Unit mezők kötelezőek v1.0-ban?
- Kell-e Knowledge Atom már v1.0-ban, vagy maradjon későbbi finomítás?

## ADR-008: Knowledge Graph szemlélet

### Státusz

Accepted

### Kontextus

A tudásegységek nem elszigetelten léteznek. Egy művelet kapcsolódhat előfeltételekhez, hibákhoz, szerepkörökhöz, jogosultságokhoz, tanulási útvonalakhoz és más fogalmakhoz.

A Knowledge Graph eredetileg a Learning Center tudásnavigációs igényeként jelent meg, de a kapcsolatok projekt szintűek. A gyártási, készletkezelési, minőségügyi, beszerzési, dokumentációs, jogosultsági, hibakezelési, AI és későbbi analitikai igények ugyanarra a tudáshálóra támaszkodhatnak.

### Döntés

A Knowledge Graph szemléletet projekt szintű architekturális elvvé emeljük. A Knowledge Graph nem kizárólag a Learning Center része, hanem a KM_Production teljes tudásrétegének fogalmi hálózata.

A Learning Center továbbra is a Knowledge Graph egyik elsődleges felhasználója. A Knowledge Unitok és kapcsolódó elemek közötti kapcsolatok képezik az ajánlások, tanulási útvonalak, kontextuális súgó, hibakeresés és AI navigáció alapját.

A projekt szintű fogalmi specifikációt a [KM_Production Knowledge Graph Specification](../../architecture/knowledge-graph.md) rögzíti.

### Következmények

A tartalom tervezésekor nem elég önálló dokumentumokat írni. Rögzíteni kell a kapcsolódásokat, előfeltételeket, következő lépéseket, szerepkörfüggő relevanciát, jogosultsági kapcsolatokat és hibaösszefüggéseket is.

A Learning Center specifikus Knowledge Graph dokumentum modulnézet marad. A projekt szintű architektúra határozza meg, hogy a Knowledge Graph nem Laravel-, Vue- vagy MySQL-specifikus, nem technikai dependency graph, hanem üzleti és tudáskapcsolatokat leíró architekturális réteg.

v1.0-ban a Knowledge Graph statikus, kézzel definiált, típusos kapcsolatokkal dolgozik. Nincs automatikus tanulás, gráfsúlyozás, önmódosító AI, automatikus ajánlórendszer vagy Knowledge Intelligence.

### Nyitott kérdések

- Milyen kapcsolattípusok kerüljenek be v1.0-ba?
- Kell-e vizuális gráfnézet admin vagy szerkesztői felületre?
- Hol legyen a Knowledge Graph elsődleges forrása: Markdownban, adatbázisban vagy hibrid formában?
- Hogyan legyen kezelve a projekt szintű gráf és a Learning Center modulnézet szinkronja?

## ADR-009: Context Engine

### Státusz

Accepted

### Kontextus

A Learning Center csak akkor hasznos, ha az aktuális oldal, felhasználó, szerepkör, jogosultság, művelet, rendszerállapot, előfeltétel és tudásszint alapján tud releváns segítséget választani.

### Döntés

A Context Engine legyen a Learning Center helyzetérzékelő rétege. Feladata a releváns, jogosultságszűrt és tudásszinthez igazított segítség kiválasztása.

### Következmények

A Context Engine nem tartalmazhat üzleti logikát, de értelmeznie kell az üzleti ellenőrzések eredményét. AI integráció esetén csak szűrt, jogosult kontextust adhat tovább.

### Nyitott kérdések

- Mely kontextusmezők szükségesek v1.0-ban?
- A Context Engine külön backend service legyen, vagy frontend és backend együttműködő rétege?

## ADR-010: Vision First tervezési megközelítés

### Státusz

Accepted

### Kontextus

A Learning Center hosszú távú tudásinfrastruktúra, nem egyszeri UI feature. A túl korai technikai részletezés könnyen beszűkítheti a modult, mielőtt a termékcélok és alapfogalmak stabilizálódnának.

### Döntés

A Learning Center tervezése vision first megközelítést követ. Először a termékvíziót, fogalmi modellt, tudásegységeket és kontextuális működést kell stabilizálni, majd ezekből következhet az adatmodell és implementáció.

### Következmények

A specifikációk nem indulhatnak pusztán adatbázistáblákból vagy képernyőkből. Az implementációs döntéseknek visszavezethetőknek kell lenniük a vízióra, Knowledge Unit modellre, Knowledge Graph szemléletre és Context Engine felelősségre.

### Nyitott kérdések

- Milyen minimum specifikációs készültség szükséges az implementáció megkezdése előtt?
- Ki hagyja jóvá a víziót és a fogalmi modellt?

## ADR-011: Projekt nyelvi és lokalizációs konvenciók

### Státusz

Accepted

### Kontextus

A Learning Center dokumentációs munkája során pontosítani kellett, milyen nyelven készüljön a projektkód, a dokumentáció, a kommentek, a felhasználói felület és a hibaüzenetek. A KM_Production egyszerre használ nemzetközi fejlesztői ökoszisztémát és magyar üzleti, gyártási, projektoldali dokumentációt.

### Döntés

A projekt nyelvi és lokalizációs konvenciói:

- a kód angol nyelvű
- a dokumentáció és a magyarázó komment magyar nyelvű
- a felhasználói felület lokalizált
- a fejlesztői log angol nyelvű
- a felhasználói üzenet lokalizált

### Következmények

Az alkalmazáskódban azonosítók, route-ok, config kulcsok, adatbázisnevek és tesztnevek angolul maradnak. Az emberi magyarázatok, specifikációk, ADR-ek és projektjegyzetek magyarul készülnek. Vue komponensekben és backend válaszokban nem maradhat hardcode-olt felhasználói szöveg; ezeknek lokalizációs kulcsból kell érkezniük.

Ez a döntés csökkenti a kevert nyelvű kódbázis kockázatát, miközben a projekt üzleti dokumentációja magyarul könnyebben validálható.

### Nyitott kérdések

- Mikor és milyen ütemben érdemes a régebbi angol dokumentációkat magyarítani?
- Kell-e külön checklist a lokalizált felhasználói üzenetek review-jához?
- Milyen nyelvi szabály vonatkozzon a külső ügyfeleknek készülő release note-okra?

## ADR-012: Knowledge Unit fogalmi modell elfogadása

### Státusz

Accepted

### Kontextus

Az ADR-007 elfogadta, hogy a Learning Center alapegysége a Knowledge Unit. A következő specifikációs szinthez tisztázni kellett, hogy mit tartalmazzon egy Knowledge Unit fogalmi szinten, hogyan jelenhet meg különböző csatornákban, milyen életciklusa legyen, hogyan kezelje a verziózást, és milyen határokat kell tartani az implementációval szemben.

Fontos tervezési szempont, hogy ez a döntés még nem adatbázisterv, nem Eloquent modellek terve és nem migrációs terv. A cél a fogalmi modell stabilizálása, amelyből később adatmodell, admin UI, Knowledge Graph és AI integráció tervezhető.

### Döntés

Elfogadjuk a [Knowledge Unit Specification v1.0](knowledge-unit.md) fogalmi modellt.

A Knowledge Unit a Learning Center kanonikus, újrahasznosítható tudásegysége. Tartalmazhat címet, célt, részletes magyarázatot, előfeltételeket, lépéseket, példákat, gyakori hibákat, javítási javaslatokat, tippeket, kapcsolódó tudáselemeket, érintett szerepköröket, érintett oldalakat, szükséges jogosultságokat, ajánlott tudásszintet, AI kulcsszavakat, lokalizációs információt, verziót és státuszt.

A Knowledge Unit több nézetben használható: dokumentációban, oldalspecifikus súgóban, tooltipként, onboarding lépésként, hibakeresőben, FAQ-ban, AI válaszként, oktatási anyagban és későbbi videós tananyagban.

A Knowledge Unit életciklusa fogalmi szinten: Draft, Review, Approved, Published, Deprecated, Archived.

### Következmények

A Learning Center további specifikációit ehhez a fogalmi modellhez kell igazítani. Az adatmodell, szerkesztői felület, publikálási workflow és AI integráció tervezésekor külön dönteni kell arról, hogy a fogalmi elemek közül melyek kerülnek v1.0 implementációba.

A Knowledge Unitok nem kerülhetik meg a jogosultsági rendszert, validációt, üzleti logikát, készletmozgási szabályokat, traceability követelményeket vagy auditálást. Ezek magyarázatára és oktatására szolgálnak, nem végrehajtására.

A Knowledge Atom lehetősége nyitva marad későbbi finomításként, de v1.0-ban nem tekintjük kötelező alapegységnek.

### Nyitott kérdések

- Markdown vagy adatbázis legyen-e az elsődleges Knowledge Unit forrás v1.0-ban?
- Mely fogalmi mezők legyenek kötelezőek az első implementációban?
- Legyen-e admin UI a Knowledge Unitok szerkesztésére?
- Hogyan történjen a szakmai review és publikálás?
- Milyen státusz szükséges ahhoz, hogy egy Knowledge Unit AI válaszforrás lehessen?
- Kell-e Knowledge Atom már az első implementációs körben?

## ADR-013: Knowledge Graph projekt szintű architektúra

### Státusz

Accepted

### Kontextus

A Knowledge Graph kapcsolatai túlmutatnak a Learning Center modulon. A KM_Production tudása összeköti a gyártási folyamatokat, készletmozgásokat, minőségellenőrzést, megrendeléseket, szerepköröket, jogosultságokat, oldalakat, hibákat, figyelmeztetéseket, dokumentációt, tanulási útvonalakat és későbbi AI asszisztenciát.

Ha a Knowledge Graph kizárólag Learning Center almodulként lenne kezelve, később nehezebben lenne használható projekt szintű AI kontextushoz, dokumentációs lefedettséghez, hibakereséshez, tesztelési gondolkodáshoz és analitikához.

### Döntés

Elfogadjuk a [KM_Production Knowledge Graph Specification](../../architecture/knowledge-graph.md) projekt szintű architekturális specifikációt.

A Knowledge Graph a teljes KM_Production tudásrétege. Nem Laravel-, Vue- vagy MySQL-specifikus, nem technikai függőségeket modellez, hanem üzleti és tudáskapcsolatokat.

A v1.0 statikus, kézzel definiált, típusos node és edge kapcsolatokkal dolgozik. A v1.0 nem tartalmaz automatikus tanulást, gráfsúlyozást, önmódosító AI-t, automatikus ajánlórendszert vagy Knowledge Intelligence réteget.

### Következmények

A Learning Center a projekt szintű Knowledge Graph egyik felhasználója és megjelenítője, nem kizárólagos tulajdonosa. A Learning Center dokumentumai hivatkozzanak a projekt szintű architektúrára, amikor a teljes KM_Production tudáshálóról van szó.

Az AI asszisztens később olvashatja a gráfot kontextusválasztáshoz és válaszforrások kiválasztásához, de v1.0-ban nem módosíthatja automatikusan.

A Knowledge Graph nem helyettesíti az adatbázis kapcsolatokat, jogosultsági rendszert, validációt, üzleti logikát, auditálást vagy technikai architektúrát. Ezekhez magyarázó és navigációs rétegként kapcsolódhat.

### Nyitott kérdések

- Hol legyen a Knowledge Graph elsődleges forrása v1.0-ban?
- Markdownból vagy adatbázisból induljon-e?
- Kik szerkeszthetik és review-zhatják a node és edge kapcsolatokat?
- Mely node és edge típusok kerüljenek ténylegesen v1.0 scope-ba?
- Kell-e vizuális gráfnézet admin vagy szerkesztői felületre?
- Hogyan kapcsolódjon az AI-hoz: retrieval indexként, kontextusszűrőként vagy választervezési térként?
- Milyen szabályok alapján válhat egy gráfkapcsolat AI által olvasható forrássá?

## Kapcsolódó témák

- [Vízió](vision.md)
- [Architektúra](architecture.md)
- [Projektkonvenciók](../../architecture/project-conventions.md)
- [Projekt szintű Knowledge Graph](../../architecture/knowledge-graph.md)
- [Knowledge Unit](knowledge-unit.md)
- [Knowledge Graph](knowledge-graph.md)
- [Knowledge Engine](knowledge-engine.md)
- [Learning Engine](learning-engine.md)
- [Context Engine](context-engine.md)
