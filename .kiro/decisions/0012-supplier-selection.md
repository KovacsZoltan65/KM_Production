# Supplier Selection

- **Állapot:** Elfogadva és implementálva
- **Dátum:** 2026-08-15
- **Kapcsolódó döntések:** [0007 Item Supplier](0007-item-supplier-procurement-source.md), [0008 Supply Proposal](0008-supply-proposal.md), [0011 Purchase Requisition Consolidation](0011-purchase-requisition-consolidation.md)

## Cél és authoritative forrás

A Supplier Selection explicit procurement döntés arról, hogy egy supplierless
Draft Purchase Requisition minden tételét melyik közös Supplier láthatja el.
Candidate kizárólag authoritative `ItemSupplier` procurement source-ból
származhat. A candidate lista számított, nem perzisztált read result; a választás
tárolt üzleti ténye a `PurchaseRequisition.supplier_id` és annak activity logja.

```text
supplierless Draft Purchase Requisition
→ bulk ItemSupplier eligibility
→ common Supplier intersection
→ explicit user selection
→ supplier-resolved Draft Purchase Requisition
```

## Selection granularity és multi-item PR

V1-ben a selection granularity a **Purchase Requisition header**. Ez illeszkedik
a jelenlegi PR és Purchase Order supplier-header contractjához. Item-szintű
supplier nem kerül a PR-be, mert az egyetlen header Supplierrel ellentmondó
állapotot hozna létre, a későbbi PO split pedig még nincs specifikálva.

Multi-item PR candidate-jeinek halmaza az egyes Itemek eligible Supplier
halmazainak metszete. Egy Supplier csak akkor választható, ha a PR minden egyedi
Itemjéhez pontosan létező, eligible ItemSupplier source tartozik. A bulk query
egyszer tölti be az ItemSupplier, Supplier és Item adatokat; nincs itemenkénti
N+1 query.

Ha nincs közös Supplier, V1-ben a selection explicit `NO_ELIGIBLE_SUPPLIER`
hibával blokkol. Nem választ részlegesen és nem mutálja vagy bontja automatikusan
a PR-t. Az automatikus PR split külön ADR-t igényelne az eredeti dokumentum
lifecycle-járól, a Proposal lineage mozgatásáról, kódgenerálásról és auditról.
A supplierless, nem feloldható multi-item PR operatív újracsoportosítása ezért
nyitott követő policy.

## Eligibility és effective date

A selection effective date a művelet végrehajtásának aktuális üzleti napja
(`today`, alkalmazás timezone). Nem a PR `required_at` vagy
`proposed_supply_at` dátuma: ezek demand/supply timing tények, nem a source
feltételének kiválasztási időpontjai.

Egy source csak akkor eligible, ha:

- az ItemSupplier aktív és approved;
- `valid_from` null vagy nem későbbi a selection napjánál;
- `valid_until` null vagy nem korábbi a selection napjánál;
- a kapcsolódó Supplier aktív;
- a kapcsolódó Item aktív.

A candidate read és a commit ugyanazt a központi repository query contractot
használja. A commit a PR row lockja után újra lekérdezi az eligibility-t; a
frontend candidate snapshot soha nem authoritative.

## Candidate contract és ordering

A nem perzisztált candidate tartalmazza a Supplier azonosítóját, kódját és
nevét, valamint minden PR Itemhez a hozzá tartozó source szükséges adatait:
preferred, priority, lead time, referenciaár és currency, purchase unit,
conversion factor, MOQ, order multiple és validity.

V1-ben **nincs automatikus ranking vagy selection**. A candidate-ek kizárólag
determinista megjelenítési sorrendet kapnak:

```text
supplier name ASC
supplier id ASC
```

Az item-szintű `is_preferred` és `priority` (`1` a legjobb) magyarázó adat,
nem aggregált score. A preferred nem kötelező. A referenciaár nem használható
„cheapest wins” döntésre, mert source-onként eltérő purchase unit/currency lehet,
és nincs stabil történeti vagy egységes összehasonlítási contract. A lead time
szintén csak információ; latest-order-date vagy feasibility számítás a 0012-n
kívül marad. A Suppliert mindig jogosult felhasználó választja explicit módon.

## Lifecycle, supplierless és meglévő Supplier

Supplier csak Draft PR-en választható. A választás nem módosítja a PR vagy a PR
Item státuszát, és nem approve-olja a dokumentumot.

- Supplierless Draft PR: a közös candidate-ek egyike explicit kiválasztható.
- Már supplieres PR + azonos Supplier: eligibility revalidation után idempotens
  no-op, új audit event nélkül.
- Már supplieres PR + más Supplier: explicit conflict; silent replacement tilos.
- Nem Draft PR: selection tiltott.
- Approved Supply Proposal Supplierét a 0012 nem módosítja; a 0008 approval
  integritása megmarad.

## Tranzakció, concurrency és audit

A selection egy adatbázis-tranzakcióban:

1. `lockForUpdate()` zárolja a PR sort;
2. újraellenőrzi a Draft lifecycle-t és a jelenlegi Suppliert;
3. bulk queryvel újraellenőrzi az aktív Itemeket és a teljes common eligibility-t;
4. beállítja a `supplier_id` értéket;
5. egy `supplier_selected` activity eseményt ír.

Az event subjectje a PR, actora a kiválasztó user, metadata:
`purchase_requisition_id`, `supplier_id`, `selection_mode = manual` és
`candidate_count`. A row lock tiltja a silent last-write-wins viselkedést.
Az azonos Supplier ismétlése idempotens és nem hoz létre audit zajt; eltérő
Supplier versenyző kérése a lock utáni revalidationnél elbukik.

## Quantity és execution határok

A selection nem módosít PR Item quantityt, unitot vagy Proposal lineage-et. Az
MOQ, order multiple, conversion factor és purchase unit candidate információ,
de nincs alkalmazva, konvertálva vagy kerekítve. Ezek, valamint safety stock és
replenishment quantity a 0013 Replenishment Strategies döntési határai.

A 0012 nem approve-ol PR-t, nem generál Purchase Ordert vagy Goods Receiptet,
és nem módosít StockBalance-t, StockMovementet vagy StockReservationt. A
későbbi PO-generationnek a kiválasztott Suppliert és ItemSupplier source-okat
újra kell validálnia; a selection időpontbeli döntés, nem örök garancia.

## Recalculation és reselection

A candidate lista minden olvasáskor az aktuális effective date és törzsadatok
alapján újraszámított read result. Nincs candidate cache vagy külön
`SupplierSelection` modell. A választott Supplier nem cserélhető automatikusan;
reselection külön, auditálható lifecycle döntést igényelne, ezért V1-ben tiltott.

## Következmények és nyitott kérdések

- A PR-header és a későbbi PO-header supplier contract konzisztens marad.
- A common intersection bizonyítja a multi-item kompatibilitást.
- A no-common-supplier PR nem oldható fel automatikusan; a split/reconsolidation
  lifecycle későbbi döntést igényel.
- A 0013-nak explicit módon el kell döntenie, hogy MOQ/order multiple miatt a
  Draft PR Item, egy új replenishment artifact vagy a későbbi PO Item mennyisége
  változhat-e. A 0012 ezt nem előlegezi meg.
- Ár- és lead-time-alapú ranking csak stabil currency, unit normalization,
  feasibility és policy contract után vezethető be.

## Elutasított alternatívák

- **Supply Proposal módosítása:** sértené az Approved Proposal immutable döntési
  tartalmát, és a már létrejött PR lineage után túl korai artifactot írna át.
- **PR Item supplier mező:** a jelenlegi supplier-headeres PR/PO modellel
  ellentmondó állapotot hozna létre split contract nélkül.
- **Automatikus preferred/priority/price selection:** dokumentálatlan üzleti
  döntést hozna, és a mezők nem alkotnak egységes score contractot.
- **Automatikus PR split:** a lineage, original document lifecycle és audit
  szabályai még nincsenek specifikálva.
- **Külön SupplierSelection tábla:** V1-ben duplikálná a természetes PR supplier
  üzleti tényt indokolt planning lifecycle nélkül.
