# Requirement Pegging

- **Állapot:** Elfogadva és implementálva
- **Dátum:** 2026-08-13
- **Kapcsolódó döntések:** [0009 Material Requirement Netting](0009-material-requirement-netting.md)

## Üzleti probléma és döntés

A Netting megmondja, hogy egy Material Requirementből mennyi marad
fedezetlenül. A tervezőnek azt is meg kell tudnia magyarázni, mely konkrét
fedezeti források járultak hozzá az adott szükséglet lefedéséhez.

A Pegging, vagyis a szükséglet-visszakapcsolás erre a kérdésre válaszol:

> Melyik konkrét tervezési fedezeti forrás milyen mennyiséggel fedezte ezt a
> Material Requirementet?

A 0010 szerinti Pegging a 0009 ugyanazon determinisztikus számítási futásának
eltárolt, aktuális állapotot magyarázó eredménye. Azt rögzíti, hogy egy konkrét
`StockBalance` vagy `PurchaseOrderItem` az Item alapegységében kifejezve milyen
mennyiséggel fedezett egy konkrét `MaterialRequirement` sort.

Ez tervezési kapcsolat, nem fizikai készletfoglalás vagy készletallokáció. Nem
általános eredetkapcsolat, és nem a Supply Proposalból Purchase Requisitionbe
vezető későbbi dokumentumkapcsolat.

## Egyszerű példa

Egy Material Requirement 10 kg anyagot kér. A 0009 Netting számítása 4 kg
fedezetet használ egy konkrét StockBalance rekordból, további 3 kg-ot pedig egy
időben alkalmazható PurchaseOrderItemből. A fennmaradó Shortage 3 kg.

```text
Material Requirement: 10 kg
├─ StockBalance peg:          4 kg
├─ PurchaseOrderItem peg:     3 kg
└─ Shortage:                  3 kg
```

A két Peg azt magyarázza meg, honnan származott a számítás 7 kg fedezete. Nem
foglalja le a 4 kg készletet, nem módosítja a Purchase Ordert, és a 3 kg hiányhoz
nem hoz létre automatikusan Supply Proposalt vagy beszerzési dokumentumot.

## A kapcsolat technikai jelentése

V1-ben a fedezeti forrást két kifejezett, nullable idegen kulcs reprezentálja:

- `stock_balance_id`;
- `purchase_order_item_id`.

Pontosan az egyik lehet kitöltve. Ez a modell szűkebb és adatbázis-szinten
jobban védhető, mint egy általános polymorphic forrás. A Supply Proposal és a
Purchase Requisition nem lehet Pegging-forrás.

Egy Requirementhez több Peg tartozhat, ha több konkrét forrás ad fedezetet.
Ugyanakkor egy adott Requirement–forrás párhoz csak egy Peg-sor tartozhat.

## Mennyiség, idő és sorrend

A `quantity` három tizedes pontosságú mennyiség az Item alapegységében. A Peg
összegeknek pontosan egyezniük kell a 0009 eredményével:

```text
stock pegek összege = on-hand coverage
Purchase Order Item pegek összege = incoming coverage
összes peg + net requirement = gross requirement
```

A Requirementek sorrendje változatlan:

1. Item;
2. dátummal rendelkező Requirementek `required_at ASC`, majd `id ASC`;
3. a dátum nélküli Requirementek `id ASC` sorrendben.

A StockBalance rekordok sorrendje `id ASC`. A rendszer először az Item teljes
készletéből vonja le az aktív foglalásokat, mégpedig StockBalance `id ASC`
sorrendben. Csak az ezután megmaradó konkrét balance-mennyiség kapcsolható
Pegként Requirementhez.

A beérkező Purchase Order-tételek sorrendje:

```text
expected_delivery_date ASC
purchase_order_id ASC
purchase_order_item_id ASC
```

Az aznapi beérkezés Pegként kapcsolható. Késői vagy dátum nélküli Purchase Order
nem kapcsolható korábbi Requirementhez. Dátum nélküli Requirementhez jövőbeli
Purchase Order szintén nem kapcsolható.

## Perzisztencia és újraszámítás

Csak automatikusan számított, aktuális Pegging-kapcsolatok léteznek. Nincs kézi
CRUD, saját státusz vagy verziótörténet.

A kötegelt újraszámítás a kért Requirementek teljes közös Item-hatókörére
bővül. Ezután egy adatbázis-tranzakcióban:

1. zárolja ezt a hatókört;
2. lefuttatja a 0009 részletes számítását;
3. ellenőrzi a pontos mennyiségi invariánsokat;
4. törli az adott hatókör korábbi Pegjeit;
5. determinisztikusan újraépíti az aktuális Peg-sorokat.

Ha bármely lépés hibát jelez, a tranzakció visszagördül, és a korábbi Peg-készlet
megmarad. Azonos adatállapot azonos üzleti eredményt ad. Az újraépített aktuális
sorok technikai azonosítója ettől még megváltozhat.

A Pegging az újraszámításkori állapot pillanatképe. Készlet-, foglalás-,
Purchase Order-státusz-, áruátvételi vagy dátumváltozás után elavulhat. Nincs
bizonyíthatatlan frissességi jelző vagy eseményvezérelt újratervezés. A
`calculated_at` az utolsó újraépítés időpontját jelzi.

A kötegelt audit esemény összesített Requirement-, Peg- és fedezettmennyiség-
adatot tárol. Nem készül minden egyes sorhoz külön, zajos naplóbejegyzés.

## Integritás, módosítás és törlés

- A Requirement törlése `cascade` szabállyal eltávolítja annak aktuális
  tervezési Pegging-kapcsolatait.
- A StockBalance- és Purchase Order Item-forrásokra mutató idegen kulcsok
  `restrict/no-action` szabályt használnak. Így egy forrás eltűnése nem
  törölheti csendben a magyarázó kapcsolatot.
- Requirement–forrás páronként egy Peg-sor létezhet.
- Perzisztálás előtt a Service minden fedezeti eltérést vagy egy forrás
  rendelkezésre álló mennyiségén túli hozzárendelést visszautasít.
- A Pegging nem kézzel módosítható. Változása a teljes érintett Item-hatókör
  tranzakciós újraszámításával történik.

## Felelősségi és végrehajtási határok

A 0010 Service a 0009 számítási eredményéből építi újra és tárolja az aktuális
Pegging-kapcsolatokat. A kapcsolatok nem helyettesítik a készlet- vagy
beszerzési végrehajtási objektumokat.

A Pegging:

- nem módosít StockBalance-t;
- nem hoz létre StockMovementet vagy StockReservationt;
- nem választ Suppliert;
- nem alkalmaz MOQ- vagy rendelésitöbbszörös-szabályt;
- nem generál Supply Proposalt, Purchase Requisitiont vagy Purchase Ordert;
- nem általános audit- vagy dokumentumeredet-kapcsolat;
- nem Proposal → Purchase Requisition eredetkapcsolat.

A következő, 0011-es konszolidáció ezért nem közvetlen Peg → Purchase
Requisition kapcsolatot hoz létre. A Requirement fedezetét magyarázó Pegging és
a későbbi dokumentumok eredetkapcsolata eltérő üzleti jelentésű marad.
