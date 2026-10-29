# RMA-0003/A – Részletes adatmodell és integritási terv

## RMA-0003/C1 Closure – aktuális státusz (2026-10-29)

Az alábbi dokumentum megőrzött **történeti baseline és review-bizonyíték**.
A korábbi „ADR változatlan / AM-jóváhagyás függő” szövegek a saját korábbi
feladatuk állapotát jelentik; aktuális státuszként **SUPERSEDED**.
RMA-D6–D11 ACCEPTED; AM-01–AM-04 APPROVED, AM-02-G1–G4 MANDATORY az
[aktuális Accepted ADR-0006](../decisions/0006-rma-repair-management-architecture.md)
3.2–3.4 szerint. C1 lezárása:
[C1 döntés- és kaputerv](rma-0003c1-business-decisions-integrity-gates.md);
következő munka: [C2 előkészítési terv](rma-0003c2-detailed-schema-review-plan.md).

A submit-held credit-javaslatot az approval-only D6/AM-03, a közvetlen returnből
available ágat a kötelező quarantine D8/AM-02 váltja fel. Az external->external
általános tiltásának kizárólag explicit return-intake type/purpose kivétele van,
AM-02-G1–G4 mellett; nem általános transfer/scrap-jog. A shipmentenként egyszeri
posting nem tilt több valódi, D9 szerint engedélyezett újraküldést.
Az alábbi régi findingok és teszteredmények nem lettek újraírt C1/C2 sikerállítások.

Státusz: **PROPOSED – architekturális review-ra**. Dátum: 2026-10-28.
Branch: `feature/returns-repair-management`.
HEAD: `ea568deded91a1c256edbefdfbec7cf5be2ce096`. Kezdő munkafa tiszta.
Autoritatív követelmény: [Accepted ADR-0006](../decisions/0006-rma-repair-management-architecture.md).

Jelölések: **VERIFIED** = repositoryban ellenőrzött; **ACCEPTED** = kötelező
ADR-követelmény; **PROPOSED** = technikai terv; **OPEN** = feloldandó döntés.
Az Accepted ADR változatlan. B-felülvizsgálat: 2026-10-29; a [külön review](rma-0003b-integrity-verification.md)
izolált SQL-prototípusok eredményeit tartalmazza. Nincs alkalmazáskód vagy migráció.

## RMA-0003/C1 – aktuális olvasási útmutató (2026-10-29)

Az alábbi A/B-kori séma és a 23/7 táblaleltár megőrzött technikai baseline.
Az új **ACCEPTED RMA-D6–D11** üzleti döntéseket és az ezekből következő aktuális
**PROPOSED** eltéréseket a [C1 döntés- és kaputerv](rma-0003c1-business-decisions-integrity-gates.md)
rögzíti; az ADR változatlan, AM-01–04 külön jóváhagyásra javasolt.
A 3.23 submitkor held ajánlását D6 felülírja: **approved quote foglal**.
Minden replacement return kötelező karantén (D8); a B-kori közvetlen available
ág nem aktuális. Claim acquire/delete hardening: C1 G1; új verified fee és
procurement coverage event tábla: C1 7./9./10.; az aktuális javaslat 25 új tábla.
Shipmentenként új reservation és U(reservation), stabil fizikai occurrence,
return intake/admission és reversal-original FK a C1 szerint specifikálandó.
A 3–15. fejezet e jelzett eltérésekkel történeti baseline, nem párhuzamos új
üzleti felhatalmazás. Az új scope-mapping AM-02 jóváhagyásfüggő; nem vezettük be.

## 1. Vezetői összefoglaló

**ACCEPTED:** többtermékes ügy egy tulajdonossal, termékenként egy munkalappal;
egy aktív RMA/serial az egész ügy lezárásáig; bizonyított saját gyártás,
önálló javítás és megőrzött történet. A három scope `company_stock`,
`customer_custody`, `external`. Visszáru nem növel normál készletet.
Csere foglalása/előkészítése vállalati tulajdonban marad; fizikai kiadás egyszer
von le, későbbi delivery és ownership esemény nem von le újra.

**PROPOSED: 23 új tábla, 7 meglévő tábla módosítása.** A 18 ADR-jelölt mellett
öt kiegészítés: `repair_diagnostic_credit_allocations`, `product_warranty_rules`, `repair_delivery_events`,
`repair_ownership_events`, `item_instance_genealogy_events`. Ezek verziózott
cikkszabályt, utólagos átadási bizonyítékot, külön tulajdonjogi eseményt és
ki-/beszerelési történetet tárolnak. Külön replacement-reservation/preparation
vagy attempt tábla nem szükséges.

Módosítandó: `item_instances`, `stock_movements`, `stock_reservations`,
`stock_balances`, `material_requirements`,
`purchase_requisition_item_sources`, `purchase_requisition_items`.
A partner-, dokumentum- és activity-séma újrahasználható; az integrációs
policyk, registryk és szolgáltatások fejlesztése külön feladat.

**OPEN:** review-ra kész terv, nem feltétel nélkül implementálható migrációterv.
Motoronkénti DDL-bizonyítás, legacy adatok, pénzügyi és szerződéses feltételek,
valamint a 14. fejezet findingjai még kapuk. A B review részleges SQL-prototípusokat
futtatott; teljes migrációs és alkalmazásbizonyíték továbbra sincs.

## 2. Ellenőrzött meglévő séma

### 2.1. Alapállapot és kulcsok

**VERIFIED:** az alábbi táblák aktuális migrációi és modelljei ellenőrzöttek.
Minden felsorolt üzleti tábla `id()` PK-t és timestamps mezőket használ.
R = restrictOnDelete, N = nullOnDelete, C = cascadeOnDelete, ? = nullable.
Az összes tényleges oszlop, nullable/default és index a 2.5 függelékben szerepel.

| Tábla                             | FK / UNIQUE és fontos hiány                                                                        | Soft delete / enum / index                                     |
| --------------------------------- | -------------------------------------------------------------------------------------------------- | -------------------------------------------------------------- |
| items                             | U(item_number); serialflag nem eredetbizonyíték                                                    | Igen; item_type string+enum; type/name/serialflag/active index |
| item_instances                    | U(serial_number); item R, factory R, current_location ?N; production_order_id ? csak index, nem FK | Nem; current_status string+enum                                |
| item_instance_relations           | parent C, child R; U(parent,child); quantity default 1                                             | Nem; nincs kiszerelési history                                 |
| production_orders                 | plan_item/customer_order_item/item/bom/sequence R; creator ?N; U(order_number)                     | Igen; status string+enum; FK/dátum indexek                     |
| production_tasks                  | order/instance C; step/employee R; U(instance,step)                                                | Nem; status/start/finish/FK index                              |
| quality_checks                    | task C; checked_by(employee) R                                                                     | Nem; result string+enum; idő/result/FK index                   |
| operation_types                   | U(code), name index                                                                                | Igen; code PHP enum-cast, DB-ben string                        |
| operation_sequences               | item R; U(item_id,version)                                                                         | Igen; active index                                             |
| operation_sequence_steps          | sequence C, type/factory/professional_role R; U(sequence,step_order)                               | Nem; order/QC index                                            |
| stock_balances                    | item/location R, batch ?N; U(item,location,batch)                                                  | Nem; nincs serial/scope/owner; nullable UNIQUE rés             |
| stock_movements                   | item R; batch/instance/from/to/performer ?N; source nullableMorphs                                 | Nem; type/time/FK/morph index; nincs scope                     |
| stock_reservations                | item R; location/batch ?N; customer_order_item/production_order ?C                                 | Igen; nincs serial vagy owner-XOR; státusz/idő/FK index        |
| material_requirements             | customer_order_item kötelező C; required_item R                                                    | Igen; nincs owner+cikk UNIQUE                                  |
| locations                         | U(code); factory_unit ?N                                                                           | Igen; type/active index; nincs központi allocatability         |
| item_batches                      | item R, supplier ?N; U(item_id,batch_number)                                                       | Igen; received/expires index, nem warranty                     |
| purchase_requisitions             | U(requisition_number); requested_by ?N                                                             | Igen; státusz/requested_at index                               |
| purchase_requisition_items        | requisition C; requirement ?N; item R                                                              | Igen; direkt requirement mellett több source                   |
| purchase_requisition_item_sources | PR-item C, requirement C; U(PR-item,requirement)                                                   | Nem; requirement IX; nincs globális demand-generation UNIQUE   |
| purchase_orders                   | U(order_number); supplier R, requisition/creator ?N                                                | Igen; státusz/dátum/FK IX; nincs javítási díjmodell            |
| customers                         | U(code), name/tax_number IX                                                                        | Igen; nincs serialos tulajdontörténet                          |
| employees                         | U(employee_number); user/professional_role ?N                                                      | Igen; user_id nem UNIQUE                                       |
| users                             | U(email)                                                                                           | Nem; HasOne employee nem DB-egyediség                          |
| documents                         | documentable morph; uploader/approver ?N; group+version csak IX                                    | Igen; storage/AI bővítések, approved/current IX                |
| activity_log                      | subject/causer nullableMorphs; changes/properties JSON                                             | Nem; morph index; nincs üzleti target-FK                       |

Az enumok DB-ben stringek, modellekben enum-castok: ez nem adatbázis CHECK.
A morph-kapcsolat nem valódi többtáblás idegen kulcs.

### 2.2. Ellenőrzött tranzakciók és repositoryminták

- [StockBalanceRepository](../../app/Repositories/Admin/StockBalanceRepository.php)
  `totalQuantityForItem()`, `balancesForReservation()`: nincs custody-szűrés.
  `increaseQuantity()` firstOrCreate/increment, nem NULL-safe egyenlegvédelem.
- [StockReservationRepository](../../app/Repositories/Admin/StockReservationRepository.php):
  aktív reserved_quantity SUM; `release()` állapotot ír, nem igazol fizikai helyet.
- [ProductionTaskMaterialService](../../app/Services/Admin/ProductionTaskMaterialService.php)
  `store()`: transactionben balance decrement, movement és audit; megadott
  reservation összetartozása nem teljesen ellenőrzött, részfogyasztásnál teljes
  reservation consumed lehet. `balanceForConsumption()` fizikai mennyiséget
  zárol, nem vonja le minden más igény lekötését.
- [MaterialRequirementRepository](../../app/Repositories/Admin/MaterialRequirementRepository.php)
  `updateForProductionOrderBomItem()` kulcsa customer_order_item+required_item,
  nem production_order. Több PO ugyanazon rendeléstételhez ugyanazt az igényt írja.
- [PurchaseRequisitionService](../../app/Services/Admin/PurchaseRequisitionService.php)
  `generateFromMaterialRequirements()` requirement-sorzár mellett source nélküli
  hiányt csoportosít; ez nem globális DB-egyediség.
- [ProductionTaskService](../../app/Services/Admin/ProductionTaskService.php)
  `generateFromOrder()` a gyártás előtt serialt hoz létre; start/finish az
  eredeti production workflowt módosítja. Javításban nem használható közvetlenül.
- [OperationSequenceRepository](../../app/Repositories/Admin/OperationSequenceRepository.php)
  `updateWithSteps()` törli és újralétrehozza a lépéseket; nem teljes történeti
  változatlansági kontroll. Eredeti gyártási tervet az RMA nem írhat át.
- [DocumentableRegistry](../../app/Support/DocumentableRegistry.php) csak a
  meglévő négy target típust engedi. Target-létezés nem target-permission.
- [BusinessCacheInvalidator](../../app/Services/BusinessCacheInvalidator.php)
  afterCommit invalidálást támogat; [AuditLogService](../../app/Services/AuditLogService.php)
  business auditot ír, önmagában nem domain-deduplikáció.

### 2.3. Lényeges adat- és történeti rések

**VERIFIED:** FK nélküli eredet; nullable batch-es egyedi kulcs; owner nélküli
reservation lehetősége; más igény foglalásának nem teljes fogyasztásvédelme;
kaszkádos történettörlés; dokumentum-morph és proof megőrzési rés.
Feltárásuk nem engedély az adatok vagy a működés mostani módosítására.

### 2.4. Konvenciók és környezet

A [database config](../../config/database.php) InnoDB/strict,
`utf8mb4_unicode_ci`, SQLite FK és DEFERRED tranzakció beállítást deklarál.
Az éles szerver verziója/isolationje nincs ellenőrizve; a
[backend quality gate](../../docs/backend-quality-gate.md) MySQL 8.4-et céloz.

A `.kiro/designs` nem létezett. A [projektindex](../index.md),
[architektúra](../steering/architecture.md) és
[projektkonvenció](../../docs/architecture/project-conventions.md) ellenőrzése
után a feladatban név szerint kért könyvtár készül. Más dokumentumot,
indexet, szabályzatot vagy Accepted ADR-t nem módosítunk.

### 2.5. Migrációs bizonyítékfüggelék

A dokumentum végén a meglévő migrációk pontos up-definíciói láthatók.
Ez repository-bizonyíték, nem éles adatbázis-introspekció.

## 3. Javasolt részletes RMA-séma

### 3.0. Közös jelölés és minden táblára érvényes követelmények

A 3–13. fejezet technikai megoldásai **PROPOSED** elemek. A sémablokkok
deklaratív specifikációk, nem végrehajtható migrációk.

| Jel        | Laravel-kompatibilis típus             | Null / default                                                  |
| ---------- | -------------------------------------- | --------------------------------------------------------------- |
| ID         | id()                                   | auto-increment PK; nem CHECK operandus                          |
| F          | foreignId()/unsignedBigInteger()       | nyíl utáni tábla id FK; effective NO ACTION/RESTRICT, update is |
| U32/U64    | unsignedInteger()/unsignedBigInteger() | SQLite-ban nonnegative CHECK szükséges                          |
| S(n)/T/J   | string(n)/text()/json()                | JSON snapshot, nem FK-pótlék                                    |
| DT         | dateTime(6), UTC                       | service által írt, nincs DB óra-default                         |
| Q          | decimal(12,3)                          | exact decimal service-aritmetika, nem float                     |
| M          | signed bigInteger()                    | pénz skálázott egész, money_scale 0..6; nem float               |
| B          | boolean()                              | CHECK IN(0,1)                                                   |
| ! / ? / =x | NOT NULL / nullable / explicit default | egyébként nincs default; ? default NULL                         |

Minden új tábla kap `id:ID`, `created_at:DT!` mezőt, kivéve
active_repair_claims: ott item_instance_id a nem-null PK, nincs id().
[MUTABLE] táblában még updated_at:DT!; [EVENT]-ben nincs updated_at.
Új táblán soft delete nincs. Minden F mező külön FK és IX(field), kivéve ha
PK/U/IX azonos bal prefixszel már fedi; composite FK-nál teljes child-tuple IX.
Ez a szabály az alábbi blokkok konkrét F oszlopait teljesen felsorolt indexszé
egészíti, nem „megfelelő indexek” későbbi feltételezése.

Minden FK effective delete/update NO ACTION, nincs CASCADE/SET NULL.
MySQL CHECK-ben használt FK-hoz a referenciális akciók DDL-kombinációját
külön bizonyítani kell: a default NO ACTION alakot tervezzük, nem cascade-t.
CHECK nem használ más táblát, subqueryt, órát vagy auto-increment PK-t.
A nullos formulák explicit IS NULL ágakat igényelnek: SQL UNKNOWN nem elutasítás.

Minden EVENT üzleti mező immutable insert után, korrekció új esemény.
MUTABLE táblában a PK, szülő-FK, serial és created_at mindig immutable; további
mezők a következő leírások szerint rögzülnek. **FK/UNIQUE nem UPDATE/DELETE-tiltás.**
Történeti változatlanság service-guard+korlátozott írásjog+append-only tények;
a B review REV-01 szerint célzott trigger/procedure hardening javasolt, production
jog- és deployment-bizonyítása még kapu.
Minden domainírás commanddal és audittal egy DB-tranzakció. Egy failed command
teljes rollback. A táblahelyi „T:” a közös követelményen felüli transactionguard.

### 3.1. `repair_requests` [MUTABLE]

Cél: egy tulajdonos többtermékes ügye, külön bejelentővel.

```text
case_number:S(64)!; owner_customer_id:F! -> customers
created_by:F! -> users; creator_employee_id:F! -> employees
reporter_employee_id:F? -> employees; reporter_name:S(255)!
reporter_contact_snapshot:J!; owner_snapshot:J!
status:S(16)!='open'; closed_at:DT?; closed_by:F? -> users
opening_command_id:F! -> repair_commands
U(case_number); U(opening_command_id); U(id,owner_customer_id)
IX(owner_customer_id,created_at); IX(status,created_at)
CHECK status IN('open','closed')
CHECK (status='open' AND closed_at IS NULL AND closed_by IS NULL)
 OR (status='closed' AND closed_at IS NOT NULL AND closed_by IS NOT NULL)
```

Owner/létrehozó és eredeti snapshot immutable. T: aktív user–employee egyezés,
permission, legalább egy tagság+order és záráskor a teljes proofhalmaz.
A két actor-FK önmagában nem bizonyítja a user–employee egyezést.

### 3.2. `repair_request_items` [MUTABLE]

```text
repair_request_id:F! -> repair_requests; owner_customer_id:F! -> customers
item_instance_id:F! -> item_instances; reported_fault:T!
origin_production_order_id:F! -> production_orders
origin_verified_by:F! -> users; origin_verified_at:DT!; origin_evidence:J!
receipt_stock_movement_id:F? -> stock_movements; received_at:DT?
U(repair_request_id,item_instance_id); U(id,item_instance_id)
U(id,repair_request_id); U(receipt_stock_movement_id)
IX(item_instance_id,created_at)
FK(repair_request_id,owner_customer_id) -> repair_requests(id,owner_customer_id)
CHECK (receipt_stock_movement_id IS NULL AND received_at IS NULL)
 OR (receipt_stock_movement_id IS NOT NULL AND received_at IS NOT NULL)
```

Az owner másolata csak a composite FK-hoz szükséges, nem külön szerkeszthető.
Szülő/serial/eredet immutable. Receipt pointer egyszer NULL->érték, aztán immutable
service-guarddal; a movement receipt-key DB-egyedisége kiegészíti. T: owner és
eredet igazolt; received pointer, movement és custody együtt. Circular FK-k:
members először receipt-pointer FK nélkül, majd movement után contract.

### 3.3. `active_repair_claims` [MUTABLE, technikai]

```text
item_instance_id:F! -> item_instances; PK(item_instance_id), nincs id()
repair_request_item_id:F! -> repair_request_items
claimed_by:F! -> users; command_id:F! -> repair_commands
U(repair_request_item_id)
FK(repair_request_item_id,item_instance_id)
 -> repair_request_items(id,item_instance_id)
CHECK: nincs további soron belüli üzleti feltétel
```

DB: egy serial egy claim, és a claim ugyanazon tagság serialjára mutat.
Nem DB-garancia: minden open tételhez létezik claim és csak lezáráskor törölhető.
T: parent+instance zár, tagság/claim/order atomikus insert; delete kizárólag
teljes ügyclose. Nincs soft delete. A történet a tagságban és auditban megmarad.

### 3.4. `repair_orders` [MUTABLE]

```text
repair_request_item_id:F! -> repair_request_items
work_order_number:S(64)!; execution_status:S(32)!='awaiting_receipt'
current_attempt_number:U32!=1
diagnostic_fee_mode:S(16)!='free'; diagnostic_rate_units:M?
money_scale:U32?; currency:S(3)?
diagnostic_consent_document_id:F? -> documents
-- diagnostic_credit_quotation_id helyett tartós allokáció a 3.23 szerint
U(repair_request_item_id); U(work_order_number)
IX(execution_status,created_at)
CHECK current_attempt_number>=1
CHECK execution_status IN('awaiting_receipt','assessment','awaiting_authorization',
 'ready','repairing','quality_control','completed','stopped')
CHECK diagnostic_fee_mode IN('free','fixed','time_based')
CHECK (diagnostic_fee_mode='free' AND diagnostic_rate_units IS NULL)
 OR (diagnostic_fee_mode IN('fixed','time_based') AND diagnostic_rate_units IS NOT NULL
 AND diagnostic_rate_units>=0 AND money_scale IS NOT NULL AND currency IS NOT NULL)
CHECK money_scale IS NULL OR money_scale BETWEEN 0 AND 6
```

Minden tétel orderének létezését a child UNIQUE/FK nem garantálja: T: parent
nyitás. Díj/consent első végrehajtástól immutable snapshot. Credit a 3.23 tartós allokációja; nem számlázási tény. T: orderzár.

### 3.5. `repair_operations` [MUTABLE]

```text
repair_order_id:F! -> repair_orders; attempt_number:U32!; sequence_number:U32!
operation_type_id:F! -> operation_types; factory_unit_id:F! -> factory_units
professional_role_id:F? -> professional_roles
instructions_snapshot:T!; estimated_seconds:U64!; requires_quality_check:B!=0
status:S(16)!='planned'; started_at:DT?; finished_at:DT?
supersedes_operation_id:F? -> repair_operations
scope_quotation_id:F? -> repair_quotations; command_id:F! -> repair_commands
U(repair_order_id,attempt_number,sequence_number)
U(id,repair_order_id); U(supersedes_operation_id)
IX(repair_order_id,status); IX(operation_type_id,created_at)
CHECK attempt_number>=1 AND sequence_number>=1 AND estimated_seconds>=0
CHECK status IN('planned','ready','in_progress','completed','cancelled','superseded')
CHECK finished_at IS NULL OR (started_at IS NOT NULL AND finished_at>=started_at)
```

Megkezdett terv/utasítás/estimate/QC-követelmény immutable. T: csak el nem
kezdett sor supersedálható; self-FK nem ciklus- vagy azonosorder-védelem.
Sorszám nem használható újra; új scope F4 approval-gate-et indít, history megmarad.

### 3.6. `repair_operation_assignments` [MUTABLE]

```text
repair_operation_id:F! -> repair_operations; employee_id:F! -> employees
assigned_by:F! -> users; assigned_at:DT!; ended_at:DT?
role_snapshot:S(100)!; command_id:F! -> repair_commands
U(repair_operation_id,employee_id); U(id,employee_id)
IX(employee_id,assigned_at)
CHECK ended_at IS NULL OR ended_at>=assigned_at
```

Employee és operation immutable, lezárás egyszeri. T: aktív dolgozó, szakmai
alkalmasság és permission külön feltétel. Több dolgozó egy operationhöz engedett.

### 3.7. `repair_time_entries` [MUTABLE -> lezárt tény]

```text
repair_operation_assignment_id:F! -> repair_operation_assignments
started_at:DT!; finished_at:DT?
active_assignment_id:F? -> repair_operation_assignments
supersedes_time_entry_id:F? -> repair_time_entries
correction_reason:T?; command_id:F! -> repair_commands
U(active_assignment_id); U(supersedes_time_entry_id)
IX(repair_operation_assignment_id,started_at)
CHECK (finished_at IS NULL AND active_assignment_id IS NOT NULL
 AND active_assignment_id=repair_operation_assignment_id)
 OR (finished_at IS NOT NULL AND active_assignment_id IS NULL AND finished_at>=started_at)
CHECK (supersedes_time_entry_id IS NULL AND correction_reason IS NULL)
 OR (supersedes_time_entry_id IS NOT NULL AND correction_reason IS NOT NULL)
```

Lezárt időpont immutable; korrekció új sor, előzmény/indok megmarad.
T: assignment-sorzár alatt interval-overlap vizsgálat; NULL végpont végtelen.
A UNIQUE egy nyitott szakaszt véd, nem az összes időintervallum átfedését.
Actual a lezárt, nem superseded szakaszok összege. Globális employee-overlap
tiltást nem találunk ki: az ADR ezt nyitott szervezeti kérdésként hagyta.

### 3.8. `repair_materials` [EVENT]

```text
repair_order_id:F! -> repair_orders; repair_operation_id:F? -> repair_operations
stock_reservation_id:F? -> stock_reservations
item_id:F! -> items; item_batch_id:F? -> item_batches
item_instance_id:F? -> item_instances
used_quantity:Q!; unit:S(50)!; stock_movement_id:F! -> stock_movements
command_id:F! -> repair_commands; notes:T?
U(stock_movement_id); U(id,repair_order_id)
IX(repair_order_id,item_id); IX(item_instance_id,created_at)
FK(repair_operation_id,repair_order_id) -> repair_operations(id,repair_order_id)
CHECK used_quantity>0
CHECK item_instance_id IS NULL OR used_quantity=1
```

Tényleges fogyasztás, nem becslés. T: item/batch/serial/unit és igénytulajdonos
egyezés, normál stock és nettó elérhetőség, más foglalás kizárása. Atomikus
material+movement+projekció+részfoglalás. NULL operation esetén az order továbbra
is kötelező; a rész-NULL composite FK ezt önmagában nem ellenőrizné.

### 3.9. `repair_inspections` [EVENT]

```text
repair_order_id:F! -> repair_orders; repair_operation_id:F? -> repair_operations
attempt_number:U32!; inspection_type:S(24)!; result:S(24)!
checked_by:F! -> employees; recorded_by:F! -> users; checked_at:DT!
evidence_document_id:F? -> documents; notes:T!; command_id:F! -> repair_commands
U(command_id); IX(repair_order_id,attempt_number,inspection_type,checked_at)
FK(repair_operation_id,repair_order_id) -> repair_operations(id,repair_order_id)
CHECK attempt_number>=1
CHECK inspection_type IN('diagnostic','operation_quality','final_quality')
CHECK result IN('accepted','rejected','rework_required')
```

T: aktuális order/attempt és vizsgálati állapot. Régi QC nem léptet új
próbálkozást; újrapróba monoton sorszám, nincs automatikus korlát. Hibás eredmény
immutable; külön valódi ismételt vizsgálat külön command, nem retry.

### 3.10. `product_warranty_rules` [EVENT]

```text
item_id:F! -> items; version:U32!; effective_from:DT!
duration_value:U32!; duration_unit:S(8)!
rule_reference:T!; approved_by:F! -> users; approved_at:DT!
command_id:F! -> repair_commands
U(item_id,version); U(item_id,effective_from); U(id,item_id)
IX(item_id,effective_from)
CHECK version>=1 AND duration_value>0
CHECK duration_unit IN('day','month','year')
```

Verziózott cikkszabály, nem beépített jogszabály. T: itemzáras publikálás;
aktuális az alkalmazandó időpontra utolsó hatályos verzió. A következő
effective_from zárja az előzőt; assessment a felhasznált szabályt snapshotolja.
Visszamenőleges módosítás nem írja át automatikusan a korábbi minősítést.

### 3.11. `product_handover_evidences` [EVENT]

```text
item_instance_id:F! -> item_instances; recipient_customer_id:F! -> customers
handed_over_at:DT!; handover_reference:S(128)!
evidence_document_id:F! -> documents
verified_by:F! -> users; verified_at:DT!
repair_release_id:F? -> repair_releases
supersedes_evidence_id:F? -> product_handover_evidences
command_id:F! -> repair_commands
U(command_id); U(supersedes_evidence_id); U(id,item_instance_id)
IX(item_instance_id,handed_over_at); IX(handover_reference)
CHECK verified_at>=handed_over_at
```

Konkrét dokumentumrekord+verzió+checksum megőrzése kötelező. Több valódi átadás
megengedett, ezért nincs hibás serial-only UNIQUE. T: ugyanazon termék/címzett,
valódi bizonyíték, célügy-permission. Korrekció új sor; rendelésdátum nem proof.

### 3.12. `warranty_assessments` [EVENT]

```text
repair_order_id:F! -> repair_orders; item_instance_id:F! -> item_instances
subject_item_id:F! -> items; version:U32!; classification:S(24)!
handover_evidence_id:F? -> product_handover_evidences
product_warranty_rule_id:F? -> product_warranty_rules
rule_snapshot:J!; warranty_start:DT?; warranty_end:DT?
assessed_by:F! -> users; assessed_at:DT!; justification:T!
is_override:B!=0; override_reason:T?; command_id:F! -> repair_commands
U(repair_order_id,version); U(command_id)
IX(item_instance_id,assessed_at); IX(repair_order_id,item_instance_id,version)
FK(item_instance_id,subject_item_id) -> item_instances(id,item_id)
FK(handover_evidence_id,item_instance_id) -> product_handover_evidences(id,item_instance_id)
FK(product_warranty_rule_id,subject_item_id) -> product_warranty_rules(id,item_id)
CHECK version>=1
CHECK classification IN('warranty','non_warranty','pending_assessment')
CHECK warranty_end IS NULL OR (warranty_start IS NOT NULL AND warranty_end>=warranty_start)
CHECK (is_override=0 AND override_reason IS NULL)
 OR (is_override=1 AND override_reason IS NOT NULL)
```

T: version globálisan orderzár alatt; aktuális döntés a **tárgyalt serialra**
legnagyobb version. Replacement subject a megőrzött foglalási kapcsolaton
igazolható, eredeti subject csak az order tagságának serialja lehet. A composite
FK-k serial–cikk–handover–rule egyezést védenek; az orderhez tartozást guard
bizonyítja. Global order-version megmarad; az aktuális döntés serialszűrt. Hiányos bizonyíték pending; override permission+indok.
A csere nem resetel garanciát; régi rule és assessment immutable.

### 3.13. `repair_quotations` [MUTABLE -> submitted tartalom immutable]

```text
repair_order_id:F! -> repair_orders; version:U32!; status:S(16)!='draft'
currency:S(3)!; money_scale:U32!; total_units:M!; content_hash:S(64)?
valid_until:DT!; submitted_at:DT?; submitted_by:F? -> users
responded_at:DT?; response_recorded_by:F? -> users
responding_customer_id:F? -> customers; response_document_id:F? -> documents
response_reference:S(128)?; command_id:F! -> repair_commands
U(repair_order_id,version); U(command_id); U(id,repair_order_id)
IX(status,valid_until)
CHECK version>=1 AND money_scale BETWEEN 0 AND 6 AND total_units>=0
CHECK status IN('draft','submitted','approved','rejected','expired','superseded')
CHECK status='draft' OR
 (submitted_at IS NOT NULL AND submitted_by IS NOT NULL AND content_hash IS NOT NULL)
CHECK status NOT IN('approved','rejected') OR
 (responded_at IS NOT NULL AND response_recorded_by IS NOT NULL
  AND responding_customer_id IS NOT NULL AND response_document_id IS NOT NULL)
```

T: orderzár, legnagyobb version az aktuális jelölt; új draft a régi approval
új munkára használatát felfüggeszti, történetét nem törli. Submitkor tartalom,
tételek, skála, pénznem, összeg/hash immutable. Ügyfélválasz proofhoz kötött tény.
Customer-egyezés, total=sum, scope és expiry a start pillanatában service-guard.
Nem elég az időzített expired job. Credit felhasználási claim a munkalapé.

### 3.14. `repair_quotation_items` [MUTABLE -> submissiontől immutable]

```text
repair_quotation_id:F! -> repair_quotations; line_number:U32!
line_type:S(24)!; item_id:F? -> items; description:T!
quantity:Q!; unit:S(50)!; unit_price_units:M!; line_total_units:M!
diagnostic_source_order_id:F? -> repair_orders
U(repair_quotation_id,line_number); U(id,repair_quotation_id,diagnostic_source_order_id)
IX(item_id,created_at)
CHECK line_number>=1 AND quantity>0
CHECK line_type IN('material','labor','additional_charge','diagnostic_fee','diagnostic_credit')
CHECK (line_type='diagnostic_credit' AND line_total_units<=0
 AND diagnostic_source_order_id IS NOT NULL)
 OR (line_type<>'diagnostic_credit' AND line_total_units>=0
 AND diagnostic_source_order_id IS NULL)
```

T: pénzskála a szülőé, exact mennyiség×egységdíj számítás/rounding; credit
<=igazolt díj és ugyanazon munkalap forrása, egyszer felhasználva. A CHECK csak
előjelet és kapcsolat-alakot véd; összeg, felső korlát és business-scope nem DB-garancia.

### 3.15. `repair_custodies` [MUTABLE, projekció]

```text
repair_request_item_id:F! -> repair_request_items
item_instance_id:F! -> item_instances; location_id:F! -> locations
last_stock_movement_id:F! -> stock_movements
U(repair_request_item_id); U(item_instance_id); U(last_stock_movement_id)
FK(repair_request_item_id,item_instance_id) -> repair_request_items(id,item_instance_id)
CHECK: nincs további soron belüli mennyiségfeltétel (egy sor egy egység)
```

Csak eredeti customer-custody, replacement itt nem lehet. T: kizárólag mozgás
melletti insert/update/delete, aktuális hely és instance-projekció együtt.
Újraépítés nem új receipt; a movement history megmarad.

### 3.16. `repair_releases` [EVENT]

```text
repair_request_item_id:F! -> repair_request_items
released_item_instance_id:F! -> item_instances
release_role:S(16)!; stock_reservation_id:F? -> stock_reservations
recipient_customer_id:F! -> customers; recipient_name:S(255)!
recipient_snapshot:J!; released_at:DT!; shipment_reference:S(128)!
release_evidence_document_id:F! -> documents; final_condition:T!
stock_movement_id:F! -> stock_movements
handover_policy_snapshot:J!; command_id:F! -> repair_commands
shipment_attempt:U32!; previous_release_id:F? -> repair_releases
redispatch_authorization_document_id:F? -> documents
U(stock_movement_id); U(command_id); U(previous_release_id)
U(stock_reservation_id,shipment_attempt)
U(repair_request_item_id,released_item_instance_id,release_role,shipment_attempt)
U(id,repair_request_item_id,released_item_instance_id,release_role)
FK(previous_release_id,repair_request_item_id,released_item_instance_id,release_role)
 -> repair_releases(id,repair_request_item_id,released_item_instance_id,release_role)
CHECK shipment_attempt>=1
CHECK (shipment_attempt=1 AND previous_release_id IS NULL
 AND redispatch_authorization_document_id IS NULL)
 OR (shipment_attempt>1 AND release_role='replacement'
 AND previous_release_id IS NOT NULL AND redispatch_authorization_document_id IS NOT NULL)
IX(shipment_reference); IX(released_item_instance_id,released_at)
CHECK release_role IN('original','replacement')
CHECK (release_role='original' AND stock_reservation_id IS NULL)
 OR (release_role='replacement' AND stock_reservation_id IS NOT NULL)
```

Egy release egy fizikai kiadás; nem delivery vagy ownership. T: tagság/serial,
owner/reservation/proof/policy/QC egyezése. Attempt ugyanazon member/serial és
reservation zára alatt, tartós command-azonossággal; retry nem növeli. Új attempt
csak bizonyított visszaérkezés, rendezett tulajdon/állapot, új mennyiségi lekötés
és explicit felhatalmazás után. Previous az előző azonos serialú kiadás,
sorszáma pontosan előző+1; lánc/egyezés guardolt. Original attempt=1, ismételt
original receipt nem engedélyezett. U(move/command/attempt/previous) duplázást véd;
nyers új attempt INSERT ellen a parentzáras guard és korlátozott írásjog kell.

### 3.17. `repair_delivery_events` [EVENT]

```text
repair_release_id:F! -> repair_releases
event_type:S(24)!; event_key:S(128)!; occurred_at:DT!
recipient_snapshot:J!; reference:S(128)!; evidence_document_id:F! -> documents
recorded_by:F! -> users; supersedes_event_id:F? -> repair_delivery_events
command_id:F! -> repair_commands
related_return_event_id:F? -> repair_delivery_events
return_stock_movement_id:F? -> stock_movements; return_location_id:F? -> locations
ownership_verification_document_id:F? -> documents
U(return_stock_movement_id); U(related_return_event_id)
U(repair_release_id,event_key); U(supersedes_event_id); U(command_id)
IX(repair_release_id,occurred_at)
CHECK event_type IN('delivered','delivery_failed','recall_requested','physically_returned','return_reconciled','correction')
```

T: webhook/extern event deduplikáció és proof ellenőrzés; hatályos állapot
superseded/correction kizárásával. Visszaérkezési event nem növel balance-t
magától. Physically_returned az észlelés bizonyítéka rendezés előtt is.
Return_reconciled új append-only esemény az adott returnre, igazolt hely/tulajdon
és egyedi inbound kapcsolattal. CHECK: csak return_reconciled esetén
related_return_event_id, return_stock_movement_id, return_location_id és
ownership_verification_document_id mind nem-NULL; más típusnál mind NULL.
Guard: azonos release/serial, return-típusú előzmény és tényleges movement.
Inbound+projekció+reconciliation atomikus. Ismeretlen tulajdonnál nincs company
posting vagy redispatch; a quarantine kezelése FND-04 OPEN.

### 3.18. `repair_ownership_events` [EVENT]

```text
repair_request_item_id:F! -> repair_request_items
repair_release_id:F? -> repair_releases; stock_reservation_id:F? -> stock_reservations
item_instance_id:F! -> item_instances
event_type:S(24)!; contractual_trigger:S(128)!; occurred_at:DT!
from_owner_kind:S(16)!; from_customer_id:F? -> customers
to_owner_kind:S(16)!; to_customer_id:F? -> customers
policy_snapshot:J!; evidence_document_id:F! -> documents
authorized_by:F! -> users; supersedes_event_id:F? -> repair_ownership_events
command_id:F! -> repair_commands
event_sequence:U32!; source_namespace:S(64)!; source_event_key:S(128)!
U(repair_request_item_id,item_instance_id,event_sequence)
U(source_namespace,source_event_key)
CHECK event_sequence>=1
U(supersedes_event_id); U(command_id)
IX(item_instance_id,occurred_at)
CHECK repair_release_id IS NOT NULL OR stock_reservation_id IS NOT NULL
CHECK event_type IN('transfer','reversal','correction')
CHECK from_owner_kind IN('company','customer') AND to_owner_kind IN('company','customer')
CHECK (from_owner_kind='company' AND from_customer_id IS NULL)
 OR (from_owner_kind='customer' AND from_customer_id IS NOT NULL)
CHECK (to_owner_kind='company' AND to_customer_id IS NULL)
 OR (to_owner_kind='customer' AND to_customer_id IS NOT NULL)
```

T: member+reservation/release+serial+contract trigger+evidence egyezés és jogosult döntés.
A nullable release megengedi a külön igazolt esemény rögzítését akkor is, ha az
alkalmazandó feltétel időpontja a fizikai kiadást megelőzi; a tartós cserefoglalás
adja a kapcsolatot. Később a release ugyanazon reservationön keresztül
lekérdezhető, nem kell az immutable eseményt átírni. Az occurrence-kulcs nem függ
a release NULL->érték változásától vagy a contractual_trigger szövegétől.
Ismételt valódi feltétel új occurrence; retry azonos tartós forrásazonosító.
UI-nál command identity, webhooknál provider event key; eltérő payload ugyanazon
kulccsal hiba. Sequence member+serial zár alatt; recorded_at a rögzítés,
occurred_at a bizonyított hatályosság ideje, sorrendjük eltérhet.
Nem állítjuk, hogy ilyen trigger jogilag alkalmazandó: ismeretlen feltétel
blokkol. Preparation önmagában továbbra sem ownership-trigger.
Nem készletmozgás, új levonás nincs. Reversal/correction nem töröl régi eseményt.
A szerződéses/jogi feltétel konkrét tartalma nem e terv által kitalált szabály.

### 3.19. `repair_dispositions` [EVENT]

```text
repair_request_item_id:F! -> repair_request_items; disposition_type:S(32)!
original_outcome:S(24)!; original_release_id:F? -> repair_releases
original_scrap_movement_id:F? -> stock_movements
replacement_reservation_id:F? -> stock_reservations
replacement_release_id:F? -> repair_releases
authorization_document_id:F! -> documents
authorized_by:F! -> users; finalized_at:DT!; justification:T!
command_id:F! -> repair_commands
U(repair_request_item_id); U(original_release_id); U(original_scrap_movement_id)
U(replacement_release_id); U(command_id)
CHECK disposition_type IN('repaired_returned','returned_unrepaired','replaced',
 'scrapped','cancelled_before_receipt')
CHECK original_outcome IN('returned','scrapped','not_received')
CHECK (original_outcome='returned' AND original_release_id IS NOT NULL
 AND original_scrap_movement_id IS NULL)
 OR (original_outcome='scrapped' AND original_release_id IS NULL
 AND original_scrap_movement_id IS NOT NULL)
 OR (original_outcome='not_received' AND original_release_id IS NULL
 AND original_scrap_movement_id IS NULL)
CHECK (disposition_type='replaced' AND replacement_reservation_id IS NOT NULL
 AND replacement_release_id IS NOT NULL)
 OR (disposition_type<>'replaced' AND replacement_reservation_id IS NULL
 AND replacement_release_id IS NULL)
```

T: disposition és original_outcome értelmes kombinációja, összes kapcsolt tény
azonos tagsághoz/serialpárhoz tartozása; final csak teljes proof/QC/authorization
mellett. Pending nem final. CHECK alakot véd, nem bizonyítja a valódi teljesítést.

### 3.20. `repair_status_histories` [EVENT]

```text
repair_request_id:F? -> repair_requests; repair_order_id:F? -> repair_orders
state_dimension:S(32)!; from_state:S(32)?; to_state:S(32)!
actor_id:F! -> users; occurred_at:DT!; reason:T!
stock_reservation_id:F? -> stock_reservations
repair_release_id:F? -> repair_releases; operation_id:F? -> repair_operations
metadata:J!; command_id:F! -> repair_commands; event_sequence:U32!
U(command_id,event_sequence)
IX(repair_request_id,occurred_at); IX(repair_order_id,state_dimension,occurred_at)
CHECK (repair_request_id IS NOT NULL AND repair_order_id IS NULL)
 OR (repair_request_id IS NULL AND repair_order_id IS NOT NULL)
CHECK event_sequence>=1
CHECK state_dimension IN('case','execution','operation','replacement','quotation',
 'warranty','custody','ownership')
```

T: cél és opcionális reservation/release/operation ugyanahhoz az ügyhöz tartozik.
Dimenziónkénti állapotok az ADR szerint; metadata terv/estimate/decision snapshot,
nem FK-pótlék. Ownership/delivery és QC saját ténytáblája nem duplikált payload.

### 3.21. `repair_commands` [MUTABLE -> committed tény]

```text
command_type:S(64)!; idempotency_key:S(128)!; payload_hash:S(64)!
actor_id:F! -> users; status:S(16)!='processing'
result_type:S(64)?; result_id:U64?; result_snapshot:J?; completed_at:DT?
U(command_type,idempotency_key); IX(actor_id,created_at)
CHECK status IN('processing','completed')
CHECK (status='processing' AND completed_at IS NULL)
 OR (status='completed' AND completed_at IS NOT NULL AND result_snapshot IS NOT NULL)
```

T: processing önállóan nem commitolható sikeres commandként; ezt CHECK nem
garantálja. Failure teljes rollback; hash/actor/auth egyezés nélkül nem adunk
vissza korábbi választ. Result morph csak válasz, a domainbizonyítékot valódi
child-FK-k adják. Sikeres eredmény immutable.

### 3.22. `item_instance_genealogy_events` [EVENT]

```text
parent_item_instance_id:F! -> item_instances
child_item_instance_id:F! -> item_instances
repair_order_id:F! -> repair_orders; repair_operation_id:F? -> repair_operations
event_type:S(16)!; occurred_at:DT!
related_install_event_id:F? -> item_instance_genealogy_events
stock_movement_id:F? -> stock_movements
supersedes_event_id:F? -> item_instance_genealogy_events
performed_by:F! -> users; evidence_document_id:F? -> documents
reason:T!; command_id:F! -> repair_commands; event_sequence:U32!
U(command_id,event_sequence); U(related_install_event_id); U(supersedes_event_id)
IX(parent_item_instance_id,occurred_at); IX(child_item_instance_id,occurred_at)
CHECK parent_item_instance_id<>child_item_instance_id
CHECK event_type IN('install','remove','legacy_install','correction')
CHECK (event_type='remove' AND related_install_event_id IS NOT NULL)
 OR (event_type<>'remove' AND related_install_event_id IS NULL)
```

T: egy install egyszer remove; child/parent sorzár alatt hatályos beépülés
vizsgálat, gráfciklus kizárása. Az FK nem védi önmagában a teljes gráfot vagy
két külön install egyidejűségét. Legacy install csak bizonyítékkal, az eredeti
item_instance_relations megőrzésével. Kiszerelés nem automatikus szabad stock
vagy tulajdonváltás; mozgás és beépülési tény külön fogalom.

### 3.23. `repair_diagnostic_credit_allocations` [MUTABLE, megőrzött történet]

```text
source_repair_order_id:F! -> repair_orders
repair_quotation_id:F! -> repair_quotations; quotation_item_id:F! -> repair_quotation_items
active_source_order_id:F? -> repair_orders
amount_units:M!; currency:S(3)!; money_scale:U32!; state:S(16)!
allocated_at:DT!; released_at:DT?; release_document_id:F? -> documents
command_id:F! -> repair_commands
U(active_source_order_id); U(quotation_item_id); U(command_id)
FK(repair_quotation_id,source_repair_order_id) -> repair_quotations(id,repair_order_id)
FK(quotation_item_id,repair_quotation_id,source_repair_order_id)
 -> repair_quotation_items(id,repair_quotation_id,diagnostic_source_order_id)
IX(source_repair_order_id,allocated_at)
CHECK amount_units>0 AND money_scale BETWEEN 0 AND 6
CHECK (state IN('held','applied') AND active_source_order_id IS NOT NULL
 AND active_source_order_id=source_repair_order_id AND released_at IS NULL
 AND release_document_id IS NULL)
 OR (state='released' AND active_source_order_id IS NULL
 AND released_at IS NOT NULL AND release_document_id IS NOT NULL)
```

Az egyetlen order-pointer nem őriz allokációs történetet. Több draftban lehet
becslés, egyszerre egy held/applied sor lehet a diagnosztikai forrásra. Draft nem
foglal kreditet és nem jogosít fel. Javasolt protokoll: submitkor orderzár+held,
approvalkor applied; kredit allokáció nélkül nem érvényesíthető. Ez technikai
javaslat, a kereskedelmi aktiválási/felszabadítási policy elfogadása kapu.
A forrásdíj igazolt, nem free; összeg/pénznem/skála/credit-line és díjhatár guardolt.
A pénzügyi mezők immutable; state/projekció változása új status_history eseménnyel
atomikus. Released sor nem törölhető vagy aktiválható újra; új quote új sort kap.
Supersede/cancel/reject/expiry önmagában nem oldhat fel applied kreditet.
Held/applied felszabadításának jogosultsági és kereskedelmi feltétele OPEN;
döntésig az érintett út blokkolt. Nincs új adózási/számlázási szabály.

## 4. Meglévő táblák tervezett bővítései

Minden új F mezőre a 3.0 FK/IX-szabály érvényes. A régi C/N akciók erősítése
csak az alábbi táblák kapcsolatait érinti, ellenőrzött cleanup után.

### 4.1. item_instances

Új `current_inventory_scope:S(24)?` default NULL (legacy/unknown),
`last_stock_movement_id:F? -> stock_movements`.
CHECK scope IS NULL OR scope IN('company_stock','customer_custody','external').
IX(current_inventory_scope,current_location_id); U(id,item_id).
A meglévő production_order_id FK-ja production_orders(id), effective RESTRICT,
orphan-cleanup után; nullable marad, de RMA-alkalmasság külön bizonyítást kér.
Serial soha nem regenerálható. NULL scope nem automatikus company_stock,
replacementként nem foglalható. Projection csak valós movementből változik.

### 4.2. stock_movements

```text
from_scope:S(24)?; to_scope:S(24)?  -- expand alatt nullable, contract után NOT NULL
repair_command_id:F? -> repair_commands; command_line:U32?
repair_request_item_id:F? -> repair_request_items
receipt_request_item_id:F? -> repair_request_items
stock_reservation_id:F? -> stock_reservations
reverses_stock_movement_id:F? -> stock_movements
U(repair_command_id,command_line); U(receipt_request_item_id)
U(id,item_instance_id); U(reverses_stock_movement_id)
IX(from_scope,item_id,performed_at); IX(to_scope,item_id,performed_at)
IX(repair_request_item_id,performed_at)
FK(receipt_request_item_id,item_instance_id) -> repair_request_items(id,item_instance_id)
```

Contract CHECK: mindkét scope az elfogadott három érték; legalább az egyik
nem external. Command-pár együtt NULL vagy mindkettő nem-NULL és line>=1.
Receipt típusnál quantity=1, external->customer_custody, serial és member
nem-NULL, receipt_request_item_id nem-NULL és egyenlő repair_request_item_id.
Minden más típusnál receipt_request_item_id NULL. Explicit IS NULL ágakkal.

Új enumérték-jelöltek: repair_receive/repair_transfer/repair_return/repair_scrap/
repair_consume/replacement_dispatch/replacement_return. A source morph továbbra
is UI/üzleti forrás, nem valódi FK és nem scope. Az instance/batch/hely régi
N akciói RESTRICT-re erősítendők, hogy RMA-történet ne nullázódjon törléssel.

Új fizikai mozgás quantity>0, de legacy correction/reservation típusokra ez
nem állítható audit nélkül: típusonkénti CHECK és mapping kell. Nem írunk
át negatív múltbeli korrekciót pozitívra. U(reverses_stock_movement_id) csak az
egyszeri teljes RMA-visszatéréshez használható; részkorrekció külön OPEN terv.
A receipt-pointer és az egyedi receipt-key együtt véd a release utáni új receipt ellen.

### 4.3. stock_reservations

```text
repair_order_id:F? -> repair_orders; reservation_purpose:S(24)?
item_instance_id:F? -> item_instances
active_item_instance_id:F? -> item_instances
active_replacement_order_id:F? -> repair_orders
consumed_quantity:Q!=0; released_quantity:Q!=0
current_replacement_stage:S(24)?
U(active_item_instance_id); U(active_replacement_order_id)
U(id,item_instance_id); U(id,repair_order_id)
IX(repair_order_id,reservation_purpose,status); IX(item_instance_id,reserved_at)
```

Owner CHECK pontosan:
`(repair_order_id IS NOT NULL AND customer_order_item_id IS NULL AND production_order_id IS NULL)
OR (repair_order_id IS NULL AND (customer_order_item_id IS NOT NULL OR production_order_id IS NOT NULL))`.
A legacy vevői+production hivatkozás együtt megmaradhat, ha ugyanazon igényé;
egyezését guard ellenőrzi. Repair és production owner nem keverhető, owner nélküli
sor nincs. Repair purpose kötelező repair_material/replacement, legacy mapping
külön. Releváns C/N FK-k RESTRICT-re váltanak. Hard/soft-delete aktív foglalásra tiltott.

Mennyiségi CHECK: reserved_quantity>0; consumed_quantity>=0;
released_quantity>=0; consumed_quantity+released_quantity<=reserved_quantity.
Fennmaradó aktív lekötés = reserved-consumed-released; minden fogyasztó ezt
összegzi, nem az eredeti teljes reserved értéket.

Aktív és serialos sor: active_item_instance_id nem-NULL, egyenlő item_instance_id;
minden más esetben NULL. CHECK-ek minden NULL-ágat explicit kezelnek.
Replacement purpose esetén serial, hely és repair_order kötelező, mennyiség 1.
Stage IN(reserved,prepared,dispatched,cancellation_pending,handed_over,cancelled).
Nyitott cserefolyamat első négy stage-jén active_replacement_order_id nem-NULL
és egyenlő repair_order_id; terminális stage-en NULL.

Dispatchkor a mennyiségi reservation fulfilled, nem szabad stockként released;
active_item_instance_id NULL lesz, de active_replacement_order_id megmarad az
átadás rendezéséig. Így nincs már kivezetett mennyiség a reserved SUM-ban,
de nincs másik párhuzamos cserefolyamat sem. Úton lévő serial az external
projekció és release alapján minden más allokációból kizárt.

Új reservation helyhez kötött: location=NULL legacy sorok rendezendők rollout
előtt. Preparation transferkor reservation.location és balance atomikusan
változik. A régi közvetlen release szolgáltatás nem kerülheti meg a csere guardjait.

Visszaérkezett csere új kiadásához új mennyiségi reservation kell ugyanazon
order/serial láncában. A korábbi consumed_quantity nem nullázható, a régi sor
nem aktiválható újra; az aktív order-obligation átadása atomikus. A visszaérkezési
stage-ek, karanténblokkolás és új reservation pontos átmenete külön FND-04 kapu;
a jelenlegi stage-javaslat önmagában nem teljes redispatch implementáció.

### 4.4. stock_balances – csak a NULL-safe egyediség miatt

Nincs új custody/owner dimenzió: ez company_stock fizikai projekció.
Szükséges `batch_identity:unsignedBigInteger NOT NULL`, önkényes default nélkül.
CHECK `(item_batch_id IS NULL AND batch_identity=0) OR
(item_batch_id IS NOT NULL AND batch_identity=item_batch_id AND batch_identity>0)`.
U(item_id,location_id,batch_identity) felváltja a nullable batch-es UNIQUE-t.
0 technikai sentinel, nem batch-FK; item_batch_id N helyett RESTRICT.
Duplikátum cleanup mozgásból egyeztetett, nem találomra összeadott készlet.
Quantity>=0 CHECK csak legacy korrekciók validálása után; új fogyasztás már
előtte sem vezethet negatív készletre. Insertversenyt item-sorzár is védi.

### 4.5. material_requirements

Új `repair_order_id:F? -> repair_orders`; customer_order_item_id nullable lesz.
CHECK pontosan egyik nem-NULL. U(customer_order_item_id,required_item_id),
U(repair_order_id,required_item_id); IX(repair_order_id,status).
A nullable UNIQUE itt helyes, mert owner-XOR egy nem-NULL ágat biztosít.
Az owner FK-k RESTRICT, nincs történettörlő cascade.
required/available/reserved/missing mennyiség nemnegatív CHECK.
Egy repair+cikk aggregált demand, uniteltérés conversion nélkül tiltott.
A legacy customer-order demand szemantikát nem alakítjuk át production_order
alapúvá hallgatólagosan. Újraszámítás owner/requirement-zár alatt, auditáltan.

### 4.6. Beszerzési források: két meglévő tábla

`purchase_requisition_item_sources`: új demand_revision:U32!=1,
generation_key:S(128)! a backfill után; U(generation_key),
U(material_requirement_id,demand_revision); CHECK quantity>0 AND demand_revision>=1.
Megmarad U(purchase_requisition_item_id,material_requirement_id).
Source C FK-k RESTRICT-re váltanak. Új source immutable demand-generálási tény.
Revision requirement-zár alatt: létező coverage ugyanarra a hiányra nem új revision.
Valódi többletigény csak új üzleti demandesemény és előző ordered/received coverage
ellenőrzése után; a hiány ismételt számítása önmagában nem jogosít új PR-ra.

`purchase_requisition_items`: közvetlen material_requirement_id pointer N
helyett RESTRICT. RMA esetén sources az autoritatív teljes demandlista; a direkt
pointer csak kompatibilitási reprezentáns, nem helyettesíti a több forrást.
Source item/unit és PR-mennyiség összeg egyezése service-guard. Más új oszlop nem kell.
Egy source törlése nem megengedett módja a duplagenerálási guard feloldásának.

## 5. FK- és egyediségi mátrix

A 3.0 szabály minden konkrét F oszlophoz FK/indexet rendel. Az alábbi a
kritikus összetett garanciák és a DB-n túli ellenőrzések mátrixa.

| Invariáns              | DB-kulcs / FK                                                                                 | Service/lock/idempotencia                           |
| ---------------------- | --------------------------------------------------------------------------------------------- | --------------------------------------------------- |
| Egy tagság/ügy/serial  | members U(repair_request_id,item_instance_id)                                                 | Alkalmasság/owner/open-state                        |
| Egy aktív RMA/serial   | claims PK(item_instance_id), U(repair_request_item_id), FK(member,serial)->members(id,serial) | Minden open tételhez claim és delete csak close-kor |
| Egy owner              | members FK(repair_request_id,owner_customer_id)->requests(id,owner_customer_id)               | Fizikai tulajdon bizonyítéka                        |
| Egy order/tagság       | orders U(repair_request_item_id)                                                              | Minden tételhez tényleg létrejön order              |
| Egyszeri receipt       | movements U(receipt_request_item_id), CHECK és member/serial FK                               | Receipt pointer megőrzés, state és fizikai egyezés  |
| Egyszeri release       | releases U(move), U(command), U(member,serial,role,attempt), U(reservation,attempt)           | Más postingpath kizárása, bizonyíték                |
| Csereallokáció         | reservations U(active_item_instance_id), U(active_replacement_order_id) + CHECK               | Claim/custody/external és minden writer guard       |
| Anyag/QC azonos order  | FK(operation_id,repair_order_id)->operations(id,repair_order_id)                              | Nullable egyéb kapcsolatok egyezése                 |
| Egy nyitott időszakasz | time U(active_assignment_id) + CHECK                                                          | Teljes intervallum-átfedés                          |
| Demand tulajdonos      | requirements két owner+cikk U + XOR                                                           | Nettósítás és unit                                  |
| PR-demandrev egyszer   | sources U(material_requirement_id,demand_revision), U(generation_key)                         | Revision nem léptethető puszta retryval             |
| Command/postingline    | commands U(command_type,idempotency_key); movement U(repair_command_id,command_line)          | Hash/auth, teljes postcondition                     |
| Remove egyszer         | genealogy U(related_install_event_id)                                                         | Ciklus, hatályos beépülés, több szülő               |

Composite parent kulcs mindig valódi UNIQUE/PK, azonos típus/sorrend.
Nem használunk morph-FK-t, COUNT+1 sorszámot vagy MySQL partial indexre épülő
aktívrekord-védelmet. A cross-table állapot és a history immutability nem CHECK.

## 6. Készletmozgási és custody-modell

### 6.1. Postingmátrix

L = a 10. fejezet közös zárprotokollja. Serialos esemény mennyisége 1,
instance/cikk/hely/scope egyezése kötelező.

| Művelet                          | from -> to scope                               | Normál balance    | Custody          | Reservation                        | Serial / lock                              |
| -------------------------------- | ---------------------------------------------- | ----------------- | ---------------- | ---------------------------------- | ------------------------------------------ |
| Receipt                          | external -> customer_custody                   | 0                 | insert           | 0                                  | Tagság/claim/receipt U; L                  |
| Eredeti belső transfer           | customer_custody -> customer_custody           | 0                 | helyfrissítés    | 0                                  | Kiinduló hely egyezik; L                   |
| Eredeti release                  | customer_custody -> external                   | 0                 | projekció delete | 0                                  | Release U/proof; L                         |
| Felhatalmazott scrap             | customer_custody -> external                   | 0                 | projekció delete | 0                                  | Scrap auth/proof; L                        |
| Anyagconsume                     | company_stock -> external                      | -q                | 0                | tényleges consumed rész            | Batch/serial/order/net stock; L            |
| Replacement reserve              | nincs movement                                 | 0                 | 0                | +1 aktív                           | Company, saját gyártás és szabad serial; L |
| Prepare másik helyre             | company_stock -> company_stock                 | forrás -1, cél +1 | 0                | helye együtt változik              | Ugyanaz a serial/foglalás; L               |
| Prepare azonos helyen            | nincs movement                                 | 0                 | 0                | változatlan                        | History+ellenőrzés; L                      |
| Replacement dispatch             | company_stock -> external                      | -1 egyszer        | 0                | fulfilled, nem szabadként released | Release U, policy/proof/QC; L              |
| Delivery confirm                 | nincs movement                                 | 0                 | 0                | obligation rendezés                | Release child-event/event_key; L           |
| Ownership transfer               | nincs movement                                 | 0                 | 0                | 0                                  | Külön feltétel+proof+unique; L             |
| Cancel preparation               | szükség szerint company_stock -> company_stock | összesen 0        | 0                | ellenőrzés után felold             | Hely/állapot/alkalmasság; L                |
| Valódi vállalati shipment-return | external -> company_stock                      | +1 egyszer        | 0                | nincs automatikus új foglalás      | Valódi return+ownership+előző dispatch; L  |

Ismeretlen tulajdonú vagy hibás visszaérkezés nem szabad company balance;
rendezési modelljét FND-04 jelöli. Félbemaradt workflowhoz nincs fake correction.

### 6.2. Egyeztetés és MRP

Normál balance = company_stock oldalra érkezés mínusz onnan kivezetés.
Custody = serialos customer-custody receipt/transfer/release/scrap projekció.
Közös movement nem közös szabadkészlet-összeg. External nem ownership-jelölés.
Vállalati tulajdonú transit külön riport, availabilityhez nem adható hozzá.

Available = normal physical - összes aktív fennmaradó reservation. Saját
reservationt az order netting egyszer veszi figyelembe, nem dupla rendelkezésre
állásként. Serialos stocknál balance és bizonyított in-stock példányok
egyeztetendők; az aggregátum nem bizonyítja a kiválasztott serial elérhetőségét.
Eltérés blokkol, nem generál automatikus mennyiségi helyreállítást.

Tervezett posting service repositorykat koordinál; a scope dönti el a projekció
hatását. Nem külön observer írja később az egyenleget. Átállítandó minden writer/
reader: StockBalanceRepository, StockReservationRepository,
MaterialRequirementService, ProductionTaskMaterialService, GoodsReceiptService,
forecast és reporting. Cache-invalidálás a meglévő afterCommit mintával.

## 7. Csere foglalási, kiadási és visszavonási modell

Eredeti identity a request_item-é, replacement identity a megőrzött reservationé,
parent orderrel összekötve. Mindkét serialnak külön gyártása és warranty-historyja
van; az összekötés nem másolja vagy nevezi át az eredeti példányt.

1. Reserve: eligibility+reconciliation; serial/balance zár és aktív UNIQUE.
   Owner company, balance 0 változás, más cél tiltott.
2. Prepare: valódi transfer+reservationhely együtt; azonos helyen csak history.
   Nem customer-custody, nem ownership.
3. Dispatch: egyedi release/outbound, fulfilled reservation, projekció+audit
   atomikus; fizikai company stock -1 egyszer, transit serial nem allokálható.
4. Confirm: új delivery event; nem outbound. Recipient, idő, referencia,
   condition és proof kötelező; késői webhook deduplikált.
5. Ownership: külön igazolt contract-trigger; akár ugyanakkor, de külön esemény.
   A jogi feltételt e dokumentum nem találja ki.
6. Cancel before release: jogosult fizikai hely/condition/unrestricted-eligibility
   ellenőrzés; szükség szerint valós transfer vissza, foglalás feloldása atomikus.
   Hiba: cancellation_pending és élő lekötés.
7. After release: outbound nem törölhető. Recall+valódi return+ownership-check,
   egyszeri inbound; unresolved állapot nem szabad stock. Újraküldés csak a 3.16 felhatalmazott új attemptje; ismeretlen tulajdon és
   quarantine kezelése FND-04 kapu; nincs mesterséges stock adjustment.

Eredeti termék független return/scrap dispositionje mindvégig kötelező.
Csere lefoglalása vagy kézbesítése nem zárja automatikusan az eredeti custodyt.

## 8. Garancia- és ajánlati integritás

Manuális, jogosult assessment, bizonyítékhiánynál pending. Igazolt átadás és
cikkszabály/feltétel snapshot; rendelésdátum nem bizonyíték. D4 szerint csere
nem resetel automatikusan warranty-t; serialonként megőrzött, indokolt döntés.

Version orderzár alatt, submitted quote tartalma+tételei+hash immutable.
Új scope új verzió, régi estimate/approval megmarad; új draft a régi approval
további executionjét felfüggeszti. Customer approval konkrét verzió/proof,
nem belső jóváhagyás helyettesítő vélelme. Lejáratot startkor is ellenőrizzük.

Free/fixed/time_based diagnosztika; actual díj csak hatályos lezárt időből,
előre igazolt hozzájárulással és díjpolicyval. M integer units; money scale,
rounding, adó és óradíj-időegység OPEN. Nem állítjuk jóváhagyott üzleti árazásnak.
Credit becslés több draftban lehet; egyetlen aktív allokáció a 3.23 szerint,
orderzár alatt és igazolt díjig. Felszabadítás csak külön jóváhagyott feltételekkel
és historyval, nem source törlésével.

Dokumentumhoz konkrét record/verzió/checksum kötődik. FK nem véd soft delete vagy
fájlcsere ellen; célügy-policy+retention guard szükséges generic documents jog mellett.

## 9. Végrehajtás és genealógia

Employee/OperationType/FactoryUnit újrahasználható, ProductionTaskService nem.
Próbálkozás a munkalap száma, operation/inspection tényeknél megőrizve.
Új attempt csak hibás QC után és újraellenőrzött kerettel; nincs automatikus limit.

El nem kezdett terv jogosult superseding sorral változhat, korábbi döntés/estimate
megmarad. Started/completed adatok nem írhatók át; új scope approvalra várhat,
új munka/fogyasztás addig tiltott. Futó munka csak dokumentált biztonságos
megállításig zárható. Sequence UNIQUE nem bizonyítja a műveleti sorrend betartását.

Tényleges idő dolgozónkénti intervallum, nem estimate/elapsed. Anyag és QC
order/attempt összetartozása guardolt. Existing item_instance_relations nem
mondja meg a kiszerelés idejét/okát: az append-only install/remove ledger tartja
meg a cserét. Child/parent zár és gráfbejárás védi a ciklust/több aktív installt.
Ismeretlen legacy állapothoz nem készül hamis history: bizonyítékos legacy_install kell.

## 10. Tranzakciók és konkurenciavédelem

### 10.1. Közös zárprotokoll

Service nyit DB-tranzakciót. Minden RMA-gyermekművelet az ügyet is zárolja.
Sorrend: ügy ID -> érintett items ID -> item_instances ID -> order ID ->
operation/assignment -> requirement ID -> reservation ID -> balance ID ->
release/proof. Több érintett sor minden kategórián belül növekvő ID szerint.

Az items létező sor közös mutex serial nélküli mennyiségnél is: hiányzó balance
vagy reservation nem zárolható létező sorként. Minden writer, a production is,
ugyanezt a protokollt követi. Nem SUM-eredményt zárolunk: itemzár után a
konkrét sorhalmaz locking readje és nettó újraszámítás történik.
Eltérő protokollú production consume mellett az RMA nem vezethető be biztonságosan.

### 10.2. Atomikus üzleti parancsok

| Parancs               | Egy tranzakcióban                                                                                                                                                                       | Failure / idempotencia                               |
| --------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------- |
| Nyitás/tételfelvétel  | Auth, owner/origin/serial, teljes tagság/order/claim, history, audit, command                                                                                                           | Egy hibás tétel minden új tételt rollbackel          |
| Receipt               | Tagság/state/prior receipt, unique movement, pointer/custody/instance projection, audit                                                                                                 | Új commandkulccsal sem második receipt               |
| Reserve/prepare       | Nettó elérhetőség, global serial guard, reservation/active keys, valódi transfer és hely, history/audit                                                                                 | Nincs átmenetileg szabad célhelyi balance            |
| Consume               | Más lekötések, saját fennmaradó rész, material+movement+projection+részfoglalás+audit                                                                                                   | Nincs negatív stock vagy idegen foglalásfogyasztás   |
| QC/tervváltás         | Aktuális attempt/quote, inspection vagy utódterv, scope-gate, history/audit                                                                                                             | Stale QC nem léptet; approvalra váró munka nem indul |
| Dispatch/scrap/return | Policy/proof/QC/auth, valós movement, release/projekció/foglalás/dispositionfeltételek, audit                                                                                           | Egyszeri levonás, nincs fél-őrzéslezárás             |
| Delivery/ownership    | Release/serial/proof és unique trigger/event, history/audit                                                                                                                             | Balance-hatás nincs                                  |
| Ügyzárás              | Minden tétel disposition, original custody, movement/proof, replacement foglalás/preparation/delivery/ownership, operation/time/material, auth/QC vizsgálata; close+claims delete+audit | Status önmagában nem elég; hiánynál teljes rollback  |

Ügyzárással versenyző gyermekinsert is ügylockot kér. Így nincs close-ellenőrzés
alatt új foglalás vagy munka. Relevant proofhoz RESTRICT+retention/soft-delete
guard szükséges, más domain sem törölheti ki közben a bizonyítékot.

Command unique insert az üzleti tranzakció elején; duplicate request várakozás
vagy konfliktus után csak completed eredményt olvas újra, azonos hash és
engedélyezett actor esetén. Domain-guard másik kulcsú ismétlést is tilt.
Deadlock/lock-timeout: teljes rollback és korlátozott, idempotens retry;
nem egy részszolgáltatás ismétlése. Audit/projection/movement hiba is rollback.

Külső hálózat/fájlmunka nem nyitott tranzakcióban. Proof előre ellenőrzött,
utólagos webhook külön deduplikált tény. Fuvarcímke vagy API-siker nem igazolt
fizikai dispatch. Mellékhatás commit után, megbízható retryval; outbox módja OPEN.

## 11. MySQL/SQLite kompatibilitás

A motorjellemzők hivatalos dokumentációval ellenőrzöttek. Nem állítjuk,
hogy a teljes jövőbeli DDL már futott volna. A B review MySQL 8.4.7 és SQLite
3.53.4 részprototípusokat futtatott; ez nem migrációs vagy service-teszt.

| Téma               | Következmény                                                                                        | Kötelező bizonyítás                                                            |
| ------------------ | --------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------ |
| MySQL locking read | FOR UPDATE valódi transactionben; normál SELECT nem azonos védelem                                  | Két kapcsolat és szinkron barrier; várakozás/commit/rollback                   |
| Isolation/gap lock | REPEATABLE READ snapshot és current read eltér; range lock isolationfüggő                           | Döntés current locking readből; isolation rögzítése                            |
| SQLite             | Egy writer, nincs MySQL row-lock garancia; DEFERRED upgrade BUSY lehet                              | Feature constraint/guardteszt, nem production concurrency bizonyíték           |
| Composite FK       | Pontos parent UNIQUE/PK, azonos típus/sorrend; rész-NULL child tuple megkerülheti                   | Raw invalid insert/update és rész-NULL eset                                    |
| Nullable UNIQUE    | Több NULL szándékos az inactive keynél, balance-nál rés                                             | Két aktív key tiltott, több inactive NULL engedett, batch_identity nem-NULL    |
| CHECK              | NULL nem elutasítás; MySQL nem enged másik táblát/subqueryt/auto-increment PK-t és ütköző FK-akciót | Explicit NULL ágak, teljes CHECK+FK DDL mindkét motoron                        |
| Pénz/DECIMAL       | SQLite numeric affinity nem MySQL exact DECIMAL-bizonyíték                                          | Integer money, service exact-aritmetika; overflow/rounding/quantity regresszió |
| Collation          | MySQL default case-insensitive, SQLite eltérhet                                                     | Hash/idempotenciakulcs ASCII-binary; serial-normalizáció külön adatpolicy      |
| DDL                | MySQL implicit commit/metadata lock; SQLite ALTER table rebuild lehet                               | Expand/backfill/contract, forward/rollback mindkét motoron                     |

Források: [MySQL locking reads](https://dev.mysql.com/doc/refman/8.4/en/innodb-locking-reads.html),
[isolation](https://dev.mysql.com/doc/refman/8.4/en/innodb-transaction-isolation-levels.html),
[deadlocks](https://dev.mysql.com/doc/refman/8.4/en/innodb-deadlocks-handling.html),
[MySQL FK](https://dev.mysql.com/doc/refman/8.4/en/create-table-foreign-keys.html),
[MySQL CHECK](https://dev.mysql.com/doc/refman/8.4/en/create-table-check-constraints.html),
[SQLite FK](https://www.sqlite.org/foreignkeys.html),
[SQLite constraints](https://www.sqlite.org/lang_createtable.html),
[SQLite transactions](https://www.sqlite.org/lang_transaction.html).

MySQL-integrációs esetek, dedikált guardolt adatbázison:

- C1: két ügy egyszerre ugyanarra a serialra; egy claim marad, hibás tuple is tiltott.
- C2: párhuzamos receipt; pontosan egy bejövő mozgás és custody.
- C3: külön commandkulcsú dupla release; pontosan egy outbound/levonás.
- C4: két replacement reserve, illetve production consume ugyanarra a serialra.
- C5: két anyagconsume együtt elérhető fölött; nincs negatív fizikai vagy szabad stock.
- C6: close kontra gyermekinsert/reservation/time-start; nincs lezárás után nyitott kötelezettség.
- C7: quoteváltás/expiry/approval kontra execution start; stale fedezet tiltott.
- C8: ugyanaz a demand két PR-generation parancsban; egy coverage marad.
- C9: child párhuzamos beépítése két parentbe; egy hatályos install.
- C10: assignment két párhuzamos időszakasznyitása; egy open interval.

Minden teszt két valódi kapcsolat/tranzakció barrierrel, nem szekvenciális két hívás.
Az állapot mellett a mennyiséget, mozgásszámot, történetet és auditot is assertálni
kell. Deadlock retry külön negatív ág. SQLite siker nem helyettesíti ezt.

## 12. Migrációs függőségi és rollback-terv

| Csoport                   | Előfeltétel/sorrend                                                                                                           | Kompatibilitás és rollback-korlát                                                  | Ellenőrzés                                             |
| ------------------------- | ----------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------- | ------------------------------------------------------ |
| M0 read-only audit        | Motorverzió/isolation/collation; orphan origin, nullable-batch duplikáció, owner nélküli/negatív stock, legacy mozgásjelentés | Nincs write; eltérés külön engedélyezett rendezés                                  | Egyeztetési baseline                                   |
| M1 független alap         | commands, warranty_rules, requests, members receipt-FK nélkül, orders (credit-pointer nélkül)                                 | Üres táblák rollbackelhetők; üzleti history után drop nem rollback                 | FK/UNIQUE smoke                                        |
| M2 domain gyerekek        | claims, operations, assignments, time, quote+items, handover release-FK nélkül, assessment, inspection, credit_allocations    | Immediate FK-ra tervezve; circular nullable hivatkozás később                      | Parent UNIQUE és tupletesztek                          |
| M3 shared expand          | Instance scope, reservation owner/serial, requirement owner, balance key, source revision                                     | Nullable átmenet, RMA feature tiltva; régi kód regressziója                        | Production/inventory/procurement suite                 |
| M4 inventory scope        | Movement scope/key/line, régi típusok ellenőrzött mappingje és backfill                                                       | Ismeretlen jelentés blokkol; új custody után régi scope-vak olvasó nem használható | Scope-rekonstrukció, custody nem normal stock          |
| M5 posting gyerekek       | Materials, custody, release, delivery, ownership, disposition, history, genealogy; majd receipt/handover-reverse FK-k         | FK-ciklus két lépésben; nem FK-check kikapcsolással                                | SQLite rebuild és MySQL DDL                            |
| M6 cleanup utáni contract | Balance NULL-safe UNIQUE, owner-XOR, source-generation UNIQUE, origin FK, szükséges NOT NULL/CHECK                            | Adatütközés mellett nem indul; alkalmazásrollback csak kompatibilis verzióra       | Invalid raw INSERT/UPDATE tiltott mindkét motoron      |
| M7 index/query/rollout    | Minden writer/reader átállítva, explicit IX-ek; idempotencia/proof/closure                                                    | Új történetet nem törlünk rollbackként; forward fix szükséges lehet                | Full+MySQL migration gate, C1–C10, consumer regresszió |

Circular függőség: members->movement->members. Handover->release és annak
proof-dokumentumai miatt a létrehozási sorrend külön ellenőrzendő; a handover
release-FK később vehető fel. Quote/allocation nem kör:
order, quote, quote-item, allocation sorrend kell (order credit-pointer már nincs); a movement morph source nem valódi visszairányú FK.
Táblák előbb, ciklikus nullable FK-k később. Nem feltételezünk MySQL deferred FK-t.

Indexek kötelező integritási kulcsok és konkrét queryk szerint. További covering
index csak méréssel; nem állítunk adathalmaz nélküli teljesítménygaranciát.
Új domainhasználat után rollback nem lehet audit/genealógia/proof eldobása.

## 13. Elfogadási tesztmátrix

PROPOSED követelmények, nem kész/futtatott tesztek. DB=3–5. fejezet konkrét
kulcsai. C1–C10 a 11. fejezet MySQL-versenyesetei.

| Döntés     | Táblák/DB-korlát                                   | Service-invariáns                           | Pozitív                                   | Negatív                                               | MySQL       |
| ---------- | -------------------------------------------------- | ------------------------------------------- | ----------------------------------------- | ----------------------------------------------------- | ----------- |
| RMA-BD-001 | requests, members U(case,serial), orders U(member) | Több külön lifecycle, közös owner           | Két serial eltérő állapot                 | Egy hibás tétel nem hagy fél ügyet                    | C6          |
| RMA-BD-002 | members origin FK, instance serial U               | Bizonyított saját gyártás+D2                | Komponens és késztermék                   | Purchased/üres/ismeretlen/orphan serial tiltott       | C1          |
| RMA-BD-003 | owner/creator/reporter FK                          | Aktív belső dolgozó, permission             | Külső reporter, más owner                 | Employee/permission nélküli rögzítés tiltott          | Parent lock |
| RMA-BD-004 | claims PK(serial), member/serial FK                | Claim close-ig él                           | Előző close után új RMA                   | Két aktív vagy rossz tuple tiltott                    | C1/C6       |
| RMA-BD-005 | assessment U(order,version), enum/override CHECK   | Manuális döntés+indok/auth                  | Három besorolás és override               | Indok/permission nélkül tiltott                       | C7          |
| RMA-BD-006 | quote U(order,version), response proof             | Aktuális approved fedezet startkor          | Approved paid start                       | Draft/rejected/expired/proof nélküli tiltott          | C7          |
| RMA-BD-007 | custody serial U, movement scopes                  | Ügyféltulajdon külön stock                  | Receipt balance 0                         | Normal balance-növelés tiltott                        | C2          |
| RMA-BD-008 | disposition U(member), proof FK/CHECK              | Original outcome és csere külön             | Return/scrap/replacement, pending nyitott | Scrap proof nélkül vagy old item sorsa nélkül tiltott | C3/C6       |
| RMA-BD-009 | operation attempt U, inspection event              | Új attempt és változatlan előzmény          | Több rework ciklus                        | Régi QC nem fogad el új attemptet                     | QC-verseny  |
| RMA-BD-010 | assignment U, time active U, material U(move)      | Több dolgozó, actual intervallum            | Két dolgozó, szünet/folytatás             | Átfedés/nyitott idő melletti finish tiltott           | C10/C5      |
| RMA-BD-011 | disposition/event/claim FK-k                       | Minden proof és obligation rendezett        | Teljes ügyclose                           | Status-only vagy hiányzó proof tiltott                | C6          |
| RMA-BD-012 | rule U(item,version), handover/assessment FK       | Igazolt átadás+snapshot, missing pending    | Határnap és későbbi szabály               | Rendelésdátum nem warranty proof                      | C7          |
| RMA-BD-013 | quote/item U, response proof FK                    | Submitted freeze, külső customer evidence   | Submit/approve/reject/expiry              | Tartalomátírás, belső vélelem tiltott                 | C7          |
| RMA-BD-014 | order fee/allocation, quote credit                 | Free/fixed/time, egyszeri credit díjig      | Időalapú díj és egy credit                | Dupla/túlzott/free credit tiltott                     | Order lock  |
| RMA-BD-015 | serial/event IX-ek                                 | Mindkét serial history elérhető             | Új ügy kiemeli előzményt                  | Claim delete nem rejtheti el múltját                  | FK deletion |
| RMA-BD-016 | movement receipt U/composite FK, custody U         | Tagság/state/korábbi receipt                | Egy helyes átvétel                        | Release utáni második receipt is tiltott              | C2          |
| RMA-BD-017 | company-only balance, scope/reservation            | Sale/production/MRP izoláció                | Custody available 0                       | Foglalás/consume/netting tiltott                      | C4/C5       |
| RMA-BD-018 | material/reservation/requirement owner, source U   | Normál stock, más igény védett              | Részconsume és hiányból PR                | Dupla demand/idegen order/custody consume tiltott     | C5/C8       |
| RMA-BD-019 | release U(move), delivery/ownership proof          | Recipient/date/ref/condition és custody end | Valódi release, késői proof               | Dupla outbound/hiányzó proof tiltott                  | C3          |
| RMA-BD-020 | active serial/order U, release/event U             | Reserve/prepare/dispatch/handover külön     | Prepare 0, dispatch -1, confirm 0         | Orphan/fake correction/preparation ownership tiltott  | C3/C4/C6    |

D1–D5: három scope rekonstrukció; semi_finished_product bizonyított eredettel
pozitív és típus önmagában negatív; eltérő owner tagság tiltott; nincs automatikus
cseregarancia-reset; külön physical/ownership tény azonos és eltérő igazolt idővel.
F1–F4 engedett és tiltott útjai service és HTTP/policy rétegben is szükségesek.

Regresszió: existing inventory+NULL batch; production consume más lekötésekkel;
MRP owner/cikk netting és cache; PR-source grouping/idempotencia; serialgenealógia;
eredeti QC; dokumentum target-permission/direct download/soft-deleted evidence.
Audit/movement/projection/proof íráshiba esetén teljes rollback. Migrációs smoke
mindkét motoron, guardolt tesztadatbázissal. Alkalmazásteszt még nincs; a B review izolált SQL-prototípusai külön bizonyítékok.

## 14. Findingok és nyitott technikai döntések

| Azonosító | Finding / döntés                                                                                     | Kapu és következmény                                                                        |
| --------- | ---------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------- |
| FND-01    | Claim FK véd serialegyezést, de nem minden open tag claimjének létezését vagy megengedett delete-jét | Nem ADR-ellentmondás: DB+parentzáras service együtt; nyitás/close implementáció előtt       |
| FND-02    | Custody U nem receipt-history U; balance NULL-batch U rés                                            | Receipt-key és batch_identity kiegészítés; legacy+DDL inventory előtt                       |
| FND-03    | Subject-item és evidence/rule composite FK-k, global order-version, serialszűrt current              | B review: tervszinten feloldott; order membership/policy alkalmazásteszt még kapu           |
| FND-04    | Release attempt+previous+authorization, return observation/reconciliation külön                      | B review: részben igazolt; quarantine/unknown owner és reservation-átmenet rollout előtt    |
| FND-05    | Order-pointer helyett 3.23 tartós allocation és aktív source-key                                     | B review: uniqueness igazolt; activation/release kereskedelmi policy OPEN                   |
| FND-06    | Legacy demand kulcsa customer_order_item+cikk, nem production_order                                  | Nem alakítjuk át hallgatólagosan; több-PO aggregáció és MRP regresszió                      |
| FND-07    | Részleges MySQL/SQLite SQL-prototípusok sikeresek; CHECK/FK action eltérés igazolt                   | B review: teljes Laravel DDL, SQLite rebuild, collation/decimal és migrációs gate hátravan  |
| O-01      | Money scale/rounding/adó, díj és időegység                                                           | M integer modell csak javaslat; pénzügyi fázis előtt explicit jóváhagyás                    |
| O-02      | Handover/ownership policy trigger, proof, cseregarancia konkrét alkalmazása                          | Igazolt konfiguráció kell; ismeretlen policyval nincs dispatch; nincs jogi vélelem          |
| O-03      | Immutable tények DB UPDATE/DELETE hardeningje                                                        | Service-védelem nem DB-garancia; trigger vagy elkülönített írásjog döntése production előtt |
| O-04      | Legacy origin/outbound, balance/serial és owner/source adatok                                        | Read-only adataudit, külön engedélyezett backfill; nincs automatikus átminősítés            |
| O-05      | Proof retention/soft delete/fájlcserék; commit utáni mellékhatás                                     | Dokumentum/dispatch implementáció előtt policy és megbízható retry/outbox döntés            |
| O-06      | Employee globális időátfedés, QC-szerepszétválasztás, scope materiality                              | Nem találunk ki új üzleti szabályt; explicit pontosítás execution előtt                     |

FND-03–05 felülvizsgálatát a [B review](rma-0003b-integrity-verification.md) rögzíti;
a technikai korrekció nem Accepted ADR-módosítás.
Az RMA-BD-001–020 és D1–D5 nem lett felülírva. Ha más credit/warranty/re-dispatch
szemantika szükséges, annak döntése megelőzi az implementációt.

## 15. Implementációs készültség és átadás

**Review-ra kész:** 23 új tábla konkrét oszlop/null/default/FK/U/CHECK/IX,
immutability és transaction szerep; 7 shared módosítás, M0–M7 függőségek,
20-as elfogadási mátrix. Nem másoljuk a normál készletet repair balance táblába.

**Implementációra még nem jóváhagyott:** findingok, motor-DDL, legacy audit,
money/contract policy és hardening nem bizonyított. Nem „migration ready”,
nem teljes alkalmazástesztsiker és nem production readiness. A következő lépés a B review szerint
a findingok és konkrét DDL/adatkapuk feloldása külön engedélyezett feladatban.

Dokumentációs DoD: táblaleltár és 20-as coverage, helyi hivatkozások, Markdown,
actual-file whitespace, git diff --check, végső Git-státusz és ADR-változatlanság
ellenőrzése. Teljes alkalmazásruntime/build/PHPStan/migrációs teszt N/A;
a B review külön izolált PHP/SQL-verifikációt futtatott.
Nincs commit, push, merge, dependency vagy alkalmazásmódosítás. A B review
eldobható teszttábláin kívül nincs adatbázis-írás.

Kapcsolódó követelmények:
[Definition of Done](../../docs/project-management/definition-of-done.md),
[layered quality gates](../../docs/development/quality-gates.md),
[Pest setup](../../tests/Pest.php), [test config](../../phpunit.xml).

## Függelék – VERIFIED migrációs definíciók

Az alábbiak a baseline valódi up-definíciói; nem javasolt vagy generált migrációk.

### 2026_06_21_000007_create_items_table.php

[Forrás](../../database/migrations/2026_06_21_000007_create_items_table.php)

```php
public function up(): void
    {
        Schema::create('items', function (Blueprint $table): void {
            $table->id();
            $table->string('item_number')->unique();
            $table->string('name');
            $table->string('item_type')->default(ItemType::PurchasedMaterial->value)->index();
            $table->string('unit');
            $table->decimal('width', 12, 3)->nullable();
            $table->decimal('length', 12, 3)->nullable();
            $table->decimal('thickness', 12, 3)->nullable();
            $table->decimal('diameter', 12, 3)->nullable();
            $table->boolean('requires_serial_number')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
        });
    }
```

### 2026_06_21_000010_create_item_instances_table.php

[Forrás](../../database/migrations/2026_06_21_000010_create_item_instances_table.php)

```php
public function up(): void
    {
        Schema::create('item_instances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->string('serial_number')->unique();
            $table->foreignId('factory_unit_id')->constrained()->restrictOnDelete();
            $table->foreignId('current_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('current_status')->default(ItemInstanceStatus::Planned->value)->index();
            $table->unsignedBigInteger('production_order_id')->nullable()->index();
            $table->timestamps();
        });
    }
```

### 2026_06_21_000011_create_item_instance_relations_table.php

[Forrás](../../database/migrations/2026_06_21_000011_create_item_instance_relations_table.php)

```php
public function up(): void
    {
        Schema::create('item_instance_relations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_item_instance_id')->constrained('item_instances')->cascadeOnDelete();
            $table->foreignId('child_item_instance_id')->constrained('item_instances')->restrictOnDelete();
            $table->decimal('quantity', 12, 3)->default(1);
            $table->timestamps();

            $table->unique(
                ['parent_item_instance_id', 'child_item_instance_id'],
                'item_instance_relation_unique',
            );
        });
    }
```

### 2026_06_21_000021_create_production_orders_table.php

[Forrás](../../database/migrations/2026_06_21_000021_create_production_orders_table.php)

```php
public function up(): void
    {
        Schema::create('production_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('production_plan_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_order_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('bom_id')->constrained()->restrictOnDelete();
            $table->foreignId('operation_sequence_id')->constrained()->restrictOnDelete();
            $table->string('order_number')->unique();
            $table->decimal('quantity', 12, 3);
            $table->string('status')->default(ProductionOrderStatus::Planned->value)->index();
            $table->date('planned_start_date')->nullable()->index();
            $table->date('planned_finish_date')->nullable()->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('production_plan_item_id');
            $table->index('customer_order_item_id');
            $table->index('item_id');
            $table->index('bom_id');
            $table->index('operation_sequence_id');
        });
    }
```

### 2026_06_21_000032_create_production_tasks_table.php

[Forrás](../../database/migrations/2026_06_21_000032_create_production_tasks_table.php)

```php
public function up(): void
    {
        Schema::create('production_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('production_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_instance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('operation_sequence_step_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->string('status')->default(ProductionTaskStatus::Planned->value)->index();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('finished_at')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['item_instance_id', 'operation_sequence_step_id'], 'production_tasks_instance_step_unique');
            $table->index('production_order_id');
            $table->index('item_instance_id');
            $table->index('operation_sequence_step_id');
            $table->index('employee_id');
        });
    }
```

### 2026_06_21_000034_create_quality_checks_table.php

[Forrás](../../database/migrations/2026_06_21_000034_create_quality_checks_table.php)

```php
public function up(): void
    {
        Schema::create('quality_checks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('production_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('checked_by')->constrained('employees')->restrictOnDelete();
            $table->string('result')->default(QualityCheckResult::Accepted->value)->index();
            $table->timestamp('checked_at')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('production_task_id');
            $table->index('checked_by');
        });
    }
```

### 2026_06_21_000014_create_operation_types_table.php

[Forrás](../../database/migrations/2026_06_21_000014_create_operation_types_table.php)

```php
public function up(): void
    {
        Schema::create('operation_types', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name')->index();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }
```

### 2026_06_21_000015_create_operation_sequences_table.php

[Forrás](../../database/migrations/2026_06_21_000015_create_operation_sequences_table.php)

```php
public function up(): void
    {
        Schema::create('operation_sequences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['item_id', 'version'], 'op_sequences_item_version_unique');
        });
    }
```

### 2026_06_21_000016_create_operation_sequence_steps_table.php

[Forrás](../../database/migrations/2026_06_21_000016_create_operation_sequence_steps_table.php)

```php
public function up(): void
    {
        Schema::create('operation_sequence_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('operation_sequence_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('step_order')->index();
            $table->foreignId('operation_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('factory_unit_id')->constrained()->restrictOnDelete();
            $table->foreignId('professional_role_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('estimated_duration_minutes');
            $table->boolean('requires_quality_check')->default(false)->index();
            $table->text('instructions')->nullable();
            $table->timestamps();

            $table->unique(['operation_sequence_id', 'step_order'], 'op_sequence_steps_order_unique');
        });
    }
```

### 2026_06_21_000023_create_stock_balances_table.php

[Forrás](../../database/migrations/2026_06_21_000023_create_stock_balances_table.php)

```php
public function up(): void
    {
        Schema::create('stock_balances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->foreignId('item_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity', 12, 3)->default(0);
            $table->timestamps();

            $table->unique(['item_id', 'location_id', 'item_batch_id'], 'stock_balances_unique');
            $table->index('item_id');
            $table->index('location_id');
            $table->index('item_batch_id');
        });
    }
```

### 2026_06_21_000025_create_stock_movements_table.php

[Forrás](../../database/migrations/2026_06_21_000025_create_stock_movements_table.php)

```php
public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('item_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('item_instance_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('from_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('to_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->string('movement_type')->default(StockMovementType::Correction->value)->index();
            $table->nullableMorphs('source', 'stock_movements_source_index');
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('performed_at')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('item_id');
            $table->index('item_batch_id');
            $table->index('item_instance_id');
            $table->index('from_location_id');
            $table->index('to_location_id');
        });
    }
```

### 2026_06_21_000024_create_stock_reservations_table.php

[Forrás](../../database/migrations/2026_06_21_000024_create_stock_reservations_table.php)

```php
public function up(): void
    {
        Schema::create('stock_reservations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('item_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_order_item_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('production_order_id')->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('reserved_quantity', 12, 3);
            $table->string('status')->default(StockReservationStatus::Active->value)->index();
            $table->foreignId('reserved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reserved_at')->nullable()->index();
            $table->timestamp('released_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('item_id');
            $table->index('location_id');
            $table->index('item_batch_id');
            $table->index('customer_order_item_id');
            $table->index('production_order_id');
        });
    }
```

### 2026_06_21_000022_create_material_requirements_table.php

[Forrás](../../database/migrations/2026_06_21_000022_create_material_requirements_table.php)

```php
public function up(): void
    {
        Schema::create('material_requirements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('required_item_id')->constrained('items')->restrictOnDelete();
            $table->decimal('required_quantity', 12, 3);
            $table->decimal('available_quantity', 12, 3)->default(0);
            $table->decimal('reserved_quantity', 12, 3)->default(0);
            $table->decimal('missing_quantity', 12, 3)->default(0);
            $table->string('unit');
            $table->string('status')->default(MaterialRequirementStatus::Calculated->value)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_order_item_id');
            $table->index('required_item_id');
        });
    }
```

### 2026_06_21_000002_create_locations_table.php

[Forrás](../../database/migrations/2026_06_21_000002_create_locations_table.php)

```php
public function up(): void
    {
        Schema::create('locations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('factory_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('location_type')->default(LocationType::Warehouse->value)->index();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }
```

### 2026_06_21_000008_create_item_batches_table.php

[Forrás](../../database/migrations/2026_06_21_000008_create_item_batches_table.php)

```php
public function up(): void
    {
        Schema::create('item_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->string('batch_number');
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->date('received_at')->nullable()->index();
            $table->date('expires_at')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['item_id', 'batch_number']);
        });
    }
```

### 2026_06_21_000026_create_purchase_requisitions_table.php

[Forrás](../../database/migrations/2026_06_21_000026_create_purchase_requisitions_table.php)

```php
public function up(): void
    {
        Schema::create('purchase_requisitions', function (Blueprint $table): void {
            $table->id();
            $table->string('requisition_number')->unique();
            $table->string('status')->default(PurchaseRequisitionStatus::Draft->value)->index();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
```

### 2026_06_21_000027_create_purchase_requisition_items_table.php

[Forrás](../../database/migrations/2026_06_21_000027_create_purchase_requisition_items_table.php)

```php
public function up(): void
    {
        Schema::create('purchase_requisition_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_requisition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_requirement_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->string('unit');
            $table->string('status')->default(PurchaseRequisitionItemStatus::Draft->value)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('purchase_requisition_id');
            $table->index('material_requirement_id');
            $table->index('item_id');
        });
    }
```

### 2026_06_24_000002_create_purchase_requisition_item_sources_table.php

[Forrás](../../database/migrations/2026_06_24_000002_create_purchase_requisition_item_sources_table.php)

```php
public function up(): void
    {
        Schema::create('purchase_requisition_item_sources', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('purchase_requisition_item_id');
            $table->unsignedBigInteger('material_requirement_id');
            $table->decimal('quantity', 12, 3);
            $table->timestamps();

            $table->unique(['purchase_requisition_item_id', 'material_requirement_id'], 'pr_item_source_unique');
            $table->index('material_requirement_id');
            $table->foreign('purchase_requisition_item_id', 'pr_item_source_item_fk')
                ->references('id')
                ->on('purchase_requisition_items')
                ->cascadeOnDelete();
            $table->foreign('material_requirement_id', 'pr_item_source_requirement_fk')
                ->references('id')
                ->on('material_requirements')
                ->cascadeOnDelete();
        });
    }
```

### 2026_06_21_000028_create_purchase_orders_table.php

[Forrás](../../database/migrations/2026_06_21_000028_create_purchase_orders_table.php)

```php
public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('purchase_requisition_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default(PurchaseOrderStatus::Draft->value)->index();
            $table->timestamp('ordered_at')->nullable()->index();
            $table->date('expected_delivery_date')->nullable()->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('supplier_id');
            $table->index('purchase_requisition_id');
        });
    }
```

### 2026_06_21_000005_create_customers_table.php

[Forrás](../../database/migrations/2026_06_21_000005_create_customers_table.php)

```php
public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name')->index();
            $table->string('tax_number')->nullable()->index();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }
```

### 2026_06_21_000004_create_employees_table.php

[Forrás](../../database/migrations/2026_06_21_000004_create_employees_table.php)

```php
public function up(): void
    {
        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('professional_role_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_number')->unique();
            $table->string('name');
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->date('hired_at')->nullable();
            $table->date('left_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
```

### 0001_01_01_000000_create_users_table.php

[Forrás](../../database/migrations/0001_01_01_000000_create_users_table.php)

```php
public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }
```

### 2026_06_22_000001_create_documents_table.php

[Forrás](../../database/migrations/2026_06_22_000001_create_documents_table.php)

```php
public function up(): void
    {
        Schema::create('documents', function (Blueprint $table): void {
            $table->id();
            $table->morphs('documentable');
            $table->string('document_type')->default(DocumentType::Other->value)->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_current')->default(true)->index();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('uploaded_by');
            $table->index('approved_by');
        });
    }
```

### 2026_06_22_135921_create_activity_log_table.php

[Forrás](../../database/migrations/2026_06_22_135921_create_activity_log_table.php)

```php
public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable()->index();
            $table->text('description');
            $table->nullableMorphs('subject', 'subject');
            $table->string('event')->nullable();
            $table->nullableMorphs('causer', 'causer');
            $table->json('attribute_changes')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();
        });
    }
```

### 2026_06_25_000001_add_storage_metadata_to_documents_table.php

[Forrás](../../database/migrations/2026_06_25_000001_add_storage_metadata_to_documents_table.php)

```php
public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->string('disk')->nullable()->after('description');
            $table->string('path')->nullable()->after('disk');
            $table->string('checksum', 64)->nullable()->after('file_size');
            $table->boolean('approved')->default(false)->after('is_current');

            $table->index(['documentable_type', 'documentable_id', 'document_type', 'version'], 'documents_group_version_index');
            $table->index(['approved', 'is_current'], 'documents_approval_current_index');
        });
    }
```

### 2026_06_28_000001_add_ai_processing_fields_to_documents_table.php

[Forrás](../../database/migrations/2026_06_28_000001_add_ai_processing_fields_to_documents_table.php)

```php
public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->string('processing_status')
                ->default(DocumentProcessingStatus::Pending->value)
                ->after('approved');
            $table->decimal('processing_confidence', 5, 4)->nullable()->after('processing_status');
            $table->json('processing_result')->nullable()->after('processing_confidence');
            $table->json('processing_error')->nullable()->after('processing_result');
            $table->timestamp('processed_at')->nullable()->after('processing_error');

            $table->index('processing_status');
        });
    }
```

### Ellenőrzött enumok értékei

- [ItemType](../../app/Enums/ItemType.php): `case PurchasedMaterial = 'purchased_material';` `case ManufacturedPart = 'manufactured_part';` `case SemiFinishedProduct = 'semi_finished_product';` `case FinishedProduct = 'finished_product';`
- [ItemInstanceStatus](../../app/Enums/ItemInstanceStatus.php): `case Planned = 'planned';` `case InProduction = 'in_production';` `case WaitingForCheck = 'waiting_for_check';` `case Checked = 'checked';` `case Rejected = 'rejected';` `case InStock = 'in_stock';` `case Consumed = 'consumed';` `case Shipped = 'shipped';` `case Scrapped = 'scrapped';`
- [StockMovementType](../../app/Enums/StockMovementType.php): `case PurchaseReceive = 'purchase_receive';` `case ProductionIssue = 'production_issue';` `case ProductionConsume = 'production_consume';` `case ProductionOutput = 'production_output';` `case Transfer = 'transfer';` `case Scrap = 'scrap';` `case Correction = 'correction';` `case Reservation = 'reservation';` `case ReservationRelease = 'reservation_release';`
- [StockReservationStatus](../../app/Enums/StockReservationStatus.php): `case Active = 'active';` `case Released = 'released';` `case Consumed = 'consumed';` `case Cancelled = 'cancelled';`
- [MaterialRequirementStatus](../../app/Enums/MaterialRequirementStatus.php): `case Calculated = 'calculated';` `case Reserved = 'reserved';` `case PartiallyAvailable = 'partially_available';` `case Missing = 'missing';` `case Ordered = 'ordered';` `case Received = 'received';` `case Cancelled = 'cancelled';`
- [LocationType](../../app/Enums/LocationType.php): `case Warehouse = 'warehouse';` `case Workshop = 'workshop';` `case QualityArea = 'quality_area';` `case FinishedGoods = 'finished_goods';` `case Scrap = 'scrap';`
- [ProductionTaskStatus](../../app/Enums/ProductionTaskStatus.php): `case Planned = 'planned';` `case Ready = 'ready';` `case InProgress = 'in_progress';` `case WaitingForCheck = 'waiting_for_check';` `case Completed = 'completed';` `case Rejected = 'rejected';` `case Cancelled = 'cancelled';`
- [ProductionOrderStatus](../../app/Enums/ProductionOrderStatus.php): `case Planned = 'planned';` `case Released = 'released';` `case InProgress = 'in_progress';` `case WaitingForCheck = 'waiting_for_check';` `case Completed = 'completed';` `case Cancelled = 'cancelled';`
- [QualityCheckResult](../../app/Enums/QualityCheckResult.php): `case Accepted = 'accepted';` `case ReworkRequired = 'rework_required';` `case Rejected = 'rejected';`
- [PurchaseRequisitionStatus](../../app/Enums/PurchaseRequisitionStatus.php): `case Draft = 'draft';` `case Requested = 'requested';` `case Approved = 'approved';` `case Ordered = 'ordered';` `case Cancelled = 'cancelled';`
- [PurchaseRequisitionItemStatus](../../app/Enums/PurchaseRequisitionItemStatus.php): `case Draft = 'draft';` `case Requested = 'requested';` `case Ordered = 'ordered';` `case Cancelled = 'cancelled';`
- [PurchaseOrderStatus](../../app/Enums/PurchaseOrderStatus.php): `case Draft = 'draft';` `case Ordered = 'ordered';` `case PartiallyReceived = 'partially_received';` `case Received = 'received';` `case Cancelled = 'cancelled';`
