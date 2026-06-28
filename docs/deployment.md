# Deployment

## Purpose

This page records deployment-facing documentation for KM_Production.

## Current Guidance

Deployment must preserve application reliability, traceability, database integrity, permissions, and auditability.

Before deployment, verify the relevant quality gates:

- [.kiro/checklists/before-merge.md](../.kiro/checklists/before-merge.md)
- [.kiro/checklists/before-commit.md](../.kiro/checklists/before-commit.md)
- [.kiro/checklists/release.md](../.kiro/checklists/release.md)
- [.kiro/checklists/security.md](../.kiro/checklists/security.md)

## Deployment Notes

- Keep secrets in environment configuration, not documentation or source control.
- Run database changes through Laravel migrations.
- Do not manually alter production inventory quantities.
- Preserve audit logs and document storage.
- Confirm frontend assets are built before release.

## Related Documentation

- [Architecture](architecture.md)
- [Manufacturing](manufacturing.md)
- [Security steering](../.kiro/steering/security.md)
- [Release workflow](../.kiro/workflows/release.md)
