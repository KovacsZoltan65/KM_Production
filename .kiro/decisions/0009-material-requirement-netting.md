# Material Requirement Netting

- **Állapot:** Elfogadva és implementálva
- **Dátum:** 2026-08-13
- **Kapcsolódó döntések:** [0006 MRP Architecture](0006-material-requirements-planning-architecture.md), [0008.5 MRP Foundation Hardening](0008-5-mrp-foundation-hardening.md)

## Cél

A 0009 requirement-szinten, időfázisosan számítja ki, hogy egy Material
Requirement bruttó mennyiségéből mennyi marad fedezetlenül a szükséges napra.
A számítás nem perzisztál eredményt vagy supply-allokációt, és nem hoz létre
planning vagy execution dokumentumot.

## Authoritative demand és identity

Az authoritative demand a `MaterialRequirement.required_quantity`, Item base
unitban. A `missing_quantity`, `available_quantity` és `reserved_quantity`
legacy snapshot, ezért nem netting input.

A requirement identitása `(production_order_id, bom_item_id)`, a calculation
output pedig megőrzi a requirement ID-t, Production Ordert, BOM Itemet,
required Itemet és `required_at` értéket. A külön requirementek Item-szintű
supply poolt osztanak meg, de lineage-ük nem olvad össze.

## Idő és determinisztikus sorrend

Azonos Itemen belül a timed requirementek `required_at ASC, id ASC` sorrendben
fogyasztanak supplyt. A `required_at = null` untimed requirement, amely a timed
requirements után, `id ASC` sorrendben fut. Nem kap kitalált dátumot.

Nap pontosságú V1-ben a `incoming_at <= required_at` supply eligible; a same-day
incoming tehát felhasználható. Későbbi incoming nem fedezhet korábbi demandet.
Untimed requirement használhat szabad on-hand készletet, de dátum nélküli cutoff
miatt future incomingot nem.

## Eligible supply

### On-hand

Az on-hand forrás a `StockBalance` pozitív, Item-szinten összesített fizikai
mennyisége. Ebből egyszer vonódik le az összes aktív `StockReservation` pozitív
mennyisége. Released, Consumed és Cancelled reservation nem csökkenti a poolt.
A V1 nem vezet be location-, batch- vagy quality-allocation policy-t; az Item
base-unit pool soha nem lehet negatív.

### Firm incoming Purchase Order

Firm incoming kizárólag olyan Purchase Order Item fennmaradó mennyisége, ahol:

- a PO státusza `ordered` vagy `partially_received`;
- a tétel státusza `ordered` vagy `partially_received`;
- `ordered_quantity - received_quantity > 0`;
- az Item base unit megegyezik a PO item unitjával;
- `expected_delivery_date` ismert és a requirement cutoff napján vagy előtte van.

A Draft, Received és Cancelled PO, valamint Received és Cancelled tétel nem
incoming. A részlegesen átvett tételből csak a fennmaradó mennyiség számít; a már
átvett mennyiség Stock Movementen keresztül a StockBalance része, így nincs
double count. Dátum nélküli PO a time-phased V1-ben nem eligible.

Purchase Requisition és Supply Proposal – státusztól függetlenül – nem firm
incoming supply.

## Supply consumption és output

Az engine Itemenként egy futásidejű on-hand poolt és dátum szerint rendezett
incoming poolokat épít. Minden requirement először on-hand, majd a cutoffig
eligible incoming mennyiséget fogyaszt. Egy mennyiség egy futáson belül csak
egyszer használható. Az immutable `MaterialRequirementNettingResult` tartalmazza
a gross requirementet, on-hand coverage-et, incoming coverage-et és net
requirementet, három tizedes pontosságra normalizálva.

Az Item-szintű összegzés csak a requirement-level eredmények derivált nézete;
nem authoritative lineage-helyettesítő.

## Határok

- **0010 Pegging:** a 0009 immutable result allocation trace-et is hordoz; a
  külön 0010 service ezt current planning pegként perzisztálhatja. A 0009 maga
  továbbra sem ír adatbázist.
- **Supplier Selection:** nincs supplier-, preferred-, price-, lead-time-, MOQ-
  vagy order-multiple döntés.
- **Supply Proposal:** a 0009 nem olvassa firm supplyként és nem generálja.
- **PR/PO:** a 0009 nem olvas Purchase Requisitiont supplyként, és nem generál
  Purchase Requisitiont vagy Purchase Ordert.
- **Legacy analytics:** a `ProcurementRecommendationService` és a
  `ManufacturingIntelligenceRepository::procurementRecommendations()` megmaradó,
  nem-authoritative analytics/read model; a netting nem függ tőlük.
