# Purchase Order Generation / Execution Hardening

- **Állapot:** Elfogadva és implementálva
- **Dátum:** 2026-08-24
- **Kapcsolódó döntések:** [0011 Purchase Requisition Consolidation](0011-purchase-requisition-consolidation.md), [0012 Supplier Selection](0012-supplier-selection.md), [0013 Replenishment Strategies](0013-replenishment-strategies.md), [0014 Purchase Requisition Execution Readiness](0014-purchase-requisition-execution-readiness.md)

## Cél és execution trigger

A meglévő `POST /admin/purchase-requisitions/{purchaseRequisition}/generate-purchase-order`
és `PurchaseRequisitionService::generatePurchaseOrder()` flow egy teljes,
supplier-specifikus Approved Purchase Requisitionből hoz létre Purchase Ordert.
Nem készül második generátor és nincs részleges PR-fogyasztás.

```text
Approved PR
→ transaction + PR row lock
→ authoritative current-state reload
→ 0014 readiness evaluation
→ READY
→ Draft PO + execution snapshots
→ PR és PR Items Ordered
```

Az execution trigger jogosultságot és request-validációt igényel, de a korábbi
UI readiness eredmény nem authority. A 0014
`PurchaseRequisitionExecutionReadinessService` változtatás nélkül az egyetlen
readiness algoritmus.

## Tranzakció, lock és rollback

A PR row, a PR itemek, a proposal-lineage sorok, valamint a snapshothoz
felhasznált Supplier, Item és ItemSupplier sorok `lockForUpdate()` zárolása, a
readiness újraértékelése, a PO header és minden item létrehozása, az audit,
majd a PR és item státuszváltása egy adatbázis-tranzakcióban történik. NOT
READY vagy bármely írási/audit hiba az egész műveletet visszagörgeti; a PR
Approved marad.

A readiness az execution tranzakción belül és a PR lock után fut. A READY
result itemenkénti `item_supplier_id` értéke az execution source identity;
snapshot előtt ezeket a source rekordokat a repository authoritatively
újratölti. A service nem futtat eltérő eligibility algoritmust.

## Supplier authority

A PO `supplier_id` értéke kizárólag a zárolt PR `supplier_id` értékéből jön.
Supplierless vagy inaktív Supplierű PR-t a 0014 readiness blokkol. A legacy
`supplier_id` request mező átmenetileg optional compatibility input: ha jelen
van, egyeznie kell a PR Supplierével, de soha nem választ vagy ír felül
Suppliert. Az új UI nem küldi és nem jeleníti meg újraválasztható inputként.

A PO header `supplier_code_snapshot` és `supplier_name_snapshot` mezői őrzik a
létrehozáskori, auditálható Supplier-identitást. A relation továbbra is az
aktuális master data linkje.

## Quantity és unit authority

A PO item `ordered_quantity` értéke változtatás nélkül a
`PurchaseRequisitionItem.quantity`, vagyis a 0013 után replenishment-adjusted
requested quantity. Az érték Item base unitban marad, és a PO item meglévő
`unit` mezője ezt a base unitot snapshotolja. Nincs float számítás és nincs
purchase-unit quantity deriválás.

A `planned_quantity_snapshot` és `replenishment_excess_quantity_snapshot`
megőrzi a planning/requested eltérést. Például planned `10.000`, excess
`5.000`, requested és PO ordered `15.000`.

## Execution snapshot policy

Egy PO item a READY result által azonosított aktuális ItemSupplierből a
következő immutable execution adatokat őrzi:

| Adat           | Snapshot helye                                        | Döntés                                               |
| -------------- | ----------------------------------------------------- | ---------------------------------------------------- |
| Supplier       | PO `supplier_code_snapshot`, `supplier_name_snapshot` | Igen; header execution identity                      |
| ItemSupplier   | PO item `item_supplier_id`                            | Igen; source trace, nullable csak legacy rekordoknál |
| Item           | PO item `item_number_snapshot`, `item_name_snapshot`  | Igen; történeti tételazonosítás                      |
| Base unit      | meglévő PO item `unit`                                | Igen                                                 |
| Purchase unit  | `purchase_unit_snapshot`                              | Igen                                                 |
| Conversion     | `conversion_factor_snapshot`                          | Igen; 1 purchase unit = factor × base unit           |
| Unit price     | `unit_price_snapshot`                                 | Igen, nullable                                       |
| Currency       | `currency_snapshot`                                   | Igen, nullable és a price-zal együtt értelmezendő    |
| Lead time      | `lead_time_days_snapshot`                             | Igen, nullable                                       |
| MOQ            | `minimum_order_quantity_snapshot`                     | Igen, nullable                                       |
| Order multiple | `order_multiple_snapshot`                             | Igen, nullable                                       |

Az ItemSupplier `unit_price` a 0007 szerint referenciaár, nem megállapodott
tranzakciós ár. A snapshot ezért pontosan a létrehozáskori reference value;
nem nevezhető supplier-confirmed árnak. A repository nem definiál PO header
currencyt vagy FX contractot, ezért 0015 nem talál ki header pénznemet. A
nullable item currency az adott nullable price snapshot párja. Hiányzó price
vagy currency a 0014 szerint warning és nem rejtett 0015 blocker.

## Lineage és idegen kulcsok

A PO `purchase_requisition_id`, a PO item
`purchase_requisition_item_id` és `item_supplier_id` explicit trace-et ad:

```text
PO item → PR item → Proposal sources
       ↘ ItemSupplier execution source
```

Az új ItemSupplier execution-source FK történeti rekordot védő restrict
policyt használ. A meglévő PR és PR-item trace FK-k a projektkonform
`nullOnDelete` policyt tartják meg; a kapcsolódó modellek soft delete-je és az
immutable snapshotok együtt őrzik a történeti értelmezhetőséget. Az új
snapshot/source mezők nullable-k a korábbi PO-k hiteltelen backfilljének
elkerülésére; a hardened generator új rekordjai mindig kitöltik őket.

## Lifecycle, idempotency és concurrency

Az Approved PR-ből Draft PO készül, mert a meglévő PO-nak külön approval/
ordering lifecycle-ja van. Csak minden header/item/snapshot és audit sikeres
írása után lesz a PR és minden PR item `Ordered`.

V1-ben egy PR legfeljebb egy PO-t hozhat létre. Az application guard a PR lock
után előbb az explicit PO relationt, majd a lifecycle/readiness állapotot
ellenőrzi. A `purchase_orders.purchase_requisition_id` nullable unique DB
constraint a végső duplicate/concurrency guard, miközben a kézzel létrehozott,
PR nélküli PO-k továbbra is támogatottak. A második hívás explicit
`already generated` domain validation hibát ad.

A meglévő PO business-number formátum marad; 0015 nem vezet be új sequence
engine-t. A unique `order_number` constraint marad a végső ütközésvédelem, és
egy sikertelen tranzakció nem hagy PO rekordot.

## Audit és cache

A `purchase_order_generated` audit ugyanabban a tranzakcióban készül, metadata:
PO ID, PR ID, Supplier ID és item count. Különböző unitokat nem összegez. Audit
hiba rollbackel minden írást. Commit után a meglévő célzott procurement cache
invalidation fut; globális cache flush nincs.

## Readiness failure contract

NOT READY esetén a service `ValidationException`-t ad a strukturált 0014
blocker reason code-okból képzett, lokalizált `execution_readiness` hibákkal.
A UI megjeleníti ezeket, de a backend guard közvetlen POST esetén is kötelező.
Az 0014 warningok, köztük `PRICE_MISSING` és `EXPECTED_LATE_SUPPLY`, nem
blokkolják a generálást.

## Execution boundaries

A PO generation nem választ vagy cserél Suppliert, nem számol replenishmentet,
nem módosít Proposal/Requirement/netting/pegging adatot, nem küldi ki a PO-t,
és nem hoz létre Goods Receiptet, StockBalance-t, StockMovementet vagy
StockReservationt. Receiving és supplier acknowledgement külön későbbi
execution modul.
