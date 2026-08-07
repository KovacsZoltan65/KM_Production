# KM_Production Domain Constitution

## Cél és hatály

Ez a dokumentum a KM_Production tartós domain alapelveit rögzíti. Új gyártási,
készlet-, beszerzési, kapacitás-, ütemezési és Manufacturing Intelligence
funkciónál kötelező tervezési bemenet.

A rendszer a valós üzleti eseményeket, szükségleteket, döntéseket, azok
ok-okozati kapcsolatait, időbeliségét és bizonytalanságát modellezi. A
szükséglet, a terv, az ígéret és a tényleges végrehajtás nem ugyanaz.

Ha a valós működés és a jelenlegi implementáció eltér, a célállapotot a domain
jelentése határozza meg. Ez nem ad felhatalmazást indokolatlan átírásra: a
meglévő stabil viselkedést csak explicit scope, migrációs terv, teszt és
visszaállítási stratégia mellett szabad módosítani.

## Alapelvek

### 1. Reality First

A modell a valós gyártási és logisztikai működést kövesse. Technikai
egyszerűsítés nem olvaszthat össze eltérő üzleti tényeket, és nem írhatja felül
azok jelentését.

### 2. Requirement Driven

A tervezés és ellátás szükségletből indul, nem végrehajtási dokumentumból:

```text
Need
→ Requirement
→ Proposal
→ Decision / Approval
→ Execution
→ Result
```

A `PurchaseOrder`, `ProductionOrder` vagy `TransferOrder` ezért nem az igény
első reprezentációja.

### 3. Planning != Execution

A `Requirement`, `Proposal`, `Approval`, `Execution` és `Result` külön üzleti
szerepet kap. Egy számítás vagy javaslat önmagában nem hozhat létre készletet,
foglalást vagy külső kötelezettséget.

### 4. Plan != Promise != Reality

A mennyiségek és időpontok jelentését explicit módon kell kezelni. A
`required`, `planned`, `proposed`, `approved`, `ordered`, `promised`,
`expected`, `received`, `accepted` és `rejected` értékek csak akkor vonhatók
össze, ha üzletileg valóban ugyanazt jelentik.

Példa:

```text
Requirement:          100 kg
Proposed:             100 kg
Approved:             100 kg
Ordered:              125 kg
Supplier confirmed:   125 kg
Received:             120 kg
Accepted:             118 kg
Rejected:               2 kg
```

Ezek nem egy mező egymást felülíró értékei, hanem eltérő tények és állapotok.

### 5. Traceability by Design

Minden jelentős üzleti eseménynél megválaszolható legyen:

- miért jött létre;
- mely szükségletből származik;
- milyen automatikus vagy emberi döntés vezetett hozzá;
- mi történt ténylegesen;
- mely további folyamatokra volt hatással.

A traceability kapcsolat nem vezethető le kizárólag szabad szövegből vagy
aktuális állapotból. A szükséges strukturált kapcsolatokat és mennyiségi
hozzárendeléseket (`Pegging`) meg kell őrizni.

### 6. Single Source of Truth

Minden üzleti ténynek legyen egyértelmű elsődleges forrása. A készletváltozás
forrása a `StockMovement`; a `StockBalance` összegzés vagy gyorsított nézet, nem
függetlenül szerkeszthető történet. Ugyanez az elv alkalmazandó a
kötelezettségekre, átvételekre, jóváhagyásokra és minőségi döntésekre.

### 7. Calculated, Not Duplicated

Származtatott értéket csak üzleti, audit-, teljesítmény- vagy historizálási okból
szabad tartósan tárolni. Tárolt számításnál dokumentálni kell:

- a forrásadatokat és számítási szabály verzióját;
- a számítás időpontját és érvényességi horizontját;
- az újraszámítás és elavulás szabályát;
- hogy a mező pillanatkép, cache vagy üzleti tény.

### 8. Explicit Decisions

Fontos automatikus és emberi döntésnél az ok és a döntéshozó visszakövethető.
Beszállítóválasztás indoka lehet például `preferred_supplier`, `lowest_price`,
`shortest_lead_time`, `approved_supplier`, `manual_override` vagy egy névvel és
verzióval azonosított szabály.

### 9. Strategy-based Extensibility

Az ellátási és feltöltési módok külön stratégiák legyenek, ne több rétegben
szétszórt feltételágak. Lehetséges `SupplyStrategy`: `Purchase`, `Transfer`,
`Manufacture`, `Subcontract`, `Consignment`. Az MRP v1 csak a `Purchase`
stratégiát valósítja meg, de a domain nem rögzítheti, hogy minden `Shortage`
automatikusan vásárlás.

### 10. Time Is a First-Class Domain Concept

Az idő üzleti jelentést hordoz. A `required_at`, `planned_at`, `ordered_at`,
`promised_at`, `expected_at`, `received_at` és `accepted_at` nem általános
timestamp-helyettesítők. Dátum nélkül csak akkor értékelhető supply egy
requirementhez, ha ezt explicit üzleti szabály megengedi.

### 11. Uncertainty Must Be Representable

A rendszer különböztesse meg legalább a ténylegesen elérhető, lefoglalt,
várható, visszaigazolt, javasolt és potenciális ellátást. Példák: `Available
Stock`, `Reserved Stock`, `Expected Purchase`, `Expected Production`,
`Proposed Purchase`, `Potential Transfer`. A számítás dokumentálja, mely
kategóriák milyen bizonyossággal és mely időhorizonton számítanak fedezetnek.

### 12. History Matters

Teljes Event Sourcing nem követelmény, de a jelentős döntések, felülbírálások,
állapotváltozások és ok-okozati kapcsolatok története megőrzendő. Későbbi
törzsadat-változás nem írhatja át a korábbi gyártási vagy beszerzési esemény
üzleti jelentését.

## Kötelező komponens-felelősségi sablon

Minden új jelentős domain komponens specifikációja válaszolja meg:

1. Miért létezik, és mi az üzleti jelentése?
2. Mi hozza létre, mi módosítja, és mi zárja le vagy szünteti meg?
3. Ki vagy mi használja?
4. Milyen domain eseményekhez és eredeti okhoz kapcsolódik?
5. Mi a `Single Source of Truth`?
6. Mi számított adat, és mi tárolt üzleti tény?
7. Mi nem a komponens feladata?
8. Milyen traceability és audit szükséges?
9. Milyen idődimenziói vannak?
10. Milyen bizonytalanságot kell reprezentálnia?

## Kapcsolódó dokumentumok

- [Architecture](architecture.md)
- [Manufacturing domain](manufacturing.md)
- [Planning Engine](../knowledge/planning-engine.md)
- [Domain terminológia](../knowledge/domain-terminology.md)
- [Material Requirements Planning Architecture](../decisions/0006-material-requirements-planning-architecture.md)
- [Stock Movements are the Single Source of Truth](../decisions/0001-stock-movements.md)
