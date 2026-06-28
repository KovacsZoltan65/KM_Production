# Purpose

Reusable prompt for creating filtered, grouped, exportable reports.

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

Create a report for: [REPORT NAME / BUSINESS QUESTION].

Preserve existing behavior unless explicitly requested. Do not modify unrelated files.

Implement filtering, sorting, grouping, totals, export behavior, permissions, audit logging where sensitive, and performance protections.

Follow Controller -> Service -> Repository -> Model.
Repositories own report queries.
Services own report orchestration and business calculations.
Controllers only authorize, validate, call services, and return responses.
```

# Required Checks

- Report filters and sort fields are explicit and validated.
- Totals match filtered data.
- CSV, Excel, or PDF export is implemented only as requested.
- Sensitive exports are authorized and audited.
- Large reports are paginated or queued.
- Labels are localized.

# Expected Output

Report created/modified files, supported filters and exports, tests run, and performance or audit notes.
