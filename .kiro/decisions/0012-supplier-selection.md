# Supplier Selection

- **Állapot:** Elfogadva és implementálva
- **Dátum:** 2026-08-15
- **Kapcsolódó döntések:** [0007 Item Supplier](0007-item-supplier-procurement-source.md), [0008 Supply Proposal](0008-supply-proposal.md), [0011 Purchase Requisition Consolidation](0011-purchase-requisition-consolidation.md)

## Üzleti probléma

Egy beszerzési igény (`Purchase Requisition`, PR) több különböző cikket is
tartalmazhat. A teljes igényhez egyetlen olyan beszállítót kell választani,
amely minden cikket szállíthat. A választás csak akkor biztonságos, ha a rendszer
az aktuális, jóváhagyott cikk–beszállító kapcsolatokból indul ki, és nem választ
automatikusan a felhasználó helyett.

A Supplier Selection ezért kifejezett beszerzési döntés: egy beszállító nélküli,
Draft állapotú PR minden tételéhez közös Suppliert rendel. A választható
beszállítók kizárólag a mérvadó `ItemSupplier` beszerzési forrásokból
származhatnak.

Ez nem automatikus rangsorolás, legolcsóbb-beszállító optimalizálás vagy
mesterséges intelligencián alapuló ajánlás. Nem hagyja jóvá a PR-t, nem hoz
létre Purchase Ordert, és nem végzi el az utánpótlási mennyiség számítását.

```text
beszállító nélküli Draft Purchase Requisition
→ érvényes ItemSupplier kapcsolatok meghatározása
→ minden tételhez közös Supplierek metszete
→ a felhasználó kifejezett választása
→ Supplierrel rendelkező Draft Purchase Requisition
```

A jelöltlista számított, nem tárolt lekérdezési eredmény. A választás tárolt
üzleti ténye a `PurchaseRequisition.supplier_id` és a hozzá tartozó
tevékenységnapló-bejegyzés.

## A választás szintje és a közös beszállító

V1-ben a Supplier a teljes PR fejlécére vonatkozik. Ez illeszkedik ahhoz, hogy a
PR és a későbbi Purchase Order is egyetlen fejlécszintű Supplierrel rendelkezik.
A PR tételei ezért nem kapnak külön Supplier mezőt: ez felosztási szabály nélkül
ellentmondó állapotot eredményezne.

Többtételes PR esetén a rendszer minden egyedi Itemhez meghatározza az érvényes
Suppliereket, majd ezek metszetét veszi. Egy Supplier csak akkor választható, ha
a PR minden egyedi Itemjéhez pontosan egy érvényes `ItemSupplier` forrás
tartozik. A tömeges lekérdezés egyszerre tölti be az `ItemSupplier`, `Supplier`
és `Item` adatokat; nem indít külön lekérdezést minden tételhez.

Például ha az A cikket az X és az Y Supplier, a B cikket pedig az Y és a Z
Supplier szállíthatja, akkor a közös jelölt kizárólag Y. A rendszer nem rangsorolja
és nem választja ki automatikusan Y-t: a döntést továbbra is jogosult
felhasználónak kell meghoznia.

Ha nincs közös Supplier, a művelet `NO_ELIGIBLE_SUPPLIER` hibával leáll. Nem
választ részlegesen, és nem módosítja vagy bontja fel automatikusan a PR-t. Az
automatikus felosztáshoz külön ADR szükséges, mert rendezni kellene az eredeti
dokumentum életciklusát, a Proposal eredetkapcsolatok áthelyezését, a
kódgenerálást és az auditot. A fel nem oldható többtételes PR operatív
újracsoportosítása ezért nyitott későbbi szabály.

## Mikor érvényes egy beszerzési forrás?

A választás érvényességi napja a művelet végrehajtásának aktuális üzleti napja
(`today`, az alkalmazás időzónájában). Nem a PR `required_at` vagy
`proposed_supply_at` dátuma, mert ezek az igény és a tervezett ellátás időzítését
írják le, nem a beszállítóválasztás időpontját.

Egy `ItemSupplier` forrás csak akkor használható, ha:

- aktív és approved;
- a `valid_from` értéke null, vagy nem későbbi a választás napjánál;
- a `valid_until` értéke null, vagy nem korábbi a választás napjánál;
- a kapcsolódó Supplier aktív;
- a kapcsolódó Item aktív.

A jelöltek megjelenítése és a választás mentése ugyanazt a központi repository
lekérdezési szabályt használja. Mentéskor a rendszer a PR sorának zárolása után
ismét lekérdezi az érvényességet. A frontend korábban betöltött jelöltlistája
nem mérvadó.

## Mit lát a döntéshozó?

A nem tárolt jelöltlista tartalmazza a Supplier azonosítóját, kódját és nevét.
Emellett minden PR Itemhez megmutatja a kapcsolódó forrás szükséges adatait:
preferred jelölés, priority, lead time, referenciaár és currency, purchase unit,
conversion factor, MOQ, order multiple és validity.

V1-ben nincs automatikus rangsorolás vagy kiválasztás. A jelöltek csak
meghatározott megjelenítési sorrendet kapnak:

```text
supplier name ASC
supplier id ASC
```

Az itemszintű `is_preferred` és `priority` — ahol `1` a legjobb — magyarázó
adat, nem összevont pontszám. A preferred jelölés nem kötelező. A referenciaár
alapján sem választható automatikusan a legolcsóbb Supplier, mert a források
purchase unit és currency adatai eltérhetnek, és nincs egységes történeti
összehasonlítási szabály. A lead time szintén csak tájékoztató adat; a legkésőbbi
rendelési nap és a teljesíthetőség számítása nem része ennek a döntésnek.

## Életciklus és ismételt választás

Supplier csak Draft PR-en választható. A választás nem módosítja sem a PR, sem
a PR Item státuszát, és nem hagyja jóvá a dokumentumot.

- Beszállító nélküli Draft PR esetén a közös jelöltek egyike kifejezetten
  kiválasztható.
- Ha a PR már ugyanazzal a Supplierrel rendelkezik, az érvényesség ismételt
  ellenőrzése után a művelet változtatás nélkül sikerül, és nem ír új
  auditeseményt.
- Ha a PR már másik Supplierrel rendelkezik, a művelet ütközési hibával leáll;
  csendes felülírás nem történhet.
- Nem Draft PR-en a választás tiltott.
- A 0012 nem módosítja az Approved Supply Proposal Supplierét, így megőrzi a
  0008 jóváhagyási integritását.

## Tranzakció, párhuzamos kérések és audit

A választás egyetlen adatbázis-tranzakcióban történik:

1. a `lockForUpdate()` zárolja a PR sorát;
2. a rendszer újraellenőrzi a Draft életciklust és a jelenlegi Suppliert;
3. tömeges lekérdezéssel újraellenőrzi az aktív Itemeket és a teljes közös
   jogosultságot;
4. beállítja a `supplier_id` értéket;
5. létrehoz egy `supplier_selected` tevékenységnapló-eseményt.

Az esemény tárgya a PR, végrehajtója a választó felhasználó. A metaadatai:
`purchase_requisition_id`, `supplier_id`, `selection_mode = manual` és
`candidate_count`.

A sorzárolás megakadályozza, hogy két párhuzamos kérés közül észrevétlenül az
utolsó felülírja az elsőt. Az azonos Supplier ismételt kiválasztása idempotens,
és nem hoz létre audit-zajt. Eltérő Supplierrel érkező versenyző kérés a zárolás
utáni újraellenőrzéskor meghiúsul.

## Mennyiségi és végrehajtási határok

A Supplier kiválasztása nem módosítja a PR Item mennyiségét, mértékegységét vagy
Proposal eredetkapcsolatát. Az MOQ, order multiple, conversion factor és
purchase unit itt csak tájékoztató jelöltadat. A rendszer ezeket nem alkalmazza,
nem konvertál és nem kerekít. Ezek, valamint a safety stock és az utánpótlási
mennyiség a [0013 Replenishment Strategies](0013-replenishment-strategies.md)
döntési körébe tartoznak.

A 0012 nem hagy jóvá PR-t, nem generál Purchase Ordert vagy Goods Receiptet, és
nem módosít `StockBalance`, `StockMovement` vagy `StockReservation` adatot. A
későbbi PO-generálásnak újra kell ellenőriznie a kiválasztott Suppliert és az
`ItemSupplier` forrásokat. A választás egy adott időpontban érvényes döntés, nem
örök garancia.

## Újraszámítás és újraválasztás

A jelöltlista minden olvasáskor az aktuális érvényességi nap és törzsadatok
alapján újraszámított eredmény. Nincs jelöltlista-gyorsítótár és külön
`SupplierSelection` modell. A kiválasztott Supplier nem cserélhető
automatikusan. Az újraválasztás külön, auditálható életciklus-döntést igényelne,
ezért V1-ben tiltott.

## Következmények és nyitott kérdések

- A PR és a későbbi PO fejlécszintű Supplier-szabálya összhangban marad.
- A közös metszet igazolja a többtételes PR beszállítói összeegyeztethetőségét.
- A közös Supplier nélküli PR nem oldható fel automatikusan; a felosztás és az
  újrakonszolidálás életciklusa későbbi döntést igényel.
- A 0013 határozza meg, hogyan változhat a Draft PR Item mennyisége az MOQ és az
  order multiple miatt. A 0012 ezt a döntést nem előlegezi meg.
- Ár- vagy lead-time-alapú rangsorolás csak egységes currency-,
  mértékegység-normalizálási, teljesíthetőségi és szabályrendszer után vezethető
  be.

## Elutasított alternatívák

- **Supply Proposal módosítása:** sértené az Approved Proposal változatlan
  döntési tartalmát, és a PR eredetkapcsolatának létrejötte után egy korábbi
  dokumentumot írna át.
- **Supplier mező a PR Itemen:** a jelenlegi, fejlécszintű Supplierrel rendelkező
  PR- és PO-modellel felosztási szabály nélkül ellentmondó állapotot hozna létre.
- **Automatikus preferred-, priority- vagy áralapú kiválasztás:** nem
  dokumentált üzleti döntést hozna, a mezők pedig nem alkotnak egységes
  pontozási szabályt.
- **Automatikus PR-felosztás:** az eredetkapcsolat, az eredeti dokumentum
  életciklusa, a kódgenerálás és az audit szabályai még nincsenek meghatározva.
- **Külön `SupplierSelection` tábla:** V1-ben indokolatlan tervezési életciklus
  nélkül megkettőzné a PR természetes Supplier üzleti tényét.
