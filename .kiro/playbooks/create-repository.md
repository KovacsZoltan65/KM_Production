# Purpose

Create a repository and repository interface for data access, query construction, pagination, filtering, sorting, and search.

# Preconditions

- Confirm the model and domain concept exist.
- Confirm the service or controller query needs.
- Identify allowed filters, sort fields, and searchable fields.
- Check existing repository patterns.

# Implementation Steps

1. Create the repository interface.
   - Define explicit methods.
   - Return predictable types.
   - Avoid exposing unnecessary Eloquent internals.

2. Create the repository implementation.
   - Follow existing project and Prettus Repository patterns.
   - Use the correct model.

3. Add pagination methods.
   - Support `per_page` safely.
   - Avoid unbounded list queries for large datasets.

4. Add filtering.
   - Support only documented filters.
   - Validate or whitelist filter keys.
   - Use domain names consistently.

5. Add sorting.
   - Support only allowed fields.
   - Support descending order where needed.
   - Avoid arbitrary column sorting from user input.

6. Add searching.
   - Define searchable fields explicitly.
   - Keep broad search performance in mind.

7. Add reusable query methods.
   - Keep methods focused.
   - Prefer clear names over generic catch-all filters.

8. Bind the interface.
   - Register interface to implementation using the project pattern.

9. Add tests for complex queries.
   - Use factories.
   - Assert expected records and ordering.

# Validation Checklist

- [ ] Repository interface exists.
- [ ] Repository implementation matches the interface.
- [ ] Pagination is server-side.
- [ ] Filters are explicit.
- [ ] Sort fields are whitelisted.
- [ ] Search fields are explicit.
- [ ] No business workflows are in the repository.

# Testing Checklist

- [ ] Pagination returns correct data.
- [ ] Filters include and exclude expected records.
- [ ] Sorting works for allowed fields.
- [ ] Search works for expected fields.
- [ ] Invalid filters or sorts are handled safely.

# Common Mistakes

- Putting business decisions in repositories.
- Allowing arbitrary user-provided sort columns.
- Duplicating query logic across controllers.
- Returning inconsistent types.
- Hiding workflow side effects inside data access methods.

# Completion Criteria

- Query logic is reusable and predictable.
- Services and controllers do not build complex queries directly.
- Repository behavior is covered by focused tests when complex.
