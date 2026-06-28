# Coding Style Guidance

## Purpose

This document defines coding style, naming, structure, and consistency rules for future development in KM_Production.

Use these rules when adding or changing Laravel, Vue, Inertia, PrimeVue, Tailwind, API, AI/OCR, testing, and documentation code. Follow existing project conventions first, then apply this guidance where the local pattern is not already clear.

## General Principles

- Prefer clarity over cleverness.
- Keep classes focused.
- Keep methods small.
- Use explicit names.
- Avoid duplicated logic.
- Follow existing project conventions.
- Do not introduce new patterns without a clear reason.
- Keep business rules easy to find and test.
- Preserve manufacturing traceability and auditability.

## PHP Style

- Use strict typing where appropriate.
- Use explicit return types.
- Use constructor property promotion where useful.
- Prefer early returns.
- Avoid deeply nested conditions.
- Avoid large methods.
- Avoid static helper sprawl.
- Prefer domain-specific services over generic utility classes.
- Prefer typed collections, DTOs, enums, or value objects when they clarify intent.
- Keep exception paths explicit and meaningful.

## Laravel Structure

Follow the project layer order:

```txt
Controller
-> Service
-> Repository
-> Model
```

Rules:

- Controllers coordinate request handling.
- Services contain business workflows.
- Repositories contain query logic.
- Models contain relationships, casts, scopes, and simple domain helpers only.
- Do not place business logic in controllers.
- Do not place complex queries in controllers.
- Do not mutate inventory directly from controllers.
- Use FormRequests for validation.
- Use policies and permissions for authorization.

## Naming Conventions

Use descriptive names.

Controllers:

- `ItemController`
- `ProductionOrderController`
- `QualityCheckController`

Services:

- `ItemService`
- `ProductionOrderService`
- `StockMovementService`
- `QualityCheckService`

Repositories:

- `ItemRepository`
- `ProductionOrderRepository`
- `StockMovementRepository`

Interfaces:

- `ItemRepositoryInterface`
- `ProductionOrderRepositoryInterface`

FormRequests:

- `StoreItemRequest`
- `UpdateItemRequest`
- `CompleteProductionTaskRequest`

Vue pages:

- `resources/js/Pages/Admin/Items/Index.vue`
- `resources/js/Pages/Admin/Items/Create.vue`
- `resources/js/Pages/Admin/Items/Edit.vue`

Vue components:

- `resources/js/Components/AdminDataTable.vue`
- `resources/js/Components/AdminPerPageSelect.vue`
- `resources/js/Components/ConfirmDeleteDialog.vue`

## Controllers

Controllers may:

- authorize
- validate
- call services
- return Inertia pages
- return JSON responses

Controllers must not:

- contain business workflows
- contain complex query logic
- directly update stock quantities
- directly process AI/OCR results
- contain large conditional workflow branches

## Services

Services own:

- business rules
- workflow orchestration
- transactions
- status transitions
- stock movement orchestration
- document processing orchestration
- AI result validation

Service methods should have intention-revealing names.

Examples:

- `createProductionOrder()`
- `completeProductionTask()`
- `consumeMaterials()`
- `receivePurchaseOrder()`
- `recordQualityInspection()`
- `approveAiExtraction()`

Rules:

- Keep service methods focused on business actions.
- Use transactions for critical multi-step workflows.
- Log important business actions.
- Preserve traceability.

## Repositories

Repositories own:

- query construction
- pagination
- filtering
- sorting
- searching
- reusable query scopes

Rules:

- Repositories should not contain business workflows.
- Use repository interfaces.
- Do not expose unnecessary Eloquent internals to controllers.
- Keep query behavior reusable and predictable.
- Validate allowed filter and sort fields before applying them.

## Models

Models may contain:

- relationships
- casts
- accessors/mutators
- scopes
- simple domain helpers

Models should not contain:

- large workflows
- service orchestration
- controller-specific formatting
- external API calls
- AI/OCR processing

## FormRequests

Use FormRequest classes for validation.

Rules:

- Use authorization in FormRequests only when appropriate.
- Keep validation rules explicit.
- Validate enum values.
- Validate foreign keys.
- Validate quantities and dates.
- Do not rely only on frontend validation.
- Normalize request input where useful before passing data to services.

## Enums

Use enums for stable domain values.

Examples:

- production order status
- task status
- quality result
- stock movement type
- document type

Rules:

- Prefer enums over magic strings.
- Keep enum names descriptive.
- Use enum values consistently in database and UI.
- Add translation keys for user-facing enum labels.
- Avoid introducing enums for values that are user-configurable reference data.

## Transactions

Use transactions for business-critical workflows.

Examples:

- stock movement creation
- production task completion
- material consumption
- goods receiving
- quality inspection
- serial number generation
- AI extraction approval

Rules:

- Transactions belong in services, not controllers.
- Keep transaction scope as small as practical.
- Avoid long-running external work inside transactions.
- Ensure audit and traceability records are consistent with committed business state.

## Activity Logging

Every important business action must be logged.

Rules:

- Use consistent messages and metadata.
- Avoid logging secrets or large raw payloads.
- Include actor, target entity, action, timestamp, and relevant identifiers.
- Include before/after values when useful.
- Use activity logs for business audit history.

## Frontend Style

Use:

- Vue 3 Composition API
- `<script setup>`
- Inertia
- PrimeVue
- Tailwind CSS
- reusable components

Avoid:

- Options API for new components
- jQuery
- duplicated UI logic
- large page components
- business logic inside presentation components

## Vue Component Organization

Pages live under:

```txt
resources/js/Pages/...
```

Shared components live under:

```txt
resources/js/Components/...
```

Admin-specific reusable components may live under:

```txt
resources/js/Components/Admin/...
```

Rules:

- Keep page components focused on composition and data flow.
- Move reusable UI into components.
- Move reusable state and behavior into composables.
- Avoid mixing unrelated workflows in one component.

## Vue Naming

Use PascalCase for component files.

Examples:

- `AdminDataTable.vue`
- `ConfirmDeleteDialog.vue`
- `StatusBadge.vue`
- `TrendIndicator.vue`

Use clear prop names.

Use emitted events with descriptive names.

Examples:

- `update:visible`
- `confirm`
- `cancel`
- `submitted`

## PrimeVue Rules

Use PrimeVue components consistently.

Prefer existing shared components before creating new ones.

Table conventions:

- server-side pagination
- server-side sorting
- server-side filtering
- `removableSort` where appropriate
- consistent empty states
- consistent loading states

Dialog conventions:

- clear title
- visible cancel action
- explicit confirm action
- destructive actions require confirmation

Form conventions:

- show validation errors near fields
- keep submit buttons disabled only when actually submitting
- cancel actions remain available
- use `DatePicker`, not deprecated `Calendar`

## Localization

Frontend must use laravel-vue-i18n with shared Laravel JSON translation files.

Rules:

- In Vue templates, use `$t(...)`.
- In `<script setup>`, use `trans(...)`.
- Do not use `$t(...)` directly inside `<script setup>` logic.
- Do not hardcode user-facing labels.
- Store translation keys in static config arrays.
- Resolve translations at runtime in computed/composable/component layer.
- Backend and frontend must use the same translation keys.

## JavaScript Style

- Prefer `const` over `let` when possible.
- Use `computed` for derived state.
- Use `ref` for mutable local state.
- Use clear function names.
- Avoid large anonymous inline functions.
- Avoid duplicated request logic.
- Debounce search inputs where appropriate.
- Keep API/Inertia calls predictable.
- Keep module-level definitions locale-safe when labels must react to locale changes.

## Inertia Rules

- Use Inertia for admin pages.
- Preserve state where useful.
- Preserve scroll where useful.
- Keep filters in query parameters.
- Avoid full reloads unless necessary.
- Keep backend props minimal and intentional.
- Use server responses as the source of truth for validation and permissions.

## Tables, Filters, and Pagination

Use consistent patterns for admin index pages.

Required behavior:

- global search where appropriate
- server-side pagination
- per-page selector
- sorting
- filters
- reset filters action
- loading state
- empty state

Rules:

- Do not implement one-off table behavior unless justified.
- Keep filter keys consistent with backend expectations.
- Keep pagination state predictable.
- Use shared components or composables where available.

## Error Handling

Backend:

- use validation errors
- throw domain exceptions where useful
- log unexpected exceptions
- return safe messages

Frontend:

- display validation errors clearly
- display safe failure messages
- avoid exposing raw stack traces
- keep failed forms editable

## AI/OCR Coding Rules

AI/OCR code must follow the same layer rules.

Laravel owns:

- validation
- authorization
- workflow
- database
- audit log

Python owns:

- OCR
- image processing
- model inference

Rules:

- Python must not access the application database.
- AI output must be structured JSON.
- AI results must include confidence.
- Laravel validates AI results before saving.
- Low-confidence results require review.
- Long-running AI processing must use queues.
- AI/OCR processing must not bypass Laravel business rules.

## Comments and Documentation

Write comments only when they add value.

Good comments explain why, not what.

Rules:

- Document complex business rules.
- Avoid noisy comments that repeat the code.
- Keep documentation close to stable concepts.
- Update steering documents when architectural guidance changes.

## Testing Style

Use clear test names.

Tests should describe behavior.

Examples:

- `it_creates_stock_movement_when_goods_are_received`
- `it_prevents_task_completion_without_required_materials`
- `it_requires_permission_to_perform_quality_check`

Rules:

- Use factories.
- Avoid brittle tests.
- Test business-critical behavior.
- Add regression tests for fixed defects.
- Keep setup focused on the behavior under test.

## Prohibitions

- Do not put business logic in controllers.
- Do not put complex queries in controllers.
- Do not mutate inventory quantities directly.
- Do not bypass service workflows.
- Do not bypass authorization.
- Do not hardcode user-facing labels.
- Do not duplicate UI logic.
- Do not create oversized Vue components.
- Do not use deprecated PrimeVue components.
- Do not let Python write directly to the Laravel database.
- Do not introduce new architecture patterns without explicit approval.
