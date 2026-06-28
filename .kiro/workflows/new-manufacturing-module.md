# Purpose

Define the workflow for creating new manufacturing domain modules.

# When to Use

Use this workflow for new production, inventory, procurement, quality, documentation, traceability, serial, or work-center modules.

# Required Context

- `AGENTS.md`
- Relevant `.kiro/knowledge/` documents
- Relevant `.kiro/decisions/` ADRs
- `.kiro/steering/architecture.md`
- `.kiro/steering/backend.md`
- `.kiro/steering/frontend.md`
- `.kiro/playbooks/create-crud-module.md`
- `.kiro/checklists/new-module.md`

# Workflow Steps

1. Understand the domain concept.
   - Define what the module represents in manufacturing terms.

2. Read relevant knowledge docs.
   - Inventory, production, procurement, quality, documents, or AI.

3. Check ADRs.
   - Stock movements, sequence versioning, quality, serial numbering, and document AI may constrain design.

4. Define entities and relationships.
   - Identify master data, workflow records, traceability records, and documents.

5. Define statuses and enums.
   - Use enums for stable states.
   - Avoid enums for configurable reference data.

6. Define permissions.
   - CRUD permissions.
   - Special workflow permissions such as `check` where needed.

7. Define workflows.
   - Status transitions.
   - Required actions.
   - Transaction boundaries.
   - Human review points.

8. Define audit events.
   - Identify important business actions that must be logged.

9. Create backend layers.
   - Migration, model, factory, repository interface, repository, service, policy, FormRequests, controller, routes.

10. Create admin UI.
    - Vue pages, reusable components, translations, loading state, empty state, validation errors, permission-aware actions.

11. Add tests.
    - Authorization, validation, service workflow, repository queries, and business-critical side effects.

12. Update docs.
    - Knowledge, steering, ADR, playbook, prompt, checklist, or workflow updates only when reusable guidance changes.

# Required Quality Gates

- [ ] New module checklist completed.
- [ ] Before-commit checklist completed.
- [ ] Security checklist completed when protected data or actions are involved.
- [ ] Performance checklist completed for large data or reports.
- [ ] Documentation checklist completed.

# Documentation Updates

Update knowledge docs when the module introduces a new domain concept.

Create or update ADRs when the module makes a durable architectural decision.

# Final Report Format

- Summary
- Domain concept implemented
- Created files
- Modified files
- Tests run
- Tests not run with reason
- Risks or assumptions
- Next recommended step

# Common Failure Modes

- Starting with database tables before understanding the domain.
- Missing permission boundaries.
- Missing audit events.
- Direct stock mutation.
- Breaking traceability by overwriting historical records.
- Skipping translations.
