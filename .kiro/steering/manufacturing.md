# Manufacturing Domain

This document describes business rules only. It does not define implementation details.

## Production Orders

- Production orders represent planned or active manufacturing work.
- A production order must identify what is being produced, required quantities, routing information, and relevant traceability data.
- Production orders must remain traceable after creation.
- Changes to production order state must be auditable.
- Production order progress is driven by operation execution, material consumption, quality checks, and output recording.

## Operation Sequences

- Manufacturing operations happen in a fixed order.
- Operation sequences describe the required steps for producing an item, component, subassembly, or finished product.
- Each operation step may define whether inspection is required.
- Operators must not skip required operations unless an authorized workflow explicitly allows it.
- Operation execution must preserve start, completion, actor, and result information.

## Versioning

- Operation sequences are versioned.
- Changing operation order requires a new operation sequence version.
- Adding, removing, or materially changing an operation requires a new version.
- Existing production orders must remain linked to their original operation sequence version.
- Historical production records must remain reproducible from their original sequence version.

## Traceability

- The system must identify materials, subassemblies, finished products, stock locations, production orders, operations, and quality records.
- Traceability must connect consumed materials to produced outputs.
- Traceability must preserve who performed important actions and when they occurred.
- Traceability records must not be destroyed by later master data changes.

## Inventory

- Inventory quantities must never be modified directly.
- All stock changes must be represented by stock movement records.
- The system must know:
  - which warehouse or location contains stock
  - which material is present
  - which subassembly is present
  - which finished product is present
  - available quantity
- Inventory must support production, procurement, transfers, scrap, corrections, and finished output.

## Stock Movements

Allowed movement types include:

- `purchase_receive`
- `production_issue`
- `production_consume`
- `production_output`
- `transfer`
- `scrap`
- `correction`

Rules:

- Every quantity change requires a stock movement.
- Stock movements must identify item, quantity, source or destination where applicable, business reason, actor, and timestamp.
- Stock movements must be auditable.
- Corrections must be traceable and justified.

## Material Consumption

- Production material consumption must be recorded explicitly.
- Consumption must link materials to production orders and operations where applicable.
- Consumption must create or be represented by stock movement records.
- Material consumption must not create negative or inconsistent stock unless an explicitly authorized workflow permits it.
- Purchased materials do not receive unique serial numbers by default.

## Serial Numbers

- Every in-house manufactured component, subassembly, and finished product receives a unique serial number.
- Purchased materials do not receive unique serial numbers.
- Serial number format:

```txt
PREFIX/YYYY/NNNN
```

Examples:

```txt
HEG/2026/0001
FES/2026/0007
AAA/2026/0001
```

Rules:

- `PREFIX` is the factory unit identifier.
- Serial numbers must be unique.
- Serial generation must be deterministic, auditable, and concurrency-safe.
- Serial numbers must remain stable after assignment.

## Quality Control

- By default, each workshop finished output must be inspected.
- Operation sequence steps define whether inspection is required.
- Inspection results can be:
  - `accepted`
  - `rejected`
  - `rework_required`
- Rejected and rework-required outputs must remain traceable.
- Quality decisions must record actor, timestamp, inspected item or output, result, and relevant notes or evidence.
- Quality records may reference documents and photos.

## Documentation

Documents can be attached to:

- items
- operation sequences
- production orders
- specific operations
- quality inspections

Supported document types include:

- `drawing`
- `operation_description`
- `work_note`
- `quality_report`
- `photo`

Rules:

- Documents must remain connected to the correct business object.
- Production history must preserve the document context used at the time of execution where required.
- Quality and traceability documents must be auditable.

## Procurement

- Procurement supplies purchased materials and external services.
- Purchase receiving must create stock movements.
- Supplier documents may support receiving, quality, and traceability workflows.
- Purchased materials do not receive the in-house unique serial number format unless a separate business rule requires external batch, lot, or supplier serial tracking.

## Manufacturing Intelligence

- Manufacturing intelligence supports decisions but does not replace auditable business workflows.
- Recommendations must not silently alter production, quality, inventory, or procurement records.
- Human review is required for low-confidence or business-critical recommendations.
- Analytics must preserve traceability back to source data.
