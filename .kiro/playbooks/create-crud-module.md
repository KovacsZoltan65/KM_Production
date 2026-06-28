# Purpose

Create a complete CRUD module using the KM_Production layer order and project conventions.

Use this playbook for business entities that need database persistence, authorization, validation, admin UI, translations, and tests.

# Preconditions

- Read relevant `.kiro/steering/` guidance.
- Read relevant `.kiro/knowledge/` domain documents.
- Check `.kiro/decisions/` for architectural constraints.
- Confirm the entity is needed and belongs to the requested domain.
- Confirm permissions, user roles, and business-critical side effects.
- Confirm whether the module affects inventory, production, quality, documents, or AI workflows.

# Implementation Steps

1. Create the migration.
   - Define columns using domain names.
   - Add foreign keys where appropriate.
   - Avoid nullable foreign keys unless there is a clear business reason.
   - Preserve traceability fields where required.

2. Create the model.
   - Define relationships.
   - Add casts.
   - Add scopes only for reusable model-level concepts.
   - Avoid large workflows in the model.

3. Create the factory.
   - Keep defaults valid and realistic.
   - Add named states for common business scenarios.

4. Create the seeder if needed.
   - Seed reference data only when useful.
   - Avoid seeding unstable workflow data unless required.

5. Create enums if required.
   - Use enums for stable domain states and types.
   - Add translation keys for user-facing enum labels.

6. Create the repository interface.
   - Define query and persistence operations needed by services and controllers.
   - Keep signatures explicit.

7. Create the repository.
   - Implement pagination, filtering, sorting, and searching.
   - Keep query construction out of controllers.
   - Do not place business workflows in repositories.

8. Create the service.
   - Place business logic and workflow orchestration here.
   - Use transactions for critical workflows.
   - Call repositories.
   - Log important business actions.

9. Create the policy.
   - Map CRUD actions to explicit permissions.
   - Add special permissions such as `check` when needed.
   - Keep professional roles separate from authorization roles.

10. Create FormRequests.
    - Use separate store and update requests where useful.
    - Validate enum values, foreign keys, dates, quantities, and ownership rules.
    - Do not rely only on frontend validation.

11. Create the controller.
    - Authorize.
    - Validate.
    - Call services.
    - Return Inertia pages, redirects, or JSON responses.
    - Do not add business logic or complex queries.

12. Add routes.
    - Follow project route organization.
    - Protect routes with authentication and relevant middleware.
    - Use resource routes where appropriate.

13. Add permissions.
    - Register explicit CRUD permissions.
    - Add special permissions for workflow actions.
    - Update seeders or permission registries as required by the project pattern.

14. Add translations.
    - Use shared Laravel JSON translation files.
    - Use the same keys in backend and frontend.
    - Do not hardcode user-facing labels.

15. Create Vue pages.
    - Use `resources/js/Pages/...`.
    - Use Vue 3 Composition API, Inertia, PrimeVue, and Tailwind.
    - Resolve translations at runtime.

16. Create shared components if needed.
    - Reuse existing components first.
    - Extract repeated table, form, dialog, or status UI.

17. Add tests.
    - Feature tests for HTTP behavior.
    - Service tests for business workflows.
    - Repository tests for complex queries.
    - Authorization tests for permission boundaries.

18. Update documentation.
    - Update steering, knowledge, decisions, or playbooks only when project guidance changes.

# Validation Checklist

- [ ] Controller contains no business logic.
- [ ] Controller contains no complex query logic.
- [ ] Service owns workflow decisions.
- [ ] Repository owns query construction.
- [ ] Policy protects read and modifying actions.
- [ ] FormRequests validate all request input.
- [ ] Translations are shared and not hardcoded.
- [ ] Business actions are logged.
- [ ] Transactions wrap critical workflows.
- [ ] Inventory is changed only through stock movements.

# Testing Checklist

- [ ] Authorized users can access allowed actions.
- [ ] Unauthorized users are denied.
- [ ] Validation failures return expected errors.
- [ ] Create, update, view, and delete flows work.
- [ ] Pagination, filtering, sorting, and search work.
- [ ] Business-critical side effects are asserted.
- [ ] Activity logs are created when required.
- [ ] Regression tests cover fixed defects.

# Common Mistakes

- Placing business logic in controllers.
- Querying directly in controllers.
- Mutating stock quantities directly.
- Skipping policy checks because buttons are hidden in Vue.
- Hardcoding user-facing labels.
- Adding a new UI pattern instead of reusing shared components.
- Forgetting activity logs for important business actions.
- Forgetting translations for enum labels.

# Completion Criteria

- The module follows `Controller -> Service -> Repository -> Model`.
- Permissions, validation, translations, UI, and tests are complete.
- Business-critical behavior is auditable.
- Documentation remains consistent with steering, knowledge, and ADR guidance.
