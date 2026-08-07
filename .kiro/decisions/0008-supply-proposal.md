# Supply Proposal domain model és lifecycle

- **Állapot:** Elfogadva és implementálva
- **Dátum:** 2026-08-07
- **Kapcsolódó döntések:** [0006 MRP Architecture](0006-material-requirements-planning-architecture.md), [0007 Item Supplier](0007-item-supplier-procurement-source.md)

## Kontextus

A rendszerben a Material Requirement, a közvetlenül generált Purchase Requisition
és több recommendation read model már létezett, de nem volt perzisztált objektum a
planning eredmény és az emberi végrehajtási döntés között. Emiatt nem volt külön
auditálható, hogy mit javasolt a planning, mit fogadott el egy felhasználó, és mi
lett később ténylegesen végrehajtva.

## Döntés

A `SupplyProposal` önálló planning artifact. Egy konkrét Item fedezésére tett,
magyarázható javaslatot tárol. Nem Material Requirement, Purchase Requisition,
Purchase Order, jóváhagyott végrehajtás vagy tényleges ellátási eredmény.

```text
Proposal → human Approval → future Execution → Result
```

Az objektum legfeljebb `Approved` döntési állapotig jut; a későbbi execution
külön domain objektum és külön csomag feladata.

## Stratégia

A core stratégiasemleges, de a v1 enum kizárólag a ténylegesen támogatott
`Purchase` értéket tartalmazza. A Transfer, Manufacture, Subcontract és
Consignment dokumentált extension point, nem választható működő opció.

A Supplier opcionális még Purchase esetén is. A `supplier_id = null` azt jelenti,
hogy a beszerzési fedezési mód eldőlt, de a supplier selection még nem történt
meg. Ha van Supplier, az Itemhez a 0007 szerint aktív, jóváhagyott, aktuálisan
érvényes procurement source szükséges; automatikus kiválasztás nincs.

## Mennyiség és idő

- `proposed_quantity` pozitív decimal, az Item base unitjában;
- `unit` az Item base unit létrehozáskori/módosításkori snapshotja;
- `required_at` az igényelt rendelkezésre állás dátuma;
- `proposed_supply_at` a javasolt rendelkezésre állás dátuma.

A dátumok nap pontosságúak. Késői proposal (`proposed_supply_at > required_at`)
megengedett valós planning tény, nem validációs hiba.

## Lifecycle és szerkeszthetőség

```text
Draft → Proposed
Draft → Cancelled
Proposed → Approved
Proposed → Rejected
Proposed → Cancelled
Approved → Cancelled
```

Csak a Draft szerkeszthető. Proposed állapotban a javaslat döntésre vár;
Approved, Rejected és Cancelled állapotban nem módosítható. Reopen nincs.
Approved → Cancelled addig megengedett, amíg nincs execution kapcsolat; a 0008
még nem hoz létre ilyen kapcsolatot. Rejected és Cancelled terminális.

Minden átmenetet a `SupplyProposalService` tranzakcióban, sorzárral és backend
transition-mátrix alapján végez. A frontend csak az engedélyezett műveleteket
mutatja, de nem biztonsági határ.

## Audit és magyarázhatóság

A tárolt üzleti tények: strategy, Item, opcionális Supplier, mennyiség és unit,
időpontok, status, `reason_code`, notes és user/timestamp attribution. A
`created`, `updated`, `proposed`, `approved`, `rejected`, `cancelled` események az
AuditLogService-en keresztül naplózottak. A user ID mindig az authenticated
felhasználóból származik.

## Domain Component Responsibility

- **Miért létezik?** Explicit, auditálható határt ad planning és execution között.
- **Üzleti jelentés:** javaslat arra, hogyan és mikorra fedezhető egy Item mennyisége.
- **Mi hozza létre?** A v1-ben jogosult felhasználó manuálisan; később Planning Engine.
- **Mi módosítja?** Jogosult felhasználó kizárólag Draft állapotban.
- **Mi zárja le?** Approve, Reject vagy Cancel transition.
- **Ki használja?** Planning és procurement döntéshozók; később execution komponensek.
- **Single Source of Truth:** `supply_proposals` tábla és annak audit trailje.
- **Tárolt tény:** a javaslat tartalma, döntési státusza és attributionje.
- **Számított adat:** a v1-ben nincs perzisztált netting/ranking eredmény.
- **Mi nem a feladata?** Demand/Requirement, pegging, netting, supplier ranking, PR/PO generálás vagy ellátás könyvelése.
- **Traceability:** létrehozó és lifecycle-döntéshozók, timestamp és activity események.
- **Idődimenzió:** required date, proposed supply date, lifecycle timestamps.
- **Bizonytalanság:** a planning feltételezését reprezentálja; nullable Supplier explicit nyitott döntés.

## Alternatívák

- A Material Requirement státuszának bővítése összekeverné a szükségletet a fedezési javaslattal.
- Azonnali Purchase Requisition létrehozás eltüntetné a planning–execution határt.
- Általános workflow engine vagy event sourcing aránytalan lenne a jelenlegi igényhez.
- Több működő stratégia bevezetése végrehajtási implementáció nélkül hamis képességet jelentene.

## Következmények

A netting, pegging, consolidation és supplier selection stabil planning artifactre
épülhet. A 0008 szándékosan nem kapcsol Supply Proposalt Material Requirementhez,
és approval után sem generál execution dokumentumot.
