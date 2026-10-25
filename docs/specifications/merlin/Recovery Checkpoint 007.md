# KM_Production — Merlin TC-02

## Suspension & Resumption Checkpoint

**Feladatazonosító:** `MERLIN_TC02_SUSPENSION_AND_RESUMPTION_CHECKPOINT`

**Feladat típusa:** dokumentáció, állapotrögzítés, későbbi folytatás előkészítése.

**Felhasználói döntés:** a Merlin TC-02 megoldásának kidolgozását határozatlan időre felfüggesztjük.

**Fontos:** a felfüggesztés nem jelenti a Merlin projekt törlését, a TC-02 követelmények módosítását vagy valamelyik architekturális alternatíva elfogadását.

---

## 1. Kötelező Git-preflight

Repository: `KM_Production`

Elvárt branch:

`feature/merlin-candidate`

Az utolsó ismert, korábban ellenőrzött HEAD:

`15ee78b75009419a39fb7a04ed41da080c6161c5`

Azóta a felhasználó visszajelzése szerint a Recovery Checkpoint 006 mentése megtörtént.

**Ezért a jelenlegi HEAD-et nem szabad előre feltételezni.**

Ellenőrizd:

```powershell
git status --short --branch
git branch --show-current
git rev-parse HEAD
git log -5 --oneline
git ls-remote origin refs/heads/feature/merlin-candidate
git diff --check
git diff --cached --check
```

Kötelező feltételek:

1. Az aktív branch `feature/merlin-candidate`.
2. A munkakönyvtár tiszta.
3. A staging üres.
4. Nincs váratlan untracked fájl.
5. A lokális és távoli HEAD egyezik.
6. A Recovery Checkpoint 006 commitja megtalálható a történetben.
7. A Checkpoint 006 kizárólag az alábbi dokumentumot mentette:

`docs/specifications/merlin/MERLIN_TC02_C4_ANCHOR_AND_LOCK_ORDER_CONTRACT_DRAFT.md`

Ha bármely feltétel sérül:

**STOP.**

Ne végezz automatikus helyreállítást.

Tilos a reset, clean, checkout, pull, merge, rebase és branchváltás.

---

## 2. A meglévő dokumentáció áttekintése

Olvasd el a következő dokumentumokat:

1. `MERLIN_TC02_AUTHORIZATION_BUSINESS_GUARD_CONTRACT_DRAFT.md`
2. `MERLIN_TC02_GUARD_PROTOCOL_SCOPE_AND_WRITER_COVERAGE_REVIEW.md`
3. `MERLIN_TC02_C4_ANCHOR_AND_LOCK_ORDER_CONTRACT_DRAFT.md`

A dokumentumok helye:

`docs/specifications/merlin/`

Vizsgáld meg a hozzájuk kapcsolódó ACCEPTED ADR-eket és a Merlin L1 műszaki szerződését is.

A cél kizárólag az aktuális helyzet rekonstruálása.

Ne végezz újabb teljes writer-, lock- vagy adatbázis-auditot.

Ne módosítsd a korábbi dokumentumokat.

---

## 3. A felfüggesztés okának rögzítése

A következő döntést dokumentáld:

A Merlin TC-02 kidolgozása során a szükséges tranzakciós garanciák biztosítása jelentős rendszerszintű módosításokat vetített előre.

A korábbi audit alapján:

* 120 writer-metódust vizsgáltunk.
* 58 forrásfájl szerepelt a leltárban.
* 29 fájl és 57 metódus lehetséges módosítása merült fel.
* 25 anchorjelöltet vizsgáltunk.
* Kilenc feltételes lock-inversion kockázatot azonosítottunk.
* A C4 és L2 megoldás teljes alkalmassága nem bizonyított.

Ezek a számok a korábbi auditok eredményei, nem végleges implementációs költségek.

A felhasználó ezért a további kidolgozás felfüggesztéséről döntött.

**A felfüggesztés elsődleges célja az aránytalan architekturális komplexitás és fejlesztési kockázat elkerülése addig, amíg Merlin végrehajtási szerepét újra nem értékeljük.**

Ez nem műszaki kudarc, hanem tudatos projektprioritási döntés.

---

## 4. Megőrzendő státuszok

A dokumentumban egyértelműen szerepeljen:

| Elem                           | Státusz                                      |
| ------------------------------ | -------------------------------------------- |
| Merlin projekt                 | Aktív projekt, a TC-02 munkája felfüggesztve |
| TC-02                          | BLOCKED / SUSPENDED                          |
| Authorization/business guard   | DRAFT                                        |
| C4                             | PROPOSED                                     |
| L2                             | PROPOSED                                     |
| C4–L2 implementáció            | NOT AUTHORIZED                               |
| Alternatív architektúra        | NOT DECIDED                                  |
| Következő implementációs lépés | NOT AUTHORIZED                               |

Ne változtasd meg a korábbi dokumentumok státuszát.

A SUSPENDED projektmenedzsment-állapot, nem helyettesíti a TC-02 műszaki BLOCKED státuszát.

---

## 5. Megőrzendő architekturális alternatívák

A folytatáshoz három lehetőséget kell nyitva hagyni.

### A — C4–L2 folytatása

A jelenlegi, meglévő adatbázissorokra épülő guard-first tranzakciós modell további bizonyítása.

Előny:

* Az eredeti szigorú tranzakciós követelmények megőrzésére törekszik.

Kockázat:

* Jelentős writerlefedettségi és lock-order módosításokat igényelhet.

### B — Központi guard-infrastruktúra

Külön guard- vagy verziókezelési infrastruktúra vizsgálata.

Előny:

* Egységesebb tranzakciós szerződés kialakítását teheti lehetővé.

Kockázat:

* Új infrastruktúrát, migrációt és writer-enrollmentet igényelhet.

### C — Egyszerűbb Merlin-végrehajtási modell

Merlin tanácsadó szerepének és a meglévő üzleti szolgáltatások végrehajtási felelősségének elválasztása.

Lehetséges működés:

1. Merlin problémát azonosít.
2. Megoldási javaslatot készít.
3. A felhasználó jóváhagyja a javaslatot.
4. A meglévő üzleti szolgáltatás végrehajtja a műveletet.
5. A szolgáltatás az aktuális jogosultságok és üzleti feltételek alapján dönt.

Előny:

* Csökkentheti a Merlin-specifikus tranzakciós infrastruktúra szükségességét.

Korlát:

* Önmagában nem teljesíti bizonyítottan a TC-02 eredeti COMMIT-ig tartó garanciáit.
* Az elfogadásához külön követelmény- és architekturális döntés szükséges.

**Egyik alternatíva sem ACCEPTED.**

---

## 6. A folytatás első kérdése

A munka újraindításakor ne azonnal a C4 anchorok bizonyításával kezdj.

Először a következő üzleti kérdést kell tisztázni:

**Merlin hosszú távon önálló üzleti műveleteket is végrehajthasson, vagy alapvetően tanácsadóként működjön, amelynek javaslatait a felhasználó hagyja jóvá?**

A kérdés jelenleg:

`OPEN — USER DECISION REQUIRED`

A válasz alapján lehet eldönteni, hogy:

* Megtartjuk-e az eredeti TC-02 végrehajtási szerződést.
* Módosítani kell-e a végrehajtási modellt.
* Folytatjuk-e a C4–L2 bizonyítását.
* Más architekturális alternatívát választunk-e.

Ne dönts a felhasználó helyett.

---

## 7. Folytatási protokoll

A későbbi újraindításkor az alábbi sorrendet kövesd:

**R1 — Repository-állapot ellenőrzése**

* Aktuális branch és HEAD.
* Tiszta worktree.
* Checkpointok és dokumentumok megléte.
* Időközben történt változások azonosítása.

**R2 — Követelmények újraértékelése**

* Merlin üzleti szerepének tisztázása.
* Önálló végrehajtás szükségessége.
* Felhasználói jóváhagyás szerepe.
* A TC-02 eredeti invariánsainak felülvizsgálata.

**R3 — Architekturális döntés**

* A, B és C összehasonlítása.
* Komplexitás.
* Biztonság.
* Fejlesztési költség.
* Üzemeltetési kockázat.
* Meglévő üzleti szolgáltatások újrafelhasználhatósága.

**R4 — Külön felhasználói jóváhagyás**

Csak kifejezett jóváhagyás után készülhet:

* Új ADR.
* Módosított műszaki szerződés.
* Implementációs terv.
* Implementáció.

**R5 — Végrehajtás**

Csak az elfogadott döntésnek megfelelő, külön engedélyezett feladat indítható.

---

## 8. Kimeneti dokumentum

Hozd létre:

`docs/specifications/merlin/MERLIN_TC02_SUSPENSION_AND_RESUMPTION.md`

A dokumentum magyar nyelvű legyen.

Tartalmazza:

1. A felfüggesztés dátumát.
2. A Git-baseline-t.
3. A Recovery Checkpoint 006 commitazonosítóját.
4. A korábbi dokumentumok pontos elérési útját.
5. A jelenlegi műszaki státuszokat.
6. A felfüggesztés indokait.
7. A három architekturális alternatívát.
8. A nyitott felhasználói döntést.
9. A folytatás kötelező sorrendjét.
10. A tiltott automatikus műveleteket.
11. A következő lépés pontos meghatározását.

A dokumentum célja, hogy a projekt több hónap kihagyás után is folytatható legyen.

A korábbi dokumentumokat ne módosítsd.

---

## 9. Ellenőrzések

Ellenőrizd:

* A dokumentum teljességét.
* A Git-azonosítók helyességét.
* A hivatkozások létezését.
* A státuszok következetességét.
* A Markdown-formázást.
* A Git whitespace állapotát.

A végén pontosan egy új untracked dokumentum maradhat.

Tilos:

* Alkalmazáskód módosítása.
* Tesztmódosítás.
* Migráció.
* Adatbázis-művelet.
* Új architekturális audit.
* Automatikus implementáció.
* Commit.
* Push.

---

## 10. Zárójelentés

Siker esetén:

`MERLIN_TC02_SUSPENSION_AND_RESUMPTION_READY`

Add meg:

1. Branch és HEAD.
2. Checkpoint 006 commitazonosító.
3. Létrehozott dokumentum.
4. Dokumentum sorszáma.
5. Ellenőrzések eredménye.
6. Végső Git-állapot.
7. Megőrzött műszaki státuszok.
8. Nyitott felhasználói döntés.

Hiba esetén:

`MERLIN_TC02_SUSPENSION_AND_RESUMPTION_STOPPED`

A hiba pontos okával.

**A dokumentum elkészítése után állj meg. Ne commitolj, ne pusholj, és ne kezdj újabb feladatba.**
