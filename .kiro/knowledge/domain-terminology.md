# KM_Production domain terminológia

## Cél

Ez a szótár a planning, MRP, inventory és procurement dokumentáció kötelező
nyelvét rögzíti. Az angol technikai név kódban és architektúrában használható;
a magyar jelentés az üzleti értelmezést pontosítja.

| Angol technikai név              | Magyar üzleti jelentés            | Definíció / nem összekeverendő                                                                                                                                                                                 |
| -------------------------------- | --------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `Demand`                         | üzleti igény                      | A teljesítendő üzleti cél vagy igény forrása, például Customer Order, Production Plan, Forecast vagy maintenance demand. Nem azonos a konkrét anyagszükséglettel.                                              |
| `Requirement`                    | szükséglet                        | Egy Demand teljesítéséhez szükséges konkrét anyag, erőforrás vagy kapacitás mennyisége és időpontja. Nem végrehajtási dokumentum.                                                                              |
| `Material Requirement`           | anyagszükséglet                   | Adott üzleti célhoz egy adott Item meghatározott mennyisége egy meghatározott `required_at` időpontra. Nem Purchase Requisition, Purchase Order, Reservation vagy aktuális shortage.                           |
| `Gross Requirement`              | bruttó szükséglet                 | A supply-k és készlet levonása előtti teljes szükséglet, például production quantity × BOM quantity.                                                                                                           |
| `Net Requirement`                | nettó szükséglet                  | A Gross Requirement elfogadott, időben és üzletileg alkalmazható fedezetekkel nettósított része. A részletes netting policy eredménye.                                                                         |
| `Shortage`                       | fedezetlen hiány                  | A Requirement azon része, amelyet a számításban elfogadott Supply nem fedez. Nem automatikusan Purchase.                                                                                                       |
| `Supply`                         | ellátási forrás                   | Tényleges vagy várható mennyiség, amely egy Requirement fedezésére üzleti szabály szerint alkalmas lehet.                                                                                                      |
| `Available Supply`               | felhasználható ellátás            | Az adott időpontra, helyre, quality állapotra és allokációra tekintettel ténylegesen felhasználható Supply. Nem minden fizikai stock available.                                                                |
| `Expected Supply`                | várható ellátás                   | Jövőbeli, kellő bizonyosságú és időzített supply, például visszaigazolt purchase vagy megfelelő státuszú production output. Nem azonos a Proposed Supply-jal.                                                  |
| `Supply Proposal`                | ellátási javaslat                 | Auditálható planning artifact egy Item tervezett fedezésére. A Supplier Purchase esetén is lehet ismeretlen. Nem Requirement, approval, execution, Purchase Requisition, Purchase Order vagy tényleges supply. |
| `Procurement Proposal`           | beszerzési javaslat               | `Purchase` stratégiájú Supply Proposal, lehetséges supplier/source és rendelési paraméterekkel. Nem Purchase Requisition vagy Purchase Order.                                                                  |
| `Allocation`                     | hozzárendelés                     | Egy supply mennyiségének tervezési hozzárendelése egy Requirementhez. Nem feltétlenül zárja vagy mozgatja a fizikai készletet.                                                                                 |
| `Reservation`                    | foglalás                          | Készlet vagy kapacitás üzleti célra történő lekötése, amely más felhasználást korlátoz. Nem azonos a planning-only Allocationnel.                                                                              |
| `Pegging`                        | szükséglet-visszakapcsolás        | Mennyiségi és ok-okozati kapcsolat a Requirement, annak eredeti Demandje és a fedező proposal/execution/result között. A konszolidáció sem törheti el.                                                         |
| `Execution`                      | végrehajtás                       | A jóváhagyott döntés tényleges üzleti végrehajtása, például Purchase Requisition, Purchase Order, Transfer Order vagy Production Order létrehozása és kezelése. Nem planning eredmény.                         |
| `Planning Engine`                | tervezési komponensréteg          | Számítást, értékelést, javaslatot, optimalizációt és szimulációt végző logikai komponenscsalád. Nem egyetlen service és nem HTTP/CRUD réteg.                                                                   |
| `Supply Strategy`                | ellátási stratégia                | Annak módja, hogyan fedezhető egy Shortage: például Purchase, Transfer, Manufacture, Subcontract vagy Consignment.                                                                                             |
| `Replenishment Strategy`         | utánpótlási stratégia             | Az a policy, amely meghatározza, milyen üzleti jel alapján és milyen készletcélra induljon ellátástervezés, például `purchase_to_order` vagy `purchase_to_stock`. Nem azonos a Supply Strategyvel.             |
| `Lead Time`                      | átfutási idő                      | Egy meghatározott kezdő és befejező üzleti esemény közötti várható vagy tényleges idő. Supplier, Item Supplier és gyártási kontextusban eltérhet.                                                              |
| `Preferred Supplier`             | preferált beszállító              | Az Itemhez tartozó procurement source-ok közül alapértelmezetten előnyben részesített supplier. Nem feltétlenül a legolcsóbb és nem automatikusan approved.                                                    |
| `Approved Supplier`              | jóváhagyott beszállító            | Az adott Item vagy beszerzési kategória ellátására üzletileg/minőségileg engedélyezett supplier. Az általánosan aktív Supplier státusz ezt nem helyettesíti.                                                   |
| `Order Multiple`                 | rendelési többszörös              | Az a mennyiségi lépés, amelynek egész számú többszörösében rendelhető az Item az adott procurement source-tól. Nem Minimum Order Quantity.                                                                     |
| `Minimum Order Quantity` (`MOQ`) | minimális rendelési mennyiség     | Az adott supplier/source által egy rendelésben elfogadott legkisebb mennyiség. MOQ miatti többlet nem növeli visszamenőleg a Gross Requirementet.                                                              |
| `Purchase Requisition`           | beszerzési igény                  | Belső, végrehajtás előtti beszerzési dokumentum. Jóváhagyható és Purchase Order alapja lehet; nem suppliernek tett megrendelés.                                                                                |
| `Purchase Order`                 | beszerzési rendelés               | Supplier felé létrehozott formális rendelés és üzleti kötelezettség. Nem Demand, Requirement vagy Proposal.                                                                                                    |
| `Goods Receipt`                  | áruátvétel                        | A beérkezett áru rögzített üzleti eseménye. A received, accepted, rejected és stockba helyezett mennyiség eltérhet.                                                                                            |
| `On-hand Stock`                  | fizikailag nyilvántartott készlet | A készletmozgásokból magyarázható, helyen lévő mennyiség. Nem automatikusan usable vagy available.                                                                                                             |
| `Usable Stock`                   | üzletileg használható készlet     | Minőségi, hely-, batch-, zárolási és más korlátozások alapján használható on-hand stock.                                                                                                                       |
| `Planning Horizon`               | tervezési horizont                | Az az időszak, amelyen belül a Planning Engine demandet és supplyt értékel. A horizonton kívüli supply nem számítható be hallgatólagosan.                                                                      |

## Mennyiség- és időállapotok

A következő jelzők önálló üzleti jelentésűek, ezért általános `quantity`, `date`
vagy `status` mezővel csak dokumentált kontextusban helyettesíthetők:

| Jelző      | Jelentés                                                                 |
| ---------- | ------------------------------------------------------------------------ |
| `required` | Az üzleti célhoz szükséges mennyiség vagy időpont.                       |
| `planned`  | A tervezésben kijelölt, még változható érték.                            |
| `proposed` | A Planning Engine vagy felhasználó által javasolt érték.                 |
| `approved` | Meghatalmazott döntéssel elfogadott érték.                               |
| `ordered`  | Külső vagy belső orderben rögzített érték.                               |
| `promised` | A teljesítő fél által visszaigazolt vállalás.                            |
| `expected` | Aktuális információ alapján várható érték; eltérhet a promised értéktől. |
| `received` | Fizikailag vagy folyamat szerint beérkezett mennyiség.                   |
| `accepted` | Átvételi/minőségi döntéssel felhasználhatónak elfogadott mennyiség.      |
| `rejected` | Átvételi/minőségi döntéssel elutasított mennyiség.                       |

## Használat

Új modell, migration, service, DTO, UI-felirat, ADR és domain dokumentum
tervezésekor ezt a szótárt kell használni. Ha egy új fogalom nem illeszthető
egyértelműen ide, előbb a jelentését és a „nem összekeverendő” határát kell
rögzíteni.

## Kapcsolódó dokumentumok

- [Domain Constitution](../steering/domain-constitution.md)
- [Planning Engine és MRP domain architektúra](planning-engine.md)
- [Material Requirements Planning Architecture ADR](../decisions/0006-material-requirements-planning-architecture.md)
- [Inventory](inventory.md)
- [Procurement](procurement.md)
- [Production](production.md)
