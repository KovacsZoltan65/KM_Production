# Serial Numbers Guarantee Full Traceability

## Status

Accepted

## Context

Manufactured components, subassemblies, and finished products must be traceable throughout production, quality, inventory, warranty, service, and root cause analysis.

Purchased materials are tracked through procurement, inventory, supplier, batch, lot, or document information as needed, but they do not receive the in-house manufactured serial number format by default.

Serial numbers connect production output to operations, materials, inspections, documents, and future service history.

## Decision

Every manufactured component, subassembly, and finished product receives a serial number.

Purchased materials do not receive in-house manufactured serial numbers.

Serial numbers must not be reused, regenerated, or reassigned after historical use.

## Alternatives Considered

- Assign serial numbers only to finished products.
  - Rejected because components and subassemblies also require traceability for quality, warranty, and root cause analysis.
- Assign serial numbers to all purchased materials.
  - Rejected because purchased material tracking is a separate concern and does not require in-house manufactured serial numbers by default.
- Allow serial number regeneration when data is corrected.
  - Rejected because historical serial assignments must remain stable and auditable.

## Consequences

Positive:

- Manufactured outputs are fully traceable.
- Warranty and service workflows can identify exact produced items.
- Root cause analysis can connect outputs to production history.
- Serial numbers provide stable references for quality and documentation.

Negative:

- Serial generation must be concurrency-safe.
- Production workflows must assign and preserve serials.
- UI and reporting must support serial-level traceability.

Trade-offs:

- The system prioritizes manufacturing traceability, warranty support, service history, and root cause analysis over simpler batch-only tracking.
- Purchased materials remain outside the in-house serial numbering rule unless a separate business rule is defined.

## AI Guidance

Future AI agents must never reuse serial numbers.

Never regenerate existing serials.

Never modify historical serial assignments.

Do not assign in-house manufactured serial numbers to purchased materials unless an explicit new business rule is approved.

Do not simplify serial generation in ways that break uniqueness, stability, or traceability.
