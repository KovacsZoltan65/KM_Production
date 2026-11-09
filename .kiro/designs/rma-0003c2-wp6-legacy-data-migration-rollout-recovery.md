# RMA-0003/C2-WP6 – Legacy Data, Migration Rollout & Recovery

Státusz: **REVIEW / NOT MIGRATION READY**. Dátum: 2026-11-04.
Dokumentációs átállási és recovery terv; nem végrehajtott migráció, adataudit, backup vagy restore.

## 1. Baseline and authority

Branch: `feature/returns-repair-management`.
HEAD: `e21e83da44e7ec2228efa6c5c6f8d1021340b73b`.
Induló `git status --short --untracked-files=all`: az alábbi négy fájl untracked;
tracked és staged diff üres. E baseline-t a feladat nem módosítja.

| Megőrzött pending dokumentum                                       | Belépési SHA-256                                                   |
| ------------------------------------------------------------------ | ------------------------------------------------------------------ |
| `.kiro/designs/rma-0003c2-wp1-schema-inventory.md`                 | `f4d6682d9bb0b8d58882263199919d9af394cfee58c0c5f0ea704ef6859fa932` |
| `.kiro/designs/rma-0003c2-wp3-inventory-mrp-consumer-contracts.md` | `1a340ac30113417672869ff0d71bc854ce02f81d4ae2dfd2a5c09ff5ec22d994` |
| `.kiro/designs/rma-0003c2-wp4-return-intake-stock-admission.md`    | `d7a1fc49dc37eadfeba6cfaaa0ee6abeb7319ef34cb4adde5f9a7a4a58538023` |
| `.kiro/designs/rma-0003c2-wp5-fee-credit-procurement-coverage.md`  | `73be78b8a02de54d99ed39cb69fddabceda5b134d696feee4cd46bdfdc0e058d` |

Üzleti autoritás: [Accepted RMA ADR-0006](../decisions/0006-rma-repair-management-architecture.md)
és [ADR-0001 Stock Movements](../decisions/0001-stock-movements.md).
Előzmények: [A](rma-0003a-data-model-integrity-design.md),
[B](rma-0003b-integrity-verification.md), [C1](rma-0003c1-business-decisions-integrity-gates.md),
[C2 terv](rma-0003c2-detailed-schema-review-plan.md), [WP1](rma-0003c2-wp1-schema-inventory.md),
[WP3](rma-0003c2-wp3-inventory-mrp-consumer-contracts.md),
[WP4](rma-0003c2-wp4-return-intake-stock-admission.md),
[WP5](rma-0003c2-wp5-fee-credit-procurement-coverage.md).
Az Accepted ADR, C1 ACCEPTED WITH CONDITIONS, RMA-BD-001–020, D1–D11, F1–F4,
AM-01–AM-04 és kötelező AM-02-G1–G4 változatlan. WP1 megőrzött fejlécének REVIEW
státuszát nem írjuk át; a WP3 külön rögzíti a projektgazdai conditional review-t.
WP5-REV02-01: több source-line/generation már OWNER-APPROVED; nem új jóváhagyási kérdés.

| Evidence class    | WP6 jelentés                                                                                 |
| ----------------- | -------------------------------------------------------------------------------------------- |
| CONFIRMED CURRENT | Rögzített RMA HEAD forrása; migrációdefiníció nem élő séma- vagy adatbizonyíték              |
| HISTORICAL        | Read-only Git-object a `379be484405a789388df5f5e8367a18b1e598bbc` MRP commiton               |
| PROPOSED          | Későbbi implementációs/deployment/recovery ajánlás, nem létező funkció                       |
| OPEN              | Ténylegesen eldöntendő részlet; nem a már elfogadott RMA-döntések újranyitása                |
| UNVERIFIED        | DB-tartalom, grant, futtatás, éles topológia, monitoring, backup és restore még nem vizsgált |

Nincs DB-kapcsolat, production-data hozzáférés, Laravel bootstrap, teszt, verifier vagy seeder.
Nem olvastunk `.env`-et, credentialt vagy éles fájlt. Nincs fetch/pull/checkout/import/stage/commit/push/merge.
Kizárólag ez az új Markdown engedélyezett. Történeti B JSON nem regenerálódik;
SHA-256: `d1b4443724984cbd21c9852c4611fa659411e09924192d7b3b9296164e33a6e6`.
A B korábbi script-hash-e történeti; a jelenlegi credential-hardened scriptet nem futtatjuk.

## 2. Current schema and legacy inventory

### 2.1. Darabszám és határ

**CONFIRMED CURRENT dokumentumleltár:** a WP1 3.1–3.25 pontosan 25 különböző új
táblajelöltet sorol fel; a 4.1 impact-táblában 18 existing sor van, ebből 7 S és 11 R.
S = közvetlen séma-/FK-változás, nem feltétlenül új oszlop; R = kapcsolat/törlés/consumer review.
A current migrációkban a 25 jelölt egyikének create-definíciója sem található.
Ez nem állítás az éles adatbázis tartalmáról vagy a későbbi végleges DDL darabszámáról.

Új, kizárólag **PROPOSED** jelöltek: `repair_requests`, `repair_request_items`, `active_repair_claims`, `repair_orders`, `repair_operations`, `repair_operation_assignments`, `repair_time_entries`, `repair_materials`, `repair_inspections`, `product_warranty_rules`, `product_handover_evidences`, `warranty_assessments`, `repair_quotations`, `repair_quotation_items`, `repair_custodies`, `repair_releases`, `repair_delivery_events`, `repair_ownership_events`, `repair_dispositions`, `repair_status_histories`, `repair_commands`, `item_instance_genealogy_events`, `repair_diagnostic_credit_allocations`, `repair_diagnostic_fees`, `procurement_coverage_events`.

A további master/FK célok (`items`, `item_batches`, `customers`, `customer_order_items`,
`bom_items`, `boms`, `operation_sequences`, `operation_sequence_steps`, `employees`,
`users`, `factory_units`, `suppliers`) és az `activity_log`, queue/cache infrastruktúra
függőségek; nem számoljuk őket át a WP1 18 sorába. Ezeket is vizsgálni kell valódi
catalog/grant/retention auditkor. Egy FK cél létezése nem jelent új schema-change igényt.

### 2.2. A 18 meglévő tábla jelenlegi sémája

Az alábbi táblánkénti kivonat az aktuális migrációk `up()` deklarációit közli,
nem futtatható migráció vagy SQL. Minden `id()` unsigned BIGINT PK; `timestamps()`
Laravel nullable created_at/updated_at, `softDeletes()` nullable deleted_at.
`nullable()` nélküli üzleti mező NOT NULL; minden további nullable mező a kivonatban szerepel.
`constrained()` a deklarált FK; `restrictOnDelete`, `cascadeOnDelete`, `nullOnDelete`
az aktuális delete action. Nem deklarált ON UPDATE akciót nem találunk ki.
Az implicit FK-indexek tényleges neve, motor/collation, telepített migration-state és
drift későbbi catalog audit: **UNVERIFIED**. A kivonat az explicit U/IX-eket is tartalmazza.
Minden mennyiségi `decimal(...,12,3)` itt signed, nem DB CHECK a nemnegativitásra.
Eloquent `decimal:3` string cast nem garantál exact service-aritmetikát vagy frontend-szerződést.

#### 2.2.1. `item_instances` – S

[Model](../../app/Models/ItemInstance.php); SoftDeletes: nincs a modellben.

[Migration](../../database/migrations/2026_06_21_000010_create_item_instances_table.php), CONFIRMED CURRENT deklaráció:

```text
$table->id();
$table->foreignId('item_id')->constrained()->restrictOnDelete();
$table->string('serial_number')->unique();
$table->foreignId('factory_unit_id')->constrained()->restrictOnDelete();
$table->foreignId('current_location_id')->nullable()->constrained('locations')->nullOnDelete();
$table->string('current_status')->default(ItemInstanceStatus::Planned->value)->index();
$table->unsignedBigInteger('production_order_id')->nullable()->index();
$table->timestamps();
```

#### 2.2.2. `item_instance_relations` – R

[Model](../../app/Models/ItemInstanceRelation.php); SoftDeletes: nincs a modellben.

[Migration](../../database/migrations/2026_06_21_000011_create_item_instance_relations_table.php), CONFIRMED CURRENT deklaráció:

```text
$table->id();
$table->foreignId('parent_item_instance_id')->constrained('item_instances')->cascadeOnDelete();
$table->foreignId('child_item_instance_id')->constrained('item_instances')->restrictOnDelete();
$table->decimal('quantity', 12, 3)->default(1);
$table->timestamps();
$table->unique( ['parent_item_instance_id', 'child_item_instance_id'], 'item_instance_relation_unique', );
```

#### 2.2.3. `stock_movements` – S

[Model](../../app/Models/StockMovement.php); SoftDeletes: nincs a modellben.

[Migration](../../database/migrations/2026_06_21_000025_create_stock_movements_table.php), CONFIRMED CURRENT deklaráció:

```text
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
```

#### 2.2.4. `stock_balances` – S

[Model](../../app/Models/StockBalance.php); SoftDeletes: nincs a modellben.

[Migration](../../database/migrations/2026_06_21_000023_create_stock_balances_table.php), CONFIRMED CURRENT deklaráció:

```text
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
```

#### 2.2.5. `stock_reservations` – S

[Model](../../app/Models/StockReservation.php); SoftDeletes: igen.

[Migration](../../database/migrations/2026_06_21_000024_create_stock_reservations_table.php), CONFIRMED CURRENT deklaráció:

```text
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
```

#### 2.2.6. `material_requirements` – S

[Model](../../app/Models/MaterialRequirement.php); SoftDeletes: igen.

[Migration](../../database/migrations/2026_06_21_000022_create_material_requirements_table.php), CONFIRMED CURRENT deklaráció:

```text
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
```

#### 2.2.7. `purchase_requisitions` – R

[Model](../../app/Models/PurchaseRequisition.php); SoftDeletes: igen.

[Migration](../../database/migrations/2026_06_21_000026_create_purchase_requisitions_table.php), CONFIRMED CURRENT deklaráció:

```text
$table->id();
$table->string('requisition_number')->unique();
$table->string('status')->default(PurchaseRequisitionStatus::Draft->value)->index();
$table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
$table->timestamp('requested_at')->nullable()->index();
$table->text('notes')->nullable();
$table->timestamps();
$table->softDeletes();
```

#### 2.2.8. `purchase_requisition_items` – S

[Model](../../app/Models/PurchaseRequisitionItem.php); SoftDeletes: igen.

[Migration](../../database/migrations/2026_06_21_000027_create_purchase_requisition_items_table.php), CONFIRMED CURRENT deklaráció:

```text
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
```

#### 2.2.9. `purchase_requisition_item_sources` – S

[Model](../../app/Models/PurchaseRequisitionItemSource.php); SoftDeletes: nincs a modellben.

[Migration](../../database/migrations/2026_06_24_000002_create_purchase_requisition_item_sources_table.php), CONFIRMED CURRENT deklaráció:

```text
$table->id();
$table->unsignedBigInteger('purchase_requisition_item_id');
$table->unsignedBigInteger('material_requirement_id');
$table->decimal('quantity', 12, 3);
$table->timestamps();
$table->unique(['purchase_requisition_item_id', 'material_requirement_id'], 'pr_item_source_unique');
$table->index('material_requirement_id');
$table->foreign('purchase_requisition_item_id', 'pr_item_source_item_fk') ->references('id') ->on('purchase_requisition_items') ->cascadeOnDelete();
$table->foreign('material_requirement_id', 'pr_item_source_requirement_fk') ->references('id') ->on('material_requirements') ->cascadeOnDelete();
```

#### 2.2.10. `purchase_orders` – R

[Model](../../app/Models/PurchaseOrder.php); SoftDeletes: igen.

[Migration](../../database/migrations/2026_06_21_000028_create_purchase_orders_table.php), CONFIRMED CURRENT deklaráció:

```text
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
```

#### 2.2.11. `purchase_order_items` – R

[Model](../../app/Models/PurchaseOrderItem.php); SoftDeletes: igen.

[Migration](../../database/migrations/2026_06_21_000029_create_purchase_order_items_table.php), CONFIRMED CURRENT deklaráció:

```text
$table->id();
$table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
$table->foreignId('purchase_requisition_item_id')->nullable()->constrained()->nullOnDelete();
$table->foreignId('item_id')->constrained()->restrictOnDelete();
$table->decimal('ordered_quantity', 12, 3);
$table->decimal('received_quantity', 12, 3)->default(0);
$table->string('unit');
$table->string('status')->default(PurchaseOrderItemStatus::Ordered->value)->index();
$table->text('notes')->nullable();
$table->timestamps();
$table->softDeletes();
$table->index('purchase_order_id');
$table->index('purchase_requisition_item_id');
$table->index('item_id');
```

#### 2.2.12. `goods_receipts` – R

[Model](../../app/Models/GoodsReceipt.php); SoftDeletes: igen.

[Migration](../../database/migrations/2026_06_21_000030_create_goods_receipts_table.php), CONFIRMED CURRENT deklaráció:

```text
$table->id();
$table->string('receipt_number')->unique();
$table->foreignId('purchase_order_id')->nullable()->constrained()->nullOnDelete();
$table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
$table->timestamp('received_at')->index();
$table->text('notes')->nullable();
$table->timestamps();
$table->softDeletes();
$table->index('purchase_order_id');
```

[Migration](../../database/migrations/2026_06_24_000001_add_status_to_goods_receipts_table.php), CONFIRMED CURRENT deklaráció:

```text
$table->string('status')->default(GoodsReceiptStatus::Draft->value)->index()->after('purchase_order_id');
```

#### 2.2.13. `goods_receipt_items` – R

[Model](../../app/Models/GoodsReceiptItem.php); SoftDeletes: nincs a modellben.

[Migration](../../database/migrations/2026_06_21_000031_create_goods_receipt_items_table.php), CONFIRMED CURRENT deklaráció:

```text
$table->id();
$table->foreignId('goods_receipt_id')->constrained()->cascadeOnDelete();
$table->foreignId('purchase_order_item_id')->nullable()->constrained()->nullOnDelete();
$table->foreignId('item_id')->constrained()->restrictOnDelete();
$table->foreignId('item_batch_id')->nullable()->constrained()->nullOnDelete();
$table->foreignId('location_id')->constrained()->restrictOnDelete();
$table->decimal('quantity', 12, 3);
$table->text('notes')->nullable();
$table->timestamps();
$table->index('goods_receipt_id');
$table->index('purchase_order_item_id');
$table->index('item_id');
$table->index('item_batch_id');
$table->index('location_id');
```

#### 2.2.14. `production_tasks` – R

[Model](../../app/Models/ProductionTask.php); SoftDeletes: nincs a modellben.

[Migration](../../database/migrations/2026_06_21_000032_create_production_tasks_table.php), CONFIRMED CURRENT deklaráció:

```text
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
```

#### 2.2.15. `production_task_materials` – R

[Model](../../app/Models/ProductionTaskMaterial.php); SoftDeletes: nincs a modellben.

[Migration](../../database/migrations/2026_06_21_000033_create_production_task_materials_table.php), CONFIRMED CURRENT deklaráció:

```text
$table->id();
$table->foreignId('production_task_id')->constrained()->cascadeOnDelete();
$table->foreignId('item_id')->constrained()->restrictOnDelete();
$table->foreignId('item_batch_id')->nullable()->constrained()->nullOnDelete();
$table->decimal('planned_quantity', 12, 3);
$table->decimal('used_quantity', 12, 3);
$table->string('unit');
$table->text('notes')->nullable();
$table->timestamps();
$table->index('production_task_id');
$table->index('item_id');
$table->index('item_batch_id');
```

#### 2.2.16. `production_orders` – R

[Model](../../app/Models/ProductionOrder.php); SoftDeletes: igen.

[Migration](../../database/migrations/2026_06_21_000021_create_production_orders_table.php), CONFIRMED CURRENT deklaráció:

```text
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
```

#### 2.2.17. `documents` – R

[Model](../../app/Models/Document.php); SoftDeletes: igen.

[Migration](../../database/migrations/2026_06_22_000001_create_documents_table.php), CONFIRMED CURRENT deklaráció:

```text
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
```

[Migration](../../database/migrations/2026_06_25_000001_add_storage_metadata_to_documents_table.php), CONFIRMED CURRENT deklaráció:

```text
$table->string('disk')->nullable()->after('description');
$table->string('path')->nullable()->after('disk');
$table->string('checksum', 64)->nullable()->after('file_size');
$table->boolean('approved')->default(false)->after('is_current');
$table->index(['documentable_type', 'documentable_id', 'document_type', 'version'], 'documents_group_version_index');
$table->index(['approved', 'is_current'], 'documents_approval_current_index');
```

[Migration](../../database/migrations/2026_06_28_000001_add_ai_processing_fields_to_documents_table.php), CONFIRMED CURRENT deklaráció:

```text
$table->string('processing_status') ->default(DocumentProcessingStatus::Pending->value) ->after('approved');
$table->decimal('processing_confidence', 5, 4)->nullable()->after('processing_status');
$table->json('processing_result')->nullable()->after('processing_confidence');
$table->json('processing_error')->nullable()->after('processing_result');
$table->timestamp('processed_at')->nullable()->after('processing_error');
$table->index('processing_status');
```

#### 2.2.18. `locations` – R

[Model](../../app/Models/Location.php); SoftDeletes: igen.

[Migration](../../database/migrations/2026_06_21_000002_create_locations_table.php), CONFIRMED CURRENT deklaráció:

```text
$table->id();
$table->foreignId('factory_unit_id')->nullable()->constrained()->nullOnDelete();
$table->string('code')->unique();
$table->string('name');
$table->string('location_type')->default(LocationType::Warehouse->value)->index();
$table->text('description')->nullable();
$table->boolean('is_active')->default(true)->index();
$table->timestamps();
$table->softDeletes();
```

### 2.3. Owner, audit, reader/writer és compatibility mátrix

Az osztálynevek a current `app` forrásai; a WP3 teljes módszerszintű katalógusa
a delegált és közvetlen utak authority-ja. Generic CRUD/raw DML és külső BI ezen túl külön auditolandó.
Audit minden sornál: meglévő Spatie Activitylog/AuditLogService használat nem DB append-only védelem.
Az új typed proof/history kapcsolatok PROPOSED; meglévő sorra nem következtetünk történeti RMA-factot.

| Tábla                               | Ownership/audit kapcsolódás                                          | Current readerek                                                      | Current writerek                                                           | Migration compatibility kockázat                                                                           |
| ----------------------------------- | -------------------------------------------------------------------- | --------------------------------------------------------------------- | -------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------- |
| `item_instances`                    | item+serial, optional origin PO; owner history még nincs             | ProductionTaskController/Repository, ItemInstance relationök          | ProductionTaskService generate/status                                      | Origin FK orphan audit, serial/QC/sequence és új scope/history; nem új serialgenerálás                     |
| `item_instance_relations`           | parent/child serial genealogy; qty12,3                               | ItemInstance/ItemInstanceRelation relationök                          | A vizsgált app-ban dedikált business writer nincs; külső/legacy UNVERIFIED | Parent CASCADE historyvesztés; genealogy proof/cycle/old relationship megőrzés                             |
| `stock_movements`                   | nullable source morph, nem valós FK; item/batch/serial               | StockMovementRepository/Controller, reporting/intelligence            | GoodsReceiptService, ProductionTaskMaterialService                         | Append-only és typed owner/occurrence; SET NULL nem tüntethet el source/place proofot                      |
| `stock_balances`                    | item/location/batch, nincs jogi owner                                | StockBalanceRepository, Reporting/Intelligence, InventoryQueryService | StockBalanceRepository increase via GR; material consume decrement         | Nullable-batch U és company-only projection; quarantine eligibility minden úton                            |
| `stock_reservations`                | nullable customer-item és production-order, nincs XOR/repair/counter | StockReservationRepository, InventoryQueryService, report/MRP         | StockReservationService; ProductionTaskMaterialService                     | Existing consumed status nem ismert used q; counters csak verified proofból, active soft-delete guard      |
| `material_requirements`             | customer-item+required item, nincs DB owner U                        | MaterialRequirementRepository, PR és intelligence                     | MaterialRequirementService/repository upsert                               | Multi-PO overwrite/duplikáció audit; stable ID és repair XOR, nincs historical remap                       |
| `purchase_requisitions`             | requester, header; nincs current supplier planning field             | PurchaseRequisitionRepository/Controller, ProcurementDashboard        | PurchaseRequisitionService create/update/approve/delete/generate           | Soft-delete és item cascade megőrzi source/supplier historyt; header status nem sent proof                 |
| `purchase_requisition_items`        | header; representative direct requirement vs minden source           | PurchaseRequisitionRepository, PO generator, sources model            | PurchaseRequisitionService                                                 | Direct pointer nem teljes lineage, CASCADE/SET NULL retention és source total cap                          |
| `purchase_requisition_item_sources` | PRitem+requirement source anchor                                     | MaterialRequirement/PRitem relations és PR generator                  | PurchaseRequisitionService generate                                        | Initial snapshot immutable, FK CASCADE review; owner-approved generation multi-line, baseline nem dupla +q |
| `purchase_orders`                   | supplier + optional PR/creator                                       | PurchaseOrderRepository, GR, reporting/intelligence                   | PurchaseOrderService és PR generatePurchaseOrder                           | Unknown commitment vs sent/ack, supplier updates/close/delete guard; current PR FK nem UNIQUE              |
| `purchase_order_items`              | header, optional PRitem; ordered/received q                          | PurchaseOrderRepository, GR és intelligence                           | PurchaseOrderService/PR generator; GoodsReceiptService counters            | Partial GR/supplier/source qty conservation, SET NULL lineage audit                                        |
| `goods_receipts`                    | optional PO/receiver, received_at; status migration                  | GoodsReceiptRepository/Controller, Reporting/Intelligence             | GoodsReceiptService create/post                                            | Posted actual movement vs header state; soft-delete/PO-link protection                                     |
| `goods_receipt_items`               | header/POitem/item/batch/location, nincs soft-delete                 | GoodsReceiptRepository, GoodsReceiptService, Reporting                | GoodsReceiptService creation/posting                                       | Header cascade/POitem-null tilthat lineage; receipt owner allocation exact és egyszeri                     |
| `production_tasks`                  | PO+instance+sequence step+employee                                   | ProductionTaskRepository/Controller, ShopFloor/Capacity/QC            | ProductionTaskService és QualityCheckService                               | Eredeti gyártás nem repair task; completed nem origin/stock proof önmagában                                |
| `production_task_materials`         | task/item/batch, nincs stored movement/reservation FK                | ProductionTaskRepository/show, Reporting/Intelligence                 | ProductionTaskMaterialService                                              | Movement source morph reverse audit, planned/actual nem inferred consumed counter                          |
| `production_orders`                 | customer-item/plan-item/item/BOM/sequence                            | ProductionTask/Plan/Capacity/MaterialRequirement és report            | ProductionPlanService/ProductionTaskService orchestration                  | Multi-PO aggregate, origin link/sequence/QC immutable; soft-deleted is történeti origin lehet              |
| `documents`                         | documentable morph target, version és actor                          | DocumentRepository/Service, registry/policy, download, AI job         | DocumentService/Version/ApprovalService, ProcessDocumentJob                | DB és object storage cut-off, version checksum, retention/permission; nincs group-version U                |
| `locations`                         | factory unit nullable, nincs globális quarantine flag                | LocationRepository/AdminService, inventory/report/master selectors    | LocationAdminService/generic CRUD                                          | Location=quarantine nem elég E-hez; deletion/inactive place history és external-rights elválasztás         |

**CONFIRMED CURRENT kódkockázatok:**
[balance increase](../../app/Repositories/Admin/StockBalanceRepository.php#L73) nullable-batch firstOrCreate;
[production material store](../../app/Services/Admin/ProductionTaskMaterialService.php#L28)
teljes reservationt consumednek állíthat részfelhasználáskor;
[requirement upsert](../../app/Repositories/Admin/MaterialRequirementRepository.php#L62)
customer-order-item+cikk kulcsra ír egy PO számításából;
[PO close](../../app/Services/Admin/PurchaseOrderService.php#L124) GR nélkül is Received állapotot állíthat.
Ezek megfigyelt kódutak, **nem bizonyított éles adatkárok**. Null-batch duplikátum,
owner-orphan, negatív stock vagy elveszett proof tényleges darabszáma: UNVERIFIED.

## 3. Read-only legacy audit specifications

### 3.1. Audit végrehajtási szerződés – későbbi feladat

Minden Axx **PLANNED / NOT EXECUTED**, read-only query specification, nem most futtatandó parancs.
Csak külön engedélyezett, reprezentatív védett snapshoton vagy jóváhagyott read-only felületen;
itt production elérés nincs. Rögzítendő snapshot/backup ID, UTC cut-off, kód/séma/engine/SQL-mode,
scope, query-verzió/hash, operator, access principal, olvasási konzisztencia és forráshash.
FK-k mögötti soft-deleted rekordokat raw olvasásnál is bevonjuk és külön jelöljük.
Nagy táblát nem offsettel vagy egymással időben eltérő adatokból mintázunk és nevezünk teljesnek:
reprezentativitás, index/EXPLAIN, terhelési limit és konzisztens cut-off DBA review tárgya.
Interleaved élő read, replica lag vagy külön snapshotból vett balance/movement hamis eltérést adhat.

Minden specifikáció kimenete: érintett PK-k és kapcsolati bizonyítékok, expected/observed exact
quantity/unit, severity és confidence, REVIEW/EXCEPTION/N/A indok; summary count csak tényleges
futtatásból. Személyes adat helyett minimalizált azonosítók, védett artifact tárolás.
NULL/unknown nem nulla vagy valid. Nincs UPDATE/DELETE/DDL/temp write/backfill/correction.
Eltéréshez felelős és később külön jóváhagyott rendezés kell, nem automatikus javítás.

| ID – PLANNED / NOT EXECUTED    | Dataset                              | Read-only query specification                                                                                                   | Kimenet                                                               | Értelmezés/gate                                                                                                    |
| ------------------------------ | ------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------ |
| A01 **PLANNED / NOT EXECUTED** | Inventory: stock_balances            | quantity<0 exact decimal; include all locations/batches                                                                         | PK+negative q, snapshot baseline                                      | Nem javítandó kézi zero-ra; issue/correction/source audit                                                          |
| A02 **PLANNED / NOT EXECUTED** | Inventory: balance identity          | GROUP BY item/location/batch NULL-safe, HAVING count>1; deleted mastert is jelöld                                               | Identity, PK-k, q-k és movement lineage                               | Nem SUM+DELETE; normalized key mapping előtt blocker                                                               |
| A03 **PLANNED / NOT EXECUTED** | Inventory: NULL batch                | A02 külön NULL-batch cohort, sentinel collision és master batch item mismatch; future sentinel nem existing oszlop              | NULL vs real batch provenance, q és references                        | Nullable U nem bizonyít egyetlen sort; sentinel értékütközés rendezendő                                            |
| A04 **PLANNED / NOT EXECUTED** | Inventory: movement/balance          | Azonos cut-off opening baseline + valid type/irány fold vs balance, transfer két location, reserve/release0                     | Exact expected/observed q és unresolved type/source                   | Unknown opening/sign esetén INDETERMINATE, nem falsified correction                                                |
| A05 **PLANNED / NOT EXECUTED** | Inventory: reservation orphan        | LEFT JOIN item/location/batch/customer-item/PO with deleted flags, missing required és cross-item batch                         | Reservation PK/owner/location/source                                  | Fizikai vagy owner nélküli sor nem válik szabad készletté                                                          |
| A06 **PLANNED / NOT EXECUTED** | Inventory: over-allocation           | Active saját/összes reserve vs eligible pool by item/location/batch, nincs proposed E mezőre futó current query                 | Raw active full sum + verified eligibility/counter evidence           | Eligible legacy state unknown: nem állítunk pass-t full physicalból                                                |
| A07 **PLANNED / NOT EXECUTED** | Inventory: reservation owner         | Mindkét legacy owner NULL, PO customer-item mismatch, soft-deleted active; consumed/released q reconstructibility proof         | Owner tuple + evidence sufficiency                                    | Current kettős PO+customer ref nem önmagában hiba; új XOR a megfelelő owner-domainre, nem e két legacy oszlop közt |
| A08 **PLANNED / NOT EXECUTED** | Inventory: missing movement source   | Nullable source_type/source_id párok és approved morph registry szerint subject existence, posted GR/material reverse join      | Movement PK, serial és original source                                | Generic morph nem FK; missing link unknown, nem automatikus owner remap                                            |
| A09 **PLANNED / NOT EXECUTED** | Inventory: serial uniqueness         | Exact és normalizált/collation szerinti serial grouping + blank/null; deployed unique/index drift ellenőrzés                    | Serial identity PK-lista, case/space eltérés                          | Current serial U nem helyettesít actual catalogot és physical serial reconciliationt                               |
| A10 **PLANNED / NOT EXECUTED** | Inventory: instance genealogy        | Parent/child existence, self/cycle, relation item+qty és production origin egyezés                                              | Edge PK-k, cycle path, candidate source                               | Nem törlünk/összevonunk genealogyt; original és replacement identity külön                                         |
| A11 **PLANNED / NOT EXECUTED** | Inventory: invalid masters           | Balance/reserve/move instance és location/item/batch joins, inactive/soft-deleted flags külön                                   | Missing vs inactive vs historical-valid references                    | Soft-delete nem orphan automatikusan; történeti place ne nullázódjon                                               |
| A12 **PLANNED / NOT EXECUTED** | Production: requirement owner        | MR customer-item és required-item joins incl deleted, unit/item és PO linkage proof                                             | Owner+PK+source dependency list                                       | Repair ID sosem legacy customer-item; unknown mapping review                                                       |
| A13 **PLANNED / NOT EXECUTED** | Production: több PO                  | GROUP BY customer_order_item több PO, item/BOM/qty/status és current requirement közös kulcs összevetés                         | PO-lista, expected aggregate candidate és ambiguity                   | Nem első/utolsó PO kiválasztás; tényleges authorized need policy/proof kell                                        |
| A14 **PLANNED / NOT EXECUTED** | Production: BOM identity             | PO bom_id/item/sequence vs bom_items és required item/unit; version/source history                                              | PO/BOM/step/req mismatch tuples                                       | Historical PO+BOM key csak bizonyított mapping után                                                                |
| A15 **PLANNED / NOT EXECUTED** | Production: duplicate requirement    | Customer-item+required-item GROUP incl soft-delete, source/PR függések teljes listája                                           | PK és before q snapshotok                                             | Nincs current U; no ID merge/delete, source lineage preservation szükséges                                         |
| A16 **PLANNED / NOT EXECUTED** | Production: actual consume           | production_task_materials reverse StockMovement source_type/source_id, type/item/batch/place/q, one effective actual occurrence | Missing/duplicate/mismatch material-move és physical q                | Current nincs stored movement_id; reverse morph exact subject, nem notes-match                                     |
| A17 **PLANNED / NOT EXECUTED** | Production: finished obligations     | Finished tasks/PO és material/reserve/requirement/procurement history összevetés                                                | Open reservation/demand/commitment és actual C                        | Statusból nem állítunk fulfilled stockot vagy autorizált cleanupot                                                 |
| A18 **PLANNED / NOT EXECUTED** | Procurement: PR requirement lineage  | PRitems direct nullable MR + source hasMany, manual vs generated source record, deleted flags                                   | Line/direct/source owner list és unknown/manual classification        | Manual source-less nem automatikus invalid, generated proof hiánya review                                          |
| A19 **PLANNED / NOT EXECUTED** | Procurement: duplicate sources       | GROUP PRitem+requirement és planned full occurrence (ha később létezik), actual unique catalog                                  | PK-k és quantity/hash/provenance                                      | Multi PRline/generation engedett; nem téves U(req,gen) audit                                                       |
| A20 **PLANNED / NOT EXECUTED** | Procurement: source quantity         | SUM initial sources by PRline vs line initial qty/unit; actual current coverage később külön fold                               | Snapshot total és effective open state külön                          | Történeti initial vs current delta eltérés nem automatikus bad source; baseline evidence kell                      |
| A21 **PLANNED / NOT EXECUTED** | Procurement: PO PR links             | PO header PR vs POitem PRitem parent/item/unit, optional manual lineage és deleted refs                                         | Typed mismatch plus manual/unknown exceptions                         | Nullable manual reference nem fictional source; exact owner allocation később                                      |
| A22 **PLANNED / NOT EXECUTED** | Procurement: partial GR              | Posted GR/GRitems POitem szerint, residual ordered-received és active source assignments                                        | Partial/overreceipt/remaining q, unassigned portion                   | Allocation priority OPEN, nem automatikus one-owner teljes receipt                                                 |
| A23 **PLANNED / NOT EXECUTED** | Procurement: received reconciliation | POitem received_quantity vs posted GRitems és purchase_receive movements; GR source jelenleg header, több line lehet            | POitem/GRitem distribution és header movement matching                | Nem egyszerű header JOIN fanout; ambiguity esetén UNVERIFIED, GR-item link nincs kitalálva                         |
| A24 **PLANNED / NOT EXECUTED** | Procurement: closed received status  | PO Received/closed-szerű státusz és actual posted GR/movement hiány vagy residual                                               | Header/items/status és actual receipt evidence                        | PO close kódút nem actual receipt; nem generálunk fake GR-t                                                        |
| A25 **PLANNED / NOT EXECUTED** | Procurement: supplier commitments    | Current approved/ordered PO list + külső sent/ack/cancel proof authority audit, unknown fulfillment külön                       | Firm/intent/unknown, qty/date és responsibility                       | Status/expected date nem bizonyít firm commitment; actual external evidence külön szükséges                        |
| A26 **PLANNED / NOT EXECUTED** | Procurement: soft-deleted deps       | with-deleted PR/PO/items/GR/req és live source/receipt/obligation dependenciák                                                  | PK-dependency graph és active effect                                  | Ne rejtse global scope az élő supplier commitmentet, ne hard delete legyen remediation                             |
| A27 **PLANNED / NOT EXECUTED** | Eligibility: own manufacture         | Item type/serial-required flags + actual PO/task/sequence/QC origin evidence                                                    | Candidate/verified/unknown eligibility profile                        | finished/manufactured/semi_finished type önmagában nem own-manufactured proof                                      |
| A28 **PLANNED / NOT EXECUTED** | Eligibility: serial origin           | Instance production_order nullable IX-only oszlop exact FK existence/item/task serial és completion                             | Missing/mismatched origin és physical serial proof                    | Nincs fabricated origin/event vagy serial regeneration                                                             |
| A29 **PLANNED / NOT EXECUTED** | Eligibility: owner/delivery          | Customer order kapcsolatok és elérhető dokumentum/verzió actual delivery/owner evidence külön                                   | Reporter/order-party vs proven owner, actual handover vs planned date | Missing historical shipment/ownership unknown, current order relation nem jogi tény                                |
| A30 **PLANNED / NOT EXECUTED** | Eligibility: warranty                | Approved applicable rule/version és igazolt handover/serial original/replacement evidence                                       | Subject/rule/source/date/reviewer proof completeness                  | Nincs backdated warranty start, automatikus reset vagy fake handover                                               |

### 3.2. Készletreconciliation és RMA eligibility

StockMovement az autoritatív fizikai ledger, de legacy opening baseline és type/sign mapping nélkül
nem állítható, hogy balance=SUM(quantity). `purchase_receive`, `production_output` bejövő,
`production_consume` kimenő; `transfer` két helyhatás, globálisan nettó0; reservation/release
nem fizikai delta. `production_issue`, `correction`, scrap és hiányzó irány/source pontos
történeti használata bizonyítandó. Nyers type-név vagy notes nem elég sign/owner/admission mappingre.
Külön physical, company, custody/pending, quarantine és on-site nézet; ne vonjuk le ugyanazt kétszer.
Nincs ismeretlen delta correctionné alakítása, nincs nullable-batch sorok önkényes SUM+DELETE összevonása.

Eligibility jelölt nem automatikusan RMA-eligible. D2: own manufacture + egyedi serial + bizonyított
production-order/item/task/sequence/QC eredet, `semi_finished_product` esetén is. D3: owner evidence
külön a reportertől. D4/D5: tervezett delivery-date nem igazolt shipment/handover vagy ownership,
replacement nem warranty reset. Hiányzó dokumentum/origin/owner/serial bizonyíték **UNKNOWN**,
nem generált történeti event. Fizikailag ismeretlen visszaérkezés WP4 discrepancy+őrzött karantén,
nem hamisított member/intake/admission; formális AM-02-G1 előfeltételek megmaradnak.

## 4. Current/historical MRP compatibility

### 4.1. Pinned történeti forrás és authority

Lokális `feature/material-requirements-planning` elérhető a
`379be484405a789388df5f5e8367a18b1e598bbc` commiton. Kizárólag `git ls-tree`/`git show`
Git-object olvasás; nincs fetch vagy branchváltás. A HISTORICAL utak nem current relatív linkek.
A történeti ADR-0006 MRP más fájl és más döntés, mint a current ADR-0006 RMA.

- HISTORICAL `.kiro/decisions/0006-material-requirements-planning-architecture.md`.
- HISTORICAL `.kiro/decisions/0007-item-supplier-procurement-source.md`.
- HISTORICAL `.kiro/decisions/0008-5-mrp-foundation-hardening.md`.
- HISTORICAL `.kiro/decisions/0008-supply-proposal.md`.
- HISTORICAL `.kiro/decisions/0009-material-requirement-netting.md`.
- HISTORICAL `.kiro/decisions/0010-requirement-pegging.md`.
- HISTORICAL `.kiro/decisions/0011-purchase-requisition-consolidation.md`.
- HISTORICAL `.kiro/decisions/0012-supplier-selection.md`.
- HISTORICAL `.kiro/decisions/0013-replenishment-strategies.md`.
- HISTORICAL `.kiro/decisions/0014-purchase-requisition-execution-readiness.md`.
- HISTORICAL `.kiro/decisions/0015-purchase-order-generation.md`.
- HISTORICAL `.kiro/decisions/0016-purchase-order-dispatch-supplier-acknowledgement.md`.

**HISTORICAL implementációs utak**, ugyanazon pinned commiton:
`app/Services/Admin/SupplyProposalService.php`, `MaterialRequirementNettingService.php`,
`MaterialRequirementPeggingService.php`, `PurchaseRequisitionConsolidationService.php`,
`SupplierSelectionService.php`, `PurchaseRequisitionReplenishmentService.php`,
`PurchaseRequisitionExecutionReadinessService.php`, `PurchaseRequisitionService.php`,
`PurchaseOrderDispatchService.php`, `SupplierAcknowledgementService.php`.
Az abbreviated service-nevek mind az `app/Services/Admin/` alatti utak; nincs current import.
ADR-0016 deklarált „implementációra vár” fejléce és a megtalált dispatch/ack források
nem helyettesítik a release-/runtime-bizonyítékot. A WP5 2.2 részletes fájlleltára megmarad.

### 4.2. Requirement identity és target-baseline alternatívák

| Opció – PROPOSED, választás OPEN                            | Identity/source/trace kezelés                                                                                                                     | Következmény és rollout kapu                                                                                                                                          |
| ----------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| O1 Current RMA baseline megtartása                          | Legacy customer-item+cikk aggregate stable ID; több PO authorized demand összegzése, repair-order+cikk XOR külön; existing source FK nem változik | Legkisebb current footprint, de WP3/WP5 shared consumer/coverage hardening így is kötelező; történeti képességek nem jelennek meg automatikusan                       |
| O2 Történeti procurement tudatos beemelésének későbbi terve | Production-order+BOM-item planning identity és külön Proposal-source/peg; legacy aggregate és repair-owner typed adapter/lineage                  | Projektgazdai scope/ADR és target release döntés, WP2 pontos constraints, WP7 kompatibilitási proof; nem mostani merge vagy új párhuzamos generátor                   |
| O3 Explicit coexistence átmeneti adapterrel                 | Régi source anchor az eredeti requirement ID-n marad; új planning identityk csak bizonyított mappinggel, origin és target lineage külön           | Több-PO many-to-many mapping/conservation/ownermutex, egyetlen authoritative procurement execution; magasabb fenntartási kockázat, időben és exit-gate-tel korlátozva |

**Javasolt döntési sorrend:** projektgazda rögzítse célbaseline-t és identity-adaptert a backfill
vagy FK-contract előtt; addig O1 alapján csak összehasonlító terv, nem végleges adoption-döntés.
Nem futtatjuk rá vakon a historical hardening migrationt current adatokra. Egy legacy aggregate
több PO/BOM sorhoz tartozhat, ezért önkényes első PO/BOM vagy új FK ID-csere elvesztené a jelentést.
Mappingnek őriznie kell legacy PK, source/PR/PO/GR hivatkozások, owner, item/unit, cut-off és evidence
teljes láncát. Ambiguous/unmapped record elkülönített review, nem átnevezett owner vagy fiktív BOM.
Az új ID nem lehet régi ID helyére csendben visszaírt másik üzleti entitás.

### 4.3. Quantity és serialization határok

| Boundary                                     | CONFIRMED CURRENT / HISTORICAL                                                                             | PROPOSED átállási feltétel                                                                                                                   |
| -------------------------------------------- | ---------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------- |
| BOM item × production order → requirement    | Current BOM és PO quantity12,3; requirement négy qty12,3; service/repository float                         | Szorzás intermediate scale/range külön; aggregate max és rounding policy review; exact arithmetic végig, nem csak szélesebb tárolás          |
| Requirement→source→PR                        | Current mind12,3; historical requirement és legacy source továbbra12,3, Proposal/source és PR18,3          | Typed adapter range/conservation, nincs silent truncation/narrowing                                                                          |
| Proposal/replenishment→requested PR          | Historical proposed/planned/requested/excess18,3, full Proposal consumption és U(proposal)                 | MOQ/excess nem repair demand; requirement multi-source jóváhagyás nem partial Proposal approval                                              |
| PR→PO commitment                             | Current PO ordered/received12,3; historical PR18,3 mellett PO snapshots18,3, de ordered/received12,3 marad | Nincs end-to-end18,3 állítás; overflow guard vagy jóváhagyott teljes widening, casting előtt range check                                     |
| Supplier ack→PO/GR                           | Historical promised/snapshot18,3; current nincs ack module                                                 | Ack nem GR, qty compatibility és source proof, őrzött sent/ack/current authority                                                             |
| GR→movement/balance/reservation              | Current quantity/reserved12,3; float service/SUM és partial counter hiány                                  | GR+coverage+movement+projection egy txn, exact q és owner allocation cap, remainder nem lost                                                 |
| Material actual consume→requirement          | Production materials planned/used12,3, genealogy relation12,3; repair PROPOSED                             | Actual C és remaining counter külön; serial qty1, sem rounding sem float nem tehet el több/másik egységet                                    |
| Domain quantity→API/Inertia/Vue/report/cache | Models decimal:3 string; balance repo derived fields float; Vue string-or-number és display formatter      | Canonical decimal string/base-unit API és versioned cache payload tervezendő, JS Number nem authority; megjelenítési kerekítés nem ír vissza |
| Money/credit                                 | WP1/WP5 signed BIGINT units+scale0..6 javaslat; nem current RMA oszlop                                     | Currency/scale/cap és checked integer sum, nincs float/FX/implicit tax policy; nem quantity width reuse                                      |

decimal(12,3) abszolút felső határ 999999999.999; decimal(18,3) 999999999999999.999.
Több rekord SUM és szorzat ezeken is túlcsordulhat. Egyedi mező range nem aggregate proof.
Minden bemeneti request, DTO/service, DB write/read, fold/SUM, JSON/queue/cache, UI/export és külső
adapter ugyanahhoz az item/base-unit/scale-hez tartozzon; conversion factor intermediate külön.
Negatív magnitude és counter nincs automatikusan engedve signed tárolástól. Túlprecíz input
ne csendesen kerekedjen; konkrét rounding/overflow policy **OPEN**. ID-k JS biztonságos integer
határát is vizsgálni kell a quantitytől külön. SQLite decimal affinity/unsigned/DDL rebuild/FK-on
eltérése nem MySQL strict-mode, precision, row-lock vagy principal bizonyíték.

### 4.4. Procurement authority

Current requirement-driven PR generator `missing>0 && no sources`, item+unit consolidation;
PO-generálás kézi supplier inputból; PO státusz nem sent/ack. HISTORICAL Proposal approval/master
revalidation → full Proposal consolidation/külön source → közös PR header supplier → explicit
replenishment → fresh readiness → egy teljes PR-ből egy PO execution snapshot → külön dispatch/ack.
Historical peg planning trace, nem physical reservation vagy WP5 coverage. GR actual posting mindkét
baseline-nál külön supply fact; a historical megléte sem bizonyít WP5 owner coverage integrationt.

WP5 szerződés: demand D, actual C, saját eligible remaining R, internal intent I, firm K,
actual G és eligible Ge külön. Source initial snapshot nem még egy +q a ledger mellé;
receipt kivonja a received részt open coverage-ből, physical supply egyszer jelenik meg.
REV-02 engedi a több line/generationt; generation-head singleton és source-line occurrence U
külön védelmet igényel, nincs source-on U(requirement,generation). Régi forrás törlése vagy fake
generation bump nem delta. Ismeretlen commitmentet owner review alá kell venni új vásárlás előtt.
Célbaseline, mapping és precision a projektgazda döntése; egyik alternatíva sem párhuzamos procurement.

## 5. Migration rollout stages

Az A–F minden sora **PROPOSED / NOT EXECUTED**; nem ad végrehajtási engedélyt.
Nincs automatikus továbbhaladás. Belépés/exit evidence-t az adott release owner és DBA ellenőrzi,
a projektgazda jóváhagyja az üzleti scope-ot. Ismeretlen topológia/RPO/RTO adatot nem találunk ki.

| Stage                  | Tevékenység és felelős                                                                                                                   | Belépés és exit gate                                                                                                                                              | Író/olvasó/gate határ                                                                                                                                  |
| ---------------------- | ---------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------ |
| A Preparation          | Release owner+DBA+domain owner: target baseline, catalog/grant/consumer audit, backup+file manifest, restore rehearsal és audit Axx      | Reprezentatív snapshot; WP2 spec és WP7 plán; minden mapping-rizikó felelőssel, recovery capability bizonyított; actual data audit nélkül következő stage BLOCKED | RMA gate OFF; sem production írás, sem adatjavítás e dokumentumból nem következik                                                                      |
| B Additive schema      | DBA+migration principal: 25-jelöltből véglegesített dependency-rend; nullable compatibility mezők/indexek, csak igazoltan biztonságos FK | WP2 executable DDL, rehearsal és online/lock/disk terv; migration journal+catalog fingerprint minden lépésre, routine/grant készítése csak review után            | Sem unverified company/eligible/settled default, sem azonnali NOT NULL/U legacy eltérésre; gate OFF                                                    |
| C Controlled alignment | Domain owner+DBA: verified mapping, legacy reserve/stock/coverage baseline, precision alignment                                          | Axx eltérések rendezése külön engedéllyel; resumable chunks/cut-off/delta-capture vagy write-freeze; count/hash/quantity/owner proof, no orphan/no partial budget | Egy authoritative posting út; backfill nem gyárt régi ownership/warranty/delivery proofot; ismeretlen sor review/blocked                               |
| D Dual compatibility   | Release owner: compatible reader/writer version minden node/worker/BI és shadow read comparison                                          | Inventory/PR/PO/GR/production/report/cache összes WP3 consumer coverage; régi query nem láthat hibás eligible/remaining supplyt; grants/principal negatív proof   | Régi scope-vak writer lezárva/revokálva/drained; read-only fallback is lehet unsafe, ezért explicit view/adapter; unrestricted dual-write nem javasolt |
| E Feature activation   | Release owner+domain operators: szerveroldali gate/permission canary, ellenőrzött felhasználók és figyelés                               | Minden BLOCKER és releváns HIGH feloldva tényleges proofval; mixed-version és restore rehearsal PASS; owner külön go/no-go                                        | Guarded routes/service/queue/finalizer, feature epoch; audit/reconciliation figyelés, rollback trigger → controlled freeze                             |
| F Legacy retirement    | Projektgazda+DBA: régi writer/adapter későbbi kivezetése                                                                                 | Megfigyelési idő és elfogadási küszöb OPEN; minden dependency és history-access igazolt, nincs régi job/client                                                    | Destructive cleanup külön explicit engedély, nem e terv automatikus része; új valid history nem dropolható                                             |

### 5.1. Alignment és dual compatibility protokoll

Schema-expand és adat-alignment külön checkpoint. Új required mező értéke lehet átmenetileg
NULL csak documented compatibility shape mellett; nem production runtime bypass. Új U/NOT NULL/FK
csak teljes audit/backfill+race barrier után. Nem az online scanner utolsó batch-e zárja le az új
duplikátum lehetőségét: az összes writer ugyanazt a constraint/transaction contractot kell használja.
Migration marker önmagában nem bizonyítja a DDL lefutását; object/constraint/grant fingerprint is kell.

PROPOSED alignment manifest: source PK/cut-off/hash, target identity és mapping proof, operation
key, transformation version, before/after exact quantity/unit, batch checkpoint, operator+approval,
exceptions és reversal/forward-fix hivatkozás. Ez deploy artifact specifikáció, nem új 26. tábla vagy
most létrehozott fájl. Retry ugyanarra a mappingre ugyanazt az eredményt adja; eltérő input/hash review.
Korábbi source.quantity snapshotból nem vezetünk le teljes aktuális open coverage-t vagy sent/ack-et:
PR/PO/GR és unresolved obligation audit kell; a baseline egyszer számít, nem plusz synthetic shipment.

**Ajánlott compatibility:** egy writer írja a tényt és derived projectiont ugyanazon DB transactionben;
shadow reader összehasonlít, nem postingol. Ha később átmeneti két projekció szükséges:
authority az egy command/posting service, boundary fact+old/new projection+history+audit egy txn;
bármelyik hibánál teljes rollback; stable command/payload hash, retry nem második event;
reconciliation azonos cut-offon fold+owner+quantity, exit minden consumer az új contracton és
zero unresolved mismatch. Két külön service/DB/process eventual dual-write nem elfogadott shortcut;
külön durable delivery/reconciliation terv nélkül BLOCKED. Nem engedjük, hogy régi writer csak
az egyik alakot módosítsa. Régi partial-consume jelentés nem mindig reprezentálható: ilyenkor
régi API blokkolása kell, nem csak read-adapter vagy feature flag.

## 6. Inventory and procurement compatibility

### 6.1. Együtt aktiválandó szerződések

WP3 **E/R/P/M/Q/C/L** együtt: eligibility, reservation remaining, actual production/repair consume,
material demand/netting, procurement, cache és közös lock sorrend. Normál company physical balance
nem azonos eligible availabilityvel. Company quarantine physical qty szerepelhet a company
projekcióban, de E szerint nem szabad; custody/pending intake company hatása0, on-site egyszer1.
`remaining = reserved_quantity - consumed_quantity - released_quantity`,
`0 <= consumed_quantity + released_quantity <= reserved_quantity`.
Logical replacement obligation és workflow custody reserve nem új normál készletlevonás.

WP4 intake I/MI és admission A/MA külön identity/posting/occurrence: admission nem második physical
receipt, karanténfeloldás nem admission. Original és replacement serial/lifecycle külön;
physical shipment és legal transfer külön idő/proof. F1 előkészítés nem fizikai kiadás;
F2 case-close csak restricted proof-finalizer; F3 páros history, F4 scope/approval nem írható vissza.
WP5 intent/firm/receipt/consumption külön, nem reservation=consumption vagy received enum=GR.

### 6.2. WP3 összes consumerének deployment-besorolása

Az alábbi diszjunkt módszerleltár a megőrzött WP3 14. fejezetének **mind a 282 metódusát**
source-path és method szerint csoportosítja; a csoportosítás nem implementation proof.
Az eredeti overlapping szerepszámok: 79 writer, 209 reader, 114 Reporting/Cache (nem összeadandók).
Minden felsorolt metódusra vonatkozik a WP3 sorának saját E/R/P/M/Q/C/L és negatív tesztkövetelménye.
Azonos nevű interface vagy örökölt docblock nem új consumer. GINV/GPROC/GEXEC/GREAD/GCAP/GDOC/GHTTP
**PROPOSED deployment cohort neve**, nem létező permission/config flag vagy elfogadott új üzleti szabály.

| Current source / method inventory                                                                                                                                                                                                                                                                                                                                                                                                                                                                     | Darab | PROPOSED cohort és gate                                  |
| ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----- | -------------------------------------------------------- |
| [StockBalanceRepository](../../app/Repositories/Admin/StockBalanceRepository.php)::`paginateForAdminIndex`, `totalQuantityForItem`, `increaseQuantity`, `balancesForReservation`                                                                                                                                                                                                                                                                                                                      | 4     | GINV/E/R/M/L: pool/owner/counter                         |
| [StockMovementRepository](../../app/Repositories/Admin/StockMovementRepository.php)::`paginateForAdminIndex`                                                                                                                                                                                                                                                                                                                                                                                          | 1     | GINV/E/R/M/L: pool/owner/counter                         |
| [StockReservationRepository](../../app/Repositories/Admin/StockReservationRepository.php)::`paginateForAdminIndex`, `activeReservedQuantity`, `activeReservedQuantityForBalance`, `createReservation`, `release`, `activeForProductionOrderItem`                                                                                                                                                                                                                                                      | 6     | GINV/E/R/M/L: pool/owner/counter                         |
| [MaterialRequirementRepository](../../app/Repositories/Admin/MaterialRequirementRepository.php)::`paginateForAdminIndex`, `paginateShortages`, `filteredQuery`, `updateForProductionOrderBomItem`                                                                                                                                                                                                                                                                                                     | 4     | GINV/E/R/M/L: pool/owner/counter                         |
| [PurchaseRequisitionRepository](../../app/Repositories/Admin/PurchaseRequisitionRepository.php)::`paginateForAdminIndex`, `findForShow`, `missingMaterialRequirements`                                                                                                                                                                                                                                                                                                                                | 3     | GPROC/Q/L: source/commitment/receipt                     |
| [PurchaseOrderRepository](../../app/Repositories/Admin/PurchaseOrderRepository.php)::`paginateForAdminIndex`, `findForShow`                                                                                                                                                                                                                                                                                                                                                                           | 2     | GPROC/Q/L: source/commitment/receipt                     |
| [GoodsReceiptRepository](../../app/Repositories/Admin/GoodsReceiptRepository.php)::`paginateForAdminIndex`, `findForShow`                                                                                                                                                                                                                                                                                                                                                                             | 2     | GPROC/Q/L: source/commitment/receipt                     |
| [ProductionTaskRepository](../../app/Repositories/Admin/ProductionTaskRepository.php)::`paginateForExecution`, `findForShow`, `readyAndActiveForShopFloor`, `executionQuery`, `applyFilters`                                                                                                                                                                                                                                                                                                          | 5     | GEXEC/P/L: actual execution/trace                        |
| [ProductionPlanRepository](../../app/Repositories/Admin/ProductionPlanRepository.php)::`paginateForAdminIndex`, `createWithItems`, `updateWithItems`, `findForShow`, `approve`, `generateProductionOrders`, `nextPlanNumber`, `nextProductionOrderNumber`                                                                                                                                                                                                                                             | 8     | GEXEC/P/L: actual execution/trace                        |
| [ReportingRepository](../../app/Repositories/Admin/ReportingRepository.php)::`dashboardSummary`, `customerOrdersSummary`, `productionSummary`, `inventorySummary`, `procurementSummary`, `qualitySummary`, `shopFloorSummary`, `countsByStatus`                                                                                                                                                                                                                                                       | 8     | GREAD/C: source/meaning/cache együtt                     |
| [ManufacturingIntelligenceRepository](../../app/Repositories/Admin/ManufacturingIntelligenceRepository.php)::`bottlenecks`, `materialForecast`, `supplierPerformance`, `qualityTrends`, `productionRisks`, `procurementRecommendations`, `leadTimeAccuracy`                                                                                                                                                                                                                                           | 7     | GREAD/C: source/meaning/cache együtt                     |
| [CapacityRepository](../../app/Repositories/Admin/CapacityRepository.php)::`factoryUnitLoads`, `employeeLoads`, `scheduleRows`, `productionOrderOptions`, `customerOrderOptions`, `tasksForProductionOrder`, `factoryCalendarFor`, `nextFactoryReservationOverlap`, `factoryReservationsForHorizon`, `createOrUpdateReservation`, `productionOrdersForCustomerOrder`, `reservationCount`                                                                                                              | 12    | GCAP: serial/production/procurement read compatibility   |
| [AbstractAdminRepository](../../app/Repositories/Admin/AbstractAdminRepository.php)::`paginateForAdminIndex`, `create`, `update`, `delete`, `query`                                                                                                                                                                                                                                                                                                                                                   | 5     | GINV+GPROC+GEXEC: shared CRUD, inherited callers szerint |
| [StockReservationService](../../app/Services/Admin/StockReservationService.php)::`reserveForProductionOrder`, `release`, `availableOnBalance`                                                                                                                                                                                                                                                                                                                                                         | 3     | GINV/E/R/M/L: pool/owner/counter                         |
| [MaterialRequirementService](../../app/Services/Admin/MaterialRequirementService.php)::`calculateForProductionOrder`, `calculateBomItem`                                                                                                                                                                                                                                                                                                                                                              | 2     | GINV/E/R/M/L: pool/owner/counter                         |
| [ProductionTaskMaterialService](../../app/Services/Admin/ProductionTaskMaterialService.php)::`store`, `reservation`, `balanceForConsumption`                                                                                                                                                                                                                                                                                                                                                          | 3     | GEXEC/P/L: actual execution/trace                        |
| [PurchaseRequisitionService](../../app/Services/Admin/PurchaseRequisitionService.php)::`paginateForAdminIndex`, `findForShow`, `create`, `update`, `delete`, `approve`, `generateFromMaterialRequirements`, `generatePurchaseOrder`, `nextRequisitionNumber`, `nextPurchaseOrderNumber`                                                                                                                                                                                                               | 10    | GPROC/Q/L: source/commitment/receipt                     |
| [PurchaseOrderService](../../app/Services/Admin/PurchaseOrderService.php)::`paginateForAdminIndex`, `findForShow`, `create`, `update`, `delete`, `approve`, `close`, `nextPurchaseOrderNumber`                                                                                                                                                                                                                                                                                                        | 8     | GPROC/Q/L: source/commitment/receipt                     |
| [GoodsReceiptService](../../app/Services/Admin/GoodsReceiptService.php)::`paginateForAdminIndex`, `findForShow`, `create`, `post`, `updatePurchaseOrderItemReceivedQuantity`, `refreshPurchaseOrderStatus`, `nextReceiptNumber`                                                                                                                                                                                                                                                                       | 7     | GPROC/Q/L: source/commitment/receipt                     |
| [ProductionTaskService](../../app/Services/Admin/ProductionTaskService.php)::`paginateForAdminIndex`, `findForShow`, `create`, `update`, `delete`, `generateFromOrder`, `start`, `finish`, `advanceNextTaskOrFinalize`, `completeProductionOrderWhenReady`, `nextSerialNumber`                                                                                                                                                                                                                        | 11    | GEXEC/P/L: actual execution/trace                        |
| [ProductionPlanService](../../app/Services/Admin/ProductionPlanService.php)::`paginateForAdminIndex`, `findForShow`, `create`, `update`, `approve`, `generateProductionOrders`, `delete`                                                                                                                                                                                                                                                                                                              | 7     | GEXEC/P/L: actual execution/trace                        |
| [QualityCheckService](../../app/Services/Admin/QualityCheckService.php)::`store`                                                                                                                                                                                                                                                                                                                                                                                                                      | 1     | GEXEC/P/L: actual execution/trace                        |
| [InventoryQueryService](../../app/Services/Admin/InventoryQueryService.php)::`paginateStockBalances`, `paginateStockMovements`, `paginateStockReservations`, `paginateMaterialRequirements`, `paginateShortages`                                                                                                                                                                                                                                                                                      | 5     | GINV/E/R/M/L: pool/owner/counter                         |
| [ProcurementDashboardService](../../app/Services/Admin/ProcurementDashboardService.php)::`metrics`, `topMissingMaterials`                                                                                                                                                                                                                                                                                                                                                                             | 2     | GREAD/C: source/meaning/cache együtt                     |
| [ReportingService](../../app/Services/Admin/ReportingService.php)::`customerOrdersSummary`, `productionSummary`, `inventorySummary`, `procurementSummary`, `qualitySummary`, `shopFloorSummary`                                                                                                                                                                                                                                                                                                       | 6     | GREAD/C: source/meaning/cache együtt                     |
| [ManufacturingIntelligenceService](../../app/Services/Admin/ManufacturingIntelligenceService.php)::`dashboard`                                                                                                                                                                                                                                                                                                                                                                                        | 1     | GREAD/C: source/meaning/cache együtt                     |
| [DashboardService](../../app/Services/Admin/DashboardService.php)::`summary`                                                                                                                                                                                                                                                                                                                                                                                                                          | 1     | GREAD/C: source/meaning/cache együtt                     |
| [MaterialForecastService](../../app/Services/Admin/MaterialForecastService.php)::`forecast`                                                                                                                                                                                                                                                                                                                                                                                                           | 1     | GREAD/C: source/meaning/cache együtt                     |
| [ProcurementRecommendationService](../../app/Services/Admin/ProcurementRecommendationService.php)::`recommendations`                                                                                                                                                                                                                                                                                                                                                                                  | 1     | GREAD/C: source/meaning/cache együtt                     |
| [ProductionRiskService](../../app/Services/Admin/ProductionRiskService.php)::`score`                                                                                                                                                                                                                                                                                                                                                                                                                  | 1     | GREAD/C: source/meaning/cache együtt                     |
| [BottleneckAnalysisService](../../app/Services/Admin/BottleneckAnalysisService.php)::`analyze`                                                                                                                                                                                                                                                                                                                                                                                                        | 1     | GREAD/C: source/meaning/cache együtt                     |
| [SupplierPerformanceService](../../app/Services/Admin/SupplierPerformanceService.php)::`analyze`                                                                                                                                                                                                                                                                                                                                                                                                      | 1     | GREAD/C: source/meaning/cache együtt                     |
| [QualityTrendService](../../app/Services/Admin/QualityTrendService.php)::`analyze`                                                                                                                                                                                                                                                                                                                                                                                                                    | 1     | GREAD/C: source/meaning/cache együtt                     |
| [CapacityPlanningService](../../app/Services/Admin/CapacityPlanningService.php)::`dashboard`, `factoryUnitLoads`, `employeeLoads`, `scheduleRows`, `productionOrderOptions`, `customerOrderOptions`, `forgetCapacityCache`, `ordersLikelyToMissDeadline`                                                                                                                                                                                                                                              | 8     | GCAP: serial/production/procurement read compatibility   |
| [SchedulingService](../../app/Services/Admin/SchedulingService.php)::`schedule`                                                                                                                                                                                                                                                                                                                                                                                                                       | 1     | GCAP: serial/production/procurement read compatibility   |
| [LeadTimeEstimator](../../app/Services/Admin/LeadTimeEstimator.php)::`estimate`, `tasksForProductionOrders`                                                                                                                                                                                                                                                                                                                                                                                           | 2     | GCAP: serial/production/procurement read compatibility   |
| [ShopFloorService](../../app/Services/Admin/ShopFloorService.php)::`tasks`, `myTasks`                                                                                                                                                                                                                                                                                                                                                                                                                 | 2     | GEXEC/P/L: actual execution/trace                        |
| [AbstractAdminService](../../app/Services/Admin/AbstractAdminService.php)::`paginateForAdminIndex`, `create`, `update`, `delete`                                                                                                                                                                                                                                                                                                                                                                      | 4     | GINV+GPROC+GEXEC: shared CRUD, inherited callers szerint |
| [ItemAdminService](../../app/Services/Admin/ItemAdminService.php)::`orderableOptions`, `afterWrite`                                                                                                                                                                                                                                                                                                                                                                                                   | 2     | GINV/E/R/M/L: pool/owner/counter                         |
| [LocationAdminService](../../app/Services/Admin/LocationAdminService.php)::`afterWrite`                                                                                                                                                                                                                                                                                                                                                                                                               | 1     | GINV/E/R/M/L: pool/owner/counter                         |
| [BusinessCacheInvalidator](../../app/Services/BusinessCacheInvalidator.php)::`customersChanged`, `suppliersChanged`, `operationTypesChanged`, `customerOrdersChanged`, `productionChanged`, `inventoryChanged`, `procurementChanged`, `qualityChanged`, `capacityChanged`, `workforceChanged`, `documentsChanged`, `invalidate`, `invalidateNow`                                                                                                                                                      | 13    | GREAD/C: source/meaning/cache együtt                     |
| [BusinessCacheKey](../../app/Support/Cache/BusinessCacheKey.php)::`dashboardSummary`, `customerOrdersReport`, `productionReport`, `inventoryReport`, `procurementReport`, `qualityReport`, `shopFloorReport`, `intelligenceDashboard`, `bottleneckAnalysis`, `materialForecast`, `supplierPerformance`, `qualityTrends`, `productionRisks`, `procurementRecommendations`, `capacityDashboard`, `capacityFactoryUnits`, `capacityEmployees`, `capacitySchedule`, `make`, `generationKey`, `generation` | 21    | GREAD/C: source/meaning/cache együtt                     |
| [GoodsReceiptController](../../app/Http/Controllers/Admin/GoodsReceiptController.php)::`index`, `show`, `store`, `post`, `purchaseOrderOptions`, `itemOptions`, `locationOptions`                                                                                                                                                                                                                                                                                                                     | 7     | GHTTP → saját domain cohort; backend guard kötelező      |
| [PurchaseOrderController](../../app/Http/Controllers/Admin/PurchaseOrderController.php)::`index`, `show`, `store`, `update`, `destroy`, `approve`, `close`, `supplierOptions`, `itemOptions`                                                                                                                                                                                                                                                                                                          | 9     | GHTTP → saját domain cohort; backend guard kötelező      |
| [PurchaseRequisitionController](../../app/Http/Controllers/Admin/PurchaseRequisitionController.php)::`index`, `show`, `store`, `update`, `destroy`, `approve`, `generatePurchaseOrder`, `generateFromMaterialRequirements`, `itemOptions`, `supplierOptions`                                                                                                                                                                                                                                          | 10    | GHTTP → saját domain cohort; backend guard kötelező      |
| [ProcurementDashboardController](../../app/Http/Controllers/Admin/ProcurementDashboardController.php)::`__invoke`                                                                                                                                                                                                                                                                                                                                                                                     | 1     | GHTTP → saját domain cohort; backend guard kötelező      |
| [InventoryController](../../app/Http/Controllers/Admin/Inventory/InventoryController.php)::`index`                                                                                                                                                                                                                                                                                                                                                                                                    | 1     | GHTTP → saját domain cohort; backend guard kötelező      |
| [MaterialRequirementController](../../app/Http/Controllers/Admin/Inventory/MaterialRequirementController.php)::`index`, `itemOptions`, `customerOrderOptions`                                                                                                                                                                                                                                                                                                                                         | 3     | GHTTP → saját domain cohort; backend guard kötelező      |
| [ShortageController](../../app/Http/Controllers/Admin/Inventory/ShortageController.php)::`index`                                                                                                                                                                                                                                                                                                                                                                                                      | 1     | GHTTP → saját domain cohort; backend guard kötelező      |
| [StockBalanceController](../../app/Http/Controllers/Admin/Inventory/StockBalanceController.php)::`index`                                                                                                                                                                                                                                                                                                                                                                                              | 1     | GHTTP → saját domain cohort; backend guard kötelező      |
| [StockMovementController](../../app/Http/Controllers/Admin/Inventory/StockMovementController.php)::`index`, `itemOptions`, `locationOptions`                                                                                                                                                                                                                                                                                                                                                          | 3     | GHTTP → saját domain cohort; backend guard kötelező      |
| [StockReservationController](../../app/Http/Controllers/Admin/Inventory/StockReservationController.php)::`index`, `release`                                                                                                                                                                                                                                                                                                                                                                           | 2     | GHTTP → saját domain cohort; backend guard kötelező      |
| [ProductionTaskController](../../app/Http/Controllers/Admin/ProductionTaskController.php)::`index`, `show`, `store`, `update`, `destroy`, `generateFromOrder`, `start`, `finish`, `employeeOptions`, `productionOrderOptions`, `itemInstanceOptions`, `operationStepOptions`, `itemOptions`, `locationOptions`                                                                                                                                                                                        | 14    | GHTTP → saját domain cohort; backend guard kötelező      |
| [ProductionTaskMaterialController](../../app/Http/Controllers/Admin/ProductionTaskMaterialController.php)::`store`                                                                                                                                                                                                                                                                                                                                                                                    | 1     | GHTTP → saját domain cohort; backend guard kötelező      |
| [ProductionPlanController](../../app/Http/Controllers/Admin/ProductionPlanController.php)::`index`, `show`, `store`, `update`, `destroy`, `approve`, `generateProductionOrders`, `customerOrderOptions`, `bomOptions`, `operationSequenceOptions`                                                                                                                                                                                                                                                     | 10    | GHTTP → saját domain cohort; backend guard kötelező      |
| [QualityCheckController](../../app/Http/Controllers/Admin/QualityCheckController.php)::`store`                                                                                                                                                                                                                                                                                                                                                                                                        | 1     | GHTTP → saját domain cohort; backend guard kötelező      |
| [ShopFloorController](../../app/Http/Controllers/Admin/ShopFloorController.php)::`index`, `myTasks`                                                                                                                                                                                                                                                                                                                                                                                                   | 2     | GHTTP → saját domain cohort; backend guard kötelező      |
| [ReportsController](../../app/Http/Controllers/Admin/ReportsController.php)::`customerOrders`, `production`, `inventory`, `procurement`, `quality`, `shopFloor`, `customerOptions`                                                                                                                                                                                                                                                                                                                    | 7     | GHTTP → saját domain cohort; backend guard kötelező      |
| [ManufacturingIntelligenceController](../../app/Http/Controllers/Admin/ManufacturingIntelligenceController.php)::`dashboard`, `bottlenecks`, `materialForecast`, `supplierPerformance`, `qualityTrends`, `risks`, `recommendations`                                                                                                                                                                                                                                                                   | 7     | GHTTP → saját domain cohort; backend guard kötelező      |
| [DashboardController](../../app/Http/Controllers/Admin/DashboardController.php)::`__invoke`                                                                                                                                                                                                                                                                                                                                                                                                           | 1     | GHTTP → saját domain cohort; backend guard kötelező      |
| [CapacityController](../../app/Http/Controllers/Admin/CapacityController.php)::`dashboard`, `factoryUnits`, `employees`, `schedule`, `storeSchedule`, `simulate`, `runSimulation`                                                                                                                                                                                                                                                                                                                     | 7     | GHTTP → saját domain cohort; backend guard kötelező      |

Mindegyik csoporthoz: compatible release digest+method test+deployed node/worker inventory,
régi writer elérhetetlenség és fresh DB-guard; gate OFF nem elég, ha egy régi production/GR út
ettől még írhat. HTTP delegálás, service call, model/direct SQL és külső BI külön belépés.
GREAD inventory/MRP riport nem maradhat régi queryn E után; nem csak RMA UI aktiválása függ tőle.
GCAP/QC továbbra sem alakít repair operationt production taskká; relevant serial/status kapcsolat
miatt kompatibilitási kontroll kell. GDOC processing metadata nem authoritative origin/ownership proof.

### 6.3. Feature gate és mixed-version tiltás

PROPOSED server-side activation epoch és authoritative DB barrier a posting/close tranzakcióban,
azonos activation row/lock vagy azzal egyenértékű WP2-bizonyított mechanizmus mellett. Cacheelt flag
nem authorization és nem safety barrier. Drain protokoll: új domainírás elutasítása → futó commandok
ismert commit/rollback lezárása → worker/scheduler/external writer fence → cutoff reconciliation →
epoch váltás. Ha deactivation vs close/credit race, az egyik soros commit nyer; nem kerülhet
közéjük claim-release vagy fél credit write. A freeze nem törli az aktív claimet/reservationt vagy
megállapodást, és nem keverhető a DB full read-onlyval, amely recovery/audit feladatokat is érinthet.

Biztonságos recovery mód: új üzleti műveletek OFF; kompatibilis olvasás és külön engedélyezett,
guardolt recovery/disposition folyamat elérhető a már fizikailag megérkezett áru és supplier/financial
kötelezettségek nyomon követésére. Nem zárunk ki fizikailag érkező árut nyom nélkül, és nem engedjük
vissza normál available-be. Emergency discrepancy/manual operational evidence részlete owner review,
nem fake RMA event vagy általános raw SQL override.

## 7. Database integrity and runtime permissions

Minden alábbi **PROPOSED / UNVERIFIED**, végleges engine-terv WP2, actual proof WP7.

| Principal / boundary               | Szükséges contract                                                                                             | Deployment evidence                                                                                        |
| ---------------------------------- | -------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------- |
| Migration principal                | Csak jóváhagyott deploy scope DDL; nem alkalmazás/queue credential, visszavont ideiglenes jog                  | Effective grants/role graph és connection routing, nincs credential a dokumentumban                        |
| Runtime web/worker                 | Csak szükséges SELECT és célzott mutable DML/EXECUTE; nincs global DROP/ALTER/TRUNCATE vagy guard-kerülő rutin | Valódi principalon raw INSERT/UPDATE/DELETE/DDL deny; örökölt role/grant és alternate connection vizsgálat |
| Controlled stock/return posting    | Event+movement+projection+reservation+history+audit totality egy txn; immutable event UPDATE/DELETE tiltott    | Legit cycle-safe create és orphan/child-delete/bulk/cascade negative; location/owner/serial/unit guards    |
| Credit és coverage posting         | Approval-only/source-order active U, fee cap/review proof; G-head/line budget+transfer/receipt conservation    | Két külön session, cap/source/occurrence és orphan commit tiltott; service-only validáció nem elég         |
| Claim acquire/finalize             | C1 tagság-trigger acquire; delete csak full-case restricted finalizerből, nincs proof_ok/session bypass        | Runtime acquire/delete/truncate deny, close vs új obligation race és audit rollback                        |
| Definer/routine/trigger owner      | Minimal rights, nem login runtime; object-definition/hash és grants együtt restore-olhatók                     | Definer létezés/EXECUTE és SQL SECURITY review; routine nem belső COMMIT, nincs network call               |
| Audit/read-only recovery principal | Minimal scope, védett read és backup artifact hozzáférés; nem minden usernek teljes személyes adat             | Restore rehearsal access/retention és business-history query proof, nem superuserrel végzett runtime teszt |

FK dependency graphot WP1/WP4/WP5 szerint executable sorrendre kell bontani. MySQL FK nem deferred;
immutable eventet nem UPDATE-elünk cycle-resolve célból. Mutable head-pointer kezdetben NULL lehet
csak pontos protocolban: parent→event→movement/source/line→projection pointer→audit/command→commit.
Event→future movement és movement→event kör helyett typed/inverse ownership. A FK a parent létét,
U legfeljebb egyet bizonyít, nem kötelező child totalitást, összeg/currency/owner/gráf invariánst.
Nullable UNIQUE több NULL-t engedhet; active key soralak CHECK-ben explicit; SQL UNKNOWN ne bypassoljon.
CHECK/FK action/auto-ID/collation/SQLite különbségek nem feltételezetten támogatottak.

C1 L változatlan: case-ek → items → item_instances → repair/production order → operations/fee/quote
→ requirements/coverage → reservations → balances → release/proof, ID-sorrendben. PR/PO/GR/Proposal/
ItemSupplier és gate-lock teljes helyét WP2 egyeztesse; régi GR-header→POitem és production-order→serial
lockok lehetséges inversionje ismert. Bounded deadlock/timeout retry csak egész txn, same command/hash,
fresh permission/guard; FK validation implicit lock és trigger út is része. Audit failure rollback,
afterCommit cache failure nem DB rollback és nem indokol új postingot.

Online schema change **UNVERIFIED**: pontos MySQL verzió/engine/SQL-mode, table size, long transaction,
metadata lock, replication, disk/headroom, FK/trigger/routine együttélés, ALTER algoritmus és lock-timeout
rehearsal kell. INSTANT/INPLACE/LOCK=NONE nem általános ígéret FK/precision/constraint change-re.
Chunkelt backfill nem teszi online-ná a DDL-t és nem tiltja a közben új orphan/duplicate írót.
Szükség esetén tervezett write-freeze/downtime; időtartam, ablak és toolválasztás OPEN.
Nem futtatunk DDL-t vagy nem igazolt online-schema-change eszközt.

## 8. Backup and rollback

### 8.1. Öt külön recovery művelet

1. **Feature deactivation:** új műveletek guardolt megállítása és drain; schema/adat/history megmarad.
2. **Application rollback:** csak a jelenlegi adatszemantikát és grantokat értő release-re; régi
   scope-vak binary nem kompatibilis pusztán azért, mert az oszlopok még léteznek.
3. **Data reconciliation:** authoritative fact/proof és projekció összevetése, jóváhagyott forward
   correction/rebuild; nem balance kézi átírása, nem történeti shipment/ownership feltalálása.
4. **Schema rollback:** csak bizonyított üres/unused additive objektum, dependency és védelem
   elvesztése nélkül, külön engedéllyel. Laravel down() nem recovery policy vagy MySQL txn rollback.
5. **Full restore:** kijelölt consistent DB+files+audit point, külön környezetben bizonyított restore,
   üzletileg jóváhagyott cut-off és minden azóta commitolt effect reconciliation. Nem hétköznapi undo.

### 8.2. Stage-specifikus recovery mátrix

| Stage | Required backup / prerequisites                                                        | Rollback trigger                                                        | Recovery point és művelet                                                        | Data-loss risk / audit megőrzés                                                         | Recovery utáni validation                                                                          | Responsible operator                                     |
| ----- | -------------------------------------------------------------------------------------- | ----------------------------------------------------------------------- | -------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------- | -------------------------------------------------------- |
| A     | DB+files/logs+grants baseline, off-host védett manifest és rehearsal                   | Backup siker/restore-próba hiány vagy audit mismatch                    | Nincs rollout; baseline cut-off marad                                            | Nincs mostani business rollback; unverified backupból recovery nem állítható            | Checksum, actual isolated restore, Axx és file-link reconciliation                                 | DBA + storage operator, release owner approval           |
| B     | Pre-DDL consistent DB/files + object/grant definitions és migration journal            | Interrupted/blocked DDL, schema-app incompatibility                     | Gate OFF; catalog diff, csak jóváhagyott restart/forward completion              | DDL részben commitolhat; down() adat/constraint-védelmet veszíthet                      | Object/constraint fingerprints, old app compatibility, no orphan                                   | DBA + release engineer                                   |
| C     | Pre-alignment checkpoint + source/mapping manifest/log chain, files cut-off            | Ambiguous owner, qty overflow, duplicate key, partial chunk             | Alignment stop; committed chunks őrzése; same-key resume vagy forward correction | Pre-C restore közbeni új legacy commitokat is elveszíthet; write-freeze/delta tail kell | PK/owner/q/source/claim proof, cutoff és exception-lista, nincs history rewrite                    | DBA + inventory/production/procurement domain owner      |
| D     | Compatible release/config+grant digests, DB/files checkpoint és queues manifest        | Shadow mismatch, old node/worker/client, stale projection               | Új posting felfüggesztése; compatible reader fallback, writer fence              | Régi binary rollback unsafe lehet; valid alignment nem dobható                          | Minden cohort és job digest/grant, same-cutoff reconciliation, permission deny                     | Release owner + worker/BI operators + DBA                |
| E     | Activation előtti recovery point + folyamatos valid post-cutoff log/evidence/file tail | Integrity/permission/duplicate/unknown obligation vagy monitoring alarm | Gate freeze+drain; compatible forward-fix; actual commits megmaradnak            | Pre-E restore önmagában új RMA history/physical business effect veszteség               | Commands/movements/credit/coverage/claims és supplier/file external reconciliation, owner go/no-go | Incident/release owner + DBA + affected domain operators |
| F     | Retirement előtti schema/object+data/file/archive backup és dependency manifest        | Régi függőség még hív, hiányzó archive/history-access                   | Retirement leáll; adapter/access compatible visszaállítás vagy forward fix       | DROP után új history visszavetítése veszélyes; destructive restore külön döntés         | History queries, source retention, runtime deny és no-old-writer evidence                          | Projektgazda + DBA + release/archive owner               |

Minden stage recovery evidence-csomagja: operator+approver, incident/cutoff, source/target release és
schema fingerprint, backup/checksum/key availability, restore sequence és eredmény, row/quantity/owner/
proof reconciliation, preserved post-cutoff commandok és külső effects listája, pending exceptions,
újranyitási engedély. RPO/RTO, backup retention/gyakoriság, binlog retention és restore window **OPEN**;
zero-data-loss cél nem bizonyított garancia. Replikáció önmagában nem backup, Git archive nem DB/file backup.

### 8.3. Új RMA transactionök utáni visszaállítás

Első új commit után az eredeti predeploy backup önmagában adatvesztő rollback lenne.
Őrizni kell a valid új claim/member/quote/approval/credit/coverage/stock/ownership/proof tényeket és
command kulcsokat. Restore egy külön célon: először manifest+DB+docs+grant/routine összhang bizonyítás,
majd valid post-cutoff transaction/durable evidence visszaillesztési terv. Replay nem hívhatja újra
a már végrehajtott supplier dispatch/payment/shipment hatást; same business occurrence és külső
ack/reconciliation szükséges. DB rollforward/PITR lehetőség csak mentett log-folytonosság és actual
engine rehearsal alapján, nem feltételezett funkció. Ismeretlen vagy hiányzó tail esetén residual
data-loss risk és üzleti megállás, nincs readiness vagy silently successful restore.

Forward-fix biztonságosabb, ha valid új history/physical shipment/receipt létezik, régi binary nem érti
az új scope-ot, új grant/constraint védi az adatot, vagy külső fél már cselekedett. Freeze+compatible
hotfix+auditált reconciliation előnyben; schema DROP/claim-delete/credit-history törlés nem javasolt.
Destructive restore/correction külön projektgazdai/üzemeltetői engedély, nem e dokumentum felhatalmazása.

## 9. Audit and evidence retention

CONFIRMED CURRENT: [activity_log migráció](../../database/migrations/2026_06_22_135921_create_activity_log_table.php)
morph subject/causer és JSON properties, nem typed domain FK vagy append-only enforcement.
[AuditLogService](../../app/Services/AuditLogService.php) explicit log és allowlist/exclusions;
a raw properties nem automatikus teljes domain proof. [DocumentService](../../app/Services/Admin/DocumentService.php)
fájlt a DB transaction előtt store-ol, checksum+verzió metadata a transactionben; download a disk/path
vagy file_path alapján. Soft-delete/metadata-versioning nem bizonyít immutable object retentiont.
[ProcessDocumentJob](../../app/Jobs/AI/ProcessDocumentJob.php) később document processing-state-t ír;
AI eredmény nem customer approval/origin/legal proof.

| Evidence                       | PROPOSED retention/recovery contract                                                                            | Későbbi proof                                                                                            |
| ------------------------------ | --------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------- |
| Domain events és audit         | Immutable origin/actor/time/command/causal identity; correction új fact, ne rewrite; ID-k megmaradnak           | Cross-table links, same-transaction audit, runtime raw/bulk/soft/cascade deny és export-access           |
| Customer approval, fee, credit | Submitted quote/line/version/hash, válaszadó jog, fee-version és review/usage/correction lánc                   | Nem csak latest/current=true file; teljes korábbi bizonyíték letölthető jogosult reviewernek             |
| Procurement                    | Legacy sources, G-budget/lines, commitment sent/ack/cancel/GR/transfer és named responsibility                  | Törölt/soft-deleted header mögötti valós kötelezettség is visszakereshető                                |
| Physical/serial/ownership      | StockMovement, actual place/time, original/replacement origin/genealogy/QC/sequence és külön ownership/reversal | Nem összevont serialok vagy új generált identity, quarantine és on-site rebuild eltérés nélkül           |
| Files/document references      | Exact version ID + content checksum + disk/object identity, storage snapshot/version és hozzáférési policy      | Bytes checksum ellenőrzés restore után; újabb külső object nem írhatja felül korábbi referenced versiont |
| Deployment/mapping/backup      | Release/schema/grant/trigger/routine digest, mapping és checkpoint manifest, backup/log chain                   | Biztonságos artifact access, encryption-key availability és független restore rehearsal                  |

Minden retention időtartam, archive/delete legal basis és personal-data access **OPEN**; nem találunk
ki kötelező évek számát. Soft-deleted users/customer/document adatok audit-snapshotja külön retention
review; log redaction nem törölhet szükséges lineage-et. Nem másolunk titkot recovery dokumentumba.
DB restore sikere nem file-consistency: DB-régebbi/file-újabb, hiányzó object, checksum-eltérés és
orphan upload külön reconciliation. Upload/AI pipeline drain és manifest cut-off kell; hibás vagy
ismeretlen file nem lesz valid approval pusztán metadata restore miatt. Evidence-read út permissionje
és régi morph-class név compatibility is bizonyítandó. Cache nem audit backup, regenerálható.

## 10. Deployment dependency matrix

| Component                     | Existing behavior                                  | PROPOSED behavior                                         | Migration dependency                                     | Reader compatibility                                  | Writer compatibility                                              | Feature gate                  | Rollback requirement                                           | Verification evidence – planned |
| ----------------------------- | -------------------------------------------------- | --------------------------------------------------------- | -------------------------------------------------------- | ----------------------------------------------------- | ----------------------------------------------------------------- | ----------------------------- | -------------------------------------------------------------- | ------------------------------- |
| Inventory                     | Full physical balance/null batch                   | Company projection+eligible E/quarantine/custody distinct | Normalized key + verified baseline + WP2 typed movements | GINV/GREAD minden E-reader                            | GR/consume/return shared posting                                  | GINV+GEXEC+GPROC              | Compatible writer, no old balance direct path                  | V04-V09/V24/V25                 |
| Reservations                  | Full active SUM/whole consume                      | Remaining counters + logical/physical purpose             | Verified used/released mapping + active guards           | Every reserve/report/MRP reader R                     | Reserve/release/consume same owner+mutex                          | GINV+GEXEC                    | Old partial-blind version unsafe                               | V07/V08/V24                     |
| Material requirements         | Customer-item/item snapshot                        | Stable aggregate/repair XOR/revisions                     | Owner decision+mapping/U+exact qty                       | Shortage/intelligence/PR same D/C meaning             | Current PO recalculation hardened, adapter only selected baseline | GINV+GPROC                    | Keep legacy IDs and source refs                                | V10/V11/V20/V23                 |
| PR                            | No-source generate, item/unit grouping             | Delta/G/line budget, one authority                        | WP5 source/G-event shape, chosen Proposal adapter        | Direct pointer vs full sources distinct               | All create/approve/generate/delete under Q/L                      | GPROC                         | Retain old source/PR snapshots; no source delete to regenerate | V14/V15/V44                     |
| PO                            | Manual supplier, status close                      | Firm sent/ack separate from creation/received             | Target supplier/readiness and commitment proof           | Reports/GR distinguish intent/firm                    | Update/cancel/generate guarded with source lineage                | GPROC                         | Existing supplier obligation preserved even case off           | V16/V17/V44                     |
| GR                            | Posted movement+counter/balance, float             | Actual receipt + owner coverage conversion                | Lineage/precision+shared locks + actual occurrence       | Received vs eligible vs consumed distinct             | GR+moves+coverage+history atomic                                  | GPROC+GINV                    | Drain partial GR; no duplicate posting on replay               | V17/V24/V38                     |
| Production execution          | Whole reservation consumed, no partial counters    | Actual C+own remaining E/R/P/L                            | Consumer adaptation before RMA gate                      | ShopFloor/production/report meaning                   | Production material/QC/task state guarded                         | GEXEC+GINV                    | Do not rollback to unsafe consume/status writer                | V08/V24/V39                     |
| RMA                           | No current module                                  | Claim/intake/admission/credit/close                       | 25 candidate DDL+principals+typed proof                  | Read-only compatible case/history access              | Controlled posting/finalizer only                                 | All cohorts + RMA server gate | Freeze new work; preserve custody and unresolved obligations   | V25-V31/V35                     |
| Reports                       | Physical-full reserved and status aggregates       | Company/eligible/custody/pending/commitment explicit      | Reader query/payload compatibility                       | All report endpoints and exports/direct BI            | No domain writer; cached read not guard                           | GREAD + source cohorts        | Compatible reader fallback; no semantic reversion              | V09/V32/V43                     |
| Manufacturing intelligence    | Customer inner joins/item open PO                  | Owner demand/coverage/eligible pool, chosen baseline      | Typed repair demand and firm source allocation           | Forecast/risk/recommendation/supplier/quality methods | Recommendation not automatic buy                                  | GREAD+GPROC                   | No old incoming duplication; invalid cache discarded           | V10/V16/V32                     |
| Cache invalidation            | Domain generations afterCommit                     | Same semantics/version+permission scoped payload          | Source-domain matrix and epoch design                    | Dashboard/reports/intelligence domains together       | Invalidation after DB commit, no repeated posting                 | GREAD/C                       | Rotate/rebuild caches from committed facts; not DB restore     | V32/V38                         |
| Background jobs               | ProcessDocumentJob and registered console commands | Versioned worker/gate, external inventory required        | Actual queue/cron/BI/runtime inventory UNVERIFIED        | Old payloads/morph/proof references compatible        | No old-code business writer permitted                             | GDOC + affected cohort        | Drain/restart/fence+idempotency, no blind replay               | V33/V35/V43                     |
| Policies/permissions          | Current inventory/document/procurement permissions | Explicit RMA role/action + DB grants                      | Policy/registry + runtime DML/EXECUTE review             | Protected history/owner read, no broad UI-only check  | HTTP/service/queue/direct-principal deny                          | GHTTP + every cohort          | Separate recovery authorization, no admin bypass of invariants | V29/V30/V43                     |
| Documents/audit               | Soft-delete/morph/version/checksum, external bytes | Typed proof/version retention and restore manifest        | Storage+DB+actor/history consistency                     | Old history/download access preserved                 | No referenced version overwrite/delete, AI not proof              | GDOC + proof users            | Restore exact bytes/versions and cut-off, not DB-only success  | V34/V37/V38                     |
| Capacity/QC/master dependency | Production/task/item/location readers and CRUD     | Original serial/sequence/history preserved                | Cross-domain relation/deletion and key compatibility     | GCAP relevant planning/read paths                     | Master delete cannot cascade proof/active identity                | GCAP+GINV+GEXEC               | Compatible access; no repair-to-production reuse               | V12/V34/V39                     |

Együtt kapcsolandó: GINV+GEXEC+GPROC+inventoryhez kötött GREAD/cache, HTTP/service/worker/direct-query
belépések és runtime-grantok. GDOC és GCAP releváns proof/serial/read útjai szintén gate-függők.
RMA önálló UI-kapcsolója nem elég: normál production/receipt/report ugyanazt a megváltozott pool és
remaining jelentést használja. Nincs queue-verzió vagy external writer bizonyíték nélküli activation.

## 11. Failure and recovery scenarios

Minden Sxx tervezett, nem megtörtént incident vagy elvégzett recovery.

| Scenario                              | Detection                                                | Immediate containment                                                               | Recovery                                                                | Data reconciliation                                                    | Verification – planned                  | Residual risk                                                         |
| ------------------------------------- | -------------------------------------------------------- | ----------------------------------------------------------------------------------- | ----------------------------------------------------------------------- | ---------------------------------------------------------------------- | --------------------------------------- | --------------------------------------------------------------------- |
| S01 Interrupted migration             | Journal vs catalog fingerprint, missing index/FK/routine | Gate OFF, concurrent deploy és posting fence                                        | DBA approved resume/forward DDL a tényleges object-state szerint        | Already-committed DDL/chunks és unchanged baseline audit               | V02/V40 + object diff/rehearsal         | Implicit commits/MDL/partial grants; down() nem automatikus undo      |
| S02 Schema only                       | App release older than compatibility contract            | No RMA activation; incompatible new defaults/constraints ne jussanak régi writerhez | Compatible app deploy vagy additive objects megtartása                  | Old writes megfelelnek-e new FK/U/type shape-nek                       | V21 és all affected legacy routes       | Nullable additive oszlop önmagában nem bizonyít old app safe működést |
| S03 App before schema                 | Capability fingerprint/health mismatch before traffic    | Route/service gate refuse, workers nem indulnak üzleti írásra                       | Complete reviewed schema vagy compatible app version                    | No partial commands/allocations/audit                                  | V22 schema deny/no business effects     | Csak UI gate esetén direct service/raw writer bypass                  |
| S04 Legacy writer after enable        | Release/node/connection audit vagy E/R mismatch          | Fence régi web/queue/BI/DB principal, stop new posting                              | Compatible cohort redeploy, authorized discrepancy correction           | All effects since activation cut-off, source/move/counter/eligibility  | V08/V09/V43; runtime inventory complete | Külső writer/grant hiányos inventory továbbra BLOCKER                 |
| S05 Duplicate source generation       | Generation/line key conflict vagy budget mismatch        | Affected item/requirement freeze, no delete/regenerate                              | Same-key canonical result, whole-txn retry vagy explicit correction     | Source snapshot vs G-line fold és PRqty, existing supplier commitments | V15/V23; two-connection proof           | Legacy duplicate already created PO nem oldható source delete-tel     |
| S06 Partial GR during deploy          | Open GR transactions, counter/coverage divergence        | GR traffic fence+drain before cut-off, preserve received goods                      | Complete/rollback known txn, compatible service resume                  | Posted GRitem portions/movements/POcounter/open coverage/Ge            | V17/V24/V38                             | Externally arrived goods státusza nem tűnhet el gate-off alatt        |
| S07 Concurrent reservation            | Overallocated pool, lock/retry errors                    | Shared item/owner locking és transaction barrier, stop unsafe writer                | One serialized winner; bounded whole retry, no duplicate reserve        | Reserved-consumed-released és eligible free pool                       | V24/V08                                 | SUM read lock nélkül vagy másik principal megkerülheti                |
| S08 Wrong batch identity alignment    | NULL/sentinel collision, q/source lineage mismatch       | Batch mapping stop, affected item unavailable pending review                        | Restore mapping checkpoint/forward correction preserving raw IDs        | Physical counts+movement+reservation and all references                | V05/V23                                 | Önkényes merge/ID change visszafordíthatatlan trace loss              |
| S09 Precision mismatch                | Boundary range/scale rejection vagy canonical mismatch   | Do not narrow/write; gate affected pipeline                                         | Exact compatible widening/adapter plan approved, resume after proof     | All D/R/I/K/G/C and API payload, rounding history                      | V18/V19                                 | Float-loss már read/compute lépésben keletkezhet                      |
| S10 Missing serial origin             | A27/A28 insufficient/foreign origin proof                | Eligibility/admission fail-closed; physical custody/discrepancy tracked             | Domain reviewer beszerzi actual evidence; no fabrication                | PO/task/sequence/QC+serial+item+owner/handover                         | V12/V13/V34                             | Historical proof lehet helyrehozhatatlan, status nem pótolja          |
| S11 Credit allocation during rollback | Feature epoch vs in-flight approval/credit command       | Gate freeze under same transactional barrier, drain                                 | Existing commit retained; authorized D7 review, not auto release        | Fee/quote/allocation/history/active source claim and settlement        | V26/V27/V30                             | External settlement after restore külön proof/reconciliation          |
| S12 Case close during deactivation    | Close command+gate epoch interleaving                    | Same-case/epoch barrier; block new close or let one full txn finish                 | No reopen/claim-delete shortcut; canonical result and compatible read   | Full member/outcome/reserve/credit/coverage typed proof and claims     | V28/V29/V35                             | Mixed grant/routine may allow partial close bypass                    |
| S13 Cache failure                     | AfterCommit error/stale generation/source mismatch       | Stop semantic stale reads or compatible bypass; no posting retry                    | Repair cache domain/epoch, rebuild from committed facts                 | Business command already committed vs rollback distinguished           | V32/V38                                 | Global flush nem helyettesít source/permission contractot             |
| S14 Old background job                | Worker digest/payload epoch/schema capability stale      | Stop/fence old worker, quarantine pending payload; record retry identities          | Restart compatible worker, explicit replay only safe jobs               | AI metadata vs proof versions, business effect occurrence/idempotency  | V33/V35/V43                             | External cron/BI worker UNVERIFIED; no blanket queue purge            |
| S15 DB restore with newer files       | Manifest/version/checksum/cutoff mismatch                | Protected evidence-read hold; no proof based on wrong object                        | Restore exact referenced versions vagy reconcile archived newer objects | DB document IDs+bytes+audit+postcutoff approvals matching              | V34/V36/V37                             | DB success mellett object retention/keys hiányozhat                   |
| S16 Historical MRP incompatible       | Target req/source/supplier/precision shape mismatch      | No historical merge/migration/adoption activation                                   | Owner chooses O1/O2/O3; WP2 reviewed adapter and compatible release     | Legacy PK/source/PO/GR, planning pegs/proposals and repair owner       | V20/V18/V11/V44                         | Historical declared status/runtime tests not verified current         |

## 12. Verification matrix

Minden sor **PLANNED / NOT EXECUTED**. Nincs most futtatott adatbázis-, migráció-, runtime-,
backup- vagy restore-teszt. Későbbi környezet: izolált reprezentatív snapshot/staging, pontos MySQL
engine/grant, két külön concurrency connection/barrier; SQLite FK-on compatibility külön, nem helyettesítő.
Minden test a saját setup/cut-off/release/schema/principal/digest artifactját is rögzítse.

| ID / status / scenario                                                | Setup                                                                          | Action                                                      | Expected database state                                                       | Expected business result                                                     | Expected evidence                                                      |
| --------------------------------------------------------------------- | ------------------------------------------------------------------------------ | ----------------------------------------------------------- | ----------------------------------------------------------------------------- | ---------------------------------------------------------------------------- | ---------------------------------------------------------------------- |
| V01 **PLANNED / NOT EXECUTED** – Catalog/owner baseline               | Pinned current schema és raw soft-deleted fixtures                             | A01-A30 és catalog/grant manifest read-only rehearsal       | Fixture/state unchanged, mismatches külön artifact                            | Nincs fake rowcount/PASS vagy audit remediation                              | Snapshot/digest/query access és exact exceptions                       |
| V02 **PLANNED / NOT EXECUTED** – Interrupted additive migration       | Izolált clone, B stage több DDL lépés                                          | Fault középen, journal/catalog compare, approved resume     | Csak teljes verified objects fogadhatók el; history/data megmarad             | Gate OFF amíg actual schema incomplete                                       | Before/after catalog és partial-commit recovery log                    |
| V03 **PLANNED / NOT EXECUTED** – Wrong baseline/drift                 | Más release/schema fingerprint staging fixture                                 | Deploy precheck                                             | No new domain writes vagy schema progression                                  | Drift STOP, nem felülírás                                                    | Expected/actual fingerprint és reviewer decision                       |
| V04 **PLANNED / NOT EXECUTED** – Negative balance                     | A01 fixture negative q és valid/unknown corrections                            | Read-only audit/fold                                        | No balance UPDATE, exact negative rows retained                               | Eltérés REVIEW, nem zero-default                                             | A01/A04 trace és immutable source                                      |
| V05 **PLANNED / NOT EXECUTED** – NULL batch duplicates                | Két NULL-batch balance és real sentinel-collision fixture                      | A02/A03, proposed alignment dry-run                         | No SUM/delete/remap; collision explicit                                       | Contract U build BLOCKED until verified mapping                              | PK/q/source mapping és collision evidence                              |
| V06 **PLANNED / NOT EXECUTED** – Physical reconciliation              | Opening baseline, transfer/reservation/correction és 2 GRline egy header alatt | A04/A23 exact fold                                          | Transfer net0, nonphysical reserve0; ambiguous header match explicit          | Balance és actual movement egyszer, unknown sign unresolved                  | Cutoff/type matrix, line/source evidence, no fanout                    |
| V07 **PLANNED / NOT EXECUTED** – Orphan/inactive owner                | Deleted PO/master és mismatched reservation fixture                            | A05/A07/A11/A12 with-deleted reads                          | Historical valid vs missing refs külön, no mutation                           | Unresolved active obligation nem válik free supplyvá                         | Owner/master/deletion graph artifact                                   |
| V08 **PLANNED / NOT EXECUTED** – Legacy partial reservation           | Reserved10, actual used4, unknown consumed-history másik sor                   | A07 mapping és future consume/release protocol              | Verified counters4/0 remaining6; unknown counters nem invented                | Whole-consume shortcut nem maradhat enabled                                  | Counter/actual movement/owner proof és denied old writer               |
| V09 **PLANNED / NOT EXECUTED** – Quarantine consumer regression       | Company quarantine/custody/pending plus usable pool                            | Minden WP3 catalog method és report/cache route later tests | E csak usable company, custody/MI company0, quarantine nem allocatable        | Normal available/report/export nem szivárog                                  | 282-method coverage + negative writer/read evidence                    |
| V10 **PLANNED / NOT EXECUTED** – Multiple PO aggregate                | Két PO egy customer-item, más BOM/item qty                                     | A13-A15 és chosen owner contract                            | Legacy requirement ID és refs megmaradnak, no last-PO overwrite               | Authorized demand összhang, ambiguity blocked                                | PO/BOM/owner matrix+exact sum                                          |
| V11 **PLANNED / NOT EXECUTED** – Stable identity/source references    | Legacy MR több PRsource/PO/GR, soft-deleted előzmény                           | O1/O3 mapping rehearsal, fail midway                        | No source FK silently remapped; immutable origin PK trace                     | Régi és új demand/coverage egyszer                                           | Mapping manifest/checkpoint/hash/ref integrity                         |
| V12 **PLANNED / NOT EXECUTED** – Missing serial origin                | Semi-finished serial missing/foreign PO/QC proof                               | A27/A28 és future eligibility command                       | No fabricated origin/member/admission                                         | Own manufacture UNKNOWN, secure discrepancy/custody                          | Exact missing proof és denial, no successful eligibility audit         |
| V13 **PLANNED / NOT EXECUTED** – Delivery/warranty ownership evidence | Planned delivery only, owner conflict, original+replacement serial             | A29/A30 és warranty/owner review                            | No backdated handover/warranty reset/ownership event                          | Date/order party nem legal/delivery proof                                    | Rule/version/serial/doc completeness artifact                          |
| V14 **PLANNED / NOT EXECUTED** – PR/source baseline                   | Manual source-less és generated consolidated PR fixtures                       | A18-A21+WP5 once-only coverage initialization               | Source anchors retained, initial snapshot nem current double ledger           | Manual vs unknown lineage explicit, no fake requirement                      | Initial qty/current fold/owner source audit                            |
| V15 **PLANNED / NOT EXECUTED** – Multi-line generation race           | Egy G budget10, source lines4+6, két concurrent retry                          | Authorize/create split és over-budget extra line attempt    | Egy Ghead, unique lines, sum10; loser no partial rows                         | Approved REV02 működik without U(req,gen) source tiltás                      | Two-connection budget/line/command+audit proof                         |
| V16 **PLANNED / NOT EXECUTED** – PO status/commitment                 | Ordered/Received enum de no sent/ack/GR evidence                               | A24/A25+firm coverage read                                  | Unknown/intent külön, no fake sent/receipt                                    | New buy/close csak obligation review után                                    | POitem/source/supplier proof list                                      |
| V17 **PLANNED / NOT EXECUTED** – Partial goods receipt                | PO10, GR4 és GR3, two sources, one unassigned portion                          | Post + rollout drain + replay later                         | Actual received7 egyszer, coverage decrease7 allocated parts cap, residual3   | Ge külön quarantine, C unchanged                                             | GRitem/movement/counter/coverage/audit exact fold                      |
| V18 **PLANNED / NOT EXECUTED** – Narrowing quantity boundary          | Historical PR18,3 > current12,3 max; multiplication/SUM overflow               | Adapter/PO/GR serialization attempt later                   | Explicit safe reject or approved widening; no truncated rows                  | No partial procurement quantity loss                                         | Boundary expected/error/rollback+exact-decimal evidence                |
| V19 **PLANNED / NOT EXECUTED** – API/Vue/cache precision              | Decimal strings, max quantities/IDs, too many decimals                         | Roundtrip request/service/JSON/cache/display                | Authoritative decimal/IDs preserved; display no writeback                     | No Number rounding procurement/stock authority                               | Payload schema/version and DB exact q, overflow denial                 |
| V20 **PLANNED / NOT EXECUTED** – Historical target identity           | Pinned PO+BOM MRP fixture vs current aggregate with RMA owner                  | O1/O2/O3 compatibility rehearsal later                      | Separate proposal-source/peg lineage, explicit adapter, no silent remap       | No historical capability mislabeled current/parallel buy                     | Branch/commit/ADR/source and approved target decision                  |
| V21 **PLANNED / NOT EXECUTED** – Schema before app                    | B additive DDL plus old compatible and incompatible app fixtures               | Legacy routes/queries with gate OFF                         | Compatible writes valid; unsafe route blocked, no RMA writes                  | Additive default nem eligible/owner invention                                | Version/grant matrix and route deny/compatibility logs                 |
| V22 **PLANNED / NOT EXECUTED** – App before schema                    | New service build with missing required objects/routine                        | Traffic/worker startup and direct-call attempt              | No partial command/movement/credit or case close                              | Capability guard fail-closed                                                 | Schema fingerprint and safe failure audit                              |
| V23 **PLANNED / NOT EXECUTED** – Alignment retry/chunks               | Cutoff manifest, duplicate retry és changed sourcehash                         | Resume C-stage mapping later                                | Same-key exact result; changed input conflict; no overwritten source ID       | Counts+q+owner conserved, unknown exceptions retained                        | Chunk journal, beforeafter hash/ref/q reconciliation                   |
| V24 **PLANNED / NOT EXECUTED** – Reserve/consume/GR concurrency       | Shared item, two sessions, partial use/release and GR                          | Overlap commands with barrier and injected deadlock         | No overreserve, counters+physical+coverage atomic; full bounded retry         | No double deductions/phantom availability                                    | Both commit orders, locks/deadlock/retry/audit outcome                 |
| V25 **PLANNED / NOT EXECUTED** – Intake/admission replay              | WP4 verified I/MI pending, legalbasis/disposition for A                        | Future intake/admission retry + cache rebuild               | MI company0; A/MA at most1, quarantine E0 until release                       | Onsite once1, original/replacement separate                                  | AM-02-G1-G4 and distinct occurrences/movements proof                   |
| V26 **PLANNED / NOT EXECUTED** – Credit allocation retry              | Verified fee10000, quote credit6000, two approvals                             | Same command/hash and competing quote race                  | One active source-order credit, complete history/approval                     | No submit credit; no second4000 wallet                                       | Fee/line/cap/permission/command/audit proof                            |
| V27 **PLANNED / NOT EXECUTED** – Credit during recovery               | Committed approval/allocation, settlement unknown, deactivation                | Freeze/compatible rollback/reconciliation later             | History/active claim retained; no auto release/refund                         | Unknown not unused/unsettled, D7 evidence required                           | Postcutoff credit/fee/review document manifest                         |
| V28 **PLANNED / NOT EXECUTED** – Close versus feature deactivation    | Open full-case evidence, concurrent gate freeze and close                      | Barrier overlap both orders                                 | One whole close+claim release or blocked close, no half state                 | Freeze doesn't delete obligations/claims                                     | Epoch/case locks and typed close/audit outcome                         |
| V29 **PLANNED / NOT EXECUTED** – Claim/finalizer direct bypass        | Runtime principal, open case+unresolved reserve/credit                         | Raw delete/truncate/close/proof_ok attempt later            | Denied and no claim loss; valid finalizer one atomic close                    | No admin/session bypass or partial member release                            | Effective grants+two-principal negative/positive evidence              |
| V30 **PLANNED / NOT EXECUTED** – Runtime stock/credit/coverage grants | Actual runtime vs migration/definer principals                                 | Raw orphan INSERT/UPDATE/DELETE and alternate connection    | Unauthorized paths denied, guarded legitimate writes possible                 | No unproved engine-enforcement claim                                         | Role/grant/routine/connection matrix and SQL denial evidence           |
| V31 **PLANNED / NOT EXECUTED** – Cycle-safe immutable inserts         | WP2 event/member/movement/credit/G-source graph                                | Legit creation and orphan commit/childdelete attempts       | No immutable event UPDATE to resolve cycles; totality protected               | No orphan proof/source or double receipt                                     | Exact insert sequence+FK/null/grant/routine negative tests             |
| V32 **PLANNED / NOT EXECUTED** – Cache afterCommit failure            | Warm reports/MI caches, actual committed GR/consume                            | Fault cache rotation and retry same command                 | Business DB remains committed once; compatible cache rebuild                  | Stale read not posting authority, no new posting on cache error              | Commit ID/domain generations and business/audit counts                 |
| V33 **PLANNED / NOT EXECUTED** – Old worker/payload                   | Old ProcessDocumentJob/deployment digest, pending payloads                     | D/E drain/fence and compatible restart                      | No old writer state update after barrier; safe idempotent metadata only       | Proof version not overwritten, jobs not blindly purged                       | Worker/payload/queue/cutoff/version inventory and replay result        |
| V34 **PLANNED / NOT EXECUTED** – DB/files restore mismatch            | Older DB with newer/missing/corrupt external document versions                 | Isolated paired restore and download/evidence validation    | Wrong bytes never valid proof; exact version retention restored or BLOCKED    | Serial/owner/approval history consistent or explicit unresolved              | Manifest/checksum/access/actor lineage and storage outcome             |
| V35 **PLANNED / NOT EXECUTED** – Postcutoff external effect           | New RMA shipment/credit/procurement sent already happened externally           | Restore replay/reconcile future rehearsal                   | Retain command/occurrence IDs; no second shipment/dispatch/payment effect     | Valid history/tail preserved, claim/obligation reconciled                    | External ack+DB/proof/tail manifest and idempotent replay evidence     |
| V36 **PLANNED / NOT EXECUTED** – Backup/log gap and RPO               | Backup keys/log chain incomplete, chosen RPO/RTO                               | Restore rehearsal+tail continuity assessment                | No claimed successful full recovery, no production mutation                   | Explicit loss exposure and owner containment; no invented zero RPO           | Backup digest/key availability/log continuity/time measurements        |
| V37 **PLANNED / NOT EXECUTED** – Retention/delete guards              | Referenced doc/serial/master/PRsource, old audit and history                   | Hard/soft/cascade/bulk/storage-delete attempts later        | Protected evidence remains queryable; authorized archive preserves refs       | No valid history lost across rollout/restore                                 | DB/file hashes, permissions, old-version download proof                |
| V38 **PLANNED / NOT EXECUTED** – Audit rollback                       | Fault after stock/credit/coverage writes before audit/command completion       | Post/receive/close transaction fault injection later        | All new fact/projection/counters/command rollback; prior history retained     | No half success or duplicate retry effect                                    | Beforeafter row/q/audit + transaction/command identity                 |
| V39 **PLANNED / NOT EXECUTED** – Operation/sequence preservation      | Existing manufacturing task/QC/sequence and begun repair op                    | Mapping/recovery then scope supersede attempt               | No original task rewrite/regenerated serial; begun op immutable               | F4 current approval guard, actual time/material retained                     | Original/repair history diff and authorized scope/version audit        |
| V40 **PLANNED / NOT EXECUTED** – Online DDL compatibility             | Representative big clone, long tx, FK/trigger/grant and disk limits            | Chosen ALTER plan with controlled interruption later        | Actual algorithm/locks known; data/guards preserved, partial state diagnosed  | No unsupported zero-downtime claim                                           | Engine version, timings/MDL/disk/replication and catalog fingerprints  |
| V41 **PLANNED / NOT EXECUTED** – Legacy retirement                    | D/E observation complete, one old dependency still registered                  | F-stage eligibility check                                   | No destructive contract/cleanup until dependency removed and approved         | History/archive access remains, old writer absent proven                     | Dependency inventory/go-no-go and retained backup/query proof          |
| V42 **PLANNED / NOT EXECUTED** – Dual projection atomicity            | Approved temporary one-writer old/new projection design                        | Inject second projection failure and same-key retry         | Both projections+fact/history/audit rollback, or exactly one committed result | No eventual unguarded dual-write or double ledger                            | Exact q/source fold and transaction/command postconditions             |
| V43 **PLANNED / NOT EXECUTED** – Gate and policy bypass               | Cached UI flag ON, server OFF, direct API/service/queue/raw paths              | Attempt domain writes/read leakage with denied actors       | Denied before effects; compatible protected read only                         | Permissions+runtime grants and fresh gate authoritative                      | All entrypoint/method/principal coverage, no sensitive payload leakage |
| V44 **PLANNED / NOT EXECUTED** – Retained procurement case close      | Withdrawn repair need, sent PO residual, partial receipt, named handoff        | Concurrent handoff/reassign/close later                     | Paired qty conserved; obligation/history retained; close only C1 proofs       | D11 procurement responsibility transferred, not cancel/financial forgiveness | Source/PO/GR/handoff/recipient/close evidence and race result          |
| V45 **PLANNED / NOT EXECUTED** – Snapshot consistency                 | Two different cutoffs/replica lag and consistent snapshot fixtures             | Audit same-cutoff vs mismatched reads                       | No data mutation; inconsistent scan classified inconclusive                   | False reconciliation PASS/FAIL avoided                                       | Snapshot/time/version/query hash and consistency annotations           |

Meglévő current tesztforrások mint regressziós kiindulás:
[InventoryTest](../../tests/Feature/InventoryTest.php),
[InventoryManagementUiTest](../../tests/Feature/InventoryManagementUiTest.php),
[ProductionExecutionTest](../../tests/Feature/ProductionExecutionTest.php),
[ProcurementTest](../../tests/Feature/ProcurementTest.php),
[ProcurementManagementUiTest](../../tests/Feature/ProcurementManagementUiTest.php),
[ReportingAnalyticsTest](../../tests/Feature/ReportingAnalyticsTest.php),
[ManufacturingIntelligenceTest](../../tests/Feature/ManufacturingIntelligenceTest.php),
[BusinessCacheInvalidationTest](../../tests/Feature/BusinessCacheInvalidationTest.php),
[DocumentTest](../../tests/Feature/DocumentTest.php),
[DocumentIntelligencePipelineTest](../../tests/Feature/DocumentIntelligencePipelineTest.php).
Forrásolvasás nem futtatási siker: a jelenlegi testsuite sem bizonyít még nem létező RMA/grant/
coverage/partial-counter integrációt. A B 35/35 történeti prototípusa nem reprezentatív legacy audit.

## 13. Findings and open decisions

### 13.1. WP6 finding register

Minden WP6 finding **OPEN**. A „kockázat” forrásalapú compatibility vagy proof-hiány;
nem mindegyik confirmed repository defect és egyik sem bizonyított production-adatkárosodás.
Korábbi finding nem záródik le e tervtől vagy documentation PASS-tól.

| ID / severity – OPEN | Evidence                                                                                                               | Risk                                                                                            | PROPOSED resolution                                                                                       | Responsible work package / owner      | Acceptance criterion                                                                              |
| -------------------- | ---------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------- | ------------------------------------- | ------------------------------------------------------------------------------------------------- |
| WP6-F01 **BLOCKER**  | Current migrációdefiníciók, nincs actual catalog/data/grant/backup/restore audit; C1 G2 rollout blocked                | Unseen orphans/negative/mapping/data-loss risk mellett unsafe migration                         | Axx representative read-only audit+catalog/drift, restoration és all-runtime inventory                    | WP6 operational follow-up + WP2/WP7   | A01-A30/V01/V36/V45 actual scoped artifacts, no hidden unknown blocking step                      |
| WP6-F02 **BLOCKER**  | WP3 full active SUM/whole consume és WP4 quarantine; 282 current methods és külső utak UNVERIFIED                      | Mixed old/new writers/readers feloldják quarantine/remaining invariánsokat                      | All cohort single-contract rollout, server/DB fence, old worker/BI/grant closure                          | WP2/WP3 integration + WP7             | V08/V09/V24/V33/V43, every catalog method+external path actual covered and old writer unreachable |
| WP6-F03 **BLOCKER**  | WP5-F03 és historical PO+BOM vs current customer-item identity, separate Proposal sources/partial18,3                  | Silent FK remap, owner/source loss vagy duplicate parallel procurement                          | Owner chooses target O1/O2/O3, explicit mapping+authority+precision adapter and lineage conservation      | Projektgazdai decision + WP2/WP7      | V10/V11/V18-V20 actual approved target; no ambiguous remap/narrowing                              |
| WP6-F04 **BLOCKER**  | C1 G1/G3 és WP1/WP4/WP5 cycles/proof totality; runtime principal unknown                                               | Raw stock/credit/coverage write, orphan event vagy partial case close megkerüli service guardot | Executable cycles/restricted principals/finalizer+gate/locking proof, no immutable backfill               | WP2 + WP7                             | V28-V31/V37/V38 actual engine/grant/two-connection negative and valid positive paths              |
| WP6-F05 **HIGH**     | stock_balances NULL-batch U és firstOrCreate; master deletion SET NULL                                                 | Duplicate/sentinel collision vagy SUM merge trace/data loss                                     | Physical/movement verified identity mapping, item mutex, safe contract after old writers fenced           | WP2 + later alignment operator/WP7    | A02-A04/V05/V06/V23, no arbitrary merge, references and q exact                                   |
| WP6-F06 **HIGH**     | Reservation nincs consumed/released counter; materials movement reverse morph, current status-only consume             | Backfill imagined partial q, fulfilled stock available again                                    | Verified actual usage/release evidence, unknown classification, no default counters from status alone     | WP3/WP2 + WP7                         | A07/A16/V08/V24, exact remaining and no fake consumption/release                                  |
| WP6-F07 **HIGH**     | PR sources CASCADE/direct pointer, current PO close without GR; receipt movement source header                         | Unknown supplier obligation fake cancelled/received vagy duplicate actual supply                | A18-A26 lineage/commitment/GR owner distribution and WP5 once-only baseline                               | WP5/WP2 + WP7                         | V14-V17/V44 plus actual source/receipt/commitment audit and no header join fanout                 |
| WP6-F08 **HIGH**     | Current12,3 plus float+number API; historical partial18,3; incoming supplier snapshots                                 | Truncated/rounded/overflow q/money vagy adapter shape mismatch                                  | End-to-end precision+serialization contract and explicit range/rounding/conversion decision               | Projektgazdai policy + WP2/WP7        | V18/V19/V20 boundary/aggregate/JSON tests, no silent narrowing                                    |
| WP6-F09 **HIGH**     | docs/deployment általános útmutató; actual backup/PITR/files/grants/topology unknown                                   | Valid post-cutoff history/physical external effects elvesznek destructive restorekor            | Stage recovery manifests, RPO/RTO decision, isolated DB+file+grant/tail rehearsal, compatible forward-fix | Deployment/DBA/storage owner + WP7    | V27/V34-V36/V41, measured restore and external reconciliation, no invented zero loss              |
| WP6-F10 **HIGH**     | DocumentService file-store before DB, soft-delete/morph; Activitylog not typed proof                                   | DB restore siker mellett missing/wrong bytes, older approvals vagy audit evidence unreachable   | Immutable version/checksum retention and access, paired cutoff and worker drain                           | WP2 + storage/security owner/WP7      | V33/V34/V37/V38 actual download/checksum/deletion deny and audit txn proof                        |
| WP6-F11 **HIGH**     | GR/header/item lock order vs C1 L, no complete gate/PO/Proposal plan; MySQL DDL interruptions untested                 | Deadlock/partial deploy or feature-off vs close/credit race causes half state                   | Full implicit+explicit lock/epoch plan, bounded whole-txn retries and verified DDL checkpoints            | WP2 + release/DBA/WP7                 | V02/V24/V26/V28/V40, two sessions and interrupted-stage rehearsal                                 |
| WP6-F12 **HIGH**     | Current registry/policy only existing targets; external cron/BI/queue grants unverified, cache generations afterCommit | UI-only gate, stale reports/old jobs/direct DML bypass és duplicate posting on cache retry      | Runtime/connection/worker/route inventory, compatible versioned read/cache, policy and principal gates    | Security/release owner + WP2/WP7      | V09/V30/V32/V33/V43 actual all-entrypoint policy deny and compatible reads                        |
| WP6-F13 **MEDIUM**   | WP4 discrepancy/pending-scrap és WP5 financial rounding/settlement/close részletek OPEN                                | Unapproved policy introduced as migration default or implicit financial forgiveness             | Keep affected path blocked/unknown; owner clarifies only new details, not accepted D6-D11                 | Projektgazdai domain review + WP2/WP7 | A27-A30/V12/V13/V27/V44, no fictional origin/tax/refund/ownership                                 |
| WP6-F14 **LOW**      | Historical ADR006 number collision/0016 declared status; document-only source evidence                                 | Historical/planned source falsely current/implemented/migration-ready                           | Pinned paths/status categories és planned/actual split, prior findings preserved                          | WP7 + projektgazdai review            | All citations/classifications truthful and no prior finding closed without proof                  |

### 13.2. Döntés- és finding-traceability

| Autoritás / előzmény       | WP6 megőrzött szerződés és kapu                                                                                                     |
| -------------------------- | ----------------------------------------------------------------------------------------------------------------------------------- |
| RMA-BD-001/003/004/011     | Case-owner/member/claim, restricted finalizer és gate-close race; 6–8, V28/V29/V35                                                  |
| RMA-BD-002/005/012/015     | Own manufacture/serial/origin, manual warranty és history; A27–A30, V12/V13/V34                                                     |
| RMA-BD-006/009/010/013/014 | Scope/approval/actual operation/time/material/fee/credit megőrzés; 5–9, V26/V27/V39                                                 |
| RMA-BD-007/016/017/018     | Custody0, egyszeri igazolt receipt, quarantine isolation és company material/coverage; 3/6, V04–V11/V24/V25                         |
| RMA-BD-008/019/020         | Original outcome és replacement preparation/shipment/ownership külön; 6/8/9, V25/V34/V35                                            |
| D1/D2/D3                   | Három scope; semi_finished_product jogosultság proof alapján; egy owner és külön reporter, unknown nem company/origin               |
| D4/D5                      | Warranty nem reset, physical kiadás nem ownership dátum; restore sem gyárt jogi eseményt                                            |
| D6/D7                      | Approval-only egy active source-order credit, cancel nem release; usage/settlement unknown megmarad                                 |
| D8/D9/D10                  | Actual return quarantine, authorized új shipment új occurrence, jogi reversal külön original proof                                  |
| D11                        | Demand/coverage/supplier obligation/receipt külön; retained/handoff responsibility close után is nyomon követett                    |
| F1/F2/F3/F4                | Előkészítés vs egyszeri kiadás; proof-only atomikus close; serialpár; megkezdett operation history nem overwrite                    |
| AM-01/AM-02/AM-03/AM-04    | Logical obligation, physical return, legal event; kétlépcsős intake; verified fee/credit; causal reversal/coverage                  |
| AM-02-G1                   | A28–A30 és WP4 discrepancy/intake evidence, nincs fake origin/backfill                                                              |
| AM-02-G2                   | MI company0, MA csak verified admission; unknown default company tilos                                                              |
| AM-02-G3                   | Minden 282 consumer és külső út E/R/P/M/Q/C/L compatibility; mixed-version activation BLOCKED                                       |
| AM-02-G4                   | MI/MA külön occurrence, admission nem második receipt; replay/restore nem új posting                                                |
| C1 G1/G2/G3                | Restricted claim-finalizer és raw DML proof / shared rollout barrier / return lifecycle DDL továbbra UNVERIFIED                     |
| WP1-F01–F10                | Mind nyitott előzmény megőrizve; WP6 inventory/audit/nullable batch/grant/cycle/key és history kapuk                                |
| WP3-F01–F15                | Mind a 282 metódus cohort-lefedettsége; E/R/P/M/Q/C/L, external bypass, cache és locking nem implementált                           |
| WP4-F01–F10                | I/MI/A/MA totality, owner/source/proof/discrepancy/retention és mixed rollout; nincs event schema lezárás                           |
| WP5-F01–F12                | Credit cycles, coverage conservation, historical adoption/precision, line cardinality, financial policy és handoff továbbra nyitott |
| REV-01                     | Executable cycle-safe insert és immutable event/grant proof WP2/WP7, nem event backfill                                             |
| REV-02 / WP5-REV02-01      | Multi-line generation owner-approved; head/line identity és quantity budget WP2/WP7 technikai kapu                                  |
| REV-03                     | Remaining counter és actual consumption; régi whole-consume/full-active SUM kód nem maradhat aktív                                  |
| REV-04                     | Globális employee-overlap üzleti tiltás nincs elfogadva; későbbi execution/WP7 dependency, nem migration-fact                       |
| REV-05                     | WP4 physical return-intake és admission movement külön; schema pointer döntés nem WP6-ban véglegesül                                |

OPEN owner-döntések: target procurement baseline/identity adapter; rounding/overflow/conversion és
pénzügyi closure/correction részletek (nem D6/D7/D11 újrajóváhagyás); unresolved discrepancy/
pending-scrap scope; deployment ablak, RPO/RTO/retention, megfigyelési küszöbök és operatorszerepek.
A konkrét engine/DDL/grant/recovery tool és performance méretfüggő részletek WP2/WP7 evidence alapján.
Nem vezetünk be új tax/payment/refund/employee-overlap vagy supplier-allocation priority szabályt.

## 14. WP2/WP7 handoff

**WP2 később, külön feladatban:** jóváhagyott target baseline, a 25+7 teljes constraint/dependency/
index/null/action/cycle profil; 18 existing és további FK-target retention-impact; owner mapping és
precision boundary; no silent remap, generation head/line U, stock/credit/coverage/close restricted
DML és routine-principal terv, gate epoch/lock-order, online DDL compatibility és interruption checkpoints.
WP6 nem szállít executable migrációt vagy engine-enforced státuszt.

**WP7 később, külön feladatban:** V01–V45 tényleges audit/migration/recovery/runtime/concurrency/
regresszió futtatása izolált környezetben, MySQL/SQLite külön bizonyíték; minden Axx és WP3 consumer
matrix actual evidence, grants és két-principal/two-connection negatív próbák, backup+files restore
és post-cutoff business effect rehearsal. Minden N/A explicit scope/indok/felelős; BLOCKED nem PASS.

Migration readiness csak a releváns WP1–WP7 kapuk és owner go/no-go tényleges bizonyítéka után
értékelhető. E dokumentum státusza marad **REVIEW / NOT MIGRATION READY**; WP2/WP7 nem indul
automatikusan. Projektgazdai review-ra vár; deploymentre vagy destructive recoveryre nincs felhatalmazás.

## 15. Acceptance checklist

| Követelmény              | WP6 dokumentációs állapot                                                                                       |
| ------------------------ | --------------------------------------------------------------------------------------------------------------- |
| Baseline és scope        | Megadott branch/HEAD, négy pending hash; csak új WP6, empty index                                               |
| Schema inventory         | WP1 25/18/7 visszaellenőrizve; 18 current migráció/model/consumer profil, actual catalog és data UNVERIFIED     |
| Legacy audit             | Read-only Axx specifikációk inventory/production/procurement/eligibility; nincs futtatott adatminőségi eredmény |
| Historical compatibility | Pinned branch/object, owner/lineage/precision/authority alternatívák; nincs import vagy silent remap            |
| Rollout                  | A–F entry/exit, compatible single writer és gate/drain; unrestricted dual-write tiltott shortcut                |
| Recovery                 | Stage-onként backup/cutoff/trigger/loss/evidence/operator; új valid transaction nem dropolható                  |
| Dependencies             | WP3 teljes consumer-katalógus és külső runtime audit; simultaneous switch és protected histories                |
| Failure/verification     | 16 recovery scenario, legalább35 setup/action/DB/business/evidence tesztsor, mind PLANNED / NOT EXECUTED        |
| Findings/authority       | Korábbi döntések/findingok megőrizve; WP6 OPEN findingok, nem migration-ready vagy WP6 Accepted                 |

### 15.1. Elvégzett dokumentációs ellenőrzések – 2026-11-04

| Check                             | Tényleges dokumentációs eredmény                                                                                                                                        |
| --------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Strukturális inventory            | PASS: 15 főfejezet, 25 különböző új jelölt, 18 current schema/model profil és 7 S + 11 R WP1 impact-sor                                                                 |
| Consumer trace                    | PASS: WP3 282 külön metódusa 61 source-class csoportba hiány és duplikáció nélkül besorolva; a current deklarációs sorszámok léteznek                                   |
| Tervezett coverage                | PASS: 30 read-only A01–A30 specifikáció, 16 S01–S16 failure scenario, 45 V01–V45 setup/action/DB/business/evidence tesztsor; audit és teszt mind PLANNED / NOT EXECUTED |
| Finding/decision trace            | PASS: 14 OPEN WP6 finding, 4 BLOCKER / 8 HIGH / 1 MEDIUM / 1 LOW; 20 BD, D1–D11, F1–F4, AM/AM-G/C1/WP/REV hivatkozások megőrizve                                        |
| Relatív link és source provenance | PASS: 132 relatív fájl-/anchorhivatkozás; 12 történeti ADR Git-object és 10 megnevezett historical service a pinned commiton megtalálható                               |
| Prettier                          | PASS: helyben telepített eszköz check WP1/WP3/WP4/WP5/WP6; csak az új WP6 formázása történt                                                                             |
| Encoding/Markdown/whitespace      | PASS: strict UTF-8, nincs BOM/replacement character/trailing whitespace; code fence-ek zártak; táblázatok és főfejezetszámok ellenőrizve                                |
| Sensitive-data mintavizsgálat     | PASS: az öt pending dokumentumban nem talált credential assignment/token/private-key/email vagy abszolút környezeti út mintát; nem production-data audit                |
| Megőrzés és Git                   | PASS: a négy belépési WP-hash és a történeti B JSON hash változatlan; branch/HEAD változatlan; staging és tracked diff üres; csak WP6 került az induló inventory mellé  |
| `git diff --check`                | PASS; az untracked WP6 whitespace-ét külön közvetlen fájlvizsgálat ellenőrizte                                                                                          |

Az ellenőrző részletek inline, memóriából futottak; nem keletkezett script vagy további artifact.
E PASS-ok nem igazolnak adatminőséget, telepített DB-sémát, motor/grant enforcementet, migrationt,
backup/restore sikerét vagy readiness-t. A terv továbbra is projektgazdai review-ra vár.

Releváns [Definition of Done](../../docs/project-management/definition-of-done.md): dokumentáció;
[Layered Quality Gates](../../docs/development/quality-gates.md): formatter, link/source/trace,
encoding/whitespace/sensitive scan és Git-scope. [Deployment guidance](../../docs/deployment.md)
és [release checklist](../checklists/release.md) alapján a későbbi release több kaput igényel.
README/index/memory/accepted dokumentumok nem módosulnak a kifejezett egy-új-fájl scope miatt.
PHP/Pint/backend/frontend/build/DB/migration/restore nem fut: nincs kódváltozás, runtime próba tiltott.
Nincs új script, dependency install, staging, commit, push vagy merge.
