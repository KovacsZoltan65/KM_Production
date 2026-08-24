# Purchase Requisition Execution Readiness

- **Állapot:** Elfogadva és implementálva
- **Dátum:** 2026-08-24
- **Kapcsolódó döntések:** [0011 Purchase Requisition Consolidation](0011-purchase-requisition-consolidation.md), [0012 Supplier Selection](0012-supplier-selection.md), [0013 Replenishment Strategies](0013-replenishment-strategies.md)

## Cél és jelentés

Az execution readiness aktuális, read-only domainértékelés arról, hogy egy
jóváhagyott Purchase Requisition biztonságosan továbbléphet-e procurement
executionbe. Nem azonos a business approvallal, és nem hoz létre Purchase
Ordert:

```text
Approved Purchase Requisition
→ current supplier/source/quantity revalidation
→ READY vagy NOT READY
→ [0015 Purchase Order Generation]
```

Az approval azt bizonyítja, hogy az üzleti igényt jóváhagyták. A readiness azt
bizonyítja, hogy az execution pillanatában a supplier, procurement source,
replenishment snapshot, quantity és lineage contract még érvényes. A 0015
külön execution use case marad.

## Reprezentáció és persistence

A V1 readiness tisztán számított, nem perzisztált result. Tartalmazza a PR
azonosítóját, az `is_ready` eredményt, determinisztikusan rendezett strukturált
blockereket és warningokat, az item-szintű eredményeket és a `checked_at`
időpontot. Nincs `is_ready` adatbázismező vagy tartósan érvényes readiness
snapshot, ezért nincs rejtett invalidation contract.

Az evaluation read operation: nem ír audit eseményt, nem módosít PR-t, PR
Itemet, Supply Proposalt, ItemSuppliert, lifecycle státuszt, quantityt, készletet
vagy execution dokumentumot. Stale replenishment esetén külön explicit
recalculation szükséges; a readiness nem hívja a replenishment service-t.

## Lifecycle prerequisite

Csak `PurchaseRequisitionStatus::Approved` lehet execution-ready. Draft és
Requested még approval előtti állapot, Ordered esetén execution már történt,
Cancelled esetén pedig tiltott. Minden más lifecycle állapot
`PR_NOT_APPROVED` blocker. Az approval nem futtat automatikusan teljes
readiness ellenőrzést, mert approval és execution külön döntési határ.

## Supplier, Item és procurement source

A PR header `supplier_id` mezője kötelező, a kapcsolódó Suppliernek aktívnak
kell lennie. Supplier nem választható vagy cserélhető automatikusan.

Minden PR Itemhez szükséges:

- létező és aktív Item;
- pozitív requested quantity;
- az Item aktuális base unitjával egyező PR unit;
- pontosan egy, a header Supplierhez tartozó execution source;
- aktív, approved és az evaluation aktuális üzleti napján érvényes
  ItemSupplier;
- aktív Supplier és Item;
- nem üres purchase unit és pozitív conversion factor.

Az effective date az evaluation aktuális üzleti napja, ugyanúgy, mint a
0012/0013 műveleteknél. A `required_at` és `proposed_supply_at` planning timing,
nem source-validity dátum. A repository egy bulk eligibility queryt használ;
itemenkénti N+1 lookup tilos. Nulla eligible source `ITEM_SUPPLIER_INVALID`,
több source `ITEM_SUPPLIER_AMBIGUOUS` blocker; a service nem választ `first()`
rekordot.

## Replenishment prerequisite és freshness

Execution előtt minden itemen bizonyíthatóan le kell futnia a 0013
replenishment calculationnek. A `planned_quantity`, `quantity`,
`replenishment_excess_quantity`, `replenishment_item_supplier_id`, strategy és
`replenishment_calculated_at` szükséges. Az exact stratégia is explicit
calculation eredmény; a null MOQ és order multiple nem azonos a hiányzó
számítással.

A V1 replenishment current, ha:

```text
current eligible ItemSupplier id == replenishment_item_supplier_id
current MOQ == stored MOQ snapshot
current order multiple == stored order-multiple snapshot
current derived strategy == stored strategy
planned quantity == current proposal lineage total, ha van Proposal lineage
```

Eltérés `REPLENISHMENT_STALE`; hiányzó metadata
`REPLENISHMENT_NOT_CALCULATED`. A conversion factor nincs a PR Itemen
snapshotolva, de a jelenlegi PO base-unit quantityt és base unitot használ,
ezért conversion-változás V1-ben nem önálló stale blocker. Az aktuális
conversion factornak ettől még pozitívnak kell lennie. A stabil historical
purchase-unit/conversion/price snapshot a 0015 PO Item felelőssége.

## Quantity és lineage invariánsok

Minden mennyiség exact integer-thousandths összehasonlítást használ:

```text
quantity > 0
quantity >= planned_quantity
planned_quantity + replenishment_excess_quantity == quantity
```

Proposal source-os itemnél ezen felül:

```text
sum(PurchaseRequisitionItemProposalSource.quantity) == planned_quantity
```

A Proposal source total nem a replenishment-adjusted requested quantityhez
igazodik. A readiness sérült invariantet jelez, de nem javít adatot.

## Blocker és warning policy

Blocker minden olyan feltétel, amely mellett execution nem kezdhető meg:
lifecycle-, supplier-, item-, source-, replenishment-, quantity- vagy lineage
hiba. A PR akkor ready, ha nincs PR- vagy item-szintű blocker.

Warning nem állítja `is_ready` értékét false-ra:

- hiányzó referenciaár vagy currency, mert a jelenlegi PO Item ár nélkül is
  létrehozható;
- a lead time alapján a `required_at` dátumig már nem várható beérkezés;
- hiányzó vagy múltbeli required date.

A late supply jelenleg planning risk, nem executiontilalom. A service reason
code-okat és paramétereket ad vissza, nem lokalizált szöveget. A reason ordering
enum-prioritás alapján determinisztikus, a felesleges duplikáció tiltott.

## Dátum, ár és snapshot határ

Ha `required_at` nincs, `REQUIRED_DATE_MISSING` warning keletkezik. Múltbeli
required date `REQUIRED_DATE_PASSED`; ha az aktuális üzleti nap és a source
`lead_time_days` összege későbbi a required date-nél, vagy a
`proposed_supply_at` későbbi annál, `EXPECTED_LATE_SUPPLY` warning keletkezik.
Új scheduling engine nem készül.

Hiányzó ItemSupplier `unit_price` vagy `currency` `PRICE_MISSING` warning. Ár
nem blocker, mert a jelenlegi PurchaseOrderItem contract nem tárol árat. A 0014
nem hoz létre PO snapshotmezőket.

## Concurrency és 0015 határ

A readiness result az evaluation pillanatképe, és nem foglal le semmit. A 0015
nem bízhat korábbi UI-resultban: a PO generation saját adatbázis-tranzakcióján
belül, a PR sor zárolása után kötelező újraértékelnie ugyanazt a readiness
contractot. Csak az így kapott READY eredmény után hozhat létre PO-t és
historical source/price/unit snapshotot.

A meglévő `PurchaseRequisitionService::generatePurchaseOrder()` útvonal csak
Approved státuszt és supplier mismatch-et ellenőriz. Ez
`LEGACY / NOT EXECUTION-READY GUARDED`; hardeningje és a readiness tranzakciós
integrációja a 0015 scope-ja. A 0014 csak a meglévő UI actiont tiltja le, ha az
aktuális detail-page readiness NOT READY.

## Határok és következmények

A 0014 nem generál vagy módosít Purchase Ordert, Goods Receiptet,
StockMovementet, StockReservationt vagy készletet. Nem módosít 0009 nettinget,
0010 pegginget, Supply Proposal quantityt/lineage-et, PR quantityt, Suppliert
vagy lifecycle állapotot. Nem perzisztál forever-ready flaget és nem auditálja
a tiszta olvasást.

V1 csak a PR detail oldalon számít readiness eredményt. A listanézet nem kap
soronkénti evaluationt, így nincs list-wide N+1 query vagy readiness explosion.
