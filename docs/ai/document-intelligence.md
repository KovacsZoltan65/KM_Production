# Document Intelligence Pipeline

## Purpose

The Document Intelligence Pipeline is the queue-based architecture that future OCR, classification, vision, and metadata extraction features will plug into.

This version includes optional stub OCR through the Python adapter boundary. It does not include real OCR libraries.

## Pipeline

```txt
User Upload
↓
Laravel Document
↓
Queue Job
↓
Python AI Engine
↓
Classification Result
↓
Optional OCR Result
↓
Laravel Validation
↓
Document Processing Result
↓
Ready for Review
```

## Current Scope

- `ProcessDocumentJob` loads a `Document`.
- The job calls `PythonAiEngineService`.
- The Python engine runs the `document_classification` task.
- The classifier is a filename heuristic stub.
- If OCR is enabled and a file path is available, the job also runs `document_ocr`.
- The current OCR backend plugin is a stub with deterministic plain text fallback.
- Laravel validates the response shape and confidence.
- Laravel updates document processing status.
- Activity log events record started, classification returned, review required, completed, and failed outcomes.

No OpenCV, EasyOCR, PaddleOCR, YOLO, PyTorch, external LLM, real OCR binary, or external API dependency is included.

## Status Rules

Classification confidence controls the processing status. OCR does not lower a successful classification status in this iteration:

- `>= 0.95`: `completed`
- `0.70` to `< 0.95`: `review_required`
- `< 0.70`: `failed`

Current document processing states:

- `pending`
- `processing`
- `completed`
- `failed`
- `review_required`

## Security Boundaries

- Python never writes to the database.
- Python does not read Laravel environment files.
- Python communicates with Laravel through JSON only.
- Laravel owns validation, state changes, audit logging, permissions, and future review workflows.
- Raw Python failures are converted into safe processing errors.
- Long-running OCR and vision work must remain queued.
- OCR text is stored in the structured AI result and must not be logged as raw text.

## Activity Log Events

- `document_ai_processing_started`
- `document_ai_classification_returned`
- `document_ai_review_required`
- `document_ai_processing_completed`
- `document_ai_processing_failed`
- `document_ai_ocr_started`
- `document_ai_ocr_completed`
- `document_ai_ocr_failed`

## OCR Integration

OCR is now represented by an optional adapter call inside this existing pipeline:

- validate uploaded document metadata
- enqueue processing
- call Python OCR adapter
- return extracted text and confidence
- validate structured JSON in Laravel
- store processing result
- require review for low-confidence or high-impact extraction

Current configuration:

```env
AI_OCR_ENABLED=false
AI_OCR_BACKEND=stub
AI_OCR_MAX_TEXT_BYTES=20000
```

OCR is disabled by default. When enabled, the stub backend can read a limited amount of text from plain `.txt` files for deterministic tests and local development.

Laravel passes the selected backend name through configuration, but Laravel must not depend on backend-specific details. Python owns backend selection and returns the same JSON contract for every backend.

Unknown backend names return a safe `ocr_backend_unknown` error and preserve the classification result.

## Future Vision Integration

Vision capabilities should follow the same boundary:

- queued processing
- controlled file access
- JSON-only results
- confidence and evidence metadata
- Laravel validation
- human review before business impact

Vision must not bypass quality workflows or directly update production, inventory, or quality records.

## Future Metadata Extraction

Metadata extraction can extend the same result structure with:

- detected document type
- supplier hints
- purchase order references
- lot or batch references
- serial number references
- confidence per extracted field
- evidence references for review

Laravel must validate all extracted metadata against known business records before saving accepted values.
