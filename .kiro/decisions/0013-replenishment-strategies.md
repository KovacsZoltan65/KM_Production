# Replenishment Strategies

- **Állapot:** Elfogadva és implementálva
- **Dátum:** 2026-08-16
- **Kapcsolódó döntések:** [0007 Item Supplier](0007-item-supplier-procurement-source.md), [0011 Purchase Requisition Consolidation](0011-purchase-requisition-consolidation.md), [0012 Supplier Selection](0012-supplier-selection.md)

## Cél és jelentés

A 0013 azt számítja ki, hogy egy már kiválasztott procurement source
supplier-specifikus mennyiségi feltételei mellett mennyit kell ténylegesen kérni.
Nem dönt ellátási stratégiáról vagy Supplierről, nem számít újra nettó
szükségletet, és nem hoz létre Purchase Ordert vagy készletváltozást.

```text
approved Proposal source quantity
→ Draft PR planned quantity
+ selected ItemSupplier MOQ / order multiple
→ Draft PR requested (replenishment) quantity
```

## Quantity ownership és lineage

A replenishment-adjusted mennyiség a `PurchaseRequisitionItem.quantity`
mezőn él. A mező jelentése a számítás után requested/replenishment quantity.
A külön `planned_quantity` a PR-be konszolidált planning mennyiség változatlan
pillanatképe. Konszolidált tételnél authoritative eredete:

```text
planned_quantity = sum(PurchaseRequisitionItemProposalSource.quantity)
quantity = replenishment-adjusted requested quantity
replenishment_excess_quantity = quantity - planned_quantity
```

A source sorok és az Approved `SupplyProposal.proposed_quantity` nem változnak.
Az 0011 source-total invariant consolidation-time invariant marad; a későbbi
állapotban a fenti háromtagú contract érvényes. A meglévő kézi és legacy PR
tételek migrációkor `planned_quantity = quantity` kezdőértéket kapnak, az új
kézi tételek pedig ugyanezt a snapshotot írják. Ha egy tételnek Proposal
source-ai vannak, újraszámítás előtt azok exact összege kötelezően egyezik a
`planned_quantity` értékkel.

Külön replenishment artifact V1-ben indokolatlan lenne, a Supply Proposal
módosítása elvesztené a történeti planning döntést, a csak PO-kori alkalmazás
pedig approval előtt elrejtené a várható többletet. A Draft PR Item a legkisebb
olyan execution-előkészítő artifact, ahol a Supplier már ismert, de külső
kötelezettség még nem jött létre.

## V1 quantity policy

Az `ItemSupplier.minimum_order_quantity` és `order_multiple` mezőkből a
stratégia determinisztikusan levezethető, ezért külön perzisztált strategy enum
nincs:

| MOQ         | Order multiple | Strategy                 | Szabály                                                           |
| ----------- | -------------- | ------------------------ | ----------------------------------------------------------------- |
| null vagy 0 | null           | `exact`                  | `adjusted = base`                                                 |
| pozitív     | null           | `moq`                    | `adjusted = max(base, MOQ)`                                       |
| null vagy 0 | pozitív        | `order_multiple`         | `adjusted = roundUp(base, multiple)`                              |
| pozitív     | pozitív        | `moq_and_order_multiple` | `candidate = max(base, MOQ)`, majd `roundUp(candidate, multiple)` |

A kerekítés mindig felfelé történik egész számú többszörösre. A requested
mennyiség soha nem lehet kisebb a planned mennyiségnél. Nulla planned demand
eredménye nulla; az MOQ önmagában nem indít beszerzést. Negatív base quantity,
negatív MOQ, nem pozitív order multiple vagy nem pozitív conversion factor
domainhiba. A service a request-validációtól függetlenül fail-fast módon védi
ezeket az invariánsokat.

Minden mennyiségi művelet integer thousandths reprezentációval történik.
Float osztás és `ceil(float)` nincs. A támogatott mennyiségi pontosság három
tizedes, összhangban a 0009–0011 contracttal.

## Unit és conversion contract

A 0007 authoritative contractja szerint:

- `planned_quantity`, MOQ és order multiple az Item base unitjában értendő;
- `1 purchase_unit = conversion_factor × Item base unit`;
- a `purchase_unit` és `conversion_factor` a beszerzési csomagolás magyarázó
  adata, de V1-ben nincs szükség konverzióra a quantity policy alkalmazásához.

Ezért a 0013 nem értelmezi az MOQ-t purchase unitként és nem végez implicit
átváltást. Az üres purchase unit vagy nem pozitív conversion factor ettől még
inkonzisztens procurement policy és blokkolja a számítást. Tört conversion
factor sem okoz float számítást, mert a V1 eredmény base unitban marad.

## Source resolution és effective date

A számítás előfeltétele egy supplier-resolved PR. Minden egyedi PR Itemhez
pontosan egy, a header Supplierhez tartozó eligible `ItemSupplier` szükséges.
Az `item_id + supplier_id` adatbázis unique contract miatt több rekord
integritási hiba lenne; nulla eligible rekord domainhiba. A source-nak aktívnak,
approvednak és az aktuális üzleti napon érvényesnek kell lennie, aktív Itemmel
és aktív Supplierrel. Ez ugyanaz az effective-date jelentés, mint a 0012-ben:
a művelet napja, nem `required_at` vagy `proposed_supply_at`.

Supplierless PR esetén nincs automatikus preferred vagy más Supplier-választás.
Multi-item PR minden sora a saját ItemSupplier policyjével számolódik; eltérő
unitokból header quantity total nem készül.

## Lifecycle, trigger és recalculation

A V1 trigger külön, explicit `Calculate Replenishment` action. Supplier
Selection nem kap rejtett quantity side effectet. A művelet csak Draft PR-en
engedélyezett; Requested, Approved, Ordered és Cancelled állapotban tiltott.

Az egész PR egy tranzakcióban, PR row lock mellett számolódik. Előbb minden
source és eredmény validálódik, majd minden tétel együtt perzisztálódik. Hiba
esetén egyik tétel sem változik. Minden újraszámítás az immutable
`planned_quantity` értékből indul, nem az előző `quantity` eredményből, ezért
azonos inputra idempotens és policy-változáskor nincs drift.

A jelenlegi Draft PR edit workflow csak notes mezőt módosít; quantity manual
override nincs. V1 nem vezet be override-ot. Egy későbbi supplier-change vagy
quantity-edit workflow-nak explicit staleness- és recalculation policy kell.

A calculation időpontját `replenishment_calculated_at`, az aktuális eredményt,
excesst, source ID-t, számításkori MOQ/order-multiple snapshotot és levezetett
strategy labelt a PR Item tárolja. Ez pillanatkép: ItemSupplier-változás után elavulhat. Nincs bizonyíthatatlan
`is_fresh` flag; a felhasználó explicit újraszámítással frissíti.

## Audit

A persisted quantity módosítása `purchase_requisition_replenishment_calculated`
üzleti esemény. Egy batch event tartalmazza a PR azonosítóját, item countot,
changed item countot és itemenként a planned, adjusted, excess, unit,
ItemSupplier, strategy, MOQ és multiple adatot. Különböző unitok mennyiségeit
nem aggregálja. Az audit ugyanabban a tranzakcióban készül, ezért audit hiba
esetén a quantity változások is rollbackelnek.

## Approval és Purchase Order boundary

A 0013 nem módosítja automatikusan a meglévő PR approval contractot. V1-ben az
explicit calculation nincs kötelező approval guarddá téve, mert a repository
kézi és legacy PR flow-t is tartalmaz; e folyamatok kötelező migrationje külön
döntést igényel. Ha lefutott, a PR `quantity` mezője a későbbi PO generation
authoritative requested mennyisége, de a 0013 maga nem approve-ol PR-t és nem
generál PO-t.

A Supplier vagy ItemSupplier policy változása után a tárolt számítás stale
lehet. A későbbi execution-readiness/PO-generation modulnak újra kell validálnia
a source eligibilityt, a replenishment calculation meglétét és frissességét,
valamint történeti supplier-policy snapshotot kell döntenie.

## Határok

A 0013 nem módosítja a Material Requirementet, 0009 netting resultot, 0010
pegeket, Supply Proposal quantityt vagy Proposal source quantityt. Nem hoz létre
PR approvalt, Purchase Ordert, Goods Receiptet, StockBalance módosítást,
StockMovementet vagy StockReservationt. Safety stock, reorder point, forecast,
purchase-to-stock és teljes inventory policy engine nincs a V1 scope-ban.

## Következmények

- Approval előtt láthatóvá válik a supplier constraint miatti tényleges kérés
  és a planninghez képesti többlet.
- A Proposal lineage változatlan, a requested mennyiség mégis közvetlenül
  továbbvihető a későbbi PO-ba.
- A tárolt calculation pillanatkép stalenessét V1-ben timestamp és explicit
  recalculation kezeli, nem dependency graph.
- A következő külön döntés az execution readiness és Purchase Order creation
  határa: kötelező freshness, source/policy snapshot, ár és approval guard.
