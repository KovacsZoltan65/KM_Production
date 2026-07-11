# KM_Production mintaadatok

```
Ez a dokumentum a KM_Production oktatási és tesztelési mintaadatait írja le.
```

A cél az, hogy a mintaadatok ténylegesen felvihetők legyenek az alkalmazás jelenlegi űrlapjain.

```
# Fontos szabály
```

A mintaadatok elsődleges forrása:

```
1. migráció
2. FormRequest
3. Vue űrlap
4. model
5. enum
```

Ha a dokumentáció és az űrlap eltér, az hibának számít, és javítani kell.

```
Zero State szabály: a dokumentáció abból indul ki, hogy az adatbázis teljesen üres, kivéve a belépéshez szükséges admin felhasználót.
```

Az oktatási példa továbbra is ugyanarra a történetre épül:

| Mintaadat          | Érték                              |
| ------------------ | ---------------------------------- |
| Cég                | `KM Gépgyártó Kft.`                |
| Gyártási egység    | `Budapesti gyártóüzem`             |
| Vevő               | `Minta Gépipari Kft.`              |
| Beszállító         | `Acél Plusz Kft.`                  |
| Termék             | `PRD-1001 Acél konzol`             |
| Alapanyagok        | `MAT-0001`, `MAT-0100`, `MAT-0200` |
| Gyártási mennyiség | `10 db`                            |

# Zero State indulás

```
A kiindulási állapot teljesen üres adatbázis. Egyetlen kivétel van: az `AdminUserSeeder` által létrehozott admin felhasználó, amellyel be tudsz lépni az alkalmazásba.
```

Nincs még gyártási egység, hely, dolgozó, vevő, beszállító, cikk, BOM, műveletsor, rendelés, készlet vagy dokumentum. Ezért az első használatnál az adatokat egymásra épülő sorrendben kell létrehozni.

## Inventory modul

Az Inventory modul kezdőoldala az **Admin → Inventory** menüpontból, a
`/admin/inventory` URL-en érhető el. Innen nyithatók meg a készletegyenlegek,
készletmozgások, készletfoglalások, anyagszükségletek és hiányok oldalai.

```
## Teljes indulási sorrend
```

1. Gyártási egység

```
    Először meg kell mondani, hol történik a gyártás. Ez lesz az alapja a helyeknek és a műveletsor lépéseinek.
```

2. Helyek

```
    A helyek mutatják meg, hol van alapanyag, hol történik a munka, hol van a készáru és hol történik az ellenőrzés. Készlet és beérkezés hely nélkül nem értelmezhető.
```

3. Szakmai szerepkörök

```
    A szakmai szerepkör írja le, milyen munkát végezhet egy dolgozó. Erre a műveletsor lépéseinél és a dolgozók felvételénél lesz szükség.
```

4. Dolgozók

```
    A dolgozókhoz rendelhetők gyártási feladatok és minőségellenőrzések. Legalább egy gyártó dolgozó és egy ellenőr szükséges az oktatási folyamat végigviteléhez.
```

5. Vevők

```
    Vevő nélkül nincs vevőrendelés. A példában a Minta Gépipari Kft. lesz az első rendelő partner.
```

6. Beszállítók

```
    Beszállító kell a beszerzési folyamathoz. A példában az Acél Plusz Kft. adja az alapanyagokat.
```

7. Cikkek

```
    A cikkek között szerepel a késztermék és minden alapanyag. Ezekre épül a BOM, a rendelés, a beérkezés és a készlet.
```

8. BOM

```
    A BOM mondja meg, miből készül az Acél konzol. Enélkül a rendszer nem tudja kiszámolni az anyagszükségletet.
```

9. Műveletsor

```
    A műveletsor mondja meg, milyen munkalépésekkel készül el a termék. Enélkül nem lesznek gyártási feladatok.
```

10. Vevőrendelés

```
    A vevőrendelés indítja el az üzleti igényt. Ebben rögzül, hogy 10 db Acél konzolt kell gyártani.
```

11. Gyártási terv

```
    A gyártási terv a rendelést gyártási szempontból előkészíti. Itt kapcsolódik össze a rendelési tétel, a BOM és a műveletsor.
```

12. Gyártási rendelés

```
    A gyártási rendelés a tervből generálódik. Ez már konkrét gyártási munka, amelyből feladatok készülhetnek.
```

13. Gyártási feladat

```
    A gyártási feladatok a műveletsor lépéseit osztják ki dolgozókra. Ezeken keresztül történik a tényleges munkavégzés rögzítése.
```

14. Beérkezés

```
    Beérkezéssel kerül alapanyag a készletbe. A példában így kerül be az acéllemez, a csavar és a porfesték.
```

15. Anyagfelhasználás

```
    Anyagfelhasználáskor a gyártási feladathoz rögzíted, mit használtatok fel. Ez köti össze a készletet a tényleges gyártással.
```

16. Minőségellenőrzés

```
    A minőségellenőrzés dönt arról, hogy a munka elfogadható-e. Az oktatási példában az első termék elfogadott eredményt kap.
```

17. Dokumentumok

```
    Dokumentumot akkor töltesz fel, amikor rajzot, minőségi riportot vagy más fájlt szeretnél egy rekordhoz kapcsolni. Ez később az AI és a Learning Center számára is hasznos kontextus.
```

# Függőségi diagramok

```
## Alap helystruktúra
```

```text
Factory Unit
    |
    v
Location
    |
    v
Stock Balance
```

```
## Dolgozó és munkavégzés
```

```text
Professional Role
    |
    v
Employee
    |
    v
Production Task
    |
    v
Quality Check
```

```
## Termék gyártási tudása
```

```text
Item
 |-- BOM
 |    |
 |    v
 |   BOM Item
 |
 |-- Operation Sequence
      |
      v
     Operation Sequence Step
```

```
## Vevői rendeléstől gyártási feladatig
```

```text
Customer
    |
    v
Customer Order
    |
    v
Production Plan
    |
    v
Production Order
    |
    v
Production Task
```

```
## Beszerzéstől készletig
```

```text
Supplier
    |
    v
Purchase Order
    |
    v
Goods Receipt
    |
    v
Stock Movement
    |
    v
Stock Balance
```

```
## Dokumentáció és tudás
```

```text
Document
    |
    v
Knowledge Unit
    |
    v
Lesson
    |
    v
Learning Center
```

```
## Forrásfájlok áttekintése
```

A dokumentum készítésekor ezekből a fájltípusokból indultunk ki:

```
- migrációk: `database/migrations`
- modellek: `app/Models`
- validációk: `app/Http/Requests/Admin`
- admin oldalak és komponensek: `resources/js/Pages/Admin`, `resources/js/Components`
- enumok: `app/Enums`
- magyar mezőnevek: `lang/hu.json`
- útvonalak: `routes/web.php`
```

## Jelölések

```
- A "Kötelező" oszlop a FormRequest alapján készült.
- Az automatikus mezők nem szerepelnek az űrlapos mezőtáblában, ha a felhasználó nem adja meg őket.
- Ha nincs közvetlen felhasználói űrlap, azt külön jelezzük.
- Az `id` mezők helyett a dokumentációban a felhasználó által választható mintaadat nevét adjuk meg.
```

# 1. Gyártási egység / Factory Unit

```
## Állapot
```

🟢 Kötelező induláskor

```
## Előfeltételek
```

Ehhez előbb létre kell hozni:

```
- nincs
```

## Mire szolgál?

```
A gyártási egység azt jelöli, hol történik a munka. Ilyen lehet egy üzem, műhely vagy gyártósor.
```

## Forrás

```
- migráció: `database/migrations/2026_06_21_000001_create_factory_units_table.php`
- model: `app/Models/FactoryUnit.php`
- FormRequest: `app/Http/Requests/Admin/StoreFactoryUnitRequest.php`
- Vue oldal: `resources/js/Pages/Admin/FactoryUnits/Index.vue`
- enum: nincs
```

## Hol használjuk?

```
Admin felület -> Gyártási egységek.
```

## Űrlapon megadható mezők

```
| Űrlap mező             | Adatbázis mező           | Kötelező | Típus                   | Minta érték                             | Megjegyzés                                        |
| ---------------------- | ------------------------ | -------- | ----------------------- | --------------------------------------- | ------------------------------------------------- |
| Kód                    | `code`                   | igen     | szöveg, max. 50, egyedi | `BP-GYARTAS`                            | Rövid, később is érthető azonosító legyen.        |
| Név                    | `name`                   | igen     | szöveg, max. 255        | `Budapesti gyártóüzem`                  | Ezt látja a felhasználó a listákban.              |
| Leírás                 | `description`            | nem      | hosszabb szöveg         | `Acél konzol gyártására használt üzem.` | Röviden írd le, mire használjátok.                |
| Napi kapacitás percben | `daily_capacity_minutes` | nem      | egész szám, minimum 0   | `480`                                   | Egy 8 órás műszak 480 perc.                       |
| Műszakszám             | `shift_count`            | igen     | egész szám, minimum 1   | `1`                                     | Ha nem tudod, kezdd 1 műszakkal.                  |
| Aktív                  | `is_active`              | nem      | igaz/hamis              | `igen`                                  | Inaktív egységet később nem érdemes kiválasztani. |
```

## Minta rekord

```
| Mező                   | Érték                                   |
| ---------------------- | --------------------------------------- |
| Kód                    | `BP-GYARTAS`                            |
| Név                    | `Budapesti gyártóüzem`                  |
| Leírás                 | `Acél konzol gyártására használt üzem.` |
| Napi kapacitás percben | `480`                                   |
| Műszakszám             | `1`                                     |
| Aktív                  | `igen`                                  |
```

## Kapcsolódó adatok

```
Előtte nincs szükség más törzsadatra.
```

Ezt fogjuk használni a helyeknél és a műveletsor lépéseinél.

```
## Gyakori hibák
```

- Üresen marad a kód vagy a név.
- Olyan kódot adsz meg, ami már létezik.
- A műszakszám 0, pedig legalább 1 kell.

```
## A létrehozás után elérhető
```

Ezután már létrehozható vagy értelmezhető:

```
- location
- operation_sequence_step
- item_instance
```

## Oktatási megjegyzés

```
Itt azt érdemes megtanulni, hogy a gyártási egység nem raktár és nem dolgozó. Ez a gyártás helyének legfelső szintű kerete.
```

## Knowledge Graph kapcsolatok

```
Kapcsolódó entitások:
```

-> Location
-> Operation Sequence Step
-> Item Instance

```
## Learning Center
```

Ez a fejezet a következő tananyagokban szerepel:

```
- Kezdő felhasználói útmutató
- Első gyártási rendelés (Tervezett)
- Kapacitástervezés alapjai (Tervezett)
```

## AI metaadat

````
```yaml
knowledge:
  entity: factory_unit
  zero_state: true
  user_creatable: true
  generated: false
  prerequisites: []
  unlocks:
    - location
    - operation_sequence_step
    - item_instance
  learning_level: beginner
`

# 2. Hely / Location
````

## Állapot

```
🟢 Kötelező induláskor
```

## Előfeltételek

```
Ehhez előbb létre kell hozni:
```

- factory_unit

```
## Mire szolgál?
```

A hely azt mutatja meg, hol található egy cikk vagy hol történik egy munka. Lehet raktár, műhely, minőségellenőrzési terület, készáru terület vagy selejt hely.

```
## Forrás
```

- migráció: `database/migrations/2026_06_21_000002_create_locations_table.php`
- model: `app/Models/Location.php`
- FormRequest: `app/Http/Requests/Admin/StoreLocationRequest.php`
- Vue oldal: `resources/js/Pages/Admin/Locations/Index.vue`
- enum: `app/Enums/LocationType.php`

```
## Hol használjuk?
```

Admin felület -> Helyek.

```
## Űrlapon megadható mezők
```

| Űrlap mező      | Adatbázis mező    | Kötelező | Típus                   | Minta érték                                  | Megjegyzés                                                                |
| --------------- | ----------------- | -------- | ----------------------- | -------------------------------------------- | ------------------------------------------------------------------------- |
| Gyártási egység | `factory_unit_id` | nem      | választólista           | `Budapesti gyártóüzem`                       | Raktárnál lehet üres, műhelynél érdemes kitölteni.                        |
| Kód             | `code`            | igen     | szöveg, max. 50, egyedi | `ALAP-R1`                                    | Rövid helyazonosító.                                                      |
| Név             | `name`            | igen     | szöveg, max. 255        | `Alapanyag raktár`                           | A felhasználók ezt látják.                                                |
| Hely típusa     | `location_type`   | igen     | enum                    | `warehouse`                                  | Választható: raktár, műhely, minőségellenőrzési terület, készáru, selejt. |
| Leírás          | `description`     | nem      | hosszabb szöveg         | `Beérkező acéllemezek és csavarok tárolása.` | Írd le, mit tároltok itt.                                                 |
| Aktív           | `is_active`       | nem      | igaz/hamis              | `igen`                                       | Inaktív helyet ne használj új készlethez.                                 |

```
## Minta rekord
```

| Mező            | Érték                                        |
| --------------- | -------------------------------------------- |
| Gyártási egység | `Budapesti gyártóüzem`                       |
| Kód             | `ALAP-R1`                                    |
| Név             | `Alapanyag raktár`                           |
| Hely típusa     | `warehouse`                                  |
| Leírás          | `Beérkező acéllemezek és csavarok tárolása.` |
| Aktív           | `igen`                                       |

```
## Kapcsolódó adatok
```

Előtte hozd létre a `Budapesti gyártóüzem` gyártási egységet.

```
További oktatási helyek:
```

| Kód        | Név                       | Hely típusa      | Leírás                                       | Aktív |
| ---------- | ------------------------- | ---------------- | -------------------------------------------- | ----- |
| `MUHELY-1` | `Megmunkáló műhely`       | `workshop`       | `Darabolási és összeszerelési munkaterület.` | igen  |
| `ME-1`     | `Minőségellenőrzési pont` | `quality_area`   | `Gyártásközi és végellenőrzési terület.`     | igen  |
| `KESZ-R1`  | `Készáru raktár`          | `finished_goods` | `Elkészült termékek átmeneti tárolása.`      | igen  |

```
## Gyakori hibák
```

- A hely típusa üresen marad.
- Már létező kódot adsz meg.
- Inaktív helyre próbálsz később beérkezést rögzíteni.

```
## A létrehozás után elérhető
```

Ezután már létrehozható vagy értelmezhető:

```
- goods_receipt_item
- stock_balance
- stock_movement
- production_task_material
```

## Oktatási megjegyzés

```
A kezdőknek sokat segít, ha a helyeket fizikailag elképzelik: alapanyag raktár, műhely, ellenőrzési pont és készáru raktár.
```

## Knowledge Graph kapcsolatok

```
Kapcsolódó entitások:
```

-> Factory Unit
-> Goods Receipt Item
-> Stock Balance
-> Stock Movement
-> Production Task Material

```
## Learning Center
```

Ez a fejezet a következő tananyagokban szerepel:

```
- Kezdő felhasználói útmutató
- Készletkezelés alapjai (Tervezett)
- Első gyártási rendelés (Tervezett)
```

## AI metaadat

````
```yaml
knowledge:
  entity: location
  zero_state: true
  user_creatable: true
  generated: false
  prerequisites:
    - factory_unit
  unlocks:
    - goods_receipt_item
    - stock_balance
    - stock_movement
    - production_task_material
  learning_level: beginner
`

# 3. Szakmai szerepkör / Professional Role
````

## Állapot

```
🟢 Kötelező induláskor
```

## Előfeltételek

```
Ehhez előbb létre kell hozni:
```

- nincs

```
## Mire szolgál?
```

A szakmai szerepkör a dolgozó munkakörét írja le. Ez nem ugyanaz, mint a rendszerjogosultsági szerepkör.

```
## Forrás
```

- migráció: `database/migrations/2026_06_21_000003_create_professional_roles_table.php`
- model: `app/Models/ProfessionalRole.php`
- FormRequest: `app/Http/Requests/Admin/StoreProfessionalRoleRequest.php`
- Vue oldal: `resources/js/Pages/Admin/ProfessionalRoles/Index.vue`
- enum: nincs

```
## Hol használjuk?
```

Admin felület -> Szakmai szerepek.

```
## Űrlapon megadható mezők
```

| Űrlap mező | Adatbázis mező | Kötelező | Típus                   | Minta érték                             | Megjegyzés                                        |
| ---------- | -------------- | -------- | ----------------------- | --------------------------------------- | ------------------------------------------------- |
| Kód        | `code`         | igen     | szöveg, max. 50, egyedi | `OPERATOR`                              | Rövid szakmai kód.                                |
| Név        | `name`         | igen     | szöveg, max. 255        | `Gépkezelő`                             | Ezt választod majd a dolgozónál és műveletsornál. |
| Leírás     | `description`  | nem      | hosszabb szöveg         | `Megmunkáló műveleteket végző dolgozó.` | Segít megkülönböztetni a szerepeket.              |
| Aktív      | `is_active`    | nem      | igaz/hamis              | `igen`                                  | Inaktív szerepet ne használj új dolgozóhoz.       |

```
## Minta rekord
```

| Kód                 | Név              | Leírás                                          | Aktív |
| ------------------- | ---------------- | ----------------------------------------------- | ----- |
| `OPERATOR`          | `Gépkezelő`      | `Megmunkáló műveleteket végző dolgozó.`         | igen  |
| `QUALITY_INSPECTOR` | `Minőségellenőr` | `Minőségellenőrzési feladatokat végző dolgozó.` | igen  |

```
## Kapcsolódó adatok
```

Előtte nincs szükség más adatra.

```
Ezeket használjuk Kiss János és Nagy Anna dolgozóknál, illetve a műveletsor lépéseinél.
```

## Gyakori hibák

```
- Összekevered a szakmai szerepet a jogosultsági szerepkörrel.
- Üres a kód vagy a név.
- Többször próbálod létrehozni ugyanazt a kódot.
```

## A létrehozás után elérhető

```
Ezután már létrehozható vagy értelmezhető:
```

- employee
- operation_sequence_step

```
## Oktatási megjegyzés
```

Fontos különválasztani a szakmai szerepet és a belépési jogosultságot. A szakmai szerep azt mondja meg, milyen munkát végez valaki.

```
## Knowledge Graph kapcsolatok
```

Kapcsolódó entitások:

```
-> Employee
-> Operation Sequence Step
-> Production Task
```

## Learning Center

```
Ez a fejezet a következő tananyagokban szerepel:
```

- Kezdő felhasználói útmutató
- Első gyártási rendelés (Tervezett)

```
## AI metaadat
```

```yaml
knowledge:
  entity: professional_role
  zero_state: true
  user_creatable: true
  generated: false
  prerequisites: []
  unlocks:
    - employee
    - operation_sequence_step
  learning_level: beginner
`

# 4. Dolgozó / Employee
```

## Állapot

```
🟢 Kötelező induláskor
```

## Előfeltételek

```
Ehhez előbb létre kell hozni:
```

- professional_role

```
## Mire szolgál?
```

A dolgozó az a személy, aki munkát végez, anyagot használ fel vagy minőségellenőrzést rögzít.

```
## Forrás
```

- migráció: `database/migrations/2026_06_21_000004_create_employees_table.php`
- model: `app/Models/Employee.php`
- FormRequest: `app/Http/Requests/Admin/StoreEmployeeRequest.php`
- Vue oldal: `resources/js/Pages/Admin/Employees/Index.vue`
- enum: nincs

```
## Hol használjuk?
```

Admin felület -> Dolgozók.

```
## Űrlapon megadható mezők
```

| Űrlap mező         | Adatbázis mező         | Kötelező | Típus                    | Minta érték                   | Megjegyzés                                               |
| ------------------ | ---------------------- | -------- | ------------------------ | ----------------------------- | -------------------------------------------------------- |
| Dolgozói azonosító | `employee_number`      | igen     | szöveg, max. 255, egyedi | `EMP-0001`                    | Ne változzon később.                                     |
| Név                | `name`                 | igen     | szöveg, max. 255         | `Kiss János`                  | A feladatoknál ez jelenik meg.                           |
| Email              | `email`                | nem      | email                    | `kiss.janos@kmgepgyarto.test` | Csak valós email formátumot fogad el.                    |
| Telefon            | `phone`                | nem      | szöveg                   | `+36 30 111 2222`             | Szabad formátumú mező.                                   |
| Szakmai szerep     | `professional_role_id` | nem      | választólista            | `Gépkezelő`                   | Előtte létre kell hozni a szakmai szerepet.              |
| Felhasználó        | `user_id`              | nem      | választólista            | üres                          | Csak akkor kell, ha a dolgozó be is lép az alkalmazásba. |
| Aktív              | `is_active`            | nem      | igaz/hamis               | `igen`                        | Inaktív dolgozót ne ossz ki új feladatra.                |
| Belépés dátuma     | `hired_at`             | nem      | dátum                    | `2026-07-06`                  | Formátum: év-hónap-nap.                                  |
| Kilépés dátuma     | `left_at`              | nem      | dátum                    | üres                          | Nem lehet korábbi, mint a belépés dátuma.                |

```
## Minta rekord
```

| Dolgozói azonosító | Név          | Email                         | Telefon           | Szakmai szerep   | Felhasználó | Aktív | Belépés dátuma | Kilépés dátuma |
| ------------------ | ------------ | ----------------------------- | ----------------- | ---------------- | ----------- | ----- | -------------- | -------------- |
| `EMP-0001`         | `Kiss János` | `kiss.janos@kmgepgyarto.test` | `+36 30 111 2222` | `Gépkezelő`      | üres        | igen  | `2026-07-06`   | üres           |
| `EMP-0002`         | `Nagy Anna`  | `nagy.anna@kmgepgyarto.test`  | `+36 30 333 4444` | `Minőségellenőr` | üres        | igen  | `2026-07-06`   | üres           |

```
## Kapcsolódó adatok
```

Előtte érdemes létrehozni a `Gépkezelő` és a `Minőségellenőr` szakmai szerepet.

```
## Gyakori hibák
```

- Rossz email formátumot írsz be.
- A kilépés dátuma korábbi, mint a belépés dátuma.
- Inaktív dolgozót próbálsz feladathoz rendelni.

```
## A létrehozás után elérhető
```

Ezután már létrehozható vagy értelmezhető:

```
- production_task
- quality_check
- production_task_material
```

## Oktatási megjegyzés

```
A dolgozót akkor is érdemes pontosan felvenni, ha nincs hozzá felhasználói fiók. A gyártási nyomon követés dolgozó rekordokra épül.
```

## Knowledge Graph kapcsolatok

```
Kapcsolódó entitások:
```

-> Professional Role
-> Production Task
-> Quality Check
-> Production Task Material

```
## Learning Center
```

Ez a fejezet a következő tananyagokban szerepel:

```
- Kezdő felhasználói útmutató
- Első gyártási rendelés (Tervezett)
```

## AI metaadat

````
```yaml
knowledge:
  entity: employee
  zero_state: true
  user_creatable: true
  generated: false
  prerequisites:
    - professional_role
  unlocks:
    - production_task
    - quality_check
    - production_task_material
  learning_level: beginner
`

# 5. Vevő / Customer
````

## Állapot

```
🟢 Kötelező induláskor
```

## Előfeltételek

```
Ehhez előbb létre kell hozni:
```

- nincs

```
## Mire szolgál?
```

A vevő az a partner, akinek terméket gyártasz és szállítasz.

```
## Forrás
```

- migráció: `database/migrations/2026_06_21_000005_create_customers_table.php`
- model: `app/Models/Customer.php`
- FormRequest: `app/Http/Requests/Admin/StoreCustomerRequest.php`
- Vue oldal: `resources/js/Pages/Admin/Customers/Index.vue`
- enum: nincs

```
## Hol használjuk?
```

Admin felület -> Vevők.

```
## Űrlapon megadható mezők
```

| Űrlap mező     | Adatbázis mező     | Kötelező | Típus                    | Minta érték                     | Megjegyzés                                                   |
| -------------- | ------------------ | -------- | ------------------------ | ------------------------------- | ------------------------------------------------------------ |
| Kód            | `code`             | igen     | szöveg, max. 255, egyedi | `CUST-0001`                     | Rövid vevőazonosító.                                         |
| Név            | `name`             | igen     | szöveg, max. 255         | `Minta Gépipari Kft.`           | Ez jelenik meg a rendelésekben.                              |
| Adószám        | `tax_number`       | nem      | szöveg, max. 255         | `12345678-2-42`                 | Nem ellenőrzi az adószám logikáját, csak szövegként tárolja. |
| Email          | `email`            | nem      | email                    | `rendeles@mintagepipar.test`    | Csak email formátumot fogad el.                              |
| Telefon        | `phone`            | nem      | szöveg                   | `+36 1 555 0101`                | Szabad formátumú mező.                                       |
| Számlázási cím | `billing_address`  | nem      | hosszabb szöveg          | `1111 Budapest, Minta utca 10.` | Számlázáshoz használt cím.                                   |
| Szállítási cím | `shipping_address` | nem      | hosszabb szöveg          | `1111 Budapest, Raktár utca 2.` | Kiszállításhoz használt cím.                                 |
| Megjegyzések   | `notes`            | nem      | hosszabb szöveg          | `Oktatási vevő.`                | Belső megjegyzés.                                            |
| Aktív          | `is_active`        | nem      | igaz/hamis               | `igen`                          | Inaktív vevőhöz ne rögzíts új rendelést.                     |

```
## Minta rekord
```

| Mező           | Érték                           |
| -------------- | ------------------------------- |
| Kód            | `CUST-0001`                     |
| Név            | `Minta Gépipari Kft.`           |
| Adószám        | `12345678-2-42`                 |
| Email          | `rendeles@mintagepipar.test`    |
| Telefon        | `+36 1 555 0101`                |
| Számlázási cím | `1111 Budapest, Minta utca 10.` |
| Szállítási cím | `1111 Budapest, Raktár utca 2.` |
| Megjegyzések   | `Oktatási vevő.`                |
| Aktív          | `igen`                          |

```
## Kapcsolódó adatok
```

Előtte nincs szükség más adatra.

```
Ezt a vevőt használjuk a vevőrendelésnél.
```

## Gyakori hibák

```
- Üresen marad a kód vagy a név.
- Hibás email formátumot írsz.
- Már létező vevőkódot adsz meg.
```

## A létrehozás után elérhető

```
Ezután már létrehozható vagy értelmezhető:
```

- customer_order

```
## Oktatási megjegyzés
```

A vevő az első üzleti kapaszkodó. Ha a vevő adatai tiszták, a rendelési folyamat később sokkal könnyebben követhető.

```
## Knowledge Graph kapcsolatok
```

Kapcsolódó entitások:

```
-> Customer Order
-> Production Plan
-> Document
```

## Learning Center

```
Ez a fejezet a következő tananyagokban szerepel:
```

- Kezdő felhasználói útmutató
- Első gyártási rendelés (Tervezett)

```
## AI metaadat
```

```yaml
knowledge:
  entity: customer
  zero_state: true
  user_creatable: true
  generated: false
  prerequisites: []
  unlocks:
    - customer_order
  learning_level: beginner
`

# 6. Beszállító / Supplier
```

## Állapot

```
🟢 Kötelező induláskor
```

## Előfeltételek

```
Ehhez előbb létre kell hozni:
```

- nincs

```
## Mire szolgál?
```

A beszállító az a partner, akitől alapanyagot vásárolsz.

```
## Forrás
```

- migráció: `database/migrations/2026_06_21_000006_create_suppliers_table.php`
- model: `app/Models/Supplier.php`
- FormRequest: `app/Http/Requests/Admin/StoreSupplierRequest.php`
- Vue oldal: `resources/js/Pages/Admin/Suppliers/Index.vue`
- enum: nincs

```
## Hol használjuk?
```

Admin felület -> Beszállítók.

```
## Űrlapon megadható mezők
```

| Űrlap mező   | Adatbázis mező | Kötelező | Típus                    | Minta érték                         | Megjegyzés                                      |
| ------------ | -------------- | -------- | ------------------------ | ----------------------------------- | ----------------------------------------------- |
| Kód          | `code`         | igen     | szöveg, max. 255, egyedi | `SUP-0001`                          | Beszállítói azonosító.                          |
| Név          | `name`         | igen     | szöveg, max. 255         | `Acél Plusz Kft.`                   | Ezt választod beszerzésnél.                     |
| Adószám      | `tax_number`   | nem      | szöveg, max. 255         | `87654321-2-13`                     | Szabad szöveg.                                  |
| Email        | `email`        | nem      | email                    | `ertekesites@acelplusz.test`        | Csak email formátumot fogad el.                 |
| Telefon      | `phone`        | nem      | szöveg                   | `+36 20 333 4444`                   | Szabad formátumú mező.                          |
| Cím          | `address`      | nem      | hosszabb szöveg          | `8000 Székesfehérvár, Acél út 5.`   | Beszállítói cím.                                |
| Megjegyzések | `notes`        | nem      | hosszabb szöveg          | `Acéllemez és kötőelem beszállító.` | Belső megjegyzés.                               |
| Aktív        | `is_active`    | nem      | igaz/hamis               | `igen`                              | Inaktív beszállítót ne használj új rendeléshez. |

```
## Minta rekord
```

| Mező         | Érték                               |
| ------------ | ----------------------------------- |
| Kód          | `SUP-0001`                          |
| Név          | `Acél Plusz Kft.`                   |
| Adószám      | `87654321-2-13`                     |
| Email        | `ertekesites@acelplusz.test`        |
| Telefon      | `+36 20 333 4444`                   |
| Cím          | `8000 Székesfehérvár, Acél út 5.`   |
| Megjegyzések | `Acéllemez és kötőelem beszállító.` |
| Aktív        | `igen`                              |

```
## Kapcsolódó adatok
```

Előtte nincs szükség más adatra.

```
Ezt használjuk a beszerzési rendelésnél és a tételkötegnél.
```

## Gyakori hibák

```
- Már létező beszállítókódot adsz meg.
- Hibás email formátumot írsz.
- Inaktív beszállítót választanál új beszerzéshez.
```

## A létrehozás után elérhető

```
Ezután már létrehozható vagy értelmezhető:
```

- purchase_order
- item_batch

```
## Oktatási megjegyzés
```

A beszállító a beszerzési történet kiindulópontja. A kezdőknek érdemes egyetlen beszállítóval gyakorolni, amíg a folyamat összeáll.

```
## Knowledge Graph kapcsolatok
```

Kapcsolódó entitások:

```
-> Purchase Order
-> Item Batch
-> Goods Receipt
```

## Learning Center

```
Ez a fejezet a következő tananyagokban szerepel:
```

- Kezdő felhasználói útmutató
- Készletkezelés alapjai (Tervezett)
- Beszerzés alapjai (Tervezett)

```
## AI metaadat
```

```yaml
knowledge:
  entity: supplier
  zero_state: true
  user_creatable: true
  generated: false
  prerequisites: []
  unlocks:
    - purchase_order
    - item_batch
  learning_level: beginner
`

# 7. Cikk / Item
```

## Állapot

```
🟢 Kötelező induláskor
```

## Előfeltételek

```
Ehhez előbb létre kell hozni:
```

- nincs

```
## Mire szolgál?
```

A cikk minden olyan termék vagy alapanyag, amit a rendszerben nyilvántartasz.

```
## Forrás
```

- migráció: `database/migrations/2026_06_21_000007_create_items_table.php`
- model: `app/Models/Item.php`
- FormRequest: `app/Http/Requests/Admin/StoreItemRequest.php`
- Vue oldal: `resources/js/Pages/Admin/Items/Index.vue`
- enum: `app/Enums/ItemType.php`

```
## Hol használjuk?
```

Admin felület -> Cikkek.

```
## Űrlapon megadható mezők
```

| Űrlap mező            | Adatbázis mező           | Kötelező | Típus                    | Minta érték        | Megjegyzés                                            |
| --------------------- | ------------------------ | -------- | ------------------------ | ------------------ | ----------------------------------------------------- |
| Cikkszám              | `item_number`            | igen     | szöveg, max. 255, egyedi | `PRD-1001`         | Ez az elsődleges cikkazonosító.                       |
| Név                   | `name`                   | igen     | szöveg, max. 255         | `Acél konzol`      | Felhasználói listákban ez jelenik meg.                |
| Típus                 | `item_type`              | igen     | enum                     | `finished_product` | Készterméknél ezt válaszd.                            |
| Egység                | `unit`                   | igen     | szöveg, max. 50          | `db`               | A rendelés és készlet egysége.                        |
| Szélesség             | `width`                  | nem      | szám, minimum 0          | `80`               | Méretadat, ha ismert.                                 |
| Hossz                 | `length`                 | nem      | szám, minimum 0          | `120`              | Méretadat, ha ismert.                                 |
| Vastagság             | `thickness`              | nem      | szám, minimum 0          | `4`                | Méretadat, ha ismert.                                 |
| Átmérő                | `diameter`               | nem      | szám, minimum 0          | üres               | Csak kör keresztmetszetű cikkeknél használd.          |
| Sorozatszám szükséges | `requires_serial_number` | nem      | igaz/hamis               | `igen`             | Készterméknél hasznos, mert egyesével követhető lesz. |
| Aktív                 | `is_active`              | nem      | igaz/hamis               | `igen`             | Inaktív cikket ne használj új folyamatban.            |

```
## Minta rekord
```

| Mező                  | Érték              |
| --------------------- | ------------------ |
| Cikkszám              | `PRD-1001`         |
| Név                   | `Acél konzol`      |
| Típus                 | `finished_product` |
| Egység                | `db`               |
| Szélesség             | `80`               |
| Hossz                 | `120`              |
| Vastagság             | `4`                |
| Átmérő                | üres               |
| Sorozatszám szükséges | `igen`             |
| Aktív                 | `igen`             |

```
## Kapcsolódó adatok
```

Előtte nincs szükség más adatra.

```
További cikkek az oktatási példához:
```

| Cikkszám   | Név                | Típus                | Egység | Sorozatszám szükséges |
| ---------- | ------------------ | -------------------- | ------ | --------------------- |
| `MAT-0001` | `4 mm acéllemez`   | `purchased_material` | `db`   | nem                   |
| `MAT-0100` | `M8x20 csavar`     | `purchased_material` | `db`   | nem                   |
| `MAT-0200` | `Szürke porfesték` | `purchased_material` | `kg`   | nem                   |

```
## Gyakori hibák
```

- Cikkszám nélkül próbálsz menteni.
- Olyan típust választasz, amely nem illik a cikkhez.
- A mennyiségi egység eltér a későbbi BOM vagy rendelési egységtől.

```
## A létrehozás után elérhető
```

Ezután már létrehozható vagy értelmezhető:

```
- bom
- bom_item
- operation_sequence
- customer_order_item
- goods_receipt_item
- stock_balance
```

## Oktatási megjegyzés

```
A cikkeknél a legfontosabb döntés a típus és az egység. Ha ezek később keverednek, a BOM, a rendelés és a készlet is nehezebben érthető.
```

## Knowledge Graph kapcsolatok

```
Kapcsolódó entitások:
```

-> BOM
-> BOM Item
-> Operation Sequence
-> Customer Order Item
-> Goods Receipt Item
-> Stock Balance

```
## Learning Center
```

Ez a fejezet a következő tananyagokban szerepel:

```
- Kezdő felhasználói útmutató
- Első gyártási rendelés (Tervezett)
- Készletkezelés alapjai (Tervezett)
```

## AI metaadat

````
```yaml
knowledge:
  entity: item
  zero_state: true
  user_creatable: true
  generated: false
  prerequisites: []
  unlocks:
    - bom
    - bom_item
    - operation_sequence
    - customer_order_item
    - goods_receipt_item
    - stock_balance
  learning_level: beginner
`

# 8. Tételköteg / Item Batch
````

## Állapot

```
🔵 Automatikusan létrejövő adat
```

## Előfeltételek

```
Ehhez előbb létre kell hozni:
```

- item
- supplier

```
## Mire szolgál?
```

A tételköteg egy adott beszerzési vagy beérkezési adag. Például egy csomag acéllemez vagy egy doboz csavar.

```
## Forrás
```

- migráció: `database/migrations/2026_06_21_000008_create_item_batches_table.php`
- model: `app/Models/ItemBatch.php`
- FormRequest: nincs külön közvetlen űrlap
- Vue oldal: nincs közvetlen felhasználói űrlap
- enum: nincs

```
## Hol használjuk?
```

Ez az adat jelenleg nem közvetlenül felhasználói űrlapon jön létre.

```
A beérkezési tétel tud `item_batch_id` mezőre hivatkozni, de a beérkezési képernyő jelenlegi űrlapja nem mutat külön tételköteg-választót.
```

## Űrlapon megadható mezők

```
Nincs közvetlen tételköteg-létrehozó űrlap.
```

| Űrlap mező           | Adatbázis mező | Kötelező     | Típus           | Minta érték          | Megjegyzés                       |
| -------------------- | -------------- | ------------ | --------------- | -------------------- | -------------------------------- |
| nincs közvetlen mező | `item_id`      | rendszeradat | kapcsolat       | `MAT-0001`           | A köteg egy cikkhez tartozik.    |
| nincs közvetlen mező | `batch_number` | rendszeradat | szöveg          | `BATCH-MAT-0001-001` | Egy cikken belül egyedi.         |
| nincs közvetlen mező | `supplier_id`  | rendszeradat | kapcsolat       | `Acél Plusz Kft.`    | Nem kötelező adatmodell szerint. |
| nincs közvetlen mező | `received_at`  | rendszeradat | dátum           | `2026-07-06`         | Beérkezés dátuma lehet.          |
| nincs közvetlen mező | `expires_at`   | rendszeradat | dátum           | üres                 | Csak lejáratos anyagnál fontos.  |
| nincs közvetlen mező | `notes`        | rendszeradat | hosszabb szöveg | `Oktatási köteg.`    | Belső megjegyzés.                |

```
## Minta rekord
```

| Mező       | Érték                       |
| ---------- | --------------------------- |
| Cikk       | `MAT-0001 - 4 mm acéllemez` |
| Tételszám  | `BATCH-MAT-0001-001`        |
| Beszállító | `Acél Plusz Kft.`           |
| Beérkezett | `2026-07-06`                |
| Lejárat    | üres                        |
| Megjegyzés | `Oktatási köteg.`           |

```
## Kapcsolódó adatok
```

Előtte kell cikk és beszállító.

```
## Gyakori hibák
```

- Olyan köteget feltételezel, amit nem hoztál létre.
- Ugyanahhoz a cikkhez duplikált tételszámot használnál.
- A beérkezésnél köteget keresel, de a jelenlegi űrlap nem erre épül.

```
## A létrehozás után elérhető
```

Ezután már létrehozható vagy értelmezhető:

```
- goods_receipt_item
- stock_balance
- stock_movement
- production_task_material
```

## Oktatási megjegyzés

```
A tételköteg haladóbb nyomon követési fogalom. Kezdéskor elég megérteni, hogy egy beérkezett adag azonosítására szolgál.
```

## Knowledge Graph kapcsolatok

```
Kapcsolódó entitások:
```

-> Item
-> Supplier
-> Goods Receipt Item
-> Stock Balance
-> Stock Movement

```
## Learning Center
```

Ez a fejezet a következő tananyagokban szerepel:

```
- Készletkezelés alapjai (Tervezett)
- Beszerzés alapjai (Tervezett)
```

## AI metaadat

````
```yaml
knowledge:
  entity: item_batch
  zero_state: true
  user_creatable: false
  generated: true
  prerequisites:
    - item
    - supplier
  unlocks:
    - goods_receipt_item
    - stock_balance
    - stock_movement
    - production_task_material
  learning_level: beginner
`

# 9. Sorozatszám / Item Instance
````

## Állapot

```
🔵 Automatikusan létrejövő adat
```

## Előfeltételek

```
Ehhez előbb létre kell hozni:
```

- item
- factory_unit
- production_order

```
## Mire szolgál?
```

A sorozatszám egy konkrét, egyedileg követett darabot jelöl.

```
## Forrás
```

- migráció: `database/migrations/2026_06_21_000010_create_item_instances_table.php`
- model: `app/Models/ItemInstance.php`
- FormRequest: nincs közvetlen létrehozó űrlap
- Vue oldal: nincs közvetlen létrehozó űrlap
- enum: `app/Enums/ItemInstanceStatus.php`

```
## Hol használjuk?
```

Ez az adat jelenleg nem közvetlenül felhasználói űrlapon jön létre.

```
A sorozatszám a gyártási rendelésből és a gyártási feladatokból követhető.
```

## Űrlapon megadható mezők

```
Nincs közvetlen sorozatszám-létrehozó űrlap.
```

| Űrlap mező           | Adatbázis mező        | Kötelező     | Típus          | Minta érték            | Megjegyzés                    |
| -------------------- | --------------------- | ------------ | -------------- | ---------------------- | ----------------------------- |
| nincs közvetlen mező | `item_id`             | rendszeradat | kapcsolat      | `PRD-1001`             | A követett cikk.              |
| nincs közvetlen mező | `serial_number`       | rendszeradat | szöveg, egyedi | `PRD-1001-000001`      | Egyedi azonosító.             |
| nincs közvetlen mező | `factory_unit_id`     | rendszeradat | kapcsolat      | `Budapesti gyártóüzem` | A gyártási hely.              |
| nincs közvetlen mező | `current_location_id` | rendszeradat | kapcsolat      | `MUHELY-1`             | Aktuális hely.                |
| nincs közvetlen mező | `current_status`      | rendszeradat | enum           | `planned`              | Állapot.                      |
| nincs közvetlen mező | `production_order_id` | rendszeradat | kapcsolat      | `PO-2026-0001`         | Kapcsolódó gyártási rendelés. |

```
## Minta rekord
```

| Mező              | Érték                    |
| ----------------- | ------------------------ |
| Cikk              | `PRD-1001 - Acél konzol` |
| Sorozatszám       | `PRD-1001-000001`        |
| Gyártási egység   | `Budapesti gyártóüzem`   |
| Aktuális hely     | `MUHELY-1`               |
| Státusz           | `planned`                |
| Gyártási rendelés | `PO-2026-0001`           |

```
## Kapcsolódó adatok
```

Előtte kell gyártási rendelés, cikk és gyártási egység.

```
## Gyakori hibák
```

- A terméknél nincs bekapcsolva a sorozatszám szükségessége.
- Sorozatszámot kézzel keresel létrehozó űrlapon, de ilyen űrlap nincs.
- Ugyanazt a sorozatszámot nem lehet kétszer használni.

```
## A létrehozás után elérhető
```

Ezután már létrehozható vagy értelmezhető:

```
- production_task
- quality_check
- stock_movement
```

## Oktatási megjegyzés

```
A sorozatszám azt segít megérteni, hogy nem csak cikket, hanem konkrét darabot is követhetünk. Ez a nyomon követhetőség alapja.
```

## Knowledge Graph kapcsolatok

```
Kapcsolódó entitások:
```

-> Item
-> Factory Unit
-> Production Order
-> Production Task
-> Quality Check

```
## Learning Center
```

Ez a fejezet a következő tananyagokban szerepel:

```
- Első gyártási rendelés (Tervezett)
- Nyomon követés alapjai (Tervezett)
```

## AI metaadat

````
```yaml
knowledge:
  entity: item_instance
  zero_state: true
  user_creatable: false
  generated: true
  prerequisites:
    - item
    - factory_unit
    - production_order
  unlocks:
    - production_task
    - quality_check
    - stock_movement
  learning_level: beginner
`

# 10. BOM / Darabjegyzék
````

## Állapot

```
🟢 Kötelező induláskor
```

## Előfeltételek

```
Ehhez előbb létre kell hozni:
```

- item

```
## Mire szolgál?
```

A BOM, magyarul darabjegyzék, megmondja, miből áll egy termék.

```
## Forrás
```

- migráció: `database/migrations/2026_06_21_000012_create_boms_table.php`
- model: `app/Models/Bom.php`
- FormRequest: `app/Http/Requests/Admin/StoreBomRequest.php`
- Vue oldal: `resources/js/Pages/Admin/Boms/Index.vue`
- komponens: `resources/js/Pages/Admin/Boms/Partials/BomItemsEditor.vue`
- enum: nincs

```
## Hol használjuk?
```

Admin felület -> Darabjegyzékek.

```
## Űrlapon megadható mezők
```

| Űrlap mező | Adatbázis mező | Kötelező | Típus                 | Minta érték                                  | Megjegyzés                                          |
| ---------- | -------------- | -------- | --------------------- | -------------------------------------------- | --------------------------------------------------- |
| Cikk       | `item_id`      | igen     | választólista         | `PRD-1001 - Acél konzol`                     | Annak a terméknek a cikke, amit gyártani szeretnél. |
| Verzió     | `version`      | igen     | egész szám, minimum 1 | `1`                                          | Egy cikken belül egyedi legyen.                     |
| Név        | `name`         | igen     | szöveg, max. 255      | `Acél konzol alap darabjegyzék`              | Emberi név.                                         |
| Aktív      | `is_active`    | nem      | igaz/hamis            | `igen`                                       | Gyártási tervezéshez aktív BOM kell.                |
| Leírás     | `description`  | nem      | hosszabb szöveg       | `10 db-os oktatási gyártáshoz használt BOM.` | Rövid magyarázat.                                   |

```
## Minta rekord
```

| Mező   | Érték                                        |
| ------ | -------------------------------------------- |
| Cikk   | `PRD-1001 - Acél konzol`                     |
| Verzió | `1`                                          |
| Név    | `Acél konzol alap darabjegyzék`              |
| Aktív  | `igen`                                       |
| Leírás | `10 db-os oktatási gyártáshoz használt BOM.` |

```
## Kapcsolódó adatok
```

Előtte létre kell hozni a késztermék cikket és az alapanyag cikkeket.

```
## Gyakori hibák
```

- Ugyanahhoz a cikkhez újra az `1` verziót adod meg.
- Inaktív cikkhez készítesz darabjegyzéket.
- Üresen hagyod a cikket vagy a nevet.

```
## A létrehozás után elérhető
```

Ezután már létrehozható vagy értelmezhető:

```
- bom_item
- production_plan_item
- production_order
- material_requirement
```

## Oktatási megjegyzés

```
A BOM-ot úgy érdemes tanítani, mint egy receptet. Ha a recept hiányos, a rendszer sem tudja, miből készüljön a termék.
```

## Knowledge Graph kapcsolatok

```
Kapcsolódó entitások:
```

-> Item
-> BOM Item
-> Production Plan Item
-> Production Order
-> Material Requirement

```
## Learning Center
```

Ez a fejezet a következő tananyagokban szerepel:

```
- Kezdő felhasználói útmutató
- Első gyártási rendelés (Tervezett)
```

## AI metaadat

````
```yaml
knowledge:
  entity: bom
  zero_state: true
  user_creatable: true
  generated: false
  prerequisites:
    - item
  unlocks:
    - bom_item
    - production_plan_item
    - production_order
    - material_requirement
  learning_level: beginner
`

# 11. BOM tétel
````

## Állapot

```
🟢 Kötelező induláskor
```

## Előfeltételek

```
Ehhez előbb létre kell hozni:
```

- bom
- item

```
## Mire szolgál?
```

A BOM tétel egy alapanyagsort jelent a darabjegyzéken belül.

```
## Forrás
```

- migráció: `database/migrations/2026_06_21_000013_create_bom_items_table.php`
- model: `app/Models/BomItem.php`
- FormRequest: `app/Http/Requests/Admin/StoreBomRequest.php`
- Vue komponens: `resources/js/Pages/Admin/Boms/Partials/BomItemsEditor.vue`
- enum: nincs

```
## Hol használjuk?
```

Admin felület -> Darabjegyzékek -> BOM szerkesztő sorai.

```
## Űrlapon megadható mezők
```

| Űrlap mező   | Adatbázis mező                  | Kötelező | Típus                | Minta érték                      | Megjegyzés                                                    |
| ------------ | ------------------------------- | -------- | -------------------- | -------------------------------- | ------------------------------------------------------------- |
| Cikk         | `items.*.item_id` / `item_id`   | igen     | választólista        | `MAT-0001 - 4 mm acéllemez`      | Ugyanabban a BOM-ban ugyanaz a cikk csak egyszer szerepelhet. |
| Mennyiség    | `items.*.quantity` / `quantity` | igen     | szám, nagyobb mint 0 | `1`                              | Egy késztermékhez szükséges mennyiség.                        |
| Egység       | `items.*.unit` / `unit`         | igen     | szöveg, max. 50      | `db`                             | Egyezzen a cikk egységével.                                   |
| Megjegyzések | `items.*.notes` / `notes`       | nem      | hosszabb szöveg      | `Egy konzolhoz egy darab lemez.` | Belső megjegyzés.                                             |

```
## Minta rekord
```

| Mező         | Érték                            |
| ------------ | -------------------------------- |
| Cikk         | `MAT-0001 - 4 mm acéllemez`      |
| Mennyiség    | `1`                              |
| Egység       | `db`                             |
| Megjegyzések | `Egy konzolhoz egy darab lemez.` |

```
További BOM tételek:
```

| Cikk                          | Mennyiség | Egység | Megjegyzés           |
| ----------------------------- | --------- | ------ | -------------------- |
| `MAT-0100 - M8x20 csavar`     | `4`       | `db`   | `Rögzítő csavarok.`  |
| `MAT-0200 - Szürke porfesték` | `0.05`    | `kg`   | `Felületkezeléshez.` |

```
## Kapcsolódó adatok
```

Előtte kell egy BOM és legalább egy alapanyag cikk.

```
## Gyakori hibák
```

- Nulla vagy negatív mennyiséget adsz meg.
- Ugyanazt az alapanyagot kétszer adod hozzá ugyanahhoz a BOM-hoz.
- Az egység eltér a cikk törzsadatában használt egységtől.

```
## A létrehozás után elérhető
```

Ezután már létrehozható vagy értelmezhető:

```
- material_requirement
- purchase_requisition_item
- production_task_material
```

## Oktatási megjegyzés

```
A BOM tételnél mindig egy darab késztermékhez gondolkodj. A rendszer majd felszorozza a mennyiséget a rendelési darabszámmal.
```

## Knowledge Graph kapcsolatok

```
Kapcsolódó entitások:
```

-> BOM
-> Item
-> Material Requirement
-> Production Task Material

```
## Learning Center
```

Ez a fejezet a következő tananyagokban szerepel:

```
- Kezdő felhasználói útmutató
- Első gyártási rendelés (Tervezett)
- Készletkezelés alapjai (Tervezett)
```

## AI metaadat

````
```yaml
knowledge:
  entity: bom_item
  zero_state: true
  user_creatable: true
  generated: false
  prerequisites:
    - bom
    - item
  unlocks:
    - material_requirement
    - purchase_requisition_item
    - production_task_material
  learning_level: beginner
`

# 12. Művelettípus
````

## Állapot

```
🟢 Kötelező induláskor
```

## Előfeltételek

```
Ehhez előbb létre kell hozni:
```

- nincs

```
## Mire szolgál?
```

A művelettípus egy ismételhető munkafajta, például vágás, festés vagy minőségellenőrzés.

```
## Forrás
```

- migráció: `database/migrations/2026_06_21_000014_create_operation_types_table.php`
- model: `app/Models/OperationType.php`
- FormRequest: `app/Http/Requests/Admin/StoreOperationTypeRequest.php`
- Vue oldal: `resources/js/Pages/Admin/OperationTypes/Index.vue`
- enum: `app/Enums/OperationTypeCode.php`

```
## Hol használjuk?
```

Admin felület -> Művelettípusok.

```
## Űrlapon megadható mezők
```

| Űrlap mező | Adatbázis mező | Kötelező | Típus            | Minta érték                 | Megjegyzés                                          |
| ---------- | -------------- | -------- | ---------------- | --------------------------- | --------------------------------------------------- |
| Kód        | `code`         | igen     | enum, egyedi     | `CUTTING`                   | Csak a rendszerben engedélyezett kód választható.   |
| Név        | `name`         | igen     | szöveg, max. 255 | `Darabolás`                 | Felhasználóbarát név.                               |
| Leírás     | `description`  | nem      | hosszabb szöveg  | `Alapanyag méretre vágása.` | Segít az oktatásban.                                |
| Aktív      | `is_active`    | nem      | igaz/hamis       | `igen`                      | Inaktív művelettípust ne használj új műveletsorban. |

```
## Minta rekord
```

| Mező   | Érték                       |
| ------ | --------------------------- |
| Kód    | `CUTTING`                   |
| Név    | `Darabolás`                 |
| Leírás | `Alapanyag méretre vágása.` |
| Aktív  | `igen`                      |

```
További művelettípusok:
```

| Kód             | Név                 |
| --------------- | ------------------- |
| `ASSEMBLY`      | `Összeszerelés`     |
| `PAINTING`      | `Festés`            |
| `QUALITY_CHECK` | `Minőségellenőrzés` |

```
## Kapcsolódó adatok
```

Előtte nincs szükség más adatra.

```
## Gyakori hibák
```

- Olyan kódot írnál be, amely nincs az enumban.
- Ugyanazt a kódot kétszer próbálod létrehozni.
- Inaktív művelettípust választasz műveletsorhoz.

```
## A létrehozás után elérhető
```

Ezután már létrehozható vagy értelmezhető:

```
- operation_sequence_step
```

## Oktatási megjegyzés

```
A művelettípus egy szótár a munkafajtákhoz. Minél egyszerűbb és következetesebb, annál könnyebb lesz műveletsort építeni.
```

## Knowledge Graph kapcsolatok

```
Kapcsolódó entitások:
```

-> Operation Sequence Step
-> Production Task

```
## Learning Center
```

Ez a fejezet a következő tananyagokban szerepel:

```
- Kezdő felhasználói útmutató
- Első gyártási rendelés (Tervezett)
```

## AI metaadat

````
```yaml
knowledge:
  entity: operation_type
  zero_state: true
  user_creatable: true
  generated: false
  prerequisites: []
  unlocks:
    - operation_sequence_step
  learning_level: beginner
`

# 13. Műveletsor
````

## Állapot

```
🟢 Kötelező induláskor
```

## Előfeltételek

```
Ehhez előbb létre kell hozni:
```

- item

```
## Mire szolgál?
```

A műveletsor megmondja, milyen lépésekből áll egy termék gyártása.

```
## Forrás
```

- migráció: `database/migrations/2026_06_21_000015_create_operation_sequences_table.php`
- model: `app/Models/OperationSequence.php`
- FormRequest: `app/Http/Requests/Admin/StoreOperationSequenceRequest.php`
- Vue oldal: `resources/js/Pages/Admin/OperationSequences/Index.vue`
- komponens: `resources/js/Pages/Admin/OperationSequences/Partials/SequenceStepsEditor.vue`
- enum: nincs

```
## Hol használjuk?
```

Admin felület -> Műveletsorok.

```
## Űrlapon megadható mezők
```

| Űrlap mező | Adatbázis mező | Kötelező | Típus                 | Minta érték                                     | Megjegyzés                                 |
| ---------- | -------------- | -------- | --------------------- | ----------------------------------------------- | ------------------------------------------ |
| Cikk       | `item_id`      | igen     | választólista         | `PRD-1001 - Acél konzol`                        | Ahhoz a termékhez tartozik, amit gyártasz. |
| Verzió     | `version`      | igen     | egész szám, minimum 1 | `1`                                             | Egy cikken belül egyedi legyen.            |
| Név        | `name`         | igen     | szöveg, max. 255      | `Acél konzol alap műveletsor`                   | Emberi név.                                |
| Aktív      | `is_active`    | nem      | igaz/hamis            | `igen`                                          | Gyártási tervhez aktív műveletsor kell.    |
| Leírás     | `description`  | nem      | hosszabb szöveg       | `Darabolás, összeszerelés, festés, ellenőrzés.` | Rövid folyamatleírás.                      |

```
## Minta rekord
```

| Mező   | Érték                                           |
| ------ | ----------------------------------------------- |
| Cikk   | `PRD-1001 - Acél konzol`                        |
| Verzió | `1`                                             |
| Név    | `Acél konzol alap műveletsor`                   |
| Aktív  | `igen`                                          |
| Leírás | `Darabolás, összeszerelés, festés, ellenőrzés.` |

```
## Kapcsolódó adatok
```

Előtte kell késztermék cikk, művelettípus, gyártási egység és szakmai szerep.

```
## Gyakori hibák
```

- Ugyanahhoz a cikkhez duplikált verziót adsz meg.
- Nem hoztad létre előtte a szükséges művelettípusokat.
- Inaktív műveletsort próbálsz gyártási tervben használni.

```
## A létrehozás után elérhető
```

Ezután már létrehozható vagy értelmezhető:

```
- operation_sequence_step
- production_plan_item
- production_order
```

## Oktatási megjegyzés

```
A műveletsor a gyártás útvonala. Kezdőként ne törekedj túl sok lépésre, először legyen végigérthető a folyamat.
```

## Knowledge Graph kapcsolatok

```
Kapcsolódó entitások:
```

-> Item
-> Operation Sequence Step
-> Production Plan Item
-> Production Order

```
## Learning Center
```

Ez a fejezet a következő tananyagokban szerepel:

```
- Kezdő felhasználói útmutató
- Első gyártási rendelés (Tervezett)
```

## AI metaadat

````
```yaml
knowledge:
  entity: operation_sequence
  zero_state: true
  user_creatable: true
  generated: false
  prerequisites:
    - item
  unlocks:
    - operation_sequence_step
    - production_plan_item
    - production_order
  learning_level: beginner
`

# 14. Műveletsor lépés
````

## Állapot

```
🟢 Kötelező induláskor
```

## Előfeltételek

```
Ehhez előbb létre kell hozni:
```

- operation_sequence
- operation_type
- factory_unit
- professional_role

```
## Mire szolgál?
```

A műveletsor lépés egy konkrét munkalépés a műveletsoron belül.

```
## Forrás
```

- migráció: `database/migrations/2026_06_21_000016_create_operation_sequence_steps_table.php`
- model: `app/Models/OperationSequenceStep.php`
- FormRequest: `app/Http/Requests/Admin/StoreOperationSequenceRequest.php`
- Vue komponens: `resources/js/Pages/Admin/OperationSequences/Partials/SequenceStepsEditor.vue`
- enum: nincs

```
## Hol használjuk?
```

Admin felület -> Műveletsorok -> lépések szerkesztése.

```
## Űrlapon megadható mezők
```

| Űrlap mező      | Adatbázis mező                                                      | Kötelező | Típus                         | Minta érték                   | Megjegyzés                               |
| --------------- | ------------------------------------------------------------------- | -------- | ----------------------------- | ----------------------------- | ---------------------------------------- |
| Sorrend         | `steps.*.step_order` / `step_order`                                 | igen     | egész szám, minimum 1, egyedi | `1`                           | A lépések sorrendje.                     |
| Művelet         | `steps.*.operation_type_id` / `operation_type_id`                   | igen     | választólista                 | `Darabolás`                   | Előtte létre kell hozni a művelettípust. |
| Gyártási egység | `steps.*.factory_unit_id` / `factory_unit_id`                       | igen     | választólista                 | `Budapesti gyártóüzem`        | Itt történik a lépés.                    |
| Szakmai szerep  | `steps.*.professional_role_id` / `professional_role_id`             | igen     | választólista                 | `Gépkezelő`                   | Ilyen szerepű dolgozó végezheti.         |
| Perc            | `steps.*.estimated_duration_minutes` / `estimated_duration_minutes` | igen     | egész szám, minimum 1         | `20`                          | Becsült idő egy darabra vagy feladatra.  |
| ME              | `steps.*.requires_quality_check` / `requires_quality_check`         | nem      | igaz/hamis                    | `nem`                         | Jelöld, ha ellenőrzés kell a lépés után. |
| Utasítások      | `steps.*.instructions` / `instructions`                             | nem      | hosszabb szöveg               | `Vágd méretre az acéllemezt.` | Rövid munkautasítás.                     |

```
## Minta rekord
```

| Mező            | Érték                         |
| --------------- | ----------------------------- |
| Sorrend         | `1`                           |
| Művelet         | `Darabolás`                   |
| Gyártási egység | `Budapesti gyártóüzem`        |
| Szakmai szerep  | `Gépkezelő`                   |
| Perc            | `20`                          |
| ME              | `nem`                         |
| Utasítások      | `Vágd méretre az acéllemezt.` |

```
További lépések:
```

| Sorrend | Művelet             | Perc | ME   |
| ------- | ------------------- | ---- | ---- |
| `2`     | `Összeszerelés`     | `25` | nem  |
| `3`     | `Festés`            | `30` | igen |
| `4`     | `Minőségellenőrzés` | `10` | igen |

```
## Kapcsolódó adatok
```

Előtte kell műveletsor, művelettípus, gyártási egység és szakmai szerep.

```
## Gyakori hibák
```

- Kétszer adod meg ugyanazt a sorszámot.
- Nulla percet írsz be.
- Hiányzik a gyártási egység vagy a szakmai szerep.

```
## A létrehozás után elérhető
```

Ezután már létrehozható vagy értelmezhető:

```
- production_task
- quality_check
```

## Oktatási megjegyzés

```
A lépések sorrendje tanítja meg a felhasználónak, hogy a gyártás nem egyetlen gombnyomás. Minden lépésnek helye, felelőse és ideje van.
```

## Knowledge Graph kapcsolatok

```
Kapcsolódó entitások:
```

-> Operation Sequence
-> Operation Type
-> Factory Unit
-> Professional Role
-> Production Task

```
## Learning Center
```

Ez a fejezet a következő tananyagokban szerepel:

```
- Kezdő felhasználói útmutató
- Első gyártási rendelés (Tervezett)
```

## AI metaadat

````
```yaml
knowledge:
  entity: operation_sequence_step
  zero_state: true
  user_creatable: true
  generated: false
  prerequisites:
    - operation_sequence
    - operation_type
    - factory_unit
    - professional_role
  unlocks:
    - production_task
    - quality_check
  learning_level: beginner
`

# 15. Vevőrendelés
````

## Állapot

```
🟡 Később szükséges
```

## Előfeltételek

```
Ehhez előbb létre kell hozni:
```

- customer
- item

```
## Mire szolgál?
```

A vevőrendelés rögzíti, hogy a vevő mit kér és mikorra.

```
## Forrás
```

- migráció: `database/migrations/2026_06_21_000017_create_customer_orders_table.php`
- model: `app/Models/CustomerOrder.php`
- FormRequest: `app/Http/Requests/Admin/StoreCustomerOrderRequest.php`
- Vue oldal: `resources/js/Pages/Admin/CustomerOrders/Index.vue`
- Vue komponens: `resources/js/Pages/Admin/CustomerOrders/Partials/CustomerOrderForm.vue`
- enum: `app/Enums/CustomerOrderStatus.php`

```
## Hol használjuk?
```

Admin felület -> Vevőrendelések.

```
## Űrlapon megadható mezők
```

| Űrlap mező            | Adatbázis mező            | Kötelező | Típus           | Minta érték               | Megjegyzés                       |
| --------------------- | ------------------------- | -------- | --------------- | ------------------------- | -------------------------------- |
| Vevő                  | `customer_id`             | igen     | választólista   | `Minta Gépipari Kft.`     | Előtte létre kell hozni a vevőt. |
| Kért szállítási dátum | `requested_delivery_date` | nem      | dátum           | `2026-07-20`              | A vevő által kért dátum.         |
| Megjegyzések          | `notes`                   | nem      | hosszabb szöveg | `Első oktatási rendelés.` | Belső megjegyzés.                |

```
Az `order_number`, `status`, `confirmed_at` és `created_by` nem közvetlen űrlapmező.
```

## Minta rekord

```
| Mező                  | Érték                     |
| --------------------- | ------------------------- |
| Vevő                  | `Minta Gépipari Kft.`     |
| Kért szállítási dátum | `2026-07-20`              |
| Megjegyzések          | `Első oktatási rendelés.` |
```

## Kapcsolódó adatok

```
Előtte kell vevő és legalább egy rendelhető cikk.
```

## Gyakori hibák

```
- Nem választasz vevőt.
- Nem adsz hozzá rendelési tételt.
- Inaktív vevővel próbálsz új rendelést készíteni.
```

## A létrehozás után elérhető

```
Ezután már létrehozható vagy értelmezhető:
```

- customer_order_item
- production_plan
- material_requirement

```
## Oktatási megjegyzés
```

A vevőrendelésnél kezdődik a konkrét történet: ki mit kér. Innen válik a törzsadat gyakorlati folyamattá.

```
## Knowledge Graph kapcsolatok
```

Kapcsolódó entitások:

```
-> Customer
-> Customer Order Item
-> Production Plan
-> Material Requirement
```

## Learning Center

```
Ez a fejezet a következő tananyagokban szerepel:
```

- Kezdő felhasználói útmutató
- Első gyártási rendelés (Tervezett)

```
## AI metaadat
```

```yaml
knowledge:
  entity: customer_order
  zero_state: true
  user_creatable: true
  generated: false
  prerequisites:
    - customer
    - item
  unlocks:
    - customer_order_item
    - production_plan
    - material_requirement
  learning_level: beginner
`

# 16. Vevőrendelés tétel
```

## Állapot

```
🟡 Később szükséges
```

## Előfeltételek

```
Ehhez előbb létre kell hozni:
```

- customer_order
- item

```
## Mire szolgál?
```

A vevőrendelés tétel mutatja meg, melyik cikkből mennyit kér a vevő.

```
## Forrás
```

- migráció: `database/migrations/2026_06_21_000018_create_customer_order_items_table.php`
- model: `app/Models/CustomerOrderItem.php`
- FormRequest: `app/Http/Requests/Admin/StoreCustomerOrderRequest.php`
- Vue komponens: `resources/js/Pages/Admin/CustomerOrders/Partials/CustomerOrderItemsEditor.vue`
- enum: `app/Enums/CustomerOrderItemStatus.php`

```
## Hol használjuk?
```

Admin felület -> Vevőrendelések -> rendelési tételek.

```
## Űrlapon megadható mezők
```

| Űrlap mező   | Adatbázis mező                  | Kötelező | Típus                | Minta érték                    | Megjegyzés                            |
| ------------ | ------------------------------- | -------- | -------------------- | ------------------------------ | ------------------------------------- |
| Cikk         | `items.*.item_id` / `item_id`   | igen     | választólista        | `PRD-1001 - Acél konzol`       | Előtte létre kell hozni a cikket.     |
| Mennyiség    | `items.*.quantity` / `quantity` | igen     | szám, nagyobb mint 0 | `10`                           | Az oktatási példa 10 db-bal dolgozik. |
| Egység       | `items.*.unit` / `unit`         | igen     | szöveg, max. 50      | `db`                           | Egyezzen a cikk egységével.           |
| Megjegyzések | `items.*.notes` / `notes`       | nem      | hosszabb szöveg      | `Oktatási gyártási mennyiség.` | Belső megjegyzés.                     |

```
## Minta rekord
```

| Mező         | Érték                          |
| ------------ | ------------------------------ |
| Cikk         | `PRD-1001 - Acél konzol`       |
| Mennyiség    | `10`                           |
| Egység       | `db`                           |
| Megjegyzések | `Oktatási gyártási mennyiség.` |

```
## Kapcsolódó adatok
```

Előtte kell vevőrendelés és cikk.

```
## Gyakori hibák
```

- Nulla vagy negatív mennyiséget adsz meg.
- Üres marad az egység.
- Olyan cikket választasz, amely még nincs létrehozva.

```
## A létrehozás után elérhető
```

Ezután már létrehozható vagy értelmezhető:

```
- production_plan_item
- material_requirement
- production_order
```

## Oktatási megjegyzés

```
A tételnél mindig ellenőrizd az egységet. A 10 db csak akkor érthető, ha a cikk egysége is db.
```

## Knowledge Graph kapcsolatok

```
Kapcsolódó entitások:
```

-> Customer Order
-> Item
-> Production Plan Item
-> Material Requirement
-> Production Order

```
## Learning Center
```

Ez a fejezet a következő tananyagokban szerepel:

```
- Kezdő felhasználói útmutató
- Első gyártási rendelés (Tervezett)
```

## AI metaadat

````
```yaml
knowledge:
  entity: customer_order_item
  zero_state: true
  user_creatable: true
  generated: false
  prerequisites:
    - customer_order
    - item
  unlocks:
    - production_plan_item
    - material_requirement
    - production_order
  learning_level: beginner
`

# 17. Gyártási terv
````

## Állapot

```
🟡 Később szükséges
```

## Előfeltételek

```
Ehhez előbb létre kell hozni:
```

- customer_order

```
## Mire szolgál?
```

A gyártási terv a vevőrendelésből indul ki, és megmondja, mikor szeretnéd legyártani a tételeket.

```
## Forrás
```

- migráció: `database/migrations/2026_06_21_000019_create_production_plans_table.php`
- model: `app/Models/ProductionPlan.php`
- FormRequest: `app/Http/Requests/Admin/StoreProductionPlanRequest.php`
- Vue oldal: `resources/js/Pages/Admin/ProductionPlans/Index.vue`
- Vue komponens: `resources/js/Pages/Admin/ProductionPlans/Partials/ProductionPlanForm.vue`
- enum: `app/Enums/ProductionPlanStatus.php`

```
## Hol használjuk?
```

Admin felület -> Gyártási tervek.

```
## Űrlapon megadható mezők
```

| Űrlap mező          | Adatbázis mező        | Kötelező | Típus                                | Minta érték                     | Megjegyzés                               |
| ------------------- | --------------------- | -------- | ------------------------------------ | ------------------------------- | ---------------------------------------- |
| Vevőrendelés        | `customer_order_id`   | igen     | választólista                        | `Minta Gépipari Kft. rendelése` | Előtte létre kell hozni a vevőrendelést. |
| Tervezett kezdés    | `planned_start_date`  | nem      | dátum                                | `2026-07-08`                    | Mikor induljon a gyártás.                |
| Tervezett befejezés | `planned_finish_date` | nem      | dátum, kezdés után vagy azonos napon | `2026-07-12`                    | Nem lehet korábbi, mint a kezdés.        |
| Megjegyzések        | `notes`               | nem      | hosszabb szöveg                      | `Első oktatási gyártási terv.`  | Belső megjegyzés.                        |

```
Az `plan_number`, `status`, `created_by`, `approved_by` és `approved_at` nem közvetlen létrehozási űrlapmező.
```

## Minta rekord

```
| Mező                | Érték                           |
| ------------------- | ------------------------------- |
| Vevőrendelés        | `Minta Gépipari Kft. rendelése` |
| Tervezett kezdés    | `2026-07-08`                    |
| Tervezett befejezés | `2026-07-12`                    |
| Megjegyzések        | `Első oktatási gyártási terv.`  |
```

## Kapcsolódó adatok

```
Előtte kell vevőrendelés legalább egy tétellel.
```

## Gyakori hibák

```
- Nem választasz vevőrendelést.
- A befejezés dátuma korábbi, mint a kezdés.
- Jóváhagyás előtt hiányzik a BOM vagy a műveletsor a tervtételeknél.
```

## A létrehozás után elérhető

```
Ezután már létrehozható vagy értelmezhető:
```

- production_plan_item
- production_order

```
## Oktatási megjegyzés
```

A gyártási terv hidat képez a vevői igény és a műhelymunka között. Itt érdemes megállni és ellenőrizni, hogy minden előkészítő adat megvan-e.

```
## Knowledge Graph kapcsolatok
```

Kapcsolódó entitások:

```
-> Customer Order
-> Production Plan Item
-> Production Order
```

## Learning Center

```
Ez a fejezet a következő tananyagokban szerepel:
```

- Kezdő felhasználói útmutató
- Első gyártási rendelés (Tervezett)

```
## AI metaadat
```

```yaml
knowledge:
  entity: production_plan
  zero_state: true
  user_creatable: true
  generated: false
  prerequisites:
    - customer_order
  unlocks:
    - production_plan_item
    - production_order
  learning_level: beginner
`

# 18. Gyártási terv tétel
```

## Állapot

```
🟡 Később szükséges
```

## Előfeltételek

```
Ehhez előbb létre kell hozni:
```

- production_plan
- customer_order_item
- bom
- operation_sequence

```
## Mire szolgál?
```

A gyártási terv tétel a vevőrendelés tételéből keletkezik. Szerkesztéskor ehhez választod ki a BOM-ot és a műveletsort.

```
## Forrás
```

- migráció: `database/migrations/2026_06_21_000020_create_production_plan_items_table.php`
- migráció: `database/migrations/2026_06_23_000001_add_planning_selection_to_production_plan_items_table.php`
- model: `app/Models/ProductionPlanItem.php`
- FormRequest: `app/Http/Requests/Admin/UpdateProductionPlanRequest.php`
- Vue komponens: `resources/js/Pages/Admin/ProductionPlans/Partials/ProductionPlanItemsEditor.vue`
- enum: `app/Enums/ProductionPlanItemStatus.php`

```
## Hol használjuk?
```

Admin felület -> Gyártási tervek -> terv szerkesztése.

```
## Űrlapon megadható mezők
```

Létrehozáskor a tételeket a rendszer a vevőrendelés tételeiből hozza létre. Szerkesztéskor ezek a mezők adhatók meg:

```
| Űrlap mező   | Adatbázis mező                                            | Kötelező | Típus           | Minta érték                     | Megjegyzés                                 |
| ------------ | --------------------------------------------------------- | -------- | --------------- | ------------------------------- | ------------------------------------------ |
| Darabjegyzék | `items.*.bom_id` / `bom_id`                               | nem      | választólista   | `Acél konzol alap darabjegyzék` | Gyártási rendelés generálásához szükséges. |
| Műveleti sor | `items.*.operation_sequence_id` / `operation_sequence_id` | nem      | választólista   | `Acél konzol alap műveletsor`   | Gyártási feladatokhoz szükséges.           |
| Kezdés       | `items.*.planned_start_date` / `planned_start_date`       | nem      | dátum           | `2026-07-08`                    | Tételszintű kezdés.                        |
| Befejezés    | `items.*.planned_finish_date` / `planned_finish_date`     | nem      | dátum           | `2026-07-12`                    | Ne legyen korábbi, mint a tétel kezdése.   |
| Megjegyzések | `items.*.notes` / `notes`                                 | nem      | hosszabb szöveg | `10 db Acél konzol gyártása.`   | Belső megjegyzés.                          |
```

## Minta rekord

```
| Mező         | Érték                           |
| ------------ | ------------------------------- |
| Darabjegyzék | `Acél konzol alap darabjegyzék` |
| Műveleti sor | `Acél konzol alap műveletsor`   |
| Kezdés       | `2026-07-08`                    |
| Befejezés    | `2026-07-12`                    |
| Megjegyzések | `10 db Acél konzol gyártása.`   |
```

## Kapcsolódó adatok

```
Előtte kell gyártási terv, BOM és műveletsor ugyanahhoz a cikkhez.
```

## Gyakori hibák

```
- Másik cikkhez tartozó BOM-ot keresel.
- Másik cikkhez tartozó műveletsort keresel.
- Befejezés dátuma korábbi, mint a kezdés.
```

## A létrehozás után elérhető

```
Ezután már létrehozható vagy értelmezhető:
```

- production_order
- material_requirement

```
## Oktatási megjegyzés
```

Ez a pont gyakran a hiányzó előkészítés helye. Ha nem választható BOM vagy műveletsor, térj vissza a cikk gyártási tudásához.

```
## Knowledge Graph kapcsolatok
```

Kapcsolódó entitások:

```
-> Production Plan
-> Customer Order Item
-> BOM
-> Operation Sequence
-> Production Order
```

## Learning Center

```
Ez a fejezet a következő tananyagokban szerepel:
```

- Első gyártási rendelés (Tervezett)

```
## AI metaadat
```

```yaml
knowledge:
  entity: production_plan_item
  zero_state: true
  user_creatable: true
  generated: false
  prerequisites:
    - production_plan
    - customer_order_item
    - bom
    - operation_sequence
  unlocks:
    - production_order
    - material_requirement
  learning_level: beginner
`

# 19. Gyártási rendelés
```

## Állapot

```
🔵 Automatikusan létrejövő adat
```

## Előfeltételek

```
Ehhez előbb létre kell hozni:
```

- production_plan_item
- bom
- operation_sequence

```
## Mire szolgál?
```

A gyártási rendelés a jóváhagyott gyártási tervből létrejövő konkrét gyártási munka.

```
## Forrás
```

- migráció: `database/migrations/2026_06_21_000021_create_production_orders_table.php`
- model: `app/Models/ProductionOrder.php`
- FormRequest: `app/Http/Requests/Admin/GenerateProductionOrdersRequest.php`
- Vue oldal: `resources/js/Pages/Admin/ProductionPlans/Index.vue`
- enum: `app/Enums/ProductionOrderStatus.php`

```
## Hol használjuk?
```

Ez az adat jelenleg nem közvetlenül felhasználói űrlapon jön létre.

```
A gyártási rendelést a gyártási tervből indított "gyártási rendelések generálása" művelet hozza létre.
```

## Űrlapon megadható mezők

```
A generáló művelethez nincs külön kitöltendő mező.
```

| Űrlap mező           | Adatbázis mező            | Kötelező     | Típus     | Minta érték                         | Megjegyzés                    |
| -------------------- | ------------------------- | ------------ | --------- | ----------------------------------- | ----------------------------- |
| nincs közvetlen mező | `production_plan_item_id` | rendszeradat | kapcsolat | `10 db Acél konzol tervtétel`       | A terv tételéből jön.         |
| nincs közvetlen mező | `customer_order_item_id`  | rendszeradat | kapcsolat | `10 db Acél konzol rendelési tétel` | A vevőrendelés tételéből jön. |
| nincs közvetlen mező | `item_id`                 | rendszeradat | kapcsolat | `PRD-1001`                          | A gyártandó cikk.             |
| nincs közvetlen mező | `bom_id`                  | rendszeradat | kapcsolat | `Acél konzol alap darabjegyzék`     | A tervtételről jön.           |
| nincs közvetlen mező | `operation_sequence_id`   | rendszeradat | kapcsolat | `Acél konzol alap műveletsor`       | A tervtételről jön.           |
| nincs közvetlen mező | `quantity`                | rendszeradat | szám      | `10`                                | A rendelési mennyiségből jön. |

```
## Minta rekord
```

| Mező              | Érték                           |
| ----------------- | ------------------------------- |
| Gyártási rendelés | `PO-2026-0001`                  |
| Cikk              | `PRD-1001 - Acél konzol`        |
| Mennyiség         | `10 db`                         |
| BOM               | `Acél konzol alap darabjegyzék` |
| Műveletsor        | `Acél konzol alap műveletsor`   |
| Státusz           | `planned`                       |

```
## Kapcsolódó adatok
```

Előtte kell jóváhagyott gyártási terv, tervtétel, BOM és műveletsor.

```
## Gyakori hibák
```

- BOM nélkül próbálsz gyártási rendelést generálni.
- Műveletsor nélkül próbálsz gyártási rendelést generálni.
- A terv még nincs jóváhagyva.

```
## A létrehozás után elérhető
```

Ezután már létrehozható vagy értelmezhető:

```
- item_instance
- production_task
- material_requirement
```

## Oktatási megjegyzés

```
A gyártási rendelés már nem terv, hanem végrehajtandó munka. Kezdőként ezt tekintsd a gyártás hivatalos indítópontjának.
```

## Knowledge Graph kapcsolatok

```
Kapcsolódó entitások:
```

-> Production Plan Item
-> BOM
-> Operation Sequence
-> Item Instance
-> Production Task

```
## Learning Center
```

Ez a fejezet a következő tananyagokban szerepel:

```
- Első gyártási rendelés (Tervezett)
```

## AI metaadat

````
```yaml
knowledge:
  entity: production_order
  zero_state: true
  user_creatable: false
  generated: true
  prerequisites:
    - production_plan_item
    - bom
    - operation_sequence
  unlocks:
    - item_instance
    - production_task
    - material_requirement
  learning_level: beginner
`

# 20. Anyagszükséglet
````

## Állapot

```
🔵 Automatikusan létrejövő adat
```

## Előfeltételek

```
Ehhez előbb létre kell hozni:
```

- customer_order_item
- bom_item
- stock_balance

```
## Mire szolgál?
```

Az anyagszükséglet megmutatja, mennyi alapanyag kell egy rendelés teljesítéséhez, mennyi érhető el, és mennyi hiányzik.

```
## Forrás
```

- migráció: `database/migrations/2026_06_21_000022_create_material_requirements_table.php`
- model: `app/Models/MaterialRequirement.php`
- FormRequest: nincs közvetlen létrehozó űrlap
- Vue oldal: `resources/js/Pages/Admin/Inventory/MaterialRequirements/Index.vue`
- enum: `app/Enums/MaterialRequirementStatus.php`

```
## Hol használjuk?
```

Admin felület -> Készlet -> Anyagszükségletek.

```
Ez az adat jelenleg nem közvetlenül felhasználói űrlapon jön létre.
```

## Űrlapon megadható mezők

```
Nincs közvetlen létrehozó űrlap. A képernyő szűrőket tartalmaz, nem adatfelviteli mezőket.
```

| Űrlap mező           | Adatbázis mező           | Kötelező     | Típus     | Minta érték         | Megjegyzés                   |
| -------------------- | ------------------------ | ------------ | --------- | ------------------- | ---------------------------- |
| nincs közvetlen mező | `customer_order_item_id` | rendszeradat | kapcsolat | `10 db Acél konzol` | Ebből számolódik.            |
| nincs közvetlen mező | `required_item_id`       | rendszeradat | kapcsolat | `MAT-0001`          | Szükséges alapanyag.         |
| nincs közvetlen mező | `required_quantity`      | rendszeradat | szám      | `10`                | BOM alapján 10 db termékhez. |
| nincs közvetlen mező | `available_quantity`     | rendszeradat | szám      | `10`                | Készlet alapján.             |
| nincs közvetlen mező | `reserved_quantity`      | rendszeradat | szám      | `10`                | Foglalás után.               |
| nincs közvetlen mező | `missing_quantity`       | rendszeradat | szám      | `0`                 | Hiányzó mennyiség.           |
| nincs közvetlen mező | `unit`                   | rendszeradat | szöveg    | `db`                | Egység.                      |
| nincs közvetlen mező | `status`                 | rendszeradat | enum      | `reserved`          | Állapot.                     |

```
## Minta rekord
```

| Mező                | Érték                       |
| ------------------- | --------------------------- |
| Vevőrendelés tétel  | `10 db Acél konzol`         |
| Szükséges cikk      | `MAT-0001 - 4 mm acéllemez` |
| Szükséges mennyiség | `10`                        |
| Elérhető mennyiség  | `10`                        |
| Foglalt mennyiség   | `10`                        |
| Hiányzó mennyiség   | `0`                         |
| Egység              | `db`                        |
| Státusz             | `reserved`                  |

```
## Kapcsolódó adatok
```

Előtte kell vevőrendelés tétel, BOM és készlet.

```
## Gyakori hibák
```

- A BOM hiányzik, ezért nincs miből számolni.
- Nincs készlet, ezért hiány keletkezik.
- A felhasználó kézzel keres létrehozó gombot, de ez listaoldal.

```
## A létrehozás után elérhető
```

Ezután már létrehozható vagy értelmezhető:

```
- purchase_requisition_item
- stock_reservation
```

## Oktatási megjegyzés

```
Az anyagszükséglet nem kézi bevitel, hanem visszajelzés. Azt tanítja meg, hogy a rendelés és a BOM milyen készlethatással jár.
```

## Knowledge Graph kapcsolatok

```
Kapcsolódó entitások:
```

-> Customer Order Item
-> BOM Item
-> Stock Balance
-> Purchase Requisition Item
-> Stock Reservation

```
## Learning Center
```

Ez a fejezet a következő tananyagokban szerepel:

```
- Készletkezelés alapjai (Tervezett)
- Beszerzés alapjai (Tervezett)
```

## AI metaadat

````
```yaml
knowledge:
  entity: material_requirement
  zero_state: true
  user_creatable: false
  generated: true
  prerequisites:
    - customer_order_item
    - bom_item
    - stock_balance
  unlocks:
    - purchase_requisition_item
    - stock_reservation
  learning_level: beginner
`

# 21. Készletegyenleg
````

## Állapot

```
🔵 Automatikusan létrejövő adat
```

## Előfeltételek

```
Ehhez előbb létre kell hozni:
```

- item
- location
- stock_movement

```
## Mire szolgál?
```

A készletegyenleg azt mutatja, mennyi van egy cikkből egy adott helyen.

```
## Forrás
```

- migráció: `database/migrations/2026_06_21_000023_create_stock_balances_table.php`
- model: `app/Models/StockBalance.php`
- FormRequest: nincs közvetlen létrehozó űrlap
- Vue oldal: `resources/js/Pages/Admin/Inventory/StockBalances/Index.vue`
- enum: nincs

```
## Hol használjuk?
```

Admin felület -> Készlet -> Készletegyenlegek.

```
Ez az adat jelenleg nem közvetlenül felhasználói űrlapon jön létre.
```

## Űrlapon megadható mezők

```
Nincs közvetlen adatfelviteli űrlap. A készletegyenleg készletmozgásokból frissül.
```

| Űrlap mező           | Adatbázis mező  | Kötelező     | Típus     | Minta érték | Megjegyzés          |
| -------------------- | --------------- | ------------ | --------- | ----------- | ------------------- |
| nincs közvetlen mező | `item_id`       | rendszeradat | kapcsolat | `MAT-0001`  | Cikk.               |
| nincs közvetlen mező | `location_id`   | rendszeradat | kapcsolat | `ALAP-R1`   | Hely.               |
| nincs közvetlen mező | `item_batch_id` | rendszeradat | kapcsolat | üres        | Köteg opcionális.   |
| nincs közvetlen mező | `quantity`      | rendszeradat | szám      | `10`        | Aktuális mennyiség. |

```
## Minta rekord
```

| Mező      | Érték                        |
| --------- | ---------------------------- |
| Cikk      | `MAT-0001 - 4 mm acéllemez`  |
| Hely      | `ALAP-R1 - Alapanyag raktár` |
| Tétel     | üres                         |
| Mennyiség | `10`                         |

```
## Kapcsolódó adatok
```

Előtte kell cikk, hely és olyan folyamat, amely készletmozgást hoz létre.

```
## Gyakori hibák
```

- Kézzel szeretnéd átírni a készletet, de erre nincs közvetlen űrlap.
- Rossz helyre rögzítesz beérkezést, ezért máshol látszik a készlet.
- Az egység eltér a cikk egységétől.

```
## A létrehozás után elérhető
```

Ezután már létrehozható vagy értelmezhető:

```
- material_requirement
- stock_reservation
- production_task_material
```

## Oktatási megjegyzés

```
A készletegyenleget ne kézi táblázatként kezeld. Ez a korábbi mozgások eredménye, ezért eltérés esetén mindig a mozgásokat keresd vissza.
```

## Knowledge Graph kapcsolatok

```
Kapcsolódó entitások:
```

-> Item
-> Location
-> Stock Movement
-> Material Requirement
-> Stock Reservation

```
## Learning Center
```

Ez a fejezet a következő tananyagokban szerepel:

```
- Készletkezelés alapjai (Tervezett)
```

## AI metaadat

````
```yaml
knowledge:
  entity: stock_balance
  zero_state: true
  user_creatable: false
  generated: true
  prerequisites:
    - item
    - location
    - stock_movement
  unlocks:
    - material_requirement
    - stock_reservation
    - production_task_material
  learning_level: beginner
`

# 22. Készletfoglalás
````

## Állapot

```
🔵 Automatikusan létrejövő adat
```

## Előfeltételek

```
Ehhez előbb létre kell hozni:
```

- stock_balance
- customer_order_item

```
## Mire szolgál?
```

A készletfoglalás elkülönít egy mennyiséget egy rendelés vagy gyártási rendelés számára.

```
## Forrás
```

- migráció: `database/migrations/2026_06_21_000024_create_stock_reservations_table.php`
- model: `app/Models/StockReservation.php`
- FormRequest: `app/Http/Requests/Admin/ReleaseStockReservationRequest.php`
- Vue oldal: `resources/js/Pages/Admin/Inventory/StockReservations/Index.vue`
- enum: `app/Enums/StockReservationStatus.php`

```
## Hol használjuk?
```

Admin felület -> Készlet -> Készletfoglalások.

```
Ez az adat jelenleg nem közvetlenül felhasználói űrlapon jön létre. A képernyőn a foglalás feloldása végezhető el.
```

## Űrlapon megadható mezők

```
Nincs közvetlen létrehozó űrlap. A feloldás művelet nem kér külön mezőt.
```

| Űrlap mező           | Adatbázis mező      | Kötelező     | Típus     | Minta érték | Megjegyzés         |
| -------------------- | ------------------- | ------------ | --------- | ----------- | ------------------ |
| nincs közvetlen mező | `item_id`           | rendszeradat | kapcsolat | `MAT-0001`  | Foglalt cikk.      |
| nincs közvetlen mező | `location_id`       | rendszeradat | kapcsolat | `ALAP-R1`   | Hely.              |
| nincs közvetlen mező | `reserved_quantity` | rendszeradat | szám      | `10`        | Foglalt mennyiség. |
| nincs közvetlen mező | `status`            | rendszeradat | enum      | `active`    | Foglalás állapota. |

```
## Minta rekord
```

| Mező              | Érték                       |
| ----------------- | --------------------------- |
| Cikk              | `MAT-0001 - 4 mm acéllemez` |
| Hely              | `ALAP-R1`                   |
| Foglalt mennyiség | `10`                        |
| Státusz           | `active`                    |

```
## Kapcsolódó adatok
```

Előtte kell készlet és vevőrendelés vagy gyártási rendelés.

```
## Gyakori hibák
```

- Nincs elég készlet, ezért nem tud foglalás létrejönni.
- Feloldott foglalást próbálsz újra feloldani.
- Nem azon a helyen van készlet, ahol keresed.

```
## A létrehozás után elérhető
```

Ezután már létrehozható vagy értelmezhető:

```
- production_task_material
- stock_movement
```

## Oktatási megjegyzés

```
A foglalás azt üzeni, hogy a készlet már valamihez tartozik. Kezdőként ezt úgy képzeld el, mint félretett anyagot.
```

## Knowledge Graph kapcsolatok

```
Kapcsolódó entitások:
```

-> Stock Balance
-> Customer Order Item
-> Production Order
-> Production Task Material
-> Stock Movement

```
## Learning Center
```

Ez a fejezet a következő tananyagokban szerepel:

```
- Készletkezelés alapjai (Tervezett)
- Első gyártási rendelés (Tervezett)
```

## AI metaadat

````
```yaml
knowledge:
  entity: stock_reservation
  zero_state: true
  user_creatable: false
  generated: true
  prerequisites:
    - stock_balance
    - customer_order_item
  unlocks:
    - production_task_material
    - stock_movement
  learning_level: beginner
`

# 23. Készletmozgás
````

## Állapot

```
🔵 Automatikusan létrejövő adat
```

## Előfeltételek

```
Ehhez előbb létre kell hozni:
```

- item
- location

```
## Mire szolgál?
```

A készletmozgás naplózza, hogy mikor, miből, mennyi mozgott be, ki vagy át.

```
## Forrás
```

- migráció: `database/migrations/2026_06_21_000025_create_stock_movements_table.php`
- model: `app/Models/StockMovement.php`
- FormRequest: nincs közvetlen létrehozó űrlap
- Vue oldal: `resources/js/Pages/Admin/Inventory/StockMovements/Index.vue`
- enum: `app/Enums/StockMovementType.php`

```
## Hol használjuk?
```

Admin felület -> Készlet -> Készletmozgások.

```
Ez az adat jelenleg nem közvetlenül felhasználói űrlapon jön létre. A képernyő lista és szűrő.
```

## Űrlapon megadható mezők

```
Nincs közvetlen adatfelviteli űrlap.
```

| Űrlap mező           | Adatbázis mező     | Kötelező     | Típus     | Minta érték        | Megjegyzés               |
| -------------------- | ------------------ | ------------ | --------- | ------------------ | ------------------------ |
| nincs közvetlen mező | `item_id`          | rendszeradat | kapcsolat | `MAT-0001`         | Mozgó cikk.              |
| nincs közvetlen mező | `from_location_id` | rendszeradat | kapcsolat | üres               | Beérkezésnél üres lehet. |
| nincs közvetlen mező | `to_location_id`   | rendszeradat | kapcsolat | `ALAP-R1`          | Célhely.                 |
| nincs közvetlen mező | `quantity`         | rendszeradat | szám      | `10`               | Mozgó mennyiség.         |
| nincs közvetlen mező | `movement_type`    | rendszeradat | enum      | `purchase_receive` | Mozgás típusa.           |
| nincs közvetlen mező | `performed_at`     | rendszeradat | időpont   | `2026-07-06 09:00` | Mozgás ideje.            |

```
## Minta rekord
```

| Mező      | Érték                       |
| --------- | --------------------------- |
| Cikk      | `MAT-0001 - 4 mm acéllemez` |
| Honnan    | üres                        |
| Hová      | `ALAP-R1`                   |
| Mennyiség | `10`                        |
| Típus     | `purchase_receive`          |
| Időpont   | `2026-07-06 09:00`          |

```
## Kapcsolódó adatok
```

Előtte kell cikk és hely. A mozgást például beérkezés vagy gyártási anyagfelhasználás hozza létre.

```
## Gyakori hibák
```

- Kézzel szeretnél készletmozgást létrehozni, de nincs ilyen felhasználói űrlap.
- Nem érted, miért változott a készlet: mindig a készletmozgás listában keresd az okot.
- Rossz mozgástípusra szűrsz.

```
## A létrehozás után elérhető
```

Ezután már létrehozható vagy értelmezhető:

```
- stock_balance
- traceability
```

## Oktatási megjegyzés

```
A készletmozgás a rendszer naplója. Ha valami nem érthető a készletben, itt érdemes keresni az események sorát.
```

## Knowledge Graph kapcsolatok

```
Kapcsolódó entitások:
```

-> Item
-> Location
-> Item Batch
-> Item Instance
-> Stock Balance

```
## Learning Center
```

Ez a fejezet a következő tananyagokban szerepel:

```
- Készletkezelés alapjai (Tervezett)
- Nyomon követés alapjai (Tervezett)
```

## AI metaadat

````
```yaml
knowledge:
  entity: stock_movement
  zero_state: true
  user_creatable: false
  generated: true
  prerequisites:
    - item
    - location
  unlocks:
    - stock_balance
    - traceability
  learning_level: beginner
`

# 24. Beszerzési igény
````

## Állapot

```
🟡 Később szükséges
```

## Előfeltételek

```
Ehhez előbb létre kell hozni:
```

- material_requirement

```
## Mire szolgál?
```

A beszerzési igény azt jelzi, milyen anyagot kell beszerezni.

```
## Forrás
```

- migráció: `database/migrations/2026_06_21_000026_create_purchase_requisitions_table.php`
- model: `app/Models/PurchaseRequisition.php`
- FormRequest: `app/Http/Requests/Admin/StorePurchaseRequisitionRequest.php`
- Vue oldal: `resources/js/Pages/Admin/PurchaseRequisitions/Index.vue`
- enum: `app/Enums/PurchaseRequisitionStatus.php`

```
## Hol használjuk?
```

Admin felület -> Beszerzési igények.

```
A jelenlegi listaképernyő fő művelete: generálás anyaghiányokból. A képernyőn nem látszik kézi soronkénti létrehozó űrlap.
```

## Űrlapon megadható mezők

```
A StoreRequest alapján a rendszer képes ilyen mezőket fogadni, de a jelenlegi Vue listaképernyő nem ad hozzá teljes kézi űrlapot.
```

| Űrlap mező   | Adatbázis mező       | Kötelező | Típus                    | Minta érték                               | Megjegyzés                                     |
| ------------ | -------------------- | -------- | ------------------------ | ----------------------------------------- | ---------------------------------------------- |
| Igényszám    | `requisition_number` | nem      | szöveg, max. 255, egyedi | `PR-2026-0001`                            | Ha üresen marad, a folyamat generálhat számot. |
| Igényelve    | `requested_at`       | nem      | dátum                    | `2026-07-06`                              | A kért dátum.                                  |
| Megjegyzések | `notes`              | nem      | hosszabb szöveg          | `Anyaghiány pótlása oktatási gyártáshoz.` | Belső megjegyzés.                              |

```
## Minta rekord
```

| Mező         | Érték                                     |
| ------------ | ----------------------------------------- |
| Igényszám    | `PR-2026-0001`                            |
| Igényelve    | `2026-07-06`                              |
| Megjegyzések | `Anyaghiány pótlása oktatási gyártáshoz.` |

```
## Kapcsolódó adatok
```

Előtte kell anyagszükséglet vagy kézzel összeállított igénytétel.

```
## Gyakori hibák
```

- Kézi létrehozó gombot keresel, de a képernyő generálásra épül.
- Duplikált igényszámot adnál meg.
- Nincs anyaghiány, ezért nincs miből generálni.

```
## A létrehozás után elérhető
```

Ezután már létrehozható vagy értelmezhető:

```
- purchase_requisition_item
- purchase_order
```

## Oktatási megjegyzés

```
A beszerzési igény a hiányból lesz feladat. Kezdőként figyeld meg, hogy nem a beszállítóval kezdődik, hanem a szükséglettel.
```

## Knowledge Graph kapcsolatok

```
Kapcsolódó entitások:
```

-> Material Requirement
-> Purchase Requisition Item
-> Purchase Order

```
## Learning Center
```

Ez a fejezet a következő tananyagokban szerepel:

```
- Beszerzés alapjai (Tervezett)
- Készletkezelés alapjai (Tervezett)
```

## AI metaadat

````
```yaml
knowledge:
  entity: purchase_requisition
  zero_state: true
  user_creatable: true
  generated: false
  prerequisites:
    - material_requirement
  unlocks:
    - purchase_requisition_item
    - purchase_order
  learning_level: beginner
`

# 25. Beszerzési igény tétel
````

## Állapot

```
🟡 Később szükséges
```

## Előfeltételek

```
Ehhez előbb létre kell hozni:
```

- purchase_requisition
- item
- material_requirement

```
## Mire szolgál?
```

A beszerzési igény tétel megmondja, melyik anyagból mennyit kell beszerezni.

```
## Forrás
```

- migráció: `database/migrations/2026_06_21_000027_create_purchase_requisition_items_table.php`
- model: `app/Models/PurchaseRequisitionItem.php`
- FormRequest: `app/Http/Requests/Admin/StorePurchaseRequisitionRequest.php`
- Vue oldal: `resources/js/Pages/Admin/PurchaseRequisitions/Index.vue`
- enum: `app/Enums/PurchaseRequisitionItemStatus.php`

```
## Hol használjuk?
```

Admin felület -> Beszerzési igények.

```
A jelenlegi képernyőn nincs külön kézi igénytétel-szerkesztő.
```

## Űrlapon megadható mezők

```
| Űrlap mező   | Adatbázis mező                  | Kötelező | Típus                | Minta érték                 | Megjegyzés                  |
| ------------ | ------------------------------- | -------- | -------------------- | --------------------------- | --------------------------- |
| Cikk         | `items.*.item_id` / `item_id`   | igen     | választólista        | `MAT-0001 - 4 mm acéllemez` | A beszerzendő anyag.        |
| Mennyiség    | `items.*.quantity` / `quantity` | igen     | szám, nagyobb mint 0 | `10`                        | Hiányzó mennyiség.          |
| Egység       | `items.*.unit` / `unit`         | igen     | szöveg, max. 50      | `db`                        | Egyezzen a cikk egységével. |
| Megjegyzések | `items.*.notes` / `notes`       | nem      | hosszabb szöveg      | `Acél konzol gyártáshoz.`   | Belső megjegyzés.           |
```

## Minta rekord

```
| Mező         | Érték                       |
| ------------ | --------------------------- |
| Cikk         | `MAT-0001 - 4 mm acéllemez` |
| Mennyiség    | `10`                        |
| Egység       | `db`                        |
| Megjegyzések | `Acél konzol gyártáshoz.`   |
```

## Kapcsolódó adatok

```
Előtte kell beszerzési igény és cikk.
```

## Gyakori hibák

```
- Nulla vagy negatív mennyiséget adnál meg.
- Hiányzik az egység.
- Olyan cikket választasz, ami nincs létrehozva.
```

## A létrehozás után elérhető

```
Ezután már létrehozható vagy értelmezhető:
```

- purchase_order_item

```
## Oktatási megjegyzés
```

Az igénytétel egy konkrét hiányt fordít le beszerzési sorra. Itt a cikk, mennyiség és egység hármasa a lényeg.

```
## Knowledge Graph kapcsolatok
```

Kapcsolódó entitások:

```
-> Purchase Requisition
-> Item
-> Material Requirement
-> Purchase Order Item
```

## Learning Center

```
Ez a fejezet a következő tananyagokban szerepel:
```

- Beszerzés alapjai (Tervezett)

```
## AI metaadat
```

```yaml
knowledge:
  entity: purchase_requisition_item
  zero_state: true
  user_creatable: true
  generated: false
  prerequisites:
    - purchase_requisition
    - item
    - material_requirement
  unlocks:
    - purchase_order_item
  learning_level: beginner
`

# 26. Beszerzési rendelés
```

## Állapot

```
🟡 Később szükséges
```

## Előfeltételek

```
Ehhez előbb létre kell hozni:
```

- supplier
- purchase_requisition

```
## Mire szolgál?
```

A beszerzési rendelés a beszállítónak küldött rendelést jelenti.

```
## Forrás
```

- migráció: `database/migrations/2026_06_21_000028_create_purchase_orders_table.php`
- model: `app/Models/PurchaseOrder.php`
- FormRequest: `app/Http/Requests/Admin/StorePurchaseOrderRequest.php`
- Vue oldal: `resources/js/Pages/Admin/PurchaseOrders/Index.vue`
- enum: `app/Enums/PurchaseOrderStatus.php`

```
## Hol használjuk?
```

Admin felület -> Beszerzési rendelések.

```
A jelenlegi listaképernyőn a létrehozás tiltott (`can-create=false`). Beszerzési rendelés jellemzően beszerzési igényből generálódik.
```

## Űrlapon megadható mezők

```
A StoreRequest alapján a rendszer képes ilyen mezőket fogadni, de a jelenlegi listaoldal nem ad kézi létrehozó űrlapot.
```

| Űrlap mező        | Adatbázis mező            | Kötelező | Típus                    | Minta érték           | Megjegyzés                              |
| ----------------- | ------------------------- | -------- | ------------------------ | --------------------- | --------------------------------------- |
| Rendelésszám      | `order_number`            | nem      | szöveg, max. 255, egyedi | `PO-SUP-2026-0001`    | Generált folyamatban automatikus lehet. |
| Beszállító        | `supplier_id`             | igen     | választólista            | `Acél Plusz Kft.`     | Előtte létre kell hozni.                |
| Beszerzési igény  | `purchase_requisition_id` | nem      | választólista            | `PR-2026-0001`        | Ha igényből készül, kapcsolódik hozzá.  |
| Megrendelve       | `ordered_at`              | nem      | dátum                    | `2026-07-06`          | Rendelés dátuma.                        |
| Várható szállítás | `expected_delivery_date`  | nem      | dátum                    | `2026-07-09`          | Várható beérkezés.                      |
| Megjegyzések      | `notes`                   | nem      | hosszabb szöveg          | `Oktatási beszerzés.` | Belső megjegyzés.                       |

```
## Minta rekord
```

| Mező              | Érték                 |
| ----------------- | --------------------- |
| Rendelésszám      | `PO-SUP-2026-0001`    |
| Beszállító        | `Acél Plusz Kft.`     |
| Beszerzési igény  | `PR-2026-0001`        |
| Megrendelve       | `2026-07-06`          |
| Várható szállítás | `2026-07-09`          |
| Megjegyzések      | `Oktatási beszerzés.` |

```
## Kapcsolódó adatok
```

Előtte kell beszállító. Igényből generálásnál kell jóváhagyott beszerzési igény.

```
## Gyakori hibák
```

- Beszállító nélkül próbálnád menteni.
- Duplikált rendelési számot használnál.
- A listaképernyőn kézi létrehozást keresel, de ott nincs ilyen gomb.

```
## A létrehozás után elérhető
```

Ezután már létrehozható vagy értelmezhető:

```
- purchase_order_item
- goods_receipt
```

## Oktatási megjegyzés

```
A beszerzési rendelés már külső partner felé mutat. Oktatásban érdemes hangsúlyozni, hogy ez más, mint a belső beszerzési igény.
```

## Knowledge Graph kapcsolatok

```
Kapcsolódó entitások:
```

-> Supplier
-> Purchase Requisition
-> Purchase Order Item
-> Goods Receipt

```
## Learning Center
```

Ez a fejezet a következő tananyagokban szerepel:

```
- Beszerzés alapjai (Tervezett)
- Készletkezelés alapjai (Tervezett)
```

## AI metaadat

````
```yaml
knowledge:
  entity: purchase_order
  zero_state: true
  user_creatable: true
  generated: false
  prerequisites:
    - supplier
    - purchase_requisition
  unlocks:
    - purchase_order_item
    - goods_receipt
  learning_level: beginner
`

# 27. Beszerzési rendelés tétel
````

## Állapot

```
🟡 Később szükséges
```

## Előfeltételek

```
Ehhez előbb létre kell hozni:
```

- purchase_order
- item

```
## Mire szolgál?
```

A beszerzési rendelés tétel megmondja, melyik cikkből mennyit rendelsz a beszállítótól.

```
## Forrás
```

- migráció: `database/migrations/2026_06_21_000029_create_purchase_order_items_table.php`
- model: `app/Models/PurchaseOrderItem.php`
- FormRequest: `app/Http/Requests/Admin/StorePurchaseOrderRequest.php`
- Vue oldal: `resources/js/Pages/Admin/PurchaseOrders/Index.vue`
- enum: `app/Enums/PurchaseOrderItemStatus.php`

```
## Hol használjuk?
```

Admin felület -> Beszerzési rendelések.

```
A jelenlegi listaoldalon nincs kézi tételszerkesztő.
```

## Űrlapon megadható mezők

```
| Űrlap mező             | Adatbázis mező                                                          | Kötelező | Típus                | Minta érték                  | Megjegyzés                      |
| ---------------------- | ----------------------------------------------------------------------- | -------- | -------------------- | ---------------------------- | ------------------------------- |
| Beszerzési igény tétel | `items.*.purchase_requisition_item_id` / `purchase_requisition_item_id` | nem      | választólista        | `MAT-0001 igénytétel`        | Igényből generálva kapcsolódik. |
| Cikk                   | `items.*.item_id` / `item_id`                                           | igen     | választólista        | `MAT-0001 - 4 mm acéllemez`  | Beszerzendő cikk.               |
| Megrendelt mennyiség   | `items.*.ordered_quantity` / `ordered_quantity`                         | igen     | szám, nagyobb mint 0 | `10`                         | Megrendelt mennyiség.           |
| Egység                 | `items.*.unit` / `unit`                                                 | igen     | szöveg, max. 50      | `db`                         | Egyezzen a cikk egységével.     |
| Megjegyzések           | `items.*.notes` / `notes`                                               | nem      | hosszabb szöveg      | `Oktatási beszerzési tétel.` | Belső megjegyzés.               |
```

## Minta rekord

```
| Mező                   | Érték                        |
| ---------------------- | ---------------------------- |
| Beszerzési igény tétel | `MAT-0001 igénytétel`        |
| Cikk                   | `MAT-0001 - 4 mm acéllemez`  |
| Megrendelt mennyiség   | `10`                         |
| Egység                 | `db`                         |
| Megjegyzések           | `Oktatási beszerzési tétel.` |
```

## Kapcsolódó adatok

```
Előtte kell beszerzési rendelés és cikk.
```

## Gyakori hibák

```
- Nulla mennyiséget adnál meg.
- Üres az egység.
- Olyan cikket választanál, amely nincs a rendszerben.
```

## A létrehozás után elérhető

```
Ezután már létrehozható vagy értelmezhető:
```

- goods_receipt_item

```
## Oktatási megjegyzés
```

A rendelési tétel a beszállító felé rendelt konkrét mennyiség. Kezdőként mindig hasonlítsd össze az igénytétellel.

```
## Knowledge Graph kapcsolatok
```

Kapcsolódó entitások:

```
-> Purchase Order
-> Item
-> Purchase Requisition Item
-> Goods Receipt Item
```

## Learning Center

```
Ez a fejezet a következő tananyagokban szerepel:
```

- Beszerzés alapjai (Tervezett)

```
## AI metaadat
```

```yaml
knowledge:
  entity: purchase_order_item
  zero_state: true
  user_creatable: true
  generated: false
  prerequisites:
    - purchase_order
    - item
  unlocks:
    - goods_receipt_item
  learning_level: beginner
`

# 28. Beérkezés
```

## Állapot

```
🟡 Később szükséges
```

## Előfeltételek

```
Ehhez előbb létre kell hozni:
```

- item
- location

```
## Mire szolgál?
```

A beérkezés rögzíti, hogy anyag érkezett be a raktárba.

```
## Forrás
```

- migráció: `database/migrations/2026_06_21_000030_create_goods_receipts_table.php`
- migráció: `database/migrations/2026_06_24_000001_add_status_to_goods_receipts_table.php`
- model: `app/Models/GoodsReceipt.php`
- FormRequest: `app/Http/Requests/Admin/StoreGoodsReceiptRequest.php`
- Vue oldal: `resources/js/Pages/Admin/GoodsReceipts/Index.vue`
- enum: `app/Enums/GoodsReceiptStatus.php`

```
## Hol használjuk?
```

Admin felület -> Beérkezések.

```
## Űrlapon megadható mezők
```

| Űrlap mező          | Adatbázis mező      | Kötelező | Típus           | Minta érték           | Megjegyzés                                                                                                           |
| ------------------- | ------------------- | -------- | --------------- | --------------------- | -------------------------------------------------------------------------------------------------------------------- |
| Beszerzési rendelés | `purchase_order_id` | nem      | választólista   | `PO-SUP-2026-0001`    | Beérkezhet rendeléshez kötve vagy anélkül.                                                                           |
| Megjegyzések        | `notes`             | nem      | hosszabb szöveg | `Oktatási beérkezés.` | A jelenlegi Vue űrlapban a fejléc megjegyzés mező elő van készítve, de a látható dialogban főként tételsorok vannak. |

```
A `receipt_number`, `received_at`, `received_by` és `status` nem közvetlenül kitöltött mező a jelenlegi dialogban.
```

## Minta rekord

```
| Mező                | Érték                 |
| ------------------- | --------------------- |
| Beszerzési rendelés | `PO-SUP-2026-0001`    |
| Megjegyzések        | `Oktatási beérkezés.` |
```

## Kapcsolódó adatok

```
Előtte kell cikk és hely. Beszerzési rendelés opcionális.
```

## Gyakori hibák

```
- Nem adsz meg egyetlen beérkezési tételt sem.
- Beszerzési rendelés nélkül keresel beszállítói kapcsolatot.
- A beérkezést létrehozod, de nem könyveled, ezért még piszkozat marad.
```

## A létrehozás után elérhető

```
Ezután már létrehozható vagy értelmezhető:
```

- goods_receipt_item
- stock_movement
- stock_balance

```
## Oktatási megjegyzés
```

A beérkezés az a pont, ahol az anyag láthatóvá válik a készletben. Kezdőknél itt érdemes külön figyelni a hely kiválasztására.

```
## Knowledge Graph kapcsolatok
```

Kapcsolódó entitások:

```
-> Purchase Order
-> Goods Receipt Item
-> Stock Movement
-> Stock Balance
```

## Learning Center

```
Ez a fejezet a következő tananyagokban szerepel:
```

- Készletkezelés alapjai (Tervezett)
- Beszerzés alapjai (Tervezett)

```
## AI metaadat
```

```yaml
knowledge:
  entity: goods_receipt
  zero_state: true
  user_creatable: true
  generated: false
  prerequisites:
    - item
    - location
  unlocks:
    - goods_receipt_item
    - stock_movement
    - stock_balance
  learning_level: beginner
`

# 29. Beérkezés tétel
```

## Állapot

```
🟡 Később szükséges
```

## Előfeltételek

```
Ehhez előbb létre kell hozni:
```

- goods_receipt
- item
- location

```
## Mire szolgál?
```

A beérkezés tétel mutatja meg, melyik cikkből mennyi érkezett és hova került.

```
## Forrás
```

- migráció: `database/migrations/2026_06_21_000031_create_goods_receipt_items_table.php`
- model: `app/Models/GoodsReceiptItem.php`
- FormRequest: `app/Http/Requests/Admin/StoreGoodsReceiptRequest.php`
- Vue oldal: `resources/js/Pages/Admin/GoodsReceipts/Index.vue`
- enum: nincs

```
## Hol használjuk?
```

Admin felület -> Beérkezések -> új beérkezés dialog tételsorai.

```
## Űrlapon megadható mezők
```

| Űrlap mező   | Adatbázis mező                        | Kötelező | Típus                | Minta érték                  | Megjegyzés                                                                                          |
| ------------ | ------------------------------------- | -------- | -------------------- | ---------------------------- | --------------------------------------------------------------------------------------------------- |
| Cikk         | `items.*.item_id` / `item_id`         | igen     | választólista        | `MAT-0001 - 4 mm acéllemez`  | Beérkező cikk.                                                                                      |
| Hely         | `items.*.location_id` / `location_id` | igen     | választólista        | `ALAP-R1 - Alapanyag raktár` | Ide kerül készletre.                                                                                |
| Mennyiség    | `items.*.quantity` / `quantity`       | igen     | szám, nagyobb mint 0 | `10`                         | Beérkező mennyiség.                                                                                 |
| Megjegyzések | `items.*.notes` / `notes`             | nem      | hosszabb szöveg      | `Oktatási beérkezési tétel.` | A jelenlegi tételsor UI-ban a mező adatként kezelt, de nem minden esetben látható külön oszlopként. |

```
## Minta rekord
```

| Mező         | Érték                        |
| ------------ | ---------------------------- |
| Cikk         | `MAT-0001 - 4 mm acéllemez`  |
| Hely         | `ALAP-R1 - Alapanyag raktár` |
| Mennyiség    | `10`                         |
| Megjegyzések | `Oktatási beérkezési tétel.` |

```
További beérkezési tételek:
```

| Cikk                          | Hely      | Mennyiség |
| ----------------------------- | --------- | --------- |
| `MAT-0100 - M8x20 csavar`     | `ALAP-R1` | `40`      |
| `MAT-0200 - Szürke porfesték` | `ALAP-R1` | `0.5`     |

```
## Kapcsolódó adatok
```

Előtte kell cikk és hely.

```
## Gyakori hibák
```

- Nem választasz helyet.
- Nulla vagy negatív mennyiséget írsz.
- Rossz helyre érkezteted az anyagot.

```
## A létrehozás után elérhető
```

Ezután már létrehozható vagy értelmezhető:

```
- stock_movement
- stock_balance
- item_batch
```

## Oktatási megjegyzés

```
A beérkezés tételnél dől el, hova kerül az anyag. Ha rossz helyre kerül, a későbbi gyártásnál hiánynak tűnhet.
```

## Knowledge Graph kapcsolatok

```
Kapcsolódó entitások:
```

-> Goods Receipt
-> Item
-> Location
-> Stock Movement
-> Stock Balance

```
## Learning Center
```

Ez a fejezet a következő tananyagokban szerepel:

```
- Készletkezelés alapjai (Tervezett)
```

## AI metaadat

````
```yaml
knowledge:
  entity: goods_receipt_item
  zero_state: true
  user_creatable: true
  generated: false
  prerequisites:
    - goods_receipt
    - item
    - location
  unlocks:
    - stock_movement
    - stock_balance
    - item_batch
  learning_level: beginner
`

# 30. Gyártási feladat
````

## Állapot

```
🟡 Később szükséges
```

## Előfeltételek

```
Ehhez előbb létre kell hozni:
```

- production_order
- item_instance
- operation_sequence_step
- employee

```
## Mire szolgál?
```

A gyártási feladat egy konkrét munkalépés egy konkrét gyártási rendeléshez és sorozatszámhoz.

```
## Forrás
```

- migráció: `database/migrations/2026_06_21_000032_create_production_tasks_table.php`
- model: `app/Models/ProductionTask.php`
- FormRequest: `app/Http/Requests/Admin/StoreProductionTaskRequest.php`
- generáló FormRequest: `app/Http/Requests/Admin/GenerateProductionTasksRequest.php`
- Vue oldal: `resources/js/Pages/Admin/ProductionTasks/Index.vue`
- enum: `app/Enums/ProductionTaskStatus.php`

```
## Hol használjuk?
```

Admin felület -> Gyártási feladatok.

```
A képernyő fő útja: gyártási feladatok generálása gyártási rendelésből.
```

## Űrlapon megadható mezők

```
A generáló dialog mezői:
```

| Űrlap mező           | Adatbázis mező        | Kötelező | Típus         | Minta érték    | Megjegyzés                      |
| -------------------- | --------------------- | -------- | ------------- | -------------- | ------------------------------- |
| Gyártási rendelés    | `production_order_id` | igen     | választólista | `PO-2026-0001` | Ebből készülnek a feladatok.    |
| Hozzárendelt dolgozó | `employee_id`         | igen     | választólista | `Kiss János`   | A generált feladatok dolgozója. |

```
A teljes StoreRequest további mezőket is ismer (`item_instance_id`, `operation_sequence_step_id`, `status`, `notes`), de ezek nem a generáló dialog kézi mezői.
```

## Minta rekord

```
| Mező                 | Érték          |
| -------------------- | -------------- |
| Gyártási rendelés    | `PO-2026-0001` |
| Hozzárendelt dolgozó | `Kiss János`   |
```

## Kapcsolódó adatok

```
Előtte kell gyártási rendelés, sorozatszám, műveletsor lépés és dolgozó.
```

## Gyakori hibák

```
- Gyártási rendelés nélkül próbálsz feladatot generálni.
- Nincs dolgozó kiválasztva.
- A gyártási rendeléshez nincs műveletsor.
```

## A létrehozás után elérhető

```
Ezután már létrehozható vagy értelmezhető:
```

- production_task_material
- quality_check
- stock_movement

```
## Oktatási megjegyzés
```

A gyártási feladat a dolgozó nézőpontja. Itt válik a terv napi munkává, ezért oktatásban ezt érdemes lassan, képernyőről képernyőre mutatni.

```
## Knowledge Graph kapcsolatok
```

Kapcsolódó entitások:

```
-> Production Order
-> Item Instance
-> Operation Sequence Step
-> Employee
-> Quality Check
```

## Learning Center

```
Ez a fejezet a következő tananyagokban szerepel:
```

- Első gyártási rendelés (Tervezett)
- Műhelymunka alapjai (Tervezett)

```
## AI metaadat
```

```yaml
knowledge:
  entity: production_task
  zero_state: true
  user_creatable: true
  generated: false
  prerequisites:
    - production_order
    - item_instance
    - operation_sequence_step
    - employee
  unlocks:
    - production_task_material
    - quality_check
    - stock_movement
  learning_level: beginner
`

# 31. Gyártási feladat anyag
```

## Állapot

```
🟡 Később szükséges
```

## Előfeltételek

```
Ehhez előbb létre kell hozni:
```

- production_task
- item
- location

```
## Mire szolgál?
```

Itt rögzíted, hogy egy gyártási feladat során mennyi anyagot használtak fel.

```
## Forrás
```

- migráció: `database/migrations/2026_06_21_000033_create_production_task_materials_table.php`
- model: `app/Models/ProductionTaskMaterial.php`
- FormRequest: `app/Http/Requests/Admin/StoreProductionTaskMaterialRequest.php`
- Vue komponens: `resources/js/Components/MaterialUsageForm.vue`
- enum: nincs

```
## Hol használjuk?
```

Admin felület -> Gyártási feladatok -> feladat megnyitása -> Anyagok.

```
## Űrlapon megadható mezők
```

| Űrlap mező            | Adatbázis mező     | Kötelező | Típus                | Minta érték                    | Megjegyzés                                               |
| --------------------- | ------------------ | -------- | -------------------- | ------------------------------ | -------------------------------------------------------- |
| Anyag                 | `item_id`          | igen     | választólista        | `MAT-0001 - 4 mm acéllemez`    | Felhasznált anyag.                                       |
| Készlethely           | `location_id`      | nem      | választólista        | `ALAP-R1 - Alapanyag raktár`   | Innen fogy az anyag, ha készletmozgás kapcsolódik hozzá. |
| Tervezett mennyiség   | `planned_quantity` | nem      | szám, minimum 0      | `10`                           | Tervezett mennyiség.                                     |
| Felhasznált mennyiség | `used_quantity`    | igen     | szám, nagyobb mint 0 | `10`                           | Tényleges felhasználás.                                  |
| Egység                | `unit`             | igen     | szöveg, max. 50      | `db`                           | A cikk kiválasztása után kitöltődhet.                    |
| Megjegyzések          | `notes`            | nem      | hosszabb szöveg      | `Felhasználva a darabolásnál.` | Belső megjegyzés.                                        |

```
A FormRequest ismeri az `item_batch_id` és `stock_reservation_id` mezőt is, de a jelenlegi komponens nem jelenít meg külön köteg- vagy foglalásválasztót.
```

## Minta rekord

```
| Mező                  | Érték                          |
| --------------------- | ------------------------------ |
| Anyag                 | `MAT-0001 - 4 mm acéllemez`    |
| Készlethely           | `ALAP-R1 - Alapanyag raktár`   |
| Tervezett mennyiség   | `10`                           |
| Felhasznált mennyiség | `10`                           |
| Egység                | `db`                           |
| Megjegyzések          | `Felhasználva a darabolásnál.` |
```

## Kapcsolódó adatok

```
Előtte kell gyártási feladat, cikk és lehetőség szerint készlet.
```

## Gyakori hibák

```
- Nulla felhasznált mennyiséget írsz be.
- Üresen marad az egység.
- Olyan helyet választasz, ahol nincs készlet.
```

## A létrehozás után elérhető

```
Ezután már létrehozható vagy értelmezhető:
```

- stock_movement
- stock_balance
- traceability

```
## Oktatási megjegyzés
```

Az anyagfelhasználásnál nem csak mennyiséget írsz be, hanem történetet rögzítesz: melyik feladathoz milyen anyag fogyott.

```
## Knowledge Graph kapcsolatok
```

Kapcsolódó entitások:

```
-> Production Task
-> Item
-> Location
-> Stock Movement
-> Stock Balance
```

## Learning Center

```
Ez a fejezet a következő tananyagokban szerepel:
```

- Első gyártási rendelés (Tervezett)
- Készletkezelés alapjai (Tervezett)

```
## AI metaadat
```

```yaml
knowledge:
  entity: production_task_material
  zero_state: true
  user_creatable: true
  generated: false
  prerequisites:
    - production_task
    - item
    - location
  unlocks:
    - stock_movement
    - stock_balance
    - traceability
  learning_level: beginner
`

# 32. Minőségellenőrzés
```

## Állapot

```
🟡 Később szükséges
```

## Előfeltételek

```
Ehhez előbb létre kell hozni:
```

- production_task
- employee

```
## Mire szolgál?
```

A minőségellenőrzés rögzíti, hogy egy gyártási feladat eredménye elfogadott, utómunkás vagy elutasított.

```
## Forrás
```

- migráció: `database/migrations/2026_06_21_000034_create_quality_checks_table.php`
- model: `app/Models/QualityCheck.php`
- FormRequest: `app/Http/Requests/Admin/StoreQualityCheckRequest.php`
- Vue komponens: `resources/js/Components/QualityCheckForm.vue`
- enum: `app/Enums/QualityCheckResult.php`

```
## Hol használjuk?
```

Admin felület -> Gyártási feladatok -> feladat megnyitása -> Minőségellenőrzések.

```
Az űrlap akkor jelenik meg, ha a feladat státusza `waiting_for_check`.
```

## Űrlapon megadható mezők

```
| Űrlap mező   | Adatbázis mező | Kötelező | Típus           | Minta érték                 | Megjegyzés                                                |
| ------------ | -------------- | -------- | --------------- | --------------------------- | --------------------------------------------------------- |
| Ellenőr      | `checked_by`   | igen     | választólista   | `Nagy Anna`                 | Dolgozó rekord kell hozzá.                                |
| Eredmény     | `result`       | igen     | enum            | `accepted`                  | Választható: elfogadott, utómunka szükséges, elutasított. |
| Megjegyzések | `notes`        | nem      | hosszabb szöveg | `Méret és felület rendben.` | Rövid ellenőrzési megjegyzés.                             |
```

A `production_task_id` és `checked_at` a feladatból és a művelet időpontjából jön.

```
## Minta rekord
```

| Mező         | Érték                       |
| ------------ | --------------------------- |
| Ellenőr      | `Nagy Anna`                 |
| Eredmény     | `accepted`                  |
| Megjegyzések | `Méret és felület rendben.` |

```
## Kapcsolódó adatok
```

Előtte kell gyártási feladat és ellenőr dolgozó.

```
## Gyakori hibák
```

- Nincs kiválasztva ellenőr.
- A feladat még nem vár ellenőrzésre, ezért nem jelenik meg az űrlap.
- Rossz eredményértéket vársz: csak az enum értékek használhatók.

```
## A létrehozás után elérhető
```

Ezután már létrehozható vagy értelmezhető:

```
- document
- item_instance_status
- customer_order_status
```

## Oktatási megjegyzés

```
A minőségellenőrzésnél a döntés mellé érdemes rövid, érthető megjegyzést írni. Ez később visszakereshető tudássá válik.
```

## Knowledge Graph kapcsolatok

```
Kapcsolódó entitások:
```

-> Production Task
-> Employee
-> Document
-> Item Instance

```
## Learning Center
```

Ez a fejezet a következő tananyagokban szerepel:

```
- Első gyártási rendelés (Tervezett)
- Minőségellenőrzés alapjai (Tervezett)
```

## AI metaadat

````
```yaml
knowledge:
  entity: quality_check
  zero_state: true
  user_creatable: true
  generated: false
  prerequisites:
    - production_task
    - employee
  unlocks:
    - document
    - item_instance_status
    - customer_order_status
  learning_level: beginner
`

# 33. Dokumentum
````

## Állapot

```
🟡 Később szükséges
```

## Előfeltételek

```
Ehhez előbb létre kell hozni:
```

- documentable_record

```
## Mire szolgál?
```

A dokumentum fájlt kapcsol egy rendszerbeli adathoz, például cikkhez, gyártási rendeléshez vagy minőségellenőrzéshez.

```
## Forrás
```

- migráció: `database/migrations/2026_06_22_000001_create_documents_table.php`
- migráció: `database/migrations/2026_06_25_000001_add_storage_metadata_to_documents_table.php`
- migráció: `database/migrations/2026_06_28_000001_add_ai_processing_fields_to_documents_table.php`
- model: `app/Models/Document.php`
- FormRequest: `app/Http/Requests/Admin/StoreDocumentRequest.php`
- Vue oldal: `resources/js/Pages/Admin/Documents/Index.vue`
- Vue komponens: `resources/js/Components/DocumentUploadForm.vue`
- enum: `app/Enums/DocumentType.php`, `app/Enums/DocumentProcessingStatus.php`

```
## Hol használjuk?
```

Admin felület -> Dokumentumok.

```
## Űrlapon megadható mezők
```

| Űrlap mező       | Adatbázis mező        | Kötelező | Típus                 | Minta érték                  | Megjegyzés                                                                                                        |
| ---------------- | --------------------- | -------- | --------------------- | ---------------------------- | ----------------------------------------------------------------------------------------------------------------- |
| Típus            | `document_type`       | igen     | enum                  | `drawing`                    | Dokumentumtípus.                                                                                                  |
| Kapcsolt entitás | `documentable_type`   | igen     | választólista         | `items`                      | A megengedett értékeket a DocumentableRegistry adja.                                                              |
| Entitás ID       | `documentable_id`     | igen     | egész szám, minimum 1 | `PRD-1001 cikk rekord ID-ja` | A képernyő konkrét ID-t kér.                                                                                      |
| Cím              | `title`               | nem      | szöveg, max. 255      | `Acél konzol rajz`           | Ha üres, a fájlnév segíthet azonosítani.                                                                          |
| Fájl             | fájlból képzett mezők | igen     | fájl, max. 51200 KB   | `acel-konzol-rajz.pdf`       | Feltöltendő fájl.                                                                                                 |
| Megjegyzések     | `notes`               | nem      | hosszabb szöveg       | `Oktatási rajzdokumentum.`   | A FormRequest `notes` mezőt fogad, az adatmodellben a leírás jellegű tartalom `description` mezőként jelenik meg. |

```
## Minta rekord
```

| Mező             | Érték                        |
| ---------------- | ---------------------------- |
| Típus            | `drawing`                    |
| Kapcsolt entitás | `items`                      |
| Entitás ID       | `PRD-1001 cikk rekord ID-ja` |
| Cím              | `Acél konzol rajz`           |
| Fájl             | `acel-konzol-rajz.pdf`       |
| Megjegyzések     | `Oktatási rajzdokumentum.`   |

```
## Kapcsolódó adatok
```

Előtte kell egy kapcsolható rekord, például cikk, gyártási rendelés vagy minőségellenőrzés.

```
## Gyakori hibák
```

- Nem választasz fájlt.
- Rossz kapcsolt entitás ID-t adsz meg.
- Túl nagy fájlt töltesz fel.

```
## A létrehozás után elérhető
```

Ezután már létrehozható vagy értelmezhető:

```
- knowledge_unit
- ai_context
- learning_center_content
```

## Oktatási megjegyzés

```
A dokumentum akkor hasznos, ha jó rekordhoz kapcsolod. Kezdőként először mindig keresd meg, mihez tartozik a fájl.
```

## Knowledge Graph kapcsolatok

```
Kapcsolódó entitások:
```

-> Item
-> Production Order
-> Quality Check
-> Knowledge Unit
-> AI Context

```
## Learning Center
```

Ez a fejezet a következő tananyagokban szerepel:

```
- Kezdő felhasználói útmutató
- Dokumentumkezelés alapjai (Tervezett)
- AI támogatott tudásbázis (Tervezett)
```

## AI metaadat

````
```yaml
knowledge:
  entity: document
  zero_state: true
  user_creatable: true
  generated: false
  prerequisites:
    - documentable_record
  unlocks:
    - knowledge_unit
    - ai_context
    - learning_center_content
  learning_level: beginner
`

# Kapcsolati sorrend az oktatási példához
````

1. Hozd létre a `Budapesti gyártóüzem` gyártási egységet.
2. Hozd létre az `ALAP-R1`, `MUHELY-1`, `ME-1`, `KESZ-R1` helyeket.
3. Hozd létre a szakmai szerepeket: `Gépkezelő`, `Minőségellenőr`.
4. Hozd létre a dolgozókat: `Kiss János`, `Nagy Anna`.
5. Hozd létre a vevőt: `Minta Gépipari Kft.`
6. Hozd létre a beszállítót: `Acél Plusz Kft.`
7. Hozd létre a cikkeket: `PRD-1001`, `MAT-0001`, `MAT-0100`, `MAT-0200`.
8. Hozd létre a művelettípusokat.
9. Hozd létre a BOM-ot és a BOM tételeket.
10. Hozd létre a műveletsort és a műveletsor lépéseket.
11. Hozd létre a vevőrendelést 10 db `PRD-1001` tétellel.
12. Hozd létre és szerkeszd a gyártási tervet.
13. Generáld a gyártási rendelést.
14. Generáld a gyártási feladatokat.
15. Rögzíts beérkezést vagy ellenőrizd a készletet.
16. Rögzíts anyagfelhasználást.
17. Rögzíts minőségellenőrzést.
18. Tölts fel dokumentumot, ha szükséges.

```
# Eltérések a korábbi sample-data szemlélethez képest
```

| Korábbi szemlélet                                          | Tényleges állapot                                                                                             |
| ---------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------- |
| Külön raktár entitásként szerepelt.                        | A rendszerben a raktár is `location`, `location_type = warehouse`.                                            |
| A tételköteg kézi törzsadatként jelent meg.                | Jelenleg nincs külön közvetlen tételköteg-létrehozó admin űrlap.                                              |
| A sorozatszám kézi adatként volt kezelhető.                | Jelenleg nincs közvetlen sorozatszám-létrehozó űrlap.                                                         |
| A készlet közvetlenül feltölthető törzsadatként szerepelt. | A készlet beérkezésből és készletmozgásokból alakul.                                                          |
| Beszerzési rendelés kézi létrehozása feltételezhető volt.  | A listaoldalon a kézi létrehozás tiltott, a folyamat generálásra épül.                                        |
| Gyártási rendelés kézi felvitele feltételezhető volt.      | A gyártási rendelés a gyártási tervből generálódik.                                                           |
| Dokumentum megjegyzés mezőként szerepelt.                  | A StoreRequest `notes` mezőt fogad, a dokumentum modellben a leíró tartalom `description` jellegű mezőben él. |

```
# Nyitott kérdések
```

- Kell-e külön felhasználói űrlap tételköteg létrehozására?
- A beérkezés űrlapján meg kell-e jeleníteni a köteg választását vagy létrehozását?
- A beszerzési igény StoreRequest kézi űrlaphoz készült-e, vagy csak jövőbeli API használatra maradt meg?
- A beszerzési rendeléshez kell-e később kézi létrehozó képernyő?
- A dokumentum `notes` mezője hogyan képeződik pontosan a dokumentum modell `description` mezőjére?
- A gyártási feladat kézi StoreRequest mezői használatban vannak-e, vagy a generálás a hivatalos felhasználói út?
- A készletkorrekcióhoz lesz-e később közvetlen űrlap?

```
# Dokumentációs lefedettség
```

| Entitás                   | Közvetlen felhasználói űrlap | Dokumentált mezők forrása                    |
| ------------------------- | ---------------------------- | -------------------------------------------- |
| Gyártási egység           | igen                         | migráció, StoreRequest, Vue oldal            |
| Hely                      | igen                         | migráció, StoreRequest, Vue oldal, enum      |
| Szakmai szerepkör         | igen                         | migráció, StoreRequest, Vue oldal            |
| Dolgozó                   | igen                         | migráció, StoreRequest, Vue oldal            |
| Vevő                      | igen                         | migráció, StoreRequest, Vue oldal            |
| Beszállító                | igen                         | migráció, StoreRequest, Vue oldal            |
| Cikk                      | igen                         | migráció, StoreRequest, Vue oldal, enum      |
| Tételköteg                | nem                          | migráció, model                              |
| Sorozatszám               | nem                          | migráció, model, enum                        |
| BOM                       | igen                         | migráció, StoreRequest, Vue oldal            |
| BOM tétel                 | igen, BOM-on belül           | migráció, StoreRequest, Vue komponens        |
| Művelettípus              | igen                         | migráció, StoreRequest, Vue oldal, enum      |
| Műveletsor                | igen                         | migráció, StoreRequest, Vue oldal            |
| Műveletsor lépés          | igen, műveletsoron belül     | migráció, StoreRequest, Vue komponens        |
| Vevőrendelés              | igen                         | migráció, StoreRequest, Vue komponens        |
| Vevőrendelés tétel        | igen, vevőrendelésen belül   | migráció, StoreRequest, Vue komponens        |
| Gyártási terv             | igen                         | migráció, StoreRequest, Vue komponens        |
| Gyártási terv tétel       | igen, terv szerkesztésekor   | migráció, UpdateRequest, Vue komponens       |
| Gyártási rendelés         | nem                          | migráció, generáló folyamat                  |
| Anyagszükséglet           | nem                          | migráció, listaoldal, enum                   |
| Készletegyenleg           | nem                          | migráció, listaoldal                         |
| Készletfoglalás           | nem                          | migráció, listaoldal, enum                   |
| Készletmozgás             | nem                          | migráció, listaoldal, enum                   |
| Beszerzési igény          | részben                      | migráció, StoreRequest, generáló oldal       |
| Beszerzési igény tétel    | részben                      | migráció, StoreRequest                       |
| Beszerzési rendelés       | részben                      | migráció, StoreRequest, generáló folyamat    |
| Beszerzési rendelés tétel | részben                      | migráció, StoreRequest                       |
| Beérkezés                 | igen                         | migráció, StoreRequest, Vue oldal            |
| Beérkezés tétel           | igen, beérkezésen belül      | migráció, StoreRequest, Vue oldal            |
| Gyártási feladat          | generáló dialog              | migráció, GenerateRequest, Vue oldal         |
| Gyártási feladat anyag    | igen, feladaton belül        | migráció, StoreRequest, Vue komponens        |
| Minőségellenőrzés         | igen, feladaton belül        | migráció, StoreRequest, Vue komponens, enum  |
| Dokumentum                | igen                         | migrációk, StoreRequest, Vue komponens, enum |

```
# Tudásarchitektúra
```

Ez a dokumentum a KM_Production tanulási és tudáskezelési rétegének egyik alapforrása. Nem alkalmazáskód, hanem emberi és AI számára is olvasható referencia.

````
```text
Business Ontology
    |
    v
Knowledge Graph
    |
    v
Knowledge Unit
    |
    v
Lesson
    |
    v
Course
    |
    v
Learning Path
    |
    v
Learning Center
````

```
A Business Ontology írja le, milyen üzleti fogalmak léteznek a rendszerben: cikk, vevőrendelés, gyártási rendelés, készletmozgás, dokumentum.
```

A Knowledge Graph ezek kapcsolatát mutatja meg. Például a vevőrendelésből gyártási terv lesz, a gyártási tervből gyártási rendelés, abból pedig gyártási feladat.

```
A Knowledge Unit egy kisebb tanulási egység. Ebben a dokumentumban egy entitásfejezet egy vagy több Knowledge Unit alapja lehet.
```

A Lesson egy vezetett lecke. Egy lecke például megtaníthatja, hogyan kell cikket, BOM-ot és műveletsort létrehozni az első gyártáshoz.

```
A Course több leckéből áll. A Kezdő felhasználói útmutató később ilyen Course formában épülhet fel.
```

A Learning Path több Course-t rendez tanulási útvonallá. Például külön út készülhet gyártásirányítóknak, raktárosoknak vagy minőségellenőröknek.

```
A Learning Center lesz az a felület, ahol ezek a tananyagok, interaktív leckék, ellenőrző listák, AI magyarázatok és későbbi videók megjelennek.
```

A jelenlegi dokumentum szerepe ebben a struktúrában: stabil, adatmodellhez kötött referencia. Ebből lehet később pontos leckét, ellenőrző kérdést, AI választ, onboarding lépést és Knowledge Graph kapcsolatot építeni.

```
# Verzió
```

- Verzió: v2.0
- Cél: űrlap- és adatmodell-alapú mintaadat referencia
- Utolsó frissítés: 2026-07-06

```

```
