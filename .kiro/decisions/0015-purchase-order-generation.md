# Purchase Order Generation / Execution Hardening

- **Állapot:** Elfogadva és implementálva
- **Dátum:** 2026-08-24
- **Kapcsolódó döntések:** [0011 Purchase Requisition Consolidation](0011-purchase-requisition-consolidation.md), [0012 Supplier Selection](0012-supplier-selection.md), [0013 Replenishment Strategies](0013-replenishment-strategies.md), [0014 Purchase Requisition Execution Readiness](0014-purchase-requisition-execution-readiness.md)

## Üzleti probléma és döntés

A jóváhagyott belső beszerzési igény (`Purchase Requisition`, PR) még nem
beszerzési rendelés (`Purchase Order`, PO). A beszerző külön művelettel indítja
a rendelés létrehozását a KM_Production rendszerében. Ezt a végrehajtási lépést
jelenti a `Purchase Order Generation`.

A művelet erre a kérdésre válaszol:

> Biztonságosan létrehozható most ebből a Purchase Requisitionből pontosan egy
> Purchase Order, az aktuális adatokból, duplikáció nélkül?

Egy teljes, meghatározott beszállítóhoz tartozó Approved PR-ből egy Draft PO
készülhet. Nincs részleges PR-felhasználás. A korábban kapott végrehajtási
készültségi (`Execution Readiness`) `READY` eredmény önmagában nem elég:
a szükséges adatoknak a tényleges létrehozáskor is érvényesnek kell lenniük.

**A PO létrehozása nem Dispatch.** A rendelés ekkor a KM_Production rendszerében
jön létre. Ez nem bizonyítja, hogy továbbították a beszállítónak, vagy hogy a
beszállító megkapta és visszaigazolta. A művelet nem vételez árut és nem
módosít készletet.

## Egyszerű példa: korábbi READY és tényleges létrehozás

A PR-hez már kiválasztották a beszállítót (`Supplier`), a 0013 szerinti
utánpótlási számítás megtörtént, majd a PR-t jóváhagyták. A részletes oldalon
az aktuális készültségi értékelés `READY` eredményt ad.

A beszerző később elindítja a PO-generálást. A rendszer a művelet
tranzakciójában zárolja és újra betölti az érintett aktuális adatokat, és ismét
elvégzi a 0014 szerinti ellenőrzést. Ha minden szükséges feltétel továbbra is
érvényes, és a teljes írás sikerül, pontosan egy Draft PO készül. A PR és
minden tétele `Ordered` állapotba kerül. A PO még nincs elküldve a Suppliernek.

Ha közben a Supplier inaktív lett, az új ellenőrzés blokkolja a létrehozást.
A korábbi `READY` nem adott állandó engedélyt. Ha pedig a sikeres létrehozás
után ismét elindítják ugyanazt a generálást, `already generated` hiba érkezik,
nem készül második PO.

## Indítás és az ellenőrzés sorrendje

A művelet a meglévő
`POST /admin/purchase-requisitions/{purchaseRequisition}/generate-purchase-order`
és `PurchaseRequisitionService::generatePurchaseOrder()` útvonalat használja.
Nem készül második generátor. Az indítás jogosultságot és kérésvalidációt
igényel; a korábbi felületi készültségi eredmény nem mérvadó.

```text
Approved PR
→ adatbázis-tranzakció indítása és a PR sorának zárolása
→ aktuális adatok újbóli betöltése
→ a 0014 készültségi ellenőrzésének ismételt futtatása
→ READY
→ Draft PO és végrehajtási pillanatképek létrehozása
→ PR és PR-tételek Ordered állapotba állítása
```

A változatlan `PurchaseRequisitionExecutionReadinessService` az egyetlen
készültségi algoritmus. Nincs külön generálási alkalmassági algoritmus vagy
tárolt készültségi engedély. A meglévő PO-kapcsolat ellenőrzése megelőzi az új
létrehozás életciklus- és készültségi ellenőrzését; ennek részletei az ismételt
kérések szabályánál szerepelnek.

## Tranzakció, zárolás és visszagörgetés

A teljes rendelésnek és a hozzá tartozó történetnek együtt kell létrejönnie.
Nem maradhat félkész PO, hiányos tételsor vagy sikeresnek jelölt PR egy
sikertelen generálás után. A kapcsolódó módosítások ezért egyetlen
adatbázis-tranzakcióban történnek: vagy együtt sikerülnek, vagy a művelet
visszagörgeti őket.

A tranzakció része:

- a PR sorának, a PR-tételeknek és a Proposal eredetkapcsolati soroknak a
  `lockForUpdate()` zárolása;
- a pillanatképhez felhasznált Supplier, cikk (`Item`) és cikk–beszállító
  kapcsolat (`ItemSupplier`) sorok zárolása;
- a készültség ismételt értékelése;
- a PO fejlécének és minden tételének létrehozása, a pillanatképekkel és
  eredetkapcsolatokkal;
- az auditbejegyzés;
- majd a PR és a PR-tételek státuszváltása.

`NOT READY` vagy bármely írási, illetve audithiba az egész megkísérelt
generálást visszagörgeti; az Approved PR Approved marad.

A készültségi ellenőrzés a végrehajtási tranzakción belül, a PR zárolása után
fut. A `READY` eredmény tételenkénti `item_supplier_id` értéke azonosítja a
végrehajtási forrást. A történeti másolat elkészítése előtt a Repository ezeket
a forrásrekordokat ismét az adatbázisból tölti be. A Service nem futtat ettől
eltérő forrásalkalmassági algoritmust.

Az ADR rögzíti a PR zárolását követő ellenőrzést és a zárolandó adatok körét,
de nem ad teljes, táblák közötti és táblán belüli zárolási sorrendet. A fenti
felsorolás nem egészíti ki ilyen sorrenddel az elfogadott döntést.

## A beszállító elsődleges forrása

A rendelés ugyanannak a beszállítónak készül, amely a zárolt beszerzési igényen
szerepel. A PO `supplier_id` értéke kizárólag a zárolt PR `supplier_id`
értékéből származik. Supplier nélküli vagy inaktív Supplierhez tartozó PR-t a
0014 szerinti ellenőrzés blokkol.

A korábbi `supplier_id` kérésmező átmenetileg megmarad kompatibilitási célból,
nem kötelező bemenetként. Ha szerepel, egyeznie kell a PR Supplierével; soha
nem választ és nem ír felül Suppliert. Az új felület nem küldi ezt a mezőt,
és nem kínál újraválasztási lehetőséget.

A PO fejléc `supplier_code_snapshot` és `supplier_name_snapshot` mezője a
létrehozáskori Supplier-azonosságot őrzi ellenőrizhetően. A modellkapcsolat
közben továbbra is az aktuális törzsadatra mutat.

## A mennyiség és a mértékegység forrása

A 0013 már elkülönítette a tervezett mennyiséget a beszállítói feltételekkel
módosított, ténylegesen kért mennyiségtől. A generálás ezt a kész eredményt
viszi tovább, nem számít újra utánpótlást vagy tervezett mennyiséget:

```text
PurchaseRequisitionItem.quantity
→ PurchaseOrderItem.ordered_quantity
```

Az érték változatlanul az Item alapegységében marad. A PO-tétel meglévő `unit`
mezője ennek az alapegységnek a létrehozáskori értékét őrzi. Nincs lebegőpontos
számítás vagy a mennyiség levezetése beszerzési egységben. A beszerzendő mennyiséghez
nem készül új `procurement_quantity` adatbázismező.

A `planned_quantity_snapshot` és `replenishment_excess_quantity_snapshot`
megőrzi a tervezett és kért mennyiség különbségét. Például `10.000` tervezett
mennyiség és `5.000` többlet esetén a PR kért mennyisége és a PO rendelt
mennyisége egyaránt `15.000`.

## A végrehajtási pillanatképek jelentése

A PO-nak meg kell őriznie a létrehozáskor felhasznált beszerzési adatokat.
Egy későbbi törzsadat-változás nem írhatja át észrevétlenül a korábbi rendelés
jelentését. Ezt szolgálják a történetileg változatlan végrehajtási pillanatképek
(`Execution Snapshot`).

A forrás a `READY` eredmény által azonosított aktuális ItemSupplier és a
kapcsolódó, zárolt Supplier-, illetve Item-adat. A megőrzött mezők:

| Adat                                | Snapshot helye                                        | Döntés                                             |
| ----------------------------------- | ----------------------------------------------------- | -------------------------------------------------- |
| Supplier                            | PO `supplier_code_snapshot`, `supplier_name_snapshot` | A létrehozáskori beszállító azonosítása a fejlécen |
| ItemSupplier                        | PO-tétel `item_supplier_id`                           | Forráskapcsolat; null csak örökölt rekordoknál     |
| Item                                | PO-tétel `item_number_snapshot`, `item_name_snapshot` | Történeti cikkazonosítás                           |
| Alapegység                          | meglévő PO-tétel `unit`                               | A létrehozáskori alapegység                        |
| Beszerzési egység                   | `purchase_unit_snapshot`                              | A létrehozáskori beszerzési egység                 |
| Átváltási tényező                   | `conversion_factor_snapshot`                          | 1 beszerzési egység = tényező × alapegység         |
| Referenciaár                        | `unit_price_snapshot`                                 | Null megengedett                                   |
| Pénznem                             | `currency_snapshot`                                   | Null megengedett; az árhoz tartozó érték           |
| Átfutási idő                        | `lead_time_days_snapshot`                             | Null megengedett                                   |
| Minimális rendelési mennyiség (MOQ) | `minimum_order_quantity_snapshot`                     | Null megengedett                                   |
| Rendelési többszörös                | `order_multiple_snapshot`                             | Null megengedett                                   |

Az ItemSupplier `unit_price` a 0007 szerint referenciaár, nem megállapodott
tranzakciós ár. A pillanatkép a létrehozáskori referenciaértéket őrzi, nem
nevezhető beszállító által visszaigazolt árnak.

A Repository nem definiál PO-fejlécszintű pénznemet vagy devizaátváltási
szabályt, ezért a 0015 sem vezet be ilyet. A nullable tételpénznem az adott
nullable árpillanatkép párja. A hiányzó ár vagy pénznem a 0014 szerint
figyelmeztetés, nem további 0015-ös blokkoló feltétel.

## Eredetkapcsolatok és a null értékek határa

A kapcsolatokból követhetőnek kell maradnia, mely beszerzési igényből és mely
forrásból készült a rendelés. A PO `purchase_requisition_id`, a PO-tétel
`purchase_requisition_item_id` és `item_supplier_id` mezője ezt kifejezetten
rögzíti:

```text
PO-tétel → PR-tétel → Proposal források
        ↘ ItemSupplier végrehajtási forrás
```

Az új ItemSupplier forráskapcsolat idegen kulcsa a történeti rekordot védő
restrict törlési szabályt használja. A meglévő PR- és PR-tétel-kapcsolatok
megtartják a projekt szerinti `nullOnDelete` szabályt. A kapcsolódó modellek
logikai törlése (soft delete) és a változatlan pillanatképek együtt őrzik a
történeti értelmezhetőséget.

Az új pillanatkép- és forrásmezők adatbázisban nullable-k, hogy a korábbi PO-khoz
ne kelljen bizonyíthatatlan történeti adatot utólag kitölteni. Az új generálás
minden előírt forrás- és pillanatképmezőt kezel. Ez nem jelenti, hogy minden
érték kötelezően nem null: ahol a fenti szabály nullt enged, a hiányzó adat
pillanatképe is null maradhat. Az új PO-tételek ItemSupplier forrásazonosítója
viszont kötelező; annak null értéke csak örökölt rekordoknál megengedett.

## Életciklus és ismételt kérések

Az Approved PR-ből Draft PO készül, mert a PO-nak külön jóváhagyási és rendelési
életciklusa van. Csak a teljes fejléc, minden tétel és pillanatkép, valamint az
audit sikeres írása után lesz a PR és minden PR-tétel `Ordered`.

Egy ismételt kattintás, párhuzamos kérés vagy újrapróbálkozás nem hozhat létre
észrevétlenül újabb PO-t ugyanahhoz a PR-hez. V1-ben egy PR legfeljebb egy PO-t
hozhat létre. Ez az idempotencia üzleti határa; a sikeres generálás ismétlése
kifejezett `already generated` üzleti validációs hibát ad, nem ismételt sikert.

Az alkalmazás a PR zárolása után előbb a meglévő PO-kapcsolatot ellenőrzi,
majd az életciklust és a készültséget. A
`purchase_orders.purchase_requisition_id` nullable unique adatbázis-korlát a
végső védelem a duplikáció és párhuzamos létrehozás ellen. A kézzel létrehozott,
PR nélküli PO-k továbbra is támogatottak.

A meglévő PO-bizonylatszám formátuma megmarad; a 0015 nem vezet be új
sorszámgenerálót. A unique `order_number` korlát marad a végső ütközésvédelem.
Sikertelen tranzakció nem hagy hátra PO-rekordot. Az ADR nem definiál
automatikus újrapróbálkozási szabályt vagy részletes, alacsony szintű
ütközéskezelést. Nem veszi át a későbbi Dispatch kulcs- és ujjlenyomat-alapú
ismétléskezelését.

## Auditnapló és gyorsítótár

A `purchase_order_generated` auditbejegyzés ugyanabban a tranzakcióban készül,
mint a rendelés. Metaadata a PO ID, PR ID, Supplier ID és a tételek száma.
Különböző egységű mennyiségeket nem összegez. Az audit hibája minden írást
visszagörget.

A tranzakció sikeres lezárása után a meglévő, célzott beszerzési
gyorsítótár-érvénytelenítés fut. Nincs globális gyorsítótár-ürítés.

## Készültségi hibák és a specifikáció részletessége

`NOT READY` esetén a Service `ValidationException`-t ad a 0014 strukturált
blokkoló okkódjaiból képzett, lokalizált `execution_readiness` hibákkal.
A felület megjeleníti ezeket, de a backend ellenőrzés közvetlen POST esetén is
kötelező. A 0014 figyelmeztetései, köztük a `PRICE_MISSING` és
`EXPECTED_LATE_SUPPLY`, nem blokkolják a generálást.

Az ADR jogosultsági ellenőrzést követel meg, de nem részletezi a pontos
permission- és policy-szerződést, illetve a végrehajtó és a létrehozómezők
összerendelését. Ezek hiánya nem új jogosultsági vagy szereplő-hozzárendelési
szabály.

Az `expected_delivery_date` generáláskori forrását, mező-hozzárendelését és
hiányzó adat esetén alkalmazandó helyettesítő értékét ez az ADR nem definiálja.
A későbbi 0016 dátum-összehasonlítási szabálya nem pótolja ezt a levezetést.

## Végrehajtási és későbbi folyamatok közötti határok

A PO-generálás nem választ és nem cserél Suppliert, nem számít utánpótlást,
nem módosít Proposal-, Requirement-, nettósítási vagy fedezeti hozzárendelési
(pegging) adatot. Nem küldi ki a PO-t, és nem hoz létre áruátvételt
(`Goods Receipt`), `StockBalance`, `StockMovement` vagy `StockReservation`
rekordot.

A [0016 Purchase Order Dispatch / Supplier Acknowledgement](0016-purchase-order-dispatch-supplier-acknowledgement.md)
a későbbi továbbítás és beszállítói válasz külön döntése. A fogalmak határa:

- PO-generálás: a rendelés belső létrehozása; nem Dispatch.
- Dispatch: a rendelés továbbításának vagy továbbítási kísérletének ténye;
  nem beszállítói visszaigazolás (`Supplier Acknowledgement`).
- Supplier Acknowledgement: a beszállító válasza; nem Goods Receipt.
- Goods Receipt: az áru beérkezésének rögzítése; nem készletmozgás
  (`Stock Movement`). A könyvelése hozza létre a készletmozgást.

Ezek külön üzleti műveletek, nem a PO-generálás automatikus további lépései.
A 0015 nem valósítja meg a 0016 továbbítási vagy visszaigazolási mechanizmusait.
