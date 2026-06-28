# Stock Movements are the Single Source of Truth

## Status

Accepted

## Context

KM_Production must maintain reliable inventory history for manufacturing, procurement, transfers, production output, material consumption, scrap, and corrections.

Direct inventory edits would make it difficult to explain why stock changed, who changed it, which workflow caused the change, and whether the change can be reversed or audited.

Manufacturing traceability requires every quantity change to be linked to a business event.

## Decision

Inventory quantities are never edited directly.

Every inventory change creates a `StockMovement`.

Stock movement workflows are the single source of truth for stock changes. Current stock balances may be derived, cached, or summarized, but those balances must remain explainable from stock movement history.

## Alternatives Considered

- Directly update stock quantity fields on inventory records.
  - Rejected because it weakens auditability, hides history, and makes reversals unreliable.
- Store only the latest inventory balance with a generic notes field.
  - Rejected because notes do not provide structured traceability or reliable workflow linkage.
- Allow manual balance edits for administrators.
  - Rejected because corrections must still be represented as auditable stock movement records.

## Consequences

Positive:

- Inventory changes are auditable.
- Stock history remains traceable to business workflows.
- Corrections and reversals can be represented explicitly.
- Reporting can explain how a balance was reached.

Negative:

- Inventory workflows require more structure than direct field updates.
- Simple-looking changes may need service-level orchestration.
- Reporting may require aggregation or optimized summaries.

Trade-offs:

- The system prioritizes auditability, traceability, reversibility, and reliable history over shortcut updates.
- Performance optimizations must preserve stock movement history as the source of truth.

## AI Guidance

Future AI agents must not modify stock balances directly.

Always use `StockMovement` services or workflows for inventory changes.

Do not introduce direct quantity mutation in controllers, repositories, models, seeders, API endpoints, AI callbacks, or frontend-driven actions.

Do not treat cached or summarized inventory balances as the authoritative history.
