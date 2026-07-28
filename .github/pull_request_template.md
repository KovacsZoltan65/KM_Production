<!--
PR-cím: angol <type>(<scope>): <subject> vagy <type>: <subject>.
Ideális maximum 72, abszolút maximum 100 karakter; ne végződjön ponttal.
-->

## Összefoglalás

<!-- Mit változtat ez a PR, és miért szükséges? Ne fájllistát írj. -->

## Kapcsolódó munka

- Backlog ID: <!-- Ha nincs, írd: Nincs. -->
- Issue: <!-- Például: Closes #123; ha nincs, írd: Nincs. -->
- Kapcsolódó dokumentum: <!-- Relatív projektútvonal vagy: Nincs. -->

## Változás típusa

<!-- Jelöld a releváns típust vagy típusokat. -->

- [ ] Új funkció
- [ ] Hibajavítás
- [ ] Refaktorálás
- [ ] Teljesítményjavítás
- [ ] Teszt
- [ ] Dokumentáció
- [ ] CI/CD
- [ ] Build vagy függőség
- [ ] Projektkarbantartás
- [ ] Breaking change

## Érintett területek

<!-- Például: inventory, production, frontend, database, i18n. -->

## Megvalósítás

<!-- Fontos technikai döntések és kompromisszumok. Töröld, ha nem releváns. -->

## Tesztelés és ellenőrzés

Futtatott parancsok:

```text
<!-- Csak a ténylegesen futtatott parancsokat sorold fel. -->
```

Eredmény:

<!-- Rövid, mérhető eredmény; ne csak azt írd, hogy „minden működik”. -->

Nem futtatott releváns ellenőrzések és indoklás:

<!-- Töröld, ha nincs ilyen. -->

## Adatbázis és migráció

<!-- Jelöld a releváns állításokat, majd töröld az irreleváns segédszöveget. -->

- [ ] Nem érint adatbázis-sémát
- [ ] Új vagy módosított migrációt tartalmaz
- [ ] A migráció előre irányú futása ellenőrzött
- [ ] A rollback hatása ellenőrzött
- [ ] Adatátalakítás szükséges
- [ ] Kézi üzemeltetési lépés szükséges

Részletek:

<!-- Séma, meglévő adatok, index/constraint, deployment és rollback. -->

## Biztonság és jogosultság

- [ ] Nem érint biztonsági vagy jogosultsági viselkedést
- [ ] Policy, permission, middleware vagy authorization változott
- [ ] Érzékeny adat kezelése felülvizsgált
- [ ] Fájl- vagy dokumentumfeldolgozás biztonsági hatása felülvizsgált

Részletek:

<!-- Töröld, ha nem releváns. -->

## Frontend és lokalizáció

- [ ] Nem érint felhasználói felületet
- [ ] A felhasználói szövegek lokalizációs kulcsot használnak
- [ ] A magyar és angol fordítások frissültek
- [ ] A releváns vizuális és reszponzív ellenőrzés megtörtént
- [ ] A billentyűzetes használat ellenőrzött
- [ ] Az Inertia prop- és Vue propszerződés ellenőrzött

## Dokumentáció

- [ ] Nem szükséges dokumentációfrissítés
- [ ] A kapcsolódó dokumentáció frissült
- [ ] A backlog és az elfogadási feltételek tényszerűen frissültek
- [ ] A breaking change dokumentált
- [ ] Az üzemeltetési lépés dokumentált

## Kockázatok és visszaállítás

Kockázatok:

<!-- Mi romolhat el, és mely folyamatot érinti? -->

Visszaállítás:

<!-- Hogyan vonható vissza vagy javítható biztonságosan? -->

## Reviewer fókusz

<!-- Mely döntéseket, szerződéseket vagy kockázatokat ellenőrizze kiemelten? -->

## Beküldő ellenőrzőlistája

- [ ] A PR címe követi a commitüzenet-konvenciót
- [ ] A PR egy logikailag összetartozó változást tartalmaz
- [ ] A teljes branch diffjét átnéztem
- [ ] Nem került be titok, jelszó vagy érzékeny adat
- [ ] Csak ténylegesen futtatott ellenőrzést jelöltem sikeresnek
- [ ] A nem futtatott releváns ellenőrzéseket megindokoltam
- [ ] A dokumentáció tényszerű
- [ ] A backlog állapota tényszerű
- [ ] Nincs szükségtelen generált vagy lokális fájl
