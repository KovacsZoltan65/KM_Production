# Item Supplier / Procurement Source

## Állapot

Elfogadva

## Kontextus és üzleti probléma

Ha egy Itemből hiány várható, a beszerzőnek tudnia kell, mely Supplierektől és
milyen feltételekkel szerezhető be. Ehhez nem elég külön nyilvántartani az
Itemeket és a Suppliereket, mert ugyanaz az Item több Suppliertől, eltérő
egységgel, minimális mennyiséggel, átfutási idővel vagy árral vásárolható meg.

A korábbi modell csak az `items` és `suppliers` törzsadatokat, valamint a már
végrehajtási szakaszban lévő Purchase Order Supplier-kapcsolatát tartalmazta.
Nem tárolt Item-specifikus beszállítói cikkszámot, beszerzési egységet, MOQ-t,
rendelési többszöröst, átfutási időt, referenciaárat, prioritást vagy jóváhagyási
állapotot.

Az Item és a Supplier közötti kapcsolat ezért önálló üzleti jelentéssel,
feltételekkel és életciklussal rendelkezik. Egy jelentés nélküli kapcsolótábla
vagy a Supplierre, illetve Itemre helyezett egyszerű idegen kulcs ezt nem tudná
helyesen kifejezni.

## Döntés és üzleti jelentés

Az Item–Supplier beszerzési kapcsolat önálló `ItemSupplier` üzleti entitás.
Elsődleges adatforrása az `item_suppliers` tábla.

Az `ItemSupplier` erre a kérdésre válaszol:

> Mely Suppliertől vásárolható meg ez az Item az aktuálisan érvényes beszerzési
> feltételek szerint?

Egy ItemSupplier rekord azt rögzíti, hogy az adott Item az adott Suppliertől a
megadott feltételekkel beszerezhető. Nem jelent Purchase Ordert,
Supplier-választási döntést, foglalást vagy beszerzési végrehajtást. Nem
bizonyítja, hogy a Supplier szállítani fog, és azt sem, hogy ez a forrás egy
konkrét Requirementet fedez.

## Rövid példa

Supplier A ItemSupplier kapcsolata jóváhagyott, de az érvényessége már lejárt.
Supplier B aktív. A hozzá tartozó ItemSupplier aktív, jóváhagyott és a mai
napon érvényes; az Item is aktív. Az aktuális alkalmazhatósági szabályok szerint
csak Supplier B használható beszerzési forrásként. Ez azonban még nem választja
ki automatikusan Supplier B-t, és nem hoz létre rendelést.

## Beszerzési forrás és szükséglet-visszakapcsolás

```text
ItemSupplier
→ honnan és milyen feltételekkel szerezhető be az Item?

PurchaseRequisitionItemSource
→ mely Material Requirement miatt és milyen mennyiséggel került a tétel a beszerzésbe?
```

Az ItemSupplier azt magyarázza meg, honnan és milyen feltételekkel vásárolható
meg az Item. A `PurchaseRequisitionItemSource` ezzel szemben azt őrzi, mely
Material Requirement miatt és milyen mennyiséggel került egy tétel a
beszerzésbe. A két kapcsolat nem helyettesíti és nem duplikálja egymást.

### Tárolt üzleti tények

- `item_id`, `supplier_id`;
- nullable `supplier_item_code`;
- `purchase_unit` és `conversion_factor`, ahol **1 beszerzési egység =
  conversion_factor × Item alapegység**;
- nullable `minimum_order_quantity` és `order_multiple` az Item alapegységében;
- nullable `lead_time_days`, amely az első verzióban naptári napot jelent;
- nullable `unit_price` és ISO 4217 formátumú `currency`;
- `priority`, ahol 1 a legmagasabb prioritás;
- egymástól független `is_preferred`, `is_approved`, `is_active`;
- nullable `valid_from`, `valid_until` üzleti érvényesség.

A `unit_price` aktuális vagy tervezési referenciaár, nem történeti tranzakciós
ár. A tényleges Purchase Order tételének később saját, megállapodott
ár-pillanatképet kell őriznie. A döntés időpontjában a Purchase Order modell még
nem tartalmazott árat; ez külön követő feladat volt. Az ItemSupplier módosítása
nem írhatja át egy végrehajtási dokumentum történeti jelentését. A teljes
árhistorika vagy az `item_supplier_prices` modell nem része ennek a döntésnek.

### Preferált, jóváhagyott és aktív állapot

- `is_preferred`: előnyben részesített forrás, de nem kizárólagos és nem
  automatikusan kiválasztott Supplier;
- `is_approved`: üzleti vagy minőségügyi jóváhagyás;
- `is_active`: a forrás használható az aktuális folyamatokban.

Az új forrás alapértelmezetten aktív, de nem jóváhagyott és nem preferált. Egy
Itemhez legfeljebb egy aktív preferált forrás tartozhat. Új preferált forrás
mentésekor a Service ugyanabban a tranzakcióban, az Item rekord zárolása után
kikapcsolja a többi aktív forrás `is_preferred` jelzőjét. Inaktív forrás nem
maradhat preferált.

### Identitás, érvényesség és életciklus

Az első verzió nem verziózza külön a feltételrekordokat, ezért az
`item_id + supplier_id` párt `unique` adatbázis-korlát védi. A `valid_from` és
`valid_until` az aktuális rekord feltételeinek időbeli alkalmazhatóságát jelöli.
A modell nem tart fenn egymást átfedő történeti feltételverziókat.

A felhasználói törlési művelet nem törli fizikailag a rekordot. Ehelyett
`is_active = false` és `is_preferred = false` értéket állít be. Így az audit és
a későbbi végrehajtási kapcsolatok történeti jelentése megmarad. A rekord
létrehozása, módosítása és inaktiválása aktivitásnapló-esemény.

### Komponens-felelősség

| Kérdés                            | Válasz                                                                                             |
| --------------------------------- | -------------------------------------------------------------------------------------------------- |
| Miért létezik?                    | Az Item lehetséges beszerzési forrását és feltételeit modellezi.                                   |
| Mi hozza létre és módosítja?      | Jogosult beszerzési felhasználó az `ItemSupplierService` tranzakcióin keresztül.                   |
| Mi inaktiválja?                   | A törlési műveletet kezelő Service-folyamat; a felületi CRUD nem végez fizikai törlést.            |
| Ki használja?                     | Beszerzési adminisztráció, később Supply Planning és Supplier Selection.                           |
| Mi az elsődleges adatforrás?      | Az `item_suppliers` rekord.                                                                        |
| Mi számított adat?                | Nincs tárolt MRP-eredmény; a későbbi rendelési kerekítés és rangsorolás számított adat.            |
| Mi tárolt üzleti tény?            | A Supplier-kapcsolat, egységátváltás, rendelési feltételek, státuszok és érvényesség.              |
| Mi nem a feladata?                | Nettó szükséglet, rendelési idő vagy mennyiség, Supplier-rangsor, pegging, PR vagy PO létrehozása. |
| Milyen nyomon követés szükséges?  | Létrehozási, módosítási és inaktiválási audit, majd végrehajtási pillanatkép; nem Demand-pegging.  |
| Milyen idődimenziói vannak?       | Feltételérvényesség és naptári napban értett átfutási idő.                                         |
| Milyen bizonytalanságot fejez ki? | Az aktív, jóváhagyott, preferált és érvényes állapot egyike sem Supplier-ígéret.                   |

## Következmények

Előnyök:

- az MRP későbbi ellátásértékelése strukturált beszerzési forrásból indul;
- Itemenként több Supplier és eltérő feltételek kezelhetők;
- a preferált forrás váltása következetes és auditálható;
- az aktív és a jóváhagyott állapot nem mosódik össze;
- a modell nem keveredik a Requirement pegginggel vagy a végrehajtással.

Korlátok és követő munka:

- nincs automatikus Supplier-rangsorolás;
- nincs MOQ-, rendelésitöbbszörös-számítás vagy egységátváltás;
- nincs árhistorika;
- nincs Supplier-naptár vagy munkanap-alapú átfutási idő;
- nincs Requirementhez allokált forrás;
- a Purchase Order történeti ár-pillanatképe későbbi döntést igényel.

## Elutasított alternatívák

- **Jelentés nélküli több-a-többhöz kapcsolótábla.** Elutasítva, mert a
  kapcsolat saját üzleti feltételekkel, jogosultsággal, audittal és
  életciklussal rendelkezik.
- **Supplier közvetlenül az Itemen.** Elutasítva, mert egy Itemnek több forrása
  lehet, a preferred pedig nem kizárólagos.
- **Több rekord supplierenként időszakonként.** Elhalasztva, mert teljes
  feltétel- és árhistorika még nincs specifikálva; az első verzió unique párt
  használ.
- **Adatbázis-specifikus részleges egyedi index a preferált jelzőre.** Az első
  verzióban a MySQL/SQLite hordozhatóság miatt elutasítva. A tranzakciós Service
  az Item sorának zárolásával tartja fenn a szabályt.
- **Fizikai törlés.** Elutasítva a történeti jelentés és az audit megőrzése
  miatt.

## Kapcsolódó dokumentumok

- [Domain Constitution](../steering/domain-constitution.md)
- [Planning Engine és MRP architektúra](../knowledge/planning-engine.md)
- [Domain terminológia](../knowledge/domain-terminology.md)
- [Material Requirements Planning Architecture](0006-material-requirements-planning-architecture.md)
