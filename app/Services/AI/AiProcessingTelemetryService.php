<?php

namespace App\Services\AI;

use App\Enums\AiProcessingRunStatus;
use App\Models\AiProcessingRun;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class AiProcessingTelemetryService
{
    private const MAX_STRING_LENGTH = 500;

    private const MAX_SUMMARY_ITEMS = 25;

    private const SENSITIVE_KEY_PARTS = [
        'api_key',
        'authorization',
        'password',
        'secret',
        'stack',
        'token',
    ];

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function startRun(?Model $processable, string $task, array $metadata = []): AiProcessingRun
    {
        $run = new AiProcessingRun([
            'task' => $task,
            'status' => AiProcessingRunStatus::Running,
            'success' => false,
            'started_at' => now(),
            'metadata' => $this->safePayload($metadata),
        ]);

        if ($processable !== null) {
            $run->processable()->associate($processable);
        }

        $run->save();

        return $run;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    public function markCompleted(AiProcessingRun $run, array $result): AiProcessingRun
    {
        return $this->finish($run, AiProcessingRunStatus::Completed, true, $result);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    public function markReviewRequired(AiProcessingRun $run, array $result): AiProcessingRun
    {
        return $this->finish($run, AiProcessingRunStatus::ReviewRequired, true, $result);
    }

    /**
     * @param  array<string, mixed>  $result
     */
    public function markFailed(AiProcessingRun $run, array $result, ?string $errorCode = null, ?string $errorMessage = null): AiProcessingRun
    {
        $firstError = $this->firstError($result);

        return $this->finish(
            $run,
            AiProcessingRunStatus::Failed,
            false,
            $result,
            $errorCode ?? $firstError['code'],
            $errorMessage ?? $firstError['message'],
        );
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function finish(
        AiProcessingRun $run,
        AiProcessingRunStatus $status,
        bool $success,
        array $result,
        ?string $errorCode = null,
        ?string $errorMessage = null,
    ): AiProcessingRun {
        $finishedAt = now();

        $run->forceFill([
            'engine' => is_string($result['engine'] ?? null) ? $result['engine'] : null,
            'engine_version' => is_string($result['version'] ?? null) ? $result['version'] : null,
            'backend' => $this->backendFromResult($result),
            'status' => $status,
            'success' => $success,
            'confidence' => is_numeric($result['confidence'] ?? null) ? (float) $result['confidence'] : null,
            'finished_at' => $finishedAt,
            'duration_ms' => $this->durationMilliseconds($run->started_at, $finishedAt),
            'error_code' => $errorCode,
            'error_message' => $this->safeErrorMessage($errorMessage),
            'result_summary' => $this->summarizeResult($run->task, $result),
        ])->save();

        return $run;
    }

    private function durationMilliseconds(?Carbon $startedAt, Carbon $finishedAt): ?int
    {
        if ($startedAt === null) {
            return null;
        }

        return max(0, (int) $startedAt->diffInMilliseconds($finishedAt));
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array{code: string|null, message: string|null}
     */
    private function firstError(array $result): array
    {
        $firstError = $result['errors'][0] ?? [];

        if (! is_array($firstError)) {
            return ['code' => null, 'message' => null];
        }

        return [
            'code' => is_string($firstError['code'] ?? null) ? $firstError['code'] : null,
            'message' => is_string($firstError['message'] ?? null) ? $firstError['message'] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function backendFromResult(array $result): ?string
    {
        $backend = $result['data']['backend'] ?? $result['backend'] ?? null;

        return is_string($backend) ? $backend : null;
    }

    private function safeErrorMessage(?string $message): ?string
    {
        if ($message === null) {
            return null;
        }

        return Str::limit($message, self::MAX_STRING_LENGTH, '');
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function summarizeResult(string $task, array $result): array
    {
        return match ($task) {
            'document_classification' => $this->summarizeClassification($result),
            'document_ocr' => $this->summarizeOcr($result),
            default => $this->safePayload(Arr::only($result, [
                'success',
                'task',
                'confidence',
                'engine',
                'version',
            ])),
        };
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function summarizeClassification(array $result): array
    {
        return $this->safePayload([
            'suggested_type' => $result['data']['suggested_type'] ?? null,
            'classification' => $result['classification'] ?? null,
            'confidence' => $result['confidence'] ?? null,
            'backend' => $result['data']['backend'] ?? $result['backend'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function summarizeOcr(array $result): array
    {
        $pages = $result['data']['pages'] ?? [];
        $errors = is_array($result['errors'] ?? null) ? $result['errors'] : [];

        return $this->safePayload([
            'backend' => $result['data']['backend'] ?? null,
            'text_length' => strlen((string) ($result['data']['text'] ?? '')),
            'page_count' => is_array($pages) ? count($pages) : null,
            'confidence' => $result['confidence'] ?? null,
            'error_codes' => collect($errors)
                ->map(fn (mixed $error): mixed => is_array($error) ? ($error['code'] ?? null) : null)
                ->filter(fn (mixed $code): bool => is_string($code))
                ->values()
                ->all(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function safePayload(array $payload): array
    {
        return collect($payload)
            ->take(self::MAX_SUMMARY_ITEMS)
            ->reject(fn (mixed $value, string|int $key): bool => $this->isSensitiveKey((string) $key))
            ->map(fn (mixed $value): mixed => $this->safeValue($value))
            ->all();
    }

    private function safeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return $this->safePayload($value);
        }

        if (is_string($value)) {
            return Str::limit($value, self::MAX_STRING_LENGTH, '');
        }

        if (is_bool($value) || is_int($value) || is_float($value) || $value === null) {
            return $value;
        }

        return null;
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = Str::lower($key);

        if (in_array($normalized, ['content', 'raw', 'raw_text', 'text'], true)) {
            return true;
        }

        foreach (self::SENSITIVE_KEY_PARTS as $part) {
            if (str_contains($normalized, $part)) {
                return true;
            }
        }

        return false;
    }
}
