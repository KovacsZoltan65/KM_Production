# Architecture

## Purpose

This page is the reader-facing architecture overview for KM_Production. Detailed AI-agent rules live in [.kiro/steering/architecture.md](../.kiro/steering/architecture.md).

## Application Architecture

KM_Production follows a layered Laravel architecture:

```txt
Controller
-> Service
-> Repository
-> Model
```

Controllers coordinate requests, authorization, validation, and responses. Services contain business rules and workflow orchestration. Repositories contain data access and query logic. Models represent persistence state, relationships, casts, scopes, and activity log configuration.

## Key Boundaries

- Business logic belongs in services, not controllers.
- Data access belongs in repositories and model scopes.
- Critical business operations use database transactions.
- Important business actions are logged.
- Inventory quantities are changed only through stock movements.
- Manufacturing traceability must survive later master data changes.

## Documentation Graph

- Architecture rules: [.kiro/steering/architecture.md](../.kiro/steering/architecture.md)
- Backend rules: [.kiro/steering/backend.md](../.kiro/steering/backend.md)
- Frontend rules: [.kiro/steering/frontend.md](../.kiro/steering/frontend.md)
- API rules: [.kiro/steering/api.md](../.kiro/steering/api.md)
- Architecture decisions: [.kiro/decisions/](../.kiro/decisions/)
- Permanent memory: [.kiro/memory/](../.kiro/memory/)
