# Python AI Engine

## Purpose

The Python AI Engine is a local proof of concept for a Laravel -> Python -> JSON processing pipeline.

It exists to prove that Laravel can call a local Python script, pass a small JSON payload, receive structured JSON, validate the response, and log execution safely.

## Current Scope

The engine is intentionally small and local:

- health-check task
- document classification stub
- optional OCR adapter task
- OCR backend plugin boundary
- direct Symfony Process call from Laravel
- JSON over stdin/stdout
- Laravel response-shape validation
- safe application logging
- Laravel-side AI processing telemetry
- stub OCR only, with deterministic plain text fallback
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

Laravel may also summarize validated engine responses into `ai_processing_runs` for monitoring. This telemetry keeps technical metadata such as task, engine, version, backend, confidence, duration, status, and safe error codes.

## Configuration

Configuration lives in `config/ai.php`.

Environment values:

```env
AI_PYTHON_BINARY=python
AI_ENGINE_SCRIPT=python/ai_engine.py
AI_ENGINE_TIMEOUT=30
AI_OCR_ENABLED=false
AI_OCR_BACKEND=stub
AI_OCR_MAX_TEXT_BYTES=20000
```

Defaults are safe for local development and do not require network access.

OCR is disabled by default at the Laravel pipeline level. The Python engine still exposes a JSON-only `document_ocr` task so tests and future queued jobs can call it safely.

## Security Boundaries

- Python must not access the Laravel database.
- Python must not read Laravel environment files.
- Python communicates through JSON only.
- Laravel owns business rules, validation, permissions, persistence, and audit logging.
- Laravel logs execution metadata and failures without exposing stack traces or raw sensitive payloads.
- Laravel telemetry must not store raw OCR text, raw document content, secrets, or stack traces.
- AI output must not bypass human review, permissions, traceability, inventory rules, quality rules, or audit logging.

Telemetry complements activity logs. Activity logs remain the business audit trail; telemetry is for operational monitoring, debugging, reporting, and future dashboard views.

## OCR Adapter

The current OCR adapter is a backend plugin boundary, not a real OCR implementation.

Input:

```json
{
  "task": "document_ocr",
  "document": {
    "filename": "delivery_note.txt",
    "path": "/absolute/path/to/file.txt",
    "mime_type": "text/plain"
  },
  "options": {
    "backend": "stub",
    "max_text_bytes": 20000
  }
}
```

Success response:

```json
{
  "success": true,
  "engine": "python-ai-engine",
  "version": "0.1.0",
  "task": "document_ocr",
  "confidence": 0.75,
  "data": {
    "text": "Detected OCR text...",
    "language": "unknown",
    "pages": [],
    "backend": "stub"
  },
  "errors": []
}
```

Unavailable response:

```json
{
  "success": false,
  "engine": "python-ai-engine",
  "version": "0.1.0",
  "task": "document_ocr",
  "confidence": 0.0,
  "data": {
    "text": "",
    "language": "unknown",
    "pages": [],
    "backend": null
  },
  "errors": [
    {
      "code": "ocr_backend_unavailable",
      "message": "No OCR backend is configured or available."
    }
  ]
}
```

The stub backend reads only plain `.txt` files and limits bytes read with `AI_OCR_MAX_TEXT_BYTES`. Other file types return structured errors until a real OCR backend is added.

When OCR is run through the Document Intelligence Pipeline, telemetry stores derived OCR metrics such as backend, text length, page count, confidence, and error codes. It does not store the extracted text.

Current backend structure:

```txt
python/adapters/ocr.py
python/adapters/ocr_backends/base.py
python/adapters/ocr_backends/stub.py
```

Unknown backend names return `ocr_backend_unknown`. Missing backend configuration returns `ocr_backend_unavailable`.

## Future OCR Expansion

The next expected step is adding a real OCR backend option, probably Tesseract first, while keeping this JSON contract stable.

Do not add heavy OCR or AI dependencies until the queued workflow and JSON contract are ready.
