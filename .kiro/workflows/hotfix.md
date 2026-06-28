# Purpose

Define the urgent production fix workflow for KM_Production.

# When to Use

Use this workflow for urgent production issues where speed matters but safety and traceability still apply.

# Required Context

- `AGENTS.md`
- Relevant steering and ADRs
- Incident details
- Affected logs, tests, or reproduction
- Deployment and rollback constraints

# Workflow Steps

1. Confirm urgency and impact.
   - Identify affected users, workflows, data, and operational risk.

2. Minimize scope.
   - Fix only the production issue.
   - Avoid unrelated cleanup.
   - Preserve existing behavior unless explicitly requested.

3. Prioritize safety.
   - Protect data integrity.
   - Protect inventory, production, quality, serial, and audit history.

4. Identify rollback strategy.
   - Determine whether the fix can be reverted safely.
   - Review migration or configuration impact.

5. Make targeted fix.
   - Follow `Controller -> Service -> Repository -> Model`.
   - Preserve authorization, validation, transactions, and activity logging.

6. Add targeted test if possible.
   - Add the smallest useful regression test.
   - If no test is possible, document why.

7. Verify.
   - Run focused tests.
   - Perform manual verification when needed.

8. Document risk.
   - Note residual risk, skipped tests, and follow-up cleanup.

# Required Quality Gates

- [ ] Scope is minimal.
- [ ] Rollback path reviewed.
- [ ] Focused verification completed.
- [ ] Residual risk documented.

# Documentation Updates

Update documentation only when the hotfix reveals a reusable rule, missing ADR, or changed operational procedure.

# Final Report Format

- Summary
- Production risk addressed
- Created files
- Modified files
- Verification performed
- Tests not run with reason
- Rollback notes
- Residual risk

# Common Failure Modes

- Expanding scope during an urgent fix.
- Skipping rollback thinking.
- Making database changes without deployment review.
- Omitting residual risk from the final report.
