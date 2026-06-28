# Purpose

Reusable prompt for adding AI vision-assisted workflows.

# Context to Read First

AI agents should consult:

- `AGENTS.md`
- `.kiro/steering/`
- `.kiro/decisions/`
- `.kiro/knowledge/`
- `.kiro/playbooks/`

# Prompt Template

```txt
You are working on the KM_Production Laravel + Vue + Inertia MES project.

Add a vision AI feature for: [INSPECTION / DETECTION / WORKFLOW].

Preserve existing behavior unless explicitly requested. Do not modify unrelated files.

Use queued processing and structured JSON results with confidence. Laravel validates all results and owns business decisions.
AI vision may assist defect detection, paint inspection, weld inspection, dimension verification, or missing component detection, but it must not bypass required quality inspections.

Follow Controller -> Service -> Repository -> Model.
```

# Required Checks

- Image uploads are validated.
- Vision processing runs outside HTTP request lifecycle.
- Output includes confidence and evidence metadata.
- Low-confidence or high-impact results require human review.
- Required inspections are not bypassed.
- AI does not auto-approve failed inspections.
- Audit logs capture execution and review.

# Expected Output

Report files changed, vision workflow, review behavior, tests run, and any model/provider assumptions.
