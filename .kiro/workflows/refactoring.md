# Purpose

Define the workflow for safe refactoring without behavior changes.

# When to Use

Use this workflow when improving structure, readability, duplication, naming, or layer boundaries while preserving behavior.

# Required Context

- `AGENTS.md`
- `.kiro/steering/coding-style.md`
- `.kiro/steering/architecture.md`
- Relevant `.kiro/playbooks/`
- Relevant `.kiro/checklists/`
- Existing tests

# Workflow Steps

1. Preserve behavior.
   - Confirm the refactor is not a feature change.
   - Do not modify unrelated files.

2. Identify the smell.
   - Examples: duplicated logic, oversized component, controller workflow logic, repeated query code, unclear naming.

3. Add or confirm tests before refactor.
   - Ensure current behavior is protected.
   - Add focused tests when the behavior is business-critical.

4. Refactor in small steps.
   - Keep commits or change groups reviewable.
   - Preserve public APIs unless explicitly requested.

5. Keep architecture boundaries stable.
   - Controllers coordinate.
   - Services orchestrate workflows.
   - Repositories own queries.
   - Models avoid large workflows.

6. Avoid feature changes.
   - Do not add new behavior during refactoring.
   - Do not change UI flows unless requested.

7. Run relevant tests and checks.
   - Run focused tests first.
   - Run broader tests when shared code is affected.

8. Update docs only if structure changes.
   - Update steering or playbooks only when reusable guidance changes.

# Required Quality Gates

- [ ] Tests existed or were added before risky refactor.
- [ ] Public behavior remains stable.
- [ ] No unrelated cleanup included.
- [ ] Relevant checklist completed.

# Documentation Updates

Update documentation if the refactor establishes a new project pattern or changes the recommended implementation path.

# Final Report Format

- Summary of refactor
- Behavior preserved evidence
- Created files
- Modified files
- Tests run
- Tests not run with reason
- Residual risk

# Common Failure Modes

- Combining refactor with feature work.
- Changing public APIs unintentionally.
- Removing behavior that looked unused but was used indirectly.
- Skipping tests before restructuring.
