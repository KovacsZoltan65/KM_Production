# Requirement Pegging

- **Állapot:** Elfogadva és implementálva
- **Dátum:** 2026-08-13
- **Kapcsolódó döntések:** [0009 Material Requirement Netting](0009-material-requirement-netting.md)

## Döntés

A pegging a 0009 ugyanazon determinisztikus számítási futásának perzisztált,
current-state magyarázata: megmondja, hogy egy konkrét `StockBalance` vagy
`PurchaseOrderItem` milyen Item-base-unit mennyiséggel fedezett egy konkrét
`MaterialRequirement` sort. Planning trace, nem fizikai készletfoglalás.

V1-ben két explicit, nullable foreign key reprezentálja a source-ot:
`stock_balance_id` és `purchase_order_item_id`; pontosan az egyik lehet kitöltve.
Ez szűkebb és DB-szinten jobban védhető, mint egy generikus polymorphic source.
Supply Proposal és Purchase Requisition nem peg source.

## Mennyiség, idő és sorrend

A quantity három tizedes Item base unit. A peg összegek pontosan egyeznek a
0009 coverage értékeivel: stock pegek összege az on-hand coverage, PO pegeké az
incoming coverage, a peg total és net requirement összege a gross requirement.

Requirement ordering változatlan: Item, timed `required_at ASC`, `id ASC`, majd
null dátumú sorok. StockBalance ordering `id ASC`. Az aktív reservation teljes
Item-poolból, StockBalance `id ASC` sorrendben vonódik le, mielőtt a megmaradó
konkrét balance pool peg-elhetővé válik. Incoming ordering
`expected_delivery_date ASC`, `purchase_order_id ASC`, `purchase_order_item_id ASC`.
Same-day incoming peg-elhető; late vagy dátum nélküli PO, illetve null
`required_at` requirementhez future PO nem peg-elhető.

## Perzisztencia és újraszámítás

Csak automatikus current calculated pegek léteznek; nincs manual CRUD, status
vagy verziótörténet. A batch újraszámítás a kért requirementek teljes közös
item-scope-jára bővül, majd tranzakcióban zárolja ezt a scope-ot, lefuttatja a
0009 calculation trace-et, ellenőrzi az exact invariánsokat,
majd törli és determinisztikusan újraépíti az adott scope pegjeit. Hiba esetén a
régi pegset rollbackkel megmarad. Azonos adatállapot idempotens üzleti eredményt
ad, bár a current-state sorok technikai azonosítója változhat.

A peg snapshot: stock-, reservation-, PO státusz-, receipt- vagy dátumváltozás
után stale lehet. Nincs hamis freshness flag vagy event-driven újratervezés;
`calculated_at` jelzi az utolsó rebuildet. A batch audit event összesített
requirement-, peg- és covered quantity adatot tárol, nem soronként zajos logot.

## Integritás és törlés

A requirement törlése cascade-del eltávolítja current planning trace-ét. A
StockBalance és PO Item source FK restrict/no-action, hogy source eltűnése ne
törölje csendben a trace-et. Requirement/source páronként egy peg sor van.
Persist előtt a service visszautasít minden coverage-eltérést vagy source
overallokációt.

## Határok

A pegging nem módosít StockBalance-t, nem hoz létre StockMovementet vagy
StockReservationt, nem választ suppliert, nem alkalmaz MOQ/order multiple
szabályt, és nem generál Supply Proposalt, Purchase Requisitiont vagy Purchase
Ordert. A következő 0011 konszolidáció nem közvetlen peg-to-PR kapcsolat.
