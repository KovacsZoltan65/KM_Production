# Testing

## Strategy

- Write tests for business-critical behavior.
- Prefer focused tests that verify rules at the correct layer.
- Add regression tests for fixed defects.
- Keep tests deterministic and independent.
- Use factories and fixtures to express domain setup clearly.

## Pest

- Use Pest for PHP tests.
- Keep test names business-readable.
- Group related tests by feature, service, repository, or domain workflow.
- Use shared Pest helpers only when they reduce duplication without hiding important setup.

## Feature Tests

- Use feature tests for HTTP, Inertia, authorization, validation, and full workflow behavior.
- Verify success paths and important failure paths.
- Assert redirects, validation errors, database changes, activity logs, and permissions where relevant.
- Do not overuse feature tests for pure calculations that belong in unit or service tests.

## Unit Tests

- Use unit tests for isolated business calculations, enums, value objects, and helpers.
- Keep unit tests fast.
- Avoid database access in unit tests unless the test is intentionally integration-like.
- Prefer explicit examples for edge cases.

## Repository Tests

- Test complex queries, filters, sorting, eager loading expectations, and persistence behavior.
- Verify repository methods return expected model sets or paginated results.
- Avoid duplicating Laravel framework tests.
- Use database factories for setup.

## Service Tests

- Test business workflows at the service layer.
- Verify transactions indirectly through consistent persisted state.
- Assert stock movements, serial numbers, operation state changes, activity logs, and emitted events where relevant.
- Include failure scenarios for invalid state transitions and missing prerequisites.

## Authorization Tests

- Test policy behavior for important model and workflow actions.
- Verify Spatie Permission roles and permissions map correctly to allowed actions.
- Keep professional roles separate from authorization roles in test setup.
- Include negative permission checks.

## Required Business Areas

Cover these areas with tests when modified or introduced:

- serial number generation
- stock movement
- material consumption
- operation sequence versioning
- quality control
- subassembly transfer
- permission checks

## Edge Cases

Include edge cases for:

- duplicate serial number attempts
- concurrent or repeated actions
- insufficient stock
- invalid operation order
- obsolete operation sequence versions
- rejected and rework-required quality results
- missing permissions
- invalid document types
- empty or partial inputs

## Regression Tests

- Add regression tests for defects before or with the fix.
- Name tests after the behavior that must not break again.
- Keep regression setup minimal but representative.
- Assert the externally observable behavior, not incidental implementation details.

## Naming Conventions

- Use test names that describe expected behavior.
- Prefer domain language over technical implementation language.
- Use consistent file names aligned with the tested class, feature, or workflow.

## Fixtures and Factories

- Use factories for database models.
- Keep factory defaults valid and realistic.
- Override only values relevant to the test.
- Use fixtures for stable documents or payloads when inline setup would be noisy.
- Avoid shared mutable state between tests.
- Prefer named factory states for common domain scenarios.
