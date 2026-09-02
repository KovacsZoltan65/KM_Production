# Beszerzés

## Cél

A beszerzési folyamat biztosítja, hogy a gyártáshoz szükséges anyagok a megfelelő időben, mennyiségben és dokumentumokkal rendelkezésre álljanak. A KM_Production külön kezeli a szükségletet, a tervezési javaslatot, az emberi jóváhagyást, a beszerzési dokumentumokat, a beszállító válaszát, az áruátvételt és a készletváltozást.

Ezek egymást követő, de nem azonos üzleti tények:

```text
Material Requirement
→ Supply Proposal
→ Purchase Requisition
→ Purchase Order
→ Dispatch
→ Supplier Acknowledgement
→ Goods Receipt
→ Stock Movement
```

Az ábra fogalmi kapcsolatot mutat, nem automatikus folyamatot. Az egyes lépésekhez az alábbiak szerint külön felhasználói döntés, jóváhagyás, ellenőrzés vagy végrehajtási művelet szükséges.

A `Material Requirement → Supply Proposal` nyíl sem jelent közvetlenül tárolt kapcsolatot. V1-ben a Supply Proposalt jogosult felhasználó hozza létre; az elfogadott modell nem kapcsolja azt közvetlenül Material Requirement rekordhoz.

## A folyamat üzleti határai

1. A `Material Requirement` megmutatja, milyen Itemből, mennyi és mikorra szükséges a gyártáshoz. A netting kiszámíthatja, hogy ebből mennyi marad fedezetlen, de nem hoz létre beszerzési dokumentumot.
2. A `Supply Proposal` javaslat az Item tervezett fedezésére. V1-ben jogosult felhasználó hozza létre, majd külön életciklus-művelettel javasolható és hagyható jóvá. A jóváhagyott javaslat még nem Purchase Requisition és nem végrehajtás.
3. A `Purchase Requisition` belső beszerzési igény. Jóváhagyott, `Purchase` stratégiájú Supply Proposalokból külön konszolidációs művelet hozhat létre `Draft` dokumentumot. A Supply Proposal jóváhagyása nem hagyja jóvá automatikusan a Purchase Requisitiont.
4. Ha a Draft Purchase Requisitionnek még nincs Supplierje, jogosult felhasználó külön Supplier Selection művelettel választ a minden tételhez közösen alkalmazható beszállítójelöltek közül. A rendszer nem választ automatikusan preferált, első vagy legolcsóbb Suppliert.
5. A beszerzendő mennyiség kiszámítása külön `Calculate Replenishment` művelet. Ez alkalmazza a kiválasztott `ItemSupplier` minimális rendelési mennyiségét és rendelési többszörösét. Nem hagyja jóvá a Purchase Requisitiont.
6. A Purchase Requisition jóváhagyása külön üzleti döntés. Az Execution Readiness ezután csak azt ellenőrzi, hogy a végrehajtáshoz szükséges aktuális Supplier-, forrás-, mennyiségi és eredetkapcsolati feltételek még érvényesek-e. Az ellenőrzés önmagában nem hoz létre Purchase Ordert.
7. A Purchase Order létrehozása külön, jogosultsághoz és ismételt Execution Readiness-ellenőrzéshez kötött művelet. Egy végrehajtásra kész, `Approved` Purchase Requisitionből `Draft` Purchase Order készül.
8. A Purchase Order létrejötte nem bizonyítja, hogy a rendelést elküldték a Suppliernek. A külső átadás vagy annak kísérlete külön `Dispatch`.
9. A Supplier válasza külön `Supplier Acknowledgement`. Ez nem módosítja automatikusan a Purchase Ordert, és nem bizonyítja az áru beérkezését.
10. A `Goods Receipt` az áru beérkezésének rögzített üzleti eseménye. A könyvelési művelete hozza létre a `Stock Movement` rekordot, amely a készlet mennyiségi változásának elsődleges forrása.

## Supplier és ItemSupplier

A `Supplier` az a beszállító, amely beszerzett anyagot, szolgáltatást vagy kapcsolódó dokumentumot biztosít. Törzsadatai támogathatják a rendeléseket, az átvételi történetet, az átfutási idő elemzését, a minőségi teljesítmény követését és a beszállítói kockázat értékelését.

Az `ItemSupplier` egy Item és egy Supplier közötti beszerzési kapcsolat. Azt rögzíti, hogy az Item milyen feltételekkel szerezhető be az adott Suppliertől. Ilyen feltétel lehet például:

- a beszállítói cikkszám;
- a beszerzési egység és az alapegységre váltási tényező;
- a minimális rendelési mennyiség (`MOQ`) és a rendelési többszörös;
- az átfutási idő;
- a referenciaár és a pénznem;
- a prioritás, valamint a preferált, jóváhagyott és aktív állapot;
- az üzleti érvényesség kezdete és vége.

Az ItemSupplier nem rendelés, nem Supplier-választás és nem beszállítói ígéret. Supplier Selection, utánpótlási számítás és végrehajtás során csak aktív, jóváhagyott, az adott üzleti napon érvényes forrás használható, amelynek Itemje és Supplierje is aktív. A `preferred` jelölés előnyt jelent, nem kizárólagosságot vagy automatikus kiválasztást.

## Material Requirement, netting és pegging

A `Material Requirement` egy konkrét gyártási szükséglet: megadja a szükséges Itemet, mennyiséget és a legközelebbi ismert rendelkezésre állási dátumot. Nem beszerzési javaslat és nem végrehajtási dokumentum.

A netting időrendben összeveti a szükségletet a fizikailag nyilvántartott, foglalásokkal csökkentett készlettel és a határidőig várható, biztos beérkező mennyiséggel. Biztos beérkező mennyiségnek csak a megfelelő állapotú Purchase Order Item fennmaradó mennyisége számít. A Purchase Requisition és a Supply Proposal egyik állapotban sem biztos beérkező ellátás.

A pegging megmutatja, hogy egy konkrét StockBalance vagy PurchaseOrderItem milyen mennyiséggel fedez egy Material Requirement sort. Ez tervezési magyarázat, nem fizikai készletfoglalás, készletmozgás vagy Supplier-választás.

## Supply Proposal

A `Supply Proposal` auditálható ellátási javaslat egy Item tervezett fedezésére. Elkülöníti azt, amit a tervezés vagy a felhasználó javasol, attól, amit később jóváhagynak és végrehajtanak.

V1-ben a működő stratégia `Purchase`. A Supplier lehet még ismeretlen; a `supplier_id = null` azt jelenti, hogy a beszerzési stratégia eldőlt, de Supplier Selection még nem történt. Ha a javaslat már megnevez Suppliert, akkor az Itemhez aktív, jóváhagyott és aktuálisan érvényes ItemSupplier szükséges. Automatikus Supplier-választás nincs.

Az életciklus:

```text
Draft → Proposed → Approved
                 ↘ Rejected
Draft vagy Proposed → Cancelled
Approved → Cancelled, amíg nincs végrehajtási kapcsolat
```

Csak a `Draft` módosítható. A `Proposed` döntésre vár. A `Rejected` és `Cancelled` lezárt állapot. A `SupplyProposalService` minden átmenetet tranzakcióban, sorzárral és ismételt backend-ellenőrzéssel hajt végre.

## Purchase Requisition

A `Purchase Requisition` belső beszerzési igény. Azt fejezi ki, hogy a vállalat milyen tételeket kíván beszerezni; még nem a Suppliernek továbbított rendelés.

Az igény származhat például gyártási szükségletből, alacsony vagy biztonsági készlet pótlásából, karbantartási igényből vagy tervezett projektből. Az MRP-hez kapcsolódó új, elsődleges út a jóváhagyott Supply Proposalok konszolidációja.

### Létrehozás jóváhagyott javaslatokból

V1-ben jogosult felhasználó választja ki a konszolidálandó Supply Proposalokat. Csak `approved` állapotú, `purchase` stratégiájú, még fel nem használt javaslatok vehetők figyelembe. A rendszer a kiválasztott javaslatokat a következők szerint csoportosítja:

```text
strategy
+ supplier_id
+ required_at
+ proposed_supply_at
```

Az eredmény csoportonként egy `Draft` Purchase Requisition. Azonos csoporton belül az azonos Item és egység javaslatai egy tételbe vonhatók össze. Minden Supply Proposal teljes mennyisége pontosan egy eredetkapcsolatban szerepel; részleges felhasználás nincs. A konszolidáció nem választ Suppliert, nem hagyja jóvá a Purchase Requisitiont, és nem hoz létre Purchase Ordert.

A régi `Material Requirement → Purchase Requisition` út átmenetileg működő kompatibilitási lehetőség, de kivezetésre jelölt. Új MRP-folyamat nem épülhet rá.

### Supplier Selection

Supplier csak Supplier nélküli, `Draft` Purchase Requisitionhöz választható. Többtételes dokumentumnál csak olyan Supplier jelölt lehet, amely minden egyedi Itemhez pontosan egy alkalmazható ItemSupplier kapcsolattal rendelkezik. A jelöltek halmaza ezért az egyes tételekhez alkalmazható Supplier-halmazok metszete.

A jelöltlista aktuálisan számított információ, nem tárolt döntés. A jogosult felhasználó választása a `PurchaseRequisition.supplier_id` mezőben és az auditnaplóban marad meg. A kiválasztás nem módosít mennyiséget vagy státuszt, és nem hagyja jóvá a dokumentumot. Ha nincs közös Supplier, a művelet `NO_ELIGIBLE_SUPPLIER` hibával leáll; nincs automatikus részleges választás vagy dokumentumfelosztás.

A már ugyanahhoz a Supplierhez tartozó Purchase Requisition ismételt kiválasztása az alkalmazhatóság újbóli ellenőrzése után nem módosít adatot és nem készít új auditbejegyzést. Másik Supplier csendes beállítása tilos és ütközési hibát ad.

### Beszerzendő mennyiség

A jóváhagyott Supply Proposalokból származó változatlan tervezett mennyiséget a `planned_quantity` őrzi. A külön `PurchaseRequisitionItem.quantity` az ItemSupplier feltételei után ténylegesen kérendő mennyiség:

```text
planned_quantity
+ replenishment_excess_quantity
= quantity
```

A számítás a minimális rendelési mennyiséget és a rendelési többszöröst alkalmazza, mindig az Item alapegységében és három tizedes pontossággal. A kerekítés felfelé történik. Nulla tervezett mennyiségből az MOQ önmagában nem indít beszerzést.

Az alkalmazott szabályt az ItemSupplier két mennyiségi feltétele határozza meg:

| Minimális rendelési mennyiség | Rendelési többszörös | Eredmény                                                                                                           |
| ----------------------------- | -------------------- | ------------------------------------------------------------------------------------------------------------------ |
| nincs vagy nulla              | nincs                | pontosan a tervezett mennyiség                                                                                     |
| pozitív                       | nincs                | legalább a minimális rendelési mennyiség                                                                           |
| nincs vagy nulla              | pozitív              | a tervezett mennyiség következő egész többszöröse                                                                  |
| pozitív                       | pozitív              | előbb a nagyobb érték a tervezett mennyiség és az MOQ közül, majd felfelé kerekítés a következő egész többszörösre |

A számítás külön felhasználói művelet, csak `Draft` Purchase Requisitionön. A Supplier Selection nem indítja el automatikusan. A tárolt eredmény az ItemSupplier későbbi változása után elavulhat, ezért végrehajtás előtt frissességi ellenőrzés szükséges.

## Execution Readiness

Az Execution Readiness aktuális, csak olvasható értékelés arról, hogy egy `Approved` Purchase Requisition biztonságosan továbbléphet-e a végrehajtásba. Nem azonos a Purchase Requisition jóváhagyásával, és nem hoz létre Purchase Ordert.

Az értékelés többek között ellenőrzi:

- a Purchase Requisition életciklusát;
- az aktív Suppliert, Itemeket és ItemSupplier kapcsolatokat;
- a beszerzési forrás jóváhagyott és aktuálisan érvényes állapotát;
- a beszerzendő mennyiség számításának meglétét és frissességét;
- a pozitív mennyiségeket, az egységeket és az eredetkapcsolatokat.

Blokkoló hiba esetén az eredmény `NOT READY`, és a Purchase Order nem hozható létre. A hiányzó referenciaár vagy a várható késés figyelmeztetés lehet, de az elfogadott szabályok szerint önmagában nem blokkolja a végrehajtást. Az értékelés nem javít adatot, nem választ Suppliert, nem számol újra mennyiséget, és nem tárol tartós `is_ready` állapotot.

## Purchase Order

A `Purchase Order` a formális beszerzési rendelés és üzleti kötelezettség dokumentuma. Létrehozása a rendelési döntést rögzíti a KM_Production rendszerében. Nem bizonyítja, hogy a rendelést továbbították a Suppliernek.

A dokumentum jellemzően azonosítja a Suppliert, a kért anyagokat vagy szolgáltatásokat, a mennyiségeket, a referenciaárakat, a várható szállítási dátumot, valamint a rendelkezésre álló szállítási és hivatkozási adatokat.

Egy `Approved` Purchase Requisition legfeljebb egy Purchase Ordert hozhat létre. A létrehozás külön, jogosultsághoz kötött művelet. A tranzakció zárolja a Purchase Requisitiont és kapcsolódó forrásadatait, majd ismét lefuttatja az Execution Readiness értékelést. Csak `READY` eredmény után készülhet `Draft` Purchase Order. Hiba esetén a teljes művelet visszagördül.

A Purchase Order a létrehozáskori Supplier-, Item-, ItemSupplier-, egység-, átváltási-, referenciaár-, utánpótlási- és eredetkapcsolati adatokat történeti másolatokban őrzi. A mennyisége a Purchase Requisition utánpótlási feltételekkel módosított `quantity` értéke. A létrehozás nem választ Suppliert, nem küldi el a rendelést, nem vételez árut és nem módosít készletet.

## Dispatch

A `Dispatch` a Purchase Order Supplier felé történő átadásának vagy átadási kísérletének auditálható ténye. Elkülönül a Purchase Order belső létrehozásától és jóváhagyásától.

Minden sikeres vagy sikertelen kísérlet külön, nem módosítható rekordként marad meg. Egy későbbi sikertelen újraküldés nem teszi meg nem történtté a korábbi sikeres Dispatch-et. Az ismételt, változatlan kérés ugyanazt a rekordot adja vissza; az azonos idempotenciakulcs eltérő tartalommal kifejezett ütközési hiba.

V1-ben a rendszer nem küld e-mailt, Supplier Portal- vagy EDI-üzenetet. A Dispatch a bejelentkezett felhasználó auditált állítása a külső átadás eredményéről. Új Dispatch csak `Ordered` vagy `PartiallyReceived` Purchase Orderhez, aktív Supplierrel és a szükséges jogosultságokkal rögzíthető.

## Supplier Acknowledgement

A `Supplier Acknowledgement` a Supplier rögzített válasza. Tételenként megadhatja az ígért mennyiséget és szállítási dátumot, vagy kifejezetten elutasíthatja a tételt. A válasz nem írja át a Purchase Ordert, nem jelent áruátvételt, és nem módosít készletet.

Acknowledgement sikeres Dispatch nélkül is rögzíthető, például telefonon érkező, Supplier által kezdeményezett vagy korábbi folyamatból származó válasz esetén. Ilyenkor a válasz forrását, a Supplierhez kötő adatot és a Dispatch hiányának magyarázatát is rögzíteni kell.

A rendszer minden válaszhoz megőrzi, hogy a rögzítéskor mely nem `cancelled` PO-tételekre várt választ. Ha egy ilyen tételhez nincs válaszsor, az `missing`: nem tekinthető sem elfogadottnak, sem elutasítottnak. A válasz teljes pillanatkép, nem csak a korábbi változathoz képest megváltozott adatok listája.

A Supplier későbbi javítása új, teljes, nem módosítható Acknowledgementként kerül a történetbe, és közvetlenül az addig hatályos választ váltja fel. A korábbi válasz megmarad. Egy Purchase Orderhez egyszerre egy hatályos Acknowledgement tartozhat.

Az ígért mennyiséget a rendelt mennyiséggel, az ígért dátumot pedig a rögzített vevői alapdátummal kell összehasonlítani. Az eltérésekből a rendszer számítja az `accepted`, `accepted_with_changes` vagy `rejected` összesített státuszt, valamint a `requires_follow_up` és `requires_replanning` jelzőket. Ezek figyelmeztető információk; nem módosítják automatikusan a Purchase Ordert vagy az MRP számítási alapját.

## Goods Receipt és Stock Movement

A `Goods Receipt` az áru beérkezését rögzíti a Purchase Orderhez kapcsolva. Az átvétel során ellenőrizhető a Supplier, a Purchase Order, a mennyiség, a batch vagy lot, a kísérő dokumentum és a minőségi követelmény. Részleges, elfogadott, karanténba helyezett vagy elutasított mennyiségek eltérhetnek egymástól.

A Goods Receipt nem azonos a készletmozgással. A könyvelt áruátvétel hozza létre a `Stock Movement` rekordot, frissíti az átvett mennyiséget és a Purchase Order áruátvételi státuszát. A Stock Movement a készlet mennyiségi változásának elsődleges üzleti forrása. Készletmennyiséget más dokumentum alapján közvetlenül módosítani tilos.

Az átvételnek meg kell őriznie a kapcsolatot a Supplierrel, a Purchase Orderrel, a szállítólevéllel, az átvett anyagokkal és a kapcsolódó minőségi adatokkal.

## Anyag-elérhetőség, átfutási idő és biztonsági készlet

Az anyag-elérhetőség azt mutatja meg, hogy a gyártás folytatható-e a meglévő vagy várható készlet alapján. Értékelésénél számíthat a fizikailag nyilvántartott készlet, a foglalás, a biztos beérkező Purchase Order-mennyiség, a várható dátum, a minőségi állapot, a biztonsági készlet és a gyártási szükséglet.

Az átfutási idő a rendelési és átvételi esemény közötti várható idő. Befolyásolhatja a gyártástervezést, az utánpótlási döntést, a biztonsági készletet, a beszállítói teljesítmény értékelését és az anyaghiány kockázatát.

A biztonsági készlet a hiány kockázatának csökkentésére tartott többlet. Meghatározásánál figyelembe vehető a beszállítói késés, a kereslet változása, a minőségi elutasítás, a szállítási kockázat és a tervezési bizonytalanság. Szintjét az üzleti kockázathoz és az anyag fontosságához kell igazítani.

## Egyszerű végponttól végpontig példa

Egy gyártási rendeléshez 1000 darab alkatrész szükséges szeptember 20-ra. A netting megállapítja, hogy nincs elegendő szabad készlet vagy időben várható biztos beérkezés. Ez még csak számítási eredmény.

Egy jogosult felhasználó 1000 darabos, `Purchase` stratégiájú Supply Proposalt hoz létre, majd a javaslat külön jóváhagyáson megy át. A jóváhagyott javaslatot a felhasználó konszolidálja egy `Draft` Purchase Requisitionbe. Ha nincs Supplier, a felhasználó a minden tételhez alkalmazható jelöltek közül választ. Ezután külön művelet számítja ki az MOQ és a rendelési többszörös szerinti beszerzendő mennyiséget.

A Purchase Requisition külön jóváhagyása után az Execution Readiness ellenőrzi az aktuális feltételeket. A felhasználó csak `READY` eredmény mellett hozhat létre `Draft` Purchase Ordert. Ez még nem Dispatch.

A rendelés Supplier felé történő átadását külön Dispatch rögzíti. A Supplier válasza külön Acknowledgement, például 900 darab szeptember 22-re. Ez eltérés és újratervezési figyelmeztetés, de nem írja át a rendelést. Amikor az áru ténylegesen megérkezik, Goods Receipt készül; annak könyvelése hozza létre a készletet módosító Stock Movementet.

## Jövőbeli beszerzési intelligencia

A későbbi beszerzési intelligencia támogathatja a Supplier-kockázat, az anyagigény, az utánrendelés, az átfutási idő, a szállítási eltérések, a tanúsítványok és a Purchase Order-egyeztetés elemzését.

Az AI-alapú ajánlás emberi döntést támogathat, de nem kerülheti meg a beszerzési, készlet-, minőségi, jogosultsági vagy auditfolyamatokat.

## Összefoglaló üzleti szabályok

- A Proposal, Approval, Execution és Result külön üzleti szakasz.
- ItemSupplier nem jelent rendelést vagy automatikus Supplier-választást.
- Supply Proposal és Purchase Requisition nem biztos beérkező ellátás.
- A Supply Proposal jóváhagyása nem hagyja jóvá automatikusan a Purchase Requisitiont.
- A Purchase Requisition jóváhagyása nem helyettesíti az Execution Readiness ellenőrzést.
- A Purchase Order létrehozása nem jelent Dispatch-et.
- A Dispatch nem jelent Supplier Acknowledgementet.
- A Supplier Acknowledgement nem jelent áruátvételt vagy automatikus PO-módosítást.
- A Goods Receipt nem azonos a Stock Movementtel; a könyvelése hozza létre a készletmozgást.
- Beszerzett anyag alapértelmezetten nem kap belső gyártási sorozatszámot.
- A Supplier-dokumentumok bizonyítékok, ezért a beszerzési és átvételi rekordokhoz kapcsolva kell megmaradniuk.
- Az áru gyártási felhasználhatósága minőségi ellenőrzéshez köthető.
- A beszerzési történetnek támogatnia kell a Supplier-teljesítmény értékelését és a nyomon követhetőséget.

## Kapcsolódó döntések

- [0007 Item Supplier / Procurement Source](../decisions/0007-item-supplier-procurement-source.md)
- [0008 Supply Proposal](../decisions/0008-supply-proposal.md)
- [0008.5 MRP Foundation Hardening](../decisions/0008-5-mrp-foundation-hardening.md)
- [0009 Material Requirement Netting](../decisions/0009-material-requirement-netting.md)
- [0010 Requirement Pegging](../decisions/0010-requirement-pegging.md)
- [0011 Purchase Requisition Consolidation](../decisions/0011-purchase-requisition-consolidation.md)
- [0012 Supplier Selection](../decisions/0012-supplier-selection.md)
- [0013 Replenishment Strategies](../decisions/0013-replenishment-strategies.md)
- [0014 Purchase Requisition Execution Readiness](../decisions/0014-purchase-requisition-execution-readiness.md)
- [0015 Purchase Order Generation](../decisions/0015-purchase-order-generation.md)
- [0016 Purchase Order Dispatch / Supplier Acknowledgement](../decisions/0016-purchase-order-dispatch-supplier-acknowledgement.md)
