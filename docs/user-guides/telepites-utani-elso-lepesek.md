# Telepítés utáni első lépések

## Az első sikeres gyártási rendelésig

Ez az útmutató kezdő felhasználóknak készült. A cél az, hogy a KM_Production rendszerben ugyanazon a példán végigmenve létrehozd az első vevőrendelést, elindítsd a gyártást, rögzítsd a műveleteket, elvégezd a minőségellenőrzést, készletre vedd a készterméket, majd kiszállítsd.

Hétfő reggel van.

A **KM Gépgyártó Kft.** most telepítette a KM_Production rendszert. Megérkezett az első megrendelés: 10 db **Acél konzol** gyártása a **Minta Gépipari Kft.** részére.

A dokumentáció során ezt a feladatot fogjuk közösen végrehajtani. Lépésről lépésre felépítjük a rendszert, feltöltjük az alapanyagokat, elindítjuk a gyártást, majd eljutunk az első teljesített kiszállításig.

A példa végig ugyanazokat az adatokat használja:

| Adat               | Minta                                                      |
| ------------------ | ---------------------------------------------------------- |
| Vállalat           | KM Gépgyártó Kft.                                          |
| Telephely          | Budapest                                                   |
| Raktár             | Alapanyag raktár                                           |
| Tárolóhely         | A-01                                                       |
| Beszállító         | Acél Plusz Kft.                                            |
| Vevő               | Minta Gépipari Kft.                                        |
| Termék             | Acél konzol                                                |
| Termék cikkszám    | PRD-1001                                                   |
| Alapanyag          | 4 mm acéllemez                                             |
| Alapanyag cikkszám | MAT-0001                                                   |
| Csavar             | M8×20                                                      |
| Csavar cikkszám    | MAT-0100                                                   |
| Festék             | Szürke porfesték                                           |
| Festék cikkszám    | MAT-0200                                                   |
| Dolgozó            | Kiss János                                                 |
| Műveletek          | Lézervágás, Hajlítás, Hegesztés, Festés, Minőségellenőrzés |
| Gyártási mennyiség | 10 db                                                      |

Ahol képernyőkép segítene, ott ezt látod: **Képernyőkép javaslat**.

## 1. Bejelentkezés

━━━━━━━━━━━━━━━━━━

1 / 22

Bejelentkezés

━━━━━━━━━━━━━━━━━━

Learning Center

- Difficulty: Beginner
- Estimated time: 3 perc
- Learning Path: Installation
- Course: Telepítés utáni első lépések

### Cél

Belépsz a KM_Production rendszerbe, hogy elérd a gyártási és raktári menüpontokat.

### Miért fontos?

A rendszer csak bejelentkezett felhasználónak engedi a munkát. Így látható marad, ki mit hozott létre vagy módosított.

### Mit kell tenned?

1. Nyisd meg a KM_Production bejelentkezési oldalát.
2. Add meg az email címedet és a jelszavadat.
3. Kattints a **Bejelentkezés** gombra.
4. Ellenőrizd, hogy megjelent-e az admin vezérlőpult.

### Minta adatok

Használd a rendszergazdától kapott felhasználói fiókot. A későbbi példában a dolgozó neve **Kiss János**, de ez nem feltétlenül ugyanaz, mint a bejelentkező felhasználó.

### Mit kell látnod?

Megjelenik a **KM Production** felső sáv, bal oldalon pedig a menü. Látnod kell például a **Cikkek**, **Helyek**, **Vevők**, **Beszállítók** és **Gyártási tervek** menüpontokat.

**Képernyőkép javaslat:** sikeres bejelentkezés utáni vezérlőpult.

## Tipp

Ha ez az első napod a rendszerben, először csak nézz körül a menüben. Nem baj, ha még nem tudod, melyik rész mire való.

### Gyakori hibák

- **Hibás email vagy jelszó.** A rendszer nem enged be. Általában elgépelés okozza. Ellenőrizd az email címet, a jelszót és a billentyűzet nyelvét.
- **Nincs jogosultság.** Be tudsz lépni, de hiányoznak menüpontok. A felhasználói szerepkör nem kapta meg a szükséges jogokat. Kérj segítséget rendszergazdától.
- **Email ellenőrzése szükséges.** A rendszer ellenőrző emailt kér. A fiók még nincs megerősítve. Nyisd meg az ellenőrző linket, vagy kérj új küldést.

### Következő lépés

Röviden átnézed, hogyan épül fel a rendszer.

## ✓ Siker!

Sikeresen beléptél a KM_Production rendszerbe.

## Mi történt?

A rendszer most már tudja, ki dolgozik benne. Innentől a munkád kapcsolható a saját felhasználói fiókodhoz.

## Ellenőrző lista

- [ ] Megnyílt a bejelentkezési oldal
- [ ] Sikerült belépni
- [ ] Látszik a felső sáv
- [ ] Látszik a bal oldali menü

## Most már tudod

- Hogyan lépsz be a rendszerbe.
- Hol látod a fő menüt.
- Miért fontos a saját felhasználói fiók.

## Próbáld ki!

Nyisd meg a profil menüt, majd térj vissza a vezérlőpultra.

## 2. Rövid áttekintés

━━━━━━━━━━━━━━━━━━

2 / 22

Rövid áttekintés

━━━━━━━━━━━━━━━━━━

Learning Center

- Difficulty: Beginner
- Estimated time: 4 perc
- Learning Path: Installation
- Course: Telepítés utáni első lépések

### Cél

Megérted, melyik menüpont mire való az első gyártási példa során.

### Miért fontos?

A KM_Production több területet köt össze: vevőrendelés, gyártás, készlet, beszerzés és minőségellenőrzés. Ha tudod, hol keresd az adatot, gyorsabban haladsz.

### Mit kell tenned?

1. Nézd végig a bal oldali menüt.
2. Keresd meg a törzsadatokat: **Cikkek**, **Helyek**, **Vevők**, **Beszállítók**, **Dolgozók**.
3. Keresd meg a gyártási részt: **Gyártási tervek**, **Gyártási feladatok**, **Üzemi felület**.
4. Keresd meg a készletet: **Készletegyenlegek**, **Készletmozgások**.
5. Keresd meg a beszerzést: **Beszerzési igények**, **Beszerzési rendelések**, **Beérkezések**.

### Minta adatok

Ebben az útmutatóban a **KM Gépgyártó Kft.** Budapesten gyárt 10 db **Acél konzol** terméket a **Minta Gépipari Kft.** részére.

### Mit kell látnod?

Látnod kell, hogy a menü csoportokra oszlik. A szürke csoportcímek nem megnyitható oldalak, hanem eligazító címek.

**Képernyőkép javaslat:** bal oldali menü a fontos menüpontokkal.

## Tipp

Gondolj a bal oldali menüre útitervként. Most még nem kell mindent megjegyezned, csak azt, hogy melyik nagy terület hol kezdődik.

### Gyakori hibák

- **Rossz menüpontot nyitsz meg.** Például riportot nyitsz szerkesztő oldal helyett. A riportok általában csak nézésre valók. Lépj vissza a megfelelő törzsadat vagy munkafolyamat oldalra.
- **Nem látsz minden menüpontot.** Jogosultsági beállítás hiányzik. Kérd a szerepkör ellenőrzését.
- **Összekevered a dolgozót és a felhasználót.** A dolgozó termelési szereplő, a felhasználó belépési fiók. Ha Kiss Jánosnak feladatot adsz, előbb dolgozóként kell szerepelnie.

### Következő lépés

Előkészíted a vállalati alapadatokat.

## ✓ Siker!

Átláttad, melyik menüpont melyik munkarészhez tartozik.

## Mi történt?

A történetünkben most kijelöltük az útvonalat: előbb alapadatokat hozunk létre, utána rendelést, gyártást, készletet és kiszállítást kezelünk.

## Ellenőrző lista

- [ ] Megnézted a törzsadat menüpontokat
- [ ] Megtaláltad a gyártási menüpontokat
- [ ] Megtaláltad a készlet menüpontokat
- [ ] Megtaláltad a beszerzési menüpontokat

## Most már tudod

- Hol találod a cikkeket.
- Hol találod a gyártási feladatokat.
- Hol tudod ellenőrizni a készletet.
- Miért több lépésből áll egy gyártási folyamat.

## Próbáld ki!

Kattints végig néhány menüponton mentés nélkül, csak hogy lásd, milyen listák nyílnak meg.

## 3. Vállalat létrehozása

━━━━━━━━━━━━━━━━━━

3 / 22

Vállalat létrehozása

━━━━━━━━━━━━━━━━━━

Learning Center

- Difficulty: Beginner
- Estimated time: 4 perc
- Learning Path: Installation
- Course: Telepítés utáni első lépések

### Cél

Rögzíted, hogy a példa a **KM Gépgyártó Kft.** működését írja le.

### Miért fontos?

A vállalati adat segít azonosítani, melyik cég gyárt, rendel, raktároz és szállít. Ha a telepítés már tartalmazza ezt az adatot, itt csak ellenőrizned kell.

### Mit kell tenned?

1. Keresd meg a vállalati vagy adminisztrációs beállításokat.
2. Ha van vállalati rekord, nyisd meg és ellenőrizd.
3. Ha nincs, hozz létre új vállalatot.
4. Mentsd az adatokat.

### Minta adatok

- Név: **KM Gépgyártó Kft.**
- Alapértelmezett telephely: **Budapest**

### Mit kell látnod?

A vállalat neve mentés után megjelenik a vállalati adatok között. Ha a telepítés egyvállalatos, lehet, hogy külön menüpont nélkül, előre beállítva szerepel.

**Képernyőkép javaslat:** vállalati adatlap vagy beállítási oldal.

## Tipp

Ha a vállalat már létezik, ne hozz létre második példányt. Ebben a lépésben az ellenőrzés is sikeres munka.

### Gyakori hibák

- **Nincs vállalati menüpont.** A telepített verzióban ez lehet előre beállított adat. Ellenőrizd a rendszergazdával, nem hiba-e.
- **Elgépelés a névben.** Később a riportokban rossz név jelenhet meg. Javítsd a vállalat nevét **KM Gépgyártó Kft.** értékre.
- **Nincs mentési jog.** A módosítás nem menthető. Kérj admin jogosultságot vagy kérd meg az adminisztrátort a beállításra.

### Következő lépés

Létrehozod vagy ellenőrzöd a budapesti telephelyet.

## ✓ Siker!

Előkészítetted a **KM Gépgyártó Kft.** vállalati alapját.

## Mi történt?

A rendszerben megjelent az a cég, amelynek a nevében az első rendelést gyártani és kiszállítani fogjuk.

## Ellenőrző lista

- [ ] A vállalat neve ellenőrizve
- [ ] A név pontosan KM Gépgyártó Kft.
- [ ] A budapesti telephely előkészítve vagy megnevezve
- [ ] A beállítás mentve vagy előre beállítottként ellenőrizve

## Most már tudod

- Miért kell vállalati alapadat.
- Mikor elég ellenőrizni egy meglévő adatot.
- Miért fontos az egységes cégnév.

## Próbáld ki!

Keresd meg, hol jelenik meg a vállalat neve a rendszerben vagy a dokumentumokban.

## 4. Telephely

━━━━━━━━━━━━━━━━━━

4 / 22

Telephely

━━━━━━━━━━━━━━━━━━

Learning Center

- Difficulty: Beginner
- Estimated time: 4 perc
- Learning Path: Installation
- Course: Telepítés utáni első lépések

### Cél

Rögzíted a **Budapest** telephelyet.

### Miért fontos?

A telephely mutatja, hol történik a gyártás és a raktározás. Ez segít a készlet és a gyártási munka követésében.

### Mit kell tenned?

1. Nyisd meg a **Gyártási egységek** vagy a kapcsolódó helykezelő oldalt.
2. Hozz létre új gyártási egységet vagy telephelyhez tartozó egységet.
3. Add meg a Budapest nevet.
4. Mentsd az adatot.

### Minta adatok

- Név: **Budapest**
- Rövid azonosító, ha kötelező: **BUD**

### Mit kell látnod?

A **Budapest** sor megjelenik a listában, aktív állapotban.

**Képernyőkép javaslat:** Budapest gyártási egység a listában.

## Tipp

A telephely a történetünk gyártási helyszíne. Most azt mondjuk meg a rendszernek, hol történik majd az Acél konzol gyártása.

### Gyakori hibák

- **Hiányzik a rövid azonosító.** A mentés nem sikerül, mert kötelező mező üres. Add meg: **BUD**.
- **Duplikált név.** Már létezik Budapest. Ne hozz létre másodikat, nyisd meg a meglévőt.
- **Inaktív telephely.** A későbbi választómezőkben nem jelenik meg. Állítsd aktívra, vagy kérj admin segítséget.

### Következő lépés

Létrehozod az alapanyagok raktárát.

## ✓ Siker!

Sikeresen létrehoztad vagy ellenőrizted a **Budapest** telephelyet.

## Mi történt?

A rendszer most már tudja, melyik helyszínhez kapcsolódik a gyártási munka és a későbbi raktári mozgás.

## Ellenőrző lista

- [ ] Budapest szerepel a listában
- [ ] Az azonosító meg van adva, ha kötelező
- [ ] A telephely aktív
- [ ] Nem hoztál létre duplikált telephelyet

## Most már tudod

- Mi a telephely szerepe.
- Miért kell aktív helyszín.
- Miért fontos a pontos név.

## Próbáld ki!

Nézd meg, hogy a Budapest telephely kiválasztható-e más űrlapokon.

## 5. Raktár

━━━━━━━━━━━━━━━━━━

5 / 22

Raktár

━━━━━━━━━━━━━━━━━━

Learning Center

- Difficulty: Beginner
- Estimated time: 4 perc
- Learning Path: Installation
- Course: Telepítés utáni első lépések

### Cél

Létrehozod az **Alapanyag raktár** helyet.

### Miért fontos?

A rendszernek tudnia kell, hol van az acéllemez, a csavar és a festék. A készlet mindig helyhez kötött.

### Mit kell tenned?

1. Nyisd meg a **Helyek** menüpontot.
2. Kattints a **Hely létrehozása** gombra.
3. Add meg a raktár nevét és típusát.
4. Mentsd.

### Minta adatok

- Név: **Alapanyag raktár**
- Hely típusa: **Raktár**
- Telephely vagy gyártási egység: **Budapest**, ha választható

### Mit kell látnod?

Az **Alapanyag raktár** megjelenik a **Helyek** listában.

**Képernyőkép javaslat:** Helyek lista az Alapanyag raktár sorral.

## Tipp

A raktár nem csak név a listában. Ez lesz az a hely, ahonnan a gyártáshoz szükséges alapanyagokat indítjuk.

### Gyakori hibák

- **Rossz helytípus.** Például műhelyt választasz raktár helyett. Javítsd a típust **Raktár** értékre.
- **Nincs Budapest kiválasztva.** Később nem egyértelmű, hová tartozik a raktár. Válaszd ki a budapesti telephelyet, ha van ilyen mező.
- **Nem mentetted a sort.** A lista frissítése után eltűnik. Hozd létre újra, majd kattints a mentésre.

### Következő lépés

Létrehozod a raktáron belüli tárolóhelyet.

## ✓ Siker!

Sikeresen létrehoztad az **Alapanyag raktár** helyet.

## Mi történt?

A rendszer most már tudja, hogy az első gyártáshoz szükséges alapanyagok melyik raktárban lesznek.

## Ellenőrző lista

- [ ] Az Alapanyag raktár létrejött
- [ ] A hely típusa raktár
- [ ] Budapesthez kapcsolódik, ha van ilyen mező
- [ ] Látszik a Helyek listában

## Most már tudod

- Miért kell raktár.
- Hol jelennek meg a raktári helyek.
- Miért nem elég csak készletmennyiséget megadni.

## Próbáld ki!

Nézd meg, milyen más helytípusokat kínál a rendszer, de most ne hozz létre új rekordot.

## 6. Tárolóhely

━━━━━━━━━━━━━━━━━━

6 / 22

Tárolóhely

━━━━━━━━━━━━━━━━━━

Learning Center

- Difficulty: Beginner
- Estimated time: 4 perc
- Learning Path: Installation
- Course: Telepítés utáni első lépések

### Cél

Rögzíted az **A-01** tárolóhelyet az **Alapanyag raktár** alatt.

### Miért fontos?

A raktár nagyobb terület, a tárolóhely pontosabb hely. Így a dolgozó tudja, hova tegye vagy honnan vegye ki az anyagot.

### Mit kell tenned?

1. Nyisd meg a **Helyek** menüpontot.
2. Hozz létre új helyet.
3. Add meg az **A-01** nevet.
4. Kapcsold az **Alapanyag raktár** raktárhoz, ha van szülőhely mező.
5. Mentsd.

### Minta adatok

- Név: **A-01**
- Hely típusa: **Raktár**
- Szülő vagy kapcsolódó hely: **Alapanyag raktár**

### Mit kell látnod?

Az **A-01** megjelenik a helylistában, és kapcsolódik az **Alapanyag raktár** helyhez.

**Képernyőkép javaslat:** A-01 tárolóhely adatlapja.

## Tipp

A tárolóhely segít a valós raktári keresésben. A történetben az alapanyagokat az **A-01** helyen fogjuk megtalálni.

### Gyakori hibák

- **A-01 külön raktárként szerepel.** Ez félrevezető lehet. Kapcsold az Alapanyag raktárhoz, ha a rendszer támogatja.
- **Eltérő írásmód.** Például `A01` vagy `A-1`. Javítsd pontosan **A-01** értékre, hogy végig azonos maradjon.
- **Nem választható később.** Lehet, hogy inaktív vagy rossz típusú. Ellenőrizd a státuszt és a típust.

### Következő lépés

Rögzíted a beszállítót.

## ✓ Siker!

Sikeresen előkészítetted az **A-01** tárolóhelyet.

## Mi történt?

A rendszer most már nemcsak azt tudja, melyik raktárban van az anyag, hanem azt is, melyik tárolóhelyen kell keresni.

## Ellenőrző lista

- [ ] Az A-01 tárolóhely létrejött
- [ ] Az Alapanyag raktárhoz kapcsolódik, ha lehetséges
- [ ] Az írásmód pontos
- [ ] A tárolóhely később kiválasztható

## Most már tudod

- Mi a különbség raktár és tárolóhely között.
- Miért kell pontos helymegnevezés.
- Hogyan segíti ez a készletkeresést.

## Próbáld ki!

Keress rá az **A-01** névre a Helyek listában.

## 7. Beszállító

━━━━━━━━━━━━━━━━━━

7 / 22

Beszállító

━━━━━━━━━━━━━━━━━━

Learning Center

- Difficulty: Beginner
- Estimated time: 4 perc
- Learning Path: Installation
- Course: Telepítés utáni első lépések

### Cél

Létrehozod az **Acél Plusz Kft.** beszállítót.

### Miért fontos?

A beszerzett alapanyagokat beszállítóhoz kell kötni. Így később visszakereshető, honnan érkezett az anyag.

### Mit kell tenned?

1. Nyisd meg a **Beszállítók** menüpontot.
2. Kattints a **Beszállító létrehozása** gombra.
3. Add meg a nevet és az elérhetőségi adatokat, ha kötelezőek.
4. Mentsd.

### Minta adatok

- Név: **Acél Plusz Kft.**
- Telefon, email, cím: használj próbaadatot, ha kötelező

### Mit kell látnod?

Az **Acél Plusz Kft.** megjelenik a beszállítók listájában.

**Képernyőkép javaslat:** Beszállítók lista az Acél Plusz Kft. sorral.

## Tipp

Mindig kereséssel kezdd, mielőtt új partnert hozol létre. Így elkerülöd a duplikált beszállítókat.

### Gyakori hibák

- **Duplikált beszállító.** Már létező céget hozol létre újra. Keress rá előbb az Acél Plusz névre.
- **Hiányzó kötelező mező.** A mentés nem sikerül. Töltsd ki a pirossal jelölt mezőket.
- **Rossz partner típus.** Vevőként rögzíted. Töröld vagy javítsd, majd hozd létre beszállítóként.

### Következő lépés

Rögzíted a vevőt.

## ✓ Siker!

Sikeresen létrehoztad az **Acél Plusz Kft.** beszállítót.

## Mi történt?

A rendszer most már tudja, kitől érkeznek majd az első gyártáshoz szükséges alapanyagok.

## Ellenőrző lista

- [ ] A beszállító neve pontos
- [ ] A rekord beszállítóként szerepel
- [ ] A kötelező mezők ki vannak töltve
- [ ] Látszik a beszállítók listájában

## Most már tudod

- Miért kell beszállító.
- Miért fontos a partner típusa.
- Hogyan segíti a beszállító a későbbi beérkezést.

## Próbáld ki!

Keress rá az **Acél Plusz** szövegre a beszállítók között.

## 8. Vevő

━━━━━━━━━━━━━━━━━━

8 / 22

Vevő

━━━━━━━━━━━━━━━━━━

Learning Center

- Difficulty: Beginner
- Estimated time: 4 perc
- Learning Path: Installation
- Course: Telepítés utáni első lépések

### Cél

Létrehozod a **Minta Gépipari Kft.** vevőt.

### Miért fontos?

A vevőrendeléshez vevő kell. Enélkül a rendszer nem tudja, kinek készül az Acél konzol.

### Mit kell tenned?

1. Nyisd meg a **Vevők** menüpontot.
2. Kattints a **Vevő létrehozása** gombra.
3. Add meg a vevő nevét.
4. Töltsd ki a kötelező cím vagy adószám mezőket, ha vannak.
5. Mentsd.

### Minta adatok

- Név: **Minta Gépipari Kft.**
- Szállítási cím: használj próba címet, ha kötelező

### Mit kell látnod?

A **Minta Gépipari Kft.** megjelenik a vevők listájában.

**Képernyőkép javaslat:** Vevők lista a Minta Gépipari Kft. sorral.

## Tipp

A vevő az a partner, akinek a gyártás végén szállítani fogunk. A történetben minden későbbi rendelési lépés ehhez a céghez kapcsolódik.

### Gyakori hibák

- **Nincs vevő.** Nem tudsz vevőrendelést létrehozni. Hozd létre a vevőt ezen a lépésen.
- **Beszállítóként rögzítetted.** A vevőrendelésnél nem jelenik meg. Hozd létre a **Vevők** alatt.
- **Hiányzó cím.** Kiszállításnál gond lehet. Add meg a szállítási címet, ha a rendszer kéri.

### Következő lépés

Rögzíted a gyártandó terméket.

## ✓ Siker!

Sikeresen létrehoztad a **Minta Gépipari Kft.** vevőt.

## Mi történt?

A rendszer most már tudja, kinek készül a 10 db Acél konzol.

## Ellenőrző lista

- [ ] A vevő neve pontos
- [ ] Vevőként szerepel, nem beszállítóként
- [ ] A kötelező címadatok kitöltve
- [ ] Látszik a Vevők listában

## Most már tudod

- Miért kell vevő a rendeléshez.
- Miért nem ugyanaz a vevő és a beszállító.
- Hogyan kapcsolódik a vevő a kiszállításhoz.

## Próbáld ki!

Nyisd meg a vevő adatlapját, és nézd meg, milyen mezőket tudnál még kitölteni.

## 9. Termék

━━━━━━━━━━━━━━━━━━

9 / 22

Termék

━━━━━━━━━━━━━━━━━━

Learning Center

- Difficulty: Beginner
- Estimated time: 4 perc
- Learning Path: Installation
- Course: Telepítés utáni első lépések

### Cél

Létrehozod az **Acél konzol** készterméket **PRD-1001** cikkszámmal.

### Miért fontos?

A gyártási rendelés mindig egy cikkre vonatkozik. A termék cikkszáma alapján találja meg a rendszer a darabjegyzéket és a műveleti sort.

### Mit kell tenned?

1. Nyisd meg a **Cikkek** menüpontot.
2. Kattints a **Cikk létrehozása** gombra.
3. Add meg a nevet és cikkszámot.
4. Válaszd a **Késztermék** vagy gyártott termék típust.
5. Jelöld, hogy sorozatszám szükséges, ha van ilyen mező.
6. Mentsd.

### Minta adatok

- Név: **Acél konzol**
- Cikkszám: **PRD-1001**
- Típus: **Késztermék**
- Egység: **db**
- Sorozatszám szükséges: **Igen**

### Mit kell látnod?

A **PRD-1001 - Acél konzol** megjelenik a cikkek között.

**Képernyőkép javaslat:** Acél konzol cikk adatlapja.

## Tipp

A cikkszám legyen rövid, egyedi és következetes. Ebben a kurzusban végig a **PRD-1001** cikkszámot keresd.

## Figyelem

Ha a terméket beszerzett anyagként rögzíted, a gyártási folyamat később félreértheti a szerepét.

### Gyakori hibák

- **Rossz cikkszám.** Később nem találod a terméket. Javítsd pontosan **PRD-1001** értékre.
- **Beszerzett anyag típust választottál.** A rendszer nem kezeli késztermékként. Állítsd késztermék vagy gyártott cikk típusra.
- **Nincs sorozatszám jelölés.** A késztermék nyomon követése hiányos lehet. Kapcsold be, ha a mező elérhető.

### Következő lépés

Rögzíted az alapanyagokat.

## ✓ Siker!

Sikeresen létrehoztad az **Acél konzol** készterméket.

## Mi történt?

A rendszer most már tudja, melyik terméket kell legyártani a Minta Gépipari Kft. rendeléséhez.

## Ellenőrző lista

- [ ] A termék neve Acél konzol
- [ ] A cikkszám PRD-1001
- [ ] A típus késztermék vagy gyártott termék
- [ ] A termék látszik a Cikkek listában

## Most már tudod

- Miért kell termékcikk.
- Miért fontos a cikkszám.
- Miért más a késztermék és a beszerzett anyag.

## Próbáld ki!

Keress rá a **PRD-1001** cikkszámra a Cikkek listában.

## 10. Alapanyagok

━━━━━━━━━━━━━━━━━━

10 / 22

Alapanyagok

━━━━━━━━━━━━━━━━━━

Learning Center

- Difficulty: Beginner
- Estimated time: 5 perc
- Learning Path: Installation
- Course: Telepítés utáni első lépések

### Cél

Létrehozod a három szükséges alapanyagot: acéllemez, csavar és festék.

### Miért fontos?

A darabjegyzék ezekből számolja ki, mire van szükség 10 db Acél konzol gyártásához.

### Mit kell tenned?

1. Nyisd meg a **Cikkek** menüpontot.
2. Hozd létre a **4 mm acéllemez** cikket.
3. Hozd létre az **M8×20** csavart.
4. Hozd létre a **Szürke porfesték** cikket.
5. Mindegyiket beszerzett anyagként mentsd.

### Minta adatok

- **4 mm acéllemez**, cikkszám: **MAT-0001**, típus: **Beszerzett anyag**, egység: **db** vagy **m2**
- **M8×20**, cikkszám: **MAT-0100**, típus: **Beszerzett anyag**, egység: **db**
- **Szürke porfesték**, cikkszám: **MAT-0200**, típus: **Beszerzett anyag**, egység: **kg**

### Mit kell látnod?

Mindhárom alapanyag megjelenik a **Cikkek** listában.

**Képernyőkép javaslat:** Cikkek lista a három alapanyaggal.

## Tipp

Az alapanyagoknál a típus legalább olyan fontos, mint a név. Ezeket most nem gyártjuk, hanem beszállítótól szerezzük be.

## Figyelem

Ne változtasd meg a három cikkszámot, mert a későbbi BOM és készletfeltöltés ezekre épül.

### Gyakori hibák

- **Nincs alapanyag.** Nem tudsz BOM-ot összeállítani. Hozd létre mindhárom cikket.
- **Készterméknek jelölted az alapanyagot.** A készlet és beszerzés félrevezető lesz. Állítsd beszerzett anyagra.
- **Eltérő cikkszámot adtál meg.** A példában nehéz lesz követni. Javítsd a cikkszámokat: **MAT-0001**, **MAT-0100**, **MAT-0200**.

### Következő lépés

Rögzíted a dolgozót.

## ✓ Siker!

Sikeresen létrehoztad az Acél konzol három alapanyagát.

## Mi történt?

A rendszer most már ismeri azokat az anyagokat, amelyekből a 10 db Acél konzol elkészül.

## Ellenőrző lista

- [ ] MAT-0001 létrejött
- [ ] MAT-0100 létrejött
- [ ] MAT-0200 létrejött
- [ ] Mindhárom cikk beszerzett anyag

## Most már tudod

- Mi az alapanyag szerepe.
- Miért kell külön cikk minden anyagnak.
- Miért épül később a BOM ezekre a cikkekre.

## Próbáld ki!

Nyisd meg mindhárom alapanyagot, és ellenőrizd a cikkszámokat mentés nélkül.

## 11. Dolgozó

━━━━━━━━━━━━━━━━━━

11 / 22

Dolgozó

━━━━━━━━━━━━━━━━━━

Learning Center

- Difficulty: Beginner
- Estimated time: 4 perc
- Learning Path: Installation
- Course: Telepítés utáni első lépések

### Cél

Létrehozod **Kiss János** dolgozót.

### Miért fontos?

A gyártási feladatokhoz dolgozó tartozik. Így látható, ki végezte a munkát és kihez vannak rendelve a feladatok.

### Mit kell tenned?

1. Nyisd meg a **Dolgozók** menüpontot.
2. Kattints a **Dolgozó létrehozása** gombra.
3. Add meg a dolgozó nevét.
4. Válassz szakmai szerepet, ha kötelező.
5. Mentsd.

### Minta adatok

- Név: **Kiss János**
- Dolgozói azonosító: **EMP-1001**, ha kötelező
- Szakmai szerep: **Operátor** vagy a rendszerben elérhető megfelelő szerep

### Mit kell látnod?

**Kiss János** megjelenik a dolgozók listájában.

**Képernyőkép javaslat:** Dolgozók lista Kiss János sorral.

## Tipp

A dolgozó a termelési munkát végzi, a felhasználó pedig belép a rendszerbe. A kettő kapcsolódhat, de nem ugyanaz.

### Gyakori hibák

- **Nincs dolgozó.** A gyártási feladat nem rendelhető ki. Hozd létre Kiss Jánost.
- **Nincs szakmai szerep.** A mentés vagy későbbi hozzárendelés hibázhat. Hozz létre vagy válassz megfelelő szakmai szerepet.
- **Felhasználóként hoztad létre, nem dolgozóként.** A belépési fiók nem elég a termelési feladatokhoz. Hozd létre a **Dolgozók** alatt is.

### Következő lépés

Felépíted a műveletsort.

## ✓ Siker!

Sikeresen létrehoztad **Kiss János** dolgozót.

## Mi történt?

A rendszer most már tudja, kihez rendelhető az első gyártási feladat.

## Ellenőrző lista

- [ ] Kiss János létrejött
- [ ] A dolgozói azonosító meg van adva, ha kötelező
- [ ] Van szakmai szerep, ha kötelező
- [ ] A dolgozó látszik a listában

## Most már tudod

- Miért kell dolgozó a gyártáshoz.
- Miért külön fogalom a dolgozó és a felhasználó.
- Hogyan kapcsolódik a dolgozó a feladatokhoz.

## Próbáld ki!

Nézd meg, hogy Kiss János kiválasztható-e egy gyártási feladat dolgozó mezőjében.

## 12. Műveletsor

━━━━━━━━━━━━━━━━━━

12 / 22

Műveletsor

━━━━━━━━━━━━━━━━━━

Learning Center

- Difficulty: Beginner
- Estimated time: 5 perc
- Learning Path: Installation
- Course: Telepítés utáni első lépések

### Cél

Létrehozod az Acél konzol gyártási lépéseit.

### Miért fontos?

A műveletsor mondja meg, milyen sorrendben kell dolgozni. A műveleteket nem érdemes átugrani, mert a gyártási előzménynek követhetőnek kell maradnia.

### Mit kell tenned?

1. Nyisd meg a **Művelettípusok** menüpontot.
2. Hozd létre a szükséges művelettípusokat, ha még nem léteznek.
3. Nyisd meg a **Műveleti sorok** menüpontot.
4. Hozz létre új műveleti sort az **Acél konzol** termékhez.
5. Add hozzá sorrendben az öt műveletet.
6. Mentsd a műveleti sort.

### Minta adatok

Műveletek sorrendben:

1. **Lézervágás**
2. **Hajlítás**
3. **Hegesztés**
4. **Festés**
5. **Minőségellenőrzés**

### Mit kell látnod?

Az Acél konzolhoz tartozó műveleti sor öt lépést tartalmaz, a fenti sorrendben.

**Képernyőkép javaslat:** műveleti sor lépései.

## Tipp

Olvasd fel magadnak a műveleteket sorrendben. Ha a sorrend a valós műhelyben is értelmes, jó úton jársz.

## Figyelem

A műveletsor később gyártási előzmény lesz. Ne hagyj ki olyan lépést, amelyet a valóságban is el kell végezni.

### Gyakori hibák

- **Nincs műveletsor.** Gyártási rendelésből nem lesz végrehajtható feladat. Hozd létre a műveleti sort.
- **Rossz sorrend.** Például festés kerül hegesztés elé. Rendezd vissza a megadott sorrendre.
- **Hiányzik a minőségellenőrzés.** A késztermék nem lesz ellenőrizve. Add hozzá az utolsó lépést.

### Következő lépés

Létrehozod a BOM-ot, vagyis a darabjegyzéket.

## ✓ Siker!

Sikeresen létrehoztad az Acél konzol műveletsorát.

## Mi történt?

A rendszer most már tudja, milyen sorrendben kell végigvezetni a gyártást a lézervágástól a minőségellenőrzésig.

## Ellenőrző lista

- [ ] Mind az öt művelet szerepel
- [ ] A sorrend helyes
- [ ] A műveletsor az Acél konzolhoz kapcsolódik
- [ ] A minőségellenőrzés is benne van

## Most már tudod

- Mi a műveletsor.
- Miért számít a sorrend.
- Miért fontos a minőségellenőrzési lépés.

## Próbáld ki!

Képzeld el a műhely útvonalát, és ellenőrizd, hogy a sorrend követi-e a valós munkát.

## 13. BOM

━━━━━━━━━━━━━━━━━━

13 / 22

BOM

━━━━━━━━━━━━━━━━━━

Learning Center

- Difficulty: Beginner
- Estimated time: 5 perc
- Learning Path: Installation
- Course: Telepítés utáni első lépések

### Cél

Megadod, miből készül egy darab **Acél konzol**.

### Miért fontos?

A BOM, más néven darabjegyzék, a termék receptje. Ebből tudja a rendszer, milyen anyagokat kell lefoglalni vagy felhasználni.

### Mit kell tenned?

1. Nyisd meg a **Darabjegyzékek** menüpontot.
2. Hozz létre új BOM-ot az **Acél konzol** termékhez.
3. Add hozzá az alapanyag sorokat.
4. Mentsd a BOM-ot.

### Minta adatok

Egy darab **Acél konzol** anyagai:

| Anyag            | Cikkszám | Mennyiség |
| ---------------- | -------- | --------- |
| 4 mm acéllemez   | MAT-0001 | 1 db      |
| M8×20            | MAT-0100 | 4 db      |
| Szürke porfesték | MAT-0200 | 0,1 kg    |

10 db gyártásához ez 10 db acéllemez, 40 db csavar és 1 kg festék.

### Mit kell látnod?

A BOM az **Acél konzol** termékhez kapcsolódik, és három tételsort tartalmaz.

**Képernyőkép javaslat:** BOM tételek az Acél konzolhoz.

## Tipp

A BOM-ot úgy képzeld el, mint a termék anyaglistáját. Ha egy darabhoz jó, a rendszer ebből tud számolni 10 darabra is.

## Figyelem

Ha a BOM hiányzik vagy rossz termékhez kapcsolódik, a gyártási rendelés nem fogja jól látni az anyagszükségletet.

### Gyakori hibák

- **Nincs BOM.** A rendszer nem tudja kiszámolni az anyagszükségletet. Hozd létre a darabjegyzéket.
- **Rossz mennyiség.** Például 4 csavar helyett 1 szerepel. Javítsd a mennyiségeket a táblázat szerint.
- **Nem az Acél konzolhoz kapcsolódik.** A gyártási terv nem találja. Válaszd ki a **PRD-1001** terméket.

### Következő lépés

Feltöltöd az alapanyag készletet.

## ✓ Siker!

Sikeresen létrehoztad az Acél konzol darabjegyzékét.

## Mi történt?

Mostantól minden Acél konzol ugyanebből az anyaglistából készül: acéllemezből, csavarból és porfestékből.

## Ellenőrző lista

- [ ] A BOM az Acél konzolhoz kapcsolódik
- [ ] MAT-0001 szerepel a BOM-ban
- [ ] MAT-0100 szerepel a BOM-ban
- [ ] MAT-0200 szerepel a BOM-ban
- [ ] A mennyiségek helyesek

## Most már tudod

- Mit jelent a BOM.
- Miért nevezhető darabjegyzéknek.
- Hogyan lesz az egy darabos anyaglistából 10 darabos igény.

## Próbáld ki!

Számold ki fejben, mennyi csavar kellene 5 db Acél konzolhoz.

## 14. Készlet feltöltése

━━━━━━━━━━━━━━━━━━

14 / 22

Készlet feltöltése

━━━━━━━━━━━━━━━━━━

Learning Center

- Difficulty: Beginner
- Estimated time: 5 perc
- Learning Path: Installation
- Course: Telepítés utáni első lépések

### Cél

Raktárra veszed a gyártáshoz szükséges anyagokat az **Alapanyag raktár / A-01** helyre.

### Miért fontos?

Gyártani csak akkor lehet, ha van elegendő elérhető készlet. A készletet nem kézzel kell átírni, hanem beérkezéssel vagy készletmozgással kell rögzíteni.

### Mit kell tenned?

1. Nyisd meg a **Beérkezések** menüpontot.
2. Hozz létre beérkezést az **Acél Plusz Kft.** beszállítótól.
3. Add hozzá a három alapanyagot.
4. Állítsd a célhelyet **Alapanyag raktár / A-01** értékre.
5. Könyveld a beérkezést.
6. Ellenőrizd a **Készletegyenlegek** oldalon.

### Minta adatok

| Anyag            | Cikkszám | Beérkező mennyiség |
| ---------------- | -------- | ------------------ |
| 4 mm acéllemez   | MAT-0001 | 20 db              |
| M8×20            | MAT-0100 | 100 db             |
| Szürke porfesték | MAT-0200 | 5 kg               |

### Mit kell látnod?

A **Készletegyenlegek** oldalon elegendő elérhető mennyiség látszik mindhárom anyagból.

**Képernyőkép javaslat:** készletegyenlegek a három alapanyaggal.

## Tipp

A készletet mindig eseménnyel növeld, például beérkezéssel. Így később is látható lesz, honnan jött az anyag.

## Figyelem

Ne kézzel írd át a készletmennyiséget. A KM_Production készletváltozásait készletmozgásoknak kell magyarázniuk.

### Gyakori hibák

- **Nincs készlet.** A gyártás anyaghiányt jelez. Könyveld a beérkezést.
- **Nem könyvelt beérkezés.** A beérkezés létezik, de a készlet nem nőtt. Kattints a **Beérkezés könyvelése** műveletre.
- **Rossz helyre érkezett az anyag.** A gyártás nem találja ott, ahol keresed. Ellenőrizd a célhelyet: **Alapanyag raktár / A-01**.

### Következő lépés

Felveszed a vevői megrendelést.

## ✓ Siker!

Sikeresen feltöltötted az első gyártáshoz szükséges alapanyagkészletet.

## Mi történt?

A rendszer most már látja, hogy az Alapanyag raktár A-01 helyén van elég acéllemez, csavar és festék a gyártáshoz.

## Ellenőrző lista

- [ ] A beérkezés létrejött
- [ ] Az Acél Plusz Kft. a beszállító
- [ ] Mindhárom alapanyag szerepel
- [ ] A beérkezés könyvelt
- [ ] A készletegyenlegekben látszik a készlet

## Most már tudod

- Miért kell beérkezést könyvelni.
- Miért helyhez kötött a készlet.
- Miért fontos a készletmozgás.

## Próbáld ki!

Nyisd meg a **Készletmozgások** oldalt, és keresd meg a beérkezéshez tartozó mozgásokat.

## 15. Megrendelés felvétele

━━━━━━━━━━━━━━━━━━

15 / 22

Megrendelés felvétele

━━━━━━━━━━━━━━━━━━

Learning Center

- Difficulty: Beginner
- Estimated time: 5 perc
- Learning Path: Installation
- Course: Telepítés utáni első lépések

### Cél

Létrehozod a vevőrendelést 10 db **Acél konzol** termékre.

### Miért fontos?

A vevőrendelés indítja el az üzleti igényt. Ebből lesz gyártási terv, majd gyártási rendelés.

### Mit kell tenned?

1. Nyisd meg a **Vevőrendelések** menüpontot.
2. Kattints a **Rendelés létrehozása** gombra.
3. Válaszd ki a **Minta Gépipari Kft.** vevőt.
4. Adj hozzá rendelési tételt: **Acél konzol**, 10 db.
5. Mentsd a rendelést.
6. Erősítsd meg a rendelést, ha a rendszer külön kéri.

### Minta adatok

- Vevő: **Minta Gépipari Kft.**
- Termék: **Acél konzol**
- Cikkszám: **PRD-1001**
- Mennyiség: **10 db**

### Mit kell látnod?

A vevőrendelés megjelenik a listában, és megerősített vagy továbbléptethető állapotban van.

**Képernyőkép javaslat:** vevőrendelés adatlap a 10 db Acél konzol tétellel.

## Tipp

Itt érkezik meg a történet üzleti indítása. A rendelés mondja ki, hogy a Minta Gépipari Kft. 10 db Acél konzolt kér.

## Figyelem

Ha a rendelés piszkozatban marad, a gyártástervezés nem feltétlenül tud továbbindulni belőle.

### Gyakori hibák

- **Nincs vevő.** Nem választható ki a rendeléshez. Hozd létre a **Minta Gépipari Kft.** vevőt.
- **Nincs termék.** Nem tudsz tételt hozzáadni. Hozd létre a **PRD-1001** Acél konzolt.
- **Piszkozatban maradt.** A gyártástervezés nem indul. Erősítsd meg a vevőrendelést.

### Következő lépés

Gyártási tervet és gyártási rendelést készítesz.

## ✓ Siker!

Sikeresen felvetted az első vevőrendelést.

## Mi történt?

A rendszerben létrejött az üzleti igény: a Minta Gépipari Kft. 10 db Acél konzolt vár.

## Ellenőrző lista

- [ ] A vevő Minta Gépipari Kft.
- [ ] A termék Acél konzol
- [ ] A mennyiség 10 db
- [ ] A rendelés mentve
- [ ] A rendelés megerősített vagy továbbléptethető

## Most már tudod

- Miért indul a folyamat vevőrendeléssel.
- Hogyan kapcsolódik a vevő a termékhez.
- Miért fontos a rendelés állapota.

## Próbáld ki!

Nyisd meg a rendelést, és keresd meg rajta a kapcsolódó tételek listáját.

## 16. Gyártási rendelés

━━━━━━━━━━━━━━━━━━

16 / 22

Gyártási rendelés

━━━━━━━━━━━━━━━━━━

Learning Center

- Difficulty: Beginner
- Estimated time: 5 perc
- Learning Path: Installation
- Course: Telepítés utáni első lépések

### Cél

A vevőrendelésből gyártási tervet, majd gyártási rendelést hozol létre.

### Miért fontos?

A gyártási rendelés mondja meg a műhelynek, mit, mennyit és milyen folyamat szerint kell legyártani.

### Mit kell tenned?

1. Nyisd meg a **Gyártási tervek** menüpontot.
2. Hozz létre tervet a megerősített vevőrendeléshez.
3. Add hozzá a **PRD-1001 - Acél konzol** tételt 10 db mennyiséggel.
4. Ellenőrizd, hogy van BOM és műveleti sor.
5. Hagyd jóvá a gyártási tervet.
6. Kattints a **Gyártási rendelések generálása** műveletre.

### Minta adatok

- Vevőrendelés: **Minta Gépipari Kft. - Acél konzol - 10 db**
- Termék: **PRD-1001**
- Mennyiség: **10 db**

### Mit kell látnod?

A gyártási tervhez megjelenik a kapcsolódó gyártási rendelés. Az állapot tervezett vagy gyártásra kész lehet.

**Képernyőkép javaslat:** gyártási terv kapcsolódó gyártási rendelésekkel.

## Tipp

A gyártási terv a rendelés és a műhely közötti híd. Innen lesz a vevői igényből tényleges gyártási munka.

## Figyelem

BOM és műveletsor nélkül a gyártási rendelés nem tud teljes értékű végrehajtási munkává válni.

### Gyakori hibák

- **Nincs BOM.** A rendszer nem tudja, milyen anyag kell. Menj vissza a **Darabjegyzékek** oldalra.
- **Nincs műveletsor.** Nem tud gyártási feladatokat készíteni. Menj vissza a **Műveleti sorok** oldalra.
- **A terv nincs jóváhagyva.** A rendelésgenerálás nem engedélyezett. Hagyd jóvá a tervet.

### Következő lépés

Elindítod a gyártást feladatok generálásával.

## ✓ Siker!

Sikeresen létrehoztad a gyártási rendelést az Acél konzolhoz.

## Mi történt?

A rendszer most már nemcsak azt tudja, mit rendelt a vevő, hanem azt is, hogy ebből gyártási munka készül.

## Ellenőrző lista

- [ ] A gyártási terv létrejött
- [ ] A tervben 10 db PRD-1001 szerepel
- [ ] Van BOM
- [ ] Van műveletsor
- [ ] A gyártási rendelés generálva

## Most már tudod

- Mi a gyártási terv szerepe.
- Miért kell jóváhagyás.
- Hogyan lesz rendelésből gyártási rendelés.

## Próbáld ki!

Nyisd meg a gyártási tervet, és nézd meg, hol jelennek meg a kapcsolódó gyártási rendelések.

## 17. Gyártás elindítása

━━━━━━━━━━━━━━━━━━

17 / 22

Gyártás elindítása

━━━━━━━━━━━━━━━━━━

Learning Center

- Difficulty: Beginner
- Estimated time: 5 perc
- Learning Path: Installation
- Course: Telepítés utáni első lépések

### Cél

Gyártási feladatokat generálsz a gyártási rendelésből, majd elindítod az első feladatot.

### Miért fontos?

A gyártási rendelés még terv. A gyártási feladat az, amit a dolgozó ténylegesen elvégez.

### Mit kell tenned?

1. Nyisd meg a **Gyártási feladatok** menüpontot.
2. Kattints a **Generálás rendelésből** műveletre.
3. Válaszd ki az Acél konzol gyártási rendelést.
4. Generáld a feladatokat.
5. Nyisd meg az első feladatot.
6. Rendeld **Kiss János** dolgozóhoz, ha szükséges.
7. Kattints az **Indítás** gombra.

### Minta adatok

- Gyártási rendelés: **Acél konzol - 10 db**
- Dolgozó: **Kiss János**
- Első művelet: **Lézervágás**

### Mit kell látnod?

A feladat állapota **Folyamatban** lesz, és Kiss Jánoshoz kapcsolódik.

**Képernyőkép javaslat:** elindított Lézervágás feladat.

## Tipp

A feladat az a napi munkadarab, amit a műhely már végre tud hajtani. A gyártási rendelésből ezért kell feladatokat készíteni.

## Figyelem

Ne generálj újra feladatokat, ha a rendszer már létrehozta őket. Keresd meg a meglévő feladatokat.

### Gyakori hibák

- **Már generálták a feladatokat.** A rendszer nem enged duplikálni. Nyisd meg a meglévő feladatokat.
- **Nincs dolgozó.** Nem tudod hozzárendelni. Hozd létre Kiss Jánost a **Dolgozók** alatt.
- **Nem indítható állapot.** A feladat még nem kész vagy már folyamatban van. Ellenőrizd az állapotot és az előző lépéseket.

### Következő lépés

Végrehajtod és lezárod a műveleteket.

## ✓ Siker!

Sikeresen elindítottad az első gyártási feladatot.

## Mi történt?

A történetben a munka eljutott a műhelybe: Kiss János elkezdheti az Acél konzol első műveletét.

## Ellenőrző lista

- [ ] A feladatok generálva
- [ ] Az első feladat megnyitva
- [ ] Kiss János hozzárendelve, ha szükséges
- [ ] A feladat állapota folyamatban

## Most már tudod

- Mi a gyártási feladat.
- Miért kell rendelésből feladatot generálni.
- Mit jelent a feladat indítása.

## Próbáld ki!

Nézd meg az **Üzemi felület** oldalt, és keresd meg az aktív feladatot.

## 18. Műveletek végrehajtása

━━━━━━━━━━━━━━━━━━

18 / 22

Műveletek végrehajtása

━━━━━━━━━━━━━━━━━━

Learning Center

- Difficulty: Beginner
- Estimated time: 6 perc
- Learning Path: Installation
- Course: Telepítés utáni első lépések

### Cél

Sorrendben elvégzed a Lézervágás, Hajlítás, Hegesztés és Festés műveleteket.

### Miért fontos?

A gyártási előzmény akkor megbízható, ha látszik, melyik művelet mikor és ki által készült el.

### Mit kell tenned?

1. Nyisd meg az aktuális gyártási feladatot.
2. Rögzítsd a felhasznált anyagot, ha a feladat kéri.
3. Fejezd be a műveletet.
4. Indítsd el a következő műveletet.
5. Ismételd a folyamatot a festésig.

### Minta adatok

10 db Acél konzolhoz rögzített felhasználás:

- **4 mm acéllemez:** 10 db
- **M8×20:** 40 db
- **Szürke porfesték:** 1 kg

### Mit kell látnod?

A műveletek sorban **Befejezett** állapotba kerülnek. A készletmozgások között megjelenik a gyártási felhasználás.

**Képernyőkép javaslat:** befejezett művelet és rögzített anyagfelhasználás.

## Tipp

Mindig az aktuális műveletnél rögzítsd azt, ami tényleg megtörtént. Így a gyártási előzmény később is érthető marad.

## Figyelem

Az anyagfelhasználás készletet csökkent. Csak olyan mennyiséget rögzíts, amelyet valóban felhasználtatok.

### Gyakori hibák

- **Nincs elég készlet.** Az anyagfelhasználás nem menthető. Ellenőrizd a beérkezést és a készletegyenleget.
- **Rossz mennyiséget rögzítesz.** A készlet és az önköltség pontatlan lesz. Javítsd a mennyiséget a BOM alapján.
- **Átugrott művelet.** A követhetőség sérül. Menj vissza a hiányzó feladathoz, és fejezd be szabályosan.

### Következő lépés

Elvégzed a minőségellenőrzést.

## ✓ Siker!

Sikeresen végigvitted a fő gyártási műveleteket.

## Mi történt?

A rendszerben már látszik, hogy az Acél konzol gyártása nem csak terv volt: műveletek készültek el, és anyagfelhasználás történt.

## Ellenőrző lista

- [ ] Lézervágás befejezve
- [ ] Hajlítás befejezve
- [ ] Hegesztés befejezve
- [ ] Festés befejezve
- [ ] Az anyagfelhasználás rögzítve

## Most már tudod

- Hogyan halad a gyártás műveletről műveletre.
- Miért kell anyagfelhasználást rögzíteni.
- Miért fontos a befejezett állapot.

## Próbáld ki!

Nyisd meg a készletmozgásokat, és keresd meg a gyártási felhasználás sorait.

## 19. Minőségellenőrzés

━━━━━━━━━━━━━━━━━━

19 / 22

Minőségellenőrzés

━━━━━━━━━━━━━━━━━━

Learning Center

- Difficulty: Beginner
- Estimated time: 4 perc
- Learning Path: Installation
- Course: Telepítés utáni első lépések

### Cél

Rögzíted, hogy a 10 db Acél konzol megfelelt a minőségellenőrzésen.

### Miért fontos?

A késztermék csak akkor legyen készleten vagy kiszállítva, ha a szükséges ellenőrzés megtörtént.

### Mit kell tenned?

1. Nyisd meg a **Minőségellenőrzés** művelethez tartozó gyártási feladatot.
2. Indítsd el a feladatot, ha még nem fut.
3. Kattints az **Ellenőrzés rögzítése** műveletre.
4. Válaszd az **Elfogadott** eredményt.
5. Írj rövid megjegyzést.
6. Mentsd, majd fejezd be a feladatot.

### Minta adatok

- Ellenőr vagy dolgozó: **Kiss János**
- Eredmény: **Elfogadott**
- Megjegyzés: **10 db Acél konzol ellenőrizve, eltérés nélkül.**

### Mit kell látnod?

A minőségellenőrzési eredmény **Elfogadott**, a feladat pedig befejezett vagy továbbengedett állapotú.

**Képernyőkép javaslat:** elfogadott minőségellenőrzés.

## Tipp

A minőségellenőrzés nem akadály, hanem kapu. Azt erősíti meg, hogy a késztermék továbbmehet raktárra vagy kiszállításra.

## Figyelem

Elutasított vagy utómunkás eredmény esetén ne kezeld a terméket elfogadott késztermékként.

### Gyakori hibák

- **A feladat nem ellenőrizhető.** Nem megfelelő állapotban van. Fejezd be az előző műveleteket.
- **Elutasított eredményt választottál.** A késztermék nem mehet tovább elfogadottként. Ha téves volt, javítsd a folyamat szabályai szerint, vagy rögzíts utóellenőrzést.
- **Nincs megjegyzés vagy bizonyíték.** Később nehezebb megérteni az ellenőrzést. Írj rövid, világos megjegyzést.

### Következő lépés

Késztermékként raktárra veszed az elfogadott darabokat.

## ✓ Siker!

Sikeresen rögzítetted az elfogadott minőségellenőrzést.

## Mi történt?

A rendszer most már tudja, hogy a 10 db Acél konzol megfelelt, és továbbléphet a késztermék készlet felé.

## Ellenőrző lista

- [ ] A minőségellenőrzési feladat megnyitva
- [ ] Az eredmény Elfogadott
- [ ] A megjegyzés kitöltve
- [ ] Az ellenőrzés mentve
- [ ] A feladat befejezve vagy továbbengedve

## Most már tudod

- Miért kell minőségellenőrzés.
- Mit jelent az elfogadott eredmény.
- Miért fontos a megjegyzés.

## Próbáld ki!

Nézd meg, milyen más eredmények választhatók, de ne ments el téves ellenőrzést.

## 20. Késztermék raktárra vétele

━━━━━━━━━━━━━━━━━━

20 / 22

Késztermék raktárra vétele

━━━━━━━━━━━━━━━━━━

Learning Center

- Difficulty: Beginner
- Estimated time: 5 perc
- Learning Path: Installation
- Course: Telepítés utáni első lépések

### Cél

Készletre veszed a 10 db **Acél konzol** készterméket.

### Miért fontos?

A késztermék akkor szállítható, ha megjelenik a készleten. A készletnövekedésnek gyártási bevétként kell látszania.

### Mit kell tenned?

1. Nyisd meg a befejezett gyártási feladatot vagy gyártási rendelést.
2. Válaszd a késztermék raktárra vételét, ha elérhető.
3. Add meg a mennyiséget: 10 db.
4. Válaszd ki a késztermék helyét. Ha nincs külön készáru raktár, használd a rendszerben elérhető megfelelő készlethelyet.
5. Mentsd vagy könyveld a műveletet.
6. Ellenőrizd a **Készletegyenlegek** és **Készletmozgások** oldalakat.

### Minta adatok

- Termék: **Acél konzol**
- Cikkszám: **PRD-1001**
- Mennyiség: **10 db**
- Mozgástípus: **Gyártási bevét**

### Mit kell látnod?

A készleten megjelenik 10 db **PRD-1001 - Acél konzol**. A készletmozgások között gyártási bevét látszik.

**Képernyőkép javaslat:** késztermék készletegyenleg és gyártási bevét mozgás.

## Tipp

A késztermék raktárra vétele azt jelzi, hogy a gyártás eredménye már készletként kezelhető.

## Figyelem

Csak elfogadott és ténylegesen elkészült mennyiséget vegyél raktárra.

### Gyakori hibák

- **Nincs elfogadott minőségellenőrzés.** A rendszer nem engedi készletre venni. Rögzíts elfogadott ellenőrzést.
- **Rossz mennyiség.** Több vagy kevesebb kerül készletre. Javítsd 10 db-ra, vagy rögzíts szabályos korrekciót.
- **Nem keletkezik készletmozgás.** A készlet nem magyarázható. Ellenőrizd, hogy könyvelt gyártási bevét történt-e.

### Következő lépés

Kiszállítod a készterméket a vevőnek.

## ✓ Siker!

Sikeresen készletre vetted a 10 db Acél konzolt.

## Mi történt?

A rendszer most már késztermékként látja az Acél konzolt. A gyártás eredménye megjelent a készleten.

## Ellenőrző lista

- [ ] A termék PRD-1001
- [ ] A mennyiség 10 db
- [ ] A készletre vétel mentve vagy könyvelve
- [ ] Látszik a készletegyenlegben
- [ ] Látszik a gyártási bevét a készletmozgások között

## Most már tudod

- Mit jelent a késztermék raktárra vétele.
- Miért kell gyártási bevét.
- Hogyan ellenőrizhető a késztermék készlete.

## Próbáld ki!

Keresd meg a **PRD-1001** készletegyenleget a készlet oldalon.

## 21. Kiszállítás

━━━━━━━━━━━━━━━━━━

21 / 22

Kiszállítás

━━━━━━━━━━━━━━━━━━

Learning Center

- Difficulty: Beginner
- Estimated time: 5 perc
- Learning Path: Installation
- Course: Telepítés utáni első lépések

### Cél

Rögzíted, hogy a 10 db **Acél konzol** kiszállításra került a **Minta Gépipari Kft.** részére.

### Miért fontos?

A kiszállítás zárja le a vevői igényt. A készlet csökken, a rendelés teljesítetté válik.

### Mit kell tenned?

1. Nyisd meg a vevőrendelést.
2. Ellenőrizd, hogy a késztermék készleten van és minőségileg elfogadott.
3. Válaszd a kiszállítás vagy lezárás műveletet, ha elérhető.
4. Add meg a kiszállított mennyiséget: 10 db.
5. Mentsd vagy könyveld a kiszállítást.
6. Ellenőrizd a rendelés állapotát.

### Minta adatok

- Vevő: **Minta Gépipari Kft.**
- Termék: **Acél konzol**
- Cikkszám: **PRD-1001**
- Kiszállított mennyiség: **10 db**

### Mit kell látnod?

A vevőrendelés teljesített vagy kiszállított állapotú. A késztermék készlete 10 db-bal csökkenhet, ha a rendszer a kiszállítást készletmozgással kezeli.

**Képernyőkép javaslat:** teljesített vevőrendelés.

## Tipp

A kiszállításnál visszatérünk oda, ahonnan indultunk: a vevőrendeléshez. Most azt zárjuk le, amit a Minta Gépipari Kft. kért.

## Figyelem

Ne zárj le rendelést, ha nincs meg a teljes és elfogadott késztermékmennyiség.

### Gyakori hibák

- **Nincs késztermék készleten.** Nem lehet kiszállítani. Ellenőrizd a raktárra vételt.
- **Részmennyiséget szállítasz.** A rendelés nem zárul teljesen. Add meg a teljes 10 db mennyiséget, ha tényleg elkészült.
- **Nincs jogosultság a lezáráshoz.** A gomb nem látszik vagy hibát ad. Kérj megfelelő jogosultságot.

### Következő lépés

Áttekinted, mit tanultál meg.

## ✓ Siker!

Sikeresen rögzítetted az első kiszállítást.

## Mi történt?

A 10 db Acél konzol eljutott a vevőhöz. A vevői igény teljesült, és a folyamat a gyártástól a kiszállításig követhető maradt.

## Ellenőrző lista

- [ ] A vevőrendelés megnyitva
- [ ] A késztermék készleten volt
- [ ] A kiszállított mennyiség 10 db
- [ ] A kiszállítás mentve vagy könyvelve
- [ ] A rendelés teljesített vagy kiszállított állapotú

## Most már tudod

- Hogyan zárul a vevői igény.
- Miért kell ellenőrizni a késztermékkészletet.
- Hogyan kapcsolódik a kiszállítás a rendeléshez.

## Próbáld ki!

Nyisd meg a vevőrendelést, és keresd meg rajta a teljesített vagy kiszállított állapotot.

## 22. Összegzés

━━━━━━━━━━━━━━━━━━

22 / 22

Összegzés

━━━━━━━━━━━━━━━━━━

Learning Center

- Difficulty: Beginner
- Estimated time: 4 perc
- Learning Path: Installation
- Course: Telepítés utáni első lépések

### Cél

Ellenőrzöd, hogy az első gyártási példa végig sikeresen lefutott.

### Miért fontos?

Az összegzés segít észrevenni, ha valamelyik lépés kimaradt. Egy gyártási folyamat akkor teljes, ha a vevői igénytől a kiszállításig minden adat összekapcsolódik.

### Mit kell tenned?

1. Nyisd meg a **Vevőrendelések** oldalt.
2. Keresd meg a **Minta Gépipari Kft.** rendelését.
3. Ellenőrizd a kapcsolódó gyártási tervet és gyártási rendelést.
4. Nyisd meg a **Gyártási feladatok** oldalt, és ellenőrizd a befejezett feladatokat.
5. Nyisd meg a **Készletmozgások** oldalt, és ellenőrizd az anyagfelhasználást és gyártási bevétet.
6. Nézd meg a minőségellenőrzési eredményt.

### Minta adatok

Teljes példa:

- Vevő: **Minta Gépipari Kft.**
- Termék: **Acél konzol / PRD-1001**
- Mennyiség: **10 db**
- Anyagok: **MAT-0001**, **MAT-0100**, **MAT-0200**
- Dolgozó: **Kiss János**
- Minőségellenőrzés: **Elfogadott**

### Mit kell látnod?

A rendelés teljesített, a gyártási feladatok befejezettek, a minőségellenőrzés elfogadott, és a készletmozgásokból követhető, miből mi készült.

**Képernyőkép javaslat:** vevőrendelés, gyártási feladatok és készletmozgások összefoglaló nézetei.

## Tipp

Az összegzésnél ne csak azt nézd, hogy minden kész lett-e. Azt is ellenőrizd, hogy az út végig követhető maradt-e.

### Gyakori hibák

- **A rendelés nyitott maradt.** Valószínűleg a kiszállítás vagy lezárás hiányzik. Menj vissza a kiszállítás lépéshez.
- **Hiányzik anyagfelhasználás.** A készlet és a nyomon követés nem teljes. Rögzítsd a felhasznált anyagokat.
- **Nincs minőségellenőrzési eredmény.** A késztermék elfogadása nem bizonyított. Rögzíts elfogadott ellenőrzést.

### Következő lépés

Olvasd el az alábbi záró részt, majd készíts saját próbagyártást egy másik termékkel.

## ✓ Siker!

Sikeresen áttekintetted az első teljes gyártási folyamatot.

## Mi történt?

A hétfő reggeli első megrendelésből teljesített gyártási folyamat lett: volt vevői igény, anyag, műveletsor, gyártás, ellenőrzés, készletre vétel és kiszállítás.

## Ellenőrző lista

- [ ] A vevőrendelés teljesített
- [ ] A gyártási feladatok befejezettek
- [ ] Az anyagfelhasználás látszik
- [ ] A minőségellenőrzés elfogadott
- [ ] A készletmozgások követhetők

## Most már tudod

- Hogyan áll össze egy teljes gyártási történet.
- Melyik adat melyik lépéshez tartozik.
- Hogyan ellenőrizd, hogy nem maradt ki fontos rész.

## Próbáld ki!

Készíts saját ellenőrző jegyzetet arról, melyik képernyőn mit néznél meg egy éles rendelés lezárása előtt.

# Mit tanultál meg?

Megtanultad, hogyan indul el egy frissen telepített KM_Production rendszerben az első egyszerű gyártási folyamat.

Végigmentél ezeken:

1. Bejelentkezés és menük áttekintése.
2. Alapadatok létrehozása: telephely, raktár, tárolóhely, beszállító, vevő, cikkek és dolgozó.
3. Gyártási alapok létrehozása: műveletsor és BOM.
4. Készlet feltöltése beérkezéssel.
5. Vevőrendelés felvétele.
6. Gyártási terv és gyártási rendelés létrehozása.
7. Gyártási feladatok indítása és befejezése.
8. Anyagfelhasználás és minőségellenőrzés rögzítése.
9. Késztermék raktárra vétele és kiszállítása.

A legfontosabb gondolat: a rendszerben ne csak az eredményt rögzítsd, hanem az utat is. Legyen látható, honnan jött az anyag, ki dolgozott rajta, milyen műveletek történtek, milyen minőségi döntés született, és hogyan jutott el a késztermék a vevőhöz.

## Gratulálunk!

Elkészítetted az első teljes gyártási folyamatodat a KM_Production rendszerben.

A történet elején még csak egy hétfő reggeli megrendelésed volt. A végére létrejött a vevő, a beszállító, a termék, az alapanyagok, a BOM, a műveletsor, a készlet, a gyártási rendelés, a gyártási feladatok, a minőségellenőrzés, a késztermék készlet és a kiszállítás.

Ajánlott következő kurzus: **Készletmozgások és anyagfelhasználás kezdőknek**.

Ajánlott következő dokumentáció: **Gyártási feladatok az üzemi felületen**.

Ajánlott Learning Center útvonal: **Gyártási alapfolyamatok**.

# Mi következik?

Ajánlott további dokumentumok:

- **Cikkek és darabjegyzékek kezdőknek**: hogyan építs fel több termékből álló gyártási receptet.
- **Készletmozgások egyszerűen**: hogyan olvasd a beérkezést, felhasználást, bevétet, áthelyezést és korrekciót.
- **Gyártási feladatok az üzemi felületen**: hogyan dolgozik a műhely napi szinten.
- **Minőségellenőrzés alapjai**: elfogadás, elutasítás és utómunka kezelése.
- **Beszerzés az anyaghiánytól a beérkezésig**: hogyan lesz hiányból beszállítói rendelés.

# Képernyőkép lista

Érdemes később képernyőképet készíteni ezekhez a fejezetekhez:

1. **Bejelentkezés**: sikeres vezérlőpult.
2. **Rövid áttekintés**: bal oldali menü.
3. **Vállalat létrehozása**: vállalati adatlap vagy beállítás.
4. **Telephely**: Budapest gyártási egység.
5. **Raktár**: Alapanyag raktár a Helyek listában.
6. **Tárolóhely**: A-01 tárolóhely.
7. **Beszállító**: Acél Plusz Kft. rekord.
8. **Vevő**: Minta Gépipari Kft. rekord.
9. **Termék**: PRD-1001 Acél konzol cikk.
10. **Alapanyagok**: MAT-0001, MAT-0100, MAT-0200 cikkek.
11. **Dolgozó**: Kiss János dolgozói adatlap.
12. **Műveletsor**: az öt művelet sorrendje.
13. **BOM**: Acél konzol darabjegyzék három tétellel.
14. **Készlet feltöltése**: beérkezés és készletegyenleg.
15. **Megrendelés felvétele**: vevőrendelés 10 db Acél konzol tétellel.
16. **Gyártási rendelés**: jóváhagyott gyártási terv és generált rendelés.
17. **Gyártás elindítása**: elindított első gyártási feladat.
18. **Műveletek végrehajtása**: befejezett művelet és anyagfelhasználás.
19. **Minőségellenőrzés**: elfogadott ellenőrzési eredmény.
20. **Késztermék raktárra vétele**: PRD-1001 készletegyenleg.
21. **Kiszállítás**: teljesített vevőrendelés.
22. **Összegzés**: rendelés, feladatok és készletmozgások áttekintése.

## Fejlesztői megjegyzés

Ez a dokumentum a jövőben felbontható:

- Knowledge Unitokra
- Guided Course leckékre
- AI válaszokra
- Tooltippekre
- Interaktív oktatásra

Ez a megjegyzés a Learning Center további fejlesztését segíti. A végfelhasználói felületen nem kell megjelennie.
