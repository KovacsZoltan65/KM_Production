# Purchase Requisition Execution Readiness

- **Állapot:** Elfogadva és implementálva
- **Dátum:** 2026-08-24
- **Kapcsolódó döntések:** [0011 Purchase Requisition Consolidation](0011-purchase-requisition-consolidation.md), [0012 Supplier Selection](0012-supplier-selection.md), [0013 Replenishment Strategies](0013-replenishment-strategies.md)

## Üzleti probléma és döntés

Egy belső beszerzési igény (`Purchase Requisition`, PR) már lehet jóváhagyott,
miközben az aktuális adatok alapján még nem készíthető belőle biztonságosan
beszerzési rendelés (`Purchase Order`, PO). Például időközben inaktívvá válhat
a beszállító, vagy elavulhat a beszerzendő mennyiség korábbi számítása.

A jóváhagyás és a végrehajtási készültség (`Execution Readiness`) két külön
kérdésre válaszol:

- Jóváhagyás: „Elfogadtuk ezt a beszerzési igényt?”
- Végrehajtási készültség: „Ha most Purchase Ordert készítenénk belőle, minden
  szükséges aktuális adat rendben van?”

Az értékelés adatot nem módosító ellenőrzés. Az értékelés időpontjában érvényes
beszállítókat, beszerzési forrásokat, mennyiségeket és eredetkapcsolatokat vizsgálja.
Az eredetkapcsolat azt őrzi, mely korábbi igényből vagy javaslatból származik
a PR tétele.

```text
Approved Purchase Requisition
→ aktuális beszállító-, forrás-, cikk- és mennyiségi ellenőrzések
→ READY vagy NOT READY
```

A `READY` azt jelenti, hogy az értékelés nem talált blokkoló hibát. Nem jelent
létrehozott PO-t vagy későbbre szóló állandó végrehajtási engedélyt. A
`NOT READY` nem a PR elutasítása: az igény jóváhagyása megmarad, az értékelés
nem változtat életciklus-státuszt.

Az Execution Readiness nem választ beszállítót, nem számít utánpótlási
mennyiséget, nem foglal készletet, és nem hoz létre PO-t. A rendelés létrehozása
a [0015 Purchase Order Generation](0015-purchase-order-generation.md) külön
végrehajtási művelete.

## Egyszerű példa és az eredmény időbeli érvényessége

Egy Approved PR egy aktív cikkből 15 darabot kér. A beszállító (`Supplier`)
aktív, a cikkhez (`Item`) pontosan egy megfelelő, aktuálisan érvényes
cikk–beszállító kapcsolat (`ItemSupplier`) tartozik. A PR egysége a cikk
alapegysége. A beszerzési egység nem üres, az átváltási tényező pozitív.

A 0013 szerinti utánpótlási számítás megtörtént és aktuális: a tervezett
mennyiség `10.000`, a többlet `5.000`, a kért mennyiség `15.000`. A Proposal
forrásmennyiségek összege `10.000`, és a többi alább felsorolt feltétel is
rendben van. Az értékelés `READY` eredményt ad. Ettől még nem jön létre PO.

Később a Supplier inaktívvá válik. Az új értékelés már `NOT READY` eredményt
ad. Nem a korábbi eredmény romlott el: az üzleti tények változtak meg. A PR
jóváhagyása ettől nem lesz elutasítás, de a rendelés létrehozása blokkolt.

## Számított eredmény és adatmegőrzés

V1-ben az eredmény kizárólag számított adat, nem kerül adatbázisba. Tartalmazza:

- a PR azonosítóját;
- az `is_ready` eredményt;
- a meghatározott sorrendbe rendezett, strukturált blokkoló hibákat és
  figyelmeztetéseket;
- a tételszintű eredményeket;
- az ellenőrzés `checked_at` időpontját.

Nincs `is_ready` adatbázismező vagy tartósan érvényes készültségi pillanatkép.
Így nincs külön tárolt eredmény, amelynek elavulását rejtett szabállyal kellene
kezelni.

Az értékelés olvasási művelet: nem ír auditeseményt, nem módosít PR-t, PR-tételt,
Supply Proposalt, ItemSuppliert, életciklus-státuszt, mennyiséget, készletet vagy
végrehajtási dokumentumot. Elavult utánpótlási számítás esetén külön, kifejezett
újraszámítás szükséges; az értékelés nem hívja az utánpótlási Service-t. Ez nem
vezet be új újraszámítási életciklust: annak feltételeit továbbra is a 0013 adja.

## Jóváhagyási előfeltétel

Csak `PurchaseRequisitionStatus::Approved` lehet végrehajtásra kész. A `Draft`
és `Requested` még jóváhagyás előtti állapot. `Ordered` esetén a végrehajtás
már megtörtént, `Cancelled` esetén pedig tiltott. Minden más életciklus-állapot
`PR_NOT_APPROVED` blokkoló hibát ad.

A jóváhagyás nem futtat automatikusan teljes készültségi ellenőrzést, mert a
jóváhagyás és a végrehajtás külön döntési határ.

## Beszállító, cikk és beszerzési forrás

A teljes PR-hez már ismert, aktív beszállító szükséges. A PR fejléc
`supplier_id` mezője kötelező, és a kapcsolódó Suppliernek aktívnak kell lennie.
Az értékelés nem választ és nem cserél automatikusan Suppliert.

Minden PR-tételhez szükséges:

- létező és aktív Item;
- pozitív kért mennyiség;
- az Item aktuális alapegységével egyező PR-egység;
- pontosan egy, a fejléc Supplieréhez tartozó végrehajtási forrás;
- aktív, approved és az értékelés aktuális üzleti napján érvényes ItemSupplier;
- aktív Supplier és Item;
- nem üres beszerzési egység és pozitív átváltási tényező.

Az érvényességi nap az értékelés aktuális üzleti napja, ugyanúgy, mint a
0012/0013 műveleteknél. A `required_at` és `proposed_supply_at` a szükséglet és
a tervezett ellátás időzítését írja le, nem a forrás érvényességi napját.

A Repository egy tömeges lekérdezéssel ellenőrzi az alkalmazható forrásokat;
a tételenkénti N+1 lekérdezés tilos. Nulla megfelelő forrás
`ITEM_SUPPLIER_INVALID`, több forrás `ITEM_SUPPLIER_AMBIGUOUS` blokkoló hibát
ad. A Service nem választ `first()` rekordot.

## Az utánpótlási számítás megléte és frissessége

Végrehajtás előtt minden tételen bizonyíthatóan le kell futnia a 0013 szerinti
utánpótlási számításnak. Ez akkor is szükséges, ha a szabály nem növeli meg a
tervezett mennyiséget: az `exact` stratégia is kifejezett számítás eredménye.

Szükséges a `planned_quantity`, `quantity`, `replenishment_excess_quantity`,
`replenishment_item_supplier_id`, a stratégia és a
`replenishment_calculated_at`. A null minimális rendelési mennyiség
(`Minimum Order Quantity`, MOQ) és rendelési többszörös (`order multiple`) nem
azonos a hiányzó számítással.

A korábbi számítás csak akkor aktuális, ha a forrás és a számítást meghatározó
adatok még egyeznek a tárolt pillanatképpel. V1-ben az ellenőrzések:

- az aktuális alkalmazható ItemSupplier azonosítója megegyezik a
  `replenishment_item_supplier_id` értékkel;
- az aktuális MOQ megegyezik a tárolt MOQ-pillanatképpel;
- az aktuális rendelési többszörös megegyezik a tárolt pillanatképpel;
- az aktuálisan levezetett stratégia megegyezik a tárolt stratégiával;
- Proposal eredetkapcsolat esetén a `planned_quantity` megegyezik a jelenlegi
  forrásmennyiségek összegével.

Eltérés `REPLENISHMENT_STALE`, hiányzó számítási metaadat
`REPLENISHMENT_NOT_CALCULATED` blokkoló hibát ad.

Az átváltási tényező (`conversion factor`) nincs pillanatképként a PR-tételen.
A V1 PO mennyisége és egysége az Item alapegységében marad, ezért az átváltási
tényező változása önmagában nem elavulási hiba. Az aktuális tényezőnek ettől
még pozitívnak kell lennie. A beszerzési egység, az átváltás és az ár történeti
pillanatképének megőrzése a 0015 szerinti PO-tétel felelőssége.

## Mennyiségek és eredetkapcsolatok

A kért mennyiség nem lehet kisebb a tervezett mennyiségnél. A külön tárolt
beszerzési többlet magyarázza a kettő eltérését. Minden összehasonlítás pontos,
ezredeket egész számként kezelő ábrázolást használ:

```text
quantity > 0
quantity >= planned_quantity
planned_quantity + replenishment_excess_quantity == quantity
```

Proposal forráskapcsolattal rendelkező tételnél ezen felül:

```text
sum(PurchaseRequisitionItemProposalSource.quantity) == planned_quantity
```

A Proposal forrásmennyiségek összege a tervezett mennyiséghez igazodik, nem az
utánpótlási szabállyal módosított kért mennyiséghez. Az értékelés jelzi az
összefüggés sérülését, de nem javít adatot.

## Blokkoló hibák és figyelmeztetések

Blokkoló hiba mellett a végrehajtás nem kezdhető meg. Ilyen az életciklus-,
beszállító-, cikk-, forrás-, utánpótlási, mennyiségi vagy eredetkapcsolati hiba.
A PR akkor kész, ha sem PR-, sem tételszinten nincs blokkoló hiba.

A figyelmeztetés nem állítja az `is_ready` értékét false-ra. Figyelmeztetés:

- a hiányzó referenciaár vagy pénznem, mert a PO-tétel ár nélkül is létrehozható;
- ha az átfutási idő alapján a `required_at` dátumig már nem várható beérkezés;
- a hiányzó vagy múltbeli szükségleti dátum.

A késés V1-ben tervezési kockázat, nem végrehajtási tilalom. A Service okkódokat
és paramétereket ad vissza, nem lokalizált szöveget. A sorrend enum-prioritás
alapján determinisztikus: azonos feltételeknél ugyanaz a sorrend. Felesleges
duplikáció tilos. Az ADR nem sorolja fel a teljes okkódkatalógust és a pontos
prioritási sorrendet; ez a magyarázat nem egészíti ki új kódokkal vagy szabállyal.

## Dátumok, ár és történeti határ

Hiányzó `required_at` esetén `REQUIRED_DATE_MISSING`, múltbeli szükségleti
dátumnál `REQUIRED_DATE_PASSED` figyelmeztetés keletkezik.
`EXPECTED_LATE_SUPPLY` figyelmeztetés keletkezik, ha:

- az aktuális üzleti nap és a forrás `lead_time_days` értékének összege későbbi
  a szükségleti dátumnál; vagy
- a `proposed_supply_at` későbbi annál.

Új ütemezési számítókomponens nem készül.

Hiányzó ItemSupplier `unit_price` vagy `currency` esetén `PRICE_MISSING`
figyelmeztetés keletkezik. Az ADR elfogadásakor a meglévő PurchaseOrderItem
adatszerződés nem tárolt árat; ezért az ár hiánya nem lett blokkoló feltétel.
A 0014 nem hoz létre PO-pillanatképmezőket. A későbbi 0015 feladata az ár és a
forrásadatok történeti megőrzése.

## Párhuzamos működés és a 0015 határa

Az eredmény az értékelés pillanatát írja le, és nem foglal le semmit. A 0015
nem támaszkodhat egy korábbi felületi eredményre: a PO-generálás saját
adatbázis-tranzakcióján belül, a PR sorának zárolása után kötelező ugyanazt a
készültségi ellenőrzést újra elvégeznie. Csak az így kapott `READY` eredmény
után hozhat létre PO-t és történeti forrás-, ár- és egységpillanatképet.

Az ADR elfogadásakor a meglévő
`PurchaseRequisitionService::generatePurchaseOrder()` útvonal csak az Approved
státuszt és a Supplier eltérését ellenőrizte. Az akkori minősítése
`LEGACY / NOT EXECUTION-READY GUARDED`. A védelem megerősítése és a készültségi
ellenőrzés tranzakciós beépítése a 0015 feladata. A 0014 önmagában csak a
meglévő felületi műveletet tiltja le, ha a részletes oldalon számított eredmény
`NOT READY`.

A fenti zárolási és tranzakciós követelmény a PO-generálásra vonatkozik. Az ADR
nem határozza meg az önálló készültségi értékelés részletes tranzakciós
izolációját, zárolását vagy pontos jogosultsági és policy-szerződését. Az
adatot nem módosító jelleg önmagában nem jelent zárolásmentességi előírást.

## Határok és következmények

A 0014 nem generál vagy módosít Purchase Ordert, áruátvételt (`Goods Receipt`),
`StockMovement` vagy `StockReservation` rekordot, illetve készletet. Nem
módosítja a 0009 nettósítását, a 0010 fedezeti hozzárendeléseit (pegging), a
Supply Proposal mennyiségét vagy eredetkapcsolatát, a PR mennyiségét,
Supplierét vagy életciklus-állapotát. Nem tárol örökre érvényes készültségi
jelzőt, és nem auditálja a tiszta olvasást.

V1-ben csak a PR részletes oldalán számít készültséget. A listanézet nem kap
soronkénti értékelést, így a funkció nem indít a teljes listára tételenkénti
N+1 lekérdezést vagy tömeges készültségi számítást.
