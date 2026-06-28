# Operation Sequences are Versioned

## Status

Accepted

## Context

Manufacturing operations happen in a fixed order. Production orders must remain historically accurate even when the manufacturing process changes later.

If an operation sequence already used by production orders is edited in place, historical production records can become misleading. Operators, quality reviewers, auditors, and future investigations need to know which process version was actually used.

## Decision

Operation sequences are immutable once used by production orders.

Changing a manufacturing process creates a new operation sequence version.

Old production orders always keep their original operation sequence version.

## Alternatives Considered

- Edit operation sequences in place.
  - Rejected because existing production orders would lose historical correctness.
- Store only the latest sequence and infer historical steps from logs.
  - Rejected because reconstruction is fragile and can fail during audit or quality review.
- Allow manual edits with comments.
  - Rejected because comments do not preserve a complete structured process version.

## Consequences

Positive:

- Production history remains traceable.
- Manufacturing records remain historically correct.
- Quality investigations can identify the exact process version used.
- Process changes are auditable.

Negative:

- Process maintenance requires explicit version management.
- Users may need to choose or review the active version.
- Queries and UI must respect sequence versions.

Trade-offs:

- The system prioritizes traceability, historical correctness, auditability, and quality over simpler master-data editing.
- Versioning adds complexity but prevents silent changes to manufacturing history.

## AI Guidance

Future AI agents must not update operation sequences already used by production orders.

Create a new operation sequence version instead.

Do not rewrite historical production orders to use a newer operation sequence version unless an explicit, audited business migration is requested.

Do not collapse operation sequence versions to simplify code or UI.
