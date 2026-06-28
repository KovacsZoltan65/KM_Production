# Architecture

## Project Architecture

KM_Production is a Laravel, Inertia, Vue, and MySQL manufacturing execution and production management system.

Use a layered architecture for all feature work:

```txt
Controller
-> Service
-> Repository
-> Model
```

## Layer Responsibilities

### Controllers

- Coordinate HTTP requests and responses.
- Authorize actions through policies or gates.
- Accept validated data from FormRequest classes.
- Call services for business operations.
- Return Inertia responses, redirects, JSON responses, or downloads.
- Do not contain business rules or query orchestration.

### Services

- Own business rules and workflow orchestration.
- Coordinate repositories, events, notifications, jobs, and activity logging.
- Wrap critical business operations in database transactions.
- Keep public methods aligned with domain actions.
- Return explicit values, DTOs, models, collections, or typed arrays as appropriate.

### Repositories

- Own data access and query logic.
- Hide query details from services.
- Use repository interfaces for dependencies.
- Keep reusable query scopes close to the model when they express model-level concepts.
- Do not perform business decisions.

### Models

- Represent persistence state and relationships.
- Define casts, attributes, scopes, relationships, and activity log options.
- Avoid orchestration logic.
- Avoid direct cross-domain workflow behavior.

## Dependency Direction

- Controllers depend on services.
- Services depend on repository interfaces and domain collaborators.
- Repositories depend on models.
- Models do not depend on controllers, services, or repositories.
- Frontend code communicates through Laravel routes and Inertia props, not directly with persistence.

## Transaction Rules

- Use database transactions for critical business operations.
- Transactions belong in services unless a lower-level persistence helper is explicitly scoped to a single write operation.
- Include all related writes in the same transaction when consistency matters.
- Avoid external network calls inside open transactions.
- Dispatch queue jobs after committed state is available.
- Never update inventory quantities directly; persist stock changes through stock movement records.

## Separation of Responsibilities

- Keep validation in FormRequest classes.
- Keep authorization in policies, gates, and permission checks.
- Keep presentation formatting in Vue components or dedicated transformers/resources.
- Keep domain calculations in services or focused domain helpers.
- Keep database access in repositories or model scopes.
- Keep audit logging close to the business action that caused the change.

## Domain-Driven Organization

- Organize code around manufacturing domains and workflows.
- Prefer explicit domain names over generic technical names.
- Keep production, inventory, quality, procurement, documentation, and permissions concerns distinct.
- Avoid mixing professional roles with authorization roles.
- Preserve traceability when changing operation sequences, inventory movement flows, or production order behavior.

## Coding Principles

- Use explicit return types.
- Prefer small methods with clear names.
- Add abstractions only when they reduce real duplication or clarify domain boundaries.
- Use enums for stable domain states and types.
- Keep business-critical behavior covered by tests.
- Log important business actions.
- Preserve existing patterns unless there is a clear reason to change them.
