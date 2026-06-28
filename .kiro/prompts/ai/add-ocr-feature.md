# Purpose

Reusable prompt for adding OCR-assisted workflows.

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

Add an OCR-assisted feature for: [DOCUMENT / WORKFLOW].

Preserve existing behavior unless explicitly requested. Do not modify unrelated files.

Follow Laravel -> Queue -> Python Worker -> OCR -> Structured JSON -> Laravel Validation -> Human Review -> Database -> Audit Log.
Laravel owns business logic, permissions, validation, database writes, and audit logs.
Python owns OCR processing only and must not access the Laravel database.

Keep Controller -> Service -> Repository -> Model.
```

# Required Checks

- Uploads are validated and treated as untrusted.
- OCR runs in a queue.
- OCR returns structured JSON with confidence.
- Laravel validates OCR output.
- Low-confidence results require review.
- No direct database access from Python.
- Activity logs capture processing and review.
- Tests cover validation, authorization, and review behavior.

# Expected Output

Report files changed, OCR workflow, tests run, confidence/review behavior, and any operational setup needed.
