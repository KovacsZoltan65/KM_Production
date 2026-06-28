# OCR Adapter

## Purpose

The OCR adapter is the first optional OCR boundary inside the Python AI Engine. It lets the Document Intelligence Pipeline request OCR through the same Laravel -> Python -> JSON contract without requiring real OCR tools yet.

## Current Scope

- JSON-only `document_ocr` task.
- Backend plugin boundary.
- Stub backend only.
- Optional plain `.txt` fallback for deterministic tests.
- No Tesseract, EasyOCR, PaddleOCR, OpenCV, YOLO, PyTorch, external LLM, or external API.
- No Python database access.

## Backend Plugin Structure

Current Python structure:

```txt
python/
  adapters/
    ocr.py
    ocr_backends/
      __init__.py
      base.py
      stub.py
```

`ocr.py` selects a backend and adapts the backend result to the stable Laravel-facing JSON contract.

Each backend returns this internal shape:

```json
{
  "success": true,
  "confidence": 0.75,
  "text": "...",
  "language": "unknown",
  "pages": [],
  "backend": "stub",
  "errors": []
}
```

The public `document_ocr` response remains nested under `data` so Laravel does not need to know which backend produced the result.

## Configuration

Laravel controls whether OCR is attempted:

```env
AI_OCR_ENABLED=false
AI_OCR_BACKEND=stub
AI_OCR_MAX_TEXT_BYTES=20000
```

OCR is disabled by default. This prevents unexpected file processing until the pipeline is intentionally enabled.

## JSON Contract

Input:

```json
{
  "task": "document_ocr",
  "document": {
    "filename": "delivery_note.pdf",
    "path": "/absolute/path/to/file.pdf",
    "mime_type": "application/pdf"
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

Unknown backend response:

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
      "code": "ocr_backend_unknown",
      "message": "OCR backend is not supported: future-backend."
    }
  ]
}
```

## Security Boundaries

- Uploaded files are untrusted.
- The adapter validates that the file path exists before reading.
- The stub reads only plain text files.
- Read size is limited by `AI_OCR_MAX_TEXT_BYTES`.
- Python never executes uploaded files.
- Python never writes to the Laravel database.
- Laravel validates OCR output before storing it.
- Raw OCR text should not be written to activity logs.

## Future Real Backends

Future backends can be added behind the same adapter boundary. Tesseract is the likely first real backend; EasyOCR can be considered later.

Any real backend must keep the same JSON contract, run through the queue-based pipeline, and remain optional.

To add a backend:

1. Create `python/adapters/ocr_backends/{backend}.py`.
2. Implement the backend contract from `base.py`.
3. Register it in `ocr_backends/__init__.py`.
4. Keep backend-specific configuration inside Python options.
5. Do not add backend-specific conditionals to Laravel.

Laravel must not depend on backend-specific details because OCR engines are replaceable and may differ by environment.
