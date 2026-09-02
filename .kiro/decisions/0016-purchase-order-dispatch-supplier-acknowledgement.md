# Purchase Order Dispatch / Supplier Acknowledgement

- **Állapot:** Elfogadva, implementációra vár
- **Dátum:** 2026-09-01
- **Kapcsolódó döntések:** [0001 Stock Movements](0001-stock-movements.md), [0009 Material Requirement Netting](0009-material-requirement-netting.md), [0010 Requirement Pegging](0010-requirement-pegging.md), [0014 Purchase Requisition Execution Readiness](0014-purchase-requisition-execution-readiness.md), [0015 Purchase Order Generation](0015-purchase-order-generation.md)

## Kontextus

A [0015-ös döntés](0015-purchase-order-generation.md) egy végrehajtásra kész,
jóváhagyott Purchase Requisitionből hozza létre a beszerzés belső dokumentumát,
a `Draft` állapotú Purchase Ordert. A létrehozás egyetlen
adatbázis-tranzakcióban, idempotens módon és történeti másolatokkal történik. A
Purchase Order létrejötte azonban nem bizonyítja, hogy a rendelést elküldték a
Suppliernek. A 0015 nem rögzít Supplier-visszaigazolást, nem vételez árut, és
nem módosít készletet.

A beszerzőnek ezért külön kell látnia:

- történt-e tényleges Dispatch-kísérlet a Supplier felé;
- sikeres volt-e a kísérlet, mikor, milyen csatornán és mely címzett felé;
- érkezett-e Supplier Acknowledgement;
- a Supplier tételenként milyen mennyiséget és szállítási dátumot ígért;
- eltér-e a válasz a Purchase Order tartalmától;
- szükséges-e beszerzői utánkövetés vagy újratervezés.

A `Purchase Order`, `Dispatch`, `Supplier Acknowledgement`, `Goods Receipt` és
`Invoice / Financial Settlement` eltérő üzleti tény. Egyik sem bizonyítja
automatikusan a másikat.

## Probléma

A jelenlegi Purchase Orderből nem állapítható meg, hogy a rendelést ténylegesen
elküldték-e, illetve mit igazolt vissza a Supplier. Ha ezeket az információkat
kizárólag a `PurchaseOrder.status`, az `ordered_at` vagy az
`expected_delivery_date` mezőben tárolnánk, akkor nem maradna külön látható:

- a sikertelen és ismételt Dispatch-kísérletek története;
- az egyes Dispatch-kísérletek címzettje és csatornája;
- az eredeti rendelés és a Supplier ígérete közötti különbség;
- a részleges, eltérő vagy javított Acknowledgement magyarázata;
- a Dispatch, az Acknowledgement és a Goods Receipt közötti üzleti határ.

A megoldásnak két hasonló, de eltérő esetet is szét kell választania. Egy
véletlenül megismételt HTTP-kérés nem hozhat létre új rekordot. Egy szándékos
újraküldésnek vagy javított Supplier-válasznak viszont új, történetileg
megőrzött rekordot kell létrehoznia.

## Döntés

A rendszer a Dispatch-kísérleteket és a Supplier Acknowledgementeket két külön,
csak új rekordokkal bővíthető történetként tárolja. Mindkettő elkülönül magától
a Purchase Ordertől:

```text
Purchase Order
├─ 1. Purchase Order Dispatch-kísérlet (sikertelen)
├─ 2. Purchase Order Dispatch-kísérlet (sikeres)
└─ Supplier Acknowledgement v1
   ├─ válasz az A PO-tételre
   └─ válasz a B PO-tételre
      ↓ felváltja
   Supplier Acknowledgement v2
```

A `PurchaseOrderStatus` nem kap `Dispatched`, `Acknowledged` vagy hasonló új
értéket. A Dispatch és az Acknowledgement aktuális állapotát a hozzájuk tartozó
rekordokból kell kiszámítani. A két állapot egymástól függetlenül jelenik meg.

Az `Ordered` és az `ordered_at` jelentése változatlan. Az `ordered_at` továbbra
is a meglévő belső jóváhagyási és rendelési átmenet időpontja, nem a kiküldés
időpontja. A sikeres Dispatch saját `dispatched_at` mezőt kap.

## Üzleti fogalmak

| Fogalom                              | Jelentés                                                                                   | Nem jelenti                                                     |
| ------------------------------------ | ------------------------------------------------------------------------------------------ | --------------------------------------------------------------- |
| `PurchaseOrder`                      | A vevő által rögzített formális rendelés és belső végrehajtási dokumentum.                 | Nem bizonyít Dispatch-et, Supplier-ígéretet vagy áruátvételt.   |
| `PurchaseOrderDispatch`              | Egy konkrét, ismert eredménnyel lezárt Dispatch-kísérlet ellenőrizhető ténye.              | Nem jóváhagyás, Acknowledgement vagy Goods Receipt.             |
| `SupplierAcknowledgement`            | A Supplier egy adott időpontban kapott válaszának teljes fejlécmásolata.                   | Nem módosított PO, áruátvétel, számla vagy fizetés.             |
| `SupplierAcknowledgementItem`        | Egy PO-tételre adott ígért mennyiség és dátum, vagy kifejezett elutasítás.                 | Nem PO-tétel-módosítás és nem áruátvételi sor.                  |
| hatályos Acknowledgement             | A felülírási lánc egyetlen olyan aktuális eleme, amelyet még nem váltott fel újabb válasz. | Nem törli és nem teszi valótlanná a korábbi történeti választ.  |
| vevő által kért szállítási alapdátum | A PO fejléc `expected_delivery_date` értékének a válaszhoz rögzített történeti másolata.   | Nem Supplier-ígéret, átvételi dátum vagy automatikus MRP-dátum. |

## Üzleti példa

Egy Purchase Order 1000 darabot kér szeptember 20-i szállítással. Az első
Dispatch-kísérlet sikertelen, ezért a rendszer megőrzi a hibát és annak okát. A
második Dispatch-kísérlet sikeres; ez sem írja át és nem törli az első
próbálkozást.

A Supplier ezután 900 darabot igazol vissza szeptember 22-re. A rendszer ezt
`accepted_with_changes` állapotú Acknowledgementként rögzíti, mert mind a
mennyiség, mind a dátum eltér a vevői igénytől. Az eltérés utánkövetést és
újratervezést igényel, de nem módosítja automatikusan a Purchase Ordert vagy az
MRP számítási alapját.

Később kiderül, hogy a Supplier helyes ígérete 1000 darab szeptember 21-re. A
javítás új, teljes Acknowledgementként kerül a történetbe, amely közvetlenül az
előző választ váltja fel. A korábbi 900 darabos, szeptember 22-i válasz
megmarad. Az aktuális nézet a javított választ mutatja hatályosként. Ez továbbra
is `accepted_with_changes`, és utánkövetést, valamint újratervezést igényel,
mert a dátum egy nappal későbbi a kért szeptember 20-nál. A teljes előzmény
közben változatlanul ellenőrizhető.

## Az Acknowledgement üzleti értelmezése

### Értékelési kör és hiányzó tételek

Minden válaszhoz történetileg rögzíteni kell az összes akkor érvényes, nem
`cancelled` PO-tételt. Ha ezek közül valamelyikhez nem érkezik tételválasz, az
`missing`: nem tekinthető sem elfogadottnak, sem elutasítottnak. Egy javítás
mindig teljes új válasz, ezért nem örökli a korábbi változat tételsorait.

### Státusz és figyelmeztető jelzők

A válasz csak akkor `accepted`, ha minden érintett tétel szerepel, minden sor
elfogadott, és minden ígért mennyiség, valamint dátum pontosan egyezik. Csak a
minden tételre kiterjedő, kifejezett elutasítás `rejected`; minden más érvényes
eset `accepted_with_changes`. A rendszer a tételadatokból számítja a
`requires_follow_up` és `requires_replanning` jelzőket, a felhasználó nem
választhatja meg őket.

### Előzmények és hatályos válasz

A Dispatch-kísérletek és az Acknowledgementek korábbi rekordjai mindig
megmaradnak. A legutóbbi sikertelen újraküldés nem törli a korábbi sikeres
Dispatch-et. A Supplier javított válasza pedig új, teljes rekordként közvetlenül
az addig hatályos választ váltja fel; egy PO-hoz a láncban egyszerre csak egy
hatályos Acknowledgement tartozhat.

### Hatás más üzleti folyamatokra

A Dispatch és az Acknowledgement nem írja át a Purchase Ordert, nem vesz át
árut, nem módosít készletet, és nem hoz létre számlát vagy fizetést. A Supplier
ígérete V1-ben figyelmeztető beszerzési információ, nem automatikus MRP-,
netting- vagy pegging-bemenet.

## Meglévő rendszerkorlátok

1. A `PurchaseOrderStatus` jelenleg `Draft`, `Ordered`, `PartiallyReceived`,
   `Received` és `Cancelled` értékeket tartalmaz. Ez az enum egyszerre írja le a
   belső rendelési, áruátvételi és MRP-életciklust.
2. A `PurchaseOrderService::approve()` a Draft PO-t `Ordered` állapotba teszi,
   és ekkor állítja be az `ordered_at` értéket. Nem történik külső kommunikáció.
3. A `PurchaseOrderItem` kizárólag ordered és received mennyiséget, valamint
   áruátvételi státuszt tárol. A Supplier által ígért mennyiség és dátum nincs
   benne.
4. A buyer delivery baseline csak a PO header
   `PurchaseOrder.expected_delivery_date` mezőjén létezik. PO item-szintű
   requested delivery date nincs.
5. A 0015-tel létrehozott PO változatlan történeti másolatokat tart a
   Supplierről, az Itemről, az ItemSupplier kapcsolatról, valamint az egység-,
   átváltási-, ár- és utánpótlási adatokról. Régi vagy kézzel létrehozott PO-ból
   ezek egy része hiányozhat.
6. A Supplier törzs egy általános e-mail-címet, telefonszámot és címet tárol.
   Nincs megnevezett kapcsolattartó, több lehetséges célcím, Supplier Portal
   vagy EDI-végpont modell.
7. A Goods Receipt külön üzleti egység. Könyvelésekor Stock Movementet hoz
   létre, frissíti az átvett mennyiséget és a PO áruátvételi státuszát.
8. Nincs beszerzési levelező, Supplier-értesítés, Supplier Portal vagy EDI
   infrastruktúra. A Laravel felhasználói e-mail-ellenőrzése nem használható
   Purchase Order Dispatch-mechanizmusként.
9. Nincs általános idempotenciakulcs-kezelés. A meglévő kritikus folyamatok
   sorzárolást, ismételt státuszellenőrzést és adatbázis-egyediségi korlátot
   használnak.
10. A meglévő PO `close()` viselkedés `Received` státuszt állít Goods Receipt
    létrehozása nélkül. Ennek rendezése nem a 0016 feladata.
11. A meglévő PO-módosítási folyamat az életciklushoz kötött változtathatósági
    védelem nélkül módosíthatja a Suppliert és a fejléc
    `expected_delivery_date` értékét. A 0016 ezt a korábbi viselkedést nem
    tervezi át. A Dispatch és az Acknowledgement összehasonlítási alapjait ezért
    saját történeti másolatukban kell megőrizni.

## Adatmodell és megőrzött üzleti tények

### PurchaseOrderDispatch

A `PurchaseOrderDispatch` egyetlen, már lezárt Dispatch-kísérletet rögzít. A
rekord a létrehozása után felhasználói folyamatból nem módosítható. Legalább a
következő üzleti tényeket őrzi:

- `purchase_order_id`;
- PO-n belüli monoton `dispatch_sequence`;
- kötelező `idempotency_key`;
- szerver által képzett `request_fingerprint`;
- nullable `previous_dispatch_id` a második és későbbi próbálkozások lineáris
  láncához;
- `channel`: `manual`, `email` vagy `other`;
- Supplier identity snapshot: `supplier_code_snapshot`,
  `supplier_name_snapshot`;
- recipient snapshot: `recipient_name`, `recipient_email`,
  `recipient_reference`;
- `attempted_at`;
- siker esetén `dispatched_at`;
- `status`: `succeeded` vagy `failed`;
- failed esetben kötelező `failure_reason`;
- ismételt kísérletnél kötelező `redispatch_reason`;
- nullable `buyer_requested_delivery_date_snapshot`;
- `initiated_by` authenticated User;
- nullable `notes`;
- technikai timestampok.

V1-ben nincs `pending` vagy `cancelled` Dispatch-állapot. Mivel a rendszer nem
végez háttérben automatikus küldést, a rekord csak ismert eredménnyel jön létre.
Egy tervezett, de meg nem kísérelt küldés még nem számít Dispatchnek.

### SupplierAcknowledgement

A `SupplierAcknowledgement` a Supplier válaszának teljes, önmagában is
értelmezhető fejlécmásolata:

- `purchase_order_id`;
- nullable `purchase_order_dispatch_id`;
- PO-n belüli monoton `acknowledgement_sequence`;
- kötelező `idempotency_key`;
- szerver által képzett `response_fingerprint`;
- nullable `supersedes_acknowledgement_id`;
- korrekciónál kötelező `correction_reason`;
- `source`: `email`, `phone`, `manual` vagy `other`;
- nullable `supplier_reference`;
- `acknowledgement_received_at`;
- `acknowledged_by_name`, `acknowledged_by_email`;
- számított `status`: `accepted`, `accepted_with_changes` vagy `rejected`;
- számított `requires_follow_up` és `requires_replanning`;
- `recorded_by` authenticated User;
- nullable `notes`;
- technikai timestampok.

A fejléc `status` értékét és a két figyelmeztető jelzőt a Service számítja ki a
tételsorok történeti adataiból, majd eltárolja. Ezeket a kérés küldője nem
választhatja meg, és ugyanazokból az adatokból mindig ugyanannak az eredménynek
kell származnia.

### SupplierAcknowledgementItem

Minden sor egy konkrét `PurchaseOrderItem` tételre adott választ rögzít:

- `supplier_acknowledgement_id`;
- `purchase_order_item_id`;
- `line_status`: `accepted` vagy `rejected`;
- nullable `promised_quantity` három tizedes Item base unitban;
- nullable `promised_delivery_date`;
- `ordered_quantity_snapshot`;
- `unit_snapshot`;
- nullable `buyer_requested_delivery_date_snapshot`;
- számított `quantity_variance`: `matched`, `reduced`, `increased` vagy
  `rejected`;
- számított nullable `quantity_variance_amount` három tizedessel;
- számított `delivery_date_variance`: `matched`, `earlier`, `later`,
  `not_confirmed`, `no_buyer_baseline` vagy `rejected`;
- nullable `notes`;
- technikai timestampok.

Az Acknowledgement tételsora teljes történeti másolat, nem csak az előző
válaszhoz képest megváltozott adat. Egy javító Acknowledgement minden jelenleg
érvényesnek szánt tételválaszt ismét, új rekordokban rögzít.

### SupplierAcknowledgementScopeItem

Minden Acknowledgementhez rögzíteni kell, hogy a válasz felvételekor mely nem
`cancelled` PO-tételekre várt választ a rendszer. Ezt a csak új rekordokkal
bővíthető történeti kört a `SupplierAcknowledgementScopeItem` tárolja. Ez nem
Supplier-válasz, és nem üres helykitöltő tételsor.

Egy Acknowledgement-tételsor csak a saját történeti körében szereplő PO-tételre
hivatkozhat. Egy tétel akkor `missing`, ha szerepel ebben a körben, de ugyanahhoz
az Acknowledgementhez nincs `SupplierAcknowledgementItem` sora. Emiatt későbbi
PO-módosítás nem változtathatja meg visszamenőleg, mely tétel számított
hiányzónak. A tárolt fejlécstátuszt és figyelmeztető jelzőket sem szabad az
aktuális PO-tételek alapján újraszámítani.

## Életciklusok és számított állapotok

A Purchase Order meglévő életciklusa és átmeneti szabályai változatlanok. A fő
áruátvételi útvonal és a már létező lezárt enumérték:

```text
Draft → Ordered ───────────────────→ Received
          └→ PartiallyReceived ────→ Received
Cancelled (meglévő lezárt állapot; a 0016 nem vezet be új átmenetet)
```

A Dispatch állapota ettől függetlenül, a kísérletekből számítható:

```text
no dispatch attempts
→ last attempt failed
→ successfully dispatched
→ successfully redispatched
```

Sikeres Dispatch után egy későbbi sikertelen újraküldés nem teszi meg nem
történtté az előző sikert. Az olvasási modell ezért külön adja vissza a
legutóbbi kísérletet és a legutóbbi sikeres Dispatch-et.

Az Acknowledgement állapota szintén külön számítható:

```text
no acknowledgement
→ accepted
→ accepted_with_changes
→ rejected
```

Aktuális állapotként mindig a hatályos Acknowledgement státusza jelenik meg. A
korábbi válaszok az előzmények között továbbra is láthatók.

## Mindig érvényes üzleti szabályok

1. Dispatch létrehozása nem módosít `PurchaseOrder.status` vagy
   `PurchaseOrder.ordered_at` értéket.
2. Acknowledgement létrehozása vagy supersessionje nem módosít:
    - Purchase Order vagy Purchase Order Item ordered quantityt;
    - `PurchaseOrderItem.received_quantity` értéket;
    - Purchase Order Item receipt státuszt;
    - `PurchaseOrder.expected_delivery_date` értéket;
    - Purchase Order receiving státuszt.
3. Dispatch, acknowledgement és acknowledgement line nem szerkeszthető és nem
   törölhető felhasználói workflow-ból.
4. Egy acknowledgement line pontosan ugyanahhoz a PO-hoz tartozó, nem cancelled
   PO itemre hivatkozhat, mint a headere.
5. Egy acknowledgementen belül ugyanaz a PO item legfeljebb egyszer szerepel.
6. Egy line ordered baseline-ja, unitja és buyer date baseline-ja a rögzítéskor
   snapshotolt; későbbi PO-változás nem számolja újra a történeti variance-et.
7. Mennyiség-összehasonlítás float nélkül, exact integer-thousandths
   reprezentációval történik.
8. Negatív promised quantity tilos. Accepted line promised quantityje pozitív.
9. Rejected line esetén a rejectiont a `line_status` bizonyítja; a
   `promised_quantity` és `promised_delivery_date` null. A nulla quantity nem
   helyettesítheti a rejection státuszt.
10. Egy PO supersession-láncának legfeljebb egy effective acknowledgementje
    lehet.
11. A korábbi dispatch és acknowledgement rekordok későbbi master-data vagy
    workflow-változás után is megmaradnak.
12. Dispatch vagy acknowledgement önmagában nem hoz létre Goods Receiptet,
    Stock Movementet, Stock Balance változást, invoice-t vagy paymentet.
13. Acknowledgement csak ugyanahhoz a PO-hoz tartozó, `succeeded` dispatchhez
    kapcsolható. Failed attempt nem lehet Supplier response előzménye; ilyen
    esetben az acknowledgement dispatch nélkül, explicit source és attribution
    mellett rögzítendő.
14. A létrehozáskor az `initiated_by` és `recorded_by` kötelező authenticated
    User. A későbbi user-törlés miatti `NULL ON DELETE` referential változás az
    egyetlen megengedett kivétel; nem jogosít user-facing record-módosításra.
15. Minden acknowledgement scope-ja a létrehozáskor snapshotolt. A status,
    attention flag és missing-line read model kizárólag ebből a történeti
    scope-ból és ugyanazon acknowledgement response line-jaiból származik.

## A Dispatch rögzítésének szabályai

### Mikor rögzíthető új Dispatch-kísérlet?

Új Dispatch-kísérlet csak akkor rögzíthető, ha:

- a PO státusza `Ordered` vagy `PartiallyReceived`;
- a PO Supplier kapcsolata létezik és a Supplier aktív;
- a PO-n legalább egy nem `cancelled` tétel van, és minden ilyen tétel rendelt
  mennyisége pozitív;
- a csatorna és a címzett megfelel az alább meghatározott szabályoknak;
- a bejelentkezett felhasználó rendelkezik `procurement.view` és
  `purchase-orders.dispatch` jogosultsággal.

`Draft`, `Received` és `Cancelled` PO-hoz nem rögzíthető Dispatch. A meglévő
`close()` jelentését a 0016 nem változtatja meg: az általa `Received` állapotba
tett PO szintén nem jogosult új Dispatchre.

### Régi és kézzel létrehozott Purchase Orderek

A 0015 által előírt történeti másolatok részleges hiánya önmagában nem tiltja a
Dispatch-et. Régi vagy kézzel létrehozott PO-hoz is rögzíthető Dispatch, ha a
fenti életciklus-, Supplier- és tételszabályok teljesülnek. A Service nem talál
ki, nem tölt vissza, és nem állít elő utólag hiányzó 0015-ös történeti adatot az
aktuális törzsadatokból.

A Dispatch külön történeti másolatban őrzi a rögzítéskor a PO-hoz kapcsolt
Supplier kódját és nevét, valamint a tényleges címzettet. A PO meglévő, 0015
szerinti Supplier-másolata nem írható felül, és a hiánya nem pótolható
visszamenőleg. A Supplier aktuális e-mail-címe csak felületi előtöltés lehet; a
backend a felhasználó által ténylegesen jóváhagyott címzettadatot menti. Így
később is ellenőrizhető, hogy a Dispatch pillanatában mely Supplier és címzett
felé rögzítették a rendelést.

### Csatorna és címzett

V1 channel értékek:

- `manual`: személyes, nyomtatott, telefonon koordinált vagy más manuális
  átadás rögzítése;
- `email`: a felhasználó által a rendszeren kívül elküldött email rögzítése;
- `other`: más külső módszer, amelyet a megjegyzés és a hivatkozás magyaráz.

`email` esetén érvényes `recipient_email` kötelező. `manual` esetén legalább
egy címzettazonosító (`recipient_name`, `recipient_email` vagy
`recipient_reference`) kötelező. `other` esetén `recipient_reference` és
`notes` kötelező.

A `portal` és `edi` V1-ben nem választható, mert nincs hozzá működő
infrastruktúra. Ezek későbbi enum-bővítési pontok, nem jelenlegi képességek.

### Mit bizonyít a Dispatch V1-ben?

A rendszer V1-ben nem küld e-mailt, nem tölt fel Supplier Portalra, és nem ad át
EDI-üzenetet. A sikeres Dispatch-rekord a bejelentkezett felhasználó ellenőrizhető
állítása arról, hogy a dokumentumot a megadott külső csatornán átadta.
A sikertelen rekord ugyanígy a felhasználó ellenőrizhető állítása arról, hogy a külső
átadást megkísérelte, de az a rögzített okból nem fejeződött be sikeresen.

A felület és az auditnapló nem használhat olyan szöveget, amely a rendszer
általi fizikai kézbesítést állít. Nincs technikai kézbesítési nyugta,
e-mail-szolgáltatói üzenetazonosító vagy külső kézbesítési garancia.

### Ismételt próbálkozás és szándékos újraküldés

Több kísérlet engedett:

- failed attempt után retry rögzíthető;
- successful attempt után szándékos redispatch is rögzíthető;
- minden új kísérlet új, nem módosítható rekordot és következő sorszámot kap;
- a második és későbbi kísérlet `previous_dispatch_id` értéke az előző kísérlet,
  és `redispatch_reason` kötelező;
- failed attemptnél `failure_reason` kötelező, `dispatched_at` null;
- succeeded attemptnél `dispatched_at` kötelező és nem korábbi az
  `attempted_at` értéknél;
- egyik üzleti timestamp sem lehet jövőbeli;
- succeeded attemptnél `failure_reason` null, első attemptnél
  `previous_dispatch_id` és `redispatch_reason` null.

A Service a PO-sor zárolása alatt a következő sorszámot `max(sequence) + 1`
értékként osztja ki. A második és későbbi kísérletnél a
`previous_dispatch_id`-nek ugyanazon PO legutóbbi kísérletére kell mutatnia. Az
elavult vagy másik PO-hoz tartozó előzményhivatkozás kifejezett ütközési hiba.
A sikertelenség és az újraküldés indoka a szélső szóközök eltávolítása után nem
lehet üres.

Az előző sikeres Dispatch soha nem válik sikertelenné vagy felülírttá. A
későbbi kísérlet csak új tényt ad az előzményekhez.

## A Supplier Acknowledgement rögzítésének szabályai

### Mikor rögzíthető válasz?

Első acknowledgement `Ordered` vagy `PartiallyReceived` PO-hoz rögzíthető.
`Draft`, `Received` vagy `Cancelled` PO-hoz új, előzmény nélküli
acknowledgement nem rögzíthető. A scope nem lehet üres, és minden scope item
ordered quantityjének pozitívnak kell lennie.

A meglévő hatályos Acknowledgement javítása lezárt PO-státusz után is
megengedett, mert ez történeti tényt helyesbít, nem új végrehajtást indít.
Ilyenkor kötelező az előző válaszra mutató felülírási kapcsolat és a
`correction_reason`. A javítás nem módosíthat PO-, MRP-, áruátvételi vagy
készletállapotot.

Acknowledgement sikeres Dispatch nélkül is rögzíthető. Ilyen lehet például a
telefonon, a Supplier kezdeményezésére vagy egy korábbi folyamatból érkező
válasz.
Minden acknowledgementnél kötelező:

- `source`;
- `acknowledgement_received_at`;
- `recorded_by` authenticated User;
- a következő attribution mezők közül legalább egy:
  `supplier_reference`, `acknowledged_by_name`, `acknowledged_by_email`.

Dispatch nélküli rögzítésnél ezen felül kötelező a `notes`, amely röviden
megmagyarázza, hogyan érkezett a válasz. A `source` a Supplier válaszának bejövő
csatornáját jelenti: `email`, `phone`, `manual` esetén papír/személyes átadás,
`other` esetén pedig notes-ban megnevezett más forrás. Nem a rendszer
adatbeviteli módját jelöli; V1-ben minden Acknowledgementet bejelentkezett User
rögzít.

Ha dispatch kapcsolat szerepel, annak ugyanahhoz a PO-hoz kell tartoznia és
`succeeded` állapotúnak kell lennie.
Acknowledgement rögzíthető időközben inaktív vagy soft-deleted Supplierhez
tartozó, még nyitott PO-ra is, mert beérkező történeti tényt őriz; új dispatch
ugyanerre az inaktív Supplierre továbbra is tiltott.

Az `acknowledgement_received_at` nem lehet jövőbeli. Kapcsolt dispatch esetén
nem lehet korábbi annak kötelező `dispatched_at` értékénél. Dispatch nélküli
esetben, ha az `ordered_at` ismert, nem lehet annál korábbi. Legacy PO hiányzó
`ordered_at` értéke nem kap kitalált fallbacket.

### Összesített státusz és figyelmeztető jelzők

Az értékelés köre a PO Acknowledgement-rögzítéskor létező összes nem
`cancelled` tételének ugyanabban a tranzakcióban létrehozott történeti másolata.

- `accepted`: minden scope-beli line szerepel, mind `accepted`, minden quantity
  matched, és minden delivery date matched;
- `rejected`: minden scope-beli line szerepel és mind `rejected`;
- `accepted_with_changes`: minden más valid válasz, beleértve a mixed,
  partial, missing-line, quantity/date variance és date-not-confirmed esetet.

`requires_follow_up = true`, ha a status nem `accepted`, bármely line hiányzik,
rejected, quantity/date variance van, vagy a promised date nem hasonlítható
össze.

`requires_replanning = true`, ha bármely line missing vagy rejected, a
promised quantity kisebb az ordered quantitynél, illetve a promised delivery
date későbbi, nincs megerősítve, vagy buyer baseline hiányában nem
hasonlítható. Pusztán increased quantity vagy earlier date follow-upot
igényelhet, de önmagában nem teszi kötelezővé a replanninget.

A két jelző egymástól független logikai eredmény, ezért egyszerre is lehetnek
`true` értékűek. A fejléc státuszához hasonlóan mindkettőt a történeti
értékelési körből és a tételválaszokból kell egyértelműen kiszámítani és
eltárolni. A kérés nem választhatja meg őket, a felület pedig nem használhat
eltérő számítási szabályt.

Ezek figyelmeztető jelzések. A 0016 nem indít automatikus utánkövetési feladatot
vagy MRP-újraszámítást.

## Tételszintű válaszok és hiányzó tételek

### Kifejezett elutasítás

Az elutasítást kifejezetten a `line_status = rejected` érték rögzíti. Elutasított
sorhoz nem tartozhat ígért mennyiség vagy szállítási dátum. A nulla ígért
mennyiség nem helyettesíti az elutasítási státuszt, és elfogadott soron nem
érvényes.

Vegyes accepted és rejected line-ok header statusa
`accepted_with_changes`. Header `rejected` csak teljes, minden aktív PO line-ra
kiterjedő explicit rejection esetén áll elő.

### Részleges válasz és hiányzó tétel

Részleges Acknowledgement megengedett, ha legalább egy érvényes tételválasz
szerepel. A kimaradó PO-tétel:

- `missing`, vagyis még nincs rá supplier response;
- nem implicit accepted;
- nem implicit rejected;
- nem kap nulla promised quantityt;
- `accepted_with_changes`, `requires_follow_up = true` és
  `requires_replanning = true` eredményt okoz.

Az aktuális nézet a hiányzó tételeket a hatályos Acknowledgement történeti
értékelési köre és tényleges tételválaszai közötti különbségből állapítja meg.
Nem készül külön üres válaszsor; a körhöz tartozást jelző rekord önmagában nem
állítja, hogy a Supplier válaszolt.

A korábbit felváltó Acknowledgement nem örököl tételsort az elődjéből. Ha egy
korábban megválaszolt PO-tétel az új verzió értékelési körében szerepel, de az
új válaszsorok közül kimarad, akkor az új hatályos verzióban `missing`. Ezt
jelenti kötelezően az a szabály, hogy minden válasz teljes történeti másolat,
nem csak a változások listája.

### Ígért mennyiség és eltérés

Az accepted line `promised_quantity` értéke Supplier által megerősített
mennyiség, a PO item base unitjában, pontosan három tizedes pontossággal.
Implicit unit conversion nincs.

Az exact integer-thousandths összehasonlítás eredménye:

```text
promised == ordered snapshot → matched
promised <  ordered snapshot → reduced
promised >  ordered snapshot → increased
rejected line                → rejected
```

A comparison baseline az acknowledgement item
`ordered_quantity_snapshot` mezője. Az eredeti
`PurchaseOrderItem.ordered_quantity` nem változik.

Az accepted line numerikus `quantity_variance_amount` értéke szintén
perzisztált evaluation result:

```text
quantity_variance_amount = promised_quantity - ordered_quantity_snapshot
negative → reduced / under-confirmation
zero     → matched
positive → increased / over-confirmation
```

Rejected line-on a `quantity_variance_amount` null. A kategória és az összeg
ugyanabból az exact integer-thousandths számításból készül; nem térhetnek el.

### Ígért szállítási dátum és a fejléc alapdátuma

Mivel a PO itemnek nincs requested delivery date mezője, V1-ben minden line a
PO header buyer-requested date-jéhez hasonlít.

- Kapcsolt dispatch esetén a line baseline-ja a dispatch
  `buyer_requested_delivery_date_snapshot` értéke.
- Dispatch nélküli acknowledgementnél a line baseline-ja a PO aktuális
  `expected_delivery_date` értéke a rögzítés pillanatában.
- A kiválasztott érték minden acknowledgement line-on
  `buyer_requested_delivery_date_snapshot` mezőbe kerül.

Ez megőrzi, hogy a Supplier válaszát mely buyer baseline-hoz értékeltük, akkor
is, ha a PO header dátuma később változik. A 0016 nem nevezi át és nem írja át
a meglévő `PurchaseOrder.expected_delivery_date` mezőt.

A delivery variance kiértékelési precedenciája:

```text
1. rejected line                   → rejected
2. promised date is null           → not_confirmed
3. buyer baseline is null          → no_buyer_baseline
4. promised date == buyer baseline → matched
5. promised date <  buyer baseline → earlier
6. promised date >  buyer baseline → later
```

Ha mind a promised date, mind a baseline null, az eredmény `not_confirmed`, nem
`matched`, mert a Supplier nem tett dátumígéretet.

## Ismételt kérések és idempotencia

Az idempotencia itt azt jelenti, hogy ugyanannak a kérésnek a véletlen
megismétlése nem hoz létre még egy üzleti rekordot. A szándékosan megváltoztatott
újraküldés vagy javítás ettől eltérő eset, ezért új rekordot hozhat létre.

### Dispatch

Minden Dispatch-kérés kötelező, kliens által generált `idempotency_key` értéket
kap. A kulcs a szélső szóközök eltávolítása után nem üres, legfeljebb 100 ASCII
karakteres, kis- és nagybetűérzékeny, a rendszer számára belső jelentés nélküli
azonosító. A `purchase_order_id + idempotency_key` pár egyediségét bináris
karakter-összehasonlítást használó adatbázis-korlát védi.

A backend SHA-256 `request_fingerprint` ujjlenyomatot képez a következő,
egységesen rendezett üzleti adattartalomból: `status`, `channel`, a szélső
szóközöktől megtisztított címzettmezők, az `attempted_at` és `dispatched_at` UTC
idő szerint másodpercre kerekítve, a megtisztított sikertelenségi és újraküldési
indok, a megtisztított `notes`, valamint a kliens által megadott
`previous_dispatch_id`. Az üres szöveg `null` értékké alakul, az e-mail-cím
kisbetűs, a JSON kulcssorrendje rögzített. Nem része a sorszám, a szerver által
rögzített Supplier- és vevőidátum-másolat, a végrehajtó, az idempotenciakulcs és
a technikai időbélyeg. Az ujjlenyomat kisbetűs hexadecimális `char(64)`.

A Service zárolja a PO adatbázissorát, majd:

- azonos kulcs és egységesített adattartalom esetén a meglévő rekordot adja
  vissza, új audit nélkül;
- azonos kulcs és eltérő adattartalom esetén kifejezett idempotenciaütközési
  hibát ad;
- új kulcs esetén új kísérlet készül; ha már volt kísérlet, ezt csak
  `previous_dispatch_id` és indok mellett fogadja el szándékos újraküldésként.

Az idempotenciakulcs keresése és az egységesített adattartalom ütközésének
ellenőrzése megelőzi az új írásra vonatkozó életciklus-ellenőrzést. Ezért egy
pontos ismétlés a PO későbbi lezárt állapotában is a meglévő rekordot adja
vissza. Új kulcs csak az aktuális jogosultsági és életciklusfeltételek
teljesülésekor hozhat létre új kísérletet.

A `purchase_order_id + dispatch_sequence` unique constraint a sequence
concurrency backstop. A `(previous_dispatch_id)` unique constraint lineáris
attempt-láncot véd.

### Supplier Acknowledgement

Az Acknowledgement `idempotency_key` mezőjére ugyanaz a szabály vonatkozik,
mint a Dispatch kulcsára: a szélső szóközök eltávolítása után 1–100 ASCII
karakteres, kis- és nagybetűérzékeny, belső jelentés nélküli azonosító, bináris
adatbázis-karakter-összehasonlítással.

Az acknowledgementet két független DB-backed guard védi:

1. `purchase_order_id + idempotency_key` unique constraint az ismételt HTTP
   submission ellen;
2. `purchase_order_id + response_fingerprint` unique constraint ugyanazon
   kanonikus Supplier response új keyvel történő ismételt rögzítése ellen.

A fingerprintet a backend készíti a `purchase_order_dispatch_id`, supplier
attribution, source, reference, UTC másodperc pontosságú
`acknowledgement_received_at`, `supersedes_acknowledgement_id`, rendezett scope
PO-item ID-k és `purchase_order_item_id` szerint rendezett line facts alapján.
A line facts pontosan a PO item ID, line status, promised quantity, promised
date és trimelt line notes; a szerver által számított baseline és variance nem
input. Stringek trimeltek, üres string null, email lowercase, dátum ISO
`YYYY-MM-DD`, mennyiség fix három tizedes string. A record ID, `recorded_by`,
header notes, correction reason, request key és technikai
`created_at`/`updated_at` timestamp nem része. A canonical JSON SHA-256
fingerprintje lowercase hex `char(64)`. Így a Supplier response business
timestampjének vagy attributionjének explicit korrekciója új verzió lehet,
miközben ugyanazon első response vagy correction változatlan újraküldése
duplicate marad.

Azonos key/fingerprint és azonos canonical response idempotens replay, új
record és audit nélkül. Conflict vagy tiltott parallel response explicit
validation hiba; nem alakul csendes successé.

Az acknowledgement idempotency lookup ugyanúgy megelőzi az új írás lifecycle-
és supersession-validációját. Exact replay a PO vagy az acknowledgement-chain
későbbi állapotától függetlenül visszaadja az eredeti rekordot; csak új canonical
response igényel aktuális eligibilityt vagy az effective predecessor explicit
supersedelését.

## Javítás, felülírás és a hatályos Acknowledgement

Az Acknowledgement javítása nem írhatja át a korábbi választ. A szabályok:

1. Az első response `supersedes_acknowledgement_id = null`.
2. Ha már van effective acknowledgement, minden új response csak annak
   explicit superseding correctionje lehet.
3. A correction ugyanahhoz a PO-hoz tartozik, a közvetlen effective elődre
   hivatkozik, és kötelező `correction_reason` értéket tartalmaz.
4. A correction teljes response snapshot, nem line-delta.
5. Accepted, accepted-with-changes és rejected acknowledgement egyaránt
   supersede-elhető.
6. Korábbi rekord státusza és adata nem módosul; superseded állapota a rá mutató
   utódból származik.
7. V1-ben nincs acknowledgement `cancelled`, `voided` vagy `invalid` state. Egy
   tévesen rögzített, de valós Supplier response csak helyes teljes response-zal
   supersedelhető; nem létezett Supplier response adminisztratív eltávolítása
   user workflow-ból nem támogatott és nem alakítható át csendesen "no
   acknowledgement" állapottá.

Hatályos az a PO-hoz tartozó Acknowledgement, amelyre nem mutat másik
Acknowledgement `supersedes_acknowledgement_id` mezője. A Service a PO-sor
zárolása mellett csak az aktuális hatályos rekord felülírását engedi. A
`supersedes_acknowledgement_id` unique constraint megakadályozza, hogy egy
elődnek két utóda legyen. PO-n belül a sequence unique és monoton.

Nem készül módosítható `is_current`, `is_effective` vagy `superseded_at` jelző.
A hatályos állapotot mindig a történeti láncból kell meghatározni, így nem jön
létre második, külön szinkronban tartandó aktuálisállapot-jelző.

## Jogosultságok

Két új, üzletileg elkülönülő jogosultság szükséges:

- `purchase-orders.dispatch`;
- `purchase-orders.acknowledge`.

A Purchase Order policy külön `dispatch` és `acknowledge` abilityt ad. Mindkét
abilityhez egyszerre szükséges a meglévő `procurement.view` és a saját action
permission; az action permission önmagában nem ad PO-láthatóságot. Első
acknowledgement és minden correction/supersession ugyanazt a
`purchase-orders.acknowledge` permissiont használja, külön correction
permission nincs. A controller vagy FormRequest authorization a service hívása
előtt kötelező, de a service lifecycle- és domain-validációja közvetlen
hívásnál is érvényes.

A `procurement.update`, `procurement.approve` és `purchase-orders.generate`
nem helyettesíti automatikusan az új permissionöket. A későbbi permission
seeder a procurement-manager szerepkörhöz explicit módon rendeli őket. A
frontend permission-aware action visibility csak UX, nem security boundary.

## Ellenőrizhetőség és auditnapló

Az új üzleti rekord és az aktivitásnapló egymást kiegészíti. Az üzleti rekord a
teljes tény elsődleges forrása; az aktivitásnapló azt teszi könnyen követhetővé,
hogy ki és milyen műveletet végzett.

Események:

- `purchase_order_dispatched` sikeres dispatch recordnál;
- `purchase_order_dispatch_failed` failed attemptnél;
- `supplier_acknowledgement_recorded` első acknowledgementnél;
- `supplier_acknowledgement_superseded` correctionnél.

Az auditbejegyzés ugyanabban az adatbázis-tranzakcióban készül, mint az üzleti
rekordok. Ha az auditálás sikertelen, minden írást vissza kell görgetni. Egy
idempotens ismétlés nem készít új auditbejegyzést.

Az activity subject az új Dispatch vagy Acknowledgement record. Metadata:
Purchase Order ID, sequence, outcome/status, kapcsolt record ID-k, item count,
missing/rejected/variance count és attention flag-ek. Különböző unitok
mennyisége nem aggregálható. Recipient email, teljes notes és teljes line
payload nem duplikálható activity metadata-ba.

## Kapcsolat a Purchase Orderrel, az MRP-vel és a Goods Receipttel

### Purchase Order

A PO marad a vevői rendelés elsődleges forrása. A Dispatch és az
Acknowledgement kapcsolódó adatként olvasható róla, de nem írja át a
dokumentumot. A PO részletes olvasási modellje külön adja vissza:

- dispatch history;
- latest attempt;
- latest successful dispatch;
- effective acknowledgement;
- acknowledgement history;
- line variance és attention summary.

### MRP és pegging

A 0009 jelenleg `Ordered` és `PartiallyReceived` PO item fennmaradó ordered
quantityjét, valamint a PO `expected_delivery_date` értékét kezeli firm
incoming supplyként. A 0016 ezt nem változtatja meg.

Supplier promised quantity/date, acknowledgement status és
`requires_replanning` V1-ben látható procurement exception, de nem lesz
automatikus netting vagy pegging input. A promised supply MRP authority-vá
emelése külön ADR-t igényelne a partial/missing/rejected response, freshness,
supersession és fallback policy teljes meghatározásával.

### Goods Receipt és inventory

Supplier Acknowledgement nem proof of delivery. Nem hoz létre Goods Receiptet,
nem postol receiptet, nem módosít received quantityt, PO item receipt státuszt,
Stock Balance-t, Stock Movementet vagy Stock Reservationt.

A meglévő Goods Receipt és PO close viselkedés változatlan. A 0016 nem javítja
vagy értelmezi újra a manual close → `Received` legacy szemantikát.

### Invoice és financial settlement

A repositoryban nincs invoice, accounts payable, payment vagy financial
settlement workflow. Dispatch és acknowledgement nem hoz létre pénzügyi
kötelezettség-könyvelést, invoice match-et, payment state-et vagy settlement
recordot.

## A V1 határai és kifejezetten kizárt céljai

A 0016 V1 nem valósítja meg:

- fizikai SMTP/email küldést vagy delivery trackinget;
- Supplier Portalt, Supplier authenticationt vagy recipient directoryt;
- EDI-t vagy külső procurement API-t;
- automatikus Supplier-kiválasztást; a Dispatch a PO már létező
  Supplier-kapcsolatát használja;
- Supplier-válasz automatikus létrehozását vagy automatikus elfogadását;
- automatikus PO amendmentet vagy quantity/date átírást;
- PO item-szintű requested delivery date bevezetését;
- automatikus follow-up taskot, escalationt vagy notificationt;
- automatikus MRP/netting/pegging újraszámítást;
- promised quantity/date firm incoming authority-vá emelését;
- Goods Receipt, ASN vagy quality receipt redesignját;
- invoice matchinget, accounts payable-t, paymentet vagy settlementet;
- a meglévő PO approval, cancellation vagy close lifecycle áttervezését;
- általános workflow engine-t vagy event sourcingot.

## Kötelező adatbázisséma a Phase 3 megvalósításhoz

### `purchase_order_dispatches`

Fő oszlopok:

```text
id
purchase_order_id FK RESTRICT
dispatch_sequence unsigned integer
idempotency_key varchar(100) ASCII binary collation
request_fingerprint char(64)
previous_dispatch_id nullable self FK RESTRICT
channel string enum-cast
supplier_code_snapshot nullable string
supplier_name_snapshot string
recipient_name nullable string
recipient_email nullable string
recipient_reference nullable string
attempted_at timestamp
dispatched_at nullable timestamp
status string enum-cast
failure_reason nullable text
redispatch_reason nullable text
buyer_requested_delivery_date_snapshot nullable date
initiated_by nullable users FK NULL ON DELETE
notes nullable text
timestamps
```

Constraint/index minimum:

- unique `(purchase_order_id, dispatch_sequence)`;
- unique `(purchase_order_id, idempotency_key)`;
- unique nullable `previous_dispatch_id`;
- index `(purchase_order_id, status, dispatched_at)` a history/latest success
  olvasáshoz;
- külön redundant FK index nem készül, ha a composite/unique index már lefedi.

Minimum DB checkek:

- `dispatch_sequence >= 1`;
- `status IN (succeeded, failed)` és `channel IN (manual, email, other)`;
- `succeeded` esetén `dispatched_at IS NOT NULL` és
  `failure_reason IS NULL`;
- `failed` esetén `dispatched_at IS NULL` és trim után nem üres
  `failure_reason`;
- sequence 1 esetén predecessor/reason null, sequence > 1 esetén mindkettő
  non-null;
- succeeded esetén `dispatched_at >= attempted_at`;
- email channelnél recipient email, manual channelnél legalább egy recipient
  descriptor, other channelnél recipient reference és notes trim után nem
  üres.

### `supplier_acknowledgements`

Fő oszlopok:

```text
id
purchase_order_id FK RESTRICT
purchase_order_dispatch_id nullable FK RESTRICT
acknowledgement_sequence unsigned integer
idempotency_key varchar(100) ASCII binary collation
response_fingerprint char(64)
supersedes_acknowledgement_id nullable self FK RESTRICT
correction_reason nullable text
source string enum-cast
supplier_reference nullable string
acknowledgement_received_at timestamp
acknowledged_by_name nullable string
acknowledged_by_email nullable string
status string enum-cast
requires_follow_up boolean
requires_replanning boolean
recorded_by nullable users FK NULL ON DELETE
notes nullable text
timestamps
```

Constraint/index minimum:

- unique `(purchase_order_id, acknowledgement_sequence)`;
- unique `(purchase_order_id, idempotency_key)`;
- unique `(purchase_order_id, response_fingerprint)`;
- unique nullable `supersedes_acknowledgement_id`;
- index `(purchase_order_id, acknowledgement_received_at)`;
- index `purchase_order_dispatch_id`, ha más index nem fedi.

Minimum DB checkek:

- `acknowledgement_sequence >= 1`;
- `source IN (email, phone, manual, other)` és
  `status IN (accepted, accepted_with_changes, rejected)`;
- sequence 1 esetén `supersedes_acknowledgement_id` és `correction_reason`
  null;
- sequence > 1 esetén mindkettő non-null, a reason trim után nem üres;
- legalább egy supplier attribution mező trim után nem üres;
- dispatch nélküli acknowledgement notes mezője trim után nem üres.

### `supplier_acknowledgement_items`

Fő oszlopok:

```text
id
supplier_acknowledgement_id FK RESTRICT
purchase_order_item_id FK RESTRICT
line_status string enum-cast
promised_quantity nullable decimal(18,3)
promised_delivery_date nullable date
ordered_quantity_snapshot decimal(18,3)
unit_snapshot string
buyer_requested_delivery_date_snapshot nullable date
quantity_variance string enum-cast
quantity_variance_amount nullable decimal(18,3)
delivery_date_variance string enum-cast
notes nullable text
timestamps
```

Constraint/index minimum:

- unique `(supplier_acknowledgement_id, purchase_order_item_id)`;
- index `purchase_order_item_id`;
- külön `purchase_order_id` nem kerül a line-ra, mert a header és a PO item
  kapcsolatból származik; same-PO invariánst a service tranzakciósan ellenőrzi.

Minimum DB checkek:

- `line_status IN (accepted, rejected)`;
- `quantity_variance IN (matched, reduced, increased, rejected)`;
- `delivery_date_variance IN (matched, earlier, later, not_confirmed,
no_buyer_baseline, rejected)`;
- accepted line promised quantityje non-null és pozitív, variance amountja
  non-null, ordered quantity snapshotja pozitív;
- rejected line promised quantityje, promised date-je és variance amountja
  null, quantity/date variance kategóriája `rejected`.

### `supplier_acknowledgement_scope_items`

Fő oszlopok:

```text
id
supplier_acknowledgement_id FK RESTRICT
purchase_order_item_id FK RESTRICT
timestamps
```

Constraint/index minimum:

- unique `(supplier_acknowledgement_id, purchase_order_item_id)`;
- index `purchase_order_item_id`;
- response line csak ugyanazon acknowledgement scope iteméhez készülhet; ezt a
  service PO row lock és tranzakció mellett ellenőrzi.

Az append-only táblákhoz nem készül user-facing update/delete route és nem
kapnak SoftDeletes traitet. A PO és PO item hard delete-jét a RESTRICT FK védi;
a meglévő soft delete továbbra is történeti elérhetőséget ad. Az `updated_at`
létrehozáskor kitöltött technikai érték, később nem írható; az actor FK-knél
korábban definiált `NULL ON DELETE` az egyetlen DB-vezérelt változás.

## Következmények és vállalt kompromisszumok

Előnyök:

- a PO, a Dispatch, a Supplier ígérete, az áruátvétel és a pénzügyi életciklus
  nem mosódik össze;
- minden sikertelen kísérlet, újraküldés és javítás megmarad;
- az előzményekből egyértelműen meghatározható a legutóbbi kísérlet, a legutóbbi
  siker és a hatályos Acknowledgement;
- a tételszintű eltérés megőrzi a rendelt mennyiséget és a vevői alapdátumot;
- a meglévő 0015-ös történeti másolat, PO-státusz, MRP és Goods Receipt
  viselkedés változatlanul együttműködik az új modellel;
- az adatbázis-egyediségi korlátok a párhuzamos és az ismételt beküldések ellen
  is védenek.

Vállalt kompromisszumok:

- a csak új rekordokkal bővíthető történet több adatbázissort és összetettebb
  részletes olvasási modellt jelent;
- mivel a PO csak fejlécszintű szállítási dátumot tárol, V1-ben minden tételt
  ugyanahhoz a vevői alapdátumhoz kell hasonlítani;
- a válasz ujjlenyomatának egységesítési szabálya a Service nyilvános és stabil
  szerződése, ezért alaposan tesztelt megvalósítást igényel;
- a rendszer V1-ben csak felhasználói állítást auditál a dispatchről, külső
  kézbesítési bizonyítékot nem;
- az Acknowledgement eltérése nem javítja automatikusan az MRP tervet, ezért az
  operatív utánkövetés emberi feladat marad.

## Elutasított alternatívák

- **`Dispatched` és `Acknowledged` PO-státuszok.** Elutasítva, mert a Dispatch,
  az Acknowledgement és az áruátvétel egymástól független dimenzió; egyetlen enum
  elveszítené a kombinált és történeti állapotokat.
- **`ordered_at` átértelmezése dispatch timestampként.** Elutasítva, mert a
  meglévő approval transition írja, külső kommunikáció nélkül.
- **Dispatch mezők közvetlenül a Purchase Orderen.** Elutasítva, mert nem őrizné
  a failed attemptet, redispatchet, recipient historyt és idempotency-határt.
- **Egy módosítható aktuális Acknowledgement-rekord.** Elutasítva, mert a javítás
  felülírná a Supplier korábbi válaszát és az auditelőzményt.
- **Header-only acknowledgement.** Elutasítva, mert multi-item PO-n a Supplier
  eltérő mennyiséget, dátumot vagy rejectiont adhat tételenként.
- **Nulla promised quantity mint rejection.** Elutasítva, mert többértelmű és
  eltünteti az explicit Supplier döntést.
- **Missing line implicit rejection vagy acceptance.** Elutasítva, mert a
  hiányzó válasz bizonytalanság, nem Supplier által közölt döntés.
- **Acknowledgementből automatikus PO-módosítás.** Elutasítva, mert a vevői
  rendelés és a Supplier válasza eltérő tény; a módosítás külön üzleti művelet.
- **Acknowledgementből automatikus receipt vagy inventory posting.** Elutasítva,
  mert Supplier promise nem bizonyít fizikai beérkezést.
- **Acknowledgement promised date visszaírása a PO
  `expected_delivery_date` mezőjébe.** Elutasítva, mert elveszítené a buyer
  baseline-t és megváltoztatná a jelenlegi MRP inputot.
- **Portal/EDI/email sending capability deklarálása V1-ben.** Elutasítva, mert a
  repositoryban nincs működő reusable procurement integration.
- **Általános event sourcing vagy workflow engine.** Elutasítva, mert négy
  explicit append-only tábla kisebb és a jelenlegi architektúrához illeszkedő
  megoldás.

## Nyitott kérdések és követő döntések

A Phase 3 implementációhoz nincs nyitott domain-szemantikai kérdés. A következő
témák szándékosan későbbi ADR-t vagy célzott scope-ot igényelnek:

1. Supplier promised quantity/date mikor és milyen fallbackkel válhat MRP firm
   incoming authority-vá.
2. PO item-szintű buyer requested delivery date és esetleges PO amendment
   lifecycle.
3. Valós email provider, Supplier Portal vagy EDI integration, delivery
   evidence és retry orchestration.
4. Named Supplier contact/destination master-data modell.
5. A legacy PO close → `Received` szemantika és a cancellation workflow külön
   rendezése.
6. Follow-up task, escalation és automatikus replanning orchestration.
