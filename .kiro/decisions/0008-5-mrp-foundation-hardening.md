# MRP Foundation Hardening: requirement identity and time semantics

- **Állapot:** Elfogadva és implementálva
- **Dátum:** 2026-08-13
- **Kapcsolódó döntések:** [0006 MRP Architecture](0006-material-requirements-planning-architecture.md), [0007 Item Supplier](0007-item-supplier-procurement-source.md), [0008 Supply Proposal](0008-supply-proposal.md)

## Kontextus és identity döntés

A Material Requirement korábbi technikai identitása a
`(customer_order_item_id, required_item_id)` pár volt. Ez nem egyedi üzleti
igény: ugyanaz a vevői rendeléstétel több Production Orderre bontható, és azonos
BOM-komponenst kérhet eltérő mennyiségben vagy időpontra.

Az aktuális gyártási demand közvetlen identitása ezért a
`(production_order_id, bom_item_id)` pár. A `customer_order_item_id` megmarad a
tágabb demand lineage, a `required_item_id` pedig a szükséges cikk explicit
kapcsolataként. Ugyanennek a párnak az újraszámítása idempotens; eltérő
Production Orderek nem írják felül egymást.

A lineage mezők nullable-k a migrációs kompatibilitás miatt. A migráció csak
akkor backfillel, ha egy legacy sorhoz pontosan egy Production Order/BOM Item
jelölt tartozik. Több jelöltnél nem talál ki történelmet: a sor unattributed
legacy rekord marad. Unique constraint egyelőre nincs, mert a soft-delete és a
későbbi lifecycle még nem bizonyítja a végleges adatbázis-korlátozást.

## `required_at` szemantika

`required_at` nap pontossággal azt a legközelebbi ismert időpontot tárolja,
amikorra a komponensnek rendelkezésre kell állnia. Mivel még nincs komponens-
vagy műveletszintű felhasználási idő, a determinisztikus precedencia:

1. Production Order `planned_start_date`;
2. Production Plan Item `planned_start_date`;
3. Production Plan `planned_start_date`;
4. Customer Order `requested_delivery_date`;
5. `null`, ha egyik tény sem ismert.

A delivery date fallback nem állít komponens-felhasználási időpontot; csak a
legközelebbi elérhető demand határidő.

## Requirement fact és planning snapshot

Authoritative requirement fact: `production_order_id`, `bom_item_id`,
`customer_order_item_id`, `required_item_id`, `required_quantity`, `unit` és
`required_at`. Az `available_quantity`, `reserved_quantity`, `missing_quantity`
és az ellátottsági `status` legacy/calculated planning snapshot. Kompatibilitási
és jelenlegi UI/reporting célból megmaradnak, de a következő netting nem
kezelheti a `missing_quantity` mezőt authoritative bemenetként.

## Supply Proposal és aktív törzsadatok

Új Item Supplier és Supply Proposal csak aktív Itemmel, Item Supplier csak
aktív Supplierrel hozható létre vagy módosítható. Supply Proposal approvalkor a
rendszer újra ellenőrzi az aktív Itemet és – ha Supplier ki van választva – az
aktív Supplierhez tartozó aktív, jóváhagyott, aktuálisan érvényes Item
Suppliert. A nullable Supplier szabály változatlan. A később inaktivált
történeti rekordok olvashatók és megmaradnak.

## Legacy Material Requirement → Purchase Requisition

`PurchaseRequisitionService::generateFromMaterialRequirements()`, a route,
controller és UI action működő kompatibilitási út marad, de **deprecation
candidate**. Új MRP kód nem építhet rá dependency-t.

A 0011 migrációs/removal terve:

1. vezesse be az approved Supply Proposalok explicit, mennyiségi PR
   consolidation inputját és idempotencia-kulcsát;
2. őrizze meg a Proposal → PR item source auditkapcsolatot;
3. terelje át az UI actiont és permissiont az új workflow-ra;
4. migrálja vagy zárja le a feldolgozatlan legacy source-okat;
5. regressziós időszak után távolítsa el a route-ot, controller actiont és
   `generateFromMaterialRequirements()` metódust;
6. csak ezután szüntesse meg a `missing_quantity`-től függő PR-generálást.

Ez a döntés nem implementál nettinget, pegginget, PR consolidationt vagy
automatikus Purchase Order generálást.
