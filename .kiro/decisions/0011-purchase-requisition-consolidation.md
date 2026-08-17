# Purchase Requisition Consolidation

- **Állapot:** Elfogadva és implementálva
- **Dátum:** 2026-08-14
- **Kapcsolódó döntések:** [0006 MRP Architecture](0006-material-requirements-planning-architecture.md), [0007 Item Supplier](0007-item-supplier-procurement-source.md), [0008 Supply Proposal](0008-supply-proposal.md), [0008.5 MRP Foundation Hardening](0008-5-mrp-foundation-hardening.md), [0009 Netting](0009-material-requirement-netting.md), [0010 Pegging](0010-requirement-pegging.md)

## Cél és kontextus

A konszolidáció explicit execution use case: jóváhagyott beszerzési javaslatokat
determinista csoportokba rendez, és minden csoportból belső beszerzési igényt
hoz létre. Nem számít újra nettó szükségletet, nem épít pegginget, nem választ
beszállítót, és nem hoz létre Purchase Ordert vagy készletváltozást.

```text
Approved Purchase Supply Proposal
→ PurchaseRequisitionConsolidationService
→ deterministic grouping
→ Draft Purchase Requisition
→ Purchase Requisition Item
→ Proposal source trace
```

Az authoritative bemenet a `SupplyProposal.proposed_quantity` az Item base-unit
snapshotjával. A legacy `MaterialRequirement.missing_quantity` nem bemenet.

## Döntés

### Eligible input és revalidation

Csak `status = approved` és `strategy = purchase` Proposal eligible. A user V1-ben
explicit Proposal ID-kat választ. A service tranzakción belül, `lockForUpdate()`
után újra ellenőrzi:

- a státuszt és stratégiát;
- a pozitív, három tizedes pontosságú mennyiséget;
- az Item létezését, aktív állapotát és a Proposal unitjának egyezését az Item
  aktuális base unitjával;
- hogy a Proposalnak még nincs downstream source kapcsolata;
- explicit Supplier esetén a Supplier aktív, és az Item–Supplier source a 0007
  szerint aktív, jóváhagyott és aktuálisan érvényes.

Draft, Proposed, Rejected és Cancelled Proposal nem konszolidálható. Explicit
kiválasztásnál bármely invalid vagy már felhasznált ID domain validation hibával
az egész batch rollbackjét okozza; nincs részleges skip.

### Determinisztikus grouping és dátum

A PR header grouping key:

```text
strategy
+ supplier_id (nullable)
+ required_at (nullable, nap pontosság)
+ proposed_supply_at (nullable, nap pontosság)
```

A kiválasztott Proposalokat `id ASC` sorrendben zároljuk, a csoportokat a fenti
kulcs szerint lexikografikusan rendezzük. Mivel a V1 csak Purchase stratégiát
támogat, a strategy a határ explicit része, de nem kerül külön PR mezőbe.

A PR `required_at` és `proposed_supply_at` headere a csoport azonos jelentésű
dátumait őrzi. Eltérő bármelyik dátum külön PR-t jelent, így nincs timing
információvesztés. A nullable dátum nem kap kitalált fallbacket. A meglévő
`requested_at` továbbra is a dokumentum létrehozási/kérési timestampje, nem
planning delivery date.

### Supplier policy

Az explicit Supplierrel rendelkező Proposalok csak azonos Supplier mellett
kerülhetnek ugyanabba a PR-be. A PR `supplier_id` mezője nullable. Supplier nélküli
approved Proposalok külön, `supplier_id = null` PR-be konszolidálhatók. A service
nem választ preferred, első, legolcsóbb vagy más Supplier-t.

Currency nem grouping input, mert a Supply Proposal és Purchase Requisition
jelenlegi domainje nem tárol currency/price snapshotot. Factory/business context
szintén nincs ezeken az artifactokon; kitalált kontextus nem kerül a kulcsba.

### Quantity és item consolidation

Azonos PR csoporton belül csak azonos `item_id + unit` Proposalok olvadnak egy
PR Itembe. Külön Itemek ugyanazon PR külön tételei. A PR Item quantity a source
mennyiségek exact, string-alapú három tizedes összege; float számítás nincs.
Implicit unit conversion tilos. MOQ, order multiple, supplier packaging,
purchase-unit conversion és rounding nem része a 0011-nek.

V1-ben partial Proposal consumption nincs: egy Proposal teljes
`proposed_quantity` értéke pontosan egy PR Item source sorba kerül. Persist előtt
és közvetlenül a konszolidáció után teljesül:

```text
sum(proposal source quantities) == purchase requisition item quantity
```

A későbbi 0013 replenishment calculation ezt az invariantet explicit
lifecycle-határon bővíti: a változatlan source total a külön
`planned_quantity`, míg a PR Item `quantity` a supplier constraint szerinti
requested quantity. Ekkor `planned_quantity + replenishment_excess_quantity =
quantity`; a Proposal source sorok nem növekednek az excess értékével.

### Source traceability és fogyasztási állapot

A legacy `PurchaseRequisitionItemSource` kizárólag Material Requirement lineage,
ezért nem kap többértelmű nullable vagy polymorphic source mezőt. Új explicit
`PurchaseRequisitionItemProposalSource` kapcsolat tárolja:

```text
purchase_requisition_item_id
supply_proposal_id
quantity
```

A `supply_proposal_id` adatbázis-szinten unique. Ez egyszerre bizonyítja a
downstream executiont, biztosít idempotenciát és tiltja az overconsumptiont.
A Proposal lifecycle státusza Approved marad; nincs `Converted` státusz.
Konszolidált Approved Proposal későbbi automatikus Cancelled → PR rollbackje
nincs a scope-ban, ezért a már forráshivatkozással rendelkező Proposal
cancel transitionje tiltott.

A `purchase_requisition_items.quantity` precision `decimal(18,3)` értékre nő,
hogy a `SupplyProposal.proposed_quantity` teljes domain tartományát az
összevonás se szűkítse le.

### Idempotencia, batch és reconsolidation

Egy explicit batch atomikus. Ugyanazon Proposalok második konszolidációs kérése
validation hibát ad, és nem hoz létre duplicate PR-t vagy source-t. Minden batch
új Draft PR dokumentumokat hoz létre; később érkező Proposal nem módosít
automatikusan korábban létrehozott Draft PR-t. Ez auditálható batch-határt ad.

### Lifecycle, kód és audit

A létrejövő PR és tételei Draft státuszúak. Az approved Proposal nem jelenti a
PR approvalját. A meglévő PR lifecycle felel a későbbi szerkesztésért,
jóváhagyásért, cancellationért és PO-generálásért.

A requisition number a projekt közös `CodeGeneratorService` és
`CodeDefinitionRegistry` contractjából készül `purchase_requisition` típussal;
nincs use-case-specifikus count-alapú kód.

Sikeres batch után egy `purchase_requisition_consolidation_completed` activity
esemény készül az első létrehozott PR subjectjével és a proposal, requisition,
item, source számokkal. A source soronkénti zajos audit nem szükséges.

### Tranzakció és concurrency

A kiválasztott Proposalok zárolása, revalidationje, PR headerek, itemek és
source-ok létrehozása, valamint a batch audit egy adatbázis-tranzakció része.
Hiba esetén sem részleges PR, sem részleges consumption nem marad. Az `id ASC`
sorzár csökkenti a deadlock kockázatot; a unique Proposal source constraint a
végső concurrency-védelem. Unique ütközés nem alakítható csendes sikerre.

### Határok és legacy deprecation

Az új authoritative út:

```text
Approved Supply Proposal
→ Purchase Requisition Consolidation
→ Purchase Requisition
```

A legacy `MaterialRequirement → Purchase Requisition` route,
controller action és `generateFromMaterialRequirements()` átmenetileg aktív,
de deprecated. Az új endpoint és UI nem használja. Eltávolításának feltétele:
az új flow regressziós időszaka, a régi UI/action kivezetése, a feldolgozatlan
legacy source-ok lezárása vagy migrációja, majd a régi route, action és service
metódus együttes eltávolítása.

A 0011 nem implementál Supplier Selectiont, MOQ/order-multiple policy-t,
Purchase Order generationt, Goods Receiptet, StockBalance-módosítást,
StockMovementet vagy StockReservationt.

## Következmények és nyitott kockázat

- A supplier- és dátumspecifikus PR headerek kifejezik a konszolidációs döntést,
  de a későbbi kézi PR workflow kompatibilitását meg kell őrizni.
- A teljes Proposal consumption egyszerű és erős V1 invariáns; részleges
  felhasználás külön ADR-t és allokációs lifecycle-t igényel.
- Egy már konszolidált Proposal vagy PR későbbi cancellationjének cross-document
  kompenzációja nincs automatizálva; későbbi procurement policy feladata.
- A Supplier Selection és replenishment policy sorrendjét a roadmap külön kezeli;
  supplierless PR domain-helyes marad addig is.

## Elutasított alternatívák

- **Material Requirement közvetlen használata:** megkerülné a Proposal approval
  határt, és a legacy snapshotot tenné authoritative inputtá.
- **A legacy source tábla polymorphic bővítése:** összemosná a Requirement okot a
  Proposal execution source-szal.
- **Dátumok összevonása a legkorábbi header dátumba:** elveszítené a Proposal
  idődimenzióját.
- **Meglévő Draft PR automatikus bővítése:** elmosná a batch audit határát.
- **Automatikus Supplier választás vagy quantity rounding:** a 0012/0013
  felelősségét hozná előre.
