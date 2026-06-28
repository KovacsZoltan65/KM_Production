# Purpose

Create a service class that owns business logic, workflow orchestration, transactions, and activity logging.

# Preconditions

- Confirm the business workflow belongs in a service.
- Identify repository interfaces needed by the service.
- Identify authorization and validation boundaries.
- Identify required transactions and audit logs.
- Review relevant ADRs for inventory, production, quality, serial numbers, or AI.

# Implementation Steps

1. Define the service responsibility.
   - Name it after the domain concept.
   - Keep the class focused.
   - Avoid generic utility naming.

2. Inject repository interfaces and collaborators.
   - Depend on interfaces where the project pattern expects it.
   - Avoid direct controller query logic.

3. Create intention-revealing public methods.
   - Examples: `completeProductionTask()`, `consumeMaterials()`, `recordQualityInspection()`.
   - Keep method names aligned with business actions.

4. Put business rules in the service.
   - Validate workflow state.
   - Enforce status transitions.
   - Coordinate domain actions.

5. Use transactions for critical workflows.
   - Include related writes in the same transaction.
   - Avoid external calls inside transactions.

6. Call repositories for data access.
   - Keep complex queries in repositories.
   - Keep persistence details out of controllers.

7. Add activity logging.
   - Log important business actions.
   - Include actor, target entity, action, and relevant metadata.
   - Avoid secrets or large raw payloads.

8. Coordinate events, queues, or notifications where needed.
   - Dispatch slow work to queues.
   - Dispatch after committed state is available.

9. Return explicit values.
   - Return models, DTOs, collections, booleans, or typed arrays as appropriate.
   - Use explicit return types.

# Validation Checklist

- [ ] Service owns business logic.
- [ ] Service uses repository interfaces.
- [ ] Critical workflows use transactions.
- [ ] Important actions are logged.
- [ ] No controller contains duplicate workflow logic.
- [ ] External work is not performed inside transactions.
- [ ] Inventory workflows use stock movements.

# Testing Checklist

- [ ] Success path is tested.
- [ ] Invalid workflow state is tested.
- [ ] Transactional side effects are asserted.
- [ ] Activity logging is asserted where important.
- [ ] Authorization is covered at the request or policy layer.
- [ ] Edge cases are covered for business-critical rules.

# Common Mistakes

- Moving validation entirely out of FormRequests into services.
- Querying directly from controllers instead of calling services.
- Putting repository query details in the service.
- Forgetting transaction boundaries.
- Forgetting activity logging.
- Making one service own unrelated domains.

# Completion Criteria

- The service clearly represents a business workflow.
- It coordinates repositories and domain collaborators.
- It is tested for critical behavior.
- Controllers remain thin.
