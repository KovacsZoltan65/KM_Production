# Supply Proposal üzleti modell és életciklus

- **Állapot:** Elfogadva és implementálva
- **Dátum:** 2026-08-07
- **Kapcsolódó döntések:** [0006 MRP Architecture](0006-material-requirements-planning-architecture.md), [0007 Item Supplier](0007-item-supplier-procurement-source.md)

## Kontextus és üzleti probléma

A szükséglet vagy hiány önmagában még nem mondja meg, hogyan kell biztosítani a
fedezetet. A tervezés tehet javaslatot, de ezt külön emberi döntésnek kell
elfogadnia, és a végrehajtásnak is külön üzleti folyamatban kell megtörténnie.

A rendszerben már létezett Material Requirement, közvetlenül létrehozott
Purchase Requisition és több ajánlási olvasási modell. Nem volt azonban tárolt
objektum a tervezési eredmény és az emberi végrehajtási döntés között. Emiatt
nem lehetett külön auditálni, mit javasolt a tervezés, mit fogadott el a
felhasználó, és mi valósult meg később.

## Döntés

A `SupplyProposal` önálló tervezési javaslat. Azt rögzíti, hogy egy konkrét Item
milyen mennyiséggel és mikorra fedezhető. Nem Material Requirement, Purchase
Requisition, Purchase Order, jóváhagyott végrehajtás vagy tényleges ellátási
eredmény.

```text
Proposal → emberi Approval → későbbi Execution → Result
```

Ez a különválasztás kötelező: a Proposal javaslat, az Approval emberi döntés, az
Execution külön végrehajtás, a Result pedig a tényleges eredmény. Az objektum
legfeljebb `Approved` döntési állapotig jut. A későbbi végrehajtás külön üzleti
objektum és külön csomag feladata.

## Egyszerű példa

Egy Itemből 10 darabra van szükség szeptember 20-ra. A tervezési javaslat 10
darab beszerzését ajánlja szeptember 22-re. A későbbi dátum tervezési kockázatot
jelez, de a Supply Proposal ettől még érvényes lehet.

A javaslat jóváhagyása azt jelenti, hogy a döntéshozó elfogadta ezt a tervezési
irányt. Nem jelenti Purchase Requisition, Purchase Order vagy Dispatch
létrejöttét, és nem bizonyít tényleges ellátást.

## Stratégia

A központi modell nincs egyetlen ellátási stratégiához kötve, de a V1 enum
kizárólag a ténylegesen támogatott `Purchase` értéket tartalmazza. A `Transfer`,
`Manufacture`, `Subcontract` és `Consignment` dokumentált bővítési lehetőség,
nem választható működő opció.

A Supplier a `Purchase` stratégia mellett is opcionális. A
`supplier_id = null` azt jelenti, hogy a beszerzési fedezési mód már eldőlt, de
a Supplier Selection még nem történt meg.

Ha a javaslat megnevez Suppliert, az Itemhez a 0007 szerint aktív, jóváhagyott
és aktuálisan érvényes Procurement Source szükséges. A Supplier megadása nem
jelent rendelést, és automatikus kiválasztás nincs. Az alkalmazhatósági szabályt
az Approval tranzakcióban, sorzár mellett ismét ellenőrizni kell.

## Mennyiség és idő

- `proposed_quantity` pozitív decimális mennyiség az Item alapegységében;
- `unit` az Item alapegységének létrehozáskor vagy módosításkor rögzített
  pillanatképe;
- `required_at` az igényelt rendelkezésre állás dátuma;
- `proposed_supply_at` a javasolt rendelkezésre állás dátuma.

A két dátum jelentése eltér. A `required_at` azt mutatja meg, mikorra szükséges
az Item; a `proposed_supply_at` azt, hogy a javaslat szerint mikorra várható a
fedezet. Mindkettő nap pontosságú. A késői javaslat
(`proposed_supply_at > required_at`) megengedett valós tervezési tény, nem
validációs hiba.

## Életciklus és szerkeszthetőség

A javaslat először szerkeszthető vázlat. Ezután döntésre küldhető, majd
jóváhagyható, elutasítható vagy visszavonható. A jóváhagyás után már nem
szerkeszthető, de végrehajtási kapcsolat hiányában még visszavonható.

```text
Draft → Proposed
Draft → Cancelled
Proposed → Approved
Proposed → Rejected
Proposed → Cancelled
Approved → Cancelled
```

Csak a `Draft` szerkeszthető. `Proposed` állapotban a javaslat döntésre vár.
`Approved`, `Rejected` és `Cancelled` állapotban nem módosítható. Újranyitás
nincs. Az `Approved → Cancelled` átmenet addig megengedett, amíg nincs
Execution-kapcsolat; a 0008 még nem hoz létre ilyen kapcsolatot. A `Rejected`
és `Cancelled` lezárt állapot.

Minden átmenetet a `SupplyProposalService` tranzakcióban, sorzárral és szerveroldali
átmeneti mátrix alapján hajt végre. A felület csak az engedélyezett műveleteket
mutatja, de nem biztonsági határ.

## Végrehajtási határ

```text
Approved Supply Proposal
≠ Purchase Requisition
≠ Purchase Order
≠ Dispatch
```

A 0008 egyik következő dokumentumot vagy eseményt sem hozza létre automatikusan.
A jóváhagyott Supply Proposal továbbra is tervezési döntés, nem beszerzési
végrehajtás.

## Audit és magyarázhatóság

A tárolt üzleti tények: `strategy`, Item, opcionális Supplier, mennyiség és
`unit`, `required_at`, `proposed_supply_at`, `status`, `reason_code`, `notes`,
valamint a felhasználó és az időpont hozzárendelése. A
`created`, `updated`, `proposed`, `approved`, `rejected`, `cancelled` események az
`AuditLogService` használatával naplózottak. A felhasználói azonosító mindig a
bejelentkezett felhasználóból származik.

## Komponensfelelősség

- **Miért létezik?** Kifejezett, auditálható határt ad a tervezés és a
  végrehajtás között.
- **Üzleti jelentés:** javaslat arra, hogyan és mikorra fedezhető egy Item mennyisége.
- **Mi hozza létre?** V1-ben jogosult felhasználó kézzel; később a Planning
  Engine is létrehozhatja.
- **Mi módosítja?** Jogosult felhasználó kizárólag `Draft` állapotban.
- **Mi zárja le?** Approve, Reject vagy Cancel átmenet.
- **Ki használja?** Tervezési és beszerzési döntéshozók; később végrehajtási
  komponensek.
- **Elsődleges adatforrás:** a `supply_proposals` tábla és auditnaplója.
- **Tárolt tény:** a javaslat tartalma, döntési státusza és a hozzá rendelt
  felhasználók.
- **Számított adat:** V1-ben nincs tárolt netting- vagy rangsorolási eredmény.
- **Mi nem a feladata?** Demand vagy Requirement tárolása, pegging, netting,
  Supplier-rangsorolás, PR- vagy PO-létrehozás, illetve ellátás könyvelése.
- **Nyomon követhetőség:** a létrehozó és az életciklus-döntéshozók, az időpontok
  és az aktivitási események.
- **Idődimenzió:** a szükségleti dátum, a javasolt ellátási dátum és az
  életciklus időpontjai.
- **Bizonytalanság:** tervezési feltételezést fejez ki; az opcionális Supplier
  kifejezetten nyitott döntést jelent.

## Alternatívák

- A Material Requirement státuszának bővítése összekeverné a szükségletet a fedezési javaslattal.
- Azonnali Purchase Requisition létrehozás eltüntetné a tervezés és végrehajtás
  közötti határt.
- Általános munkafolyamat-motor vagy eseményalapú teljes állapottárolás
  aránytalan lenne a jelenlegi igényhez.
- Több működő stratégia bevezetése végrehajtási implementáció nélkül hamis képességet jelentene.

## Következmények

A netting, a pegging, az összevonás és a Supplier Selection stabil tervezési
javaslatra épülhet. A 0008 szándékosan nem kapcsolja a Supply Proposalt Material
Requirementhez, és Approval után sem hoz létre végrehajtási dokumentumot.
