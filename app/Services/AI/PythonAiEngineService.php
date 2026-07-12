<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;
use JsonException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

/**
 * A Laravel alkalmazás és a Python AI-adapter közötti folyamatkommunikációt kezeli.
 *
 * Strukturált JSON-kéréseket küld, ellenőrzi a válaszformátumot és technikai
 * hibát jelez; adatbázis-műveletet nem végez.
 */
class PythonAiEngineService
{
    private const ENGINE = 'python-ai-engine';

    private const VERSION = '0.1.0';

    /**
     * @return array<string, mixed>
     */
    public function healthCheck(): array
    {
        return $this->run([
            'task' => 'health_check',
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function run(array $payload): array
    {
        $task = $this->taskFromPayload($payload);
        $script = $this->engineScriptPath();

        if (! is_file($script)) {
            return $this->failure('engine_script_missing', 'Python AI Engine script is not available.', $task, [
                'configured_script' => config('ai.engine_script'),
            ]);
        }

        try {
            $input = json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            return $this->failure('payload_encoding_failed', 'AI Engine payload could not be encoded.', $task, [
                'exception' => $exception::class,
            ]);
        }

        $process = $this->makeProcess($script, $input);

        try {
            $process->run();
        } catch (ProcessTimedOutException $exception) {
            return $this->failure('process_timeout', 'Python AI Engine timed out.', $task, [
                'exception' => $exception::class,
                'timeout_seconds' => $this->timeoutSeconds(),
            ]);
        }

        if (! $process->isSuccessful()) {
            return $this->failure('process_failed', 'Python AI Engine failed.', $task, [
                'exit_code' => $process->getExitCode(),
                'stderr_length' => strlen($process->getErrorOutput()),
            ]);
        }

        $decoded = json_decode($process->getOutput(), true);

        if (! is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
            return $this->failure('invalid_engine_json', 'Python AI Engine returned invalid JSON.', $task, [
                'json_error' => json_last_error_msg(),
            ]);
        }

        if (! $this->hasExpectedResponseShape($decoded)) {
            return $this->failure('invalid_response_shape', 'Python AI Engine returned an unexpected response shape.', $task);
        }

        $result = $this->normalizeResponse($decoded);

        Log::info('ai.python_engine.executed', [
            'task' => $result['task'],
            'success' => $result['success'],
            'confidence' => $result['confidence'],
            'engine' => $result['engine'],
            'version' => $result['version'],
        ]);

        return $result;
    }

    protected function makeProcess(string $script, string $input): Process
    {
        return new Process(
            [
                (string) config('ai.python_binary', 'python'),
                $script,
            ],
            base_path(),
            null,
            $input,
            $this->timeoutSeconds(),
        );
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function hasExpectedResponseShape(array $response): bool
    {
        if (
            ! array_key_exists('success', $response)
            || ! is_bool($response['success'])
            || ! is_string($response['engine'] ?? null)
            || ! is_string($response['version'] ?? null)
            || ! (is_string($response['task'] ?? null) || ($response['task'] ?? null) === null)
            || ! is_numeric($response['confidence'] ?? null)
            || ! is_array($response['data'] ?? null)
            || ! is_array($response['errors'] ?? null)
        ) {
            return false;
        }

        foreach ($response['errors'] as $error) {
            if (
                ! is_array($error)
                || ! is_string($error['code'] ?? null)
                || ! is_string($error['message'] ?? null)
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    private function normalizeResponse(array $response): array
    {
        $normalized = [
            'success' => $response['success'],
            'engine' => $response['engine'],
            'version' => $response['version'],
            'task' => $response['task'],
            'confidence' => (float) $response['confidence'],
            'data' => $response['data'],
            'errors' => $response['errors'],
        ];

        if (array_key_exists('classification', $response)) {
            $normalized['classification'] = $response['classification'];
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function failure(string $code, string $message, ?string $task = null, array $context = []): array
    {
        Log::warning('ai.python_engine.failed', array_merge([
            'code' => $code,
            'task' => $task,
            'engine' => self::ENGINE,
            'version' => self::VERSION,
        ], $context));

        return [
            'success' => false,
            'engine' => self::ENGINE,
            'version' => self::VERSION,
            'task' => $task,
            'confidence' => 0.0,
            'data' => [],
            'errors' => [
                [
                    'code' => $code,
                    'message' => $message,
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function taskFromPayload(array $payload): ?string
    {
        return is_string($payload['task'] ?? null) ? $payload['task'] : null;
    }

    private function engineScriptPath(): string
    {
        $configuredPath = (string) config('ai.engine_script', 'python/ai_engine.py');

        if ($this->isAbsolutePath($configuredPath)) {
            return $configuredPath;
        }

        return base_path($configuredPath);
    }

    private function timeoutSeconds(): int
    {
        return max(1, (int) config('ai.timeout_seconds', 30));
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || (bool) preg_match('/^[A-Za-z]:[\\\\\\/]/', $path);
    }
}
