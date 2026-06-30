# Purpose

Mandatory checklist before committing work in KM_Production.

# Checklist

## Architecture

- [ ] Controller contains no business logic.
- [ ] Service created where business workflow exists.
- [ ] Repository used for query logic.
- [ ] Policy exists for protected actions.
- [ ] FormRequest exists for request validation.
- [ ] Transactions added where required.
- [ ] Activity logging added for important business actions.

## Database

- [ ] Migration reviewed.
- [ ] Foreign keys correct.
- [ ] Indexes added where needed.
- [ ] Soft deletes reviewed.
- [ ] No destructive migration mistakes.

## Frontend

- [ ] Localization complete.
- [ ] PrimeVue conventions followed.
- [ ] Shared components reused.
- [ ] Loading state implemented.
- [ ] Empty state implemented.
- [ ] Validation errors shown.

## Permissions

- [ ] Permission created.
- [ ] Policy updated.
- [ ] Menu visibility reviewed.
- [ ] Backend authorization enforced.

## Testing

- [ ] Pest tests added.
- [ ] Authorization tested.
- [ ] Validation tested.
- [ ] Happy path tested.
- [ ] Failure path tested.

## Code Quality

- [ ] No TODO.
- [ ] No FIXME.
- [ ] No debug code.
- [ ] No `dd()`.
- [ ] No `dump()`.
- [ ] No `console.log()`.
- [ ] No commented dead code.
- [ ] Pint passes.
- [ ] PHPStan/Larastan passes.
- [ ] New PHPStan/Larastan findings are fixed instead of added to the baseline unless explicitly justified.

## Documentation

- [ ] Documentation updated.
- [ ] Playbook still valid.
- [ ] ADR affected?
- [ ] Knowledge affected?

# Common Mistakes

- Committing before running focused tests.
- Leaving debug statements.
- Adding frontend labels without translations.
- Forgetting backend authorization because the menu item is hidden.
- Updating inventory without stock movement workflow.

# Completion Criteria

- Checklist is complete or any exception is explicitly documented.
- Required tests and static checks have been run or skipped with reason.
- No unrelated files are included.
