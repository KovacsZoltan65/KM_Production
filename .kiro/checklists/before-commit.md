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

- [ ] `npm audit` and `npm audit --omit=dev` both report zero vulnerabilities.
- [ ] Page titles use Inertia's `<Head>` and the centralized title formatter; do not add a second head-manager plugin.

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
- [ ] `composer test:backend:sqlite` passes.
- [ ] `composer test:backend:mysql` passes on a dedicated guarded test database.
- [ ] Cache-elt adatforrást módosító write műveletnél frissült a cache-mátrix és zöld a `composer test:cache`.
- [ ] SQLite and MySQL migration round-trip plus seeder smoke passes; see [backend quality gate](../../docs/backend-quality-gate.md).

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
- [ ] `composer validate --strict` passes.
- [ ] `vendor/bin/pint --test` passes.
- [ ] `composer analyse` passes.
- [ ] `git diff --check` passes.
- [ ] A PHPStan/Larastan hibák valódi javítást kapnak; baseline vagy széles ignore nem adható hozzá.

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
