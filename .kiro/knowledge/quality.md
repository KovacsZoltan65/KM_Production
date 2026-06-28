# Quality

## Purpose

Quality knowledge describes how KM_Production represents inspection, acceptance, rejection, rework, and quality traceability in manufacturing workflows.

Quality ensures that produced items meet required standards before they are released for further production, stock, delivery, or service.

## Quality Inspection

A quality inspection is a structured check performed on material, a production operation, a subassembly, or finished output.

Inspections may verify:

- dimensions
- visual quality
- weld quality
- paint quality
- completeness
- documentation
- certificate presence
- functional requirements

## Inspection Result

An inspection result records the quality decision.

The main result types are:

- accepted
- rejected
- rework required

Inspection results must remain connected to the inspected item, operation, production order, inspector, timestamp, and evidence.

## Accepted

Accepted means the inspected output passed required quality checks.

Accepted items may proceed to the next operation, inventory, delivery, or release workflow depending on the production process.

## Rejected

Rejected means the inspected output failed quality requirements and cannot proceed as accepted.

Rejected outputs may lead to:

- scrap
- supplier return
- investigation
- replacement production
- corrective action

Rejected results remain part of quality history.

## Rework Required

Rework required means the item is not accepted yet but may be corrected.

Rework should preserve:

- original inspection result
- rework action
- follow-up inspection
- final decision

Rework history supports process improvement and root cause analysis.

## Quality Workflow

Quality workflow defines when inspections are required and how results affect production.

Inspection requirements may be defined by operation sequence steps.

A required inspection should be completed before output is treated as accepted.

## Inspection Points

Inspection points are moments in the workflow where quality checks occur.

Examples:

- after welding
- before painting
- after final assembly
- before goods release
- during receiving

Inspection points ensure defects are found at the correct stage.

## Inspection History

Inspection history records all quality decisions over time.

It should show:

- what was inspected
- who inspected it
- when it was inspected
- result
- notes
- evidence
- related documents
- follow-up actions

## Quality Traceability

Quality traceability connects inspections to:

- production orders
- operation sequence steps
- tasks
- materials
- subassemblies
- finished products
- serial numbers
- documents
- employees
- stock movements

Traceability supports compliance, warranty, service, customer claims, and root cause analysis.

## Quality Documents

Quality documents provide evidence for inspection and compliance.

Examples:

- inspection reports
- photos
- certificates
- measurement records
- nonconformance reports
- corrective action notes

Quality documents should remain linked to the inspected entity.

## Future AI Vision Inspection

Future AI vision inspection may assist with:

- defect detection
- missing component detection
- paint inspection
- weld inspection
- dimension verification
- visual comparison
- predictive quality

AI vision results must be reviewed where confidence is low or business impact is high. AI should assist quality decisions, not silently replace required inspection workflows.

## Business Rules

- Inspection history must never be removed.
- Required inspections must not be bypassed.
- Failed inspections must remain visible.
- Rework does not erase the original quality result.
- Quality evidence should remain linked permanently.
- Quality decisions must be auditable.
