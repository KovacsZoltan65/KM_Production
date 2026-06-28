# Purpose

Define the workflow for fixing bugs safely in KM_Production.

# When to Use

Use this workflow when correcting broken, unexpected, or regressed behavior.

# Required Context

- `AGENTS.md`
- Relevant `.kiro/steering/`
- Relevant `.kiro/knowledge/`
- Relevant `.kiro/decisions/`
- Relevant `.kiro/playbooks/`
- Relevant `.kiro/checklists/`
- Existing tests and issue reports

# Workflow Steps

1. Reproduce the issue.
   - Identify the expected behavior.
   - Identify the actual behavior.
   - Capture the smallest reliable reproduction.

2. Identify the affected layer.
   - Controller, service, repository, model, frontend, database, queue, permissions, translations, or tests.

3. Find the root cause.
   - Trace the behavior through `Controller -> Service -> Repository -> Model`.
   - Check relevant policies, FormRequests, translations, and activity logs.

4. Make the minimal fix.
   - Preserve existing behavior unless explicitly requested.
   - Do not modify unrelated files.
   - Avoid broad refactors.

5. Add a regression test.
   - Prefer the narrowest test that proves the bug will not return.

6. Verify no behavior regression.
   - Run focused tests.
   - Run broader tests when the affected surface is shared or risky.

7. Document if rule or decision changes.
   - Update steering, ADRs, knowledge, or playbooks only if the fix changes reusable guidance.

# Required Quality Gates

- [ ] Bug reproduced or clearly understood.
- [ ] Root cause identified.
- [ ] Minimal fix applied.
- [ ] Regression test added.
- [ ] Relevant checklist completed.

# Documentation Updates

Update documentation only if the bug reveals a missing rule, incorrect decision, unclear domain concept, or repeatable fix pattern.

# Final Report Format

- Summary of bug and fix
- Root cause
- Created files
- Modified files
- Tests run
- Tests not run with reason
- Residual risk

# Common Failure Modes

- Fixing symptoms instead of root cause.
- Refactoring unrelated code.
- Changing behavior without approval.
- Forgetting regression tests.
- Missing authorization or validation side effects.
