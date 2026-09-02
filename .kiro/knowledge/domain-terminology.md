# KM_Production dokumentációs terminológiai standard

## Cél

Ez a dokumentum a tervezési, MRP-, készlet- és beszerzési dokumentáció kötelező
fogalomhasználatát rögzíti. Célja, hogy a magyar magyarázat közérthető legyen,
miközben a rendszer hivatalos fogalmai és technikai azonosítói pontosak
maradnak.

Az alapelv:

> Magyarul írjuk a magyarázatot, angolul tartjuk meg a rendszer hivatalos
> technikai azonosítóit.

Ez nem jelenti minden angol szó mechanikus lefordítását. Az angol elnevezés
megmarad, ha pontos domain fogalmat, kódbeli azonosítót, rövidítést vagy
szabványos technikai kifejezést jelöl. Magyar dokumentumban azonban nem szabad
angol szót csak azért használni, hogy a szöveg technikaibbnak hasson.

## Dokumentációs terminológiai elvek

1. Először az üzleti jelentést kell elmagyarázni egyszerű magyar nyelven.
2. A hivatalos angol domain fogalmat az első érdemi előforduláskor a magyar
   jelentésével együtt kell bevezetni.
3. A kódbeli azonosító pontos írásmódját meg kell őrizni, és indokolt esetben
   kódformázással kell jelölni.
4. Rövidítés csak a teljes név bevezetése után használható rendszeresen.
5. Szükséges technikai kifejezésnél előbb a megfigyelhető működést kell
   leírni, és csak utána a szakkifejezést.
6. Azonos fogalomhoz következetesen ugyanazt a magyar magyarázatot kell
   használni.
7. Hasonló, de eltérő üzleti tényeket nem szabad egy közös, általános szóval
   összemosni.

## Terminológiai kategóriák

### A — Pontos technikai azonosító

Ide tartozik minden tényleges osztálynév, metódusnév, mezőnév, enumérték,
adatbázis-objektum, route, permission, eseménynév és parancs.

Példák:

- `PurchaseOrder`, `PurchaseRequisition`, `SupplierAcknowledgement`;
- `ItemSupplier`, `MaterialRequirement`, `StockMovement`;
- `required_at`, `supplier_id`, `planned_quantity`;
- `lockForUpdate()`;
- `purchase-orders.dispatch`.

Ezeket nem szabad lefordítani vagy átnevezni. A körülöttük lévő szöveg magyarul
magyarázza el a jelentésüket. A fenti lista példa, nem teljes azonosító-jegyzék.

### B — Hivatalos domain fogalom

Az elkülönült üzleti jelentést hordozó fogalom megtarthatja a projektben
használt angol nevét. Első érdemi előfordulásakor egyszerű magyar magyarázatot
kell adni hozzá.

Példa:

> A `Purchase Requisition` belső beszerzési igény. Jóváhagyható, és később
> beszerzési rendelés alapja lehet, de még nem a beszállítónak szóló rendelés.

Az alábbi fogalomtár B kategóriájú domain fogalmakat rögzít. Ha ugyanaz a név
egy kódbeli osztály vagy mező neveként jelenik meg, akkor az adott előfordulás
az A kategória szabályait követi.

### C — Bevezetett rövidítés

A rövidítést az első érdemi előfordulás előtt vagy mellett fel kell oldani.
Olyan dokumentumban is újra be kell vezetni, amelyet az olvasó önállóan nyithat
meg.

| Rövidítés | Teljes név                     | Magyar magyarázat                                                                           |
| --------- | ------------------------------ | ------------------------------------------------------------------------------------------- |
| MRP       | Material Requirements Planning | anyagszükséglet-tervezés                                                                    |
| BOM       | Bill of Materials              | darabjegyzék                                                                                |
| PR        | Purchase Requisition           | belső beszerzési igény; projektvezetési szövegben a `PR` a pull request rövidítése is lehet |
| PO        | Purchase Order                 | beszerzési rendelés                                                                         |
| MOQ       | Minimum Order Quantity         | minimális rendelési mennyiség                                                               |

Ha a `PR` jelentése a szövegkörnyezetből nem egyértelmű, a teljes kifejezést
kell használni.

### D — Magyarázatot igénylő technikai kifejezés

Ezek a kifejezések szükségesek lehetnek, de önmagukban nem magyarázzák el az
üzleti működést.

| Technikai kifejezés                               | Előbb ezt mondd el magyarul                                                                         |
| ------------------------------------------------- | --------------------------------------------------------------------------------------------------- |
| idempotens működés                                | Ugyanannak a kérésnek az ismétlése nem hoz létre még egy üzleti eredményt.                          |
| pillanatkép (`snapshot`)                          | A rendszer egy adott időpont értékeit történeti vagy újraszámítási célból megőrzi.                  |
| tranzakció                                        | A kapcsolódó módosítások vagy együtt sikerülnek, vagy egyik sem marad meg.                          |
| konkurens végrehajtás (`concurrency`)             | Két egyidejű kérés sem hozhat létre ellentmondó vagy duplikált eredményt.                           |
| zárolás (`lock`, `row lock`)                      | A művelet idejére a rendszer megakadályozza ugyanannak a rekordnak az ütköző módosítását.           |
| eredetkapcsolat (`lineage`)                       | Megőrzött kapcsolat mutatja meg, mely korábbi üzleti igényből vagy dokumentumból származik az adat. |
| aktuális állapot (`current state`)                | Az értékelés a művelet időpontjában érvényes adatokból indul, nem egy korábbi eredményből.          |
| elsődleges üzleti forrás (`authoritative source`) | Az az adat vagy rekord, amelyből az adott üzleti tényt értelmezni kell.                             |

Egy pillanatképnél mindig meg kell nevezni, hogy aktuális számítási
pillanatképről vagy történetileg változatlan végrehajtási pillanatképről van-e
szó. A két jelentés nem cserélhető fel.

### E — Kerülendő angol zsargon

Az alábbi minták általában nem hivatalos nevek és nem pontos technikai
azonosítók. A mondat tényleges jelentése alapján magyarul kell őket megfogalmazni.

| Kerülendő minta           | Előnyben részesített megfogalmazás                                           |
| ------------------------- | ---------------------------------------------------------------------------- |
| candidate lista           | lehetséges beszállítók listája; beszállítójelöltek listája                   |
| authoritative forrás      | elsődleges üzleti forrás; az üzleti döntés alapjául szolgáló forrás          |
| current-state reload      | az aktuális állapot újbóli betöltése                                         |
| execution use case        | végrehajtási művelet; végrehajtási folyamat                                  |
| supplierless              | beszállító nélküli                                                           |
| read-only domainértékelés | adatot nem módosító üzleti értékelés                                         |
| flow                      | folyamat; lépéssor; útvonal                                                  |
| gate                      | ellenőrzési kapu; kötelező ellenőrzés                                        |
| review                    | felülvizsgálat; ellenőrzés                                                   |
| artifact                  | önálló dokumentum, rekord, eredmény vagy javaslat a konkrét jelentés szerint |

Ez a lista nem mechanikus cserejegyzék. Például a `Supplier Candidate`
hivatalos domain fogalom, ezért nem azonos egy tetszőleges „candidate list”
kifejezéssel. A `Quality Gate` vagy egy parancsban szereplő `gate` szintén
megtartható, ha hivatalos név vagy technikai azonosító.

## Hivatalos domain fogalmak

| Angol technikai név                  | Magyar üzleti jelentés            | Definíció / nem összekeverendő                                                                                                                                                |
| ------------------------------------ | --------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `Item`                               | cikk                              | A rendszerben kezelt anyag, alkatrész, részegység vagy termék. A konkrét jelentést az Item típusa adja.                                                                       |
| `Supplier`                           | beszállító                        | Beszerzett anyagot, szolgáltatást vagy kapcsolódó dokumentumot biztosító üzleti partner.                                                                                      |
| `ItemSupplier`                       | cikk–beszállító kapcsolat         | Egy Item és Supplier közötti, beszerzési feltételeket tároló kapcsolat. Nem rendelés és nem beszállítóválasztás.                                                              |
| `Demand`                             | üzleti igény                      | A teljesítendő üzleti cél forrása, például Customer Order vagy Production Plan. Nem azonos a konkrét szükséglettel.                                                           |
| `Requirement`                        | szükséglet                        | Egy üzleti igény teljesítéséhez szükséges konkrét anyag, erőforrás vagy kapacitás mennyisége és időpontja. Nem végrehajtási dokumentum.                                       |
| `Material Requirement`               | anyagszükséglet                   | Egy üzleti célhoz szükséges Item, mennyiség és `required_at` időpont. Akkor is fennáll, ha teljesen fedezett.                                                                 |
| `Gross Requirement`                  | bruttó szükséglet                 | A fedezetek levonása előtti teljes szükséglet, például gyártási mennyiség × BOM-mennyiség.                                                                                    |
| `Net Requirement`                    | nettó szükséglet                  | A bruttó szükséglet időben és üzletileg felhasználható fedezetek levonása után maradó része.                                                                                  |
| `Shortage`                           | fedezetlen hiány                  | A szükséglet számításkor fedezetlen része. Nem jelent automatikusan beszerzést.                                                                                               |
| `Supply`                             | ellátás; fedezet                  | Tényleges vagy várható mennyiség, amely egy Requirement fedezésére üzleti szabály szerint alkalmas lehet.                                                                     |
| `Available Supply`                   | felhasználható ellátás            | Az adott időpont, hely, minőségi állapot és hozzárendelések alapján ténylegesen felhasználható ellátás. Nem minden fizikailag meglévő készlet ilyen.                          |
| `Expected Supply`                    | várható ellátás                   | Kellő bizonyosságú és időzített jövőbeli ellátás. Nem azonos a javasolt ellátással.                                                                                           |
| `Proposal`                           | javaslat                          | Lehetséges megoldás egy szükséglet fedezésére. Nem jóváhagyott döntés és nem végrehajtás.                                                                                     |
| `Approval`                           | jóváhagyás                        | Meghatalmazott döntés egy javaslat vagy dokumentum elfogadásáról. A jóváhagyás tárgyát mindig meg kell nevezni.                                                               |
| `Supply Proposal`                    | ellátási javaslat                 | Auditálható javaslat egy Item tervezett fedezésére. Nem szükséglet, jóváhagyás, végrehajtási dokumentum vagy tényleges ellátás.                                               |
| `Supply Proposal Approval`           | ellátási javaslat jóváhagyása     | Az ellátási javaslat elfogadása. Nem hagyja jóvá automatikusan a később létrejövő Purchase Requisition dokumentumot.                                                          |
| `Procurement Proposal`               | beszerzési javaslat               | `Purchase` stratégiájú Supply Proposal. Nem Purchase Requisition vagy Purchase Order.                                                                                         |
| `Purchase Requisition Consolidation` | beszerzésiigény-konszolidáció     | Jóváhagyott beszerzési javaslatokat dokumentált beszállító- és időkulcs szerint Draft Purchase Requisition dokumentumokba csoportosító művelet.                               |
| `Procurement Source`                 | beszerzési forrás                 | Egy Item és Supplier közötti elsődleges `ItemSupplier` kapcsolat, amely a beszerzési feltételeket tárolja. Nem beszállítóválasztás vagy rendelés.                             |
| `Supplier Candidate`                 | beszállítójelölt                  | Olyan Supplier, amely minden érintett Itemhez érvényes és alkalmazható Procurement Source kapcsolattal rendelkezik. Nem automatikusan kiválasztott vagy „legjobb” beszállító. |
| `Supplier Selection`                 | beszállítóválasztás               | Auditált felhasználói döntés a beszállító nélküli Draft Purchase Requisition minden tételéhez közös beszállítójelöltek közül.                                                 |
| `Planned Quantity`                   | tervezett mennyiség               | A Purchase Requisitionbe vitt jóváhagyott javaslati mennyiség változatlan, alapegységben tárolt pillanatképe.                                                                 |
| `Replenishment Quantity`             | beszerzendő mennyiség             | A kiválasztott ItemSupplier minimális rendelési mennyiségre és rendelési többszörösre vonatkozó feltételei után kapott mennyiség.                                             |
| `Replenishment Excess`               | beszerzési többlet                | `Replenishment Quantity - Planned Quantity`. Beszállítói feltétel miatt keletkezik, és nem új Requirement.                                                                    |
| `Allocation`                         | tervezési hozzárendelés           | Egy ellátási mennyiség tervezési hozzárendelése egy Requirementhez. Nem feltétlenül köt le fizikai készletet.                                                                 |
| `Reservation`                        | foglalás                          | Készlet vagy kapacitás üzleti célra történő lekötése, amely más felhasználást korlátoz. Nem azonos az Allocationnel.                                                          |
| `Netting`                            | szükséglet nettósítása            | A bruttó szükségletből a szabály szerint elfogadható fedezetek levonása. Eredménye a nettó szükséglet vagy hiány.                                                             |
| `Pegging`                            | szükséglet-visszakapcsolás        | Egy konkrét ellátási mennyiség és Requirement közötti tervezési kapcsolat. Megmagyarázza a netting fedezetét, de nem fizikai foglalás vagy készletmozgás.                     |
| `Execution`                          | végrehajtás                       | A jóváhagyott döntés tényleges üzleti végrehajtása. Nem tervezési eredmény.                                                                                                   |
| `Planning Engine`                    | tervezési komponensréteg          | Számítást, értékelést, javaslatot, optimalizációt és szimulációt végző komponenscsalád. Nem egyetlen service és nem HTTP/CRUD réteg.                                          |
| `Supply Strategy`                    | ellátási stratégia                | Annak módja, ahogyan egy Shortage fedezhető, például `Purchase`, `Transfer` vagy `Manufacture`.                                                                               |
| `Replenishment Strategy`             | utánpótlási stratégia             | A kiválasztott beszerzési forrás mennyiségi szabálya. Nem Supply Strategy és V1-ben nem készletszint-tervező rendszer.                                                        |
| `Lead Time`                          | átfutási idő                      | Egy meghatározott kezdő és befejező üzleti esemény közötti várható vagy tényleges idő. Kontextusonként eltérhet.                                                              |
| `Preferred Supplier`                 | preferált beszállító              | Egy Itemhez üzletileg előnyben részesített Supplier. Nem kizárólagos és nem automatikusan kiválasztott beszállító.                                                            |
| `Approved Supplier`                  | jóváhagyott beszállító            | Egy Item vagy beszerzési kategória ellátására üzletileg vagy minőségileg engedélyezett Supplier. Nem azonos az aktív státusszal.                                              |
| `Order Multiple`                     | rendelési többszörös              | Az a mennyiségi lépés, amelynek egész számú többszörösében az Item rendelhető. Nem Minimum Order Quantity.                                                                    |
| `Minimum Order Quantity` (`MOQ`)     | minimális rendelési mennyiség     | A Supplier által egy rendelésben elfogadott legkisebb mennyiség. Az emiatti többlet nem növeli visszamenőleg a Gross Requirementet.                                           |
| `Purchase Requisition`               | beszerzési igény                  | Belső, végrehajtás előtti dokumentum. Jóváhagyható és Purchase Order alapja lehet, de még nem a Suppliernek szóló rendelés.                                                   |
| `Purchase Requisition Approval`      | beszerzési igény jóváhagyása      | Üzleti döntés a beszerzési igény elfogadásáról. Nem bizonyítja, hogy a végrehajtáskor minden forrás- és mennyiségi feltétel még érvényes.                                     |
| `Execution Readiness`                | végrehajtási készültség           | Adatot nem módosító, aktuális értékelés arról, hogy egy Approved PR biztonságosan továbbléphet-e a rendelés létrehozásához. Nem jóváhagyás vagy végrehajtás.                  |
| `Purchase Order Generation`          | beszerzési rendelés generálása    | A készültség tranzakciós újraellenőrzése után Draft Purchase Ordert létrehozó végrehajtási művelet.                                                                           |
| `Execution Snapshot`                 | végrehajtási pillanatkép          | A PO létrehozásakor megőrzött, történetileg stabil beszállító-, Item-, forrás-, egység-, átváltási-, ár- és szabályadat.                                                      |
| `Purchase Order`                     | beszerzési rendelés               | A beszerző által létrehozott formális rendelés és üzleti kötelezettség. Létrejötte önmagában nem bizonyítja, hogy elküldték a Suppliernek.                                    |
| `Dispatch`                           | rendelés továbbítása              | A Purchase Order Supplier felé történő átadásának vagy átadási kísérletének auditálható ténye. Nem rendelés-jóváhagyás, visszaigazolás vagy áruátvétel.                       |
| `Supplier Acknowledgement`           | beszállítói visszaigazolás        | A Supplier rögzített válasza az ígért mennyiségről, dátumról vagy elutasításról. Nem módosított PO, áruátvétel vagy készletváltozás.                                          |
| `Goods Receipt`                      | áruátvétel                        | A beérkezett áru rögzített üzleti eseménye. A beérkezett, elfogadott, elutasított és készletre helyezett mennyiség eltérhet.                                                  |
| `Stock Movement`                     | készletmozgás                     | A készlet mennyiségi változásának elsődleges üzleti forrása.                                                                                                                  |
| `On-hand Stock`                      | fizikailag nyilvántartott készlet | A készletmozgásokból magyarázható, fizikailag meglévő mennyiség. Nem automatikusan felhasználható készlet.                                                                    |
| `Usable Stock`                       | üzletileg használható készlet     | A minőségi, hely-, batch-, zárolási és más korlátozások alapján használható készlet.                                                                                          |
| `Planning Horizon`                   | tervezési horizont                | Az az időszak, amelyen belül a Planning Engine az igényeket és fedezeteket értékeli. A horizonton kívüli ellátás nem számítható be hallgatólagosan.                           |
| `Result`                             | tényleges eredmény                | Egy megnevezett folyamat kimenete. Nem terv, javaslat vagy ígéret.                                                                                                            |

## Védett domainkülönbségek

Az alábbi ábra a fogalmak kapcsolatát mutatja, nem automatikus vagy minden
esetben kötelező állapotátmeneteket. A Shortage csak akkor pozitív, ha a
Requirement nincs teljesen fedezve. A javaslat, a jóváhagyás és a végrehajtási
dokumentumok létrehozása külön szabályhoz vagy felhasználói döntéshez kötött.

```text
Demand
→ Requirement
→ Netting
→ Shortage
→ Supply Proposal
→ Supply Proposal Approval
→ Purchase Requisition
→ Purchase Requisition Approval
→ Execution Readiness
→ Purchase Order
→ Dispatch
→ Supplier Acknowledgement
→ Goods Receipt
→ Stock Movement
→ Result
```

- A `Demand` a teljesítendő üzleti cél forrása.
- A `Requirement` a célhoz szükséges konkrét mennyiséget és időpontot fejezi
  ki. Akkor is létezik, ha teljesen fedezett.
- A `Shortage` számított hiány. Csak a Requirement azon része, amelyet az
  elfogadott fedezet nem takar.
- A `Supply Proposal` javaslat a fedezés módjára. Nem jóváhagyás és nem biztos
  ellátás.
- A Supply Proposal jóváhagyása elfogadja a javaslatot, de nem hagyja jóvá
  automatikusan a később létrejövő Purchase Requisition dokumentumot.
- A `Purchase Requisition` belső beszerzési igény. Még nem a Suppliernek szóló
  rendelés.
- A Purchase Requisition jóváhagyása elfogadja a beszerzési igényt, de nem
  bizonyítja a végrehajtási készültséget.
- Az `Execution Readiness` közvetlenül a rendelés létrehozása előtt ellenőrzi
  az aktuális beszállító-, forrás- és mennyiségi feltételeket.
- A `Purchase Order` formális beszerzési rendelés. Létrejötte nem bizonyítja a
  továbbítást.
- A `Dispatch` a rendelés külső átadásának vagy átadási kísérletének ténye.
  Nem bizonyít Supplier-választ.
- A `Supplier Acknowledgement` a Supplier válasza és ígérete. Nem bizonyít
  fizikai beérkezést.
- A `Goods Receipt` az áru beérkezésének rögzített üzleti eseménye. A beérkezett,
  elfogadott, elutasított és készletre helyezett mennyiség eltérhet.
- A `Stock Movement` a készlet mennyiségi változásának elsődleges üzleti
  forrása. Más dokumentum önmagában nem módosít készletet.
- A `Result` a folyamat tényleges kimenete. Mindig meg kell nevezni, mely
  folyamat eredményéről van szó.

### Pegging, eredetkapcsolat és foglalás

- A `Pegging` megmutatja, hogy egy konkrét ellátási mennyiség mely Requirementet
  fedezi.
- A `lineage` vagy eredetkapcsolat megmutatja, mely korábbi igényből,
  javaslatból vagy dokumentumból származik egy rekord.
- A `Reservation` ténylegesen leköt egy készlet- vagy kapacitásmennyiséget, és
  más felhasználást korlátozhat.

A három kapcsolat nem cserélhető fel. Különösen a Proposal → Purchase
Requisition forráskapcsolat eredetkapcsolat, nem a 0010 szerinti Pegging.

### Jóváhagyás, végrehajtási készültség és végrehajtás

- A jóváhagyás azt igazolja, hogy egy meghatalmazott szereplő elfogadta az
  üzleti igényt vagy javaslatot.
- A végrehajtási készültség az aktuális adatok alapján ellenőrzi, hogy a
  következő művelet biztonságosan elindítható-e.
- A végrehajtás létrehozza vagy kezeli a jóváhagyott döntéshez tartozó üzleti
  dokumentumot vagy eseményt.

Egy Approved Purchase Requisition ezért lehet még `NOT READY`, és a `READY`
eredmény önmagában még nem Purchase Order.

## Mennyiség- és időállapotok

A következő jelzők önálló üzleti jelentésűek, ezért általános `quantity`, `date`
vagy `status` mezővel csak dokumentált kontextusban helyettesíthetők:

| Jelző      | Jelentés                                                                                                               |
| ---------- | ---------------------------------------------------------------------------------------------------------------------- |
| `required` | Az üzleti célhoz szükséges mennyiség vagy időpont.                                                                     |
| `planned`  | A tervezésben kijelölt, még változható érték. Nem ígéret vagy tény.                                                    |
| `proposed` | A Planning Engine vagy egy felhasználó által javasolt érték.                                                           |
| `approved` | Meghatalmazott döntéssel elfogadott érték. A jóváhagyás tárgyát mindig meg kell nevezni.                               |
| `ordered`  | Purchase Orderben vagy más rendelésben rögzített érték. Nem bizonyítja a továbbítást vagy a Supplier visszaigazolását. |
| `promised` | A teljesítő fél által visszaigazolt vállalás.                                                                          |
| `expected` | Az aktuális információ alapján várható érték. Eltérhet a `promised` értéktől.                                          |
| `received` | Fizikailag vagy a fogadási folyamatban beérkezett mennyiség.                                                           |
| `accepted` | Átvételi vagy minőségi döntéssel felhasználhatónak elfogadott mennyiség.                                               |
| `rejected` | Átvételi vagy minőségi döntéssel elutasított mennyiség.                                                                |

Példa ugyanazon mennyiség eltérő állapotaira:

```text
planned:   100 kg
proposed:  100 kg
approved:  100 kg
ordered:   125 kg
promised:  120 kg
expected:  118 kg
received:  118 kg
accepted:  116 kg
rejected:    2 kg
```

Ezek nem ugyanannak a mezőnek egymást felülíró nevei. Külön üzleti tényeket és
döntéseket jelölnek.

## Használat

Új modell, migration, service, DTO, UI-felirat, ADR és domain dokumentum
tervezésekor ezt a standardot kell használni. Ha egy új fogalom nem illeszthető
egyértelműen ide, előbb a jelentését, kategóriáját és a „nem összekeverendő”
határát kell rögzíteni.

Magyar dokumentáció szerkesztésekor:

1. Azonosítsd a használt domain fogalmat és technikai azonosítókat.
2. Írd le a megfigyelhető üzleti működést egyszerű magyar mondatokkal.
3. Az első előfordulásnál vezesd be a hivatalos angol nevet vagy rövidítést.
4. Ellenőrizd, hogy a magyar megfogalmazás nem vont-e össze két eltérő
   fogalmat.
5. Kerüld a táblázatot, ha a magyarázathoz több teljes mondat vagy példa kell.

## Kapcsolódó dokumentumok

- [Domain Constitution](../steering/domain-constitution.md)
- [Planning Engine és MRP domain architektúra](planning-engine.md)
- [Material Requirements Planning Architecture ADR](../decisions/0006-material-requirements-planning-architecture.md)
- [Material Requirement Netting ADR](../decisions/0009-material-requirement-netting.md)
- [Replenishment Strategies ADR](../decisions/0013-replenishment-strategies.md)
- [Purchase Requisition Execution Readiness ADR](../decisions/0014-purchase-requisition-execution-readiness.md)
- [Purchase Order Generation ADR](../decisions/0015-purchase-order-generation.md)
- [Purchase Order Dispatch / Supplier Acknowledgement ADR](../decisions/0016-purchase-order-dispatch-supplier-acknowledgement.md)
- [Inventory](inventory.md)
- [Procurement](procurement.md)
- [Production](production.md)
