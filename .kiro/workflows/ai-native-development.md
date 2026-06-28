# AI-Native Development Workflow

# Purpose

Define the project-wide AI-native development workflow for KM_Production.

Every AI-assisted task should follow this lifecycle from request intake through implementation, verification, documentation, and learning.

# When to Use

Use this workflow for all AI-assisted development, review, maintenance, testing, documentation, AI/OCR, and manufacturing module work.

# Required Context

Read in this order:

1. `AGENTS.md`
2. `.kiro/steering/`
3. `.kiro/decisions/`
4. `.kiro/knowledge/`
5. `.kiro/playbooks/`
6. `.kiro/prompts/`
7. `.kiro/templates/`
8. `.kiro/checklists/`
9. `.kiro/workflows/`
10. `.kiro/memory/`

# Workflow Steps

1. Intake
   - Clarify the requested outcome.
   - Identify whether it is:
     - feature
     - bug fix
     - refactor
     - documentation
     - AI feature
     - manufacturing module
     - security review
     - performance review
   - Preserve existing behavior unless explicitly requested.
   - Do not modify unrelated files.

2. Context Loading
   - Read `AGENTS.md`.
   - Read relevant steering, decisions, knowledge, playbooks, prompts, templates, checklists, workflows, and memory.
   - Load only context relevant to the task.

3. Impact Analysis
   - Identify affected areas:
     - backend
     - frontend
     - database
     - permissions
     - translations
     - tests
     - documentation
     - AI/OCR
     - inventory
     - production
     - quality
     - procurement

4. Execution Plan
   - Prepare a short implementation plan.
   - Mention:
     - files likely affected
     - risks
     - tests needed
     - documentation updates

5. Implementation
   - Follow:

```txt
Controller
-> Service
-> Repository
-> Model
```

   - Respect:
     - authorization
     - validation
     - transactions
     - audit logging
     - localization
     - existing conventions

6. Verification
   - Run or recommend:
     - Pest tests
     - frontend tests if relevant
     - type/static checks
     - Pint
     - Larastan/PHPStan
     - build
     - manual verification

7. Quality Gates
   - Run relevant checklists:
     - before-commit
     - new-module
     - security
     - performance
     - documentation
     - ai-feature
     - release where relevant

8. Documentation Sync
   - Update relevant documentation only when needed:
     - steering
     - decisions
     - knowledge
     - playbooks
     - prompts
     - checklists
     - README
     - AGENTS.md

9. Final Report
   - Must include:
     - summary
     - created files
     - modified files
     - tests run
     - tests not run with reason
     - risks
     - next recommended step

10. Learning Loop
    - If the task reveals a new reusable rule, suggest updating:
      - steering
      - decision
      - knowledge
      - playbook
     - prompt
     - checklist
     - workflow
      - memory

The goal is not only to complete the task, but to improve the project’s ability to complete similar tasks better next time.

# Required Quality Gates

- [ ] Correct workflow selected.
- [ ] Required context loaded.
- [ ] Impact analysis completed.
- [ ] Relevant playbook followed.
- [ ] Relevant checklist completed.
- [ ] Verification performed or skipped with reason.
- [ ] Documentation synced where needed.

# Documentation Updates

Documentation updates are required when the task changes reusable project knowledge, architecture, domain understanding, implementation process, prompt templates, checklists, workflows, or permanent memory.

Do not update documentation for incidental implementation details that do not affect future work.

# Final Report Format

- Summary
- Created files
- Modified files
- Tests run
- Tests not run with reason
- Risks
- Next recommended step

# Common Failure Modes

- Acting before loading context.
- Selecting the wrong workflow.
- Skipping impact analysis.
- Forgetting permissions, translations, or activity logging.
- Running no verification and failing to explain why.
- Updating unrelated files.
- Missing the learning loop after discovering reusable guidance.

# Related Navigation

- `.kiro/index.md`
- `.kiro/checklists/documentation.md`
- `.kiro/memory/index.md`
