# RMA-0003/C2-WP1 – Teljes sémaleltár és integritási térkép

Státusz: **REVIEW / NOT MIGRATION READY**. Dátum: 2026-11-02.
Ez dokumentációs sémaleltár; nincs végrehajtott C2-DDL, migráció vagy működő RMA-funkció.
Az alábbi technikai oszlop-, kulcs- és guard-javaslatok **PROPOSED** elemek.

## 1. Baseline, autoritás és bizonyítékhatár

- Ellenőrzött branch: `feature/returns-repair-management`.
- Ellenőrzött HEAD és meglévő C1-checkpoint: `e21e83da44e7ec2228efa6c5c6f8d1021340b73b`.
- Induláskor tiszta munkafa és üres staging; a C1-checkpoint hét fájlja jelen van.
- Kizárólag ez az új dokumentum készül. Az előzmények, PHP-verifikáció és JSON változatlanok.
- Forrás: repository-migráció és Eloquent-modell; ez nem élő adatbázis-introspekció, adatminőség- vagy grantbizonyíték.

Elsődleges üzleti autoritás az [Accepted ADR-0006](../decisions/0006-rma-repair-management-architecture.md).
Teljesen áttekintett előzmények: [A adatmodell](rma-0003a-data-model-integrity-design.md),
[B integritásreview](rma-0003b-integrity-verification.md),
[C1 döntések és kapuk](rma-0003c1-business-decisions-integrity-gates.md),
[C2 előkészítés](rma-0003c2-detailed-schema-review-plan.md).
Az ADR **Accepted**, C1 **ACCEPTED WITH CONDITIONS**; D1–D11 elfogadott,
AM-01–AM-04 jóváhagyott, AM-02-G1–G4 kötelező. WP1 nem nyitja újra ezeket.
A teljes C2 és a migrációs készenlét ettől még nem igazolt.
Navigáció: [új táblák](#3-a-25-új-tábla-teljes-jelöltleltára),
[MES-hatások](#4-meglévő-mes-séma-bizonyított-tény-és-javasolt-hatás),
[nyitott kapuk](#8-findingok--nyitott-technikai-kapuk).

A B 35/35 szűk SQL-prototípusának eredménye történeti bizonyíték; nem bizonyítja
a C1 claim-finalizert, a 25 teljes tábla DDL-jét vagy a shared inventory integrációt.
A [történeti JSON](verification/rma-0003b-results.json) nem regenerálódik;
SHA-256: `d1b4443724984cbd21c9852c4611fa659411e09924192d7b3b9296164e33a6e6`.
A [jelenlegi script](verification/rma-0003b-verify.php) credential-hardening utáni változat;
a régi dokumentumok script-hash-e történeti, nem jelenlegi egyezési feltétel.
A scriptet itt nem futtatjuk, MySQL-/SQLite-kapcsolat nem készül.

A releváns DoD-típus dokumentáció: [Definition of Done](../../docs/project-management/definition-of-done.md)
és [Layered Quality Gates](../../docs/development/quality-gates.md).
A nyitott implementációs blokkolók az inventory tartalmát képezik, nem elhallgatott sikerfeltételek.

## 2. Jelölés, közös szerződés és darabszám

**25 új jelölt**, **18 név szerint vizsgált meglévő érintett tábla**.
A 18-ból 7 közvetlen séma-bővítés; 11 kapcsolati, törlésvédelmi vagy consumer-felülvizsgálat.
Az FK-cél önmagában nem jelent új oszlopot. További master/FK-célok külön szerepelnek a 4. fejezetben.
Nincs új általános készletfőkönyv: a `stock_movements` marad a fizikai/könyvelési mozgások forrása.

| Jel              | Laravel / MySQL cél                                   | SQLite megfeleltetés, null/default                                     |
| ---------------- | ----------------------------------------------------- | ---------------------------------------------------------------------- |
| ID               | `id()`, unsigned BIGINT auto-increment PK             | INTEGER PK; csak az active_repair_claims kivétel                       |
| F                | unsigned BIGINT, valódi FK a nyíl utáni tábla id-jére | azonos integer típus és FK, FK-védelem kötelező                        |
| U32 / U64        | unsigned integer / unsigned BIGINT                    | INTEGER + explicit nemnegatív CHECK                                    |
| S(n) / T / J     | varchar(n) / text / JSON                              | TEXT; JSON-validálás motorfüggő, nem FK-pótlék                         |
| DT               | dateTime(6), UTC                                      | normalizált UTC szöveg; ugyanaz a service-precizitás                   |
| Q                | decimal(12,3)                                         | exact decimal szerződés; SQLite affinity nem azonos precision-garancia |
| M                | signed BIGINT, skálázott pénzegység                   | INTEGER; skála 0..6, nincs float                                       |
| B                | boolean, CHECK IN(0,1)                                | INTEGER + ugyanaz a CHECK                                              |
| ! / ? / =x       | NOT NULL / nullable / explicit default                | ! esetén nincs implicit default; ? default NULL                        |
| PK / U / FK / IX | primary / unique / foreign key / index                | oszlopsorrend a blokkban megadott                                      |

Minden új tábla implicit mezői: `id:ID`, `created_at:DT!`; az active_repair_claims
helyett `item_instance_id` a PK. **MUTABLE** tábláknál `updated_at:DT!` is kötelező;
**EVENT** táblákon nincs updated_at. Új soft delete nincs. Minden F mező FK és
IX(field), kivéve ha azonos bal prefixű PK/U/IX már fedi; composite FK teljes
child-tuple indexet kap. Ez minden alábbi név szerint felsorolt F oszlopra érvényes.
Minden FK delete/update akciója effective **NO ACTION / RESTRICT**, nincs cascade
vagy nullázás. Pontos motoronkénti DDL és CHECK/FK-akció kompatibilitás: WP2.

Alap collation-javaslat a projekt `utf8mb4_unicode_ci` beállítása; gépi occurrence-,
idempotencia- és hash-kulcsnál byte-exact összehasonlítás szükséges. Ezekhez
ASCII/binary collation vagy normalizált bináris kulcs WP2-ben választandó,
a jelenlegi case-insensitive default nem tekinthető automatikusan helyesnek (WP1-F08).
A szöveg-hosszak technikai javaslatok, nem új üzleti korlátok.

EVENT esetén minden üzleti mező insert után immutable; javítás új esemény,
typed supersedes/causal kapcsolat és indok. MUTABLE esetén PK, létrehozás, owner,
szülő-FK és serial immutable; a további rögzülő mezőket táblánként megadjuk.
FK/U önmagában nem append-only védelem: korlátozott írásjog, célzott guard,
indokolt trigger és audittal közös tranzakció szükséges. Dokumentum-FK mellett
konkrét verzió/checksum és a tárolt fájl megőrzése is feltétel.

A deklaratív blokkok nem végrehajtható SQL-ek. CHECK csak egy sor mezőit védi,
nem más táblát, órát, összeget vagy gráfot. Az explicit NULL-ágakat kötelező
megtartani; SQL UNKNOWN nem elutasítás. A cross-row feltételek a táblaleírás és
az 5. fejezet tranzakciós guardjai. Minden command/domainírás/history/audit atomikus;
hiba teljes rollback, afterCommit cache-invalidálás. A C1 T-C1-04 közös lock-sorrendje
megmarad: case-ek → items → item_instances → repair/production order →
operations/fee/quote → requirements/coverage → reservations → balances → release/proof;
csoporton belül ID szerint. Nem-RMA író sem vehet fel később case-lockot item-lock
után: előre határozza meg a case-eket, vagy elutasítás után rendezett sorrenddel
próbálkozik. A lenti mutexek e sorrend részei; WP2/WP3 cross-domain és PO/GR
illesztése még nem végrehajtott deadlockbizonyíték.

## 3. A 25 új tábla teljes jelöltleltára

### 3.1. repair_requests

**Cél/gazda:** egy tulajdonos RMA-ügye; RMA case aggregate.
**Osztály:** Source of Truth + MUTABLE lifecycle.

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

**Immutable/lifecycle:** tulajdonos, létrehozó, eredeti bejelentő/owner-snapshot és nyitócommand
rögzül. Reporter lehet külön szereplő. Nyitás tagokkal, claimmel és munkalappal atomikus.
`closing_command_id:F? -> repair_commands`, U(closing_command_id) a C1-kiegészítés;
open esetén NULL, closed esetén kötelező a closed_at/closed_by mellett.
Csak a restricted finalizer zárhat, nincs reopen vagy tagonkénti claim-release.
**Döntés:** BD-001/003/004/011, D3, F2; C1 G1.
**MES:** customers/users/employees/documents bizonyítékok.
**Nyitott:** WP1-F01; a close proof teljes typed halmaza, rutinjog és rollback WP2/WP4/WP5-ben igazolandó.

### 3.2. repair_request_items

**Cél/gazda:** ügytagság és az eredeti gyártott serial jogosultsága; case tulajdonában.
**Osztály:** Source of Truth + MUTABLE receipt-pointer.

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

**C1-kiegészítés:** `membership_command_id:F! -> repair_commands`, IX(membership_command_id).
A command/actor azonossága és nyitott parent vizsgálandó acquire-triggerben;
az AFTER INSERT trigger hozza létre a claimet ugyanabban a tranzakcióban.
**Immutable/lifecycle:** parent/owner/serial/eredet/bizonyíték immutable;
receipt NULL→érték egyszer, a receipt movement/custody/instance-projekcióval atomikus.
Legalább egy order létét önmagában nem bizonyítja a child FK.
**Döntés:** BD-001/002/004/015/016, D2/D3, F3.
**MES:** item_instances, production_orders, customers, users, stock_movements.
**Nyitott:** WP1-F01/F04; bizonyított saját gyártás, nem a semi_finished_product enum önmagában.

### 3.3. active_repair_claims

**Cél/gazda:** serial globális kizárása más aktív RMA-ból; case integritási őr.
**Osztály:** Derived State, MUTABLE technikai claim; története nem itt tárolt.

```text
item_instance_id:F! -> item_instances; PK(item_instance_id), nincs id()
repair_request_item_id:F! -> repair_request_items
claimed_by:F! -> users; command_id:F! -> repair_commands
U(repair_request_item_id)
FK(repair_request_item_id,item_instance_id)
 -> repair_request_items(id,item_instance_id)
CHECK: nincs további soron belüli üzleti feltétel
```

**Immutable/lifecycle:** tagság/serial/claim-actor/command nem cserélhető.
Acquire csak tagságtriggerrel, DELETE csak teljes case-close finalizerből;
runtime közvetlen INSERT/UPDATE/DELETE/TRUNCATE/DDL tiltott.
BEFORE DELETE ellenőrzi closed parent + completed closing command + typed close-history meglétét.
Nincs session-variable bypass vagy általános törlőproxy. A finalizer nem fogad proof_ok flaget
és nem COMMIT-ol belül. Claim, case és history együtt rollbackel.
**Döntés:** BD-004/011, F2; C1 G1, T-C1-03.
**MES:** item_instances/users; a valódi lezárási proof a kapcsolódó MES/repair tényekből.
**Nyitott:** WP1-F01; MySQL definer/grant terv és SQLite eltérés nem igazolt.

### 3.4. repair_orders

**Cél/gazda:** termékenként önálló javítási munkalap; repair execution aggregate.
**Osztály:** Source of Truth + MUTABLE execution projection.

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

**Immutable/lifecycle:** tagság/work number rögzül; díjszabás és consent első végrehajtástól
snapshot. A rate nem igazolt fee: annak külön táblája 3.24.
Fizetős start F4-gate alatt, új scope ismételt jóváhagyást igényel;
completed execution nem case-close. Attempt monoton, QC-hiba előzményét nem törli.
**Döntés:** BD-001/006/009/010/014, D6/D7, F4.
**MES:** documents; gyártási production_tasks újrafelhasználása nem megengedett.
**Nyitott:** WP1-F06; díjkerekítés és scope/hash pontos algoritmus, nem új díjpolitika.

### 3.5. repair_operations

**Cél/gazda:** javítási terv és végrehajtás egy attemptben; repair_order.
**Osztály:** Source of Truth + MUTABLE lifecycle.

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

**Immutable/lifecycle:** indítás után terv/utasítás/estimate/QC-követelmény nem írható át;
csak meg nem kezdett előzmény supersedálható. Ugyanazon order/attempt és monoton
sorrend guardolt; self-FK nem ciklusvédelem. Új fizetős scope F4.
**Döntés:** BD-006/009/010, F4.
**MES:** operation_types/factory_units/professional_roles; production task/sequence történet változatlan.
**Nyitott:** WP1-F04/F09; scope_quotation_id azonos orderre igazítása composite parentkulccsal vagy guarddal WP2-ben pontosítandó.

### 3.6. repair_operation_assignments

**Cél/gazda:** több dolgozó hozzárendelése; operation.
**Osztály:** Source of Truth, MUTABLE egyszeri lezárásig.

```text
repair_operation_id:F! -> repair_operations; employee_id:F! -> employees
assigned_by:F! -> users; assigned_at:DT!; ended_at:DT?
role_snapshot:S(100)!; command_id:F! -> repair_commands
U(repair_operation_id,employee_id); U(id,employee_id)
IX(employee_id,assigned_at)
CHECK ended_at IS NULL OR ended_at>=assigned_at
```

**Immutable/lifecycle:** operation/employee/role/assigner snapshot rögzül, ended_at egyszer állítható.
Employee aktív és szakmailag jogosult; FK nem permission. Új időszelet nem írja át a hozzárendelést.
**Döntés:** BD-003/010.
**MES:** employees/users, szakmai szerepkörök.
**Nyitott:** WP1-F09; ugyanazon dolgozó újbóli külön hozzárendelése az U(operation,employee) mellett
nem külön sor, hanem időszakaszok; ettől eltérő igény csak későbbi explicit döntéssel.

### 3.7. repair_time_entries

**Cél/gazda:** tényleges munkaidő; assignment.
**Osztály:** Source of Truth, MUTABLE → lezárt immutable tény; actual összeg Derived State.

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

**Immutable/lifecycle:** lezárt intervallum korrekciója új sor indokkal;
assignment mutex alatt overlap-guard, NULL végpont végtelen. U(active_assignment_id)
csak egy nyitott intervallumot véd, lezárt átfedést nem.
**Döntés:** BD-010/015.
**MES:** employees az assignmenten keresztül.
**Nyitott:** WP1-F09; globális employee-overlap szervezeti szabály nyitott, nem vezetjük be automatikusan.

### 3.8. repair_materials

**Cél/gazda:** tényleges javítási anyagfogyasztás; repair_order/operation.
**Osztály:** Immutable Event, EVENT.

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

**Immutable/lifecycle:** minden anyag/mennyiség/serial/movement mező rögzül;
korrekció új indokolt mozgás/tény. Item/batch/serial/unit/owner/hely egyezés és
eligible company remaining stock guardolt. Material+movement+balance+reservation partial counter
egy tranzakció; más owner foglalása nem fogyasztható.
**Döntés:** BD-010/017/018, D1, AM-02-G3.
**MES:** stock_movements/stock_reservations/items/item_batches/item_instances.
**Nyitott:** WP1-F02/F03; shared fogyasztók és részfogyasztás nem implementált.

### 3.9. repair_inspections

**Cél/gazda:** diagnosztikai, műveleti, végső és return-quarantine vizsgálati tény; order.
**Osztály:** Immutable Event, EVENT.

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

**C1-kiegészítés:** `subject_item_instance_id:F! -> item_instances`,
`return_event_id:F? -> repair_delivery_events`; IX(return_event_id,checked_at).
`return_quarantine` felvétel az inspection_type CHECK-be;
return_quarantine iff return_event_id nem-NULL. A return/subject/order egyezés guard és
tervezett composite kapcsolatok tárgya, pontos parentkulcs WP2/WP4-ben.
**Immutable/lifecycle:** hibás QC sem törölhető; új vizsgálat új command,
régi attempt eredménye nem minősíti az újat. Quarantine inspection nem önmagában feloldás.
**Döntés:** BD-009/010, D8/D9, AM-02-G1/G3.
**MES:** employees/users/documents/item_instances.
**Nyitott:** WP1-F04/F05; kötelező bizonyítékok típusfüggő nullability/retention.

### 3.10. product_warranty_rules

**Cél/gazda:** cikkszintű verziózott garanciaszabály; terméktörzs-jóváhagyó.
**Osztály:** Source of Truth, Immutable Event, EVENT.

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

**Immutable/lifecycle:** publikált verzió nem szerkeszthető; itemzár alatt verzió és effective_from.
Az alkalmazandó időpont utolsó hatályos szabálya; assessment snapshot megmarad.
Nincs beépített jogszabály vagy visszamenőleges automatikus újraminősítés.
**Döntés:** BD-005/012, D4.
**MES:** items/users.
**Nyitott:** WP1-F06; alkalmazandó jogi/szerződéses időszámítás konfigurációja, nem új garanciakezdet.

### 3.11. product_handover_evidences

**Cél/gazda:** serialhoz kötött bizonyított ügyfélátadás; warranty evidence owner.
**Osztály:** Source of Truth, Immutable Event, EVENT.

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

**Immutable/lifecycle:** konkrét dokumentumverzió+checksum megőrzendő;
korrekció új sor, nem serial-only UNIQUE. Rendelés dátuma nem átadási proof.
Release opcionális, ezért eredeti értékesítési átadás is reprezentálható.
**Döntés:** BD-012/015/019, D4/D5.
**MES:** item_instances/customers/documents/users.
**Nyitott:** WP1-F05; release/serial/recipient összetartozás és dokumentum-retention bizonyítás.

### 3.12. warranty_assessments

**Cél/gazda:** manuális, bizonyított minősítés a megnevezett subject serialra; repair_order.
**Osztály:** Source of Truth, Immutable Event, EVENT.

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

**Immutable/lifecycle:** globális order-version mutex alatt; aktuális minősítés a subjectre szűrt
legnagyobb verzió. Eredeti és csere serial külön történet, nincs automatikus reset.
Hiányos bizonyíték pending; override csak indokkal és jogosultsággal.
Parent `item_instances` U(id,item_id) szükséges. A composite FK-k nem igazolják az orderhez tartozást:
eredeti tagság vagy tartós replacement reservation guardja kötelező.
**Döntés:** BD-002/005/012/015, D2/D4.
**MES:** items/item_instances/documents/users.
**Nyitott:** WP1-F04/F06; alkalmazandó szabály kiválasztása és override permission.

### 3.13. repair_quotations

**Cél/gazda:** verziózott fizetős scope és ügyfélválasz; repair_order.
**Osztály:** Source of Truth, MUTABLE → submitted tartalom immutable.

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

**Immutable/lifecycle:** submit után lines/currency/scale/total/hash tartalom rögzül;
válasz külön bizonyítékkal. Startkor érvényes aktuális approval kell, nem elég lejárati cron.
Új draft felfüggeszti régi approval használatát új munkára; történetet nem töröl.
Credit **csak approvalkor** foglal, draft/submitted nem.
**Döntés:** BD-006/013/014, D6/D7, AM-03, F4.
**MES:** customers/users/documents.
**Nyitott:** WP1-F06; scope/hash/expiry atomikus startguard; cancelled és superseded üzleti tény megőrzése a historyban,
új enum nem állítható elfogadott szabályként.

### 3.14. repair_quotation_items

**Cél/gazda:** ajánlat tartalma, díj és credit sora; quotation.
**Osztály:** Source of Truth, MUTABLE → submit után immutable.

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

**Immutable/lifecycle:** parenttel együtt submitkor rögzül; pénz egész egység,
quantity exact decimal. Total=sum és credit<=igazolt fee cross-row guard;
azonos order/currency/scale kötelező. A negatív sor önmagában nem credit-allocation.
**Döntés:** BD-013/014, D6/D7, AM-03.
**MES:** items; igazolt díj a 3.24-ben.
**Nyitott:** WP1-F06; kerekítés, total/hash és zero-credit-line kezelése technikai pontosítás, nem új pénzügyi szabály.

### 3.15. repair_custodies

**Cél/gazda:** kizárólag az eredeti visszáru aktuális őrzése; member.
**Osztály:** Projection, MUTABLE, újraépíthető movementből.

```text
repair_request_item_id:F! -> repair_request_items
item_instance_id:F! -> item_instances; location_id:F! -> locations
last_stock_movement_id:F! -> stock_movements
U(repair_request_item_id); U(item_instance_id); U(last_stock_movement_id)
FK(repair_request_item_id,item_instance_id) -> repair_request_items(id,item_instance_id)
CHECK: nincs további soron belüli mennyiségfeltétel (egy sor egy egység)
```

**Immutable/lifecycle:** member/serial rögzül; hely/last movement csak valós mozgással
együtt változik. Release/scrap megszünteti az aktuális sort, történeti mozgás nem törölhető.
Replacement preparation/return ide nem kerülhet; rebuilding nem új receipt.
**Döntés:** BD-007/016/017/019, D1.
**MES:** item_instances/locations/stock_movements.
**Nyitott:** WP1-F02; company/custody/pending riportok diszjunktak legyenek.

### 3.16. repair_releases

**Cél/gazda:** egy megtörtént fizikai shipment identitása; member/order.
**Osztály:** Immutable Event, EVENT.

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

**Immutable/lifecycle:** a release maga az attempt; minden replacement attempt új reservationt kap.
U(stock_reservation_id) visszaállítva; nem a gyengébb U(reservation,attempt).
Source occurrence stabil új command esetén is; címkenyomtatás nem fizikai bizonyíték.
Previous azonos member/serial/role, attempt=előző+1 guard. Original csak attempt1.
Új shipmenthez D9: tényleges return, condition/rights/prior-shipment rendezés és authorization.
Delivery és ownership külön, nem újabb outbound; recipient proof nem automatikus tulajdonjog.
**Döntés:** BD-019/020, D5/D8/D9, AM-01/02.
**MES:** stock_reservations/stock_movements/customers/documents/item_instances.
**Nyitott:** WP1-F04; occurrence identity, reservation-root tuple és késői proof konfliktusok WP2/WP4.

### 3.17. repair_delivery_events

**Cél/gazda:** shipment delivery/failure/intake/admission bizonyítéklánc; release.
**Osztály:** Immutable Event, EVENT.

```text
repair_release_id:F! -> repair_releases
item_instance_id:F! -> item_instances; repair_request_item_id:F! -> repair_request_items
release_role:S(16)!; event_type:S(24)!; event_key:S(128)!; occurred_at:DT!
recipient_snapshot:J!; reference:S(128)!; evidence_document_id:F! -> documents
recorded_by:F! -> users; supersedes_event_id:F? -> repair_delivery_events
command_id:F! -> repair_commands
related_return_event_id:F? -> repair_delivery_events
return_stock_movement_id:F? -> stock_movements; return_location_id:F? -> locations
ownership_verification_document_id:F? -> documents
physical_return_namespace:S(64)?; physical_return_source_event_key:S(128)?
admission_return_key:F? -> repair_delivery_events
U(return_stock_movement_id); U(physical_return_namespace,physical_return_source_event_key)
U(admission_return_key)
U(repair_release_id,event_key); U(supersedes_event_id); U(command_id)
U(id,repair_release_id,item_instance_id)
IX(repair_release_id,occurred_at); IX(related_return_event_id,event_type,occurred_at)
FK(repair_release_id,repair_request_item_id,item_instance_id,release_role)
 -> repair_releases(id,repair_request_item_id,released_item_instance_id,release_role)
FK(related_return_event_id,repair_release_id,item_instance_id)
 -> repair_delivery_events(id,repair_release_id,item_instance_id)
CHECK release_role IN('original','replacement')
CHECK event_type IN('delivered','delivery_failed','recall_requested','physically_returned','return_reconciled','correction')
CHECK (event_type='physically_returned' AND physical_return_namespace IS NOT NULL
 AND physical_return_source_event_key IS NOT NULL AND admission_return_key IS NULL
 AND related_return_event_id IS NULL AND return_stock_movement_id IS NOT NULL
 AND return_location_id IS NOT NULL)
 OR (event_type='return_reconciled' AND physical_return_namespace IS NULL
 AND physical_return_source_event_key IS NULL
 AND related_return_event_id IS NOT NULL AND admission_return_key IS NOT NULL
 AND admission_return_key=related_return_event_id AND return_stock_movement_id IS NOT NULL
 AND return_location_id IS NOT NULL AND ownership_verification_document_id IS NOT NULL)
 OR (event_type NOT IN('physically_returned','return_reconciled')
 AND physical_return_namespace IS NULL AND physical_return_source_event_key IS NULL
 AND admission_return_key IS NULL
 AND related_return_event_id IS NULL AND return_stock_movement_id IS NULL
 AND return_location_id IS NULL AND ownership_verification_document_id IS NULL)
```

**Immutable/lifecycle:** return és admission külön identity; failure balance0.
Minden fizikai return intake+hely+quarantine tranzakció, ismeretlen ownership mellett is.
Admission ugyanazon return egyszeri besorolása, nem második fizikai receipt;
company balance csak verified jogcímmel +1, quarantine ettől még megmarad.
U(related_return_event_id) általános tiltását C1 felülírja: több inspection/decision child lehet;
csak egyszeri intake/admission purpose-key egyedi. Late delivered saját attempten marad;
ellentmondó proof blokkol, nincs latest-timestamp-wins.
**Döntés:** D8/D9, AM-01/02 és AM-02-G1–G4.
**MES:** stock_movements/locations/documents/users/item_instances.
**Nyitott:** WP1-F04; az alábbi singleton-key és verziózott döntési kapcsolat technikai terv, motoros DDL nem igazolt.

### 3.18. repair_ownership_events

**Cél/gazda:** bizonyított tulajdonjogi esemény, nem stock posting; member + serial.
**Osztály:** Source of Truth, Immutable Event, EVENT.

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

**Immutable/lifecycle:** reversal külön causal FK az originalra, correction külön supersedes.
Teljes reversal egyedi; korrekció a reversalre mutat, nem második reversal.
Previous effective jogi lánc és rögzítési sequence eltérhet az occurred_at sorrendtől.
Policy/proof/owner/right/jogosultság guardolt, fizikai returnből nem következik reversal.
**Döntés:** BD-020, D5/D10, AM-04.
**MES:** item_instances/customers/documents/users/stock_reservations.
**Nyitott:** WP1-F04/F06; részleges reversal jogi jelentése nincs kitalálva, szükség esetén külön döntés.

### 3.19. repair_dispositions

**Cél/gazda:** eredeti termék végső sorsa és teljes repair végkimenetel; member.
**Osztály:** Source of Truth, Immutable Event, EVENT.

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

**Immutable/lifecycle:** egy végső rendelkezés/member; pending nem final.
Az eredeti return/scrap/not_received proof és a replacement rendezése függetlenül kell.
Típus/outcome értelmes kombinációját, linked subject/QC/auth egyezést service vizsgálja.
Quarantine közbenső rendelkezése a typed history, nem ez az egyedi final sor.
**Döntés:** BD-008/011/019/020, D8/D9.
**MES:** stock_movements/stock_reservations/documents/users.
**Nyitott:** WP1-F01/F04; minden attempt és nyitott procurement responsibility rendezése a case-close proof része.

### 3.20. repair_status_histories

**Cél/gazda:** typed append-only állapot- és döntéstörténet; XOR case vagy order.
**Osztály:** Immutable Event, EVENT; nem általános inventory/procurement ledger.

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

**C1/WP1 typed kiegészítés:** `repair_request_item_id:F? -> repair_request_items`,
`item_instance_id:F? -> item_instances`, `inspection_id:F? -> repair_inspections`,
`return_event_id:F? -> repair_delivery_events`, `authorization_document_id:F? -> documents`,
`diagnostic_credit_allocation_id:F? -> repair_diagnostic_credit_allocations`,
`related_allocation_history_id:F? -> repair_status_histories`,
`execution_review_document_id:F? -> documents`, `settlement_review_document_id:F? -> documents`,
`settlement_observation:S(16)?`, `reviewed_by:F? -> users`, `reviewed_at:DT?`.
IX(diagnostic_credit_allocation_id,occurred_at); IX(return_event_id,occurred_at);
FK(member,serial)→repair_request_items(id,item_instance_id), a pár együtt NULL vagy nem-NULL.
Dimenzió CHECK kibővül claim/credit/quarantine értékekkel;
settlement_observation NULL vagy unknown/unsettled/settled. Review esetén actor/time
és execution/settlement proof kötelező; claim close esetén member/serial/closing command;
quarantine decision esetén subject/return/inspection/authorization kötelező.
Ezek pontos type→nullability/parenttuple DDL-je WP2/WP4/WP5 kapu, nem JSON proof_ok.
**Immutable/lifecycle:** minden review új sor; korábbi observation nem írható át.
Fee-credit projekcióváltás és history atomikus, settled/unknown nem automatikus release.
**Döntés:** BD-004/011/015, D7/D8/D10, AM-02/03/04, F2.
**MES:** users/documents; activity_log kiegészíti, nem helyettesíti.
**Nyitott:** WP1-F01/F04/F05/F06; teljes typed proofhalmaz és ciklikus FK-k contract-sorrendje.

### 3.21. repair_commands

**Cél/gazda:** tartós command identity, payload és eredmény; RMA íráskoordinátor.
**Osztály:** Source of Truth, MUTABLE processing→completed, majd immutable.

```text
command_type:S(64)!; idempotency_key:S(128)!; payload_hash:S(64)!
actor_id:F! -> users; status:S(16)!='processing'
result_type:S(64)?; result_id:U64?; result_snapshot:J?; completed_at:DT?
U(command_type,idempotency_key); IX(actor_id,created_at)
CHECK status IN('processing','completed')
CHECK (status='processing' AND completed_at IS NULL)
 OR (status='completed' AND completed_at IS NOT NULL AND result_snapshot IS NOT NULL)
```

**Immutable/lifecycle:** actor/hash/kulcs nem cserélhető; failed command teljes rollback.
Processing nem commitolható sikeres commandként. Retry csak azonos hash/actor/auth mellett
ad korábbi eredményt; új command nem új fizikai occurrence.
Result morph válasz, nem helyettesít domain-FK-t. Legacy procurement nem kényszerül fiktív repair command alá.
**Döntés:** BD-004/016/019/020, D6/D9/D11; C1 G1.
**MES:** users/audit; külső source key külön névtér.
**Nyitott:** WP1-F08; binary összehasonlítás, command/rutin atomikus protokoll WP2.

### 3.22. item_instance_genealogy_events

**Cél/gazda:** beépülés/kiszerelés/korrekció serialos története; repair_order.
**Osztály:** Immutable Event, EVENT; aktuális beépülés Derived State.

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

**Immutable/lifecycle:** install egyszer remove; parent+child mutex alatt ciklus és párhuzamos
effective install kizárása. Legacy_install csak bizonyítékkal, régi relations megőrzésével.
Kiszerelés nem automatikus szabad stock/tulajdonváltás; valós mozgás külön.
**Döntés:** BD-002/010/015/018, D2.
**MES:** item_instances/item_instance_relations/stock_movements/documents/users.
**Nyitott:** WP1-F05/F07; legacy relation cascade és event-védett hard delete, pontos correction/effective gráf.

### 3.23. repair_diagnostic_credit_allocations

**Cél/gazda:** egy igazolt diagnosztikai forrás effektív felhasználási igénye; source order.
**Osztály:** Source of Truth + MUTABLE projekció, megőrzött immutable historyval.

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

**Immutable/lifecycle:** fee/quote/line/order/amount/currency/scale rögzül.
Approval-only reserved; submit-held/applied protokoll SUPERSEDED.
U(active_source_order_id) fee-verziókon át is egy effektív kreditet véd.
Cancel/supersede nem release; execution+settlement review szükséges.
Authorized unused+unsettled release törli az active keyt, nem a történetet.
Unknown/executed/settled ág blokkolt/retained a külön igazolt rendelkezésig;
új fee-verzió nem nyit második kreditet. Released nem aktiválható újra.
**Döntés:** BD-014, D6/D7, AM-03.
**MES:** documents/users, külső settlement proof csak megfigyelés, nem accounting alrendszer.
**Nyitott:** WP1-F06; amount-cap és effective-use guard, immutable review és settled korrekció határa WP5.

### 3.24. repair_diagnostic_fees

**Cél/gazda:** ajánlattól független, ténylegesen igazolt diagnosztikai díj verziója; order.
**Osztály:** Source of Truth, Immutable Event, EVENT.

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

**Immutable/lifecycle:** minden mező rögzül; correction új verzió+bizonyíték,
orderzár alatt aktuális fee-választás. Egy meglévő diagnosztikai díj verziózott proofja,
nem több számlázható díjkategória vagy számla. Self-FK nem monoton/ciklusvédelem.
**Döntés:** BD-014, D6/D7, AM-03.
**MES:** documents/users.
**Nyitott:** WP1-F06; fee-correction és korábbi allocation eltérésének rendezése, nincs automatikus átírás/újrafelhasználás.

### 3.25. procurement_coverage_events

**Cél/gazda:** demand/coverage/commitment/actual receipt változatlan mennyiségi története;
procurement domain, legacy és repair igény számára egyaránt.
**Osztály:** Source of Truth, Immutable Event, EVENT; aktuális coverage Derived State.

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

**WP1 típusjavaslat:** a quantity nemnegatív nagyság, előjele a típus/transfer_side által adott;
demand snapshot before/after hordozza a revíziós mennyiséget/ownert/unitot. Demand esemény
nem önkényes stock-delta; a teljes type→NULL/quantity mátrix WP5/WP2 kapu (WP1-F07),
különösen correction, supplier cancellation, legacy baseline és kézi procurement esetén.
Nem állítjuk, hogy a fenti minimum CHECK-ek már minden tiltott alakot kizárnak.
**Immutable/lifecycle:** source+első allocated egy tranzakció, auditált legacy baseline egyszer.
Transfer két sora azonos key/from+to; nincs körkörös paired FK vagy utólagos összelinkelés.
Source/commitment mutex védi a mennyiségi conservationt és actual GR konverziót.
Owner/revision/key/snapshot nem írható át, correction új esemény.
**Döntés:** BD-018, D11, AM-04.
**MES:** material_requirements, PR sources, PO items, GR items, documents/users;
optional repair_command, nincs fiktív RMA-owner legacy eseményhez.
**Nyitott:** WP1-F07; teljes link/type mapping, receipt owner-szétosztás és generation line-split.

## 4. Meglévő MES-séma: bizonyított tény és javasolt hatás

### 4.1. Bizonyíték és osztályozás

A migrációk `up()` definícióit és a kapcsolódó modellek fillable/cast/relationship/SoftDeletes
részeit vizsgáltuk. Modell-enum nem DB CHECK; Eloquent policy nem raw SQL-védelem.
Jelölés: **S** közvetlen schema change required (7); **R** relationship/deletion/integrity review
vagy consumer adaptation (11). R nem automatikus új oszlop/migráció.
A pontos reverse relation metódusnevek WP2 implementációs részletek; a felelősség itt rögzített.

| Meglevo tabla / tipus / bizonyitek                                                                                                                                                                                                                                                                                                       | Ellenorzott schema es modellteny                                                                                                                                    | Javasolt schema / kapcsolat / enforcement hatas                                                                                                                                          |
| ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `item_instances` **S** [M1](../../database/migrations/2026_06_21_000010_create_item_instances_table.php), [model](../../app/Models/ItemInstance.php)                                                                                                                                                                                     | production_order_id nullable unsigned BIGINT + IX, FK nelkul; serial UNIQUE, current_location nullable SET NULL; modellben nincs productionOrder kapcsolat.         | Scope/movement/ownership/disposition projekciok, U(id,item_id), origin FK + scope/allocation IX a 4.2 szerint; productionOrder es latest-event kapcsolatok; serial/proof retention.      |
| `item_instance_relations` **R** [M1](../../database/migrations/2026_06_21_000011_create_item_instance_relations_table.php), [model](../../app/Models/ItemInstanceRelation.php)                                                                                                                                                           | parent FK CASCADE, child RESTRICT; U(parent_item_instance_id,child_item_instance_id), quantity Q; nincs remove-history.                                             | Uj oszlop nem igazoltan szukseges; genealogy event kulon SoT, parent/child deletion guard es kapcsolatok; regi relation nem torolheto korrekciokent.                                     |
| `stock_movements` **S** [M1](../../database/migrations/2026_06_21_000025_create_stock_movements_table.php), [model](../../app/Models/StockMovement.php)                                                                                                                                                                                  | Q quantity; nullable serial/batch/from/to/performed_by SET NULL; movement_type string/index; nullable source morph nem FK.                                          | Typed repair/receipt/reservation/return/release/command es reverse FK/U/IX, scope CHECK; append-only posting es hivatkozott tortenet nullazas/tarolt hely torlese elleni vedelem.        |
| `stock_balances` **S** [M1](../../database/migrations/2026_06_21_000023_create_stock_balances_table.php), [model](../../app/Models/StockBalance.php)                                                                                                                                                                                     | U(item_id,location_id,item_batch_id) nullable batch mellett; Q quantity default0; batch SET NULL.                                                                   | batch_identity CHECK + nem-null U, company-only projection; batch RESTRICT; repository sentinel/eligible contract. Nincs custody mennyisegi oszlop.                                      |
| `stock_reservations` **S** [M1](../../database/migrations/2026_06_21_000024_create_stock_reservations_table.php), [model](../../app/Models/StockReservation.php)                                                                                                                                                                         | customer_order_item/production_order nullable CASCADE; location/batch SET NULL; reserved_quantity Q, active status; SoftDeletes, nincs partial counter vagy serial. | Repair owner/purpose/scope/serial/active U/root/previous/counter; FK deletion guard, active soft-delete tiltasa; uj repair/instance/lanc kapcsolatok.                                    |
| `material_requirements` **S** [M1](../../database/migrations/2026_06_21_000022_create_material_requirements_table.php), [model](../../app/Models/MaterialRequirement.php)                                                                                                                                                                | customer_order_item kotelezo CASCADE; required_item RESTRICT; Q required/available/reserved/missing; SoftDeletes, nincs owner U.                                    | Repair XOR owner, ket owner-item U, revision/generation/current-event FK/IX; owner RESTRICT; demand withdrawal esemennyel, nem soft-delete-tel.                                          |
| `purchase_requisitions` **R** [M1](../../database/migrations/2026_06_21_000026_create_purchase_requisitions_table.php), [model](../../app/Models/PurchaseRequisition.php)                                                                                                                                                                | U(requisition_number), status draft index, requested_by nullable SET NULL, requested_at nullable; SoftDeletes.                                                      | Uj FK/column/U/IX nem igazoltan szukseges; items/sources/coverage es authorization review. Delete/approve/cancel megorzi commitment/proof tortenetet.                                    |
| `purchase_requisition_items` **S** [M1](../../database/migrations/2026_06_21_000027_create_purchase_requisition_items_table.php), [model](../../app/Models/PurchaseRequisitionItem.php)                                                                                                                                                  | header CASCADE, direct requirement nullable SET NULL, item RESTRICT; Q quantity/unit/status, SoftDeletes; sources es pivot kapcsolatok.                             | Direct requirement RESTRICT; sources az osszes igeny; source/coverage reverse kapcsolatok. Nincs uj oszlop/U; header cascade es generic delete retention review.                         |
| `purchase_requisition_item_sources` **S** [M1](../../database/migrations/2026_06_24_000002_create_purchase_requisition_item_sources_table.php), [model](../../app/Models/PurchaseRequisitionItemSource.php)                                                                                                                              | PRitem es requirement CASCADE; Q quantity, U(PRitem,requirement); modellben ket belongsTo es qty cast.                                                              | Generation/revision/key U/IX/CHECK; mindket FK RESTRICT; immutable source anchor, coverage events kapcsolat; source quantity nem aktualis coverage.                                      |
| `purchase_orders` **R** [M1](../../database/migrations/2026_06_21_000028_create_purchase_orders_table.php), [model](../../app/Models/PurchaseOrder.php)                                                                                                                                                                                  | supplier RESTRICT, PR/creator nullable SET NULL; U(order_number), status draft, dates; SoftDeletes.                                                                 | Uj oszlop/U/IX nem automatikus: sent/ack bizonyitek coverage event; generic delete/close/cancel guard, PR lineage megtartas; received status nem GR.                                     |
| `purchase_order_items` **R** [M1](../../database/migrations/2026_06_21_000029_create_purchase_order_items_table.php), [model](../../app/Models/PurchaseOrderItem.php)                                                                                                                                                                    | header CASCADE, PRitem nullable SET NULL, item RESTRICT; Q ordered/received quantity, unit/status; SoftDeletes.                                                     | Coverage event FK cel; item/source/commitment kapcsolat es mennyisegi guard; header/PR deletion lineage review, nincs feltetelezett uj received ledger oszlop.                           |
| `goods_receipts` **R** [M1](../../database/migrations/2026_06_21_000030_create_goods_receipts_table.php), [M2](../../database/migrations/2026_06_24_000001_add_status_to_goods_receipts_table.php), [model](../../app/Models/GoodsReceipt.php)                                                                                           | U(receipt_number), PO/receiver nullable SET NULL, received_at kotelezo; status kesobbi migracioban draft; SoftDeletes.                                              | Posted actual receipt es stock/coverage kozos transaction, PO deletion/proof guard. Uj oszlop/FK/U/IX nem bizonyitottan kell, meglevo items kapcsolat marad.                             |
| `goods_receipt_items` **R** [M1](../../database/migrations/2026_06_21_000031_create_goods_receipt_items_table.php), [model](../../app/Models/GoodsReceiptItem.php)                                                                                                                                                                       | header CASCADE, POitem/batch nullable SET NULL, item/location RESTRICT; Q quantity, nincs SoftDeletes.                                                              | Supply_received FK cel; posting/coverage/owner kapcsolatok, actual receipt idempotencia guard. Header/POitem/batch deletion audit; partial supply elosztas nem uj stock ledger.          |
| `production_tasks` **R** [M1](../../database/migrations/2026_06_21_000032_create_production_tasks_table.php), [model](../../app/Models/ProductionTask.php)                                                                                                                                                                               | production_order es item_instance CASCADE, step/employee RESTRICT; U(instance,step), planned status, nullable start/finish.                                         | Nincs repair_order FK es nem lesz repair task reuse. Eredeti gyartasi eredet/QC/serial megorzese, deletion guard es kozos inventory consumer contract.                                   |
| `production_task_materials` **R** [M1](../../database/migrations/2026_06_21_000033_create_production_task_materials_table.php), [model](../../app/Models/ProductionTaskMaterial.php)                                                                                                                                                     | task CASCADE, item RESTRICT, batch nullable SET NULL; Q planned/used quantity, unit; nincs tarolt reservation_id/movement_id/serial.                                | Consumer adaptation kotelezo owner/partial/eligible guarddal; uj trace FK-oszlop igenye WP3-ban indokolando, WP1 nem talal ki plusz production schema-bovitest. History deletion review. |
| `production_orders` **R** [M1](../../database/migrations/2026_06_21_000021_create_production_orders_table.php), [model](../../app/Models/ProductionOrder.php)                                                                                                                                                                            | U(order_number), customer/plan/item/BOM/sequence RESTRICT, creator nullable SET NULL; SoftDeletes, Q quantity/status/dates.                                         | Origin FK cel + model origin/repair-members reverse relation; gyartasi serial/sequence nem ujrageneralando. Soft-delete/origin bizonyitek retention, tobb-PO demand aggregacio review.   |
| `documents` **R** [M1](../../database/migrations/2026_06_22_000001_create_documents_table.php), [M2](../../database/migrations/2026_06_25_000001_add_storage_metadata_to_documents_table.php), [M3](../../database/migrations/2026_06_28_000001_add_ai_processing_fields_to_documents_table.php), [model](../../app/Models/Document.php) | Morph target nem FK; version group/index, nincs group-version U; uploader/approver SET NULL; SoftDeletes + storage/checksum/version/AI mezok.                       | Verziozott proof FK cel; file/checksum megorzes, soft-delete/target permission/retention guard. Uj target registry/policy kapcsolat kellhet, schema U csak audit utan indokolhato.       |
| `locations` **R** [M1](../../database/migrations/2026_06_21_000002_create_locations_table.php), [model](../../app/Models/Location.php)                                                                                                                                                                                                   | code UNIQUE, factory nullable SET NULL, type string enum cast, is_active; SoftDeletes; nincs globalis quarantine-availability flag.                                 | Valos quarantine hely FK cel; location magaban nem eleg isolation. Uj flag/oszlop nem onkenyes default; state/consumer filtering, hivatkozott hely retention guard.                      |

A fenti 18-on kívüli kapcsolatok osztályozása (nem újabb kötelező schema change):

| Meglévő cél                                          | Osztály / követelmény                                                                                                                                                                               |
| ---------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| items / item_batches                                 | **FK target only + consumer adaptation**: item/type/unit és batch-cikk egyezés; serial eligibility nem item-type alapú. Batch soft-delete/retention a hivatkozott történetet nem érvénytelenítheti. |
| customer_order_items                                 | **FK target only + consumer adaptation**: legacy owner és több-production-order aggregáció megőrzése; repair XOR mellett nullable legacy ág nem belső joinnal eldobható.                            |
| customers / users / employees                        | **FK target only + policy adaptation**: owner, reporter, actor és dolgozó eltérő szerep; aktív dolgozó és permission külön guard. Új tábla FK-ja önmagában nem kíván parent oldali oszlopot.        |
| operation_types / factory_units / professional_roles | **FK target only**: javítás saját operation/assignment történet, ugyanazok a masterek; új repair FK parent oszlop nélkül.                                                                           |
| operation_sequences / operation_sequence_steps       | **Unchanged schema**: eredeti gyártási sequence és step nem javítási terv; repair snapshot/supersedes nem törölheti az eredeti gyártási történetet.                                                 |
| activity_log                                         | **Consumer adaptation only**: domainírással közös audit; morph nem typed domain-FK vagy coverage ledger. Új mező nem indokolt.                                                                      |
| roles / permissions és permission pivotok            | **Policy adaptation only**: részletes repair/ownership/close/credit/procurement jogok később specifikálandók; nincs most önkényes szerepkör vagy jogosultsági schema.                               |

A célok migrációs/modelldefinícióit az A 2.1/2.5 migrációs bizonyítékfüggeléke is
rögzíti; új kötelező oszlopot e leltár ezekre nem vezet be. A schema-change és
consumer-only kategóriák száma ezért nem keverendő az összes hivatkozott master darabszámával.

### 4.2. A hét közvetlen séma-bővítés oszlopszerződése

Az alábbiak új vagy módosuló mezők; a többi meglévő oszlop marad.
Minden új F oszlop FK/IX és NO ACTION a 2. fejezet szerint; nincs előre végrehajtott backfill.

```text
item_instances:
 current_inventory_scope:S(24)?; last_stock_movement_id:F? -> stock_movements
 allocation_state:S(24)?; ownership_verification_state:S(16)?
 latest_ownership_event_id:F? -> repair_ownership_events
 last_disposition_history_id:F? -> repair_status_histories
 U(id,item_id); IX(current_inventory_scope,current_location_id)
 IX(allocation_state,current_inventory_scope,current_location_id)
 scope CHECK NULL OR IN(company_stock,customer_custody,external)
 allocation_state CHECK NULL OR IN(available,reserved,in_transit,quarantined,blocked,consumed,scrapped)
 verification CHECK NULL OR IN(verified,unresolved)
 production_order_id meglévő nullable oszlophoz FK -> production_orders(id), orphan audit után

stock_movements:
 from_scope:S(24)?; to_scope:S(24)? -- expand NULL, contract NOT NULL
 repair_command_id:F? -> repair_commands; command_line:U32?
 repair_request_item_id:F? -> repair_request_items
 receipt_request_item_id:F? -> repair_request_items
 stock_reservation_id:F? -> stock_reservations
 reverses_stock_movement_id:F? -> stock_movements
 source_repair_release_id:F? -> repair_releases
 return_event_id:F? -> repair_delivery_events; return_purpose:S(24)?
 U(repair_command_id,command_line); U(receipt_request_item_id)
 U(id,item_instance_id); U(reverses_stock_movement_id); U(return_event_id,return_purpose)
 IX(from_scope,item_id,performed_at); IX(to_scope,item_id,performed_at)
 IX(repair_request_item_id,performed_at)
 FK(receipt_request_item_id,item_instance_id) -> repair_request_items(id,item_instance_id)

stock_reservations:
 repair_order_id:F? -> repair_orders; reservation_purpose:S(32)?
 reservation_inventory_scope:S(24)?; item_instance_id:F? -> item_instances
 active_item_instance_id:F? -> item_instances
 active_replacement_order_id:F? -> repair_orders
 consumed_quantity:Q!=0; released_quantity:Q!=0; current_replacement_stage:S(32)?
 replacement_root_reservation_id:F? -> stock_reservations
 previous_reservation_id:F? -> stock_reservations
 U(active_item_instance_id); U(active_replacement_order_id); U(previous_reservation_id)
 U(id,item_instance_id); U(id,repair_order_id); U(id,repair_order_id,item_instance_id)
 FK(previous_reservation_id,repair_order_id,item_instance_id)
  -> stock_reservations(id,repair_order_id,item_instance_id)
 FK(replacement_root_reservation_id,repair_order_id,item_instance_id)
  -> stock_reservations(id,repair_order_id,item_instance_id)
 IX(repair_order_id,reservation_purpose,status); IX(item_instance_id,reserved_at)

stock_balances:
 batch_identity:U64! -- expand/backfill/contract, nincs önkényes qty-default
 CHECK (item_batch_id IS NULL AND batch_identity=0)
  OR (item_batch_id IS NOT NULL AND batch_identity=item_batch_id AND batch_identity>0)
 U(item_id,location_id,batch_identity) felváltja a nullable batch U-t
 quantity>=0 csak igazolt legacy audit/contract után

material_requirements:
 customer_order_item_id meglévő F nullable lesz; repair_order_id:F? -> repair_orders
 demand_revision:U32!=1; demand_state:S(16)!='active'; procurement_generation:U32!=0
 current_revision_event_id:F? -> procurement_coverage_events
 CHECK pontosan egyik owner nem-NULL; demand_state IN(active,superseded,withdrawn)
 CHECK demand_revision>=1; required/available/reserved/missing_quantity>=0
 U(customer_order_item_id,required_item_id); U(repair_order_id,required_item_id)
 IX(repair_order_id,status); IX(demand_state,required_item_id)

purchase_requisition_item_sources:
 demand_revision:U32!=1; procurement_generation:U32!; generation_key:S(128)!
 U(generation_key); U(material_requirement_id,procurement_generation)
 meglévő U(purchase_requisition_item_id,material_requirement_id) megmarad
 CHECK quantity>0 AND demand_revision>=1 AND procurement_generation>=1

purchase_requisition_items:
 új oszlop nincs; material_requirement_id FK SET NULL helyett effective RESTRICT
 sources az autoritatív teljes ownerlista; direkt pointer csak kompatibilitási reprezentáns
```

A S(32) reservation purpose tudja a C1 `replacement_redispatch_custody` értékét;
az A S(24) itt túl rövid lenne. Ez típuskorrekció, nem új üzleti purpose.
Scope/owner/allocation projection új oszlopai legacy alatt NULL/blocked/unresolved
állapotból csak bizonyított mappinggel léphetnek tovább; NULL nem company_stock-default.
Latest ownership pointer hiánya nem törli az előző bizonyított ownert.

### 4.3. Soron belüli és cross-row contract a közös táblákon

| Tábla                 | Kötelező CHECK/alak és külön guard                                                                                                                                                                                                                                                                                                                                                                                                                                                          | Immutability, deletion, tranzakció                                                                                                                                                                                                                                                                 |
| --------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| stock_movements       | Scope-ok a három D1 érték; external→external kizárólag explicit replacement_return_intake + pending-admission/quarantine + return/release/serial/proof kontextussal. Más típusra nem általános kivétel. Command-pár együtt NULL vagy nem-NULL, line>=1. Original receipt quantity=1, external→customer_custody, member/serial/receipt-key egyezés; más típus receipt-key NULL. Intake/admission purpose iff return_event_id nem-NULL; típusfüggő hely/quantity/subject CHECK + parentguard. | Posting után üzleti mezők immutable; admission reversal-pointer csak a source dispatchre, intake nem foglalja el. Minden movement+projekció+proof atomikus. Nullable serial/batch/location FK nullázás RMA-tényen tiltandó. Teljes type allowlist WP2/WP4.                                         |
| stock_reservations    | Repair owner XOR legacy customer/production ág; legacy két hivatkozás együtt csak összetartozó igény. reserved>0, consumed/released>=0, sum<=reserved. Aktív serial-key iff mennyiségi aktív, egyenlő serial. Replacement serial/hely/order, qty1; scope company vagy igazolt custody purpose szerint. Root/previous mindkettő NULL vagy mindkettő nem-NULL; utód azonos order/serial/root, U(previous) nincs ág.                                                                           | Owner/serial/root/eredeti qty rögzül. Counter csak nő; dispatch fulfilled quantity nem resetelhető. Logical order-key nyitott obligation alatt marad, új reservationre átadás atomikus. Active soft/hard delete tiltott, owner FK-k cascade helyett RESTRICT-javaslat. State/type mapping WP2/WP4. |
| stock_balances        | Nem-null sentinel identity; exact Q, nem float; egy company item/location/batch sor. Nincs custody/owner ledgerdimenzió. Quarantine company physical létezhet, de nem eligible available.                                                                                                                                                                                                                                                                                                   | Csak movement projection írhatja; batch SET NULL helyett RESTRICT. Item mutex+balance lock az insertverseny ellen. Duplicate cleanup csak movement-egyeztetésből.                                                                                                                                  |
| item_instances        | U(serial) megmarad, serial nem regenerálható. Scope/physical location/ownership/disposition négy külön információ; unresolved/quarantine nem foglalható. Latest-event pointernek azonos serialra kell mutatnia (guard/tervezett composite FK).                                                                                                                                                                                                                                              | Eredet/serial immutable. Movement/frissített projection atomikus. Location SET NULL és production-order törlés RMA-históriát nem ronthat; pontos deletion rollout auditfüggő.                                                                                                                      |
| material_requirements | Owner-XOR és két nullable U valóban egy nem-NULL ownerágat hagy. Unit/cikk owner nem írható más igényre. Revision csak üzleti változás, generation csak authorized valódi delta, retry egyik sem.                                                                                                                                                                                                                                                                                           | Current qty/revision projection + immutable coverage event atomikus; FK ciklus miatt nullable current pointer csak expand/létrehozási szakaszban. Soft delete nem ledger-visszavonás.                                                                                                              |
| PR sources/items      | Source quantity eredeti immutable snapshot, nem aktuális coverage. Generation key és PR-line/owner U; item/unit/összeg egyezés guard. Source existence nem elegendő netting/idempotencia.                                                                                                                                                                                                                                                                                                   | Source create+allocated event atomikus; release/reassign/receipt esemény nem törli az anchort. PR line módosítás/deletion csak D11 review-val; régi közvetlen pointer nem teljes ownership.                                                                                                        |

Kötelező tágabb törlésvizsgálat: az R táblák meglévő CASCADE/SET NULL és soft-delete
útjai nem kerülhetik meg az új FK-t és retentiont. Nem rendeljük el bizonyíték nélkül
minden régi FK globális átírását; védett, hivatkozott soron a törlés elutasítása kell,
a konkrét affected-constraint lista és legacy cleanup WP2/WP6 eredménytermék.

## 5. Kritikus invariánsok, enforcement és verifikáció

Jelölés: **DB** database constraint; **TR** trigger; **RP** restricted database privilege;
**TS** transactional service; **AP** application policy; **RF** read-model filtering.
Az alábbi V01–V15 verifikációk **TERVEZETT / NEM FUTTATOTT** próbák.
WP1 dokumentációs ellenőrzése nem helyettesíti őket.

| Invariáns / döntés                                        | Gazdatábla és enforcement                                                                                  | Tranzakció/mutex és konkurenciakockázat                                                                                | Kötelező bizonyítás                                                                                                                                                                                                             |
| --------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| V01 Serial identity / BD-002/004, D2                      | item_instances U(serial), members composite, claims serial PK (**DB**); eredet/own-manufacture (**TS/AP**) | Instance+case lock, membership trigger (**TR**), tag/claim/order együtt. Két case ugyanarra a serialra.                | Duplicate serial/idegen member/más owner/closed parent raw SQL; két konkurens open közül egy; rollbackkor egyik félkész tag se maradjon.                                                                                        |
| V02 Aktív claim és bizonyítékalapú close / BD-004/011, F2 | requests/items/claims/history/commands; **DB/TR/RP/TS/AP**                                                 | Case→member/claim/proof zár; finalizer kizárólagos DELETE, no internal COMMIT. Open vs close és child insert vs close. | Nem-login definer, örökölt grant nélküli runtime; közvetlen DML/TRUNCATE/DDL/close-field tiltás; hiányos disposition/attempt/credit/coverage proof elutasítás; audit/proof hiba teljes rollback. SQLite security eltérés külön. |
| V03 Return eligibility / BD-002/003, D2/D3                | members origin FK/evidence, requests owner; **DB/TS/AP**                                                   | Instance+origin+case zár nyitáskor; eredet bizonyítás és owner snapshot. Soft-deleted/hiányos origin verseny.          | Semi-finished saját serial+igazolt gyártás jogosult lehet; purchased/idegen/hiányos eredet nem automatikusan jogosult; reporter eltérhet, ownerkeverés tilos.                                                                   |
| V04 Warranty history / BD-005/012, D4                     | rules/handover/assessments typed composite **DB**, immutability **RP/TS**, permission **AP**               | Item rule-publish és order assessment-version mutex. Két új assessment, külön subject serial.                          | Más cikk/rule/evidence composite hiba, missing proof pending, override indok/jog; original+replacement történet megmarad, nincs automatikus reset.                                                                              |
| V05 Physical custody / BD-007/016/019, D1                 | movements/members/custodies/instances; receipt U/composite **DB**, **TS/RF**                               | Serial/hely movement+custody+projection+receipt pointer együtt; dupla receipt/transfer/rebuild race.                   | Customer receipt company balance0; duplicate receipt új commanddal is hiba; custody release egyszer; projection rebuild nem új posting.                                                                                         |
| V06 Ownership / D5/D10, AM-04                             | ownership_events original/previous/supersedes/occurrence **DB**, **RP/TS/AP**                              | Member+serial+effective chain zár; késői proof és reversal race.                                                       | Release időpont nem automatikus jogátadás; original FK kötelező reversalnél, idegen serial és dupla effective reversal elutasítás; correction külön, nincs stockhatás.                                                          |
| V07 Quarantine isolation / D8, AM-02-G3                   | instances/allocation, movements, inspections/history, balances/reservations; **DB/TS/AP/RF**               | Serial+hely/balance/reservation+döntési history atomikus; release vs consume/reserve.                                  | Minden production/sales/repair/unrelated reserve és összes aggregált reader/export/cache kizár; admission utáni quarantined company qty sem available; nem dupla levonás.                                                       |
| V08 Replacement reservation / BD-020, AM-01               | reservations active serial/order U + root/prev composite **DB**, **TS/AP**                                 | Item/serial/balance/order/current chain-head mutex; partial consume és új attempt/order-key átadás.                    | Globális serial és nyitott obligation egyszeri; dispatch után quantity fulfilled, obligation marad; régi counter nem resetelhető; custody workflow nem company reserved SUM.                                                    |
| V09 Shipment identity / BD-019/020, D9                    | releases occurrence/move/command/reservation/attempt U **DB**, **TS/AP**                                   | Order+serial+reservation+previous release zár; új command ugyanarra a shipmentre, konkurens attempt.                   | Egy valós occurrence egyszer; új valódi shipment új reservation/ID, prior return és authorization kötelező; original attempt>1 tiltott.                                                                                         |
| V10 Return intake / D8, AM-02-G1/G2/G4                    | delivery_events+movement singleton keys+source release/serial **DB**, **TS/AP**                            | Return proof+external→external movement+hely/quarantine együtt; webhook/UI párhuzamos intake.                          | Verified serial/location/time/source/proof; unresolved owner mellett is actual intake; balance0; explicit szűk kivétel, generic transfer/scrap ne mehessen át.                                                                  |
| V11 Stock admission / AM-02-G2/G4                         | return event/admission-purpose/movement reverse U **DB**, **TS/AP/RF**                                     | Ugyanazon return/admission lock + company balance, instance és proof atomikus. Intake vs admission/retry race.         | Intake és admission külön kulcs; company jogcím nélkül +1 tilos; egyszeri +1; nincs második physical receipt, nincs automatikus quarantine release.                                                                             |
| V12 Diagnostic credit / D6/D7, AM-03                      | fees/allocation/quote/line composite és active-order U **DB**, history **RP**, **TS/AP**                   | Order→fee→quote/allocation+review mutex; két approval vagy approve vs cancel/release.                                  | Draft/submit nem foglal; amount<=fee/currency/scale/order; új fee nem dupla credit; unknown/settled/executed nem automatikus unused release; proof/audit hiba teljes rollback.                                                  |
| V13 Procurement coverage / D11, AM-04                     | requirements/sources/coverage_events/POitems/GRitems; generation/side U **DB**, **TS/AP/RF**               | Item/unit+requirement/source/commitment root+GR posting zár; generate/reassign/partial receive race.                   | Delta-only netting, from+to conservation atomikus, retry nem generation; sent/ack PO nem silent cancel; actual GR egyszer physical supply, nem open coverage is.                                                                |
| V14 Immutable audit/history / BD-015, F1–F4               | minden EVENT + documents/activity_log; **RP/TR/TS/AP**                                                     | Domain+history+audit+command egy commit; correction külön; fájlverzió retention és afterCommit cache.                  | UPDATE/DELETE/soft-delete/cascade/bulk bypass negatív próbák; audit failure rollback; supersedes graph helyes; nem elegendő egy FK vagy JSON proof_ok.                                                                          |
| V15 Production consumption / BD-017/018                   | materials/movements/balances/reservations/production_task_materials; **DB/TS/RF**                          | Item+balance+reservation+serial közös mutex mindkét domainben; más owner reserve vs consume.                           | Remaining reserved-consumed-released pontos; részfogyasztás nem teljes consume; karantén/custody/transit tiltott; rollback után qty és audit egyezik.                                                                           |

## 6. Inventory, MRP és authoritative források

### 6.1. Mennyiségi jelentés

A `stock_balances` kizárólag **company physical** projekció. Original customer custody
és replacement pending-admission balance-hatása 0. A company-admitted, de quarantined
mennyiség physical stock lehet, **eligible stock nem**. A physical on-site riport három
egymást kizáró halmaz: company physical + original/verified custody + pending admission;
a replacement custody nem kerül a csak-original repair_custodies táblába.
External könyvelési scope AM-02 pending kivételben fizikailag belső helyet is jelenthet.
Tulajdon és scope külön dimenzió.

**Available = eligible company physical − az arra vonatkozó aktív fennmaradó mennyiségi foglalások.**
Remaining = reserved − consumed − released. Már eligibilityből kizárt quarantine-hold
nem vonható le másodszor. Workflow-only custody redispatch nem company reservation.
Transit nem available; serialos készletnél az aggregált balance és a bizonyított eligible
serialhalmaz egyeztetendő, eltérés blokkol, nem automatikus quantity correction.

Netting: hatályos, még teljesítendő saját demandból saját eligible remaining reservation,
egyszer kiosztott free stock és saját open committed coverage vonható le. Ugyanazt a free
stockot több demand nem tekintheti egyszerre saját fedezetének. Received supply már physical
stock/own reservation, többé nem open PO coverage; receipt nem actual repair consumption.
Unassigned aktív commitment review/allocation tárgya, nem eltüntethető vagy korlátlanul szabad.

### 6.2. Függőség- és consumer-mátrix

| Függőség                     | Autoritatív forrás / projekció                                                                                                                                                                                                                                                | Adaptáció és blokkoló                                                                                                                                                                                                                              |
| ---------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Fizikai mozgások             | stock_movements immutable posting; return/delivery proof és command FK                                                                                                                                                                                                        | Scope/type/purpose dönt projectionhatásról; [StockMovement modell](../../app/Models/StockMovement.php) és minden writer közös contract. Source morph nem FK/owner. WP1-F02/F04.                                                                    |
| Fizikai balance/availability | movements → stock_balances + eligible serial/disposition; [StockBalanceRepository](../../app/Repositories/Admin/StockBalanceRepository.php)                                                                                                                                   | firstOrCreate sentinel U, összes reader company/eligible szűrése, legacy audit. Custody/pending nem balance; quarantined company qty nem available. WP1-F02/F03.                                                                                   |
| Foglalás                     | stock_reservations immutable owner és monoton counter; [StockReservationRepository](../../app/Repositories/Admin/StockReservationRepository.php), [StockReservationService](../../app/Services/Admin/StockReservationService.php)                                             | Full active reserved SUM helyett remaining; serial/order U, scope/purpose, partial release és parentmutex. Generic release ne kerülje meg D9-et. WP1-F02/F04.                                                                                      |
| Production consumption       | production_task_materials + production_tasks + movement/reservation tény; [ProductionTaskMaterialService](../../app/Services/Admin/ProductionTaskMaterialService.php)                                                                                                         | Saját owner/batch/serial/hely/unit, nettó elérhetőség más foglalások után; partial counter. Repair nem production_task, közös készletguard szükséges. WP1-F02.                                                                                     |
| Repair consumption           | repair_materials + movements + reservation; saját order                                                                                                                                                                                                                       | Ugyanaz a company eligible contract, karantén nem javítási anyag. Nem külön inventory ledger. V15.                                                                                                                                                 |
| Material demand              | material_requirements stable owner/current revision + coverage_events demand snapshot; [MaterialRequirementRepository](../../app/Repositories/Admin/MaterialRequirementRepository.php), [MaterialRequirementService](../../app/Services/Admin/MaterialRequirementService.php) | Customer-order-item+cikk legacy jelentés, több production order aggregáció vizsgálata; repair XOR; retry nem új revision/generation. WP1-F07.                                                                                                      |
| PR és source                 | sources immutable initial allocation, coverage_events effective delta; [PurchaseRequisitionService](../../app/Services/Admin/PurchaseRequisitionService.php)                                                                                                                  | whereDoesntHave(source) helyett current coverage; direct pointer nem teljes ownership. Manual/generated/approve/delete mind D11 alatt. WP1-F07.                                                                                                    |
| PO commitment                | PO/POitem + linked PR sources + coverage_events sent/ack proof; [PurchaseOrderService](../../app/Services/Admin/PurchaseOrderService.php)                                                                                                                                     | Demand withdraw nem PO cancel; jelenlegi received státusz/soft-delete nem actual supply. Cancellation/reassign explicit bizonyíték. WP1-F07.                                                                                                       |
| GR supply                    | posted GR/GRitem + stock_movement + coverage supply_received; [GoodsReceiptService](../../app/Services/Admin/GoodsReceiptService.php)                                                                                                                                         | PO/PR/source/owner egyezés, partial receipt, physical projection és coverage csökkentés atomikus. RMA return nem purchase receipt. WP1-F07.                                                                                                        |
| Inventory reporting          | [ReportingRepository](../../app/Repositories/Admin/ReportingRepository.php): balance/reservation aggregátumok                                                                                                                                                                 | Normal available, company physical, custody, pending és transit jelentés külön; quarantine és remaining, export/cache ugyanaz. V07.                                                                                                                |
| MRP/forecast/intelligence    | [ManufacturingIntelligenceRepository](../../app/Repositories/Admin/ManufacturingIntelligenceRepository.php): demand + eligible stock + assigned open coverage                                                                                                                 | Customer-order inner join ne tüntesse el repair demandot; item-szintű open PO levonás ne osszon idegen coverage-et; production_consume trend és repair consume értelmezése explicit. WP1-F02/F07.                                                  |
| Production orchestration     | [ProductionPlanService](../../app/Services/Admin/ProductionPlanService.php), production_orders/tasks                                                                                                                                                                          | Közös owner/stock mutex, eredeti manufacturing sequence/serial nem átírandó; több-PO demand aggregáció felülvizsgálat. V03/V15.                                                                                                                    |
| Read/cache delegálók         | C1 G2 mátrix: ReportingService, DashboardService, ManufacturingIntelligenceService, MaterialForecastService, ProcurementRecommendationService, ProductionRiskService, BottleneckAnalysisService, QualityTrendService, SupplierPerformanceService                              | Ugyanaz a derived meaning, permission és [BusinessCacheInvalidator](../../app/Services/BusinessCacheInvalidator.php) afterCommit. Queue/export/direct-query és új consumer csak ugyanilyen szerződéssel. WP3 teljes módszerszintű review kötelező. |

### 6.3. Mozgási szerződés – nincs párhuzamos ledger

| Esemény                               | Scope és company balance                                | Custody/allocation és jog                                                                                                                         |
| ------------------------------------- | ------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------- |
| Original receive                      | external→customer_custody, 0                            | Egyszeri member receipt, actual location, original custody; D2/D3 proof                                                                           |
| Original transfer/return/scrap        | custody→custody vagy external, 0                        | Valós mozgás; return/scrap megszünteti az aktuális original custodyt; scrap authorization                                                         |
| Company repair material consume       | company_stock→external, −q                              | Eligible stock és saját remaining foglalás; repair_materials tény                                                                                 |
| Replacement reserve/prepare           | Foglalás 0; valós company→company helyváltás összesen 0 | Más célra tiltott; preparation nem ownership                                                                                                      |
| Replacement dispatch                  | company_stock→external, −1 egyszer/shipment             | Reservation fulfilled, serial in_transit, logical obligation megmarad                                                                             |
| Failure/delivery/ownership            | Nincs fizikai movement, 0                               | Három külön bizonyíték; nincs automatikus receipt vagy jogi reversal                                                                              |
| Physical return-intake                | Szűk external→external intake, 0                        | Mandatory quarantine/actual location, külön return identity; G1–G4                                                                                |
| Verified company admission            | external→company_stock, +1 egyszer/admission            | Nem második fizikai receipt; quarantine marad dispositionig                                                                                       |
| Verified noncompany custody besorolás | external→customer_custody, 0                            | Replacement nem original custody tábla; proof/movement/instance követi                                                                            |
| Authorized quarantine release         | Már admitted esetben nincs új qty                       | Inspection/rights/authorization history; company free vagy atomikus új reservation                                                                |
| Rights-alapú custody redispatch       | customer_custody→external, 0                            | Új workflow reservation és release; nincs fiktív company ownership/reversal                                                                       |
| Pending scrap/karanténhely-váltás     | Nincs implicit új external→external kivétel             | A C1 lifecycle igényét WP4 explicit type/purpose/proof mappingre kell bontani; addig ez az út blokkolt, nem általános correction/transfer kiskapu |

## 7. Történeti egyeztetés és döntéskövetés

### 7.1. Táblák és felülírt feltételezések

| Csoport / történeti terv                                                                                                                                                                                                                             | Jelen leltár                                                                             | Minősítés / megmaradó eltérés                                                                                                        |
| ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------ |
| A eredeti 22: requests, members, claims, orders, operations, assignments, time, materials, inspections, warranty rules, handover, assessments, quotes, lines, custodies, releases, delivery, ownership, dispositions, histories, commands, genealogy | Azonos nevű és felelősségű 22 jelölt                                                     | Identity változatlan; a 3. fejezet teljes oszlopblokkjai A/B baseline + explicit C1/WP1 kiegészítés. Nem állítunk változatlan DDL-t. |
| B hozzáadott credit allocations                                                                                                                                                                                                                      | 23. jelölt megmarad                                                                      | B tartós source-claim helyes; submit-held/applied protocol D6/AM-03 által SUPERSEDED.                                                |
| C1 verified diagnostic fee                                                                                                                                                                                                                           | 24. jelölt hozzáadva                                                                     | Önálló fee identity szükséges, order-rate/quote nem helyettesíti.                                                                    |
| C1 coverage events                                                                                                                                                                                                                                   | 25. jelölt hozzáadva                                                                     | Legacy procurementet is lefed; repair-only history és generic audit nem quantity ledger.                                             |
| Átnevezett táblák                                                                                                                                                                                                                                    | Nincs                                                                                    | A 25 C2-jelölt neve megőrzött.                                                                                                       |
| Eltávolított jelölt                                                                                                                                                                                                                                  | Nincs                                                                                    | Külön shipments/attempts/quarantine/reversal/obligation tábla továbbra sem indokolt.                                                 |
| A claim csak service INSERT/DELETE guard                                                                                                                                                                                                             | Trigger acquire + restricted close finalizer, typed proof                                | C1 design resolved, implementation unverified; WP1-F01.                                                                              |
| A/B external→external általános tiltás; közvetlen company return                                                                                                                                                                                     | AM-02 intake→quarantine→verified admission                                               | Szűk intake kivétel; G1–G4 kötelező. Általános scrap/transfer kivétel nincs; WP1-F04.                                                |
| B U(reservation,attempt)                                                                                                                                                                                                                             | C1 U(reservation), új reservation/attempt                                                | Movement/command/previous és source occurrence U is kell; WP2-ben bizonyítandó.                                                      |
| B U(related_return_event_id) minden childra                                                                                                                                                                                                          | Típusfüggő singleton intake/admission + verziózott döntések                              | Több inspection/history child engedett; WP1-F04.                                                                                     |
| A egyetlen case/serial ownership-trigger kulcs                                                                                                                                                                                                       | Source namespace/key és event_sequence megmarad, explicit reversal original-FK hozzáadva | D5/D10, AM-04; effective lánc nem timestamp maximum.                                                                                 |
| A source U(requirement,demand_revision)                                                                                                                                                                                                              | U(requirement,procurement_generation), generation_key és PR-line/owner U                 | Ugyanazon demand authorized coverage delta után új generation lehet; retry nem növel. Line-split WP1-F07.                            |
| A available=physical−full active reserve                                                                                                                                                                                                             | Eligible company physical−remaining mennyiségi reserve                                   | Karantén/custody/pending/transit és double deduction kizárása; WP1-F02.                                                              |
| A/B 7 existing change                                                                                                                                                                                                                                | 7 közvetlen +11 explicit hatásvizsgálati tábla                                           | A többi FK-target nem automatikus schema change; konkrét deletion contract WP2/WP6.                                                  |
| B SQL prototípus és hash                                                                                                                                                                                                                             | Történeti bizonyíték változatlan                                                         | Nem C1/C2 teljes DDL/service bizonyíték; hardening utáni script nem köteles régi hashhez egyezni.                                    |

### 7.2. RMA-BD-001–020, D1–D11 és F1–F4 traceability

Az alábbi rövid BD jelölések az ADR változatlan **RMA-BD** azonosítóira utalnak;
nem újraszámozás vagy új üzleti szabályok.

| Döntés        | WP1 gazda / ellenőrzés                                                                                               |
| ------------- | -------------------------------------------------------------------------------------------------------------------- |
| RMA-BD-001    | requests→members→egy order/member; 3.1/3.2/3.4, V01                                                                  |
| RMA-BD-002    | Serial + saját gyártás + bizonyított eredet, semi-finished is lehet; 3.2/3.12, V03                                   |
| RMA-BD-003    | Creator/user/employee és reporter külön; owner jogosultság; 3.1, V03                                                 |
| RMA-BD-004    | Claim serial-PK, close-only release; 3.3, V01/V02                                                                    |
| RMA-BD-005    | Immutable manual assessment/override; 3.12, V04                                                                      |
| RMA-BD-006    | Fizetős start aktuális approval; 3.5/3.13, F4                                                                        |
| RMA-BD-007    | Customer custody nem company balance; 3.15/6.3, V05                                                                  |
| RMA-BD-008    | Végső disposition/proof és engedélyezett scrap; 3.19, V02                                                            |
| RMA-BD-009    | Attempt/operation/QC history, nincs auto retry limit; 3.5/3.9                                                        |
| RMA-BD-010    | Assignment/time/material/inspection; 3.5–3.9, V15                                                                    |
| RMA-BD-011    | Teljes case proof, minden termék/attempt/obligation rendezett; 3.1/3.19/3.20, V02                                    |
| RMA-BD-012    | Rule/handover/evidence/manual assessment; 3.10–3.12, V04                                                             |
| RMA-BD-013    | Quote/lines/submission/customer response/expiry; 3.13/3.14                                                           |
| RMA-BD-014    | Fee-mode/verified fee/credit; 3.4/3.23/3.24, V12                                                                     |
| RMA-BD-015    | Serialos változatlan history és genealogy; 3.20/3.22, V14                                                            |
| RMA-BD-016    | Member/serial/receipt identity + custody/movement/audit; 3.2/3.15, V05                                               |
| RMA-BD-017    | Normal availability/foglalás/fogyasztás kizárás; 6.1/6.2, V07/V15                                                    |
| RMA-BD-018    | Company material/owner demand/procurement coverage; 3.8/3.25, V13/V15                                                |
| RMA-BD-019    | Recipient/time/reference/proof/condition és custody vége; 3.16, V05/V09                                              |
| RMA-BD-020    | Reservation/preparation/shipment/ownership/return és original outcome külön; 3.16–3.19, V06–V11                      |
| D1            | Három scope, customer balance0, 2/6.3                                                                                |
| D2            | Saját gyártású serialos semi_finished_product is eligibility proof mellett, 3.2/V03                                  |
| D3            | Egy case egy owner, reporter külön, 3.1/3.2                                                                          |
| D4            | Csere nem automatikus garanciareset, mindkét history, 3.10–3.12                                                      |
| D5            | Physical release és ownership külön proof/időpont, 3.16/3.18                                                         |
| D6 (RMA-D6)   | Approval-only credit<=verified fee, 3.23/3.24/V12                                                                    |
| D7 (RMA-D7)   | Execution/settlement review és authorized release, 3.20/3.23/V12                                                     |
| D8 (RMA-D8)   | Failure külön, minden actual return quarantine, 3.17/V07/V10                                                         |
| D9 (RMA-D9)   | Új valós shipment új reservation/occurrence, 3.16/V08/V09                                                            |
| D10 (RMA-D10) | Reversal original event FK + jogalap, 3.18/V06                                                                       |
| D11 (RMA-D11) | Demand/coverage/PO commitment/receipt külön, 3.25/V13                                                                |
| F1            | Company reservation/preparation és külön shipment/delivery/ownership, idempotens posting; 3.16–3.18/4.2/6.3          |
| F2            | Státusz helyett movement/proof/reservation/QC/authorization alapú atomikus case-close; 3.1–3.3/3.19/3.20, V02        |
| F3            | Eredeti–csere serialpár megőrzött reservation/release/decision kapcsolatokon, original outcome külön; 3.12/3.16/3.19 |
| F4            | Fizetős execution start/scope aktuális jóváhagyással, 3.4/3.5/3.13                                                   |
| AM-01         | Logical obligation ≠ physical shipment, 3.16/4.2/V08/V09                                                             |
| AM-02         | Kétlépcsős intake/admission; G1 evidence V10, G2 balance0 V11, G3 isolation V07, G4 külön identity V10/V11           |
| AM-03         | Verified fee + approval-only allocation/review, 3.23/3.24/V12                                                        |
| AM-04         | Causal ownership és coverage history, 3.18/3.25/V06/V13                                                              |

## 8. Findingok – nyitott technikai kapuk

Minden alábbi finding **OPEN / nem implementált, nem verifikált**.
BLOCKER a migrációs/rollout készenlétre vonatkozik; az Accepted üzleti döntéseket nem minősíti át.

| ID / súlyosság      | Bizonyíték, érintett táblák, invariáns                                                                                                                                                                                                       | Javasolt feloldás és szükséges döntés/bizonyítás                                                                                                                                                                                                                      |
| ------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| WP1-F01 **BLOCKER** | C1 G1 és C2 WP2 csak terv; claims/requests/members/commands/history. U/PK nem biztosít open-member→claim teljességet vagy close-only DELETE-et; V01/V02.                                                                                     | Acquire-trigger + restricted runtime/definer/finalizer + typed proof + close guard pontos DDL/jog/rollback. WP2 raw SQL és kétprincipalos negatív/concurrency próba, SQLite eltérés. Nincs új üzleti jóváhagyásigény.                                                 |
| WP1-F02 **BLOCKER** | A 2.2/C1 G2 és tényleges balance/reservation/consume/reporting kód: scope/disposition nélküli összegek és teljes reserved SUM. balances/reservations/movements/instances/requirements, production és procurement consumerek; V07/V15.        | WP3 összes writer/reader/cache/queue szerződés, közös lock/remaining/eligible meaning. Quarantine/custody/transit/pending és idegen owner negatív próba; mixed-version rollout tilos igazolásig.                                                                      |
| WP1-F03 **BLOCKER** | stock_balances U(item,location,nullable batch), item_instances origin FK nélkül, reservation nullable hely/owner. Migrációforrás nem adat-audit; V01/V05/V08.                                                                                | WP6 reprezentatív read-only audit, duplicate/orphan/null mapping és expand/backfill/contract/rollback terv. Nem önkényes összeadás, default company scope vagy feltételezett eredet.                                                                                  |
| WP1-F04 **HIGH**    | A/B return U(related_return) túl erős, A external→external CHECK túl szigorú, C1 lifecycle pending scrap/transfer útja nincs teljes type-contractra bontva. releases/delivery/ownership/inspections/history/movements/reservations; V06–V11. | WP4 pontos típus/cél/nullable singleton/composite/rights/state mátrix; WP2 DDL. Intake kivétel nem általánosítható. Pending scrap/transfer új kivételéhez explicit feltétel-jóváhagyás kell, ha az Accepted szűk mappingen túllépne.                                  |
| WP1-F05 **HIGH**    | documents SoftDeletes/morph/verzió U nélkül; item_instance_relations parent CASCADE; több legacy FK SET NULL. V04/V14/genealogy.                                                                                                             | WP2/WP6 konkrét FK/deletion/jog és file-version/checksum retention terv, törlés/bulk/soft-delete negatív teszt. Retention idő és szerepkör permission üzleti/adatgazdai specifikáció marad.                                                                           |
| WP1-F06 **HIGH**    | C1 fee/credit review minimum, A money/quote/rule snapshot technikai terv; fees/allocations/quotes/lines/history/assessments. CHECK nem cross-row amount/eligibility/authorization; V04/V12.                                                  | WP5 fee-cap/effective-use/correction/settlement proof és exact rounding specifikáció, WP2 composite/active U/concurrency. Accounting correction és alkalmazandó jogi/szerződéses feltétel owner-specifikáció, nincs automatikus új credit/garanciareset.              |
| WP1-F07 **HIGH**    | C1 coverage minimum és generation-key line-split nyitott; meglévő PR/PO/GR státusz/soft-delete nem sent/ack/actual receipt proof. requirements/sources/coverage_events/PR/PO/GR; V13.                                                        | WP5 teljes type→quantity/NULL/lineage/revision/transfer/legacy baseline mátrix; generation egyszeri delta és actual receipt ownerallokáció. WP2 conservation/paired transfer/concurrent generate+receive próbák. Több-PO legacy demand aggregációt WP3-ban tisztázni. |
| WP1-F08 **MEDIUM**  | Projekt case-insensitive collation; command/occurrence/generation/hash kulcsok byte-exact meaningje és hossz/indexkapacitás nincs motorral igazolva. commands/releases/ownership/coverage/sources.                                           | WP2 collation/canonicalization, teljes index-prefix/length és SQL UNKNOWN/unsigned/JSON/decimal eltérések próbája. Nincs query-plan/teljesítmény sikerállítás mérés nélkül.                                                                                           |
| WP1-F09 **MEDIUM**  | A self-FK-k/correction/overlap/QC/version kombinációja és opcionális composite tuple nem teljes gráf- vagy intervalguard; operations/assignments/time/genealogy/inspections/history.                                                         | WP2 parenttuple/cycle/overlap/monoton guard és FK-contract sorrend; WP7 negatív párhuzamos tesztek. Globális employee-overlap szervezeti policy nyitott, addig nem találunk ki tiltást.                                                                               |
| WP1-F10 **LOW**     | A/B történeti szövegek és C1 closure aktuális felülírásai egy dokumentumon belül együtt szerepelnek; minden leltározott jelölt értelmezését érintheti.                                                                                       | Ez a 7.1 reconciliation az aktuális olvasási irány. Történeti eredmények nem átírhatók; későbbi új specifikáció linkelje a felülírt feltételezést. Ez dokumentációs útmutatás, nem technikai finding lezárása.                                                        |

## 9. Nyitott döntések és WP2 belépési feltételek

Nem függő elfogadás a D1–D11 vagy AM-01–AM-04; nem kérünk ismételt üzleti jóváhagyást.
Nyitott részletek: szerepkör/permission és retention; konkrét pénzkerekítés;
alkalmazandó szerződéses/jogi garancia- és tulajdonátszállási feltétel;
settled kreditkorrekció külső eljárása; globális dolgozói időátfedés policy.
Részleges ownership reversal vagy az intake szűk kivételén túli external→external
workflow szükség esetén külön pontosítandó, nem hallgatólagosan engedélyezett.
Ezek nem teszik újra OPEN-né a már elfogadott alapdöntéseket.

WP2 tervezése e leltárból indulhat; véglegesítése előtt:

1. WP4 oldja fel WP1-F04 teljes shipment/return/admission/rights/purpose és typed proof mátrixát,
   változatlan AM-02-G1–G4 mellett.
2. WP5 adja a fee/credit/coverage teljes type- és quantity/amount/lineage szerződését (F06/F07).
3. WP3 rögzítse az összes consumer és közös mutex/availability/netting szerződését (F02),
   a legacy több-production-order demand aggregációval együtt.
4. WP2 készítse el a 25+7 jelölt pontos motoros constraint/index/grant/guard specifikációját,
   composite parent U-kat, ciklikus FK-k expand→contract sorrendjét és claim-finalizert.
5. WP6 tervezze az adat-auditot, retention/deletion és mixed-version rollout/rollback kapukat;
   az audit nem futott WP1-ben. WP7 a tervezett és futtatott bizonyítékot külön vezesse.

Nincs deferred MySQL FK-feltételezés. Különösen member↔receipt movement,
release↔return movement, history↔allocation és requirement↔coverage current pointer
ciklusok kétlépcsős FK-létrehozást és tranzakciós insert-sorrendet igényelnek.
Immutable event utólagos „kitöltése” nem lehet cikluskezelési megoldás;
nullable projection pointer csak a teljes tranzakció végére válhat érvényessé.
Új séma után az alkalmazásfunkció továbbra is külön felhatalmazott fázis.

## 10. Dokumentációs ellenőrzés és átadási határ

A WP1 ellenőrzése helyi fájlokra korlátozott: Prettier, relatív link/anchor,
döntésazonosító és 25-táblás névleltár, UTF-8/whitespace, `git diff --check`,
tracked fájlok és index változatlansága. Lefutott ellenőrzések (2026-11-02):

| Ellenőrzés                                                | Eredmény                                                                                                                                        |
| --------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------- |
| Helyi Prettier `--check` kizárólag erre a Markdown-fájlra | Exit 0, formázás megfelelő; nincs dependency install                                                                                            |
| Memóriában futó leltár- és döntéshivatkozás-vizsgálat     | 25 egyedi jelölt, 18 existing-impact sor (7 S +11 R); 20 RMA-BD, D1–D11, F1–F4 és AM-01–AM-04 táblázatos nyoma                                  |
| Relatív fájl/anchor-vizsgálat                             | 65 létező relatív hivatkozás, köztük 3 létező anchor; nincs abszolút környezeti út                                                              |
| Encoding/whitespace/fence ellenőrzés                      | Szigorúan dekódolható UTF-8, BOM és helyettesítő karakter nélkül; zárt fence-ek, nincs trailing whitespace                                      |
| `git diff --check`                                        | Exit 0; az untracked dokumentum whitespace-ét külön közvetlen tartalomvizsgálat is ellenőrizte                                                  |
| Scope/index és történeti eredmény                         | Tracked diff és staged diff üres; csak ez az új dokumentum untracked; a JSON hash-e az 1. fejezet rögzített értékével egyező                    |
| Érzékeny tartalom ellenőrzése                             | Nem talált credential-érték, token, privát kulcs, személyes elérhetőség vagy abszolút környezeti út; a forrásblokkok csak deklaratív sémaadatok |

Az ellenőrző Python-részletek memóriából futottak; új verifikációs script vagy eredményfájl nem készült.

Új domain-teszt, Pint/PHP futás és build nem releváns a kizárólag Markdown változáshoz;
DDL/DB/service/concurrency próbák itt tiltottak és **nem futottak**.
Nincs dependency install, migráció, DB-kapcsolat/írás, eredmény-regenerálás,
staging, commit, push, merge vagy branchváltás.

A dokumentum leltárként review-ra átadható. A teljes C2, az alkalmazás és a séma
státusza továbbra is **NOT MIGRATION READY**; a 8. fejezet nyitott kapui megmaradnak.
