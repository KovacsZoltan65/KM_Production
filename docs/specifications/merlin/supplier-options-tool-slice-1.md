# Supplier Options tool — authorized backend boundary, slice 1

This slice adds the internal `GetSupplierOptionsTool` capability named
`get_supplier_options`. It adds no route, UI, provider, MerlinRun, registry,
supplier ranking or procurement execution. Trusted PHP callers build the context;
AI output and request arguments must never construct it.

## Authorization and context

The accepted V1 rule is:

```text
inventory.view AND item-suppliers.view
AND explicit backend AI allowlist permits get_supplier_options
AND requested case UUID equals the trusted bound case UUID
AND fresh current resolver gate passes
```

No additional supplier, procurement, Merlin or Problem Case permission is required.
The context retains a persisted backend actor's ID, one case UUID and an explicit
backend allowlist. It does not retain permission authority from a hydrated User.
Availability and execution independently resolve a fresh actor and apply Laravel
Gate checks. Availability describes disclosure authorization only; it does not
promise current domain applicability and is never consumed as execution authority.

The existing super-admin Gate behavior satisfies only the user permission part.
It cannot bypass the separate allowlist, UUID equality or current-state checks.
Unknown names cannot resolve classes, services or methods. Invalid arguments,
foreign case IDs and disallowed capabilities are rejected before case lookup.
Execution re-resolves state even after a successful availability check.

## Input and fresh gate

The only input is an object containing exactly one `problem_case_id` UUID string.
No user, item, quantity, date, unit, supplier, provenance or scope override is
accepted. The tool definition publishes a closed input schema; execution validates
the shape independently of that schema.

Supplier evaluation requires OPEN + MATERIAL_SHORTAGE, VALID authoritative source,
ACTIVE evaluation, authoritative selected netting, positive exact net requirement
and no invalidation candidate. The existing resolver calculates the full competing
scope before selecting the case requirement. Stored current projection, detection
evidence and `missing_quantity` are never current authority.

The selected netting result maps directly to item, quantity, date and unit.
Provenance is `material_requirement_netting` / `net_requirement`, source ID is the
requirement integer ID, and scope is `all_requirements`. Observation timestamp and
evaluation business day come from the backend clock in the application timezone.
Decimal strings are preserved without float conversion or a second netting pass.

## Snapshot ownership

The accepted composition decision assigns ownership to the concrete
`MaterialShortageSupplierOptionsRead` application boundary. It invokes the existing
`SupplierOptionReadSnapshot` helper around case lookup, source assessment,
full-scope netting, resolver evaluation, input construction and supplier reads.

The existing `SupplierOptionService::evaluate(query)` continues to own its own
snapshot. Its MySQL caller-transaction rejection, REPEATABLE READ / READ ONLY
policy, initial PDO identity check and single-attempt behavior remain unchanged.

The additional `evaluateMaterialShortageObservation()` entry accepts only the
final concrete composition object, not a query, boolean bypass flag or arbitrary
callback. The composition's query and PDO are private and transient. Input can
only be obtained during its synchronous active observation, on the original PDO
with an active transaction. The query is built from fresh resolver output; there
is no caller setter. Authority is cleared in `finally`. A post-evaluation check
also rejects connection or transaction loss. There is no general snapshot bypass.

Consistency means one database read snapshot on the default connection, with
MySQL guarantees relying on InnoDB consistent reads. It is not a supply reservation
or a promise that state remains unchanged after the observation. Permission checks
precede business observation; no atomic transaction spanning permission revocation
and all business data is claimed. Existing Spatie cache invalidation conventions
apply. Trusted callers must supply the current backend allowlist on every call.

No audit or provider work is inside this snapshot. Projection occurs afterward
using detached immutable data. MySQL runtime proof requires the guarded test DB;
SQLite and mocked MySQL checks do not prove MySQL runtime isolation.

Execution requires entry outside any caller-owned transaction, including an
untracked PDO transaction. Otherwise it throws the safe
`SUPPLIER_OPTIONS_TOOL_CALLER_TRANSACTION_UNVERIFIED` precondition exception before
reads or audit, preserving the caller's transaction. It cannot satisfy the audit
boundary by silently logging inside, committing or rolling back that transaction.

## Output and outcomes

The tool envelope has `schema_version: "1"`, `status`, `code` and `data`.
Success also has the bound `problem_case_id` and `source_observed_at`.
Success data uses the supplier result schema version `0.1` through an explicit
projection; domain `jsonSerialize()` is never passed through wholesale.

Allowed sections are item identity/state, requirement quantity/date/unit and
compact provenance, evaluation dates, and options containing supplier identity,
source relationship, eligibility, quantity/date fit, ordering, delivery,
reference commercial terms and data-quality diagnostics. `KNOWN`, `UNKNOWN` and
`NOT_APPLICABLE` envelopes retain `state`, scalar/null `value` and nullable `reason`.
Quantities and prices remain strings. Future extra domain fields are excluded.

No models, lazy relations, allocations, stock/PO source details, full lineage,
detection evidence, supplier contact/tax/bank/address information or raw exceptions
are exposed. Preferred/priority fields are information, not a ranking. Delivery
dates are calendar-day estimates; undefined price basis is not invented.

| Code                       | Status         | Meaning                                                |
| -------------------------- | -------------- | ------------------------------------------------------ |
| ACTIVE                     | success        | Fresh proven shortage; supplier options evaluated      |
| AUTHORIZATION_DENIED       | denied         | Actor missing or user permission denied                |
| AI_CAPABILITY_DENIED       | denied         | Backend allowlist excludes capability                  |
| CASE_CONTEXT_MISMATCH      | denied         | Requested UUID differs from bound context              |
| UNKNOWN_TOOL               | denied         | Name is outside the single closed capability           |
| INVALID_TOOL_INPUT         | error          | Invalid shape, UUID or extra parameters                |
| CASE_NOT_FOUND             | error          | Authorized bound case lookup failed                    |
| CASE_NOT_CURRENT           | not_applicable | Closed/invalidated or unsupported type                 |
| SOURCE_INVALID             | not_applicable | Source is an invalidation candidate                    |
| CURRENT_STATE_UNDETERMINED | undetermined   | Authoritative positive shortage cannot be proven       |
| NO_CURRENT_SHORTAGE        | not_applicable | Current evaluation is RESOLVED                         |
| SUPPLIER_OPTIONS_FAILED    | error          | Technical, integrity, snapshot or domain-input failure |

Non-success data is null. No known supplier sources produces successful empty
options. Ineligible/unusable known sources remain successful diagnostics.
Internal exceptions are not converted into unknown supplier facts.

## Audit boundary and failure

Each execution attempt admitted past the caller-transaction precondition emits
`merlin_supplier_options_tool_called` via the existing AuditLogService after the
read transaction ends. Metadata includes backend user,
trusted case, sanitized bound-ID input, canonical tool/version, invocation UUID,
authorization and resolver outcomes, result category/schema, option count and
observation/evaluation timestamps when available. Activity timestamps provide the
call recording time. Unvalidated tool names, requested foreign IDs, supplier
payloads, raw prompts and exception/SQL/stack data are not logged.

Tool-call audit does not write Problem Case Evaluation History or change its
current projection/lifecycle. The existing project convention propagates audit
failure and aborts result delivery. This boundary throws the safe
`SUPPLIER_OPTIONS_TOOL_AUDIT_FAILED` exception with no raw message or previous
exception attached; supplier payload is not returned. It does not retry the tool
or recursively audit the logging failure. Activitylog enablement and retention
remain existing operational configuration, not a new immutable evidence store.

## Validation

`tests/Feature/SupplierOptionsToolTest.php` covers direct permission checks,
fresh execution, super-admin restrictions, enumeration safety, all blocking domain
outcomes, authoritative exact mapping, empty/diagnostic success, explicit output
projection, business-write exclusion, audit sanitization and audit failure.
An independent-writer SQLite test verifies a common observation across source
validity, netting and supplier reads, then fresh invalidation on the next call.
This is composition evidence, not proof of MySQL transaction semantics.
The MySQL-only case changes stock and source data through an independent writer
after case lookup and verifies the original observation followed by fresh state
on the next execution. Existing supplier snapshot tests continue to protect the
public service transaction path.

Related: [resolver slice 2](material-shortage-resolver-slice-2.md),
[supplier domain contract](supplier-options-contract-v0.1.md),
[Problem Case foundation](problem-case-foundation-slice-1.md).
