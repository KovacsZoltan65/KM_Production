# Purpose

Mandatory checklist for adding a new module to KM_Production.

# Checklist

- [ ] Migration created and reviewed.
- [ ] Model created.
- [ ] Factory created.
- [ ] Seeder created if needed.
- [ ] Enum created if required.
- [ ] Repository Interface created.
- [ ] Repository created.
- [ ] Service created.
- [ ] Policy created.
- [ ] Requests created.
- [ ] Controller created.
- [ ] Routes added.
- [ ] Permissions added.
- [ ] Translations added.
- [ ] Vue Pages created.
- [ ] Tests added.
- [ ] Documentation updated.
- [ ] Activity Log added for important actions.
- [ ] Controller -> Service -> Repository -> Model followed.

# Common Mistakes

- Creating routes and pages before defining authorization.
- Skipping repository interface.
- Putting query logic in controllers.
- Forgetting factories for tests.
- Missing activity logs for business actions.

# Completion Criteria

- Module is complete across backend, frontend, permissions, tests, and documentation.
- Business-critical behavior is authorized, validated, logged, and tested.
