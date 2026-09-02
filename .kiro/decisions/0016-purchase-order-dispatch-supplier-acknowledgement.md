# Purchase Order Dispatch / Supplier Acknowledgement

- **Állapot:** Elfogadva, implementációra vár
- **Dátum:** 2026-09-01
- **Kapcsolódó döntések:** [0001 Stock Movements](0001-stock-movements.md), [0009 Material Requirement Netting](0009-material-requirement-netting.md), [0010 Requirement Pegging](0010-requirement-pegging.md), [0014 Purchase Requisition Execution Readiness](0014-purchase-requisition-execution-readiness.md), [0015 Purchase Order Generation](0015-purchase-order-generation.md)

## Kontextus

A 0015 egy execution-ready, Approved Purchase Requisitionből tranzakciósan,
idempotensen és történeti snapshotokkal hoz létre Draft Purchase Ordert. Ez a
belső execution dokumentum létrejötte; a 0015 explicit módon nem küldi el a
rendelést a Suppliernek, nem rögzít Supplier-visszaigazolást, nem vételez árut,
és nem módosít készletet.

A következő execution-határon a rendszernek külön kell megválaszolnia:

- történt-e tényleges dispatch-kísérlet a Supplier felé;
- sikeres volt-e a kísérlet, mikor, milyen csatornán és mely recipient felé;
- érkezett-e Supplier Acknowledgement;
- a Supplier tételenként milyen mennyiséget és szállítási dátumot ígért;
- a válasz eltér-e a Purchase Order tartalmától;
- szükséges-e operatív follow-up vagy replanning.

A `Purchase Order`, `Dispatch`, `Supplier Acknowledgement`, `Goods Receipt` és
`Invoice / Financial Settlement` eltérő üzleti tény. Egyik sem bizonyítja
automatikusan a másikat.

## Probléma

A jelenlegi Purchase Orderből nem állapítható meg, hogy a dokumentum ténylegesen
elhagyta-e a rendszert, illetve mit igazolt vissza a Supplier. Ha ezeket az
információkat kizárólag a `PurchaseOrder.status`, az `ordered_at` vagy az
`expected_delivery_date` mezőbe olvasztanánk, akkor eltűnne:

- a sikertelen és ismételt dispatch-kísérletek története;
- a dispatch recipient és channel történeti állapota;
- az eredeti rendelés és a Supplier promise közötti különbség;
- a részleges, eltérő vagy javított acknowledgement magyarázhatósága;
- a dispatch, acknowledgement és Goods Receipt közötti domain-határ.

Az implementációnak emellett egyszerre kell védenie az ismételt HTTP-kérések
idempotenciáját és engednie a szándékos redispatchet, illetve az append-only
acknowledgement-korrekciót.

## A Phase 1 auditból következő meglévő korlátok

1. A `PurchaseOrderStatus` jelenleg `Draft`, `Ordered`, `PartiallyReceived`,
   `Received` és `Cancelled` értékeket tartalmaz. Az enum egyszerre szolgálja a
   belső ordering és a receiving/MRP lifecycle-t.
2. A `PurchaseOrderService::approve()` a Draft PO-t `Ordered` állapotba teszi,
   és ekkor állítja be az `ordered_at` értéket. Nem történik külső kommunikáció.
3. A `PurchaseOrderItem` kizárólag ordered és received mennyiséget, valamint
   receipt-orientált státuszt tárol; supplier-promised quantity/date nincs.
4. A buyer delivery baseline csak a PO header
   `PurchaseOrder.expected_delivery_date` mezőjén létezik. PO item-szintű
   requested delivery date nincs.
5. A 0015-tel generált PO immutable Supplier-, Item-, ItemSupplier-, unit-,
   conversion-, price- és replenishment-snapshotokat tart. Legacy vagy kézzel
   létrehozott PO-ból ezek egy része hiányozhat.
6. A Supplier törzs egy általános emailt, telefont és címet tárol; nincs named
   contact, több destination, Supplier Portal vagy EDI endpoint modell.
7. A Goods Receipt külön aggregate, amely postingkor Stock Movementet hoz
   létre, frissíti a received mennyiséget és a PO receiving státuszát.
8. Nincs procurement mailer, supplier notification, Supplier Portal vagy EDI
   infrastruktúra. A Laravel user email verification nem újrahasznosítható
   Purchase Order dispatch mechanizmusként.
9. Nincs általános idempotency-key framework. A meglévő kritikus workflow-k
   row lockot, status revalidationt és adatbázis unique constraintet használnak.
10. A meglévő PO `close()` viselkedés `Received` státuszt állít Goods Receipt
    létrehozása nélkül. Ennek rendezése nem a 0016 feladata.
11. A meglévő PO update flow a Suppliert és a header
    `expected_delivery_date` értékét lifecycle-specifikus immutability guard
    nélkül módosíthatja. A 0016 ezt a legacy viselkedést nem tervezi át, ezért
    a dispatch és acknowledgement összehasonlítási alapjait saját történeti
    snapshotokban kell megőrizni.

## Döntés

A dispatch és a Supplier Acknowledgement két új, egymástól és a Purchase
Ordertől is elkülönülő, append-only domain record története:

```text
Purchase Order
├─ Purchase Order Dispatch attempt 1 (failed)
├─ Purchase Order Dispatch attempt 2 (succeeded)
└─ Supplier Acknowledgement v1
   ├─ line response for PO item A
   └─ line response for PO item B
      ↓ superseded by
   Supplier Acknowledgement v2
```

A `PurchaseOrderStatus` nem kap `Dispatched`, `Acknowledged` vagy hasonló új
értéket. A dispatch state a dispatch rekordokból, az acknowledgement state az
acknowledgement rekordokból származtatott, egymástól ortogonális read state.

Az `Ordered` és az `ordered_at` jelentése változatlan. Az `ordered_at` továbbra
is a meglévő belső approval/ordering transition időpontja, nem dispatch
timestamp. A sikeres dispatch saját `dispatched_at` mezőt kap.

## Domain terminológia

| Fogalom                             | Jelentés                                                                                      | Nem jelenti                                                    |
| ----------------------------------- | --------------------------------------------------------------------------------------------- | -------------------------------------------------------------- |
| `PurchaseOrder`                     | A buyer által rögzített formális rendelés és execution dokumentum.                            | Nem bizonyít dispatch-et, Supplier promise-t vagy receiptet.   |
| `PurchaseOrderDispatch`             | Egy konkrét, kimenetellel lezárt dispatch-kísérlet auditálható ténye.                         | Nem approval, acknowledgement vagy Goods Receipt.              |
| `SupplierAcknowledgement`           | A Supplier egy konkrét időpontban rögzített válaszának header snapshotja.                     | Nem módosított PO, receipt, invoice vagy payment.              |
| `SupplierAcknowledgementItem`       | Egy PO itemre adott supplier-promised quantity/date vagy explicit rejection.                  | Nem PO item módosítás és nem receipt line.                     |
| `effective acknowledgement`         | A supersession-lánc egyetlen, utód nélküli aktuális eleme.                                    | Nem törli vagy teszi valótlanná a korábbi történeti választ.   |
| `buyer requested delivery baseline` | A PO header `expected_delivery_date` értékéből a válaszhoz snapshotolt összehasonlítási alap. | Nem Supplier promise, receipt date vagy automatikus MRP dátum. |

## Domain model

### PurchaseOrderDispatch

A `PurchaseOrderDispatch` egy append-only dispatch-attempt. Legalább a
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

V1-ben nincs `pending` vagy `cancelled` dispatch state. Mivel nincs rendszer
által végrehajtott aszinkron küldés, a rekord csak ismert kimenetellel jön
létre. Egy tervezett, de meg nem kísérelt küldés még nem dispatch domain fact.

### SupplierAcknowledgement

A `SupplierAcknowledgement` egy teljes, önmagában értelmezhető response
snapshot header:

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

A header status és a két attention flag service-ben, a line snapshotokból
determinista módon számított és perzisztált evaluation result. Nem a request
szabadon választható állítása.

### SupplierAcknowledgementItem

Minden line egy konkrét `PurchaseOrderItem` válasza:

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

Az acknowledgement line snapshot nem delta. Egy correction/superseding
acknowledgement minden aktuálisan közölni kívánt line választ új rekordokban
ismét teljesen rögzít.

### SupplierAcknowledgementScopeItem

Minden acknowledgement saját, append-only evaluation scope snapshotot kap. A
`SupplierAcknowledgementScopeItem` egy acknowledgement és a rögzítéskor a PO
scope-jába tartozó minden nem cancelled PO item kapcsolatát őrzi. Nem Supplier
response és nem placeholder acknowledgement line.

Az acknowledgement line csak a saját acknowledgement scope-jában szereplő PO
itemre hivatkozhat. Egy scope item akkor `missing`, ha ugyanahhoz az
acknowledgementhez nincs hozzá `SupplierAcknowledgementItem`. Ez megőrzi a
történeti missing-line jelentést akkor is, ha a PO item scope-ja később
megváltozna, és a tárolt header status/attention flag soha nem értékelődik újra
aktuális PO scope alapján.

## Lifecycle és származtatott state

A Purchase Order meglévő lifecycle-ja és a jelenlegi transition guardok
változatlanok. A fő receiving útvonal és a már létező terminális enum state:

```text
Draft → Ordered ───────────────────→ Received
          └→ PartiallyReceived ────→ Received
Cancelled (meglévő terminális state; a 0016 nem vezet be új transitiont)
```

A dispatch state ettől függetlenül származik:

```text
no dispatch attempts
→ last attempt failed
→ successfully dispatched
→ successfully redispatched
```

Sikeres dispatch után egy későbbi failed redispatch nem teszi meg nem történtté
az előző sikeres dispatch-et. A read model ezért külön adja vissza a latest
attemptet és a latest successful dispatch-et.

Az acknowledgement state szintén külön származik:

```text
no acknowledgement
→ accepted
→ accepted_with_changes
→ rejected
```

Mindig az effective acknowledgement statusa jelenik meg aktuális state-ként;
az előzményei historyként maradnak láthatók.

## Invariánsok

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

## Dispatch szabályok

### Eligibility

Új dispatch-attempt csak akkor rögzíthető, ha:

- a PO státusza `Ordered` vagy `PartiallyReceived`;
- a PO Supplier kapcsolata létezik és a Supplier aktív;
- a PO-n legalább egy nem cancelled item van, és minden ilyen item ordered
  quantityje pozitív;
- a channel- és recipient-validáció teljesül;
- az authenticated actor rendelkezik `procurement.view` és
  `purchase-orders.dispatch` permissionnel.

`Draft`, `Received` és `Cancelled` PO nem dispatch-elhető. A meglévő `close()`
semantikát a 0016 nem változtatja meg; az általa `Received` állapotba tett PO is
ineligible.

### Legacy és manual Purchase Orderek

A 0015 execution snapshotok részleges hiánya önmagában nem blokkolja a
dispatch-et. Legacy/manual PO dispatch-elhető, ha a fenti lifecycle-, Supplier-
és item-invariánsok teljesülnek. A service nem talál ki, nem backfillel és nem
rekonstruál hiányzó 0015 snapshotokat aktuális master datából.

A dispatch a rögzítéskor aktuálisan kapcsolt Supplier code/name értékét és a
tényleges recipientet külön snapshotolja. A PO meglévő 0015 Supplier snapshotja
nem írható felül, és hiánya nem pótolható visszamenőleg. A Supplier aktuális
emailje csak UI-prefill lehet; a backend a felhasználó által ténylegesen
jóváhagyott recipient adatot menti. Ez auditálhatóvá teszi azt is, hogy egy
legacy vagy időközben módosított PO-t a dispatch pillanatában mely Supplier és
recipient felé rögzítettek.

### Channel és recipient

V1 channel értékek:

- `manual`: személyes, nyomtatott, telefonon koordinált vagy más manuális
  átadás rögzítése;
- `email`: a felhasználó által a rendszeren kívül elküldött email rögzítése;
- `other`: más, notes/reference adatokkal magyarázott külső módszer.

`email` esetén valid `recipient_email` kötelező. `manual` esetén legalább egy
recipient descriptor (`recipient_name`, `recipient_email` vagy
`recipient_reference`) kötelező. `other` esetén `recipient_reference` és notes
kötelező.

A `portal` és `edi` V1-ben nem választható, mert nincs hozzá működő
infrastruktúra. Ezek későbbi enum-bővítési pontok, nem jelenlegi képességek.

### A dispatch V1 jelentése

A rendszer V1-ben nem küld emailt, nem tölt fel Supplier Portalra, és nem ad át
EDI üzenetet. A sikeres dispatch rekord az authenticated felhasználó auditált
állítása arról, hogy a dokumentumot a megadott külső csatornán átadta.
A failed rekord ugyanígy a felhasználó auditált állítása arról, hogy a külső
átadást megkísérelte, de az a rögzített okból nem fejeződött be sikeresen.

Az UI és az audit nem használhat olyan szöveget, amely rendszer általi fizikai
kézbesítést állít. Technikai delivery receipt, email provider message ID vagy
külső kézbesítési garancia nincs.

### Többszöri dispatch és redispatch

Több attempt engedett:

- failed attempt után retry rögzíthető;
- successful attempt után szándékos redispatch is rögzíthető;
- minden új attempt új append-only rekord és következő sequence;
- a második és későbbi attempt `previous_dispatch_id` értéke az előző attempt,
  és `redispatch_reason` kötelező;
- failed attemptnél `failure_reason` kötelező, `dispatched_at` null;
- succeeded attemptnél `dispatched_at` kötelező és nem korábbi az
  `attempted_at` értéknél;
- egyik üzleti timestamp sem lehet jövőbeli;
- succeeded attemptnél `failure_reason` null, első attemptnél
  `previous_dispatch_id` és `redispatch_reason` null.

A service a PO row lock alatt a következő sequence-et `max(sequence) + 1`
értékként osztja ki. Második és későbbi attemptnél a
`previous_dispatch_id`-nek ugyanazon PO legutolsó attemptjére kell mutatnia;
stale vagy másik PO-hoz tartozó predecessor explicit conflict. A failure és
redispatch reason trim után nem lehet üres.

Az előző sikeres dispatch soha nem lesz failed vagy superseded. A későbbi
attempt csak új tényt ad a historyhoz.

## Supplier Acknowledgement szabályok

### Eligibility és dispatch nélküli acknowledgement

Első acknowledgement `Ordered` vagy `PartiallyReceived` PO-hoz rögzíthető.
`Draft`, `Received` vagy `Cancelled` PO-hoz új, előzmény nélküli
acknowledgement nem rögzíthető. A scope nem lehet üres, és minden scope item
ordered quantityjének pozitívnak kell lennie.

Meglévő effective acknowledgement korrekciója terminális PO-státusz után is
megengedett, mert ez történeti tényt javít, nem új executiont indít. Ilyenkor
kötelező a supersession link és a `correction_reason`, és az eredmény nem
módosíthat PO-, MRP-, receipt- vagy inventory-state-et.

Acknowledgement sikeres dispatch nélkül is rögzíthető. Ez támogatja például a
telefonon, Supplier által kezdeményezve vagy legacy folyamatból érkező választ.
Minden acknowledgementnél kötelező:

- `source`;
- `acknowledgement_received_at`;
- `recorded_by` authenticated User;
- a következő attribution mezők közül legalább egy:
  `supplier_reference`, `acknowledged_by_name`, `acknowledged_by_email`.

Dispatch nélküli rögzítésnél ezen felül kötelező a notes, amely röviden
megmagyarázza, hogyan érkezett a válasz. A `source` a Supplier response bejövő
csatornáját jelenti: `email`, `phone`, `manual` esetén papír/személyes átadás,
`other` esetén pedig notes-ban megnevezett más forrás. Nem a rendszer
adatbeviteli módját jelöli; V1-ben minden acknowledgementet authenticated User
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

### Overall status és attention flag

Az értékelés scope-ja a PO acknowledgement-rögzítéskor létező összes nem
cancelled itemének ugyanabban a tranzakcióban létrehozott scope snapshotja.

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

A két flag egymástól független boolean evaluation result, ezért egyszerre is
lehetnek `true` értékűek. Mindkettő a header statushoz hasonlóan a snapshotolt
scope-ból és line factekből determinisztikusan számított, perzisztált tény; a
request nem választhatja meg őket, a UI pedig nem számíthat eltérő szabállyal.

Ezek attention signalok. A 0016 nem indít automatikus follow-up taskot vagy MRP
újraszámítást.

## Line-level acknowledgement szabályok

### Rejection

A rejection explicit `line_status = rejected`. Rejected line nem ad
promised quantityt vagy promised delivery date-et. A nulla promised quantity
nem rejection-kód és accepted line-on nem valid.

Vegyes accepted és rejected line-ok header statusa
`accepted_with_changes`. Header `rejected` csak teljes, minden aktív PO line-ra
kiterjedő explicit rejection esetén áll elő.

### Partial és missing line

Partial acknowledgement megengedett, ha legalább egy valid line szerepel. A
kimaradó PO item:

- `missing`, vagyis még nincs rá supplier response;
- nem implicit accepted;
- nem implicit rejected;
- nem kap nulla promised quantityt;
- `accepted_with_changes`, `requires_follow_up = true` és
  `requires_replanning = true` eredményt okoz.

A current acknowledgement read model a hiányzó line-okat az effective
acknowledgement saját scope snapshotja és response line-jainak különbségéből
származtatja. Külön placeholder response line nem készül; a scope membership
rekord nem állít Supplier választ.

Superseding acknowledgement nem örököl line-t az elődjéből. Ha egy korábban
megválaszolt PO item az új verzió scope-jában szerepel, de az új response
line-jai közül kimarad, az az új effective verzióban `missing`. Ez a "teljes
response snapshot, nem delta" szabály kötelező jelentése.

### Quantity promise és variance

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

### Promised delivery date és header baseline

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

## Duplicate- és idempotency-szabályok

### Dispatch

Minden dispatch request kötelező, kliens által generált `idempotency_key`
értéket kap. A key trimelt, nem üres, legfeljebb 100 ASCII karakteres,
case-sensitive opaque token. Adatbázis binary collationnel létrehozott unique
constraint védi a `purchase_order_id + idempotency_key` párt.

A backend SHA-256 `request_fingerprint` értéket képez a következő kanonikus
business payloadból: status, channel, trimelt recipient mezők, UTC másodperc
pontosságú `attempted_at` és `dispatched_at`, trimelt failure/redispatch reason,
trimelt notes és a kliens által megadott `previous_dispatch_id`. Üres string
nullra normalizálódik, az email lowercase, a JSON kulcssorrend rögzített. A
sequence, Supplier/buyer-date server snapshot, actor, idempotency key és
technikai timestamp nem része. A fingerprint lowercase hex `char(64)`.

A service a PO sort zárolja, majd:

- azonos key és kanonikusan azonos payload esetén a meglévő rekordot adja
  vissza, új audit nélkül;
- azonos key és eltérő payload esetén explicit idempotency conflict hibát ad;
- új key esetén új attempt készül, és ha már volt attempt, azt szándékos
  redispatchként csak `previous_dispatch_id` és reason mellett fogadja el.

Az idempotency lookup és canonical conflict check megelőzi az új írásra
vonatkozó aktuális lifecycle eligibility ellenőrzést. Ezért egy exact replay a
PO későbbi terminális állapotában is a meglévő rekordot adja vissza; új key csak
az aktuális eligibility teljesülésekor hozhat létre új attemptet.

A `purchase_order_id + dispatch_sequence` unique constraint a sequence
concurrency backstop. A `(previous_dispatch_id)` unique constraint lineáris
attempt-láncot véd.

### Supplier Acknowledgement

Az acknowledgement `idempotency_key` ugyanazt a trimelt, 1–100 ASCII
karakteres, case-sensitive opaque-token contractot és binary database
collationt használja, mint a dispatch key.

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

## Supersession és effective acknowledgement

Az acknowledgement append-only correction policy-ja:

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

Effective az a PO-hoz tartozó acknowledgement, amelyre nem mutat másik
acknowledgement `supersedes_acknowledgement_id` mezője. A service PO row lock
mellett csak az aktuális effective rekord supersedelését engedi. A
`supersedes_acknowledgement_id` unique constraint megakadályozza, hogy egy
elődnek két utóda legyen. PO-n belül a sequence unique és monoton.

Nem készül mutable `is_current`, `is_effective` vagy `superseded_at` flag. Az
effective state a történeti chainből authoritatively származik, így nincs
duplikált current-state invariáns.

## Authorization

Két új, üzletileg elkülönülő permission szükséges:

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

## Auditálhatóság

Az új domain record és az activity log egymást kiegészíti. A domain record a
teljes üzleti tény Single Source of Truth-ja; az activity log az actiont és az
actort teszi könnyen követhetővé.

Események:

- `purchase_order_dispatched` sikeres dispatch recordnál;
- `purchase_order_dispatch_failed` failed attemptnél;
- `supplier_acknowledgement_recorded` első acknowledgementnél;
- `supplier_acknowledgement_superseded` correctionnél.

Az audit ugyanabban a DB-tranzakcióban készül, mint a domain recordok. Audit
hiba minden írást rollbackel. Idempotens replay nem készít új auditot.

Az activity subject az új Dispatch vagy Acknowledgement record. Metadata:
Purchase Order ID, sequence, outcome/status, kapcsolt record ID-k, item count,
missing/rejected/variance count és attention flag-ek. Különböző unitok
mennyisége nem aggregálható. Recipient email, teljes notes és teljes line
payload nem duplikálható activity metadata-ba.

## Interakció a Purchase Orderrel, MRP-vel és Goods Receipttel

### Purchase Order

A PO marad a buyer order Single Source of Truth-ja. Dispatch és
acknowledgement relationként olvasható róla, de nem írja át a dokumentumot. A
PO detail read model külön adja vissza:

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

## V1 boundaries és non-goals

A 0016 V1 nem implementál:

- fizikai SMTP/email küldést vagy delivery trackinget;
- Supplier Portalt, Supplier authenticationt vagy recipient directoryt;
- EDI-t vagy külső procurement API-t;
- automatikus PO amendmentet vagy quantity/date átírást;
- PO item-szintű requested delivery date bevezetését;
- automatikus follow-up taskot, escalationt vagy notificationt;
- automatikus MRP/netting/pegging újraszámítást;
- promised quantity/date firm incoming authority-vá emelését;
- Goods Receipt, ASN vagy quality receipt redesignját;
- invoice matchinget, accounts payable-t, paymentet vagy settlementet;
- a meglévő PO approval, cancellation vagy close lifecycle áttervezését;
- általános workflow engine-t vagy event sourcingot.

## Implied schema a Phase 3 implementációhoz

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

## Következmények és trade-offok

Pozitív:

- a PO, dispatch, Supplier promise, receipt és financial lifecycle nem mosódik
  össze;
- minden failed attempt, redispatch és correction megmarad;
- a historyból determinisztikusan származtatható a latest attempt, latest
  success és effective acknowledgement;
- a line-level variance megőrzi az ordered és buyer-date baseline-t;
- a meglévő 0015 snapshot, PO status, MRP és Goods Receipt behavior kompatibilis
  marad;
- a DB unique guardok concurrency és ismételt submission ellen is védenek.

Trade-offok:

- az append-only history több sort és összetettebb show-page read modelt jelent;
- a header-only PO delivery date miatt minden line ugyanahhoz a buyer baseline-
  hoz hasonlít V1-ben;
- a response fingerprint kanonizálási szabálya publikus service-invariáns, ezért
  stabil és tesztelt implementációt igényel;
- a rendszer V1-ben csak felhasználói állítást auditál a dispatchről, külső
  delivery evidence-et nem;
- az acknowledgement variance nem javítja automatikusan az MRP tervet, ezért az
  operatív follow-up emberi feladat marad.

## Elutasított alternatívák

- **`Dispatched` és `Acknowledged` PO statusok.** Elutasítva, mert a dispatch,
  acknowledgement és receiving egymástól független dimenzió; egyetlen enum
  elveszítené a kombinált és történeti állapotokat.
- **`ordered_at` átértelmezése dispatch timestampként.** Elutasítva, mert a
  meglévő approval transition írja, külső kommunikáció nélkül.
- **Dispatch mezők közvetlenül a Purchase Orderen.** Elutasítva, mert nem őrizné
  a failed attemptet, redispatchet, recipient historyt és idempotency-határt.
- **Egy mutable current acknowledgement rekord.** Elutasítva, mert correction
  felülírná a Supplier korábbi válaszát és az audit trailt.
- **Header-only acknowledgement.** Elutasítva, mert multi-item PO-n a Supplier
  eltérő mennyiséget, dátumot vagy rejectiont adhat tételenként.
- **Nulla promised quantity mint rejection.** Elutasítva, mert többértelmű és
  eltünteti az explicit Supplier döntést.
- **Missing line implicit rejection vagy acceptance.** Elutasítva, mert a
  hiányzó válasz bizonytalanság, nem Supplier által közölt döntés.
- **Acknowledgementből automatikus PO amendment.** Elutasítva, mert a buyer
  order és Supplier response eltérő tény; amendment külön business action.
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
