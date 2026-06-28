# Purpose

Reusable prompt for refactoring an existing module without changing behavior.

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

Refactor this module without changing behavior: [MODULE / FILES].

Preserve existing behavior unless explicitly requested. Do not modify unrelated files.

Improve structure, naming, duplication, and layer boundaries while keeping Controller -> Service -> Repository -> Model.
Maintain authorization, validation, translations, activity logging, and tests.
```

# Required Checks

- Behavior is preserved.
- No unrelated refactors are included.
- Controllers remain thin.
- Services and repositories have clear responsibilities.
- Translations and permissions still work.
- Existing tests pass or unchanged areas are not disturbed.

# Expected Output

Report what was refactored, files changed, tests run, behavior-preservation evidence, and remaining risks.
