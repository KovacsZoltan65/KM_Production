# Python AI Engine

## Purpose

The Python AI Engine is a local proof of concept for a Laravel -> Python -> JSON processing pipeline.

It exists to prove that Laravel can call a local Python script, pass a small JSON payload, receive structured JSON, validate the response, and log execution safely.

## Current Scope

This first step is intentionally small:

- health-check task only
- direct Symfony Process call from Laravel
- JSON over stdin/stdout
- Laravel response-shape validation
- safe application logging
- no OCR
- no OpenCV, EasyOCR, PaddleOCR, YOLO, PyTorch, external LLMs, or external APIs

Long-running future AI work must use queues. The direct process call is acceptable here only because this is a local health-check foundation.

## Communication Flow

```txt
Laravel
-> PythonAiEngineService
-> Symfony Process
-> python/ai_engine.py
-> JSON stdout
-> Laravel validation
-> array result
```

## JSON Contract

Success response:

```json
{
  "success": true,
  "engine": "python-ai-engine",
  "version": "0.1.0",
  "task": "health_check",
  "confidence": 1.0,
  "data": {
    "message": "Python AI Engine is reachable"
  },
  "errors": []
}
```

Error response:

```json
{
  "success": false,
  "engine": "python-ai-engine",
  "version": "0.1.0",
  "task": null,
  "confidence": 0.0,
  "data": {},
  "errors": [
    {
      "code": "invalid_json",
      "message": "Invalid JSON input."
    }
  ]
}
```

Laravel treats Python output as untrusted and validates the response shape before returning it to callers.

## Configuration

Configuration lives in `config/ai.php`.

Environment values:

```env
AI_PYTHON_BINARY=python
AI_ENGINE_SCRIPT=python/ai_engine.py
AI_ENGINE_TIMEOUT=30
```

Defaults are safe for local development and do not require network access.

## Security Boundaries

- Python must not access the Laravel database.
- Python must not read Laravel environment files.
- Python communicates through JSON only.
- Laravel owns business rules, validation, permissions, persistence, and audit logging.
- Laravel logs execution metadata and failures without exposing stack traces or raw sensitive payloads.
- AI output must not bypass human review, permissions, traceability, inventory rules, quality rules, or audit logging.

## Future OCR Expansion

The next expected step is a queue-based Document Intelligence job that calls the Python AI Engine for document classification and OCR.

Future OCR work should add:

- queued job orchestration
- uploaded document validation
- structured document classification output
- field-level confidence
- human review workflow
- audit records for execution, validation, review, and acceptance
- retry and timeout strategy

Do not add heavy OCR or AI dependencies until the queued workflow and JSON contract are ready.
