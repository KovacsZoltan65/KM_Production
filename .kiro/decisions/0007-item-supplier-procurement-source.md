# Item Supplier / Procurement Source

## Status

Accepted

## Context

Az MRP v1-nek meg kell tudnia állapítani, hogy egy hiányzó Item mely
Supplierektől és milyen tervezési feltételekkel szerezhető be. A korábbi modell
csak önálló `items` és `suppliers` törzsadatot, illetve a már végrehajtási
szakaszban lévő Purchase Orderen supplier kapcsolatot tartalmazott. Nem volt
item-specifikus supplier cikkszám, purchase unit, MOQ, order multiple, lead
time, referenciaár, prioritás vagy approval információ.

Az Item–Supplier kapcsolat saját üzleti attribútumokkal és életciklussal
rendelkezik. Emiatt egy jelentés nélküli pivot vagy a Supplier/Item modellre
helyezett idegen kulcs nem tudná helyesen reprezentálni.

## Decision

Az Item–Supplier beszerzési kapcsolat önálló `ItemSupplier` domain entitás,
amelynek Single Source of Truth-ja az `item_suppliers` tábla.

Jelentése:

> Egy adott Item egy adott Suppliertől meghatározott beszerzési feltételekkel
> beszerezhető.

Nem jelenti azt, hogy rendelés történt, a supplier biztosan szállítani tud,
automatikusan őt kell választani, kizárólagos, vagy egy konkrét Requirementet
fedez.

### Procurement Source != Pegging

```text
ItemSupplier
→ honnan és milyen feltételekkel szerezhető be az Item?

PurchaseRequisitionItemSource
→ mely Material Requirement miatt és milyen mennyiséggel került a tétel a beszerzésbe?
```

A két kapcsolat nem helyettesíti és nem duplikálja egymást.

### Tárolt üzleti tények

- `item_id`, `supplier_id`;
- nullable `supplier_item_code`;
- `purchase_unit` és `conversion_factor`, ahol **1 purchase unit =
  conversion_factor × Item base unit**;
- nullable `minimum_order_quantity` és `order_multiple` Item base unitban;
- nullable `lead_time_days`, amely az első verzióban naptári nap;
- nullable `unit_price` és ISO 4217 formátumú `currency`;
- `priority`, ahol 1 a legmagasabb prioritás;
- egymástól független `is_preferred`, `is_approved`, `is_active`;
- nullable `valid_from`, `valid_until` üzleti érvényesség.

A `unit_price` aktuális vagy tervezési referenciaár, nem történelmi
tranzakciós ár. A tényleges Purchase Order tételnek a későbbiekben saját
megállapodott ár-pillanatképet kell őriznie; a jelenlegi Purchase Order modell
még nem tartalmaz árat, ez külön későbbi gap. ItemSupplier-változás nem írhatja
át egy végrehajtási dokumentum történeti jelentését. Teljes árhistorika vagy
`item_supplier_prices` modell nem része ennek a döntésnek.

### Preferred, approved és active

- `is_preferred` preferencia, nem kizárólagosság és nem automatikus döntés;
- `is_approved` üzleti/minőségügyi jóváhagyás;
- `is_active` azt jelzi, hogy a source használható az aktuális folyamatokban.

Új source alapértelmezetten active, de nem approved és nem preferred. Egy
Itemhez legfeljebb egy aktív preferred source maradhat. Új preferred source
mentésekor a service ugyanabban a tranzakcióban, az Item rekord zárolása után
visszaállítja a többi aktív preferred flaget. Inaktív source nem maradhat
preferred.

### Identitás, érvényesség és lifecycle

Az első verzió nem verziózza külön a feltételrekordokat, ezért az
`item_id + supplier_id` pár unique. A `valid_from` és `valid_until` az aktuális
rekord feltételeinek alkalmazhatóságát jelöli; nem tesz lehetővé átfedő
történeti verziókat.

A felhasználói törlési művelet hard delete helyett `is_active = false` és
`is_preferred = false` átállítást végez. Ez megőrzi az auditot és a jövőbeli
végrehajtási kapcsolatok értelmezhetőségét. A rekord létrehozása, módosítása és
inaktiválása activity log esemény.

### Komponens-felelősség

| Kérdés                               | Válasz                                                                                           |
| ------------------------------------ | ------------------------------------------------------------------------------------------------ |
| Miért létezik?                       | Az Item lehetséges beszerzési forrását és feltételeit modellezi.                                 |
| Mi hozza létre és módosítja?         | Jogosult procurement felhasználó a `ItemSupplierService` tranzakcióin keresztül.                 |
| Mi inaktiválja?                      | A delete műveletet kezelő service workflow; nincs hard delete a CRUD-ban.                        |
| Ki használja?                        | Procurement adminisztráció, később Supply Planning és Supplier Selection.                        |
| Mi a Single Source of Truth?         | Az `item_suppliers` rekord.                                                                      |
| Mi számított adat?                   | Nincs tárolt MRP-eredmény; a jövőbeli order rounding és ranking számított.                       |
| Mi tárolt üzleti tény?               | A supplier kapcsolat, unit conversion, rendelési feltételek, státuszok és érvényesség.           |
| Mi nem a feladata?                   | Net requirement, rendelési időpont/mennyiség, supplier ranking, pegging, PR vagy PO létrehozása. |
| Milyen traceability szükséges?       | Létrehozó/módosító/inaktiváló audit és későbbi execution snapshot; nem Demand-pegging.           |
| Milyen idődimenziói vannak?          | Feltételérvényesség és naptári napban értett lead time.                                          |
| Milyen bizonytalanságot reprezentál? | Active/approved/preferred és érvényesség; egyik sem supplier promise.                            |

## Consequences

Pozitív:

- az MRP későbbi supply evaluationje strukturált procurement source-ból indul;
- több supplier és eltérő feltételek kezelhetők Itemenként;
- preferred váltás konzisztens és auditálható;
- active és approved állapot nem mosódik össze;
- a modell nem keveredik a Requirement pegginggel vagy executionnel.

Korlátok és követő munka:

- nincs automatikus supplier ranking;
- nincs MOQ/order-multiple számítás vagy unit conversion végrehajtás;
- nincs price history;
- nincs supplier calendar vagy working-day lead time;
- nincs Requirementhez allokált source;
- a Purchase Order történeti ár-pillanatképe későbbi döntést igényel.

## Alternatives Considered

- **Jelentés nélküli many-to-many pivot.** Elutasítva, mert a kapcsolat saját
  üzleti feltételekkel, jogosultsággal, audittal és lifecycle-lal rendelkezik.
- **Supplier közvetlenül az Itemen.** Elutasítva, mert egy Itemnek több forrása
  lehet, a preferred pedig nem kizárólagos.
- **Több rekord supplierenként időszakonként.** Elhalasztva, mert teljes
  feltétel- és árhistorika még nincs specifikálva; az első verzió unique párt
  használ.
- **Adatbázis-specifikus partial unique index a preferred flagre.** Elutasítva
  az első verzióban a MySQL/SQLite hordozhatóság miatt; a tranzakciós service az
  Item sorának zárolásával tartja az invariánst.
- **Hard delete.** Elutasítva a történeti és auditjelentés megőrzése miatt.

## Kapcsolódó dokumentumok

- [Domain Constitution](../steering/domain-constitution.md)
- [Planning Engine és MRP architektúra](../knowledge/planning-engine.md)
- [Domain terminológia](../knowledge/domain-terminology.md)
- [Material Requirements Planning Architecture](0006-material-requirements-planning-architecture.md)
