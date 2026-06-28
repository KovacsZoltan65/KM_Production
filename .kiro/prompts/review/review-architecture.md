# Purpose

Reusable prompt for reviewing architecture and layer boundaries.

# Context to Read First

AI agents should consult:

- `AGENTS.md`
- `.kiro/steering/`
- `.kiro/decisions/`
- `.kiro/knowledge/`
- `.kiro/playbooks/`

# Prompt Template

```txt
You are reviewing KM_Production changes for architecture.

Review: [FILES / BRANCH / DIFF].

Preserve existing behavior unless explicitly requested. Do not modify unrelated files unless asked to fix findings.

Check adherence to Controller -> Service -> Repository -> Model.
Verify controllers coordinate only, services own workflows, repositories own queries, and models avoid orchestration.
Check authorization, validation, translations, activity logging, transactions, and tests where relevant.
```

# Required Checks

- No business logic in controllers.
- No complex queries in controllers.
- Repository interfaces are used where expected.
- Business workflows are service-owned.
- Critical workflows use transactions.
- ADR constraints are respected.
- No new architecture pattern is introduced without reason.

# Expected Output

Lead with architecture findings ordered by severity, with file and line references. Include summary, assumptions, and test gaps.
