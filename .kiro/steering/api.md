# API Guidance

## Purpose

This document defines API design rules for future internal and external APIs in KM_Production.

Use these rules when adding JSON APIs for manufacturing, inventory, quality, procurement, documentation, AI processing, integrations, or operational reporting. APIs must preserve the project architecture and business rules defined in the other steering documents.

## API Style

- Prefer RESTful APIs.
- Use JSON only.
- Keep endpoints predictable.
- Use plural resource names.
- Use consistent naming.
- Avoid exposing database implementation details.
- Design endpoints around domain resources and workflows.
- Keep response shapes consistent across endpoints.
- Do not leak internal model relationships unless they are intentionally part of the contract.

## URL Conventions

Use predictable resource URLs:

```txt
GET /api/items
GET /api/items/{item}
POST /api/items
PUT /api/items/{item}
DELETE /api/items/{item}
```

Use nested URLs when the child resource or action belongs to a parent workflow:

```txt
GET /api/production-orders/{productionOrder}/tasks
POST /api/production-orders/{productionOrder}/tasks/{task}/complete
```

Rules:

- Use plural nouns for resources.
- Use route model identifiers consistently.
- Use action suffixes only for workflow commands that do not map cleanly to standard CRUD.
- Avoid deeply nested URLs when query parameters or a dedicated resource endpoint would be clearer.

## Response Format

Standard success response:

```json
{
  "success": true,
  "message": "Operation completed successfully.",
  "data": {}
}
```

Standard error response:

```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {}
}
```

Rules:

- Keep response shapes consistent.
- Return user-safe messages.
- Include domain data under `data`.
- Include validation and field-specific errors under `errors`.
- Do not expose stack traces, SQL, internal class names, or sensitive implementation details.

## Pagination

Use server-side pagination for large datasets.

Standard paginated response:

```json
{
  "success": true,
  "message": "Items retrieved successfully.",
  "data": [],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 15,
    "to": 15,
    "total": 75
  },
  "links": {
    "first": "https://example.test/api/items?page=1",
    "last": "https://example.test/api/items?page=5",
    "prev": null,
    "next": "https://example.test/api/items?page=2"
  }
}
```

Rules:

- Use `data`, `meta`, and `links` for paginated collections.
- Validate `per_page` limits.
- Avoid returning unbounded collections for operational data.
- Keep pagination behavior consistent across list endpoints.

## Filtering

Use structured filter query parameters:

```txt
?filter[status]=active
?filter[item_type]=material
?filter[customer_id]=1
```

Rules:

- Do not use inconsistent ad-hoc parameters.
- Validate filter keys and values.
- Only support documented filters.
- Implement filtering in repositories, not controllers.
- Preserve domain naming consistency.

## Sorting

Use a single `sort` query parameter:

```txt
?sort=name
?sort=-created_at
```

Rules:

- A leading minus means descending order.
- No leading minus means ascending order.
- Only explicitly allowed fields may be sortable.
- Validate sort fields before applying them.
- Implement sorting in repositories, not controllers.

## Searching

Use a `search` query parameter:

```txt
?search=steel
```

Rules:

- Search must be implemented in repositories, not controllers.
- Define which fields are searchable for each endpoint.
- Keep search behavior predictable.
- Use database indexes or dedicated search infrastructure when scale requires it.
- Avoid broad unbounded searches on large operational datasets.

## Validation

- Use FormRequest classes.
- Keep validation errors consistent.
- Do not validate business rules only in the frontend.
- Validate query parameters, filters, sorting, pagination, and payloads.
- Normalize input before passing it to services when needed.
- Use Laravel validation as the source of truth.
- Business rule validation belongs in services when it depends on workflow state.

## Authorization

- Use policies and permissions.
- Never rely only on frontend visibility.
- Every modifying endpoint must enforce authorization.
- Authorization must happen before business actions.
- Use Spatie Permission for authorization roles and permissions.
- Keep professional roles separate from authorization roles.
- Do not expose records a user is not authorized to view.

## Transactions

Use database transactions for critical workflows, especially:

- stock movements
- production task completion
- material consumption
- quality inspection
- purchase receiving
- serial number generation

Rules:

- Transaction boundaries belong in services for business workflows.
- Keep transactions as small as practical.
- Do not perform long-running external work inside transactions.
- Preserve traceability and audit records when committing state changes.

## Audit Logging

Every important API action must be auditable.

Examples:

- created production order
- completed task
- consumed material
- received goods
- performed quality inspection
- modified stock

Rules:

- Log actor, action, affected entity, timestamp, and relevant identifiers.
- Include before and after values where useful.
- Do not log sensitive payloads unnecessarily.
- Use activity logs for business audit history and application logs for technical diagnostics.

## Error Handling

- Do not expose stack traces.
- Use meaningful messages.
- Log unexpected exceptions.
- Return proper HTTP status codes.
- Keep error response shapes consistent.

Status code examples:

- `200 OK`
- `201 Created`
- `204 No Content`
- `400 Bad Request`
- `401 Unauthorized`
- `403 Forbidden`
- `404 Not Found`
- `422 Validation Error`
- `500 Internal Server Error`

## Idempotency

Critical operations should be designed with idempotency in mind where appropriate.

Examples:

- external integrations
- receiving goods
- AI processing callbacks
- payment-like operations if added later

Rules:

- Prefer idempotency keys for external callbacks and integration writes.
- Avoid duplicate stock movements from repeated requests.
- Make retry behavior explicit for queued or integration-driven workflows.
- Log duplicate or ignored requests when useful for auditability.

## File Upload APIs

Rules for uploaded files:

- Validate MIME type.
- Validate size.
- Never trust file extension.
- Store metadata.
- Attach documents to supported entities.
- Scan or sanitize files where possible.
- Store files in controlled locations.
- Treat extracted file contents as untrusted input.
- Preserve audit context for uploaded business documents.

Supported document attachment targets must follow the documentation domain rules for items, operation sequences, production orders, operations, and quality inspections.

## AI API Integration

Rules for AI-related API endpoints:

- AI endpoints must not directly modify business data without Laravel validation.
- AI outputs must be reviewed when confidence is low.
- AI services return structured JSON.
- AI processing should use queues.
- Long-running AI tasks must not block HTTP requests.
- AI results must include confidence.
- Laravel owns final validation, permissions, workflow decisions, database writes, and audit logging.
- Python or external AI workers must not access the database directly.

## Versioning

Public APIs should use versioning when needed:

```txt
/api/v1/...
```

Rules:

- Use versioning for APIs exposed to external clients or stable integrations.
- Internal Inertia endpoints do not need public API versioning unless exposed externally.
- Avoid breaking public API contracts without a versioning or migration strategy.
- Document version-specific behavior.

## Naming Rules

- Use camelCase in frontend payloads only if the existing frontend convention requires it.
- Use snake_case for database fields and backend arrays unless the project establishes a transformer layer.
- Keep naming consistent within each endpoint.
- Do not mix naming conventions in the same response.
- Prefer domain names over database table or column implementation names when exposing public contracts.

## Controller Rules

Controllers may:

- authorize
- validate
- call services
- return responses

Controllers must not contain:

- business logic
- complex queries
- stock mutation logic
- AI processing logic

## Repository Rules

Repositories own query construction:

- pagination
- filtering
- sorting
- search

Rules:

- Keep query logic out of controllers.
- Validate allowed query fields before applying repository filters or sorts.
- Keep reusable query behavior consistent across endpoints.
- Do not place workflow decisions in repositories.

## Service Rules

Services own business workflows:

- production transitions
- inventory movement creation
- quality decision logic
- document processing orchestration
- AI result validation

Rules:

- Services enforce domain rules.
- Services coordinate transactions, repositories, events, queues, and audit logging.
- Services must preserve traceability.
- Services must not bypass inventory movement or serial number rules.

## Testing Requirements

API behavior must be tested for:

- authorization
- validation
- success response
- error response
- pagination
- filtering
- sorting
- business-critical side effects

Rules:

- Use feature tests for API request and response behavior.
- Assert status codes and response shapes.
- Assert database side effects for business workflows.
- Assert that unauthorized users cannot view or modify protected resources.
- Add regression tests for fixed API bugs.

## Prohibitions

- Do not expose internal model structure accidentally.
- Do not bypass policies.
- Do not mutate stock directly.
- Do not return inconsistent response shapes.
- Do not run long AI/OCR processing inside HTTP request lifecycle.
- Do not put query logic in controllers.
