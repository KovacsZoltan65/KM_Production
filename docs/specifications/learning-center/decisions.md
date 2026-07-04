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

A Learning Center több megjelenési formát támogat: hosszú dokumentációt, tooltipet, onboardingot, hibamagyarázatot, FAQ-t és későbbi AI válaszokat. Ha ezek különálló tartalomként készülnek, gyorsan duplikáció és ellentmondás alakulhat ki.

### Döntés

A Learning Center alapegysége a Knowledge Unit. Ez fogalmi tudásmag, amely több csatornán és több részletességi szinten újrafelhasználható.

### Következmények

A dokumentációt, tanulási útvonalakat, tooltippeket és hibamagyarázatokat úgy kell tervezni, hogy visszavezethetők legyenek stabil Knowledge Unitokra. Ez támogatja a tartalmi review-t, verziózást, lokalizációt és AI-ready működést.

### Nyitott kérdések

- v1.0-ban a Knowledge Unit csak dokumentációs fogalom legyen, vagy már adatbázisban is megjelenjen?
- Ki legyen egy Knowledge Unit szakmai tulajdonosa?

## ADR-008: Knowledge Graph szemlélet

### Státusz

Accepted

### Kontextus

A tudásegységek nem elszigetelten léteznek. Egy művelet kapcsolódhat előfeltételekhez, hibákhoz, szerepkörökhöz, jogosultságokhoz, tanulási útvonalakhoz és más fogalmakhoz.

### Döntés

A Learning Center tudását gráfként kezeljük. A Knowledge Unitok és kapcsolódó elemek közötti kapcsolatok képezik az ajánlások, tanulási útvonalak, kontextuális súgó és AI navigáció alapját.

### Következmények

A tartalom tervezésekor nem elég önálló dokumentumokat írni. Rögzíteni kell a kapcsolódásokat, előfeltételeket, következő lépéseket és relevancia-szabályokat is.

### Nyitott kérdések

- Milyen kapcsolattípusok kerüljenek be v1.0-ba?
- Kell-e vizuális gráfnézet admin vagy szerkesztői felületre?

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

## Kapcsolódó témák

- [Vízió](vision.md)
- [Architektúra](architecture.md)
- [Projektkonvenciók](../../architecture/project-conventions.md)
- [Knowledge Unit](knowledge-unit.md)
- [Knowledge Graph](knowledge-graph.md)
- [Knowledge Engine](knowledge-engine.md)
- [Learning Engine](learning-engine.md)
- [Context Engine](context-engine.md)
