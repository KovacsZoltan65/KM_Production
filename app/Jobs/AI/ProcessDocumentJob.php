<?php

namespace App\Jobs\AI;

use App\Enums\DocumentProcessingStatus;
use App\Models\Document;
use App\Services\AI\AiProcessingTelemetryService;
use App\Services\AI\PythonAiEngineService;
use App\Services\AuditLogService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
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

    public function handle(
        PythonAiEngineService $engine,
        AuditLogService $auditLog,
        AiProcessingTelemetryService $telemetry,
    ): void {
        $document = Document::query()->findOrFail($this->documentId);

        $this->markProcessing($document, $auditLog);

        $classificationRun = $telemetry->startRun($document, 'document_classification', [
            'document_id' => $document->id,
            'filename' => $document->original_filename ?? $document->title,
        ]);

        try {
            $classificationResult = $engine->run([
                'task' => 'document_classification',
                'document' => [
                    'id' => $document->id,
                    'filename' => $document->original_filename ?? $document->title,
                ],
            ]);
        } catch (Throwable $exception) {
            $telemetry->markFailed($classificationRun, [
                'success' => false,
                'task' => 'document_classification',
                'confidence' => 0.0,
                'data' => [],
                'errors' => [
                    [
                        'code' => 'classification_exception',
                        'message' => 'Document classification failed.',
                    ],
                ],
            ]);

            throw $exception;
        }

        if (! $this->isValidClassificationResult($classificationResult)) {
            $telemetry->markFailed(
                $classificationRun,
                $classificationResult,
                $this->failureReason($classificationResult),
            );
            $this->markFailed($document, $auditLog, $classificationResult, $this->failureReason($classificationResult));

            return;
        }

        $auditLog->log('document_ai_classification_returned', $document, [
            'confidence' => $classificationResult['confidence'],
            'classification' => $classificationResult['classification'] ?? null,
            'suggested_type' => $classificationResult['data']['suggested_type'] ?? null,
        ]);

        $result = $this->withOptionalOcr($document, $classificationResult, $engine, $auditLog, $telemetry);
        $confidence = (float) $classificationResult['confidence'];

        if ($confidence >= 0.95) {
            $telemetry->markCompleted($classificationRun, $classificationResult);
            $this->markCompleted($document, $auditLog, $result);

            return;
        }

        if ($confidence >= 0.70) {
            $telemetry->markReviewRequired($classificationRun, $classificationResult);
            $this->markReviewRequired($document, $auditLog, $result);

            return;
        }

        $telemetry->markFailed($classificationRun, $classificationResult, 'low_confidence_classification');
        $this->markFailed($document, $auditLog, $result, 'low_confidence_classification');
    }

    public function failed(?Throwable $exception): void
    {
        $document = Document::query()->find($this->documentId);

        if ($document === null) {
            return;
        }

        $auditLog = app(AuditLogService::class);
        $telemetry = app(AiProcessingTelemetryService::class);

        $run = $telemetry->startRun($document, 'document_classification', [
            'document_id' => $document->id,
            'job_failed' => true,
        ]);

        $telemetry->markFailed($run, [
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
        ]);

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

    /**
     * @param  array<string, mixed>  $classificationResult
     * @return array<string, mixed>
     */
    private function withOptionalOcr(
        Document $document,
        array $classificationResult,
        PythonAiEngineService $engine,
        AuditLogService $auditLog,
        AiProcessingTelemetryService $telemetry,
    ): array {
        if (! config('ai.ocr_enabled', false)) {
            return $classificationResult;
        }

        $path = $this->documentAbsolutePath($document);
        if ($path === null) {
            return $classificationResult;
        }

        $auditLog->log('document_ai_ocr_started', $document, [
            'backend' => config('ai.ocr_backend', 'stub'),
        ]);

        $ocrRun = $telemetry->startRun($document, 'document_ocr', [
            'document_id' => $document->id,
            'filename' => $document->original_filename ?? $document->title,
            'mime_type' => $document->mime_type,
            'backend' => config('ai.ocr_backend', 'stub'),
        ]);

        try {
            $ocrResult = $engine->run([
                'task' => 'document_ocr',
                'document' => [
                    'id' => $document->id,
                    'filename' => $document->original_filename ?? $document->title,
                    'path' => $path,
                    'mime_type' => $document->mime_type,
                ],
                'options' => [
                    'backend' => config('ai.ocr_backend', 'stub'),
                    'max_text_bytes' => (int) config('ai.ocr_max_text_bytes', 20000),
                ],
            ]);
        } catch (Throwable $exception) {
            $telemetry->markFailed($ocrRun, [
                'success' => false,
                'task' => 'document_ocr',
                'confidence' => 0.0,
                'data' => [
                    'text' => '',
                    'language' => 'unknown',
                    'pages' => [],
                    'backend' => config('ai.ocr_backend', 'stub'),
                ],
                'errors' => [
                    [
                        'code' => 'ocr_exception',
                        'message' => 'Document OCR failed.',
                    ],
                ],
            ]);

            throw $exception;
        }

        $ocrResult = $this->normalizeOcrResult($ocrResult);
        $classificationResult['data']['ocr'] = $ocrResult;

        if (($ocrResult['success'] ?? false) === true) {
            $telemetry->markCompleted($ocrRun, $ocrResult);

            $auditLog->log('document_ai_ocr_completed', $document, [
                'confidence' => $ocrResult['confidence'],
                'backend' => $ocrResult['data']['backend'] ?? null,
                'text_length' => strlen((string) ($ocrResult['data']['text'] ?? '')),
            ]);

            return $classificationResult;
        }

        $telemetry->markFailed($ocrRun, $ocrResult, $this->failureReason($ocrResult));

        $auditLog->log('document_ai_ocr_failed', $document, [
            'reason' => $this->failureReason($ocrResult),
            'backend' => $ocrResult['data']['backend'] ?? null,
        ]);

        return $classificationResult;
    }

    private function documentAbsolutePath(Document $document): ?string
    {
        $relativePath = $document->path ?? $document->file_path;

        if ($relativePath === null) {
            return null;
        }

        $disk = $document->disk ?? 'local';

        if (! Storage::disk($disk)->exists($relativePath)) {
            return null;
        }

        return Storage::disk($disk)->path($relativePath);
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function normalizeOcrResult(array $result): array
    {
        if ($this->isValidOcrResult($result)) {
            return $result;
        }

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
                    'code' => 'invalid_ocr_result',
                    'message' => 'Python AI Engine returned an invalid OCR response.',
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function isValidOcrResult(array $result): bool
    {
        if (($result['task'] ?? null) !== 'document_ocr') {
            return false;
        }

        if (! is_bool($result['success'] ?? null)) {
            return false;
        }

        if (! is_numeric($result['confidence'] ?? null)) {
            return false;
        }

        if (! is_array($result['data'] ?? null)) {
            return false;
        }

        if (! is_string($result['data']['text'] ?? null)) {
            return false;
        }

        if (! is_string($result['data']['language'] ?? null)) {
            return false;
        }

        if (! is_array($result['data']['pages'] ?? null)) {
            return false;
        }

        if (! is_array($result['errors'] ?? null)) {
            return false;
        }

        return array_key_exists('backend', $result['data']);
    }
}
