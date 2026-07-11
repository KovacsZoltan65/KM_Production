# Jövőbeni ötletek

Státusz: Élő dokumentum
Hatókör: Learning Center v1.0-n túli ötletek
Tulajdonos: TBD

## Cél

Ez a dokumentum a Learning Center hosszú távú ötleteit gyűjti. Ötlet-inkubátor, nem roadmap, nem backlog és nem kötelező megvalósítási lista. Az itt szereplő ötletek később értékesek lehetnek, de önmagukban nem jelentenek fejlesztési kötelezettséget sem a Learning Center v1.0-ra, sem későbbi verziókra.

A cél az ígéretes koncepciók megőrzése még azelőtt, hogy teljes specifikáció készülne belőlük. Egyes ötletek később roadmapbe, ADR-be, implementációs specifikációba vagy külön discovery dokumentumba kerülhetnek.

## Szabályok

- Egy ötlet bekerülése nem jelent prioritást.
- Az ötletek később termék-, UX-, technikai és üzleti review után kerülhetnek roadmapbe.
- Az ötleteket időről időre felül kell vizsgálni, majd pontosítani, szétbontani, archiválni vagy előléptetni.
- A túl nagy ötletekből később külön specifikáció készülhet.
- Az ötletek nem bővítik automatikusan a Learning Center v1.0 scope-ját.
- Az ötletek maradjanak érthetőek termék-, design-, engineering- és projektstakeholderek számára is.

## 1. Élő dokumentáció

### Rövid leírás

Dokumentáció, amely az aktuális oldal, felhasználói jogosultságok, entitásállapot, hiányzó előfeltételek és közelmúltbeli felhasználói műveletek alapján változik.

### Milyen problémát old meg

A statikus dokumentáció gyakran az általános szabályt magyarázza el, de nem azt, hogy az adott felhasználó éppen most miért nem tud végrehajtani egy műveletet.

### Miért lehet értékes

Csökkentheti a support igényeket, és az összetett gyártási workflow-kat közvetlenül a munkafolyamatban teszi érthetőbbé.

### Lehetséges nehézségek

Megbízható kontextusmodell, biztonságos jogosultsági szűrés és óvatos UX szükséges, hogy a dinamikus üzenetek ne váljanak zajossá.

### Javasolt időtáv

v1.x

## 2. Oldalankénti asszisztenciaszintek

### Rövid leírás

Minden támogatott oldal saját asszisztenciaszinttel rendelkezik felhasználónként.

### Milyen problémát old meg

A globális tapasztalati szint túl általános. Egy felhasználó lehet erős készletkezelésben, de kezdő kapacitástervezésben.

### Miért lehet értékes

Személyre szabottabbá teszi a segítséget anélkül, hogy azt feltételezné, a felhasználó mindenhol kezdő vagy profi.

### Lehetséges nehézségek

Oldalregiszter, profilkezelés, alapértelmezési szabályok és minden támogatott oldalon egyértelmű UI jelzés szükséges.

### Javasolt időtáv

v1.x

## 3. Automatikus asszisztencia-alkalmazkodás

### Rövid leírás

A rendszer ismétlődő hibák, sikeres műveletek, súgóhasználat és magabiztossági jelek alapján javasolja a segítség intenzitásának növelését vagy csökkentését.

### Milyen problémát old meg

A felhasználók nem mindig tudják, mikor lenne szükségük több segítségre, vagy mikor csökkenthető biztonságosan az útmutatás.

### Miért lehet értékes

A Learning Center reszponzívabbnak érződik, és egyszerre csökkentheti az új és tapasztalt felhasználók súrlódását.

### Lehetséges nehézségek

A rendszer nem lehet tolakodó. Minden automatikus változtatáshoz felhasználói jóváhagyás szükséges.

### Javasolt időtáv

v1.x

## 4. Tanulási útvonalak

### Rövid leírás

Szerepkörorientált tanulási útvonalak Raktáros, Beszerző, Termelésvezető, Minőségellenőr, Adminisztrátor és Ügyvezető számára.

### Milyen problémát old meg

A felhasználóknak eltérő tudásra van szükségük szerepkörük és napi feladataik alapján.

### Miért lehet értékes

Rövidítheti az onboardingot és következetesebb csapatképzést tesz lehetővé.

### Lehetséges nehézségek

Az útvonalakat karban kell tartani workflow-változások esetén. Egyes felhasználók több szerepkört is betölthetnek.

### Javasolt időtáv

v1.x

## 5. Mentor mód

### Rövid leírás

Olyan mód, amelyben egy tapasztalt felhasználó láthatja egy másik felhasználó tanulási előrehaladását, és témákat vagy útvonalakat javasolhat neki.

### Milyen problémát old meg

A csapatvezetők gyakran támogatják a betanítást, de nem látják pontosan, hol akad el a felhasználó.

### Miért lehet értékes

Strukturált belső képzést támogathat külön LMS bevezetése nélkül.

### Lehetséges nehézségek

Adatvédelmi szabályok, mentor jogosultságok és a coaching és munkavállalói értékelés közti világos határ szükséges.

### Javasolt időtáv

v2.x

## 6. Vállalati tudásbázis

### Rövid leírás

A Learning Center kibővítése vállalatspecifikus SOP-okkal, belső szabályokkal, minőségügyi eljárásokkal és onboarding anyagokkal.

### Milyen problémát old meg

Az operatív tudás gyakran az MES-en kívül, szétszórt fájlokban, megosztott meghajtókon vagy egyéni tapasztalatban él.

### Miért lehet értékes

A KM_Production központi operatív tudásközponttá válhat.

### Lehetséges nehézségek

Tartalomtulajdonosi kör, governance, verziózás és jóváhagyási workflow szükséges.

### Javasolt időtáv

v2.x

## 7. AI Coach

### Rövid leírás

AI-támogatott coach, amely magyarázza a workflow-kat, leckéket ajánl és a felhasználó szintjéhez igazítja a magyarázatot.

### Milyen problémát old meg

A felhasználóknak személyre szabott magyarázatokra lehet szükségük, amelyeket a statikus súgó nem tud biztosítani.

### Miért lehet értékes

Interaktívabbá teheti az onboardingot és csökkentheti a senior munkatársak terhelését.

### Lehetséges nehézségek

Az AI-t jóváhagyott tudásra kell korlátozni, figyelembe kell vennie a jogosultságokat, jeleznie kell a bizonytalanságot, és nem találhat ki workflow szabályokat.

### Javasolt időtáv

v2.x

## 8. AI hibakereső

### Rövid leírás

AI-támogatott hibakeresési folyamat, amely elmagyarázza, miért nem sikerült egy művelet, és következő lépéseket javasol.

### Milyen problémát old meg

A validációs és workflow hibák technikailag pontosak lehetnek, de nehezen értelmezhetőek.

### Miért lehet értékes

Csökkentheti a support terhelést, és segíthet a felhasználóknak az oldalon belül helyreállni a hibából.

### Lehetséges nehézségek

Biztonságos kontextusszűrés, megbízható hibamappelés és rendszerállapotra alapozott emberi magyarázat szükséges.

### Javasolt időtáv

v2.x

## 9. Screenshot-alapú segítség

### Rövid leírás

A súgótémák annotált screenshotokat tartalmaznak, amelyek megmutatják, hova kell kattintani és mit jelentenek az egyes felületrészek.

### Milyen problémát old meg

Sok felhasználó vizuálisan gyorsabban tanul, mint szövegből.

### Miért lehet értékes

Javíthatja az onboardingot összetett képernyőkön, például kapacitástervezésben, gyártási feladatoknál vagy dokumentum AI review-ban.

### Lehetséges nehézségek

A screenshotok elavulhatnak UI-változáskor. Lokalizációt és verziókezelést igényelnek.

### Javasolt időtáv

v1.x

## 10. Hangalapú útmutató

### Rövid leírás

Hangalapú útmutatás olyan környezetekhez, ahol a felhasználó keze foglalt, például shop floor vagy raktári workflow-kban.

### Milyen problémát old meg

Egyes felhasználók munka közben nem tudnak folyamatosan képernyőt olvasni vagy kattintani.

### Miért lehet értékes

Javíthatja az akadálymentességet és támogatja azokat az operatív környezeteket, ahol a vizuális figyelem korlátozott.

### Lehetséges nehézségek

Beszédtechnológia, zajos környezetben végzett tesztelés, akadálymentességi review és szigorú adatvédelmi megfontolások szükségesek.

### Javasolt időtáv

later

## 11. Tanulási analitika

### Rövid leírás

Analitika lecketeljesítésről, súgóhasználatról, ismétlődő hibákról, keresési hiányokról és ajánlások hatékonyságáról.

### Milyen problémát old meg

Analitika nélkül nehéz megállapítani, hogy a Learning Center ténylegesen segíti-e a felhasználókat.

### Miért lehet értékes

Támogatja a tartalomfejlesztést, és azonosítja azokat a workflow-kat, ahol jobb UX vagy képzés szükséges.

### Lehetséges nehézségek

Az analitika nem válhat munkavállalói megfigyeléssé. Fontosak az aggregációs és adatvédelmi határok.

### Javasolt időtáv

v1.x

## 12. Dokumentációminőségi metrikák

### Rövid leírás

Metrikák, amelyek mérik, hogy a tudásoldalak teljesek, aktuálisak, linkeltek, review-zottak és hasznosak-e.

### Milyen problémát old meg

A dokumentáció világos minőségi jelek nélkül könnyen elavul.

### Miért lehet értékes

Segít fenntartani a Learning Centerbe vetett bizalmat mint egyetlen igazságforrásba.

### Lehetséges nehézségek

A minőség részben szubjektív. Az automatizált metrikák támogassák, ne helyettesítsék a review-t.

### Javasolt időtáv

v1.x

## 13. Tudásszint heatmap

### Rövid leírás

Vizuális térkép arról, hogy a felhasználók vagy csapatok mely modulokban rendelkeznek erős vagy gyenge tanulási lefedettséggel.

### Milyen problémát old meg

A vezetők nem mindig látják, mely modulok igényelnek több képzési támogatást.

### Miért lehet értékes

Feltárhatja a szervezeti tudáshiányokat, mielőtt operatív hibákat okoznának.

### Lehetséges nehézségek

Képzéstervezési eszközként kell megtervezni, nem minősítési eszközként. Az adatvédelem és értelmezés különös figyelmet igényel.

### Javasolt időtáv

v2.x

## 14. Tanulási idővonal

### Rövid leírás

Kronologikus nézet a felhasználó tanulási aktivitásáról, teljesített leckéiről, megnyitott súgótémáiról és elfogadott ajánlásairól.

### Milyen problémát old meg

A felhasználóknak és mentoroknak szükségük lehet a tanulási előzmények megértésére és a megszakított képzés folytatására.

### Miért lehet értékes

Láthatóvá teszi a progresszt, és segít ott folytatni, ahol a felhasználó abbahagyta.

### Lehetséges nehézségek

Eseménymegőrzési szabályok és világos különbségtétel szükséges a hasznos előzmény és a túlzott követés között.

### Javasolt időtáv

v1.x

## 15. Okos javaslatok

### Rövid leírás

Kontextusfüggő javaslatok, amelyek az aktuális oldalállapot alapján súgót, leckét vagy következő műveletet ajánlanak.

### Milyen problémát old meg

A felhasználók gyakran nem tudják, melyik súgót nyissák meg vagy milyen előfeltétel hiányzik.

### Miért lehet értékes

Proaktívabbá teheti a segítséget úgy, hogy közben könnyű marad.

### Lehetséges nehézségek

A rossz javaslatok csökkentik a bizalmat. A rendszernek el kell magyaráznia, miért javasol valamit.

### Javasolt időtáv

v1.x

## 16. Miért mód

### Rövid leírás

Olyan mód, amely elmagyarázza, miért követel meg a rendszer egy lépést, jogosultságot, állapotot vagy előfeltételt.

### Milyen problémát old meg

A felhasználók tudhatják, hova kell kattintani, de nem feltétlenül értik az üzleti okot a workflow mögött.

### Miért lehet értékes

Tanítja a domain logikát és csökkenti azokat a kerülőutakat, amelyek sértenék a traceability vagy készletszabályokat.

### Lehetséges nehézségek

Világos üzleti szakértői magyarázatok és gondos lokalizáció szükséges.

### Javasolt időtáv

v1.x

## 17. Mi történik most mód

### Rövid leírás

Olyan mód, amely elmagyarázza, mi fog történni a rendszerben, miután a felhasználó jóváhagy egy műveletet.

### Milyen problémát old meg

A felhasználók bizonytalanok lehetnek olyan műveletek előtt, amelyek készletmozgást, foglalást, audit logot vagy gyártási állapotváltozást hoznak létre.

### Miért lehet értékes

Növeli a magabiztosságot és csökkenti az üzletileg kritikus műveletek véletlen végrehajtását.

### Lehetséges nehézségek

A magyarázatoknak pontosnak kell maradniuk workflow-változások után is.

### Javasolt időtáv

v1.x

## 18. Sandbox mód

### Rövid leírás

Biztonságos gyakorló környezet, ahol a felhasználók kipróbálhatnak workflow-kat anélkül, hogy éles termelési adatokat érintenének.

### Milyen problémát old meg

A felhasználóknak gyakorlati tapasztalatra van szükségük, de éles gyártási adatokon nem szabad kísérletezni.

### Miért lehet értékes

Javíthatja az onboarding minőségét és a felhasználói magabiztosságot éles műveletek előtt.

### Lehetséges nehézségek

Izolált adatok, egyértelmű környezethatárok és annak megelőzése szükséges, hogy a felhasználók összekeverjék a sandboxot az éles rendszerrel.

### Javasolt időtáv

later

## 19. Instruktor mód

### Rövid leírás

Trénereknek szánt mód, amellyel egy csoportot vagy egyéni felhasználót előre definiált leckesorozaton lehet végigvezetni.

### Milyen problémát old meg

A belső képzéshez struktúra, közös progress és ismételhető tananyagok szükségesek.

### Miért lehet értékes

Támogathatja a tantermi vagy távoli onboarding alkalmakat.

### Lehetséges nehézségek

Instruktor jogosultságok, session állapot és esetleg prezentáció-orientált UI szükséges.

### Javasolt időtáv

v2.x

## 20. Teljesítési tanúsítványok

### Rövid leírás

Opcionális tanúsítványok vagy teljesítési rekordok tanulási útvonalakhoz.

### Milyen problémát old meg

Egyes szervezeteknek bizonyítékra van szükségük arról, hogy a felhasználók elvégezték az operatív képzést.

### Miért lehet értékes

Támogathatja a belső compliance-t, onboarding nyilvántartást és auditfelkészülést.

### Lehetséges nehézségek

A tanúsítás formális megfelelőségi jelentést hordozhat. A kritériumokat, megőrzést és jóváhagyási szabályokat gondosan kell definiálni.

### Javasolt időtáv

v2.x

## 21. Adaptív tanulás

### Rövid leírás

Az adaptív tanulás azt jelenti, hogy a Learning Center nemcsak a segítség mértékét, hanem a tanulás sorrendjét is módosítja. A rendszer a magabiztosság, ismétlődő hibák, nem használt modulok, teljesített leckék, súgóhasználat és szerepköri elvárások alapján ajánlja a következő leghasznosabb tanulási lépést.

### Milyen problémát old meg

A merev tanulási útvonalak azt feltételezik, hogy minden azonos szerepkörű felhasználónak ugyanarra a sorrendre van szüksége. A valóságban két azonos szerepkörű felhasználónak eltérő erősségei és hiányosságai lehetnek. Az egyik értheti a készletmozgásokat, de elakadhat a foglalásoknál. A másik jól kezeli a gyártási rendeléseket, de kerüli a kapacitástervezést.

### Miért lehet értékes

Az adaptív tanulás hatékonyabbá teheti a képzést. Nem kényszeríti a felhasználót olyan anyagokra, amelyeket már ért, miközben segít gyakorolni azokat a területeket, ahol gyakran elakad. A cél nem a felhasználó minősítése, hanem a gyorsabb, relevánsabb és kevésbé frusztráló tanulás.

### Alapműködés

- Nemcsak a segítség mértéke változhat, hanem a tanulás sorrendje is.
- A rendszer figyeli, hogy a felhasználó mely területeken tűnik magabiztosnak.
- A rendszer figyeli, hol akad el gyakran a felhasználó.
- Két azonos szerepkörű felhasználó eltérő tanulási javaslatokat kaphat.
- A tanulási útvonal nem merev lista, hanem adaptív ajánlórendszer.
- A felhasználó mindig felülbírálhatja vagy figyelmen kívül hagyhatja az ajánlást.
- A rendszer nem lehet tolakodó.
- Az ajánlások legyenek átláthatóak: a rendszer magyarázza el, miért ajánl egy adott lépést.

### Jelek

Lehetséges jelek:

- ismétlődő validációs hibák ugyanazon az oldalon
- ugyanazon súgótéma ismételt megnyitása
- sikeres műveletek segítség nélkül
- a szerepkör számára fontos, de nem használt modulok
- félbehagyott onboarding lépések
- találat nélküli keresések
- gyakori váltás Profi vagy Haladó szintről Kezdőre egy oldalon
- mentor vagy admin ajánlás

### Példa 1

Egy raktáros gyorsan megtanulja a készletmozgásokat, de gyakran elakad a foglalásoknál. A rendszer ismételt foglalási súgómegnyitásokat és validációs hibákat érzékel. Több foglalási gyakorlatot és rövid magyarázatot ajánl arról, hogyan befolyásolják az aktív foglalások a rendelkezésre álló készletet.

### Példa 2

Egy termelésvezető jól kezeli a gyártási rendeléseket, de nem használja a kapacitástervezést. A rendszer sikeres gyártási rendelés aktivitást lát, de kapacitás dashboard használatot nem. Rövid kapacitástervezési bevezetőt ajánl, fókuszban a gyártóegység-terheléssel, alkalmazotti terheléssel és késési kockázattal.

### Példa 3

Egy adminisztrátor sokszor megnyitja ugyanazt a jogosultsági súgót. A rendszer célzott jogosultsági tanulási blokkot ajánl, amely elmagyarázza az autorizációs szerepköröket, szakmai szerepeket, jogosultságöröklést és gyakori admin hibákat.

### Lehetséges nehézségek

- A rossz ajánlások csökkenthetik a bizalmat.
- A rendszernek elegendő adatra van szüksége az alkalmazkodás előtt.
- A felhasználóknak érteniük kell, hogy az ajánlás támogatás, nem értékelés.
- Az adatvédelmi és riportolási határoknak világosnak kell lenniük.
- Az ajánlási logikának magyarázhatónak kell lennie.
- A UI nem zaklathatja a felhasználót.

### Javasolt időtáv

v2.x

## Review megjegyzések

- Ezek az ötletek későbbi termék- és technikai döntésre várnak.
- Nem bővítik automatikusan a Learning Center v1.0 scope-ját.
- Implementáció előtt külön specifikáció szükséges.
