# Purpose

Mandatory checklist before preparing or deploying a release.

# Checklist

- [ ] Tests pass.
- [ ] Migrations reviewed.
- [ ] Rollback reviewed.
- [ ] Permissions migrated.
- [ ] Seeders reviewed.
- [ ] Translations complete.
- [ ] Activity logging verified.
- [ ] Performance reviewed.
- [ ] Security reviewed.
- [ ] Deployment notes written.
- [ ] Environment configuration reviewed.
- [ ] Queue workers and scheduled jobs reviewed.
- [ ] AI/OCR worker impact reviewed where applicable.

# Common Mistakes

- Deploying migrations without rollback awareness.
- Forgetting new permissions in production.
- Missing translation keys.
- Ignoring queue worker requirements.
- Releasing without documenting deployment notes.

# Completion Criteria

- Release is deployable, reversible where practical, and documented.
- Operational impacts are known before deployment.
