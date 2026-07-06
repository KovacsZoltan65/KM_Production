# Course Model Specification

Version: v1.0

Status: Draft  
Scope: Project-level learning architecture

## Purpose

Ez a dokumentum a KM_Production felhasználói dokumentációja és a későbbi Learning Center közötti átmeneti architektúrát írja le.

A cél nem a jelenlegi dokumentáció átszervezése. A `docs/user-guides/` struktúra változatlan marad. A cél annak rögzítése, hogy a meglévő Markdown alapú útmutatók hogyan értelmezhetők később Course, Lesson, Knowledge Unit és Learning Path szinten.

Ez a specifikáció fogalmi dokumentum. Nem adatbázisterv, nem Laravel implementációs terv, nem Vue komponensspecifikáció és nem AI promptgyűjtemény.

## Oktatási hierarchia

```txt
Business Ontology
↓
Knowledge Graph
↓
Knowledge Unit
↓
Lesson
↓
Course
↓
Learning Path
↓
Learning Center
```

Ez a hierarchia azt mutatja, hogyan lesz a gyártási üzleti tudásból felhasználói tananyag.

### Business Ontology

A Business Ontology a KM_Production üzleti fogalmainak rendszere.

Ide tartoznak például:

- gyár
- raktár
- dolgozó
- vevő
- beszállító
- termék
- alapanyag
- darabjegyzék
- gyártási folyamat
- gyártási feladat
- minőségellenőrzés
- kiszállítás

Szerepe:

- közös szókincset ad
- megmondja, milyen üzleti fogalmak léteznek
- segít elkerülni, hogy ugyanazt a dolgot több néven magyarázzuk
- alapot ad a dokumentációhoz, a Knowledge Graphhoz és az AI válaszokhoz

Példa: a "darabjegyzék" üzleti fogalom. A felhasználónak egyszerűen azt kell értenie, hogy ez a termék anyaglistája.

### Knowledge Graph

A Knowledge Graph a fogalmak és tudáselemek kapcsolatainak hálója.

Szerepe:

- megmutatja, mi minek az előfeltétele
- megmutatja, mely fogalmak kapcsolódnak egymáshoz
- segít tanulási sorrendet kialakítani
- segít hibák magyarázatában
- később segíthet az AI-nak kontextust választani

Példa kapcsolatok:

- "Gyártás indítása" igényli a "Darabjegyzék létrehozása" tudást.
- "Raktárkészlet feltöltése" kapcsolódik a "Beszállító", "Alapanyag" és "Raktár" fogalmakhoz.
- "Nincs elég készlet" hiba magyarázható a "Készletegyenleg" és "Beérkezés" tudással.

### Knowledge Unit

A Knowledge Unit önálló, újrahasznosítható tudásegység.

Szerepe:

- egy konkrét fogalmat, műveletet vagy hibát magyaráz
- több dokumentumban, Lessonben, tooltipben, AI válaszban vagy videóban is felhasználható
- nem kötődik kizárólag egyetlen oldalhoz
- nem azonos egy teljes fejezettel

Példák:

- "Mi a raktár?"
- "Mi a darabjegyzék?"
- "Miért kell gyártási folyamat?"
- "Mit jelent az elfogadott minőségellenőrzés?"
- "Miért nem indul a gyártás, ha nincs készlet?"

### Lesson

A Lesson egy tanulási egység, amely egy konkrét felhasználói célt vezet végig.

Szerepe:

- gyakorlati lépéseket ad
- több Knowledge Unitot fog össze
- a felhasználót egy kis, ellenőrizhető eredményig vezeti
- alkalmas Markdown fejezetként, interaktív onboarding lépésként, videóként vagy AI által magyarázott leckeként megjelenni

Példa: "Raktárak létrehozása" Lesson. Ebben a felhasználó megtanulja, mi a raktár, mi a tárolóhely, mely mezők kötelezőek, és létrehozza az Alapanyag raktárat.

### Course

A Course több Lessonből álló, összefüggő tananyag.

Szerepe:

- egy nagyobb felhasználói célt fed le
- sorrendbe rendezi a Lessonöket
- előfeltételeket, célközönséget és becsült időt ad
- követhető tanulási élményt biztosít

Példa: "Kezdő felhasználói útmutató" Course. A célja, hogy a teljesen kezdő felhasználó végigjusson a bejelentkezéstől az első kiszállításig.

### Learning Path

A Learning Path több Course-ból vagy Lessonből álló útvonal.

Szerepe:

- szerepkörhöz vagy hosszabb tanulási célhoz igazodik
- több Course-t is összefűzhet
- támogatja az onboardingot és később az adaptív tanulást

Példák:

- "Adminisztrátor alapútvonal"
- "Raktáros alapútvonal"
- "Termelésvezető onboarding"
- "Minőségellenőr betanulás"

### Learning Center

A Learning Center a felhasználó számára látható tanulási és segítségnyújtási felület.

Szerepe:

- Course-okat és Learning Pathokat jelenít meg
- oldalspecifikus súgót ad
- segít az első használatban
- kontextus alapján ajánlhat következő lépést
- később AI, videó, tooltip és interaktív onboarding felületeket kapcsolhat ugyanahhoz a tudáshoz

## Mi egy Lesson?

Egy Lesson olyan tanulási egység, amely egy kezdő felhasználót egy konkrét, ellenőrizhető eredményig vezet.

Egy Lesson jellemzői:

- egy fő célja van
- önállóan értelmezhető
- rövid idő alatt feldolgozható
- gyakorlati lépéseket tartalmaz
- üzleti magyarázatot ad
- tartalmazhat több Knowledge Unitot
- végén ellenőrizhető, hogy sikerült-e

Példák Lessonre:

- "Bejelentkezés"
- "Raktárak létrehozása"
- "Darabjegyzék létrehozása"
- "Raktárkészlet feltöltése"
- "Minőségellenőrzés"

### Mi nem Lesson?

Nem Lesson:

- egyetlen tooltip
- egyetlen mező magyarázata
- egy hibaüzenet önmagában
- egy teljes kézikönyv
- egy teljes Learning Path
- egy Laravel route vagy Vue komponens
- egy adatbázistábla

Példák nem Lessonre:

- "Cikkszám mező" önmagában nem Lesson, hanem Knowledge Unit vagy tooltip lehet.
- "Nincs készlet" önmagában nem Lesson, hanem hiba- vagy Knowledge Unit téma lehet.
- "Teljes raktáros betanulás" nem Lesson, hanem Course vagy Learning Path lehet.

## Mi egy Course?

Egy Course egymásra épülő Lessonökből álló tananyag, amely egy nagyobb felhasználói cél teljesítéséig vezet.

Egy Course jellemzői:

- több Lessonből áll
- van kezdete és vége
- van célközönsége
- van előfeltétele
- van becsült ideje
- követhető tanulási sorrendet ad
- teljes munkafolyamatot vagy nagyobb témakört fed le

Példák Course-ra:

- "Kezdő felhasználói útmutató"
- "Raktárkezelés alapjai"
- "Gyártási feladatok az üzemi felületen"
- "Minőségellenőrzés kezdőknek"

### Mi nem Course?

Nem Course:

- egyetlen Lesson
- egyetlen Knowledge Unit
- egy lista mezőmagyarázatokból
- egy oldalspecifikus tooltipgyűjtemény
- egy teljes Learning Center
- egy szerepkör teljes hosszú távú tanulási útvonala, ha több nagy tananyagot fog össze

Példák nem Course-ra:

- "Mi a darabjegyzék?" nem Course, hanem Knowledge Unit.
- "Darabjegyzék létrehozása" önmagában Lesson.
- "Raktáros karrierút" Learning Path lehet, nem egyetlen Course.

## A Kezdő felhasználói útmutató Course felépítése

A jelenlegi `docs/user-guides/kezdo-felhasznaloi-utmutato/` struktúra Course-ként értelmezhető.

```txt
Course: Kezdő felhasználói útmutató
↓
Lesson 01: Bejelentkezés
↓
Lesson 02: Az admin felület rövid bemutatása
↓
Lesson 03: Gyár létrehozása
↓
Lesson 04: Raktárak létrehozása
↓
Lesson 05: Dolgozók felvétele
↓
Lesson 06: Beszállítók felvétele
↓
Lesson 07: Vevők felvétele
↓
Lesson 08: Termékek felvétele
↓
Lesson 09: Alapanyagok felvétele
↓
Lesson 10: Műveletek létrehozása
↓
Lesson 11: Gyártási folyamat létrehozása
↓
Lesson 12: Darabjegyzék létrehozása
↓
Lesson 13: Raktárkészlet feltöltése
↓
Lesson 14: Vevői megrendelés rögzítése
↓
Lesson 15: Gyártás indítása
↓
Lesson 16: Munkafolyamatok elvégzése
↓
Lesson 17: Minőségellenőrzés
↓
Lesson 18: Késztermék raktárba helyezése
↓
Lesson 19: Megrendelés kiszállítása
```

Ebben a Course-ban a felhasználó a teljesen üres rendszerből indul. A Course végére létrejönnek az alapadatok, elkészül az első termék, és megtörténik az első kiszállítás.

## Lesson és Knowledge Unit kapcsolat

Egy Lesson több Knowledge Unitot is tartalmazhat.

Példa: "Raktárak létrehozása" Lesson tartalmazhatja ezeket a Knowledge Unitokat:

- "Mi a raktár?"
- "Mi a tárolóhely?"
- "Miért kell külön alapanyag raktár és késztermék raktár?"
- "Mit jelent az aktív raktár?"
- "Miért nem készlet maga a raktár?"

Példa: "Gyártás indítása" Lesson tartalmazhatja:

- "Mi a vevőrendelés?"
- "Mi a gyártási terv?"
- "Mi a gyártási feladat?"
- "Miért kell darabjegyzék?"
- "Miért kell elérhető készlet?"

Példa: "Minőségellenőrzés" Lesson tartalmazhatja:

- "Mit jelent az elfogadott eredmény?"
- "Mit jelent az elutasított eredmény?"
- "Miért kell ellenőrzési megjegyzés?"
- "Miért nem ugyanaz az ellenőrzés és a kiszállítás?"

## Knowledge Unit újrafelhasználás több Lessonben

Ugyanaz a Knowledge Unit több Lessonben is megjelenhet.

Példa: "Mi a készlet?" Knowledge Unit használható:

- "Raktárak létrehozása" Lessonben, mert a raktár és készlet különbségét magyarázza
- "Raktárkészlet feltöltése" Lessonben, mert a beérkezés készletet hoz létre
- "Gyártás indítása" Lessonben, mert a gyártáshoz elérhető készlet kell
- "Késztermék raktárba helyezése" Lessonben, mert a gyártás eredménye készletként jelenik meg
- "Megrendelés kiszállítása" Lessonben, mert a késztermék elhagyja a raktárt

Példa: "Mi a cikkszám?" Knowledge Unit használható:

- "Termékek felvétele" Lessonben
- "Alapanyagok felvétele" Lessonben
- "Darabjegyzék létrehozása" Lessonben
- "Vevői megrendelés rögzítése" Lessonben

Ez csökkenti a duplikációt. Ha a Knowledge Unit magyarázata javul, minden Lesson profitálhat belőle.

## Course metaadatok

Egy Course ajánlott metaadatai:

- **Cím**: a Course felhasználói címe.
- **Cél**: milyen eredményig vezeti el a felhasználót.
- **Célközönség**: kinek szól.
- **Előfeltételek**: mit kell tudni vagy elvégezni előtte.
- **Becsült idő**: mennyi idő alatt végezhető el.
- **Nehézségi szint**: például Beginner, Intermediate, Advanced.
- **Kapcsolódó Learning Path**: mely útvonal része.
- **Kapcsolódó szerepkörök**: például Adminisztrátor, Raktáros, Termelésvezető.
- **Verzió**: a Course tartalmi verziója.
- **Státusz**: Draft, Review, Published vagy Archived.
- **Forrásdokumentumok**: mely Markdown dokumentumokból épül.
- **Nyelv**: például magyar.

Példa:

```yaml
title: Kezdő felhasználói útmutató
goal: Az első teljes gyártási folyamat végigvezetése
audience: Teljesen kezdő felhasználó
prerequisites:
    - Frissen telepített rendszer
    - Bejelentkezési adatok
estimated_time: 90-120 perc
difficulty: Beginner
learning_path: Első használat
roles:
    - Adminisztrátor
    - Termelésvezető
    - Raktáros
version: v1.0
status: Draft
```

## Lesson metaadatok

Egy Lesson ajánlott metaadatai:

- **Cím**: a Lesson felhasználói címe.
- **Cél**: mit ér el a felhasználó a Lesson végére.
- **Idő**: becsült feldolgozási idő.
- **Előfeltételek**: mely Lessonök vagy Knowledge Unitok szükségesek előtte.
- **Tartalmazott Knowledge Unitok**: milyen tudásegységek jelennek meg benne.
- **Gyakorlati feladat**: mit kell ténylegesen elvégezni.
- **Ellenőrző lista**: honnan tudja a felhasználó, hogy sikerült.
- **Kapcsolódó oldal vagy workflow**: hol jelenhet meg a rendszerben.
- **Kapcsolódó szerepkörök**: kinek releváns.
- **Gyakori hibák**: mely hibákat magyarázza.

Példa:

```yaml
title: Raktárkészlet feltöltése
goal: Az első alapanyagkészlet rögzítése
time: 8 perc
prerequisites:
    - Raktárak létrehozása
    - Beszállítók felvétele
    - Alapanyagok felvétele
knowledge_units:
    - Mi a készlet?
    - Mi a beérkezés?
    - Mi a készletmozgás?
practice_task: Rögzíts beérkezést az Acél Plusz Kft. beszállítótól
checklist:
    - Beérkezés létrejött
    - Beérkezés könyvelt
    - Készletegyenleg látszik
```

## Jövőbeli Learning Center kapcsolat

A jelenlegi Markdown dokumentáció később interaktív Lessonné alakítható.

Lehetséges átalakulás:

1. A Markdown fejezet Course/Lesson metaadatot kap.
2. A fejezetből kiemelhetők a Knowledge Unitok.
3. A lépések interaktív feladatokká válhatnak.
4. Az "Ellenőrizd magad" rész ellenőrző listává vagy progress feltétellé válhat.
5. A gyakori hibák oldalspecifikus hibamagyarázatként jelenhetnek meg.
6. A "Következő lépés" Learning Center ajánlássá válhat.

Példa:

- Markdown Lesson: `13-raktarkeszlet-feltoltese.md`
- Interaktív Lesson: "Töltsd fel az első alapanyag készletet"
- UI ellenőrzés: van-e beérkezés, látszik-e készletegyenleg
- Ajánlott következő Lesson: "Vevői megrendelés rögzítése"

## Jövőbeli AI kapcsolat

Az AI ugyanabból a Lessonből többféleképpen magyarázhat.

AI felhasználási módok:

- rövid magyarázat kezdő felhasználónak
- mezőmagyarázat egy adott képernyőn
- hiba okának egyszerű magyarázata
- következő lépés javaslata
- tananyag összefoglalása
- gyakorló kérdések generálása emberi jóváhagyással

Fontos határok:

- Az AI nem módosíthat üzleti adatot.
- Az AI nem kerülheti meg a jogosultságokat.
- Az AI csak jóváhagyott dokumentációból és Knowledge Unitokból magyarázhat.
- Az AI válaszai tanácsadó jellegűek.

Példa:

Ha a felhasználó a gyártás indításánál elakad, az AI a "Gyártás indítása" Lessonből és a kapcsolódó Knowledge Unitokból magyarázhatja:

- kell vevőrendelés
- kell darabjegyzék
- kell gyártási folyamat
- kell készlet
- lehet, hogy jogosultság hiányzik

## Jövőbeli videós oktatás kapcsolat

Egy Lesson videós tananyaggá is alakítható.

Videós felépítés:

- rövid bevezetés: mit fogunk csinálni
- képernyőbemutató: hol kell kattintani
- mezők magyarázata: mit kell kitölteni
- tipikus hibák: mire figyelj
- ellenőrzés: honnan tudod, hogy sikerült
- következő Lesson ajánlása

Példa:

"Darabjegyzék létrehozása" videó:

1. Mi az a darabjegyzék?
2. Acél konzol kiválasztása.
3. Három alapanyag hozzáadása.
4. Mennyiségek ellenőrzése.
5. Gyakori hibák: rossz termék, rossz mennyiség, hiányzó alapanyag.

A videó nem új tudásforrás. Ugyanarra a Lessonre és Knowledge Unitokra épül, mint a Markdown és a Learning Center.

## Zero State kapcsolat

A Zero State filozófia azt mondja ki, hogy az üres rendszer ne legyen néma vagy ijesztő.

Friss telepítés után a felhasználó nem tudja:

- mit kell először létrehozni
- miért üres minden lista
- melyik lépés következik
- miért nem indul még a gyártás

A Course Model ehhez úgy kapcsolódik, hogy az üres képernyőkhöz tanulási irányt ad.

Példák:

- Üres **Cikkek** lista: ajánlott Lesson "Termékek felvétele".
- Üres **Raktárak/Helyek** lista: ajánlott Lesson "Raktárak létrehozása".
- Üres **Gyártási feladatok** lista: ajánlott Lesson "Gyártás indítása".
- Hiányzó készlet: ajánlott Lesson "Raktárkészlet feltöltése".

Így az üres állapot nem zsákutca, hanem belépési pont a tanulásba.

## Guided Course kapcsolat

A Guided Course filozófia szerint a rendszer nemcsak dokumentációt ad, hanem végigvezeti a felhasználót egy valós célon.

A Course Model ezt támogatja:

- a Course nagy célt ad
- a Lesson kis, teljesíthető lépést ad
- a Knowledge Unit magyarázza a szükséges fogalmat
- az ellenőrző lista sikerélményt ad
- a következő Lesson irányt ad

Példa:

A "Kezdő felhasználói útmutató" Guided Course nem csak elmagyarázza, mi a raktár vagy a darabjegyzék. A felhasználó ténylegesen létrehozza őket, majd látja, hogyan vezetnek el a gyártásig és kiszállításig.

## Nyitott kérdések

- Mekkora legyen egy Lesson ideális mérete?
- Mekkora legyen egy Course ideális mérete?
- Egy Lesson hány Knowledge Unitot tartalmazhat?
- Mikor számít egy Lesson teljesítettnek?
- Legyen-e automatikus haladáskövetés v1.0-ban?
- Milyen adatok alapján jelöljön a rendszer automatikusan késznek egy Lesson lépést?
- Generálhat-e AI gyakorlati feladatokat, vagy csak javasolhat emberi review után?
- Mikor válhat egy AI által generált feladat publikált tananyaggá?
- Hogyan kapcsolódjanak videók a Lessonökhöz?
- Kell-e külön vizsga vagy tudásellenőrzés?
- Kell-e bizonyítvány Course vagy Learning Path teljesítése után?
- Legyen-e gamification, például jelvény, pont vagy szint?
- Hogyan kezeljük a Course verziókat, ha a felület vagy workflow változik?
- Ki felel egy Course szakmai karbantartásáért?
- Hol legyen a Course és Lesson metaadatok elsődleges forrása: Markdownban, adatbázisban vagy hibrid módon?

## Kapcsolódó dokumentumok

- [Learning Center v1.0 specifikáció](../specifications/learning-center/README.md)
- [Knowledge Unit Specification](../specifications/learning-center/knowledge-unit.md)
- [KM_Production Knowledge Graph Specification](knowledge-graph.md)
- [Kezdő felhasználói útmutató](../user-guides/kezdo-felhasznaloi-utmutato/README.md)
- [Learning Center döntések](../specifications/learning-center/decisions.md)
