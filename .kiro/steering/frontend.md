# Frontend

## Stack

- Vue 3 Composition API
- Inertia.js
- PrimeVue 4
- Tailwind CSS 4
- Vite
- laravel-vue-i18n

## Component Organization

- Page components live under `resources/js/Pages/...`.
- Shared components live under `resources/js/Components/...`.
- Keep page components responsible for page composition and data flow.
- Move reusable UI behavior into shared components or composables.
- Avoid oversized components with mixed responsibilities.
- Do not duplicate workflow logic across pages.

## Vue Composition API

- Use `<script setup>` for Vue components.
- Prefer `computed`, `ref`, and composables for reactive state.
- Keep derived values as computed state.
- Avoid translating or formatting reactive labels at module import time.
- Keep side effects in explicit handlers or lifecycle hooks.

## Inertia

- Use Inertia props as the page data boundary.
- Use Inertia form helpers for form submissions where appropriate.
- Preserve scroll and state intentionally.
- Handle validation errors from Laravel responses.
- Avoid direct API calls when an existing Inertia workflow fits the feature.

## PrimeVue

- Use PrimeVue components for tables, forms, dialogs, menus, buttons, toasts, and overlays.
- Prefer existing project wrappers and shared components when available.
- Keep PrimeVue state such as filters, sort fields, pagination, and selections predictable.
- Use accessible labels and clear actions.

## Tailwind

- Use Tailwind utilities for layout and spacing.
- Match existing project design patterns.
- Avoid one-off visual systems unless requested.
- Keep responsive behavior stable on desktop and mobile.

## Localization

Frontend must use `laravel-vue-i18n` with the shared Laravel JSON translation files.

Use the same translation keys in backend and frontend.

In Vue templates:

```vue
{{ $t('employees.title') }}
```

In `<script setup>`:

```js
import { trans } from 'laravel-vue-i18n'

const label = trans('employees.title')
```

Rules:

- Use `$t(...)` in templates.
- Use `trans(...)` in `<script setup>` logic.
- Do not use `$t(...)` directly inside `<script setup>`.
- Store translation keys in static definitions.
- Resolve translation keys at runtime in computed values, composables, or components.
- Do not eagerly translate module-level menu definitions, action definitions, column metadata, or static config arrays when values must react to locale changes.

## State Management

- Keep local UI state inside the component when it is not shared.
- Use composables for reusable state behavior.
- Keep server-owned state in Laravel and Inertia props.
- Avoid duplicating source-of-truth state between client and server.

## Tables

- Use shared table conventions where available.
- Support pagination for large datasets.
- Keep filters and sorting explicit.
- Translate column labels through shared keys.
- Keep row actions predictable and permission-aware.
- Avoid loading unnecessary relationship data for table views.

## Dialogs

- Use dialogs for focused create, edit, confirm, and inspect flows.
- Reset dialog state when opening or closing.
- Show validation errors close to the relevant fields.
- Keep destructive actions behind confirmation.
- Do not hide critical workflow decisions in generic dialogs.

## Forms

- Use Laravel validation as the source of truth.
- Reflect server-side validation errors in the UI.
- Disable submit actions while processing.
- Use clear success and error feedback.
- Keep field labels, placeholders, and validation text localized.
- Use reusable field components where the project already provides them.

## Pagination, Filtering, and Sorting

- Prefer server-side pagination for large datasets.
- Keep filter state shareable through query parameters when useful.
- Preserve current filters after actions when practical.
- Validate filter and sorting input on the backend.
- Avoid client-only filtering for business-critical lists unless the full dataset is intentionally loaded.

## Code Style

- Prefer clear names over comments.
- Keep components small enough to review.
- Avoid jQuery.
- Avoid direct DOM manipulation unless a component integration requires it.
- Keep business rules out of presentation components.
- Match existing import ordering and formatting.
