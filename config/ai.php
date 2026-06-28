<?php

return [
    'python_binary' => env('AI_PYTHON_BINARY', 'python'),
    'engine_script' => env('AI_ENGINE_SCRIPT', 'python/ai_engine.py'),
    'timeout_seconds' => (int) env('AI_ENGINE_TIMEOUT', 30),
    'ocr_enabled' => (bool) env('AI_OCR_ENABLED', false),
    'ocr_backend' => env('AI_OCR_BACKEND', 'stub'),
    'ocr_max_text_bytes' => (int) env('AI_OCR_MAX_TEXT_BYTES', 20000),
];
