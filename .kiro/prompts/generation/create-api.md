# Purpose

Reusable prompt for creating JSON API endpoints in KM_Production.

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

Create JSON API endpoints for: [RESOURCE / WORKFLOW].

Preserve existing behavior unless explicitly requested. Do not modify unrelated files.

Follow Controller -> Service -> Repository -> Model.
Use FormRequest validation, policy authorization, consistent JSON response shapes, proper HTTP status codes, activity logging, and transactions for critical workflows.

Use RESTful conventions, predictable URLs, plural resource names, server-side pagination, filter[...], sort, and search where appropriate.
```

# Required Checks

- Every protected endpoint is authorized.
- Every request is validated.
- Responses use consistent success/error shapes.
- Filtering, sorting, searching, and pagination are repository-owned.
- Modifying endpoints log important actions.
- Critical workflows use transactions.
- Tests cover authorization, validation, success, error, and side effects.

# Expected Output

Report endpoint list, files changed, tests run, response format, and any compatibility notes.
