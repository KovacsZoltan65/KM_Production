<?php

use App\Services\AI\PythonAiEngineService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

function pythonAiEngineScript(string $name, string $contents): string
{
    $directory = storage_path('framework/testing/ai-engine');

    File::ensureDirectoryExists($directory);

    $path = $directory.DIRECTORY_SEPARATOR.$name;

    File::put($path, $contents);

    return $path;
}

it('python script returns health check json', function (): void {
    $process = new Process([
        (string) config('ai.python_binary', 'python'),
        base_path('python/ai_engine.py'),
    ], base_path(), null, json_encode(['task' => 'health_check']));

    $process->run();

    expect($process->isSuccessful())->toBeTrue();

    $result = json_decode($process->getOutput(), true);

    expect($result['success'])->toBeTrue();
    expect($result['engine'])->toBe('python-ai-engine');
    expect($result['version'])->toBe('0.1.0');
    expect($result['task'])->toBe('health_check');
    expect($result['confidence'])->toBe(1.0);
    expect($result['data']['message'])->toBe('Python AI Engine is reachable');
    expect($result['errors'])->toBe([]);
});

it('python script handles invalid json input safely', function (): void {
    $process = new Process([
        (string) config('ai.python_binary', 'python'),
        base_path('python/ai_engine.py'),
    ], base_path(), null, '{invalid-json');

    $process->run();

    expect($process->isSuccessful())->toBeTrue();

    $result = json_decode($process->getOutput(), true);

    expect($result['success'])->toBeFalse();
    expect($result['engine'])->toBe('python-ai-engine');
    expect($result['task'])->toBeNull();
    expect($result['confidence'])->toBe(0.0);
    expect($result['data'])->toBe([]);
    expect($result['errors'][0]['code'])->toBe('invalid_json');
});

it('laravel service successfully calls python engine', function (): void {
    $result = app(PythonAiEngineService::class)->healthCheck();

    expect($result['success'])->toBeTrue();
    expect($result['engine'])->toBe('python-ai-engine');
    expect($result['task'])->toBe('health_check');
    expect($result['confidence'])->toBe(1.0);
    expect($result['errors'])->toBe([]);
});

it('laravel service handles invalid json output', function (): void {
    $script = pythonAiEngineScript('invalid-json.py', 'print("not-json")');

    Config::set('ai.engine_script', $script);

    $result = app(PythonAiEngineService::class)->healthCheck();

    expect($result['success'])->toBeFalse();
    expect($result['task'])->toBe('health_check');
    expect($result['confidence'])->toBe(0.0);
    expect($result['errors'][0]['code'])->toBe('invalid_engine_json');
});

it('laravel service handles missing script', function (): void {
    Config::set('ai.engine_script', storage_path('framework/testing/ai-engine/missing.py'));

    $result = app(PythonAiEngineService::class)->healthCheck();

    expect($result['success'])->toBeFalse();
    expect($result['task'])->toBe('health_check');
    expect($result['errors'][0]['code'])->toBe('engine_script_missing');
});

it('laravel service validates response shape', function (): void {
    $script = pythonAiEngineScript('invalid-shape.py', 'import json; print(json.dumps({"success": True}))');

    Config::set('ai.engine_script', $script);

    $result = app(PythonAiEngineService::class)->healthCheck();

    expect($result['success'])->toBeFalse();
    expect($result['task'])->toBe('health_check');
    expect($result['errors'][0]['code'])->toBe('invalid_response_shape');
});
