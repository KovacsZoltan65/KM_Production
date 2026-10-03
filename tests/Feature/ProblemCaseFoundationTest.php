<?php

use App\Enums\ProblemCaseEvaluationResult;
use App\Enums\ProblemCaseLifecycle;
use App\Enums\ProblemCaseType;
use App\Models\BomItem;
use App\Models\MaterialRequirement;
use App\Models\ProblemCase;
use App\Models\ProblemCaseEvaluation;
use App\Models\ProductionOrder;
use App\Repositories\Contracts\ProblemCaseRepositoryInterface;
use App\Support\MaterialPlanning\MaterialRequirementNettingResult;
use App\Support\Merlin\MaterialShortageDetectionSnapshot;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/** Sort object keys recursively while preserving scalar types and list order. */
function problemCaseCanonicalJson(mixed $value): mixed
{
    if (! is_array($value)) {
        return $value;
    }

    $value = array_map(problemCaseCanonicalJson(...), $value);

    if (! array_is_list($value)) {
        ksort($value);
    }

    return $value;
}

/** @return array{MaterialRequirement, MaterialShortageDetectionSnapshot, ProblemCaseRepositoryInterface} */
function problemCaseFixture(string $detectedAt = '2026-10-03T08:00:00Z'): array
{
    $productionOrder = ProductionOrder::factory()->create();
    $bomItem = BomItem::factory()->create(['bom_id' => $productionOrder->bom_id]);
    $requirement = MaterialRequirement::factory()->create([
        'customer_order_item_id' => $productionOrder->customer_order_item_id,
        'production_order_id' => $productionOrder->id,
        'bom_item_id' => $bomItem->id,
        'required_item_id' => $bomItem->item_id,
        'required_quantity' => '12.000',
        'required_at' => '2026-10-10',
        'unit' => 'kg',
    ]);
    $item = $requirement->requiredItem;
    $snapshot = new MaterialShortageDetectionSnapshot(
        netting: new MaterialRequirementNettingResult(
            requirementId: $requirement->id,
            productionOrderId: $productionOrder->id,
            bomItemId: $bomItem->id,
            requiredItemId: $item->id,
            requiredAt: '2026-10-10',
            unit: 'kg',
            grossRequirement: '12.000',
            onHandCoverage: '4.000',
            incomingCoverage: '0.000',
            netRequirement: '8.000',
            allocations: [['source_type' => 'stock_balance', 'source_id' => 123, 'quantity' => '4.000', 'supply_at' => null]],
        ),
        customerOrderItemId: $requirement->customer_order_item_id,
        customerOrderId: $requirement->customerOrderItem->customer_order_id,
        itemIdentifier: $item->item_number,
        itemName: $item->name,
        detectedAt: CarbonImmutable::parse($detectedAt),
        detectionSource: 'SYSTEM',
        nettingProvenance: ['producer' => 'MaterialRequirementNettingService', 'rules' => '0009'],
        nettingScope: ['kind' => 'complete_item_requirements', 'item_ids' => [$item->id], 'requirement_ids' => [$requirement->id, 999]],
        evidence: ['competing_requirement_ids' => [999], 'quantity_basis' => 'net_requirement'],
    );
    $repository = app(ProblemCaseRepositoryInterface::class);

    return [$requirement, $snapshot, $repository];
}

it('persists a case with its own stable identity and creation evaluation', function (): void {
    [$requirement, $snapshot, $repository] = problemCaseFixture();
    $case = $repository->createMaterialShortage($snapshot, ProblemCaseEvaluationResult::Active)->refresh();
    $initial = $case->evaluations->sole();

    expect(Str::isUuid($case->id))->toBeTrue()
        ->and($case->id)->not->toBe((string) $requirement->id)
        ->and($case->type)->toBe(ProblemCaseType::MaterialShortage)
        ->and($case->lifecycle)->toBe(ProblemCaseLifecycle::Open)
        ->and($case->current_evaluation)->toBe(ProblemCaseEvaluationResult::Active)
        ->and($case->current_evaluated_at->toIso8601String())->toBe('2026-10-03T08:00:00+00:00')
        ->and(problemCaseCanonicalJson($case->detection_snapshot))->toBe(problemCaseCanonicalJson($snapshot->toArray()))
        ->and($case->materialRequirement->is($requirement))->toBeTrue()
        ->and($requirement->problemCases->sole()->is($case))->toBeTrue()
        ->and($initial->problemCase->is($case))->toBeTrue()
        ->and($initial->result)->toBe(ProblemCaseEvaluationResult::Active)
        ->and($initial->evaluated_at->equalTo($case->current_evaluated_at))->toBeTrue()
        ->and($initial->recorded_for)->toBe('case_creation')
        ->and(problemCaseCanonicalJson($initial->evidence))->toBe(problemCaseCanonicalJson($snapshot->evaluationEvidence()))
        ->and(problemCaseCanonicalJson($case->current_evaluation_evidence))->toBe(problemCaseCanonicalJson($initial->evidence));

    $identity = $case->id;
    expect($case->fresh()->id)->toBe($identity);
});

it('preserves the same absolute detection instant in UTC evaluation timestamps', function (): void {
    [$requirement, $snapshot, $repository] = problemCaseFixture('2026-10-03T10:00:00+02:00');
    $case = $repository->createMaterialShortage($snapshot, ProblemCaseEvaluationResult::Active)->refresh();
    $initial = $case->evaluations()->sole();
    $detectedAt = CarbonImmutable::parse($case->detection_snapshot['detected_at']);

    expect($case->detection_snapshot['detected_at'])->toBe('2026-10-03T08:00:00+00:00')
        ->and($case->getRawOriginal('current_evaluated_at'))->toBe('2026-10-03 08:00:00')
        ->and($initial->getRawOriginal('evaluated_at'))->toBe('2026-10-03 08:00:00')
        ->and($detectedAt->equalTo($snapshot->detectedAt))->toBeTrue()
        ->and($case->current_evaluated_at->equalTo($detectedAt))->toBeTrue()
        ->and($initial->evaluated_at->equalTo($detectedAt))->toBeTrue()
        ->and($snapshot->detectedAt->toIso8601String())->toBe('2026-10-03T10:00:00+02:00');
});

it('ignores nested JSON object key order while preserving types and list order', function (): void {
    $expected = ['nested' => ['quantity' => '8.000', 'ids' => [1, 2]], 'version' => 1];
    $reordered = ['version' => 1, 'nested' => ['ids' => [1, 2], 'quantity' => '8.000']];
    $changedType = $reordered;
    $changedType['version'] = '1';
    $changedList = $reordered;
    $changedList['nested']['ids'] = [2, 1];

    expect(problemCaseCanonicalJson($reordered))->toBe(problemCaseCanonicalJson($expected))
        ->and(problemCaseCanonicalJson($changedType))->not->toBe(problemCaseCanonicalJson($expected))
        ->and(problemCaseCanonicalJson($changedList))->not->toBe(problemCaseCanonicalJson($expected));
});

it('allows distinct case identities for the same requirement without defining event deduplication', function (): void {
    [$requirement, $snapshot, $repository] = problemCaseFixture();
    $first = $repository->createMaterialShortage($snapshot, ProblemCaseEvaluationResult::Active);
    $second = $repository->createMaterialShortage($snapshot, ProblemCaseEvaluationResult::Active);

    expect($first->id)->not->toBe($second->id)
        ->and($requirement->problemCases()->count())->toBe(2)
        ->and($first->evaluations()->count())->toBe(1)
        ->and($second->evaluations()->count())->toBe(1);
});

it('keeps current evaluation separate from lifecycle and historical evidence', function (): void {
    [$requirement, $snapshot, $repository] = problemCaseFixture();
    $case = $repository->createMaterialShortage($snapshot, ProblemCaseEvaluationResult::Active);
    $identity = $case->id;
    $detectionSnapshot = $case->detection_snapshot;
    $initial = $case->evaluations()->sole();

    // Storage capability only: this is not a resolver or a transition workflow.
    foreach ([ProblemCaseEvaluationResult::Resolved, ProblemCaseEvaluationResult::Undetermined] as $result) {
        $case->update([
            'current_evaluation' => $result,
            'current_evaluated_at' => '2026-10-04 08:00:00',
            'current_evaluation_evidence' => ['information' => 'supplied by future backend evaluator'],
        ]);
        $case->refresh();

        expect($case->lifecycle)->toBe(ProblemCaseLifecycle::Open)
            ->and($case->id)->toBe($identity)
            ->and($case->current_evaluation)->toBe($result)
            ->and(problemCaseCanonicalJson($case->detection_snapshot))->toBe(problemCaseCanonicalJson($detectionSnapshot))
            ->and($initial->fresh()->result)->toBe(ProblemCaseEvaluationResult::Active)
            ->and(problemCaseCanonicalJson($initial->fresh()->evidence))->toBe(problemCaseCanonicalJson($snapshot->evaluationEvidence()))
            ->and($case->evaluations()->count())->toBe(1);
    }

    foreach ([ProblemCaseLifecycle::Closed, ProblemCaseLifecycle::Invalidated] as $lifecycle) {
        $case->update(['lifecycle' => $lifecycle]);
        expect($case->fresh()->lifecycle)->toBe($lifecycle)
            ->and($case->fresh()->current_evaluation)->toBe(ProblemCaseEvaluationResult::Undetermined)
            ->and($case->evaluations()->count())->toBe(1);
    }
});

it('preserves readable detection facts after item and requirement changes', function (): void {
    [$requirement, $snapshot, $repository] = problemCaseFixture();
    $case = $repository->createMaterialShortage($snapshot, ProblemCaseEvaluationResult::Active);
    $requirement->requiredItem->update(['item_number' => 'CHANGED', 'name' => 'Changed name']);
    $requirement->update(['required_quantity' => '20.000', 'required_at' => '2026-11-01', 'missing_quantity' => '0.000']);
    $requirement->delete();

    expect(problemCaseCanonicalJson($case->fresh()->detection_snapshot))->toBe(problemCaseCanonicalJson($snapshot->toArray()))
        ->and($case->fresh()->materialRequirement->trashed())->toBeTrue()
        ->and($case->fresh()->current_evaluation)->toBe(ProblemCaseEvaluationResult::Active)
        ->and($case->evaluations()->sole()->evidence['netting']['net_requirement'])->toBe('8.000');
});

it('rejects changes to immutable case identity and detection evidence', function (string $attribute): void {
    [$requirement, $snapshot, $repository] = problemCaseFixture();
    $case = $repository->createMaterialShortage($snapshot, ProblemCaseEvaluationResult::Active);
    $original = $case->refresh()->getRawOriginal();
    $case->setAttribute($attribute, match ($attribute) {
        'id' => (string) Str::uuid(),
        'material_requirement_id' => $requirement->id + 1,
        'type' => null,
        'detection_snapshot' => ['changed' => true],
        default => throw new InvalidArgumentException('Unknown immutable attribute'),
    });

    expect(fn () => $case->save())->toThrow(LogicException::class);
    expect(ProblemCase::query()->findOrFail($original['id'])->getRawOriginal())->toBe($original);
})->with(['id', 'type', 'material_requirement_id', 'detection_snapshot']);

it('supports explicit history append without rewriting existing evaluations or current state', function (): void {
    [$requirement, $snapshot, $repository] = problemCaseFixture();
    $case = $repository->createMaterialShortage($snapshot, ProblemCaseEvaluationResult::Active);
    $initial = $case->evaluations()->sole();
    $later = $case->evaluations()->create([
        'result' => ProblemCaseEvaluationResult::Resolved,
        'evaluated_at' => '2026-10-04 08:00:00',
        'recorded_for' => 'evaluation_change',
        'evidence' => ['net_requirement' => '0.000'],
    ]);

    expect($case->evaluations()->pluck('id')->all())->toBe([$initial->id, $later->id])
        ->and($initial->fresh()->result)->toBe(ProblemCaseEvaluationResult::Active)
        ->and($case->fresh()->current_evaluation)->toBe(ProblemCaseEvaluationResult::Active);

    expect(fn () => $initial->update(['evidence' => ['changed' => true]]))->toThrow(LogicException::class);
    expect(fn () => $initial->delete())->toThrow(LogicException::class);
    expect(fn () => $case->delete())->toThrow(LogicException::class);
    expect(problemCaseCanonicalJson($initial->fresh()->evidence))->toBe(problemCaseCanonicalJson($snapshot->evaluationEvidence()));
});

it('preserves history by restricting hard deletion of the requirement', function (): void {
    [$requirement, $snapshot, $repository] = problemCaseFixture();
    $repository->createMaterialShortage($snapshot, ProblemCaseEvaluationResult::Active);
    expect(fn () => $requirement->forceDelete())->toThrow(QueryException::class);
    expect(ProblemCase::query()->count())->toBe(1)
        ->and(ProblemCaseEvaluation::query()->count())->toBe(1);
});

it('requires a real material requirement at the database boundary', function (): void {
    [$requirement, $snapshot, $repository] = problemCaseFixture();
    expect(fn () => ProblemCase::query()->create([
        'type' => ProblemCaseType::MaterialShortage,
        'material_requirement_id' => 999999999,
        'lifecycle' => ProblemCaseLifecycle::Open,
        'detection_snapshot' => $snapshot->toArray(),
        'current_evaluation' => ProblemCaseEvaluationResult::Active,
        'current_evaluated_at' => $snapshot->detectedAt,
        'current_evaluation_evidence' => $snapshot->evaluationEvidence(),
    ]))->toThrow(QueryException::class);
});

it('rolls back case persistence when creation history cannot be saved', function (): void {
    [$requirement, $snapshot, $repository] = problemCaseFixture();
    $event = 'eloquent.creating: '.ProblemCaseEvaluation::class;
    Event::listen($event, function (): void {
        throw new RuntimeException('History write failure');
    });

    try {
        expect(fn () => $repository->createMaterialShortage($snapshot, ProblemCaseEvaluationResult::Active))
            ->toThrow(RuntimeException::class, 'History write failure');
    } finally {
        Event::forget($event);
    }

    expect(ProblemCase::query()->count())->toBe(0)
        ->and(ProblemCaseEvaluation::query()->count())->toBe(0);
});

it('round trips only the new migration without changing existing requirements', function (): void {
    [$requirement, $snapshot, $repository] = problemCaseFixture();
    $migration = require database_path('migrations/2026_10_03_000001_create_problem_case_foundation.php');
    $migration->down();
    expect(Schema::hasTable('problem_cases'))->toBeFalse()
        ->and(Schema::hasTable('problem_case_evaluations'))->toBeFalse()
        ->and($requirement->fresh()->id)->toBe($requirement->id);

    $migration->up();
    $case = $repository->createMaterialShortage($snapshot, ProblemCaseEvaluationResult::Active);
    expect($case->evaluations()->count())->toBe(1);
});
