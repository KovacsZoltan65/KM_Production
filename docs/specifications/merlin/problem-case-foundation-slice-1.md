# Material Shortage Problem Case – persistence foundation, slice 1

This implementation note accompanies the authoritative
[conceptual specification](material-shortage-problem-case.md). It records storage
choices only; it does not settle the specification's open business decisions.

`ProblemCase` uses its own UUID primary key, a `material_shortage` type, and one
non-null `MaterialRequirement` foreign key. Multiple cases may reference the same
requirement. Lifecycle (`open`, `closed`, `invalidated`) and current evaluation
(`active`, `resolved`, `undetermined`) are independent enum-cast columns, following
the repository's lowercase persistence convention. Storing an evaluation does
not authorize a lifecycle transition or a Merlin investigation.

The versioned `detection_snapshot` JSON preserves the observed Customer Order /
Customer Order Item, Production Order / BOM Item and required Item identifiers,
item number and name, gross and net quantities, coverage, unit, required date,
allocations, detection timestamp and source, netting provenance, actual competing
scope, and compact evidence. Nullable production lineage and required dates are
preserved without guessing. Case identity, type and requirement link are immutable
columns alongside the snapshot, rather than duplicated JSON fields. Decimal
quantities remain the existing netting result's exact strings. Item display
fields are historical display data, not calculation authority.

`MaterialShortageDetectionSnapshot` accepts an already supplied netting result
and context. Neither the DTO nor the repository proves business validity,
complete competing scope, or authorization. The future trusted backend producer
must establish these before persistence; supplied provenance is not proof.
There is no route, UI, tool, AI integration, resolver, permission mapping or
automatic detection in this slice. A Problem Case grants no new permission;
`Effective Merlin Permission = User Permission ∩ AI Permission ∩ Case Context`.
The AI is not a trusted backend.

`ProblemCaseRepositoryInterface::createMaterialShortage()` persists an open case
and its supplied initial evaluation in one aggregate transaction. The result is
explicitly supplied, not inferred from legacy `missing_quantity` or a snapshot.
The current result, evaluation timestamp and evidence are a replaceable
projection; this slice does not recompute them. Future current evaluation must
use authoritative current data, not detection evidence.

`problem_case_evaluations` has a separate stable row ID, case foreign key,
evaluation result/time, recording occasion and evidence. Creation writes exactly
one `case_creation` row. Future significant evaluations can be appended, while
reads and changes to the current projection do not automatically archive them.
The recording occasion is a string, deliberately not a finalized reason enum.
No MerlinRun relationship or future recording workflow is implemented.

Model instance updates reject changes to case identity/detection evidence, and
evaluation instance updates and deletes are rejected. Case instance deletion is
also rejected. Foreign keys restrict hard deletion of referenced requirements
and cases, preventing cascading history loss. The requirement relationship
includes soft-deleted rows for historical navigation. These are persistence
safeguards, not invalidation or closure rules. Eloquent bulk updates/deletes,
quiet saves, and raw SQL bypass model events; all future application writes must
respect this boundary. Database triggers are not introduced in this slice.

The migration adds two empty tables without changing existing rows. Deploy it
before any future consumer uses these models. `down()` drops history first and
then cases, losing all data in these two tables; it leaves existing requirement
and procurement tables intact. A populated deployment requires an appropriate
backup before rollback. Targeted tests exercise forward/rollback storage and
foreign keys; they do not replace the full migration/seeder release gate.

Still open: detection and event separation/deduplication, lifecycle transitions
and reopening, invalidation and information-gap reasons, authorization mapping,
authoritative resolver orchestration, significant-evaluation archival policy,
and links to Merlin runs, human decisions and actions. This slice introduces no
decisions for these areas.
