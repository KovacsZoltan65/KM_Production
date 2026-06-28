# Security Guidance

## Purpose

This document defines security rules for future development in KM_Production.

Use these rules when implementing authentication, authorization, APIs, production workflows, inventory changes, document uploads, AI/OCR processing, reporting, and administrative features. Security decisions must preserve manufacturing traceability and auditability.

## Core Security Principles

- Security must be enforced on the backend.
- Frontend visibility is never authorization.
- Business-critical actions must be auditable.
- Traceability must not be broken.
- Direct stock mutation is forbidden.
- Uploaded files are untrusted.
- AI/OCR outputs are untrusted until validated.
- Users must only access data and actions they are authorized to use.
- Security-sensitive decisions must not depend on client-side state.

## Authentication

- Use Laravel authentication.
- Protect admin routes.
- Do not bypass middleware.
- Do not expose sensitive user data.
- Do not log passwords, tokens, session IDs, API keys, or personal secrets.
- Keep authentication state server-controlled.
- Use existing authentication patterns before introducing new mechanisms.

## Authorization

Use:

- Policies
- Gates where appropriate
- Spatie Permission
- Role/permission checks

Rules:

- Every modifying action must be authorized.
- Every read action for restricted data must be authorized.
- Do not rely on Vue-side hiding.
- Do not mix professional roles with authorization roles.
- Professional roles describe work capability, not system access.
- Authorization must happen before business actions.
- Authorization must be enforced for HTTP, API, queued, and service-triggered workflows where applicable.

## Permission Rules

Permissions should remain explicit and domain-oriented.

Examples:

- `items.view`
- `items.create`
- `items.update`
- `items.delete`
- `production-orders.view`
- `production-orders.update`
- `quality-checks.check`
- `intelligence.view`
- `intelligence.recommendations`

Rules:

- Use clear permission names.
- Avoid broad implicit access.
- Keep permission checks close to the action boundary.
- Keep authorization roles separate from professional employee roles.
- Add new permissions deliberately when introducing new protected actions.

## Audit Logging

Every important business action must be logged.

Examples:

- production order created
- operation started
- operation completed
- material consumed
- stock movement created
- goods received
- quality inspection performed
- scrap recorded
- document uploaded
- document classified by AI
- AI extraction reviewed
- permission changed

Audit logs should include:

- actor
- action
- target entity
- timestamp
- relevant metadata
- before/after values when useful

Rules:

- Use activity logs for business audit history.
- Use application logs for technical diagnostics.
- Do not log sensitive secrets or raw sensitive documents.
- Keep audit records connected to the business action that caused them.

## Inventory Security

Inventory quantities must never be edited directly.

All inventory changes must go through stock movement workflows.

Critical workflows must use transactions:

- purchase receiving
- production issue
- production consume
- production output
- transfer
- scrap
- correction

Rules:

- Stock movement records are the source of inventory change history.
- Every stock change must be authorized.
- Every stock change must be auditable.
- Corrections must include a clear business reason.
- Prevent duplicate or inconsistent stock movements during retries.

## Traceability

Do not delete or overwrite traceability-critical records casually.

Traceability-critical entities include:

- production orders
- production tasks
- stock movements
- item instances
- serial numbers
- quality checks
- documents
- audit logs

Prefer status changes or soft deletes where business history must remain visible.

Rules:

- Preserve historical links between production orders, operations, materials, serial numbers, stock movements, quality checks, and documents.
- Do not rewrite history to hide operational mistakes.
- Keep original operation sequence versions connected to existing production orders.
- Permanent deletion must be explicit, justified, and safe for business history.

## Database Safety

- Use migrations for schema changes.
- Use foreign keys where appropriate.
- Avoid nullable foreign keys unless there is a clear business reason.
- Use transactions for multi-step workflows.
- Avoid raw SQL unless justified.
- Never concatenate user input into SQL.
- Validate and sanitize input.
- Preserve referential integrity.
- Avoid schema shortcuts that weaken auditability or traceability.

## Validation

Use FormRequest validation.

Validate:

- required fields
- enum values
- foreign keys
- ownership/access rules
- file uploads
- quantities
- dates
- status transitions

Rules:

- Do not rely only on frontend validation.
- Validate server-side for every request.
- Keep business state validation in services when it depends on workflow state.
- Validate query parameters, filters, sorting, and pagination.
- Reject invalid data before business workflows run.

## File Upload Security

Uploaded files are untrusted.

Rules:

- validate MIME type
- validate size
- do not trust file extension
- store files outside public paths unless intentionally public
- generate safe server-side filenames
- keep original filename only as metadata
- avoid executing uploaded files
- scan or sanitize files where possible
- restrict previews to safe MIME types

Supported document uploads should attach only to allowed entities:

- items
- operation sequences
- production orders
- operations
- quality inspections

Additional rules:

- Store metadata needed for auditability.
- Validate user permission to attach files to the target entity.
- Treat parsed file contents as untrusted input.
- Do not expose uploaded files publicly unless the business workflow explicitly allows it.

## AI/OCR Security

AI/OCR processing must treat uploads and outputs as untrusted.

Rules:

- AI services must not directly write to the database.
- Python workers must not access the application database.
- AI returns structured JSON only.
- Laravel validates AI output before saving.
- Low-confidence results require human review.
- Long-running AI/OCR tasks must run in queues.
- Never run arbitrary code from uploaded files.
- Limit runtime, memory, and file size.
- Log AI execution results and failures.
- Store confidence scores and review status.

Additional rules:

- AI must not bypass permissions, workflows, validation, audit logging, or traceability rules.
- Treat AI extraction results like external input.
- Require human review for ambiguous, conflicting, low-confidence, or business-critical outputs.
- Do not send sensitive documents to external AI providers unless approved by business policy.

## Frontend Security

- Do not expose secrets in Vue props.
- Do not rely on hidden buttons for authorization.
- Use server-provided permissions only for UX.
- Escape/display user content safely.
- Avoid rendering raw HTML unless sanitized.
- Keep destructive actions behind confirmation dialogs.
- Do not expose internal-only identifiers unless needed.
- Do not store sensitive tokens or secrets in frontend state.

## API Security

- Validate every request.
- Authorize every protected endpoint.
- Use consistent error responses.
- Do not expose stack traces.
- Use proper HTTP status codes.
- Rate-limit sensitive endpoints where appropriate.
- Never expose internal model structure accidentally.
- Keep API response shapes stable.
- Treat external integrations as untrusted input.

## Secrets and Configuration

- Secrets must live in `.env`.
- Do not commit `.env`.
- Do not hardcode credentials.
- Do not log secrets.
- Provide only safe example values in documentation.
- Keep API keys, tokens, database passwords, SMTP credentials, and OAuth secrets private.
- Use environment-specific configuration for local, staging, and production environments.
- Rotate exposed secrets immediately.

## Logging Rules

Logs must help debugging without leaking secrets.

Do not log:

- passwords
- tokens
- API keys
- session IDs
- raw uploaded sensitive documents
- full personal identifiers unless required

Do log:

- action type
- actor ID
- entity ID
- failure reason
- correlation/request ID if available

Rules:

- Keep user-facing messages safe.
- Keep internal diagnostic context useful.
- Avoid logging full request payloads for sensitive workflows.
- Use audit logs for business actions and application logs for technical failures.

## Error Handling

- Show safe messages to users.
- Log detailed internal errors.
- Do not expose stack traces in production.
- Handle authorization failures clearly.
- Handle validation failures consistently.
- Handle AI/OCR failures without corrupting business state.
- Roll back failed critical transactions.
- Avoid partial writes in business-critical workflows.

## Soft Delete and Deletion Rules

- Do not hard-delete traceability-critical data unless explicitly required.
- Prefer soft delete or status changes for business records.
- Ensure related records remain consistent.
- Prevent orphaned records.
- Document any permanent deletion behavior clearly.
- Restrict permanent deletion to authorized users and audited workflows.
- Preserve audit logs even when related business records are hidden or archived.

## Security Testing

Security-relevant behavior must be tested.

Include tests for:

- unauthorized access
- forbidden actions
- validation failures
- permission boundaries
- stock movement integrity
- file upload validation
- AI result validation
- deletion rules
- audit logging

Rules:

- Test both allowed and denied paths.
- Verify frontend visibility is not the only protection.
- Assert critical side effects are blocked when authorization fails.
- Add regression tests for security fixes.

## Prohibitions

- Do not bypass authorization.
- Do not trust frontend-only checks.
- Do not directly mutate stock quantities.
- Do not allow AI to write business data directly.
- Do not execute uploaded files.
- Do not log secrets.
- Do not expose stack traces.
- Do not mix professional roles with authorization roles.
- Do not hard-delete traceability records casually.
- Do not place security-sensitive logic only in Vue components.
