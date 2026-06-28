# Purpose

Reusable prompt for creating a dashboard with metrics, charts, cards, and tables.

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

Create a dashboard for: [AUDIENCE / DOMAIN].

Preserve existing behavior unless explicitly requested. Do not modify unrelated files.

Define metrics, cards, charts, tables, filters, permissions, and localization. Keep metric calculations in services/repositories, not Vue.

Follow Controller -> Service -> Repository -> Model.
Use repositories for aggregation queries and services for metric orchestration.
Use caching only when justified and safe.
Keep the dashboard responsive and performant.
```

# Required Checks

- Metrics have clear business definitions.
- Access is permission-protected.
- Filters are validated.
- Expensive queries are optimized or cached.
- UI has loading and empty states.
- Translations are shared.
- Tests cover critical metric behavior where practical.

# Expected Output

Report files changed, dashboard behavior, tests run, performance notes, and any assumptions about metric definitions.
