# Quality Control is Workflow Driven

## Status

Accepted

## Context

KM_Production must ensure that manufacturing outputs meet quality requirements. Quality inspections are part of the manufacturing workflow, not optional notes added after production.

Inspection requirements depend on operation sequence steps. Inspection results affect traceability, compliance, rework, rejection, and final acceptance.

Quality records must remain available for audits, investigations, warranty, and root cause analysis.

## Decision

Quality inspections belong to the manufacturing workflow.

Inspection requirements are defined by operation sequence steps.

Inspection results never disappear.

Required inspections must be completed according to workflow rules before outputs are treated as accepted.

## Alternatives Considered

- Treat quality checks as optional attachments or comments.
  - Rejected because quality decisions must be structured, enforceable, and traceable.
- Allow operators to bypass inspection when production is urgent.
  - Rejected because required quality gates must remain reliable.
- Replace failed inspections with later passing results.
  - Rejected because failed, rejected, and rework-required results are part of manufacturing history.

## Consequences

Positive:

- Quality requirements are enforceable.
- Inspection history supports compliance and traceability.
- Failed and rework-required outcomes remain visible.
- Manufacturing quality decisions are connected to production workflow.

Negative:

- Workflows must handle inspection-required states.
- Users may need additional review and correction flows.
- Reporting must distinguish accepted, rejected, and rework-required results.

Trade-offs:

- The system prioritizes compliance, traceability, and manufacturing quality over faster unverified completion.
- Quality failures remain part of history even when later corrected.

## AI Guidance

Future AI agents must not bypass required inspections.

Never auto-approve failed inspections.

Do not delete, overwrite, or hide failed inspection results to simplify workflow state.

Do not move inspection requirements out of operation sequence workflow rules without explicit architectural approval.
