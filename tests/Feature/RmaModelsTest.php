<?php

use App\Enums\RepairCommandStatus;
use App\Enums\RepairExecutionStatus;
use App\Enums\RepairRequestStatus;
use App\Enums\RepairStateDimension;
use App\Enums\WarrantyClassification;
use App\Enums\WarrantyDurationUnit;
use App\Models\ActiveRepairClaim;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Employee;
use App\Models\Item;
use App\Models\ItemInstance;
use App\Models\ProductHandoverEvidence;
use App\Models\ProductionOrder;
use App\Models\ProductWarrantyRule;
use App\Models\RepairCommand;
use App\Models\RepairOrder;
use App\Models\RepairRequest;
use App\Models\RepairRequestItem;
use App\Models\RepairStatusHistory;
use App\Models\User;
use App\Models\WarrantyAssessment;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\RmaSchemaFixture;

uses(RefreshDatabase::class);

/**
 * Trusted test data only, not an application command/authorization implementation.
 *
 * @return array{
 *     'App\\Models\\RepairCommand': RepairCommand,
 *     'App\\Models\\RepairRequest': RepairRequest,
 *     'App\\Models\\RepairRequestItem': RepairRequestItem,
 *     'App\\Models\\RepairOrder': RepairOrder,
 *     'App\\Models\\ActiveRepairClaim': ActiveRepairClaim,
 *     'App\\Models\\ProductWarrantyRule': ProductWarrantyRule,
 *     'App\\Models\\ProductHandoverEvidence': ProductHandoverEvidence,
 *     'App\\Models\\WarrantyAssessment': WarrantyAssessment,
 *     'App\\Models\\RepairStatusHistory': RepairStatusHistory,
 * }
 */
function rmaModelGraph(): array
{
    $fixture = new RmaSchemaFixture;
    $command = new RepairCommand;
    $command->forceFill([
        'command_type' => "case_open\0\xFF", 'idempotency_key' => "key\0\xFE",
        'payload_hash' => hash('sha256', 'model fixture', true), 'actor_id' => $fixture->actor->id,
    ])->save();
    $request = new RepairRequest;
    $request->forceFill([
        'case_number' => 'RMA-model', 'owner_customer_id' => $fixture->owner->id,
        'created_by' => $fixture->actor->id, 'creator_employee_id' => $fixture->employee->id,
        'reporter_employee_id' => $fixture->employee->id, 'reporter_name' => 'Reporter',
        'reporter_contact_snapshot' => ['email' => 'reporter@example.test'],
        'owner_snapshot' => ['name' => $fixture->owner->name], 'opening_command_id' => $command->id,
    ])->save();
    $member = new RepairRequestItem;
    $data = $fixture->memberData($request->id);
    $data['origin_evidence'] = ['reference' => 'origin'];
    unset($data['created_at'], $data['updated_at']);
    $member->forceFill($data)->save();
    $order = new RepairOrder;
    $order->forceFill(['repair_request_item_id' => $member->id, 'work_order_number' => 'WO-model'])->save();
    $rule = new ProductWarrantyRule;
    $rule->forceFill([
        'item_id' => $fixture->serial->item_id, 'version' => 1, 'effective_from' => RmaSchemaFixture::TIME,
        'duration_value' => 12, 'duration_unit' => WarrantyDurationUnit::Month,
        'rule_reference' => 'Fixture only', 'approved_by' => $fixture->actor->id,
        'approved_at' => RmaSchemaFixture::TIME, 'command_id' => $command->id,
    ])->save();
    $document = $fixture->document();
    $handover = new ProductHandoverEvidence;
    $handover->forceFill([
        'item_instance_id' => $fixture->serial->id, 'recipient_customer_id' => $fixture->owner->id,
        'handed_over_at' => RmaSchemaFixture::TIME, 'handover_reference' => 'handover-model',
        'evidence_document_id' => $document->id, 'verified_by' => $fixture->actor->id,
        'verified_at' => RmaSchemaFixture::TIME, 'command_id' => $command->id,
    ])->save();
    $assessment = new WarrantyAssessment;
    $assessment->forceFill([
        'repair_order_id' => $order->id, 'item_instance_id' => $fixture->serial->id,
        'subject_item_id' => $fixture->serial->item_id, 'version' => 1,
        'classification' => WarrantyClassification::Warranty, 'handover_evidence_id' => $handover->id,
        'product_warranty_rule_id' => $rule->id, 'rule_snapshot' => ['version' => 1],
        'warranty_start' => RmaSchemaFixture::TIME, 'warranty_end' => '2027-11-25 10:00:00.123456',
        'assessed_by' => $fixture->actor->id, 'assessed_at' => RmaSchemaFixture::TIME,
        'justification' => 'Fixture proof', 'is_override' => false, 'command_id' => $command->id,
    ])->save();
    $history = new RepairStatusHistory;
    $history->forceFill([
        'repair_request_id' => $request->id, 'repair_request_item_id' => $member->id,
        'item_instance_id' => $fixture->serial->id, 'state_dimension' => RepairStateDimension::Case,
        'to_state' => 'open', 'actor_id' => $fixture->actor->id, 'occurred_at' => RmaSchemaFixture::TIME,
        'reason' => 'Opened', 'metadata' => ['source' => 'model test'],
        'authorization_document_id' => $document->id, 'command_id' => $command->id, 'event_sequence' => 1,
    ])->save();

    // The member INSERT trigger is the only claim-acquisition mechanism.
    $claim = ActiveRepairClaim::findOrFail($fixture->serial->id);

    return [
        RepairCommand::class => $command->refresh(),
        RepairRequest::class => $request->refresh(),
        RepairRequestItem::class => $member->refresh(),
        RepairOrder::class => $order->refresh(),
        ActiveRepairClaim::class => $claim->refresh(),
        ProductWarrantyRule::class => $rule->refresh(),
        ProductHandoverEvidence::class => $handover->refresh(),
        WarrantyAssessment::class => $assessment->refresh(),
        RepairStatusHistory::class => $history->refresh(),
    ];
}

it('maps all nine identities timestamps and protected columns to the accepted schema', function (): void {
    $tables = [
        RepairCommand::class => 'repair_commands',
        RepairRequest::class => 'repair_requests',
        RepairRequestItem::class => 'repair_request_items',
        RepairOrder::class => 'repair_orders',
        ActiveRepairClaim::class => 'active_repair_claims',
        ProductWarrantyRule::class => 'product_warranty_rules',
        ProductHandoverEvidence::class => 'product_handover_evidences',
        WarrantyAssessment::class => 'warranty_assessments',
        RepairStatusHistory::class => 'repair_status_histories',
    ];
    foreach ($tables as $class => $table) {
        $model = new $class;
        expect($model->getTable())->toBe($table)
            ->and($model->getKeyName())->toBe($class === ActiveRepairClaim::class ? 'item_instance_id' : 'id')
            ->and($model->getKeyType())->toBe('int')
            ->and($model->getIncrementing())->toBe($class !== ActiveRepairClaim::class)
            ->and($model->usesTimestamps())->toBeTrue()
            ->and($model->getDateFormat())->toBe('Y-m-d H:i:s.u')
            ->and($model->getCreatedAtColumn())->toBe('created_at')
            ->and($model->getUpdatedAtColumn())->toBe(Schema::hasColumn($table, 'updated_at') ? 'updated_at' : null)
            ->and($model->getFillable())->toBe([])
            ->and(Schema::hasColumn($table, 'deleted_at'))->toBeFalse();
        foreach (Schema::getColumnListing($table) as $column) {
            expect($model->isFillable($column))->toBeFalse();
            expect(fn () => (new $class)->fill([$column => 'client value']))->toThrow(MassAssignmentException::class);
        }
    }
});

it('persists and hydrates every model with microseconds scalar casts JSON and database defaults', function (): void {
    Carbon::setTestNow(Carbon::parse(RmaSchemaFixture::TIME));
    try {
        $graph = rmaModelGraph();
        foreach ($graph as $model) {
            expect($model->getAttribute('created_at')->format('Y-m-d H:i:s.u'))->toBe(RmaSchemaFixture::TIME)
                ->and($model->getKey())->toBeInt();
            foreach ($model->getCasts() as $column => $cast) {
                $value = $model->getAttribute($column);
                if ($value === null) {
                    expect($model->getRawOriginal($column))->toBeNull();

                    continue;
                }
                if ($cast === 'integer' || $cast === 'int') {
                    expect($value)->toBeInt();
                } elseif ($cast === 'boolean') {
                    expect($value)->toBeBool();
                } elseif ($cast === 'array') {
                    expect($value)->toBeArray();
                } elseif ($cast === 'datetime') {
                    expect($value)->toBeInstanceOf(Carbon::class)
                        ->and($value->format('u'))->toBe('123456');
                }
            }
            if ($model->getUpdatedAtColumn() !== null) {
                expect($model->getAttribute('updated_at')->format('Y-m-d H:i:s.u'))->toBe(RmaSchemaFixture::TIME);
            } else {
                expect($model->getAttributes())->not->toHaveKey('updated_at');
            }
            // Serialization is safe even with non-UTF8 binary command attributes.
            expect(json_decode($model->toJson(), true, 512, JSON_THROW_ON_ERROR))->toBeArray();
        }
        expect($graph[RepairCommand::class]->status)->toBe(RepairCommandStatus::Processing)
            ->and($graph[RepairRequest::class]->status)->toBe(RepairRequestStatus::Open)
            ->and($graph[RepairOrder::class]->execution_status)->toBe(RepairExecutionStatus::AwaitingReceipt)
            ->and($graph[RepairOrder::class]->current_attempt_number)->toBe(1)
            ->and($graph[ProductWarrantyRule::class]->duration_unit)->toBe(WarrantyDurationUnit::Month)
            ->and($graph[WarrantyAssessment::class]->classification)->toBe(WarrantyClassification::Warranty)
            ->and($graph[RepairStatusHistory::class]->state_dimension)->toBe(RepairStateDimension::Case)
            ->and($graph[RepairRequest::class]->reporter_contact_snapshot)->toBe(['email' => 'reporter@example.test'])
            ->and($graph[RepairRequestItem::class]->origin_evidence)->toBe(['reference' => 'origin'])
            ->and($graph[WarrantyAssessment::class]->is_override)->toBeFalse();
        expect(DB::table('activity_log')->where('log_name', 'rma')->count())->toBe(0);
    } finally {
        Carbon::setTestNow();
    }
});

it('preserves raw binary command bytes and permits only the database-approved completion update', function (): void {
    $graph = rmaModelGraph();
    $command = $graph[RepairCommand::class];
    expect($command->command_type)->toBe("case_open\0\xFF")
        ->and($command->idempotency_key)->toBe("key\0\xFE")
        ->and($command->payload_hash)->toBe(hash('sha256', 'model fixture', true))
        ->and($command->result_snapshot)->toBeNull()
        ->and($command->completed_at)->toBeNull()
        ->and($command->result_id)->toBeNull()
        ->and($command->result_type)->toBeNull();
    $command->status = RepairCommandStatus::Completed;
    $command->completed_at = Carbon::parse(RmaSchemaFixture::TIME);
    $command->result_snapshot = ['case_id' => $graph[RepairRequest::class]->getKey()];
    $command->result_type = "case\0\xFF";
    $command->result_id = $graph[RepairRequest::class]->getKey();
    $command->save();
    $command->refresh();
    expect($command->status)->toBe(RepairCommandStatus::Completed)
        ->and($command->result_type)->toBe("case\0\xFF")
        ->and($command->result_snapshot)->toBe(['case_id' => $graph[RepairRequest::class]->getKey()])
        ->and($command->attributesToArray()['status'])->toBe('completed');
    foreach (['command_type', 'idempotency_key', 'payload_hash', 'result_type'] as $binary) {
        expect($command->attributesToArray())->not->toHaveKey($binary);
    }
    $command->result_snapshot = ['tampered' => true];
    expect(fn () => $command->save())->toThrow(QueryException::class);
});

it('navigates actual foreign keys and unique or plural inverses with eager loading', function (): void {
    $graph = rmaModelGraph();
    $edges = [
        [RepairCommand::class, 'actor', User::class, 'actor_id', 'repairCommands', 'HasMany'],
        [RepairRequest::class, 'ownerCustomer', Customer::class, 'owner_customer_id', 'repairRequests', 'HasMany'],
        [RepairRequest::class, 'creator', User::class, 'created_by', 'createdRepairRequests', 'HasMany'],
        [RepairRequest::class, 'creatorEmployee', Employee::class, 'creator_employee_id', 'createdRepairRequests', 'HasMany'],
        [RepairRequest::class, 'reporterEmployee', Employee::class, 'reporter_employee_id', 'reportedRepairRequests', 'HasMany'],
        [RepairRequest::class, 'closer', User::class, 'closed_by', 'closedRepairRequests', 'HasMany'],
        [RepairRequest::class, 'openingCommand', RepairCommand::class, 'opening_command_id', 'openedRepairRequest', 'HasOne'],
        [RepairRequest::class, 'closingCommand', RepairCommand::class, 'closing_command_id', 'closedRepairRequest', 'HasOne'],
        [RepairRequestItem::class, 'repairRequest', RepairRequest::class, 'repair_request_id', 'items', 'HasMany'],
        [RepairRequestItem::class, 'ownerCustomer', Customer::class, 'owner_customer_id', 'repairRequestItems', 'HasMany'],
        [RepairRequestItem::class, 'itemInstance', ItemInstance::class, 'item_instance_id', 'repairRequestItems', 'HasMany'],
        [RepairRequestItem::class, 'originProductionOrder', ProductionOrder::class, 'origin_production_order_id', 'repairRequestItems', 'HasMany'],
        [RepairRequestItem::class, 'originVerifier', User::class, 'origin_verified_by', 'originVerifiedRepairRequestItems', 'HasMany'],
        [RepairRequestItem::class, 'membershipCommand', RepairCommand::class, 'membership_command_id', 'requestItems', 'HasMany'],
        [RepairOrder::class, 'repairRequestItem', RepairRequestItem::class, 'repair_request_item_id', 'repairOrder', 'HasOne'],
        [ActiveRepairClaim::class, 'itemInstance', ItemInstance::class, 'item_instance_id', 'activeRepairClaim', 'HasOne'],
        [ActiveRepairClaim::class, 'repairRequestItem', RepairRequestItem::class, 'repair_request_item_id', 'activeRepairClaim', 'HasOne'],
        [ActiveRepairClaim::class, 'claimer', User::class, 'claimed_by', 'activeRepairClaims', 'HasMany'],
        [ActiveRepairClaim::class, 'command', RepairCommand::class, 'command_id', 'activeClaims', 'HasMany'],
        [ProductWarrantyRule::class, 'item', Item::class, 'item_id', 'productWarrantyRules', 'HasMany'],
        [ProductWarrantyRule::class, 'approver', User::class, 'approved_by', 'approvedProductWarrantyRules', 'HasMany'],
        [ProductWarrantyRule::class, 'command', RepairCommand::class, 'command_id', 'warrantyRules', 'HasMany'],
        [ProductHandoverEvidence::class, 'itemInstance', ItemInstance::class, 'item_instance_id', 'productHandoverEvidences', 'HasMany'],
        [ProductHandoverEvidence::class, 'recipientCustomer', Customer::class, 'recipient_customer_id', 'productHandoverEvidences', 'HasMany'],
        [ProductHandoverEvidence::class, 'evidenceDocument', Document::class, 'evidence_document_id', 'productHandoverEvidences', 'HasMany'],
        [ProductHandoverEvidence::class, 'verifier', User::class, 'verified_by', 'verifiedProductHandoverEvidences', 'HasMany'],
        [ProductHandoverEvidence::class, 'supersedesEvidence', ProductHandoverEvidence::class, 'supersedes_evidence_id', 'supersedingEvidence', 'HasOne'],
        [ProductHandoverEvidence::class, 'command', RepairCommand::class, 'command_id', 'handoverEvidence', 'HasOne'],
        [WarrantyAssessment::class, 'repairOrder', RepairOrder::class, 'repair_order_id', 'warrantyAssessments', 'HasMany'],
        [WarrantyAssessment::class, 'itemInstance', ItemInstance::class, 'item_instance_id', 'warrantyAssessments', 'HasMany'],
        [WarrantyAssessment::class, 'subjectItem', Item::class, 'subject_item_id', 'warrantyAssessments', 'HasMany'],
        [WarrantyAssessment::class, 'handoverEvidence', ProductHandoverEvidence::class, 'handover_evidence_id', 'warrantyAssessments', 'HasMany'],
        [WarrantyAssessment::class, 'productWarrantyRule', ProductWarrantyRule::class, 'product_warranty_rule_id', 'warrantyAssessments', 'HasMany'],
        [WarrantyAssessment::class, 'assessor', User::class, 'assessed_by', 'warrantyAssessments', 'HasMany'],
        [WarrantyAssessment::class, 'command', RepairCommand::class, 'command_id', 'warrantyAssessment', 'HasOne'],
        [RepairStatusHistory::class, 'repairRequest', RepairRequest::class, 'repair_request_id', 'statusHistories', 'HasMany'],
        [RepairStatusHistory::class, 'repairOrder', RepairOrder::class, 'repair_order_id', 'statusHistories', 'HasMany'],
        [RepairStatusHistory::class, 'repairRequestItem', RepairRequestItem::class, 'repair_request_item_id', 'statusHistories', 'HasMany'],
        [RepairStatusHistory::class, 'itemInstance', ItemInstance::class, 'item_instance_id', 'repairStatusHistories', 'HasMany'],
        [RepairStatusHistory::class, 'actor', User::class, 'actor_id', 'repairStatusHistories', 'HasMany'],
        [RepairStatusHistory::class, 'authorizationDocument', Document::class, 'authorization_document_id', 'repairStatusHistories', 'HasMany'],
        [RepairStatusHistory::class, 'command', RepairCommand::class, 'command_id', 'statusHistories', 'HasMany'],
    ];
    $covered = [];
    foreach ($edges as [$source, $navigation, $target, $foreignKey, $inverse, $kind]) {
        $row = $graph[$source];
        if (! method_exists($row, $navigation)) {
            throw new LogicException('Missing FK navigation: '.$navigation);
        }
        $relation = $row->{$navigation}();
        expect($relation->getForeignKeyName())->toBe($foreignKey)
            ->and($relation->getOwnerKeyName())->toBe('id')
            ->and($relation->getRelated()::class)->toBe($target);
        $row->load($navigation);
        $parent = $row->getRelation($navigation);
        $id = $row->getAttribute($foreignKey);
        if ($id === null) {
            expect($parent)->toBeNull();
        } else {
            expect($parent)->toBeInstanceOf($target)
                ->and($parent->getKey())->toBe($id);
            $parent->load($inverse);
            $back = $parent->getRelation($inverse);
            expect($parent->{$inverse}())->toBeInstanceOf('Illuminate\\Database\\Eloquent\\Relations\\'.$kind);
            if ($kind === 'HasMany') {
                expect($back->contains(fn (Model $candidate): bool => $candidate->is($row)))->toBeTrue();
            } else {
                expect($back->is($row))->toBeTrue();
            }
        }
        $covered[$row->getTable()][] = $foreignKey;
    }
    // Every single-column FK is navigable; composite integrity is still enforced by the DB.
    foreach ($graph as $row) {
        foreach (Schema::getForeignKeys($row->getTable()) as $fk) {
            if (count($fk['columns']) === 1) {
                expect($covered[$row->getTable()])->toContain($fk['columns'][0]);
            }
        }
    }
    $claim = $graph[ActiveRepairClaim::class];
    expect($claim->getKey())->toBe($graph[RepairRequestItem::class]->item_instance_id)
        ->and($claim->claimed_by)->toBe($graph[RepairCommand::class]->actor_id)
        ->and($claim->getAttributes())->not->toHaveKey('id')
        ->and(ActiveRepairClaim::count())->toBe(1);
});

it('keeps nullable proof relations and attributes null and supports order-targeted history', function (): void {
    $graph = rmaModelGraph();
    $fixture = new RmaSchemaFixture;
    $order = $graph[RepairOrder::class];
    $assessment = WarrantyAssessment::findOrFail($fixture->assessment($order->id, [
        'item_instance_id' => $graph[RepairRequestItem::class]->item_instance_id,
        'subject_item_id' => $graph[WarrantyAssessment::class]->subject_item_id, 'version' => 2,
    ]));
    foreach (['handover_evidence_id', 'product_warranty_rule_id', 'warranty_start', 'warranty_end', 'override_reason', 'handoverEvidence', 'productWarrantyRule'] as $field) {
        expect($assessment->getAttribute($field))->toBeNull();
    }
    expect($assessment->classification)->toBe(WarrantyClassification::PendingAssessment)
        ->and($order->warrantyAssessments()->count())->toBe(2)
        ->and($order->repairRequestItem->repairOrder->is($order))->toBeTrue();
    $history = new RepairStatusHistory;
    $history->forceFill([
        'repair_order_id' => $order->id, 'state_dimension' => RepairStateDimension::Warranty,
        'to_state' => 'pending_assessment', 'actor_id' => $graph[RepairCommand::class]->actor_id,
        'occurred_at' => RmaSchemaFixture::TIME, 'reason' => 'Pending', 'metadata' => [],
        'command_id' => $graph[RepairCommand::class]->id, 'event_sequence' => 2,
    ])->save();
    $history->refresh()->load(['repairRequest', 'repairOrder', 'repairRequestItem', 'itemInstance', 'authorizationDocument']);
    expect($history->repairOrder->is($order))->toBeTrue();
    foreach (['repairRequest', 'repairRequestItem', 'itemInstance', 'authorizationDocument', 'from_state'] as $field) {
        expect($history->getAttribute($field))->toBeNull();
    }
    $request = $graph[RepairRequest::class];
    expect($request->closed_at)->toBeNull()->and($request->closer)->toBeNull()->and($request->closingCommand)->toBeNull();
});

it('navigates append-only handover supersession in both directions without changing the prior evidence', function (): void {
    $graph = rmaModelGraph();
    $old = $graph[ProductHandoverEvidence::class];
    $new = new ProductHandoverEvidence;
    $attributes = $old->getAttributes();
    unset($attributes['id'], $attributes['created_at']);
    $attributes['command_id'] = (new RmaSchemaFixture)->command();
    $attributes['supersedes_evidence_id'] = $old->id;
    $new->forceFill($attributes)->save();
    expect($new->fresh()->supersedesEvidence->is($old))->toBeTrue()
        ->and($old->fresh()->supersedingEvidence->is($new))->toBeTrue()
        ->and($old->fresh()->supersedesEvidence)->toBeNull();
});

it('keeps historical parent navigation after permitted soft deletion', function (): void {
    $graph = rmaModelGraph();
    $request = $graph[RepairRequest::class];
    $customer = $request->ownerCustomer;
    $employee = $request->creatorEmployee;
    $item = $graph[ProductWarrantyRule::class]->item;
    $origin = $graph[RepairRequestItem::class]->originProductionOrder;
    foreach ([$customer, $employee, $item, $origin] as $parent) {
        $parent->delete();
    }
    expect($request->fresh()->ownerCustomer->is($customer))->toBeTrue()
        ->and($request->fresh()->creatorEmployee->is($employee))->toBeTrue()
        ->and($request->fresh()->reporterEmployee->is($employee))->toBeTrue()
        ->and($graph[ProductWarrantyRule::class]->fresh()->item->is($item))->toBeTrue()
        ->and($graph[RepairRequestItem::class]->fresh()->originProductionOrder->is($origin))->toBeTrue();
});

it('rejects ordinary Eloquent updates and deletes of immutable rows at the database boundary', function (string $class, string $field, mixed $value, string $operation): void {
    $row = rmaModelGraph()[$class];
    if ($operation === 'update') {
        $row->setAttribute($field, $value);
    }
    expect(fn () => $operation === 'update' ? $row->save() : $row->delete())->toThrow(QueryException::class);
})->with([
    'RepairCommand update' => [RepairCommand::class, 'actor_id', 0, 'update'],
    'RepairCommand delete' => [RepairCommand::class, 'actor_id', 0, 'delete'],
    'RepairRequest update' => [RepairRequest::class, 'reporter_name', 'changed', 'update'],
    'RepairRequest delete' => [RepairRequest::class, 'reporter_name', 'changed', 'delete'],
    'RepairRequestItem update' => [RepairRequestItem::class, 'reported_fault', 'changed', 'update'],
    'RepairRequestItem delete' => [RepairRequestItem::class, 'reported_fault', 'changed', 'delete'],
    'RepairOrder update' => [RepairOrder::class, 'current_attempt_number', 2, 'update'],
    'RepairOrder delete' => [RepairOrder::class, 'current_attempt_number', 2, 'delete'],
    'ActiveRepairClaim update' => [ActiveRepairClaim::class, 'claimed_by', 0, 'update'],
    'ActiveRepairClaim delete' => [ActiveRepairClaim::class, 'claimed_by', 0, 'delete'],
    'ProductWarrantyRule update' => [ProductWarrantyRule::class, 'duration_value', 99, 'update'],
    'ProductWarrantyRule delete' => [ProductWarrantyRule::class, 'duration_value', 99, 'delete'],
    'ProductHandoverEvidence update' => [ProductHandoverEvidence::class, 'handover_reference', 'changed', 'update'],
    'ProductHandoverEvidence delete' => [ProductHandoverEvidence::class, 'handover_reference', 'changed', 'delete'],
    'WarrantyAssessment update' => [WarrantyAssessment::class, 'justification', 'changed', 'update'],
    'WarrantyAssessment delete' => [WarrantyAssessment::class, 'justification', 'changed', 'delete'],
    'RepairStatusHistory update' => [RepairStatusHistory::class, 'reason', 'changed', 'update'],
    'RepairStatusHistory delete' => [RepairStatusHistory::class, 'reason', 'changed', 'delete'],
]);

it('does not let Eloquent persistence escape composite foreign keys or evidence protection', function (): void {
    $graph = rmaModelGraph();
    $member = new RepairRequestItem;
    $fixture = new RmaSchemaFixture;
    $member->forceFill($fixture->memberData($graph[RepairRequest::class]->id));
    expect(fn () => $member->save())->toThrow(QueryException::class);
    $proof = $graph[ProductHandoverEvidence::class]->evidenceDocument;
    $proof->checksum = str_repeat('f', 64);
    expect(fn () => $proof->save())->toThrow(QueryException::class);
    expect(fn () => $proof->fresh()->delete())->toThrow(QueryException::class);
});

it('round-trips exactly the approved enum values through casts and serialization', function (): void {
    $fields = [
        [RepairCommand::class, 'status', RepairCommandStatus::class, ['processing', 'completed']],
        [RepairRequest::class, 'status', RepairRequestStatus::class, ['open']],
        [RepairOrder::class, 'execution_status', RepairExecutionStatus::class, ['awaiting_receipt']],
        [ProductWarrantyRule::class, 'duration_unit', WarrantyDurationUnit::class, ['day', 'month', 'year']],
        [WarrantyAssessment::class, 'classification', WarrantyClassification::class, ['warranty', 'non_warranty', 'pending_assessment']],
        [RepairStatusHistory::class, 'state_dimension', RepairStateDimension::class, ['case', 'claim', 'warranty']],
    ];
    foreach ($fields as [$class, $field, $enum, $values]) {
        expect(array_column($enum::cases(), 'value'))->toBe($values);
        foreach ($enum::cases() as $case) {
            $model = (new $class)->newFromBuilder([$field => $case->value]);
            expect($model->getAttribute($field))->toBe($case)
                ->and($model->attributesToArray()[$field])->toBe($case->value);
        }
        expect((new $class)->newFromBuilder([$field => null])->getAttribute($field))->toBeNull();
    }
});

it('rejects unsupported case closure both in enum assignment and database-backed mass updates', function (): void {
    $request = rmaModelGraph()[RepairRequest::class];
    expect(fn () => $request->setAttribute('status', 'closed'))->toThrow(ValueError::class);
    expect(fn () => RepairRequest::query()->whereKey($request->getKey())->update(['status' => 'closed']))
        ->toThrow(QueryException::class);
    expect($request->fresh()->status)->toBe(RepairRequestStatus::Open);
});
