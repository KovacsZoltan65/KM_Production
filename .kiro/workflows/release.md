# Purpose

Define the release preparation workflow for KM_Production.

# When to Use

Use this workflow before preparing, merging, tagging, or deploying a release.

# Required Context

- `AGENTS.md`
- `.kiro/checklists/release.md`
- Release scope
- Migration list
- Permission and seeder changes
- Test results
- Deployment environment notes

# Workflow Steps

1. Confirm release scope.
   - Identify included features, fixes, migrations, and operational changes.

2. Run tests.
   - Run backend, frontend, and focused tests as appropriate.

3. Review migrations.
   - Check schema changes, data safety, rollback path, and downtime risk.

4. Review permissions.
   - Confirm new permissions are migrated or seeded.
   - Confirm roles are updated intentionally.

5. Review seeders.
   - Confirm seed data is safe and environment-appropriate.

6. Review translations.
   - Confirm user-facing labels are translated.

7. Review docs.
   - Confirm relevant documentation is current.

8. Review security.
   - Confirm authorization, secrets, uploads, and sensitive endpoints.

9. Review performance.
   - Confirm large queries, reports, dashboards, queues, and AI tasks are safe.

10. Prepare release notes.
    - Include behavior changes, migrations, permissions, and operational steps.

11. Prepare rollback plan.
    - Identify revert steps and any irreversible changes.

# Required Quality Gates

- [ ] Release checklist completed.
- [ ] Tests pass or exceptions documented.
- [ ] Migrations reviewed.
- [ ] Rollback plan prepared.
- [ ] Security and performance reviewed.

# Documentation Updates

Update release notes and any affected project documentation before deployment.

# Final Report Format

- Release summary
- Created files
- Modified files
- Tests run
- Migration notes
- Permission notes
- Deployment notes
- Rollback plan
- Known risks

# Common Failure Modes

- Missing permission deployment.
- Missing translations.
- Unreviewed migrations.
- No rollback plan.
- Queue or worker requirements omitted.
