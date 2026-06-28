# Purpose

Reusable prompt for optimizing slow or risky queries.

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

Optimize this query or data-loading path: [QUERY / PAGE / REPORT / ENDPOINT].

Preserve existing behavior unless explicitly requested. Do not modify unrelated files.

Keep query logic in repositories. Keep workflow logic in services. Do not move business logic into controllers.
Address N+1 queries, indexes, pagination, filtering, sorting, eager loading, caching, and report/dashboard performance as appropriate.
```

# Required Checks

- Query behavior remains equivalent.
- Repository owns query changes.
- Pagination is used for large data.
- Eager loading is intentional.
- Index needs are identified.
- Authorization and validation are unchanged.
- Tests or measurements verify improvement where practical.

# Expected Output

Report query changes, files changed, tests or measurements run, performance impact, and any recommended follow-up.
