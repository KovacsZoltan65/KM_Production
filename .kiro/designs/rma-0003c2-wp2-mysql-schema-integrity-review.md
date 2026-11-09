# RMA-0003/C2-WP2 – MySQL Schema & Integrity Review

Státusz: **REVIEW / NOT MIGRATION READY**. Dátum: 2026-11-04.
Dokumentációs, executable-level review-terv; nincs implementált vagy futtatott DDL/rutin/teszt.

## 1. Baseline and authority

Branch: `feature/returns-repair-management`.
HEAD: `e21e83da44e7ec2228efa6c5c6f8d1021340b73b`.
Induló `git status --short --untracked-files=all`: az alábbi öt WP untracked; tracked és
staged diff üres. Kizárólag ez az új WP2 készül, a meglévő fájlok nem módosulnak.

| Megőrzött untracked WP                                                   | Belépési SHA-256                                                   |
| ------------------------------------------------------------------------ | ------------------------------------------------------------------ |
| `.kiro/designs/rma-0003c2-wp1-schema-inventory.md`                       | `f4d6682d9bb0b8d58882263199919d9af394cfee58c0c5f0ea704ef6859fa932` |
| `.kiro/designs/rma-0003c2-wp3-inventory-mrp-consumer-contracts.md`       | `1a340ac30113417672869ff0d71bc854ce02f81d4ae2dfd2a5c09ff5ec22d994` |
| `.kiro/designs/rma-0003c2-wp4-return-intake-stock-admission.md`          | `d7a1fc49dc37eadfeba6cfaaa0ee6abeb7319ef34cb4adde5f9a7a4a58538023` |
| `.kiro/designs/rma-0003c2-wp5-fee-credit-procurement-coverage.md`        | `73be78b8a02de54d99ed39cb69fddabceda5b134d696feee4cd46bdfdc0e058d` |
| `.kiro/designs/rma-0003c2-wp6-legacy-data-migration-rollout-recovery.md` | `5e4c012222c8bd13beedcf837f56f3dd7c4cdd750c1feab0eacf6d5e39f5f8ae` |

Mind a tizenegy kötelező hivatkozás útját ellenőriztük: [ADR-0006](../decisions/0006-rma-repair-management-architecture.md),
[ADR-0001](../decisions/0001-stock-movements.md), [A](rma-0003a-data-model-integrity-design.md),
[B](rma-0003b-integrity-verification.md), [C1](rma-0003c1-business-decisions-integrity-gates.md),
[C2 terv](rma-0003c2-detailed-schema-review-plan.md), [WP1](rma-0003c2-wp1-schema-inventory.md),
[WP3](rma-0003c2-wp3-inventory-mrp-consumer-contracts.md),
[WP4](rma-0003c2-wp4-return-intake-stock-admission.md),
[WP5](rma-0003c2-wp5-fee-credit-procurement-coverage.md),
[WP6](rma-0003c2-wp6-legacy-data-migration-rollout-recovery.md).
Az ADR Accepted és C1 ACCEPTED WITH CONDITIONS nem motor-/runtime-bizonyíték.
Történeti B eredmény JSON változatlan, nem regeneráljuk; hash:
`d1b4443724984cbd21c9852c4611fa659411e09924192d7b3b9296164e33a6e6`.
Az eredeti B script-hash történeti, a credential-hardened jelenlegi scriptet nem futtatjuk.

**CONFIRMED CURRENT** = current repository-kód/migrációdefiníció; **HISTORICAL** = pinned másik ág;
**ACCEPTED DECISION** = explicit projektgazdai jóváhagyás; **PROPOSED** = nem implementált technikai
terv; **OPEN** = eldöntendő részlet; **UNVERIFIED** = DB/grant/adat/deployment/teszt bizonyíték hiányzik.
Nincs DB-kapcsolat, tartalomolvasás, DDL/SQL-script/migráció/test végrehajtás, credential/.env olvasás,
dependency install, fetch/pull/branchváltás/import/stage/commit/push/merge. SQL csak e Markdown példája.

## 2. Accepted decisions

**ACCEPTED DECISION – jelen WP2 projektgazdai utasítás:** O1 a current RMA ág authoritative baseline;
O2 a történeti MRP/procurement külön későbbi review-val jóváhagyható stratégiai cél; O3 adapteres
átmeneti coexistence **NOT SELECTED**. A WP5/WP6 korábbi OPEN O1/O2/O3 alternatívái történetiek:
itt a választás lezárt, az integration technikai megvalósítása továbbra nem történt meg.
Nem írjuk át a korábbi dokumentumokat és nem kérjük újra ugyanezt a döntést.

**ACCEPTED DECISION – REV-02 / WP5-REV02-01:** egy requirement egy generationjében több source-line
megengedett, distinct line identity, budget/conservation, partial cancel/receipt, supplier lineage,
consolidation és konkurenciavédelem mellett. **Source-on nincs U(requirement,generation).**
A generation-head singleton U nem tiltja a több line-t. Nem engedélyez implicit partial historical
SupplyProposal consumptiont vagy új párhuzamos RMA-procurement rendszert.

RMA-BD-001–020, D1–D11, F1–F4 és AM-01–AM-04 változatlan; AM-02-G1–G4 kötelező.
D1 három scope: `company_stock`, `customer_custody`, `external`, nem negyedik pending scope.
D2 own manufacture+serial+origin, semi_finished_product típus önmagában nem jogosultság.
D3 egy owner/case, reporter külön lehet. D4 nincs automatic warranty reset. D5 physical és legal event
külön. D6 approval-only credit, D7 proofos unused/unsettled release, cancel nem release.
D8 minden actual replacement return karantén; D9 új authorized shipment új reservation/attempt;
D10 legal reversal saját original event/proof; D11 demand/coverage/commitment/receipt/handoff külön.

## 3. Schema inventory

### 3.1. Pontos leltár és közös típus/enforcement profil

WP1 3.1–3.25: **25 külön új jelölt**; impact 4.1: **18 existing = 7 S + 11 R**.
WP6 ezt current migráció/model leltárral támasztja alá. Current migrációban nincs a 25 új tábla
create-definíciója. A deployed schema és actual rows továbbra **UNVERIFIED**.
Az alábbi 25 profil oszlop-/kulcs-inputját a WP1 név szerinti blokkja pontosan rögzíti;
a WP2 3.2 delta-regisztere és 4–15. fejezete az itt javasolt MySQL-finomítás.
Nem tekintjük az egymás mellé másolt, régi CHECK/FK blokkokat tesztelt teljes DDL-nek.

| Rövidítés        | PROPOSED MySQL 8.4/InnoDB szerződés                                                                                                                             |
| ---------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| ID/F             | BIGINT UNSIGNED, FK azonos típus/width/signedness; PK `id` AUTO_INCREMENT, kivétel active_repair_claims PK item_instance_id                                     |
| Q                | DECIMAL(12,3) O1 baseline; exact arithmetic/base-unit, intermediate DECIMAL range külön; nincs implicit historical18,3 import                                   |
| M                | BIGINT signed scaled units, scale TINYINT UNSIGNED 0..6; currency CHAR(3), canonical uppercase; currency identity/domain-validity guard, nem új FX policy       |
| S/T/J/DT/B       | VARCHAR(n)/TEXT/JSON/DATETIME(6) UTC/TINYINT UNSIGNED; B CHECK IN(0,1); NOT NULL/nullable a WP1 mezője szerint                                                  |
| Machine key/hash | PROPOSED VARBINARY(n) canonical occurrence/line/idempotency key; BINARY(32) SHA-256 digest, explicit byte/hex codec minden app/API boundaryn; nem prefix UNIQUE |
| U/FK/IX          | Minden candidate parent tuple explicit UNIQUE, child tuple azonos sorrendű index; szükségtelen duplikált balprefix index elhagyható proof után                  |
| Common lifecycle | Új soft delete nincs; created_at NOT NULL; mutable updated_at NOT NULL; eventen nincs üzleti UPDATE/DELETE. Mutable owner/PK/created/source identity immutable  |
| Common deletion  | Új FK-knál omitted ON DELETE/UPDATE, InnoDB effective NO ACTION/RESTRICT. Nincs CASCADE/SET NULL; régi FK-k külön audit/contract gate                           |
| Writer E/P       | E=guardolt teljes operation routine, append-only fact; P=ugyanazon protected operation mutable projectionja. Runtime direct DML egyikre sem jogosult            |
| Authority        | Parent aggregate és named domain owner; JSON/result morph/audit properties nem FK vagy proof substitute                                                         |
| Verification     | Minden profil: valid insert + invalid NULL/FK/U/type + UPDATE/DELETE/raw deny + rollback és relevant race; Vxx a 17. fejezetben                                 |

Nem összehasonlítható automatikus ID-t CHECK-ben használni; correction/self-loop/monoton sequence
guard routine/triggerben, már kiosztott identityvel történik. Nincs CHECK-ben foreign SUM/SELECT,
óra, session authorization vagy stored function. A default/current NULL nem „verified company”.

A profilok WP1-ből megőrzött „service-guard” lifecycle-szövege nem application-only enforcement:
a kritikus owner/cap/type/proof/transition ellenőrzést a protected DB operation is végzi.
A service továbbra a workflow és authorization koordinátora; a DB a tárolt tények közötti integritást
védi. External proof valódiságát nem helyettesíti SQL. Ahol a typed evidence interface vagy shape
még nem választható ki bizonyítékból, ott explicit OPEN gate marad, nem kitalált kész séma.

#### 3.1.1. `repair_requests`

Purpose/authority: egy tulajdonos RMA-ügye; RMA case aggregate.
Class/ownership: Source of Truth + MUTABLE lifecycle.
PK: id BIGINT UNSIGNED AUTO_INCREMENT. Új soft delete nincs; common ID/time/FK/IX/type policy fent.

PROPOSED field/null/type/status/candidate-key input, WP2 delta szerint korrigálva; minden F scalar FK és IX, a megadott composite FK ezen felül kötelező:

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

WP2/C1 kiegészítés: closing_command_id:F? -> repair_commands; U(closing_command_id); closed branch requires it, open branch NULL.

Immutable columns/transition scope: tulajdonos, létrehozó, eredeti bejelentő/owner-snapshot és nyitócommand
rögzül. Reporter lehet külön szereplő. Nyitás tagokkal, claimmel és munkalappal atomikus.
`closing_command_id:F? -> repair_commands`, U(closing_command_id) a C1-kiegészítés;
open esetén NULL, closed esetén kötelező a closed_at/closed_by mellett.
Csak a restricted finalizer zárhat, nincs reopen vagy tagonkénti claim-release.

Allowed writer: **case_open / case_finalize**, protected E/P routine; runtime raw DML deny. Delete: no event/business deletion, projection-only claim exception C1 finalizer; referenced parent effective NO ACTION. Migration dependency: command+customer/user/employee. WP7: **V28-V32** + common FK/U/NULL/immutable/permission tests.
Exact input provenance: [WP1 3.1](rma-0003c2-wp1-schema-inventory.md#31-repair_requests).

#### 3.1.2. `repair_request_items`

Purpose/authority: ügytagság és az eredeti gyártott serial jogosultsága; case tulajdonában.
Class/ownership: Source of Truth + MUTABLE receipt-pointer.
PK: id BIGINT UNSIGNED AUTO_INCREMENT. Új soft delete nincs; common ID/time/FK/IX/type policy fent.

PROPOSED field/null/type/status/candidate-key input, WP2 delta szerint korrigálva; minden F scalar FK és IX, a megadott composite FK ezen felül kötelező:

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

WP2/C1 kiegészítés: membership_command_id:F! -> repair_commands; IX(membership_command_id); acquire trigger binds same complete opening command.

Immutable columns/transition scope: parent/owner/serial/eredet/bizonyíték immutable;
receipt NULL→érték egyszer, a receipt movement/custody/instance-projekcióval atomikus.
Legalább egy order létét önmagában nem bizonyítja a child FK.

Allowed writer: **case_member_acquire + trigger**, protected E/P routine; runtime raw DML deny. Delete: no event/business deletion, projection-only claim exception C1 finalizer; referenced parent effective NO ACTION. Migration dependency: case+instance+origin+command. WP7: **V01/V07/V28-V32** + common FK/U/NULL/immutable/permission tests.
Exact input provenance: [WP1 3.2](rma-0003c2-wp1-schema-inventory.md#32-repair_request_items).

#### 3.1.3. `active_repair_claims`

Purpose/authority: serial globális kizárása más aktív RMA-ból; case integritási őr.
Class/ownership: Derived State, MUTABLE technikai claim; története nem itt tárolt.
PK: item_instance_id FK, nincs surrogate id. Új soft delete nincs; common ID/time/FK/IX/type policy fent.

PROPOSED field/null/type/status/candidate-key input, WP2 delta szerint korrigálva; minden F scalar FK és IX, a megadott composite FK ezen felül kötelező:

```text
item_instance_id:F! -> item_instances; PK(item_instance_id), nincs id()
repair_request_item_id:F! -> repair_request_items
claimed_by:F! -> users; command_id:F! -> repair_commands
U(repair_request_item_id)
FK(repair_request_item_id,item_instance_id)
 -> repair_request_items(id,item_instance_id)
CHECK: nincs további soron belüli üzleti feltétel
```

Immutable columns/transition scope: tagság/serial/claim-actor/command nem cserélhető.
Acquire csak tagságtriggerrel, DELETE csak teljes case-close finalizerből;
runtime közvetlen INSERT/UPDATE/DELETE/TRUNCATE/DDL tiltott.
BEFORE DELETE ellenőrzi closed parent + completed closing command + typed close-history meglétét.
Nincs session-variable bypass vagy általános törlőproxy. A finalizer nem fogad proof_ok flaget
és nem COMMIT-ol belül. Claim, case és history együtt rollbackel.

Allowed writer: **member AFTER INSERT / case_finalize only**, protected E/P routine; runtime raw DML deny. Delete: no event/business deletion, projection-only claim exception C1 finalizer; referenced parent effective NO ACTION. Migration dependency: member+instance+trigger. WP7: **V28-V32/V41** + common FK/U/NULL/immutable/permission tests.
Exact input provenance: [WP1 3.3](rma-0003c2-wp1-schema-inventory.md#33-active_repair_claims).

#### 3.1.4. `repair_orders`

Purpose/authority: termékenként önálló javítási munkalap; repair execution aggregate.
Class/ownership: Source of Truth + MUTABLE execution projection.
PK: id BIGINT UNSIGNED AUTO_INCREMENT. Új soft delete nincs; common ID/time/FK/IX/type policy fent.

PROPOSED field/null/type/status/candidate-key input, WP2 delta szerint korrigálva; minden F scalar FK és IX, a megadott composite FK ezen felül kötelező:

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

Immutable columns/transition scope: tagság/work number rögzül; díjszabás és consent első végrehajtástól
snapshot. A rate nem igazolt fee: annak külön táblája 3.24.
Fizetős start F4-gate alatt, új scope ismételt jóváhagyást igényel;
completed execution nem case-close. Attempt monoton, QC-hiba előzményét nem törli.

Allowed writer: **order_progress**, protected E/P routine; runtime raw DML deny. Delete: no event/business deletion, projection-only claim exception C1 finalizer; referenced parent effective NO ACTION. Migration dependency: member+command. WP7: **V34/V35** + common FK/U/NULL/immutable/permission tests.
Exact input provenance: [WP1 3.4](rma-0003c2-wp1-schema-inventory.md#34-repair_orders).

#### 3.1.5. `repair_operations`

Purpose/authority: javítási terv és végrehajtás egy attemptben; repair_order.
Class/ownership: Source of Truth + MUTABLE lifecycle.
PK: id BIGINT UNSIGNED AUTO_INCREMENT. Új soft delete nincs; common ID/time/FK/IX/type policy fent.

PROPOSED field/null/type/status/candidate-key input, WP2 delta szerint korrigálva; minden F scalar FK és IX, a megadott composite FK ezen felül kötelező:

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

Immutable columns/transition scope: indítás után terv/utasítás/estimate/QC-követelmény nem írható át;
csak meg nem kezdett előzmény supersedálható. Ugyanazon order/attempt és monoton
sorrend guardolt; self-FK nem ciklusvédelem. Új fizetős scope F4.

Allowed writer: **operation_plan/start/finish**, protected E/P routine; runtime raw DML deny. Delete: no event/business deletion, projection-only claim exception C1 finalizer; referenced parent effective NO ACTION. Migration dependency: order+quote+command+masters. WP7: **V34/V35/V37** + common FK/U/NULL/immutable/permission tests.
Exact input provenance: [WP1 3.5](rma-0003c2-wp1-schema-inventory.md#35-repair_operations).

#### 3.1.6. `repair_operation_assignments`

Purpose/authority: több dolgozó hozzárendelése; operation.
Class/ownership: Source of Truth, MUTABLE egyszeri lezárásig.
PK: id BIGINT UNSIGNED AUTO_INCREMENT. Új soft delete nincs; common ID/time/FK/IX/type policy fent.

PROPOSED field/null/type/status/candidate-key input, WP2 delta szerint korrigálva; minden F scalar FK és IX, a megadott composite FK ezen felül kötelező:

```text
repair_operation_id:F! -> repair_operations; employee_id:F! -> employees
assigned_by:F! -> users; assigned_at:DT!; ended_at:DT?
role_snapshot:S(100)!; command_id:F! -> repair_commands
U(repair_operation_id,employee_id); U(id,employee_id)
IX(employee_id,assigned_at)
CHECK ended_at IS NULL OR ended_at>=assigned_at
```

Immutable columns/transition scope: operation/employee/role/assigner snapshot rögzül, ended_at egyszer állítható.
Employee aktív és szakmailag jogosult; FK nem permission. Új időszelet nem írja át a hozzárendelést.

Allowed writer: **assignment_assign/end**, protected E/P routine; runtime raw DML deny. Delete: no event/business deletion, projection-only claim exception C1 finalizer; referenced parent effective NO ACTION. Migration dependency: operation+employee+command. WP7: **V34-V36** + common FK/U/NULL/immutable/permission tests.
Exact input provenance: [WP1 3.6](rma-0003c2-wp1-schema-inventory.md#36-repair_operation_assignments).

#### 3.1.7. `repair_time_entries`

Purpose/authority: tényleges munkaidő; assignment.
Class/ownership: Source of Truth, MUTABLE → lezárt immutable tény; actual összeg Derived State.
PK: id BIGINT UNSIGNED AUTO_INCREMENT. Új soft delete nincs; common ID/time/FK/IX/type policy fent.

PROPOSED field/null/type/status/candidate-key input, WP2 delta szerint korrigálva; minden F scalar FK és IX, a megadott composite FK ezen felül kötelező:

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

Immutable columns/transition scope: lezárt intervallum korrekciója új sor indokkal;
assignment mutex alatt overlap-guard, NULL végpont végtelen. U(active_assignment_id)
csak egy nyitott intervallumot véd, lezárt átfedést nem.

Allowed writer: **time_start/finish/correct**, protected E/P routine; runtime raw DML deny. Delete: no event/business deletion, projection-only claim exception C1 finalizer; referenced parent effective NO ACTION. Migration dependency: assignment+command+self prior. WP7: **V34-V36** + common FK/U/NULL/immutable/permission tests.
Exact input provenance: [WP1 3.7](rma-0003c2-wp1-schema-inventory.md#37-repair_time_entries).

#### 3.1.8. `repair_materials`

Purpose/authority: tényleges javítási anyagfogyasztás; repair_order/operation.
Class/ownership: Immutable Event, EVENT.
PK: id BIGINT UNSIGNED AUTO_INCREMENT. Új soft delete nincs; common ID/time/FK/IX/type policy fent.

PROPOSED field/null/type/status/candidate-key input, WP2 delta szerint korrigálva; minden F scalar FK és IX, a megadott composite FK ezen felül kötelező:

```text
repair_order_id:F! -> repair_orders; repair_operation_id:F? -> repair_operations
stock_reservation_id:F? -> stock_reservations
item_id:F! -> items; item_batch_id:F? -> item_batches
item_instance_id:F? -> item_instances
used_quantity:Q!; unit:S(50)!;
command_id:F! -> repair_commands; notes:T?
U(id,repair_order_id)
IX(repair_order_id,item_id); IX(item_instance_id,created_at)
FK(repair_operation_id,repair_order_id) -> repair_operations(id,repair_order_id)
CHECK used_quantity>0
CHECK item_instance_id IS NULL OR used_quantity=1
```

WP2/C1 kiegészítés: Movement inverse FK stock_movements.repair_material_id U, not old material.stock_movement_id.

Immutable columns/transition scope: minden anyag/mennyiség/serial/movement mező rögzül;
korrekció új indokolt mozgás/tény. Item/batch/serial/unit/owner/hely egyezés és
eligible company remaining stock guardolt. Material+movement+balance+reservation partial counter
egy tranzakció; más owner foglalása nem fogyasztható.

Allowed writer: **material_consume**, protected E/P routine; runtime raw DML deny. Delete: no event/business deletion, projection-only claim exception C1 finalizer; referenced parent effective NO ACTION. Migration dependency: order+op+reservation+command then movement child. WP7: **V08-V12/V38** + common FK/U/NULL/immutable/permission tests.
Exact input provenance: [WP1 3.8](rma-0003c2-wp1-schema-inventory.md#38-repair_materials).

#### 3.1.9. `repair_inspections`

Purpose/authority: diagnosztikai, műveleti, végső és return-quarantine vizsgálati tény; order.
Class/ownership: Immutable Event, EVENT.
PK: id BIGINT UNSIGNED AUTO_INCREMENT. Új soft delete nincs; common ID/time/FK/IX/type policy fent.

PROPOSED field/null/type/status/candidate-key input, WP2 delta szerint korrigálva; minden F scalar FK és IX, a megadott composite FK ezen felül kötelező:

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

WP2/C1 kiegészítés: subject_item_instance_id:F! -> item_instances; return_event_id:F? -> repair_delivery_events; IX(return_event_id,checked_at). inspection_type includes return_quarantine iff return_event_id nonNULL; order/subject/return type and tuple guards.

Immutable columns/transition scope: hibás QC sem törölhető; új vizsgálat új command,
régi attempt eredménye nem minősíti az újat. Quarantine inspection nem önmagában feloldás.

Allowed writer: **inspection_record**, protected E/P routine; runtime raw DML deny. Delete: no event/business deletion, projection-only claim exception C1 finalizer; referenced parent effective NO ACTION. Migration dependency: order+operation+return subject+command. WP7: **V18-V24** + common FK/U/NULL/immutable/permission tests.
Exact input provenance: [WP1 3.9](rma-0003c2-wp1-schema-inventory.md#39-repair_inspections).

#### 3.1.10. `product_warranty_rules`

Purpose/authority: cikkszintű verziózott garanciaszabály; terméktörzs-jóváhagyó.
Class/ownership: Source of Truth, Immutable Event, EVENT.
PK: id BIGINT UNSIGNED AUTO_INCREMENT. Új soft delete nincs; common ID/time/FK/IX/type policy fent.

PROPOSED field/null/type/status/candidate-key input, WP2 delta szerint korrigálva; minden F scalar FK és IX, a megadott composite FK ezen felül kötelező:

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

Immutable columns/transition scope: publikált verzió nem szerkeszthető; itemzár alatt verzió és effective_from.
Az alkalmazandó időpont utolsó hatályos szabálya; assessment snapshot megmarad.
Nincs beépített jogszabály vagy visszamenőleges automatikus újraminősítés.

Allowed writer: **warranty_rule_publish**, protected E/P routine; runtime raw DML deny. Delete: no event/business deletion, projection-only claim exception C1 finalizer; referenced parent effective NO ACTION. Migration dependency: item+user+command. WP7: **V37/V39** + common FK/U/NULL/immutable/permission tests.
Exact input provenance: [WP1 3.10](rma-0003c2-wp1-schema-inventory.md#310-product_warranty_rules).

#### 3.1.11. `product_handover_evidences`

Purpose/authority: serialhoz kötött bizonyított ügyfélátadás; warranty evidence owner.
Class/ownership: Source of Truth, Immutable Event, EVENT.
PK: id BIGINT UNSIGNED AUTO_INCREMENT. Új soft delete nincs; common ID/time/FK/IX/type policy fent.

PROPOSED field/null/type/status/candidate-key input, WP2 delta szerint korrigálva; minden F scalar FK és IX, a megadott composite FK ezen felül kötelező:

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

Immutable columns/transition scope: konkrét dokumentumverzió+checksum megőrzendő;
korrekció új sor, nem serial-only UNIQUE. Rendelés dátuma nem átadási proof.
Release opcionális, ezért eredeti értékesítési átadás is reprezentálható.

Allowed writer: **handover_verify**, protected E/P routine; runtime raw DML deny. Delete: no event/business deletion, projection-only claim exception C1 finalizer; referenced parent effective NO ACTION. Migration dependency: instance+release+document+command. WP7: **V25/V37/V39** + common FK/U/NULL/immutable/permission tests.
Exact input provenance: [WP1 3.11](rma-0003c2-wp1-schema-inventory.md#311-product_handover_evidences).

#### 3.1.12. `warranty_assessments`

Purpose/authority: manuális, bizonyított minősítés a megnevezett subject serialra; repair_order.
Class/ownership: Source of Truth, Immutable Event, EVENT.
PK: id BIGINT UNSIGNED AUTO_INCREMENT. Új soft delete nincs; common ID/time/FK/IX/type policy fent.

PROPOSED field/null/type/status/candidate-key input, WP2 delta szerint korrigálva; minden F scalar FK és IX, a megadott composite FK ezen felül kötelező:

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

Immutable columns/transition scope: globális order-version mutex alatt; aktuális minősítés a subjectre szűrt
legnagyobb verzió. Eredeti és csere serial külön történet, nincs automatikus reset.
Hiányos bizonyíték pending; override csak indokkal és jogosultsággal.
Parent `item_instances` U(id,item_id) szükséges. A composite FK-k nem igazolják az orderhez tartozást:
eredeti tagság vagy tartós replacement reservation guardja kötelező.

Allowed writer: **warranty_assess**, protected E/P routine; runtime raw DML deny. Delete: no event/business deletion, projection-only claim exception C1 finalizer; referenced parent effective NO ACTION. Migration dependency: order+instance/rule/handover+command. WP7: **V25/V37/V39** + common FK/U/NULL/immutable/permission tests.
Exact input provenance: [WP1 3.12](rma-0003c2-wp1-schema-inventory.md#312-warranty_assessments).

#### 3.1.13. `repair_quotations`

Purpose/authority: verziózott fizetős scope és ügyfélválasz; repair_order.
Class/ownership: Source of Truth, MUTABLE → submitted tartalom immutable.
PK: id BIGINT UNSIGNED AUTO_INCREMENT. Új soft delete nincs; common ID/time/FK/IX/type policy fent.

PROPOSED field/null/type/status/candidate-key input, WP2 delta szerint korrigálva; minden F scalar FK és IX, a megadott composite FK ezen felül kötelező:

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

Immutable columns/transition scope: submit után lines/currency/scale/total/hash tartalom rögzül;
válasz külön bizonyítékkal. Startkor érvényes aktuális approval kell, nem elég lejárati cron.
Új draft felfüggeszti régi approval használatát új munkára; történetet nem töröl.
Credit **csak approvalkor** foglal, draft/submitted nem.

Allowed writer: **quote_draft/submit/respond**, protected E/P routine; runtime raw DML deny. Delete: no event/business deletion, projection-only claim exception C1 finalizer; referenced parent effective NO ACTION. Migration dependency: order+command+customer/document. WP7: **V25-V27/V33** + common FK/U/NULL/immutable/permission tests.
Exact input provenance: [WP1 3.13](rma-0003c2-wp1-schema-inventory.md#313-repair_quotations).

#### 3.1.14. `repair_quotation_items`

Purpose/authority: ajánlat tartalma, díj és credit sora; quotation.
Class/ownership: Source of Truth, MUTABLE → submit után immutable.
PK: id BIGINT UNSIGNED AUTO_INCREMENT. Új soft delete nincs; common ID/time/FK/IX/type policy fent.

PROPOSED field/null/type/status/candidate-key input, WP2 delta szerint korrigálva; minden F scalar FK és IX, a megadott composite FK ezen felül kötelező:

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

Immutable columns/transition scope: parenttel együtt submitkor rögzül; pénz egész egység,
quantity exact decimal. Total=sum és credit<=igazolt fee cross-row guard;
azonos order/currency/scale kötelező. A negatív sor önmagában nem credit-allocation.

Allowed writer: **quote_line_edit + submit guard**, protected E/P routine; runtime raw DML deny. Delete: no event/business deletion, projection-only claim exception C1 finalizer; referenced parent effective NO ACTION. Migration dependency: quote+item/source order. WP7: **V25-V27/V33** + common FK/U/NULL/immutable/permission tests.
Exact input provenance: [WP1 3.14](rma-0003c2-wp1-schema-inventory.md#314-repair_quotation_items).

#### 3.1.15. `repair_custodies`

Purpose/authority: kizárólag az eredeti visszáru aktuális őrzése; member.
Class/ownership: Projection, MUTABLE, újraépíthető movementből.
PK: id BIGINT UNSIGNED AUTO_INCREMENT. Új soft delete nincs; common ID/time/FK/IX/type policy fent.

PROPOSED field/null/type/status/candidate-key input, WP2 delta szerint korrigálva; minden F scalar FK és IX, a megadott composite FK ezen felül kötelező:

```text
repair_request_item_id:F! -> repair_request_items
item_instance_id:F! -> item_instances; location_id:F! -> locations
last_stock_movement_id:F! -> stock_movements
U(repair_request_item_id); U(item_instance_id); U(last_stock_movement_id)
FK(repair_request_item_id,item_instance_id) -> repair_request_items(id,item_instance_id)
CHECK: nincs további soron belüli mennyiségfeltétel (egy sor egy egység)
```

Immutable columns/transition scope: member/serial rögzül; hely/last movement csak valós mozgással
együtt változik. Release/scrap megszünteti az aktuális sort, történeti mozgás nem törölhető.
Replacement preparation/return ide nem kerülhet; rebuilding nem új receipt.

Allowed writer: **stock_original_custody projection**, protected E/P routine; runtime raw DML deny. Delete: no event/business deletion, projection-only claim exception C1 finalizer; referenced parent effective NO ACTION. Migration dependency: member+posted movement. WP7: **V13-V24** + common FK/U/NULL/immutable/permission tests.
Exact input provenance: [WP1 3.15](rma-0003c2-wp1-schema-inventory.md#315-repair_custodies).

#### 3.1.16. `repair_releases`

Purpose/authority: egy megtörtént fizikai shipment identitása; member/order.
Class/ownership: Immutable Event, EVENT.
PK: id BIGINT UNSIGNED AUTO_INCREMENT. Új soft delete nincs; common ID/time/FK/IX/type policy fent.

PROPOSED field/null/type/status/candidate-key input, WP2 delta szerint korrigálva; minden F scalar FK és IX, a megadott composite FK ezen felül kötelező:

```text
repair_request_item_id:F! -> repair_request_items
released_item_instance_id:F! -> item_instances
release_role:S(16)!; stock_reservation_id:F? -> stock_reservations
recipient_customer_id:F! -> customers; recipient_name:S(255)!
recipient_snapshot:J!; released_at:DT!; shipment_reference:S(128)!
release_evidence_document_id:F! -> documents; final_condition:T!

handover_policy_snapshot:J!; command_id:F! -> repair_commands
shipment_attempt:U32!; previous_release_id:F? -> repair_releases
redispatch_authorization_document_id:F? -> documents
U(command_id); U(previous_release_id)
U(stock_reservation_id)
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
shipment_source_namespace:S(64)!; shipment_source_event_key:S(128)!
U(shipment_source_namespace,shipment_source_event_key)
```

WP2/C1 kiegészítés: Movement inverse FK stock_movements.shipment_release_id U, not old release.stock_movement_id; source_repair_release_id remains context for returns.

Immutable columns/transition scope: a release maga az attempt; minden replacement attempt új reservationt kap.
U(stock_reservation_id) visszaállítva; nem a gyengébb U(reservation,attempt).
Source occurrence stabil új command esetén is; címkenyomtatás nem fizikai bizonyíték.
Previous azonos member/serial/role, attempt=előző+1 guard. Original csak attempt1.
Új shipmenthez D9: tényleges return, condition/rights/prior-shipment rendezés és authorization.
Delivery és ownership külön, nem újabb outbound; recipient proof nem automatikus tulajdonjog.

Allowed writer: **shipment_post**, protected E/P routine; runtime raw DML deny. Delete: no event/business deletion, projection-only claim exception C1 finalizer; referenced parent effective NO ACTION. Migration dependency: member+reservation+command then movement child. WP7: **V13/V23/V24** + common FK/U/NULL/immutable/permission tests.
Exact input provenance: [WP1 3.16](rma-0003c2-wp1-schema-inventory.md#316-repair_releases).

#### 3.1.17. `repair_delivery_events`

Purpose/authority: shipment delivery/failure/intake/admission bizonyítéklánc; release.
Class/ownership: Immutable Event, EVENT.
PK: id BIGINT UNSIGNED AUTO_INCREMENT. Új soft delete nincs; common ID/time/FK/IX/type policy fent.

PROPOSED field/null/type/status/candidate-key input, WP2 delta szerint korrigálva; minden F scalar FK és IX, a megadott composite FK ezen felül kötelező:

```text
repair_release_id:F! -> repair_releases
item_instance_id:F! -> item_instances; repair_request_item_id:F! -> repair_request_items
release_role:S(16)!; event_type:S(24)!; event_key:S(128)!; occurred_at:DT!
recipient_snapshot:J!; reference:S(128)!; evidence_document_id:F! -> documents
recorded_by:F! -> users; supersedes_event_id:F? -> repair_delivery_events
command_id:F! -> repair_commands
related_return_event_id:F? -> repair_delivery_events
return_location_id:F? -> locations
ownership_verification_document_id:F? -> documents
physical_return_namespace:S(64)?; physical_return_source_event_key:S(128)?
admission_intake_key:F? -> repair_delivery_events
U(physical_return_namespace,physical_return_source_event_key)
U(admission_intake_key)
U(repair_release_id,event_key); U(supersedes_event_id); U(command_id)
U(id,repair_release_id,item_instance_id)
IX(repair_release_id,occurred_at); IX(related_return_event_id,event_type,occurred_at)
FK(repair_release_id,repair_request_item_id,item_instance_id,release_role)
 -> repair_releases(id,repair_request_item_id,released_item_instance_id,release_role)
FK(related_return_event_id,repair_release_id,item_instance_id)
 -> repair_delivery_events(id,repair_release_id,item_instance_id)
CHECK release_role IN('original','replacement')
CHECK event_type IN('delivered','delivery_failed','recall_requested','physically_returned','return_reconciled','correction')
admission_target_scope:S(24)?; return_authorization_history_id:F? -> repair_status_histories
admission_basis_history_id:F? -> repair_status_histories
-- Type/NULL/MI/MA/candidate tuple contract: sections 7 and 15 (EX04).

```

Immutable columns/transition scope: return és admission külön identity; failure balance0.
Minden fizikai return intake+hely+quarantine tranzakció, ismeretlen ownership mellett is.
Admission ugyanazon return egyszeri besorolása, nem második fizikai receipt;
company balance csak verified jogcímmel +1, quarantine ettől még megmarad.
U(related_return_event_id) általános tiltását C1 felülírja: több inspection/decision child lehet;
csak egyszeri intake/admission purpose-key egyedi. Late delivered saját attempten marad;
ellentmondó proof blokkol, nincs latest-timestamp-wins.

Allowed writer: **return_intake / admission_post / delivery_record**, protected E/P routine; runtime raw DML deny. Delete: no event/business deletion, projection-only claim exception C1 finalizer; referenced parent effective NO ACTION. Migration dependency: release+I prior+basis prior+command then movement child. WP7: **V13-V24** + common FK/U/NULL/immutable/permission tests.
Exact input provenance: [WP1 3.17](rma-0003c2-wp1-schema-inventory.md#317-repair_delivery_events).

#### 3.1.18. `repair_ownership_events`

Purpose/authority: bizonyított tulajdonjogi esemény, nem stock posting; member + serial.
Class/ownership: Source of Truth, Immutable Event, EVENT.
PK: id BIGINT UNSIGNED AUTO_INCREMENT. Új soft delete nincs; common ID/time/FK/IX/type policy fent.

PROPOSED field/null/type/status/candidate-key input, WP2 delta szerint korrigálva; minden F scalar FK és IX, a megadott composite FK ezen felül kötelező:

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
reverses_ownership_event_id:F? -> repair_ownership_events
previous_ownership_event_id:F? -> repair_ownership_events
U(id,repair_request_item_id,item_instance_id); U(reverses_ownership_event_id)
FK(reverses_ownership_event_id,repair_request_item_id,item_instance_id)
 -> repair_ownership_events(id,repair_request_item_id,item_instance_id)
FK(previous_ownership_event_id,repair_request_item_id,item_instance_id)
 -> repair_ownership_events(id,repair_request_item_id,item_instance_id)
CHECK (event_type='reversal' AND reverses_ownership_event_id IS NOT NULL)
 OR (event_type<>'reversal' AND reverses_ownership_event_id IS NULL)
```

Immutable columns/transition scope: reversal külön causal FK az originalra, correction külön supersedes.
Teljes reversal egyedi; korrekció a reversalre mutat, nem második reversal.
Previous effective jogi lánc és rögzítési sequence eltérhet az occurred_at sorrendtől.
Policy/proof/owner/right/jogosultság guardolt, fizikai returnből nem következik reversal.

Allowed writer: **ownership_record/reverse**, protected E/P routine; runtime raw DML deny. Delete: no event/business deletion, projection-only claim exception C1 finalizer; referenced parent effective NO ACTION. Migration dependency: member+serial+release/reservation+previous+command. WP7: **V24/V37/V39** + common FK/U/NULL/immutable/permission tests.
Exact input provenance: [WP1 3.18](rma-0003c2-wp1-schema-inventory.md#318-repair_ownership_events).

#### 3.1.19. `repair_dispositions`

Purpose/authority: eredeti termék végső sorsa és teljes repair végkimenetel; member.
Class/ownership: Source of Truth, Immutable Event, EVENT.
PK: id BIGINT UNSIGNED AUTO_INCREMENT. Új soft delete nincs; common ID/time/FK/IX/type policy fent.

PROPOSED field/null/type/status/candidate-key input, WP2 delta szerint korrigálva; minden F scalar FK és IX, a megadott composite FK ezen felül kötelező:

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

Immutable columns/transition scope: egy végső rendelkezés/member; pending nem final.
Az eredeti return/scrap/not_received proof és a replacement rendezése függetlenül kell.
Típus/outcome értelmes kombinációját, linked subject/QC/auth egyezést service vizsgálja.
Quarantine közbenső rendelkezése a typed history, nem ez az egyedi final sor.

Allowed writer: **member_disposition_finalize**, protected E/P routine; runtime raw DML deny. Delete: no event/business deletion, projection-only claim exception C1 finalizer; referenced parent effective NO ACTION. Migration dependency: member+all prior outcome proof+command. WP7: **V28-V32/V39** + common FK/U/NULL/immutable/permission tests.
Exact input provenance: [WP1 3.19](rma-0003c2-wp1-schema-inventory.md#319-repair_dispositions).

#### 3.1.20. `repair_status_histories`

Purpose/authority: typed append-only állapot- és döntéstörténet; XOR case vagy order.
Class/ownership: Immutable Event, EVENT; nem általános inventory/procurement ledger.
PK: id BIGINT UNSIGNED AUTO_INCREMENT. Új soft delete nincs; common ID/time/FK/IX/type policy fent.

PROPOSED field/null/type/status/candidate-key input, WP2 delta szerint korrigálva; minden F scalar FK és IX, a megadott composite FK ezen felül kötelező:

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

WP2/C1 kiegészítés: member+serial optional pair, inspection_id/return_event_id/authorization_document_id optional typed FKs; diagnostic_credit_allocation_id/related_allocation_history_id optional typed FKs. execution_review_document_id/settlement_review_document_id/reviewed_by/reviewed_at/settlement_observation optional, required on credit-review types; observation unknown/unsettled/settled. credit_effect_kind/units/currency/scale only verified usage/correction; dimension CHECK adds claim/credit/quarantine; section9 type guards. Exact final alias names gated, not a second freeform proof store.

Immutable columns/transition scope: minden review új sor; korábbi observation nem írható át.
Fee-credit projekcióváltás és history atomikus, settled/unknown nem automatikus release.

Allowed writer: **typed_history via complete domain operation**, protected E/P routine; runtime raw DML deny. Delete: no event/business deletion, projection-only claim exception C1 finalizer; referenced parent effective NO ACTION. Migration dependency: case/order+typed existing subject+command. WP7: **V01/V27-V33/V39** + common FK/U/NULL/immutable/permission tests.
Exact input provenance: [WP1 3.20](rma-0003c2-wp1-schema-inventory.md#320-repair_status_histories).

#### 3.1.21. `repair_commands`

Purpose/authority: tartós command identity, payload és eredmény; RMA íráskoordinátor.
Class/ownership: Source of Truth, MUTABLE processing→completed, majd immutable.
PK: id BIGINT UNSIGNED AUTO_INCREMENT. Új soft delete nincs; common ID/time/FK/IX/type policy fent.

PROPOSED field/null/type/status/candidate-key input, WP2 delta szerint korrigálva; minden F scalar FK és IX, a megadott composite FK ezen felül kötelező:

```text
command_type:S(64)!; idempotency_key:S(128)!; payload_hash:S(64)!
actor_id:F! -> users; status:S(16)!='processing'
result_type:S(64)?; result_id:U64?; result_snapshot:J?; completed_at:DT?
U(command_type,idempotency_key); IX(actor_id,created_at)
CHECK status IN('processing','completed')
CHECK (status='processing' AND completed_at IS NULL)
 OR (status='completed' AND completed_at IS NOT NULL AND result_snapshot IS NOT NULL)
```

Immutable columns/transition scope: actor/hash/kulcs nem cserélhető; failed command teljes rollback.
Processing nem commitolható sikeres commandként. Retry csak azonos hash/actor/auth mellett
ad korábbi eredményt; új command nem új fizikai occurrence.
Result morph válasz, nem helyettesít domain-FK-t. Legacy procurement nem kényszerül fiktív repair command alá.

Allowed writer: **command wrapper only**, protected E/P routine; runtime raw DML deny. Delete: no event/business deletion, projection-only claim exception C1 finalizer; referenced parent effective NO ACTION. Migration dependency: users, no business result FK required. WP7: **V06/V40-V43** + common FK/U/NULL/immutable/permission tests.
Exact input provenance: [WP1 3.21](rma-0003c2-wp1-schema-inventory.md#321-repair_commands).

#### 3.1.22. `item_instance_genealogy_events`

Purpose/authority: beépülés/kiszerelés/korrekció serialos története; repair_order.
Class/ownership: Immutable Event, EVENT; aktuális beépülés Derived State.
PK: id BIGINT UNSIGNED AUTO_INCREMENT. Új soft delete nincs; common ID/time/FK/IX/type policy fent.

PROPOSED field/null/type/status/candidate-key input, WP2 delta szerint korrigálva; minden F scalar FK és IX, a megadott composite FK ezen felül kötelező:

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

Immutable columns/transition scope: install egyszer remove; parent+child mutex alatt ciklus és párhuzamos
effective install kizárása. Legacy_install csak bizonyítékkal, régi relations megőrzésével.
Kiszerelés nem automatikus szabad stock/tulajdonváltás; valós mozgás külön.

Allowed writer: **genealogy_post**, protected E/P routine; runtime raw DML deny. Delete: no event/business deletion, projection-only claim exception C1 finalizer; referenced parent effective NO ACTION. Migration dependency: instances+order+operation+prior edges. WP7: **V37/V39** + common FK/U/NULL/immutable/permission tests.
Exact input provenance: [WP1 3.22](rma-0003c2-wp1-schema-inventory.md#322-item_instance_genealogy_events).

#### 3.1.23. `repair_diagnostic_credit_allocations`

Purpose/authority: egy igazolt diagnosztikai forrás effektív felhasználási igénye; source order.
Class/ownership: Source of Truth + MUTABLE projekció, megőrzött immutable historyval.
PK: id BIGINT UNSIGNED AUTO_INCREMENT. Új soft delete nincs; common ID/time/FK/IX/type policy fent.

PROPOSED field/null/type/status/candidate-key input, WP2 delta szerint korrigálva; minden F scalar FK és IX, a megadott composite FK ezen felül kötelező:

```text
source_repair_order_id:F! -> repair_orders
repair_quotation_id:F! -> repair_quotations; quotation_item_id:F! -> repair_quotation_items
diagnostic_fee_id:F! -> repair_diagnostic_fees
active_source_order_id:F? -> repair_orders
amount_units:M!; currency:S(3)!; money_scale:U32!; state:S(16)!
allocated_at:DT!; released_at:DT?; release_document_id:F? -> documents
latest_review_history_id:F? -> repair_status_histories
command_id:F! -> repair_commands
U(active_source_order_id); U(quotation_item_id); U(command_id); U(id,source_repair_order_id)
FK(diagnostic_fee_id,source_repair_order_id) -> repair_diagnostic_fees(id,repair_order_id)
FK(repair_quotation_id,source_repair_order_id) -> repair_quotations(id,repair_order_id)
FK(quotation_item_id,repair_quotation_id,source_repair_order_id)
 -> repair_quotation_items(id,repair_quotation_id,diagnostic_source_order_id)
IX(source_repair_order_id,allocated_at)
CHECK amount_units>0 AND money_scale BETWEEN 0 AND 6
CHECK (state IN('reserved','retained') AND active_source_order_id IS NOT NULL
 AND active_source_order_id=source_repair_order_id AND released_at IS NULL
 AND release_document_id IS NULL)
 OR (state='released' AND active_source_order_id IS NULL
 AND released_at IS NOT NULL AND release_document_id IS NOT NULL
 AND latest_review_history_id IS NOT NULL)
```

Immutable columns/transition scope: fee/quote/line/order/amount/currency/scale rögzül.
Approval-only reserved; submit-held/applied protokoll SUPERSEDED.
U(active_source_order_id) fee-verziókon át is egy effektív kreditet véd.
Cancel/supersede nem release; execution+settlement review szükséges.
Authorized unused+unsettled release törli az active keyt, nem a történetet.
Unknown/executed/settled ág blokkolt/retained a külön igazolt rendelkezésig;
új fee-verzió nem nyit második kreditet. Released nem aktiválható újra.

Allowed writer: **quote_approve / credit_review_release**, protected E/P routine; runtime raw DML deny. Delete: no event/business deletion, projection-only claim exception C1 finalizer; referenced parent effective NO ACTION. Migration dependency: verified fee+quote/line+command then history. WP7: **V25-V27/V33/V43** + common FK/U/NULL/immutable/permission tests.
Exact input provenance: [WP1 3.23](rma-0003c2-wp1-schema-inventory.md#323-repair_diagnostic_credit_allocations).

#### 3.1.24. `repair_diagnostic_fees`

Purpose/authority: ajánlattól független, ténylegesen igazolt diagnosztikai díj verziója; order.
Class/ownership: Source of Truth, Immutable Event, EVENT.
PK: id BIGINT UNSIGNED AUTO_INCREMENT. Új soft delete nincs; common ID/time/FK/IX/type policy fent.

PROPOSED field/null/type/status/candidate-key input, WP2 delta szerint korrigálva; minden F scalar FK és IX, a megadott composite FK ezen felül kötelező:

```text
repair_order_id:F! -> repair_orders; version:U32!; amount_units:M!
currency:S(3)!; money_scale:U32!; calculation_snapshot:J!
consent_document_id:F! -> documents; evidence_document_id:F! -> documents
verified_by:F! -> users; verified_at:DT!
supersedes_fee_id:F? -> repair_diagnostic_fees; command_id:F! -> repair_commands
U(repair_order_id,version); U(supersedes_fee_id); U(command_id); U(id,repair_order_id)
FK(supersedes_fee_id,repair_order_id) -> repair_diagnostic_fees(id,repair_order_id)
IX(repair_order_id,verified_at)
CHECK version>=1 AND amount_units>=0 AND money_scale BETWEEN 0 AND 6
```

Immutable columns/transition scope: minden mező rögzül; correction új verzió+bizonyíték,
orderzár alatt aktuális fee-választás. Egy meglévő diagnosztikai díj verziózott proofja,
nem több számlázható díjkategória vagy számla. Self-FK nem monoton/ciklusvédelem.

Allowed writer: **diagnostic_fee_verify**, protected E/P routine; runtime raw DML deny. Delete: no event/business deletion, projection-only claim exception C1 finalizer; referenced parent effective NO ACTION. Migration dependency: order+consent/proof+command+prior fee. WP7: **V25-V27/V39** + common FK/U/NULL/immutable/permission tests.
Exact input provenance: [WP1 3.24](rma-0003c2-wp1-schema-inventory.md#324-repair_diagnostic_fees).

#### 3.1.25. `procurement_coverage_events`

Purpose/authority: demand/coverage/commitment/actual receipt változatlan mennyiségi története;
procurement domain, legacy és repair igény számára egyaránt.
Class/ownership: Source of Truth, Immutable Event, EVENT; aktuális coverage Derived State.
PK: id BIGINT UNSIGNED AUTO_INCREMENT. Új soft delete nincs; common ID/time/FK/IX/type policy fent.

PROPOSED field/null/type/status/candidate-key input, WP2 delta szerint korrigálva; minden F scalar FK és IX, a megadott composite FK ezen felül kötelező:

```text
material_requirement_id:F! -> material_requirements; demand_revision:U32!
event_type:S(32)!
purchase_requisition_item_source_id:F? -> purchase_requisition_item_sources
purchase_order_item_id:F? -> purchase_order_items
goods_receipt_item_id:F? -> goods_receipt_items
related_allocation_event_id:F? -> procurement_coverage_events
transfer_key:S(128)?; transfer_side:S(4)?
quantity:Q!; unit:S(50)!; before_demand_snapshot:J!; after_demand_snapshot:J!
actor_id:F! -> users; occurred_at:DT!; evidence_document_id:F? -> documents
idempotency_key:S(128)!; event_sequence:U32!; payload_hash:S(64)!
supersedes_event_id:F? -> procurement_coverage_events
repair_command_id:F? -> repair_commands
commitment_kind:S(16)?
U(idempotency_key,event_sequence); U(supersedes_event_id); U(transfer_key,transfer_side)
U(id,material_requirement_id)
IX(material_requirement_id,demand_revision,occurred_at)
IX(purchase_requisition_item_source_id,event_type,occurred_at)
IX(purchase_order_item_id,event_type,occurred_at); IX(goods_receipt_item_id,event_type)
CHECK demand_revision>=1 AND event_sequence>=1 AND quantity>=0
CHECK event_type IN('demand_revised','withdrawn','coverage_allocated','coverage_released',
 'coverage_reassigned','commitment_confirmed','commitment_cancelled','supply_received','correction')
CHECK (event_type='coverage_reassigned' AND transfer_key IS NOT NULL
 AND transfer_side IS NOT NULL AND transfer_side IN('from','to'))
 OR (event_type<>'coverage_reassigned' AND transfer_key IS NULL AND transfer_side IS NULL)
CHECK (event_type='commitment_confirmed' AND commitment_kind IS NOT NULL
 AND commitment_kind IN('sent','supplier_ack') AND evidence_document_id IS NOT NULL)
 OR (event_type<>'commitment_confirmed' AND commitment_kind IS NULL)
CHECK event_type NOT IN('coverage_allocated','coverage_released','coverage_reassigned','supply_received')
 OR (quantity>0 AND purchase_requisition_item_source_id IS NOT NULL)
CHECK event_type<>'supply_received'
 OR (goods_receipt_item_id IS NOT NULL AND purchase_order_item_id IS NOT NULL)
```

WP2/C1 kiegészítés: generation_authorized and responsibility_handed_off included; Ghead/gen/line/root and nullable singleton fields section8; source-line budget completion under protected routine. responsibility_domain/recipient identity/proof typed required on handoff; exact recipient-role policy remains OPEN, no fake RMA owner for legacy command.

Immutable columns/transition scope: source+első allocated egy tranzakció, auditált legacy baseline egyszer.
Transfer két sora azonos key/from+to; nincs körkörös paired FK vagy utólagos összelinkelés.
Source/commitment mutex védi a mennyiségi conservationt és actual GR konverziót.
Owner/revision/key/snapshot nem írható át, correction új esemény.

Allowed writer: **procurement_generate/allocate/transfer/receive/cancel**, protected E/P routine; runtime raw DML deny. Delete: no event/business deletion, projection-only claim exception C1 finalizer; referenced parent effective NO ACTION. Migration dependency: requirement+command optional, G before source then allocation. WP7: **V15-V17/V19-V22/V38/V44** + common FK/U/NULL/immutable/permission tests.
Exact input provenance: [WP1 3.25](rma-0003c2-wp1-schema-inventory.md#325-procurement_coverage_events).

### 3.2. WP1 input → WP2 normatív javaslat delta-regiszter

| ID       | PROPOSED pontosítás a megőrzött inputhoz                                                                                                                                                                            | Miért / readiness gate                                                                                               |
| -------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------- |
| DELTA-01 | repair_materials.stock_movement_id és repair_releases.stock_movement_id helyett inverse owner link: stock_movements.repair_material_id / shipment_release_id, nullable, mindkettő scalar FK+U                       | Nem marad két előre hivatkozó immutable tény; fact parent létrejön előbb, movement utána; totality protected routine |
| DELTA-02 | repair_delivery_events.return_stock_movement_id és admission_return_key régi alak helyett WP4-P01: inverse typed MI/MA és admission_intake_key; event → future movement FK nincs                                    | REV-05/REV-01, eltérő intake/admission identity; régi unique generic movement nem marad második authority            |
| DELTA-03 | stock_movements.return_intake_event_id / return_admission_event_id / source_return_event_id; source_repair_release_id csak source context, shipment_release_id kizárólag outbound owner; return_purpose VARCHAR(32) | Composite A/I/release/serial, owner-field type XOR, 7. fejezet                                                       |
| DELTA-04 | procurement_coverage_events Ghead/line fieldjei WP5 szerint; source U(req,gen) eltávolítandó a proposed final tervből, U(PRitem,req) marad                                                                          | ACCEPTED REV02, 8. fejezet; head singleton más, mint source line                                                     |
| DELTA-05 | material_requirements stable O1 customer-item+cikk vagy repair-order+cikk XOR, U-k; current_revision_event pointer mutable; nem historical PO+BOM identity                                                          | O1 accepted, több-PO aggregate audit; O2 future distinct source lineage                                              |
| DELTA-06 | Credit latest_review pointer kezdetben nullable mutable; review history FK→már létrejött allocation; approval proof előbb quote/fee-re mutat                                                                        | Immutable history nem utólag UPDATE-elt; release totality és unique source-order továbbra kötelező                   |
| DELTA-07 | CHECK által érintett FK-oszlopon nincs expliciten választott CASCADE/SET NULL action; omitted default és exact DDL engine proof                                                                                     | WP1 effective RESTRICT szándék nem minden explicit syntax tested kompatibilitása                                     |
| DELTA-08 | VARBINARY occurrence+line key és BINARY digest codec felülírja az input S(n) gépi kulcs default collationjét                                                                                                        | Case/trailing-space/collation collision; Laravel cast/API compatibility V05/V45                                      |
| DELTA-09 | WP5 fee/usage/correction typed history fields, quote cancelled technikai representation alternatíva; new accounting/payment tables nincsenek                                                                        | Event-shape/type matrix kell; OPEN pénzügyi policy nem lesz default schema state                                     |

DELTA-01 nem változtat actual material/shipment immutable jelentésen. Régi `stock_movement_id`-k
csak inputnevek; a final outputban nem coexistálnak a választott inverse authorityval.
Genealogy optional movement link egy korábbi, létező mozgásra mutathat; nincs inverse mandatory
movement→genealogy backedge. Manufacturing production_task_materials nem kap itt automatikus új oszlopot.

### 3.3. A hét közvetlen existing change és a tizenegy review-only tábla

| Existing S table                  | Purpose/authority                                       | CONFIRMED CURRENT identity/type                                                | PROPOSED exact extension/key/null contract                                                                                                                                                                                         | Immutable/writer/dependency                                                                                                                                  | Deletion/verification                                                                   |
| --------------------------------- | ------------------------------------------------------- | ------------------------------------------------------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------ | --------------------------------------------------------------------------------------- |
| item_instances                    | Physical serial projection/trace                        | Existing PK id/serial U; origin PO nullable indexed without FK; no soft-delete | Add scope/allocation/verification and last_movement/ownership/disposition pointers nullable under expand; U(id,item_id), origin FK after audit; IX(scope,location),IX(allocation,scope,location)                                   | Owner/item/serial/origin immutable; pointers P writer only; masters/protected events first                                                                   | No SET NULL proof loss; V07/V18-V24/V37                                                 |
| stock_movements                   | Shared immutable physical ledger                        | Existing Q12,3, serial/batch/from/to/source morph nullable, no soft-delete     | Add WP1 scope/command/member/receipt/reservation/reverse links; typed repair_material_id and shipment_release_id FK+U; MI/MA/source-return fields+U/composites section7; scope IX and U(command,line),U(receipt member),U(reverse) | E stock posting only, every source/item/quantity/time field immutable; event parents before movement; no movement_type CHECK silently blocking current types | Old SET NULL and raw mutate guard, whole physical fact/projection txn; V08-V24/V37-V43  |
| stock_balances                    | Company-only mutable projection                         | Existing id, U(item,location,nullable batch), Q12,3; no soft-delete            | Add explicit batch_identity BIGINT UNSIGNED NOT NULL after verified mapping, U(item,location,batch_identity); CHECK map and q>=0; real batch FK effective RESTRICT                                                                 | Item/location/batch identity immutable; qty only P stock posting, no direct correction; depends master/audit/event pipeline                                  | No custody quantity column; E separate quarantine; V04/V05/V08/V38                      |
| stock_reservations                | Physical/workflow/logical reservation                   | Existing PK/FKs/status enum/soft-delete/reserved Q12,3; no counters or serial  | Add repair owner/purpose VARCHAR32/scope/serial/active keys/root/previous, consumed/released Q12,3; owner XOR shape, counters CHECK, active U/composite tuple/IX per WP1 and section6                                              | Owner/identity/reserved snapshot immutable; counters/keys/stage P only; legacy soft-delete remains but active/proof-referenced delete blocked                | Base legacy nullable flags not proof; no auto counter backfill; V08-V12/V24/V28         |
| material_requirements             | O1 stable owner demand/head                             | Existing customer-item required, Q12,3 totals, no owner U, SoftDeletes         | customer owner nullable +repair_order nullable XOR; U(customer,item),U(repair,item); revision/gen/state and current_event FK; IX demand/item and repair/status                                                                     | Owner+item+unit stable, exact revisions/history, P demand protocol; root before revision then mutable pointer                                                | No historical PO+BOM remap; source/event ref retention; V15-V17/V38/V44                 |
| purchase_requisition_item_sources | Initial immutable source anchor                         | PK id, PRitem+req FKs CASCADE, U(PRitem,req), Q12,3, no SoftDeletes            | Revision/gen/Ghead/line key/full generation_key U, U(req,creationgen,linekey); no U(req,gen); both FK effective RESTRICT; IX Ghead/req                                                                                             | E source creation part of complete budget operation, never adjust snapshot for current coverage; Ghead then source then allocation                           | Parent deletion/procurement authority, generation history retained; V15-V17/V19-V22/V44 |
| purchase_requisition_items        | Existing shared PRline and compatibility representative | PK id, headerCASCADE/direct req SET NULL/itemRESTRICT; Q12,3, SoftDeletes      | No new columns; direct req FK effective NO ACTION, sources authoritative full owner list; existing IX/status and immutable source lineage guard                                                                                    | PR domain protected routines; published/committed amounts frozen or explicit authorized amendment; source budget and header locks                            | Header cascade/soft delete contract gated; V15-V17/V19-V22/V37/V44                      |

Review-only R (11): `item_instance_relations`, `purchase_requisitions`, `purchase_orders`,
`purchase_order_items`, `goods_receipts`, `goods_receipt_items`, `production_tasks`,
`production_task_materials`, `production_orders`, `documents`, `locations`.
Current PK/FK/U/IX/null/soft-delete/quantity részletek a WP6 2.2 név szerinti migráció/model
profiljaiban; nincs önkényes nyolcadik direct schema change. R old CASCADE/SET NULL/source morph,
document-file retention és writer műveletek protected boundaryba vonása ettől még kötelező.
Ha exact integrity proof további módosítást igényel, külön change-list/approval gate; nem rejtett új tábla.

### 3.4. Existing extension mezőleltár – PROPOSED / NOT EXECUTED

A hét S tábla új/megváltozó oszlopai, a common F/Q/type/nullable szabályokkal. A current többi
oszlop nem tűnik el; a teljes régi PK/FK/U/IX/null/delete/cast profil WP6 2.2-ben linkelt actual
migráció/model. Status itt string+enforced type CHECK a választott technical listára, nem MySQL ENUM
implicit sorszámmal; current model enum casting adaptációt igényel, nem most végzett módosítás.

```text
item_instances:
 current_inventory_scope:S(24)? IN(company_stock,customer_custody,external)
 allocation_state:S(24)? IN(available,reserved,in_transit,quarantined,blocked,consumed,scrapped)
 ownership_verification_state:S(16)? IN(verified,unresolved)
 last_stock_movement_id:F? -> stock_movements
 latest_ownership_event_id:F? -> repair_ownership_events
 last_disposition_history_id:F? -> repair_status_histories
 production_order_id existing nullable F: add FK after orphan audit
 U(id,item_id); IX(current_inventory_scope,current_location_id)
 IX(allocation_state,current_inventory_scope,current_location_id)
stock_movements:
 from_scope:S(24)?; to_scope:S(24)? (expand NULL; contract only verified mappings)
 repair_command_id:F? -> repair_commands; command_line:U32?
 repair_request_item_id:F? -> repair_request_items
 receipt_request_item_id:F? -> repair_request_items
 stock_reservation_id:F? -> stock_reservations
 reverses_stock_movement_id:F? -> stock_movements
 source_repair_release_id:F? -> repair_releases (context only)
 repair_material_id:F? -> repair_materials (owner); U(repair_material_id)
 shipment_release_id:F? -> repair_releases (owner); U(shipment_release_id)
 return_intake_event_id:F? -> repair_delivery_events (owner); U(return_intake_event_id)
 return_admission_event_id:F? -> repair_delivery_events (owner); U(return_admission_event_id)
 source_return_event_id:F? -> repair_delivery_events (context only)
 return_purpose:S(32)?
 U(repair_command_id,command_line); U(receipt_request_item_id); U(reverses_stock_movement_id)
 U(id,item_instance_id); IX(from_scope,item_id,performed_at); IX(to_scope,item_id,performed_at)
 IX(repair_request_item_id,performed_at)
 FK(receipt_request_item_id,item_instance_id) -> repair_request_items(id,item_instance_id)
 typed MI/MA candidate tuples section7; old return_event_id/return_stock_movement_id generic authority omitted
stock_reservations:
 repair_order_id:F? -> repair_orders; reservation_purpose:S(32)?
 reservation_inventory_scope:S(24)?; item_instance_id:F? -> item_instances
 active_item_instance_id:F? -> item_instances; U(active_item_instance_id)
 active_replacement_order_id:F? -> repair_orders; U(active_replacement_order_id)
 consumed_quantity:Q!; released_quantity:Q! (zero only after verified initial/legacy proof)
 current_replacement_stage:S(32)?
 replacement_root_reservation_id:F? -> stock_reservations
 previous_reservation_id:F? -> stock_reservations; U(previous_reservation_id)
 U(id,item_instance_id); U(id,repair_order_id); U(id,repair_order_id,item_instance_id)
 FK(previous_reservation_id,repair_order_id,item_instance_id)
   -> stock_reservations(id,repair_order_id,item_instance_id)
 FK(replacement_root_reservation_id,repair_order_id,item_instance_id)
   -> stock_reservations(id,repair_order_id,item_instance_id)
 IX(repair_order_id,reservation_purpose,status); IX(item_instance_id,reserved_at)
stock_balances:
 batch_identity:BIGINT UNSIGNED NOT NULL only after verified expand/map/contract
 CHECK((batch IS NULL AND identity=0) OR (batch IS NOT NULL AND identity=batch AND identity>0))
 U(item_id,location_id,batch_identity); quantity>=0 after actual audit/contract
material_requirements:
 customer_order_item_id existing F becomes nullable; repair_order_id:F? -> repair_orders
 demand_revision:U32!; demand_state:S(16)! IN(active,superseded,withdrawn)
 procurement_generation:U32!; current_revision_event_id:F? -> procurement_coverage_events
 revision>=1; gen>=0; required/available/reserved/missing Q>=0
 exactly one customer-item/repair-order owner; U(customer_order_item_id,required_item_id)
 U(repair_order_id,required_item_id); IX(repair_order_id,status); IX(demand_state,required_item_id)
purchase_requisition_item_sources:
 demand_revision:U32!; procurement_generation:U32!; generation_head_event_id:F! -> procurement_coverage_events
 source_line_key:VARBINARY(128)!; generation_key:VARBINARY(128)!
 revision/gen>=1; quantity>0; U(generation_key); U(req,gen,line_key)
 keep U(PRitem,req), no U(req,gen); IX(generation_head_event_id), IX(material_requirement_id)
purchase_requisition_items:
 no new column; existing direct material_requirement_id nullable FK action becomes effective NO ACTION
```

Nem kell minden új nullable oszlop minden legacy sorra kitöltve legyen az expand alatt. Posting
contractban viszont purpose/scope/stage/active-key shape a művelet szerint kötelező: aktív physical
serial-slot nonNULL iff ténylegesen kizáró quantity-allocation; replacement logical order-slot külön.
`replacement_redispatch_custody` S(32)-ben elfér, workflow company-delta0. További purpose/stage pontos
technical whitelist és old mapping F05 gate, nem önkényes default company purpose minden sorra.
Stock-movement owner mezők közül az új typed material/shipment/MI/MA fajtánál pontosan a megfelelő
owner nonNULL, a többiNULL; legacy type-on mindNULL, source morph kompatibilitás megőrzött. Current
mozgástípusokat nem tiltja új RMA-only type CHECK. Semantic type→scope→owner whitelist finalizálandó,
nem generic external→external kiskapu. A generic source morph nem második authoritative RMA owner.

PROPOSED hét-S FK action contract a tényleges constraintnevek catalog-egyeztetése után:
item_instances current_location SET NULL → omitted NO ACTION; stock_movements meglévő batch/serial/
from/to/performed_by SET NULL → omitted NO ACTION; stock_balances batch SET NULL → omitted NO ACTION;
stock_reservations customer/PO CASCADE és batch/location/reserved_by SET NULL → omitted NO ACTION;
material_requirements customer-item CASCADE → omitted NO ACTION; source mindkét CASCADE → omitted
NO ACTION; PRitem direct-requirement SET NULL és header CASCADE → omitted NO ACTION. A többi már
restrictive FK megmarad. Ez nem új nyolcadik érintett S tábla, de több constraint transition;
legacy deletion-workflow és minden CHECK/FK kombináció V02/V37/V41 feltétele. Az R táblák mögötti
régi CASCADE/SET NULL út csak akkor maradhat, ha a protected parent-delete művelet a hivatkozott
tényeknél elutasítja; ha ez nem bizonyítható, további explicit DDL-scope review szükséges.

Generation key opaque canonical <=128-byte occurrence identity, nem korlátlan req/gen/line
string-összefűzés. Full req/gen/line candidate U és payload hash köti a jelentést; hash-key alternative
BINARY(32) codec review-val, collision/payload mismatch safe conflict, sosem más sor csendes reuse.
Reservation location csak bizonyított physical transferrel, old/new pool/balance zárakkal változik;
owner/item/batch/serial/purpose snapshot nem. A reservation consumed_counter célfüggő fulfillmentet
(material consume vagy shipment) mér, nem automatikusan material requirement actual_C-t.

## 4. MySQL relational integrity

### 4.1. Engine és deklaratív korlátok

Tervezési profil: **MySQL 8.4, InnoDB**, enforced CHECK, strict SQL mode, utf8mb4 szöveg és byte-exact
gépi kulcs. A történeti B MySQL8.4.7 eredmény nem igazolja a jelenlegi szerver/motor/grant állapotot.
InnoDB FK azonnali, nincs deferred commit-time ellenőrzés; parent tuple és child index exact típusban
kell. Default NO ACTION effective RESTRICT; virtual generated column nem lehet referenced FK target.
Forrás: [Oracle MySQL 8.4 FK](https://dev.mysql.com/doc/refman/8.4/en/create-table-foreign-keys.html).

CHECK TRUE vagy UNKNOWN esetén elfogadhat, ezért minden nullable branch explicit IS NULL/IS NOT NULL.
Foreign table/subquery, AUTO_INCREMENT oszlop, nondeterministic/session/stored-function operandus
nem használható. CHECK-ben szereplő FK-oszlopok referential-action korlátja miatt omitted defaults
és exact CREATE/ALTER kombináció külön V02; nem általános „RESTRICT kiírva is biztos jó” állítás.
Forrás: [Oracle CHECK](https://dev.mysql.com/doc/refman/8.4/en/create-table-check-constraints.html).

Ha V02 az exact CHECK+FK+action kombinációt elutasítja, implementálható technikai alternatíva:
az FK megmarad restrictive, a FK-mezőket érintő shape feltétel BEFORE INSERT/UPDATE triggerbe
kerül, a sima numeric counter CHECK megmarad. Triggerben `IF (row_shape) IS NOT TRUE THEN SIGNAL`
védi a NULL UNKNOWN ágat; NEW/OLD mezők összehasonlítása null-safe. E alternatíva is DB-enforcement,
nem application-only fallback; ugyanúgy grant/DDL/raw-write negatív próbát igényel. Nem szabad
FK-t kikapcsolni, CHECK-et NOT ENFORCED-re gyengíteni vagy proof_ok flaggel helyettesíteni.

Optional composite FK-ben bármely NULL rész felmentheti a tuple ellenőrzését. Így nullable operation/order,
member/serial, A/I/proof és credit review tuple-okra külön all-or-none/conditional shape kell.
Parent-type=I, matching owner/currency/current scope, kötelező child és fee/source SUM nem FK/CHECK;
protected procedural guards és runtime privilege fence szükséges. U(id,owner) nem új domain ID.

### 4.2. FK-ciklusok és creation protocol

Az alábbi ciklusok a WP1/WP4/WP5 **tervgráfjának** erősen összefüggő részei, nem current RMA-schema hibák.
A hosszabb útvonalak több ilyen rész összetételéből állnak; minden új FK hozzáadásakor SCC újraszámítás
kell. Egy projection backedge és valódi immutable forward/backedge kezelése nem azonos.

| Cycle | Participating graph                                                                                                                                      | Direct-insert problem                                                                                         | Implementable PROPOSED resolution                                                                                                                                                                    | Transaction/concurrency consequence                                                                                                                   | WP7                     |
| ----- | -------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------- |
| CY01  | repair_request_items → stock_movements → member/receipt FK; movements→instance→last movement                                                             | Member needs future receipt, movement needs existing member; projection cycle                                 | Insert member receiptNULL +claim/order; movement owns member; update receipt/time and custody/instance once in same T                                                                                | case→item→serial→order/reserve→balance/proof; duplicate member/receipt U                                                                              | V01/V07/V13/V28         |
| CY02  | repair_materials ↔ stock_movements typed owner; repair_releases ↔ stock_movements owner                                                                | Both immutable forward links cannot be satisfied without placeholder UPDATE                                   | DELTA01 inverse only: immutable material/release → movement FK to parent; parent inverse query, mandatory child verified before operation returns                                                    | Actual consume/shipment T with whole stock/counter/audit; unique movement owner and command line                                                      | V01/V08/V13/V38/V43     |
| CY03  | repair_delivery_events ↔ stock_movements; I/A ↔ status histories ↔ inspections                                                                        | Generic event movement pointer ambiguous, future A basis history could point back to A                        | DELTA02/03: I→MI; basis/inspection references existing I; A references that prior basis/I; MA references A/I; no future A reference in basis                                                         | Return/admission separate T, case/serial/source locks, U(I occurrence),U(A intake key),U(MI/MA owner)                                                 | V01/V18-V23             |
| CY04  | item_instances.last_movement/latest_ownership/last_disposition → events/movement → item_instances; custody.last movement→movement→member→instance        | Mutable state points into immutable history whose subject is same row                                         | Create/use stable instance first; pointersNULL until fact exists; update only projection under serial mutex; custody created after actual movement                                                   | No immutable UPDATE, no ownership default; one serial current projection, T includes proof                                                            | V07/V18-V24/V37         |
| CY05  | credit_allocation.latest_review → histories.allocation → allocation; quote.scope/approval histories indirect order chain                                 | Latest review cannot exist before allocation it reviews                                                       | Approval proof first on existing quote/fee; allocation initial latestNULL; insert complete typed review FK→allocation; update mutable pointer only                                                   | One case/order mutex, active-source U, approval+allocation+history+audit T                                                                            | V01/V25-V27/V33/V43     |
| CY06  | material_requirements.current_revision → coverage_events.req; events.source → sources.Ghead → events; source→PRitem.direct req                           | Source anchor and allocation would need each other; req head points to child fact                             | RequirementNULL head → demand/Ghead event no source → PRline/source FK→G → line allocation FK→source/G → requirement mutable head. Existing PRline parent created before source, not after its child | Item/all-req/PR/PO/GR-root locks; G+sources+exact budget+audit one T                                                                                  | V01/V15-V17/V19-V22/V43 |
| CY07  | stock_reservations previous/root self-FKs; release.reservation→reservation.serial→instance→movement→release                                              | Root first row cannot point forward to generated own ID; future attempt cannot preexist                       | Root row previous/rootNULL means self-root; successors root=first existing ID and previous=prior; active obligation slot transfer only mutable rows, shipment parent then movement child             | Prior attempt/condition/D9 proof, case/serial/order/root locks, no reset counters                                                                     | V01/V10/V13/V23/V24     |
| CY08  | ownership.previous/reverses/supersedes, time.supersedes, fee.supersedes, genealogy.related_install/supersedes, operation.supersedes, delivery.supersedes | Self FK permits existing predecessor but alone no no-cycle/same subject/effective sequence                    | Only existing eligible predecessor, same typed subject/order, no self/new future link; monotonic sequence and unique successor/reverse under aggregate mutex; correction append-only                 | Graph genealogy locks whole planned connected component+item/serial set and revalidates closure; insert id checked after allocation, no auto-ID CHECK | V01/V24/V26/V34-V37     |
| CY09  | case.closed/command.complete → close histories/claims → member → case; command.result generic optional response                                          | Trying command-result FK to future business child would cycle; claim-delete guard needs completed close proof | Command first processing result morph not FK; typed proof history+case closed+complete command before guarded claim DELETE, all in same public finalizer T                                           | No partial helpers/raw DML; case root locks also every child writer; no reopen                                                                        | V28-V32/V38/V41-V43     |

Közös transaction T: admission/member/source/credit/reservation írás sem válhat külön kézzel
commitolható lépéssé. Forward references csak mutable projectionnál lehetnek átmenetileg NULL-ok,
és csak a teljes protected public operationben állnak finalra. Immutable event „előbb üresen,
később feltöltve” tiltott. Nincs foreign_key_checks=0, event UPDATE, generic patch-row vagy public
helper-routine EXECUTE. A runtime nem hozhat létre önálló orphan command/event/proof sort.

DDL dependency: masters+existing PK/U → command → case/member/order → fee/quote/lines → other facts,
events/claim acquire trigger → typed movement children és histories → mutable pointer/composite
FK-k külön ADD fázisban → guard/routine/privileges. A CREATE sorrend nem row-insert sorrend.
Az SCC miatt FK későbbi ALTERrel felvehető **schema-buildkor**; ez nem business FK-disable.
Minden parent candidate U a rá mutató FK előtt; live ALTER és orphan audit WP6 gate.

## 5. Nullable identity

MySQL UNIQUE megengedi a nullable kulcs több NULL értékét; így a régi U(item,location,batch)
nem védi az összes batch nélküli balance egyszeriségét.
[Oracle UNIQUE](https://dev.mysql.com/doc/refman/8.4/en/create-index.html).

| Alternative                    | MySQL compatibility                                                                                       | Index behavior                                       | Legacy migration                                                                 | Concurrency                                                                       | Read/Laravel compatibility                                                | Review disposition                                                                              |
| ------------------------------ | --------------------------------------------------------------------------------------------------------- | ---------------------------------------------------- | -------------------------------------------------------------------------------- | --------------------------------------------------------------------------------- | ------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------- |
| Generated normalized column    | STORED generated COALESCE(batch,0)+U; virtual cannot be FK target, exact generated/FK restrictions tested | Nulls become deterministic indexed key; no app drift | Still audit duplicates/id0 and rebuild/index cost; not auto merge                | U arbitrates create race with parent mutex                                        | Direct indexed lookup; ORM generated attribute read-only codec needed     | Viable alternative; engine/FK/action support V02/V04, not selected over C1 explicit contract    |
| Explicit normalized key        | BIGINT UNSIGNED NOT NULL + mapping CHECK + scalar real FK + U                                             | Full nonNULL tuple index; deterministic equality     | Verified mapping then NOT NULL/U; remove old SET NULL before CHECK compatibility | Item mutex + U + protected writer prevents divergence; raw checked drift rejected | Fast point/pool lookup; fillable not public, relation stays item_batch_id | Preferred PROPOSED, matches C1/WP1; actual catalog and migration proof still absent             |
| Separate identity table        | New row keyed normalized identity linked balance                                                          | PK/U can serialize identity independently            | Would add table outside25, ID/backfill/cycle/master ownership work               | Extra identity lock and mandatory-child totality required                         | Extra join/write; Eloquent new model and relation                         | Not selected, new scope/approval required; no hidden table26                                    |
| Application-only firstOrCreate | Existing nullable U + service existence query                                                             | NULL uniqueness remains weak                         | No schema change but duplicates may persist                                      | Two callers can see missing row and both insert; no DB totality                   | Existing ORM simplest but unsafe identity guarantee                       | Rejected for critical identity; only in combination with real U/CHECK and protected transaction |

**Preferred PROPOSED O1 balance:** a C1/WP1 explicit `batch_identity BIGINT UNSIGNED NOT NULL`,
NULL batch→0, real batch→id>0; U(item_id,location_id,batch_identity). Valódi item_batch_id nullable
FK marad a real batchre, nem kell fiktív batch0 master. Routine és row CHECK tartja az egyezést;
orphan/id0/sentinel collision/duplicate adat proof előtt nincs backfill/contract. ItemBatch
`item_id` egyezése scalar FK-ból nem következik, protected owner/item guard vagy approved composite
parent U kell; utóbbi új existing master change igénye külön leltár-kapu, nem hallgatólagos.

| Identity                    | Preferred representation és NULL-ág                                                                       | Korlát/proof                                                                                                                    |
| --------------------------- | --------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------- |
| Item batches                | Current PK id, U(item_id,batch_number), item FK; a balance-normalizer nem új batch                        | Batch-number unit/item scope actual catalogból; nincs új global U kitalálva                                                     |
| Serial                      | Current item_instances.serial_number U + immutable numeric instance PK; új U(id,item_id)                  | Legacy collation/trim/case és physical serial audit; normalizálás nem merge vagy serial-regeneration                            |
| Original custody            | U(repair_request_item_id), U(item_instance_id); parent tuple; current projection movement-derived         | Replacement return nem kap fake original custody-sort; customer ownership nem scope-default                                     |
| Intake I/MI                 | I physical namespace+key all nonNULL, U(namespace,key); MI U(return_intake_event_id) és typed owner tuple | Új command ugyanarra physical returnre nem új occurrence; source namespace autoridade és proof guard                            |
| Admission A/MA              | A admission_intake_key nonNULL iff A, =related_return_event_id, U; MA U(return_admission_event_id)        | Company vagy custody target közül összesen egy admission/I; other eventek NULL-ja szándékos                                     |
| Reservation physical serial | active_item_instance_id nullable U, ha physical serial-allocation active akkor =instance, külön row-shape | Scope/purpose/status/counter szerint; in_transit/quarantine projection akkor is tilt consumptiont, ha physical slot már NULL    |
| Replacement obligation      | active_replacement_order_id nullable U + root/previous chain                                              | Nem release a consumed statusból; logical obligation current slot atomikusan átadható új attemptnek, nem full reserve deduction |
| Credit origin               | U(active_source_order_id), reserved/retained esetén nonNULL=source_order, released esetén NULL+proof      | Fee-version nem több credit; all-null composite bypass shape tiltott                                                            |
| Generation/line             | Ghead U(req,gen,head_slot=1), source/line U és full occurrence                                            | Head és line singleton külön; multiple source-line accepted                                                                     |

Nullable U a conditional singletonra szándékos, a „kötelező active kulcs legyen NULL és így bypass”
nem. A CHECK stringstatus listát és explicit nullabilityt védi, az active state-transition/proof/serial
owner és teljes immutable payload változatlanságát routine és UPDATE/DELETE trigger védje.

## 6. Reservation conservation

### 6.1. Row és ownership contract

Existing reserved_quantity Q>0; új consumed_quantity/released_quantity Q NOT NULL, valid baseline után0;
remaining derived exact Q = reserved−consumed−released, nem külön függetlenül írható amount.
Row CHECK: counters>=0 és consumed+released<=reserved. Owner XOR:
repair_order_id nonNULL → customer_order_item_id és production_order_id NULL;
repair_order NULL → legalább egy valid legacy owner, ha mindkettő van a PO ugyanazon customer-itemhez
tartozik. A legacy két mező között nincs új XOR. Parent/owner/item/unit/batch/serial és purpose immutable.

Purpose/scope/stage C1/WP3 szerint: company quantity, original/customer workflow és logical replacement
obligation külön. Nem minden active reservation csökkenti normál stockot; karanténból eleve kizárt
q-t nem vonunk le ismét. Serial q1; scalar instance FK és item consistency proof. Active owner keys
és actual remaining külön, nem statusból visszaszámolt physical fulfillment.
StockReservationStatus existing értékei current enum szerint; ha counters vegyesen terminalak,
status csak kompatibilis derived projection, nem új üzleti állapotpolitika vagy counters reset.

### 6.2. Protected mutation protocol

| Operation         | Row/posting effect                                                                     | Kötelező guard / failure                                                                                      |
| ----------------- | -------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------- |
| Create            | reserved=q, consumed/released0, immutable creation evidence, owner/purpose/active keys | Fresh E pool under item+owner/serial locks; q<=unassigned eligible; no correction of ambiguous legacy         |
| Partial consume c | consumed+=c, actual material+movement, physical balance-=c, immutable typed history    | c>0, c<=remaining, same owner/item/unit/place/serial, E/purpose/approval valid; no full-row Consumed shortcut |
| Partial release r | released+=r, immutable release evidence, physical delta0                               | r>0,r<=remaining; release permission+reason; not system quarantine/claim/retained logical obligation release  |
| Full consume      | c=remaining →remaining0, used evidence preserved                                       | Release later r>0 rejected; no reactivation by return                                                         |
| Full release      | r=remaining →remaining0, release proof preserved                                       | Consumption later c>0 rejected; no fake outbound                                                              |
| Cancellation      | Authorized workflow decision, actual unused quantity release separately                | Cancel≠consume≠shipment; D9/D11 logical/procurement residual review; consumed q never released                |
| Duplicate         | Same command/type+key+hash returns canonical result                                    | New key same occurrence U conflict; different payload/actor conflict, never silently successful retry         |

Mutating routine locks current row FOR UPDATE, conditional UPDATE WHERE remaining>=q plus rowcount=1,
then actual fact/movement/history/audit in same T. Balance update cannot be separated from ledger.
Critical pool cap is cross-row: all reserve/consume/release/GR callers serialize on existing items
PK and involved parent owners, not unlocked SUM or absent balance row. Missing balance creation
under item mutex + normalized U; direct helper writes unavailable to runtime. No SKIP LOCKED shortage.
Append-only history claim dimension includes reservation ID, command/sequence, before/after exact
counters, proof/actor/time; production non-RMA occurrence uses stock movement/audit identity and shared
posting authority, no fictitious repair owner. If exact evidence shape cannot express legacy release,
mapping remains blocked instead of invented used/released amounts.

Read-only reconciliation specs (later WP7): group effective actual movement/material by reservation,
compare counters and typed history fold; compare sum active eligible remaining per physical pool
with allocatable stock; compare logical-slot chain vs all attempts; reverse morph ambiguity classified
UNKNOWN. Full material consumption may historically lack explicit reservation link, so no mass
`status=consumed → consumed=reserved` backfill. Raw SQL direct counter/event/balance modification denied.

## 7. Return custody and admission

**PROPOSED adopts WP4-P01:** I physical return event owns MI via inverse FK; A admission event references
I and owns MA via inverse FK. U(A.admission_intake_key) excludes second company/custody classification;
U(MI owner) and U(MA owner) at-most-one movement each. Mutable member/instance/custody pointers update
only after facts exist in T. No generic event.return_stock_movement_id ambiguous dual meaning.

| Record/transition            | Typed identity, required links és effect                                                                                    | DB/procedure guard                                                                                                  |
| ---------------------------- | --------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------- |
| I physically_returned        | release/member/serial/role, actual timestamp/location/proof, source namespace/key; ownership may unresolved                 | All tuple parents valid/current source; physical return U; no A/basis requirement at intake                         |
| MI replacement_return_intake | return_intake_event_id, source release+serial, qty1, external→external, pending_admission_quarantine, physical actual place | U(I), event_type=I guard, no admission/reverse owner; company delta0, quarantine mandatory                          |
| Inspection/basis             | return_quarantine inspection, subject/order/return, condition/right/owner evidence and named authorizer                     | Parent I, not future A; cross-row type/subject/doc-version integrity; unknown rights do not imply company           |
| A return_reconciled          | related_return_event_id=admission_intake_key, target company_stock/customer_custody, authorization+basis histories          | U(intake key); A1–A10 WP4 fresh guards, same source/serial/current place, no admission from bare failed delivery    |
| MA return_stock_admission    | return_admission_event_id+source_return_event_id+source_release+serial composite; qty1 external→target                      | U(A), company+1 only verified basis, custody0; sourceReturn=A.relatedReturn; company reverse prior dispatch, not MI |
| Quarantine release           | Separate authorized typed history+instance/allocation projection; qty0                                                      | Current admitted/company/right/condition/inspection proof, no new A or inbound movement; E enabled only here        |
| Redispatch/disposition       | New authorized reservation/release/physical outbound with D9 root/attempt; original outcome independent                     | Old counters not reset; custody workflow company0; pending scrap/transfer outside accepted allowlist OPEN           |

Composite MA FK tuple `(return_admission_event_id,source_return_event_id,source_repair_release_id,item_instance_id)`
→ A U(id,related_return_event_id,repair_release_id,item_instance_id); all fields nonNULL iff MA.
MI tuple `(return_intake_event_id,source_repair_release_id,item_instance_id)`→I U(id,release,serial).
These do not prove event_type/legalbasis or mandatory movement child: posting routine validates before
return and public raw DML cannot insert/commit event alone. I→MI immutable inserts do not UPDATE I.

Failure/late delivered facts stay with their own release attempt; conflicting proof reviewed, not
latest timestamp wins. Original customer custody never auto company. W legal transfer/reversal separate
from I/MI/A/MA, own effective source proof and serial chain, no balance delta. Full company dispatch
compensation U(reverses_stock_movement_id) cannot be reused by MI or already compensated admission;
not a legal reversal. Unmatched physical arrival goes secure discrepancy/quarantine per WP4 OPEN
workflow; no fictional serial/origin/receipt, no silent operational disappearance.

## 8. Procurement source-line integrity

### 8.1. Exact identity és type-shape

O1 existing material_requirements and purchase_requisition_item_sources authoritative identity.
WP5 Ghead lives in procurement_coverage_events, not new table26. Proposed exact fields:
`procurement_generation INT UNSIGNED NULL`, `generation_singleton_key TINYINT UNSIGNED NULL`,
`generation_head_event_id BIGINT UNSIGNED NULL`, `source_line_key VARBINARY(128) NULL`,
`generation_allocation_slot TINYINT UNSIGNED NULL`; scalar self-FK Ghead and U(id,requirement).
Head iff event_type=generation_authorized: gen>=1, singleton1, q=authorized_delta>0,
source/PO/GR/head-parent/line-slot NULL, immutable canonical line-plan/hash.
Other types singleton NULL. U(req,procurement_generation,generation_singleton_key) protects only head.

Source anchor adds creation revision/gen, generation_head_event_id FK, source_line_key nonNULL,
generation_key VARBINARY(128) nonNULL UNIQUE. Keep U(PRitem,req); candidate
U(req,creation_gen,source_line_key). Composite Ghead/requirement FK plus guard type/gen/revision.
If several intents consolidate same PRitem+req, one anchor and several explicit line allocation events;
initial source.quantity equals their initial sum, immutable, not another fold addend.
One source anchor may later receive an authorized delta only if actual PRline/commitment amendment
allowed; otherwise new PRline/source. No historical Proposal-source FK disguised as requirement-source.

Incoming G-line allocation iff authorized initial coverage_allocated or transfer to-side identified by
line-plan: head+line+allocation_slot1 nonNULL; U(Ghead,line,allocation_slot). Each G line assigned once,
sum incoming line quantities = G budget at completed command, same req/item/unit/revision and line hash.
An incoming transfer counts toward that transfer-plan, **not** a second new-purchase budget.
Repeat receipt/review/correction has allocation_slot NULL, does not block future legitimate facts.

| Event                     | Required context / nullable shape                                                                                           | Quantity fold                                                                                 |
| ------------------------- | --------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------- |
| generation_authorized     | req/revision/gen/headslot, q>0, immutable line-plan; source/PO/GR NULL                                                      | Budget, not open coverage +q                                                                  |
| demand_revised/withdrawn  | req before/after revision snapshot, explicit owner/item/unit; source/PO/GR NULL unless separately typed approved correction | Demand only, no automatic supply/release                                                      |
| coverage_allocated        | req/source/G/line/root/command occurrence, q>0                                                                              | +q internal intent or assigned source, not physical stock                                     |
| coverage_released         | req/source/root, q>0, authorized reason and retained supplier responsibility if firm                                        | −q assigned open, not supplier cancel                                                         |
| coverage_reassigned       | transfer_key and from/to, source/target roots/req, q>0; both sides in one public operation                                  | −q old/+q new, q/unit/item conservation, no circular pair FK                                  |
| commitment_confirmed      | req/source/root+POitem and sent/ack type/proof, q>0 exact assigned portion                                                  | Reclassifies I to K, not second +q; effective proof/version chain                             |
| commitment_cancelled      | req/source/root+POitem+cancellation evidence, no GR                                                                         | Adjust firm outstanding; matching release/transfer explicit, not fake receipt                 |
| supply_received           | req/source/root+POitem+posted GRitem, q>0; event occurrence/owner/root uniqueness                                           | −q open; physical receipt once, Ge may0 in quarantine                                         |
| responsibility_handed_off | original req/source/commitment and named recipient/domain/doc/status/q snapshot                                             | No extra ±q; assignment release/transfer separately                                           |
| correction                | original/supersedes event, typed reason/proof/actor; shape/effect inherited from corrected type                             | Choose effective replacement fold, never add both superseded and correction as independent +q |

All fields not needed by a type explicitly NULL or documented context; no partial nullable tuple.
Full type enumeration includes WP5 new generation_authorized/responsibility_handed_off, superseding WP1
short list. Arbitrary JSON source/owner identity is not accepted. Every event PK immutable, U(key,sequence),
U(supersedes), U(transfer,side); roots FK→existing immutable allocation. Qty range/scale same base unit.

Root-nullability pontosítás: az első incoming coverage_allocated vagy reassign-to sorban
`related_allocation_event_id IS NULL` jelenti a sor saját, beszúráskor kiosztott root identityjét.
Későbbi release/receipt/commitment/correction a már létező root ID-jára mutat; a routine ellenőrzi
root-type/source/owner/unit egyezését. Nem írjuk UPDATE-tel az első immutable sor rootját a saját ID-jára.
Új to-side saját incoming rootot képez, a from-side előzménye a közös transfer_key két sorával
követhető; nincs előre mutató paired FK. Ha más végleges oszlopnév választódik, ez a NULL→self
jelentés és a typed existing-parent ellenőrzés változatlanul kötelező a cycle-safe DDL-ben.

### 8.2. Conservation, transactional enforcement és failure

```text
N = max(0, authorized_D - actual_C)
open_assigned(root) = allocations + transfer_in - releases - transfer_out - actual_received >= 0
sum(initial incoming lines per G) = authorized_G_budget
sum(active allocated commitment portions) <= actual unreceived firm quantity
sum(received owner portions per GRitem) <= actual posted GRitem.quantity
new_buy_delta = max(0, physical_shortage - valid_uncommitted_intent_I - firm_unreceived_K)
```

These are not foreign SUM CHECKs. Protected generate/allocate/transfer/receive/cancel routines lock
items, every affected owner/requirement, G/root/source and PR/PO/GR identities in L order, then compute
fresh current effective fold. Read roots/events via current locking reads, not old RR consistent SUM
snapshot. Immutable child insert serialization depends on **every** writer owning same mutex; unguarded
direct event DML is revoked. Routine checks all planned line-key/quantity totals before completion and
all current PRline/source/POitem unit+owner caps, writes facts/projections/history/audit together.
Error → full T rollback; no orphan Ghead, partial transfer side or source alone may commit.

Demand mutex alone not enough if two different owner requirements share item/free stock/PR/PO/GR;
item/root/POitem/GRitem cap locks required. Each GRitem received assignment across owners shares one
actual GRitem-cap lock; unassigned remainder is real procurement responsibility, not fake RMA req.
Closed PO enum and source existence do not prove current coverage, supplier commitment or actual GR.
Cancel cannot release consumed/received quantities, supplier change cannot rewrite sent/ack history.
Authorized old cancellation+new source/commitment preserves causal lineage; substitution/allocation
priority/overreceipt policy OPEN. Partial fulfillment retains residual K, actual G no longer incoming.
Custody/quarantine cannot satisfy usable stock merely from G. Actual C remains material+movement.

Before future O2: keep stable O1 requirement/source PKs, explicit typed boundary to separate Proposal
sources/pegs, supplier-authority/snapshots/readiness and precision reconciliation in separately approved
integration. Do not hardcode future one-proposal=one-requirement, mutate original owner IDs, repurpose
pegs as reservations or add polymorphic source_type/id instead of proved FK. O3 coexistence not selected.

## 9. Financial credit integrity

Estimate, quoted/approved fee, final fee, payment evidence, outstanding and diagnostic credit are separate
WP5 concepts. No invoice/tax/refund/payment-provider subsystem or automatic policy in schema. Verified
fee immutable version/cap source; quote approval is not payment or usage. Money M+currency+scale,
quote lines exact total and sign; multiply Q×M with reviewed scale/overflow, not implicit rounding.

PROPOSED cancellation representation: a quote cancellation önálló typed history-fact;
a tárolt status='approved' önmagában nem effective authorization, ha azóta cancellation/supersede
proof keletkezett. Current approval/start guard a teljes hatályos historyt ellenőrzi.
Így draft cancellation sem kényszerít fiktív submitted_at/by/hash kitöltést. A WP5-ben felvetett
külön cancelled enum alternatíva marad; annak teljes draft/submitted shape-je még nincs kiválasztva,
ezért nem került észrevétlenül a fenti status CHECK-be. Ez technikai representation, nem új cancel-policy.

| Integrity            | Representation / DB or procedure enforcement                                                                                              | Failure / proof                                                                                       |
| -------------------- | ----------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------- |
| Origin/order         | Fee U(order,version), supersedes same order; allocation composite fee/order+quote/order+line/quote/source-order                           | Reject cross-order/currency/scale, missing verified consent/proof; actual history retained            |
| Approval only        | Current submitted immutable quote/line/hash and verified responding authority; allocation creation and approved projection/history same T | Submit/draft cannot reserve credit; callback/UI status alone insufficient                             |
| One effective source | U(active_source_order_id), reserved/retained nonNULL=source; released NULL+required proof and latest review                               | Fee-version or extra quote does not allow second credit; NULL bypass shape rejected                   |
| Amount conservation  | 0<a<=verified f; quote credit line_total=-a, sum(lines)=total; one effective partial allocation allowed                                   | f10000/a6000 not parallel4000 wallet; cross-row cap under order/fee/quote mutex                       |
| Review/release       | Execution+settlement review typed histories/document/reviewer/time, unused+unsettled authorized release only                              | Cancel/supersede/unknown/executed/settled retained; no auto release or history-delete                 |
| Usage/reversal       | Typed external verified usage/correction source proof, causal original history; effective used<=a, reversal<=original used                | New correction, not automatic refund/reuse; no reactivation of released row, no reverse-as-new-credit |
| Quote immutability   | Submitted currency/scale/lines/total/hash immutable; new scope new version/current approval guard                                         | F4 no post-start overwrite; response/status changes append typed history                              |
| Financial close      | Typed fee/approval/allocation/usage/review/correction/outstanding proof and responsible domain                                            | Unknown not zero/settled; detailed closure/rounding/final-charge policy OPEN, no financial_ok flag    |

History proposed credit_effect_kind/units/currency/scale optional only on verified usage/correction
types (WP5); original/supersedes FK same allocation/order. Credit release review records already
existing allocation; allocation.latest_review pointer is mutable only. Approval proof predates new
allocation and references quote/fee; later allocation-review points back without immutable cycle.
All authorizer/user/doc-version foreign references retained. Exact review types/NULL branches must
match actual financial proof interface, not invented enums silently marked accepted.

Protected credit routine locks case/order→fee/quote/lines/allocation/current review in L, validates
actual owned/capped verified proof and current scope, then allocation/history/projection/audit. Error
whole rollback. Runtime cannot insert fake approval proof or mutate fee/source/history directly;
customer response verification authenticity is external trust boundary, not a DB FK guarantee.

## 10. Repair scheduling

REV-04 remains an **architectural observation / OPEN business policy**, not confirmed current defect.
Existing production/capacity services do not implement repair assignments. Repair operation owns
assignments; professional role/employee active eligibility differs from authorization user role.
U(operation,employee) preserves one assignment identity; ended_at once, intervals carry actual work.
Task reassignment closes old assignment with history; no employee/operation identity overwrite.
Reassigning same employee uses retained assignment/time slices unless separately approved policy.

| Policy alternative                      | Representation/enforcement                                                                                                                                          | Decision status                                                                              |
| --------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------- |
| Assignment-local nonoverlap             | U(active_assignment_id) + start/finish CHECK; assignment row lock, effective intervals [start,end), open end infinity; overlap test under lock                      | WP1 intended local invariant, technical PROPOSED protocol                                    |
| Global employee hard no-overlap         | Employee row mutex before assignments; predicate across all effective repair intervals and any selected production domain; no MySQL exclusion constraint assumption | OPEN, not imposed; owner must define domains, interval endpoints/open entries and exceptions |
| Advisory planning / capacity preference | Employee availability/calendar and plan slots return conflict warning, no new global reject; actual recorded time/evidence stays truthful                           | OPEN policy alternative, not hard DB invariant                                               |

Within local assignment, overlap if new_start<old_end AND old_start<new_end, corrections fold out only
the explicitly superseded entry, not all history. Compare UTC DATETIME(6), zero duration boundary
decision consistent with WP1 >= (zero allowed); do not silently change to strict >. Concurrent local
start/cancel/reassign/time correction locks order/operation→employee (if future global policy)→assignment
→interval IDs in deterministic order. Placing employee lock for cross-domain global rule requires
shared production/capacity extension to L, remains OPEN, not a new accepted lock order.
Ended assignment no new started work without authorized appropriate assignment; actual correction
preserves original facts. Planning capacity is not actual time or physical stock reservation.

## 11. Immutable history and closure

Event E immutable columns all business payload, actor/occurred/created/command/source/subject/proof/version.
Correction uses new causal/supersedes event and reason, original immutable; legal reversal distinct.
Mutable P update whitelist: current status/head/location/lastmovement/counters/review pointer only
through complete protected operation, with old/new proof and audit. Quote draft exception ends at submit;
started op and completed time never editable. No new soft delete, no cascaded history delete.

**C1 finalizer protocol, PROPOSED / NOT EXECUTED:**

1. Resolve all case IDs and actor scope; lock case first. Read all members/orders/serials, original
   outcomes, every replacement attempt/return, QC/authorization/current custody/stock/ownership.
2. Lock subordinate obligation/proof sets in L; require no unresolved physical reserve, quarantine/
   in-transit, approval/credit/final-fee branch, demand or supplier residual without explicit allowed D11
   named handoff. Never let financial evidence substitute physical/original outcome.
3. Insert typed case close-history and per-member/serial proof-history, linking same closing command;
   mark case closed with closed_at/by/closing_command; complete command with canonical result/audit.
4. Delete that case's active claims through definer-only finalizer; BEFORE DELETE verifies closed
   parent+completed matching closing command+typed claim-close history, no session flag bypass.
5. Recheck expected claim/member cardinality and complete proof set, return to outer service T;
   finalizer has **no internal COMMIT**. Any failure including audit/claimdelete rolls back all.

Acquire: BEFORE member insert verifies command/open parent/origin/owner; AFTER INSERT creates claim
from NEW fields under serial/case mutex, PK(instance) excludes another active case. No trigger that
modifies its own invoking member table or relies on FK-cascade firing. Raw claim INSERT/UPDATE/DELETE/
TRUNCATE denied. New child/evidence/return/coverage writers must lock same parent case, so close cannot
race a newly created unresolved obligation. Completed close replay same command/hash returns result;
different command on already closed case returns conflict/current result by explicit API contract,
not new claim deletion. Reopening not accepted, no reopen API/automatic migration reopen.

DB guarantees structure/allowed data transition relative to stored evidence. It does not prove a
customer signed genuine bytes, goods physically arrived or a legalbasis is valid; domain reviewers,
file retention/authorization and external evidence remain required. Audit JSON not proof_ok override.

## 12. Concurrency and lock ordering

### 12.1. Shared order és current read

C1 L unchanged at aggregate level: case IDs → items → item_instances → repair/production orders →
operations/fee/quote → requirements/coverage → reservations → balances → release/proof; ID order inside
groups. Command idempotency key acquired at command entry consistently before domain locks; never
first lock item then discover and lock earlier case. Multi-owner mapping planned read before locks,
revalidate membership after locking; changed plan rollback/retry from root. Non-RMA paths must also
discover relevant cases up front. No unknown count+1 number/sequence or phantom balance mutex.

PROPOSED L extension inside requirements/coverage phase: all requirements IDs → existing Ghead/root
IDs → PR headers/lines/source anchors → PO headers/items → GR headers/items. Mutable credit-review
rows belong to fee/quote phase; actual return/ownership/disposition final proof rows last. Immutable
new rows do not establish a new later→earlier lock acquisition. Trigger and FK locks, existing source
direct pointers, parent/master proof update/delete and legacy caller inversion must be checked in
WP7; this deterministic proposal is **not** claim of deadlock-free enforcement.

Isolation assumed REPEATABLE READ with explicit current FOR UPDATE reads; READ COMMITTED alternative
requires own matrix, not accidental prod default. Parent mutex held for every participating child
writer; aggregations used for cap are read with current semantics (lock relevant children/roots after
parent), not a stale nonlocking SUM from an earlier RR snapshot. Empty child set protected by existing
parent row; no reliance on NULL/gap-lock absence at different isolation. SKIP LOCKED cannot silently
skip obligations. Shared read-only dashboards may lag for display, never decision authority.
[Oracle locking reads](https://dev.mysql.com/doc/refman/8.4/en/innodb-locking-reads.html).

### 12.2. Concurrency matrix – all PROPOSED / NOT EXECUTED

Minden Cxx RR+explicit current locking read-et és az alábbi közös full rollback/bounded retry policyt használja; a lock terv actual proofig UNVERIFIED.

| Scenario                         | Lock targets/order                                                                              | DB uniqueness / guard                                                | Idempotency/retry postcondition                                                                                    | Expected failure result                                            | WP7             |
| -------------------------------- | ----------------------------------------------------------------------------------------------- | -------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------ | --------------- |
| C01 Duplicate intake             | case/item/serial/source release → existing return/proof, item mutex before child absence        | U(physical namespace,key),U(MI owner),U(command/key)                 | Same occurrence one I/MI, retry canonical; other command cannot duplicate                                          | Reject stale/wrong physical proof, no company delta                | V18/V20/V40     |
| C02 Duplicate admission          | case/item/serial/order → source I/basis →balance/proof in L; no earlier lock after balance      | U(A.intake),U(MA owner),U(reverse original outbound)                 | Exactly one A/MA target and company+1 at most; loser rolls back                                                    | Already classified cannot switch target by new key                 | V19/V20/V21     |
| C03 Quarantine release race      | case/item/serial/order →current review/inspection→reservation/balance→proof                     | Command and typed history occurrence, serial active U                | Release/hold/consume fresh state serializes; no physical new receipt                                               | Missing right/condition/MA remains quarantine                      | V21/V22/V23     |
| C04 Consume vs release           | case/item/serial/order/req→reservation→balance per L                                            | Counter CHECK, command/material/movement U                           | One remaining cap, one T winner/retry; partial release never consumes released portion                             | q>remaining/no owner/E fails all writes                            | V08-V12/V38     |
| C05 Concurrent generation        | All cases/item/owners/requirements→G/root→PR/source→PO/GR identities                            | U(Ghead),U(line occurrence),U(source gen-key),U(PRitem,req)          | Both callers same actual delta cannot allocate twice, SUM under current root mutex                                 | No G budget exceed or standalone head                              | V15/V16/V17/V40 |
| C06 Concurrent source allocation | Item+all source/destination requirements/G/PRline/POitem/GRitem roots in same order             | Incoming singleton,transfer(side),receipt occurrence                 | Commitment/line/G/GR budgets checked across all owners under shared roots                                          | Partial line/transfer rollback, exact quantity conserved           | V16/V17/V19/V22 |
| C07 PO cancel vs receipt         | case/item/requirements/coverage→PR/POheader/items→GRheader/items→reservation/balance            | Posting occurrence/source/root and GRitem caps                       | Receipt before cancel leaves residual; cancel before invalid receipt rejects/reviews; no silent supplier overwrite | No fake received or deletion of actual G                           | V17/V19/V22/V38 |
| C08 Credit allocate vs reversal  | case/order→fee/quote/line/allocation→review chain                                               | U(active source order),U(line),usage/supersedes occurrence           | One effective claim; reversal observation not active-key release/reuse                                             | Policy/proof unknown retained/blocked                              | V25-V27/V33     |
| C09 Close vs evidence change     | case first then every affected member/serial/order/proof/obligation in L                        | Closing command U,typed close history and claim PK                   | Either child fact committed before full close recheck or closed case refuses new unresolved child                  | No partial case/claim close or status-only success                 | V28-V32/V38     |
| C10 Serial ownership transition  | All cases/item/serial/orders→current legal predecessor/source proof                             | U(original reversal),U(source occurrence),monotonic subject sequence | Current previous owner/right checked, physical delta0; no receipt implied reversal                                 | Stale/legalbasis fail, original event immutable                    | V24/V37/V39     |
| C11 Repair assignment race       | case/item/serial/order→operation→assignment/time; employee-lock global alternative not selected | U(op,employee),U(active assignment),U(supersedes)                    | Local intervals serialize, no invented global overlap refusal                                                      | Ended/unauthorized assignment new work denied, correction retained | V34-V36         |
| C12 Timeout/unknown commit retry | Same planned locks fresh on new root transaction; no partial-call retry                         | Same command/type/key/hash and actual occurrence U                   | 1213 full retry;1205 explicit rollback first; network loss resolve canonical completed command                     | Different actor/payload conflict; no duplicate stock/credit/source | V38/V40/V42/V43 |

Common error policy: routine handler and outer service abort whole T on any domain/SQL error.
Deadlock1213 whole txn rollback; lock timeout1205 may otherwise roll back only statement, so explicit
full rollback required. Duplicate U failure is not necessarily full T rollback either. Retry bounded
whole public operation with same command/hash after fresh guard; no retry of one movement/counter
substep, no INSERT IGNORE/upsert rewriting immutable event. Unknown commit/network outcome first read
canonical command/occurrence, then decide safe retry. [Oracle error handling](https://dev.mysql.com/doc/refman/8.4/en/innodb-error-handling.html).

## 13. Runtime database permissions

### 13.1. Proposed privilege topology and protected operations

| Principal                 | Intended privileges                                                             | Forbidden boundary                                                                    | Operational prerequisites                                                                  | WP7                     |
| ------------------------- | ------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------ | ----------------------- |
| Migration                 | DDL only authorized release; objects/index/FK/trigger/routine grants controlled | No app web/worker usage or committed credential                                       | Catalog/grant/definer/backup manifests, no trial production DDL                            | V02/V41/V44             |
| Runtime application       | Approved SELECT plus EXECUTE on complete public domain routines                 | No direct protected DML/DDL/TRUNCATE, helper EXECUTE or elevated inherited roles      | Actual grants/default-role graph and alternate connection audit; actor binding/PDO profile | V28-V33/V41-V43         |
| Posting/finalizer definer | Minimal table-specific DML and needed SELECT, controlled nested helper EXECUTE  | No global admin/schema DDL/file/network exposure, no generic raw-SQL callable routine | Stable definer account/SQL SECURITY, same audit connection, restored definitions/grants    | V01/V28-V33/V38/V41-V43 |
| Read-only reporting       | Approved SELECT/views for allowed purpose, protected owner/privacy data         | No source mutation or stale view decision authority                                   | View/connection permission and compatible E/R/Q reads/cache keys                           | V22/V39/V41/V44         |
| Backup/recovery operator  | Explicit authorized backup/restore scope separate from runtime                  | No auto destructive restore or unreviewed data repair                                 | Paired DB/file/key/grant/tail restore rehearsal and incident approval                      | V39/V44/V46             |

All 25 new tables and seven changed shared structures are protected, plus relevant PR/PO/GR/production/
documents/masters paths that can destroy referenced evidence. Runtime **no direct domain INSERT/UPDATE/
DELETE**, no protected helper EXECUTE, TRUNCATE/DDL, CREATE/ALTER ROUTINE/TRIGGER/EVENT, FILE/GRANT or
elevated role through alternate connection. Named public domain operation routines only. They check
whole proof and perform every domain/projection/claim/history write; not generic `execute_sql`,
`delete_claim` or `append_any_event`. Existing Eloquent direct writers must be adapted before grant
cutover; keeping direct DML for legacy code would invalidate totality/immutability proof.

SQL SECURITY DEFINER minimizes operation privileges; definer non-runtime login, not migration admin.
Trigger runs in definer context; cannot start/commit transaction, modify invoking table or use dynamic
SQL. FK actions do not activate triggers, so protected history cannot rely on cascade callbacks.
[Oracle stored-program restrictions](https://dev.mysql.com/doc/refman/8.4/en/stored-program-restrictions.html).
Definer object/role/grant restore and CALL EXECUTE boundary are operational prerequisites;
CURRENT_USER inside definer context is not the Laravel user actor. [Oracle stored object security](https://dev.mysql.com/doc/refman/8.4/en/stored-objects-security.html).

Actor ID passed by the application is not cryptographic proof of user authority. Application Policies
and approval verification remain necessary; operation-scoped principal/gateway must authenticate
requester and bind actor+payload to permitted command. DB guards enforce owner/cap/proof-source/type,
but cannot independently authenticate a forged external approval. No common runtime principal may
gain a generic bypass by impersonating reviewer/claim actor; exact gateway/grant/actor binding
**UNVERIFIED technical deployment gate**, not a claim that Policies alone prevent direct SQL.

### 13.2. Multi-statement CALL atomicity gate

A service transaction and a stored procedure name alone do not prove all CALLs are atomic. Autocommit
and error handling must be explicit. **Preferred PROPOSED integration profile:** protected posting
connection autocommit=0, public routine refuses @@autocommit<>0 before any business write, outer
service owns complete root transaction/commit, routine never COMMITs. EXIT handler for SQLEXCEPTION
ROLLBACKs the whole T and RESIGNALs; failed call cannot be followed by a commit of half its work.
This is transaction-mode guard, not user-settable `@allow_delete` authorization bypass. Changing mode0
does not weaken constraints; changing it to1 makes protected routines refuse. No partial helper call
or pending processing command from runtime direct DML is possible under the intended grant profile.

Standard Laravel explicit BEGIN with autocommit1 is a valid database transaction but **would be refused
by this stricter proposed entry guard**. Therefore a tested dedicated connection/service lifecycle is
required, including PDO nesting/transaction state after routine rollback, reconnect/connection pool
reset, query interruption and explicit root-only commands. We do not claim current config supports it.
Alternative: a demonstrably reliable explicit-transaction-presence guard compatible with existing
Laravel BEGIN. Its implementation is OPEN; no invented @@in_transaction portability assumption or
performance_schema instrumentation guarantee. A public wrapper owning its COMMIT would change C1
outer-transaction contract and is not selected here. Until actual chosen profile passes V42/V43,
DB-level totality remains BLOCKED. [Oracle autocommit](https://dev.mysql.com/doc/refman/8.4/en/innodb-autocommit-commit-rollback.html).

Routine error must unwind caller bookkeeping; caller never catches domain error then continues
unrelated writes in the aborted transaction. All writes participating in the command use same
InnoDB connection including audit. Runtime cannot use migration credential. Appactor audit and DB
login/object definer identities recorded distinctly where supported, not leaking credential values.

## 14. Legacy compatibility

CONFIRMED CURRENT: StockBalanceRepository nullable-batch firstOrCreate and full qty SUM;
StockReservationRepository full active SUM; ProductionTaskMaterialService whole reservation Consumed;
MaterialRequirementRepository customer-item/item upsert from individual production-order calculation;
PR generator source-existence gate; PO.close Received without GR; GR movement source current header
morph, received counter+balance float. WP6 A01–A30 is required actual-data audit, not current success.
Current [migrations](../../database/migrations/2026_06_21_000024_create_stock_reservations_table.php),
[consume](../../app/Services/Admin/ProductionTaskMaterialService.php),
[PR generation](../../app/Services/Admin/PurchaseRequisitionService.php),
[GR posting](../../app/Services/Admin/GoodsReceiptService.php),
[reporting](../../app/Repositories/Admin/ReportingRepository.php),
[intelligence](../../app/Repositories/Admin/ManufacturingIntelligenceRepository.php) inspected as source.

WP6 A–F: preparation/catalog/backup → additive schema → verified controlled alignment → compatible
cohorts → gated activation → separately authorized legacy retirement. New nullable columns first,
never default unknown scope=company/eligible, unknown used=0 or settled. Owner duplicate/orphan/nullbatch
mapping before U/FK/NOT NULL; no automatic SUM/delete/backfill. Old CASCADE/SET NULL/soft delete and
source morph must be handled by retained identities/restricted runtime; legacy schema meaning ≠ valid DDL.

WP3 282 methods plus direct SQL/BI/jobs/cron inventory, HTTP/service/repo/model and report/cache paths
must switch E/R/P/M/Q/C/L together. AfterCommit invalidation preserved, cache error not business rollback.
Current ProcessDocumentJob is document metadata writer; external inventory jobs/grants UNKNOWN, not
assumed absent. New RMA behaviors cannot activate while old consumers violate eligibility/remaining.
Feature OFF requires ingress fence+drain+compatible recovery path; no undocumented new 26th gate table.
Exact activation epoch/barrier implementation remains technical gate; C1 case locks protect close races.

HISTORICAL: local `feature/material-requirements-planning`, pinned
`379be484405a789388df5f5e8367a18b1e598bbc`, read-only Git objects. Requirement identity PO+BOM-item,
SupplyProposal, separate proposal-source, netting/pegging, supplier selection/replenishment/readiness,
PO snapshot/dispatch/ack exist there, not current RMA. ADR-0006 MRP number conflicts with RMA name;
keep branch/path/commit in references. O1 no importing historical migration; O2 future review must
preserve distinct lineage/IDs and address partial18,3→PO/GR/balance12,3 narrowing, exact strings/API,
aggregate overflow and immutable supplier history. O3 not selected, no coexistence adapter deployment.

WP6 recovery: new valid RMA/stock/credit/coverage/proof commits survive feature-off/app rollback;
old scope-blind binary rollback unsafe. Schema down() and full restore not interchangeable.
DB+object bytes/checksums+grants/routines+postcutoff external effects must reconcile; backup/PITR/RPO/RTO
UNVERIFIED, not zero-data-loss guarantee. Forward-fix safer when valid new facts/external shipments exist.
No destructive cleanup/restore is authorized by this design. Online ALTER algorithm/MDL/disk/replication
requires actual rehearsal, not blanket INSTANT/LOCK=NONE promise.

## 15. Proposed SQL examples

Minden alábbi példa **PROPOSED / NOT EXECUTED**, MySQL8.4/InnoDB feltételezés.
Fragment/pseudocode, nem teljes DDL, nem külön SQL-file vagy Laravel migration. Minden SQL blokkra
e címke vonatkozik. Preconditions: parent scalar/candidate U-k és exact typed mezők már definiáltak;
DDL migration principal, mutation csak protected definer, raw runtime deny. Nem kaptak engine proofot.

### EX01 – Normalized balance identity

**PROPOSED / NOT EXECUTED**. Invariant: batch nélküli egyedi physical identity, no sentinel FK.
Precondition: batch_identity backfill és duplicate/id0 audit PASS, FK action compatibility rendezett.
Migration principal only, runtime posting lock items PK; V02/V04/V05.

```sql
ALTER TABLE stock_balances
  ADD CONSTRAINT sb_batch_shape CHECK (
    (item_batch_id IS NULL AND batch_identity = 0)
    OR (item_batch_id IS NOT NULL AND batch_identity = item_batch_id AND batch_identity > 0)
  ),
  ADD UNIQUE KEY sb_physical_identity (item_id, location_id, batch_identity);
```

Korlát: ez nem oldja meg az item_batch.item_id mismatcht, legacy opening reconciliationt vagy E-t.
A régi nullable unique/index és FK action contract külön átmeneti lépés, nem e fragment vak futtatása.

### EX02 – Reservation counters és owner shape

**PROPOSED / NOT EXECUTED**. Numeric row CHECK nem cross-row eligible pool proof. Counters NOT NULL,
Q12,3; existing owner FK-k actionje és soft-delete guard előfeltétel. V02/V08–V12.

```sql
ALTER TABLE stock_reservations
  ADD CONSTRAINT sr_counter_bounds CHECK (
    reserved_quantity > 0 AND consumed_quantity >= 0 AND released_quantity >= 0
    AND consumed_quantity + released_quantity <= reserved_quantity
  ),
  ADD CONSTRAINT sr_owner_shape CHECK (
    (repair_order_id IS NOT NULL AND customer_order_item_id IS NULL AND production_order_id IS NULL)
    OR (repair_order_id IS NULL
        AND (customer_order_item_id IS NOT NULL OR production_order_id IS NOT NULL))
  );
```

Legacy PO+customer-item egyezéshez cross-row routine szükséges. Status projection PROPOSED:
remaining>0→active a fizikai mennyiségi ágon; remaining0 és consumed=reserved→consumed;
remaining0 és released>0→released (mixed consumed+released counters megmaradnak).
Cancelled workflow proof nem enged positive unused remaining elrejtését; logical obligation slot
ettől külön élhet. Rész-release után csak a még nem released/consumed remaining fogyasztható,
full release vagy full consumption után új pozitív consume/release elutasított. Új visszaérkezés
nem reaktiválja a régi countert. Old status-only readerek e projectionnel nem kompatibilisek.

### EX03 – Counter update protected operation

**PROPOSED / NOT EXECUTED**, procedural pseudocode, nem teljes CREATE PROCEDURE. Definer DML,
root transaction profile13.2, items/owner mutex és every competing writer fence előfeltétel.
V08–V12/V38/V40/V42/V43.

```text
PUBLIC consume_reservation(command_key, payload_hash, actor_binding, reservation_id, quantity_text):
  install EXIT HANDLER FOR SQLEXCEPTION: ROLLBACK whole T; RESIGNAL
  refuse autocommit != 0, wrong operation principal/actor binding, unsafe connection mode
  validate canonical exact decimal quantity_text bytes BEFORE CAST to Q (positive, <=3 decimals, range)
  acquire command identity, plan all cases/items/serials/owners; lock in L; current reread
  validate current scope/approval/E/item/unit/batch/serial, own reservation and available pool
  UPDATE stock_reservations
    SET consumed_quantity = consumed_quantity + q
    WHERE id = reservation_id
      AND reserved_quantity - consumed_quantity - released_quantity >= q;
  require affected_rows = 1; otherwise SIGNAL domain conflict
  insert immutable actual material, then movement FK to material, decrement physical projection
  insert exact before/after typed history, command complete, business audit in SAME connection/T
  assert mandatory children/amount consistency; return canonical response; no internal COMMIT
```

Canonical numeric input értéke nem DECIMAL routine-paraméterként érkezik, mert annak scale-conversionje
már a body előtt kerekíthet. Text codec pontos grammar/byte length/range ellenőrzése előzi a CAST-ot;
float/JS Number már elvesztett pontosságát a DB nem tudja visszaállítani. Ugyanez G/GR/fee inputra.
No warning/IGNORE-as-success; SQLWARNING conversion kezelés és strict SQL-mode V45 proof szükséges.

### EX04 – Typed admission and movement parent tuples

**PROPOSED / NOT EXECUTED**, all referenced scalar/tuple columns created before fragment.
Invariant: A/I/release/serial ugyanaz; one admission/I, MI/MA külön; not rights/child-totality proof.
Migration principal creates, posting definer validates row/type and mandatory child; V01/V18–V24.

```sql
ALTER TABLE repair_delivery_events
  ADD UNIQUE KEY rde_admission_once (admission_intake_key),
  ADD UNIQUE KEY rde_admission_subject
    (id, related_return_event_id, repair_release_id, item_instance_id),
  ADD CONSTRAINT rde_admission_key_shape CHECK (
    (event_type = 'return_reconciled'
      AND admission_intake_key IS NOT NULL AND related_return_event_id IS NOT NULL
      AND admission_intake_key = related_return_event_id
      AND admission_target_scope IS NOT NULL
      AND admission_target_scope IN ('company_stock','customer_custody')
      AND admission_basis_history_id IS NOT NULL AND return_authorization_history_id IS NOT NULL)
    OR (event_type <> 'return_reconciled' AND admission_intake_key IS NULL
      AND admission_target_scope IS NULL AND admission_basis_history_id IS NULL)
  );
ALTER TABLE stock_movements
  ADD UNIQUE KEY sm_intake_once (return_intake_event_id),
  ADD UNIQUE KEY sm_admission_once (return_admission_event_id),
  ADD KEY sm_admission_subject
    (return_admission_event_id, source_return_event_id, source_repair_release_id, item_instance_id),
  ADD CONSTRAINT sm_admission_subject_fk FOREIGN KEY
    (return_admission_event_id, source_return_event_id, source_repair_release_id, item_instance_id)
    REFERENCES repair_delivery_events
      (id, related_return_event_id, repair_release_id, item_instance_id);
```

Ehhez a 7. fejezet teljes MI/MA/OTHER row-shape-e is kötelező: MA mind a négy tuple mező nonNULL;
MI más owner/key és external→external qty1; other nem birtokol MI/MA owner mezőt. Fragment nem
bizonyít type/rights/actual locationt, és omitted ON action engine proof V02 szükséges.
Original member receipt U és release/material inverse U külön megmarad.

### EX05 – Generation-head ≠ source-line uniqueness

**PROPOSED / NOT EXECUTED**. Revision/gen integer NOT NULL a source-on, Ghead shape a8 szerint,
keys canonical byte sequences, event/root owner typed. V15/V16/V17/V22/V44.

```sql
ALTER TABLE procurement_coverage_events
  ADD UNIQUE KEY pce_one_generation_head
    (material_requirement_id, procurement_generation, generation_singleton_key),
  ADD UNIQUE KEY pce_generation_line_once
    (generation_head_event_id, source_line_key, generation_allocation_slot),
  ADD CONSTRAINT pce_head_slot_shape CHECK (
    (event_type = 'generation_authorized' AND procurement_generation IS NOT NULL
      AND procurement_generation >= 1 AND generation_singleton_key IS NOT NULL
      AND generation_singleton_key = 1)
    OR (event_type <> 'generation_authorized' AND generation_singleton_key IS NULL)
  );
ALTER TABLE purchase_requisition_item_sources
  ADD UNIQUE KEY pris_creation_line
    (material_requirement_id, procurement_generation, source_line_key),
  ADD UNIQUE KEY pris_generation_occurrence (generation_key);
```

PRitem+requirement U már existing és marad; nincs source U(req,gen). Gen-line allocation-slot
nonNULL iff initial incoming line, otherwiseNULL, ezt külön type-shape és scalar/composite FK adja.
Budget equality/cap és line-plan completeness nem UNIQUE vagy CHECK: EX06 procedure.

### EX06 – Generation, transfer, receipt totality

**PROPOSED / NOT EXECUTED**, definer public multi-row pseudocode, T-mode guard és no helper EXECUTE.
V15–V17/V19/V22/V38/V40/V43/V44.

```text
GENERATE:
  command + all current locks L, exact D/R/C/free-once/I/K fold; validate authorized uncovered delta
  assign next generation from locked requirement, not MAX+1 outside mutex
  insert Ghead (no source FK), immutable canonical line-plan+budget+reason/hash
  insert/reuse PRheader/PRline, new source anchors after parent PRline/Ghead exist
  insert one incoming event per authorized line; no double source.snapshot + event amount
  require line set/hash exact plan and SUM(initial incoming q)=G budget
  require every affected PRline/requirement quantity conservation and no other source overlap
  history/audit + command complete; full rollback on any missing line/error, then outer commit
TRANSFER:
  lock both owners/sources/commitment roots, verify residual q and valid target
  insert from and to immutable rows with same transfer_key, exactly one of each and same q/unit/item
  require source/target folds nonnegative/capped, no new procurement quantity for reassign-in
  update projections+named responsibility/history/audit/command together, not paired future FK
RECEIVE:
  current PO/GRitem/req/source locks; verify actual posted GR lineage and unassigned remaining cap
  insert one physical purchase_receive movement per actual original posting occurrence
  allocate owner portions with SUM<=actual GRitem q; unassigned remainder explicit, not fake requirement
  supply_received reduces open assigned, counter/balance/event/history/audit one T
  delivery GR already posted legacy path must not repost physical movement: reconcile authoritative
    posting identity first, planned existing receipt baseline references it without second stock delta
```

A current GR header morph nem elég több GRitem egyértelmű quantity-distributionjéhez.
Választandó technical alternatives: (a) protected posting command authoritative full immutable line-set
snapshot + GRitem cap, preserving current source morph; (b) explicit typed GRitem movement link új
existing change-ként külön leltárral. (a) előnyben, mert nem bővíti hallgatólagosan a7 S scope-ot,
csak ha actual line identity/proof megőrizhető; ellenkező esetben (b) review-gate. Nem állítjuk a
jelenlegi header source-ból automatikusan egy-egy GRitem-movement FK létét.

### EX07 – Credit row shape and typed order identity

**PROPOSED / NOT EXECUTED**. Parent U(id,order) és U(line,quote,sourceorder) előfeltétel;
scale/currency/cap és quote approval procedural. V25–V27/V33/V43.

```sql
ALTER TABLE repair_diagnostic_credit_allocations
  ADD UNIQUE KEY rdca_active_source_once (active_source_order_id),
  ADD CONSTRAINT rdca_state_shape CHECK (
    (state IN ('reserved','retained') AND active_source_order_id IS NOT NULL
      AND active_source_order_id = source_repair_order_id
      AND released_at IS NULL AND release_document_id IS NULL)
    OR (state = 'released' AND active_source_order_id IS NULL
      AND released_at IS NOT NULL AND release_document_id IS NOT NULL
      AND latest_review_history_id IS NOT NULL)
  ),
  ADD CONSTRAINT rdca_fee_subject FOREIGN KEY (diagnostic_fee_id, source_repair_order_id)
    REFERENCES repair_diagnostic_fees (id, repair_order_id),
  ADD CONSTRAINT rdca_quote_subject FOREIGN KEY (repair_quotation_id, source_repair_order_id)
    REFERENCES repair_quotations (id, repair_order_id);
```

Korlát: no SUM/CHECK fee-cap; event/time/amount/source immutable trigger és definer guard kell.
Reversed usage nem oldja active-order U-t és nem új entitlement. Same actor/quote/source/currency/scale
rutin ellenőrzés, nonNULL triple FK és quoted negative amount egyezése külön required.

### EX08 – Claim acquire and closure authority

**PROPOSED / NOT EXECUTED**, trigger/procedure pseudocode; MySQL TRIGGER privilege a definernek,
public runtime csak whole case_open/case_finalize EXECUTE; V07/V28–V32/V38/V41–V43.

```text
member BEFORE INSERT: require existing locked open case, valid membership command/actor/origin/owner
member AFTER INSERT: INSERT active_repair_claims(instance PK, member U, claimed_by, command)
  use NEW member tuple; duplicate PK fails whole public operation
claim BEFORE UPDATE: SIGNAL immutable-identity violation
claim BEFORE DELETE: require actual closed parent, matching completed closing command,
  typed case/member/serial close histories and required full finalizer proof
PUBLIC case_finalize: lock case and all subordinate facts in L, evaluate proof from typed rows
  insert close histories; set case closed with closing command; complete command/audit
  delete every member claim through protected guarded route; assert complete member cardinality
  return, no COMMIT; any SQL/domain/audit error ROLLBACK whole T + RESIGNAL
```

No raw table DELETE grant, no standalone delete helper, no session flag. Reopen not accepted.
Trigger cannot start transaction; totality depends protected root CALL and error mode, not trigger alone.

### EX09 – Typed evidence and append-only guard

**PROPOSED / NOT EXECUTED**. Event UPDATE/DELETE always SIGNAL for E; P whitelist only controlled
transition+typed new history in public operation. FK NO ACTION and no runtime DDL/TRUNCATE required.
V01/V27–V33/V37/V39/V41.

```text
history subject XOR case/order; member/serial both NULL or both set with composite FK
credit review: order + diagnostic_credit_allocation_id + review documents + reviewer/time required,
  settlement_observation explicit unknown/unsettled/settled, no NULL-as-unsettled
credit usage/correction: exact amount/currency/scale + verified source document,
  correction original/related allocation history FK same subject, once-only effective proof
quarantine basis: I+serial+inspection+authorization doc, actual rights/condition review before A
claim-close: case+member+serial+completed closing command, not free JSON proof_ok
ordinary dimension/state change: irrelevant typed fields NULL; no overload as arbitrary proof
```

Alternatives where exact external proof interface not yet selected: typed existing columns extended
explicit purpose/check per history type (preferred within25); separate normalized proof subtable would
exceed current inventory and needs explicit scope change. Free JSON proof flags rejected. Full exact
history type enumeration/type→NULL mapping remains WP2-F05 gate rather than fictitious approved data model.

### EX10 – Local time overlap under assignment mutex

**PROPOSED / NOT EXECUTED**, assignment-local policy only. Definer locks operation/assignment, then
all effective existing intervals; scalar/composite subject consistency guard. V34–V36.

```text
reject invalid finish<start or wrong/ended unauthorized assignment
effective_entries = all current non-superseded intervals under same assignment current locks
conflict if any interval: proposed.start < coalesce(existing.finish,+infinity)
                         and existing.start < coalesce(proposed.finish,+infinity)
on valid correction: append new entry with unique prior FK/reason, never rewrite completed original
U(active_assignment_id) excludes multiple open intervals, not historical overlap by itself
```

Closed-interval touching endpoint policy [start,end) technical proposal must align WP1 zero-duration
allowance. Global employee prohibition and production/repair combined domain remains owner decision;
no global UNIQUE(employee) or invented database interval exclusion capability.

## 16. Findings and open decisions

### 16.1. WP2 findings – all OPEN

| ID / severity – OPEN | Evidence                                                                                                         | Risk                                                                                          | PROPOSED resolution                                                                                                           | Owner                                              | Acceptance criterion                                                                                         | WP7 requirement             |
| -------------------- | ---------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------- | ------------------------------------------------------------------------------------------------------------ | --------------------------- |
| WP2-F01 **BLOCKER**  | WP1/WP4/WP5 SCCs, DELTA01-09, CHECK/FK/action/type and mandatory children not engine-tested                      | Impossible inserts/orphan immutable facts, unknown exact history DDL mistaken migration-ready | Complete exact selected DDL/procedure/type mapping and valid/raw invalid proofs, no immutable UPDATE or FK disable            | DB architect/WP7                                   | 25+7 profiles and SCC graph actual schema build/insert/proof totality PASS                                   | V01-V03/V07/V13-V24/V28-V33 |
| WP2-F02 **BLOCKER**  | C1 no internal COMMIT; public multi-statement CALL+autocommit/errors/definer privileges unverified               | Raw CALL can commit half command or bypass DML/actor/proof restrictions                       | Chosen root transaction/PDO profile, full rollback handler, complete public routines only, grant+actor binding fence          | DB/security architect + backend owner/WP7          | No partial effect after any raw CALL failure/no BEGIN, no standalone helper access; actual principals proved | V38/V40-V43                 |
| WP2-F03 **BLOCKER**  | Current full active SUM/whole consume/sourceexistence, direct Eloquent shared writes; WP3 282 paths              | Old clients bypass new E/R/P/Q/C/L and DB totality while new feature active                   | Adapt/fence every shared path+external BI/job/connection, target O1 single writer, coordinated WP6 rollout                    | Inventory/production/procurement/release owner/WP7 | Every WP3 consumer and external principal classified/tested, old unsafe write unavailable                    | V08-V12/V15-V24/V41/V48     |
| WP2-F04 **BLOCKER**  | WP6 actual catalog/data/mapping/backup/restore absent, current owner/nullbatch/source/origin ambiguity           | Valid business meaning/history lost despite structurally valid ALTER/backfill                 | Representative audit+mapping and source preservation, controlled expand/alignment/contract, paired restore proof              | DBA/domain/release/storage owners/WP7              | A01-A30 actual evidence, no fabricated origin/counters/ownership/supply; no postcutoff loss shortcut         | V04/V07/V14/V44/V46         |
| WP2-F05 **HIGH**     | WP1 typed history aliases/type-null rules and current GR header morph; EX06/09 alternatives                      | JSON proof loophole or fake GRitem one-to-one lineage; hidden extra table/schema count        | Select exact typed history proof interface and GR line occurrence path; any extra existing change explicitly relisted         | DB architect + domain owner/WP7                    | Exact type→column/null/FK matrix and mandatory child proof, no unresolved alias or arbitrary JSON            | V01/V03/V22/V27-V33/V39     |
| WP2-F06 **HIGH**     | REV02 accepted multiplelines, WP5 generation-head/line and effective coverage fold                               | Source/head U ambiguity, budget/receipt cap race or double source snapshot+ledger             | Head vs line singleton/typed root and complete plan/hash equality under all shared item/req/GR roots                          | Procurement architect/WP7                          | Multiple4+6 lines work; extra line/retry/transfer/GR cannot exceed budget/actual q                           | V15-V17/V22/V38/V40/V44     |
| WP2-F07 **HIGH**     | D6/D7/WP5 fee/cap/approval/current scope, financial OPEN policies                                                | Duplicate/reused/reversed credit, cancellation auto-release or false settled/close            | Active source-order U+typed proof/cap routines; financial details explicit OPEN, unknown retained                             | Repair/financial domain owner + DB architect/WP7   | Approval-only one claim, amount/currency/order guards, reversal never automatic reuse, full close proof      | V25-V27/V28/V33/V43         |
| WP2-F08 **HIGH**     | REV05 old generic return pointer; WP4 original/source/right/discrepancy/open pending paths                       | MI confused with MA, unauthorized company+1 or duplicate admission/stock                      | Typed inverse MI/MA and A intake key, legalbasis/condition/source/current-place guard; no generic external-external exception | Repair/inventory/legal-domain reviewer/WP7         | MI0, MA+1 at most and quarantine E0, unresolved owner doesn't prevent valid I                                | V18-V24/V39                 |
| WP2-F09 **HIGH**     | Self FK and active assignment U not graph/closed interval proof; global employee policy OPEN                     | Genealogy cycle/correction bypass or false new global no-overlap rule                         | Whole planned graph-component locks/revalidation, local assignment interval guard; owner global policy only if needed         | Execution/traceability architect/WP7               | Concurrent graph/interval negative tests, no immutable rewrite and no unapproved global prohibition          | V34-V37                     |
| WP2-F10 **HIGH**     | Current GR/production lock inversions, proposed L extension and RR/current SUM distinction                       | Deadlock/phantom/stale cap causes retry duplicate or missed obligation at close               | All explicit/implicit locks/root reads current, bounded whole T retry and canonical unknown-commit recovery                   | DB/concurrency owner/WP7                           | Both commit orders with barriers,1205/1213/disconnect fully reconciled; no partial success                   | V12/V17/V20-V24/V30/V38/V40 |
| WP2-F11 **HIGH**     | Q12,3 and float/API Number; historical partial18,3; VARBINARY/BINARY codec not current                           | Pre-body DECIMAL coercion, overflow/collation collision or silent API narrowing               | Exact text-before-CAST boundaries, codec/range/scale/hash payload contract; preserve O2 feasibility                           | Backend/API/DB owner/WP7                           | No silent rounding/overflow/key collision across request/routine/DB/JSON/cache, actual engine profile        | V05/V06/V15/V25/V44/V45/V47 |
| WP2-F12 **HIGH**     | documents soft-delete/files, AuditLogService raw properties, definer/trigger/grant restore and retention unknown | Referenced proof/history disappears; DB-only restore or missing audit breaks trust            | Protected deletes, paired version/checksum storage+DB manifest, routine audit same T, actual restored principals              | Security/storage/DBA/release owners/WP7            | Old proof still accessible bytes match, raw/cascade/DDL deny, actual backup/recovery passes                  | V31/V37-V39/V41/V46         |
| WP2-F13 **MEDIUM**   | O1/O2 accepted, historical PO+BOM/proposal-source/supplier and precision differences                             | O1 identity shortcuts irreversibly obstruct separately reviewed O2                            | Stable IDs/original lineage, explicit future integration dependency; no historical import or O3 coexistence                   | Strategic integration owner + DB architect         | O1 schema doesn't repurpose Proposal-source/peg/owner meaning; future change reviewed separately             | V14/V44/V45                 |
| WP2-F14 **LOW**      | Historical ADR006 collision, ADR0016 declared implementation wait vs source files, all runtime proof absent      | Proposed/historical source mislabeled current/accepted/verified                               | Maintain branch+path+pin/status labels and planned/actual evidence separation; no prior finding closure                       | Documentation/review owner/WP7                     | Links/provenance and final verdict truthful; only documentation PASS claimed                                 | All doc traces + V44        |

### 16.2. Carried-forward findings, technical choices és owner-kapuk

| Authority/finding          | Preserved meaning/disposition                                                                                             | WP2/WP7 trace                                                                                          |
| -------------------------- | ------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------ |
| RMA-BD-001/003/004/011     | Single-owner case/members, reporter separate, claim through full evidence-only close                                      | 3/11/13;V07/V28-V32                                                                                    |
| RMA-BD-002/005/012/015     | Own manufacture/serial/origin, subject-specific manual warranty+history; no reset/fake origin                             | 3/7/14;V07/V24/V37/V39/V44                                                                             |
| RMA-BD-006/009/010/013/014 | Scope approval/QC/attempt/actual work/material and fee/credit distinct                                                    | 3/6/9/10;V08-V12/V25-V27/V34-V36                                                                       |
| RMA-BD-007/016/017/018     | Custody0/receipt-once/quarantine exclusion, eligible company consume and owner demand/coverage                            | 5-8;V08-V24/V48                                                                                        |
| RMA-BD-008/019/020         | Original disposition independent, physical shipment recipient/proof and replacement reserve/prepare                       | 7/11;V13/V18-V24/V28-V30                                                                               |
| D1/D2/D3                   | Three scopes, eligible own-manufactured semi-finished proof, one owner/reporter separate                                  | 2/3/7/14                                                                                               |
| D4/D5                      | Warranty not reset; physical/legal events separate source/time proof                                                      | 3/7/11;V24/V37/V39                                                                                     |
| D6/D7                      | Approval-only one source-order credit, release unused/unsettled evidence; no auto reversed reuse                          | 9/13/15;V25-V27/V33/V43                                                                                |
| D8/D9/D10                  | Mandatory quarantine, new authorized actual attempt, legal reversal original event proof                                  | 7/11;V18-V24                                                                                           |
| D11                        | Demand/intent/coverage/firm/actual receipt/consume/handoff separate; supplier residual survives                           | 8/11;V15-V17/V22/V28-V30                                                                               |
| F1/F2/F3/F4                | Prepare vs once-only dispatch, full-proof close, original-replacement serials/history, started scope immutable            | 3/6/7/9/11;V13/V23-V24/V28-V35                                                                         |
| AM-01/AM-02/AM-03/AM-04    | Logical obligation/shipment separate, two-stage return, verified fee/credit, causal legal/procurement history             | 6-9/11                                                                                                 |
| AM-02-G1                   | I verified serial/place/time/source/proof even unresolved owner, not fake receipt                                         | 7;V18/V20/V24                                                                                          |
| AM-02-G2                   | MI company0; MA only applicable verified basis, unknown not company                                                       | 7;V18-V21                                                                                              |
| AM-02-G3                   | Quarantine excluded from every E/R/P/M/Q reader/writer/cache; not location-only                                           | 6/7/14;V21/V22/V48                                                                                     |
| AM-02-G4                   | I/MI vs A/MA identities/U distinct, actual physical return once                                                           | 5/7/15;V18-V20                                                                                         |
| C1 G1/G2/G3                | Restricted acquire/finalizer and raw DML / shared rollout / return lifecycle DDL proof still required                     | 4/11-14;V01/V07/V28-V32/V41-V43/V48                                                                    |
| WP1-F01–F10                | All retained: claim/proof, shared eligibility, legacy identity, cycles/retention, fee/coverage/key/interval/history risks | WP2-F01-F14; no blanket closure                                                                        |
| WP3-F01–F15                | All retained: 282-method E/R/P/M/Q/C/L and direct/raw/external/locking/scope/quantity/current-source risks                | WP2-F02/F03/F04/F06/F10-F12;V08-V24/V38/V40/V48                                                        |
| WP4-F01–F10                | All retained: immutable I/MA cycles/child totality, isolation, origin/rights/discrepancy, locking/retention/provenance    | WP2-F01-F05/F08/F10/F12/F14;V18-V24/V39                                                                |
| WP5-F01–F12                | All retained: credit/coverage, lineage, precision, policy/handoff/retention/current-vs-historical risks                   | O1 decision resolves former target-choice question only; implementation/integrity findings remain OPEN |
| WP6-F01–F14                | All retained: actual audit/restore/principal/cohort/mapping/recovery evidence absent                                      | O1/O2/O3 choice now accepted, not data/grant/recovery proof; WP2-F03/F04/F10-F14                       |
| REV-01                     | PROPOSED nine cycle protocols/inverse FK/mutable pointer patterns, no deferred FK/event update                            | 4/CY01-CY09/15;V01/V02/V13/V18-V20/V25/V43                                                             |
| REV-02                     | ACCEPTED multiple source-lines; head/line/G budget technical enforcement PROPOSED/UNVERIFIED                              | 8/EX05-06;V15-V17/V22/V44                                                                              |
| REV-03                     | Remaining=reserved-consumed-released; protected current counter/pool and shared consumer contract                         | 6/EX02-03;V08-V12/V38/V48                                                                              |
| REV-04                     | Assignment-local guard proposed; global employee hard prohibition OPEN, not introduced                                    | 10/EX10;V34-V36                                                                                        |
| REV-05                     | Typed MI/MA inverse ownership selected technical proposal, generic event movement pointer omitted                         | 7/EX04;V18-V24                                                                                         |
| O1/O2/O3                   | ACCEPTED current baseline/strategic later integration/no temporary adapter coexistence                                    | 2/8/14;V44, no owner reapproval requested                                                              |

Already accepted: O1 current/O2 strategic/O3 not selected és REV02 multi-line; D1–D11/AM/F/BD nem
újra eldöntendők. Technical PROPOSED: inverse typed movement links, explicit batch_identity,
Ghead/line singletonek, protected routine-only DML, transaction profile és L extension.
Új owner-részletkérdések review-ra: REV04 global employee overlap hard/advisory scope; WP4 pending
scrap/unmatched-rights workflow és speciális admission legalbasis; WP5 financial final-charge/
rounding/settlement/correction/closure policy; WP6 RPO/RTO/retention és release window. Addig érintett
út unknown/blocked, nem inventált default. E feladat nem igényli, hogy a tervezéshez ezeket kitaláljuk.

## 17. WP7 verification matrix

Minden tesztsor **PLANNED / NOT EXECUTED**. Izolált actual MySQL8.4/InnoDB, dedicated runtime és
migration/definer principal, két külön session/barrier és explicit isolation szükséges. SQLite FK-on
compatibility külön: nem bizonyít precision, raw grant, row-lock, trigger/routine vagy concurrency
egyenértékűséget. Current tesztforrások csak regressziós inputok; nem futtattuk őket.

| ID/status/scenario                                                  | Initial state                                                                                                     | Operation                                                                             | Expected database result                                                                                        | Expected business result                                                            | Required evidence                                                                         |
| ------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------- |
| V01 **PLANNED / NOT EXECUTED** – All FK SCC/totality                | Exact final 25+7 DDL on isolated MySQL, every CY graph                                                            | Create legitimate paths then raw orphan/missing-child/pointer partial commit attempts | All valid insert sequences work without event UPDATE/FK disable; orphan runtime paths denied                    | No incomplete command/fact/proof may commit                                         | Catalog/SCC graph, exact SQL/order/principal and beforeafter rows                         |
| V02 **PLANNED / NOT EXECUTED** – CHECK/FK/action capability         | Actual8.4 schema fragments, CHECK columns FK with omitted and explicit actions                                    | CREATE/ALTER all chosen combinations in disposable env later                          | Chosen syntax installs ENFORCED checks; incompatible alternative documented rejected                            | No claimed enforcement from abstract definitions                                    | SHOW CREATE/catalog/check validation and error artifacts                                  |
| V03 **PLANNED / NOT EXECUTED** – Nullable composite bypass          | Child tuple one fieldNULL, wrong owner/order/serial counterpart                                                   | Invalid inserts/updates vs valid optional allNULL                                     | Shape denies partial tuple; matching full tuple succeeds                                                        | No NULL bypass for owner/source/proof                                               | Raw SQL constraints and FK/NULL expected outcomes                                         |
| V04 **PLANNED / NOT EXECUTED** – NULL batch single identity         | No-batch duplicate fixtures and real batch0/collision, two sessions same missing pool                             | Audit/map proposed key; concurrent valid balance creation                             | One normalized identity, no arbitrary merge; collision blocks alignment                                         | No duplicate pool/no lost quantities                                                | U/CHECK/cutoff/PK mapping, q reconciliation, session barrier                              |
| V05 **PLANNED / NOT EXECUTED** – Machine-key codec                  | Case variants/trailing bytes/UTF8/oversize/hash bytes and canonical same occurrence                               | Routine/API/DB roundtrip and duplicate attempts                                       | Exact intended byte equality; no silent truncation/prefix collisions                                            | Occurrence duplicate vs distinct identity correct                                   | Codec vectors, binary lengths/collation/index and canonical hashes                        |
| V06 **PLANNED / NOT EXECUTED** – Command retry                      | Same key/hash/actor and changed payload/actor request                                                             | Replay before/after commit and connect loss                                           | Single completed canonical result, conflicting input no effects                                                 | No duplicate actual occurrence from new request                                     | Command U/hash/auth trace and row/audit counts                                            |
| V07 **PLANNED / NOT EXECUTED** – Claim acquisition                  | Two cases same proven own-manufactured serial, origin mismatch candidate                                          | Concurrent case/member acquire, trigger and direct raw acquire                        | At most1 active instance PK, valid member+order+claim all T; raw denied                                         | No duplicate active RMA or fake semi-finished eligibility                           | Case/member/claim/origin proof, two-session outcomes                                      |
| V08 **PLANNED / NOT EXECUTED** – Reserve creation                   | Eligible stock10, existing own/other remaining, custody/quarantine stocks                                         | Concurrent own reserve and protected new reserve                                      | Sumeligible assigned<=pool; matching owner and serial slots                                                     | Excluded inventory never normal supply                                              | Pool/current-lock/counter/owner/history evidence                                          |
| V09 **PLANNED / NOT EXECUTED** – Partial consume                    | Company reservation10, actual operation approved, available physical10                                            | Consume4 then same-key retry                                                          | Counter4,released0,remaining6; one material/movement and physical -4                                            | No full-row Consumed shortcut/double deduction                                      | Exact fact/counters/balance/audit/command proof                                           |
| V10 **PLANNED / NOT EXECUTED** – Partial release                    | Reservation10 consumed4, permitted unused release proof                                                           | Release2 then attempt consuming released portion                                      | Consumed4/released2/remaining4; release physical0                                                               | Only unreleased4 consumable; revoked portion cannot return free twice               | Counter and history fold+permission and no movement delta                                 |
| V11 **PLANNED / NOT EXECUTED** – Terminal/cancel bounds             | Full consume10, full release10 and mixed4+6 separate fixtures                                                     | Extra positive release/consume and cancelled workflow retry                           | Remaining0, all extra q refused; old actual facts/counters retained                                             | Cancel/return never resets fulfillment or logical obligation                        | Boundary CHECK/status/key/history matrix                                                  |
| V12 **PLANNED / NOT EXECUTED** – Consume-release race               | Remaining6, two sessions consume4/release4                                                                        | Overlap both orders and audit fault                                                   | One fits, other conflict/recompute, no negative remaining, full T rollback on fault                             | No double use/release of same4                                                      | Barrier/locks/1205-1213 outcomes plus q/audit                                             |
| V13 **PLANNED / NOT EXECUTED** – Inverse shipment/material relation | Existing valid member/order/reservation, no child movement yet                                                    | Complete consume/shipment then orphan parent and duplicate child attempts             | One typed movement owner, no parent futureFK/event UPDATE, orphan raw denied                                    | Actual stock decreases once, preparation doesn't dispatch                           | Insert protocol/owner U/canonical command+physical facts                                  |
| V14 **PLANNED / NOT EXECUTED** – Owner/item/unit tuples             | Repair vs legacy owners, PO/customer mismatch, batch wrong item, serial wrong item                                | Valid and invalid material/reserve/source/instance tuples                             | Valid legacy two refs accepted only consistent, repair XOR enforced, mismatch rejected                          | No owner transfer through FK update or unit implicit conversion                     | All tuple/row guards+actual item_batches U(item,batch_number) catalog                     |
| V15 **PLANNED / NOT EXECUTED** – Generation multi-line              | Req/G authorized10 with stable linekeys a4,b6 on two PRlines                                                      | Generate once/retry/consolidate same PRitem alternative                               | One head, multiple lines allowed, anchored sources and exact sum10                                              | Accepted REV02 not blocked by source U(req,gen)                                     | G plan/hash/source/line/PR q exact trace                                                  |
| V16 **PLANNED / NOT EXECUTED** – Generation overbudget/missing line | Budget10, lines4+6 plus third1, missing to-side and duplicated key variants                                       | Attempt allocate/complete at each fault point                                         | Whole T fails, no orphanG/source/partial transfer; original history unchanged                                   | No double coverage or incomplete generation                                         | Cap/equality/line-set singleton and rollback artifacts                                    |
| V17 **PLANNED / NOT EXECUTED** – Generation shared-owner race       | Two requirements same item/free pool or two callers same req delta                                                | Concurrent generation/current GR recalculation                                        | Serialized current D/R/I/K fold, delta allocated at mostonce                                                    | No stale SUM/duplicate buy under RR                                                 | Two sessions/current roots/commits, G/source q and audit                                  |
| V18 **PLANNED / NOT EXECUTED** – Intake I/MI                        | Verified returned replacement serial/location/time/source, ownership unresolved                                   | Protected actual intake                                                               | One I+MI external-external qty1, company0, quarantine/on-site1                                                  | Unknown owner does not block legitimate physical record or imply company            | AM-02-G1/G2 typed tuple/doc/time/command evidence                                         |
| V19 **PLANNED / NOT EXECUTED** – Admission A/MA                     | Prior I/MI, valid company basis/inspection/right and distinct custody fixture                                     | Protected company/custody classification, prior outbound reverse links                | One A/I and MA, company+1 or custody0; quarantine remains, no new receipt date                                  | Ge0 until separate release; legal W not implied                                     | All A1-A10, MI/MA separate IDs/scopes/source/serial/reverse proof                         |
| V20 **PLANNED / NOT EXECUTED** – Duplicate intake/admission race    | Same physical return or two target admissions of sameI, two sessions                                              | Different command keys and same-key retries both commit orders                        | Exactly1 I/MI and1 A/MA, target conflict safe rollback                                                          | No double physical/company stock or target switch                                   | Occurrence keys+U+barrier/lock+canonical replay evidence                                  |
| V21 **PLANNED / NOT EXECUTED** – Quarantine release race            | Admitted company quarantine, valid then stale/wrong condition/right proof                                         | Concurrent release/hold/material consume                                              | Fresh authorized release only; no new admission/movement; stale use refused                                     | Every consumer excludes quarantine until valid release                              | Typed inspection/current scope/owner lock and availability/audit                          |
| V22 **PLANNED / NOT EXECUTED** – Partial GR/coverage cap            | PO10, GR4+3, two assigned roots and unassigned quantity; cancel/substitute race                                   | Post and convert receipt/cancel with shared GRitem/PO locks                           | ActualG7 once, assigned receipt<=actual, residual3 not incoming twice, no wrong supplier rewrite                | Ge vsG/C separate, supplier obligation retained if not cancelled                    | GRitem/PO/source/root/stock/audit exact folds and both order races                        |
| V23 **PLANNED / NOT EXECUTED** – Redispatch/root chain              | Fulfilled replacement attempt and physical return after it, D9 authorization                                      | Create next reservation/release and duplicate previous pointer attempt                | New attempt/reservation/root chain, old counters unchanged; one release/reservation                             | Physical previous return != legal reversal, original outcome independent            | Condition/right/source/recipient/previous/U/audit proof                                   |
| V24 **PLANNED / NOT EXECUTED** – Ownership/reversal                 | Existing legal chain, original company dispatch/return and wrong-subject proof                                    | Concurrent ownership transition and duplicate/full reversal/correction                | One causal reversal/original, current previous valid; physical delta0                                           | No ownership date from physical receipt or reused compensated outbound              | Legal source/current-chain/serial/version actor and immutable histories                   |
| V25 **PLANNED / NOT EXECUTED** – Fee/quote approval cap             | Verified fee10000, approved credit6000, same-order matching currency/scale                                        | Submit vs approve, competing quote and new fee-version approval                       | Submit no allocation; approval one source-order claim6000 only                                                  | No parallel4000 wallet/no quote=payment or fake fee                                 | Fee/quote/line/response authority, active U and exact totals                              |
| V26 **PLANNED / NOT EXECUTED** – Credit usage correction            | Allocation6000, proven usage4000, original proof/correction fixtures                                              | Record correction/reversal, excess/duplicate/crosssubject attempts                    | New causal history only, effective caps valid, no active-key auto NULL/reused credit                            | Reversal not refund/new entitlement                                                 | Original usage/amount/currency/source/actor proof and invariant failures                  |
| V27 **PLANNED / NOT EXECUTED** – D7 release/cancellation            | Unused+unsettled valid, unknown/executed/settled other fixtures                                                   | Cancel/supersede and authorized release                                               | Only valid unused/unsettled released with doc/history; others retained/key kept                                 | No auto release/reuse, unknown not0                                                 | Execution/settlement reviews, immutable previous observations and raw negative            |
| V28 **PLANNED / NOT EXECUTED** – Close missing proof                | Case with missing original disposition, quarantine/credit/PO obligation                                           | Finalize with status/JSON proof_ok caller claim                                       | No case-close or claim release, no partial command/audit                                                        | Evidence-based close, no financial/PO status substitute                             | Typed missing-proof diagnostics and unchanged case/claims                                 |
| V29 **PLANNED / NOT EXECUTED** – Close multiple members             | Case two members one resolved and one unresolved; then actual all-valid proof                                     | Finalize both phases                                                                  | First refused entire case; valid full proof closes all and deletes only own claims                              | No per-member premature release or independent finalizer                            | Full member set/cardinality/command/claim-close history+audit                             |
| V30 **PLANNED / NOT EXECUTED** – Close vs new child                 | Same case, concurrent return/evidence/demand/credit append vs close                                               | Overlap both commit orders using case mutex                                           | Child before close included; closed parent refuses incompatible new obligation                                  | No hidden unresolved fact after proof scan                                          | Root locks/current reads and whole outcomes with barriers                                 |
| V31 **PLANNED / NOT EXECUTED** – Raw claim bypass                   | Actual runtime/alternate connection, open/resolved cases and claimed serial                                       | INSERT/UPDATE/DELETE/TRUNCATE/member bulk cascade attempts                            | Direct forbidden, guarded finalizer valid; no trigger FK-cascade assumption                                     | Claims cannot disappear by SQL/admin UI shortcut                                    | Effective grants+trigger/raw SQL denied/allowed evidence                                  |
| V32 **PLANNED / NOT EXECUTED** – Close idempotency/reopen           | Completed close, same command and new-key request                                                                 | Replay/attempt reopening/child membership mutation                                    | Canonical result once, no extra claim removal/history; reopen not allowed                                       | No accidental business reopening during retry or migration                          | Close source/history/count/permissions and safe conflict                                  |
| V33 **PLANNED / NOT EXECUTED** – Raw credit/history bypass          | Runtime user and duplicate/partial NULL/cross-order allocation fixtures                                           | Direct mutate financial facts or invoke generic helper                                | Denied; valid routine enforces U/cap/typed proof, orphan commit unavailable                                     | No unauthorized historical alteration/double credit                                 | Principals/EX07/EX09 raw and valid evidence                                               |
| V34 **PLANNED / NOT EXECUTED** – Local assignment interval          | One assignment and overlapping/touching/zero-duration/completed intervals                                         | Concurrent local starts/corrections/ends, duplicate assign                            | One open interval, local effective overlap guard, original completed record immutable                           | No lost time/reassigned identity overwrite                                          | Assignment mutex/U/interval policy vectors and typed history                              |
| V35 **PLANNED / NOT EXECUTED** – Started scope immutable            | Started operation or submitted quote, original manufacturing task/sequence                                        | Change scope/plan, supersede and try prior approval on new work                       | Started plan unchanged; new allowed draft requires current approved scope before work                           | F4 preserved, manufacturing serial/tasks not reused for repair                      | Plan/quote hashes, operation history and denial/rollback                                  |
| V36 **PLANNED / NOT EXECUTED** – Global employee policy boundary    | Same employee across two operations and production availability input                                             | Plan/record intervals under current local-only contract                               | No invented global employee U/no-overlap rejection; accepted future rule only explicit fixture                  | Preference vs hard invariant distinct, actual time truthful                         | Owner policy absent classification; future alternative tests clearly conditional          |
| V37 **PLANNED / NOT EXECUTED** – Genealogy/self predecessor         | Existing two graph components and concurrent joins that would form cycle; fee/time/ownership predecessor fixtures | Try self/future/crosssubject/sibling/cycle and overlapping graph edits                | No invalid effective cycle or wrong subject, no immutable UPDATE; complete component replan locks               | Traceability/corrections preserved not graph rebuilt from labels                    | SCC/closure/ID/correction/sequence tests and current graph locks                          |
| V38 **PLANNED / NOT EXECUTED** – Audit/constraint fault atomicity   | Valid reserve/material/credit/G/return/close operation, fault after each write                                    | Inject DB/history/audit/movement/claim/constraint failures                            | All new operation effects rollback, prior facts retained, command not completed                                 | No partial stock/credit/coverage/close; retry effect once                           | Beforeafter q/rows/audit/command connection and handler result                            |
| V39 **PLANNED / NOT EXECUTED** – Proof/file retention               | Referenced doc versions/bytes, morph/current flags and soft-deleted masters                                       | Hard/soft/cascade/storage-delete/update and restore evidence reads                    | Protected refs/history unchanged and readable; checksum/version mismatch blocks proof                           | DB metadata restore not approval/origin/legal truth                                 | Exact file manifests/rights, FK/trigger/principal and download/checksum                   |
| V40 **PLANNED / NOT EXECUTED** – 1205/1213/unknown commit           | Two connections, lock timeout/deadlock, disconnect after COMMIT response lost                                     | Retry same full command after explicit rollback/canonical lookup                      | No half counters/lines/facts, one completed occurrence; fresh guards every retry                                | No statement-only retry double effect                                               | Engine errors/settings, barrier schedules, server commit/canonical key trace              |
| V41 **PLANNED / NOT EXECUTED** – Privilege graph/definer            | Real migration/runtime/posting/report roles, defaults/inherited/alternate connection                              | Raw DML/DDL/helper EXECUTE and allowed operation calls                                | Forbidden paths fail, scoped complete routine succeeds, restored definer rights match                           | No admin credential in runtime or SQL SECURITY actor confusion                      | SHOW GRANTS/objects/principal test artifacts with secrets excluded                        |
| V42 **PLANNED / NOT EXECUTED** – Autocommit and PDO mode            | Mode1 no-BEGIN and explicitBEGIN, mode0 dedicated connection, nested/reconnected sessions                         | Invoke protected routine across driver lifecycle                                      | Mode1 refused under selected profile; mode0 all effects only outer commit; driver state correct or gate blocked | No false atomic CALL assumption; no accepted internal finalizer COMMIT              | PDO/connection/autocommit/transaction trace, no partial persisted effects                 |
| V43 **PLANNED / NOT EXECUTED** – CALL partial commit attack         | Runtime can CALL but no rawDML/helper; force exception midway and catch then commit                               | No-BEGIN/mode toggles, duplicate key, timeout and posterror attempted COMMIT          | Procedure handler whole rollback+RESIGNAL; no orphan/processing/event survives                                  | External caller cannot retain partial command, no modeflag authorization bypass     | Beforeafter raw principal/session fault matrix and actual rows                            |
| V44 **PLANNED / NOT EXECUTED** – O1/O2 historical boundary          | Current sources linked to legacy req, pinned historical PO+BOM/Proposal variants                                  | Static schema/adoption compatibility and isolated mapping rehearsal later             | Stable original IDs/source history, no O3 adapter deployment/automatic historical import                        | Feasible O2 later review, no source/peg reinterpretation/current capability fiction | Pinned branch/ADR/object/source and lineage/precision architecture review                 |
| V45 **PLANNED / NOT EXECUTED** – Decimal/codec boundaries           | Over3scale,12,3 overflow,18,3 narrowing, largeSUM/QxM, case/trailing key bytes                                    | Request/routine beforeCAST/DB/JSON/cache/UI roundtrip                                 | Reject or explicit approved policy before loss; exact units/bytes after valid roundtrip                         | No silent precision or policy creation; canonical hash matches source               | Grammar/range/SQLWARNING/strictmode and serialization vectors                             |
| V46 **PLANNED / NOT EXECUTED** – Backup/rollout/restore             | WP6 stage fixtures, valid postcutoff RMA facts/external shipment/doc bytes/grants                                 | Feature drain/recovery/paired restore rehearsal later                                 | Valid history retained/reconciled, no destructive down/replay effect; old binary unsafe refused                 | RPO/RTO actual evidence or explicit BLOCKED, not guessed zero loss                  | DB+files+key/grant/routine/tail+external ack manifest and restore results                 |
| V47 **PLANNED / NOT EXECUTED** – SQLite difference                  | Separate FK-on SQLite affinity/index/rebuild fixture vs real MySQL8.4                                             | Run later engine-specific migration/NULL/CHECK/raw SQL probes                         | Differences documented; SQLite cannot substitute grants/row-lock/routine/precision proof                        | No portability success claimed from prototype only                                  | Engine/version/catalog and separate actual pass/fail artifacts                            |
| V48 **PLANNED / NOT EXECUTED** – Shared consumers/cache             | All282 WP3 methods plus current jobs/BI/direct SQL inventory, warm caches                                         | Mixed-version gate/old writer/GR/consume/reports, afterCommit cache fault later       | Unsafe path fenced, compatible E/R/P/M/Q/L everywhere, business DB commit once despite cachefail                | No stale normal supply/quarantine leak, no incoming+received double count           | Method/cohort/actor/principal/node/payload/domain-version matrix and negative regressions |

Forrásalapú regressziós kiindulás: [ProcurementManagementUiTest](../../tests/Feature/ProcurementManagementUiTest.php),
[ProductionExecutionUiTest](../../tests/Feature/ProductionExecutionUiTest.php),
[BusinessCacheInvalidationTest](../../tests/Feature/BusinessCacheInvalidationTest.php),
[InventoryManagementUiTest](../../tests/Feature/InventoryManagementUiTest.php),
[ReportingAnalyticsTest](../../tests/Feature/ReportingAnalyticsTest.php),
[DocumentTest](../../tests/Feature/DocumentTest.php). Történeti B 35/35 nem e terv teljes DB-proofja.

## 18. Migration-readiness blockers

1. Teljes selected exact CREATE/ALTER/routine/grant signature és matrix összeállítása, új/old field
   delta egyeztetés; az illustrative fragmentek nem futtatott complete DDL. TYPE/NULL/FK/CHECK/action
   kombinációk, cycle-safe valid inserts és orphan-child totality actual engine proof hiányzik.
2. Protected public CALL transaction-profile, actor/trust boundary, direct DML deny és C1 finalizer/
   claim-trigger teljes runtime-grant bizonyíték hiányzik; Policies nem elegendőek.
3. Same-lock current cross-row conservation és minden shared writer/reader rolloutja, actual
   two-connection fault/retry/race, audit rollback/cache compatibility hiányzik.
4. Legacy representative snapshot/catalog/drift, batch/owner/counter/source/serial/proof mapping,
   non-null/index transition és valid-history backup/restore rehearsal hiányzik.
5. Explicit OPEN business/rule részletek az érintett operationt blokkolják; O1/O2/O3/REV02 nem OPEN.
   Future O2 integration nem mostani readiness prerequisite implementáció, de visszafordíthatatlan
   identity/precision/source döntés nem akadályozhatja.

Verdikt: accepted invariánsoknak van konkrét **javasolt enforcement útjuk**, de a kiválasztatlan
részletek és futtatási bizonyíték hiánya miatt **NOT MIGRATION READY**. Korábbi findingok nem záródnak
le egy proposed kulcstól/rutintól; nincs DB-enforced vagy concurrency-safe sikerállítás.
Projektgazdai review-ra vár. WP7 nem indul automatikusan; migráció/alkalmazásimplementáció nincs.

## 19. Acceptance checklist

| Követelmény          | Dokumentációs átadás és bizonyítékhatár                                                                                                   |
| -------------------- | ----------------------------------------------------------------------------------------------------------------------------------------- |
| Baseline/scope       | Expected branch/HEAD, öt pending hash, tracked/index unchanged, csak új WP2                                                               |
| Inventory            | 25 new +7 direct change, 18 affected complete existing references; field/key/null/status/authority/writer/delete/dependency profiles      |
| Accepted decisions   | O1/O2/O3 és REV02 elfogadott, RMA-BD-001–020/D1–D11/F1–F4/AM/C1 preserved                                                                 |
| Integrity            | FK cycles, nullable identity, counters/coverage/credit totality, typed MI/MA és proof-only close executable-level protocols               |
| Concurrency/security | Deterministic shared L extension, explicit current reads, full rollback/retry, restricted principals/CALL/autocommit gate és trust limits |
| Legacy/future        | O1 no silent remap; O2 future path; WP6 alignment/cohort/recovery, no history-loss rollback                                               |
| Verification         | Planned setup/operation/DB/business/evidence és permission/concurrency/MySQL/SQLite/recovery coverage; no tests executed                  |
| Status               | REVIEW / NOT MIGRATION READY; OPEN findings and proof gates, no automatic WP7                                                             |

### 19.1. Elvégzett dokumentációs ellenőrzések – 2026-11-04

| Check                         | Tényleges dokumentációs eredmény / határ                                                                                                                    |
| ----------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Baseline/references           | PASS: expected branch/HEAD, mind a11 required document létezik; öt pending belépési hash rögzítve                                                           |
| Struktúra és inventory        | PASS: 19 főfejezet; 25 névazonos új profil; WP1 18 impact-sorából 7 S és11 R; hét S részletes extension/null/key/FK/index profil                            |
| Critical design coverage      | PASS: 9 CY ciklusprotokoll, 12 C konkurenciahelyzet, 10 EX illustrative SQL/procedural példa; nincs SQL file vagy futtatás                                  |
| Verification és findings      | PASS: 48 V01–V48 scenario öt required postcondition mezővel, mind PLANNED / NOT EXECUTED; 14 OPEN finding, 4 BLOCKER / 8 HIGH / 1 MEDIUM / 1 LOW            |
| Authority/trace               | PASS:20 BD, D1–D11, F1–F4, AM/AM-G/C1 és korábbi WP finding/REV trace; O1/O2/O3/REV02 accepted és technical/proof gates külön                               |
| Hivatkozások                  | PASS:50 relatív fájl-/anchorhivatkozás célja ellenőrzött;8 distinct hivatalos Oracle MySQL8.4 dokumentációs forrás; external docs nem deployed-engine proof |
| Prettier                      | PASS: helyben telepített formatter check mind a hat pending WP-re; csak WP2 formázása történt                                                               |
| Encoding/Markdown/whitespace  | PASS: strict UTF-8, nincs BOM/replacement character/trailing whitespace; code fence-ek zártak, számozás és matrix row-shape ellenőrzött                     |
| Sensitive-data mintavizsgálat | PASS: a hat pending WP-ben nem talált credential-assignment/token/private-key/email vagy abszolút environment-path mintát; nem production-data audit        |
| Existing artifacts            | PASS: WP1/WP3/WP4/WP5/WP6 byte-hash és történeti B JSON hash változatlan; tracked diff és staging üres                                                      |
| Git integrity                 | PASS:`git diff --check`; induló öt untracked mellé csak WP2 került, branch/HEAD változatlan                                                                 |

Az ellenőrzések inline, memóriából futottak; nem jött létre új verifier/script/artifact a WP2-n kívül.
Nincs actual SQL syntax/DDL/constraint/routine/grant/lock/data/backup/restore teszt; ezek UNVERIFIED.
Dokumentációs PASS nem jelenti a findingok lezárását, WP2 elfogadását vagy migration-readiness-t.

Releváns [Definition of Done](../../docs/project-management/definition-of-done.md): dokumentáció;
[Layered Quality Gates](../../docs/development/quality-gates.md): formatter/link/source/trace,
UTF-8/whitespace/sensitive scan és Git integrity. Existing README/index/ADR/memory változtatás
a kifejezett egy-új-fájl scope miatt nem része a feladatnak. PHP/Pint/backend/frontend/DB/DDL/
migration/grant/restore nem futott, dependency install és új ellenőrző scriptfájl nincs.
