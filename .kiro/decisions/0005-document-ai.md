# Python AI Processing is Isolated from Business Logic

## Status

Accepted

## Context

KM_Production may use AI, OCR, computer vision, and document classification to assist manufacturing workflows. These capabilities can process supplier documents, invoices, certificates, inspection reports, production photos, labels, and other operational files.

AI models and OCR engines can change over time. Their outputs may be uncertain, incomplete, or incorrect. Business rules, permissions, workflows, database writes, and audit logs must remain stable and controlled by Laravel.

## Decision

Laravel owns:

- business logic
- workflows
- permissions
- database
- audit log

Python owns:

- OCR
- Computer Vision
- AI inference
- document classification

Communication occurs only through structured JSON.

Python never writes directly to the Laravel database.

All AI output must be validated by Laravel before it affects business data.

## Alternatives Considered

- Put AI business workflows directly in Python.
  - Rejected because business rules would become fragmented and harder to audit.
- Allow Python workers to write directly to the Laravel database.
  - Rejected because it bypasses Laravel validation, permissions, workflows, and audit logging.
- Couple the system to one AI provider or OCR engine.
  - Rejected because AI services must remain replaceable.
- Run long AI/OCR processing during HTTP requests.
  - Rejected because long-running work should use queues and must not block user requests.

## Consequences

Positive:

- Business rules remain maintainable in Laravel.
- AI models and OCR engines remain replaceable.
- Security boundaries are clearer.
- Laravel continues to own validation, permissions, database writes, and audit history.
- AI processing can scale independently.

Negative:

- Integration requires structured JSON contracts.
- AI workflows need queue orchestration and review states.
- Laravel must validate and interpret AI output before saving.

Trade-offs:

- The system prioritizes maintainability, replaceable AI models, security, technology independence, and scalability over direct AI-to-database shortcuts.
- AI assists workflows but does not own business decisions.

## AI Guidance

Future AI integrations must respect this architecture.

Do not move business rules into Python.

Do not allow AI models or Python workers direct database access.

All AI output must be validated by Laravel.

Do not let AI/OCR processing bypass permissions, user review, audit logging, or workflow rules.

Do not make business workflows depend on one specific AI provider unless explicitly approved.
