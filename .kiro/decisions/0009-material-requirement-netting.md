# Material Requirement Netting

- **Állapot:** Elfogadva és implementálva
- **Dátum:** 2026-08-13
- **Kapcsolódó döntések:** [0006 MRP Architecture](0006-material-requirements-planning-architecture.md), [0008.5 MRP Foundation Hardening](0008-5-mrp-foundation-hardening.md)

## Cél és üzleti kérdés

A Netting, vagyis a szükséglet nettósítása egy tervezési számítás. Erre a
kérdésre válaszol:

> A szükséges anyagmennyiségből mennyi marad fedezetlen a szükség időpontjára,
> miután figyelembe vettük a szabály szerint használható fedezetet?

A számítás a teljes, bruttó Material Requirementből indul. Előbb figyelembe
veszi a felhasználható készletet, majd a szükségleti határidőig alkalmazható,
kellően biztos beérkező ellátást. Az eredmény a fedezett mennyiség és a nettó
szükséglet, vagyis a Shortage.

A 0009 ezt Requirement-szinten és időrendben számítja ki. Nem tárolja el az
eredményt vagy az ellátás hozzárendelését, és nem hoz létre tervezési vagy
végrehajtási dokumentumot.

## Egyszerű példa

Egy Material Requirement szerint 10 kg anyagra van szükség szeptember 20-ra.
A rendszer 4 kg felhasználható készletet és egy szeptember 20-ig beérkező,
alkalmazható Purchase Order-tételből 3 kg fedezetet talál.

```text
Bruttó szükséglet:              10 kg
- felhasználható készlet:        4 kg
- időben alkalmazható PO-fedezet: 3 kg
= Shortage:                      3 kg
```

A Material Requirement továbbra is 10 kg. A Netting csak azt állapítja meg,
hogy ebből a számítás pillanatában 3 kg fedezetlen. Ha a 3 kg-os beérkezés
szeptember 21-re lenne várható, nem fedezhetné a szeptember 20-i szükségletet.

## Elsődleges szükségleti adat és azonosság

A számítás elsődleges szükségleti bemenete a
`MaterialRequirement.required_quantity`, az Item alapegységében. A
`missing_quantity`, `available_quantity` és `reserved_quantity` régi, számított
pillanatkép, ezért nem Netting-bemenet.

A Requirement azonossága `(production_order_id, bom_item_id)`. A számítás
kimenete megőrzi:

- a Requirement azonosítóját;
- a Production Ordert;
- a BOM Itemet;
- a szükséges Itemet;
- a `required_at` értéket.

Az egyes Requirementek Item-szintű közös fedezeti készletből fogyasztanak, de
az eredetkapcsolatuk nem olvad össze.

## Idő és determinisztikus sorrend

Az idő azért számít, mert csak az a jövőbeli ellátás fedezhet egy szükségletet,
amely legkésőbb a szükség napján rendelkezésre áll.

Azonos Itemen belül a dátummal rendelkező Requirementek
`required_at ASC, id ASC` sorrendben fogyasztanak fedezetet. A
`required_at = null` dátum nélküli Requirement. Ezek a dátummal rendelkező
Requirementek után, `id ASC` sorrendben következnek. A rendszer nem talál ki
hozzájuk dátumot.

A nap pontosságú V1-ben az ellátás akkor alkalmazható, ha
`incoming_at <= required_at`. Az aznapi beérkezés tehát felhasználható, a
későbbi beérkezés viszont nem fedezhet korábbi szükségletet. A dátum nélküli
Requirement használhat szabad, fizikailag meglévő készletet, de dátumhatár
hiányában jövőbeli beérkezést nem.

## Figyelembe vehető fedezet

### Fizikailag meglévő készlet

A fizikailag meglévő készlet forrása a `StockBalance` pozitív, Item-szinten
összesített mennyisége. Ebből a rendszer egyszer vonja le az összes aktív
`StockReservation` pozitív mennyiségét. A `Released`, `Consumed` és `Cancelled`
foglalás nem csökkenti a közös készletet.

A V1 nem vezet be teljes hely-, batch-, minőségi vagy készlethozzárendelési
szabályrendszert. Az Item alapegységében kezelt közös készlet soha nem lehet
negatív.

### Biztos beérkező Purchase Order-fedezet

Biztos beérkező fedezetként kizárólag olyan Purchase Order Item fennmaradó
mennyisége vehető figyelembe, amelynél minden következő feltétel teljesül:

- a Purchase Order státusza `ordered` vagy `partially_received`;
- a tétel státusza `ordered` vagy `partially_received`;
- `ordered_quantity - received_quantity > 0`;
- az Item alapegysége megegyezik a Purchase Order Item `unit` értékével;
- az `expected_delivery_date` ismert, és legkésőbb a Requirement határnapjára
  esik.

A `Draft`, `Received` és `Cancelled` Purchase Order, valamint a `Received` és
`Cancelled` tétel nem beérkező fedezet. Részlegesen átvett tételnél csak a
fennmaradó mennyiség számít. A már átvett mennyiség a `StockMovement` rekordon
keresztül a StockBalance része, ezért nem számolható el még egyszer. Dátum nélküli
Purchase Order a V1 időfázisos számításában nem használható.

A Purchase Requisition és a Supply Proposal egyik státuszában sem biztos
beérkező ellátás.

ADR 0016 később bevezeti a Supplier Acknowledgementet, de nem módosítja ezt a
történeti szabályt. A 0009 továbbra is a fenti Purchase Order-státuszokat,
fennmaradó rendelt mennyiséget és `expected_delivery_date` értéket használja.

## A fedezet felhasználása és a számítás eredménye

A számítási komponens Itemenként egy futás közben kezelt készletkeretet és dátum
szerint rendezett beérkezőellátás-kereteket épít. Minden Requirement
először a fizikailag meglévő készletből, majd a határnapig alkalmazható beérkező
ellátásból fogyaszt. Ugyanaz a mennyiség egy futáson belül csak egyszer
használható fel.

Az eredmény egy nem módosítható `MaterialRequirementNettingResult`, amely három
tizedes pontosságra normalizálva tartalmazza:

- a bruttó szükségletet;
- a készletből származó fedezetet;
- a beérkező ellátásból származó fedezetet;
- a nettó szükségletet.

Az eredmény számított és származtatott; a 0009 nem perzisztálja. Az Item-szintű
összegzés csak a Requirement-szintű eredményekből képzett nézet. Nem válik az
eredetkapcsolatok elsődleges forrásává.

## Végrehajtási és felelősségi határok

- **0010 Pegging:** a `MaterialRequirementNettingResult` a felhasznált fedezet
  részletes hozzárendelését is hordozza. Ezt külön 0010 Service aktuális
  tervezési Pegging-kapcsolatként eltárolhatja. A 0009 maga nem ír adatbázist.
- **Supplier Selection:** nincs Supplier-, preferencia-, ár-, átfutásiidő-,
  MOQ- vagy rendelésitöbbszörös-döntés.
- **Supply Proposal:** a 0009 nem kezeli biztos ellátásként, nem hozza létre és
  nem hagyja jóvá.
- **Purchase Requisition és Purchase Order:** a 0009 nem olvas Purchase
  Requisitiont fedezetként, és egyiket sem hozza létre.
- **Készlet:** a 0009 nem hoz létre `StockReservation` vagy `StockMovement`
  rekordot, és nem módosít készletet.
- **Régi elemzések:** a `ProcurementRecommendationService` és a
  `ManufacturingIntelligenceRepository::procurementRecommendations()` továbbra
  is megmaradó, de nem elsődleges elemzési olvasási modell. A Netting nem függ
  tőlük.

A Netting tehát számol, de nem hajt végre. Nem indít automatikus Supply
Proposal-, Supplier Selection-, Purchase Requisition-, Purchase Order- vagy
készletfoglalási folyamatot.
