# RMA-0003/C2-WP5 – Fee, Diagnostic Credit & Procurement Coverage

Státusz: **REVIEW / NOT MIGRATION READY**. Dátum: 2026-11-04.
Dokumentációs részletes terv, nem implementáció vagy elfogadási határozat.

## 1. Baseline and authority

Ellenőrzött branch: `feature/returns-repair-management`.
HEAD: `e21e83da44e7ec2228efa6c5c6f8d1021340b73b`.
Induló `git status --short --untracked-files=all`:

```text
?? .kiro/designs/rma-0003c2-wp1-schema-inventory.md
?? .kiro/designs/rma-0003c2-wp3-inventory-mrp-consumer-contracts.md
?? .kiro/designs/rma-0003c2-wp4-return-intake-stock-admission.md
```

Tracked diff és staging üres. Az előzmények belépési SHA-256 értékei:

| Dokumentum | SHA-256                                                            |
| ---------- | ------------------------------------------------------------------ |
| WP1        | `f4d6682d9bb0b8d58882263199919d9af394cfee58c0c5f0ea704ef6859fa932` |
| WP3        | `1a340ac30113417672869ff0d71bc854ce02f81d4ae2dfd2a5c09ff5ec22d994` |
| WP4        | `d7a1fc49dc37eadfeba6cfaaa0ee6abeb7319ef34cb4adde5f9a7a4a58538023` |

Current üzleti autoritás: [Accepted ADR-0006 RMA](../decisions/0006-rma-repair-management-architecture.md)
és [ADR-0001 Stock Movements](../decisions/0001-stock-movements.md).
Áttekintett előzmények: [A](rma-0003a-data-model-integrity-design.md),
[B](rma-0003b-integrity-verification.md), [C1](rma-0003c1-business-decisions-integrity-gates.md),
[C2 terv](rma-0003c2-detailed-schema-review-plan.md), [WP1](rma-0003c2-wp1-schema-inventory.md),
[WP3](rma-0003c2-wp3-inventory-mrp-consumer-contracts.md),
[WP4](rma-0003c2-wp4-return-intake-stock-admission.md).
Accepted D6/D7/D11 és AM-03/04 nem nyitott új üzleti jóváhagyásra; technikai bizonyításuk viszont nincs kész.

### 1.1. Bizonyítékosztályok

| Jelölés                   | Jelentés                                                                                                          |
| ------------------------- | ----------------------------------------------------------------------------------------------------------------- |
| CONFIRMED CURRENT         | A fenti RMA HEAD alkalmazáskódja/migrációja/dokumentuma igazolja                                                  |
| HISTORICAL                | Másik lokális ág rögzített Git-objectjében megtalált forrás; nem aktuális RMA-képesség vagy deployment-bizonyíték |
| MISSING                   | A megnevezett, ellenőrzött ág fájllistájában/forráskeresésében nincs meg a kérdéses objektum                      |
| UNVERIFIED                | Éles adat, grant, tesztfutás, külső integráció vagy nem ellenőrizhető működési állítás                            |
| ACCEPTED / OWNER-APPROVED | Korábban elfogadott ADR/C1 döntés, illetve az alábbi explicit projektgazdai kiegészítés                           |
| PROPOSED / OPEN           | Technikai terv review tárgya / elfogadott alapdöntésből nem következő üzleti részlet                              |

**WP5-REV02-01 – OWNER-APPROVED, projektgazda, 2026-11-04:** egy procurement generation
több source-line-t tartalmazhat, külön line-azonossággal, mennyiségi conservationnel és
konkurenciavédelemmel. Ez nem önkényes WP5-policy: a feladat közben explicit jóváhagyott kiegészítés.
Nem számozza át az RMA-BD-001–020-at vagy D1–D11-et, nem módosítja a megőrzött ADR/WP1/WP3 fájlokat.
A korábban felvetett `UNIQUE(material_requirement_id,procurement_generation)` source-korlát
ezért **nem alkalmazható** a több source-line-t engedő modellben. A pontos alternatív DDL még UNVERIFIED.

Kizárólag ez az új Markdown készül; nincs branchváltás, fetch, checkout, merge, staging vagy commit.
Mindkét ág vizsgálata read-only `git branch`, `git ls-tree`, `git show` és forrásolvasás.
Másik ág tartalma nem kerül a munkafába. Nem fut Laravel/DB/script/migráció/seeder vagy teszt.

## 2. Existing MES procurement behavior

### 2.1. CONFIRMED CURRENT alkalmazás

| Current source / method                                                                                                                      | CONFIRMED CURRENT behavior                                                                    | WP5 impact / PROPOSED                                                                         |
| -------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------- |
| [MaterialRequirementService::calculateBomItem](../../app/Services/Admin/MaterialRequirementService.php#L46)                                  | Egy production order x BOM; global stock-full active reserve es sajat reserve; float qty.     | D/R/N/coverage es több-PO owner aggregacio nem implementalt.                                  |
| [MaterialRequirementRepository::updateForProductionOrderBomItem](../../app/Repositories/Admin/MaterialRequirementRepository.php#L62)         | updateOrCreate(customer_order_item,required_item), egy order snapshot overwrite lehet.        | Stable owner/revision es actual consumption/procurement head-kulonbseg kell.                  |
| [PurchaseRequisitionService::create](../../app/Services/Admin/PurchaseRequisitionService.php#L44)                                            | Manual PR es line-ok source nelkul; status requested default.                                 | Manual/unassigned intenthez nincs fictiv RMA owner.                                           |
| [PurchaseRequisitionService::generateFromMaterialRequirements](../../app/Services/Admin/PurchaseRequisitionService.php#L124)                 | Missing>0, nincs source; requirement FOR UPDATE; item+unit grouping, egy source/igény/futas.  | Current legacy ut, nem SupplyProposal execution; REV02 uj split event/lineage kell.           |
| [PurchaseRequisitionService::generatePurchaseOrder](../../app/Services/Admin/PurchaseRequisitionService.php#L182)                            | Approved PR header lock; explicit supplierId; egy PO/minden line teljesqty; PR/items ordered. | Nincs source-readiness/supplier-authority/typed commitment delta a current flow-ban.          |
| [PurchaseRequisitionService::approve](../../app/Services/Admin/PurchaseRequisitionService.php#L103)                                          | Draft/requested precheck, transactionben approved+item status+audit; delete softdelete.       | Fresh guard es D11 commitment/review; approval nem actual supply.                             |
| [PurchaseRequisitionRepository::missingMaterialRequirements](../../app/Repositories/Admin/PurchaseRequisitionRepository.php#L54)             | WhereDoesntHave source + missing_quantity readmodel.                                          | Source-existence nem current open coverage, regenerationt nem ezen kell vegleg lezarni.       |
| [PurchaseOrderService::create](../../app/Services/Admin/PurchaseOrderService.php#L38)                                                        | Opcionális PR/PRitem es service received_quantity, manual PO.                                 | Parent/item/unit/source egyezes es actual receipt proof kulon.                                |
| [PurchaseOrderService::update](../../app/Services/Admin/PurchaseOrderService.php#L78)                                                        | Supplier/date/notes update; delete soft-delete; sent/ack-specific guard itt nincs.            | Supplier substitution nem silent history rewrite.                                             |
| [PurchaseOrderService::close](../../app/Services/Admin/PurchaseOrderService.php#L124)                                                        | Ordered/partiallyreceived -> Received status, GR nelkul.                                      | PO status nem physical supply, D11 cancellation/closure proof.                                |
| [GoodsReceiptService::post](../../app/Services/Admin/GoodsReceiptService.php#L84)                                                            | GR majd POitems lock, purchase_receive+balance+received counter+audit; posted retry refused.  | Owner coverage conversion nincs; GR partial qty ledger/stock egyszeri kozos transaction kell. |
| [StockReservationRepository::activeReservedQuantity](../../app/Repositories/Admin/StockReservationRepository.php#L36)                        | Teljes active reserved SUM, nincs partial consumed/released counter.                          | WP3 E/R es current remaining, nem credit vagy procurement accounting.                         |
| [ProductionTaskMaterialService::store](../../app/Services/Admin/ProductionTaskMaterialService.php#L28)                                       | Material+physical movement+balance, egesz reservation Consumed lehet részfelhasznalasnal.     | P owner/partial guard es actual C, nem reservation-statusbol C.                               |
| [StockBalanceRepository::increaseQuantity](../../app/Repositories/Admin/StockBalanceRepository.php#L73)                                      | Nullable batch firstOrCreate+increment; totalQuantity full physical SUM.                      | Company eligible/quarantine/sentinel key és közös mutex, nem planner által direkt stockírás.  |
| [ManufacturingIntelligenceRepository::procurementRecommendations](../../app/Repositories/Admin/ManufacturingIntelligenceRepository.php#L312) | Item-level open PO SUM es customer-order inner join, MAX(open) levonasa missing-SUM-bol.      | Repair owner/source/intent/commitment/receipt meaning, nem automatikus elo rendelés.          |
| [BusinessCacheInvalidator::invalidate](../../app/Services/BusinessCacheInvalidator.php#L123)                                                 | Nyitott transactionben afterCommit domain-generation rotate.                                  | Megorzendo; fee/coverage/receipt/cache hibak nem hozhatnak masodik postingot.                 |

Jelenleg nincs perzisztált repair fee/quotation/diagnostic credit vagy coverage-event implementáció.
A current procurement út nem tartalmaz SupplyProposal/ItemSupplier/netting/pegging/readiness külön
modulokat. A current PR generátor a tárolt missing_quantity-t használja; a beszállító kézi input
a PO-generálásnál. Az approved/ordered/received/cancelled enumérték nem önmagában supplier commitment,
actual receipt, számla vagy payment. Az AuditLogService és afterCommit invalidator meglévő közös infrastruktúra,
nem pénzügyi ledger vagy általános workflow engine.

### 2.2. HISTORICAL procurement források, branch-specifikus authority

A `feature/material-requirements-planning` lokális ág létezik, vizsgált commit:
`379be484405a789388df5f5e8367a18b1e598bbc`. Remote-tracking neve is jelen van;
nem történt fetch, ezért távoli aktuális állapotról nem állítunk semmit.
Az alábbi utak **Git-object utak**, nem a current munkafa relatív linkjei.
Olvasás: `git show 379be484405a789388df5f5e8367a18b1e598bbc:<path>`.
Az adott ág saját ADR-státuszát idézzük, nem fogadjuk el automatikusan RMA-baseline-ként.

| HISTORICAL Git-object path                                                 | Fejlecben deklaralt status                | Relevancia / nem current autoritas                                                                     |
| -------------------------------------------------------------------------- | ----------------------------------------- | ------------------------------------------------------------------------------------------------------ |
| `.kiro/decisions/0006-material-requirements-planning-architecture.md`      | Accepted (declared)                       | Planning/decision/execution boundary, StockMovement SoT; current ADR006 RMA-val azonos szam, masik ut. |
| `.kiro/decisions/0007-item-supplier-procurement-source.md`                 | Accepted (declared)                       | ItemSupplier mint procurement-source; nem demand pegging vagy tranzakcios ar.                          |
| `.kiro/decisions/0008-5-mrp-foundation-hardening.md`                       | Elfogadva és implementálva (declared)     | Requirement owner identity/time es legacy snapshot/fact separation.                                    |
| `.kiro/decisions/0008-supply-proposal.md`                                  | Elfogadva és implementálva (declared)     | Proposal nem requirement/PR/PO/stock; v1 teljes planning artifact lifecycle.                           |
| `.kiro/decisions/0009-material-requirement-netting.md`                     | Elfogadva és implementálva (declared)     | Exact netting read-result, time/pool allocation; nem fizikai reservation vagy stockiras.               |
| `.kiro/decisions/0010-requirement-pegging.md`                              | Elfogadva és implementálva (declared)     | Persisted planning pegs, nem execution coverage vagy StockReservation.                                 |
| `.kiro/decisions/0011-purchase-requisition-consolidation.md`               | Elfogadva és implementálva (declared)     | Approved Proposal consolidation; kulon proposal-source, unique proposal es teljes V1 consumption.      |
| `.kiro/decisions/0012-supplier-selection.md`                               | Elfogadva és implementálva (declared)     | Kozos header supplier eligible ItemSupplier-metszetbol, nincs implicit sor-supplier.                   |
| `.kiro/decisions/0013-replenishment-strategies.md`                         | Elfogadva és implementálva (declared)     | Planned vs requested/excess; MOQ/multiple csak Draft PR, exact base-unit.                              |
| `.kiro/decisions/0014-purchase-requisition-execution-readiness.md`         | Elfogadva és implementálva (declared)     | Current-state read-only readiness, nem business approval vagy tartos ready flag.                       |
| `.kiro/decisions/0015-purchase-order-generation.md`                        | Elfogadva és implementálva (declared)     | Hardened full PR->one PO, PR supplier authority, snapshots es nullable U(PR).                          |
| `.kiro/decisions/0016-purchase-order-dispatch-supplier-acknowledgement.md` | Elfogadva, implementációra vár (declared) | Dispatch/ack kulon fogalmak; deklaralt implementacio-varakozas es megtalalt source egyutt vizsgalando. |

Kiemelt implementációs bizonyítékok ugyanazon történeti commiton:

| HISTORICAL fájl / metódus                                                                                                                                                 | Ellenőrzött működés / current eltérés                                                                                                                                                       |
| ------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `app/Services/Admin/SupplyProposalService.php::create/update/propose/approve/reject/cancel/transition`                                                                    | Draft szerkeszthető; transition sorzár/tranzakció; approval master/source revalidation; downstream source után cancel tiltás. Nem requirement vagy actual supply                            |
| `app/Services/Admin/PurchaseRequisitionConsolidationService.php::consolidate` (L35)                                                                                       | Approved Purchase Proposalok ID szerint lockolva; strategy/supplier/date header-csoport, item+unit line-csoport; exact quantity; új draft PR-ek és proposal-source sorok egy transactionben |
| `app/Services/Admin/SupplierSelectionService.php::candidatesForPurchaseRequisition/selectSupplier/commonCandidates`                                                       | Eligible ItemSupplier-alapú közös header supplier; nem soronként tetszőleges supplier felülírás                                                                                             |
| `app/Services/Admin/PurchaseRequisitionReplenishmentService.php::calculateForPurchaseRequisition`                                                                         | Planned source total és requested/MOQ/excess külön; nem demand vagy source automatikus megnövelése                                                                                          |
| `app/Services/Admin/PurchaseRequisitionExecutionReadinessService.php::evaluate`                                                                                           | Authoritative readiness újraellenőrzés; UI-result nem execution authority                                                                                                                   |
| `app/Services/Admin/PurchaseRequisitionService.php::generatePurchaseOrder` (L198)                                                                                         | PR lock, existing PO guard, PR supplier authority; readiness source-lock után újra; execution snapshot; PR→PO nullable UNIQUE és egy PR→egy PO V1                                           |
| `app/Services/Admin/PurchaseOrderDispatchService.php::recordSuccessful/recordFailed/record`; `app/Services/Admin/SupplierAcknowledgementService.php::record/effectiveFor` | Külön dispatch/ack source fájlok jelen vannak. ADR-0016 fejlécében ugyanakkor „implementációra vár”; a fájlok létezése nem teljes teszt-/release-bizonyíték                                 |

Supply Proposal további megtalált HISTORICAL fájlok:
`app/Models/SupplyProposal.php`, `app/Enums/SupplyProposalStatus.php`,
`app/Repositories/Admin/SupplyProposalRepository.php`,
`app/Repositories/Contracts/SupplyProposalRepositoryInterface.php`,
`app/Http/Controllers/Admin/SupplyProposalController.php`,
`app/Http/Requests/Admin/StoreSupplyProposalRequest.php`,
`app/Http/Requests/Admin/UpdateSupplyProposalRequest.php`, `app/Policies/SupplyProposalPolicy.php`,
`database/migrations/2026_08_07_000002_create_supply_proposals_table.php`,
`database/factories/SupplyProposalFactory.php`, `tests/Feature/SupplyProposalTest.php`,
`resources/js/Pages/Admin/SupplyProposals/Index.vue` és
`resources/js/Pages/Admin/SupplyProposals/Partials/SupplyProposalStatusBadge.vue`,
`tests/frontend/pages/SupplyProposalIndex.test.js`.
Ezeket nem hoztuk át, és egyiket sem futtattuk.

### 2.3. Current / historical különbségek és integrációs kapu

| Terület              | CONFIRMED CURRENT                                                  | HISTORICAL / jövőbeli compatibility                                                                                                                                                                                         |
| -------------------- | ------------------------------------------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| ADR-0006 név         | RMA architecture                                                   | Másik fájlnévvel MRP architecture; azonos sorszám nem azonos döntés. Hivatkozás mindig ág+út+commit                                                                                                                         |
| Proposal             | MISSING current                                                    | Supply Proposal külön planning artifact, approval nem PR/PO/receipt; HISTORICAL ADR-0008                                                                                                                                    |
| PR source            | `purchase_requisition_item_sources`: requirement lineage           | Külön `purchase_requisition_item_proposal_sources`, U(supply_proposal_id); requirement source nem alakult polymorphic proposal sourcé                                                                                       |
| Quantity             | Több current tábla decimal(12,3), float aritmetika                 | Proposal/PR-line decimal(18,3), exact string quantity; requested=planned+replenishment excess, excess nem nő rá a proposal/requirementre                                                                                    |
| Supplier             | PO-generation input supplierId                                     | PR supplier authoritative; item-supplier eligibility/readiness és immutable execution snapshot                                                                                                                              |
| PR→PO                | Current generator státusz+PR lock                                  | Historical nullable UNIQUE(purchase_requisition_id), withTrashed duplicate guard, V1 teljes PR→egy PO                                                                                                                       |
| Netting/pegging      | Current global balance/full active reserve snapshot                | Historical külön netting result és planning peg; peg nem StockReservation vagy execution coverage                                                                                                                           |
| Requirement identity | Current upsert és RMA/C1 legacy terv customer-order-item+cikk      | HISTORICAL ADR-0008.5/0009 production_order_id+bom_item_id identitás. Ez nem azonos aggregátum; target adapter/migráció külön döntés, nem silent FK/owner-átírás                                                            |
| Incoming/time        | Current recommendation item-level státusz/expected-date összesítés | Historical ADR-0009 time-phased ordered/partially_received incoming és required_at cutoff, unknown date nem fiktív fallback. RMA D11 sent/ack proof és WP3 quarantine/remaining további követelmény, nem status-only import |
| Dispatch/ack         | Current service-ben nincs külön küldés/ack workflow                | Historical külön modellek/services/migráció és ADR; nem importált supplier promise                                                                                                                                          |

**Integrációs szerződés:** WP5 a current RMA-baseline-t írja le, és a historical compatibilityt
kötelező WP2/WP6 összehangolási kapuként kezeli. Nem indít párhuzamos supply-proposal,
supplier-selection vagy PO-generátor implementációt. Ha később történeti procurement funkciók
kerülnek be a célbaseline-ba, az authority a Proposal→PR execution boundary és külön
requirement↔coverage adapter szerint specifikálandó; a current legacy generátor nem nevezhető
automatikusan az új authoritative útnak. E baseline/adoption terv UNVERIFIED, nem történt merge.
Jelen WP5 sem historical MOQ, sem partial Proposal consumption, sem supplier substitution policyt
nem vezet be új current üzleti szabályként.
Külön precision-rés: a historical 0015 migráció planned/excess snapshotot decimal(18,3)-ként ad hozzá,
de önmagában nem bővíti a meglévő POitem ordered/received és GR/balance quantity teljes láncát.
Nem állítjuk, hogy a történeti ág már mindenhol 18,3 végponttól végpontig. A reference unit-price
snapshot sem supplier-confirmed tranzakciós ár vagy repair final-charge.

## 3. Repair fee lifecycle

### 3.1. Pénzügyi fogalmak – nem felcserélhetők

| Fogalom             | Autoritatív bizonyíték / határ                                                                                             |
| ------------------- | -------------------------------------------------------------------------------------------------------------------------- |
| Estimated fee       | Diagnosztikai díjszabás, operation/time/material estimate; nem ügyfélvállalás vagy számla                                  |
| Quoted fee          | Submitted immutable quotation+line/version/currency/scale/hash; tartalmi ajánlat                                           |
| Approved fee        | Az adott quote scope-jára jogosult ügyfélválasz+approval proof; nem payment vagy final charge                              |
| Final charge        | Alkalmazandó engedélyezett díjmegállapítás és külső/végleges elszámolási proof; pontos policy OPEN                         |
| Amount paid         | Ellenőrzött külső payment/settlement bizonyíték; current rendszerben nincs általános payment ledger                        |
| Outstanding balance | Csak azonos pénznem/skála/basis szerinti verified final-charge és applied-payment összesítésből ismert; unknown nem nulla  |
| Diagnostic credit   | D6/D7 szerinti, ugyanazon order igazolt diagnosztikai forrására egy effektív allocation; nem készpénz vagy átvihető wallet |

Repair approval nem payment; recorded payment nem repair approval. Sem a quotation total,
sem `settlement_observation='settled'` nem általános invoice/payment engine.
Adó, számlázás, refund, részfizetési policy, pénzügyi outstanding miatti végső close,
discount és FX szabály nincs e feladatban elfogadva. Ezek **OPEN**, nem rejtett automatizmusok.

### 3.2. Átmeneti szerződés

Minden alábbi írás service-transactionben: stable command type/key/hash + immutable history + audit,
aktuális owner/order/scope/permission locking guarddal. Actorok képességek, nem kitalált új szerepkörnevek:
diagnosztika/vizsgálat rögzítője, quote készítő/benyújtó, jogosult ügyfél vagy igazolt képviselő válaszát
rögzítő user, execution/final fee/settlement reviewer. Reporter külön lehet ownernél; reporter státusz
önmagában nem approval authority, de igazolt képviseletet sem tiltunk automatikusan.

| Átmenet                        | Preconditions / actor / evidence                                                               | State / immutable history / identity                                                           | Financial effect / failure                                                                                           |
| ------------------------------ | ---------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------- |
| F01 Diagnostic assessment      | Jogosult order/member/serial, elvégzett diagnosztika/QC/time/material proof, szükséges consent | Inspection és díjszabás snapshot, saját occurrence; order mode free/fixed/time_based           | Nem final bill vagy credit. Hiányzó proof nem lesz verified díj                                                      |
| F02 Verified diagnostic fee    | Calculation+consent+evidence dokumentum, verified actor/time; exact amount/currency/scale      | Immutable repair_diagnostic_fees version+supersedes; order mutex                               | Díjtény, nem számla. Fee correction új verzió, korábbi allocationt nem írja át                                       |
| F03 Fee proposal / draft quote | Aktuális scope és költségbecslés, quote-authorized actor                                       | Draft quote/lines szerkeszthető; version és creator/command history                            | Több credit estimate lehetséges, draft nem foglal kreditet vagy pénzt                                                |
| F04 Submit                     | Valid tartalom/lines/sum/currency/scale és customer target, benyújtó actor                     | Quote submitted_at/by/hash, submitted tartalom és lines immutable; submit occurrence           | Nem effective credit. Hibás total/precision/proof: teljes rollback                                                   |
| F05 Customer approval          | Aktuális, nem expired quote/scope/hash; jogosult válaszadó és konkrét approval proof           | Approval response+quote state+esetleges D6 allocation+history/audit ugyanazon commit           | Approved scope, nem payment; allocation csak itt. Más aktív credit/source vagy hibás proof egész approvalt blokkolja |
| F06 Rejection / expiry         | Customer rejection proof vagy valós valid_until vizsgálat, jogosult rögzítő                    | Új history és quote lifecycle; régi submitted tartalom marad                                   | Rejection/expiry nem refund és nem automatikus korábbi allocation release                                            |
| F07 Revision / supersede       | Új tartalmi scope/becslés, jogosult készítő; új verzió indoka                                  | Új draft, előző history megmarad; új scope a régi approval új munkára használatát felfüggeszti | Nincs új credit fee-version váltás miatt. F4 startguard újraértékeli az approvalt                                    |
| F08 Cancellation               | Jogosult lemondási döntés és indok/proof; aktuális execution/settlement állapot                | Immutable cancellation history; technikai cancelled quote-state javaslat, nem már létező enum  | Nincs automatikus fee-elengedés, allocation release vagy payment reversal; D7 review kell                            |
| F09 Repair execution           | Fizetős munkára aktuális approval+scope/expiry és permission; nem payment-statusból dönt       | Operation/time/material/QC history változatlan, command és scope hash                          | Tényleges execution nem bizonyít fizetettséget vagy az egész credit pénzügyi felhasználását                          |
| F10 Final fee determination    | Actual execution/material/time és alkalmazandó jóváhagyott díjalap, jogosult reviewer          | Végleges díj proof dokumentum+typed history; diagnostic fee változás külön új fee-version      | Final-charge algoritmus/rounding/policy OPEN; nem írjuk át a quote total-t „kifizetett” összeggé                     |
| F11 Settlement observation     | Ellenőrzött külső/belső proof, reviewer/time, explicit allocation/order                        | Új immutable review history: unknown/unsettled/settled; forrás/verzió dokumentum               | Nem invoice vagy banki művelet. Hiányzó adat unknown; korábbi observation nem overwrite                              |
| F12 Closure review             | Quote/final-fee/credit és outstanding felelősség teljes proofja, C1 close actor                | Typed close-proof history, kizárólag C1 restricted finalizer                                   | Sem approval/payment/execution önmagában nem case close. Pontos pénzügyi closure policy OPEN pontja nem vélelmezhető |

Tax/refund/expiration/supplier compensation policy választásához külön projektgazdai döntés kell;
ez a terv nem indít önálló accounting alrendszert. External API/payment call nem nyitott DB-tranzakcióban fut.

## 4. Diagnostic credit contracts

### 4.1. Accepted minimum és partial amount

**D6/D7 és AM-03:** verified fee a source; approval-only allocation; ugyanazon source order/quote/line,
currency/scale; egy effektív reserved/retained credit a teljes fee-verzióláncra.
Submitted-held/applied régi javaslat SUPERSEDED. Cancel/supersede nem automatikus feloldás.

| Fogalom                    | WP5 pontos jelentés                                                                                                                                                                                                               |
| -------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Entitlement / eligible cap | Igazolt, az adott orderre alkalmazható diagnosztikai fee-ből származó keret; nem feltétlenül minden jövőbeli accounting helyzetre szóló újrahasználati jog                                                                        |
| Approved credit amount `a` | Az aktuális quote approvalban bizonyított pozitív credit, legfeljebb az eligible verified fee `f`                                                                                                                                 |
| Allocated amount           | Immutable allocation.amount_units=`a`; source/fee/quote/line/currency/scale rögzül                                                                                                                                                |
| Consumed amount            | Csak konkrét külső/ellenőrzött felhasználási proofból ismert credit-hatás; execution vagy allocation state önmagában nem ilyen bizonyíték                                                                                         |
| Reversed amount            | Eredeti igazolt felhasználási eseményre mutató külön authorized correction/reversal proof; nem reservation release vagy automatikus pénzvisszatérítés                                                                             |
| Remaining available credit | Nem `fee - quote line` egyszerű wallet. Aktív reserved/retained allocation mellett további allokálható összeg 0; újraengedés csak D7 szerint igazolt unused/unsettled release után vagy külön rendezett correction policy alapján |

**Partial allocation:** `0 < a < f` egyetlen approval/allocation összegeként támogatandó D6 cap szerint.
Példa: verified fee10000 units, approved credit6000 units. Nem következik ebből párhuzamos
4000 units másik quote-ba allokálása: az active source-order UNIQUE azt is tiltja.
Részleges felhasználás után a maradék automatikus release/újraallokálása vagy cross-case transfer
nincs elfogadva; az érintett ág **OPEN/blocked**, a pénzügyi és execution proof megőrzött.
Nincs credit expiration, amíg nincs explicit owner-döntés. Quote expiry nem credit expiry.

### 4.2. Invariánsok és precision

```text
0 < allocated_units <= eligible_verified_fee_units
allocation.currency == fee.currency == quote.currency
allocation.money_scale == fee.money_scale == quote.money_scale
allocation.source_order == fee.order == quote.order == credit_line.source_order
credit_line.line_total_units == -allocated_units
count(effective reserved/retained allocations per source order) <= 1
0 <= effective_verified_used_units <= allocated_units
0 <= authorized_usage_reversal_units <= original_verified_used_units
```

Az utolsó két összeg ellenőrzött usage/correction evidence aktuális hatályos láncán értendő;
nem írunk új fizetési ledgert, és nem töltjük ki feltételezett consumed/reversed összegekkel a hiányzó proofot.
Ugyanazt a proof occurrence-t egyszer számoljuk; superseded/corrected előzmény nem plusz usage.
Fee correction vagy új quote-version nem nullázza a source felhasználási történetét/cap-kötöttségét.
Cap alá csökkentett fee és már igazolt credit-use eltérése review/correction, nem múltbeli debit/credit átírás.

Money signed BIGINT skálázott integer, money_scale 0..6, canonical currency és ellenőrzött overflow;
quantity exact decimal, sem money, sem quantity nem float. Rounding/tax/minor-unit business-policy OPEN;
formátum vagy currency-code létezése nem választ helyette policyt. Más currency/skála összeadása és
FX-conversion nem implicit. Quote total=sum(lines), credit előjele és levonása egyszer, nem kétszer.
Verified fee0/free nem pozitív allocation, zero credit estimate nem active claim.

### 4.3. Lifecycle és review

| Credit művelet            | Kötelező transaction és proof                                                                       | Eredmény / tiltott következtetés                                                                                  |
| ------------------------- | --------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------- |
| Create/authorize          | Verified diagnostic fee+approval/credit line, aktuális order lock, fee/quote/current scope/cap      | Csak approvalkor reserved + active_source_order_id; estimate/submit nem allocation                                |
| Consume observation       | Konkrét allocationra/currency/amount-ra vonatkozó verified usage proof, actor/time/source key       | Új typed credit history; nem application által végzett banki vagy invoice posting. Unknown use nem nulla          |
| Cancel/supersede review   | Execution-review és settlement-review document, observation/reviewer/time                           | State retained/blocked ha execution történt, settlement unknown/settled vagy nincs unused proof; active key marad |
| Authorized unused release | Fel nem használt és unsettled teljes allocation igazolt, jogosult reviewer, release proof           | State released, active key NULL, released_at/document+history; régi sor soha nem törölt/újraaktivált              |
| Reversal/correction       | Eredeti usage/allocation/fee proof, jogszerű authorization és indok; pontos accounting eljárás OPEN | Új immutable causal proof; nem automatikus credit újraengedés, refund vagy fee-version reset                      |
| Final reconciliation      | Fee/quote/approval/allocation/review/usage/correction lánc egyezése                                 | Unknown/conflict/settled korrekciós kötelesség nem tűnhet el quote cancel vagy case close miatt                   |

Az összeg/cap és effective-use cross-row guard; CHECK nem összegez idegen fee/history sorokat.
Runtime UPDATE/DELETE/raw bypass és az orphan allocation proof-teljesség WP2 restricted DML/principal/routine
bizonyítás tárgya, service-validáció egyedül nem elég. U(active_source_order_id) nullable jelentése
explicit CHECK-kel: reserved/retained→nem-NULL=source; released→NULL+release proof. Fee-verziónkénti
egyediség önmagában túl gyenge, source-order szint megmarad.

## 5. Repair material requirements

### 5.1. Mennyiségi tények és authoritative források

| Fogalom                   | Autoritatív input / jelentés                                                                                                      |
| ------------------------- | --------------------------------------------------------------------------------------------------------------------------------- |
| Required quantity `D`     | Jóváhagyott repair scope/operation material szükséglet owner+cikk+unit aggregate; current revision mögött immutable snapshot      |
| Reserved `R`              | Saját eligible company aktív remaining=reserved−consumed−released; nem teljes eredeti reserve vagy workflow custody               |
| Procurement demand        | Physical hiány és még nem lefedett, authorized delta; nem minden recalculationből új PR                                           |
| Committed quantity `K`    | Saját current coverage-ből suppliernek valóban kiküldött/acknowledged, még be nem érkezett mennyiség; PO státusz nem elég         |
| Received `G`              | Tényleges posted GR-item + movement és own coverage supply_received; nem PO received enum                                         |
| Eligible received `Ge`    | `G` azon része, amely current verified company scope/condition/disposition alapján WP3 E szerint használható; quarantine G nem Ge |
| Consumed `C`              | Immutable repair_materials+stock_movement és owner/operation kapcsolata; reservation vagy GR nem consumption                      |
| Remaining requirement `N` | Hatályos demandból a tényleges effective consumption után hátralévő rész; történeti felhasználás revíziótól nem törlődik          |

Repair operation→owner material requirement→reservation/coverage→PR/source→POitem→GRitem→eligible stock
→actual consumption külön tények. Egy repair-order+cikk aggregált requirement; operation-level kapcsolat
megőrzött az actual material/scope snapshoton, nem kitalált production_order vagy fake customer-order item.
Legacy customer-order-item+cikk owner megmarad, több production order aggregate-jét nem írhatja felül
egy order számítása. Unit eltérés conversion policy nélkül tiltott; a scope változása nem owner-átírás.

### 5.2. Netting és belső intent / külső commitment

```text
remaining_demand N = max(0, active_authorized_D - effective_actual_C)
consumption_overrun = max(0, effective_actual_C - active_authorized_D)   -> explicit review
physical_shortage = max(0, N - own_eligible_remaining_R - free_stock_allocated_once)
current_open_coverage = valid_uncommitted_internal_intent I + firm_unreceived_commitment K
new_procurement_delta = max(0, physical_shortage - I - K)
```

`I` ugyanazon owner/current demandhoz rendelt, még nem visszavont PR/draft-PO intent; megakadályozza
a már folyamatban lévő procurement megduplázását, de nem supplier promise és nem fizikai readiness.
`K` ugyanazon coverage firm állapota, nem az `I` mellé még egyszer hozzáadott mennyiség.
Source existence önmagában egyik sem current valid mennyiség. Unknown legacy commitment review-t
blokkol, nem automatikusan draft. Unassigned outstanding commitmentet új vásárlás előtt review/allokáció
alá kell venni, nem törölni vagy minden owner számára szabad supplynak tekinteni.

Free stock elosztása item mutex alatt legfeljebb egyszer történik; tényleges allokáció az existing
reservation mechanizmussal követett. Ugyanaz a qty nem szerepel egyszerre `free_stock_allocated_once`
és saját `R` értékében. Read-only preview nem persistent ígéret több ownernek ugyanarra a szabad stockra.
Open coverage nem available physical; GR után a received qty nem marad open coverage is.
Ha G quarantine/noneligible, a fizikai hiány fennmaradhat, de ez nem automatikus újravásárlási engedély:
supplier/quality/return/replacement obligation review és authorized delta kell a következő generationhöz.

### 5.3. Scope/revision/cancel változások

Demand revízió csak valódi üzleti változásra, nem retry/újraszámításra. Generation csak authorized
valós procurement/coverage-deltára; a kettő nem ugyanaz a sorszám. Qty-csökkentés nem nullázza C-t
vagy a reservation consumed counterét. Overrun negatív quantity helyett explicit review/history.
Saját unused reservation részleges feloldása csak E/R szerint, actual consumed nem release.
Draft intent módosítása review-val, approved/sent/ack commitment D11 szerint külön cancel/reassign;
receipt nem tűnhet el demand withdraw miatt. Withdrawn demand nem generál új beszerzést.
Új anyag/scope fizetős munkánál F4 approval-gate-et is újraértékeltet.

## 6. Procurement coverage and source allocation

### 6.1. REV-02: bizonyított cardinality és jóváhagyott kiegészítés

| Alak                                             | CONFIRMED CURRENT / HISTORICAL tény                                                                                 | WP5 szerződés                                                                                                                                              |
| ------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Egy requirement→egy PRitem                       | Current generator egy futásban egy source/requirement                                                               | Támogatandó egyszerű eset; nem globális max-one szabály                                                                                                    |
| Egy requirement→több PRitem ugyanazon generation | Current séma U(PRitem,requirement) engedi több line-ra; current generator nem állítja elő                           | **OWNER-APPROVED WP5-REV02-01**, explicit line identity+budget+conservation; nem kell fake új generation minden splithez                                   |
| Több requirement→egy PRitem                      | Current item+unit grouping, source soronként qty                                                                    | Minden owner/source trace megmarad, direct pointer csak reprezentáns                                                                                       |
| Egy requirement→több POitem                      | Current FK-alak megengedheti külön manual POitem-ekből, de generator teljes PR-t egyszer másol; aggregate cap nincs | Line/source/commitment allocation guard kell; nem állítunk már működő split-generátort                                                                     |
| HISTORICAL Proposal→PRitem                       | ADR-0011 teljes Proposal consumption, U(supply_proposal_id); több Proposal consolidate egy line                     | Requirement-generation split jóváhagyása **nem** partial Proposal execution engedély. Külön proposal-source lineage megmarad                               |
| Partial PO fulfillment / GR                      | Current több GR received countert összegez; owner receipt-split ledger nincs                                        | Owner allocations összege legfeljebb actual GR quantity; allocation priority/arány policy nincs kitalálva                                                  |
| Supplier substitution                            | Current PO update supplier_id-t módosíthat; historical PR supplier authority/readiness más                          | Sent/ack/received lineage átírása nem accepted cancellation. Authorized correction/cancel+új source/commitment proof, automatikus substitution policy OPEN |
| Replenishment excess                             | Current nincs modul; historical requested=planned+MOQ excess                                                        | Nem nő meg a repair requirement vagy proposal-source qty a többlettel. Excess külön unassigned inventory/procurement responsibility                        |
| Regeneration                                     | Current whereDoesntHave source blokkolna minden további generálást                                                  | Authorized valódi uncovered delta új generation lehet; source múlt nem törölt, retry és split önmagában nem új generation                                  |

Vizsgált alternatívák: A – source-on U(requirement,generation), egyszerű, de tiltja a jóváhagyott
line-splitet; B – több source line egy generation budget alatt explicit line identityvel.
A projektgazda B-t engedélyezte. **Üzleti cardinality eldöntött; a pontos DDL/runtime garantálás UNVERIFIED.**

### 6.2. Generation és source identity – PROPOSED, REV-01-ciklus nélkül

Egy source nem azonos requirementtel, Proposal-lal, POitemmel vagy generationnel.
Meglévő source anchor = PRitem+requirement, immutable initial quantity és creation snapshot.
A generation egy authorized delta/terv, melynek több line-intentje lehet, külön canonical
`source_line_key` identitással. Új source creation `generation_key` a teljes requirement+generation+
line occurrence egyediségét képviseli, nem csupán requirement+generationt.

Új technikai eventjelölt ugyanazon `procurement_coverage_events` táblában:
`generation_authorized`: requirement/revision/generation, action/reason, teljes authorized delta,
canonical line-plan/payload hash, actor/proof/command identity. Nincs hozzá előre source FK.
Egy generation-head event/requirement/generation; a source-line-okban **nincs** U(requirement,generation).
A jóváhagyott több line mennyisége összesen a generation budgettel egyezik, minden line azonos
item/base-unit/current owner-scope mellett. Split nem növelheti meg az authorized deltát.
Példa: ugyanazon requirement/G budget10, két PRline source4+6, két stabil line-key; összesen10.
Ha több intent ugyanarra a PRitem+requirement párra konszolidálódik, egy source anchor alatt
több explicit generation-line allocation occurrence őrizheti a trace-et; nem készül az existing
U(PRitem,requirement)-et megkerülő duplicate source. Az initial source snapshot a commitolt initial
line-allocation mennyiségek összegével egyezzen. Canonical line-key retrykor stabil.

Insert protokoll: requirement parent → generation-head event G → source anchorok FK→G
→ coverage_allocated line-események FK→source és G → PR/current projection/history/audit → commit.
Nincs körkörös G→future source FK vagy immutable event utólagos update. Source creation és első
incoming coverage event egy commit; orphan G/source/budget-túllépés runtime útját WP2-nek zárnia kell.

Meglévő anchor további release/reassign/receipt/correction eseményhez újra használható; nem kell
duplicate source és az U(PRitem,requirement) törlése. Valódi új delta az existing PRline-hoz csak
authorized line/commitment szerződés mellett adható; máskülönben új line/anchor. Az anchor eredeti
quantityja nem írható át és nem adódik még egyszer az eseményösszeghez. Generation head és line
occurrence az új eseményben is megőrzött; nem mutatunk történeti source-t a requirement mutable
current-generation mezőjére FK-ként.

### 6.3. Coverage quantity / commitment / receipt fold

```text
open_assigned(source/root, owner, item, unit) =
    initial_or_new_allocations + reassigned_in - releases - reassigned_out - actual_supply_received
open_assigned >= 0
paired_transfer.from.quantity == paired_transfer.to.quantity
sum(active assigned portions on commitment) <= actual remaining commitment quantity
sum(owner supply_received portions on GRitem) <= GRitem actual posted quantity
```

A source.quantity az eredeti allocation snapshotja, **nem plusz addend**. Ha új anchor első
incoming eseménye reassigned_in, nem rögzítjük ugyanazt q-t coverage_allocated-ként is.
`commitment_confirmed(sent/supplier_ack)` a meglévő coverage firm/confidence besorolása,
nem új +q allocation. Ack ugyanarra a qtyra nem duplázza a sent coverage-et.
Demand revised/withdrawn a demandot módosítja, nem önmagában coverage vagy stock-delta.
Release önmagában nem supplier cancellation. Correction új superseding tény; az effective fold
vagy a korrekciós delta explicit típusszerződés szerint egyszer számít, nem mindkét alak összeadva.

| Esemény                    | Required links / quantity                                                             | Fold és proof                                                                                   |
| -------------------------- | ------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------- |
| generation_authorized      | Requirement+revision+generation, q>0 és line budget; source/PO/GR NULL                | Nem open supply +q; allocation line-ok később adják a q-t, atomikus budget-guard                |
| demand_revised / withdrawn | Stable requirement/owner, before/after qty/unit/reason snapshot                       | Demand változik; coverage/commitment változatlan külön authorized eventig                       |
| coverage_allocated         | Requirement+source+G+line occurrence, q>0                                             | +q internal intent/hozzárendelt coverage, nem physical/firm supply önmagában                    |
| commitment_confirmed       | Source/allocation root+POitem, kind sent/ack, actual proof                            | Ugyanaz a q firm állapotba kerül, nem második +q; version/occurrence és current effective proof |
| coverage_released          | Source+eredeti allocation/root, q>0 és authorized review                              | −q owner assigned; linked PO supplier obligation fennmaradhat unassigned formában               |
| coverage_reassigned        | Közös transfer_key, from/to side, forrás- és célowner/source, q>0                     | Két oldal atomikus, conservation; nincs körkörös paired FK vagy utólagos event összelinkelés    |
| commitment_cancelled       | POitem/source/root és supplier/business cancellation evidence                         | Commitment és hozzárendelt coverage rendezése külön explícit release/transfer; nem receipt      |
| supply_received            | Posted GRitem+POitem+source/root+requirement, q>0                                     | −q open coverage, actual physical posting egyszer; Ge lehet kevesebb mint G                     |
| responsibility_handed_off  | Eredeti requirement/source/commitment context, named responsibility/proof, q snapshot | Nincs önálló +/−q; a tényleges assigned release/transfer külön event, nincs dupla delta         |
| correction                 | Eredeti/supersedes occurrence, indok/actor/proof, typed effect                        | Nem törlés, nem negatív physical receipt vagy elrejtett supplier cancel                         |

Az elosztási priority (FIFO/pro-rata/explicit manual), overreceipt és supplier-substitution részletszabály
OPEN. Konkrét owner/qty distribution hiányában nincs vélelmezett teljes allocation egy demandra.
Manual/unassigned PR/PO/GR számára nem készül fictív RMA/requirement: residual commitment/supply
az authoritative PO/GR minus effective assigned mennyiségből követett, explicit procurement responsibilityvel.
A coverage ledger csak valódi requirement/source kapcsolatokhoz ír sort.

### 6.4. Supply Proposal és pegging compatibility

A HISTORICAL proposal-source külön tábla marad; a requirement-source nem kap generic nullable
source_type/source_id-t. Proposal approval nem coverage commitment, peg nem StockReservation,
netting read-result nem execution event. Proposal→requirement/RMA owner adapter nem igazolt a current
ágban; historical Proposal v1 sem önmagában requirement-identity. A szükséges typed összerendelés
és future baseline/adoption review kapu, nem feltételezett létező relation.
Partial Proposal consumption tilalmát nem írja felül a requirement multi-source engedélye.

## 7. Responsibility handoff

Handoff explicit, auditált assignment egy megnevezett felelősséghez; nem claim/quantity eltüntetés.
Az elfogadott D11 procurement handoffot nem terjesztjük ki automatikusan minden unpaid pénzügyi
kötelezettség elengedésére vagy átadására. Két külön személy kötelező aláírását sem találjuk ki:
exact authorization/acceptance policy OPEN, de címzett felelősség és proof mindig szükséges.

| Handoff                                        | Responsible domain / authoritative record                                                | Evidence / qty / current status                                                                      | Cancel/reverse / closure consequence                                                                                        |
| ---------------------------------------------- | ---------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------- |
| Repair scope→material planning                 | RMA execution scope + stable material_requirement revision                               | Operation/quote approval/actual need; item/unit/D/N, current owner                                   | Revise/withdraw új snapshot; nem törli C/coverage-et. Unresolved active need close-kapu                                     |
| Planning→PR/proposal review                    | Current: requirement/source/G; historical future: explicit approved proposal adapter     | Authorized delta/line plan és generation, internal intent I                                          | Proposal/PR rejection nem supplier cancel; nincs új generation puszta retryból                                              |
| PR→procurement PO                              | Procurement, PR line/source és PO item lineage                                           | Approval/supplier/readiness bizonyíték a tényleges baseline szerint; q és supplier context           | Sent/ack commitment külön proof; PO creation nem fulfilled repair requirement                                               |
| Procurement→supplier commitment                | Procurement, POitem + immutable sent/ack evidence                                        | Firm q/date/identity; unknown külön state                                                            | Cancellation/substitution nem silence update/delete; supplier obligationt named domain kezeli                               |
| Supplier→goods receipt                         | Receiving, posted GRitem + movement + supply_received owner portions                     | Actual q/item/unit/batch/serial/hely/time; partial receipt és residual open qty                      | GR != payment vagy consumption; hibás/karantén supply nem automatikus új procurement                                        |
| Receiving→inventory                            | Inventory movement/physical projection és disposition                                    | G vs Ge külön, quarantine ownership/condition verified                                               | WP4 intake/admission nem supplier GR; quarantine nem eligible supply                                                        |
| Inventory→repair execution                     | RMA operation, own reservation/material/movement                                         | Remaining reservation és actual consume C                                                            | Partial release nem consume, actual consumed history nem törölt; owner guard                                                |
| RMA withdraw→procurement retained obligation   | Procurement, fennmaradó PO/GR plusz requirementhez kötött handoff/release/transfer event | Named responsible domain/user vagy engedélyezett responsibility identity, q/status/proof, actor/time | RMAhoz rendelt részt rendezi, supplier commitment élhet; case-close csak explicit handoff után, nem fake PO cancel/received |
| RMA financial review→settlement responsibility | Order/allocation/verified fee és külső settlement proof                                  | Unknown/unsettled/settled, execution/usage/correction state                                          | Pénzügyi closure/handoff konkrét policy OPEN; D11 önmagában nem financial forgiveness                                       |

Unassigned supplier commitment residual mennyiség nem válhat minden owner szabad fedezetévé.
Handoff event q snapshotja nem önálló második release; original source/PO/GR és supplier history megmarad.
Ha nincs named responsibility/evidence vagy eltérnek a mennyiségek, handoff és case-close blokkolt.

## 8. Transaction and concurrency matrix

**C1 T-C1-04 lock-sorrend változatlan:** case-ek → items → item_instances → repair/production order
→ operations/fee/quote → requirements/coverage → reservations → balances → release/proof;
csoporton belül ID szerint. Parent/case terv előre, item után nem kérhető késői case-lock.
Missing balance item mutex; SUM nem zárolás. PR/PO/GR és historical Proposal/ItemSupplier lockok
e sorrendbe illesztése WP2-kapu, nem automatikus teljes concurrency-bizonyíték.

Minden alábbi javasolt írás egy outer service transaction: guard+domain fact+mutable projection+
typed history+command completion+audit; bármi hiba teljes rollback, retry csak stabil occurrence/hash
és friss policy mellett. Nincs routine belső COMMIT vagy external hálózati hívás nyitott transactionben.

| Scenario                               | Transaction / lock owner and order                                                    | Business identity / DB constraints / application guards                                          | Conflict / postcondition                                                                                                                | Verification                    |
| -------------------------------------- | ------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------- |
| C01 Fee approval vs revision           | Order/current quote+fee lock L                                                        | Command+quote version/scope hash; U(order,version), submitted immutability; fresh approval scope | Egy current approval/allocation vagy uj draft; stale approval refused; minden failure teljes rollback, bounded same-key/hash retry      | T01/T02/T28                     |
| C02 Approval vs cancellation           | Order/quote/allocation/review lock                                                    | Source active U, completed customer proof; D7 execution/settlement guard                         | Cancel nem release; az egyik sorrend szerinti canonical outcome; minden failure teljes rollback, bounded same-key/hash retry            | T03/T10/T28                     |
| C03 Duplicate credit allocation        | Order->fee/quote/allocation                                                           | U(active_source_order_id), U(line), command/hash; cap/currency/owner                             | Egy active credit, nem fee-versionenkent uj; loser conflict; minden failure teljes rollback, bounded same-key/hash retry                | T04/T06/T07/T09                 |
| C04 Allocation vs reversal             | Order/allocation+original usage/review chain                                          | Original-history FK/U occurrence, amount-cap, authorized external correction proof               | Nincs auto release/reuse vagy history overwrite; policy OPEN blocked; minden failure teljes rollback, bounded same-key/hash retry       | T08/T42                         |
| C05 Credit usage vs settlement         | Order/allocation/current review                                                       | Typed immutable usage/settlement source key, no double effect; unknown != unsettled              | Fresh proof vagy retained/blocked; egy event-fold, nem payment call; minden failure teljes rollback, bounded same-key/hash retry        | T10/T35/T42                     |
| C06 Concurrent material reserve        | Case/item/serial/order->requirements->reservations->balances                          | Active serial U/owner XOR, own remaining E/R/P; shared item mutex                                | Nem tobb owner ugyanazon free supply; actual partial counter; minden failure teljes rollback, bounded same-key/hash retry               | T13/T24/T25                     |
| C07 Demand reduction vs reserve        | Owner/item/requirement revision->reservation                                          | Stable owner U, revision event identity, consumed immutable, unused release proof                | D csokkenes nem C reset vagy negativ R; unresolved quantity review; minden failure teljes rollback, bounded same-key/hash retry         | T12/T13                         |
| C08 PR consolidation vs regeneration   | Item/owner/requirement/G/source/PR; historical Proposal id-order is L-hez illesztendo | G-head singleton, line/source occurrence U, budget; historical U(proposal) kulon                 | Ket caller nem uj generation ugyanarra retryra; split osszesen delta; minden failure teljes rollback, bounded same-key/hash retry       | T15/T16/T17/T22/T23/T29/T37/T38 |
| C09 PO generation vs PR cancel         | L alapjan source/commitment es PR/PO roots                                            | Current fresh status/lineage; historical readiness+U(PR->PO); D11 cancel proof                   | Egy soros execution vagy cancel refusal, supplier commitment nem veszik el; minden failure teljes rollback, bounded same-key/hash retry | T18/T19/T29                     |
| C10 GR vs PO modification              | Item/requirement/source/commitment roots es GR/POitem/balance L-sorrendben            | Actual GR/source/item/unit/q cap, posted identity, sent/ack snapshot                             | Nincs supplier/line utolagos silent rewrite; receipt/coverage atomic; minden failure teljes rollback, bounded same-key/hash retry       | T19/T20/T21/T40                 |
| C11 Partial GR vs shortage recalc      | Item+requirement/coverage->reservation/balance/GR                                     | Receipt allocation occurrences, sum<=GRqty; I/K->received conversion egyszer                     | Nem open coverage es physical is; quarantine Ge=0 helyesen; minden failure teljes rollback, bounded same-key/hash retry                 | T14/T20/T23/T24/T40             |
| C12 Procurement handoff vs case close  | C1 case lock ->order->requirement/coverage/PO obligation proof                        | Typed handoff domain/recipient/qty/current version, finalizer guard                              | Residual obligation gazdatlanul nem tunik el; close csak rendezett handoff; minden failure teljes rollback, bounded same-key/hash retry | T27/T33/T44                     |
| C13 Consumption vs reservation release | Item/serial/order->requirement->reservation->balance                                  | Own owner/purpose/unit/remaining, stable material/movement command                               | Counter nincsen resetelve, consumed nem release, whole rollback; minden failure teljes rollback, bounded same-key/hash retry            | T13/T25/T31                     |
| C14 Case close vs unresolved fee       | C1 case/order/fee/quote/allocation/current proof                                      | Restricted finalizer EXECUTE/DML; full typed credit/settlement state, nem financial_ok flag      | Unknown/correction/finalfee policy hiany close-block; claim megmarad; minden failure teljes rollback, bounded same-key/hash retry       | T26/T41/T44                     |

Current veszély: GR→POitem→balance és a javasolt item→requirement/coverage→reservation/balance,
valamint historical PR→Supplier/ItemSupplier lockok fordított sorrendje. A sorzár puszta jelenléte
nem bizonyít deadlockmentességet. Whole-transaction bounded retry szükséges; részlépést nem ismétlünk
külön, számokat vagy allocationt nem duplázunk. UI readiness/cached availability nem current-state guard.

## 9. Data model and integrity constraints

### 9.1. Közös típus-, index- és retention-szerződés

Új RMA jelöltek továbbra is a WP1 25 tábláján belül; nincs új általános accounting vagy inventory ledger.
Minden új table id unsigned BIGINT PK, created_at UTC dateTime(6); mutable projekció updated_at,
append-only eventen nincs business UPDATE vagy soft-delete. Minden F unsigned BIGINT valódi FK,
delete/update effective NO ACTION/RESTRICT, child index, composite tuple teljes index és parent U.
Nem ugyanazt jelenti az EVENT és a mutable head-pointer; pointer frissíthető ugyanazon fact-transactionben.

Money M=signed BIGINT integerunits + S(3)currency + scale unsigned0..6; allocation pozitív,
credit quote line negatív. Quantity Q current decimal(12,3), historical planning/PR ágban decimal(18,3).
Ez valós precision-különbség: nem szűkíthető silent casttal. Exact canonical base-unit arithmetic,
összeadás/szorzás/rounding és BIGINT overflow guard; a végleges uniform precision WP2/WP6 profile.
Nem telepítünk decimal dependencyt és nem választunk itt implementációs könyvtárat.
Binary/canonical command/line/hash kulcsok, nem véletlen case-insensitive equality.

| Structure                            | Ownership / mutability                               | PK/FK/nullability/U/index/type review                                                                                                                                                                                                                                                                                                             | CHECK / immutable/delete / cross-row guard                                                                                                                                                     |
| ------------------------------------ | ---------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| repair_orders                        | Source of Truth / mutable execution                  | PK id; member FK! U, work_number U; diagnostic mode/rate?/scale?/currency?/consent?; IX(execution_status,created_at).                                                                                                                                                                                                                             | Mode/free/fixed/time CHECK es rate-shape; parent/member immutable, díjszabás first executiontol; fee-verification nem itt.                                                                     |
| repair_diagnostic_fees               | Immutable verified fee                               | PK id; order F!, version U32!, amount M!, currency/scale!, calculation J!, consent/evidence F!, actor/time!, supersedes_fee F?, command F!; U(order,version), U(supersedes), U(command), U(id,order); IX(order,verified_at).                                                                                                                      | amount>=0, version>=1, scale0..6; correction ugyanazon order FK/guard; insert utan immutable, cap cross-row.                                                                                   |
| repair_quotations                    | Mutable draft/lifecycle, submitted immutable content | PK id; order F!, version!, currency/scale/total!, hash?/submitted/responded fields?, customer/document/actor F?; U(order,version), U(command), U(id,order); IX(status,valid_until).                                                                                                                                                               | Total>=0; non-draft submit proof, approval/reject response proof; customer authority/current scope/expiry/sum service guard.                                                                   |
| repair_quotation_items               | Mutable draft -> immutable submitted line            | PK id; quotation F!, line_number!, item F?, diagnostic_source_order F?; qty Q!, price/total M!; U(quote,line), U(id,quote,source_order); IX(item,created_at).                                                                                                                                                                                     | Qty>0, credit negative/source nonnull vs other positive/source NULL; price\*qty rounding, line/cap/sum foreign-row guard.                                                                      |
| repair_diagnostic_credit_allocations | Immutable amount/source, mutable state projection    | PK id; source_order/quote/line/fee/command F!, active_source F?, latest_review F?, released_at/doc?; U(active_source), U(line), U(command); composite fee/order,quote/order,line/source; IX(source_order,allocated_at).                                                                                                                           | Amount>0, scale0..6; reserved/retained active=source vs released activeNULL+proof; old amount/source/history not modified.                                                                     |
| repair_status_histories              | Append-only typed evidence/review                    | PK id; XOR case/order F?, command F!, seq!; allocation F?, related/original/supersedes history F?; execution/settlement doc F?, reviewer/time?, observation?; proposed credit_effect_units M?/currency?/scale?/kind?; U(command,seq); IX(allocation,occurred_at).                                                                                 | Typed nullable shape: review/proof/usage/correction guards, unknown/unsettled/settled, nonnegative observed magnitude; no general payment ledger; original-correction same allocation/subject. |
| repair_commands                      | Mutable processing->completed then immutable         | PK id; type/key/hash/actor F!; result fields?, completed_at?; U(type,key), IX(actor,created_at).                                                                                                                                                                                                                                                  | Processing nem successful partial commit; same-key/hash/auth replay; result morph nem domain proof; DDL csak row-shape, full transaction guard kell.                                           |
| repair_operations                    | Scope/execution snapshot and projection              | PK id; order F!, attempt/sequence!, scope_quote F?, supersedes F?; U(order,attempt,sequence), U(id,order); IX(order,status).                                                                                                                                                                                                                      | Started instructions/plan/QC snapshot immutable; new scope F4 approval recheck. Never original production task rewrite.                                                                        |
| repair_materials                     | Immutable actual consumption                         | PK id; order F!, operation/reservation F?, item F!, batch/serial F?, qty Q!, movement/command F!; U(movement), U(id,order); IX(order,item); operation/order composite.                                                                                                                                                                            | Qty>0, serial qty1; owner/item/unit/batch/serial/location eligible guard, counter+physical+material audit atomikus.                                                                            |
| material_requirements                | Stable owner demand / mutable head projection        | Existing PK id; required_item F!, customer_owner F? vs repair_order F? XOR; required/available/reserved/missing Q; demand_revision/state/gen; current_revision_event F?; U(customer_owner,item), U(repair_order,item), IX(state,item).                                                                                                            | Nonnegative qty, revision>=1; stable owner; head pointer is mutable, historical events not. Legacy multi-PO aggregation and unknown mapping audit.                                             |
| procurement_coverage_events          | Immutable demand/G/coverage/responsibility ledger    | PK id; requirement F!, revision!, eventtype!, source/POitem/GRitem/G/root F?; q Q!, unit!, before/after J!, actor/time!; key/seq/hash!; transfer_key/side?, supersedes F?, repair_command F?; U(key,seq), U(transfer,side), U(supersedes), generation-head U; IX(req,revision,time), IX(source,type,time), IX(POitem,type,time), IX(GRitem,type). | 6.3 type-shape; q>0 for deltas, generation head budget positive; no fictive RMA legacy; lineage/totality/effective fold/conservation cross-row; handoff q snapshot not double delta.           |
| purchase_requisition_item_sources    | Immutable initial requirement-source anchor          | Existing PK id; PRitem F!, requirement F!, initial quantity Q!; creation_revision/gen/G-event F+number!, source_line_key!, generation_key!; U(PRitem,req), U(generation_key), proposed U(req,creation_gen,line_key); IX(req), IX(G-event).                                                                                                        | Q>0, no U(req,gen). Initial snapshot not current coverage; FK CASCADE->RESTRICT proposal; actual legacy source baseline validated once.                                                        |
| purchase_requisitions                | Existing procurement header                          | PK id, business_number U, status; requester F?, timestamps/notes?; existing items relation. Current nincs supplier/date planning fields; HISTORICAL nullable supplier F?, required_at?/proposed_supply_at? only on that branch.                                                                                                                   | Delete/status guard D11; confirmed current notes-only update. No new unconditional supplier column claimed; historical current merge/retention review required.                                |
| purchase_requisition_items           | Existing line requested qty / owner list             | PK id; PR F!, direct requirement F?, item F!, quantity Q!, unit!; source relations; historical planned/excess/replenishment fields only branch-specific. IX(parent FK).                                                                                                                                                                           | Current direct pointer compatibility only, source total atcreation; historical planned+excess=requested, immutable proposal-source qty. SET NULL/CASCADE guard/retention review.               |
| purchase_orders                      | Existing supplier commitment document                | PK id; supplier F!, PR F?, creator F?, number U/status/date; current PR FK not U; historical U(PR) and immutable supplier snapshots.                                                                                                                                                                                                              | Current ordered/received status not sent/ack/GR. D11 update/delete/cancel guard; historical one PR->one PO not imported current implementation proof.                                          |
| purchase_order_items                 | Existing ordered/received/source line                | PK id; PO F!, PRitem F?, item F!, ordered/received Q!, unit/status; historical item_supplier F?+execution snapshots only historical. IX(parent FKs).                                                                                                                                                                                              | Actual receipt/order caps vs policy, unit/source/owner guards; sum commitment allocations, no automatic overwritten supplier/history. Legacy nullable trace retention.                         |
| goods_receipts                       | Existing actual receiving header                     | PK id; receipt_number U, PO F?, receiver F?, received_at!, draft/posted state, softdelete. IX(state/time).                                                                                                                                                                                                                                        | Draft not supply; posted identity immutable, one post transaction, no silent status or softdelete as correction.                                                                               |
| goods_receipt_items                  | Existing receipt qty/item/location                   | PK id; GR F!, POitem F?, item F!, batch F?, location F!, quantity Q!; no SoftDeletes; IX FKs.                                                                                                                                                                                                                                                     | PO/PR/source/item/unit/qty distribution guard; receipt assigned portions<=actual q, unassigned remainder real inventory not fictive requirement.                                               |
| stock_reservations                   | Shared physical/workflow/obligation projection       | PK id; owner XOR, purpose/scope/serial/active keys and partial counters WP1/WP3; U(active serial),U(active replacement order), root/previous FK/IX.                                                                                                                                                                                               | Remaining>=0, consumed/released nonnegative sum<=reserved; no active deletion, no full-consume shortcut or company SUM for custody workflow.                                                   |
| stock_movements / stock_balances     | Sole stock ledger + mutable company projection       | Movement PK id, typed owner/command/serial/source FKs+occurrence U; balances PK id, U(item,location,batch_identity) proposed; Q exact, FK/IX typed.                                                                                                                                                                                               | WP4 MI0/MA separate; quarantine not E; only real movement explains qty, no procurement planning increase. Existing nullable-batch audit required.                                              |
| HISTORICAL proposal/source/pegs      | Branch-specific artifacts, not new current tables    | supply_proposals PK and status/source fields; purchase_requisition_item_proposal_sources PK, PRitem F!, proposal F! UNIQUE, q decimal(18,3); material_requirement_pegs separate planning trace.                                                                                                                                                   | Proposal full-consumption U distinct from req multi-source; pegs not reservations/coverage; precision/lineage/soft-delete compatibility unverified current.                                    |

### 9.2. Konkrét kulcs- és alakjavaslatok

- `repair_diagnostic_fees`: U(order,version), U(supersedes_fee_id), U(command_id), U(id,order).
  Fee>=0, version>=1, scale0..6; parent correction ugyanazon order, no-cycle/monoton guard.
- `repair_quotations`: U(order,version), U(command_id), U(id,order); submitted tartalom immutable.
  Approved/rejected esetén response actor/customer/document/time kötelező. Cancelled technikai
  state/history-kiegészítés PROPOSED, nem már meglévő enum. Total>=0 és exact line sum guard.
- Allocation: U(active_source_order_id), U(quotation_item_id), U(command_id); composite fee/order,
  quote/order és quote-line/source-order FK. Released branch NULL activekey + release proof;
  reserved/retained branch activekey=source, not NULL; minden NULL ág explicit.
- History usage/correction: typed allocation_id FK és original-history/supersedes FK, exact optional
  amount/currency/scale csak hiteles usage observationnél; settlement-review/execution-review doc,
  reviewer/time és status. Nem minden history-sor pénzügyi esemény, NULL-shape típusfüggő.
- Source: existing U(PRitem,requirement) megmarad; **nincs U(requirement,generation)**.
  Creation-line kulcs-javaslat U(requirement,creation_generation,source_line_key), valamint
  generation_key U a teljes line occurrence-re. G-head FK explicit immutable generation identity.
- G-head és coverage ugyanabban az event táblában: `procurement_generation` U32?,
  `generation_singleton_key` nullable tiny integer; csak generation_authorizednál=1 és generation>=1,
  más eventen NULL. U(requirement,procurement_generation,generation_singleton_key) kizárólag
  a **generation head** egyszeriségét védi, nem tiltja annak több source-line-ját.
- Generation-line eventek: `generation_head_event_id` nullable self-FK, `source_line_key` nullable
  canonical S(128), `generation_allocation_slot` nullable tiny integer. Az authorized G adott
  line-jának egyszeri initial incoming allocationjánál mind kötelező, slot=1;
  U(generation_head_event_id,source_line_key,generation_allocation_slot) védi a retryt.
  Head/review/receipt/correction nem foglalja el ezt a singleton-slotot. Reassign-in actionnél
  a to-side az incoming mennyiség, nem új purchase: type/side budget mapping WP2-ben explicit.
- Coverage U(idempotency_key,event_sequence), U(supersedes_event_id), U(transfer_key,transfer_side),
  source/G/POitem/GRitem/request-owner typed FK és query index; transfer from/to pár két sor,
  nem körkörös paired FK. Receipt root/GRitem/owner occurrence és q cap guard véd dupla receipt ellen.
- Optional `responsible_user_id` FK, `responsibility_domain` és handoff evidence csak handoff-typenál
  kötelező; pontos recipient identity/business-role mátrix review-kapu. Legacy procurement command
  nem köteles fiktív repair_commands vagy RMA owner alá kerülni.

Ezek declarative review-javaslatok, nem executable SQL. A type→nullable/FK/quantity határokat
a 6.3 táblázat és current/historical profile együtt határozza meg; a teljes engine DDL WP2 feladata.
CHECK nem tud foreign fee/quote összeget, source totalitást, párkapcsolati mennyiséget vagy permissiont
ellenőrizni. MySQL nincs deferred FK; CHECK és FK-action kompatibilitás, auto-ID operandus, NULL UNKNOWN,
unsigned/decimal/collation/index-prefix és raw write tilalom külön proof. SQLite affinity/ALTER/rebuild
és DB-principal/routine eltérés nem bizonyít MySQL production integritást.

### 9.3. REV-01 insert protokollok és immutable history

**Credit:** command → immutable verified fee (korábbi vagy új verzió) → quotation+lines →
approval guard alatt allocation → typed history FK→allocation → mutable quote state/allocation
latest_review_history pointer → command complete+audit → commit. History nem mutat előre még
nem létező allocationra, allocation kezdeti review-pointere nullable. Releasekor új review history
már létező allocationt referál, utána state/pointer atomikusan frissül. Fee/line/korábbi event nem UPDATE.
Ha külön approval-history ID kell az allocationon, azt előbb kell létrehozni meglévő quote/fee-re
mutatva vagy inverse relationnel kezelni; nem két immutable sor egymásra előre mutatásával.

**Demand/coverage:** stable requirement első insert current_revision_event=NULL → revision/G-event FK→requirement
→ source FK→G → allocation event FK→source/G → mutable requirement head/current_generation pointer update
→ history/audit/commit. A parent-pointer ciklus projection, nem immutable event backfill.
**Reassignment:** két külön immutable event közös transfer_key/from/to; mindkét side meglévő rootra és
célanchorra mutat, nincs utólagos pair UPDATE. **Receipt:** actual GR/POitem és lineage ismert →
supply_received event és physical movement/counter/projection egy transaction, nem future GRitem FK.

Az FK csak child→parent létezést, U csak legfeljebb egyet biztosít. Event/proof/line/budget teljes
commitoltságát restricted DML+szűk posting/finalizer guard és negatív SQL próbák kell bizonyítsák.
Runtime orphan generation/quote-approval/allocation/coverage vagy child-delete nem maradhat nyitott út.
Nem használunk sessionflag bypass-t, FK-disable-t vagy az immutable history utólagos kitöltését.

## 10. Evidence-based closure

Kizárólag a C1 restricted finalizer zár; WP5 nem ad külön close API-t, nem töröl önállóan claimet.
WP5 typed proofokat szolgáltat a case mutex alatt újraellenőrzött teljes member/obligation halmazhoz.
Több termék ügye nem zárható egyetlen order jóváhagyása vagy kifizetése alapján.

| Close proof             | Kötelező jelentés / blokkoló                                                                                                                                                                                                                |
| ----------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Quote és execution      | Releváns approved/rejected/cancelled/superseded scope és actual execution összhangja, F4; customer response proof, nem csak enum                                                                                                            |
| Final fee               | Alkalmazandó végleges díjmegállapítás/bizonyíték és responsibility. Pontos charge/invoice/payment-close policy OPEN; unknown nem „nincs tartozás”                                                                                           |
| Diagnostic credit       | Verified fee/order/quote/line/allocation és execution/settlement review egyező; reserved/retained/unknown/correction residual nem tüntethető el státusszal                                                                                  |
| Settlement              | Verified observation és külső felelős/proof; settled nem általános paid-balance, unsettled nem automatikus close-tilalom minden lehetséges policyban. Konkrét closure döntés OPEN marad, nem találunk ki „csak teljesen kifizetve” szabályt |
| Material demand         | Hatályos need teljesített actual C-vel, vagy dokumentáltan withdrawn/revised a valós disposition szerint; PO/GR/reserve nem fulfilled repair önmagában                                                                                      |
| Reservations            | Nincs elrejtett aktív physical remaining vagy unresolved logical obligation; released/consumed counter és actual stock összhang                                                                                                             |
| PR/PO/partial GR        | Source allocation, sent/ack supplier obligation, receipt és residual qty külön egyeztetett; PO close received nem actual receipt                                                                                                            |
| Procurement handoff     | D11 szerinti fennmaradó supplier responsibility más, megnevezett domainhez explicit proof+qty/status assignmenttel átadott; nem fake cancel vagy törlés                                                                                     |
| Pending cancel/reassign | Unacknowledged cancellation, hiányzó transfer side, unmatched source/receipt/budget vagy unassigned responsibility close-blocker                                                                                                            |
| Physical/ownership      | WP4 original outcome, minden shipment/return/quarantine/legal event rendezett; pénzügyi proof nem válthatja ki                                                                                                                              |

Nyitott pénzügyi policy esetén a finalizernek az érintett close-út nem engedhető puszta
`financial_ok=true` flaggel. Projektgazdai specifikáció és typed evidence kell; nem jelenti azt,
hogy D6/D7/D11 újra elfogadásra vár. D11 explicit procurement handoffja lehetővé teszi a megfelelően
rendezett RMA lezárását fennmaradó supplier commitment mellett; financial handoff általános joga nem ebből következik.

## 11. Verification matrix

Minden eset **PLANNED / NOT EXECUTED**. Money példa integerunits, fee10000 ugyanazon currency/scale;
quantity példa ugyanazon item base-unit exact decimal. Eltérő fixture/currency/owner külön állapot.
DB state, pénz/qty és audit mind külön postcondition; rollbackelt audit nem nevezhető tartós sikernek.
Current és historical profile tesztjeit külön kell futtatni későbbi felhatalmazással.

| Test / status                                                                | Setup                                                                            | Action                                          | Expected database state                                                                                      | Expected financial / quantity result                           | Expected audit evidence                                           |
| ---------------------------------------------------------------------------- | -------------------------------------------------------------------------------- | ----------------------------------------------- | ------------------------------------------------------------------------------------------------------------ | -------------------------------------------------------------- | ----------------------------------------------------------------- |
| T01 **PLANNED / NOT EXECUTED** – Fee approval                                | Verified fee10000, submitted credit6000 line es valid customer proof             | Approve                                         | Approved quote + egy reserved allocation es active order-key                                                 | a6000, invoice/payment nem kovetkezik                          | Approval actor/owner/doc/hash/fee/order/allocation audit          |
| T02 **PLANNED / NOT EXECUTED** – Fee revision                                | Submitted/approved quote v1 es uj scope                                          | Create v2                                       | Uj draft, v1/approval/history megmarad; regi scope nem jogosít uj munkara                                    | Nincs uj fee vagy credit reset                                 | Revision reason/version/scope audit                               |
| T03 **PLANNED / NOT EXECUTED** – Approval cancellation                       | Reserved6000 es execution/settlement unknown                                     | Cancel quote                                    | Cancellation history+retained/blocked projection; active key marad                                           | Nem release/refund/reuse                                       | Execution/settlement review hiany es cancel evidence              |
| T04 **PLANNED / NOT EXECUTED** – Duplicate approval retry                    | T01 commitolt, same key/hash/actor                                               | Replay approval                                 | Ugyanaz canonical result, nincs masodik allocation/response                                                  | a6000 egyszer                                                  | Egy successful domain audit es retry trace                        |
| T05 **PLANNED / NOT EXECUTED** – Credit source creation                      | Diagnostic consent+calculation+evidence                                          | Verify fee10000                                 | Immutable fee v1 U(order,version), nem quotationbol feltetelezett amount                                     | f10000 cap candidate, nem payment                              | Verified actor/time/source documents audit                        |
| T06 **PLANNED / NOT EXECUTED** – Partial allocation                          | Fee10000, approved line -6000                                                    | Allocate es uj quote -4000 attempt              | Egy effective source claim, uj quote conflict amig active                                                    | a6000; fennmarado4000 nem parhuzamos wallet                    | Partial amount proof es second-approval conflict                  |
| T07 **PLANNED / NOT EXECUTED** – Duplicate credit allocation                 | Ket quote ugyanazon source orderre                                               | Concurrent approve                              | Active-order U/guard miatt pont egy allocation                                                               | Nem a1+a2 cap bypass                                           | Nyertes es loser eredmeny, nincs partial successful approval      |
| T08 **PLANNED / NOT EXECUTED** – Credit reversal proof                       | Verified settled usage6000 es original proof                                     | Record authorized correction observation        | Uj causal history; original immutable; active claim nem automatikusan NULL                                   | Reversal<=original, nincs app-refund vagy uj credit            | Original proof/reason/authorized actor/correction audit           |
| T09 **PLANNED / NOT EXECUTED** – Credit over-allocation                      | Eligible fee10000, credit11000                                                   | Approve                                         | Nincs approved quote/allocation partial commit                                                               | Cap violation, effect0                                         | Safe cap refusal; nincs successful allocation audit               |
| T10 **PLANNED / NOT EXECUTED** – Credit settlement                           | Reserved6000 es verified settled external proof                                  | Review es cancel/release attempt                | Settled history append-only, retained/blocked; release refused                                               | Settlement nem quote approval vagy paid-balance engine         | Reviewer/time/doc/allocation audit                                |
| T11 **PLANNED / NOT EXECUTED** – Material requirement creation               | Repair scope itemA10 es valos owner/order                                        | Create aggregate requirement                    | Stable repair-order/item owner row es revision event                                                         | D10, nem fake customer/production order                        | Scope/owner/unit/revision audit                                   |
| T12 **PLANNED / NOT EXECUTED** – Requirement reduction                       | D10, C2, own remainingR8; new scopeD6                                            | Revise es authorized unused release4            | Revision history+reservation release counter4; consumed2 megmarad                                            | N4, remainingR4, nincs uj physical qty                         | Scope/revision/oldnew quantities es release proof                 |
| T13 **PLANNED / NOT EXECUTED** – Reservation reconciliation                  | Reserved10, consume3, release2                                                   | Apply partial commands                          | Consumed3/released2; remaining5, history/move atomikus                                                       | Physical csak -3; nem entire Consumed                          | Material/movement/reservation command audit                       |
| T14 **PLANNED / NOT EXECUTED** – Procurement shortage                        | N10, ownR3 es freeassigned2, internalI2 es firmK1                                | Net es generate delta                           | Ugyanaz a free2 egyszer allokalt; sourceG csak uncovered2                                                    | Newdelta2; I/K nem physical availability                       | Owner/netting/allocation/procurement decision trace               |
| T15 **PLANNED / NOT EXECUTED** – PR generation                               | Authorized uncovered10 es Gline terv4+6                                          | Create G/source/PR                              | Egy G head, lines/sources/allocated events es budget proof commit                                            | Total10, nem head+source duplicate20                           | G/line keys/req revision/actor audit                              |
| T16 **PLANNED / NOT EXECUTED** – PR consolidation                            | Ket requirement owner qty4 es6 same item/unit                                    | Current consolidate                             | Egy PRitem10, ket requirement source; direct pointer nem teljes ownerlista                                   | 4+6=10                                                         | Batch es source/owner quantity audit                              |
| T17 **PLANNED / NOT EXECUTED** – Multiple sources per generation             | Egy requirement G1 budget10, lineA4/B6                                           | Split into ket PRitem/source                    | Ket source-line engedett; Ghead csak1; nincs U(req,gen) a source-on                                          | Conserved10, mindket canonical line trace                      | OWNER-APPROVED decision/G/source-line audit                       |
| T18 **PLANNED / NOT EXECUTED** – PO generation                               | Eligible approved PR; current/historical profile kulon                           | Generate PO twice                               | Fresh guard; historical readiness/PR supplier es U(PR) proof; current gap kulon jelzett                      | Egy authorized execution, nem duplicate commitment             | PO/PR/supplier/source snapshot es duplicate refusal               |
| T19 **PLANNED / NOT EXECUTED** – PO cancellation                             | Sent/ack open10, RMA demand withdrawn                                            | Cancel request without/with supplier proof      | Demand withdrawal megmarad, commitment proof nelkul nem torolt; release/transfer explicit                    | Supplier residual10 amig jogszeru cancel nincs                 | Cancellation/remaining-responsibility audit                       |
| T20 **PLANNED / NOT EXECUTED** – Partial GR                                  | Assigned open10, actual GR4                                                      | Post es netting                                 | Stock movement+received counter+coverage event egy commit                                                    | Open6, G4, Ge<=4; nem open10+physical4                         | GRitem/source/owner quantity/audit                                |
| T21 **PLANNED / NOT EXECUTED** – Supplier substitution                       | Sent PO supplierA, request supplierB                                             | Legacy update bypass/correction workflow        | Silent history rewrite tiltott; authorized policy/proof nelkul blocked                                       | Qty nem duplazott, actual GR nem atirt                         | Old/new supplier/proof/commitment decision audit                  |
| T22 **PLANNED / NOT EXECUTED** – Duplicate source occurrence                 | Existing G/line source event                                                     | Same line uj command/hash mismatch              | Canonical duplicate refused vagy same-hash replay; nincs uj G/source                                         | Delta egyszer                                                  | Source-line business key es hash conflict evidence                |
| T23 **PLANNED / NOT EXECUTED** – Regeneration                                | Same demand, authorized coverage release2 utan uncovered2                        | Generate next delta                             | NewG valodi authorized2, demand revision nem retry miatt nott                                                | Nem uj teljes10, csak2                                         | Release->newG provenance es reason audit                          |
| T24 **PLANNED / NOT EXECUTED** – Quarantine exclusion                        | Posted G4, company quarantine, custody/pending other qty                         | Availability/netting/reserve/consume            | Nem eligible supply, nincs automatic generation review nelkul                                                | Ge0 az adottG-bol, nem physical free4                          | Disposition/predicate/blocked-review proof                        |
| T25 **PLANNED / NOT EXECUTED** – Actual material consumption                 | Eligible own reserve10, used3                                                    | Consume3                                        | Repair material+movement+partial counter; owner/unit/serial helyes                                           | C3, remainingR7, physical-3                                    | Actual task/order/item/batch/qty audit                            |
| T26 **PLANNED / NOT EXECUTED** – Financial closure blocking                  | Quote complete, fee/credit/settlement policy/proof hianyzik                      | C1 case close                                   | Refused, active claims es case megmarad                                                                      | Unknown nem 0/paid; nincs financial_ok bypass                  | Missing proof/obligation azonosito, nincs successful close        |
| T27 **PLANNED / NOT EXECUTED** – Procurement closure blocking                | Pending sent commitment/source qty es nincs named handoff                        | Case close                                      | C1 finalizer refuses; supplier/source history megmarad                                                       | Residual obligation nem torolt                                 | Close refusal + outstanding source/PO/qty evidence                |
| T28 **PLANNED / NOT EXECUTED** – Concurrent financial changes                | Same quote/fee, approve vs revise/cancel                                         | Ket connection                                  | Order lock/current version es active U szerint egy soros outcome                                             | Nincs approval rossz scope-ra vagy ket credit                  | Winner/loser/hash/scope/audit rollback trace                      |
| T29 **PLANNED / NOT EXECUTED** – Concurrent procurement changes              | Same uncovered10, ket generator/PO/cancel caller                                 | Ket connection                                  | Ghead/line source U + item/owner lock; whole transaction winner                                              | Nem20 coverage, nem duplicate PO                               | One execution es conflicting generation/cancel evidence           |
| T30 **PLANNED / NOT EXECUTED** – Raw SQL integrity bypass                    | Runtime principal es protected fee/event/allocation/G                            | Orphan insert, UPDATE/DELETE/child-delete       | Restricted DML/proof totality blocks; nem service-only guarantee                                             | Nincs unauthorized money/qty effect                            | DB grant/trigger/routine negative evidence es domain proof        |
| T31 **PLANNED / NOT EXECUTED** – Audit rollback                              | Approval+allocation vagy G/source/GR kozepe audit failure                        | Failure injection                               | Minden uj domain/projection/history/command write rollback                                                   | Original amounts/qty unchanged                                 | Nincs megmaradt successful audit; safe failure evidence           |
| T32 **PLANNED / NOT EXECUTED** – Cache consistency                           | Forecast/report elomelegitve, posting vagy coverage change                       | Commit/outer rollback/cache failure             | Commit utan erintett domains rotate, rollback nem; cache fail nem duplicate posting                          | Committed balances/coverage/remaining egyezo                   | Generation/scope/transaction and failure trace                    |
| T33 **PLANNED / NOT EXECUTED** – Named responsibility handoff                | Withdrawn repairneed, supplier commitment10 tovabb el                            | Authorized procurement handoff                  | Typed release/transfer+named domain/recipient/proof; actual PO nem cancel                                    | Unassigned/responsible residual10, nem fictive newrequirement  | Sender/recipient-assignment basis/qty/status/source audit         |
| T34 **PLANNED / NOT EXECUTED** – Unauthorized credit expiration              | Active credit vagy expired quote, nincs expiry policy                            | Expire/reuse credit attempt                     | Blocked, old allocation/usage history remains                                                                | Nincs automatikus cap/claim-felszabaditas                      | No-policy refusal es original allocation proof                    |
| T35 **PLANNED / NOT EXECUTED** – Currency/scale/precision                    | Fee es line mas currency/scale vagy overflow                                     | Approve/netting                                 | Mismatched/overflow atomic failure, nincs implicit FX/float                                                  | Exactunits vagy no effect                                      | Validation unit/currency/scale/overflow evidence                  |
| T36 **PLANNED / NOT EXECUTED** – FK-cycle creation                           | WP2 fee/quote/allocation/history and req/G/source DDL                            | Legitim insert es rollback                      | Protokoll immutable UPDATE nelkul; mutable head late-set csak ugyanabban txn                                 | Pont egy effect, nincs orphan/partial head                     | Engine/protocol/raw-negative evidence                             |
| T37 **PLANNED / NOT EXECUTED** – Generation budget excess                    | Gbudget10, lineqty4+7                                                            | Atomic split create                             | Budget guard rejects wholeG/source/allocation txn                                                            | 0 commit, nem11                                                | Budget/source line quantities es failure evidence                 |
| T38 **PLANNED / NOT EXECUTED** – Historical Proposal consumption             | Historical approved Proposal10 es req multi-source jovahagyas                    | Proposal partial4 majd6 attempt                 | V1 U(proposal) es teljes-consumption policy nem lazult; separate approved proposals csak explicit adapterrel | Nem partial Proposal consumption enabled                       | Proposal/req distinction es compatibility test evidence           |
| T39 **PLANNED / NOT EXECUTED** – Replenishment excess                        | Historical planned/source10, MOQ requested15                                     | PO/coverage allocation review                   | Source/demand nem nott15-re; excess5 procurement/inventory residual responsibility                           | 10 assigned need,5 excess, ordered15                           | Planned/requested/excess snapshot/source audit                    |
| T40 **PLANNED / NOT EXECUTED** – Receipt owner distribution                  | GR6, ket owner source4+6                                                         | Allocate receipt4+3 vagy approved distribution  | Over-allocation7>6 refused; valid assigned portions es unassigned residual osszege6                          | Open/physical konverzio egyszer, nem source.quantity ujra      | GRitem/source/owner portions/current roots audit                  |
| T41 **PLANNED / NOT EXECUTED** – Payment vs approval                         | Payment proof de rejected quote; masik approved quote no payment                 | Start/close review                              | Payment nem approval, approved nem paid; close policy OPEN-hoz blocked ahol hianyzik                         | Nincs automatikus charge/pay/credit conversion                 | Customer scope es separate settlement evidence                    |
| T42 **PLANNED / NOT EXECUTED** – Fee downward correction                     | Oldfee10000 es verified-used6000, corrected fee5000                              | Verify correction es uj allocation attempt      | Old fee/usage immutable, conflict retained/blocked, nincs new active loophole                                | Nem usage0 vagy uj cap5000 ujrahasznalhato                     | Version/supersedes/conflict/authorized-review audit               |
| T43 **PLANNED / NOT EXECUTED** – Cross-owner transfer attempt                | Credit source/orderA es source demandA, callerB                                  | Allocate creditB vagy rewrite requirement owner | Credit unrelated blocked; requirement owner nem atirt, procurement reassign csak paired explicitD11          | Nincs unauthorized credit/case transfer vagy q creation        | From/to owner/permission/source refusal proof                     |
| T44 **PLANNED / NOT EXECUTED** – Handoff vs close race                       | Current residual obligation, ket caller handoff es close                         | Ket connection C1 case lock                     | Close vagy valid committed handoff utan vagy refusal; nincs koztes missing responsibility                    | Qty/obligation nem veszik el; claim csak finalizer closekor    | Atomic handoff/current-version/full-close audit                   |
| T45 **PLANNED / NOT EXECUTED** – Historical identity/precision compatibility | Current customer-item req vs historical PO+BOM req, PR18/PO vagy GR12 qty domain | Adapter es boundary validation                  | Nincs silent owner-FK remap vagy quantity truncation; incompatible profile blocked                           | Exact lineages es supported range, nem overflow/kettes fedezet | Pinned branch/path/schema-profile es migration-readiness evidence |

Létező current tesztforrás: [ProcurementManagementUiTest](../../tests/Feature/ProcurementManagementUiTest.php),
[InventoryManagementUiTest](../../tests/Feature/InventoryManagementUiTest.php),
[ProductionExecutionUiTest](../../tests/Feature/ProductionExecutionUiTest.php),
[BusinessCacheInvalidationTest](../../tests/Feature/BusinessCacheInvalidationTest.php).
Historical test-objectek: `tests/Feature/SupplyProposalTest.php`,
`PurchaseRequisitionConsolidationTest.php`, `SupplierSelectionTest.php`,
`PurchaseRequisitionReplenishmentTest.php`, `PurchaseRequisitionExecutionReadinessTest.php`,
`PurchaseOrderGenerationTest.php`, `PurchaseOrderDispatchAcknowledgementTest.php` ugyanazon pinned commiton.
Forráslétezés és assertok olvasása nem tesztfutás, és nem RMA-profile sikerbizonyíték.

## 12. Findings and dependencies

**3 BLOCKER / 7 HIGH / 1 MEDIUM / 1 LOW; mind OPEN.**

| ID / severity       | Evidence                                                                                                                                | Risk                                                                                                   | Proposed resolution                                                                                               | Responsible package                            | Acceptance criterion                                                                           |
| ------------------- | --------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------ | ----------------------------------------------------------------------------------------------------------------- | ---------------------------------------------- | ---------------------------------------------------------------------------------------------- |
| WP5-F01 **BLOCKER** | WP1 credit/latest_review es history FK, C1 proof/finalizer kapuk; 9.3 insert terv.                                                      | Orphan approval/allocation, immutable event overwrite vagy raw release/cap bypass.                     | Cycle-safe insert+typed proof+restricted runtime DML es raw SQL proof, nem csak service flag.                     | WP2/WP7                                        | T01-T10/T30/T31/T36 es two-principal tests; nincs orphan vagy history UPDATE.                  |
| WP5-F02 **BLOCKER** | Current source-existence gate, no coverage ledger; 6.3 mennyisegi fold es actual GR.                                                    | Duplicate coverage, received kettos szamolas, source+ledger duplazas vagy incomplete transfer.         | Exact G/source/commitment/receipt owner conservation es totality; shared E/R/P/M rollout.                         | WP2/WP3/WP6/WP7                                | T13-T25/T29/T37/T40; every writer/reader ugyanazt a meaninget alkalmazza.                      |
| WP5-F03 **BLOCKER** | Historical379be48 ADR0008.5/0009 PO+BOM requirement, current customer-item owner; Proposal-source/precision/supplier/readiness eltérés. | Masik ág implementaciojanak hamis current tekintese, lineage/owner torzulese vagy quantity truncation. | Pinned target-baseline/adoption es typed RMA/procurement adapter review; nem silent merge/ADR006 atiras.          | Projektgazdai integration review + WP2/WP6/WP7 | T18/T38/T39/T45; current es historical profile kulon, precision end-to-end bizonyitott.        |
| WP5-F04 **HIGH**    | OWNER-APPROVED WP5-REV02-01; current U(PRitem,req), historical U(proposal); source G uniqueness korabbi javaslat.                       | U(req,gen) tiltana allowed splitet; unique gyengites budget/line identity nelkul dupla delta.          | Source line-key/Ghead singleton kulon U es immutable budget; historical Proposal policy nem lazul.                | WP2/WP7                                        | T15-T17/T22/T23/T29/T37/T38; cardinality accepted, DDL/konkurencia meg nem bizonyitott.        |
| WP5-F05 **HIGH**    | D6/D7/AM03 es fee/quote/settlement fogalmak; invoicing/payment/refund/partial-used-release policy nincs accepted.                       | Approval=payment, settled=invoice, partial residual vagy correction jogosulatlan ujrahasznalasa/close. | OPEN reszletszabalyok owner review-ra; unknown/retained fail-closed, nincs uj accounting subsystem.               | Projektgazdai penzugyi pontositas + WP2/WP7    | T03/T08/T10/T26/T34/T35/T41/T42; evidence es scope kulon, nincs feltetelezett policy.          |
| WP5-F06 **HIGH**    | MaterialRequirementRepository customer-item upsert es global physical/full reserve, WP3 consumer findings.                              | Multi-PO overwrite, idegen owner freeqty duplazas, quarantine supplier/repair supplykent.              | Stable owner revision+actual C es once-allocation; I/K internal vs firm vs Ge fold.                               | WP3/WP2/WP6/WP7                                | T11-T14/T20/T24/T25/T43; no phantom supply/consumption.                                        |
| WP5-F07 **HIGH**    | Current PO close/status/update/delete es GR post lineage; historical sent/ack source masik agon.                                        | Supplier obligation silent cancellation/substitution, PO receivedbol fake supply; lock inversion.      | D11 proof/cancel/reassign, current fresh locks es shared L; actual GR/coverage atomic.                            | WP2/WP6/WP7                                    | T18-T21/T28/T29/T40/T44; full rollback es same-key bounded retry.                              |
| WP5-F08 **HIGH**    | C1 case-close es D11 named responsibility handoff; current nincs ilyen ledger.                                                          | Unpaid/unsettled vagy procurement residual eltunik status/close flagtol.                               | Typed fee/credit/requirement/commitment handoff proof, C1 finalizer kizárólag; financial close policy OPEN.       | Projektgazdai review + WP2/WP7                 | T26/T27/T33/T41/T44; nincs fuggetlen WP5 close vagy gazdatlan supplier obligation.             |
| WP5-F09 **HIGH**    | Source CASCADE, soft-delete, document morph/verzio es AuditLogService raw payload nem typed FK.                                         | Korabbi credit/coverage/proof torles/nullazas, unauthorized transfer, audit off-txn.                   | RESTRICT/append-only DML es fileversion retention; typed source/owner/review es audit kozos transaction.          | WP2/WP6/WP7                                    | T30/T31/T36/T43; hard/soft/bulk/raw/storage negative proof.                                    |
| WP5-F10 **HIGH**    | Current Reporting/Intelligence source queries es BusinessCacheInvalidator inventory/procurement kulon domainlista.                      | Stale shortage/coverage/received, permission-filtered cache leak vagy commit utani retry dup effect.   | WP3 C explicit source/projection/domain matrix; afterCommit, no business rollback cachefailtol.                   | WP3/WP2/WP7                                    | T20/T24/T32/T40; current/HISTORICAL/adopted scopes egyeznek, no global flush workaround.       |
| WP5-F11 **MEDIUM**  | Current float money/qty nincsen RMA-ban, qty arithmeticban van; current12,3/historical18,3 es numeric keys.                             | Rounding/overflow/case-insensitive key vagy nyers count+1 collision.                                   | Canonical exactunits/baseunit/binary occurrence es checked range, stable command+line keys; policy rounding OPEN. | WP2/WP6/WP7                                    | T04/T22/T35/T45; boundary values/collation/SQLite eltérés tesztelve.                           |
| WP5-F12 **LOW**     | Historical ADR0016 header implementációra vár, miközben service/model/migráció található; ADR006 szam utkozik.                          | Forras/declared status mas scope-jat current acceptancenek vagy runtime successnek olvassak.           | Branch+commit+fullpath es CONFIRMED CURRENT/HISTORICAL/MISSING/UNVERIFIED cimke mindig megmaradjon.               | Projektgazdai review/WP7                       | Minden hivatkozas provenance-ja egyertelmu; nincs branchvaltás/import vagy tesztsiker allitas. |

### 12.1. Traceability

| Autoritás / előzmény       | WP5 megőrzött követelmény / függőség                                                                                            |
| -------------------------- | ------------------------------------------------------------------------------------------------------------------------------- |
| RMA-BD-001/003/004/011     | Case owner/members/orders és actor/reporter külön; aktív claim és teljes proof-only close C1 finalizer                          |
| RMA-BD-002/005/012/015     | Saját gyártás/serial/origin, manuális warranty/proof/history; fee/receipt nem warranty reset vagy eligibility proof             |
| RMA-BD-006/009/010/013/014 | Fizetős execution approval, scope/retry/QC/time/material/quote/diagnostic fee és credit külön tények                            |
| RMA-BD-007/016/017/018     | Customer custody/return nem normál supply; E/R/P/M eligible anyag/demand/coverage és actual use                                 |
| RMA-BD-008/019/020         | Original disposition, replacement physical/ownership/obligation és minden proof külön; fee/procurement status nem helyettesíti  |
| D1/D2/D3                   | Három scope, igazolt own manufacture és egy owner/case; reporter nem automatikus pénzügyi meghatalmazott                        |
| D4/D5                      | Nincs automatikus garanciakezdet/owner change receiptből, paymentből vagy PO-ból                                                |
| D6/D7                      | Approval-only, egy effective source-order credit; cancel nem release, execution/settlement proof és authorized correction       |
| D8/D9/D10                  | Mandatory return quarantine, authorized új shipment, külön legal reversal; return nem supplier GR vagy financial reversal       |
| D11                        | Demand/intent/coverage/commitment/actual supply/handoff külön, nincs silent cancel/dupla procurement                            |
| AM-01/AM-02/AM-03/AM-04    | Shipment/obligation külön, return intake/admission külön, verified fee és causal credit/ownership/coverage history              |
| AM-02-G1                   | Verified intake proof, nem payment/PO evidence                                                                                  |
| AM-02-G2                   | Intake company0; company admission csak igazolt jogcímmel, nem procurement statusból                                            |
| AM-02-G3                   | Quarantine/custody/pending nem physical eligible repair/production/procurement supply                                           |
| AM-02-G4                   | Intake/admission identity külön; GR→coverage conversion sem számolhat received supplyt kétszer                                  |
| F1/F2/F3/F4                | Company reservation/preparation vs shipment/ownership; proof-only close; original–replacement serialpár; új scope approval-gate |
| C1 G1/G2/G3                | Restricted finalizer/raw DML; minden consumer rollout; return/quantity/logical obligation lifecycle                             |
| WP1-F01–F10                | Claim/consumer/legacy/return/retention/fee-credit/coverage/key/cycle/history nyitott kapuk, különösen F06/F07                   |
| WP3-F01–F15                | Remaining/eligible/owner/partial use/demand/source/commitment/GR/cache/runtime közös szerződések, nem külön RMA ledger          |
| WP4-F01–F10                | Intake/admission/ownership/proof/totality/legacy/consumer/lock/unmatched kapuk, settlement nem befogadás                        |
| REV-01                     | Credit/history és requirement/coverage FK-ciklusok: immutable backfill helyett explicit insert order és restricted DML          |
| REV-02                     | Current/historical elemzés + OWNER-APPROVED WP5-REV02-01; multi-source generation, head vs line U elkülönítés                   |
| REV-03                     | Reserved/consumed/released remaining, nincs logical/custody quantity dupla levonás                                              |
| REV-04                     | Global employee-overlap policy nem fee/credit/procurement döntés, későbbi execution/WP7 függőség                                |
| REV-05                     | WP4 typed intake/admission javaslatot nem zárjuk le vagy írjuk át; supplier receipt és RMA admission külön                      |

## 13. WP2/WP6/WP7 handoff

**WP2:** csak külön későbbi feladatban. Current/historical célbaseline és lineage/precision egyeztetés,
generation-head vs source-line U/FK, fee/quote/allocation/history nullable/composite és explicit CHECK,
restricted DML/proof totality, exact arithmetic/overflow és MySQL/SQLite eltérés, L lock sorrend.
Az owner által engedélyezett multi-line cardinality nem futtatott SQL-bizonyíték.

**WP6:** read-only legacy audit terve: owner/revision/source/PR/PO/GR/batch/proof/currency és consumed
eltérések; source snapshot és effective ledger baseline egyszeri. Historical artifactok nem current
adatok; nincs silent backfill vagy branch merge. Deployment/feature gate, old/new reader-writer
compatibility, retention/filechecksum, backup/rollback, runtime/grant/BI/job inventory.

**WP7:** az alábbi planned matrix actual motor/service/policy/raw SQL/concurrency/audit/cache futtatása
később; külön current és historical-compatibility profile, külön tervezett/futtatott eredmények.
Financial policy OPEN ágai nem „passed” tesztek: az elvárt viselkedés addig explicit blocked/unknown.

Projektgazdai review szükséges: final-charge/rounding/tax/payment/refund/credit-use correction/expiration
és financial closure részletek; allocation priority/overreceipt/substitution; historical procurement
baseline/adoption. Multi-line generation alapjoga már jóváhagyott, nem kérjük újra.
E dokumentum után WP2/WP6/WP7 nem indul automatikusan.

## 14. Acceptance checklist

| Tétel                      | WP5 állapot / bizonyítékhatár                                                                                               |
| -------------------------- | --------------------------------------------------------------------------------------------------------------------------- |
| Git baseline és scope      | Branch/HEAD, három meglévő untracked doc/hash, empty staging/tracked diff; csak új WP5                                      |
| Evidence-first procurement | CONFIRMED CURRENT / HISTORICAL / MISSING / UNVERIFIED ág+commit+út szerint; nincs automatikus historical import             |
| Fee/credit                 | F01–F12, estimate/quote/approval/final-charge/payment/balance/credit külön; D6/D7, partial amount vs több active allocation |
| Material/coverage          | D/R/I/K/G/Ge/C/N külön; exact quantity, one-time free allocation, received nem open supply vagy consumption                 |
| REV-02                     | Owner-approved multiple source-lines; current/historical cardinality és generation-head/source-line constraints elkülönítve |
| Handoff/close              | Named responsibility, megmaradt supplier obligation/history; kizárólag C1 restricted finalizer, financial policy OPEN       |
| Concurrency/data model     | 14 scenario, PK/FK/U/CHECK/null/delete/index/precision/cross-row guard és cycle-safe protokoll; engine proof még UNVERIFIED |
| Verification               | Legalább 30 planned scenario, minden setup/action/DB/amount-or-qty/audit postconditionnel; nem actual tesztfutás            |
| Findings                   | OPEN evidence/risk/resolution/felelős/acceptance; nincs tervtől automatikus closure                                         |
| Státusz                    | REVIEW / NOT MIGRATION READY; nem WP5 Accepted, projektgazdai review-ra vár                                                 |

### 14.1. Elvégzett dokumentációs ellenőrzések

| Ellenőrzés                               | Eredmény, 2026-11-04                                                                                                                                            |
| ---------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Prettier, meglévő helyi dependencyvel    | PASS, WP1/WP3/WP4/WP5; csak az új WP5 formázása történt                                                                                                         |
| UTF-8, whitespace, Markdown              | PASS, nincs BOM, replacement character vagy trailing whitespace; zárt code fence-ek, 14 számozott főfejezet                                                     |
| Relatív hivatkozások és anchorok         | PASS, 31 link/cél, köztük 16 current metódus tényleges deklarációs sorszáma                                                                                     |
| Historical forrásprovenance              | PASS, 12 ADR Git-object és a felsorolt implementációs utak a rögzített `379be484405a789388df5f5e8367a18b1e598bbc` commiton; nem current munkafalinkek           |
| Decision trace és tervezett verification | PASS, RMA-BD-001–020, D1–D11, F1–F4, AM/AM-G/C1/WP/REV trace; 45 T01–T45 planned teszt és 14 C01–C14 konkurenciahelyzet                                         |
| Sensitive-data mintavizsgálat            | PASS, a négy pending dokumentumban nem talált hardcoded secret/token/private key vagy abszolút környezeti út; nem adatbázis-vizsgálat                           |
| Korábbi artifactok változatlansága       | PASS, WP1/WP3/WP4 belépési SHA-256 változatlan; történeti B results JSON SHA-256 `d1b4443724984cbd21c9852c4611fa659411e09924192d7b3b9296164e33a6e6` változatlan |
| Git-scope és whitespace gate             | PASS, `git diff --check`; változatlan branch/HEAD, üres tracked diff és staging; az induló három untracked fájl mellé kizárólag WP5 került                      |

Az ellenőrzések inline, csak olvasó helyi parancsokkal készültek; nem jött létre ellenőrző scriptfájl.
A dokumentációs PASS nem runtime, pénzügyi, MySQL-integritási vagy migration-readiness bizonyíték.

Releváns [Definition of Done](../../docs/project-management/definition-of-done.md) típus: dokumentáció;
[Layered Quality Gates](../../docs/development/quality-gates.md): helyi formázás, link/anchor/source és
decision trace, UTF-8/whitespace/sensitive scan, teljes scope és `git diff --check`.
PHP/Pint/backend/frontend/build/DB/migration/seeder nem fut: nincs kódváltozás és a feladat dokumentációs.
Nincs új script, dependency install vagy eredmény-regenerálás. Nincs staging/commit/push/merge.
