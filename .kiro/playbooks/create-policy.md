# Purpose

Create a policy that protects model and workflow actions with explicit permissions.

# Preconditions

- Identify the model or domain action being protected.
- Identify required CRUD permissions.
- Identify any special permissions such as `check`.
- Confirm professional roles are not being used as authorization roles.
- Confirm how permissions are registered in the project.

# Implementation Steps

1. Create the policy class.
   - Follow Laravel policy conventions.
   - Use clear method names such as `viewAny`, `view`, `create`, `update`, `delete`.

2. Map CRUD actions to permissions.
   - Examples: `items.view`, `items.create`, `items.update`, `items.delete`.
   - Keep permission names explicit and domain-oriented.

3. Add special permission methods where needed.
   - Examples: `check`, `approve`, `complete`, `export`.
   - Use special permissions only for distinct protected actions.

4. Register the policy if required.
   - Follow the existing project pattern.

5. Use the policy inside controllers.
   - Authorize before calling services.
   - Protect read and modifying actions.

6. Add permissions to the project permission registry or seeder.
   - Use existing permission naming conventions.
   - Keep permission roles separate from professional roles.

7. Add authorization tests.
   - Test allowed users.
   - Test forbidden users.
   - Test special permissions.

# Validation Checklist

- [ ] Policy exists and is registered.
- [ ] CRUD permissions are explicit.
- [ ] Special permissions are explicit.
- [ ] Controllers call authorization.
- [ ] Frontend visibility is not the only protection.
- [ ] Professional roles are not used as authorization roles.

# Testing Checklist

- [ ] User with permission can perform action.
- [ ] User without permission is forbidden.
- [ ] Restricted read actions are protected.
- [ ] Modifying actions are protected.
- [ ] Special permissions are covered.

# Common Mistakes

- Hiding buttons in Vue without backend authorization.
- Using professional roles like welder or inspector as permission roles.
- Forgetting `viewAny` or `view` protection.
- Adding broad admin checks instead of explicit permissions.
- Forgetting tests for denied paths.

# Completion Criteria

- Authorization is enforced on the backend.
- Permission names are explicit and consistent.
- Tests prove permission boundaries.
