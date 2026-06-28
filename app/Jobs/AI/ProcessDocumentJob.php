<?php

namespace App\Jobs\AI;

use App\Enums\DocumentProcessingStatus;
use App\Models\Document;
use App\Services\AI\PythonAiEngineService;
use App\Services\AuditLogService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ProcessDocumentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        public readonly int $documentId,
    ) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(PythonAiEngineService $engine, AuditLogService $auditLog): void
    {
        $document = Document::query()->findOrFail($this->documentId);

        $this->markProcessing($document, $auditLog);

        $result = $engine->run([
            'task' => 'document_classification',
            'document' => [
                'id' => $document->id,
                'filename' => $document->original_filename ?? $document->title,
            ],
        ]);

        if (! $this->isValidClassificationResult($result)) {
            $this->markFailed($document, $auditLog, $result, $this->failureReason($result));

            return;
        }

        $auditLog->log('document_ai_classification_returned', $document, [
            'confidence' => $result['confidence'],
            'classification' => $result['classification'] ?? null,
            'suggested_type' => $result['data']['suggested_type'] ?? null,
        ]);

        $confidence = (float) $result['confidence'];

        if ($confidence >= 0.95) {
            $this->markCompleted($document, $auditLog, $result);

            return;
        }

        if ($confidence >= 0.70) {
            $this->markReviewRequired($document, $auditLog, $result);

            return;
        }

        $this->markFailed($document, $auditLog, $result, 'low_confidence_classification');
    }

    public function failed(?Throwable $exception): void
    {
        $document = Document::query()->find($this->documentId);

        if ($document === null) {
            return;
        }

        $auditLog = app(AuditLogService::class);

        $this->markFailed($document, $auditLog, [
            'success' => false,
            'task' => 'document_classification',
            'confidence' => 0.0,
            'data' => [],
            'errors' => [
                [
                    'code' => 'job_failed',
                    'message' => 'Document Intelligence processing failed.',
                ],
            ],
        ], 'job_failed');
    }

    private function markProcessing(Document $document, AuditLogService $auditLog): void
    {
        $document->forceFill([
            'processing_status' => DocumentProcessingStatus::Processing,
            'processing_error' => null,
        ])->save();

        $auditLog->log('document_ai_processing_started', $document, [
            'task' => 'document_classification',
        ]);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function markCompleted(Document $document, AuditLogService $auditLog, array $result): void
    {
        $this->storeResult($document, DocumentProcessingStatus::Completed, $result);

        $auditLog->log('document_ai_processing_completed', $document, [
            'confidence' => $result['confidence'],
            'suggested_type' => $result['data']['suggested_type'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function markReviewRequired(Document $document, AuditLogService $auditLog, array $result): void
    {
        $this->storeResult($document, DocumentProcessingStatus::ReviewRequired, $result);

        $auditLog->log('document_ai_review_required', $document, [
            'confidence' => $result['confidence'],
            'suggested_type' => $result['data']['suggested_type'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function markFailed(Document $document, AuditLogService $auditLog, array $result, string $reason): void
    {
        $document->forceFill([
            'processing_status' => DocumentProcessingStatus::Failed,
            'processing_confidence' => is_numeric($result['confidence'] ?? null) ? (float) $result['confidence'] : 0.0,
            'processing_result' => $result,
            'processing_error' => [
                'reason' => $reason,
                'errors' => $result['errors'] ?? [],
            ],
            'processed_at' => now(),
        ])->save();

        $auditLog->log('document_ai_processing_failed', $document, [
            'reason' => $reason,
            'confidence' => $document->processing_confidence,
        ]);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function storeResult(Document $document, DocumentProcessingStatus $status, array $result): void
    {
        $document->forceFill([
            'processing_status' => $status,
            'processing_confidence' => (float) $result['confidence'],
            'processing_result' => $result,
            'processing_error' => null,
            'processed_at' => now(),
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function isValidClassificationResult(array $result): bool
    {
        if (($result['success'] ?? null) !== true) {
            return false;
        }

        if (($result['task'] ?? null) !== 'document_classification') {
            return false;
        }

        if (! is_numeric($result['confidence'] ?? null)) {
            return false;
        }

        if (! is_string($result['classification'] ?? null)) {
            return false;
        }

        if (! is_array($result['data'] ?? null)) {
            return false;
        }

        return is_string($result['data']['suggested_type'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function failureReason(array $result): string
    {
        $firstError = $result['errors'][0]['code'] ?? null;

        return is_string($firstError) ? $firstError : 'invalid_classification_result';
    }
}
