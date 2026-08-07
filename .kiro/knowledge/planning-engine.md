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

| Komponens                          | Jelenlegi szerep                                                                                | Besorolás és határ                                                                                                                 |
| ---------------------------------- | ----------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------- |
| `CapacityPlanningService`          | Kapacitásterhelést, ütemezési sorokat és késési kockázatot állít össze                          | Planning/analytics komponens; cache invalidálása technikai mellékhatás, végrehajtást nem végez                                     |
| `CapacitySlotFinder`               | Naptár és meglévő foglalások alapján szabad időablakot keres                                    | Tiszta Planning Engine-segéd; dokumentáltan nem hoz létre foglalást                                                                |
| `LeadTimeEstimator`                | Feladatokból várható kezdést, befejezést és késést becsül                                       | Szimulációs/értékelési komponens; opcionális auditot ír, de kapacitást nem foglal                                                  |
| `SchedulingService`                | Időablakot keres, majd `CapacityReservation` rekordokat hoz létre                               | Hibrid orchestrator: planning eredményt használ, de a foglalás már execution; a két felelősséget későbbi refaktor szétválaszthatja |
| `ManufacturingIntelligenceService` | Több domainből dashboardot és kockázati összesítést komponál                                    | Értékelési/analytics fogyasztó; nem az MRP számítás elsődleges forrása                                                             |
| `ProcurementRecommendationService` | Anyaghiányból és nyitott PO-mennyiségből cache-elt ajánlást ad                                  | Korai supply-planning jellegű read model; nem perzisztált `SupplyProposal`, nincs supplier source vagy teljes időfázisos netting   |
| `MaterialRequirementService`       | Production Order BOM-ját felrobbantja, készletet és aktív foglalást számol, pillanatképet tárol | Részleges MRP előzmény; a target MRP-ben a BOM explosion és a netting külön felelősség                                             |

Ezeket ebben a dokumentációs feladatban nem nevezzük át és nem módosítjuk.

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
| `material_requirements`                                                                    | `customer_order_item_id`, item, required/available/reserved/missing quantity, unit és vegyes jelentésű status  | Részleges Material Requirement + aktuális netting snapshot; hiányzik a közvetlen planning source, `required_at`, calculation context és általános Demand kapcsolat          |
| `stock_balances`, `stock_reservations`, `stock_movements`                                  | Balance összegzés, explicit aktív foglalás, auditálható készletváltozás                                        | Jó alap, de a usable stock policynek quality-, location-, batch-, idő- és allokációs szabályt is definiálnia kell; a Stock Movement marad a készletváltozás forrása         |
| `purchase_requisitions`, `purchase_requisition_items`, `purchase_requisition_item_sources` | Kézi PR és hiányból generált, item/unit szerint konszolidált PR; mennyiségi source pegging létezik             | A generálás előtt külön Supply Proposal és approval indok szükséges; a singular `material_requirement_id` és a többes source kapcsolat párhuzamos jelentését tisztázni kell |
| `purchase_orders`, `purchase_order_items`                                                  | Supplier, requisition kapcsolat, ordered/received quantity, header-szintű expected delivery és workflow status | Nincs külön supplier-promised mennyiség/idő és requirement-szintű supply allocation; a draft nem tekinthető automatikusan biztos incoming supplynak                         |
| `goods_receipts`, `goods_receipt_items`                                                    | PO-hoz köthető receipt és mennyiség; postingkor stock movement alapja                                          | A received, accepted és rejected mennyiségek üzleti különbségét a target modellnek fenn kell tartania                                                                       |
| Capacity Planning, Scheduling, Lead Time, Manufacturing Intelligence                       | Kapacitásértékelés, slotkeresés, foglalás, becslés és recommendation részben létezik                           | A tiszta planning eredményt el kell választani a reservation/execution mellékhatástól; az analytics nem válhat authoritative MRP state-té                                   |

### Az aktuális netting korlátai

A `MaterialRequirementService` jelenlegi számítása koncepcionálisan:

```text
gross = production order quantity × BOM item quantity
free stock = total stock balance - all active reservations
demand reservation = active reservation for this customer/production demand
missing = gross - demand reservation - free stock
```

Ez használható részleges alap, de még nem teljes MRP-netting:

- nem kezeli a `required_at` időpontot és a time-phased elérhetőséget;
- nem értékeli a location, quality, quarantine, batch vagy más használhatósági
  korlátozást teljes szabályrendszerként;
- a `MaterialRequirement` nem kapcsolódik közvetlenül a számítást kiváltó
  `ProductionOrder`-höz, csak a `CustomerOrderItem`-hez;
- az `updateOrCreate(customer_order_item_id, required_item_id)` több lehetséges
  production order/BOM eredetét egy aktuális sorba olvaszthatja;
- az `available_quantity`, `reserved_quantity` és `missing_quantity` tárolt
  pillanatkép, de nincs explicit `calculated_at`, horizon vagy szabályverzió;
- a requirement status egyszerre tartalmaz ellátottsági és procurement
  végrehajtási jelentéseket (`calculated`, `missing`, `ordered`, `received`);
- a Material Requirement számítás nem nettósít open purchase ordert vagy
  production-related incoming supplyt.

A Manufacturing Intelligence ajánlás külön levonja az item-szinten összesített
nyitott PO mennyiséget a hiányok összegéből. Ez részleges jelzés, nem az MRP
authoritative nettingje: a jelenlegi lekérdezés `draft` PO-t is incoming
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

### Net Material Requirements

Az elvi egyenleg nem puszta `required - stock`:

```text
Gross Requirement
- usable available stock at required time
- applicable, sufficiently certain incoming supply at required time
± allocation and reservation effects
= Net Requirement / Shortage
```

A részletes specifikáció dönti el többek között:

- mely location és quality állapot használható;
- hogyan kezelendő active reservation, allocation és ugyanazon demand saját
  reservationje;
- mely PO státusz jelent fedezetet, és a supplier confirmation hogyan módosítja
  a bizonyosságot;
- részszállítás, túlszállítás, rejected/accepted receipt kezelése;
- mikor válik a receipt ténylegesen használható stockká;
- planned vagy released production output mikor számíthat incoming supplynak;
- unit conversion, rounding, minimum order quantity és order multiple;
- azonos supply kettős felhasználásának megakadályozása;
- időfázis, planning horizon, újraszámítás és concurrency.

Az authoritative számítás a `StockMovement`-ből magyarázható készletet,
`StockBalance` összegzést, `StockReservation`-t, open PO/PO itemet, receiptet és
production-related incoming supplyt együtt auditálja. Pontos netting algoritmus
csak külön specifikáció és ADR után implementálható.

### Supply Proposal és konszolidáció

A `SupplyProposal` a Planning Engine magyarázható javaslata; nem végrehajtott
üzleti esemény és nem supplier commitment. Egy proposal több requirementet
fedezhet:

```text
SO-A requirement → 40 kg ┐
SO-B requirement → 30 kg ├→ 1 × 100 kg Purchase Supply Proposal
SO-C requirement → 30 kg ┘
```

A proposal–requirement kapcsolatok külön-külön őrzik a 40/30/30 kg pegginget.
Konszolidáció, MOQ vagy order multiple miatti többlet nem osztható el
hallgatólagosan, és nem törölheti az eredeti Demand kapcsolatát.

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
6. A supply evaluation a `Purchase` stratégiát választja. A supplier csak ezen
   a ponton jelenik meg: a `SupplierSelectionService` az Itemhez tartozó,
   approved és időben érvényes procurement source-okat értékeli.
7. Item–Supplier kapcsolat nélkül automatikus supplier-, lead-time-, MOQ- és
   order-multiple-alapú procurement planning nem lehetséges.
8. A Planning Engine `SupplyProposal`-t készít, amely még nem rendelés.
9. A jóváhagyott proposalból `PurchaseRequisition`, emberi jóváhagyás után
   `PurchaseOrder` jön létre. A PO tehát nem az első objektum.
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
2. Material Requirement identitás-, idő- és forrásmodell tisztázása.
3. Stock availability és időfázisos netting specifikáció.
4. Supply Proposal + proposal pegging modell.
5. Supplier selection policy.
6. Proposalból requisition konszolidáció, meglévő
   `PurchaseRequisitionItemSource` traceability továbbvitelével.

Az új kalkulációk a Planning Engine komponenseiben, az adatlekérdezések
repository-kban, a jóváhagyott requisition/PO létrehozása execution service-ben
kap helyet. Controller nem tartalmazhat nettinget vagy supplier-döntést.

## Kapcsolódó dokumentumok

- [Domain Constitution](../steering/domain-constitution.md)
- [Domain terminológia](domain-terminology.md)
- [Material Requirements Planning Architecture ADR](../decisions/0006-material-requirements-planning-architecture.md)
- [Inventory](inventory.md)
- [Procurement](procurement.md)
- [Production](production.md)
- [Stock Movements ADR](../decisions/0001-stock-movements.md)
