<?php

use App\Enums\DocumentProcessingStatus;
use App\Jobs\AI\ProcessDocumentJob;
use App\Models\Document;
use App\Services\AI\PythonAiEngineService;
use App\Services\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
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

function runDocumentIntelligenceJob(Document $document, array $engineResult): Document
{
    $engine = Mockery::mock(PythonAiEngineService::class, function (MockInterface $mock) use ($document, $engineResult): void {
        $mock->shouldReceive('run')
            ->once()
            ->withArgs(function (array $payload) use ($document): bool {
                return ($payload['task'] ?? null) === 'document_classification'
                    && ($payload['document']['id'] ?? null) === $document->id
                    && ($payload['document']['filename'] ?? null) === $document->original_filename;
            })
            ->andReturn($engineResult);
    });

    (new ProcessDocumentJob($document->id))->handle($engine, app(AuditLogService::class));

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

it('marks medium confidence classification as review required', function (): void {
    $document = Document::factory()->create(['original_filename' => 'delivery_note.pdf']);

    $document = runDocumentIntelligenceJob($document, documentClassificationResult(0.82));

    expect($document->processing_status)->toBe(DocumentProcessingStatus::ReviewRequired);
    expect($document->processing_confidence)->toBe(0.82);
    expect($document->processing_error)->toBeNull();

    expect(Activity::query()->where('event', 'document_ai_review_required')->exists())->toBeTrue();
});

it('marks low confidence classification as failed', function (): void {
    $document = Document::factory()->create(['original_filename' => 'unknown.pdf']);

    $document = runDocumentIntelligenceJob($document, documentClassificationResult(0.5, 'other'));

    expect($document->processing_status)->toBe(DocumentProcessingStatus::Failed);
    expect($document->processing_confidence)->toBe(0.5);
    expect($document->processing_error['reason'])->toBe('low_confidence_classification');

    expect(Activity::query()->where('event', 'document_ai_processing_failed')->exists())->toBeTrue();
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
