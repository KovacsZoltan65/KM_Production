# Projektkonvenciók

## Cél

Ez a dokumentum a KM_Production projekt egészére vonatkozó nyelvi és dokumentációs konvenciókat rögzíti. Nem csak a Learning Centerre érvényes, hanem minden jövőbeli fejlesztésre, specifikációra, ADR-re, felhasználói dokumentációra és fejlesztői dokumentációra.

## Tervezett tartalom

- Kódnyelvi szabályok.
- Dokumentációs nyelvi szabályok.
- Kommentek, PHPDoc és Vue kommentek kezelése.
- Indoklás és következmények.
- Kapcsolódás az i18n és dokumentációs gyakorlathoz.

## Döntés

A KM_Production projektben a programkód nyelve angol, az ember által olvasott dokumentáció elsődleges nyelve magyar.

## Nyelvi és lokalizációs konvenciók

Ez a szekció pontosítja a projekt nyelvi határait: mi marad angolul technikai okból, mi legyen magyarul emberi olvasásra, és mely szövegeknek kell lokalizációs rendszeren keresztül megjelenniük.

### Kód

- Minden programkód angol nyelvű.
- A class, method, variable, enum, migration, seeder, factory, route, API és config kulcs angol.
- Az adatbázis táblák és mezők angol nyelvűek.
- A tesztmetódusok és tesztfájlok nevei angol nyelvűek.

### Dokumentáció

- Minden projekt dokumentáció magyar nyelvű.
- A README, specifikáció, ADR, felhasználói dokumentáció és fejlesztői dokumentáció magyar.
- A PHPDoc és Vue kommentek magyar nyelvűek, ha üzleti vagy magyarázó jellegűek.
- Technikai komment csak akkor legyen, ha valóban segíti a karbantartást.

### Felhasználói felület

- Minden felhasználói szöveg lokalizált.
- Az alapértelmezett nyelv magyar.
- Később támogatható angol és más nyelvek.
- Ne legyen hardcode-olt felhasználói szöveg Vue komponensekben vagy backend válaszokban.

### Hibaüzenetek

- A fejlesztői log üzenetek angol nyelvűek.
- A felhasználói hibaüzenetek lokalizáltak.
- A validációs üzenetek lokalizáltak.
- Az API válaszok felhasználói üzenetei lokalizációs kulcsból érkezzenek.

### Commitok és issue-k

- A commit üzenet lehet angol, hogy illeszkedjen a Git ökoszisztémához.
- Az issue, feladatleírás és fejlesztési jegyzet magyar nyelvű.
- Ha a commit üzleti döntést vagy fontos változást érint, legyen hozzá magyar magyarázat dokumentációban vagy ADR-ben.

### Tesztek

- A tesztek neve angol.
- A tesztek üzleti magyarázata magyar lehet.
- Az assertion üzenetek lehetőség szerint angolok, de felhasználói szöveget ne hardcode-oljanak.

### Indoklás

Ez a megközelítés azért előnyös, mert a kód illeszkedik a Laravel, Vue, GitHub és CI/CD angol nyelvű ökoszisztémájához, miközben a dokumentáció magyarul könnyebben érthető az üzleti és projektoldali szereplőknek.

A felhasználói felület lokalizálható marad, csökken a kevert nyelvű kódbázis kockázata, és később könnyebb lesz többnyelvűsíteni a rendszert.

## Kódnyelvi szabályok

Minden programkód angol nyelvű.

Angolul írandó:

- class nevek
- method nevek
- változónevek
- adatbázis táblák és oszlopok
- migration fájlok és migration osztályok
- seeder nevek
- factory nevek
- enum nevek és enum value-k
- route nevek
- API endpoint fogalmak
- service, repository, policy, rule és request osztályok
- tesztnevek és teszt helper kód
- konfigurációs kulcsok

Indok: a Laravel, PHP, Vue, adatbázis és tooling ökoszisztéma angol nyelvű. Az angol kód csökkenti a kevert nyelvű API-k, osztálynevek és adatbázissémák hosszú távú karbantartási kockázatát.

## Dokumentációs nyelvi szabályok

Minden ember által olvasott dokumentáció magyar nyelvű.

Magyarul írandó:

- README dokumentáció
- specifikáció
- felhasználói dokumentáció
- fejlesztői dokumentáció
- ADR dokumentáció
- issue leírás
- commit magyarázat
- release jegyzet
- playbook
- checklist
- döntési napló
- tanulási és onboarding dokumentum

## Kommentek és PHPDoc

A kommentek és PHPDoc blokkok ember által olvasott magyarázatok, ezért alapértelmezésben magyar nyelvűek. Kivételt képezhet:

- külső library által megkövetelt angol formátum
- szabványos PHPDoc tag vagy típusleírás
- olyan rövid technikai megjegyzés, amely kizárólag kódolvasási kontextusban értelmezhető
- upstream projektből vagy vendor dokumentációból átvett pontos terminológia

A komment ne ismételje meg nyilvánvalóan a kódot. Csak akkor legyen komment, ha üzleti szabályt, domain döntést, nem triviális algoritmust vagy architekturális okot magyaráz.

## Vue kommentek

Vue komponensekben a kommentek magyarul írandók, ha emberi magyarázatot adnak. A template, script és style részekben használt technikai azonosítók továbbra is angolul maradnak.

## Felhasználói szövegek

A felhasználói felületen megjelenő szövegek nem hardcoded formában, hanem a projekt i18n rendszerén keresztül szerepeljenek. A fordítási kulcsok technikai azonosítók, ezért angol struktúrát követhetnek, de a magyar tartalom a `lang/hu.json` fájlban él.

## Indoklás

A kettős nyelvi szabály célja, hogy a projekt egyszerre legyen technikailag kompatibilis a nemzetközi fejlesztői ökoszisztémával és érthető a magyar üzleti, gyártási és projektstakeholderek számára.

Az angol kód előnyei:

- illeszkedik a framework és package konvenciókhoz
- egyszerűbb külső fejlesztőt bevonni
- csökkenti az API-k és adatbázissémák kevert nyelvűségét
- támogatja az automatizált toolingot és generátorokat

A magyar dokumentáció előnyei:

- pontosabb üzleti egyeztetést tesz lehetővé
- támogatja a hazai gyártási és operatív felhasználókat
- csökkenti a félreértéseket domain döntéseknél
- egységesíti a specifikációk, ADR-ek és felhasználói anyagok nyelvét

## Következmények

- Új dokumentációt magyarul kell írni.
- Új kódot angol azonosítókkal kell készíteni.
- Meglévő dokumentáció fokozatosan magyarítható, ha érdemi módosítás történik benne.
- Meglévő angol kódot nem kell magyarítani.
- Meglévő magyar kódazonosítót csak akkor kell átnevezni, ha biztonságosan és indokoltan elvégezhető.
- Az i18n kulcsok angol technikai kulcsok maradhatnak, a megjelenített tartalom lokalizált.

## Kapcsolódó témák

- [Projekt architektúra](../architecture.md)
- [i18n dokumentáció](../i18n.md)
- [Learning Center specifikáció](../specifications/learning-center/README.md)
