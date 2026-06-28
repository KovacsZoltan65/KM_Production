# Backend

## Laravel Conventions

- Use Laravel conventions unless the project has a stronger local pattern.
- Keep controllers thin.
- Place business rules in services.
- Place query and persistence logic in repositories.
- Use explicit return types on methods.
- Prefer constructor injection and interfaces for replaceable dependencies.
- Keep methods focused on one business action or one query concern.

## Repositories

- Use Prettus Repository conventions where applicable.
- Define repository interfaces for service dependencies.
- Keep query construction, filtering, eager loading, and persistence details in repositories.
- Return predictable types: models, collections, paginators, booleans, or typed arrays.
- Do not place authorization, validation, or business workflow decisions in repositories.
- Avoid duplicating query logic across controllers or services.

## Services

- Services own domain workflows and business decisions.
- Services coordinate repositories, events, policies, activity logging, and queues.
- Critical write workflows must use database transactions.
- Public service methods should map to business actions.
- Important business actions must be logged.
- Services must not bypass stock movement rules or traceability rules.

## Policies and Permissions

- Use policy-based authorization for model and workflow actions.
- Use Spatie Permission for authorization roles and permissions.
- Keep professional employee roles separate from authorization roles.
- Use the project permission vocabulary:
  - `create`
  - `view`
  - `update`
  - `delete`
  - `check`
- Apply authorization before performing business actions.

## FormRequests and Validation

- Use FormRequest classes for request validation.
- Keep validation close to the request boundary.
- Use authorization methods in FormRequests only for request-level checks.
- Use policies for domain and model authorization.
- Normalize request input before passing it into services when needed.
- Do not trust frontend validation.

## Spatie Activitylog

- Log important business actions.
- Include enough context for auditability:
  - actor
  - affected model
  - action
  - relevant identifiers
  - before and after values where useful
- Use consistent activity names.
- Avoid logging sensitive data unnecessarily.

## Enums

- Use enums for stable business states, movement types, inspection results, document types, and workflow statuses.
- Avoid raw string duplication for domain values.
- Keep enum labels and translations in shared localization files when displayed in the UI.

## Events and Queues

- Use events to announce completed domain actions.
- Use queued jobs for slow or external work.
- Long-running work must not block HTTP requests.
- Dispatch jobs only after required database state is committed.
- Queue handlers must be idempotent where practical.
- Log failures with enough context to reproduce or recover.

## Error Handling

- Throw domain-specific exceptions for expected business rule failures.
- Let validation errors remain validation errors.
- Return user-safe messages.
- Do not leak internal details to users.
- Log unexpected exceptions with context.
- Prefer explicit failure paths over silent no-ops.

## Database Transactions

- Wrap critical multi-write workflows in transactions.
- Keep transaction scope as small as practical.
- Avoid external APIs, file processing, or AI calls inside transactions.
- Include stock movements, production state changes, and audit records in the consistency boundary when required.
- Preserve traceability data during updates.

## Logging

- Use application logs for technical diagnostics.
- Use activity logs for business audit history.
- Log important manufacturing events such as:
  - production order creation
  - operation start and completion
  - material consumption
  - stock movement creation
  - quality inspection
  - scrap recording
  - subassembly transfer
- Include identifiers, not large payloads.

## Performance Recommendations

- Prevent N+1 queries with eager loading.
- Paginate large lists.
- Use indexes for common filters and joins.
- Keep heavy reports and exports queued where practical.
- Cache stable reference data carefully.
- Measure before adding complex optimizations.
- Avoid loading large relationships for table views unless required.
