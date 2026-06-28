<?php

return [
    'python_binary' => env('AI_PYTHON_BINARY', 'python'),
    'engine_script' => env('AI_ENGINE_SCRIPT', 'python/ai_engine.py'),
    'timeout_seconds' => (int) env('AI_ENGINE_TIMEOUT', 30),
];
