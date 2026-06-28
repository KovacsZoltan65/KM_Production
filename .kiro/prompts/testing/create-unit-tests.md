# Purpose

Reusable prompt for creating focused unit or service-level tests.

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

Create unit tests for: [CLASS / SERVICE / HELPER / ENUM].

Preserve existing behavior unless explicitly requested. Do not modify unrelated files.

Use clear test names and focused setup. Test business rules, edge cases, invalid states, enum behavior, service decisions, and regression cases.
Keep Controller -> Service -> Repository -> Model responsibilities in mind.
```

# Required Checks

- Tests are deterministic.
- Edge cases are covered.
- Business-critical branches are covered.
- Factories are used where database setup is needed.
- Tests do not assert incidental implementation details.
- Existing behavior is preserved.

# Expected Output

Report tests added, scenarios covered, test command run, results, and any residual risk.
