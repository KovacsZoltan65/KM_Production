# Material Requirements Planning Architecture

## Status

Accepted

## Context

KM_Production már kezel Customer Ordert, Production Plan objektumot, verziózott BOM-mal
rendelkező Production Ordert, Material Requirementet, készletegyenleget,
reservationt, stock movementet, Purchase Requisitiont, Purchase Ordert és Goods
Receiptet. A jelenlegi `MaterialRequirementService` BOM-ból bruttó szükségletet
számol, levonja a készlet és aktív foglalások hatását, majd tárolja az aktuális
hiányt. A `PurchaseRequisitionService` item és unit szerint konszolidálhatja a
hiányokat, a `PurchaseRequisitionItemSource` pedig requirementenként megőrzi a
forrásmennyiséget.

Ez értékes alap, de nem teljes MRP architektúra. A jelenlegi Material
Requirement közvetlenül Customer Order Itemhez kötött, nincs szükségleti
időpontja vagy általános Demand-forrása, és készlet-/hiánypillanatképeket tárol.
A procurement recommendation az item-szinten összesített open PO mennyiséget
külön read modelben vonja le, nem time-phased és nincs requirement-szintű
incoming-supply pegging. Nincs perzisztált Supply Proposal, Item–Supplier
procurement source vagy többféle Supply Strategy modell.

Ha az MRP közvetlenül Purchase Orderből indulna, összekeverné a szükségletet,
javaslatot, jóváhagyást és végrehajtást. Ez gyengítené a traceabilityt,
megnehezítené a konszolidáció magyarázatát, és a `Purchase` stratégiához kötné a
jövőbeli transfer/manufacture lehetőségeket.

## Decision

KM_Production MRP architektúrája requirement-driven.

Az elsődleges folyamat:

```text
Demand
→ Material Requirement
→ availability and supply evaluation
→ Net Requirement / Shortage
→ Supply Proposal
→ decision / approval
→ Purchase Requisition
→ human approval
→ Purchase Order
→ Goods Receipt
→ Stock Movement / Result
```

A döntés kötelező részei:

1. A `Demand`, `Requirement`, `Supply Proposal`, approval, execution és result
   külön domain jelentésű.
2. A Material Requirement akkor is fennáll, ha teljesen fedezett; a Shortage
   számított eredmény, nem a requirement identitása.
3. Az MRP számítás nem közvetlenül Purchase Ordert hoz létre. Az MRP v1
   pénzügyi vagy supplier commitmentet csak emberi jóváhagyási pont után
   enged.
4. A Planning Engine logikai komponenscsalád, nem egyetlen mindenért felelős
   Laravel service. Számít, értékel, szimulál és javasol; az execution külön
   service-felelősség.
5. A shortage fedezése `SupplyStrategy` választás eredménye. MRP v1-ben csak
   `Purchase` implementálandó, de a modell extension pointként fenntartja a
   `Transfer`, `Manufacture`, `Subcontract` és `Consignment` stratégiát.
6. Supplier csak a Purchase stratégia értékelésekor jelenik meg. Automatikus
   supplier selection előtt Item–Supplier / Procurement Source kapcsolatot
   kell kialakítani.
7. A netting a használható készletet, reservation/allocation hatásokat,
   megfelelő bizonyosságú és időben alkalmazható incoming supplyt együtt
   értékeli. A pontos szabályokat külön ADR és specifikáció rögzíti.
8. A konszolidáció megengedett, de minden proposal-, requisition-, order- és
   result-mennyiség visszakövethető marad az eredeti Requirementhez és Demandhez.
9. A `required`, `planned`, `proposed`, `approved`, `ordered`, `promised`,
   `expected`, `received`, `accepted` és `rejected` adatok nem olvadnak egyetlen
   általános mennyiségbe, dátumba vagy státuszba.
10. A stock tényleges változásának Single Source of Truth-ja továbbra is a
    `StockMovement`; planning eredmény vagy expected supply nem módosít közvetlenül
    `StockBalance`-ot.

MRP v1 scope:

- Item Supplier / Procurement Source;
- BOM-alapú Material Requirement;
- használható stock, reservation és releváns open purchase figyelembevétele;
- Net Requirement / Shortage;
- Purchase Supply Strategy és Supply / Procurement Proposal;
- több Requirement konszolidációja mennyiségi pegginggel;
- Purchase Requisition generálása;
- emberi jóváhagyás és Purchase Order létrehozása;
- traceability az eredeti Demandtől a receipt/stock eredményig.

MRP v1-en kívül marad a Transfer, Manufacture, Subcontract, Consignment,
forecast-driven és safety-stock replenishment, advanced optimization,
automatikus supplier transmission és fully autonomous purchasing.

## Consequences

Pozitív:

- a beszerzési dokumentumok eredeti üzleti oka visszakövethető;
- a konszolidáció nem veszti el a mennyiségi pegginget;
- külön kezelhető terv, ígéret, várakozás és tény;
- a Purchase v1 mellett később új supply strategy alapvető újratervezés nélkül
  illeszthető;
- az automatikus számítás és az emberi kötelezettségvállalás határa egyértelmű.

Költség és kockázat:

- több explicit domain objektum és kapcsolat szükséges;
- a nettinghez idő-, bizonyossági, unit- és allokációs szabályokat kell
  véglegesíteni;
- a jelenlegi Material Requirement tábla és status modell későbbi, migrációval
  védett evolúciót igényel;
- a jelenlegi recommendation és requisition generation nem nevezhető teljes
  Supply Proposal workflow-nak;
- a supplier-, purchase- és receipt-adatok historizálási igénye növekszik.

Követő döntések:

- [`0007` Item Supplier / Procurement Source](0007-item-supplier-procurement-source.md) — elfogadva és implementálva;
- `0008` Supply Proposal;
- `0009` Material Requirement Netting;
- `0010` Requirement Pegging;
- `0011` Purchase Requisition Consolidation;
- `0012` Supplier Selection;
- `0013` Replenishment Strategies.

A sorszámok ajánlott következő helyek a jelenlegi, `0001`–`0006` folyamatos
konvencióban; csak ténylegesen elfogadott döntés létrehozásakor foglalhatók le.

## Alternatives Considered

- **Purchase-order-driven MRP.** Elutasítva, mert a végrehajtási dokumentumot
  tenné a szükséglet forrásává, megkerülné a proposal/approval határt, és a
  modellt Purchase stratégiához kötné.
- **Shortage = Purchase.** Elutasítva, mert ugyanaz a hiány később transferrel,
  gyártással, subcontracttal vagy consignmenttel is fedezhető.
- **Csak aktuális hiány tárolása Requirement nélkül.** Elutasítva, mert a
  shortage idővel változik, miközben az eredeti üzleti szükséglet és annak
  traceabilityje megmarad.
- **Az összes planning logika egyetlen service-ben.** Elutasítva, mert
  összekeverné a BOM explosion, stock availability, netting, supply strategy,
  supplier selection, proposal és execution felelősségét.
- **Azonnali autonóm PO-létrehozás.** Elutasítva az MRP v1-ben a pénzügyi,
  supplier-, jogosultsági és auditkockázat miatt. Később csak külön ADR alapján
  vizsgálható.

## Kapcsolódó dokumentumok

- [Domain Constitution](../steering/domain-constitution.md)
- [Planning Engine és MRP domain architektúra](../knowledge/planning-engine.md)
- [Domain terminológia](../knowledge/domain-terminology.md)
- [Stock Movements are the Single Source of Truth](0001-stock-movements.md)
- [Procurement knowledge](../knowledge/procurement.md)
- [Inventory knowledge](../knowledge/inventory.md)
