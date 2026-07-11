# Context Engine

## Cél

A Context Engine határozza meg, hogy a Learning Center mikor, kinek, milyen helyzetben és milyen részletességgel adjon segítséget. Feladata nem az üzleti döntések meghozatala, hanem az aktuális felhasználói és rendszerkörnyezet értelmezése, majd a releváns Knowledge Unitok, dokumentációs szakaszok, tooltippek, hibamagyarázatok vagy tanulási javaslatok kiválasztása.

## Tervezett tartalom

- Aktuális oldal és page key felismerése.
- Aktuális felhasználó, szerepkör és jogosultság figyelembevétele.
- Aktuális művelet, rendszerállapot és előfeltételek értelmezése.
- Tudásszint és asszisztenciaszint alkalmazása.
- Döntési modell: mikor kell segíteni.
- Biztonsági és adatvédelmi határok.
- AI-ready, de jogosultságszűrt kontextus payload.

## Alapelv

A Context Engine a Learning Center helyzetérzékelő rétege. Nem önálló termékfunkció, hanem koordinációs komponens a Knowledge Engine és a Learning Engine között.

Egyszerűsített kérdései:

- Hol van most a felhasználó?
- Ki a felhasználó és mire jogosult?
- Mit próbál éppen tenni?
- Milyen állapotban van az érintett adat vagy workflow?
- Van-e hiányzó előfeltétel?
- Milyen tudásszinten van ezen az oldalon?
- Kell-e most segítséget adni, vagy elég kérésre elérhetővé tenni?

## Aktuális oldal

Az aktuális oldal a kontextus elsődleges horgonya. A rendszernek stabil page key-t kell használnia, amely nem változik pusztán UI átrendezés miatt.

Példák:

- `production.orders.show`
- `inventory.movements.index`
- `capacity.dashboard`
- `admin.permissions.index`
- `quality.inspections.show`

Az oldal alapján a Context Engine azonosíthatja:

- releváns Knowledge Unitokat
- oldalszintű asszisztenciaszintet
- támogatott tooltippeket
- gyakori hibákat
- tanulási útvonal kapcsolódó lépéseit

## Aktuális felhasználó

A felhasználói kontextus nem csak az azonosítót jelenti. A Learning Centernek azt kell megértenie, milyen támogatás lehet releváns az adott felhasználónak.

Figyelembe vehető elemek:

- felhasználói azonosító
- nyelvi beállítás
- manuálisan választott oldalszintű asszisztenciaszint
- korábbi progress
- ismétlődő elakadások
- elutasított vagy elfogadott javaslatok
- mentor vagy admin által ajánlott tanulási blokkok

A Context Engine nem használhat olyan felhasználói adatot, amely nem szükséges a segítség kiválasztásához.

## Szerepkör

A szerepkör segít meghatározni, mely tudás releváns. Egy Raktáros számára más súgó hasznos, mint egy Termelésvezető, Minőségellenőr vagy Adminisztrátor számára.

Példák:

- Raktáros: készletmozgások, foglalások, lokációk.
- Beszerző: beszerzési igények, rendelési státuszok, beszállítói adatok.
- Termelésvezető: gyártási rendelések, kapacitás, késési kockázat.
- Minőségellenőr: zárolások, inspekciók, nemmegfelelőségek.
- Adminisztrátor: felhasználók, szerepkörök, jogosultságok.

A szerepkör nem írhatja felül a tényleges jogosultságokat. Csak relevanciaszűrőként használható.

## Jogosultság

A jogosultsági kontextus biztonsági határ. A Learning Center nem magyarázhat el olyan részletet, amelyhez a felhasználónak nincs hozzáférése, és nem sugallhat olyan műveletet, amelyet nem végezhet el.

A jogosultság alapján szűrni kell:

- látható Knowledge Unitokat
- hibamagyarázat részletességét
- ajánlott következő lépéseket
- AI-nak átadható kontextust
- adminisztratív súgótartalmakat

## Aktuális művelet

Az aktuális művelet megmutatja, mit próbál a felhasználó végrehajtani. Ez lehet explicit művelet, például gombnyomás, vagy implicit szándék, például szűrés, export, státuszváltás.

Példák:

- gyártás indítása
- készletmozgás rögzítése
- minőségügyi zárolás feloldása
- kapacitási slot keresése
- jogosultság mentése
- dokumentum AI review indítása

A művelet alapján pontosabb segítség adható, mint pusztán oldal alapján.

## Rendszerállapot

A rendszerállapot azt írja le, hogy az érintett entitás vagy workflow milyen helyzetben van.

Példák:

- gyártási rendelés: tervezett, előkészített, futó, lezárt
- beszerzési rendelés: draft, elküldött, részben beérkezett
- készlet: elérhető, foglalt, zárolt
- dokumentum: feltöltött, feldolgozás alatt, review-zott
- kapacitás: szabad, túlterhelt, késési kockázattal érintett

A Context Engine ebből tudja eldönteni, hogy a segítség magyarázó, figyelmeztető vagy helyreállító jellegű legyen.

## Előfeltételek

Az előfeltételek különösen fontosak, mert sok felhasználói elakadás abból ered, hogy a rendszer jogosan tilt egy műveletet, de az ok nem egyértelmű.

Előfeltétel lehet:

- szükséges adat hiánya
- hibás állapot
- hiányzó jogosultság
- aktív minőségügyi zárolás
- elégtelen készlet
- hiányzó kapacitás
- lezáratlan előző workflow lépés

A Context Engine feladata nem az előfeltétel ellenőrzés implementálása, hanem az ellenőrzések eredményének értelmezhető súgóvá alakítása.

## Tudásszint

A tudásszint oldalanként értelmezett felhasználói beállítás. A Context Engine ennek alapján választja ki a segítség részletességét.

- Kezdő: lépésről lépésre vezet, több magyarázatot ad.
- Haladó: jelzi az irányt, de nem magyaráz túl.
- Profi: csak kérésre vagy kritikus hiba esetén segít.

A tudásszint nem minősítés. A cél az, hogy a felhasználó a saját munkatempójához igazíthassa a Learning Center viselkedését.

## Mikor kell segíteni?

A Context Engine akkor javasoljon aktív segítséget, ha a helyzet alapján a felhasználó valószínűleg elakadt vagy kockázatos művelet előtt áll.

Aktív segítség indokolt lehet:

- ismétlődő validációs hiba esetén
- kritikus vagy visszafordíthatatlan művelet előtt
- hiányzó előfeltétel felismerésekor
- üres állapotban, ahol a következő lépés nem egyértelmű
- első látogatáskor egy támogatott oldalon
- új szerepkör vagy új modul használatakor
- gyakori súgómegnyitás után

Passzív, kérésre elérhető segítség elég lehet:

- Profi szinten
- jól ismert, gyakran sikeresen végrehajtott műveleteknél
- egyszerű listaoldalakon
- amikor a felhasználó korábban elutasította az adott javaslatot

## Döntési modell

A döntés ajánlott lépései:

1. Azonosítsd az oldalt és a műveletet.
2. Szűrd a kontextust jogosultság alapján.
3. Vizsgáld meg az entitás és workflow állapotát.
4. Azonosítsd a hiányzó előfeltételeket vagy hibákat.
5. Keresd meg a kapcsolódó Knowledge Unitokat.
6. Alkalmazd az oldalszintű tudásszintet.
7. Döntsd el, aktív vagy passzív segítség szükséges-e.
8. Magyarázd el röviden, miért ezt a segítséget ajánlja a rendszer.

## AI-ready kontextus

Későbbi AI integráció esetén a Context Engine állítsa elő a biztonságosan átadható kontextust. Ez nem tartalmazhat jogosulatlan adatot, rejtett üzleti részletet vagy szükségtelen személyes információt.

AI payload javasolt elemei:

- page key
- művelet típusa
- látható entitás összefoglalója
- engedélyezett Knowledge Unit azonosítók
- releváns hibák és előfeltételek
- asszisztenciaszint
- válasznyelv

## Kapcsolódó témák

- [Knowledge Unit](knowledge-unit.md)
- [Knowledge Graph](knowledge-graph.md)
- [Élő dokumentáció](live-documentation.md)
- [Hibakezelés](error-handling.md)
- [AI integráció](ai-integration.md)
- [Jogosultságok](permissions.md)
