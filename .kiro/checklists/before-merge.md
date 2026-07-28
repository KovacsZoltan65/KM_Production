# Purpose

Mandatory checklist before merging work into a shared branch.

Detailed process: [code review guide](../../docs/project-management/code-review-guide.md).

# Checklist

- [ ] Regression review completed.
- [ ] Breaking changes identified.
- [ ] Performance review completed.
- [ ] Security review completed.
- [ ] Translation review completed.
- [ ] Permission review completed.
- [ ] API compatibility reviewed.
- [ ] Database changes reviewed.
- [ ] Deployment impact reviewed.
- [ ] Rollback possibility reviewed.
- [ ] Tests pass in the expected environment.
- [ ] Documentation changes are complete.
- [ ] No unrelated changes are included.
- [ ] PR title follows the commit message convention.
- [ ] Test evidence lists commands that actually ran.
- [ ] No unresolved `BLOCKER` or `REQUIRED` review comment remains.
- [ ] PR description, backlog state, migration notes, and rollback are current.

# Common Mistakes

- Merging without checking permission migrations or seeders.
- Missing translation keys.
- Ignoring slow queries introduced by new dashboards or reports.
- Treating local success as deployment readiness.
- Forgetting rollback impact for migrations.

# Completion Criteria

- Merge risk is understood.
- Required reviews are complete.
- Deployment and rollback notes are available when needed.
