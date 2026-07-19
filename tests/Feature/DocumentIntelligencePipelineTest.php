<?php

use App\Enums\AiProcessingRunStatus;
use App\Enums\DocumentProcessingStatus;
use App\Jobs\AI\ProcessDocumentJob;
use App\Models\AiProcessingRun;
use App\Models\Document;
use App\Services\AI\AiProcessingTelemetryService;
use App\Services\AI\PythonAiEngineService;
use App\Services\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Mockery\CompositeExpectation;
use Mockery\MockInterface;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\Process\Process;

uses(RefreshDatabase::class);

function documentClassificationResult(float $confidence, string $suggestedType = 'delivery_note'): array
{
    return [
        'success' => true,
        'engine' => 'python-ai-engine',
        'version' => '0.1.0',
        'task' => 'document_classification',
        'confidence' => $confidence,
        'classification' => 'unknown',
        'data' => [
            'suggested_type' => $suggestedType,
        ],
        'errors' => [],
    ];
}

function documentOcrResult(string $text = 'Detected OCR text'): array
{
    return [
        'success' => true,
        'engine' => 'python-ai-engine',
        'version' => '0.1.0',
        'task' => 'document_ocr',
        'confidence' => 0.75,
        'data' => [
            'text' => $text,
            'language' => 'unknown',
            'pages' => [],
            'backend' => 'stub',
        ],
        'errors' => [],
    ];
}

function documentOcrUnavailableResult(): array
{
    return [
        'success' => false,
        'engine' => 'python-ai-engine',
        'version' => '0.1.0',
        'task' => 'document_ocr',
        'confidence' => 0.0,
        'data' => [
            'text' => '',
            'language' => 'unknown',
            'pages' => [],
            'backend' => null,
        ],
        'errors' => [
            [
                'code' => 'ocr_backend_unavailable',
                'message' => 'No OCR backend is configured or available.',
            ],
        ],
    ];
}

function runDocumentIntelligenceJob(Document $document, array ...$engineResults): Document
{
    $engine = Mockery::mock(PythonAiEngineService::class, function (MockInterface $mock) use ($document, $engineResults): void {
        $expectation = $mock->shouldReceive('run');

        if (! $expectation instanceof CompositeExpectation) {
            throw new LogicException('Mockery did not create a concrete method expectation.');
        }

        $expectation->__call('times', [count($engineResults)]);
        $expectation->__call('withArgs', [function (array $payload) use ($document): bool {
            return in_array($payload['task'] ?? null, ['document_classification', 'document_ocr'], true)
                && ($payload['document']['id'] ?? null) === $document->id
                && ($payload['document']['filename'] ?? null) === $document->original_filename;
        }]);
        $expectation->andReturn(...$engineResults);
    });

    if (! $engine instanceof PythonAiEngineService) {
        throw new LogicException('Mockery did not create the requested AI engine test double.');
    }

    (new ProcessDocumentJob($document->id))->handle(
        $engine,
        app(AuditLogService::class),
        app(AiProcessingTelemetryService::class),
    );

    return $document->refresh();
}

it('dispatches the document processing job', function (): void {
    Bus::fake();

    $document = Document::factory()->create();

    ProcessDocumentJob::dispatch($document->id);

    Bus::assertDispatched(ProcessDocumentJob::class, fn (ProcessDocumentJob $job): bool => $job->documentId === $document->id);
});

it('python classifier stub returns a document classification result', function (): void {
    $process = new Process([
        (string) config('ai.python_binary', 'python'),
        base_path('python/ai_engine.py'),
    ], base_path(), null, json_encode([
        'task' => 'document_classification',
        'document' => [
            'filename' => 'delivery_note.pdf',
        ],
    ]));

    $process->run();

    expect($process->isSuccessful())->toBeTrue();

    $result = json_decode($process->getOutput(), true);

    expect($result['success'])->toBeTrue();
    expect($result['task'])->toBe('document_classification');
    expect($result['confidence'])->toBe(0.82);
    expect($result['classification'])->toBe('unknown');
    expect($result['data']['suggested_type'])->toBe('delivery_note');
});

it('python ocr task returns structured unavailable json without a backend', function (): void {
    $process = new Process([
        (string) config('ai.python_binary', 'python'),
        base_path('python/ai_engine.py'),
    ], base_path(), null, json_encode([
        'task' => 'document_ocr',
        'document' => [
            'filename' => 'delivery_note.pdf',
            'path' => base_path('missing-delivery-note.pdf'),
            'mime_type' => 'application/pdf',
        ],
        'options' => [
            'backend' => null,
        ],
    ]));

    $process->run();

    expect($process->isSuccessful())->toBeTrue();

    $result = json_decode($process->getOutput(), true);

    expect($result['success'])->toBeFalse();
    expect($result['task'])->toBe('document_ocr');
    expect($result['confidence'])->toBe(0.0);
    expect($result['data']['text'])->toBe('');
    expect($result['data']['language'])->toBe('unknown');
    expect($result['data']['pages'])->toBe([]);
    expect($result['data']['backend'])->toBeNull();
    expect($result['errors'][0]['code'])->toBe('ocr_backend_unavailable');
});

it('python ocr task returns structured error for unknown backend', function (): void {
    $process = new Process([
        (string) config('ai.python_binary', 'python'),
        base_path('python/ai_engine.py'),
    ], base_path(), null, json_encode([
        'task' => 'document_ocr',
        'document' => [
            'filename' => 'delivery_note.pdf',
            'path' => base_path('missing-delivery-note.pdf'),
            'mime_type' => 'application/pdf',
        ],
        'options' => [
            'backend' => 'future-backend',
        ],
    ]));

    $process->run();

    expect($process->isSuccessful())->toBeTrue();

    $result = json_decode($process->getOutput(), true);

    expect($result['success'])->toBeFalse();
    expect($result['task'])->toBe('document_ocr');
    expect($result['confidence'])->toBe(0.0);
    expect($result['data']['text'])->toBe('');
    expect($result['data']['language'])->toBe('unknown');
    expect($result['data']['pages'])->toBe([]);
    expect($result['data']['backend'])->toBeNull();
    expect($result['errors'][0]['code'])->toBe('ocr_backend_unknown');
});

it('python txt fallback ocr works deterministically with the stub backend', function (): void {
    $path = storage_path('framework/testing/ocr-sample.txt');
    file_put_contents($path, 'Plain text OCR fixture');

    $process = new Process([
        (string) config('ai.python_binary', 'python'),
        base_path('python/ai_engine.py'),
    ], base_path(), null, json_encode([
        'task' => 'document_ocr',
        'document' => [
            'filename' => 'ocr-sample.txt',
            'path' => $path,
            'mime_type' => 'text/plain',
        ],
        'options' => [
            'backend' => 'stub',
            'max_text_bytes' => 20000,
        ],
    ]));

    $process->run();

    expect($process->isSuccessful())->toBeTrue();

    $result = json_decode($process->getOutput(), true);

    expect($result['success'])->toBeTrue();
    expect($result['task'])->toBe('document_ocr');
    expect($result['confidence'])->toBe(0.75);
    expect($result['data']['text'])->toBe('Plain text OCR fixture');
    expect($result['data']['backend'])->toBe('stub');
    expect($result['errors'])->toBe([]);
});

it('marks high confidence classification as completed', function (): void {
    $document = Document::factory()->create(['original_filename' => 'delivery_note.pdf']);

    $document = runDocumentIntelligenceJob($document, documentClassificationResult(0.97));

    expect($document->processing_status)->toBe(DocumentProcessingStatus::Completed);
    expect($document->processing_confidence)->toBe(0.97);
    expect($document->processing_result['data']['suggested_type'])->toBe('delivery_note');
    expect($document->processed_at)->not->toBeNull();

    expect(Activity::query()->where('event', 'document_ai_processing_started')->exists())->toBeTrue();
    expect(Activity::query()->where('event', 'document_ai_classification_returned')->exists())->toBeTrue();
    expect(Activity::query()->where('event', 'document_ai_processing_completed')->exists())->toBeTrue();
});

it('creates telemetry for classification runs', function (): void {
    $document = Document::factory()->create(['original_filename' => 'delivery_note.pdf']);

    runDocumentIntelligenceJob($document, documentClassificationResult(0.97));

    $run = AiProcessingRun::query()
        ->whereMorphedTo('processable', $document)
        ->where('task', 'document_classification')
        ->firstOrFail();

    expect($run->status)->toBe(AiProcessingRunStatus::Completed);
    expect($run->success)->toBeTrue();
    expect($run->engine)->toBe('python-ai-engine');
    expect($run->engine_version)->toBe('0.1.0');
    expect($run->confidence)->toBe(0.97);
    expect($run->duration_ms)->not->toBeNull();
    expect($run->result_summary)->toMatchArray([
        'suggested_type' => 'delivery_note',
        'classification' => 'unknown',
        'confidence' => 0.97,
    ]);
});

it('captures completed telemetry status', function (): void {
    $document = Document::factory()->create(['original_filename' => 'delivery_note.pdf']);

    runDocumentIntelligenceJob($document, documentClassificationResult(0.97));

    expect(AiProcessingRun::query()->sole()->status)->toBe(AiProcessingRunStatus::Completed);
});

it('marks medium confidence classification as review required', function (): void {
    $document = Document::factory()->create(['original_filename' => 'delivery_note.pdf']);

    $document = runDocumentIntelligenceJob($document, documentClassificationResult(0.82));

    expect($document->processing_status)->toBe(DocumentProcessingStatus::ReviewRequired);
    expect($document->processing_confidence)->toBe(0.82);
    expect($document->processing_error)->toBeNull();

    expect(Activity::query()->where('event', 'document_ai_review_required')->exists())->toBeTrue();
});

it('captures review required telemetry status', function (): void {
    $document = Document::factory()->create(['original_filename' => 'delivery_note.pdf']);

    runDocumentIntelligenceJob($document, documentClassificationResult(0.82));

    $run = AiProcessingRun::query()->sole();

    expect($run->status)->toBe(AiProcessingRunStatus::ReviewRequired);
    expect($run->success)->toBeTrue();
    expect($run->confidence)->toBe(0.82);
});

it('marks low confidence classification as failed', function (): void {
    $document = Document::factory()->create(['original_filename' => 'unknown.pdf']);

    $document = runDocumentIntelligenceJob($document, documentClassificationResult(0.5, 'other'));

    expect($document->processing_status)->toBe(DocumentProcessingStatus::Failed);
    expect($document->processing_confidence)->toBe(0.5);
    expect($document->processing_error['reason'])->toBe('low_confidence_classification');

    expect(Activity::query()->where('event', 'document_ai_processing_failed')->exists())->toBeTrue();
});

it('captures failed telemetry status', function (): void {
    $document = Document::factory()->create(['original_filename' => 'unknown.pdf']);

    runDocumentIntelligenceJob($document, documentClassificationResult(0.5, 'other'));

    $run = AiProcessingRun::query()->sole();

    expect($run->status)->toBe(AiProcessingRunStatus::Failed);
    expect($run->success)->toBeFalse();
    expect($run->error_code)->toBe('low_confidence_classification');
    expect($run->confidence)->toBe(0.5);
});

it('preserves classification when optional ocr fails', function (): void {
    Storage::fake('local');
    Storage::disk('local')->put('documents/delivery_note.pdf', 'fake pdf bytes');
    Config::set('ai.ocr_enabled', true);

    $document = Document::factory()->create([
        'disk' => 'local',
        'path' => 'documents/delivery_note.pdf',
        'file_path' => 'documents/delivery_note.pdf',
        'original_filename' => 'delivery_note.pdf',
        'mime_type' => 'application/pdf',
    ]);

    $document = runDocumentIntelligenceJob(
        $document,
        documentClassificationResult(0.97),
        documentOcrUnavailableResult()
    );

    expect($document->processing_status)->toBe(DocumentProcessingStatus::Completed);
    expect($document->processing_result['data']['suggested_type'])->toBe('delivery_note');
    expect($document->processing_result['data']['ocr']['success'])->toBeFalse();
    expect($document->processing_result['data']['ocr']['errors'][0]['code'])->toBe('ocr_backend_unavailable');

    expect(Activity::query()->where('event', 'document_ai_ocr_failed')->exists())->toBeTrue();
});

it('combines classification and ocr results when optional ocr succeeds', function (): void {
    Storage::fake('local');
    Storage::disk('local')->put('documents/delivery_note.txt', 'Received material: Steel plate');
    Config::set('ai.ocr_enabled', true);

    $document = Document::factory()->create([
        'disk' => 'local',
        'path' => 'documents/delivery_note.txt',
        'file_path' => 'documents/delivery_note.txt',
        'original_filename' => 'delivery_note.txt',
        'mime_type' => 'text/plain',
    ]);

    $document = runDocumentIntelligenceJob(
        $document,
        documentClassificationResult(0.82),
        documentOcrResult('Received material: Steel plate')
    );

    expect($document->processing_status)->toBe(DocumentProcessingStatus::ReviewRequired);
    expect($document->processing_result['data']['suggested_type'])->toBe('delivery_note');
    expect($document->processing_result['data']['ocr']['success'])->toBeTrue();
    expect($document->processing_result['data']['ocr']['data']['text'])->toBe('Received material: Steel plate');
    expect($document->processing_result['data']['ocr']['data']['backend'])->toBe('stub');

    expect(Activity::query()->where('event', 'document_ai_ocr_completed')->exists())->toBeTrue();
});

it('records ocr telemetry text length without raw text', function (): void {
    Storage::fake('local');
    Storage::disk('local')->put('documents/delivery_note.txt', 'Received material: Steel plate');
    Config::set('ai.ocr_enabled', true);

    $document = Document::factory()->create([
        'disk' => 'local',
        'path' => 'documents/delivery_note.txt',
        'file_path' => 'documents/delivery_note.txt',
        'original_filename' => 'delivery_note.txt',
        'mime_type' => 'text/plain',
    ]);

    runDocumentIntelligenceJob(
        $document,
        documentClassificationResult(0.97),
        documentOcrResult('Received material: Steel plate')
    );

    $ocrRun = AiProcessingRun::query()
        ->whereMorphedTo('processable', $document)
        ->where('task', 'document_ocr')
        ->firstOrFail();

    expect($ocrRun->status)->toBe(AiProcessingRunStatus::Completed);
    expect($ocrRun->result_summary)->toMatchArray([
        'backend' => 'stub',
        'text_length' => strlen('Received material: Steel plate'),
        'page_count' => 0,
        'confidence' => 0.75,
        'error_codes' => [],
    ]);
    expect(json_encode($ocrRun->result_summary))->not->toContain('Received material: Steel plate');
});

it('handles python engine failure output safely', function (): void {
    $document = Document::factory()->create(['original_filename' => 'delivery_note.pdf']);

    $document = runDocumentIntelligenceJob($document, [
        'success' => false,
        'engine' => 'python-ai-engine',
        'version' => '0.1.0',
        'task' => 'document_classification',
        'confidence' => 0.0,
        'data' => [],
        'errors' => [
            [
                'code' => 'process_timeout',
                'message' => 'Python AI Engine timed out.',
            ],
        ],
    ]);

    expect($document->processing_status)->toBe(DocumentProcessingStatus::Failed);
    expect($document->processing_error['reason'])->toBe('process_timeout');
});

it('allows queued retries with backoff', function (): void {
    $job = new ProcessDocumentJob(123);

    expect($job->tries)->toBe(3);
    expect($job->backoff())->toBe([60, 300]);
});
