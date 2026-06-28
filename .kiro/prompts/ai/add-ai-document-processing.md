# Purpose

Reusable prompt for adding AI document classification and extraction workflows.

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

Add AI document processing for: [DOCUMENT TYPE / WORKFLOW].

Preserve existing behavior unless explicitly requested. Do not modify unrelated files.

Implement Upload -> Queue -> OCR/Classification/Extraction -> Structured JSON -> Laravel Validation -> Human Review -> Approval -> Database -> Audit Log.
Laravel owns permissions, validation, workflows, database writes, and audit logs.
AI/Python workers must not directly access the Laravel database.

Follow Controller -> Service -> Repository -> Model.
```

# Required Checks

- Document upload validation is secure.
- Processing is queued.
- Structured JSON includes extracted fields and confidence.
- Laravel validates schema and business constraints.
- Low-confidence results require human review.
- Approved results are saved through services.
- Activity logs capture AI execution, review, and accepted changes.
- Tests cover authorization, validation, low confidence, approval, and failure paths.

# Expected Output

Report files changed, supported document types, JSON schema, review workflow, tests run, and any remaining risks.
