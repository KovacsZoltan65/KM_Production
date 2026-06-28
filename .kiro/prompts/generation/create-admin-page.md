# Purpose

Reusable prompt for creating an admin index or management page.

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

Create an admin page for: [RESOURCE / WORKFLOW].

Preserve existing behavior unless explicitly requested. Do not modify unrelated files.

Use Inertia and Vue 3 Composition API. Use PrimeVue DataTable, server-side pagination, sorting, filtering, global search where appropriate, loading state, empty state, reusable components, AdminPerPageSelect where available, confirmation dialogs, and permission-aware actions.

Follow Controller -> Service -> Repository -> Model.
Controllers authorize, validate, call services, and return responses.
Repositories own query logic.
Services own workflow decisions.

Use shared translations. In templates use $t(...). In <script setup> use trans(...).
```

# Required Checks

- Server-side pagination, sorting, filtering, and search are implemented in repositories.
- Backend authorization is enforced.
- Frontend permission props are UX only.
- Labels are localized.
- Destructive actions use confirmation dialogs.
- No unrelated files are modified.

# Expected Output

Report created and modified files, tests run, UI behavior implemented, and any limitations.
