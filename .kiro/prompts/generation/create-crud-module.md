# Purpose

Reusable prompt for creating a complete CRUD module in KM_Production.

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

Create a CRUD module for: [MODULE NAME].

Preserve existing behavior unless explicitly requested. Do not modify unrelated files.

Follow Controller -> Service -> Repository -> Model.

Use:
- migration, model, factory, seeder if needed
- enum if justified
- repository interface and repository
- service for business rules and transactions
- policy authorization
- FormRequest validation
- controller coordination only
- routes
- explicit permissions
- shared translations
- Vue 3 Composition API, Inertia, PrimeVue, Tailwind
- tests for critical behavior

Do not place business logic or complex queries in controllers.
Use activity logging for important business actions.
Use transactions where consistency matters.
Keep inventory changes behind stock movement workflows.
```

# Required Checks

- Authorization is enforced through policies and permissions.
- Validation uses FormRequests.
- Translations use shared Laravel JSON keys.
- Important actions are logged.
- Tests cover authorization, validation, success paths, and critical side effects.
- No unrelated files are modified.

# Expected Output

Report created and modified files, tests run, any skipped items, and any remaining risks or assumptions.
