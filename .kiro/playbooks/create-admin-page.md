# Purpose

Create a standard admin page for listing, filtering, sorting, and managing records in KM_Production.

# Preconditions

- Confirm the page belongs under `resources/js/Pages/Admin/...`.
- Confirm the backend route, controller, service, repository, and policy exist or are part of the task.
- Confirm required permissions.
- Confirm translation keys.
- Check for existing shared admin components before creating new UI.

# Implementation Steps

1. Define the page route and controller action.
   - Authorize access.
   - Use repository-backed query data.
   - Return an Inertia page with minimal, intentional props.

2. Implement server-side pagination.
   - Validate `page` and `per_page`.
   - Use consistent pagination metadata.
   - Avoid unbounded dataset loading.

3. Implement sorting.
   - Accept allowed sort fields only.
   - Support ascending and descending order.
   - Keep sorting in the repository.

4. Implement filtering.
   - Use predictable query parameters.
   - Validate filter values.
   - Keep filters in query parameters.
   - Keep filtering in the repository.

5. Implement global search where appropriate.
   - Debounce frontend search input.
   - Keep search in the repository.
   - Define searchable fields explicitly.

6. Build the Vue page.
   - Use Vue 3 Composition API and `<script setup>`.
   - Use Inertia for navigation and form actions.
   - Use PrimeVue DataTable.
   - Use `AdminPerPageSelect` when available.
   - Use reusable components for repeated UI.

7. Add loading and empty states.
   - Show clear loading feedback during navigation.
   - Show a localized empty state when no records match.

8. Add row actions.
   - Check permissions from server-provided props.
   - Keep hidden buttons as UX only, not authorization.
   - Use confirmation dialogs for destructive actions.

9. Add localization.
   - Use `$t(...)` in templates.
   - Use `trans(...)` in `<script setup>`.
   - Store keys in static metadata and resolve at runtime.

10. Add tests.
    - Test authorization.
    - Test pagination, sorting, filtering, and search.
    - Test destructive action confirmation behavior where practical.

# Validation Checklist

- [ ] Uses server-side pagination.
- [ ] Uses server-side sorting.
- [ ] Uses server-side filtering.
- [ ] Global search is debounced when present.
- [ ] Loading state is visible.
- [ ] Empty state is localized.
- [ ] Row actions are permission-aware.
- [ ] Destructive actions use confirmation dialogs.
- [ ] Labels are localized.
- [ ] Inertia state and query parameters behave predictably.

# Testing Checklist

- [ ] Page requires authentication.
- [ ] Restricted users cannot access protected records.
- [ ] Pagination returns correct records.
- [ ] Sorting works for allowed fields.
- [ ] Filtering works for allowed filters.
- [ ] Search works for defined fields.
- [ ] Invalid filters or sort fields are rejected or ignored safely.

# Common Mistakes

- Loading all records on the client.
- Implementing filters only in Vue.
- Hardcoding labels.
- Treating hidden buttons as authorization.
- Duplicating table logic instead of using shared components.
- Forgetting empty and loading states.
- Adding unsupported sort fields.

# Completion Criteria

- The admin page follows existing KM_Production UI patterns.
- The page is localized, permission-aware, and server-driven.
- Query behavior is implemented in repositories.
- Tests cover critical page behavior.
