# Material Requirements Planning Architecture

## Állapot

Elfogadva

## Kontextus és üzleti probléma

Az anyagtervezésnek nem elég azt tudnia, mennyi anyagot kér a gyártás. Azt is
meg kell állapítania, hogy a szükséglet mikorra esedékes, miből fedezhető, és
mennyi marad fedezetlen. A fedezetlen mennyiség még nem beszerzési rendelés:
előbb tervezési javaslat, majd emberi döntés és külön végrehajtás következhet.

A KM_Production már kezel Customer Ordert, Production Plan objektumot, verziózott BOM-mal
rendelkező Production Ordert, Material Requirementet, készletegyenleget,
foglalást, Stock Movementet, Purchase Requisitiont, Purchase Ordert és Goods
Receiptet. A `MaterialRequirementService` a BOM alapján bruttó szükségletet
számol, figyelembe veszi a készletet és az aktív foglalásokat, majd eltárolja az
aktuális hiányt. A `PurchaseRequisitionService` Item és egység szerint
összevonhatja a hiányokat. A `PurchaseRequisitionItemSource` szükségletenként
őrzi a forrásmennyiséget.

Ez használható alap, de még nem teljes MRP-architektúra. A Material Requirement
közvetlenül Customer Order Itemhez kötött, nincs szükségleti időpontja vagy
általános Demand-forrása, és aktuális készlet- és hiányadatokat tárol. A
beszerzési ajánlás külön olvasási modellben, Item-szinten vonja le a nyitott PO-k
összesített mennyiségét. Nem időfázisos, és nem mutatja meg szükségletenként,
mely beérkező ellátás ad fedezetet. Nincs tárolt Supply Proposal,
Item–Supplier beszerzési forrás vagy többféle Supply Strategy modell.

Ha az MRP közvetlenül Purchase Ordert hozna létre, összemosná a szükségletet, a
javaslatot, a jóváhagyást és a végrehajtást. Emiatt nehezebb lenne megmagyarázni
egy összevont beszerzés eredetét, és a teljes tervezést idő előtt a `Purchase`
stratégiához kötné.

## Döntés

A KM_Production MRP-architektúrája a szükségletekből indul ki. A rendszer előbb
megőrzi az üzleti igényt, majd kiszámítja annak fedezetét és hiányát. A tervezés
csak ezután tehet javaslatot; végrehajtási dokumentum kizárólag külön döntési
lépés után jöhet létre.

Az elsődleges folyamat:

```text
Demand
→ Material Requirement
→ készlet- és ellátásértékelés
→ Net Requirement / Shortage
→ Supply Proposal
→ döntés / jóváhagyás
→ Purchase Requisition
→ emberi jóváhagyás
→ Purchase Order
→ Goods Receipt
→ Stock Movement / Result
```

Az ábra fogalmi sorrendet mutat. Nem jelenti azt, hogy minden nyíl automatikus
átmenet vagy már megvalósított folyamat.

## Üzleti jelentés és példa

A Demand azt fejezi ki, hogy milyen üzleti igényt kell teljesíteni. A Material
Requirement ennek egy konkrét anyagszükséglete. A Shortage számított hiány: azt
mutatja meg, hogy a szükségletből mennyit nem fedez a megfelelő készlet vagy
ellátás. A tervezés ezt értékeli és javaslatot készíthet; a végrehajtás külön
folyamatban hoz létre beszerzési vagy gyártási dokumentumot.

Például egy Production Orderhez 10 kg anyag szükséges. Ha 4 kg használható
készlet áll rendelkezésre, 6 kg marad fedezetlenül. A Material Requirement
továbbra is 10 kg; a 6 kg a számított Shortage. Ez a hiány később vezethet
Supply Proposalhoz, de a jelen döntés nem teszi automatikussá sem a javaslat,
sem a Purchase Requisition vagy Purchase Order létrehozását.

A készletvalóság a tényleges készlethelyzetet jelenti, amelyet a Stock
Movementek magyaráznak. A beszerzéstervezés a külső beszerzés lehetőségét, a
gyártástervezés pedig a belső gyártási fedezetet vizsgálhatja. Egyik tervezési
eredmény sem módosíthat közvetlenül készletet.

A döntés kötelező részei:

1. A `Demand`, `Requirement`, `Supply Proposal`, jóváhagyás, végrehajtás és
   eredmény külön üzleti jelentésű.
2. A Material Requirement akkor is fennáll, ha teljesen fedezett; a Shortage
   számított eredmény, nem a requirement identitása.
3. Az MRP számítás nem közvetlenül Purchase Ordert hoz létre. Az MRP v1
   pénzügyi vagy Supplier felé vállalt kötelezettséget csak emberi jóváhagyási
   pont után enged.
4. A Planning Engine logikai komponenscsalád, nem egyetlen mindenért felelős
   Laravel Service. Számít, értékel, szimulál és javasol. A végrehajtás külön
   Service felelőssége.
5. A Shortage fedezése `SupplyStrategy` választás eredménye. MRP V1-ben csak
   `Purchase` valósítandó meg, de a modell bővítési lehetőségként fenntartja a
   `Transfer`, `Manufacture`, `Subcontract` és `Consignment` stratégiát.
6. Supplier csak a Purchase stratégia értékelésekor jelenik meg. Automatikus
   Supplier Selection előtt Item–Supplier, vagyis Procurement Source
   kapcsolatot kell kialakítani.
7. A netting együtt értékeli a használható készletet, a foglalások és
   allokációk hatását, valamint a megfelelő bizonyosságú és időben alkalmazható
   beérkező ellátást. A pontos szabályokat külön ADR és specifikáció rögzíti.
8. Az összevonás megengedett, de minden javaslati, beszerzésiigény-, rendelési
   és eredménymennyiségnek visszakövethetőnek kell maradnia az eredeti
   Requirementhez és Demandhez.
9. A `required`, `planned`, `proposed`, `approved`, `ordered`, `promised`,
   `expected`, `received`, `accepted` és `rejected` adatok nem olvadnak egyetlen
   általános mennyiségbe, dátumba vagy státuszba.
10. A készlet tényleges változásának elsődleges üzleti forrása továbbra is a
    `StockMovement`. Tervezési eredmény vagy várt ellátás nem módosíthat
    közvetlenül `StockBalance`-ot.

## Az MRP V1 határai

Az MRP V1 része:

- Item Supplier / Procurement Source;
- BOM-alapú Material Requirement;
- a használható készlet, a foglalások és a releváns nyitott beszerzések
  figyelembevétele;
- Net Requirement / Shortage;
- Purchase Supply Strategy és Supply / Procurement Proposal;
- több Requirement összevonása mennyiségi visszakapcsolással;
- Purchase Requisition generálása;
- emberi jóváhagyás és Purchase Order létrehozása;
- nyomon követhetőség az eredeti Demandtől az áruátvételi és készleteredményig.

Az MRP V1-en kívül marad a `Transfer`, `Manufacture`, `Subcontract` és
`Consignment` stratégia, az előrejelzésből vagy biztonsági készletből induló
utánpótlás, a fejlett optimalizálás, az automatikus Supplier-továbbítás és a
teljesen önálló beszerzés.

## Következmények

Előnyök:

- a beszerzési dokumentumok eredeti üzleti oka visszakövethető;
- a konszolidáció nem veszti el a mennyiségi pegginget;
- külön kezelhető a terv, az ígéret, a várakozás és a tény;
- a Purchase V1 mellett később új Supply Strategy alapvető újratervezés nélkül
  illeszthető;
- az automatikus számítás és az emberi kötelezettségvállalás határa egyértelmű.

Költség és kockázat:

- több külön üzleti objektum és kapcsolat szükséges;
- a nettinghez idő-, bizonyossági, egység- és allokációs szabályokat kell
  véglegesíteni;
- a jelenlegi Material Requirement tábla és státuszmodell későbbi, migrációval
  védett evolúciót igényel;
- a jelenlegi ajánlási és beszerzésiigény-létrehozási működés nem nevezhető
  teljes Supply Proposal-folyamatnak;
- több Supplier-, beszerzési és áruátvételi adat történeti megőrzése szükséges.

Követő döntések:

- [`0007` Item Supplier / Procurement Source](0007-item-supplier-procurement-source.md) — elfogadva és implementálva;
- [`0008` Supply Proposal](0008-supply-proposal.md) — elfogadva és implementálva;
- `0009` Material Requirement Netting;
- `0010` Requirement Pegging;
- `0011` Purchase Requisition Consolidation;
- `0012` Supplier Selection;
- `0013` Replenishment Strategies.

A sorszámok a dokumentum elfogadásakor ajánlott következő helyek voltak az
akkori `0001`–`0006` folytonos sorrendben. Csak ténylegesen elfogadott döntés
létrehozásakor foglalhatók le.

## Elutasított alternatívák

- **Purchase Orderből kiinduló MRP.** Elutasítva, mert a végrehajtási dokumentumot
  tenné a szükséglet forrásává, megkerülné a proposal/approval határt, és a
  modellt Purchase stratégiához kötné.
- **Shortage = Purchase.** Elutasítva, mert ugyanaz a hiány később transferrel,
  gyártással, subcontracttal vagy consignmenttel is fedezhető.
- **Csak aktuális hiány tárolása Requirement nélkül.** Elutasítva, mert a
  shortage idővel változik, miközben az eredeti üzleti szükséglet és annak
  nyomon követhetősége megmarad.
- **Az összes tervezési logika egyetlen Service-ben.** Elutasítva, mert
  összekeverné a BOM-felbontás, a készlet-elérhetőség, a netting, a Supply
  Strategy, a Supplier Selection, a javaslat és a végrehajtás felelősségét.
- **Azonnali autonóm PO-létrehozás.** Elutasítva az MRP v1-ben a pénzügyi,
  Supplier-, jogosultsági és auditkockázat miatt. Később csak külön ADR alapján
  vizsgálható.

## Kapcsolódó dokumentumok

- [Domain Constitution](../steering/domain-constitution.md)
- [Planning Engine és MRP domain architektúra](../knowledge/planning-engine.md)
- [Domain terminológia](../knowledge/domain-terminology.md)
- [Stock Movements are the Single Source of Truth](0001-stock-movements.md)
- [Procurement knowledge](../knowledge/procurement.md)
- [Inventory knowledge](../knowledge/inventory.md)
