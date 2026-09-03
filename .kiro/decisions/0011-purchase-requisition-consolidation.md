# Purchase Requisition Consolidation

- **Állapot:** Elfogadva és implementálva
- **Dátum:** 2026-08-14
- **Kapcsolódó döntések:** [0006 MRP Architecture](0006-material-requirements-planning-architecture.md), [0007 Item Supplier](0007-item-supplier-procurement-source.md), [0008 Supply Proposal](0008-supply-proposal.md), [0008.5 MRP Foundation Hardening](0008-5-mrp-foundation-hardening.md), [0009 Netting](0009-material-requirement-netting.md), [0010 Pegging](0010-requirement-pegging.md)

## Cél és üzleti probléma

Egy jóváhagyott Supply Proposal még tervezési javaslat, nem Purchase
Requisition és nem beszerzési végrehajtás. Több jóváhagyott javaslat azonban
azonos beszerzési feltételekhez tartozhat. Ezeket érdemes egy közös belső
beszerzési igénybe rendezni úgy, hogy minden javaslat eredete és mennyisége
továbbra is pontosan követhető maradjon.

A konszolidáció erre a kérdésre válaszol:

> Mely kiválasztott, jóváhagyott beszerzési javaslatok kerülhetnek ugyanabba a
> Draft Purchase Requisitionbe anélkül, hogy eltérő beszerzési feltételeket
> kevernénk össze vagy elveszítenénk az eredetüket?

A felhasználó V1-ben kifejezetten kiválasztja a feldolgozandó Supply
Proposalokat. A rendszer a kompatibilis javaslatokat determinisztikus
csoportokba rendezi, majd csoportonként egy Draft Purchase Requisitiont hoz
létre.

```text
Approved Purchase Supply Proposalok
→ kifejezett felhasználói kiválasztás
→ PurchaseRequisitionConsolidationService
→ determinisztikus csoportosítás
→ Draft Purchase Requisition
→ Purchase Requisition Item
→ Proposal-eredetkapcsolat
```

Ez beszerzés-előkészítés. Nem számít újra nettó szükségletet, nem épít
Requirement Pegginget, nem választ Suppliert, nem hagy jóvá Purchase
Requisitiont, és nem hoz létre Purchase Ordert vagy készletváltozást.

## Egyszerű példa

A felhasználó három jóváhagyott, `purchase` stratégiájú Supply Proposalt választ
ki:

- Item A: 100 db, Supplier X;
- Item A: 50 db, Supplier X;
- Item B: 20 db, Supplier nélkül.

Az első két javaslat csak akkor kerülhet ugyanabba a Purchase Requisitionbe, ha
a `strategy`, `supplier_id`, `required_at` és `proposed_supply_at` értékük is
megegyezik. Ha ezek az értékek azonosak, az Item és az egység is megegyezik,
ezért egy 150 darabos Purchase Requisition Item készülhet belőlük.

A harmadik javaslat Supplier nélküli, ezért nem kerülhet Supplier X csoportjába.
Saját kompatibilis csoportjából `supplier_id = null` értékű Draft Purchase
Requisition készülhet. Ez nem hiba: a Supplier kiválasztása a későbbi, külön
Supplier Selection műveletre marad. A rendszer nem választ automatikusan
Suppliert.

## Döntés

### Kiválasztható bemenet és ismételt ellenőrzés

A számítás elsődleges bemenete a `SupplyProposal.proposed_quantity` és az Item
alapegységének a Proposalon tárolt pillanatképe. A régi
`MaterialRequirement.missing_quantity` nem bemenet.

Csak `status = approved` és `strategy = purchase` Supply Proposal választható.
A felhasználó V1-ben kifejezett Proposal-azonosítókat ad meg. A Service az
adatbázis-tranzakción belül, `lockForUpdate()` után ismét ellenőrzi:

- a státuszt és a stratégiát;
- a pozitív, három tizedes pontosságú mennyiséget;
- az Item létezését és aktív állapotát;
- a Proposal egységének egyezését az Item aktuális alapegységével;
- hogy a Proposalhoz még nem tartozik későbbi forráskapcsolat;
- megadott Supplier esetén annak aktív állapotát, valamint az Item–Supplier
  kapcsolat aktív, jóváhagyott és aktuálisan érvényes állapotát a 0007 szerint.

`Draft`, `Proposed`, `Rejected` és `Cancelled` Supply Proposal nem
konszolidálható. Ha a kifejezett kiválasztás bármely azonosítója érvénytelen
vagy már felhasznált, az egész köteg üzleti validációs hibával visszagördül.
Nincs részleges kihagyás vagy részleges siker.

### Csoportosítás és idő

A rendszer csak azonos stratégiájú, Supplierű és tervezési dátumú javaslatokat
helyez közös Purchase Requisitionbe. A pontos fejléc-csoportosítási kulcs:

```text
strategy
+ supplier_id (nullable)
+ required_at (nullable, nap pontosság)
+ proposed_supply_at (nullable, nap pontosság)
```

A kiválasztott Proposalokat `id ASC` sorrendben zárolja. A csoportokat a fenti
kulcs szerint lexikografikus sorrendbe rendezi. Mivel V1-ben csak a `Purchase`
stratégia támogatott, a `strategy` a csoportosítási határ kifejezett része, de
nem kerül külön Purchase Requisition-mezőbe.

A Purchase Requisition fejlécének `required_at` és `proposed_supply_at` mezője
a csoport azonos jelentésű dátumait őrzi. Bármelyik dátum eltérése külön
Purchase Requisitiont eredményez, ezért nem vész el időzítési információ. A
nullable dátum nem kap kitalált helyettesítő értéket.

A meglévő `requested_at` továbbra is a dokumentum létrehozási vagy kérési
időpontja. Nem tervezett szállítási dátum.

### Supplier-szabály

A megadott Supplierrel rendelkező Supply Proposalok csak azonos Supplier esetén
kerülhetnek ugyanabba a Purchase Requisitionbe. A Purchase Requisition
`supplier_id` mezője nullable. A Supplier nélküli jóváhagyott Proposalok külön,
`supplier_id = null` fejlécű Purchase Requisitionbe konszolidálhatók.

A Service nem választ preferált, első, legolcsóbb vagy más Suppliert.

A pénznem nem csoportosítási bemenet, mert a Supply Proposal és a Purchase
Requisition jelenlegi modellje nem tárol pénznem- vagy árpillanatképet. Gyár-
vagy más üzletikörnyezet-adat szintén nincs ezeken az objektumokon, ezért a
rendszer nem talál ki ilyen csoportosítási feltételt.

### Mennyiség és Itemek összevonása

Azonos Purchase Requisition-csoporton belül csak azonos `item_id + unit`
értékű Proposalok olvadnak egy Purchase Requisition Itembe. Külön Itemek
ugyanazon Purchase Requisition külön tételei maradnak.

A Purchase Requisition Item `quantity` értéke a forrásmennyiségek pontos,
szöveges számábrázolásból képzett, három tizedes összege. Lebegőpontos számítás
nincs. Rejtett egységátváltás tilos. MOQ, rendelési többszörös, Supplier-
csomagolás, beszerzésiegység-átváltás és kerekítés nem része a 0011-nek.

V1-ben nincs részleges Proposal-felhasználás. Egy Proposal teljes
`proposed_quantity` értéke pontosan egy Purchase Requisition Item
forrásrekordjába kerül. A konszolidáció előtt és közvetlenül utána teljesül:

```text
sum(proposal source quantities) == purchase requisition item quantity
```

A későbbi 0013-as utánpótlási számítás ezt az invariánst kifejezett
életciklus-határon bővíti. A változatlan forrásösszeg a külön
`planned_quantity`, míg a Purchase Requisition Item `quantity` értéke a
Supplier feltétele szerinti beszerzendő mennyiség:

```text
planned_quantity + replenishment_excess_quantity = quantity
```

A Proposal-forrásrekordok mennyiségét a beszerzési többlet nem növeli.

### Proposal → Purchase Requisition eredetkapcsolat

A `PurchaseRequisitionItemSource` kizárólag a legacy Material Requirement
eredetkapcsolatát tárolja. Nem kap többértelmű nullable vagy polymorphic
forrásmezőt.

Az új, kifejezett `PurchaseRequisitionItemProposalSource` kapcsolat tárolja:

```text
purchase_requisition_item_id
supply_proposal_id
quantity
```

Ez a kapcsolat azt magyarázza meg, mely Supply Proposalból és milyen
mennyiséggel származik a Purchase Requisition Item. Nem Requirement Pegging: a
Pegging azt magyarázza, mely konkrét készlet vagy biztos beérkező ellátás
fedezett egy Material Requirementet.

A `supply_proposal_id` adatbázis-szinten unique. Ez bizonyítja a későbbi
felhasználást, biztosítja az idempotenciát, és megakadályozza, hogy ugyanazt a
Proposal-mennyiséget többször használják fel.

A Proposal életciklus-státusza `Approved` marad; nincs `Converted` státusz. A
már forráshivatkozással rendelkező Proposal `cancel` átmenete tiltott, mert a
konszolidált Approved Proposal későbbi automatikus `Cancelled → Purchase
Requisition` visszagörgetése nincs a feladat hatókörében.

A `purchase_requisition_items.quantity` pontossága `decimal(18,3)` értékre nő,
hogy az összevonás ne szűkítse le a `SupplyProposal.proposed_quantity` teljes
tartományát.

### Idempotencia és ismételt konszolidáció

Egy kifejezetten kiválasztott köteg atomikus: minden módosítás együtt sikerül,
vagy egyik sem marad meg. Ugyanazon Proposalok második konszolidációs kérése
validációs hibát ad. Nem hoz létre duplikált Purchase Requisitiont vagy
forrásrekordot.

Minden köteg új Draft Purchase Requisition dokumentumokat hoz létre. Egy később
érkező Proposal nem módosít automatikusan korábban létrehozott Draft Purchase
Requisitiont. Ez egyértelmű, auditálható köteghatárt tart fenn.

### Életciklus, kód és audit

A létrejövő Purchase Requisition és tételei `Draft` státuszúak. A Supply
Proposal jóváhagyása nem jelenti a Purchase Requisition jóváhagyását. A meglévő
Purchase Requisition-életciklus felel a későbbi szerkesztésért, jóváhagyásért,
visszavonásért és Purchase Order-generálásért.

A beszerzési igény száma a projekt közös `CodeGeneratorService` és
`CodeDefinitionRegistry` szerződéséből készül, `purchase_requisition` típussal.
Nincs erre a műveletre külön, darabszámlálásra épülő kódgenerálás.

Sikeres köteg után egy `purchase_requisition_consolidation_completed`
aktivitásnapló-esemény készül. Tárgya az első létrehozott Purchase Requisition,
adatai pedig a Proposalok, Purchase Requisitionök, tételek és forrásrekordok
darabszámai. Nem szükséges minden forrásrekordhoz külön naplóbejegyzés.

### Tranzakció és párhuzamos végrehajtás

A következők egyetlen adatbázis-tranzakció részei:

- a kiválasztott Proposalok zárolása és ismételt ellenőrzése;
- a Purchase Requisition-fejlécek, tételek és forrásrekordok létrehozása;
- a kötegelt auditbejegyzés.

Hiba esetén sem részleges Purchase Requisition, sem részleges
Proposal-felhasználás nem marad. Az `id ASC` sorrendű sorzár csökkenti a
holtpont kockázatát. A Proposal-forrás unique constraintje a párhuzamos
végrehajtással szembeni végső védelem. Egy unique ütközés nem alakítható csendes
sikerré.

### Határok és legacy út

Az új elsődleges út:

```text
Approved Supply Proposal
→ Purchase Requisition Consolidation
→ Draft Purchase Requisition
```

A legacy `MaterialRequirement → Purchase Requisition` route, controller
művelet és `generateFromMaterialRequirements()` metódus átmenetileg aktív, de
kivezetésre jelölt. Az új végpont és felület nem használja.

Az eltávolítás feltétele az új folyamat regressziós időszaka, a régi felület és
művelet kivezetése, a feldolgozatlan legacy források lezárása vagy migrációja,
majd a régi route, művelet és Service-metódus együttes eltávolítása.

A 0011 nem valósít meg Supplier Selectiont, MOQ- vagy
rendelésitöbbszörös-szabályt, Purchase Order-generálást, Goods Receiptet,
StockBalance-módosítást, StockMovementet vagy StockReservationt.

## Következmények és nyitott kockázatok

- A Supplier- és dátumspecifikus Purchase Requisition-fejlécek kifejezik a
  konszolidációs döntést, de a későbbi kézi Purchase Requisition-folyamat
  kompatibilitását meg kell őrizni.
- A teljes Proposal-felhasználás egyszerű és erős V1-invariáns. A részleges
  felhasználás külön ADR-t és hozzárendelési életciklust igényel.
- Egy már konszolidált Proposal vagy Purchase Requisition későbbi
  visszavonásának dokumentumok közötti kompenzációja nincs automatizálva. Ez
  későbbi beszerzési szabály feladata.
- A Supplier Selection és az utánpótlási szabály sorrendjét a roadmap külön
  kezeli. A Supplier nélküli Purchase Requisition addig is helyes domainállapot.

## Elutasított alternatívák

- **Material Requirement közvetlen használata:** megkerülné a Proposal
  jóváhagyási határát, és a legacy pillanatképet tenné elsődleges bemenetté.
- **A legacy forrástábla polymorphic bővítése:** összemosná a Requirement okát a
  Proposal végrehajtási forrásával.
- **A dátumok összevonása a legkorábbi fejlécdátumba:** elveszítené a Proposal
  idődimenzióját.
- **Meglévő Draft Purchase Requisition automatikus bővítése:** elmosná az
  auditálható köteghatárt.
- **Automatikus Supplier-választás vagy mennyiségi kerekítés:** idő előtt a
  0012 és 0013 felelősségét hozná a konszolidációba.
