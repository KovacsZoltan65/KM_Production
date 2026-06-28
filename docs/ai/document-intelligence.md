# Document Intelligence Pipeline

## Purpose

The Document Intelligence Pipeline is the queue-based architecture that future OCR, classification, vision, and metadata extraction features will plug into.

This first version does not perform OCR. It proves the safe workflow from Laravel document records through a queued job, the local Python AI Engine, Laravel validation, processing state updates, and audit logging.

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
- Laravel validates the response shape and confidence.
- Laravel updates document processing status.
- Activity log events record started, classification returned, review required, completed, and failed outcomes.

No OCR, OpenCV, EasyOCR, PaddleOCR, YOLO, PyTorch, external LLM, or external API dependency is included.

## Status Rules

Classification confidence controls the processing status:

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

## Activity Log Events

- `document_ai_processing_started`
- `document_ai_classification_returned`
- `document_ai_review_required`
- `document_ai_processing_completed`
- `document_ai_processing_failed`

## Future OCR Integration

OCR should be added as an adapter inside this existing pipeline:

- validate uploaded document metadata
- enqueue processing
- call Python OCR adapter
- return extracted text and confidence
- validate structured JSON in Laravel
- store processing result
- require review for low-confidence or high-impact extraction

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
