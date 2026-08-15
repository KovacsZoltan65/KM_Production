# Planning Engine és MRP domain architektúra

## Összefoglaló

A `Planning Engine` a KM_Production logikai architekturális rétege és
komponenscsaládja. Nem egyetlen kötelező Laravel service, hanem egymással
együttműködő, fókuszált számítási, értékelési, javaslatkészítési,
optimalizációs és szimulációs komponensek határa.

A réteg bemenetei domain tények és tervezési paraméterek; kimenetei számítási
eredmények, magyarázatok és javaslatok. HTTP-kezelés, CRUD-vezérlés, közvetlen
UI-logika és nem deklarált adatbázis-mellékhatás nem a feladata.

## Felelősségi határ

```text
Domain facts + planning policy + time horizon
                    ↓
             Planning Engine
        calculate / evaluate / simulate
                    ↓
       explained result or proposal
                    ↓
      approval and execution workflow
```

A projekt rétegzése továbbra is `Controller -> Service -> Repository -> Model`.
A Planning Engine ezen belül logikai komponenscsalád: service-ek és fókuszált
domain kalkulátorok repository interfészeken keresztül olvasnak, míg az
execution service-ek külön, tranzakcióban hajtják végre a jóváhagyott döntést.

Egy planning komponens:

- determinisztikus és idempotens legyen azonos bemenetre, ahol lehetséges;
- tegye láthatóvá a felhasznált horizontot, szabályt és feltételezést;
- adjon magyarázható eredményt és traceability hivatkozást;
- mellékhatást csak explicit, névvel jelzett pillanatkép- vagy
  javaslatpersistálási műveletben végezzen;
- ne hozzon létre kontroll nélkül `PurchaseOrder`-t vagy más külső
  kötelezettséget.

## Current State audit

### Planning Engine-jellegű meglévő komponensek

| Komponens                                 | Jelenlegi szerep                                                                                | Besorolás és határ                                                                                                                        |
| ----------------------------------------- | ----------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------- |
| `CapacityPlanningService`                 | Kapacitásterhelést, ütemezési sorokat és késési kockázatot állít össze                          | Planning/analytics komponens; cache invalidálása technikai mellékhatás, végrehajtást nem végez                                            |
| `CapacitySlotFinder`                      | Naptár és meglévő foglalások alapján szabad időablakot keres                                    | Tiszta Planning Engine-segéd; dokumentáltan nem hoz létre foglalást                                                                       |
| `LeadTimeEstimator`                       | Feladatokból várható kezdést, befejezést és késést becsül                                       | Szimulációs/értékelési komponens; opcionális auditot ír, de kapacitást nem foglal                                                         |
| `SchedulingService`                       | Időablakot keres, majd `CapacityReservation` rekordokat hoz létre                               | Hibrid orchestrator: planning eredményt használ, de a foglalás már execution; a két felelősséget későbbi refaktor szétválaszthatja        |
| `ManufacturingIntelligenceService`        | Több domainből dashboardot és kockázati összesítést komponál                                    | Értékelési/analytics fogyasztó; nem az MRP számítás elsődleges forrása                                                                    |
| `ProcurementRecommendationService`        | Anyaghiányból és nyitott PO-mennyiségből cache-elt ajánlást ad                                  | Korai supply-planning jellegű read model; nem perzisztált `SupplyProposal`, nincs supplier source vagy teljes időfázisos netting          |
| `MaterialRequirementService`              | Production Order BOM-ját felrobbantja, készletet és aktív foglalást számol, pillanatképet tárol | Részleges MRP előzmény; a target MRP-ben a BOM explosion és a netting külön felelősség                                                    |
| `MaterialRequirementNettingService`       | Requirement-szintű, időfázisos nettó szükségletet számít batch supply poolokból                 | Authoritative 0009 kalkuláció; immutable eredményt ad, supply-allokációt és procurement artifactet nem perzisztál                         |
| `MaterialRequirementPeggingService`       | A 0009 allocation trace-ből current StockBalance/PO Item pegeket épít és perzisztál             | 0010 planning traceability; tranzakciós rebuild, nem készletfoglalás vagy procurement execution                                           |
| `PurchaseRequisitionConsolidationService` | Explicit selected approved Purchase Proposalokat Draft PR dokumentumokká csoportosít            | 0011 execution orchestrator; supplier-, required- és proposed-supply-date szerint csoportosít, de nem választ Suppliert vagy generál PO-t |

### Meglévő domain lánc

A jelenlegi modell már tartalmazza a lánc jelentős részét:

```text
CustomerOrder / CustomerOrderItem
→ ProductionPlan / ProductionPlanItem
→ ProductionOrder (BOM + operation sequence)
→ MaterialRequirement
→ PurchaseRequisitionItemSource
→ PurchaseRequisitionItem
→ PurchaseOrderItem
→ GoodsReceiptItem
→ StockMovement
```

Erősségek:

- a BOM verziózott, a `ProductionOrder` konkrét `bom_id`-t őriz;
- a készletváltozás `StockMovement` által auditálható;
- a reservation customer-order-itemhez és/vagy production-orderhez köthető;
- a requisition konszolidációja item és unit szerint történik;
- a `purchase_requisition_item_sources.quantity` megőrzi, hogy az összevont
  requisition tételből mennyi tartozik egy-egy `MaterialRequirement`-hez;
- a PO-tétel a requisition tételhez, a receipt tétel a PO-tételhez kapcsolható.

Az auditált elemek felelőssége és fő eltérései:

| Terület / táblák                                                                           | Current State                                                                                                  | Target szempont                                                                                                                                                             |
| ------------------------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `items`, `suppliers`, `item_suppliers`                                                     | Külön törzsadatok és önálló Procurement Source; supplier közvetlenül PO-n továbbra is választható              | Az item-specifikus active/approved/preferred, unit conversion, lead time, referenciaár, MOQ és order multiple alap implementált; automatikus selection még nincs            |
| `boms`, `bom_items`                                                                        | Verziózott BOM és mennyiségi komponensek                                                                       | A BOM explosion megfelelő kiindulás, de a kiválasztott verzió és a requirement eredete végig megőrzendő                                                                     |
| `customer_orders`, `production_plans`, `production_orders`                                 | A production flow Customer Orderhöz kötött; a Production Order konkrét BOM-ot és planning dátumokat őriz       | Az MRP v1 Demand-forrása lehet ez a lánc; a target később más Demand-típusokat is fogad anélkül, hogy azokat Customer Ordernek álcázná                                      |
| `production_tasks` és task materialok                                                      | Végrehajtható gyártási lépések és anyagfelhasználás                                                            | Execution és actual adat; nem helyettesítik a Requirementet vagy a jövőbeli production supplyt                                                                              |
| `material_requirements`                                                                    | Production Order/BOM Item lineage, `required_at`, requirement factek és legacy calculated snapshot mezők       | Stabil production-demand alap; calculation context és általános Demand kapcsolat későbbi bővítés                                                                            |
| `stock_balances`, `stock_reservations`, `stock_movements`                                  | Balance összegzés, explicit aktív foglalás, auditálható készletváltozás                                        | Jó alap, de a usable stock policynek quality-, location-, batch-, idő- és allokációs szabályt is definiálnia kell; a Stock Movement marad a készletváltozás forrása         |
| `purchase_requisitions`, `purchase_requisition_items`, `purchase_requisition_item_sources` | Kézi PR és hiányból generált, item/unit szerint konszolidált PR; mennyiségi source pegging létezik             | A generálás előtt külön Supply Proposal és approval indok szükséges; a singular `material_requirement_id` és a többes source kapcsolat párhuzamos jelentését tisztázni kell |
| `purchase_orders`, `purchase_order_items`                                                  | Supplier, requisition kapcsolat, ordered/received quantity, header-szintű expected delivery és workflow status | Nincs külön supplier-promised mennyiség/idő és requirement-szintű supply allocation; a draft nem tekinthető automatikusan biztos incoming supplynak                         |
| `goods_receipts`, `goods_receipt_items`                                                    | PO-hoz köthető receipt és mennyiség; postingkor stock movement alapja                                          | A received, accepted és rejected mennyiségek üzleti különbségét a target modellnek fenn kell tartania                                                                       |
| Capacity Planning, Scheduling, Lead Time, Manufacturing Intelligence                       | Kapacitásértékelés, slotkeresés, foglalás, becslés és recommendation részben létezik                           | A tiszta planning eredményt el kell választani a reservation/execution mellékhatástól; az analytics nem válhat authoritative MRP state-té                                   |

### Material Requirement snapshot és authoritative netting

A `MaterialRequirementService` jelenlegi számítása koncepcionálisan:

```text
gross = production order quantity × BOM item quantity
free stock = total stock balance - all active reservations
demand reservation = active reservation for this customer/production demand
missing = gross - demand reservation - free stock
```

Ez a tárolt snapshot továbbra is használható legacy UI/reporting célra, de nem
az authoritative MRP-netting. A 0009 kalkuláció:

- a `required_quantity` demand factből indul, a snapshot mezőket figyelmen kívül hagyja;
- Itemenként `required_at ASC, id ASC` sorrendben nettósít, a null dátumokat a
  timed requirements után kezeli;
- a pozitív StockBalance-ból egyszer levonja az összes aktív reservationt;
- csak az `ordered` vagy `partially_received`, ismert és időben megfelelő PO
  fennmaradó mennyiségét tekinti firm incomingnak;
- egy futáson belül ugyanazt a stockot vagy PO-mennyiséget csak egyszer használja.

A V1 tudatos korlátai:

- nem értékeli a location, quality, quarantine, batch vagy más használhatósági
  korlátozást teljes szabályrendszerként;
- az explicit Production Order/BOM Item lineage megakadályozza a split
  production demandek összemosását; ambiguous legacy sorok nullable lineage-dzsel maradnak;
- az `available_quantity`, `reserved_quantity` és `missing_quantity` tárolt
  pillanatkép, de nincs explicit `calculated_at`, horizon vagy szabályverzió;
- a requirement status egyszerre tartalmaz ellátottsági és procurement
  végrehajtási jelentéseket (`calculated`, `missing`, `ordered`, `received`);
- production-related incoming supplyt nem nettósít.

A Manufacturing Intelligence ajánlás külön levonja az item-szinten összesített
nyitott PO mennyiséget a hiányok összegéből. Ez részleges jelzés, nem az MRP
authoritative nettingje: a legacy lekérdezés `draft` PO-t is incoming
supplyként számít, nem time-phased, és nem allokálja mennyiségileg az incoming
supplyt egyedi requirementekhez.

A goods receipt és a purchase receiving folyamat létezik; a tényleges
készletre hatás csak a kapcsolódó `StockMovement` után tekinthető valós
stocknak. A production output szintén stock movement típusként létezik, de
jövőbeli, még el nem készült production supply MRP-fedezetként való
értékeléséhez nincs teljes modell.

## Target Architecture

### Javasolt komponenscsalád

A nevek illeszkednek a jelenlegi `*Service` konvencióhoz, de implementáció előtt
külön design review szükséges:

| Komponens                             | Egyetlen elsődleges felelősség                                                                |
| ------------------------------------- | --------------------------------------------------------------------------------------------- |
| `MaterialRequirementsPlanningService` | Egy planning run koordinálása, bemeneti horizon és policy rögzítése, eredmények összeállítása |
| `StockAvailabilityService`            | Használható on-hand stock meghatározása hely, quality, batch, reservation és idő alapján      |
| `SupplyPlanningService`               | Elfogadható supply-k értékelése, shortage és stratégia-jelöltek képzése                       |
| `SupplierSelectionService`            | Item procurement source-ok rangsorolása explicit, auditálható szabály szerint                 |
| `ReplenishmentPlanningService`        | Az item replenishment strategy alkalmazása és paramétereinek értékelése                       |

A BOM explosion külön kalkulátor lehet. A repository-k csak adatot szolgáltatnak;
nem döntik el, hogy egy supply elfogadható-e vagy mely supplier nyer.

### Material Requirement

A `MaterialRequirement` jelentése:

> Egy adott üzleti cél teljesítéséhez egy adott itemből meghatározott
> mennyiségre meghatározott időpontra szükség van.

Nem `PurchaseOrder`, `PurchaseRequisition`, `StockReservation`, supplier
kapcsolat vagy puszta aktuális hiányérték. A gross requirement akkor is létezik,
ha teljes egészében fedezett. A shortage a requirementből és elfogadott
supplykból számított eredmény.

A target modellnek legalább az eredeti Demandhez, a közvetlen tervezési
forráshoz, a konkrét itemhez, mennyiséghez, unithoz és `required_at` időponthoz
kell traceabilityt biztosítania. A jelenlegi tábla átalakítását külön ADR és
migrációs terv előzze meg; ez a dokumentum nem ír elő azonnali sémamódosítást.

### Supply Strategy

```text
Shortage
→ eligible supply evaluation
→ selected Supply Strategy
→ Supply Proposal
```

Lehetséges stratégiák: `Purchase`, `Transfer`, `Manufacture`, `Subcontract`,
`Consignment`. MRP v1-ben csak `Purchase` aktív; a többi extension point. A
stratégiaválasztás előtt supplier nem része a requirementnek.

### Item Supplier / Procurement Source

Az automatikus procurement planning alapjaként külön kapcsolat készült:

```text
Item
↕
Item Supplier / Procurement Source
↕
Supplier
```

Egy Itemhez több aktív vagy időben érvényes source tartozhat. A source várható
felelőssége: supplier-specifikus cikkszám, purchase unit és átváltás, minimum
order quantity, order multiple, ár és currency, lead time, prioritás,
`preferred`, `approved`, `active`, valamint érvényességi idő. Az ár és más
feltételek historizálása külön döntést igényel.

Az `ItemSupplier` nem készlet, requirement vagy purchase order. Az első verzió
az `item_id + supplier_id` párt egyedivé teszi, külön kezeli az active,
approved és preferred állapotot, naptári napban tárolja a lead time-ot, és az
árat csak aktuális tervezési referenciaárként kezeli. A részletes döntést a
[0007 Item Supplier / Procurement Source ADR](../decisions/0007-item-supplier-procurement-source.md)
rögzíti.

### Supply Proposal

A `SupplyProposal` perzisztált planning artifact, amely egy konkrét Item
tervezett fedezési módját, base-unit mennyiségét és időpontjait rögzíti. A 0008
első verzió kizárólag Purchase stratégiát támogat, de a Supplier szándékosan
nullable: a rendszer már tudhatja, hogy beszerzés szükséges, miközben a supplier
selection még nyitott.

```text
Supply Proposal != Material Requirement
Supply Proposal != Purchase Requisition
Supply Proposal != Purchase Order
Supply Proposal != execution vagy tényleges supply
```

A Draft manuálisan létrehozható és szerkeszthető. A Proposed emberi döntésre
vár; Approved után sem jön létre automatikusan execution dokumentum. A
Az approved proposal továbbra sem firm supply peg. A 0010 pegging kizárólag a
0009-ben ténylegesen felhasznált StockBalance és firm PO Item coverage-et
perzisztálja; proposal-to-requirement kapcsolat későbbi supply-planning döntés.

[0008 Supply Proposal ADR](../decisions/0008-supply-proposal.md)

### Net Material Requirements

Az elvi egyenleg nem puszta `required - stock`:

```text
Gross Requirement
- usable available stock at required time
- applicable, sufficiently certain incoming supply at required time
± allocation and reservation effects
= Net Requirement / Shortage
```

A 0009 V1 policy eldönti a PO státuszokat, részszállítást, unit egyezést,
same-day cutoffot, null dátumot, aktív reservationt és az egyszeri
supply-felhasználást. Későbbi specifikáció dönti el többek között:

- mely location és quality állapot használható;
- hogyan kezelendő a perzisztált allocation és ugyanazon demand saját reservationje;
- a supplier confirmation hogyan módosítja a bizonyosságot;
- részszállítás, túlszállítás, rejected/accepted receipt kezelése;
- mikor válik a receipt ténylegesen használható stockká;
- planned vagy released production output mikor számíthat incoming supplynak;
- unit conversion, minimum order quantity és order multiple;
- időfázis, planning horizon, újraszámítás és concurrency.

Az authoritative V1 számítás a `StockMovement`-ből magyarázható
`StockBalance` összegzést, az aktív `StockReservation`-t és a firm open PO/PO
item fennmaradó mennyiségét használja. A pontos algoritmust a
[Material Requirement Netting ADR](../decisions/0009-material-requirement-netting.md)
rögzíti; production-related incoming supply még nincs a scope-ban.

### Supply Proposal és konszolidáció

A `SupplyProposal` a Planning Engine magyarázható javaslata; nem végrehajtott
üzleti esemény és nem supplier commitment. Egy proposal több requirementet
fedezhet:

```text
SO-A requirement → 40 kg ┐
SO-B requirement → 30 kg ├→ 1 × 100 kg Purchase Supply Proposal
SO-C requirement → 30 kg ┘
```

A 0011-ben az approved Purchase Supply Proposal explicit user selection után
`strategy + supplier_id nullable + required_at + proposed_supply_at` kulccsal
új Draft Purchase Requisition dokumentumokba konszolidálható. Azonos PR-csoport
azonos Item és unit sorai exact base-unit mennyiséggel összeadódnak; minden
Proposal teljes mennyisége külön `PurchaseRequisitionItemProposalSource` soron
marad visszakövethető. Supplierless Proposal supplierless PR-ben marad.

A konszolidáció nem automatikus Proposal→PR lifecycle-átmenet, nem számít újra
net requirementet vagy pegginget, és nem alkalmaz MOQ-t, order multiple-t vagy
supplier selectiont. Egy Proposal V1-ben csak teljesen és pontosan egyszer
használható fel. A Material Requirement lineage és a Proposal execution source
eltérő jelentésű, ezért külön kapcsolatban maradnak.

### Replenishment Strategy

Jövőbeli extension pointok:

- `make_to_order`;
- `make_to_stock`;
- `purchase_to_order`;
- `purchase_to_stock`;
- `manual`.

Például a `Kémcső alapanyag` lehet `purchase_to_order`, míg egy általánosan
fogyó csomagolóanyag `purchase_to_stock`. Stock-driven stratégiánál később
`safety_stock`, `reorder_point`, `minimum_stock`, `maximum_stock` és
`order_quantity` paraméterek szükségesek. Ezek nem részei az MRP v1-nek.

## MRP v1 scope

### Benne van

1. Item Supplier / Procurement Source.
2. BOM-alapú Material Requirement.
3. Használható készlet figyelembevétele.
4. Foglalások és allokációs hatások figyelembevétele.
5. Releváns nyitott beszerzések figyelembevétele.
6. Net Requirement / Shortage számítás.
7. `Purchase` supply strategy.
8. Perzisztált, magyarázható Supply / Procurement Proposal.
9. Több Requirement konszolidációja mennyiségi pegginggel.
10. Purchase Requisition generálása proposalból.
11. Emberi jóváhagyás.
12. Purchase Order létrehozása.
13. Traceability az eredeti Demand forrásig és a receipt/stock eredményig.

## Future Extension / roadmap

- `Transfer`, `Manufacture`, `Subcontract` és `Consignment` strategy;
- forecast-driven planning;
- safety-stock replenishment és stock-policy optimalizáció;
- advanced optimization;
- automatikus supplier order transmission;
- fully autonomous purchasing.

## Automatizálási határ

Az MRP v1 számolhat, hiányt ismerhet fel, konszolidálhat, javaslatot és supplier
ajánlást készíthet. Pénzügyi vagy beszerzési kötelezettség kontroll nélküli
létrehozása nem megengedett:

```text
Requirement
→ Planning
→ Supply Proposal
→ Purchase Requisition
→ Human approval
→ Purchase Order
```

A későbbi autonóm purchase order kiadás külön ADR-t, explicit kockázati és
jogosultsági kontrollt igényel.

## End-to-end példa

Kiindulás:

```text
Customer Order: SO-2026-000001
Item:           Kémcső 11x70
Quantity:       10 000 db
BOM item:       Kémcső alapanyag
Starting stock: 0 kg
```

1. A `CustomerOrder` a Demand üzleti forrása.
2. A `ProductionPlan` időzíti a 10 000 db gyártási célt; még nem tényleges
   végrehajtás.
3. A kiválasztott, verziózott BOM explosionje létrehozza például a 100 kg
   `MaterialRequirement`-et a gyártáshoz szükséges időpontra.
4. A `StockAvailabilityService` 0 kg használható stockot állapít meg, a netting
   pedig az elfogadható incoming supplyt és reservationöket is megvizsgálja.
5. Fedezet hiányában 100 kg `Shortage` keletkezik.
6. A supply evaluation a `Purchase` stratégiát választja. A Proposal Supplierje
   lehet explicit vagy null; automatikus Supplier Selection még nincs.
7. Item–Supplier kapcsolat nélkül automatikus supplier-, lead-time-, MOQ- és
   order-multiple-alapú procurement planning nem lehetséges.
8. A Planning Engine `SupplyProposal`-t készít, amely még nem rendelés.
9. A user az approved Proposalokat explicit konszolidálja. A 0011 determinisztikus
   csoportjai Draft `PurchaseRequisition` dokumentumokat hoznak létre, külön
   approval workflow-val. Supplier Selection és a későbbi `PurchaseOrder`
   létrehozása más use case; a PO tehát nem az első objektum.
10. A `GoodsReceipt` rögzíti a beérkezést; az elfogadott készletváltozás
    `StockMovement` útján kerül a stockba.
11. A production availability újraszámítható, miközben a teljes pegging lánc
    megmarad:

```text
Goods Receipt / Stock Movement
→ Purchase Order
→ Purchase Requisition
→ Supply Proposal
→ Material Requirement
→ Production Plan / Production Order
→ SO-2026-000001
```

## Implementációs sorrend és első felelősségi határok

Az első későbbi implementációk ajánlott sorrendje:

1. ~~Item Supplier / Procurement Source ADR és adatmodell.~~ Elkészült a 0007 döntésben.
2. ~~Material Requirement identitás-, idő- és forrásmodell tisztázása.~~ Elkészült a 0008.5 hardeningben.
3. ~~Stock availability és időfázisos netting specifikáció és V1 kalkuláció.~~ Elkészült a 0009 döntésben.
4. ~~Supply Proposal domainmodell és lifecycle.~~ Elkészült a 0008 döntésben.
5. ~~Requirement coverage konkrét supply pegging.~~ Elkészült a 0010 döntésben.
6. ~~Approved Purchase Supply Proposalok Draft Purchase Requisition
   konszolidációja explicit Proposal source trace-szel.~~ Elkészült a 0011
   döntésben.
7. Supplier selection policy (0012).

### Legacy direct Requirement → PR deprecation

A `PurchaseRequisitionService::generateFromMaterialRequirements()` és a hozzá
tartozó route/controller/UI action kompatibilitási okból még aktív, de
deprecated. Új kód nem használhatja. Az authoritative út:

```text
Approved Supply Proposal
→ Purchase Requisition Consolidation
→ Purchase Requisition
```

A legacy út csak az új workflow regressziós időszaka, a régi UI átvezetése és a
feldolgozatlan legacy source-ok migrációja vagy üzleti lezárása után távolítható
el. A removal egy későbbi célzott változásban együtt törli a route-ot,
controller actiont és service metódust; a 0011 ezeket még nem törli.

Az új kalkulációk a Planning Engine komponenseiben, az adatlekérdezések
repository-kban, a jóváhagyott requisition/PO létrehozása execution service-ben
kap helyet. Controller nem tartalmazhat nettinget vagy supplier-döntést.

## Kapcsolódó dokumentumok

- [Domain Constitution](../steering/domain-constitution.md)
- [Domain terminológia](domain-terminology.md)
- [Material Requirements Planning Architecture ADR](../decisions/0006-material-requirements-planning-architecture.md)
- [Material Requirement Netting ADR](../decisions/0009-material-requirement-netting.md)
- [Requirement Pegging ADR](../decisions/0010-requirement-pegging.md)
- [Purchase Requisition Consolidation ADR](../decisions/0011-purchase-requisition-consolidation.md)
- [Inventory](inventory.md)
- [Procurement](procurement.md)
- [Production](production.md)
- [Stock Movements ADR](../decisions/0001-stock-movements.md)
