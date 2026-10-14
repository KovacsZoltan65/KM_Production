<?php

use App\Enums\MerlinAdmissionOutcome;
use App\Support\Merlin\MerlinAdmissionResult;
use App\Support\Merlin\MerlinRunConfiguration;
use App\Support\Merlin\StartInvestigationRequest;

it('normalizes strict UUIDs and emits the exact versioned canonical request and fingerprint', function (): void {
    $request = StartInvestigationRequest::fromArray([
        'idempotency_key' => 'AA123456-7890-4ABC-8DEF-1234567890AB',
        'problem_case_id' => 'BB123456-7890-4ABC-8DEF-1234567890AB',
    ]);
    $bytes = '{"schema_version":1,"problem_case_id":"bb123456-7890-4abc-8def-1234567890ab"}';
    expect($request->canonicalRequest())->toBe($bytes)
        ->and($request->fingerprint())->toBe(hash('sha256', $bytes))
        ->and($request->idempotencyKey)->toBe('aa123456-7890-4abc-8def-1234567890ab')
        ->and(StartInvestigationRequest::SCHEMA_VERSION)->toBe(1)
        ->and(StartInvestigationRequest::FINGERPRINT_VERSION)->toBe(1);
    expect($request->persistenceAttributes()['request_fingerprint'])->toBe(hash('sha256', $bytes));
    $otherKey = StartInvestigationRequest::fromArray([
        'problem_case_id' => $request->problemCaseId,
        'idempotency_key' => 'cc123456-7890-4abc-8def-1234567890ab',
    ]);
    expect($otherKey->canonicalRequest())->toBe($bytes)->and($otherKey->fingerprint())->toBe($request->fingerprint());
});

it('rejects missing unexpected malformed and whitespace containing request fields', function (array $change): void {
    $input = ['problem_case_id' => 'bb123456-7890-4abc-8def-1234567890ab', 'idempotency_key' => 'aa123456-7890-4abc-8def-1234567890ab'];
    if (isset($change['remove'])) {
        unset($input[$change['remove']]);
    } else {
        $input = [...$input, ...$change];
    }
    expect(fn () => StartInvestigationRequest::fromArray($input))->toThrow(InvalidArgumentException::class);
})->with([
    [['remove' => 'idempotency_key']], [['actor_id' => 1]], [['configuration' => ['max_model_turns' => 999]]],
    [['problem_case_id' => 'invalid']], [['idempotency_key' => null]], [['problem_case_id' => 123]],
    [['problem_case_id' => ' bb123456-7890-4abc-8def-1234567890ab']],
    [['idempotency_key' => "aa123456-7890-4abc-8def-1234567890ab\n"]],
    [['idempotency_key' => 'aa12345678904abc8def1234567890ab']],
]);

it('keeps backend limits immutable and missing cost explicitly unable to enable paid execution', function (): void {
    $configuration = new MerlinRunConfiguration;
    expect(MerlinRunConfiguration::SCHEMA_VERSION)->toBe(1)
        ->and($configuration->persistenceAttributes()['configuration_schema_version'])->toBe(1)
        ->and($configuration->toArray())->toBe([
            'max_model_turns' => 8, 'max_tool_call_attempts' => 4, 'max_running_seconds' => 180,
            'cost_ceiling' => ['state' => 'NOT_CONFIGURED'], 'paid_execution_enabled' => false,
        ]);
    $copy = $configuration->toArray();
    $copy['max_model_turns'] = 999;
    $copy['paid_execution_enabled'] = true;
    expect($configuration->toArray()['max_model_turns'])->toBe(8)
        ->and($configuration->toArray()['paid_execution_enabled'])->toBeFalse();
    $request = StartInvestigationRequest::fromArray([
        'problem_case_id' => 'bb123456-7890-4abc-8def-1234567890ab', 'idempotency_key' => 'aa123456-7890-4abc-8def-1234567890ab',
    ]);
    expect($request->canonicalRequest())->not->toContain('configuration', 'actor', 'idempotency', 'cost')
        ->and(json_encode($configuration->toArray(), JSON_THROW_ON_ERROR))->not->toContain('secret', 'prompt', 'permission', 'provider', 'currency');
});

it('allows only a minimal run reference for success and no run disclosure on rejection', function (MerlinAdmissionOutcome $outcome): void {
    $success = in_array($outcome, [MerlinAdmissionOutcome::Created, MerlinAdmissionOutcome::Replayed], true);
    $runId = 'bb123456-7890-4abc-8def-1234567890ab';
    $result = new MerlinAdmissionResult($outcome, $success ? $runId : null);
    expect($result->outcome)->toBe($outcome)->and($result->runId)->toBe($success ? $runId : null);
    expect(fn () => new MerlinAdmissionResult($outcome, $success ? null : $runId))->toThrow(InvalidArgumentException::class);
})->with(MerlinAdmissionOutcome::cases());
