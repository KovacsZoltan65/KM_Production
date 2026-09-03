# Replenishment Strategies

- **Állapot:** Elfogadva és implementálva
- **Dátum:** 2026-08-16
- **Kapcsolódó döntések:** [0007 Item Supplier](0007-item-supplier-procurement-source.md), [0011 Purchase Requisition Consolidation](0011-purchase-requisition-consolidation.md), [0012 Supplier Selection](0012-supplier-selection.md)

## Üzleti probléma

A tervezett beszerzési mennyiség nem mindig rendelhető meg változtatás nélkül.
A kiválasztott beszállító előírhat legkisebb rendelési mennyiséget (`MOQ`) vagy
rendelési többszöröst (`order_multiple`). A döntéshozónak még a jóváhagyás előtt
látnia kell, hogy emiatt ténylegesen mennyit kell kérni, és mekkora többlet
keletkezik a tervezett igényhez képest.

A 0013 ezt a ténylegesen kért mennyiséget számítja ki a már kiválasztott
beszerzési forrás Supplier-specifikus feltételei alapján. Nem dönt ellátási
stratégiáról vagy Supplierről, nem számít újra nettó szükségletet, és nem hoz
létre Purchase Ordert vagy készletváltozást.

```text
Approved Proposal forrásmennyiség
→ Draft PR tervezett mennyiség
+ a kiválasztott ItemSupplier MOQ / order multiple szabálya
→ Draft PR ténylegesen kért utánpótlási mennyiség
```

## A tervezett, kért és többletmennyiség jelentése

A számítás után a `PurchaseRequisitionItem.quantity` tartalmazza a ténylegesen
kért utánpótlási mennyiséget. A külön `planned_quantity` változatlan
pillanatképként őrzi a PR-be konszolidált tervezési mennyiséget.

A tervezett mennyiség és a beszerzendő mennyiség két külön üzleti tény. A
modellben nincs `procurement_quantity` nevű mező: a beszerzendő mennyiséget a
meglévő `PurchaseRequisitionItem.quantity` tárolja. Ez a technikai elnevezés nem
változtat a két mennyiség üzleti különbségén.

Konszolidált tételnél a mérvadó összefüggés:

```text
planned_quantity = sum(PurchaseRequisitionItemProposalSource.quantity)
quantity = a szabályokkal módosított, ténylegesen kért mennyiség
replenishment_excess_quantity = quantity - planned_quantity
```

A forrássorok és az Approved `SupplyProposal.proposed_quantity` nem változnak.
Az 0011 szerinti forrásösszeg-egyezés a konszolidálás időpontjára továbbra is
érvényes. A későbbi állapotban a fenti háromtagú összefüggés írja le a
mennyiségeket.

A meglévő kézi és örökölt PR-tételek migrációkor `planned_quantity = quantity`
kezdőértéket kapnak. Az új kézi tételek ugyanezt a pillanatképet írják. Ha egy
tételnek Proposal forrássorai vannak, újraszámítás előtt azok pontos összegének
kötelezően egyeznie kell a `planned_quantity` értékkel.

V1-ben nincs külön utánpótlási dokumentum. A Supply Proposal módosítása
elveszítené a történeti tervezési döntést, a szabályok kizárólag PO-készítéskori
alkalmazása pedig a jóváhagyásig elrejtené a várható többletet. A Draft PR Item
a legkorábbi végrehajtás-előkészítő dokumentum, amelynél a Supplier már ismert,
de külső kötelezettség még nem jött létre.

## V1 mennyiségi szabály

A stratégia egyértelműen levezethető az `ItemSupplier.minimum_order_quantity`
és `order_multiple` mezőből, ezért nincs külön tárolt strategy enum.

| MOQ         | Order multiple | Strategy                 | Szabály                                                           |
| ----------- | -------------- | ------------------------ | ----------------------------------------------------------------- |
| null vagy 0 | null           | `exact`                  | `adjusted = base`                                                 |
| pozitív     | null           | `moq`                    | `adjusted = max(base, MOQ)`                                       |
| null vagy 0 | pozitív        | `order_multiple`         | `adjusted = roundUp(base, multiple)`                              |
| pozitív     | pozitív        | `moq_and_order_multiple` | `candidate = max(base, MOQ)`, majd `roundUp(candidate, multiple)` |

Az MOQ a legkisebb rendelhető mennyiség. Az order multiple azt jelenti, hogy a
mennyiség csak a megadott érték egész számú többszöröse lehet. A kerekítés
mindig felfelé történik, ezért a kért mennyiség soha nem lehet kisebb a
tervezett mennyiségnél.

Példák ugyanabban az alap-mértékegységben:

- Ha a tervezett mennyiség 70, az MOQ 100, és nincs order multiple, akkor a
  kért mennyiség 100.
- Ha a tervezett mennyiség 70, nincs MOQ, az order multiple pedig 24, akkor a
  kért mennyiség 72.
- Ha a tervezett mennyiség 70, az MOQ 100, az order multiple pedig 24, akkor a
  rendszer először 100-at választ, majd felfelé kerekít 120-ra.

Nulla tervezett igény eredménye nulla; az MOQ önmagában nem indít beszerzést.
Negatív alapmennyiség, negatív MOQ, nem pozitív order multiple vagy nem pozitív
conversion factor üzleti tartományhiba. A service ezeket a szabályokat a kérés
validációjától függetlenül, azonnali hibával védi.

Minden mennyiségi művelet integer thousandths ábrázolással történik. Nincs
lebegőpontos osztás vagy `ceil(float)`. A támogatott pontosság három tizedes,
összhangban a 0009–0011 döntésekkel.

## Mértékegység és conversion factor

A 0007 mérvadó szabálya szerint:

- a `planned_quantity`, az MOQ és az order multiple az Item alap-mértékegységében
  értendő;
- `1 purchase_unit = conversion_factor × Item base unit`;
- a `purchase_unit` és a `conversion_factor` a beszerzési csomagolást írja le,
  de V1-ben nincs szükség átváltásra a mennyiségi szabály alkalmazásához.

A 0013 ezért nem értelmezi az MOQ-t purchase unitként, és nem végez hallgatólagos
átváltást. Az üres purchase unit vagy a nem pozitív conversion factor ettől még
ellentmondásos beszerzési szabály, ezért blokkolja a számítást. A tört
conversion factor sem vezet lebegőpontos számításhoz, mert a V1 eredménye az
alap-mértékegységben marad.

## A beszerzési forrás meghatározása

A számítás előfeltétele egy Supplierrel már rendelkező PR. Minden egyedi PR
Itemhez pontosan egy olyan érvényes `ItemSupplier` szükséges, amely a fejléc
Supplieréhez tartozik. Az `item_id + supplier_id` adatbázis-egyediség miatt több
rekord integritási hiba, nulla érvényes rekord pedig üzleti tartományhiba.

A forrásnak aktívnak, approvednak és az aktuális üzleti napon érvényesnek kell
lennie, aktív Itemmel és aktív Supplierrel. Az érvényességi nap jelentése
megegyezik a 0012 döntéssel: a művelet napja, nem a `required_at` vagy a
`proposed_supply_at`.

Supplier nélküli PR esetén nincs automatikus preferred vagy más
Supplier-választás. Többtételes PR minden sora a saját `ItemSupplier` szabálya
alapján számolódik. Az eltérő mértékegységű mennyiségekből nem készül
fejlécszintű összeg.

## Életciklus, indítás és újraszámítás

V1-ben a felhasználó külön `Calculate Replenishment` művelettel indítja a
számítást. A Supplier Selectionnek nincs rejtett mennyiségi mellékhatása. A
művelet csak Draft PR-en engedélyezett; Requested, Approved, Ordered és Cancelled
állapotban tiltott.

Az egész PR számítása egyetlen tranzakcióban, a PR sorának zárolása mellett
történik. A rendszer előbb minden forrást és eredményt ellenőriz, majd minden
tételt együtt ment. Hiba esetén egyik tétel sem változik.

Minden újraszámítás a változatlan `planned_quantity` értékből indul, nem az előző
`quantity` eredményből. Emiatt azonos bemenet ugyanazt az eredményt adja, és a
szabályok változásakor nem halmozódik kerekítési eltérés.

A jelenlegi Draft PR-szerkesztési folyamat csak a notes mezőt módosítja;
kézi quantity felülírás nincs. V1 nem vezet be ilyen felülírást. Egy későbbi
Supplier-váltási vagy mennyiségszerkesztési folyamatnak külön szabályt kell adnia
az elavulás és az újraszámítás kezelésére.

A PR Item tárolja a számítás időpontját (`replenishment_calculated_at`), az
aktuális eredményt és többletet, a forrás azonosítóját, a számításkori MOQ és
order multiple pillanatképét, valamint a levezetett strategy címkét. Ez
pillanatkép, ezért az `ItemSupplier` későbbi változásakor elavulhat. Nincs
bizonyíthatatlan `is_fresh` jelző; a felhasználó kifejezett újraszámítással
frissíti az eredményt.

## Audit

A tárolt mennyiség módosítása
`purchase_requisition_replenishment_calculated` üzleti eseményt hoz létre. Egy
kötegelt esemény tartalmazza a PR azonosítóját, a tételek számát, a megváltozott
tételek számát, valamint tételenként a planned, adjusted, excess, unit,
`ItemSupplier`, strategy, MOQ és multiple adatot. Az eltérő mértékegységű
mennyiségeket nem összesíti.

Az audit ugyanabban a tranzakcióban készül. Ha az audit írása hibát ad, a
mennyiségi változtatások is visszagörgetődnek.

## Jóváhagyási és Purchase Order-határ

A 0013 nem módosítja automatikusan a meglévő PR-jóváhagyási szabályt. V1-ben a
kifejezett számítás nem kötelező jóváhagyási előfeltétel, mert a rendszer kézi
és örökölt PR-folyamatokat is tartalmaz. E folyamatok kötelező migrációjához
külön döntés szükséges.

Ha a számítás lefutott, a PR `quantity` mezője a későbbi PO-generálás mérvadó,
ténylegesen kért mennyisége. A 0013 azonban nem hagy jóvá PR-t, és nem generál
PO-t.

A Supplier vagy az `ItemSupplier` szabályának változása után a tárolt számítás
elavulhat. A későbbi végrehajtási készenléti vagy PO-generálási modulnak újra
kell ellenőriznie a forrás érvényességét, a számítás meglétét és frissességét,
valamint döntenie kell a történeti Supplier-szabály pillanatképéről.

## Határok

A 0013 nem módosítja a Material Requirementet, a 0009 netting eredményét, a
0010 pegjeit, a Supply Proposal mennyiségét vagy a Proposal forrásmennyiséget.
Nem hoz létre PR-jóváhagyást, Purchase Ordert, Goods Receiptet,
`StockBalance`-módosítást, `StockMovement` vagy `StockReservation` rekordot.
Safety stock, reorder point, forecast, purchase-to-stock és a teljes inventory
policy engine nem része a V1-nek.

## Következmények

- A jóváhagyás előtt láthatóvá válik a Supplier feltételei miatti tényleges
  kérés és a tervezéshez képesti többlet.
- A Proposal eredetkapcsolata változatlan marad, miközben a kért mennyiség
  közvetlenül továbbvihető a későbbi PO-ba.
- A tárolt számítás elavulását V1-ben időbélyeg és kifejezett újraszámítás
  kezeli, nem függőségi gráf.
- A következő külön döntési terület a végrehajtási készenlét és a Purchase Order
  létrehozásának határa: kötelező frissesség, forrás- és szabálypillanatkép, ár
  és jóváhagyási előfeltétel.
