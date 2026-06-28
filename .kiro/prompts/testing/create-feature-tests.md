# Purpose

Reusable prompt for creating Laravel feature tests.

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

Create feature tests for: [FEATURE / WORKFLOW].

Preserve existing behavior unless explicitly requested. Do not modify unrelated files.

Test HTTP behavior, authorization, validation, success response, error response, redirects or Inertia props, activity logging, and business-critical side effects.
Respect Controller -> Service -> Repository -> Model responsibilities.
Use factories and clear Pest test names.
```

# Required Checks

- Authorized and unauthorized paths are covered.
- Validation failures are covered.
- Success path is covered.
- Business side effects are asserted.
- Activity logs are asserted where important.
- Inventory, quality, serial, and traceability rules are covered when relevant.

# Expected Output

Report tests added, scenarios covered, test command run, results, and any remaining coverage gaps.
