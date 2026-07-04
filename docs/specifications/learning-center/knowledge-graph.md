# Knowledge Graph

## Cél

Ez a dokumentum bemutatja a Learning Center tudásháló szemléletét. A cél annak rögzítése, hogy a Knowledge Unitok nem elszigetelt szövegek, hanem kapcsolatokkal rendelkező tudáselemek, amelyekből dokumentáció, tanulási útvonal, kontextuális súgó és AI navigáció épülhet.

## Tervezett tartalom

- Knowledge Graph fogalma.
- Kapcsolattípusok és gráf szemlélet.
- Ajánlások, tanulási útvonalak és AI navigáció.
- Példák gyártási, készletkezelési és jogosultsági tudáshálóra.

## Mi a Knowledge Graph?

A Knowledge Graph a Learning Center tudásegységei közötti kapcsolatok rendszere. Ahelyett, hogy minden dokumentum különálló sziget lenne, a Knowledge Graph megmutatja, mely fogalmak, műveletek, hibák, szerepkörök, oldalak és előfeltételek kapcsolódnak egymáshoz.

Egy gráfban a csomópontok lehetnek:

- Knowledge Unitok
- oldalak és workflow-k
- szerepkörök
- jogosultságok
- hibák és validációs esetek
- tanulási útvonalak
- dokumentációs fejezetek
- üzleti entitások

Az élek a kapcsolatok:

- előfeltétele
- része
- magyarázza
- kapcsolódik hozzá
- következő ajánlott lépés
- hiba esetén releváns
- szerepkör számára fontos
- AI válasz forrása lehet

## Miért nem különálló Knowledge Unitok léteznek?

Egy gyártási rendszerben a tudás természetesen összefügg. A készletfoglalás kapcsolódik a gyártás indításához, a kapacitástervezéshez, a vevői rendeléshez és a késési kockázathoz. Egy jogosultsági hiba kapcsolódhat szerepkörhöz, oldalhoz, művelethez és üzleti szabályhoz.

Ha ezek különálló egységek maradnak, a Learning Center nem tud értelmesen ajánlani következő lépést. Ha viszont gráfként kezeljük őket, a rendszer képes lehet megmutatni, hogy egy hiba, oldal vagy tanulási hiány milyen kapcsolódó tudást igényel.

## Gráf szemlélet

A gráf szemlélet azt jelenti, hogy a tudás nem lineáris kézikönyvként épül fel. A felhasználó az aktuális helyzetből indul:

- egy oldalon áll
- egy műveletet próbál végrehajtani
- hibát kap
- hiányzó jogosultsággal találkozik
- új szerepkörben dolgozik
- tanulási útvonalat követ

A Knowledge Graph ebből a helyzetből keresi meg a kapcsolódó tudásegységeket.

## Kapcsolatok

Javasolt kapcsolattípusok:

- `requires`: egy tudásegység előfeltétele egy másiknak
- `explains`: egy egység magyaráz egy hibát, állapotot vagy döntést
- `related_to`: lazább szakmai kapcsolat
- `part_of`: egy egység nagyobb folyamat része
- `next_step`: tanulási vagy workflow következő lépés
- `blocked_by`: műveletet akadályozó feltétel
- `visible_for`: szerepkör vagy jogosultság szerinti relevancia
- `used_by`: dokumentációs, tooltip, onboarding vagy AI felhasználás

Az implementáció később eltérő technikai megoldást választhat, de a fogalmi kapcsolatoknak stabilnak kell maradniuk.

## Ajánlások

A Knowledge Graph támogatja az ajánlásokat:

- "Ezt a súgót gyakran a készletfoglalás után érdemes elolvasni."
- "A kapacitástervezés megértéséhez előbb nézd át a gyártóegység-terhelést."
- "Ez a hiba a minőségügyi zárolás témához kapcsolódik."
- "Ha ezt a műveletet nem tudod végrehajtani, ellenőrizd a jogosultsági beállításokat."

Az ajánlások nem kötelezőek. Céljuk a tájékozódás gyorsítása és a tanulási útvonal személyre szabása.

## AI navigáció

A későbbi AI asszisztens számára a Knowledge Graph kontrollált navigációs térként működhet. Az AI nem szabadon találgatja, mi lehet releváns, hanem a gráf alapján azonosíthatja:

- mely Knowledge Unit kapcsolódik az aktuális oldalhoz
- milyen előfeltételek hiányozhatnak
- milyen hibák magyarázata releváns
- milyen tanulási lépés ajánlható
- milyen tartalom nem látható a felhasználó jogosultsága miatt

Ez csökkenti a pontatlan vagy jogosulatlan válaszok kockázatát.

## Tanulási útvonalak

A tanulási útvonal nem merev lista, hanem gráfon bejárható útvonal. Egy Raktáros útvonala indulhat készletmozgásokkal, majd foglalásokkal és raktári hibákkal folytatódhat. Egy Termelésvezető útvonala indulhat gyártási rendelésekkel, majd kapacitástervezéssel és késési kockázattal bővülhet.

A gráf lehetővé teszi, hogy a Learning Engine:

- előfeltételeket ajánljon
- kihagyjon már ismert témákat
- visszalépést javasoljon gyakori elakadás esetén
- szerepkörön belül személyre szabjon

## Példák

### Gyártás indítása

Kapcsolódó egységek:

- gyártási rendelés állapota
- anyagelérhetőség
- minőségügyi zárolás
- készletfoglalás
- jogosultság gyártás indítására
- traceability követelmények

### Készletfoglalás

Kapcsolódó egységek:

- elérhető készlet
- vevői rendelés
- gyártási igény
- foglalás felszabadítása
- készlethiány magyarázata

### Jogosultsági hiba

Kapcsolódó egységek:

- felhasználói szerepkör
- szakmai szerepkör
- jogosultság öröklése
- admin beállítások
- audit követelmények

## Kapcsolódó témák

- [Knowledge Unit](knowledge-unit.md)
- [Knowledge Engine](knowledge-engine.md)
- [Learning Paths](learning-paths.md)
- [AI integráció](ai-integration.md)
- [Context Engine](context-engine.md)
