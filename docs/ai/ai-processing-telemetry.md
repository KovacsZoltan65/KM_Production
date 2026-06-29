# AI Processing Telemetry

## Purpose

AI processing telemetry records technical execution details for Python AI Engine and Document Intelligence Pipeline runs.

Telemetry is for monitoring, debugging, reporting, and future dashboard views. It complements activity logs; it does not replace business audit history.

## Storage

Telemetry is stored in `ai_processing_runs`.

Each run can optionally point to a processable model, such as a `Document`, through a nullable morph relation.

Recorded fields include:

- task
- engine and engine version
- OCR backend when present
- status
- success flag
- confidence
- start and finish timestamps
- duration in milliseconds
- safe error code and message
- safe metadata
- small result summary

Current statuses are:

- `pending`
- `running`
- `completed`
- `failed`
- `review_required`

## Current Document Intelligence Runs

`ProcessDocumentJob` records telemetry for:

- `document_classification`
- `document_ocr` when OCR is enabled and a stored document path is available

Classification summaries include:

- suggested type
- classification
- confidence
- backend when present

OCR summaries include:

- backend
- text length
- page count
- confidence
- error codes

OCR telemetry never stores the full extracted text.

## Relationship With Activity Logs

Activity logs remain the business audit trail. They record workflow events such as processing started, classification returned, review required, completed, and failed.

Telemetry records technical run details such as duration, engine version, backend, confidence, and compact result summaries.

Use activity logs to answer "what happened to this business document?" Use telemetry to answer "how did the AI processing run behave?"

## Privacy And Security Boundaries

Telemetry must not record:

- raw OCR text
- raw document content
- secrets, tokens, API keys, passwords, or authorization headers
- stack traces
- large unbounded payloads

Telemetry may record derived values such as text length, page count, confidence, backend, and safe error codes.

AI output remains untrusted. Laravel still owns validation, persistence, workflow status, permissions, activity logging, and human review requirements.

## Future Dashboard Use

The telemetry table is intended to support future operational dashboards, including:

- run counts by task, status, engine, and backend
- average and percentile durations
- confidence distributions
- review-required rates
- failure rates and error-code trends
- backend comparison when real OCR engines are added

Future dashboards should expose only summarized telemetry and must keep raw document content out of monitoring views.
