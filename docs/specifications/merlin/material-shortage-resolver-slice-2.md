# Material Shortage Resolver – current evaluation, slice 2

`MaterialShortageProblemCaseResolver::resolve(problem_case_id)` reads an OPEN
Material Shortage case and returns an immutable `MaterialShortageCurrentEvaluation`.
It does not update the persisted current projection, detection snapshot, lifecycle,
or evaluation history. Missing case IDs use Eloquent's existing `findOrFail`
boundary. CLOSED and INVALIDATED cases reject current evaluation with
`LogicException`; they remain historical cases.

Source assessment belongs to the shared MRP
`MaterialRequirementDemandEligibilityService::assessSource()` under
[ADR 0016](../../../.kiro/decisions/0016-material-requirement-demand-eligibility.md).
Normal netting derives its eligibility boolean from this same assessment.
Resolver source reads include soft-deleted parents so a proven deletion is
distinguished from unavailable lineage. Independently sufficient invalid facts
take precedence over partial or contradictory lineage.

The accepted Current Evaluation contract decision keeps
`ProblemCaseEvaluationResult` unchanged. Source validity is a separate
`MaterialRequirementSourceValidity` value:

| Source validity                    | Evaluation   | Net requirement      | Invalidation candidate |
| ---------------------------------- | ------------ | -------------------- | ---------------------- |
| Invalid                            | null         | null                 | true                   |
| Undetermined                       | UNDETERMINED | null                 | false                  |
| Valid, selected result unavailable | UNDETERMINED | null                 | false                  |
| Valid, authoritative positive net  | ACTIVE       | exact netting string | false                  |
| Valid, authoritative zero net      | RESOLVED     | `0.000`              | false                  |

`sourceValidityAuthoritative` indicates proven validity or invalidity.
`nettingAuthoritative()` indicates a selected authoritative netting result.
These are separate facts: proven invalidity never claims a netting evaluation.
`invalidationCandidate()` derives the case consequence from invalid source
validity; it does not execute invalidation.

Valid sources use the normal full competing-scope
`MaterialRequirementNettingService::calculate()` before selecting the case's
requirement result. No isolated-demand calculation or snapshot quantity is used.
An absent selected result never becomes zero shortage. Completely absent legacy
production lineage remains eligible and is exposed through
`legacyProductionLineage`; a soft-deleted Production Order is invalid instead.
MR snapshot status remains non-authoritative.

Reasons are internal evidence identifiers, not lifecycle transition reasons or
user-facing translation text. This internal trusted-backend capability grants
no permission and provides no route, UI, AI, supplier tool, action execution,
or history-recording orchestration.

The [slice 1 persistence foundation](problem-case-foundation-slice-1.md) retains
its historical projection and append-only history contracts. A prior stored
evaluation remains intact even when the current read reports invalid source.
