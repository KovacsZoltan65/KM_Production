# Az MRP-alapok megerősítése: szükségletazonosság és időszemantika

- **Állapot:** Elfogadva és implementálva
- **Dátum:** 2026-08-13
- **Kapcsolódó döntések:** [0006 MRP Architecture](0006-material-requirements-planning-architecture.md), [0007 Item Supplier](0007-item-supplier-procurement-source.md), [0008 Supply Proposal](0008-supply-proposal.md)

## Kontextus és üzleti probléma

Az anyagszükséglet-tervezésnek (Material Requirements Planning, MRP) minden
gyártási szükségletet önállóan kell azonosítania. Ugyanaz a vevői rendeléstétel
több Production Orderre bontható. Ezek ugyanazt a BOM-komponenst eltérő
mennyiségben vagy eltérő időpontra kérhetik. Ha a rendszer ezeket egyetlen
rekordként kezelné, az egyik gyártási szükséglet újraszámítása felülírhatná a
másikat.

A `MaterialRequirement` azt az anyagszükségletet rögzíti, amely egy konkrét
gyártási cél teljesítéséhez szükséges: melyik Itemből, mennyi és mikorra kell
rendelkezésre állnia. Ez a szükséglet attól függetlenül megmarad, hogy éppen
mennyi készlet vagy várható ellátás fedezi.

Ezért a Material Requirement nem azonos:

- a pillanatnyi Shortage értékével;
- a Supply Proposallal;
- a Purchase Requisitionnel;
- a készletfoglalással;
- a Purchase Orderrel.

A jelen döntés az MRP megbízható alapját erősíti meg. Nem vezet be új Netting-,
Pegging- vagy beszerzési végrehajtási folyamatot.

## Egyszerű példa

Egy Production Orderhez 10 kg anyag szükséges szeptember 20-ra. Ez a 10 kg a
Material Requirement akkor is, ha mind a 10 kg rendelkezésre áll, ha csak 4 kg
áll rendelkezésre, vagy ha egy Purchase Order már fedezi egy részét. A későbbi
Netting akár nulla hiányt is számíthat, de ettől a 10 kg-os anyagszükséglet nem
szűnik meg és nem változik más üzleti objektummá.

## Döntés a szükséglet azonosságáról

A Material Requirement korábbi technikai azonossága a
`(customer_order_item_id, required_item_id)` pár volt. Ez nem azonosított
egyértelműen egy üzleti szükségletet, mert ugyanaz a vevői rendeléstétel több
Production Orderre bontható.

Az aktuális gyártási Demand közvetlen azonossága ezért a
`(production_order_id, bom_item_id)` pár. A `customer_order_item_id` megmarad a
tágabb Demand eredetkapcsolataként. A `required_item_id` továbbra is a szükséges
Item kifejezett kapcsolata.

Ugyanennek a `(production_order_id, bom_item_id)` párnak az újraszámítása
idempotens: azonos bemenet ismételt feldolgozása nem hoz létre második üzleti
eredményt. Eltérő Production Orderek nem írják felül egymást.

### Migrációs kompatibilitás

Az eredetkapcsolati mezők nullable-k maradnak. Ez azért szükséges, mert a régi
rekordok története nem minden esetben állapítható meg egyértelműen.

A migráció csak akkor tölti vissza ezeket a mezőket, ha egy régi sorhoz pontosan
egy Production Order- és BOM Item-jelölt tartozik. Több jelölt esetén a rendszer
nem talál ki történelmi kapcsolatot: a sor hozzárendelés nélküli legacy rekord
marad.

Egyelőre nincs unique constraint. A soft delete és a későbbi életciklus még nem
bizonyítja, hogy mely végleges adatbázis-korlát lenne helyes.

## A szükségleti dátum jelentése

A `required_at` nap pontossággal azt a legközelebbi ismert időpontot tárolja,
amikorra a komponensnek rendelkezésre kell állnia. Mivel még nincs komponens-
vagy műveletszintű felhasználási idő, a rendszer a következő, determinisztikus
sorrendben választ dátumot:

1. Production Order `planned_start_date`;
2. Production Plan Item `planned_start_date`;
3. Production Plan `planned_start_date`;
4. Customer Order `requested_delivery_date`;
5. `null`, ha egyik tény sem ismert.

A szállítási dátumra történő visszaesés nem állítja, hogy azon a napon használják
fel a komponenst. Csak a legközelebbi rendelkezésre álló Demand-határidőt őrzi.

## Szükségleti tény és tervezési pillanatkép

A Material Requirement saját, elsődleges üzleti tényei:

- `production_order_id`;
- `bom_item_id`;
- `customer_order_item_id`;
- `required_item_id`;
- `required_quantity`;
- `unit`;
- `required_at`.

Ezek mondják meg, mely gyártási célhoz, mely Itemből, mennyi és mikorra
szükséges.

Az `available_quantity`, `reserved_quantity`, `missing_quantity` és az
ellátottsági `status` ezzel szemben korábban számított tervezési pillanatkép.
Kompatibilitási, valamint jelenlegi felületi és riportálási célból megmaradnak,
de a következő Netting nem kezelheti a `missing_quantity` mezőt elsődleges
bemenetként. A Material Requirement azonossága és mennyisége tehát független az
éppen számított hiánytól.

## Supply Proposal és aktív törzsadatok

Az MRP későbbi lépései csak használható törzsadatokra épülhetnek. Ezért:

- új Item Supplier és Supply Proposal csak aktív Itemmel hozható létre vagy
  módosítható;
- Item Supplier csak aktív Supplierrel hozható létre vagy módosítható;
- Supply Proposal jóváhagyásakor a rendszer ismét ellenőrzi az aktív Itemet;
- ha a Supply Proposalhoz Suppliert választottak, a rendszer ismét ellenőrzi az
  aktív Suppliert és a hozzá tartozó aktív, jóváhagyott, aktuálisan érvényes
  Item Suppliert.

A Supplier továbbra is opcionális a Supply Proposalon. A később inaktivált
történeti rekordok olvashatók és megmaradnak. Ezek a pontosítások a tervezési
alapot védik; nem jelentenek Supplier-választást, rendelést vagy más
végrehajtást.

## Legacy Material Requirement → Purchase Requisition út

A `PurchaseRequisitionService::generateFromMaterialRequirements()`, a hozzá
tartozó route, controller és felületi művelet működő kompatibilitási út marad,
de **kivezetésre jelölt**. Új MRP-kód nem építhet rá függőséget.

A 0011 migrációs és eltávolítási terve:

1. vezesse be a jóváhagyott Supply Proposalok kifejezett, mennyiségi Purchase
   Requisition-konszolidációs bemenetét és idempotenciakulcsát;
2. őrizze meg a Proposal → Purchase Requisition tétel forráskapcsolatát az
   audithoz;
3. terelje át a felületi műveletet és a jogosultságot az új folyamatra;
4. migrálja vagy zárja le a feldolgozatlan legacy forrásokat;
5. regressziós időszak után együtt távolítsa el a route-ot, a controller
   műveletet és a `generateFromMaterialRequirements()` metódust;
6. csak ezután szüntesse meg a `missing_quantity` mezőtől függő Purchase
   Requisition-generálást.

## Határok

Ez a döntés nem valósít meg Nettinget, Pegginget, Purchase
Requisition-konszolidációt vagy automatikus Purchase Order-generálást. A
Material Requirement megbízható azonosságát, időpontját, mennyiségét és
eredetkapcsolatait alapozza meg a későbbi tervezési lépések számára.
