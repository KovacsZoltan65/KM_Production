# 0015.5 project stabilization audit

## Scope

This stabilization restores database portability, dependency security, E2E
reliability, and quality-gate orchestration after 0015. It introduces no 0016
business behavior and changes no MRP or procurement calculation rule.

## Migration policy decision

The affected 0013-era migrations are still part of the active feature branch,
and repository history contains no evidence that they were deployed to a
production database. Their forward schema is correct; the defects exist only
in `down()` and prevent the repository's required full rollback contract.

A new forward corrective migration cannot repair a later full rollback that
must still execute the defective historical `down()` methods. The narrowly
scoped decision is therefore to repair those methods in place:

- use Laravel's column-based foreign-key drop on SQLite where dropping a named
  foreign key is unsupported;
- drop MySQL foreign keys before indexes that MySQL requires for those keys;
- preserve every `up()` column, index, foreign key, nullability, and delete
  policy unchanged.

This is a development-branch portability correction, not a production schema
rewrite. If deployment evidence is later discovered, release owners must
reassess the migration history before merging.

## Test database environment

The repository-owned MySQL 8.4 service is defined in `compose.testing.yml` and
publishes `127.0.0.1:33060`. It is intentionally not started by the gate. On the
audited workstation Docker CLI was unavailable, while a compatible WAMP MySQL
service and the guard-approved test database were available on port `3306`.
The documented `TEST_MYSQL_PORT=3306` override allowed the genuine MySQL gate
to run; it did not substitute SQLite.

## Dependency security changelog

| Package             | Source                                           |    Old |    New | Reason                                                          |
| ------------------- | ------------------------------------------------ | -----: | -----: | --------------------------------------------------------------- |
| `league/commonmark` | Laravel transitive runtime dependency            |  2.8.3 | 2.10.0 | Resolves the six advisories affecting versions below 2.9.0.     |
| `nette/schema`      | Transitive Composer dependency                   |  1.3.5 |  1.3.6 | Solver-selected patch update in the targeted CommonMark update. |
| `nanoid`            | Vite → PostCSS transitive development dependency | 3.3.17 | 3.3.18 | Resolves the high-severity predictable-generation advisory.     |

No direct dependency constraint changed, no audit finding was ignored, and no
force update was used.

## Quality-gate ownership

The Composer wrappers previously imposed a second 300-second process timeout
around a runner that already owns categorized timeouts and process cleanup.
The integration baseline was terminated by that outer limit. All `qa:*`
wrappers now disable only Composer's outer process timeout; the runner's
default, backend, frontend, Playwright, build, and PHPStan limits remain in
force and continue to distinguish timeout exit 124 from ordinary process/test
failure.

The integration baseline then passed 634 backend tests in 480.54 seconds but
the following two-worker jsdom suite exhausted the 240-second frontend
watchdog. Standalone full Vitest remained green in 93.63 seconds, and the
project's existing worker audit had already measured the one-worker `forks`
profile as stable with lower peak memory. Integration and full gates therefore
run their complete frontend/coverage step with one worker; smaller module and
affected selections retain the two-worker default. No timeout, assertion, or
retry policy was weakened.

## Production-plan E2E finding

Programmatic date fills opened two PrimeVue DatePicker overlays faster than
their transitions completed, leaving a calendar to intercept Save. This was a
test interaction race. The test now handles each field as a user would: wait
for its visible calendar, press Escape on that input, verify the calendar is
closed, then continue. There is no forced click and no application component
change.
