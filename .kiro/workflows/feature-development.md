# Purpose

Define the standard end-to-end workflow for implementing a new feature in KM_Production.

# When to Use

Use this workflow when adding new user-facing or internal functionality that changes application behavior.

# Required Context

- `AGENTS.md`
- Relevant `.kiro/steering/` documents
- Relevant `.kiro/knowledge/` documents
- Relevant `.kiro/decisions/` ADRs
- Relevant `.kiro/playbooks/`
- Relevant `.kiro/checklists/`

# Workflow Steps

1. Understand the request.
   - Identify the desired outcome.
   - Identify affected domain areas.
   - Preserve existing behavior unless explicitly requested.
   - Do not modify unrelated files.

2. Read `AGENTS.md`.
   - Confirm project-wide rules.
   - Confirm architecture and prohibitions.

3. Read relevant `.kiro/steering/` docs.
   - Architecture, backend, frontend, API, security, coding style, and testing guidance may apply.

4. Read relevant `.kiro/knowledge/` docs.
   - Understand the manufacturing domain before implementing.

5. Check `.kiro/decisions/`.
   - Respect ADRs for stock movements, operation sequence versioning, quality control, serial numbering, and AI isolation.

6. Select relevant `.kiro/playbooks/`.
   - Use task-specific SOPs instead of inventing a new implementation path.

7. Implement using project architecture.
   - Follow `Controller -> Service -> Repository -> Model`.
   - Add authorization through policies and permissions.
   - Add FormRequest validation.
   - Use transactions where consistency matters.
   - Add activity logging for important business actions.
   - Use shared translations.

8. Add or update tests.
   - Cover authorization, validation, success path, failure path, and business-critical side effects.

9. Run relevant checklists.
   - At minimum use `before-commit`.
   - Use `new-module`, `security`, `performance`, `documentation`, or `ai-feature` when relevant.

10. Update documentation if needed.
    - Update steering, decisions, knowledge, playbooks, prompts, checklists, or workflows only when the task changes reusable guidance.

11. Report changes clearly.
    - Summarize what changed, files touched, tests run, and residual risks.

# Required Quality Gates

- [ ] Architecture boundary reviewed.
- [ ] Authorization implemented.
- [ ] Validation implemented.
- [ ] Activity logging added where required.
- [ ] Translations added.
- [ ] Tests added or updated.
- [ ] Relevant checklist completed.

# Documentation Updates

Update documentation only when the feature changes:

- project rules
- architectural decisions
- domain concepts
- repeatable implementation procedures
- reusable prompt templates
- mandatory quality gates
- workflow process

# Final Report Format

- Summary
- Created files
- Modified files
- Tests run
- Tests not run with reason
- Risks or assumptions
- Next recommended step

# Common Failure Modes

- Implementing before reading domain knowledge.
- Putting business logic in controllers.
- Skipping policy authorization.
- Hardcoding labels instead of translations.
- Forgetting activity logs.
- Skipping tests for failure paths.
- Modifying unrelated files.
